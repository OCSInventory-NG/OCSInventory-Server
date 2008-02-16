###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2006
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface::Internals;

use strict;

require Exporter;

# If SOAP lite doesn't decode xml entities
eval {
  require XML::Entities;
};
if($@){
  print STDERR "[".localtime()."] OCSINVENTORY: (SOAP): Cannot find XML::Entities\n";
}

use Apache::Ocsinventory::Interface::Database;
use XML::Simple;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  decode_xml
  encode_xml
  send_error 
/;

sub decode_xml{
  my $data = shift;
  unless( $data =~ /^</ ){
    return XML::Entities::decode('all', $data);
  }
  return $data;
}

sub search_engine{
# Available search engines
  my %search_engines = (
    'first'  => \&engine_first
  );
  &{ $search_engines{ (lc $_[1]->{ENGINE}) } }( @_ );
}

sub engine_first {
  my ($request, $parsed_request, $computers, $begin) = @_;
  my $parsed_request = XML::Simple::XMLin( $request, ForceArray => ['ID', 'TAG', 'USERID'], SuppressEmpty => 1 ) or die;
  my ($id, $name, $userid, $checksum, $tag);
    
# Database ids criteria
  if( $parsed_request->{ID} ){
    $id .= ' AND';
    $id .= ' hardware.ID IN('.join(',', @{ $parsed_request->{ID} }).')';
  }
# Tag criteria
  if( $parsed_request->{TAG} ){
    s/^(.*)$/\"$1\"/ for @{ $parsed_request->{TAG} };
    $tag .= ' AND';
    $tag .= ' accountinfo.TAG IN('.join(',', @{ $parsed_request->{TAG} }).')';
  }
# Checksum criteria (only positive "&" will match
  if( $parsed_request->{CHECKSUM} ){
    $checksum = ' AND ('.$parsed_request->{CHECKSUM}.' & hardware.CHECKSUM)';
  }
# Associated user criteria
  if( $parsed_request->{USERID} ){
    s/^(.*)$/\"$1\"/ for @{ $parsed_request->{USERID} };
    $userid .= ' AND';
    $userid .= ' hardware.USERID IN('.join(',', @{ $parsed_request->{USERID} } ).')';
  }
# Generate sql string
  my $search_string = "SELECT DISTINCT hardware.ID FROM hardware,accountinfo WHERE hardware.DEVICEID NOT LIKE '\\_%' AND hardware.ID=accountinfo.HARDWARE_ID $id $name $userid $checksum $tag ORDER BY LASTDATE LIMIT $begin,$ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT}";
# Play it  
  my $sth = get_sth($search_string);
# Get ids
  while( my $row = $sth->fetchrow_hashref() ){
    push @{$computers}, $row->{ID};
  }
# Destroy request object
  $sth->finish();
}

sub reset_checksum {
  my( $checksum, $ref ) = @_;
  my $where = join(',', @$ref);
  return do_sql("UPDATE hardware SET CHECKSUM=? WHERE ID IN ($where)", $checksum);
}
sub send_error{
  my $error = shift;
  return XMLout ( 
    { 'ERROR' => [ $error ] }, 
    RootName => 'RESULT'
  );
}
1;
