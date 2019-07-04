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

package Apache::Ocsinventory::Server::Useragent;
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

require Exporter;

our @ISA = qw /Exporter/;
our @EXPORT = qw / _get_useragent /;

# These are the core modules you must include in addition
use Apache::Ocsinventory::Server::System;
use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::Constants;

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
  'NAME' => 'USERAGENT',
  'HANDLER_PROLOG_READ' => \&useragent_prolog_read,
  'HANDLER_PROLOG_RESP' => undef, 
  'HANDLER_PRE_INVENTORY' => undef, 
  'HANDLER_POST_INVENTORY' => undef,
  'REQUEST_NAME' => undef,
  'HANDLER_REQUEST' => undef,
  'HANDLER_DUPLICATE' => undef,
  'TYPE' => OPTION_TYPE_SYNC,
  'XML_PARSER_OPT' => {
      'ForceArray' => ['xml_tag']
  }
};

#Special hash to define allowed agents to content to OCS server
my %ocsagents = ( 		
   'OCS-NG_unified_unix_agent' => undef,
   'OCS-NG_windows_client' => [4032,4062],
   'OCS-NG_WINDOWS_AGENT' => undef,
   'OCS-NG_windows_mobile_agent' => undef,
   'OCS-NG_iOS_agent' => undef,
   'OCS-NG_Android_agent' => undef,
);

sub useragent_prolog_read{

  my $current_context=shift;
  my $stop = 1;  #We stop PROLOG by default
  my $srvver = $Apache::Ocsinventory::VERSION;

  my $useragent= &_get_useragent; 

  if (grep /^($useragent->{'NAME'})$/, keys %ocsagents) {
     $useragent->{'VERSION'} =~ s/(\d)\.(\d)(.*)/$1\.$2/g;

     unless ($ocsagents{$useragent->{NAME}}) { #If no version specifed in hash
       if ($useragent->{'VERSION'} <= $srvver) {
         $stop=0;
       }
     } elsif ($useragent->{'VERSION'} >= $ocsagents{$useragent->{'NAME'}}[0] && $useragent->{'VERSION'} <= $ocsagents{$useragent->{'NAME'}}[1]) { #For old windows agent versions compatibility
       $stop= 0;
     }
  }

  #If external references file exists
  if ($ENV{'OCS_OPT_EXT_USERAGENTS_FILE_PATH'} && -f $ENV{'OCS_OPT_EXT_USERAGENTS_FILE_PATH'}) {
    open(FILE, $ENV{'OCS_OPT_EXT_USERAGENTS_FILE_PATH'}) or die "Cannot open $ENV{'OCS_OPT_EXT_USERAGENTS_FILE_PATH'} file !!"; 
    my @ext_useragents = <FILE>; chomp @ext_useragents;
    close FILE;

    #Removing comments lines   
    @ext_useragents = grep {  !/^\#/ } @ext_useragents;

    $stop = 0 if (grep(/^$Apache::Ocsinventory::CURRENT_CONTEXT{'USER_AGENT'}$/, @ext_useragents)); 
  }

  #Does we have to stop PROLOG ?
  if ($stop) {
    &_log(400,'useragent','Bad agent or agent version too recent for server !!') if $ENV{'OCS_OPT_LOGLEVEL'};
    return BAD_USERAGENT;
  }
  else {
    return PROLOG_CONTINUE;
  }
}

sub _get_useragent {
  my $useragent = {};

  $Apache::Ocsinventory::CURRENT_CONTEXT{'USER_AGENT'} =~ m/(.*)_v(.*)$/;
  $useragent->{'NAME'} = $1;
  $useragent->{'VERSION'} = $2;

  return $useragent;
}


1;
