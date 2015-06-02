###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Capacities::Ipdiscover;

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

use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::System;
use Apache::Ocsinventory::Server::Constants;

use constant IPD_NEVER => 0;
use constant IPD_ON => 1;
use constant IPD_MAN => 2;

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
  'NAME' => 'IPDISCOVER',
  'HANDLER_PROLOG_READ' => undef,
  'HANDLER_PROLOG_RESP' => \&_ipdiscover_prolog_resp,
  'HANDLER_PRE_INVENTORY' => undef,
  'HANDLER_POST_INVENTORY' => \&_ipdiscover_main,
  'REQUEST_NAME' => undef,
  'HANDLER_REQUEST' => undef,
  'HANDLER_DUPLICATE' => undef,
  'TYPE' => OPTION_TYPE_SYNC,
  'XML_PARSER_OPT' => {
      'ForceArray' => ['H', 'NETWORKS']
  }
};

sub _ipdiscover_prolog_resp{

  return unless $ENV{'OCS_OPT_IPDISCOVER'};
  
  my $current_context = shift;
  
  return unless $current_context->{'EXIST_FL'};
  
  my $resp = shift;
  
  my ($ua, $os, $v);
 
  my $lanToDiscover = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'TVALUE'};
  my $behaviour     = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'IVALUE'};
  my $groupsParams  = $current_context->{'PARAMS_G'};
  my $ipdiscoverLatency;
  
  return if !defined($behaviour) or $behaviour == IPD_NEVER;

  if($lanToDiscover){
    &_log(1004,'ipdiscover','incoming') if $ENV{'OCS_OPT_LOGLEVEL'};
    # We can use groups to prevent some computers to be elected
    if( $ENV{'OCS_OPT_ENABLE_GROUPS'} && $ENV{'OCS_OPT_IPDISCOVER_USE_GROUPS'} ){
      for(keys(%$groupsParams)){
        if(defined($$groupsParams{$_}->{'IPDISCOVER'}->{'IVALUE'}) && $$groupsParams{$_}->{'IPDISCOVER'}->{'IVALUE'} == IPD_NEVER){
          &_log(1005,'ipdiscover','conflict') if $ENV{'OCS_OPT_LOGLEVEL'};
          return;
        }
      }
    }

    $resp->{'RESPONSE'} = [ 'SEND' ];
    # Agents newer than 13(linux) ans newer than 4027(Win32) receive new xml formatting (including ipdisc_lat)
    $ua = $current_context->{'USER_AGENT'};

    my $legacymode;
    if( $ua=~/OCS-NG_(\w+)_client_v(\d+)/ ){
      $legacymode = 1 if ($1 eq "windows" && $2<=4027) or ($1 eq "linux" && $2<=13);
    }
    
    if( $legacymode ){    
      push @{$$resp{'OPTION'}}, { 'NAME' => [ 'IPDISCOVER' ], 'PARAM' => [ $lanToDiscover ] };
    }
    else{
      if(defined( $current_context->{'PARAMS'}{'IPDISCOVER_LATENCY'}->{'IVALUE'} )){
        $ipdiscoverLatency = $current_context->{'PARAMS'}{'IPDISCOVER_LATENCY'}->{'IVALUE'};
      }
      else{
        for(keys(%$groupsParams)){
          $ipdiscoverLatency = $$groupsParams{$_}->{'IPDISCOVER_LATENCY'}->{'IVALUE'} 
          if (exists($$groupsParams{$_}->{'IPDISCOVER_LATENCY'}->{'IVALUE'}) 
            and $$groupsParams{$_}->{'IPDISCOVER_LATENCY'}->{'IVALUE'}>$ipdiscoverLatency) 
            or !$ipdiscoverLatency;
        }
      }
      
      unless( $ipdiscoverLatency ){
        $ipdiscoverLatency = $ENV{'OCS_OPT_IPDISCOVER_LATENCY'}?$ENV{'OCS_OPT_IPDISCOVER_LATENCY'}:100;
      }
            
      push @{$$resp{'OPTION'}}, { 
            'NAME' => [ 'IPDISCOVER' ], 
            'PARAM' => { 
              'IPDISC_LAT' => $ipdiscoverLatency,
              'content' => $lanToDiscover
            } 
      };
    }
    
    &_set_http_header('Connection', 'close', $current_context->{'APACHE_OBJECT'});
    return 1;
  }else{
    return 0;
  }
}

sub _ipdiscover_main{

  my $request;
  my $row;
  my $subnet;
  my $remove;

  return unless $ENV{'OCS_OPT_IPDISCOVER'};
  
  my $current_context = shift;

  return if $current_context->{'LOCAL_FL'};
  
  my $DeviceID = $current_context->{'DATABASE_ID'};
  my $dbh = $current_context->{'DBI_HANDLE'};
  my $result = $current_context->{'XML_ENTRY'};
  my $lanToDiscover = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'TVALUE'};
  my $behaviour     = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'IVALUE'};
  my $groupsParams  = $current_context->{'PARAMS_G'};
  
  #IVALUE = 0 means that computer will not ever be elected
  if( defined($behaviour) && $behaviour == IPD_NEVER ){
    return 0;
  }
  
  
  # We can use groups to prevent some computers to be elected
  if( $ENV{'OCS_OPT_ENABLE_GROUPS'} && $ENV{'OCS_OPT_IPDISCOVER_USE_GROUPS'} ){
    for(keys(%$groupsParams)){
      return 0 if defined($$groupsParams{$_}->{'IPDISCOVER'}->{'IVALUE'}) 
        && $$groupsParams{$_}->{'IPDISCOVER'}->{'IVALUE'} == IPD_NEVER;
    }
  }

  # Is the device already have the ipdiscover function ?
  if($lanToDiscover){
    # get 1 on removing and 0 if ok
    $remove = &_ipdiscover_read_result($dbh, $result, $lanToDiscover);
    if( $behaviour == IPD_MAN ){
      $remove = 0;
    }
    if(!defined($remove)){
      return 1;
    }
  }else{
    if($result->{CONTENT}->{HARDWARE}->{OSNAME}!~/xp|2000|linux|2003|vista/i){
      return 0;
    }
    
    # Get quality and fidelity
    $request = $dbh->prepare('SELECT QUALITY,FIDELITY FROM hardware WHERE ID=?');
    $request->execute($DeviceID);

    if($row = $request->fetchrow_hashref){
      if( ($row->{'FIDELITY'} > 2 and $row->{'QUALITY'} != 0) || $ENV{'OCS_OPT_IPDISCOVER_NO_POSTPONE'} ){
        $subnet = &_ipdiscover_find_iface($result, $current_context->{'DBI_HANDLE'});
        if(!$subnet){
          return &_ipdiscover_evaluate($result, $row->{'FIDELITY'}, $row->{'QUALITY'}, $dbh, $DeviceID);
        }elsif($subnet =~ /^(\d{1,3}(?:\.\d{1,3}){3})$/){
          # The computer is elected, we have to write it in devices
          $dbh->do('INSERT INTO devices(HARDWARE_ID, NAME, IVALUE, TVALUE, COMMENTS) VALUES(?,?,?,?,?)',{},$DeviceID,'IPDISCOVER',1,$subnet,'') or return 1;
          &_log(1001,'ipdiscover','elected'."($subnet)") if $ENV{'OCS_OPT_LOGLEVEL'};
          return 0;
        }else{
          return 0;
        }
      }else{
        return 0;
      }
    }
  }
  


  # If needed, we remove
  if($remove){
    if(!$dbh->do('DELETE FROM devices WHERE HARDWARE_ID=? AND NAME="IPDISCOVER"', {}, $DeviceID)){
      return 1;
    }
    $dbh->commit;
    &_log(1002,'ipdiscover','removed') if $ENV{'OCS_OPT_LOGLEVEL'};
  }
  0;
}

sub _ipdiscover_read_result{

  my ($dbh, $result, $subnet) = @_;
  my $mask;
  my $update_req;
  my $insert_req;
  my $request;

  if(exists($result->{CONTENT}->{IPDISCOVER})){
    my $base = $result->{CONTENT}->{NETWORKS};
    
    # Retrieve netmask
    for(@$base){
      if($_->{IPSUBNET} eq $subnet){
        $mask = $_->{IPMASK};
        last;
      }    
    }
    
    # We insert the results (MAC/IP)
    $update_req = $dbh->prepare('UPDATE netmap SET IP=?,MASK=?,NETID=?,DATE=NULL, NAME=? WHERE MAC=?');
    $insert_req = $dbh->prepare('INSERT INTO netmap(IP, MAC, MASK, NETID, NAME) VALUES(?,?,?,?,?)');
    
    $base = $result->{CONTENT}->{IPDISCOVER}->{H};
    for(@$base){
      unless($_->{I}=~/^(\d{1,3}(?:\.\d{1,3}){3})$/ and $_->{M}=~/.{2}(?::.{2}){5}/){
        &_log(1003,'ipdiscover','bad_result') if $ENV{'OCS_OPT_LOGLEVEL'};
        next;
      }
      $update_req->execute($_->{I}, $mask, $subnet, $_->{N}, $_->{M});
      unless($update_req->rows){
        $insert_req->execute($_->{I}, $_->{M}, $mask, $subnet, $_->{N});
      }
    }
    $dbh->commit;
  }else{
    return 1;
  }

  # Maybe There are too much ipdiscover per subnet ?
  $request=$dbh->prepare('SELECT HARDWARE_ID FROM devices WHERE TVALUE=? AND NAME="IPDISCOVER"');
  $request->execute($subnet);
  if($request->rows > $ENV{'OCS_OPT_IPDISCOVER'}){
    $request->finish;
    return 1;
  }
  
  return 0;
}

sub _ipdiscover_find_iface{

  my $result = shift;
  my $base = $result->{CONTENT}->{NETWORKS};
  
  my $dbh = shift;
  
  my $request;
  my @worth;
  
  for(@$base){
    if($_->{DESCRIPTION}!~/ppp/i){
      if($_->{STATUS}=~/up/i){
        if($_->{IPMASK}=~/^(?:255\.){2}/){
          if($_->{IPSUBNET}=~/^(\d{1,3}(?:\.\d{1,3}){3})$/){
  
    # Looking for a need of ipdiscover
    $request = $dbh->prepare('SELECT HARDWARE_ID FROM devices WHERE TVALUE=? AND NAME="IPDISCOVER"');
    $request->execute($_->{IPSUBNET});
    if($request->rows < $ENV{'OCS_OPT_IPDISCOVER'}){
      $request->finish;
      return $_->{IPSUBNET};
    }
    $request->finish;
    
    }}}}  
    # Looking for ipdiscover older than ipdiscover_max_value
    # and compare current computer with actual ipdiscover
  }
  return 0;
  
}

sub _ipdiscover_evaluate{

  my ($result, $fidelity, $quality, $dbh, $DeviceID) = @_;
  
  my $request;
  my $row;
  my $time = time();
  my $max_age = $ENV{'OCS_OPT_IPDISCOVER_MAX_ALIVE'}*86400;
  
  my $over;
  my @worth;

  my $base = $result->{CONTENT}->{NETWORKS};
  
  for(@$base){
    if(defined($_->{IPSUBNET}) and $_->{IPSUBNET}=~/^(\d{1,3}(?:\.\d{1,3}){3})$/ ){

      $request = $dbh->prepare('
      SELECT h.ID AS ID, h.QUALITY AS QUALITY, UNIX_TIMESTAMP(h.LASTDATE) AS LAST 
      FROM hardware h,devices d 
      WHERE d.HARDWARE_ID=h.ID AND d.TVALUE=? AND h.ID<>? AND d.IVALUE<>? AND d.NAME="IPDISCOVER"');
      $request->execute($_->{IPSUBNET}, $DeviceID, IPD_MAN);

      while($row = $request->fetchrow_hashref){
        # If we find an ipdiscover that is older than IP_MAX_ALIVE, we replace it with the current
        if( (($time - $row->{'LAST'}) > $max_age) and $max_age){
          @worth = ($row->{'ID'}, $row->{'QUALITY'} );
          $over = 1;
          last;
        }
        # For the first round
        unless(@worth){
          @worth = ($row->{'ID'}, $row->{'QUALITY'} );
          next;
        }
        # Put the worth in @worth
        @worth = ( $row->{'ID'}, $row->{'QUALITY'} ) if $worth[1] < $row->{'QUALITY'};
      }

      # If not over, we compare our quality with the one of the worth on this subnet.
      # If it is better more than one, we replace it
      if(@worth){
        if(($quality < $worth[1] and (($worth[1]-$quality)>$ENV{'OCS_OPT_IPDISCOVER_BETTER_THRESHOLD'})) or $over){
          # Compare to the current and replace it if needed
          if(!$dbh->do('UPDATE devices SET HARDWARE_ID=? WHERE HARDWARE_ID=? AND NAME="IPDISCOVER"', {}, $DeviceID, $worth[0])){
            return 1;
          }
          $dbh->commit;
          &_log(1001,'ipdiscover',($over?'over':'better')."($_->{IPSUBNET})") if $ENV{'OCS_OPT_LOGLEVEL'};
          return 0;
        }
      }
    }else{
        next;
    }
  }
  return 0;
}
1;
