###############################################################################
## OCSINVENTORY-NG
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Capacities::Registry;

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
use Apache::Ocsinventory::Map;

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
  'NAME' => 'REGISTRY',
  'HANDLER_PROLOG_READ' => undef,
  'HANDLER_PROLOG_RESP' => \&_registry_prolog_resp,
  'HANDLER_PRE_INVENTORY' => undef,
  'HANDLER_POST_INVENTORY' => undef,
  'REQUEST_NAME' => undef,
  'HANDLER_REQUEST' => undef,
  'HANDLER_DUPLICATE' => undef,
  'TYPE' => OPTION_TYPE_SYNC,
  'XML_PARSER_OPT' => {
      'ForceArray' => ['REGISTRY']
  }
};

sub _registry_prolog_resp{

  return unless $ENV{'OCS_OPT_REGISTRY'};
  
  my $current_context = shift;
  my $resp = shift;
  
  my $dbh = $current_context->{'DBI_HANDLE'};

  # Sync option
  return if $resp->{'RESPONSE'} eq 'STOP';

  my $request;
  my $row;
  #################################
  #REGISTRY
  #########
  # Ask computer to retrieve the requested registry keys
  my @registry;
  $request=$dbh->prepare('SELECT * FROM regconfig');
  $request->execute;
  while($row = $request->fetchrow_hashref){
    push @registry,
      {
        'REGTREE' =>  $row->{'REGTREE'} ,
        'REGKEY'  =>  $row->{'REGKEY'} ,
        'NAME'    =>  $row->{'NAME'} ,
        'content' =>  $row->{'REGVALUE'}
      };
  }

  if(@registry){
    push @{ $resp->{'OPTION'} }, {
          'NAME'  => [ 'REGISTRY' ],
          'PARAM'  => \@registry
        };
    return 1;
  }else{
    return 0;
  }
}
1;
