###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory;

use strict;

our %CURRENT_CONTEXT;

my $DeviceID;
my @accountkeys;
my $update;
my $result;
my $data;
my $dbh;

#Proceed with inventory
#######################
#
# Subroutine called for an incoming inventory
sub _inventory_handler{

	$dbh = $CURRENT_CONTEXT{'DBI_HANDLE'};
	$data = $CURRENT_CONTEXT{'DATA'};
	$DeviceID = $CURRENT_CONTEXT{'DEVICEID'};
	undef @accountkeys;
	
	# XML to Perl
	unless($result = XML::Simple::XMLin( $$data, SuppressEmpty => 1, ForceArray => ['INPUTS', 'CONTROLLERS', 'MEMORIES', 'MONITORS', 'PORTS','SOFTWARES', 'STORAGES', 'DRIVES', 'INPUTS', 'MODEMS', 'NETWORKS', 'PRINTERS', 'SLOTS', 'SOUNDS', 'VIDEOS', 'PROCESSES', 'ACCOUNTINFO'] )){
		&_log(507,'inventory') if $ENV{'OCS_OPT_LOGLEVEL'};
		return APACHE_BAD_REQUEST;
	}

	# writing deviceid in the mutex table
	if(&_lock($DeviceID)){
		&_log(516,'inventory') if $ENV{'OCS_OPT_LOGLEVEL'};
		return APACHE_FORBIDDEN;
	}else{
		$CURRENT_CONTEXT{'LOCK_FL'} = 1;
	}
	
	#Inventory incoming
	&_log(104,'inventory') if $ENV{'OCS_OPT_LOGLEVEL'};

	if(&_check_deviceid($DeviceID)){
		&_log(502,'inventory') if $ENV{'OCS_OPT_LOGLEVEL'};
		return(APACHE_BAD_REQUEST);
	}

	# Put the inventory in the database
	return APACHE_SERVER_ERROR if
	# To know more about the situation (update, new machine...)
	&_context()
	or &_hardware()
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

	&_options();

	#############
	# Manage several questions, including duplicates
	&_post_inventory();
	
	# That's all
	&_log(101,'inventory') if $ENV{'OCS_OPT_LOGLEVEL'};
	return APACHE_OK;
}

# We determine the context
sub _context{
	my $request;
	my $row;
	
	#####
	# Is DeviceID existing in the database ?
	$request = $dbh->prepare('SELECT DEVICEID FROM hardware WHERE DEVICEID=?');
	$request->execute($DeviceID);
	if($request->rows){
		#It exists, so we activate the update flag
		$CURRENT_CONTEXT{'EXIST_FL'} = 1;
		$update = 1;
	}else{
		$CURRENT_CONTEXT{'EXIST_FL'} = 0;
		$update = 0;
	}
	$request->finish;
	0;
}	

# Inserting values of <HARDWARE> in hardware table
sub _hardware{
	my $base = $result->{CONTENT}->{HARDWARE};
	my $ua = _get_http_header('User-agent');
	# We replace all data but quality and fidelity. The last come becomes the last date.
	if($update){
		if(!$dbh->do("UPDATE hardware SET USERAGENT=".$dbh->quote($ua).", 
						LASTDATE=NOW(), 
						LASTCOME=NOW(),
						CHECKSUM=(".(defined($base->{CHECKSUM})?$base->{CHECKSUM}:CHECKSUM_MAX_VALUE)."|CHECKSUM), 
						NAME=".$dbh->quote($base->{NAME}).", 
						WORKGROUP=".$dbh->quote($base->{WORKGROUP}).",
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
						USERID=".$dbh->quote($base->{USERID}).",
						TYPE=".(defined($base->{TYPE})?$base->{TYPE}:0).",
						DESCRIPTION=".$dbh->quote($base->{DESCRIPTION}).",
						WINCOMPANY=".$dbh->quote($base->{WINCOMPANY}).",
						WINOWNER=".$dbh->quote($base->{WINOWNER}).",
						WINPRODID=".$dbh->quote($base->{WINPRODID})."
						WHERE DEVICEID=".$dbh->quote($DeviceID)))
		{
			return(1);
		}
	}else{
	  	if(!$dbh->do('INSERT INTO hardware( DEVICEID, NAME, WORKGROUP, OSNAME, OSVERSION, OSCOMMENTS, PROCESSORT, PROCESSORS, PROCESSORN, MEMORY, SWAP, IPADDR, ETIME, LASTDATE, LASTCOME, QUALITY, FIDELITY, USERID, TYPE, DESCRIPTION, WINCOMPANY, WINOWNER, WINPRODID, USERAGENT, CHECKSUM ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', {}, $DeviceID, $base->{NAME}, $base->{WORKGROUP}, $base->{OSNAME}, $base->{OSVERSION}, $base->{OSCOMMENTS}, $base->{PROCESSORT}, $base->{PROCESSORS}, $base->{PROCESSORN}, $base->{MEMORY}, $base->{SWAP}, $base->{IPADDR}, $base->{ETIME}, 0, 1, $base->{USERID}, $base->{TYPE}, $base->{DESCRIPTION}, $base->{WINCOMPANY}, $base->{WINOWNER}, $base->{WINPRODID}, $ua, CHECKSUM_MAX_VALUE))
		{
			return(1);
		}
	}
	0;
}


# Inserting values of <ACCESSLOG> in accesslog table
sub _accesslog{
	if($update){
		if(!$dbh->do('DELETE FROM accesslog WHERE DEVICEID=?', {},$DeviceID)){
			return(1);
		}
	}
	if(!$dbh->do('INSERT INTO accesslog(DEVICEID, USERID, LOGDATE, PROCESSES) VALUES (?, ?, ?, ?)', {}, $DeviceID, $result->{CONTENT}->{ACCESSLOG}->{USERID}, $result->{CONTENT}->{ACCESSLOG}->{LOGDATE}, $result->{CONTENT}->{ACCESSLOG}->{PROCESSES})){
		return(1);
	}

}
# Inserting values of <BIOS> in bios table
sub _bios{
	if($update){
		if(!$dbh->do('DELETE FROM bios WHERE DEVICEID=?', {},$DeviceID)){
			return(1);
		}
	}
	
	if(!$dbh->do('INSERT INTO bios(DEVICEID, SMANUFACTURER, SMODEL, SSN, TYPE, BMANUFACTURER, BVERSION, BDATE) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', {}, $DeviceID, $result->{CONTENT}->{BIOS}->{SMANUFACTURER}, $result->{CONTENT}->{BIOS}->{SMODEL}, $result->{CONTENT}->{BIOS}->{SSN}, $result->{CONTENT}->{BIOS}->{TYPE}, $result->{CONTENT}->{BIOS}->{BMANUFACTURER}, $result->{CONTENT}->{BIOS}->{BVERSION}, $result->{CONTENT}->{BIOS}->{BDATE})){
		return(1);
	}

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
                push @accountkeys, $row->{'Field'} if($row->{'Field'} ne 'DEVICEID');
        }
	if(!$update or $lost){
		# writing (if new id, but duplicate, it will be erased at the end of the execution)
		$dbh->do('INSERT INTO accountinfo(DEVICEID) VALUES(?)', {}, $DeviceID);
		# Now, we know what are the account info name fields
		# We can insert the client's data. This data will be kept only one time, in the first inventory
		for $accountkey (@accountkeys){
			my $array = $result->{CONTENT}->{ACCOUNTINFO};
			for(@$array){
				if($_->{KEYNAME} eq $accountkey){
					if(!$dbh->do('UPDATE accountinfo SET '.$accountkey."=".$dbh->quote($_->{KEYVALUE}).' WHERE DEVICEID='.$dbh->quote($DeviceID))){
						return(1);
					}
				}
			}
		}
	}
	if($lost){
		if(!$dbh->do('UPDATE accountinfo SET TAG = "LOST" WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	0;
}

# Inserting values of <MEMORIES> in memories table
sub _memories{
	my $sth = $dbh->prepare('INSERT INTO memories(DEVICEID, CAPTION, DESCRIPTION, CAPACITY, PURPOSE, TYPE, SPEED, NUMSLOTS) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM memories WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{MEMORIES};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{CAPTION}, $_->{DESCRIPTION}, $_->{CAPACITY}, $_->{PURPOSE}, $_->{TYPE}, $_->{SPEED}, $_->{NUMSLOTS})){
			return(1);
		}
	}
	0;
}

# Inserting values of <SLOTS> in slots table
sub _slots{
	my $sth = $dbh->prepare('INSERT INTO slots(DEVICEID, NAME, DESCRIPTION, DESIGNATION, PURPOSE, STATUS, PSHARE) VALUES(?, ?, ?, ?, ?, ?, ?)');	
	
	if($update){
		if(!$dbh->do('DELETE FROM slots WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{SLOTS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{NAME}, $_->{DESCRIPTION}, $_->{DESIGNATION}, $_->{PURPOSE}, $_->{STATUS}, $_->{SHARED})){
			return(1);
		}
	}
	0;
}

# Inserting values of <CONTROLLERS> in controllers table
sub _controllers{
	my $sth = $dbh->prepare('INSERT INTO controllers(DEVICEID, MANUFACTURER, NAME, CAPTION, DESCRIPTION, VERSION, TYPE) VALUES(?, ?, ?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM controllers WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{CONTROLLERS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{MANUFACTURER}, $_->{NAME}, $_->{CAPTION}, $_->{DESCRIPTION},  $_->{VERSION}, $_->{TYPE})){
			return(1);
		}
	}
	0;
}

# Inserting values of <MONITORS> in monitors table
sub _monitors{
	my $sth = $dbh->prepare('INSERT INTO monitors(DEVICEID, MANUFACTURER, CAPTION, DESCRIPTION, TYPE, SERIAL) VALUES(?, ?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM monitors WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{MONITORS};
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{MANUFACTURER}, $_->{CAPTION}, $_->{DESCRIPTION}, $_->{TYPE}, $_->{SERIAL})){
			return(1);
		}	
	}
	0;
}

# Inserting values of <PORTS> in ports table
sub _ports{
	my $sth = $dbh->prepare('INSERT INTO ports(DEVICEID, TYPE, NAME, CAPTION, DESCRIPTION) VALUES(?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM ports WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{PORTS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{TYPE}, $_->{NAME}, $_->{CAPTION}, $_->{DESCRIPTION})){
			return(1);
		}
	}
	0;
}

# Inserting values of <STORAGES> in storages table
sub _storages{
	my $sth = $dbh->prepare('INSERT INTO storages(DEVICEID, MANUFACTURER, NAME, MODEL, DESCRIPTION, TYPE, DISKSIZE) VALUES(?, ?, ?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM storages WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{STORAGES};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{MANUFACTURER}, $_->{NAME}, $_->{MODEL}, $_->{DESCRIPTION}, $_->{TYPE}, $_->{DISKSIZE})){
			return(1);
		}
	}
	0;
}

# Inserting values of <DRIVES> in drives table
sub _drives{
	my $sth = $dbh->prepare('INSERT INTO drives(DEVICEID, LETTER, TYPE, FILESYSTEM, TOTAL, FREE, NUMFILES, VOLUMN) VALUES(?, ?, ?, ?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM drives WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{DRIVES};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{LETTER}, $_->{TYPE}, $_->{FILESYSTEM}, $_->{TOTAL}, $_->{FREE}, $_->{NUMFILES}, $_->{VOLUMN})){
			return(1);
		}
	}
	0;
}

# Inserting values of <INPUTS> in inputs table
sub _inputs{
	my $sth = $dbh->prepare('INSERT INTO inputs(DEVICEID, TYPE, MANUFACTURER, CAPTION, DESCRIPTION, INTERFACE, POINTTYPE) VALUES(?, ?, ?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM inputs WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{INPUTS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{TYPE}, $_->{MANUFACTURER}, $_->{CAPTION}, $_->{DESCRIPTION}, $_->{INTERFACE}, $_->{POINTTYPE})){
			return(1);
		}
	}
	0;
}

# Inserting values of <MODEMS> in modems table
sub _modems{
	my $sth = $dbh->prepare('INSERT INTO modems(DEVICEID, NAME, MODEL, DESCRIPTION, TYPE) VALUES(?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM modems WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{MODEMS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{NAME}, $_->{MODEL}, $_->{DESCRIPTION}, $_->{TYPE})){
			return(1);
		}
	}
	0;
}

# Inserting values of <NETWORKS> in networks table
sub _networks{
	my $sth = $dbh->prepare('INSERT INTO networks(DEVICEID, DESCRIPTION, TYPE, TYPEMIB, SPEED, MACADDR, STATUS, IPADDRESS, IPMASK, IPGATEWAY, IPSUBNET, IPDHCP) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM networks WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{NETWORKS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{DESCRIPTION}, $_->{TYPE}, $_->{TYPEMIB}, $_->{SPEED}, $_->{MACADDR}, $_->{STATUS}, $_->{IPADDRESS}, $_->{IPMASK}, $_->{IPGATEWAY}, $_->{IPSUBNET}, $_->{IPDHCP})){
			return(1);
		}
	}
	0;
}

# Inserting values of <PRINTERS> in printers table
sub _printers{
	my $sth = $dbh->prepare('INSERT INTO printers(DEVICEID, NAME, DRIVER, PORT) VALUES(?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM printers WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{PRINTERS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{NAME}, $_->{DRIVER}, $_->{PORT})){
			return(1);
		}
	}
	0;
}

# Inserting values of <SOUNDS> in sounds table
sub _sounds{
	my $sth = $dbh->prepare('INSERT INTO sounds(DEVICEID, MANUFACTURER, NAME, DESCRIPTION) VALUES(?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM sounds WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{SOUNDS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{MANUFACTURER}, $_->{NAME}, $_->{DESCRIPTION})){
			return(1);
		}
	}
	0;
}

# Inserting values of <VIDEOS> in videos table
sub _videos{
	my $sth = $dbh->prepare('INSERT INTO videos(DEVICEID, NAME, CHIPSET, MEMORY, RESOLUTION) VALUES(?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM videos WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{VIDEOS};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{NAME}, $_->{CHIPSET}, $_->{MEMORY}, $_->{RESOLUTION})){
			return(1);
		}
	}
	0;
}
# Inserting values of <SOFTWARES> in softwares table
sub _softwares{
	my $sth = $dbh->prepare('INSERT INTO softwares(DEVICEID, PUBLISHER, NAME, VERSION, FOLDER, COMMENTS, FILENAME, FILESIZE, SOURCE) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)');
	
	if($update){
		if(!$dbh->do('DELETE FROM softwares WHERE DEVICEID=?', {}, $DeviceID)){
			return(1);
		}
	}
	
	my $array = $result->{CONTENT}->{SOFTWARES};
	
	for(@$array){
		if(!$sth->execute($DeviceID, $_->{PUBLISHER}, $_->{NAME}, $_->{VERSION}, $_->{FOLDER}, $_->{COMMENTS}, $_->{FILENAME}, $_->{FILESIZE}, $_->{SOURCE})){
			return(1);
		}
	}
	0;
}

sub _post_inventory{
	my $request;
	my $row;
	my $red;
	my $accountkey;
	my %elements;

	$red = &_duplicate_main($result);
	# We verify accountinfo diff if the machine was already in the database
	if($update or $red){
		# We put back the account infos to the agent if necessary
		$request = $dbh->prepare('SELECT * FROM accountinfo WHERE DEVICEID=?');
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
					print LOG @accountkeys;
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
			&_log(509,'postinventory') if $ENV{'OCS_OPT_LOGLEVEL'};
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

sub _options{
	for(&_modules_get_inventory_options()){
		last if $_== 0;
		&$_();
	}
}
1;
