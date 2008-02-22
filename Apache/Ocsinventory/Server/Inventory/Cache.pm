###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Inventory::Cache;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  _init_inventory_cache 
  _update_inventory_cache
/;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Server::System qw / :server /;

sub _init_inventory_cache{
  my ( $sectionsMeta, $sectionsList ) = @_;
  
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $check_cache = $dbh->prepare('SELECT UNIX_TIMESTAMP(NOW())-IVALUE AS IVALUE FROM engine_persistent WHERE NAME="INVENTORY_CACHE_CLEAN_DATE"');
  $check_cache->execute();
  if($check_cache->rows()){
    my $row = $check_cache->fetchrow_hashref();
    if($row->{IVALUE}<($ENV{OCS_OPT_INVENTORY_CACHE_REVALIDATE}?$ENV{OCS_OPT_INVENTORY_CACHE_REVALIDATE}*86400:7*86400)){
      return;
    }
  }
  &_log(110,'inventory_cache',"Checking") if $ENV{'OCS_OPT_LOGLEVEL'};
  if( !$dbh->do("INSERT INTO engine_mutex(NAME, PID, TAG) VALUES('CACHE_REVALIDATE',?,'ALL')", {}, $$) ){
    &_log(111,'inventory_cache',"Checking") if $ENV{'OCS_OPT_LOGLEVEL'};
  }
  for my $section ( @$sectionsList ){
    &_inventory_cache( $sectionsMeta, $section, 1 );
  }
  
  $dbh->do('INSERT INTO engine_persistent(NAME,IVALUE) VALUES("INVENTORY_CACHE_CLEAN_DATE", UNIX_TIMESTAMP(NOW()))')
    if($dbh->do('UPDATE engine_persistent SET IVALUE=UNIX_TIMESTAMP(NOW()) WHERE NAME="INVENTORY_CACHE_CLEAN_DATE"')==0E0);
    
  # We release our own mutex
  $dbh->do("DELETE FROM engine_mutex WHERE NAME='CACHE_REVALIDATE' AND PID=?", {}, $$);
  &_log(112,'inventory_cache',"Checking") if $ENV{'OCS_OPT_LOGLEVEL'};
}

sub _update_inventory_cache{
  my ($sectionsMeta, $sectionsList) = @_;
  
  &_init_inventory_cache( $sectionsMeta, $sectionsList );
  
  for(@$sectionsList){
    &_inventory_cache( $sectionsMeta, $_);
  }
}

# Called for each section
# Feed the "cache" table for each field "cache" activated
sub _inventory_cache{
  my ($sectionsMeta, $section, $init) = @_;
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  # A flag to know if there is some cache in the section
  return if !$sectionsMeta->{$section}->{cache} || !$sectionsMeta->{$section}->{hasChanged};
  
  # From the current xml data
  my $base = $result->{CONTENT}->{uc $section};
  # From Map.pm (only field to cache)
  my $fields_array = $sectionsMeta->{$section}->{field_cached};
  
  # See if there is some cache to generate for each field
  for my $field (@$fields_array){
  # We lock the section
    my $table = $section.'_'.lc $field.'_cache';
    if( $init ){
      &_log(108,'inventory_cache',"Cache($section.$field)") if $ENV{'OCS_OPT_LOGLEVEL'};
      my $src_table = lc $section;
      if( $dbh->do("TRUNCATE TABLE $table") ){
        if( $dbh->do("INSERT INTO $table($field) SELECT $field FROM $src_table") ){
          &_log(109,'inventory_cache',"Cache($section.$field)") if $ENV{'OCS_OPT_LOGLEVEL'};
        }
        else{
          &_log(522,'inventory_cache',"Cache($section.$field)") if $ENV{'OCS_OPT_LOGLEVEL'};
        }
      }
      else{
        &_log(522,'inventory_cache',"Cache($section.$field)") if $ENV{'OCS_OPT_LOGLEVEL'};
      }
      next;
    }
    # Prepare queries
    my $select = $dbh->prepare("SELECT $field FROM $table WHERE $field=?");
    my $insert = $dbh->prepare("INSERT INTO $table($field) VALUES(?)");
    # hash ref or array ref ?
    if($sectionsMeta->{$section}->{multi}){
      for(@$base){
        next unless $_->{$field};
        next unless $select->execute($_->{$field});
        # Value is already in the cache
        if($select->rows){
          $select->finish;
          $dbh->commit;
          next;
        }
        # We have to insert the value
        $insert->execute($_->{$field});
      }
    }
    else{
      next unless $base->{$field};
      next unless $select->execute($base->{$field});
      if($select->rows){
        $select->finish;
        next;
      }
      $insert->execute($_->{$field});
    }
  }
}

1;
