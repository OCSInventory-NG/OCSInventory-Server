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
package Apache::Ocsinventory::Interface::Updates;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Server::System;
use Apache::Ocsinventory::Interface::Database;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw //;

sub delete_computers_by_id {
  my $computerIds = shift ;
  my $dbh = &get_dbh_write ;

  for my $hardwareId (@{$computerIds}){
    # We lock the computer to avoid race condition
    next if &_lock($hardwareId, $dbh) ;

    for my $section ( keys %DATA_MAP ){
      my $hardwareIdField = get_hardware_table_pk($section) ;

      # delOnReplace is used here even if the section is not "auto"
      # "auto" is only useful for the import phases
      next if !$DATA_MAP{ $section }->{delOnReplace} ;
      do_sql("DELETE FROM $section WHERE $hardwareIdField=$hardwareId") ;
    }
    &_unlock($hardwareId, $dbh) ;
  }
  return 'OK' ;
}
1;
