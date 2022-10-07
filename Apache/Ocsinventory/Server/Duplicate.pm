###############################################################################
## Copyright 2005-2016 OCSInventory-NG/OCSInventory-Server contributors.
## See the Contributors file for more details about them.
## 
## This file is part of OCSInventory-NG/OCSInventory-ocsreports.
##
## OCSInventory-NG/OCSInventory-Server is free software: you can redistribute
## it and/or modify it under the terms of the GNU General Public License as
## published by the Free Software Foundation, either version 2 of the License,
## or (at your option) any later version.
##
## OCSInventory-NG/OCSInventory-Server is distributed in the hope that it
## will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
## of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.
##
## You should have received a copy of the GNU General Public License
## along with OCSInventory-NG/OCSInventory-ocsreports. if not, write to the
## Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
## MA 02110-1301, USA.
################################################################################
package Apache::Ocsinventory::Server::Duplicate;

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

our @EXPORT = qw / _duplicate_main /;

use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Server::System qw /:server _modules_get_duplicate_handlers/;
use Apache::Ocsinventory::Map;

# Subroutine called at the end of database inventory insertions
sub _duplicate_main{
  my %exist;
  my $red;
  
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $DeviceID = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};

  # workaround agent old_deviceid bug
  if(!$result->{CONTENT}->{OLD_DEVICEID} and $result->{CONTENT}->{DOWNLOAD}->{HISTORY}->{OLD_DEVICEID})
  {
    $result->{CONTENT}->{OLD_DEVICEID} = $result->{CONTENT}->{DOWNLOAD}->{HISTORY}->{OLD_DEVICEID};
  }

  # If the duplicate is specified
  if($result->{CONTENT}->{OLD_DEVICEID} and $result->{CONTENT}->{OLD_DEVICEID} ne $Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'}){
    &_log(326,'duplicate',$result->{CONTENT}->{OLD_DEVICEID}) if $ENV{'OCS_OPT_LOGLEVEL'};
    # Looking for database id of old deviceid
    my $request = $dbh->prepare('SELECT ID FROM hardware WHERE DEVICEID=?');
    $request->execute($result->{CONTENT}->{OLD_DEVICEID});
    if(my $row = $request->fetchrow_hashref){
      if(&_duplicate_replace($row->{'ID'})){
        # If there is an invalid old deviceid
        &_log(513,'duplicate','old deviceid') if $ENV{'OCS_OPT_LOGLEVEL'};
        $dbh->rollback;
      }else{
        $dbh->commit;
        $red = 1;
      }
    }
  }
  
  # Handle duplicates if $ENV{'OCS_OPT_AUTO_DUPLICATE_LVL'} is set
  if($ENV{'OCS_OPT_AUTO_DUPLICATE_LVL'}){
    # Trying to find some duplicate evidences
    &_duplicate_detect(\%exist);
    # For each result, we are trying to know if it is a true duplicate (according to AUTO_DUPLICATE_LVL
    for(sort keys(%exist)){
      if(&_duplicate_evaluate(\%exist, $_)){
        if(&_duplicate_replace($_)){
          &_log(517,'duplicate','replacing_error') if $ENV{'OCS_OPT_LOGLEVEL'};
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
  $exist->{$key}->{'MASK'}|=DUP_SMODEL_FL if $exist->{$key}->{'SMODEL'};
  $exist->{$key}->{'MASK'}|=DUP_UUID_FL if $exist->{$key}->{'UUID'};
  $exist->{$key}->{'MASK'}|=DUP_ASSETTAG_FL if $exist->{$key}->{'ASSETTAG'};
  
  if((($ENV{'OCS_OPT_AUTO_DUPLICATE_LVL'} & $exist->{$key}->{'MASK'})) == $ENV{'OCS_OPT_AUTO_DUPLICATE_LVL'}){
    return(1);
  }else{
    return(0);
  }
}

sub _duplicate_detect{

  my $exist = shift;
  
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $DeviceID = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
    
  my $request;
  my $row;
  
  my(@bad_serial, @bad_mac);
  
  # Retrieve generic mac addresses
  $request = $dbh->prepare('SELECT MACADDRESS FROM blacklist_macaddresses');
  $request->execute();
  push @bad_mac, $row->{MACADDRESS} while($row = $request->fetchrow_hashref());
  
  # Retrieve generic serials
  $request = $dbh->prepare('SELECT SERIAL FROM blacklist_serials');
  $request->execute();
  push @bad_serial, $row->{SERIAL} while($row = $request->fetchrow_hashref());
  
  # Do we already have the hostname
  $request = $dbh->prepare('SELECT ID, NAME FROM hardware WHERE NAME=? AND ID<>? ORDER BY ID');
  $request->execute($result->{CONTENT}->{HARDWARE}->{NAME}, $DeviceID);
  while($row = $request->fetchrow_hashref()){
    if(!($row->{'NAME'} eq '')){
      $exist->{$row->{'ID'}}->{'HOSTNAME'}=1;
    }
  }
  
  # Do we already have the assettag ?
  $request = $dbh->prepare('SELECT HARDWARE_ID, ASSETTAG FROM bios WHERE ASSETTAG=? AND HARDWARE_ID<>? ORDER BY HARDWARE_ID');
  $request->execute($result->{CONTENT}->{BIOS}->{ASSETTAG}, $DeviceID);
  while($row = $request->fetchrow_hashref()){
    if(!($row->{'ASSETTAG'} eq '')){
      $exist->{$row->{'ID'}}->{'ASSETTAG'}=1;
    }
  }
  # Do we already have the uuid ?
  $request = $dbh->prepare('SELECT ID, UUID FROM hardware WHERE UUID=? AND ID<>? ORDER BY ID');
  $request->execute($result->{CONTENT}->{HARDWARE}->{UUID}, $DeviceID);
  while($row = $request->fetchrow_hashref()){
    if(!($row->{'UUID'} eq '')){
      $exist->{$row->{'ID'}}->{'UUID'}=1;
    }
  }

  # ...and one MAC of this machine
  for(@{$result->{CONTENT}->{NETWORKS}}){
    if(!&_already_in_array($_->{'MACADDR'}, \@bad_mac)){
      $request = $dbh->prepare('SELECT HARDWARE_ID,DESCRIPTION,MACADDR FROM networks WHERE MACADDR=? AND HARDWARE_ID<>?');
      $request->execute($_->{MACADDR}, $DeviceID);
      while($row = $request->fetchrow_hashref()){
        $exist->{$row->{'HARDWARE_ID'}}->{'MACADDRESS'}++;
      }
    }
  }
  # ...or its serial
  if($result->{CONTENT}->{BIOS}->{SSN}){
    $request = $dbh->prepare('SELECT HARDWARE_ID, SSN FROM bios WHERE SSN=? AND HARDWARE_ID<>?');
    $request->execute($result->{CONTENT}->{BIOS}->{SSN}, $DeviceID);
    while($row = $request->fetchrow_hashref()){
      if(!&_already_in_array($row->{'SSN'}, \@bad_serial)){
        $exist->{$row->{'HARDWARE_ID'}}->{'SSN'}=1;
      }
    }
  }
  
  # ...or its serial model
  if($result->{CONTENT}->{BIOS}->{SMODEL}){
    $request = $dbh->prepare('SELECT HARDWARE_ID, SMODEL FROM bios WHERE SMODEL=? AND HARDWARE_ID<>?');
    $request->execute($result->{CONTENT}->{BIOS}->{SMODEL}, $DeviceID);
    while($row = $request->fetchrow_hashref()){
      $exist->{$row->{'HARDWARE_ID'}}->{'SMODEL'}=1;
    }
  }
  
  $request->finish;
}


sub _duplicate_replace{
  my $device = shift;
  
  #Locks the device
  if( &_lock($device) ){
    &_log( 516, 'duplicate', 'device locked');
    return 1;
  }
  my $DeviceID = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
          
  # We keep the old quality and fidelity
  my $request=$dbh->prepare('SELECT QUALITY,FIDELITY,CHECKSUM,USERID FROM hardware WHERE ID=?');
  $request->execute($device);
  
  # If it does not exist
  unless($request->rows){
    &_unlock($device);
    return(1);
  }
  my $row = $request->fetchrow_hashref;
  my $quality = $row->{'QUALITY'}?$row->{'QUALITY'}:0;
  my $fidelity = $row->{'FIDELITY'};
  my $checksum = $row->{'CHECKSUM'};
  my $userid = $row->{'USERID'};
  $request->finish;
  
  # Current userid or previous one ? 
  if( $result->{CONTENT}->{HARDWARE}->{USERID}!~/system|localsystem/i ){
     $userid = $result->{CONTENT}->{HARDWARE}->{USERID};
  }
  # TODO: catch the queries return code
  # Keeping few informations from hardware 
  $dbh->do(" UPDATE hardware SET QUALITY=".$dbh->quote($quality).",
             FIDELITY=".$dbh->quote($fidelity).",
             CHECKSUM=(".(defined($checksum)?$checksum:CHECKSUM_MAX_VALUE)."|".(defined($result->{CONTENT}->{HARDWARE}->{CHECKSUM})?$result->{CONTENT}->{HARDWARE}->{CHECKSUM}:CHECKSUM_MAX_VALUE)."),
             USERID=".$dbh->quote($userid)." 
             WHERE ID=".$DeviceID
  ) ;
  $dbh->do("DELETE FROM hardware WHERE ID=?", {}, $device) ;

  # We keep the informations of the following tables: devices, accountinfo, itmgmt_comments
  $dbh->do('DELETE FROM accountinfo WHERE HARDWARE_ID=?', {}, $DeviceID) ;
  $dbh->do('UPDATE accountinfo SET HARDWARE_ID=? WHERE HARDWARE_ID=?', {}, $DeviceID, $device) ;
  $dbh->do('DELETE FROM devices WHERE HARDWARE_ID=?', {}, $DeviceID) ;
  $dbh->do('UPDATE devices SET HARDWARE_ID=? WHERE HARDWARE_ID=?', {}, $DeviceID, $device) ;
  $dbh->do('UPDATE itmgmt_comments SET HARDWARE_ID=? WHERE HARDWARE_ID=?', {}, $DeviceID, $device) ;
  # We keep the static inclusions/exclusions (STATIC=1|2)
  $dbh->do('UPDATE groups_cache SET HARDWARE_ID=? WHERE HARDWARE_ID=? AND (STATIC=1 OR STATIC=2)', {}, $DeviceID, $device) ;
  # The computer may not correspond to the previous dynamic groups, as its inventory could potentially change
  $dbh->do('DELETE FROM groups_cache WHERE HARDWARE_ID=?', {}, $device) ;
  
  # Drop old computer from "auto" tables in Map
  # TODO: is it possible to manage the not auto section => not auto for import but auto for deletion ?
  for (keys(%DATA_MAP)){
    next if !$DATA_MAP{$_}->{delOnReplace} || !$DATA_MAP{$_}->{auto} || $DATA_MAP{$_}->{capacities};
    unless($dbh->do("DELETE FROM $_ WHERE HARDWARE_ID=?", {}, $device)){
      &_log(301,'duplicate',"error to delete hardware_id from $_") if $ENV{'OCS_OPT_LOGLEVEL'};
      &_unlock($device);
      return(1);
    }
  }

  # Trace duplicate if needed
  if($ENV{'OCS_OPT_TRACE_DELETED'}){
    unless(  $dbh->do('INSERT INTO deleted_equiv(DATE,DELETED,EQUIVALENT) VALUES(NOW(),?,?)', {} , $device,$DeviceID)){
      &_log(302,'duplicate',"error on trace deleted") if $ENV{'OCS_OPT_LOGLEVEL'};
      &_unlock($device);
      return(1);
    }
  }

  # To enable option managing duplicates
  for(&_modules_get_duplicate_handlers()){
    last if $_==0;
    # Returning 1 will abort replacement
    unless(&$_(\%Apache::Ocsinventory::CURRENT_CONTEXT, $device)){
      &_unlock($device);
      return(1);
    }
  }

  &_log(300,'duplicate',"$device => $DeviceID") if $ENV{'OCS_OPT_LOGLEVEL'};

  #Remove lock
  &_unlock($device);
  0;

}
1;
