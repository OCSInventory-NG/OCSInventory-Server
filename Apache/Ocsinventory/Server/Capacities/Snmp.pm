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

# This core module is used to guide you through the module creation
# All modules using modperl api functions must use the wrappers defined in MODPERL1 or 2 .pm 
# or create a new one in these 2 files if you need to use something that is not wrapped yet

package Apache::Ocsinventory::Server::Capacities::Snmp;
use XML::Simple;
use strict;

# This block specify which wrapper will be used ( your module will be compliant with all mod_perl versions )
BEGIN{
  if($ENV{'OCS_MODPERL_VERSION'} == 1){
    require Apache::Ocsinventory::Server::Modperl1;
    Apache::Ocsinventory::Server::Modperl1->import();
  }elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
    require Apache::Ocsinventory::Server::Modperl2;
    Apache::Ocsinventory::Server::Modperl2->import();
  }
}

# These are the core modules you must include in addition
use Apache::Ocsinventory::Server::System;
use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::Communication::Session;
use Apache::Ocsinventory::Server::Constants;

use Apache::Ocsinventory::Server::Inventory::Export;
use Apache::Ocsinventory::Server::Capacities::Snmp::Data;
use Apache::Ocsinventory::Server::Capacities::Snmp::Inventory;

#Getting sections for the 'ForceArray' option
my @forceArray = ('DEVICE'); 
&_get_snmp_parser_ForceArray(\@forceArray);

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
  'NAME' => 'SNMP',
  'HANDLER_PROLOG_READ' => undef, #or undef # Called before reading the prolog
  'HANDLER_PROLOG_RESP' => \&snmp_prolog_resp, #or undef # Called after the prolog response building
  'HANDLER_PRE_INVENTORY' => undef , #or undef # Called before reading inventory
  'HANDLER_POST_INVENTORY' => undef, #or undef # Called when inventory is stored without error
  'REQUEST_NAME' => 'SNMP',  #or undef # Value of <QUERY/> xml tag
  'HANDLER_REQUEST' => \&snmp_handler, #or undef # function that handle the request with the <QUERY>'REQUEST NAME'</QUERY>
  'HANDLER_DUPLICATE' => \&snmp_duplicate, #or undef # Called when a computer is handle as a duplicate
  'TYPE' => OPTION_TYPE_SYNC, # or OPTION_TYPE_ASYNC ASYNC=>with pr without inventory, SYNC=>only when inventory is required
  'XML_PARSER_OPT' => {
      'ForceArray' => [@forceArray] 
  }
};

sub snmp_prolog_resp{

  my $current_context = shift;
  my $resp = shift;
  my $select_ip_req;
  my $select_communities_req;
  my $select_snmp_type_req;
  my $select_deviceid_req;
  my $select_network_req;
  my $select_mibs_req;
  my @devicesToScan;
  my @networksToScan;
  my @communities;
  my @snmp_types;
  my @mibs;
  my @snmp_rework;

  my $behaviour = undef;
  my $lanToDiscover = undef;

  #Verify if SNMP is enable for this computer or in config
  my $snmpSwitch = &_get_snmp_switch($current_context);
  return unless $snmpSwitch;

  #########
  #SNMP
  #########
  # Ask computer to scan the requested snmp network devices 
  my @snmp;

  my $dbh = $current_context->{'DBI_HANDLE'};

  if(defined $current_context->{'PARAMS'}{'IPDISCOVER'}->{'IVALUE'}) {
    $behaviour = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'IVALUE'};
  }

  if(defined $behaviour && $behaviour == 2) {
    $lanToDiscover = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'TVALUE'};
  }

  #Only if communication is https 
  if ($current_context->{'APACHE_OBJECT'}->subprocess_env('https')) {

    $select_deviceid_req=$dbh->prepare('SELECT DEVICEID FROM hardware WHERE DEVICEID=?');
    $select_deviceid_req->execute($current_context->{'DEVICEID'});

    #Only if agent deviceid already exists in database
    if ($select_deviceid_req->fetchrow_hashref) {

      #Getting networks specified for scans
      $select_network_req=$dbh->prepare("SELECT TVALUE FROM devices WHERE HARDWARE_ID=? AND NAME='SNMP_NETWORK'");
      $select_network_req->execute($current_context->{'DATABASE_ID'});

      #Getting networks separated by commas (will be removed when GUI will be OK to add several networks cleanly) 
      my $row = $select_network_req->fetchrow_hashref; #Only one line per HARDWARE_ID
      @networksToScan= split(',',$row->{TVALUE});
      

      #TODO: use this lines instead of previous ones when GUI will be OK to add several networks cleanly
      #while(my $row = $select_network_req->fetchrow_hashref){
      #   push @networksToScan,$row;
      #}

      if (@networksToScan) {
        #Adding devices informations in the XML
        foreach my $network (@networksToScan) {
          push @snmp,{
            #'SUBNET' => $network->{TVALUE},   #TODO: uncomment this line when GUI will be OK to add several networks cleanly 
            'SUBNET' => $network,
            'TYPE' => 'NETWORK',
          };
        }
      }

      #If the computer is Ipdicover elected 
      if (defined $behaviour && $behaviour == 2) {
        
        #Getting non inventoried network devices for the agent subnet 
        $select_ip_req=$dbh->prepare('SELECT IP,MAC FROM netmap WHERE NETID=? AND mac NOT IN (SELECT DISTINCT(macaddr) FROM networks WHERE macaddr IS NOT NULL AND IPSUBNET=?)');
        $select_ip_req->execute($lanToDiscover,$lanToDiscover);
        
        while(my $row = $select_ip_req->fetchrow_hashref){
          push @devicesToScan,$row;
        }

        if (@devicesToScan) {
          #Adding devices informations in the XML
          foreach my $device (@devicesToScan) {
            push @snmp,{
              'IPADDR' => $device->{IP},
              'MACADDR' => $device->{MAC},
              'TYPE' => 'DEVICE',
            };
          }
	      }
      }

      #Getting snmp types
      $select_snmp_type_req = $dbh->prepare('SELECT t.TYPE_NAME, tc.CONDITION_OID, tc.CONDITION_VALUE, t.TABLE_TYPE_NAME, l.LABEL_NAME, c.OID FROM snmp_types t LEFT JOIN snmp_configs c ON t.ID = c.TYPE_ID LEFT JOIN snmp_labels l ON l.ID = c.LABEL_ID LEFT JOIN snmp_types_conditions tc ON tc.TYPE_ID = t.ID');
      $select_snmp_type_req->execute();

      while(my $row = $select_snmp_type_req->fetchrow_hashref){
        push @snmp_types,$row;
      }

      if (@snmp_types) {
        foreach my $type (@snmp_types) {
          push @snmp,{
            'TYPE_NAME' => $type->{'TYPE_NAME'}?$type->{'TYPE_NAME'}:'',
            'CONDITION_OID' => $type->{'CONDITION_OID'}?$type->{'CONDITION_OID'}:'',
            'CONDITION_VALUE'=> $type->{'CONDITION_VALUE'}?$type->{'CONDITION_VALUE'}:'',
            'TABLE_TYPE_NAME' => $type->{'TABLE_TYPE_NAME'}?$type->{'TABLE_TYPE_NAME'}:'',
            'LABEL_NAME'=> $type->{'LABEL_NAME'}?$type->{'LABEL_NAME'}:'',
            'OID' => $type->{'OID'}?$type->{'OID'}:'',
            'TYPE' => 'SNMP_TYPE',
          };
        }
      }

      if (@snmp) {
        #Getting snmp communities
        $select_communities_req = $dbh->prepare('SELECT VERSION,NAME,USERNAME,AUTHPASSWD,LEVEL,AUTHPROTO,PRIVPASSWD,PRIVPROTO FROM snmp_communities');
        $select_communities_req->execute();

        while(my $row = $select_communities_req->fetchrow_hashref){
          push @communities,$row;
        }

        if (@communities) {
          foreach my $community (@communities) {
            push @snmp,{
              'VERSION' => $community->{'VERSION'}?$community->{'VERSION'}:'',
              'NAME' => $community->{'NAME'}?$community->{'NAME'}:'',
              'USERNAME'=> $community->{'USERNAME'}?$community->{'USERNAME'}:'',
              'AUTHPASSWD' => $community->{'AUTHPASSWD'}?$community->{'AUTHPASSWD'}:'',
              'LEVEL'=> $community->{'LEVEL'}?$community->{'LEVEL'}:'',
              'AUTHPROTO' => $community->{'AUTHPROTO'}?$community->{'AUTHPROTO'}:'',
              'PRIVPASSWD' => $community->{'PRIVPASSWD'}?$community->{'PRIVPASSWD'}:'',
              'PRIVPROTO' => $community->{'PRIVPROTO'}?$community->{'PRIVPROTO'}:'',
              'TYPE' => 'COMMUNITY',
            };
          }
        }

        #Getting custom mibs informations 
        $select_mibs_req = $dbh->prepare('SELECT VENDOR,URL,CHECKSUM,VERSION,PARSER FROM snmp_mibs');
        $select_mibs_req->execute();

        while(my $row = $select_mibs_req->fetchrow_hashref){
          push @mibs,$row;
        }

        if (@mibs) {
          foreach my $mib (@mibs) {
            push @snmp,{
              'VENDOR' => $mib->{'VENDOR'}?$mib->{'VENDOR'}:'',
              'URL'=> $mib->{'URL'}?$mib->{'URL'}:'',
              'CHECKSUM' => $mib->{'CHECKSUM'}?$mib->{'CHECKSUM'}:'',
              'VERSION' => $mib->{'VERSION'}?$mib->{'VERSION'}:'',
              'PARSER' => $mib->{'PARSER'}?$mib->{'PARSER'}:'',
              'TYPE' => 'MIB',
            };
          }
        }

      my $groupsParams  = $current_context->{'PARAMS_G'};
      my $scan_type_snmp = assign_config('SCAN_TYPE_SNMP', 'OCS_OPT_SCAN_TYPE_SNMP', $groupsParams, $current_context);
      my $scan_arp_bandwidth = assign_config('SCAN_ARP_BANDWIDTH', 'OCS_OPT_SCAN_ARP_BANDWIDTH', $groupsParams, $current_context);

      push @snmp,{
        # add TYPE to avoid warnings when receiving on the agent
        'TYPE' => 'OPTION',
        'SCAN_TYPE_SNMP' => $scan_type_snmp,
        'SCAN_ARP_BANDWIDTH' => $scan_arp_bandwidth,
      };

      #Final XML
      push @{ $resp->{'OPTION'} },{
        'NAME' => ['SNMP'],
        'PARAM' => \@snmp,
      };

      } 

    } else { &_log(104,'snmp',"error: agent must have a deviceid in database !!") if $ENV{'OCS_OPT_LOGLEVEL'}; }
  } else { &_log(103,'snmp',"error: agent must communicate using https to be able to get SNMP communities (only affects OCS unix agent) !!") if $ENV{'OCS_OPT_LOGLEVEL'} and $ENV{'OCS_OPT_SNMP_PRINT_HTTPS_ERROR'} } 

}

sub snmp_handler{
  my $current_context = shift;

  #Verify if SNMP is enable for this computer or in config
  my $snmpSwitch = &_get_snmp_switch($current_context);
  return unless $snmpSwitch;

  my $dbh    = $current_context->{'DBI_HANDLE'};
  my $result  = $current_context->{'XML_ENTRY'};
  my $r     = $current_context->{'APACHE_OBJECT'};
  my $hardware_id = $current_context->{'DATABASE_ID'};

  # Remanent data
  my ( %SNMP_SECTIONS, @SNMP_SECTIONS );

  #We get snmp tables references from Map.pm 
  &_init_snmp_map( \%SNMP_SECTIONS, \@SNMP_SECTIONS );

  &_generate_ocs_file_snmp( \%SNMP_SECTIONS, \@SNMP_SECTIONS, $current_context->{'DATABASE_ID'})
  &kill_session( \%Apache::Ocsinventory::CURRENT_CONTEXT );

  #Inventory incoming
  &_log(100,'snmp','inventory incoming') if $ENV{'OCS_OPT_LOGLEVEL'};
  
  # Putting the SNMP inventory in the database
  if (&_snmp_inventory( \%SNMP_SECTIONS, \@SNMP_SECTIONS, $current_context->{'DATABASE_ID'})) {
    &_log(101,'snmp','inventory error !!') if $ENV{'OCS_OPT_LOGLEVEL'};
  } else { 
    &_log(102,'snmp','inventory transmitted') if $ENV{'OCS_OPT_LOGLEVEL'};
  }

  #Sending Response to the agent
  &_set_http_header('content-length', 0, $r);
  &_send_http_headers($r);

  return (APACHE_OK);
}

sub snmp_duplicate{
# Useful to manage duplicate with your own tables/structures when a computer is evaluated as a duplicate and replaced
  return 1;
}

sub _get_snmp_switch {
  my $current_context = shift ;
  my $groupsParams = $current_context->{'PARAMS_G'};
  my $snmpSwitch ;

  if($ENV{'OCS_OPT_SNMP'}){
    $snmpSwitch = 1;
    # Groups custom parameter
    for(keys(%$groupsParams)){
      $snmpSwitch = $$groupsParams{$_}->{'SNMP_SWITCH'}->{'IVALUE'}
        if exists( $$groupsParams{$_}->{'SNMP_SWITCH'}->{'IVALUE'} )
        and $$groupsParams{$_}->{'SNMP_SWITCH'}->{'IVALUE'} < $snmpSwitch;
    }
  }
  else{
    $snmpSwitch = 0;
  }

  #Computer custom parameter
  $snmpSwitch = $current_context->{'PARAMS'}{'SNMP_SWITCH'}->{'IVALUE'}
    if defined($current_context->{'PARAMS'}{'SNMP_SWITCH'}->{'IVALUE'}) and $snmpSwitch;

  return ($snmpSwitch);
}

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

