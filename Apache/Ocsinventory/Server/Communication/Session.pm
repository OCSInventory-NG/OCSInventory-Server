###############################################################################
## OCSINVENTORY-NG
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
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

sub start_session{
  my $current_context = shift;
  my $dbh = $current_context->{DBI_HANDLE};
  my $deviceId = $current_context->{DEVICEID}; 
  
  clean_sessions( $current_context );
  
  # Trying to start session
  if( !$dbh->do( 'INSERT INTO prolog_conntrack(DEVICEID,PID,TIMESTAMP) VALUES(?,?,UNIX_TIMESTAMP())', {}, $deviceId, $$) ){
    &_log(525,'session', 'failed') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 0;
  }
  &_log(311,'session', 'started') if $ENV{'OCS_OPT_LOGLEVEL'};
  return 1;
}

sub clean_sessions{
  my $current_context = shift;
  my $dbh = $current_context->{DBI_HANDLE};
  
  # To avoid race conditions
  if( !$dbh->do("INSERT INTO engine_mutex(NAME, PID, TAG) VALUES('SESSION',?,'CLEAN')", {}, $$) ){
    &_log(315,'session',"already handled") if $ENV{'OCS_OPT_LOGLEVEL'};
    return;
  }
  
  # We have to make it every SESSION_CLEAN_TIME seconds
  my $check_clean = $dbh->prepare('SELECT UNIX_TIMESTAMP()-IVALUE AS IVALUE FROM engine_persistent WHERE NAME="SESSION_CLEAN_DATE"');
  if($check_clean->execute() && $check_clean->rows()){
    my $row = $check_clean->fetchrow_hashref();
    if($row->{IVALUE}< $ENV{OCS_OPT_SESSION_CLEAN_TIME} ){
      $dbh->do('DELETE FROM engine_mutex WHERE PID=? AND NAME="SESSION" AND TAG="CLEAN"', {}, $$ );
      return;
    }
  }
  &_log(314,'session', "clean(check)") if $ENV{'OCS_OPT_LOGLEVEL'};
  # Delete old sessions
  $dbh->do('INSERT INTO engine_persistent(NAME,IVALUE) VALUES("SESSION_CLEAN_DATE", UNIX_TIMESTAMP())')
    if($dbh->do('UPDATE engine_persistent SET IVALUE=UNIX_TIMESTAMP() WHERE NAME="SESSION_CLEAN_DATE"')==0E0);
    
  my $cleaned = $dbh->do('DELETE FROM prolog_conntrack WHERE UNIX_TIMESTAMP()-TIMESTAMP>?', {}, $ENV{OCS_OPT_SESSION_CLEAN_TIME} );
  
  $dbh->do('DELETE FROM engine_mutex WHERE PID=? AND NAME="SESSION" AND TAG="CLEAN"', {}, $$ );
  
  &_log(316,'session', "clean($cleaned)") if $cleaned && ($cleaned!=0E0) && $ENV{'OCS_OPT_LOGLEVEL'};
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

sub kill_session{
  my $current_context = shift;
  my $dbh = $current_context->{DBI_HANDLE};
  my $deviceId = $current_context->{DEVICEID};
  
  my $code = $dbh->do('DELETE FROM prolog_conntrack WHERE DEVICEID=?', {}, $deviceId);
  if(!$code){
    &_log(527,'session', 'error') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 0;
  }
  elsif( $code != 0E0 ){
    &_log(320,'session', 'end') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 1;
  }
  else{
    return 0;
  }
}

1;
