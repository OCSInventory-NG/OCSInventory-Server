################################################################################
## OCSINVENTORY-NG 
## Copyleft Guillaume PROTET DANEK 2010
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################

package Apache::Ocsinventory::Server::Option::Useragent;
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

#Special hash to define allowed agents to conent to OCS server
my %ocsagents = ( 		
   'OCS-NG_unified_unix_agent' => undef,
   'OCS-NG_windows_client' => [4032,4062],
   'OCS-NG_WINDOWS_AGENT' => undef,
);

sub useragent_prolog_read{

  my $current_context=shift;
  my $stop = 1;  #We stop PROLOG by default
  my $useragent = $current_context->{'USER_AGENT'};
  my $srvver = $Apache::Ocsinventory::VERSION;

    &_log(200,'useragent',"USERAGNET=$useragent");

  $useragent =~ m/(.*)_v(.*)$/;
  my ($agentname, $agentver) = ($1, $2);

  if (grep /^($agentname)$/, keys %ocsagents) {
     $agentver=~s/(\d)\.(\d)(.*)/$1\.$2/g;

     unless ($ocsagents{$agentname}) { #If no version specifed in hash
       if ($agentver <= $srvver) {
         $stop=0;
       }
     } elsif ($agentver >= $ocsagents{$agentname}[0] && $agentver <= $ocsagents{$agentname}[1]) { #For old windows agent versions compatibility
       $stop= 0;
     }
  }

  #Does we have to stop PROLOG ?
  if ($stop) {
    &_log(400,'useragent','Bad agent or agent version too recent for server !!');
    return BAD_USERAGENT;
  }
  else {
    return PROLOG_CONTINUE;
  }
}

1;
