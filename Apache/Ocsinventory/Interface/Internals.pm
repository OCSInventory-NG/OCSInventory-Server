###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2006
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface::Internals;

use Apache::Ocsinventory::Map;

use strict;

use constant CHECKSUM_MAX_VALUE => 131071;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
/;

sub search_engine{
# Available search engines
	my %search_engines = (
		'first'	=> \&engine_first
	);
	&{ $search_engines{ (lc $_[1]->{ENGINE}) } }( @_ );
}

sub engine_first {
	my ($request, $parsed_reques, $computers) = @_;
	my $parsed_request = XML::Simple::XMLin( $request, ForceArray => ['ID', 'TAG', 'USERID'], SuppressEmpty => 1 ) or die;
	my ($id, $name, $userid, $checksum, $tag);
	  
# Database ids criteria
  if( $parsed_request->{ID} ){
  	$id .= ' AND';
		$id .= ' hardware.ID IN('.join(',', @{ $parsed_request->{ID} }).')';
	}
# Tag criteria
	if( $parsed_request->{TAG} ){
		s/^(.*)$/\"$1\"/ for @{ $parsed_request->{TAG} };
		$tag .= ' AND';
		$tag .= ' accountinfo.TAG IN('.join(',', @{ $parsed_request->{TAG} }).')';
	}
# Checksum criteria (only positive "&" will match
	if( $parsed_request->{CHECKSUM} ){
		$checksum = ' AND ('.$parsed_request->{CHECKSUM}.' & hardware.CHECKSUM)';
	}
# Associated user criteria
	if( $parsed_request->{USERID} ){
		s/^(.*)$/\"$1\"/ for @{ $parsed_request->{USERID} };
		$userid .= ' AND';
		$userid .= ' hardware.USERID IN('.join(',', @{ $parsed_request->{USERID} } ).')';
	}
# Generate sql string
  my $search_string = "SELECT DISTINCT hardware.ID FROM hardware,accountinfo WHERE hardware.ID=accountinfo.HARDWARE_ID $id $name $userid $checksum $tag";
# Play it	
	my $sth = get_sth($search_string);
# Get ids
	while( my $row = $sth->fetchrow_hashref() ){
		push @{$computers}, $row->{ID};
	}
# Destroy request object
  $sth->finish();
}

# Database connection
sub database_connect{
	my $dbHost;
	my $dbName;
	my $dbPort;
	my $dbUser;
	my $dbPwd;
	
	if($ENV{'OCS_DB_SL_HOST'}){
	  $dbHost = $ENV{'OCS_DB_SL_HOST'};
	  $dbName = $ENV{'OCS_DB_SL_NAME'} || 'ocsweb';
	  $dbPort = $ENV{'OCS_DB_SL_PORT'} || '3306';
	  $dbUser = $ENV{'OCS_DB_SL_USER'};
	  $dbPwd  = $Apache::Ocsinventory::SOAP::apache_req->dir_config('OCS_DB_SL_PWD');
	}
	else{
  	  $dbHost = $ENV{'OCS_DB_HOST'};
	  $dbName = $ENV{'OCS_DB_NAME'} || 'ocsweb';
	  $dbPort = $ENV{'OCS_DB_PORT'} || '3306';
	  $dbUser = $ENV{'OCS_DB_USER'};
	  $dbPwd  = $Apache::Ocsinventory::SOAP::apache_req->dir_config('OCS_DB_PWD');
	}
	
	return DBI->connect( "DBI:mysql:database=$dbName;host=$dbHost;port=$dbPort" );
}
# Process the sql requests (prepare)
sub get_sth {
	my ($sql, @values) = @_;
	my $dbh = database_connect();
	my $request = $dbh->prepare( $sql );
	$request->execute( @values ) or die;
	return $request;
}

# Process the sql requests (do)
sub do_sql {
	my ($sql, @values) = @_;
	my $dbh = database_connect();
	return $dbh->do( $sql, {}, @values );
}

# Build whole inventory (sections specified using checksum)
sub build_xml_inventory {
	my ($computer, $checksum, $wanted) = @_;
	my %xml;
	my %special_sections = (
		accountinfo => 1,
 		dico_soft => 2
	);
# Whole inventory by default
	$checksum = CHECKSUM_MAX_VALUE unless $checksum=~/\d+/;
# Build each section using ...standard_section
	for( keys(%DATA_MAP) ){
		if( ($checksum & $DATA_MAP{$_}->{mask} ) ){
			&build_xml_standard_section($computer, \%xml, $_) or die;
		}
	}
# Accountinfos
	for( keys( %special_sections ) ){
		&build_xml_special_section($computer, \%xml, $_) if $special_sections{$_} & $wanted;
	}
# Return the xml response to interface
	return XML::Simple::XMLout( \%xml, 'RootName' => 'COMPUTER' ) or die;
}
# Build metadata of a computer
sub build_xml_meta {
	my $id = shift;
	my %xml;
# For mapped fields
	my @mapped_fields = qw / NAME TAG DEVICEID LASTDATE LASTCOME CHECKSUM DATABASEID/;
# For others
	my @other_fields = qw //;
	
	my $sql_str = qq/
		SELECT 
			hardware.DEVICEID AS DEVICEID,
			hardware.LASTDATE AS LASTDATE,
			hardware.LASTCOME AS LASTCOME,
			hardware.checksum AS CHECKSUM,
			hardware.ID AS DATABASEID,
			hardware.NAME AS NAME,
			accountinfo.TAG AS TAG
		FROM hardware,accountinfo
		WHERE accountinfo.HARDWARE_ID=hardware.ID
		AND ID=?
	/;
	my $sth = get_sth( $sql_str, $id);
	while( my $row = $sth->fetchrow_hashref ){
		for( @mapped_fields ){
			$xml{ $_ }=[$row->{ $_ }];
		}
	}
	$sth->finish;
	return XML::Simple::XMLout( \%xml, 'RootName' => 'COMPUTER' ) or die;
}
# Build a database mapped inventory section
sub build_xml_standard_section{
	my ($id, $xml_ref, $section) = @_;
	my %element;
	my @tmp;
# Request database
	my $deviceid = get_table_pk($section);
	my $sth = get_sth("SELECT * FROM $section WHERE $deviceid=?", $id);
	
# Build data structure...
	while ( my $row = $sth->fetchrow_hashref() ){		
		for( keys(%{$DATA_MAP{ $section }->{fields}}) ){
			next if $DATA_MAP{ $section }->{fields}->{$_}->{noSql};
			$element{$_} = [ $row->{ $_ } ];
		}
		
		push @tmp, { %element };
		%element = ();
	}
	$section =~ s/$section/\U$&/g;
	$xml_ref->{$section}=[ @tmp ];
	@tmp = ();
	$sth->finish;
}

# For non-standard sections
sub build_xml_special_section {
	my ($id, $xml_ref, $section) = @_;
# Accountinfos retrieving
	if($section eq 'accountinfo'){
		my %element;
		my @tmp;
	# Request database
		my $sth = get_sth('SELECT * FROM accountinfo WHERE HARDWARE_ID=?', $id);
	# Build data structure...
		my $row = $sth->fetchrow_hashref();
		for( keys( %$row ) ){
			next if $_ eq get_table_pk('accountinfo');
			push @tmp, { Name => $_ ,  content => $row->{ $_ } };
		}
		$xml_ref->{'ACCOUNTINFO'}{'ENTRY'} = [ @tmp ];
		$sth->finish;
	}
	elsif($section eq 'dico_soft'){
		my @tmp;
		my $sth = get_sth('SELECT dico_soft.FORMATTED AS FORMAT FROM softwares,dico_soft WHERE HARDWARE_ID=? AND EXTRACTED=NAME', $id);
		while( my $row = $sth->fetchrow_hashref() ){
			push @tmp, $row->{FORMAT};
		}
		$xml_ref->{'DICO_SOFT'}{WORD} = [ @tmp ];
		$sth->finish;
	}
	
	
}
# Return the id field of an inventory section
sub get_table_pk{
	my $section = shift;
	return ($section eq 'hardware')?'ID':'HARDWARE_ID';
}

# Return a config value
sub ocs_config_read{
	my $key = shift;
	my $sth = get_sth('SELECT IVALUE,TVALUE FROM config WHERE NAME=?', $key);
	unless($sth->rows){
		$sth->finish();
		return undef;	
	}
	my ($i,$t) = $sth->fetchrow_array();
	$sth->finish();
	return defined($i)?$i:$t;
}

# Set a config value in "config" table
# If ocs GUI is not used,
# you have to change parameters in ocsinventory.conf
sub ocs_config_write{
	my( $key, $value ) = @_;
	my @parameters_t = (qw//);
	my @parameters_i = (qw/ 
		FREQUENCY
		PROLOG_FREQ
		DEPLOY
		TRACE_DELETED
		AUTO_DUPLICATE_LVL
		LOGLEVEL
		INVENTORY_DIFF
		INVENTORY_TRANSACTION
		PROXY_REVALIDATE_DELAY
		IPDISCOVER
		IPDISCOVER_MAX_ALIVE
		REGISTRY
		UPDATE
		DOWNLOAD
		DOWNLOAD_FRAG_LATENCY
		DOWNLOAD_CYCLE_LATENCY
		DOWNLOAD_PERIOD_LATENCY
		DOWNLOAD_TIMEOUT
	/);
	
	my $type='TVALUE';
	$type='IVALUE' if grep /$key/,@parameters_i;
	get_sth("UPDATE config SET ".$type."=? WHERE NAME=?", $value, $key )->finish();
}

# Return software name alias
sub get_dico_soft_extracted{
	my $extracted = shift;
	my $sth = get_sth('SELECT FORMATTED FROM dico_soft WHERE EXTRACTED=?', $extracted);
	unless($sth->rows){
		$sth->finish();
		return undef;	
	}
	my ($formatted) = $sth->fetchrow_array;
	$sth->finish();
	return $formatted;
}

# To get the computer's history
sub get_history_events {
	my( $begin, $num ) = @_;
	my @tmp;
	my $sth = get_sth( "SELECT DATE,DELETED,EQUIVALENT FROM deleted_equiv ORDER BY DATE LIMIT $begin,$num" );
		
	while( my $row = $sth->fetchrow_hashref() ){
		push @tmp, {
				'DELETED' => [ $row->{'DELETED'} ],
				'DATE' => [ $row->{'DATE'} ],
				'EQUIVALENT' => [ $row->{'EQUIVALENT'} ]
		}
	}
	$sth->finish();
	return XML::Simple::XMLout( {'EVENT' => \@tmp} , RootName => 'EVENTS' );
}

sub clear_history_events {
	my( $begin, $num ) = @_;
	my $sth = get_sth( "SELECT * FROM deleted_equiv ORDER BY DATE LIMIT $begin,$num" );
	while( my $row = $sth->fetchrow_hashref() ) {
		do_sql('DELETE FROM deleted_equiv WHERE DELETED=? AND DATE=? AND EQUIVALENT=?', $row->{'DELETED'}, $row->{'DATE'}, $row->{'EQUIVALENT'}) or die;
	}
	return 1;
}

sub reset_checksum {
	my( $checksum, $ref ) = @_;
	my $where = join(',', @$ref);
	return do_sql("UPDATE hardware SET CHECKSUM=? WHERE ID IN ($where)", $checksum);
}
1;








