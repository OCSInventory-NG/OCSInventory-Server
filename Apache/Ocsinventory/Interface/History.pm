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
package Apache::Ocsinventory::Interface::History;

use Apache::Ocsinventory::Interface::Database;
use XML::Simple;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  get_history_events
  clear_history_events
/;

sub get_history_events {
  my $offset = shift;
  my @tmp;
  
  $offset = $offset * $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};
  
  my $sth = get_sth( "SELECT DATE,DELETED,EQUIVALENT FROM deleted_equiv ORDER BY DATE,DELETED LIMIT $offset, $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT}" );
    
  while( my $row = $sth->fetchrow_hashref() ){
    push @tmp, {
        'DELETED' => [ $row->{'DELETED'} ],
        'DATE' => [ $row->{'DATE'} ],
        'EQUIVALENT' => [ $row->{'EQUIVALENT'} ]
    }
  }
  $sth->finish();
  return XML::Simple::XMLout( {'EVENT' => \@tmp} , RootName => 'EVENTS' );
}

sub clear_history_events {
  my $offset = shift;
  my $ok = 0;

  my $sth = get_sth( "SELECT * FROM deleted_equiv ORDER BY DATE,DELETED LIMIT 0,$offset" );
  while( my $row = $sth->fetchrow_hashref() ) {
    if( defined $row->{'EQUIVALENT'} ){
      do_sql('DELETE FROM deleted_equiv WHERE DELETED=? AND DATE=? AND EQUIVALENT=?', $row->{'DELETED'}, $row->{'DATE'}, $row->{'EQUIVALENT'}) or die;
    }
    else{
      do_sql('DELETE FROM deleted_equiv WHERE DELETED=? AND DATE=? AND EQUIVALENT IS NULL', $row->{'DELETED'}, $row->{'DATE'}) or die;
    }
    $ok++;
  }
  return $ok;
}
