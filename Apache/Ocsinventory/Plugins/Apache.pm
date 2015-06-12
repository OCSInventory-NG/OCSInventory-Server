################################################################################
## OCSINVENTORY-NG
## Copyleft Gilles DUBOIS 2015
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################

package Apache::Ocsinventory::Plugins::Apache ;

  use SOAP::Transport::HTTP;

  my $server = SOAP::Transport::HTTP::Apache
    -> dispatch_to('Apache::Ocsinventory::Plugins::Modules'); 

  sub handler { $server->handler(@_) }

  1;