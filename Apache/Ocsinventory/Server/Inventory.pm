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
use Apache::Ocsinventory::Server::System qw / :server /;

use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::Communication::Session;
use Apache::Ocsinventory::Server::Duplicate;

use Apache::Ocsinventory::Server::Inventory::Data;
use Apache::Ocsinventory::Server::Inventory::Capacities;
use Apache::Ocsinventory::Server::Inventory::Export;
use Apache::Ocsinventory::Server::Inventory::Update;
use Apache::Ocsinventory::Server::Inventory::Filter;
use Apache::Ocsinventory::Server::Inventory::Update::AccountInfos;
use Apache::Ocsinventory::Server::Inventory::Software;
use Apache::Ocsinventory::Interface::SoftwareCategory;
use Apache::Ocsinventory::Interface::AssetCategory;

our %XML_PARSER_OPT = (
	'ForceArray' => []
);

# Remanent data
my ( %SECTIONS, @SECTIONS );

&_init_map( \%SECTIONS, \@SECTIONS );
&_get_parser_ForceArray( $XML_PARSER_OPT{ForceArray} );

sub _context{
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  
  if(!$Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'}){
    $dbh->do('INSERT INTO hardware(DEVICEID) VALUES(?)', {}, $Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'}) or return(1);
    my $request = $dbh->prepare('SELECT ID FROM hardware WHERE DEVICEID=?');
    unless($request->execute($Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'})){
      &_log(518,'inventory','id_error') if $ENV{'OCS_OPT_LOGLEVEL'};
      return(1);
    }
    my $row = $request->fetchrow_hashref;
    $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'} = $row->{'ID'};
  }
  return(0);
}

sub _inventory_handler{
  
  # Call to preinventory handlers
  if( &_pre_options() == INVENTORY_STOP ){
    &_log(107,'inventory','stopped_by_module') if $ENV{'OCS_OPT_LOGLEVEL'};
    return APACHE_FORBIDDEN;
  }

  return APACHE_SERVER_ERROR if &_context();
  
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  
  # Lock device
  if(&_lock($Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'})){
    &_log( 516, 'inventory', 'device_locked');
    return(APACHE_FORBIDDEN);
  }
  
  # Check prolog
  if( !check_session( \%Apache::Ocsinventory::CURRENT_CONTEXT ) ){
    &_log( 114, 'inventory', 'no_session');
    if( !$Apache::Ocsinventory::CURRENT_CONTEXT{'IS_TRUSTED'} && $ENV{OCS_OPT_INVENTORY_SESSION_ONLY} ){
      &_log( 115, 'inventory', 'refused');
      return(APACHE_FORBIDDEN);
    }
  }

  #Inventory incoming
  &_log(104,'inventory','incoming') if $ENV{'OCS_OPT_LOGLEVEL'};
  
  # Put the inventory in the database
  return APACHE_SERVER_ERROR if &_update_inventory( \%SECTIONS, \@SECTIONS );
  
  #Committing inventory
  $dbh->commit;
  #Call to post inventory handlers
  &_post_options();
  
  #############
  # Manage several questions, including duplicates
  &_post_inventory();
  
  # That's all
  &_log(101,'inventory','transmitted') if $ENV{'OCS_OPT_LOGLEVEL'};
  return APACHE_OK;
}

sub _post_inventory{
  my $request;
  my $row;
  my $red;
  my $accountkey;
  my %elements;

  my @accountFields = _get_account_fields();
  
  my $deviceId = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};

  &_generate_ocs_file();
  &kill_session( \%Apache::Ocsinventory::CURRENT_CONTEXT );
  
  $red = &_duplicate_main();
  # We verify accountinfo diff if the machine was already in the database
  if($Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'} or $red){
    # We put back the account infos to the agent if necessary
    $request = $dbh->prepare('SELECT * FROM accountinfo WHERE HARDWARE_ID=?');
    $request->execute($deviceId);
    if($row = $request->fetchrow_hashref()){
      my $up = 0;
      # Compare the account infos with the user's ones
      for $accountkey (@accountFields){
        for(@{$result->{CONTENT}->{ACCOUNTINFO}}){
          if($_->{KEYNAME} eq $accountkey){
            utf8::encode($_->{KEYVALUE});    #We encode string to be able to make comparison if string is UTF8
            $up=1,last if($_->{KEYVALUE} ne $row->{$accountkey});
          }
        }
      }
      # If there is something new in the table
      if(
          !exists($result->{CONTENT}->{ACCOUNTINFO})
          ||
          @accountFields != @{$result->{CONTENT}->{ACCOUNTINFO}}
      ) {
          $up = 1 
      }
      
      if($up){
        # we write the xml data
        $elements{'RESPONSE'} = [ 'ACCOUNT_UPDATE' ];
        for(@accountFields){
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
      &_log(509,'postinventory','no_account_infos') if $ENV{'OCS_OPT_LOGLEVEL'};
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

1;
