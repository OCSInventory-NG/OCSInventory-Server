###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2006
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface;

use Apache::Ocsinventory::Interface::Internals;

use strict;

sub get_computers_V1{
	shift;
	my $request = shift;
	my @result;
	my $parsed_request = XML::Simple::XMLin( $request ) or die;
	
	for( search_engine($request, $parsed_request) ){
		push @result, build_xml_inventory($_, 	$parsed_request->{CHECKSUM});
	}
	print STDERR for @result;
	return(@result);
}
1;



















