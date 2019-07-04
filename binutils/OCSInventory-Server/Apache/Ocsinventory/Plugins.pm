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

package Apache::Ocsinventory::Plugins;

use strict;

BEGIN {
	push @INC, $ENV{OCS_PLUGINS_PERL_DIR};
}

#Loading plugins modules
if($ENV{'OCS_MODPERL_VERSION'} == 1){
	Apache->httpd_conf("Include $ENV{OCS_PLUGINS_CONF_DIR}");
}elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
        use Apache2::ServerUtil();
        Apache2::ServerUtil->server->add_config(["Include $ENV{OCS_PLUGINS_CONF_DIR}"]);
	
}else{
  if(!defined($ENV{'OCS_MODPERL_VERSION'})){
    die("OCS_MODPERL_VERSION not defined. Abort\n");
  }else{
    die("OCS_MODPERL_VERSION set to, a bad parameter. Must be '1' or '2'. Abort\n");
  }
}
 
1;
