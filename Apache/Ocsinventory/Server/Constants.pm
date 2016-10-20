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
   	BAD_USERAGENT
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
use constant BAD_USERAGENT => 2;
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
