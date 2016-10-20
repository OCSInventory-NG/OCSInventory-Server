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
