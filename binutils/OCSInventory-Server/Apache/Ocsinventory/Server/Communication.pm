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
package Apache::Ocsinventory::Server::Communication;

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

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / _send_response _prolog /;

use Apache::Ocsinventory::Server::Constants;

use Apache::Ocsinventory::Server::System(qw/
   :server
   _modules_get_prolog_readers
   _modules_get_prolog_writers
/);

use Apache::Ocsinventory::Server::Communication::Session;

# Subroutine which answer to client prolog
sub _prolog{

  my $frequency;
  my $quality;
  my $now;
  my $lastdate;
  my $request;
  my $row;
  
  my $DeviceID = $Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $info = $Apache::Ocsinventory::CURRENT_CONTEXT{'DETAILS'};

  my $read = &_prolog_read();
   
  if( $read == PROLOG_STOP ){
    &_log(106,'prolog','stopped by module') if $ENV{'OCS_OPT_LOGLEVEL'};
    &_prolog_resp(PROLOG_RESP_BREAK);
    return APACHE_OK;
  } elsif( $read == BAD_USERAGENT ) { 					#If we detect a wrong useragent
    &_log(106,'prolog','stopped by module') if $ENV{'OCS_OPT_LOGLEVEL'};
    return APACHE_BAD_REQUEST;
  }

  $frequency = $ENV{'OCS_OPT_FREQUENCY'};

  # If we do not have the default frequency
  unless(defined($frequency)){
    &_prolog_resp(PROLOG_RESP_STOP);
    &_log(503,'prolog','no_frequency') if $ENV{'OCS_OPT_LOGLEVEL'};
    return APACHE_OK;
  }

  # We have this computer in the database
  if($Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'}){
    # Get the current timestamp
    $now = time();
    
    # Compute quality 
    if($info->{'FIDELITY'} > 1){
      $quality = ((($now-$info->{'LCOME'})/86400) + ($info->{'QUALITY'}*$info->{'FIDELITY'}))/(($info->{'FIDELITY'})+1);
    }else{
      # We increment the number of visits
      $quality = (($now-$info->{'LCOME'})/86400);
    }
    
    # We update device data
    if(!$dbh->do('UPDATE hardware SET FIDELITY=FIDELITY+1,QUALITY=?,LASTCOME=NOW(),USERAGENT=? WHERE DEVICEID=?', 
      {}, $quality, $Apache::Ocsinventory::CURRENT_CONTEXT{'USER_AGENT'}, $DeviceID)){
      return APACHE_SERVER_ERROR;
    }


    ##########
    # If special value 0, we always accept
    if($frequency==0){
      &_prolog_resp(PROLOG_RESP_SEND);
      return APACHE_OK;
    # If -1, we always reject
    }elsif($frequency==(-1)){
      &_prolog_resp(PROLOG_RESP_BREAK);
      return APACHE_OK;
    }
    
    # Saving lastdate
    $lastdate = $info->{'LDATE'};

    # Maybe there are computer's special frequency
    $request=$dbh->prepare('SELECT IVALUE FROM devices WHERE HARDWARE_ID IN ('.($Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'}.(@{$Apache::Ocsinventory::CURRENT_CONTEXT{'MEMBER_OF'}}?',':''). join(',',@{$Apache::Ocsinventory::CURRENT_CONTEXT{'MEMBER_OF'}})).') AND NAME="FREQUENCY" ORDER BY IVALUE DESC');
    $request->execute();
    while($row=$request->fetchrow_hashref()){
      $frequency=$row->{'IVALUE'};
    }
    $request->finish;
    
    # If special values...
    if($frequency==0){
      &_prolog_resp(PROLOG_RESP_SEND);
      return APACHE_OK;
    }elsif($frequency==(-1)){
      &_prolog_resp(PROLOG_RESP_BREAK);
      return APACHE_OK;
    }

    unless ($lastdate){
      &_prolog_resp(PROLOG_RESP_SEND);
      return APACHE_OK;
    }

    # Have we override the period ?
    if((($lastdate-$now)+$frequency*86400)<0){
      &_prolog_resp(PROLOG_RESP_SEND);
      return APACHE_OK;
    }else{
      &_prolog_resp(PROLOG_RESP_STOP);
      return APACHE_OK;
    }
  }else{#This is a new Device ID
    if($frequency==(-1)){
      &_prolog_resp(PROLOG_RESP_BREAK);
      return APACHE_OK;
    }else{
      &_log(103,'prolog','new_deviceid') if $ENV{'OCS_OPT_LOGLEVEL'};
      &_prolog_resp(PROLOG_RESP_SEND);
      return APACHE_OK;
    }  
  }
  
}

sub _send_response{
  my $response = shift;
  my( $xml, $message, $d, $status, $inflated );
  my $r = $Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'};

  # Generate the response
  # Generation of xml message
  $message = XML::Simple::XMLout( $response, RootName => 'REPLY', XMLDecl => "<?xml version='1.0' encoding='UTF-8'?>",
                   NoSort => 1, SuppressEmpty => undef);
  # send
  unless($inflated = &{$Apache::Ocsinventory::CURRENT_CONTEXT{'DEFLATE_SUB'}}( $message )){
    &_log(506,'send_response','compress_stage') if $ENV{'OCS_OPT_LOGLEVEL'};
    #TODO: clean exit
  }

  &_set_http_header('content-length', length($inflated),$r);
  &_set_http_header('Cache-control', 'no-cache',$r);
  &_set_http_content_type('application/x-compressed',$r);
  &_send_http_headers($r);
  $r->print($inflated);
  return 0;
}

sub _prolog_resp{
  my $decision = shift;
  my %resp;
  
  &_prolog_build_resp($decision, \%resp);

  if($resp{'RESPONSE'}[0] eq 'STOP'){
    &_log(102,'prolog','declined') if $ENV{'OCS_OPT_LOGLEVEL'};
  }elsif($resp{'RESPONSE'}[0] eq 'SEND'){
    &_log(100,'prolog','accepted') if $ENV{'OCS_OPT_LOGLEVEL'};
    &start_session( \%Apache::Ocsinventory::CURRENT_CONTEXT );
  }elsif($resp{'RESPONSE'}[0] eq 'OTHER'){
    &_log(105,'prolog','') if $ENV{'OCS_OPT_LOGLEVEL'};
  }
  &_send_response(\%resp);
  return 0;
}

sub _prolog_build_resp{
  my ($decision, $resp) = @_;
  my $module;
  my $state;

  #Agent execution periodicity
  if(defined($Apache::Ocsinventory::CURRENT_CONTEXT{'PARAMS'}{'PROLOG_FREQ'}->{'IVALUE'})){
    $resp->{'PROLOG_FREQ'} = [$Apache::Ocsinventory::CURRENT_CONTEXT{'PARAMS'}{'PROLOG_FREQ'}->{'IVALUE'}];
  }
  else{
    my ($groupFreq, $groupsParams);
  
    if($ENV{'OCS_OPT_ENABLE_GROUPS'}){
      $groupsParams = $Apache::Ocsinventory::CURRENT_CONTEXT{'PARAMS_G'};
      for(keys(%$groupsParams)){
        $groupFreq = $$groupsParams{$_}->{'PROLOG_FREQ'}->{'IVALUE'} 
        if (exists($$groupsParams{$_}->{'PROLOG_FREQ'}->{'IVALUE'}) 
          and $$groupsParams{$_}->{'PROLOG_FREQ'}->{'IVALUE'}<$groupFreq) or !$groupFreq;
      }
    }
          $resp->{'PROLOG_FREQ'} = [ $groupFreq || $ENV{'OCS_OPT_PROLOG_FREQ'} ];
  }

  #Agent inventory on startup feature
  if(defined($Apache::Ocsinventory::CURRENT_CONTEXT{'PARAMS'}{'INVENTORY_ON_STARTUP'}->{'IVALUE'})){
    $resp->{'INVENTORY_ON_STARTUP'} = [$Apache::Ocsinventory::CURRENT_CONTEXT{'PARAMS'}{'INVENTORY_ON_STARTUP'}->{'IVALUE'}];
  }else{
    my ($groupFreq, $groupsParams);
  
    if($ENV{'OCS_OPT_ENABLE_GROUPS'}){
      $groupsParams = $Apache::Ocsinventory::CURRENT_CONTEXT{'PARAMS_G'};
      for(keys(%$groupsParams)){
        $groupFreq = $$groupsParams{$_}->{'INVENTORY_ON_STARTUP'}->{'IVALUE'} 
        if (exists($$groupsParams{$_}->{'INVENTORY_ON_STARTUP'}->{'IVALUE'}) 
          and $$groupsParams{$_}->{'INVENTORY_ON_STARTUP'}->{'IVALUE'}<$groupFreq) or !$groupFreq;
      }
    }
          $resp->{'INVENTORY_ON_STARTUP'} = [ $groupFreq || $ENV{'OCS_OPT_INVENTORY_ON_STARTUP'} ];
  }
  
  if($decision == PROLOG_RESP_BREAK){
    $resp->{'RESPONSE'} = [ 'STOP' ];
    return 0;
  }elsif($decision == PROLOG_RESP_STOP){
    $resp->{'RESPONSE'} = [ 'STOP' ];
  }elsif($decision == PROLOG_RESP_SEND){
    $resp->{'RESPONSE'} = [ 'SEND' ];
  }
  
  for(&_modules_get_prolog_writers()){
    last if $_ == 0;
    &$_(\%Apache::Ocsinventory::CURRENT_CONTEXT, $resp);
  }

  return 0;
}

sub _prolog_read{
  for(&_modules_get_prolog_readers()){
    last if $_==0;
    my $resp=&$_(\%Apache::Ocsinventory::CURRENT_CONTEXT);

    if($resp==PROLOG_STOP){
      return PROLOG_STOP;
    }

    if($resp==BAD_USERAGENT){
      return BAD_USERAGENT;
    }
  }
  return PROLOG_CONTINUE;
}
1;


