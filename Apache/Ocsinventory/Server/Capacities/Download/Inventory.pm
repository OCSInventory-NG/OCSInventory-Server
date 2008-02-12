 ################################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Capacities::Download::Inventory;

use strict;

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
  my $dbh = shift;
  my $sth = $dbh->prepare('SELECT PKD_ID from download_history WHERE HARDWARE_ID=?');
  my @ret;
  
  if( $sth->execute ){
    while( $row = $sth->fetchrow_hashref ){
      push @ret, $row->{PKG_ID};
    }
  else{
    &_log(2502, 'download');
  }
  return @ret;
}

sub update_history_full{
  my ( $hardwareId, $dbh, $pkgList ) = @_;
  my ( @blacklist, $already_set );
  
  my $sth = $dbh->prepare('INSERT INTO download_history(HARDWARE_ID, PKG_ID) VALUE(?,?)');
  
  $dbh->do('DELETE FROM download_history WHERE HARDWARE_ID=?', {}, $hardware_id);
  
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
      $sth->execute( $hardware_id, $entry );
    }
  }
}

sub update_history_diff{
  my ( $dbh, $hardwareId, $fromXml, $fromDb ) = @_;
  
  for my $l_xml (@$fromXml){
    my $found = 0;
    for my $i_db (0..(@{$fromDb}-1)){
      next unless $fromDb->[$i_db];
      $found = 1 if $fromDb->[$i_db] eq $l_xml;
      # The value has been found, we have to delete it from the db list
      # (elements remaining will be deleted)
      delete $fromDb->[$i_db];
      last;
    }
    if(!$found){
      $dbh->do( 'INSERT INTO download_history(HARDWARE_ID, PKG_ID) VALUE(?,?)', {}, $hardwareId, $l_xml );
    }
  }
  
  for( @$fromDb ){
    $dbh->do( 'DELETE FROM download_history WHERE HARDWARE_ID=? AND PKG_ID=?', {}, $hardwareId, $_ );
  }
}
1;
