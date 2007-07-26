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

our @EXPORT = qw / %DATA_MAP /;

# Field's attributes : cache, filter, noXml, noSql

our %DATA_MAP= (
	hardware		=> {
									mask => 1,
									
									multi => 0,
									
									auto => 0,
									
									delOnReplace => 0,
									
									fields => {
											ID 						=> { noXml => 1 },
											NAME 					=> {},
											WORKGROUP 		=> {},
											USERDOMAIN 		=> {},
											OSNAME 				=> { cache=>1 },
											OSVERSION 		=> {},
											OSCOMMENTS 		=> {},
											PROCESSORT 		=> {},
											PROCESSORS 		=> {},
											PROCESSORN 		=> {},
											MEMORY 				=> {},
											SWAP 					=> {},
											IPADDR 				=> {},
											USERID 				=> { filter => 1 },
											TYPE 					=> {},
											DESCRIPTION 	=> {},
											WINCOMPANY 		=> {},
											WINOWNER 			=> {},
											WINPRODID 		=> {},
											WINPRODKEY 		=> {},
											LASTDATE 			=> {},
											LASTCOME 			=> {},
											CHECKSUM 			=> {},
											QUALITY 			=> {},
											FIDELITY 			=> {},
											SSTATE 				=> { noXml => 1 },
											USERAGENT 		=> { noXml => 1 }
										},
	},
	
	accountinfo	=>	{
									mask => 0,
									
									multi => 1,
									
									auto => 0,
									
									delOnReplace => 0,
									
									fields => {
											TAG => {}
									}
	},
										
	bios				=>  {
									mask => 2,
									
									multi => 0,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields => {
											SMANUFACTURER 	=> {},
											SMODEL 					=> {},
											SSN 						=> {},
											BMANUFACTURER 	=> {},
											BVERSION 				=> {},
											BDATE 					=> {}
									}
	},
										
	memories		=> {
									mask => 4,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {	
											CAPACITY 			=> {},
											SPEED 				=> {},
											CAPTION 			=> {},
											DESCRIPTION 	=> {},
											NUMSLOTS 			=> {},
											TYPE 					=> {},
											PURPOSE 			=> {},
									}
	},
	
	slots				=> {
									mask => 8,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											NAME 					=> {},
											DESCRIPTION 	=> {},
											DESIGNATION 	=> {},
											PURPOSE 			=> {},
											STATUS 				=> {},
											PSHARE 				=> {},
									}
	},
	
	registry		=> {
									mask => 16,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											NAME 			=> { cache => 1 },
											REGVALUE 	=> { cache => 1 }
									}
	},
	
	controllers	=> {
									mask => 32,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											MANUFACTURER 	=> {},
											NAME 					=> {},
											CAPTION 			=> {},
											DESCRIPTION 	=> {},
											VERSION 			=> {},
											TYPE 					=> {}
									}
	},
	
	monitors		=> {
									mask => 64,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											MANUFACTURER 	=> {},
											CAPTION 			=> {},
											DESCRIPTION 	=> {},
											TYPE 					=> {},
											SERIAL 				=> {}
									}
	},
	
	ports				=> {
									mask => 128,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											NAME 				=> {},
											CAPTION 		=> {},
											DESCRIPTION => {},
											TYPE 				=> {}
									}
	},
		
	storages		=> {
									mask => 256,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											MANUFACTURER 	=> {},
											NAME 					=> {},
											MODEL 				=> {},
											DESCRIPTION 	=> {},
											TYPE 					=> {},
											DISKSIZE 			=> {}
									}
	},
	
	drives			=> {
									mask => 512,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											LETTER 			=> {},
											TYPE 				=> {},
											FILESYSTEM 	=> {},
											TOTAL 			=> {},
											FREE 				=> {},
											VOLUMN 			=> {},
											NUMFILES 		=> {}
									}
	},
	
	inputs			=> {
									mask => 1024,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											TYPE 					=> {},
											MANUFACTURER 	=> {},
											CAPTION 			=> {},
											DESCRIPTION 	=> {},
											INTERFACE 		=> {},
											POINTTYPE 		=> {}
									}
	},
	
	modems			=> {
									mask => 2048,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											NAME 				=> {},
											MODEL 			=> {},
											DESCRIPTION => {},
											TYPE 				=> {}
									}
	},
	
	networks		=> {
									mask => 4096,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											IPADDRESS 	=> {},
											IPMASK 			=> {},
											IPADDRESS 	=> {},
											IPSUBNET 		=> {},
											DESCRIPTION => {},
											TYPE 				=> {},
											TYPEMIB 		=> {},
											SPEED				=> {},
											MACADDR 		=> {},
											STATUS 			=> {},
											IPGATEWAY 	=> {},
											IPDHCP 			=> {}
									}
	},
	
	printers		=> {
									mask => 8192,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											NAME 		=> {},
											DRIVER 	=> {},
											PORT 		=> {}
									}
	},

	sounds			=> {
									mask => 16384,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											NAME 					=> {},
											MANUFACTURER 	=> {},
											DESCRIPTION 	=> {}
									}
	},
	
	videos			=> {
									mask => 32768,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											NAME 				=> {},
											CHIPSET 		=> {},
											MEMORY 			=> {},
											RESOLUTION 	=> {}
									}
	},
	
	softwares		=> {
									mask => 65536,
									
									multi => 1,
									
									auto => 1,
									
									delOnReplace => 1,
									
									fields =>  {
											PUBLISHER => {},
											NAME 			=> { cache => 1 },
											VERSION 	=> {},
											FOLDER 		=> {},
											COMMENTS 	=> {},
											FILENAME 	=> {},
											FILESIZE 	=> {},
											SOURCE 		=> {}
									},
	},
); 
1;
