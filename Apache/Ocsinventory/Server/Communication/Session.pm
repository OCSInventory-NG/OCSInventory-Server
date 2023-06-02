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
package Apache::Ocsinventory::Server::Communication::Session;

use strict;

use Apache::Ocsinventory::Server::System(qw/ :server /);

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  start_session
  check_session
  kill_session
/;

sub start_session {
  my $current_context = shift;
  my $dbh = $current_context->{DBI_HANDLE};
  my $deviceId = $current_context->{DEVICEID}; 
  
  clean_sessions( $current_context );
  
  # Trying to start session
  if( !$dbh->do('INSERT INTO prolog_conntrack(DEVICEID,PID,TIMESTAMP) VALUES(?,?,UNIX_TIMESTAMP())', {}, $deviceId, $$)){
    &_log(525, 'session', 'failed') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 0;
  }
  &_log(311, 'session', 'started') if $ENV{'OCS_OPT_LOGLEVEL'};
  return 1;
}

sub clean_sessions {
  my $current_context = shift;
  my $dbh = $current_context->{DBI_HANDLE};

  # Check if entry already in engine_mutex to prevent duplicate entry error
  my $request = $dbh->prepare('SELECT `NAME` FROM `engine_mutex` WHERE `NAME` = "SESSION" AND `TAG` = "CLEAN"');
  $request->execute;

  my $resultVerif = undef;

  while(my $row = $request->fetchrow_hashref()) {
    $resultVerif = $row->{NAME};
  }

  if(defined $resultVerif) {
    &_log(315, "session", "already handled") if $ENV{'OCS_OPT_LOGLEVEL'};
    return;
  } else {
    $dbh->do("INSERT INTO engine_mutex(NAME, PID, TAG) VALUES('SESSION',?,'CLEAN')", {}, $$);
  }
  
  # We have to make it every SESSION_CLEAN_TIME seconds
  my $check_clean = $dbh->prepare('SELECT UNIX_TIMESTAMP()-IVALUE AS IVALUE FROM engine_persistent WHERE NAME = "SESSION_CLEAN_DATE"');

  if($check_clean->execute() && $check_clean->rows()){
    my $row = $check_clean->fetchrow_hashref();

    if($row->{IVALUE} < $ENV{OCS_OPT_SESSION_CLEAN_TIME}){
      $dbh->do('DELETE FROM engine_mutex WHERE PID = ? AND NAME = "SESSION" AND TAG = "CLEAN"', {}, $$);
      return;
    }
  }

  &_log(314, "session", "clean(check)") if $ENV{'OCS_OPT_LOGLEVEL'};

  # Delete old sessions
  my $updateRequest = $dbh->do('UPDATE engine_persistent SET IVALUE=UNIX_TIMESTAMP() WHERE NAME="SESSION_CLEAN_DATE"');

  if($updateRequest == 0E0) {
    $dbh->do('INSERT INTO engine_persistent(NAME, IVALUE) VALUES("SESSION_CLEAN_DATE", UNIX_TIMESTAMP())');
  }
    
  my $cleaned = $dbh->do('DELETE FROM prolog_conntrack WHERE UNIX_TIMESTAMP()-TIMESTAMP > ?', {}, $ENV{OCS_OPT_SESSION_CLEAN_TIME});
  
  $dbh->do('DELETE FROM engine_mutex WHERE PID = ? AND NAME = "SESSION" AND TAG = "CLEAN"', {}, $$);
  
  &_log(316, "session", "clean($cleaned)") if $cleaned && ($cleaned != 0E0) && $ENV{'OCS_OPT_LOGLEVEL'};
}

sub check_session {
  my $current_context = shift;
  my $dbh = $current_context->{DBI_HANDLE};
  my $deviceId = $current_context->{DEVICEID};
  
  unless( $ENV{OCS_OPT_SESSION_VALIDITY_TIME} ){
    &_log(317,'session', 'always_true') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 1;
  }
  
  my $check = $dbh->do('SELECT DEVICEID FROM prolog_conntrack WHERE DEVICEID=? AND (UNIX_TIMESTAMP()-TIMESTAMP<?)',
               {}, $deviceId, $ENV{OCS_OPT_SESSION_VALIDITY_TIME});
  if(!$check){
    &_log(526,'session', 'error') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 0;
  }
  elsif($check==0E0){
    &_log(318,'session', 'missing') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 0;
  }
  else{
    &_log(319,'session', 'found') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 1;
  }
}

sub kill_session {
  my $current_context = shift;
  my $dbh = $current_context->{DBI_HANDLE};
  my $deviceId = $current_context->{DEVICEID};
  
  my $code = $dbh->do('DELETE FROM prolog_conntrack WHERE DEVICEID = ?', {}, $deviceId);

  if(!$code) {
    &_log(527, 'session', 'error') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 0;
  } elsif( $code != 0E0 ) {
    &_log(320, 'session', 'end') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 1;
  } else {
    return 0;
  }
}

1;
