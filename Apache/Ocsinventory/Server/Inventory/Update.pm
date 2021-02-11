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
package Apache::Ocsinventory::Server::Inventory::Update;

use Apache::Ocsinventory::Server::Inventory::Cache;
use Apache::Ocsinventory::Server::Inventory::Update::Hardware;
use Apache::Ocsinventory::Server::Inventory::Update::AccountInfos;

use Apache::Ocsinventory::Server::Inventory::Software;
use Apache::Ocsinventory::Interface::SoftwareCategory;
use Apache::Ocsinventory::Interface::AssetCategory;
use Apache::Ocsinventory::Interface::Saas;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / _update_inventory /;

use Apache::Ocsinventory::Server::System qw / :server /;
use Apache::Ocsinventory::Server::Inventory::Data;

sub _update_inventory{
  my ( $sectionsMeta, $sectionsList ) = @_;
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};

  my $section;

  set_category();

  if(&_insert_software()) {
    return 1;
  }

  set_asset_category();  
  set_saas();

  &_reset_inventory_cache( $sectionsMeta, $sectionsList ) if $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED};
   
  # Call special sections update
  if(&_hardware($sectionsMeta->{'hardware'}) or &_accountinfo()){
    return 1;
  }

  # Call the _update_inventory_section for each section
  for $section (@{$sectionsList}){
    #Only if section exists in XML or if table is mandatory
    if (($result->{CONTENT}->{uc $section} || $sectionsMeta->{$section}->{mandatory}) && $sectionsMeta->{$section}->{auto}) { 
      if(_update_inventory_section($section, $sectionsMeta->{$section})){
        return 1;
      }
    }
  }
}

sub _update_inventory_section{
  my ($section, $sectionMeta) = @_;

  my @bind_values;
  my $deviceId = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
	
  # The computer exists. 
  # We check if this section has changed since the last inventory (only if activated)
  # We delete the previous entries
  if($Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'}){
    if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
      if( _has_changed($section) ){
        &_log( 113, 'inventory', "u:$section") if $ENV{'OCS_OPT_LOGLEVEL'};
        $sectionMeta->{hasChanged} = 1;
      }
      else{
        return 0;
      }
    }
    else{
      $sectionMeta->{hasChanged} = 1;
    }
    if( $sectionMeta->{delOnReplace} && !($sectionMeta->{writeDiff} && $ENV{'OCS_OPT_INVENTORY_WRITE_DIFF'}) ){
      if(!$dbh->do("DELETE FROM $section WHERE HARDWARE_ID=?", {}, $deviceId)){
        return(1);
      }
    }
  }

  # DEL AND REPLACE, or detect diff on elements of the section (more load on frontends, less on DB backend)
  if($Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'} && $ENV{'OCS_OPT_INVENTORY_WRITE_DIFF'} && $sectionMeta->{writeDiff}){
    my @fromDb;
    my @fromXml;
    my $refXml = $result->{CONTENT}->{uc $section};
    my $sth = $dbh->prepare($sectionMeta->{sql_select_string});
    $sth->execute($deviceId) or return 1;
    while(my @row = $sth->fetchrow_array){
      push @fromDb, [ @row ];
    }	  
    for my $line (@$refXml){
      &_get_bind_values($line, $sectionMeta, \@bind_values);
      push @fromXml, [ @bind_values ];
      @bind_values = ();
    }
    #TODO: Sorting XML entries, to compare more quickly with DB elements
    my $new=0;
    my $del=0;
    for my $l_xml (@fromXml){
      my $found = 0;
      for my $i_db (0..$#fromDb){
        next unless $fromDb[$i_db];
        my @line = @{$fromDb[$i_db]};
        my $comp_xml = join '', @$l_xml;
        my $comp_db = join '', @line[2..$#line];
        if( $comp_db eq $comp_xml ){
          $found = 1;
          # The value has been found, we have to delete it from the db list
          # (elements remaining will be deleted)
          delete $fromDb[$i_db];
          last;
        }
      }
      if(!$found){
        $new++;
        $dbh->do( $sectionMeta->{sql_insert_string}, {}, $deviceId, @$l_xml ) or return 1;
        if( $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED} && $sectionMeta->{cache} ){
          &_cache( 'add', $section, $sectionMeta, $l_xml );
        }
      }
    }
    # Now we have to delete from DB elements that still remain in fromDb
    for (@fromDb){
      next if !defined (${$_}[0]);
      $del++;
      $dbh->do( $sectionMeta->{sql_delete_string}, {}, $deviceId, ${$_}[0]) or return 1;
      my @ldb = @$_;
      @ldb = @ldb[ 2..$#ldb ];
      if( $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED} && $sectionMeta->{cache} && !$ENV{OCS_OPT_INVENTORY_CACHE_KEEP}){
        &_cache( 'del', $section, $sectionMeta, \@ldb );
      }
    }
    if( $new||$del ){
      &_log( 113, 'write_diff', "ch:$section(+$new-$del)") if $ENV{'OCS_OPT_LOGLEVEL'};
    }
  }
  else{
    # Processing values	
    my $sth = $dbh->prepare( $sectionMeta->{sql_insert_string} );
    # Multi lines (forceArray)
    my $refXml = $result->{CONTENT}->{uc $section};
    
    if($sectionMeta->{multi}){
      for my $line (@$refXml){
        &_get_bind_values($line, $sectionMeta, \@bind_values);
        if(!$sth->execute($deviceId, @bind_values)){
          return(1);
        }
        if( $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED} && $sectionMeta->{cache} ){
          &_cache( 'add', $section, $sectionMeta, \@bind_values );
        }
        @bind_values = ();
      }
    }
    # One line (hash)
    else{
      &_get_bind_values($refXml, $sectionMeta, \@bind_values);
      if( !$sth->execute($deviceId, @bind_values) ){
        return(1);
      }
      if( $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED} && $sectionMeta->{cache} ){
        &_cache( 'add', $section, $sectionMeta, \@bind_values );
      }
    }
  }
  $dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
  0;
}
1;
