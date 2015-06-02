################################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################

# This core module is used to implement what filter you want.

package Apache::Ocsinventory::Server::Capacities::Filter;

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

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
  'HANDLER_PROLOG_READ' => \&filter_prolog,
  'HANDLER_PROLOG_RESP' => undef,
  'HANDLER_PRE_INVENTORY' => \&filter_inventory,
  'HANDLER_POST_INVENTORY' => undef,
  'REQUEST_NAME' => undef,
  'HANDLER_REQUEST' => undef,
  'HANDLER_DUPLICATE' => undef,
  'TYPE' => OPTION_TYPE_SYNC,
  'XML_PARSER_OPT' => {
      'ForceArray' => []
  }
};

sub filter_prolog{
  # ON/OFF
  return PROLOG_CONTINUE unless $ENV{'OCS_OPT_PROLOG_FILTER_ON'};
  
  my $current_context = shift;
  
  return PROLOG_CONTINUE if $current_context->{IS_TRUSTED};
  
  my @filters = ( );
  
  for( @filters ){
    if ( &$_( $current_context ) == PROLOG_STOP ){
      return PROLOG_STOP;
    }
  }
  
  return PROLOG_CONTINUE;
}

sub filter_inventory{
  # ON/OFF
  return INVENTORY_CONTINUE unless $ENV{'OCS_OPT_INVENTORY_FILTER_ON'};
  
  my $current_context = shift;
  
  return INVENTORY_CONTINUE if $current_context->{IS_TRUSTED};
  
  my @filters = ( \&filter_flood_ip_killer );
  
  for( @filters ){
    if ( &$_( $current_context ) == INVENTORY_STOP ){
      return INVENTORY_STOP;
    }
  }
  return INVENTORY_CONTINUE;
}

sub filter_flood_ip_killer{
  return INVENTORY_CONTINUE unless $ENV{'OCS_OPT_INVENTORY_FILTER_FLOOD_IP'};
  my $current_context = shift;
  my $dbh = $current_context->{DBI_HANDLE};
# In seconds
  my $flushEverySeconds = $ENV{OCS_OPT_INVENTORY_FILTER_FLOOD_IP_CACHE_TIME};
  
# Clear cache
  $dbh->do( 'DELETE FROM conntrack WHERE (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(TIMESTAMP))>?', {}, $flushEverySeconds );
  
# If we cannot insert ipadress, we consider that it is in cache, then forbid transmission
  if( !($current_context->{EXIST_FL}) && !( $dbh->do('INSERT INTO conntrack(IP,TIMESTAMP) VALUES(?,NULL)', {}, $current_context->{IPADDRESS})) ){
    &_log(519,'filter_flood_ip_killer','new device forbidden') if $ENV{'OCS_OPT_LOGLEVEL'};
    return INVENTORY_STOP;
  }
  else{
# Everything is ok
    return INVENTORY_CONTINUE;
  }
}
1;

