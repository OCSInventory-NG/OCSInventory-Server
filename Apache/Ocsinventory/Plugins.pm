##############################################################################
## OCSINVENTORY-NG 
## Copyleft Guillaume PROTET 2013
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
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
