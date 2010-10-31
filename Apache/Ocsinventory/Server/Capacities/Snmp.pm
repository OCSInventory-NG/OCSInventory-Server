################################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
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
use Apache::Ocsinventory::Server::Constants;

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
  my $select_snmpcom_uri;
  my @DevicesToScan;
  my @SnmpCommunities;

  #Verify if SNMP is enable for this computer or in config
  my $snmpSwitch = &_get_snmp_switch($current_context);
  return unless $snmpSwitch;

  #########
  #SNMP
  #########
  # Ask computer to scan the requested snmp network devices 
  my @snmp;

  my $dbh = $current_context->{'DBI_HANDLE'};
  my $lanToDiscover = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'TVALUE'};
  my $behaviour     = $current_context->{'PARAMS'}{'IPDISCOVER'}->{'IVALUE'};
  my $groupsParams  = $current_context->{'PARAMS_G'};
 
  #If the computer is Ipdicover elected 
  if ($behaviour == 1 || $behaviour == 2) {

    #Getting non inventoried network devices for the agent subnet 
    $select_ip_req=$dbh->prepare('SELECT IP,MAC FROM netmap WHERE NETID=? AND mac NOT IN (SELECT DISTINCT(macaddr) FROM networks WHERE macaddr IS NOT NULL AND IPSUBNET=?)');
    $select_ip_req->execute($lanToDiscover,$lanToDiscover);

    while(my $row = $select_ip_req->fetchrow_hashref){
      push @DevicesToScan,$row;
    }

    if (@DevicesToScan) {

      #Adding devices informations in the XML
      foreach my $device (@DevicesToScan) {
        push @snmp,{
          'IPADDR'       => $device->{IP},
          'MACADDR'       => $device->{MAC},
          'TYPE'     => 'DEVICE',
        };
      }

      #Getting snmp_com.txt URI
      $select_snmpcom_uri=$dbh->prepare('SELECT TVALUE FROM config WHERE NAME="SNMP_URI"');
      $select_snmpcom_uri->execute();

      if (my $row = $select_snmpcom_uri->fetchrow_hashref) {
        #Adding snmp_com.txt URI in XML
        push @snmp,{
          'TYPE' => 'COMMUNITY',
          'SNMPCOM_LOC'  => $row->{'TVALUE'},
        };
      }

      #Final XML
      push @{ $resp->{'OPTION'} },{
        'NAME' => ['SNMP'],
        'PARAM' => \@snmp,
      };
    }
  }
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
  my $result = $current_context->{'XML_ENTRY'};

  # Remanent data
  my ( %SNMP_SECTIONS, @SNMP_SECTIONS );

  #We get snmp tables references from Map.pm 
  &_init_snmp_map( \%SNMP_SECTIONS, \@SNMP_SECTIONS );

  #Inventory incoming
  &_log(100,'snmp','inventory incoming') if $ENV{'OCS_OPT_LOGLEVEL'};
  
  # Putting the SNMP inventory in the database
  &_snmp_inventory( \%SNMP_SECTIONS, \@SNMP_SECTIONS, $current_context->{'DATABASE_ID'} );
  #return APACHE_SERVER_ERROR if &_update_snmp_inventory( \%SNMP_SECTIONS, \@SNMP_SECTIONS );

  # That's all
  &_log(101,'snmp','transmitted') if $ENV{'OCS_OPT_LOGLEVEL'};

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

1;

