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
	search_engine 
	build_xml_inventory 
	build_xml_meta 
	ocs_config_write
	ocs_config_read
	get_dico_soft_extracted
/;

sub search_engine{
# Available search engines
	my %search_engines = (
		'first'	=> \&engine_first
	);
	&{ $search_engines{ $_[1]->{ENGINE} } }( @_ );
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
	my $cstr = "DBI:mysql:database=$ENV{OCS_DB_NAME};host=$ENV{OCS_DB_HOST};port=$ENV{OCS_DB_PORT}";
	
	return DBI->connect(
		$cstr, $ENV{OCS_DB_USER},
		$Apache::Ocsinventory::SOAP::apache_req->dir_config('OCS_DB_PWD')
	);
}
# Process the sql requests
sub get_sth {
	my ($sql, @values) = @_;
	my $dbh = database_connect();
	my $request = $dbh->prepare( $sql );
	$request->execute( @values ) or die;
	return $request;
}
# Build whole inventory (sections specified using checksum)
sub build_xml_inventory {
	my ($computer, $checksum) = @_;
	my %xml;
# Whole inventory by default
	$checksum = CHECKSUM_MAX_VALUE unless $checksum=~/\d+/;
# Build each section using ...standard_section
	for( keys(%data_map) ){
		if( ($checksum & $data_map{$_}->{mask} ) ){
			&build_xml_standard_section($computer, \%xml, $_) or die;
		}
	}
# Return the xml response to interface
	return XML::Simple::XMLout( \%xml, 'RootName' => 'COMPUTER' ) or die;
}
# Build metadata of a computer
sub build_xml_meta {
	my $id = shift;
	my %xml;
# For mapped fields
	my @mapped_fields = qw / DEVICEID LASTDATE LASTCOME CHECKSUM DATABASEID/;
# For others
	my @other_fields = qw //;
	
	my $sql_str = qq/
		SELECT 
			hardware.DEVICEID AS DEVICEID,
			hardware.LASTDATE AS LASTDATE,
			hardware.LASTCOME AS LASTCOME,
			hardware.checksum AS CHECKSUM,
			hardware.ID AS DATABASEID
		FROM hardware 
		WHERE ID=?
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
		for( @{ $data_map{ $section }->{fields} } ){
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
1;








