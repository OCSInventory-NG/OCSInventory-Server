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
package Apache::Ocsinventory::Server::Inventory::Data;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  _init_map 
  _get_bind_values 
  _has_changed 
  _get_parser_ForceArray 
/;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Server::System qw / :server /;

sub _init_map{
  my ($sectionsMeta, $sectionsList) = @_;
  my $section;
  my @bind_num;
  my $field;
  my $fields_string;
  my $field_index;

  # Parse every section
  for $section (keys(%DATA_MAP)){
    $field_index = 0;
    # Field array (from data_map field hash keys), filtered fields and cached fields
    $sectionsMeta->{$section}->{field_arrayref} = [];
    $sectionsMeta->{$section}->{field_filtered} = [];
    $sectionsMeta->{$section}->{field_cached} = {};
    ##############################################
    #Don't process the sections that are use for capacities special inventory
    next if $DATA_MAP{$section}->{capacities};
    # Don't process the non-auto-generated sections
    next if !$DATA_MAP{$section}->{auto};
    $sectionsMeta->{$section}->{multi} = 1 if $DATA_MAP{$section}->{multi};
    $sectionsMeta->{$section}->{mask} = $DATA_MAP{$section}->{mask};
    $sectionsMeta->{$section}->{delOnReplace} = 1 if $DATA_MAP{$section}->{delOnReplace};
    $sectionsMeta->{$section}->{writeDiff} = 1 if $DATA_MAP{$section}->{writeDiff};
    $sectionsMeta->{$section}->{cache} = 1 if $DATA_MAP{$section}->{cache};
    $sectionsMeta->{$section}->{mandatory} = 1 if $DATA_MAP{$section}->{mandatory};
    $sectionsMeta->{$section}->{auto} = 1 if $DATA_MAP{$section}->{auto};
    $sectionsMeta->{$section}->{name} = $section;
    # $sectionsMeta->{$section}->{hasChanged} is set while inventory update
     
    # Parse fields of the current section
    for $field ( keys(%{$DATA_MAP{$section}->{fields}} ) ){
      if(!$DATA_MAP{$section}->{fields}->{$field}->{noSql}){
        push @{$sectionsMeta->{$section}->{field_arrayref}}, $field;
        $sectionsMeta->{$section}->{noSql} = 1 unless $sectionsMeta->{$section}->{noSql};
      }
      if($DATA_MAP{$section}->{fields}->{$field}->{filter}){
        next unless $ENV{OCS_OPT_INVENTORY_FILTER_ENABLED};
        push @{$sectionsMeta->{$section}->{field_filtered}}, $field;
        $sectionsMeta->{$section}->{filter} = 1 unless $sectionsMeta->{$section}->{filter};
      }
      if($DATA_MAP{$section}->{fields}->{$field}->{cache}){
        next unless $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED};
        $sectionsMeta->{$section}->{field_cached}->{$field} = $field_index;
        $sectionsMeta->{$section}->{cache} = 1 unless $sectionsMeta->{$section}->{cache};
      }
      if(defined $DATA_MAP{$section}->{fields}->{$field}->{fallback}){
        $sectionsMeta->{$section}->{fields}->{$field}->{fallback} = $DATA_MAP{$section}->{fields}->{$field}->{fallback};
      }

      if(defined $DATA_MAP{$section}->{fields}->{$field}->{type}){
        $sectionsMeta->{$section}->{fields}->{$field}->{type} = $DATA_MAP{$section}->{fields}->{$field}->{type};
      }   
 
      $field_index++;      
    }
    # Build the "DBI->prepare" sql insert string 
    for (@{$sectionsMeta->{$section}->{field_arrayref}}) {
      s/^(.*)$/\`$1\`/;
    }
    $fields_string = join ',', ('`HARDWARE_ID`', @{$sectionsMeta->{$section}->{field_arrayref}});
    $sectionsMeta->{$section}->{sql_insert_string} = "INSERT INTO $section($fields_string) VALUES(";
    for(0..@{$sectionsMeta->{$section}->{field_arrayref}}){
      push @bind_num, '?';
    }
    
    $sectionsMeta->{$section}->{sql_insert_string}.= (join ',', @bind_num).')';
    @bind_num = ();
    # Build the "DBI->prepare" sql select string 
    $sectionsMeta->{$section}->{sql_select_string} = "SELECT ID,$fields_string FROM $section 
      WHERE HARDWARE_ID=? ORDER BY ".$DATA_MAP{$section}->{sortBy};
    # Build the "DBI->prepare" sql deletion string 
    $sectionsMeta->{$section}->{sql_delete_string} = "DELETE FROM $section WHERE HARDWARE_ID=? AND ID=?";
    # to avoid many "keys"
    push @$sectionsList, $section;
  }

  #Special treatment for hardware section
  $sectionsMeta->{'hardware'} = &_get_hardware_fields; 
  push @$sectionsList, 'hardware';

}

sub _get_bind_values{
  my ($refXml, $sectionMeta, $arrayToFeed) = @_;
  
  my ($bind_value, $xmlvalue, $xmlfield);

  for my $field ( @{ $sectionMeta->{field_arrayref} } ) {
    if(ref($refXml) eq 'HASH'){
      if(defined($refXml->{$field}) && !defined($sectionMeta->{fields}->{$field}->{type}) && $refXml->{$field} ne '' && $refXml->{$field} ne '??' && $refXml->{$field}!~/^N\/?A$/) {
        $bind_value = $refXml->{$field}
      }
      else{
        my $fieldMod = $field;
        $fieldMod =~ s/^`(.*)`$/$1/;
        if(defined($refXml->{$fieldMod})){
          $bind_value = $refXml->{$fieldMod}
        }
        else{
          if( defined $sectionMeta->{fields}->{$field}->{fallback} ){
            $bind_value = $sectionMeta->{fields}->{$field}->{fallback};
            &_log( 000, 'fallback', "$field:".$sectionMeta->{fields}->{$field}->{fallback}) if $ENV{'OCS_OPT_LOGLEVEL'}>1;
          }
          else{
            &_log( 000, 'generic-fallback', "$field:".$sectionMeta->{fields}->{$field}->{fallback}) if $ENV{'OCS_OPT_LOGLEVEL'}>1;
            $bind_value = '';
          }
        }
        
      }
    }

    # We have to substitute the value with the ID matching "type_section_field.name" if the field is tagged "type".
    # It allows to support different DB structures
    if(defined $sectionMeta->{fields}->{$field}->{type}) {
      $xmlfield = $field;
      $xmlfield =~ s/_ID//g;    #We delete the _ID pattern to be in concordance with XML 

      if(defined $sectionMeta->{fields}->{$field}->{fallback}) {
        $bind_value = _get_type_id($sectionMeta->{name}, $xmlfield, $sectionMeta->{fields}->{$field}->{fallback} );
        &_log( 000, 'fallback', "$field:".$sectionMeta->{fields}->{$field}->{fallback}) if $ENV{'OCS_OPT_LOGLEVEL'}>1;

       } else {  #No fallback for this field
        $xmlvalue = $refXml->{$xmlfield};
        $bind_value = _get_type_id($sectionMeta->{name}, $xmlfield, $xmlvalue);
      }
    }
    push @$arrayToFeed, $bind_value;
  }
}

sub _get_parser_ForceArray{
  my $arrayRef = shift;
  for my $section (keys(%DATA_MAP)){
    unless ($DATA_MAP{$section}->{capacities}) {
     # Feed the multilines section array in order to parse xml correctly
     push @{ $arrayRef }, uc $section if $DATA_MAP{$section}->{multi};
    }
  }
}

sub _has_changed{
  my $section = shift;
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  
  # Don't use inventory diff if section mask is
  return 1 if $DATA_MAP{$section}->{mask}==0;
   
  # Check checksum to know if section has changed
  if( defined($result->{CONTENT}->{HARDWARE}->{CHECKSUM}) ){
    return $DATA_MAP{$section}->{mask} & $result->{CONTENT}->{HARDWARE}->{CHECKSUM};
  }
  else{
    &_log( 524, 'inventory', "$section (no checksum)") if $ENV{'OCS_OPT_LOGLEVEL'};
    return 1;
  }
}

sub _get_type_id {
#TODO: create it if needed
# Type table structure
# CREATE TABLE type_${section}_$field (
#   ID INTEGER NOT NULL auto_increment,
#   NAME VARCHAR(255))
# ENGINE=INNODB, DEFAULT CHARSET=UTF8 ;
# For migration, add the following line :
# ALTER TABLE $section CHANGE COLUMN $field $field_ID INTEGER NOT NULL ;

  my ($section, $field, $value) = @_ ;
  
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'} ;
  
  my ($id, $existsReq, $createSql);

  my $table_name = 'type_'.lc $section.'_'.lc $field ;

  if ($value eq '') {  #Value from XML is empty 
    $existsReq = $dbh->prepare("SELECT ID FROM $table_name WHERE NAME IS NULL") ;
    $createSql = "INSERT INTO $table_name(NAME) VALUES(NULL)" ;
    $existsReq->execute() ;
  } else {
    $existsReq = $dbh->prepare("SELECT ID FROM $table_name WHERE NAME=?") ;
    $createSql = "INSERT INTO $table_name(NAME) VALUES(?)" ;
    $existsReq->execute($value) ;
  }

  # It exists
  if($existsReq->rows){
    my $row = $existsReq->fetchrow_hashref() ;
    $id = $row->{ID} ;
  }
  # It does not exist
  else{

    if ($value eq '') {
      $dbh->do($createSql) && $existsReq->execute();
    } else { 
      $dbh->do($createSql, {}, $value) && $existsReq->execute($value);
    }

    my $row = $existsReq->fetchrow_hashref() ;
    $id = $row->{ID} ;
  }

  return $id ;
}

sub _get_hardware_fields {
  my $sectionMeta = {};
  my $field;
  my $field_index=0;  #Variable for feeding in _cache function

  #We only get cache fields 
  for $field ( keys(%{$DATA_MAP{'hardware'}->{fields}} ) ){

    if($DATA_MAP{'hardware'}->{fields}->{$field}->{cache}){
      next unless $ENV{OCS_OPT_INVENTORY_CACHE_ENABLED};
      $sectionMeta->{field_cached}->{$field}=$field_index;
      $field_index++;
    }
  }
  return $sectionMeta;
}

1;



