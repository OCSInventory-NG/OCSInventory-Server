###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::SOAP;

BEGIN{
	eval{
		if($ENV{OCS_MODPERL_VERSION}==1){
			require Apache::Ocsinventory::Server::Modperl1;
			Apache::Ocsinventory::Server::Modperl1->import();
			require SOAP::Transport::HTTP;
			our $server = SOAP::Transport::HTTP::Apache->dispatch_to('Apache::Ocsinventory::Interface');
		}
		elsif( $ENV{OCS_MODPERL_VERSION}==2 ){
			require Apache::Ocsinventory::Server::Modperl2;
			Apache::Ocsinventory::Server::Modperl2->import();
			require SOAP::Transport::HTTP2;
			our $server = SOAP::Transport::HTTP2::Apache->dispatch_to('Apache::Ocsinventory::Interface');
		}
		$XML::Simple::PREFERRED_PARSER = 'XML::Parser';	
		require Apache::Ocsinventory::Interface;
	};
	if($@){
	  print STDERR "ocsinventory-server: Can't load SOAP::Transport::HTTP* - Web service will be unavailable\n";
	}
}

sub handler {
	our $apache_req = $_[0];	
	return APACHE_FORBIDDEN unless $ENV{OCS_OPT_WEB_SERVICE_ENABLED};
	$server->handler(@_);
}
1;
