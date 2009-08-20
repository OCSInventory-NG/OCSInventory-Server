################################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Capacities::Download;

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

use Apache::Ocsinventory::Server::System;
use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Server::Capacities::Download::Inventory;

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
  'NAME' => 'DOWNLOAD',
  'HANDLER_PROLOG_READ' => undef,
  'HANDLER_PROLOG_RESP' => \&download_prolog_resp,
  'HANDLER_PRE_INVENTORY' => undef,
  'HANDLER_POST_INVENTORY' => \&download_post_inventory,
  'REQUEST_NAME' => 'DOWNLOAD',
  'HANDLER_REQUEST' => \&download_handler,
  'HANDLER_DUPLICATE' => \&download_duplicate,
  'TYPE' => OPTION_TYPE_ASYNC,
  'XML_PARSER_OPT' => {
      'ForceArray' => ['PACKAGE']
  }
};

sub download_prolog_resp{
  
  my $current_context = shift;
  my $resp = shift;

  my $dbh = $current_context->{'DBI_HANDLE'};
  my $groups = $current_context->{'MEMBER_OF'};
  my $hardware_id = $current_context->{'DATABASE_ID'};
  
  my $groupsParams = $current_context->{'PARAMS_G'};
  my ( $downloadSwitch, $cycleLatency, $fragLatency, $periodLatency, $periodLength, $timeout);
  

  my($pack_sql, $hist_sql);
  my($pack_req, $hist_req);
  my($hist_row, $pack_row);
  my(@packages, @history, @dont_repeat);
  my $blacklist;
  
  if($ENV{'OCS_OPT_DOWNLOAD'}){
    $downloadSwitch = 1;
    # Group's parameters
    for(keys(%$groupsParams)){
      
      $cycleLatency = $$groupsParams{$_}->{'DOWNLOAD_CYCLE_LATENCY'}->{'IVALUE'} 
        if ( (exists($$groupsParams{$_}->{'DOWNLOAD_CYCLE_LATENCY'}->{'IVALUE'}) 
        and $$groupsParams{$_}->{'DOWNLOAD_CYCLE_LATENCY'}->{'IVALUE'} > $cycleLatency)
        or !$cycleLatency);
      
      $fragLatency = $$groupsParams{$_}->{'DOWNLOAD_FRAG_LATENCY'}->{'IVALUE'} 
        if ( (exists($$groupsParams{$_}->{'DOWNLOAD_FRAG_LATENCY'}->{'IVALUE'}) 
        and $$groupsParams{$_}->{'DOWNLOAD_FRAG_LATENCY'}->{'IVALUE'} > $fragLatency)
        or !$fragLatency);
      
      $periodLatency = $$groupsParams{$_}->{'DOWNLOAD_PERIOD_LATENCY'}->{'IVALUE'} 
        if ( (exists($$groupsParams{$_}->{'DOWNLOAD_PERIOD_LATENCY'}->{'IVALUE'}) 
        and $$groupsParams{$_}->{'DOWNLOAD_PERIOD_LATENCY'}->{'IVALUE'} > $periodLatency)
        or !$periodLatency);
      
      $timeout = $$groupsParams{$_}->{'DOWNLOAD_TIMEOUT'}->{'IVALUE'} 
        if ( (exists($$groupsParams{$_}->{'DOWNLOAD_TIMEOUT'}->{'IVALUE'}) 
        and $$groupsParams{$_}->{'DOWNLOAD_TIMEOUT'}->{'IVALUE'} < $timeout) 
        or !$timeout);
      
      $periodLength = $$groupsParams{$_}->{'DOWNLOAD_PERIOD_LENGTH'}->{'IVALUE'}
        if ( (exists($$groupsParams{$_}->{'DOWNLOAD_PERIOD_LENGTH'}->{'IVALUE'}) 
        and $$groupsParams{$_}->{'DOWNLOAD_PERIOD_LENGTH'}->{'IVALUE'} < $periodLength) 
        or !$periodLength); 
      
      $downloadSwitch = $$groupsParams{$_}->{'DOWNLOAD_SWITCH'}->{'IVALUE'}
        if exists( $$groupsParams{$_}->{'DOWNLOAD_SWITCH'}->{'IVALUE'} ) 
        and $$groupsParams{$_}->{'DOWNLOAD_SWITCH'}->{'IVALUE'} < $downloadSwitch;
    }  
  }
  else{
    $downloadSwitch = 0;
  }
  
  $downloadSwitch = $current_context->{'PARAMS'}{'DOWNLOAD_SWITCH'}->{'IVALUE'} 
      if defined($current_context->{'PARAMS'}{'DOWNLOAD_SWITCH'}->{'IVALUE'}) and $downloadSwitch;
      
  $cycleLatency   = $ENV{'OCS_OPT_DOWNLOAD_CYCLE_LATENCY'} unless $cycleLatency;
  $fragLatency   = $ENV{'OCS_OPT_DOWNLOAD_FRAG_LATENCY'} unless $fragLatency;
  $periodLatency   = $ENV{'OCS_OPT_DOWNLOAD_PERIOD_LATENCY'} unless $periodLatency;
  $periodLength   = $ENV{'OCS_OPT_DOWNLOAD_PERIOD_LENGTH'} unless $periodLength;
  $timeout  = $ENV{'OCS_OPT_DOWNLOAD_TIMEOUT'} unless $timeout;
  
  push @packages,{
    'TYPE'       => 'CONF',
    'ON'       => $downloadSwitch,
    'TIMEOUT'     => $current_context->{'PARAMS'}{'DOWNLOAD_TIMEOUT'}->{'IVALUE'}
            || $timeout,
    'PERIOD_LENGTH'   => $current_context->{'PARAMS'}{'DOWNLOAD_PERIOD_LENGTH'}->{'IVALUE'}   
            || $periodLength,
    'PERIOD_LATENCY'   => $current_context->{'PARAMS'}{'DOWNLOAD_PERIOD_LATENCY'}->{'IVALUE'} 
            || $periodLatency,
    'FRAG_LATENCY'     => $current_context->{'PARAMS'}{'DOWNLOAD_FRAG_LATENCY'}->{'IVALUE'}   
            || $fragLatency,
    'CYCLE_LATENCY'   => $current_context->{'PARAMS'}{'DOWNLOAD_CYCLE_LATENCY'}->{'IVALUE'}   
            || $cycleLatency
  };
  
  if($downloadSwitch){
  
# If this option is set, we send only the needed package to the agent
# Can be a performance issue
# Agents prior to 4.0.3.0 do not send history data
    $hist_sql = q {
      SELECT PKG_ID
      FROM download_history
      WHERE HARDWARE_ID=?
    };
    $hist_req = $dbh->prepare( $hist_sql );
    $hist_req->execute( $hardware_id );
    
    while( $hist_row = $hist_req->fetchrow_hashref ){
      push @history, $hist_row->{'PKG_ID'};
    }
    
    if( $current_context->{'EXIST_FL'} && $ENV{'OCS_OPT_ENABLE_GROUPS'} && @$groups ){
      $pack_sql =  q {
        SELECT IVALUE,FILEID,INFO_LOC,PACK_LOC,CERT_PATH,CERT_FILE
        FROM devices,download_enable
        WHERE HARDWARE_ID=? 
        AND devices.NAME='DOWNLOAD'
        AND download_enable.ID=devices.IVALUE
      };
      
      my $verif_affected = 'SELECT TVALUE FROM devices WHERE HARDWARE_ID=? AND IVALUE=? AND NAME="DOWNLOAD"';
      my $trace_event = 'INSERT INTO devices(HARDWARE_ID,NAME,IVALUE,TVALUE) VALUES(?,"DOWNLOAD",?,NULL)';
      $pack_req = $dbh->prepare( $pack_sql );
            
      for( @$groups ){
        $pack_req->execute( $_ );
        while( $pack_row = $pack_req->fetchrow_hashref ){
          my $fileid = $pack_row->{'FILEID'};
          if( (grep /^$fileid$/, @history) or (grep /^$fileid$/, @dont_repeat)){
            next;
          }

          if( $ENV{'OCS_OPT_DOWNLOAD_GROUPS_TRACE_EVENTS'} ){
            # We verify if the package is already traced and not already in history
            my $verif_affected_sth = $dbh->prepare($verif_affected);
            $verif_affected_sth->execute($hardware_id, $pack_row->{'IVALUE'});
            if($verif_affected_sth->rows){
              my $verif_affected_row = $verif_affected_sth->fetchrow_hashref();
              if($verif_affected_row!~/NULL/ && $verif_affected_row!~/NOTIFIED/){
                $verif_affected_sth->finish();
                # We do not send package if the current state is fed
                next;
              }
            }
            else{
              $dbh->do($trace_event, {}, $hardware_id, $pack_row->{'IVALUE'})
            }
          }
          
          push @packages,{
            'TYPE'    => 'PACK',
            'ID'    => $pack_row->{'FILEID'},
            'INFO_LOC'  => $pack_row->{'INFO_LOC'},
            'PACK_LOC'  => $pack_row->{'PACK_LOC'},
            'CERT_PATH'  => $pack_row->{'CERT_PATH'}?$pack_row->{'CERT_PATH'}:'INSTALL_PATH',
            'CERT_FILE'  => $pack_row->{'CERT_FILE'}?$pack_row->{'CERT_FILE'}:'INSTALL_PATH'
          };
          push @dont_repeat, $fileid;
        }
      }
    }
  
    $pack_sql =  q {
      SELECT FILEID, INFO_LOC, PACK_LOC, CERT_PATH, CERT_FILE, SERVER_ID
      FROM devices,download_enable 
      WHERE HARDWARE_ID=? 
      AND devices.IVALUE=download_enable.ID 
      AND devices.NAME='DOWNLOAD'
      AND (TVALUE IS NULL OR TVALUE='NOTIFIED')
    };
      
    $pack_req = $dbh->prepare( $pack_sql );
    # Retrieving packages associated to the current device
    $pack_req->execute( $hardware_id );
    
    while($pack_row = $pack_req->fetchrow_hashref()){
      my $fileid = $pack_row->{'FILEID'};
      my $pack_loc = $pack_row->{'PACK_LOC'};
      # If the package is in history, the device will not be notified
      # We have to show this behaviour to user. We use the package events.
      if( grep /^$fileid$/, @history ){
       $dbh->do(q{ UPDATE devices SET TVALUE='ERR_ALREADY_IN_HISTORY', COMMENTS=? WHERE NAME='DOWNLOAD' AND HARDWARE_ID=? AND IVALUE=? }, {},  scalar localtime(), $current_context->{'DATABASE_ID'}, $fileid ) ;
        next ;
      }
      if( grep /^$fileid$/, @dont_repeat){
        next;
      }
      
      # Substitude $IP$ with server ipaddress or $NAME with server name
      my %substitute = (
        '$IP$'   => {'table' => 'hardware', 'field' => 'IPADDR'},
        '$NAME$' => {'table' => 'hardware', 'field' => 'NAME'}
      );

      for my $motif (keys(%substitute)){
        if($pack_loc=~/\Q$motif\E/){
          
          my( $srvreq, $srvreq_sth, $srvreq_row);
          my $field = $substitute{$motif}->{field};
          my $table = $substitute{$motif}->{table};

          $srvreq = "select $field from $table where ID=?";
          $srvreq_sth = $dbh->prepare($srvreq);
          $srvreq_sth->execute($pack_row->{'SERVER_ID'});

          if($srvreq_row=$srvreq_sth->fetchrow_hashref()){
            my $template = $srvreq_row->{$field};
            $pack_loc =~ s/(.*)\Q$motif\E(.*)/${1}${template}${2}/g;
          }
          else{
            $pack_loc='';
          }
        }
      }

      next if $pack_loc eq '';

      push @packages,{
        'TYPE'    => 'PACK',
        'ID'    => $pack_row->{'FILEID'},
        'INFO_LOC'  => $pack_row->{'INFO_LOC'},
        'PACK_LOC'  => $pack_loc,
        'CERT_PATH'  => $pack_row->{'CERT_PATH'}?$pack_row->{'CERT_PATH'}:'INSTALL_PATH',
        'CERT_FILE'  => $pack_row->{'CERT_FILE'}?$pack_row->{'CERT_FILE'}:'INSTALL_PATH'
      };
      push @dont_repeat, $fileid;
    }
    $dbh->do(q{ UPDATE devices SET TVALUE='NOTIFIED', COMMENTS=? WHERE NAME='DOWNLOAD' AND HARDWARE_ID=? AND TVALUE IS NULL }
    ,{},  scalar localtime(), $current_context->{'DATABASE_ID'} ) if $pack_req->rows;
  }

  push @{ $resp->{'OPTION'} },{
    'NAME'   => ['DOWNLOAD'],
    'PARAM' => \@packages
  };
    
  return 0;
}

sub download_post_inventory{
  my $current_context = shift;
  
  return if !$ENV{'OCS_OPT_DOWNLOAD'};
  
  my $dbh = $current_context->{'DBI_HANDLE'};
  my $hardwareId = $current_context->{'DATABASE_ID'};
  my $result = $current_context->{'XML_ENTRY'}; 
    
  my @fromXml = get_history_xml( $result );
  my @fromDb;
  
  if( $ENV{OCS_OPT_INVENTORY_WRITE_DIFF} ){
    @fromDb = get_history_db( $hardwareId, $dbh );
    &update_history_diff( $hardwareId, $dbh, \@fromXml, \@fromDb );
  }
  else{
    &update_history_full( $hardwareId, $dbh, \@fromXml );
  }
}

sub download_handler{
  # Initialize data
  my $current_context = shift;
  
  my $dbh    = $current_context->{'DBI_HANDLE'};
  my $result  = $current_context->{'XML_ENTRY'};
  my $r     = $current_context->{'APACHE_OBJECT'};
  my $hardware_id = $current_context->{'DATABASE_ID'};

  my $request;
  
  $request = $dbh->prepare('
    SELECT ID FROM download_enable 
    WHERE FILEID=? 
    AND ID IN (SELECT IVALUE FROM devices WHERE NAME="download" AND HARDWARE_ID=?)');
  $request->execute( $result->{'ID'}, $hardware_id);

  &_log(2001, 'download', "$result->{'ID'}(".($result->{'ERR'}?$result->{'ERR'}:'UNKNOWN_CODE').")");
  
  if(my $row = $request->fetchrow_hashref()){
    $dbh->do('UPDATE devices SET TVALUE=?, COMMENTS=?
      WHERE NAME="DOWNLOAD" 
      AND HARDWARE_ID=? 
      AND IVALUE=?',
    {}, $result->{'ERR'}?$result->{'ERR'}:'UNKNOWN_CODE', scalar localtime(), $hardware_id, $row->{'ID'} ) 
      or return(APACHE_SERVER_ERROR);
    &_set_http_header('content-length', 0, $r);
    &_send_http_headers($r);
    return(APACHE_OK);
  }else{
    &_log(2501, 'download', "$result->{'ID'}(".($result->{'ERR'}?$result->{'ERR'}:'UNKNOWN_CODE').")");
    &_set_http_header('content-length', 0, $r);
    &_send_http_headers($r);
    return(APACHE_OK);
  }
}

sub download_duplicate {
  my $current_context = shift;
  my $device = shift;
  
  my $dbh = $current_context->{'DBI_HANDLE'};

  # Handle deployment servers
  $dbh->do('UPDATE download_enable SET SERVER_ID=? WHERE SERVER_ID=?', {}, $current_context->{'DATABASE_ID'}, $device);
  $dbh->do('UPDATE download_servers SET HARDWARE_ID=? WHERE HARDWARE_ID=?', {}, $current_context->{'DATABASE_ID'}, $device);

  # If we encounter problems, it aborts whole replacement
  return $dbh->do('DELETE FROM download_history WHERE HARDWARE_ID=?', {}, $device);
}
1;

