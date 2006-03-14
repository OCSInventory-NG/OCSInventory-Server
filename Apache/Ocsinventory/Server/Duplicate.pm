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
my $result;
my $dbh;

# Subroutine called at the end of database inventory insertions
sub _duplicate_main{
	my %exist;
	my $red;

	$result = shift;
	$dbh = $CURRENT_CONTEXT{'DBI_HANDLE'};
	$DeviceID = $CURRENT_CONTEXT{'DEVICEID'};

	# If the duplicate is specified
	if($result->{CONTENT}->{OLD_DEVICEID} and $result->{CONTENT}->{OLD_DEVICEID} ne $DeviceID){
		if(&_duplicate_replace($result->{CONTENT}->{OLD_DEVICEID})){
			# If there is an invalid old deviceid
			&_log(513,'duplicate') if $ENV{'OCS_OPT_LOGLEVEL'};
			$dbh->rollback;
		}else{
			$dbh->commit;
			$red = 1;
		}
	}
	
	# Handle duplicates if $ENV{'OCS_OPT_AUTO_DUPLICATE_LVL'} is set
	if($ENV{'OCS_OPT_AUTO_DUPLICATE_LVL'}){
		# Trying to find some duplicate evidences
		&_duplicate_detect(\%exist);
		# For each result, we are trying to know if it is a true duplicate (according to AUTO_DUPLICATE_LVL
		for(keys(%exist)){
			if(&_duplicate_evaluate(\%exist, $_)){
				if(&_duplicate_replace($_)){
					&_log(517,'duplicate') if $ENV{'OCS_OPT_LOGLEVEL'};
					$dbh->rollback;
				}else{
					$dbh->commit;
					$red = 1;
				}
			}
		}
	}
	return $red;
}

sub _already_in_array {
        my $lookfor = shift;
        my $ref   = shift;
        foreach (@$ref){
        	return 1 if($lookfor eq $_);
        }
        return 0;
}

sub _duplicate_evaluate{
	my $exist = shift;
	my $key = shift;
	
	# Check duplicate , according to AUTO_DUPLICATE_LVL
	$exist->{$key}->{'MASK'} = 0;
	$exist->{$key}->{'MASK'}|=DUP_HOSTNAME_FL if $exist->{$key}->{'HOSTNAME'};
	$exist->{$key}->{'MASK'}|=DUP_SERIAL_FL if $exist->{$key}->{'SSN'};
	$exist->{$key}->{'MASK'}|=DUP_MACADDR_FL if $exist->{$key}->{'MACADDRESS'};
	
	# If  
	if((($ENV{'OCS_OPT_AUTO_DUPLICATE_LVL'} & $exist->{$_}->{'MASK'})) == $ENV{'OCS_OPT_AUTO_DUPLICATE_LVL'}){
		return(1);
	}else{
		return(0);
	}
}

sub _duplicate_detect{
	my $exist = shift;
	my $request;
	my $row;

	my @bad_serial = ( 'N/A','(null string)','INVALID','SYS-1234567890','SYS-9876543210','SN-12345','SN-1234567890','1111111111','1111111','1','0123456789','12345','123456','1234567','12345678','123456789','1234567890','123456789000','12345678901234567','0000000000','000000000','00000000','0000000','000000','NNNNNNN','xxxxxxxxxxx','EVAL','IATPASS','none','To Be Filled By O.E.M.','Tulip Computers','Serial Number xxxxxx','SN-123456fvgv3i0b8o5n6n7k','');

	my @bad_mac = ('00:00:00:00:00:00','FF:FF:FF:FF:FF:FF','44:45:53:54:00:00','44:45:53:54:00:01','');
	
	# Have we already got the hostname
	$request = $dbh->prepare('SELECT DEVICEID, NAME FROM hardware WHERE NAME=? AND DEVICEID<>?');
	$request->execute($result->{CONTENT}->{HARDWARE}->{NAME}, $DeviceID);
	while($row = $request->fetchrow_hashref()){
		if(!($row->{'NAME'} eq '')){
			$exist->{$row->{'DEVICEID'}}->{'HOSTNAME'}=1;
		}
	}
	
	# ...and one MAC of this machine
	for(@{$result->{CONTENT}->{NETWORKS}}){
		$request = $dbh->prepare('SELECT DEVICEID,DESCRIPTION,MACADDR FROM networks WHERE MACADDR=? AND DEVICEID<>?');
		$request->execute($_->{MACADDR}, $DeviceID);
		while($row = $request->fetchrow_hashref()){
			if(!&_already_in_array($row->{'MACADDR'}, \@bad_mac)){
				$exist->{$row->{'DEVICEID'}}->{'MACADDRESS'}++;
			}
		}
	}
	# ...or its serial
	$request = $dbh->prepare('SELECT DEVICEID, SSN FROM bios WHERE SSN=? AND DEVICEID<>?');
	$request->execute($result->{CONTENT}->{BIOS}->{SSN}, $DeviceID);
	while($row = $request->fetchrow_hashref()){
		if(!&_already_in_array($row->{'SSN'}, \@bad_serial)){
			$exist->{$row->{'DEVICEID'}}->{'SSN'}=1;
		}
	}
	$request->finish;
}


sub _duplicate_replace{

	my @tables=qw/hardware accesslog bios memories slots
			controllers monitors ports storages drives inputs
			modems networks printers sounds videos softwares /;

	my $device = shift;

	#Locks the device
	return 1 if(&_lock($device));
				
	# We keep the old quality and fidelity
	my $request=$dbh->prepare('SELECT QUALITY,FIDELITY,CHECKSUM FROM hardware WHERE DEVICEID=?');
	$request->execute($device);
	
	# If it does not exist
	unless($request->rows){
		&_unlock($device);
		return(1);
	}
	my $row = $request->fetchrow_hashref;
	my $quality = $row->{'QUALITY'};
	my $fidelity = $row->{'FIDELITY'};
	my $checksum = $row->{'CHECKSUM'};
	$request->finish;
	
	# Keeping the accountinfos and the devices options
	unless(	$dbh->do("	UPDATE hardware SET QUALITY=".$dbh->quote($quality).",
				FIDELITY=".$dbh->quote($fidelity).",
				CHECKSUM=(".(defined($checksum)?$checksum:CHECKSUM_MAX_VALUE)."|".(defined($result->{CONTENT}->{HARDWARE}->{CHECKSUM})?$result->{CONTENT}->{HARDWARE}->{CHECKSUM}:CHECKSUM_MAX_VALUE).") 
				WHERE DEVICEID=".$dbh->quote($DeviceID))
		and
		$dbh->do('DELETE FROM accountinfo WHERE DEVICEID=?', {}, $DeviceID)
		and
		$dbh->do('UPDATE accountinfo SET DEVICEID=? WHERE DEVICEID=?', {}, $DeviceID, $device)
		and
		$dbh->do('UPDATE devices SET DEVICEID=? WHERE DEVICEID=?', {}, $DeviceID, $device)
	){
		&_unlock($device);
		return(1);
	}
	
	# Drop old computer
	for (@tables){
		unless($dbh->do("DELETE FROM $_ WHERE DEVICEID=?", {}, $device)){
			&_unlock($device);
			return(1);
		}
	}
	
	#Trace duplicate
	if($ENV{'OCS_OPT_TRACE_DELETED'}){
		unless(	$dbh->do('INSERT INTO deleted_equiv(DATE,DELETED,EQUIVALENT) VALUES(NULL,?,?)', {} , $device,$DeviceID)){
			&_unlock($device);
			return(1);
		}
	}

	# To enable option managing duplicates
	for(&_modules_get_duplicate_handlers()){
		last if $_==0;
		# Returning 1 will abort replacement
		unless(&$_($device)){
			&_unlock($device);
			return(1);
		}
	}

	&_log(300,'duplicate') if $ENV{'OCS_OPT_LOGLEVEL'};

	#Remove lock
	&_unlock($device);
	0;

}
1;
