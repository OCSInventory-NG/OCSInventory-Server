###############################################################################
## OCSINVENTORY-NG
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Constants;

use strict;

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
/;

use constant PROLOG_RESP_BREAK => 0;
use constant PROLOG_RESP_STOP => 1;
use constant PROLOG_RESP_SEND => 2;

use constant OPTION_TYPE_SYNC => 0;
use constant OPTION_TYPE_ASYNC => 1;

# Path to log file directory
use constant LOGPATH => $ENV{'OCS_LOGPATH'};

# Max size of the inventory diff value (17 bits for the moment)
use constant CHECKSUM_MAX_VALUE => 131071;

# To enable user to set how auto-duplicates works
use constant DUP_HOSTNAME_FL => 1;
use constant DUP_SERIAL_FL => 2;
use constant DUP_MACADDR_FL => 4;
1;