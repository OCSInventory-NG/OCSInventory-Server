###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2006
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface;

use Apache::Ocsinventory::Interface::Internals;
use Apache::Ocsinventory::Interface::Database;

require Apache::Ocsinventory::Interface::Ipdiscover;
require Apache::Ocsinventory::Interface::Inventory;
require Apache::Ocsinventory::Interface::Config;
require Apache::Ocsinventory::Interface::History;
require Apache::Ocsinventory::Interface::Extensions;
require Apache::Ocsinventory::Interface::Updates;

use strict;

$ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT} = 100 if !defined $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};

eval{
  if( $ENV{OCS_OPT_WEB_SERVICE_PRIV_MODS_CONF} ){
    require $ENV{OCS_OPT_WEB_SERVICE_PRIV_MODS_CONF} or die($!);
  }
};
if($@){
  print STDERR "ocsinventory-server: $@\n";
  print STDERR "ocsinventory-server: Can't load $ENV{OCS_OPT_WEB_SERVICE_PRIV_MODS_CONF} - Web service Private extensions will be unavailable\n";
};


# ===== ACCESSOR TO COMPUTER'S DATA =====

sub get_computers_V1{
  my $class = shift;
# Xml request
  my $request = shift;
  return Apache::Ocsinventory::Interface::Inventory::get_computers( $request );
}

sub delete_computers_by_id_V1{
  my $class = shift ;
  my @ids = @_ ;
  return Apache::Ocsinventory::Interface::Updates::delete_computers_by_id( \@ids );
}

# ===== CONFIGURATION METHODS =====

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

# ===== SOFTWARE DICTIONNARY =====

# Get a software dictionnary word
sub get_dico_soft_element_V1{
  my( $class, $word ) = @_;
  $word = decode_xml( $word );
  return Apache::Ocsinventory::Interface::Inventory::get_dico_soft_extracted( $word );
}

# ===== CHECKSUM UPDATE =====

sub reset_checksum_V1 {
  my $class = shift;
  my $checksum = shift;
  return send_error('BAD_CHECKSUM') unless $checksum =~ /^\d+$/;
  for(@_){
    return send_error('BAD_ID') unless $_ =~ /^\d+$/;
  }
  return Apache::Ocsinventory::Interface::Internals::reset_checksum( $checksum, \@_ );
}

# ===== EVENTS TRACKING =====

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

# ===== IPDISCOVER METHODS =====

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
  $description = decode_xml( $description );
  $type = decode_xml( $type );
  $user = decode_xml( $user );
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
  $type = decode_xml( $type );
  return Apache::Ocsinventory::Interface::Ipdiscover::ipdiscover_add_type( $type );
}

sub ipdiscover_delete_type_V1{
  my $class = shift;
  my $type = shift;
  $type = decode_xml( $type );
  return Apache::Ocsinventory::Interface::Ipdiscover::ipdiscover_del_type( $type );
}
1;
