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
package Apache::Ocsinventory::Server::Inventory::Cache;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  _reset_inventory_cache 
  _cache
/;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Server::System qw / :server /;

sub _cache{
  my ($op, $section, $sectionMeta, $values ) = @_;
  
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my @fields_array = keys %{ $sectionMeta->{field_cached} };
  
  for my $field ( @fields_array ){
    my $table = $section.'_'.lc $field.'_cache';
    my $err = $dbh->do("SELECT $field FROM $table WHERE $field=?", {}, $values->[ $sectionMeta->{field_cached}->{$field} ]);
    if( $err && $err == 0E0 && $op eq 'add'){
      $dbh->do("INSERT INTO $table($field) VALUES(?)", {}, $values->[ $sectionMeta->{field_cached}->{$field} ]);
    }
    elsif( $err != 0E0 && $op eq 'del'){
      my $err2 = $dbh->do("SELECT $field FROM $section WHERE $field=? LIMIT 0,1", {}, $values->[ $sectionMeta->{field_cached}->{$field} ]);
      if( $err2 && $err2 == 0E0 ){
        $dbh->do("DELETE FROM $table WHERE $field=?", {}, $values->[ $sectionMeta->{field_cached}->{$field} ]);
      }
    }
  }
}

sub _reset_inventory_cache{
  my ( $sectionsMeta, $sectionsList ) = @_;
  
  return if !$ENV{OCS_OPT_INVENTORY_CACHE_REVALIDATE};
  
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  
  if( &_check_cache_validity() ){
    
    &_log(110,'inventory_cache','checking') if $ENV{'OCS_OPT_LOGLEVEL'};
    
    if( &_lock_cache() ){
      for my $section ( @$sectionsList ){
        my @fields_array = keys %{ $sectionsMeta->{$section}->{field_cached} };
        for my $field (@fields_array){
          my $table = $section.'_'.lc $field.'_cache';
          &_log(108,'inventory_cache',"cache($section.$field)") if $ENV{'OCS_OPT_LOGLEVEL'};
          my $src_table = lc $section;
	  $dbh->do("LOCK TABLES $table WRITE, $src_table READ");
          if( $dbh->do("DELETE FROM $table") ){
	    my $err = $dbh->do("INSERT INTO $table($field) SELECT DISTINCT $field FROM $src_table");
	    $dbh->do('UNLOCK TABLES');
	    if( $err ){
              &_log(109,'inventory_cache',"ok:$section.$field") if $ENV{'OCS_OPT_LOGLEVEL'};
            }
            else{
              &_log(522,'inventory_cache',"fault:$section.$field") if $ENV{'OCS_OPT_LOGLEVEL'};
              &_lock_cache_release();
              return;
            }
          }
          else{
            $dbh->do('UNLOCK TABLES');
            &_log(523,'inventory_cache',"fault:$section.$field") if $ENV{'OCS_OPT_LOGLEVEL'};
            &_lock_cache_release();
            return;
          }
        }
      }
    }
    else{
      &_log(111,'inventory_cache','already_handled') if $ENV{'OCS_OPT_LOGLEVEL'};
      return;
    }
    $dbh->do('INSERT INTO engine_persistent(NAME,IVALUE) VALUES("INVENTORY_CACHE_CLEAN_DATE", UNIX_TIMESTAMP(NOW()))')
      if($dbh->do('UPDATE engine_persistent SET IVALUE=UNIX_TIMESTAMP(NOW()) WHERE NAME="INVENTORY_CACHE_CLEAN_DATE"')==0E0);
    
    &_lock_cache_release();
    &_log(109,'inventory_cache','done') if $ENV{'OCS_OPT_LOGLEVEL'};
  }
  else{
    return;
  }
}

sub _check_cache_validity{
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $check_cache = $dbh->prepare('SELECT UNIX_TIMESTAMP(NOW())-IVALUE AS IVALUE FROM engine_persistent WHERE NAME="INVENTORY_CACHE_CLEAN_DATE"');
  $check_cache->execute();
  if($check_cache->rows()){
    my $row = $check_cache->fetchrow_hashref();
    if($row->{IVALUE}< $ENV{OCS_OPT_INVENTORY_CACHE_REVALIDATE}*86400 ){
      return 0;
    }
    else{
      return 1;
    }
  }
  else{
    return 1;
  }
}

sub _lock_cache{
  return $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'}->do("INSERT INTO engine_mutex(NAME, PID, TAG) VALUES('INVENTORY_CACHE_REVALIDATE',?,'ALL')", {}, $$)
}

sub _lock_cache_release{
  return $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'}->do("DELETE FROM engine_mutex WHERE NAME='INVENTORY_CACHE_REVALIDATE' AND PID=?", {}, $$);
}

1;
