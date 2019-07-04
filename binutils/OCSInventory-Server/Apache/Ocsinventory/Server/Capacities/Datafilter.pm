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

package Apache::Ocsinventory::Server::Capacities::Datafilter;

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

use Apache::Ocsinventory::Map;

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
  'NAME' => 'DATAFILTER',
  'HANDLER_PROLOG_READ' => undef, #or undef # Called before reading the prolog
  'HANDLER_PROLOG_RESP' => undef, #or undef # Called after the prolog response building
  'HANDLER_PRE_INVENTORY' => \&datafilter_pre_inventory, #or undef # Called before reading inventory
  'HANDLER_POST_INVENTORY' => undef, #or undef # Called when inventory is stored without error
  'REQUEST_NAME' => undef,  #or undef # Value of <QUERY/> xml tag
  'HANDLER_REQUEST' => undef, #or undef # function that handle the request with the <QUERY>'REQUEST NAME'</QUERY>
  'HANDLER_DUPLICATE' => undef,#or undef # Called when a computer is handle as a duplicate
  'TYPE' => OPTION_TYPE_SYNC, # or OPTION_TYPE_ASYNC ASYNC=>with pr without inventory, SYNC=>only when inventory is required
  'XML_PARSER_OPT' => {
      'ForceArray' => ['']
  }
};



sub datafilter_pre_inventory{

  my $current_context = shift;
  my $xml = $current_context->{'XML_ENTRY'};
  my $apache = $current_context->{'APACHE_OBJECT'};

  if ($ENV{'OCS_OPT_DATA_FILTER'}) {
    my ($map_section, $multi_sections, $field, $mask);

    #Geting table and field from configuration file
    my %DATA_TO_FILTER = $apache->dir_config->get('OCS_OPT_DATA_TO_FILTER');

    for my $section ( keys %DATA_TO_FILTER) {
      $map_section = lc $section;
      $field = $DATA_TO_FILTER{$section};

      #Deleting data from XML
      unless ($DATA_MAP{$map_section}{multi}) {
        delete $xml->{'CONTENT'}->{$section}->{$field} if $xml->{'CONTENT'}->{$section}->{$field};
        &_log(1,'datafilter',"$section $field field deleted") if $ENV{'OCS_OPT_LOGLEVEL'};;
      }
    }
  }
  return INVENTORY_CONTINUE;
}

1;

