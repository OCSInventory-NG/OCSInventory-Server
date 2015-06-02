###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface::Ipdiscover;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Internals;
use XML::Simple;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  get_ipdiscover_devices
  ipdiscover_tag
  ipdiscover_untag
  ipdiscover_remove
  ipdiscover_add_type
  ipdiscover_del_type
/;

sub get_ipdiscover_devices{
  my ($date, $offset, $nInv ) = @_;
  
  $offset = $offset*$ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};  
  
  my $sth;
  
  if( !$nInv ){
    $sth = get_sth("SELECT * FROM netmap WHERE DATE>? ORDER BY DATE LIMIT $offset, $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT}", $date);
  }
  else{
    $sth = get_sth("
      SELECT * 
      FROM netmap nm 
      LEFT JOIN networks nw ON nm.MAC=nw.MACADDR 
      LEFT JOIN network_devices nd ON nd.MACADDR=nm.MAC
      WHERE nd.MACADDR IS NULL AND nw.MACADDR IS NULL
      AND DATE>?
      ORDER BY nm.DATE 
      LIMIT $offset, $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT}", $date);
  }
  my @result;
  while( my $row = $sth->fetchrow_hashref() ){
    push @result, &build_xml( $row->{MAC}, $row->{IP}, $row->{MASK}, $row->{DATE}, $row->{NAME} );
  }
  return XMLout( { IFACE => \@result }, rootName => 'RESULT' );
}

sub ipdiscover_tag {
  my ( $device, $description, $type, $user ) = @_;
  return send_error('BAD_TYPE') if do_sql('SELECT * FROM devicetype WHERE NAME=?', $type) == 0E0;
  return send_error('BAD_USER') if do_sql('SELECT * FROM operators WHERE ID=?', $user) == 0E0;
  return do_sql('INSERT INTO network_devices(DESCRIPTION,TYPE,MACADDR,USER) VALUES(?,?,?,?)', ($description, $type, $device, $user ) );
}

sub ipdiscover_untag{
  my $device = shift;
  return do_sql('DELETE FROM network_devices WHERE MACADDR=?', $device);
}

sub ipdiscover_remove{
  my $device = shift;
  return do_sql('DELETE FROM network_devices WHERE MACADDR=?', $device);
}

sub ipdiscover_add_type{
  my $type = shift;
  return 0 if do_sql( 'SELECT * FROM devicetype WHERE NAME=?', $type )!=0E0;
  return do_sql( 'INSERT INTO devicetype(NAME) VALUES(?)', $type );
}

sub ipdiscover_del_type{
  my $type = shift;
  return do_sql( 'DELETE FROM devicetype WHERE NAME=?', $type );
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
