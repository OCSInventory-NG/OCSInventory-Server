###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2006
## Web : http://www.ocsinventory-ng.org
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

# Field's attributes : cache, filter, noXml, noSql, fallback

our %DATA_MAP= (
  hardware => {
   mask => 1,
   multi => 0,
   auto => 0,
   delOnReplace => 0,
   sortBy => 'NAME',
   writeDiff => 0,
   cache => 1,
   fields => {
       ID => { noXml => 1 },
       NAME => {},
       WORKGROUP => {},
       USERDOMAIN => {},
       OSNAME => { cache=>1 },
       OSVERSION => {},
       OSCOMMENTS => {},
       PROCESSORT => {},
       PROCESSORS => {},
       PROCESSORN => {},
       MEMORY => {},
       SWAP => {},
       IPADDR => {},
       USERID => { filter => 1 },
       TYPE => {},
       DESCRIPTION => {},
       WINCOMPANY => {},
       WINOWNER => {},
       WINPRODID => {},
       WINPRODKEY => {},
       LASTDATE => {},
       LASTCOME => {},
       CHECKSUM => {},
       QUALITY => {},
       FIDELITY => {},
       SSTATE => { noXml => 1 },
       USERAGENT => { noXml => 1 }
     },
  },
  
  accountinfo =>  {
   mask => 0,
   multi => 1,
   auto => 0,
   delOnReplace => 0,
   sortBy => 'TAG',
   writeDiff => 0,
   cache => 0,
   fields => {
       TAG => {}
   }
  },
     
  bios =>  {
   mask => 2,
   multi => 0,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'SSN',
   writeDiff => 0,
   cache => 0,
   fields => {
       SMANUFACTURER => {},
       SMODEL => {},
       SSN => {},
       BMANUFACTURER => {},
       BVERSION => {},
       BDATE => {},
       TYPE => {}
   }
  },
     
  memories => {
   mask => 4,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'CAPTION',
   writeDiff => 1,
   cache => 0,
   fields =>  {  
       CAPACITY => {},
       SPEED => {},
       CAPTION => {},
       DESCRIPTION => {},
       NUMSLOTS => { fallback=>0 },
       TYPE => {},
       PURPOSE => {},
       SERIALNUMBER => {}
   }
  },
  
  slots => {
   mask => 8,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       NAME => {},
       DESCRIPTION => {},
       DESIGNATION => {},
       PURPOSE => {},
       STATUS => {},
       PSHARE => { fallback=>0 }
   }
  },
  
  registry => {
   mask => 16,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 1,
   fields =>  {
       NAME => { cache => 1 },
       REGVALUE => { cache => 1 }
   }
  },
  
  controllers => {
   mask => 32,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       MANUFACTURER => {},
       NAME => {},
       CAPTION => {},
       DESCRIPTION => {},
       VERSION => {},
       TYPE => {}
   }
  },
  
  monitors => {
   mask => 64,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'CAPTION',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       MANUFACTURER => {},
       CAPTION => {},
       DESCRIPTION => {},
       TYPE => {},
       SERIAL => {}
   }
  },
  
  ports => {
   mask => 128,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       NAME => {},
       CAPTION => {},
       DESCRIPTION => {},
       TYPE => {}
   }
  },
    
  storages => {
   mask => 256,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       MANUFACTURER => {},
       NAME => {},
       MODEL => {},
       DESCRIPTION => {},
       TYPE => {},
       DISKSIZE => { fallback=>0 },
       SERIALNUMBER => {},
       FIRMWARE => {}
   }
  },
  
  drives => {
   mask => 512,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'VOLUMN',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       LETTER => {},
       TYPE => {},
       FILESYSTEM => {},
       TOTAL => { fallback=>0 },
       FREE => { fallback=>0 },
       VOLUMN => {},
       NUMFILES => { fallback=>0}
   }
  },
  
  inputs => {
   mask => 1024,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'CAPTION',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       TYPE => {},
       MANUFACTURER => {},
       CAPTION => {},
       DESCRIPTION => {},
       INTERFACE => {},
       POINTTYPE => {}
   }
  },
  
  modems => {
   mask => 2048,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       NAME => {},
       MODEL => {},
       DESCRIPTION => {},
       TYPE => {}
   }
  },
  
  networks => {
   mask => 4096,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'IPADDRESS',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       IPADDRESS => {},
       IPMASK => {},
       IPADDRESS => {},
       IPSUBNET => {},
       DESCRIPTION => {},
       TYPE => {},
       TYPEMIB => {},
       SPEED => {},
       MACADDR => {},
       STATUS => {},
       IPGATEWAY => {},
       IPDHCP => {}
   }
  },
  
  printers => {
   mask => 8192,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       NAME => {},
       DRIVER => {},
       PORT => {}
   }
  },

  sounds => {
   mask => 16384,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       NAME  => {},
       MANUFACTURER => {},
       DESCRIPTION => {}
   }
  },
  
  videos => {
   mask => 32768,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   fields =>  {
       NAME => {},
       CHIPSET => {},
       MEMORY => {},
       RESOLUTION => {}
   }
  },
  
  softwares => {
   mask => 65536,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 1,
   fields =>  {
       PUBLISHER => {},
       NAME => { cache => 1 },
       VERSION => {},
       FOLDER => {},
       COMMENTS => {},
       FILENAME => {},
       FILESIZE => { fallback=>0 },
       SOURCE => { fallback=>0 }
   },
  },
  
  netmap => {
   mask => 0,
   multi => 1,
   auto => 0,
   delOnReplace => 1,
   sortBy => 'DATE',
   writeDiff => 0,
   cache => 0,
   fields =>  {
       IP => {},
       MAC => {},
       MASK => {},
       NETID => {},
       DATE => {},
       NAME => {}
   },
  },
); 
1;
