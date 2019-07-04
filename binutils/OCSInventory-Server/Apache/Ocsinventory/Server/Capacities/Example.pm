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

package Apache::Ocsinventory::Server::Option::Example;

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

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
  'NAME' => 'EXAMPLE',
  'HANDLER_PROLOG_READ' => \&example_prolog_read, #or undef # Called before reading the prolog
  'HANDLER_PROLOG_RESP' => \&example_prolog_resp, #or undef # Called after the prolog response building
  'HANDLER_PRE_INVENTORY' => \&example_pre_inventory, #or undef # Called before reading inventory
  'HANDLER_POST_INVENTORY' => \&example_post_inventory, #or undef # Called when inventory is stored without error
  'REQUEST_NAME' => 'EXAMPLE',  #or undef # Value of <QUERY/> xml tag
  'HANDLER_REQUEST' => \&example_handler, #or undef # function that handle the request with the <QUERY>'REQUEST NAME'</QUERY>
  'HANDLER_DUPLICATE' => \&example_duplicate,#or undef # Called when a computer is handle as a duplicate
  'TYPE' => OPTION_TYPE_SYNC, # or OPTION_TYPE_ASYNC ASYNC=>with pr without inventory, SYNC=>only when inventory is required
  'XML_PARSER_OPT' => {
      'ForceArray' => ['xml_tag']
  }
};

# Default options of your module
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_EXAMPLE_FOO'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_EXAMPLE_BAR'} = 1;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_EXAMPLE_TOTO'} = 'titi';
# These options will be loaded by ocs when apache will start.
# You can put it in ocsinventory.conf using PerlSetEnv directive (Ex: PerlSetEnv OCS_OPT_EXAMPLE_FOO 1) 
# The settings you put to the configuration file will overload your default
# Use it as it was in the apache environnement (ex: $Apache::Ocsinventory::OPTIONS{'OCS_OPT_EXAMPLE_FOO'}-use it as $ENV{'OCS_OPT_EXAMPLE_FOO'}
# The GUI refer to it with OCS_OPT_ removed (ex: OCS_OPT_EXAMPLE_FOO => EXAMPLE_FOO). It must be in 'config' table and overload the default settings of this file AND overload the settings in ocsinventory.conf too.

# For every module handlers, your functions will be called with certain parameters.
# It provides you all the current environnement at the moment of the call
# The structure: 
#
#%CURRENT_CONTEXT = (
#  'APACHE_OBJECT' => ref, gives you a pointer to the apache request object -- Always filled
#  'DBI_HANDLE' => ref, gives you a pointer to the database handle -- Always filled
#  'DEVICEID' => string, gives you the computer's unique deviceid -- Always filled
#  'DATABASE_ID' => integer, gives you the computer's database identifier -- Always filled
#  'DATA' => ref, gives you a pointer to the initial deflated data (earliest stage) -- Always filled
#  'RAW_DATA' => ref, gives you a pointer to the initial compressed data (earliest stage) -- Always filled
#  'XML_ENTRY' => ref, gives you a pointer to the data structure generated when parsing the incoming request (with core and optionnal modules parsing options) -- Always filled
#  'LOCK_FL' => 1(true) or 0(false), shows if the computer is locked -- Always filled
#  'EXIST_FL' => 1(true) or 0(false) shows if the computer whether exists in the database or not (update) -- Always filled
#       'MEMBER_OF' => array ref, gives you the groups that computer is member of
#);

sub example_prolog_read{
# This handler must return either PROLOG_CONTINUE or PROLOG_STOP
# This mechanism enables you to completely stop the process at its beginning
  my $stop = 0;
  if($stop){
    &_log(0,'example','stop prolog !!') if $ENV{'OCS_OPT_LOGLEVEL'};
    return PROLOG_STOP;
  }
  else{
    &_log(0,'example','let prolog go on !!') if $ENV{'OCS_OPT_LOGLEVEL'};
    return PROLOG_CONTINUE;
  }
}

sub example_prolog_resp{
# Enables you to add tags to xml prolog response
# Commonly used to ask an agent module to do something
  return 1;
}

sub example_pre_inventory{
# This handler must return either INVENTORY_CONTINUE or INVENTORY_STOP
# This mechanism enables you to completely stop the process at its beginning
  my $stop = 0;
  if($stop){
    &_log(0,'example','I HATE inventory, I stop it !!') if $ENV{'OCS_OPT_LOGLEVEL'};
    return INVENTORY_STOP;
  }
  else{
    &_log(0,'example','I love inventory !!') if $ENV{'OCS_OPT_LOGLEVEL'};
    return INVENTORY_CONTINUE;
  }
}

sub example_post_inventory{
# This handler is useful to read "extra section" of an inventory
# Commonly used to read data added to inventory by an agent module
  return 1;
}

sub example_handler{
# This function will be used for a dedicated request (like "INVENTORY" or "PROLOG")
# It is designed to implement your own requests by using agent modules
# You return from here directly to apache
# Then, you must use the apache constants redefined in MODPERL1 or 2 .pm
  return 1;
}

sub example_duplicate{
# Useful to manage duplicate with your own tables/structures when a computer is evaluated as a duplicate and replaced
  return 1;
}
1;

