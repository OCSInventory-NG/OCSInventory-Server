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

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / %DATA_MAP /;

# Field's attributes : cache, filter, noXml, noSql, fallback, type

our %DATA_MAP= (
  hardware => {
   mask => 1,
   multi => 0,
   auto => 0,
   delOnReplace => 1,
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
       DEFAULTGATEWAY => {},
       IPADDR => {},
       DNS => {},
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
       USERAGENT => { noXml => 1 },
       IPSRC => {}
     },
  },
  
  accountinfo =>  {
   mask => 0,
   multi => 1,
   auto => 0,
   delOnReplace => 1,
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
       TYPE => {},
       ASSETTAG => {},
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
       NUMFILES => { fallback=>0},
       CREATEDATE => {}
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
       MACADDR => { fallback => '00:00:00:00:00:00' },
       STATUS => {},
       IPGATEWAY => {},
       IPDHCP => {},
       VIRTUALDEV => {}
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
       PORT => {},
       DESCRIPTION => {}
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
       SOURCE => { fallback=>0 },
       FROM => {}
   }
  },
  
  virtualmachines => {
    mask => 131072,
    multi => 1,
    auto => 1,
    delOnReplace => 1,
    sortBy => 'NAME',
    writeDiff => 1,
    cache => 0,
    fields =>  {
      NAME => {},
      MEMORY => {},
      UUID => {},
      STATUS => {},
      SUBSYSTEM => {},
      VMTYPE => {},
      VCPU => {},
    }
  },

javainfo => {
    mask => 0,
    multi => 0,
    auto => 1,
    delOnReplace => 1,
    sortBy => 'JAVANAME',
    writeDiff => 0,
    cache => 0,
    fields => {
      JAVANAME => { fallback=>'noname' },
      JAVAPATHLEVEL => { fallback=>0 },
      JAVACOUNTRY => {},
      JAVACLASSPATH => {},
      JAVAHOME => {}
    }
  },

  journallog => {
    mask => 0,
    multi => 1,
    auto => 1,
    delOnReplace => 0,
    sortBy => '',
    writeDiff => 0,
    cache => 0,
    fields => {
      JOURNALLOG => {},
      LISTENERNAME => { fallback=>'noname' },
      DATE => {},
      STATUS => { fallback=>0 },
      ERRORCODE => {}
    }
  },

  itmgmt_comments => {
    mask => 0,
    multi => 1,
    auto => 0,
    delOnReplace => 0,
    sortBy => 'DATE_INSERT',
    writeDiff => 0,
    cache => 0,
    fields =>  {
      COMMENTS => {},
      USER_INSERT => {},
      DATE_INSERT => {},
      ACTION  => {},
      VISIBLE => {}
    }
  },

  devices => {
    mask => 0,
    multi => 1,
    auto => 0,
    delOnReplace => 1,
    sortBy => 'NAME',
    writeDiff => 0,
    cache => 0,
    fields =>  {
      NAME => {}, 
      IVALUE => {}, 
      TVALUE  => {},
      COMMENTS => {}
    }
  },

  download_history => {
    mask => 0,
    multi => 1,
    auto => 0,
    delOnReplace => 1,
    sortBy => 'PKD_ID',
    writeDiff => 0,
    cache => 0,
    fields =>  {
      PKG_ID => {},
      PKG_NAME => {}
    }
  },

  groups_cache => {
    mask => 0,
    multi => 1,
    auto => 0,
    delOnReplace => 1,
    sortBy => 'GROUP_ID',
    writeDiff => 0,
    cache => 0,
    fields =>  {
      GROUP_ID => {},
      STATIC => {}
    } 
  }
);
1;
