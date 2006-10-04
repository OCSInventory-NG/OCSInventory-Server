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
#xml request
	my $request = shift;
#returned values
	my @result;
#first xml parsing 
	my $parsed_request = XML::Simple::XMLin( $request ) or die;
#max number of responses sent back to client
	my $max_responses = 100;
	my @ids;
#call search_engine stub
	search_engine($request, $parsed_request, \@ids);
#generate boundaries
	my $begin = $parsed_request->{'BEGIN'} > @ids ? return undef : $parsed_request->{'BEGIN'};
	my $end = $#ids;
	$end = $begin + ($max_responses-1) if ($end-$begin)>$max_responses;
	
#generate xml responses
	for(@ids[$begin..$end]){
		push @result, build_xml_inventory($_, $parsed_request->{CHECKSUM});
	}
#send
	return(@result);
}
1;



















