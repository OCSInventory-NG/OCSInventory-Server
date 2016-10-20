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
package Apache::Ocsinventory::Server::Capacities::Download::Inventory;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  get_history_xml 
  get_history_db
  update_history_full
  update_history_diff
/;

use strict;

use Apache::Ocsinventory::Server::System;

sub get_history_xml{
  my $result = shift;
  my $base = $result->{CONTENT}->{DOWNLOAD}->{HISTORY}->{PACKAGE};
  my @ret;
  
  for( @$base ){
    push @ret, $_->{ID};
  }
  return @ret;
}

sub get_history_db{
  my ( $hardwareId, $dbh ) = @_;
  my $sth = $dbh->prepare('SELECT PKG_ID from download_history WHERE HARDWARE_ID=?');
  my @ret;
  
  if( $sth->execute( $hardwareId ) ){
    while( my $row = $sth->fetchrow_hashref ){
      push @ret, $row->{PKG_ID};
    }
  }
  else{
    &_log(2502, 'download');
  }
  return @ret;
}

sub update_history_full{
  my ( $hardwareId, $dbh, $pkgList ) = @_;
  my ( @blacklist, $already_set );
  
  $dbh->do('DELETE FROM download_history WHERE HARDWARE_ID=?', {}, $hardwareId);
  
  my $sth = $dbh->prepare('INSERT INTO download_history(HARDWARE_ID, PKG_ID) VALUE(?,?)');
  
  for my $entry ( @{ $pkgList }) {
  # fix the history handling bug (agent side)
    $already_set=0;
    for(@blacklist){
      if($_ eq $entry){
        $already_set=1;
        last;
      }
    }
    if(!$already_set){
      push @blacklist, $entry;
      $sth->execute( $hardwareId, $entry );
    }
  }
}

sub update_history_diff{
  my ( $hardwareId, $dbh, $fromXml, $fromDb ) = @_;

  my @alreadyhandled;
  
  for my $l_xml (@$fromXml){
    my $found = 0;
    for my $i_db (0..(@{$fromDb}-1)){
      next unless $fromDb->[$i_db];
      if($fromDb->[$i_db] eq $l_xml){
        $found = 1;
        # The value has been found, we have to delete it from the db list
        # (elements remaining will be deleted)
        delete $fromDb->[$i_db];
        last;
      }
    }
    if(!$found){
      $dbh->do( 'INSERT INTO download_history(HARDWARE_ID, PKG_ID) VALUE(?,?)', {}, $hardwareId, $l_xml )
        unless grep /\Q$l_xml\E/, @alreadyhandled; 
    }
    push @alreadyhandled, $l_xml;
  }

  for( @$fromDb ){
    next if !defined ($_);
    $dbh->do( 'DELETE FROM download_history WHERE HARDWARE_ID=? AND PKG_ID=?', {}, $hardwareId, $_ );
  }
}
1;
