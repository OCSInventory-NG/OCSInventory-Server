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
use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Ipdiscover;
use Apache::Ocsinventory::Interface::Inventory;
use Apache::Ocsinventory::Interface::Config;
use Apache::Ocsinventory::Interface::History;
use Apache::Ocsinventory::Interface::Extensions;

use strict;

$ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT} = 100 if !defined $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};

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
  my $max_responses = $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};
  my @ids;
  # Generate boundaries
  my $begin;
  if( defined $parsed_request->{'OFFSET'} ){
    $begin = $parsed_request->{'OFFSET'}*$ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};
  }
  elsif( defined $parsed_request->{'BEGIN'}){
    $begin = $parsed_request->{'BEGIN'};
  }
  else{
    $begin = 0;
  }
# Call search_engine stub
  Apache::Ocsinventory::Interface::Internals::search_engine($request, $parsed_request, \@ids, $begin);
# Type of requested data (meta datas, inventories, special features..
  my $type=$parsed_request->{'ASKING_FOR'}||'INVENTORY';
  $type =~ s/^(.+)$/\U$1/;
  
# Generate xml responses
  for(@ids){
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
  
  return send_error('BAD_KEY') unless $key =~ /^[_\w]+$/;
  
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
  
  return send_error('BAD_KEY') unless $key =~ /^[_\w]+$/;
  
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
  my( $class, $offset ) = @_;
  return send_error('BAD_OFFSET') unless $offset =~ /^\d+$/;
  return Apache::Ocsinventory::Interface::History::get_history_events( $offset );
}

# Clear computer's history
sub clear_history_V1{
  my( $class, $offset ) = @_;
  return send_error('BAD_OFFSET') unless $offset =~ /^\d+$/;
  return Apache::Ocsinventory::Interface::History::clear_history_events( $offset );
}

sub reset_checksum_V1 {
  my $class = shift;
  my $checksum = shift;
  return send_error('BAD_CHECKSUM') unless $checksum =~ /^\d+$/;
  for(@_){
    return send_error('BAD_ID') unless $_ =~ /^\d+$/;
  }
  return Apache::Ocsinventory::Interface::Internals::reset_checksum( $checksum, \@_ );
}

sub get_ipdiscover_devices_V1{
  my $class = shift;
  my ( $date, $offset, $nInv ) = @_;
  $nInv = 0 if !defined $nInv;
  return send_error('BAD_DATE') unless $date =~ /^\d{4}-\d{2}-\d{2}(\s\d\d(:\d\d){2})?$/; 
  return send_error('BAD_OFFSET') unless $offset =~ /^\d+$/;
  return send_error('BAD_THIRD_PARAMETER') unless $nInv =~ /^(?:0|1)$/;
  return Apache::Ocsinventory::Interface::Ipdiscover::get_ipdiscover_devices( $date, $offset, $nInv );
}

sub ipdiscover_tag_V1 {
  my $class = shift;
  my ( $device, $description, $type, $user ) = @_;
  return send_error('BAD_MAC') unless $device =~ /^\w\w(.\w\w){5}$/;
  return Apache::Ocsinventory::Interface::Ipdiscover::ipdiscover_tag( $device, $description, $type, $user );
}

sub ipdiscover_untag_V1{
  my $class = shift;
  my $device = shift;
  return send_error('BAD_MAC') unless $device =~ /^\w\w(.\w\w){5}$/;
  return Apache::Ocsinventory::Interface::Ipdiscover::ipdiscover_untag( $device );
}

sub ipdiscover_remove_V1{
  my $class = shift;
  my $device = shift;
  return send_error('BAD_MAC') unless $device =~ /^\w\w(.\w\w){5}$/;
  return Apache::Ocsinventory::Interface::Ipdiscover::ipdiscover_remove( $device );
}

sub ipdiscover_create_type_V1{
  my $class = shift;
  my $type = shift;
  return Apache::Ocsinventory::Interface::Ipdiscover::ipdiscover_add_type( $type );
}

sub ipdiscover_delete_type_V1{
  my $class = shift;
  my $type = shift;
  return Apache::Ocsinventory::Interface::Ipdiscover::ipdiscover_del_type( $type );
}
1;
