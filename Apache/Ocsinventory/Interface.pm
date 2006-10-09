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
	my $class = shift;
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

# Read a general config parameter
# If a value is provided, set it to given parameters
# If only a tvalue is given, set ivalue to NULL
sub ocs_config{
	my $class = shift;
	my ($key, $value) = @_;
	return undef unless $key;
	ocs_config_write( $key, $value ) if defined($value);
	return ocs_config_read( $key );
}
# Get a software dictionnary word
sub get_dico_soft_element{
	my $class = shift;
	my $word = shift;
	return get_dico_soft_extracted( $word );
}
# Get ipdiscover network device(s)
sub network_devices{
	my $class = shift;
	
}
1;



















