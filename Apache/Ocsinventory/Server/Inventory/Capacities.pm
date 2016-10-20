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
package Apache::Ocsinventory::Server::Inventory::Capacities;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  _pre_options
  _post_options
/;

use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Server::System qw /
  :server 
  _modules_get_pre_inventory_options 
  _modules_get_post_inventory_options
/;

sub _pre_options{
  for(&_modules_get_pre_inventory_options()){
    last if $_== 0;
    if (&$_(\%Apache::Ocsinventory::CURRENT_CONTEXT) == INVENTORY_STOP){
      return INVENTORY_STOP;
    }
  }
}

sub _post_options{
  for(&_modules_get_post_inventory_options()){
    last if $_== 0;
    &$_(\%Apache::Ocsinventory::CURRENT_CONTEXT);
  }
}
1;
