###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2006
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Groups;

use strict;

BEGIN{
	if($ENV{'OCS_MODPERL_VERSION'} == 1){
		require Apache::Ocsinventory::Server::Modperl1;
		Apache::Ocsinventory::Server::Modperl1->import();
        }elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
		require Apache::Ocsinventory::Server::Modperl2;
		Apache::Ocsinventory::Server::Modperl2->import();
        }
}

use Apache::Ocsinventory::Server::System(qw/ :server /);

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / _get_groups / ;

sub _get_groups{
  my($request, @groups);
# We ensure that cache is not out-of-date 
  &_validate_groups_cache();
# Sending the groups the computer is part of
  $request = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'}->prepare('SELECT GROUP_ID FROM groups_cache WHERE HARDWARE_ID=? AND STATIC=0 OR STATIC=1');
  $request->execute( $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'} );
  while( my $row = $request->fetchrow_hashref){
    push @groups, $row->{'GROUP_ID'};
  }
  return @groups;
}

sub _validate_groups_cache{
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  
# Test cache validity
  my $request = $dbh->prepare('SELECT g.HARDWARE_ID FROM groups g LEFT OUTER JOIN locks l ON g.HARDWARE_ID=l.HARDWARE_ID WHERE UNIX_TIMESTAMP()-CREATE_TIME > ? AND l.HARDWARE_ID IS NULL FOR UPDATE');
  while(1){
    # Updating cache when needed
    return unless $request->execute( $ENV{'OCS_OPT_GROUPS_CACHE_REVALIDATE'} );
    if($request->rows){
      my $row = $request->fetchrow_hashref();
      if( !&_lock($row->{'HARDWARE_ID'}) ){
      # Release groups locks
        $dbh->do('UNLOCK TABLES');
      # We lock it like a computer
        &_log(306,'groups','cache out-of-date('.$row->{'HARDWARE_ID'}.')') if $ENV{'OCS_OPT_LOGLEVEL'};
      # We build the new cache
        &_build_group_cache( $row->{'HARDWARE_ID'} );
      # Release cache locks
        $dbh->commit;
      # Unlock group
        &_unlock($row->{'HARDWARE_ID'});
      }
      else{
        sleep(1);
	$request->finish();
        next;
      }
# The group is already locked. 
      $request->finish();
    }
    else{
      last;
    }
  }
}

sub _build_group_cache{
  my $group_id = shift;
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $offset = int rand($ENV{OCS_OPT_GROUPS_CACHE_OFFSET});
  
# Retrieving the group request. It must be a SELECT statement on ID(hardware)
  my $get_request = $dbh->prepare('SELECT REQUEST FROM groups WHERE HARDWARE_ID=?');
  $get_request->execute( $group_id );
  my $row = $get_request->fetchrow_hashref();
  my $group_request = $dbh->prepare( $row->{'REQUEST'} );
  $group_request->execute();
# Build cache request
  my $build_cache = $dbh->prepare('INSERT INTO groups_cache(GROUP_ID, HARDWARE_ID, STATIC) VALUES(?,?,0)');
# Deleting the current cache
  $dbh->do('DELETE FROM groups_cache WHERE GROUP_ID=? AND STATIC=0', {}, $group_id);
# Build the cache
  while( my $cache = $group_request->fetchrow_hashref() ){
    $build_cache->execute($group_id, $cache->{'ID'});
  }
# Updating cache time
  $dbh->do("UPDATE groups SET CREATE_TIME=UNIX_TIMESTAMP(NOW())+? WHERE HARDWARE_ID=?", {}, $group_id, $offset);
  &_log(307,'groups', "revalidate cache($group_id)") if $ENV{'OCS_OPT_LOGLEVEL'};
}
1;










