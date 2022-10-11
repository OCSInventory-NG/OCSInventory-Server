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
package Apache::Ocsinventory::Server::Capacities::Snmp::Inventory;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / _snmp_inventory /;

use Digest::MD5 qw(md5_base64);

use Apache::Ocsinventory::Server::System qw / :server /;
use Apache::Ocsinventory::Server::Capacities::Snmp::Data;

sub _snmp_context {
  my $snmpDeviceId = shift;
  my $request;
  my $snmpDatabaseId;
  my $snmpContext = {};

  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};

  # Retrieve Device ID if exists
  $request = $dbh->prepare('SELECT ID FROM snmp WHERE SNMPDEVICEID=?' );

  #TODO:retrieve the unless here like standard Inventory.pm
  $request->execute($snmpDeviceId);

  if($request->rows){
    my $row = $request->fetchrow_hashref;
    $snmpContext->{DATABASE_ID} = $row->{'ID'};
    $snmpContext->{EXIST_FL} = 1;
  } else {
    #We add the new device in snmp table
    $dbh->do('INSERT INTO snmp(SNMPDEVICEID) VALUES(?)', {}, $snmpDeviceId);

    $request = $dbh->prepare('SELECT ID FROM snmp WHERE SNMPDEVICEID=?');
    unless($request->execute($snmpDeviceId)){
      &_log(518,'snmp','id_error') if $ENV{'OCS_OPT_LOGLEVEL'};
      return(1);
    }
    my $row = $request->fetchrow_hashref;
    $snmpContext->{DATABASE_ID} = $row->{'ID'};
    
    #We add the device in snmp_accountinfo ans snmp_laststate tables;
    $dbh->do('INSERT INTO snmp_accountinfo(SNMP_ID) VALUES(?)', {}, $row->{'ID'});
    $dbh->do('INSERT INTO snmp_laststate(SNMP_ID) VALUES(?)', {}, $row->{'ID'});
    $dbh->commit;

  }

  if($ENV{'OCS_OPT_SNMP_INVENTORY_DIFF'}){ 

    #Getting laststate data for this device
    $request = $dbh->prepare('SELECT * FROM snmp_laststate WHERE SNMP_ID=?' );

    unless ($request->execute($snmpContext->{DATABASE_ID})) {
      &_log(519,'snmp','laststate_error') if $ENV{'OCS_OPT_LOGLEVEL'};
      return(1);
    }

    if($request->rows) {
       my $row = $request->fetchrow_hashref;
       $snmpContext->{LASTSTATE}=$row;
    }
  }

  return($snmpContext);
}


sub _snmp_inventory{
  my ( $sectionsMeta, $sectionsList, $agentDatabaseId ) = @_;
  my ($section,$XmlSection);

  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'}; 

  my $snmp_devices = $result->{CONTENT};
  
  #Getting data for the several snmp devices that we have in the xml
  while (my ($key, $value) = each (%{$snmp_devices})) {
    if(ref $value eq 'ARRAY') {
      while (my ($keybis, $valuebis) = each (@{$value})) {
        insert_snmp_inventory($key, $valuebis);
      }
    } else {
      insert_snmp_inventory($key, $value);
    }
  }
}

sub insert_snmp_inventory{
  my ($key, $value) = @_;
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my @columns;
  my @bind_num;
  my @arguments;
  my @update;
  my $i = 1;

  foreach my $snmp_infos (keys %{$value}) {
    push @columns, $snmp_infos;
    push @bind_num, '?';
    push @arguments, $value->{$snmp_infos};
    push @update, $snmp_infos." = ?";
  }

  my $column = join ',', @columns;
  my $args_prepare = join ',', @bind_num;
  my $update_prepare = join ',', @update;

  my $query = $dbh->prepare("INSERT INTO $key($column) VALUES($args_prepare) ON DUPLICATE KEY UPDATE $update_prepare");

  foreach my $values (@arguments) {
    $query->bind_param($i, $values);
    $i++;
  }

  foreach my $values (@arguments) {
    $query->bind_param($i, $values);
    $i++;
  }

  $query->execute;

  # Create first SNMP accountinfo entry
  my $reconciliation_field;
  my $reconciliation_value;

  # Retrieve the reconciliation field
  my $queryConfig = $dbh->prepare("SELECT l.LABEL_NAME as LABEL_NAME FROM `snmp_configs` c LEFT JOIN `snmp_types` t ON t.ID = c.TYPE_ID LEFT JOIN `snmp_labels` l ON l.ID = c.LABEL_ID WHERE t.TABLE_TYPE_NAME = ? AND c.RECONCILIATION = 'Yes'");
  
  unless($queryConfig->execute($key)){
    &_log(519,'snmp','get reconciliation field error') if $ENV{'OCS_OPT_LOGLEVEL'};
    return(1);
  }

  my $resultConfig = $queryConfig;

  if(!defined $resultConfig) { return 1; }

  my $rowConfig = $resultConfig->fetchrow_hashref;

  if(defined $rowConfig->{LABEL_NAME}) {
    $reconciliation_field = $rowConfig->{LABEL_NAME};
  }

  # Check if row already exists on snmp_accountinfo
  if(defined $reconciliation_field) {
    my $queryCheck = $dbh->prepare("SELECT ID FROM `snmp_accountinfo` WHERE SNMP_TYPE = ? AND SNMP_RECONCILIATION_FIELD = ? AND SNMP_RECONCILIATION_VALUE = ?");
    $queryCheck->bind_param(1, $key);
    $queryCheck->bind_param(2, $reconciliation_field);
    $queryCheck->bind_param(3, $value->{$reconciliation_field});

    my $id;

    unless($queryCheck->execute){
      &_log(519,'snmp','check if snmp_accountinfo field exists error') if $ENV{'OCS_OPT_LOGLEVEL'};
      return(1);
    }

    my $resultCheck = $queryCheck;

    if(!defined $resultCheck) { return 1; }

    my $rowCheck = $resultCheck->fetchrow_hashref;

    if(defined $rowCheck->{ID}) {
      $id = $rowCheck->{ID};
    }

    # If id is undefined insert row in snmp_accountinfo
    if(!defined $id) {
      my $queryInsert = $dbh->prepare("INSERT INTO `snmp_accountinfo`(SNMP_TYPE, SNMP_RECONCILIATION_FIELD, SNMP_RECONCILIATION_VALUE) VALUES(?,?,?)");
      $queryInsert->bind_param(1, $key);
      $queryInsert->bind_param(2, $reconciliation_field);
      $queryInsert->bind_param(3, $value->{$reconciliation_field});

      my $resultInsert = $queryInsert->execute or return undef;
      if(!defined $resultInsert) { return 1; }
    }
  }
}

sub _update_snmp_inventory_section{
  my ($snmpDeviceXml, $snmpContext, $section, $XmlSection, $sectionMeta) = @_;

  my $snmpDatabaseId = $snmpContext->{DATABASE_ID};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my @bind_values;

  my $refXml = $snmpDeviceXml->{$XmlSection};

  # We continue only if data for this section
  return 0 unless ($refXml);

  #TODO: prevent a bug if one (or more) of the snmp tables has no SNMP_ID field)
  #We delete related data for this device if already exists	
  if ($snmpContext->{EXIST_FL})  {
    if($ENV{'OCS_OPT_SNMP_INVENTORY_DIFF'}){
      if( _snmp_has_changed($refXml,$XmlSection,$section,$snmpContext) ){
        &_log( 113, 'snmp', "u:$XmlSection") if $ENV{'OCS_OPT_LOGLEVEL'};
        $sectionMeta->{hasChanged} = 1;
      }
      else {
         return 0; #We don't update this section
      }

    } else {
      $sectionMeta->{hasChanged} = 1; 
    }

    if( $sectionMeta->{delOnReplace}) {
      if(!$dbh->do("DELETE FROM $section WHERE SNMP_ID=?", {}, $snmpDatabaseId)){
        return(1);
      }
    }
  }

  # Processing values	
  my $sth = $dbh->prepare( $sectionMeta->{sql_insert_string} );

  
  # Multi lines (forceArray)
  if($sectionMeta->{multi}){
    for my $line (@$refXml){
      &_get_snmp_bind_values($line, $sectionMeta, \@bind_values);

      if(!$sth->execute($snmpDatabaseId, @bind_values)){
        return(1);
      }

      @bind_values = ();
    }
  }
  # One line (hash)
  else{
    &_get_snmp_bind_values($refXml, $sectionMeta, \@bind_values);
    if( !$sth->execute($snmpDatabaseId, @bind_values) ){
      return(1);
    }
  }

  #Getting laststate for this section
  my $md5_hash = md5_base64(XML::Simple::XMLout($refXml));
  push @{$snmpContext->{'LASTSTATE_UPDATE_VALUES'}} , "$XmlSection='$md5_hash'";

  #We compute checksum for this section
  $snmpDeviceXml->{'COMMON'}->{'CHECKSUM'} |= $sectionMeta->{mask};

  $dbh->commit;
  0;
}


sub _snmp_common{
  my $base= shift;
  my $snmpDatabaseId = shift;
  my $agentDatabaseId = shift;
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};

 #Store the COMMON data from XML
 $dbh->do("UPDATE snmp SET IPADDR=".$dbh->quote($base->{IPADDR}).", 
  LASTDATE=NOW(),
  CHECKSUM=(".$base->{CHECKSUM}."|CHECKSUM|1),
  MACADDR=".$dbh->quote($base->{MACADDR}).",
  SNMPDEVICEID=".$dbh->quote($base->{SNMPDEVICEID}).",
  NAME=".$dbh->quote($base->{NAME}).",
  DESCRIPTION=".$dbh->quote($base->{DESCRIPTION}).",
  CONTACT=".$dbh->quote($base->{CONTACT}).",
  LOCATION=".$dbh->quote($base->{LOCATION}).",
  UPTIME=".$dbh->quote($base->{UPTIME}).",
  DOMAIN=".$dbh->quote($base->{DOMAIN}).",
  TYPE=".$dbh->quote($base->{TYPE})."
   WHERE ID = $snmpDatabaseId")
  or return(1);
 
  $dbh->commit;

  #We get and store the TAG of the computer doing SNMP inventory
  my $request = $dbh->prepare('SELECT TAG FROM accountinfo WHERE HARDWARE_ID=?');

  unless($request->execute($agentDatabaseId)){
      &_log(519,'snmp','computer tag error') if $ENV{'OCS_OPT_LOGLEVEL'};
      return(1);
  }

  my $row = $request->fetchrow_hashref;
 
  if (defined $row->{'TAG'}) {
    $dbh->do("UPDATE snmp_accountinfo SET TAG=".$dbh->quote($row->{'TAG'})." WHERE SNMP_ID=$snmpDatabaseId"); 
    $dbh->commit;
  }

  0;
}

1;
