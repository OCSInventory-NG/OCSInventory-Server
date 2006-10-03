################################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################

# This core module is used to implement what filter you want.

package Apache::Ocsinventory::Server::Option::Filter;

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
	'HANDLER_PROLOG_READ' => \&prolog_filter,
	'HANDLER_PROLOG_RESP' => undef,
	'HANDLER_PRE_INVENTORY' => \&inventory_filter,
	'HANDLER_POST_INVENTORY' => undef,
	'REQUEST_NAME' => undef,
	'HANDLER_REQUEST' => undef,
	'HANDLER_DUPLICATE' => undef,
	'TYPE' => OPTION_TYPE_SYNC
};

# Default
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_PROLOG_FILTER_ON'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_INVENTORY_FILTER_ON'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_INVENTORY_FILTER_FLOOD_IP'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_INVENTORY_FILTER_FLOOD_INVENTORY'} = 0;

my %autorized;

sub prolog_filter{
	# ON/OFF
	return PROLOG_CONTINUE unless $ENV{'OCS_OPT_PROLOG_FILTER_ON'};
	
	my $block;
	my $current_ip = $ENV{'HTTP_X_FORWARDED_FOR'})?$ENV{'HTTP_X_FORWARDED_FOR'}:$ENV{'REMOTE_ADDR'};
}

sub inventory_filter{
	# ON/OFF
	return INVENTORY_CONTINUE unless $ENV{'OCS_OPT_INVENTORY_FILTER_ON'};
	
	my $block;
	my $current_ip = $ENV{'HTTP_X_FORWARDED_FOR'})?$ENV{'HTTP_X_FORWARDED_FOR'}:$ENV{'REMOTE_ADDR'};

}
1;

