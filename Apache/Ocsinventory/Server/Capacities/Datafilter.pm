################################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
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

