###############################################################################
## Copyright 2005-2016 OCSInventory-NG/OCSInventory-Server contributors.
## See the Contributors file for more details about them.
## 
## This file is part of OCSInventory-NG/OCSInventory-ocsreports.
##
## OCSInventory-NG/OCSInventory-Server is free software: you can redistribute
## it and/or modify it under the terms of the GNU General Public License as
## published by the Free Software Foundation, either version 2 of the License,
## or (at your option) any later version.
##
## OCSInventory-NG/OCSInventory-Server is distributed in the hope that it
## will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
## of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.
##
## You should have received a copy of the GNU General Public License
## along with OCSInventory-NG/OCSInventory-ocsreports. if not, write to the
## Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
## MA 02110-1301, USA.
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
