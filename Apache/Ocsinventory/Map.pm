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
       IPSRC => {},
       ARCH => {}
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
       DESCRIPTION => {},
       SERVERNAME => {},
       SHARENAME => {},
       RESOLUTION => {},
       COMMENT => {},
       SHARED => {},
       NETWORK => {}
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
       GUID => {},
       LANGUAGE => {},
       INSTALLDATE => {},
       BITSWIDTH => {}
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
      VCPU => {}
    }
  },

  cpus => {
    mask => 262144,
    multi => 1,
    auto => 1,
    delOnReplace => 1,
    sortBy => 'SERIALNUMBER',
    writeDiff => 1,
    cache => 0,
    fields =>  {
      MANUFACTURER => {},
      TYPE => {},
      SERIALNUMBER => {},
      SPEED => {},
      CORES => {},
      L2CACHESIZE => {}, 
      CPUARCH => {},
      DATA_WIDTH => {},
      CURRENT_ADDRESS_WIDTH => {},
      LOGICAL_CPUS => {},
      VOLTAGE => {},
      CURRENT_SPEED => {},
      SOCKET => {}
    }
  },

  sim => {
    mask => 524288,
    multi => 1,
    auto => 1,
    delOnReplace => 1,
    sortBy => 'SERIALNUMBER',
    writeDiff => 1,
    cache => 0,
    fields =>  {
      OPERATOR => {},
      OPNAME => {},
      COUNTRY => {},
      SERIALNUMBER => {},
      DEVICEID => {}
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
  },

  snmp => {
   mask => 1,
   multi => 0,
   auto => 0,
   delOnReplace => 1,
   sortBy => 'IPADDRESS',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       ID => { noXml => 1 },
       IPADDR => {},
       MACADDR => {},
       SNMPDEVICEID => {},
       NAME => {},
       DESCRIPTION => {},
       CONTACT => {},
       LOCATION => {},
       UPTIME => {},
       DOMAIN => {},
       TYPE => {},
       LASTDATE => {},
       CHECKSUM => {}
   }
  },

  snmp_printers => {
   mask => 2,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       NAME => {},
       SERIALNUMBER => {},
       COUNTER => {},
       STATUS => {},
       ERRORSTATE => {}
   }
  },

  snmp_switchs => {
   mask => 4,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       MANUFACTURER => {},
       REFERENCE => {},
       TYPE => {},
       SOFTVERSION => {},
       FIRMVERSION => {},
       SERIALNUMBER => {},
       REVISION => {},
       DESCRIPTION => {}
   }
  },

  snmp_firewalls => {
   mask => 8,
   multi => 0,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       SERIALNUMBER => {},
       SYSTEM => {}
   }
  },

  snmp_blades => {
   mask => 16,
   multi => 0,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       SERIALNUMBER => {},
       SYSTEM => {}
   }
  },

  snmp_loadbalancers => {
   mask => 32,
   multi => 0,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       SERIALNUMBER => {},
       SYSTEM => {},
       TYPE => {},
       MANUFACTURER => {}
   }
  },

  snmp_trays => {
   mask => 64,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'NAME',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       NAME => {},
       DESCRIPTION => {},
       LEVEL => {},
       MAXCAPACITY => {}
   }
  },

  snmp_cartridges => {
   mask => 128,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'DESCRIPTION',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       DESCRIPTION => {},
       TYPE => {},
       LEVEL => {},
       MAXCAPACITY => {},
       COLOR => {}
   }
  },

  snmp_networks => {
   mask => 256,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       DESCRIPTION => {},
       MACADDR => {},
       DEVICEMACADDR => {},
       SLOT => {},
       STATUS => {},
       SPEED => {},
       TYPE => {},
       DEVICEADDRESS => {},
       DEVICENAME => {},
       DEVICEPORT => {},
       DEVICETYPE => {},
       TYPEMIB => {},
       IPADDR => {},
       IPMASK => {},
       IPGATEWAY => {},
       IPSUBNET => {},
       IPDHCP => {},
       DRIVER => {},
       VIRTUALDEV => {}
   }
  },

  snmp_storages => {
   mask => 512,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       DESCRIPTION => {},
       MANUFACTURER => {},
       NAME => {},
       MODEL => {},
       DISKSIZE => {},
       TYPE => {},
       SERIALNUMBER => {},
       FIRMWARE => {}
   }
  },


  snmp_drives => {
   mask => 1024,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       LETTER => {},
       TYPE => {},
       FILESYSTEM => {},
       TOTAL => {},
       FREE => {},
       NUMFILES => {},
       VOLUMN => {},
       LABEL => {},
       SERIAL => {}
   }
  },

  snmp_powersupplies => {
   mask => 2048,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       MANUFACTURER => {},
       REFERENCE => {},
       TYPE => {},
       SERIALNUMBER => {},
       DESCRIPTION => {},
       REVISION => {}
   }
  },

  snmp_fans => {
   mask => 4096,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       DESCRIPTION => {},
       REFERENCE => {},
       REVISION => {},
       SERIALNUMBER => {},
       MANUFACTURER => {},
       TYPE => {}
   }
  },


  snmp_cards => {
   mask => 8192,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       DESCRIPTION => {},
       REFERENCE => {},
       FIRMWARE => {},
       SOFTWARE => {},
       REVISION => {},
       SERIALNUMBER => {},
       MANUFACTURER => {},
       TYPE => {}
   }
  },

  snmp_switchinfos => {
   mask => 16384,
   multi => 0,
   auto => 1,
   delOnReplace => 1,
   sortBy => 'TYPE',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       TYPE => {}
   }
  },

  snmp_computers => {
   mask => 32768,
   multi => 0,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       SYSTEM => {}
   }
  },

  snmp_softwares => {
   mask => 65536,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       NAME => {},
       INSTALLDATE => {},
       COMMENTS => {},
       VERSION => {}
   }
  },

  snmp_memories => {
   mask => 131072,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       CAPACITY => {}
   }
  },

  snmp_cpus => {
   mask => 262144,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       SPEED => {},
       TYPE => {},
       MANUFACTURER => {}
   }
  },

  snmp_inputs => {
   mask => 524288,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       DESCRIPTION => {},
       TYPE => {}
   }
  },

  snmp_ports => {
   mask => 1048576,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       NAME => {},
       TYPE => {}
   }
  },

  snmp_sounds => {
   mask => 2097152,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       NAME => {}
   }
  },

  snmp_videos => {
   mask => 4194304,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       NAME => {}
   }
  },

  snmp_modems => {
   mask => 8388608,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       NAME => {}
   }
  },

  snmp_localprinters => {
   mask => 16777216,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       NAME => {}
   }
  },

  snmp_virtualmachines => {
   mask => 33554432,
   multi => 1,
   auto => 1,
   delOnReplace => 1,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       CONFIG_FILE => {},
       CPU => {},
       MEMORY => {},
       NAME => {},
       OS => {},
       POWER => {},
       UUID => {} 
   }
  },

  snmp_accountinfo =>  {
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

  snmp_laststate => {
   mask => 0,
   multi => 0,
   auto => 0,
   delOnReplace => 0,
   sortBy => '',
   writeDiff => 1,
   cache => 0,
   capacities => 'snmp',
   fields =>  {
       COMMON => {},
       PRINTERS => {},
       TRAYS => {},
       CARTRIDGES => {},
       NETWORKS => {},
       SWITCHS => {},
       BLADES => {},
       STORAGES => {},
       DRIVES => {},
       POWERSUPPLIES => {},
       FANS => {},
       LOADBALANCERS => {},
       CARDS => {},
       FIREWALLS => {},
       SWITCHINFOS => {},
       COMPUTERS => {},
       SOFTWARES => {},
       MEMORIES => {},
       CPUS => {},
       INPUTS => {},
       PORTS => {},
       SOUNDS => {},
       VIDEOS => {},
       MODEMS => {},
       LOCALPRINTERS => {},
       VIRTUALMACHINES => {}
   }
  },

);
1;
