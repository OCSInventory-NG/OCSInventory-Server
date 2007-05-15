###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Inventory;

use strict;

BEGIN{
	if($ENV{'OCS_MODPERL_VERSION'} == 1){
		require Apache::Ocsinventory::Server::Modperl1;
		Apache::Ocsinventory::Server::Modperl1->import();
	}elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
		require Apache::Ocsinventory::Server::Modperl2;
		Apache::Ocsinventory::Server::Modperl2->import();
	}
}

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /_inventory_handler/;

use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Server::System qw /
	:server 
	_modules_get_pre_inventory_options 
	_modules_get_post_inventory_options
/;
use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::Duplicate;
use Apache::Ocsinventory::Map;

my $DeviceID;
my @accountkeys;
my $update;
my $result;
my $dbh;
my @multiSections;
my $initCache=0;

# Loading the section's hash
my %SECTIONS;
# To avoid many "keys"
my @SECTIONS;

&_init_map;

our %XML_PARSER_OPT = (
	'ForceArray' => [ @multiSections ]
);

sub _init_map{

	my $section;
	my @bind_num;
	my $field;
	my $fields_string;

# Parse every section
	for $section (keys(%DATA_MAP)){
# Field array (from data_map field hash keys), filtered fields and cached fields
		$SECTIONS{$section}->{field_arrayref} = [];
		$SECTIONS{$section}->{field_filtered} = [];
		$SECTIONS{$section}->{field_cached} = [];
##############################################
		
# Feed the multilines section array in order to parse xml correctly
		push @multiSections, uc $section if $DATA_MAP{$section}->{multi};
# Don't process the non-auto-generated sections
		next if !$DATA_MAP{$section}->{auto};
# Parse fields of the current section
		for $field ( keys(%{$DATA_MAP{$section}->{fields}} ) ){
			if(!$DATA_MAP{$section}->{fields}->{$field}->{noSql}){
				push @{$SECTIONS{$section}->{field_arrayref}}, $field;
				$SECTIONS{$section}->{noSql} = 1 unless $SECTIONS{$section}->{noSql};
			}
			if($DATA_MAP{$section}->{fields}->{$field}->{filter}){
				next unless $ENV{OCS_OPT_INVENTORY_FILTER_ENABLED};
				push @{$SECTIONS{$section}->{field_filtered}}, $field;
				$SECTIONS{$section}->{filter} = 1 unless $SECTIONS{$section}->{filter};
			}
			if($DATA_MAP{$section}->{fields}->{$field}->{cache}){
				next unless $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED};
				push @{$SECTIONS{$section}->{field_cached}}, $field;
				$SECTIONS{$section}->{cache} = 1 unless $SECTIONS{$section}->{cache};
			}
		}	
# Build the "DBI->prepare" sql string 
		$fields_string = join ',', ('HARDWARE_ID', @{$SECTIONS{$section}->{field_arrayref}});
		$SECTIONS{$section}->{sql_string} = "INSERT INTO $section($fields_string) VALUES(";
		for(0..@{$SECTIONS{$section}->{field_arrayref}}){
			push @bind_num, '?';
		}
		$SECTIONS{$section}->{sql_string}.= (join ',', @bind_num).')';
		@bind_num = ();
# To avoid calling keys every times
		push @SECTIONS, $section;
######################################
	}

}

#Proceed with inventory
#######################
#
# Subroutine called for an incoming inventory
sub _inventory_handler{

# Initialize data
	$dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
	undef @accountkeys;
	&_init_inventory_cache() if !$initCache && $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED};
			
	$result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
# Ref to xml in global struct 
	$Apache::Ocsinventory::CURRENT_CONTEXT{'XML_INVENTORY'} = $result;

#Inventory incoming
	&_log(104,'inventory','Incoming') if $ENV{'OCS_OPT_LOGLEVEL'};

# Call to preinventory handlers
	if( &_pre_options() == INVENTORY_STOP ){
		&_log(107,'inventory','stopped by module') if $ENV{'OCS_OPT_LOGLEVEL'};
		return APACHE_FORBIDDEN;
}
	
	return APACHE_SERVER_ERROR if &_context();
	
# Lock device
	if(&_lock($Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'})){
		&_log( 516, 'inventory', 'device locked');
		return(APACHE_FORBIDDEN);
	}

# Put the inventory in the database
	return APACHE_SERVER_ERROR if &_update_inventory();

#Committing inventory
	$dbh->commit;
#Call to post inventory handlers
	&_post_options();

#############
# Manage several questions, including duplicates
	&_post_inventory();
	
# That's all
	&_log(101,'inventory','Transmitted') if $ENV{'OCS_OPT_LOGLEVEL'};
	return APACHE_OK;
}

sub _context{
	
	if($Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'}){
		$DeviceID = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
		$update = 1;
	}else{
		$dbh->do('INSERT INTO hardware(DEVICEID) VALUES(?)', {}, $Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'}) or return(1);
#unless($Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'} = $dbh->last_insert_id(undef,undef,'hardware', 'ID')){
			my $request = $dbh->prepare('SELECT ID FROM hardware WHERE DEVICEID=?');
			unless($request->execute($Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'})){
				&_log(518,'inventory','ID error') if $ENV{'OCS_OPT_LOGLEVEL'};
				return(1);
	}
			my $row = $request->fetchrow_hashref;
			$Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'} = $row->{'ID'};
			$DeviceID = $row->{'ID'};
#}
		$update=0;
	}
	return(0);
}

sub _update_inventory{
my $section;

# Call special sections update
	if(&_hardware() or &_accountinfo){
		return 1;
	}
# Call the _update_inventory_section for each section
	for $section (@SECTIONS){
		if(_update_inventory_section($section)){
			return 1;
		}
	}
}

sub _update_inventory_section{
	my $section = shift;
	my @bind_values;
	
# The computer exists. 
# We check if this section has changed since the last inventory (only if activated)
# We delete the previous entries
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed($section);
		}
		if($DATA_MAP{$section}->{delOnReplace}){
			if(!$dbh->do("DELETE FROM $section WHERE HARDWARE_ID=?", {}, $DeviceID)){
				return(1);
			}
		}
	}
# Call the filter
# &_inventory_filter($section);
# Call the cache if needed
	&_inventory_cache($section,0) if $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED};

# Processing values	
	my $sth = $dbh->prepare( $SECTIONS{$section}->{sql_string} );
# Multi lines (forceArray)
	my $ref = $result->{CONTENT}->{uc $section};
	if($DATA_MAP{$section}->{multi}){
		for my $line (@$ref){
			&_get_bind_values($section, $line, \@bind_values);
			if(!$sth->execute($DeviceID, @bind_values)){
				return(1);
			}
			@bind_values = ();
		}
	}
# One line (hash)
	else{
		&_get_bind_values($section, $ref, \@bind_values);
		if( !$sth->execute($DeviceID, @bind_values) ){
				return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

sub _get_bind_values{
	my ($section, $ref, $arrayref) = @_;
	for ( @{ $SECTIONS{$section}->{field_arrayref} } ) {
		if(defined($ref->{$_})){
			push @$arrayref, $ref->{$_};
		}
		else{
				push @$arrayref, '';
		}
	}
}

# Called for each section
# Filter value for each field "filter" activated
#TODO:Apache::Ocsinventory::Server::DataFilter.pm 
#sub _inventory_filter{
#	my $section = shift;
#	return unless $SECTIONS{$section}->{filter};
#There is at least one field to filter
#	my $fields_array = $SECTIONS{$section}->{field_filtered};
#}


sub _init_inventory_cache{
	my $check_cache = $dbh->prepare('SELECT UNIX_TIMESTAMP(NOW())-TVALUE AS TVALUE FROM config WHERE NAME="_EP_INVENTORY_CACHE_CLEAN_DATE"');
	$check_cache->execute();
	if($check_cache->rows()){
		my $row = $check_cache->fetchrow_hashref();
		if($row->{TVALUE}<($ENV{OCS_OPT_INVENTORY_CACHE_REVALIDATE}?$ENV{OCS_OPT_INVENTORY_CACHE_REVALIDATE}:3600)){
			$initCache = 1;
			return;
		}
	}
	
	for my $section (keys(%DATA_MAP)){
		_inventory_cache($section, 1);
	}
	&_log(108,'inventory','Cache') if $ENV{'OCS_OPT_LOGLEVEL'};
	$dbh->do('INSERT INTO config(NAME,TVALUE) VALUES("_EP_INVENTORY_CACHE_CLEAN_DATE", UNIX_TIMESTAMP(NOW()))')
		if($dbh->do('UPDATE config SET TVALUE=UNIX_TIMESTAMP(NOW()) WHERE NAME="_EP_INVENTORY_CACHE_CLEAN_DATE"')==0E0);
	$initCache = 1;
}

# Called for each section
# Feed the "cache" table for each field "cache" activated
sub _inventory_cache{
	my ($section, $init) = @_;
	return unless $SECTIONS{$section}->{cache};
	
	my $base = $result->{CONTENT}->{uc $section};
	my $fields_array = $SECTIONS{$section}->{field_cached};
# There is some cache to generate
	for my $field (@$fields_array){
		my $table = $section.'_'.lc $field.'_cache';
		if($init){
			my $src_table = lc $section;
			my $to_clean = $dbh->prepare(qq{
				SELECT c.$field AS $field
				FROM $table c
				LEFT JOIN $src_table src
				ON c.$field=src.$field
				WHERE src.$field IS NULL FOR UPDATE
			});
			$to_clean->execute();
			while(my $row = $to_clean->fetchrow_hashref()){
				$dbh->do("DELETE FROM $table WHERE $field=?", {}, $row->{$field});
			}
			$dbh->do('UNLOCK TABLES');
			next;
		}
# Prepare queries
		my $select = $dbh->prepare("SELECT $field FROM $table WHERE $field=? FOR UPDATE");
		my $insert = $dbh->prepare("INSERT INTO $table($field) VALUES(?)");
# hash ref or array ref ?
		if($DATA_MAP{$section}->{multi}){
			for(@$base){
				$select->execute($_->{$field});
# Value is already in the cache
				if($select->rows){
					$select->finish;
					$dbh->do('UNLOCK TABLES');
					next;
				}
# We have to insert the value
				$insert->execute($_->{$field});
				$dbh->do('UNLOCK TABLES');
			}
		}
		else{
			$select->execute($base->{$field});
			if($select->rows){
					$select->finish;
					$dbh->do('UNLOCK TABLES');
					next;
			}
			$insert->execute($_->{$field});
			$dbh->do('UNLOCK TABLES');
		}
	}
}

# Inserting values of <HARDWARE> in hardware table
sub _hardware{
	my $base = $result->{CONTENT}->{HARDWARE};
	my $ua = _get_http_header('User-agent', $Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'});
	# We replace all data but quality and fidelity. The last come becomes the last date.
	my $userid = '';
	$userid = "USERID=".$dbh->quote($base->{USERID})."," if( $base->{USERID}!~/(system|localsystem)/i );

$dbh->do("UPDATE hardware SET USERAGENT=".$dbh->quote($ua).", 
		LASTDATE=NOW(), 
		LASTCOME=NOW(),
		CHECKSUM=(".(defined($base->{CHECKSUM})?$base->{CHECKSUM}:CHECKSUM_MAX_VALUE)."|CHECKSUM), 
		NAME=".$dbh->quote($base->{NAME}).", 
		WORKGROUP=".$dbh->quote($base->{WORKGROUP}).",
		USERDOMAIN=".$dbh->quote($base->{USERDOMAIN}).",
		OSNAME=".$dbh->quote($base->{OSNAME}).",
		OSVERSION=".$dbh->quote($base->{OSVERSION}).",
		OSCOMMENTS=".$dbh->quote($base->{OSCOMMENTS}).",
		PROCESSORT=".$dbh->quote($base->{PROCESSORT}).", 
		PROCESSORS=".(defined($base->{PROCESSORS})?$base->{PROCESSORS}:0).", 
		PROCESSORN=".(defined($base->{PROCESSORN})?$base->{PROCESSORN}:0).", 
		MEMORY=".(defined($base->{MEMORY})?$base->{MEMORY}:0).",
		SWAP=".(defined($base->{SWAP})?$base->{SWAP}:0).",
		IPADDR=".$dbh->quote($base->{IPADDR}).",
		ETIME=".$dbh->quote($base->{ETIME}).",
		$userid
		TYPE=".(defined($base->{TYPE})?$base->{TYPE}:0).",
		DESCRIPTION=".$dbh->quote($base->{DESCRIPTION}).",
		WINCOMPANY=".$dbh->quote($base->{WINCOMPANY}).",
		WINOWNER=".$dbh->quote($base->{WINOWNER}).",
		WINPRODID=".$dbh->quote($base->{WINPRODID}).",
		WINPRODKEY=".$dbh->quote($base->{WINPRODKEY})."
		 WHERE ID=".$DeviceID)
or return(1);
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <ACCOUNTINFO> in accountinfo table
sub _accountinfo{
	my $lost=shift;
# We have to look for the field's names because this table has a dynamic structure
	my ($row, $request, $accountkey);
# Fields names
	$request=$dbh->prepare('SHOW COLUMNS FROM accountinfo');
	$request->execute;
	while($row=$request->fetchrow_hashref){
					push @accountkeys, $row->{'Field'} if($row->{'Field'} ne 'HARDWARE_ID');
	}
	
	if(!$update or $lost){
# writing (if new id, but duplicate, it will be erased at the end of the execution)
		$dbh->do('INSERT INTO accountinfo(HARDWARE_ID) VALUES(?)', {}, $DeviceID);
# Now, we know what are the account info name fields
# We can insert the client's data. This data will be kept only one time, in the first inventory
		for $accountkey (@accountkeys){
			my $array = $result->{CONTENT}->{ACCOUNTINFO};
			for(@$array){
				if($_->{KEYNAME} eq $accountkey){
					if(!$dbh->do('UPDATE accountinfo SET '.$accountkey."=".$dbh->quote($_->{KEYVALUE}).' WHERE HARDWARE_ID='.$DeviceID)){
						return(1);
					}
				}
			}
		}
	}
	if($lost){
		if(!$dbh->do('UPDATE accountinfo SET TAG = "LOST" WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

sub _post_inventory{
	my $request;
	my $row;
	my $red;
	my $accountkey;
	my %elements;

	&_generate_ocs_file();
	
	$red = &_duplicate_main();
	# We verify accountinfo diff if the machine was already in the database
	if($update or $red){
		# We put back the account infos to the agent if necessary
		$request = $dbh->prepare('SELECT * FROM accountinfo WHERE HARDWARE_ID=?');
		$request->execute($DeviceID);
		if($row = $request->fetchrow_hashref()){
			my $up = 0;
			# Compare the account infos with the user's ones
			for $accountkey (@accountkeys){
				for(@{$result->{CONTENT}->{ACCOUNTINFO}}){
					if($_->{KEYNAME} eq $accountkey){
						$up=1,last if($_->{KEYVALUE} ne $row->{$accountkey});
					}
				}
			}
			# If there is something new in the table
			$up = 1 if(@accountkeys != @{$result->{CONTENT}->{ACCOUNTINFO}});
			
			if($up){
				# we write the xml data
				$elements{'RESPONSE'} = [ 'ACCOUNT_UPDATE' ];
				for(@accountkeys){
					push @{$elements{'ACCOUNTINFO'}}, { 'KEYNAME' => [ $_ ], 'KEYVALUE' => [ $row->{$_} ] };
				}
				$request->finish;
				
				# send the update to the client
				&_send_response(\%elements);
				return;
			}else{
				$request->finish;
				&_send_response({'RESPONSE' => [ 'NO_ACCOUNT_UPDATE' ]});
				return;
			}
		
		}else{
			# There is a problem. The device MUST be present in the table
			&_log(509,'postinventory','No account infos') if $ENV{'OCS_OPT_LOGLEVEL'};
			$request->finish;
			$elements{'RESPONSE'} = [ 'ACCOUNT_UPDATE' ];
			for(@{$result->{CONTENT}->{ACCOUNTINFO}}){
				if($_->{KEYNAME} eq 'TAG'){
			  		push @{$elements{'ACCOUNTINFO'}}, { 'KEYNAME' => [$_->{KEYNAME}], 'KEYVALUE' => [ 'LOST' ] };
				}else{
					push @{$elements{'ACCOUNTINFO'}}, { 'KEYNAME' => [ $_->{KEYNAME} ], 'KEYVALUE' => [ $_->{KEYVALUE} ] };
			  	}
			}
			# call accountinfo to insert agent values in addition to the LOST value in TAG
			&_accountinfo("lost");
			# send the update to the client with TAG=LOST
                        &_send_response(\%elements);
			return;
		}

	}else{
		&_send_response({'RESPONSE' => [ 'NO_ACCOUNT_UPDATE' ]});
		return;
	}
	0;
}

####################
# Generate .ocs file 
# Helpful to merge few servers
#
sub _generate_ocs_file{
	return if !$ENV{'OCS_OPT_GENERATE_OCS_FILES'};
	my $ocs_path = $ENV{'OCS_OPT_OCS_FILES_PATH'};
	my $ocs_file_name = $Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'};
	my $ocs_file = $ocs_path.'/'.$ocs_file_name.'.ocs';
	my $format;
	$format = 'ocs' unless $format = $ENV{'OCS_OPT_OCS_FILES_FORMAT'};
	
	if( !open FILE, ">$ocs_file" ){
		&_log(520,'postinventory',"$ocs_file: $!") if $ENV{'OCS_OPT_LOGLEVEL'};
	}
	else{
		if($format=~/^ocs$/i){
			print FILE ${$Apache::Ocsinventory::CURRENT_CONTEXT{'RAW_DATA'}};
		}
		elsif($format=~/^xml$/i){
			print FILE ${$Apache::Ocsinventory::CURRENT_CONTEXT{'DATA'}};
		}
		else{
			&_log(521,'postinventory','wrong file format') if $ENV{'OCS_OPT_LOGLEVEL'};
		}
		close(FILE);
	}
	return;
}

sub _pre_options{
	for(&_modules_get_pre_inventory_options()){
		last if $_== 0;
		if (&$_(\%Apache::Ocsinventory::CURRENT_CONTEXT) == INVENTORY_STOP){
			return INVENTORY_STOP;
		}
	}
}

sub _post_options{
	for(&_modules_get_post_inventory_options()){
		last if $_== 0;
		&$_(\%Apache::Ocsinventory::CURRENT_CONTEXT);
	}
}

sub _has_changed{
	my $section = shift;
	
	# Check checksum to know if section has changed
	if( defined($result->{CONTENT}->{HARDWARE}->{CHECKSUM}) ){
		return($DATA_MAP{$section}->{mask} & $result->{CONTENT}->{HARDWARE}->{CHECKSUM});
	}else{
		return(1);
	}
}
1;
