###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::SOAP;

BEGIN{
	eval{
		# Use the good modperl transport
		if( $ENV{OCS_MODPERL_VERSION}==1 ){
			require Apache::Ocsinventory::Server::Modperl1;
			Apache::Ocsinventory::Server::Modperl1->import();
			require SOAP::Transport::HTTP;
			SOAP::Transport::HTTP->import();
			use constant SERVER => SOAP::Transport::HTTP::Apache;
		}
		elsif( $ENV{OCS_MODPERL_VERSION}==2 ){
			require Apache::Ocsinventory::Server::Modperl2;
			Apache::Ocsinventory::Server::Modperl2->import();
			require SOAP::Transport::HTTP2;
			SOAP::Transport::HTTP2->import();
			use constant SERVER => SOAP::Transport::HTTP2::Apache;
		}
		our $server = SERVER
			-> dispatch_to('Apache::Ocsinventory::Interface');
	}
}

sub handler {
	our $apache_req = $_[0];
	#return APACHE_FORBIDDEN unless $ENV{WEB_SERVICE_ENABLE};
	$server->handler(@_);
}
1;