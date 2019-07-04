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
