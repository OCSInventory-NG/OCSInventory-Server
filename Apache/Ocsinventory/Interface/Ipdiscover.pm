###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface::Ipdiscover;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Interface::Database;
use XML::Simple;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  get_ipdiscover_devices_V1
/;

sub get_ipdiscover_devices_V1{
  my ($date, $offset ) = @_;
  $offset = $offset*$ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};
  my $sth = get_sth("SELECT * FROM netmap WHERE DATE>? ORDER BY DATE LIMIT $offset, $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT}", $date);
  my @result;
  while( my $row = $sth->fetchrow_hashref() ){
    push @result, &build_xml( $row->{MAC}, $row->{IP}, $row->{MASK}, $row->{DATE}, $row->{NAME} );
  }
  return XMLout( { IFACE => \@result }, rootName => 'RESULT' );
}

sub build_xml{
  my ( $mac, $ip, $mask, $date, $name ) = @_;
  return {
    MAC => [ $mac ],
    IP => [ $ip ],
    MASK => [ $mask ],
    DATE => [ $date ],
    NAME => [ $name ]
  };
}
1;
