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
package Apache::Ocsinventory::Server::Inventory::Update::AccountInfos;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw/_get_account_fields  _accountinfo/;

use Apache::Ocsinventory::Server::System qw/ :server /;

sub _get_account_fields{
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $request = $dbh->prepare('SHOW COLUMNS FROM accountinfo');
  my @accountFields;
  
  $request->execute;
  while(my $row=$request->fetchrow_hashref){
    push @accountFields, $row->{'Field'} if($row->{'Field'} ne 'HARDWARE_ID');
  }
  return @accountFields;
}

sub _accountinfo{
  my $lost=shift; 
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  my $hardwareId = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'}; 
  
  # We have to look for the field's names because this table has a dynamic structure
  my ($row, $request, $accountkey, @accountFields);
  @accountFields = _get_account_fields();

  # The default behavior of the server is to ignore TAG changes from the agent
  if($ENV{OCS_OPT_ACCEPT_TAG_UPDATE_FROM_CLIENT} || !$Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'} || $lost){
    # Check if HARDWARE_ID already in accountinfo to prevent duplicate entry error
    $request = $dbh->prepare('SELECT `HARDWARE_ID` FROM `accountinfo` WHERE `HARDWARE_ID` = ?');
    $request->bind_param(1, $hardwareId);
    $request->execute;

    my $resultVerif = undef;

    while($row = $request->fetchrow_hashref()) {
      $resultVerif = $row->{HARDWARE_ID};
    }

    if(!defined $resultVerif) {
      # writing (if new id, but duplicate, it will be erased at the end of the execution)
      $dbh->do('INSERT INTO accountinfo(HARDWARE_ID) VALUES(?)', {}, $hardwareId);
    }
    
    # Now, we know what are the account info name fields
    # We can insert the client's data. This data will be kept only one time, in the first inventory
    if( exists ($result->{CONTENT}->{ACCOUNTINFO}) ){
      for $accountkey (@accountFields){
        my $array = $result->{CONTENT}->{ACCOUNTINFO};
        for(@$array){
          if($_->{KEYNAME} eq $accountkey){
            if(!$dbh->do('UPDATE accountinfo SET '.$accountkey."=".$dbh->quote($_->{KEYVALUE}).' WHERE HARDWARE_ID='.$hardwareId)){
  	          return 1;
	          }
	        }
        }
      }
    } else {
      &_log(528,'accountinfos','missing') if $ENV{'OCS_OPT_LOGLEVEL'};
    }
  }
  if($lost){
    if(!$dbh->do('UPDATE accountinfo SET TAG = "LOST" WHERE HARDWARE_ID=?', {}, $hardwareId)){
      return(1);
    }
  }
	
  $dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
  0;
}
1;
