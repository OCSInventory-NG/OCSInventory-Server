###############################################################################
## OCSINVENTORY-NG
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Constants;

use strict;

use Apache::Ocsinventory::Map;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw/
	PROLOG_RESP_BREAK
	PROLOG_RESP_STOP
	PROLOG_RESP_SEND
	OPTION_TYPE_SYNC
	OPTION_TYPE_ASYNC
	LOGPATH
	CHECKSUM_MAX_VALUE
	DUP_HOSTNAME_FL
	DUP_SERIAL_FL
	DUP_MACADDR_FL
	DUP_SMODEL_FL
	DUP_UUID_FL
	DUP_ASSETTAG_FL
	PROLOG_STOP
	PROLOG_CONTINUE
	INVENTORY_STOP
	INVENTORY_CONTINUE
/;

use constant PROLOG_RESP_BREAK => 0;
use constant PROLOG_RESP_STOP => 1;
use constant PROLOG_RESP_SEND => 2;

use constant OPTION_TYPE_SYNC => 0;
use constant OPTION_TYPE_ASYNC => 1;

my $checksum_max_value = &get_checksum();
use constant CHECKSUM_MAX_VALUE => $checksum_max_value;

# To enable user to set how auto-duplicates works
use constant DUP_HOSTNAME_FL => 1  ;
use constant DUP_SERIAL_FL   => 2  ;
use constant DUP_MACADDR_FL  => 4  ;
use constant DUP_SMODEL_FL   => 8  ;
use constant DUP_UUID_FL     => 16 ; 
use constant DUP_ASSETTAG_FL => 32 ;

use constant PROLOG_STOP => 1;
use constant PROLOG_CONTINUE => 0;
use constant INVENTORY_STOP => 1;
use constant INVENTORY_CONTINUE => 0;

sub get_checksum {
  my $checksum;

  for my $section (keys %DATA_MAP){
    $checksum|=$DATA_MAP{$section}->{mask};
  }  
  return $checksum;
}
1;
