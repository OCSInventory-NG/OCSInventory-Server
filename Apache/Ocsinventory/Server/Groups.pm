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
  $request = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'}->prepare('SELECT GROUP_ID FROM groups_cache WHERE HARDWARE_ID=? AND (STATIC=0 OR STATIC=1)');
  $request->execute( $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'} );
  while( my $row = $request->fetchrow_hashref){
    push @groups, $row->{'GROUP_ID'};
  }
  return @groups;
}

sub _validate_groups_cache{
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  
  return unless $ENV{'OCS_OPT_GROUPS_CACHE_REVALIDATE'};
 
  # Test cache validity
  my $request = $dbh->prepare('
    SELECT g.HARDWARE_ID
    FROM `groups` 
    g LEFT OUTER JOIN locks l
    ON l.HARDWARE_ID=g.HARDWARE_ID
    WHERE UNIX_TIMESTAMP()-REVALIDATE_FROM > ?
    AND l.HARDWARE_ID IS NULL'
  );
  # Updating cache when needed
  return unless $request->execute( $ENV{'OCS_OPT_GROUPS_CACHE_REVALIDATE'} );
  while(my $row = $request->fetchrow_hashref()){
    # We lock it like a computer
    if( !&_lock($row->{'HARDWARE_ID'}) ){
      # Check if the group has already been computed
      my $check_request = $dbh->prepare('SELECT HARDWARE_ID, (UNIX_TIMESTAMP()-REVALIDATE_FROM) AS OFF FROM `groups` WHERE UNIX_TIMESTAMP()-REVALIDATE_FROM > ? AND HARDWARE_ID=?'); 
      $check_request->execute($ENV{'OCS_OPT_GROUPS_CACHE_REVALIDATE'}, $row->{'HARDWARE_ID'});
      if(!$check_request->rows()){
        &_unlock($row->{'HARDWARE_ID'});  
        $check_request->finish();
        next;
      }

      &_log(306,'groups','cache_out-of-date('.$row->{'HARDWARE_ID'}.')') if $ENV{'OCS_OPT_LOGLEVEL'};
      # We build the new cache
      &_build_group_cache( $row->{'HARDWARE_ID'} );
      # Unlock group
      &_unlock($row->{'HARDWARE_ID'});
    }
    else{
      &_log(306,'groups','cache_in_process('.$row->{'HARDWARE_ID'}.')') if $ENV{'OCS_OPT_LOGLEVEL'};
    }
  }
}

sub _build_group_cache{
  my $group_id = shift;
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $dbh_sl = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_SL_HANDLE'};
  my $offset = int rand($ENV{OCS_OPT_GROUPS_CACHE_OFFSET});
  
  my (@ids,@quoted_ids);
  my ($id_field, $error);

  # Build cache request
  my $build_cache = $dbh->prepare('INSERT INTO groups_cache(GROUP_ID, HARDWARE_ID, STATIC) VALUES(?,?,0)');
  my $delete_cache = 'DELETE FROM groups_cache WHERE GROUP_ID=? AND STATIC=0';

  # Retrieving the group request. It must be a SELECT statement on ID(hardware)
  my $get_request = $dbh->prepare('SELECT REQUEST,XMLDEF FROM `groups` WHERE HARDWARE_ID=?');
  $get_request->execute( $group_id );
  my $row = $get_request->fetchrow_hashref();
  
  # XMLDEF can contain < and >, we need to encode them but leave the xml tags untouched to avoid malformed xml
  $row->{'XMLDEF'} =~ s/ < / &lt; /g;
  $row->{'XMLDEF'} =~ s/ > / &gt; /g;

  # legacy: one request per group
  if($row->{'REQUEST'} ne '' and $row->{'REQUEST'} ne 'NULL' ){
    my $group_request = $dbh_sl->prepare( $row->{'REQUEST'} );
    if($group_request->execute()){
      # Deleting the current cache
      $dbh->do($delete_cache, {}, $group_id);

      while( my @cache = $group_request->fetchrow_array() ){
        push @ids, $cache[0] ;
      }
      #We verify that HARDWARE_IDs are not groups 
      &verify_ids($row->{'REQUEST'},\@ids);

      # Build the cache
      for( @ids ){
        $build_cache->execute($group_id, $_);
      }
    }
    else{
      &_log(520,'groups','bad_request('.$row->{'HARDWARE_ID'}.')') if $ENV{'OCS_OPT_LOGLEVEL'};
    }
  }
  # New behaviour : multiple requests for one group xml encoded
  elsif( $row->{'XMLDEF'} ne '' and $row->{'XMLDEF'} ne 'NULL' ){
    my $xml = XML::Simple::XMLin($row->{'XMLDEF'}, ForceArray => ['REQUEST'] );

    for my $request (@{$xml->{REQUEST}}){
      if(@ids){
        for(@ids){
	  push @quoted_ids, $dbh_sl->quote($_);
	}

	# When request is from hardware, the id is called "ID", if not "HARDWARE_ID"
	if( $request =~ /^select\s+(distinct\s+)?ID/i ){
	  $id_field = 'ID';
	}
	else{
	  $id_field = 'HARDWARE_ID';
	}

        my $string = join ",", @quoted_ids;
        $request = $request." AND $id_field IN ($string)";
	@ids=@quoted_ids=();
      }

      my $group_request = $dbh_sl->prepare( $request );
      unless($group_request->execute){
        &_log(520,'groups','bad_request('.$row->{'HARDWARE_ID'}.')') if $ENV{'OCS_OPT_LOGLEVEL'};
	last;
      }
      # If no results for one request, we stop the computing (always AND statements)
      if(!$group_request->rows){
         &_log(307,'groups','empty') if $ENV{'OCS_OPT_LOGLEVEL'};
         @ids=@quoted_ids=();
	 $error = 1;
      }

      while( my @cache = $group_request->fetchrow_array() ){
        push @ids, $cache[0];
      }
     
      #We verify that HARDWARE_IDs are not groups 
      $error = &verify_ids($request,\@ids);
    }
    # Deleting the current cache
    $dbh->do($delete_cache, {}, $group_id);

    unless($error){
      # Build the cache
      for( @ids ){
        $build_cache->execute($group_id, $_);
      }
    }
    else{
      &_log(520,'groups','will not build('.$row->{'HARDWARE_ID'}.')') if $ENV{'OCS_OPT_LOGLEVEL'};
    }
  }

# Updating cache time
  $dbh->do("UPDATE `groups` SET CREATE_TIME=UNIX_TIMESTAMP(), REVALIDATE_FROM=UNIX_TIMESTAMP()+? WHERE HARDWARE_ID=?", {}, $offset, $group_id);
  &_log(307,'groups', "revalidate_cache($group_id(".scalar @ids."))") if $ENV{'OCS_OPT_LOGLEVEL'};
}
1;



sub verify_ids {
  my ($request,$ids) = @_;
  my $error;
  my $dbh_sl = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_SL_HANDLE'};

  unless ($request =~ m/and\s+deviceid(\s+)?<>(\s+)?'_SYSTEMGROUP_'\s+and\s+deviceid(\s+)?<>(\s+)?'_DOWNLOADGROUP_'/i) {
     #We will have to verify that HARDWARE_IDs are not groups
     my $ids_list= join(',',@$ids);
     @$ids=();

     my $verify_request = $dbh_sl->prepare("SELECT distinct ID from hardware where ID in ($ids_list) and deviceid <> '_SYSTEMGROUP_' AND deviceid <> '_DOWNLOADGROUP_'");
     $verify_request->execute();

     if(!$verify_request->rows){
       &_log(307,'groups','empty') if $ENV{'OCS_OPT_LOGLEVEL'};
       @$ids=();
       $error = 1;
     }

     while( my @cache = $verify_request->fetchrow_array() ){
       push @$ids, $cache[0];
     }
  } 

  return $error;
}






