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
use Apache::Ocsinventory::Server::System qw /:server _modules_get_pre_inventory_options _modules_get_post_inventory_options/;
use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::Duplicate;


my $DeviceID;
my @accountkeys;
my $update;
my $result;
my $data;
my $dbh;

#To apply to $checksum with an OR
our %mask = (
	'hardware' 	=> 1,
	'bios'		=> 2,
	'memories'	=> 4,
	'slots'		=> 8,
	'registry'	=> 16,
	'controllers'	=> 32,
	'monitors'	=> 64,
	'ports'		=> 128,
	'storages'	=> 256,
	'drives'	=> 512,
	'inputs'	=> 1024,
	'modems'	=> 2048,
	'networks'	=> 4096,
	'printers'	=> 8192,
	'sounds'	=> 16384,
	'videos'	=> 32768,
	'softwares'	=> 65536
);

our %XML_PARSER_OPT = (
	'ForceArray' => ['INPUTS', 'CONTROLLERS', 'MEMORIES', 'MONITORS', 'PORTS','SOFTWARES', 'STORAGES', 'DRIVES', 'INPUTS', 'MODEMS', 'NETWORKS', 'PRINTERS', 'SLOTS', 'SOUNDS', 'VIDEOS', 'PROCESSES', 'ACCOUNTINFO']
);

#Proceed with inventory
#######################
#
# Subroutine called for an incoming inventory
sub _inventory_handler{

	# Initialize data
	$dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
	$data = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATA'};
	undef @accountkeys;
			
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
	}else{
		$Apache::Ocsinventory::CURRENT_CONTEXT{'LOCK_FL'} = 1;
	}

	# Put the inventory in the database
	return APACHE_SERVER_ERROR if
	# To know more about the situation (update, new machine...)
	&_hardware()
	or &_accountinfo()
	or &_accesslog()
	or &_bios()
	or &_memories()
	or &_slots()
	or &_controllers()
	or &_monitors()
	or &_ports()
	or &_storages()
	or &_drives()
	or &_inputs()
	or &_modems()
	or &_networks()
	or &_printers()
	or &_sounds()
	or &_videos()
	or &_softwares();

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


# Inserting values of <ACCESSLOG> in accesslog table
sub _accesslog{
	if($update){
		if(!$dbh->do('DELETE FROM accesslog WHERE HARDWARE_ID=?', {},$DeviceID)){
			return(1);
		}
	}
	if(!$dbh->do('INSERT INTO accesslog(HARDWARE_ID, USERID, LOGDATE, PROCESSES) VALUES (?, ?, ?, ?)', {}, $DeviceID, $result->{CONTENT}->{ACCESSLOG}->{USERID}, $result->{CONTENT}->{ACCESSLOG}->{LOGDATE}, $result->{CONTENT}->{ACCESSLOG}->{PROCESSES})){
		return(1);
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;

}
# Inserting values of <BIOS> in bios table
sub _bios{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('bios');
		}
		if(!$dbh->do('DELETE FROM bios WHERE HARDWARE_ID=?', {},$DeviceID)){
			return(1);
		}
	}
	
	if(!$dbh->do('INSERT INTO bios(HARDWARE_ID, SMANUFACTURER, SMODEL, SSN, TYPE, BMANUFACTURER, BVERSION, BDATE) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', {}, $DeviceID, $result->{CONTENT}->{BIOS}->{SMANUFACTURER}, $result->{CONTENT}->{BIOS}->{SMODEL}, $result->{CONTENT}->{BIOS}->{SSN}, $result->{CONTENT}->{BIOS}->{TYPE}, $result->{CONTENT}->{BIOS}->{BMANUFACTURER}, $result->{CONTENT}->{BIOS}->{BVERSION}, $result->{CONTENT}->{BIOS}->{BDATE})){
		return(1);
	}
	
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

# Inserting values of <MEMORIES> in memories table
sub _memories{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('memories');
		}		
		if(!$dbh->do('DELETE FROM memories WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO memories(HARDWARE_ID, CAPTION, DESCRIPTION, CAPACITY, PURPOSE, TYPE, SPEED, NUMSLOTS) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{MEMORIES};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{CAPTION}, $_->{DESCRIPTION}, $_->{CAPACITY}, $_->{PURPOSE}, $_->{TYPE}, $_->{SPEED}, $_->{NUMSLOTS})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <SLOTS> in slots table
sub _slots{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('slots');
		}
		if(!$dbh->do('DELETE FROM slots WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO slots(HARDWARE_ID, NAME, DESCRIPTION, DESIGNATION, PURPOSE, STATUS, PSHARE) VALUES(?, ?, ?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{SLOTS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{NAME}, $_->{DESCRIPTION}, $_->{DESIGNATION}, $_->{PURPOSE}, $_->{STATUS}, $_->{SHARED})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <CONTROLLERS> in controllers table
sub _controllers{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('controllers');
		}
		if(!$dbh->do('DELETE FROM controllers WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO controllers(HARDWARE_ID, MANUFACTURER, NAME, CAPTION, DESCRIPTION, VERSION, TYPE) VALUES(?, ?, ?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{CONTROLLERS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{MANUFACTURER}, $_->{NAME}, $_->{CAPTION}, $_->{DESCRIPTION},  $_->{VERSION}, $_->{TYPE})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <MONITORS> in monitors table
sub _monitors{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('monitors');
		}
		if(!$dbh->do('DELETE FROM monitors WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO monitors(HARDWARE_ID, MANUFACTURER, CAPTION, DESCRIPTION, TYPE, SERIAL) VALUES(?, ?, ?, ?, ?, ?)');	
	my $array = $result->{CONTENT}->{MONITORS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{MANUFACTURER}, $_->{CAPTION}, $_->{DESCRIPTION}, $_->{TYPE}, $_->{SERIAL})){
			return(1);
		}	
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <PORTS> in ports table
sub _ports{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('ports');
		}
		if(!$dbh->do('DELETE FROM ports WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO ports(HARDWARE_ID, TYPE, NAME, CAPTION, DESCRIPTION) VALUES(?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{PORTS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{TYPE}, $_->{NAME}, $_->{CAPTION}, $_->{DESCRIPTION})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <STORAGES> in storages table
sub _storages{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('storages');
		}
		if(!$dbh->do('DELETE FROM storages WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO storages(HARDWARE_ID, MANUFACTURER, NAME, MODEL, DESCRIPTION, TYPE, DISKSIZE) VALUES(?, ?, ?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{STORAGES};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{MANUFACTURER}, $_->{NAME}, $_->{MODEL}, $_->{DESCRIPTION}, $_->{TYPE}, $_->{DISKSIZE})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <DRIVES> in drives table
sub _drives{
	if($update){
# 		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
# 			return(0) unless _has_changed('drives');
# 		}
		if(!$dbh->do('DELETE FROM drives WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO drives(HARDWARE_ID, LETTER, TYPE, FILESYSTEM, TOTAL, FREE, NUMFILES, VOLUMN) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{DRIVES};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{LETTER}, $_->{TYPE}, $_->{FILESYSTEM}, $_->{TOTAL}, $_->{FREE}, $_->{NUMFILES}, $_->{VOLUMN})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <INPUTS> in inputs table
sub _inputs{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('inputs');
		}
		if(!$dbh->do('DELETE FROM inputs WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO inputs(HARDWARE_ID, TYPE, MANUFACTURER, CAPTION, DESCRIPTION, INTERFACE, POINTTYPE) VALUES(?, ?, ?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{INPUTS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{TYPE}, $_->{MANUFACTURER}, $_->{CAPTION}, $_->{DESCRIPTION}, $_->{INTERFACE}, $_->{POINTTYPE})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <MODEMS> in modems table
sub _modems{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('modems');
		}
		if(!$dbh->do('DELETE FROM modems WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO modems(HARDWARE_ID, NAME, MODEL, DESCRIPTION, TYPE) VALUES(?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{MODEMS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{NAME}, $_->{MODEL}, $_->{DESCRIPTION}, $_->{TYPE})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <NETWORKS> in networks table
sub _networks{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('networks');
		}
		if(!$dbh->do('DELETE FROM networks WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO networks(HARDWARE_ID, DESCRIPTION, TYPE, TYPEMIB, SPEED, MACADDR, STATUS, IPADDRESS, IPMASK, IPGATEWAY, IPSUBNET, IPDHCP) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{NETWORKS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{DESCRIPTION}, $_->{TYPE}, $_->{TYPEMIB}, $_->{SPEED}, $_->{MACADDR}, $_->{STATUS}, $_->{IPADDRESS}, $_->{IPMASK}, $_->{IPGATEWAY}, $_->{IPSUBNET}, $_->{IPDHCP})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <PRINTERS> in printers table
sub _printers{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('printers');
		}
		if(!$dbh->do('DELETE FROM printers WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO printers(HARDWARE_ID, NAME, DRIVER, PORT) VALUES(?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{PRINTERS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{NAME}, $_->{DRIVER}, $_->{PORT})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <SOUNDS> in sounds table
sub _sounds{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('sounds');
		}
		if(!$dbh->do('DELETE FROM sounds WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO sounds(HARDWARE_ID, MANUFACTURER, NAME, DESCRIPTION) VALUES(?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{SOUNDS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{MANUFACTURER}, $_->{NAME}, $_->{DESCRIPTION})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}

# Inserting values of <VIDEOS> in videos table
sub _videos{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('videos');
		}
		if(!$dbh->do('DELETE FROM videos WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO videos(HARDWARE_ID, NAME, CHIPSET, MEMORY, RESOLUTION) VALUES(?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{VIDEOS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{NAME}, $_->{CHIPSET}, $_->{MEMORY}, $_->{RESOLUTION})){
			return(1);
		}
	}
	
	$dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
	0;
}
# Inserting values of <SOFTWARES> in softwares table
sub _softwares{
	if($update){
		if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
			return(0) unless _has_changed('softwares');
		}
		if(!$dbh->do('DELETE FROM softwares WHERE HARDWARE_ID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $sth = $dbh->prepare('INSERT INTO softwares(HARDWARE_ID, PUBLISHER, NAME, VERSION, FOLDER, COMMENTS, FILENAME, FILESIZE, SOURCE) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
	my $array = $result->{CONTENT}->{SOFTWARES};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{PUBLISHER}, $_->{NAME}, $_->{VERSION}, $_->{FOLDER}, $_->{COMMENTS}, $_->{FILENAME}, $_->{FILESIZE}, $_->{SOURCE})){
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
		return($mask{$section} & $result->{CONTENT}->{HARDWARE}->{CHECKSUM});
	}else{
		return(1);
	}
}
1;
