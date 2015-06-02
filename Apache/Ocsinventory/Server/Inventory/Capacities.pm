###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
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
