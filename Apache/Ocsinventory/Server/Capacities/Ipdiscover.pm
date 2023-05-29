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

use Apache::Ocsinventory::Server::Useragent qw / _get_useragent /;

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

  &_log(1001,'ipdiscover','checking if is enabled') if $ENV{'OCS_OPT_LOGLEVEL'};
  return unless $ENV{'OCS_OPT_IPDISCOVER'};
  
  my $current_context = shift;
  
  return unless $current_context->{'EXIST_FL'};
  
  my $resp = shift;
  
  my ($ua, $os, $v);

  &_log(1001,'ipdiscover','checking if parameters are OK') if $ENV{'OCS_OPT_LOGLEVEL'};

  my $behaviour = undef;
  my $lanToDiscover = undef;
    # checking if we have a value for IPDISCOVER (IVALUE and TVALUE)
  if(defined $current_context->{'PARAMS'}{'IPDISCOVER'}->{'TVALUE'}) {
    $lanToDiscover = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'TVALUE'};
  }
  if(defined $current_context->{'PARAMS'}{'IPDISCOVER'}->{'IVALUE'}) {
    $behaviour     = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'IVALUE'};
  }
 
  my $groupsParams  = $current_context->{'PARAMS_G'};
  my $ipdiscoverLatency;
  
  &_log(1001,'ipdiscover','checking if computer is able to be elected') if $ENV{'OCS_OPT_LOGLEVEL'};
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

      # SCAN_TYPE_IPDISCOVER & SCAN_ARP_BANDWIDTH
      my $scan_type_ipdiscover = assign_config('SCAN_TYPE_IPDISCOVER', 'OCS_OPT_SCAN_TYPE_IPDISCOVER', $groupsParams, $current_context);
      my $scan_arp_bandwidth = assign_config('SCAN_ARP_BANDWIDTH', 'OCS_OPT_SCAN_ARP_BANDWIDTH', $groupsParams, $current_context);
            
      push @{$$resp{'OPTION'}}, { 
            'NAME' => [ 'IPDISCOVER' ], 
            'PARAM' => { 
              'IPDISC_LAT' => $ipdiscoverLatency,
              'content' => $lanToDiscover,
              'SCAN_TYPE_IPDISCOVER' => $scan_type_ipdiscover,
              'SCAN_ARP_BANDWIDTH' => $scan_arp_bandwidth
            } 
      };
    }
    
    &_set_http_header('Connection', 'close', $current_context->{'APACHE_OBJECT'});
    return 1;
  }else{
    &_log(1001,'ipdiscover','no lan detected for discovery') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 0;
  }
}

sub _ipdiscover_main{

  my $request;
  my $row;
  my $subnet;
  my $remove;

  &_log(1001,'ipdiscover','checking if is enabled') if $ENV{'OCS_OPT_LOGLEVEL'};
  return unless $ENV{'OCS_OPT_IPDISCOVER'};
  
  my $current_context = shift;

  return if $current_context->{'LOCAL_FL'};

  &_log(1001,'ipdiscover','checking if parameters are OK') if $ENV{'OCS_OPT_LOGLEVEL'};
  # return unless (ref $current_context->{'PARAMS'}{'IPDISCOVER'} eq ref {}); # Not a HASH reference
  
  my $DeviceID = $current_context->{'DATABASE_ID'};
  my $dbh = $current_context->{'DBI_HANDLE'};
  my $result = $current_context->{'XML_ENTRY'};

  my $behaviour = undef;
  my $lanToDiscover = undef;

  # checking if we have a value for IPDISCOVER (IVALUE and TVALUE)
  if(defined $current_context->{'PARAMS'}{'IPDISCOVER'}->{'TVALUE'}) {
    $lanToDiscover = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'TVALUE'};
  }
  if(defined $current_context->{'PARAMS'}{'IPDISCOVER'}->{'IVALUE'}) {
    $behaviour = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'IVALUE'};
  }

  my $groupsParams  = $current_context->{'PARAMS_G'};

  #Special array to define agents that could be automatic ipdscover elected
  my @ipdiscover_agents = (
    'OCS-NG_unified_unix_agent',
    'OCS-NG_windows_client',
    'OCS-NG_WINDOWS_AGENT',
  );

  &_log(1001,'ipdiscover','processing') if $ENV{'OCS_OPT_LOGLEVEL'};

  #IVALUE = 0 means that computer will not ever be elected
  if(defined($behaviour) && $behaviour == IPD_NEVER ){
    &_log(1001,'ipdiscover','this computer will not ever be elected due to his configuration') if $ENV{'OCS_OPT_LOGLEVEL'};
    return 0;
  }
  
  # We can use groups to prevent some computers to be elected
  &_log(1001,'ipdiscover','checking if ipdiscover group capabilities are enabled') if $ENV{'OCS_OPT_LOGLEVEL'};
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
    my $useragent = &_get_useragent();

    &_log(1001,'ipdiscover','checking user agent') if $ENV{'OCS_OPT_LOGLEVEL'};
    unless (grep /^($useragent->{'NAME'})$/, @ipdiscover_agents){
      return 0;
    }
    ## AUTO ELECTION PROCESS ##
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
  my $DeviceID = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};

  if(exists($result->{CONTENT}->{IPDISCOVER})){
    my $base = $result->{CONTENT}->{NETWORKS};
    
    # Retrieve netmask
    for(@$base){
      if($_->{IPSUBNET} eq $subnet){
        $mask = $_->{IPMASK};
        last;
      }    
    }

    $base = $result->{CONTENT}->{IPDISCOVER}->{H};

    if( $ENV{'OCS_OPT_IPDISCOVER_LINK_TAG_NETWORK'} ) {
      my $request_tag = $dbh->prepare('SELECT TAG FROM accountinfo WHERE HARDWARE_ID=?');
      my $tag = undef;

      unless($request_tag->execute($DeviceID)){
          &_log(519,'ipdiscover','computer tag error') if $ENV{'OCS_OPT_LOGLEVEL'};
          return(1);
      }

      my $row = $request_tag->fetchrow_hashref;

      if (defined $row->{'TAG'}) {
        $tag = $row->{'TAG'};
      }

      # We insert the results (MAC/IP)
      for(@$base){
        unless($_->{I}=~/^(\d{1,3}(?:\.\d{1,3}){3})$/ and $_->{M}=~/.{2}(?::.{2}){5}/){
          &_log(1003,'ipdiscover','bad_result') if $ENV{'OCS_OPT_LOGLEVEL'};
          next;
        }
        my $request_verif = $dbh->prepare('SELECT TAG FROM netmap WHERE MAC=?');
        $request_verif->execute($_->{M});
        my $row_verif = $request_verif->fetchrow_hashref;

        if (defined $row_verif->{'TAG'}) {
          $update_req = $dbh->prepare('UPDATE netmap SET IP=?,MASK=?,NETID=?,DATE=NOW(), NAME=?, HARDWARE_ID=? WHERE MAC=? AND TAG=?');
          $update_req->execute($_->{I}, $mask, $subnet, $_->{N}, $DeviceID, $_->{M}, $tag);
        } else {
          $update_req = $dbh->prepare('UPDATE netmap SET IP=?,MASK=?,NETID=?,DATE=NOW(), NAME=?, HARDWARE_ID=?, TAG=? WHERE MAC=?');
          $update_req->execute($_->{I}, $mask, $subnet, $_->{N}, $DeviceID, $tag, $_->{M});
        }
        unless($update_req->rows){
          $insert_req = $dbh->prepare('INSERT INTO netmap(IP, MAC, MASK, NETID, NAME, TAG, HARDWARE_ID) VALUES(?,?,?,?,?,?,?)');
          $insert_req->execute($_->{I}, $_->{M}, $mask, $subnet, $_->{N}, $tag, $DeviceID);
        }
      }
    } else {
      # We insert the results (MAC/IP)
      $update_req = $dbh->prepare('UPDATE netmap SET IP=?,MASK=?,NETID=?,DATE=NOW(), NAME=?, HARDWARE_ID=? WHERE MAC=?');
      $insert_req = $dbh->prepare('INSERT INTO netmap(IP, MAC, MASK, NETID, NAME, HARDWARE_ID) VALUES(?,?,?,?,?,?)');

      for(@$base){
        unless($_->{I}=~/^(\d{1,3}(?:\.\d{1,3}){3})$/ and $_->{M}=~/.{2}(?::.{2}){5}/){
          &_log(1003,'ipdiscover','bad_result') if $ENV{'OCS_OPT_LOGLEVEL'};
          next;
        }
        $update_req->execute($_->{I}, $mask, $subnet, $_->{N},$DeviceID, $_->{M});
        unless($update_req->rows){
          $insert_req->execute($_->{I}, $_->{M}, $mask, $subnet, $_->{N},$DeviceID);
        }
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
  
  # check ignored subnet
  $request=$dbh->prepare('SELECT SUBNET FROM blacklist_subnet WHERE SUBNET=?');
  $request->execute($subnet);
  if($request->rows > 0){
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
        if($_->{IPMASK}=~/^(?:255\.){2}|^0x(?:ff){2}/){
          if($_->{IPSUBNET}=~/^(\d{1,3}(?:\.\d{1,3}){3})$/){
  
    # check ignored subnet
    $request = $dbh->prepare('SELECT SUBNET FROM blacklist_subnet WHERE SUBNET=?');
    $request->execute($_->{IPSUBNET});
    if($request->rows > 0){
      next;
    }

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
           &_log(1001,'ipdiscover',($over?'over':'better')."($_->{IPSUBNET})"."(OLD=($worth[0],$worth[1]),NEW=($DeviceID,$quality))(THRESHOLD=$ENV{'OCS_OPT_IPDISCOVER_BETTER_THRESHOLD'})") if $ENV{'OCS_OPT_LOGLEVEL'};
          return 0;
        }
      }
    }else{
        next;
    }
  }
  return 0;
}

# hierarchy is: general config < group config < device config, we get the general config first then override it with group config and device config
# this ensures that we always have a value for each option
sub assign_config {
    my ($param, $env_default, $group_config, $device_config) = @_;
    # general config
    my $value = $ENV{$env_default} || 0;

    # group config
    for my $group (keys %$group_config){
        if(defined($group_config->{$group}->{$param}->{'TVALUE'})){
            $value = $group_config->{$group}->{$param}->{'TVALUE'};
        }
    }

    # device config
    if(defined($device_config->{'PARAMS'}{$param}->{'IVALUE'})){
        $value = $device_config->{'PARAMS'}{$param}->{'TVALUE'};
    }

    return $value;
}



1;
