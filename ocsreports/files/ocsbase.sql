CREATE DATABASE ocsweb;
USE ocsweb;

CREATE TABLE hardware (
  ID INTEGER NOT NULL auto_increment,
  DEVICEID VARCHAR(255) not NULL,
  NAME VARCHAR(255) default NULL,
  WORKGROUP VARCHAR(255) default NULL,
  USERDOMAIN VARCHAR(255) default NULL,
  OSNAME VARCHAR(255) default NULL,
  OSVERSION VARCHAR(255) default NULL,
  OSCOMMENTS VARCHAR(255) default NULL,
  PROCESSORT VARCHAR(255) default NULL,
  PROCESSORS INTEGER default 0,
  PROCESSORN SMALLINT default NULL,
  MEMORY INTEGER default NULL,
  SWAP INTEGER default NULL,
  IPADDR VARCHAR(255) default NULL,
  ETIME DATETIME default NULL,
  LASTDATE DATETIME default NULL,
  LASTCOME DATETIME default NULL,
  QUALITY DECIMAL(7,4) default 0,
  FIDELITY BIGINT default 1,
  USERID VARCHAR(255) default NULL,
  `TYPE` INTEGER default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  WINCOMPANY VARCHAR(255) default NULL,
  WINOWNER VARCHAR(255) default NULL,
  WINPRODID VARCHAR(255) default NULL,
  WINPRODKEY VARCHAR(255) default NULL,
  USERAGENT VARCHAR(50) default NULL,
  CHECKSUM INTEGER default 0,
  SSTATE INTEGER default 0,
  PRIMARY KEY  (ID),
  INDEX NAME (NAME),
  INDEX CHECKSUM (CHECKSUM),
  INDEX USERID(USERID),
  INDEX WORKGROUP(WORKGROUP),
  INDEX OSNAME(OSNAME),
  INDEX MEMORY(MEMORY),
  INDEX DEVICEID (DEVICEID)
) ENGINE=INNODB ;

CREATE TABLE accesslog (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  USERID VARCHAR(255) default NULL,
  LOGDATE DATETIME default NULL,
  PROCESSES TEXT,
  INDEX USERID(USERID),
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE accountinfo (
  HARDWARE_ID INTEGER NOT NULL,
  TAG VARCHAR(255) default 'NA',
  primary key(HARDWARE_ID),
  INDEX TAG (TAG)
) ENGINE=INNODB ;

CREATE TABLE deploy (
  NAME VARCHAR(255) NOT NULL,
  CONTENT LONGBLOB NOT NULL,
  PRIMARY KEY  (NAME)
) ENGINE=MYISAM ;

CREATE TABLE netmap (
  IP VARCHAR(15) NOT NULL,
  MAC VARCHAR(17) NOT NULL,
  MASK VARCHAR(15) NOT NULL,
  NETID VARCHAR(15) NOT NULL,
  DATE TIMESTAMP default CURRENT_TIMESTAMP,
  NAME VARCHAR(255) default NULL,
  PRIMARY KEY  (MAC),
  INDEX IP (IP),
  INDEX NETID (NETID)
) ENGINE=INNODB ;

CREATE TABLE bios (
  HARDWARE_ID INTEGER NOT NULL,
  SMANUFACTURER VARCHAR(255) default NULL,
  SMODEL VARCHAR(255) default NULL,
  SSN VARCHAR(255) default NULL,
  `TYPE` VARCHAR(255) default NULL,
  BMANUFACTURER VARCHAR(255) default NULL,
  BVERSION VARCHAR(255) default NULL,
  BDATE VARCHAR(255) default NULL,
  PRIMARY KEY  (HARDWARE_ID),
  INDEX SSN (SSN)
) ENGINE=INNODB ;

CREATE TABLE config (
  NAME VARCHAR(50) NOT NULL,
  IVALUE INTEGER default NULL,
  TVALUE VARCHAR(255) default NULL,
  COMMENTS TEXT,
  PRIMARY KEY (NAME)
) ENGINE=MYISAM ;

CREATE TABLE controllers (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  MANUFACTURER VARCHAR(255) default NULL,
  NAME VARCHAR(255) default NULL,
  CAPTION VARCHAR(255) default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  VERSION VARCHAR(255) default NULL,
  `TYPE` VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE devices (
  HARDWARE_ID INTEGER NOT NULL,
  NAME VARCHAR(50) NOT NULL,
  IVALUE INTEGER default NULL,
  TVALUE VARCHAR(255) default NULL,
  COMMENTS TEXT,
  INDEX HARDWARE_ID (HARDWARE_ID),
  INDEX TVALUE (TVALUE),
  INDEX IVALUE (IVALUE),
  INDEX NAME (NAME)
) ENGINE=INNODB ;

CREATE TABLE drives (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  LETTER VARCHAR(255) default NULL,
  `TYPE` VARCHAR(255) default NULL,
  FILESYSTEM VARCHAR(255) default NULL,
  TOTAL INTEGER default NULL,
  FREE INTEGER default NULL,
  NUMFILES INTEGER default NULL,
  VOLUMN VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE files (
  NAME VARCHAR(255) NOT NULL,
  VERSION VARCHAR(255) NOT NULL,
  OS VARCHAR(255) NOT NULL,
  CONTENT LONGBLOB NOT NULL,
  PRIMARY KEY  (NAME, OS, VERSION)
) ENGINE=MYISAM ;

CREATE TABLE inputs (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  `TYPE` VARCHAR(255) default NULL,
  MANUFACTURER VARCHAR(255) default NULL,
  CAPTION VARCHAR(255) default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  INTERFACE VARCHAR(255) default NULL,
  POINTTYPE VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE memories (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  CAPTION VARCHAR(255) default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  CAPACITY VARCHAR(255) default NULL,
  PURPOSE VARCHAR(255) default NULL,
  `TYPE` VARCHAR(255) default NULL,
  SPEED VARCHAR(255) default NULL,
  NUMSLOTS SMALLINT default NULL,
  SERIALNUMBER VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE modems (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  NAME VARCHAR(255) default NULL,
  MODEL VARCHAR(255) default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  `TYPE` VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE monitors (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  MANUFACTURER VARCHAR(255) default NULL,
  CAPTION VARCHAR(255) default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  `TYPE` VARCHAR(255) default NULL,
  SERIAL VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;


CREATE TABLE networks (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  `TYPE` VARCHAR(255) default NULL,
  TYPEMIB VARCHAR(255) default NULL,
  SPEED VARCHAR(255) default NULL,
  MACADDR VARCHAR(255) default NULL,
  `STATUS` VARCHAR(255) default NULL,
  IPADDRESS VARCHAR(255) default NULL,
  IPMASK VARCHAR(255) default NULL,
  IPGATEWAY VARCHAR(255) default NULL,
  IPSUBNET VARCHAR(255) default NULL,
  IPDHCP VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID),
  INDEX MACADDR (MACADDR),
  INDEX IPADDRESS(IPADDRESS),
  INDEX IPGATEWAY(IPGATEWAY),
  INDEX IPSUBNET (IPSUBNET)
) ENGINE=INNODB ;

CREATE TABLE network_devices(
  ID INTEGER NOT NULL auto_increment,
  DESCRIPTION VARCHAR(255) default NULL,
  `TYPE` VARCHAR(255) default NULL,
  MACADDR VARCHAR(255) default NULL,
  `USER` VARCHAR(255) default NULL,
  PRIMARY KEY (ID),
  INDEX MACADDR (MACADDR)
) ENGINE=MYISAM ;

CREATE TABLE operators (
  ID VARCHAR(255) NOT NULL default '',
  FIRSTNAME VARCHAR(255) default NULL,
  LASTNAME VARCHAR(255) default NULL,
  PASSWD VARCHAR(50) default NULL,
  ACCESSLVL INTEGER default NULL,
  COMMENTS text,
  PRIMARY KEY  (ID)
) ENGINE=MYISAM ;

CREATE TABLE ports (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  `TYPE` VARCHAR(255) default NULL,
  NAME VARCHAR(255) default NULL,
  CAPTION VARCHAR(255) default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE printers (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  NAME VARCHAR(255) default NULL,
  DRIVER VARCHAR(255) default NULL,
  PORT VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE regconfig (
  ID INTEGER NOT NULL auto_increment,
  NAME VARCHAR(255) default NULL,
  REGTREE INTEGER default NULL,
  REGKEY text,
  REGVALUE VARCHAR(255) default NULL,
  PRIMARY KEY  (ID),
  KEY NAME (NAME)
) ENGINE=MYISAM ;

CREATE TABLE registry (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  NAME VARCHAR(255) default NULL,
  REGVALUE VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID),
  KEY NAME (NAME)
) ENGINE=INNODB ;

CREATE TABLE slots (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  NAME VARCHAR(255) default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  DESIGNATION VARCHAR(255) default NULL,
  PURPOSE VARCHAR(255) default NULL,
  `STATUS` VARCHAR(255) default NULL,
  PSHARE tinyint(4) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE softwares (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  PUBLISHER VARCHAR(255) default NULL,
  NAME VARCHAR(255) default NULL,
  VERSION VARCHAR(255) default NULL,
  FOLDER text,
  COMMENTS text,
  FILENAME VARCHAR(255) default NULL,
  FILESIZE INTEGER default '0',
  SOURCE INTEGER default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID),
  INDEX NAME (NAME),
  INDEX `VERSION`(`VERSION`)
) ENGINE=INNODB ;

CREATE TABLE `sounds` (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  MANUFACTURER VARCHAR(255) default NULL,
  NAME VARCHAR(255) default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE storages (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  MANUFACTURER VARCHAR(255) default NULL,
  NAME VARCHAR(255) default NULL,
  MODEL VARCHAR(255) default NULL,
  DESCRIPTION VARCHAR(255) default NULL,
  `TYPE` VARCHAR(255) default NULL,
  DISKSIZE INTEGER default NULL,
  SERIALNUMBER VARCHAR(255) default NULL,
  FIRMWARE VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE videos (
  ID INTEGER NOT NULL auto_increment,
  HARDWARE_ID INTEGER NOT NULL,
  NAME VARCHAR(255) default NULL,
  CHIPSET VARCHAR(255) default NULL,
  MEMORY VARCHAR(255) default NULL,
  RESOLUTION VARCHAR(255) default NULL,
  PRIMARY KEY  (ID, HARDWARE_ID)
) ENGINE=INNODB ;

CREATE TABLE devicetype (
  ID INTEGER NOT NULL auto_increment,
  NAME VARCHAR(255) default NULL,
  PRIMARY KEY  (ID)
) ENGINE=MYISAM ;

CREATE TABLE subnet (
  NETID VARCHAR(15) NOT NULL,
  NAME VARCHAR(255),
  ID INTEGER,
  MASK VARCHAR(255),
  PRIMARY KEY (NETID),
  INDEX ID(ID)
) ENGINE=MYISAM ;

CREATE TABLE locks(
  HARDWARE_ID INTEGER NOT NULL PRIMARY KEY,
  ID INTEGER DEFAULT NULL,
  SINCE TIMESTAMP,
  INDEX SINCE (SINCE)
) ENGINE=HEAP ;

CREATE TABLE dico_ignored(
  EXTRACTED VARCHAR(255) NOT NULL,
  PRIMARY KEY(EXTRACTED)
) ENGINE=MYISAM ;

CREATE TABLE dico_soft( 
  EXTRACTED VARCHAR(255) NOT NULL,
  FORMATTED VARCHAR(255) NOT NULL,
  PRIMARY KEY(EXTRACTED)
) ENGINE=MYISAM ;

CREATE TABLE deleted_equiv(
  DATE TIMESTAMP, 
  DELETED VARCHAR(255) NOT NULL,
  EQUIVALENT VARCHAR(255) default NULL
) ENGINE=MYISAM ;

CREATE TABLE download_available(
	FILEID VARCHAR(255) NOT NULL PRIMARY KEY,
	NAME VARCHAR(255) NOT NULL,
	PRIORITY INTEGER NOT NULL,
	FRAGMENTS INTEGER NOT NULL,
	SIZE INTEGER NOT NULL,
	OSNAME VARCHAR(255) NOT NULL,
	COMMENT TEXT
) ENGINE = INNODB;

CREATE TABLE download_enable(
	ID INTEGER NOT NULL auto_increment PRIMARY KEY,
	FILEID VARCHAR(255) NOT NULL,
	INFO_LOC VARCHAR(255) NOT NULL,
	PACK_LOC VARCHAR(255) NOT NULL,
	CERT_PATH VARCHAR(255),
	CERT_FILE VARCHAR(255),
	INDEX FILEID(FILEID)
) ENGINE = INNODB;

CREATE TABLE download_history(
	HARDWARE_ID INTEGER NOT NULL,
	PKG_ID INTEGER default NULL,
	PKG_NAME VARCHAR(255),
	PRIMARY KEY(HARDWARE_ID, PKG_ID)
) ENGINE = INNODB;

CREATE TABLE conntrack(
	IP VARCHAR(255),
	`TIMESTAMP` TIMESTAMP,
	PRIMARY KEY(IP)
) ENGINE = HEAP;

CREATE TABLE groups(
	HARDWARE_ID integer default NULL,
	REQUEST longtext,
	CREATE_TIME INT,
	PRIMARY KEY(HARDWARE_ID)
) ENGINE=MYISAM;

CREATE TABLE groups_cache(
	HARDWARE_ID integer NOT NULL default 0,
	GROUP_ID integer NOT NULL default 0,
	STATIC integer default 0,
	PRIMARY KEY(HARDWARE_ID,GROUP_ID)
) ENGINE=MYISAM;

CREATE TABLE blacklist_macaddresses(
	ID INTEGER auto_increment,
	MACADDRESS VARCHAR(255),
	PRIMARY KEY(MACADDRESS),
	INDEX ID(ID)
) ENGINE = MYISAM;

CREATE TABLE blacklist_serials(
	ID INTEGER auto_increment,
	SERIAL VARCHAR(255),
	PRIMARY KEY(SERIAL),
	INDEX ID(ID)
) ENGINE = MYISAM;

CREATE TABLE registry_name_cache(
        ID INTEGER auto_increment,
        NAME VARCHAR(255) UNIQUE,
        PRIMARY KEY(ID)
) ENGINE = MYISAM;
TRUNCATE TABLE registry_name_cache;
INSERT INTO registry_name_cache(name) SELECT DISTINCT name FROM registry;

CREATE TABLE registry_regvalue_cache(
	ID INTEGER auto_increment,
	REGVALUE VARCHAR(255) UNIQUE,
	PRIMARY KEY(ID)
) ENGINE = MYISAM;
TRUNCATE TABLE registry_regvalue_cache;
INSERT INTO registry_regvalue_cache(regvalue) SELECT DISTINCT regvalue FROM registry;

CREATE TABLE hardware_osname_cache(
        ID INTEGER auto_increment,
        OSNAME VARCHAR(255) UNIQUE,
        PRIMARY KEY(ID)
) ENGINE = MYISAM;
TRUNCATE TABLE hardware_osname_cache;
INSERT INTO hardware_osname_cache(osname) SELECT DISTINCT osname FROM hardware;

CREATE TABLE softwares_name_cache(
        ID INTEGER auto_increment,
        NAME VARCHAR(255) UNIQUE,
        PRIMARY KEY(ID)
) ENGINE = MYISAM;
TRUNCATE TABLE softwares_name_cache;
INSERT INTO softwares_name_cache(name) SELECT DISTINCT name FROM softwares;

CREATE TABLE tags (
  Tag VARCHAR(255) NOT NULL default '',
  Login VARCHAR(255) NOT NULL default '',
  PRIMARY KEY  (Tag,Login),
  KEY Tag (Tag),
  KEY Login (Login)
) ENGINE=MyISAM;

CREATE TABLE engine_mutex (
  NAME varchar(255) NOT NULL default '',
  PID int(11) default NULL,
  TAG varchar(255) NOT NULL default '',
  PRIMARY KEY  (NAME,TAG)
) ENGINE=MEMORY DEFAULT CHARSET=latin1;

CREATE TABLE engine_persistent (
 ID int(11) NOT NULL auto_increment,
 NAME varchar(255) NOT NULL default '',
 IVALUE int(11) default NULL,
 TVALUE varchar(255) default NULL,
  UNIQUE KEY NAME (NAME),
  KEY ID (ID)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

ALTER TABLE devices ADD INDEX IVALUE (IVALUE);
ALTER TABLE devices ADD INDEX NAME (NAME);
ALTER TABLE monitors ADD COLUMN SERIAL VARCHAR(255);
ALTER TABLE netmap ADD COLUMN MASK VARCHAR(15);
ALTER TABLE netmap ADD COLUMN NETID VARCHAR(15);
ALTER TABLE netmap ADD INDEX NETID (NETID);
ALTER TABLE netmap ADD COLUMN DATE TIMESTAMP;
ALTER TABLE netmap ADD COLUMN NAME VARCHAR(255) default NULL;
ALTER TABLE networks ADD COLUMN IPSUBNET VARCHAR(15);
ALTER TABLE networks ADD INDEX IPSUBNET (IPSUBNET);
ALTER TABLE networks ADD INDEX MACADDR (MACADDR);
ALTER TABLE hardware ADD COLUMN CHECKSUM INTEGER default NULL;
ALTER TABLE hardware CHANGE COLUMN CHECKSUM CHECKSUM INTEGER default 131071;
ALTER TABLE hardware add column WINPRODKEY VARCHAR(255) default NULL;
ALTER TABLE hardware add column USERDOMAIN VARCHAR(255) default NULL;
ALTER TABLE hardware ADD COLUMN SSTATE INTEGER default 0;

ALTER TABLE hardware CHANGE ID ID INTEGER;
ALTER TABLE hardware DROP PRIMARY KEY;
ALTER TABLE hardware ADD COLUMN ID integer not NULL FIRST;
ALTER TABLE hardware ADD INDEX ID (ID);
ALTER TABLE hardware CHANGE ID ID INTEGER auto_increment;
ALTER TABLE hardware add PRIMARY KEY(DEVICEID, ID);

ALTER TABLE bios DROP PRIMARY KEY;
ALTER TABLE bios ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE bios SET bios.HARDWARE_ID= (SELECT ID FROM hardware WHERE bios.DEVICEID = hardware.DEVICEID);
ALTER TABLE bios DROP DEVICEID;
ALTER TABLE bios ADD PRIMARY KEY(HARDWARE_ID);

ALTER TABLE accountinfo DROP PRIMARY KEY;
ALTER TABLE accountinfo ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE accountinfo SET accountinfo.HARDWARE_ID= (SELECT ID FROM hardware WHERE accountinfo.DEVICEID = hardware.DEVICEID);
ALTER TABLE accountinfo DROP DEVICEID;
ALTER TABLE accountinfo ADD PRIMARY KEY(HARDWARE_ID);

ALTER TABLE devices DROP PRIMARY KEY;
ALTER TABLE devices ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE devices SET devices.HARDWARE_ID= (SELECT ID FROM hardware WHERE devices.DEVICEID = hardware.DEVICEID);
ALTER TABLE devices DROP DEVICEID;
ALTER TABLE devices ADD INDEX HARDWARE_ID (HARDWARE_ID);

ALTER TABLE controllers change ID ID INTEGER;
ALTER TABLE controllers DROP PRIMARY KEY;
ALTER TABLE controllers ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE controllers SET controllers.HARDWARE_ID= (SELECT ID FROM hardware WHERE controllers.DEVICEID = hardware.DEVICEID);
ALTER TABLE controllers ADD INDEX ID (ID);
ALTER TABLE controllers change ID ID INTEGER auto_increment;
ALTER TABLE controllers ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE controllers DROP DEVICEID;

ALTER TABLE slots change ID ID INTEGER;
ALTER TABLE slots DROP PRIMARY KEY;
ALTER TABLE slots ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE slots SET slots.HARDWARE_ID= (SELECT ID FROM hardware WHERE slots.DEVICEID = hardware.DEVICEID);
ALTER TABLE slots ADD INDEX ID (ID);
ALTER TABLE slots change ID ID INTEGER auto_increment;
ALTER TABLE slots ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE slots DROP DEVICEID;

ALTER TABLE registry change ID ID INTEGER;
ALTER TABLE registry DROP PRIMARY KEY;
ALTER TABLE registry ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE registry SET registry.HARDWARE_ID= (SELECT ID FROM hardware WHERE registry.DEVICEID = hardware.DEVICEID);
ALTER TABLE registry ADD INDEX ID (ID);
ALTER TABLE registry change ID ID INTEGER auto_increment;
ALTER TABLE registry ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE registry DROP DEVICEID;

INSERT INTO network_devices(DESCRIPTION,TYPE,MACADDR,`USER`) SELECT DESCRIPTION,TYPE,MACADDR,TYPEMIB FROM networks WHERE DEVICEID LIKE "NETWORK_DEVICE-%";
DELETE FROM network_devices WHERE DEVICEID LIKE "NETWORK_DEVICE-%";

ALTER TABLE networks change ID ID INTEGER;
ALTER TABLE networks DROP PRIMARY KEY;
ALTER TABLE networks ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE networks SET networks.HARDWARE_ID= (SELECT ID FROM hardware WHERE networks.DEVICEID = hardware.DEVICEID);
ALTER TABLE networks ADD INDEX ID (ID);
ALTER TABLE networks change ID ID INTEGER auto_increment;
ALTER TABLE networks ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE networks DROP DEVICEID;

ALTER TABLE memories change ID ID INTEGER;
ALTER TABLE memories DROP PRIMARY KEY;
ALTER TABLE memories ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE memories SET memories.HARDWARE_ID= (SELECT ID FROM hardware WHERE memories.DEVICEID = hardware.DEVICEID);
ALTER TABLE memories ADD INDEX ID (ID);
ALTER TABLE memories change ID ID INTEGER auto_increment;
ALTER TABLE memories ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE memories DROP DEVICEID;
ALTER TABLE memories ADD COLUMN SERIALNUMBER VARCHAR(255) default NULL AFTER NUMSLOTS;

ALTER TABLE drives change ID ID INTEGER;
ALTER TABLE drives DROP PRIMARY KEY;
ALTER TABLE drives ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE drives SET drives.HARDWARE_ID= (SELECT ID FROM hardware WHERE drives.DEVICEID = hardware.DEVICEID);
ALTER TABLE drives ADD INDEX ID (ID);
ALTER TABLE drives change ID ID INTEGER auto_increment;
ALTER TABLE drives ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE drives DROP DEVICEID;

ALTER TABLE storages change ID ID INTEGER;
ALTER TABLE storages DROP PRIMARY KEY;
ALTER TABLE storages ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE storages SET storages.HARDWARE_ID= (SELECT ID FROM hardware WHERE storages.DEVICEID = hardware.DEVICEID);
ALTER TABLE storages ADD INDEX ID (ID);
ALTER TABLE storages change ID ID INTEGER auto_increment;
ALTER TABLE storages ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE storages DROP DEVICEID;
ALTER TABLE storages ADD COLUMN SERIALNUMBER VARCHAR(255) default NULL AFTER DISKSIZE;
ALTER TABLE storages ADD COLUMN FIRMWARE VARCHAR(255) default NULL AFTER SERIALNUMBER;

ALTER TABLE ports change ID ID INTEGER;
ALTER TABLE ports DROP PRIMARY KEY;
ALTER TABLE ports ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE ports SET ports.HARDWARE_ID= (SELECT ID FROM hardware WHERE ports.DEVICEID = hardware.DEVICEID);
ALTER TABLE ports ADD INDEX ID (ID);
ALTER TABLE ports change ID ID INTEGER auto_increment;
ALTER TABLE ports ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE ports DROP DEVICEID;

ALTER TABLE accesslog change ID ID INTEGER;
ALTER TABLE accesslog DROP PRIMARY KEY;
ALTER TABLE accesslog ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE accesslog SET accesslog.HARDWARE_ID= (SELECT ID FROM hardware WHERE accesslog.DEVICEID = hardware.DEVICEID);
ALTER TABLE accesslog ADD INDEX ID (ID);
ALTER TABLE accesslog change ID ID INTEGER auto_increment;
ALTER TABLE accesslog ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE accesslog DROP DEVICEID;

ALTER TABLE softwares change ID ID INTEGER;
ALTER TABLE softwares DROP PRIMARY KEY;
ALTER TABLE softwares ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE softwares SET softwares.HARDWARE_ID= (SELECT ID FROM hardware WHERE softwares.DEVICEID = hardware.DEVICEID);
ALTER TABLE softwares ADD INDEX ID (ID);
ALTER TABLE softwares change ID ID INTEGER auto_increment;
ALTER TABLE softwares ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE softwares DROP DEVICEID;

ALTER TABLE monitors change ID ID INTEGER;
ALTER TABLE monitors DROP PRIMARY KEY;
ALTER TABLE monitors ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE monitors SET monitors.HARDWARE_ID= (SELECT ID FROM hardware WHERE monitors.DEVICEID = hardware.DEVICEID);
ALTER TABLE monitors ADD INDEX ID (ID);
ALTER TABLE monitors change ID ID INTEGER auto_increment;
ALTER TABLE monitors ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE monitors DROP DEVICEID;

ALTER TABLE modems change ID ID INTEGER;
ALTER TABLE modems DROP PRIMARY KEY;
ALTER TABLE modems ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE modems SET modems.HARDWARE_ID= (SELECT ID FROM hardware WHERE modems.DEVICEID = hardware.DEVICEID);
ALTER TABLE modems ADD INDEX ID (ID);
ALTER TABLE modems change ID ID INTEGER auto_increment;
ALTER TABLE modems ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE modems DROP DEVICEID;

ALTER TABLE inputs change ID ID INTEGER;
ALTER TABLE inputs DROP PRIMARY KEY;
ALTER TABLE inputs ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE inputs SET inputs.HARDWARE_ID= (SELECT ID FROM hardware WHERE inputs.DEVICEID = hardware.DEVICEID);
ALTER TABLE inputs ADD INDEX ID (ID);
ALTER TABLE inputs change ID ID INTEGER auto_increment;
ALTER TABLE inputs ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE inputs DROP DEVICEID;

ALTER TABLE printers change ID ID INTEGER;
ALTER TABLE printers DROP PRIMARY KEY;
ALTER TABLE printers ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE printers SET printers.HARDWARE_ID= (SELECT ID FROM hardware WHERE printers.DEVICEID = hardware.DEVICEID);
ALTER TABLE printers ADD INDEX ID (ID);
ALTER TABLE printers change ID ID INTEGER auto_increment;
ALTER TABLE printers ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE printers DROP DEVICEID;

ALTER TABLE videos change ID ID INTEGER;
ALTER TABLE videos DROP PRIMARY KEY;
ALTER TABLE videos ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE videos SET videos.HARDWARE_ID= (SELECT ID FROM hardware WHERE videos.DEVICEID = hardware.DEVICEID);
ALTER TABLE videos ADD INDEX ID (ID);
ALTER TABLE videos change ID ID INTEGER auto_increment;
ALTER TABLE videos ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE videos DROP DEVICEID;

ALTER TABLE sounds change ID ID INTEGER;
ALTER TABLE sounds DROP PRIMARY KEY;
ALTER TABLE sounds ADD COLUMN HARDWARE_ID integer not NULL FIRST;
UPDATE sounds SET sounds.HARDWARE_ID= (SELECT ID FROM hardware WHERE sounds.DEVICEID = hardware.DEVICEID);
ALTER TABLE sounds ADD INDEX ID (ID);
ALTER TABLE sounds change ID ID INTEGER auto_increment;
ALTER TABLE sounds ADD PRIMARY KEY(HARDWARE_ID,ID);
ALTER TABLE sounds DROP DEVICEID;

DROP TABLE IF EXISTS tag;
TRUNCATE TABLE locks;

ALTER TABLE softwares CHANGE NAME NAME VARCHAR(255) default NULL;
ALTER TABLE locks DROP DEVICEID;
ALTER TABLE locks ADD HARDWARE_ID INTEGER NOT NULL PRIMARY KEY FIRST;
ALTER TABLE locks ADD INDEX SINCE (SINCE);

DROP TABLE IF EXISTS dico_cat;
ALTER TABLE accesslog ADD INDEX USERID(USERID);
ALTER TABLE download_enable ADD INDEX FILEID(FILEID);
ALTER TABLE hardware ADD INDEX USERID(USERID);
ALTER TABLE hardware ADD INDEX WORKGROUP(WORKGROUP);
ALTER TABLE hardware ADD INDEX OSNAME(OSNAME);
ALTER TABLE hardware ADD INDEX MEMORY(MEMORY);
ALTER TABLE networks ADD INDEX IPADDRESS(IPADDRESS);
ALTER TABLE networks ADD INDEX IPGATEWAY(IPGATEWAY);
ALTER TABLE softwares ADD INDEX `VERSION`(`VERSION`);
ALTER TABLE subnet ADD INDEX ID(ID);
ALTER TABLE hardware CHANGE QUALITY QUALITY DECIMAL(7,4) default NULL;

DELETE FROM config WHERE name='GUI_VERSION';
DELETE FROM config WHERE name='IP_MIN_QUALITY';

INSERT INTO config VALUES ('FREQUENCY', 0, '', 'Specify the frequency (days) of inventories. (0: inventory at each login. -1: no inventory)');
INSERT INTO config VALUES ('PROLOG_FREQ', 24, '', 'Specify the frequency (hours) of prolog, on agents');
INSERT INTO config VALUES ('IPDISCOVER', 2, '', 'Max number of computers per gateway retrieving IP on the network');
INSERT INTO config VALUES ('INVENTORY_DIFF', 1, '', 'Activate/Deactivate inventory incremental writing');
INSERT INTO config VALUES ('IPDISCOVER_LATENCY', 100, '', 'Default latency between two arp requests');
INSERT INTO config VALUES ('INVENTORY_TRANSACTION', 1, '', 'Enable/disable db commit at each inventory section');
INSERT INTO config VALUES ('REGISTRY', 0, '', 'Activates or not the registry query function');
INSERT INTO config VALUES ('IPDISCOVER_MAX_ALIVE', 7, '','Max number of days before an Ip Discover computer is replaced');
INSERT INTO config VALUES ('DEPLOY', 1, '', 'Activates or not the automatic deployment option');
INSERT INTO config VALUES ('UPDATE', 0, '', 'Activates or not the update feature');
INSERT INTO config VALUES ('TRACE_DELETED', 0, '', 'Trace deleted/duplicated computers (Activated by GLPI)');
INSERT INTO config VALUES ('LOGLEVEL', 0, '', 'ocs engine loglevel');
INSERT INTO config VALUES ('AUTO_DUPLICATE_LVL', 7, '', 'Duplicates bitmap');
INSERT INTO config VALUES ('DOWNLOAD', 0, '', 'Activate softwares auto deployment feature');
INSERT INTO config VALUES ('DOWNLOAD_CYCLE_LATENCY', 60, '', 'Time between two cycles (seconds)');
INSERT INTO config VALUES ('DOWNLOAD_PERIOD_LENGTH', 10, '', 'Number of cycles in a period');
INSERT INTO config VALUES ('DOWNLOAD_FRAG_LATENCY', 10, '', 'Time between two downloads (seconds)');
INSERT INTO config VALUES ('DOWNLOAD_PERIOD_LATENCY', 0, '', 'Time between two periods (seconds)');
INSERT INTO config VALUES ('DOWNLOAD_TIMEOUT', 30, '', 'Validity of a package (in days)');
INSERT INTO config VALUES ('LOCAL_SERVER', 0, 'localhost', 'Server address used for local import');
INSERT INTO config VALUES ('LOCAL_PORT', 80, '', 'Server port used for local import');
INSERT INTO blacklist_serials(SERIAL) VALUES ('N/A'),('(null string)'),('INVALID'),('SYS-1234567890'),('SYS-9876543210'),('SN-12345'),('SN-1234567890'),('1111111111'),('1111111'),('1'),('0123456789'),('12345'),('123456'),('1234567'),('12345678'),('123456789'),('1234567890'),('123456789000'),('12345678901234567'),('0000000000'),('000000000'),('00000000'),('0000000'),('000000'),('NNNNNNN'),('xxxxxxxxxxx'),('EVAL'),('IATPASS'),('none'),('To Be Filled By O.E.M.'),('Tulip Computers'),('Serial Number xxxxxx'),('SN-123456fvgv3i0b8o5n6n7k'),('');
INSERT INTO blacklist_macaddresses(MACADDRESS) VALUES ('00:00:00:00:00:00'),('FF:FF:FF:FF:FF:FF'),('44:45:53:54:00:00'),('44:45:53:54:00:01'),('00:01:02:7D:9B:1C'),('00:08:A1:46:06:35'),('00:08:A1:66:E2:1A'),('00:09:DD:10:37:68'),('00:0F:EA:9A:E2:F0'),('00:10:5A:72:71:F3'),('00:11:11:85:08:8B'),('10:11:11:11:11:11'),('44:45:53:54:61:6F'),('');

INSERT INTO operators VALUES ('admin','admin','admin','admin',1, 'Default administrator account');

GRANT ALL PRIVILEGES ON ocsweb.* TO ocs IDENTIFIED BY 'ocs';
GRANT ALL PRIVILEGES ON ocsweb.* TO ocs@localhost IDENTIFIED BY 'ocs';

INSERT INTO config VALUES ('GUI_VERSION', 0, '5002', 'Version of the installed GUI and database');

CREATE TABLE download_servers (
  HARDWARE_ID int(11) NOT NULL,
  URL varchar(250) collate latin1_general_ci NOT NULL,
  ADD_PORT int(11) NOT NULL,
  ADD_REP varchar(250) collate latin1_general_ci NOT NULL,
  GROUP_ID int(11) NOT NULL,
  PRIMARY KEY  (HARDWARE_ID)
) ENGINE=MyISAM;

CREATE TABLE download_affect_rules (
  ID int(11) NOT NULL auto_increment,
  RULE int(11) NOT NULL,
  PRIORITY int(11) NOT NULL,
  CFIELD varchar(20) collate latin1_general_ci NOT NULL,
  OP varchar(20) collate latin1_general_ci NOT NULL,
  COMPTO varchar(20) collate latin1_general_ci NOT NULL,
  SERV_VALUE varchar(20) collate latin1_general_ci default NULL,
  RULE_NAME varchar(200) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (ID)
) ENGINE=MyISAM;


insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('DOWNLOAD_SERVER_URI','','$IP$/local','Server url used for group of server');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('DOWNLOAD_SERVER_DOCROOT','','d:\\\\tele_ocs','Server directory used for group of server');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('LOCK_REUSE_TIME',600,'','Validity of a computer\'s lock');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('INVENTORY_DIFF',1,'','Configure engine to update inventory regarding to CHECKSUM agent value (lower DB backend load)');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('INVENTORY_TRANSACTION',1,'','Make engine consider an inventory as a transaction (lower concurency, better disk usage)');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('INVENTORY_WRITE_DIFF',0,'','Configure engine to make a differential update of inventory sections (row level). Lower DB backend load, higher frontend load');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('INVENTORY_CACHE_ENABLED',1,'','Enable some stuff to improve DB queries, especially for GUI multicriteria searching system');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('DOWNLOAD_GROUPS_TRACE_EVENTS',1,'','Specify if you want to track packages affected to a group on computer\'s level');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('ENABLE_GROUPS',1,'','Enable the computer\'s groups feature');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GROUPS_CACHE_OFFSET',43200,'','Random number computed in the defined range. Designed to avoid computing many groups in the same process');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GROUPS_CACHE_REVALIDATE',43200,'','Specify the validity of computer\'s groups (default: compute it once a day - see offset)');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('IPDISCOVER_BETTER_THRESHOLD',1,'','Specify the minimal difference to replace an ipdiscover agent');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('IPDISCOVER_NO_POSTPONE',0,'','Disable the time before a first election (not recommended)');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('IPDISCOVER_USE_GROUPS',1,'','Enable groups for ipdiscover (for example, you might want to prevent some groups');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GENERATE_OCS_FILES',0,'','Use with ocsinventory-local, enable the multi entities feature');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('OCS_FILES_FORMAT','','OCS','Generate either compressed file or clear XML text');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('OCS_FILES_OVERWRITE',0,'','Specify if you want to keep trace of all inventory between to synchronisation with the higher level server');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('OCS_FILES_PATH','','/tmp','Path to ocs files directory (must be writeable)');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('PROLOG_FILTER_ON',0,'','Enable prolog filter stack');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('INVENTORY_FILTER_ENABLED',0,'','Enable core filter system to modify some things "on the fly"');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('INVENTORY_FILTER_FLOOD_IP',0,'','Enable inventory flooding filter. A dedicated ipaddress ia allowed to send a new computer only once in this period');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('INVENTORY_FILTER_FLOOD_IP_CACHE_TIME',300,'','Period definition for INVENTORY_FILTER_FLOOD_IP');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('INVENTORY_FILTER_ON',0,'','Enable inventory filter stack');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GUI_REPORT_RAM_MAX',512,'','Filter on RAM for console page');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GUI_REPORT_RAM_MINI',128,'','Filter on RAM for console page');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GUI_REPORT_NOT_VIEW',3,'','Filter on DAY for console page');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GUI_REPORT_PROC_MINI',1000,'','Filter on Hard Drive for console page');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GUI_REPORT_DD_MAX',4000,'','Filter on Hard Drive for console page');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GUI_REPORT_PROC_MAX',3000,'','Filter on PROCESSOR for console page');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GUI_REPORT_DD_MINI',500,'','Filter on PROCESSOR for console page');
insert into config (NAME,IVALUE,TVALUE,COMMENTS) values ('GUI_REPORT_AGIN_MACH',30,'','Filter on lastdate for console page');


ALTER TABLE download_enable ADD SERVER_ID INT(11);
ALTER TABLE download_enable ADD GROUP_ID INT(11);
ALTER TABLE groups ADD REVALIDATE_FROM INT(11);

CREATE TABLE prolog_conntrack (
  ID int(11) NOT NULL auto_increment,
  DEVICEID varchar(255) default NULL,
  TIMESTAMP int(11) default NULL,
  PID int(11) default NULL,
  KEY ID (ID),
  KEY DEVICEID (DEVICEID)
) ENGINE=MEMORY; 
