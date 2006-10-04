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
# Xml request
	my $request = shift;
	my %build_functions = (
		'INVENTORY' => \&build_xml_inventory,
		'META' => \&build_xml_meta
	);
		
# Returned values
	my @result;
# First xml parsing 
	my $parsed_request = XML::Simple::XMLin( $request ) or die;
# Max number of responses sent back to client
	my $max_responses = 100;
	my @ids;
# Call search_engine stub
	search_engine($request, $parsed_request, \@ids);
# Generate boundaries
	my $begin = $parsed_request->{'BEGIN'} > @ids ? return undef : $parsed_request->{'BEGIN'};
	my $end = $#ids;
	$end = $begin + ($max_responses-1) if ($end-$begin)>$max_responses;
# Type of requested data (meta datas, inventories, special features..
	my $type=$parsed_request->{'ASKING_FOR'}||'INVENTORY';
	$type =~ s/^(.+)$/\U$1/;
	
# Generate xml responses
	for(@ids[$begin..$end]){
			push @result, &{ $build_functions{ $type } }($_, $parsed_request->{CHECKSUM});
	}
# Send
	return(@result);
}
1;



















