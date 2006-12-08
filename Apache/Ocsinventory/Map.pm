###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2006
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Map;

use strict;

use constant CHECKSUM_MAX_VALUE => 131071;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / %data_map /;

our %data_map= (
	hardware		=> {
									mask => 1,
									
									fields => [qw /
											ID
											NAME	
											WORKGROUP	
											USERDOMAIN
											OSNAME 
											OSVERSION 
											OSCOMMENTS 
											PROCESSORT 
											PROCESSORS 
											PROCESSORN 
											MEMORY 
											SWAP 
											IPADDR 
											USERID 
											TYPE 
											DESCRIPTION 
											WINCOMPANY 
											WINOWNER 
											WINPRODID
											LASTDATE
											LASTCOME
											CHECKSUM
											QUALITY
											FIDELITY
										/]
									},
										
	bios				=>  {
									mask => 2,
									
									fields => [qw /		
											SMANUFACTURER
											SMODEL
											SSN
											BMANUFACTURER
											BVERSION
											BDATE
										/]
									},
										
	memories		=> {
									mask => 4,
									
									fields =>  [qw /		
											CAPACITY
											SPEED
											CAPTION
											DESCRIPTION
											NUMSLOTS
											TYPE
											PURPOSE
										/]
									},
	
	slots				=> {
									mask => 8,
									
									fields =>  [qw /
											NAME
											DESCRIPTION
											DESIGNATION
											PURPOSE
											STATUS
											PSHARE
										/]
									},
	
	registry		=> {
									mask => 16,
									
									fields =>  [qw /
											NAME
											REGVALUE
										/]
									},
	
	controllers	=> {
									mask => 32,
									
									fields =>  [qw /
											MANUFACTURER
											NAME
											CAPTION
											DESCRIPTION
											VERSION
											TYPE
										/]
									},
	
	monitors		=> {
									mask => 64,
									
									fields =>  [qw /
											MANUFACTURER
											CAPTION
											DESCRIPTION
											TYPE
											SERIAL
										/]
									},
	
	ports				=> {
									mask => 128,
									
									fields =>  [qw /
											NAME
											CAPTION
											DESCRIPTION
											TYPE
										/]
									},
		
	storages		=> {
									mask => 256,
									
									fields =>  [qw /
											MANUFACTURER
											NAME
											MODEL
											DESCRIPTION
											TYPE
											DISKSIZE
										/]
								},
	
	drives			=> {
									mask => 512,
									
									fields =>  [qw /
											LETTER
											TYPE
											FILESYSTEM
											TOTAL
											FREE
											VOLUMN
											NUMFILES
										/]
									},
	
	inputs			=> {
									mask => 1024,
									
									fields =>  [qw /
											TYPE
											MANUFACTURER
											CAPTION
											DESCRIPTION
											INTERFACE
											POINTTYPE
										/]
									},
	
	modems			=> {
									mask => 2048,
									
									fields =>  [qw /
											NAME
											MODEL
											DESCRIPTION
											TYPE
										/]
									},
	
	networks		=> {
									mask => 4096,
									
									fields =>  [qw /
											IPADDRESS
											IPMASK
											IPADDRESS
											IPSUBNET
											DESCRIPTION
											TYPE
											TYPEMIB
											MACADDR
											STATUS
											IPGATEWAY
											IPDHCP
										/]
									},
	
	printers		=> {
									mask => 8192,
									
									fields =>  [qw /
											NAME
											DRIVER
											PORT
										/]
									},

	sounds			=> {
									mask => 16384,
									
									fields =>  [qw /
											NAME
											MANUFACTURER
											DESCRIPTION
										/]
									},
	
	videos			=> {
									mask => 32768,
									
									fields =>  [qw /
											NAME
											CHIPSET
											MEMORY
											RESOLUTION
										/]
									},
	
	softwares		=> {
									mask => 65536,
									
									fields =>  [qw /
											PUBLISHER
											NAME
											VERSION
											FOLDER
											COMMENTS
											FILENAME
											FILESIZE
											SOURCE
										/]
									},
); 
