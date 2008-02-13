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
use Apache::Ocsinventory::Interface::Ipdiscover;
use Apache::Ocsinventory::Interface::Inventory;
use Apache::Ocsinventory::Interface::Config;
use Apache::Ocsinventory::Interface::History;
use Apache::Ocsinventory::Interface::Extensions;

use strict;

sub get_computers_V1{
  my $class = shift;
# Xml request
  my $request = shift;
  $request = decode_xml( $request );
  my %build_functions = (
    'INVENTORY' => \&Apache::Ocsinventory::Interface::Inventory::build_xml_inventory,
    'META' => \&Apache::Ocsinventory::Interface::Inventory::build_xml_meta
  );
    
# Returned values
  my @result;
# First xml parsing 
  my $parsed_request = XML::Simple::XMLin( $request ) or die($!);
# Max number of responses sent back to client
  my $max_responses = 100;
  my @ids;
# Call search_engine stub
  Apache::Ocsinventory::Interface::Internals::search_engine($request, $parsed_request, \@ids);
# Generate boundaries
  my $begin = $parsed_request->{'BEGIN'} > @ids ? return undef : $parsed_request->{'BEGIN'};
  my $end = $#ids;
  $end = $begin + ($max_responses-1) if ($end-$begin)>$max_responses;
# Type of requested data (meta datas, inventories, special features..
  my $type=$parsed_request->{'ASKING_FOR'}||'INVENTORY';
  $type =~ s/^(.+)$/\U$1/;
  
# Generate xml responses
  for(@ids[$begin..$end]){
      push @result, &{ $build_functions{ $type } }($_, $parsed_request->{CHECKSUM}, $parsed_request->{WANTED});#Wanted=>special sections bitmap
  }
# Send
  return "<COMPUTERS>\n", @result, "</COMPUTERS>\n";
}

# Read a general config parameter
# If a value is provided, set it to given parameters
# If only a tvalue is given, set ivalue to NULL
sub ocs_config_V1{
  my $class = shift;
  my ($key, $value) = @_;
  
  if( defined( $key ) and defined( $value ) ){
    $key = decode_xml( $key );
    $value = decode_xml( $value );
    Apache::Ocsinventory::Interface::Config::ocs_config_write( $key, $value, undef ) if defined($value);
  }
  return Apache::Ocsinventory::Interface::Config::ocs_config_read( $key, 1 );
}

sub ocs_config_V2{
  my $class = shift;
  my ($key, $ivalue, $tvalue) = @_;
  
  if( defined( $key ) and ( defined( $ivalue) or defined( $tvalue) ) ){
    $key = decode_xml( $key );
    $ivalue = decode_xml( $ivalue ) if defined $ivalue;
    $tvalue = decode_xml( $tvalue ) if defined $tvalue;
    my ($error, $result ) = Apache::Ocsinventory::Interface::Config::ocs_config_write( $key, $ivalue, $tvalue );
    return $result if $error;
  }
  return Apache::Ocsinventory::Interface::Config::ocs_config_read( $key, 0 );
}

# Get a software dictionnary word
sub get_dico_soft_element_V1{
  my( $class, $word ) = @_;
  return Apache::Ocsinventory::Interface::Inventory::get_dico_soft_extracted( $word );
}
# Get ipdiscover network device(s)
# sub network_devices_V1{
#   my $class = shift;
#   
# }

# Get computer's history
sub get_history_V1{
  my( $class, $begin, $num ) = @_;
  return Apache::Ocsinventory::Interface::History::get_history_events( $begin, $num );
}

# Clear computer's history
sub clear_history_V1{
  my( $class, $begin, $num ) = @_;
  return Apache::Ocsinventory::Interface::History::clear_history_events( $begin, $num );
}

sub reset_checksum_V1 {
  my $class = shift;
  my $checksum = shift;
  return Apache::Ocsinventory::Interface::Internals::reset_checksum( $checksum, \@_ );
}

sub get_ipdiscover_devices_V1{
  my $class = shift;
  my $offset = shift;
  return Apache::Ocsinventory::Interface::Ipdiscover::get_ipdiscover_devices_V1( $offset );
}
1;
