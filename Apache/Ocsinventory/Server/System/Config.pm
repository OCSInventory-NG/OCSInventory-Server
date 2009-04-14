###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::System::Config;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  get_settings
  check_config
  %CONFIG
  CRITICAL
  IMPORTANT
  CANSKIP
  DEPRECATED
/;

use constant CRITICAL  => 0;
use constant IMPORTANT => 1;
use constant CANSKIP   => 2;
use constant DEPRECATED=> 3;

our %CONFIG = (
  LOGPATH => { 
    type => 'TVALUE',
    default => '/var/log/ocsinventory-server',
    unit => 'NA',
    description => 'Path to log directory (must be writeable)',
    level => CRITICAL,
    filter => qr '^(.+)$'
  },
  FREQUENCY => {
    type => 'IVALUE',
    default => 0,
    unit => 'day',
    description => 'Specify the validity of inventory data',
    level => CRITICAL,
    filter => qr '^(-1|\d+)$'
  },
  PROLOG_FREQ => { 
    type => 'IVALUE',
    default => 12,
    unit => 'hour',
    description => 'Specify agent\'s prolog frequency',
    level => CRITICAL,
    filter => qr '^([1-9]\d*)$'
  },
  DEPLOY => { 
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Enable ocs engine to deliver agent\'s files (deprecated)',
    level => DEPRECATED,
    filter => qr '^(1|0)$'
  },
  TRACE_DELETED => { 
    type => 'IVALUE', 
    default => 0,
    unit => 'NA',
    description => 'Enable the history tracking system (useful for external data synchronisation',
    level => CANSKIP,
    filter => qr '^(1|0)$'
  },
  AUTO_DUPLICATE_LVL => { 
    type => 'IVALUE',
    default => 15,
    unit => 'NA',
    description => 'Configure the duplicates detection system',
    level => IMPORTANT,
    filter => qr '^(\d+)$'
  },
  LOGLEVEL => { 
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Enable engine logs (see LOGPATH setting)',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  INVENTORY_DIFF => { 
    type => 'IVALUE',
    default => 1,
    unit => 'NA',
    description => 'Configure engine to update inventory regarding to CHECKSUM agent value (lower DB backend load)',
    level => CRITICAL,
    filter => qr '^(1|0)$'
  },
  INVENTORY_WRITE_DIFF => { 
    type => 'IVALUE',
    default => 1,
    unit => 'NA',
    description => 'Configure engine to make a differential update of inventory sections (row level). Lower DB backend load, higher frontend load',
    level => CRITICAL,
    filter => qr '^(1|0)$'
  },
  INVENTORY_TRANSACTION => { 
    type => 'IVALUE',
    default => 1,
    unit => 'NA',
    description => 'Make engine consider an inventory as a transaction (lower concurency, better disk usage)',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  INVENTORY_CACHE_ENABLED => { 
    type => 'IVALUE',
    default => 1,
    unit => 'NA',
    description => 'Enable some stuff to improve DB queries, especially for GUI multicriteria searching system',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  INVENTORY_CACHE_REVALIDATE => { 
    type => 'IVALUE',
    default => 7,
    unit => 'day',
    description => 'Specify when the engine will reset the inventory cache structures',
    level => CRITICAL,
    filter => qr '^(\d+)$'
  },
  INVENTORY_CACHE_KEEP => { 
    type => 'IVALUE',
    default => 1,
    unit => 'NA',
    description => 'Specify if ou want to keep trace of every elements encountered in the db life',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  INVENTORY_FILTER_ENABLED => { 
    type => 'IVALUE',  
    default => 0,
    unit => 'NA',
    description => 'Enable core filter system to modify some things "on the fly"',
    level => CANSKIP,
    filter => qr '^(1|0)$'
  },
  INVENTORY_SESSION_ONLY => { 
    type => 'IVALUE',  
    default => 0,
    unit => 'NA',
    description => 'Accept an inventory only if there is a prolog before',
    level => CANSKIP,
    filter => qr '^(1|0)$'
  },
  PROXY_REVALIDATE_DELAY => { 
    type => 'IVALUE',  
    default => 3600,
    unit => '',
    description => 'Set the proxy cache validity in http headers when sending a file',
    level => DEPRECATED,
    filter => qr '^(\d+)$'
  },
  LOCK_REUSE_TIME => { 
    type => 'IVALUE',  
    default => 600,
    unit => 'second',
    description => 'Validity of a computer\'s lock',
    level => CANSKIP,
    filter => qr '^(\d+)$'
  },
  ENABLE_GROUPS => { 
    type => 'IVALUE',
    default => 1,
    unit => 'NA',
    description => 'Enable the computer\s groups feature',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  GROUPS_CACHE_REVALIDATE => {
    type => 'IVALUE',
    default => 43200,
    unit => 'second',
    description => 'Specify the validity of computer\'s groups (default: compute it once a day - see offset)',
    level => CRITICAL,
    filter => qr '^(\d+)$'
  },
  GROUPS_CACHE_OFFSET => { 
    type => 'IVALUE',
    default => 43200,
    unit => 'second',
    description => 'Random number computed in the defined range. Designed to avoid computing many groups in the same process',
    level => CRITICAL,
    filter => qr '^(\d+)$'
  },
  GENERATE_OCS_FILES => { 
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Use with ocsinventory-injector, enable the multi entities feature',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  OCS_FILES_OVERWRITE => { 
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Specify if you want to keep trace of all inventory between to synchronisation with the higher level server',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  OCS_FILES_PATH => { 
    type => 'TVALUE',
    default => '/tmp',
    unit => 'NA',
    description => 'Path to ocs files directory (must be writeable)',
    level => IMPORTANT,
    filter => qr '^(.+)$'
  },
  OCS_FILES_FORMAT => { 
    type => 'TVALUE',  
    default => 'OCS',
    unit => 'NA',
    description => 'Generate either compressed file or clear XML text',
    level => IMPORTANT,
    filter => qr '^(OCS|XML)$'
  },
  SECURITY_LEVEL => { 
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Futur security improvements',
    level => CANSKIP,
    filter => qr '^(\d+)$'
  },
  IPDISCOVER => { 
    type => 'IVALUE',
    default => 2,
    unit => 'NA',
    description => 'Specify how much agent per LAN will discovered connected peripherals (0 to disable)',
    level => CRITICAL,
    filter => qr '^(\d+)$'
  },
  IPDISCOVER_MAX_ALIVE => {
    type => 'IVALUE',
    default => 14,
    unit => 'day',
    description => 'Specify when to remove a computer when it has not come until this period',
    level => CANSKIP,
    filter => qr '^([1-9]\d*)$'
  },
  IPDISCOVER_BETTER_THRESHOLD => {
    type => 'TVALUE',
    default => 1,
    unit => 'day',
    description => 'Specify the minimal difference to replace an ipdiscover agent',
    level => IMPORTANT,
    filter => qr '^(\d+(?:,\d+)?)$'
  },
  IPDISCOVER_LATENCY => {
    type => 'IVALUE',
    default => 100,
    unit => 'millisecond',
    description => 'Time between 2 arp requests (mini: 10 ms)',
    level => CRITICAL,
    filter => qr '^([1-9]\d+)$'
  },
  IPDISCOVER_USE_GROUPS => {
    type => 'IVALUE',
    default => 1,
    unit => 'NA',
    description => 'Enable groups for ipdiscover (for example, you might want to prevent some groups to be ipdiscover agents)',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  IPDISCOVER_NO_POSTPONE => {
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Disable the time before a first election (not recommended)',
    level => CANSKIP,
    filter => qr '^(1|0)$'
  },
  REGISTRY => {
    type => 'IVALUE',
    default => 1,
    unit => 'NA',
    description => 'Enable the registry capacity',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  UPDATE => {
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Deprecated',
    level => DEPRECATED,
    filter => qr '^(1|0)$'
  },
  DOWNLOAD => {
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Enable the softwares deployment capacity (bandwidth control)',
    level => CRITICAL,
    filter => qr '^(1|0)$'
  },
  DOWNLOAD_FRAG_LATENCY => {
    type => 'IVALUE',
    default => 60,
    unit => 'second',
    description => 'Time between two fragment downloads (bandwidth control)',
    level => CRITICAL,
    filter => qr '^([1-9]\d*)$'
  },
  DOWNLOAD_CYCLE_LATENCY => {
    type => 'IVALUE',
    default => 60,
    unit => 'second',
    description => 'Time between two download cycles (bandwidth control)',
    level => CRITICAL,
    filter => qr '^(\d+)$'
  },
  DOWNLOAD_PERIOD_LATENCY => {
    type => 'IVALUE',
    default => 60,
    unit => 'second',
    description => 'Time between two download periods (bandwidth control)',
    level => CRITICAL,
    filter => qr '^(\d+)$'
  },
  DOWNLOAD_TIMEOUT => {
    type => 'IVALUE',
    default => 7,
    unit => 'day',
    description => 'Agents will send ERR_TIMEOUT event and clean the package it is older than this setting',
    level => IMPORTANT,
    filter => qr '^([1-9]\d*)$'
  },
  DOWNLOAD_GROUPS_TRACE_EVENTS => {
    type => 'IVALUE',
    default => 1,
    unit => 'NA',
    description => 'Specify if you want to track packages affected to a group on computer\'s level',
    level => IMPORTANT,
    filter => qr '^(1|0)$'
  },
  DOWNLOAD_PERIOD_LENGTH => {
    type => 'IVALUE',
    default => 10,
    unit => 'cycle',
    description => 'Specify the number of cycle within a period',
    level => IMPORTANT,
    filter => qr '^([1-9]\d*)$'
  },
  PROLOG_FILTER_ON => {
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Enable prolog filter stack',
    level => CANSKIP,
    filter => qr '^(1|0)$'
  },
  INVENTORY_FILTER_ON => {
    type => 'IVALUE',  
    default => 0,
    unit => 'NA',
    description => 'Enable inventory filter stack',
    level => CANSKIP,
    filter => qr '^(1|0)$'
  },
  INVENTORY_FILTER_FLOOD_IP => {
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Enable inventory flooding filter. A dedicated ipaddress ia allowed to send a new computer only once in this period',
    level => CANSKIP,
    filter => qr '^(1|0)$'
  },
  INVENTORY_FILTER_FLOOD_IP_CACHE_TIME => {
    type => 'IVALUE',
    default => 300,
    unit => 'second',
    description => 'Period definition for INVENTORY_FILTER_FLOOD_IP',
    level => CANSKIP,
    filter => qr '^(\d+)$'
  },
  SESSION_VALIDITY_TIME => {
    type => 'IVALUE',
    default => 3600,
    unit => 'second',
    description => 'Set the validity of a session (prolog => end)',
    level => CANSKIP,
    filter => qr '^(\d+)$'
  },
  SESSION_CLEAN_TIME => {
    type => 'IVALUE',
    default => 86400,
    unit => 'second',
    description => 'Clean old sessions every <> seconds',
    level => CANSKIP,
    filter => qr '^(\d+)$'
  },
  COMPRESS_TRY_OTHERS => {
    type => 'IVALUE',
    default => 0,
    unit => 'NA',
    description => 'Configure engine to try other compress algorythm than raw zlib',
    level => CANSKIP,
    filter => qr '^(1|0)$'
  }
);

sub get_settings{
  my $realName = shift;
  if($realName){
    my @ret;
    push @ret, "OCS_OPT_$_" for keys %CONFIG;
    return sort @ret;
  }
  else{
    return sort keys %CONFIG;
  }
}

sub check_config{
  my $verbose = shift;
  for my $name ( keys( %CONFIG ) ){
    my $truename = 'OCS_OPT_'.$name;
    if( !defined($ENV{$truename}) ){
      print STDERR "ocsinventory-server: Bad setting. `$name` is not set. Default: `$CONFIG{$name}->{default}`\n";
      $ENV{$truename} = $CONFIG{$name}->{default};
    }
    elsif( $ENV{$truename}  !~ $CONFIG{$name}->{filter} ){
      print STDERR "ocsinventory-server: Bad setting. `$name` is set to `$ENV{$truename}`. Default: `$CONFIG{$name}->{default}`\n";
      $ENV{$truename} = $CONFIG{$name}->{default};
    }
    else{
      print "ocsinventory-server: Parameter `$truename` is ok\n" if $verbose;
    }
  }
  return undef;
}
1;
