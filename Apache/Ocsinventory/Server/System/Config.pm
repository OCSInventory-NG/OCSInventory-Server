###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://ocsinventory.sourceforge.net
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
  %CONFIG
  getSettings
/;

our %CONFIG = (
  LOGPATH => { type => 'TVALUE' },
  FREQUENCY => { type => 'IVALUE' },
  PROLOG_FREQ => { type => 'IVALUE' },
  DEPLOY => { type => 'IVALUE' },
  TRACE_DELETED => { type => 'IVALUE' },
  AUTO_DUPLICATE_LVL => { type => 'IVALUE' },
  LOGLEVEL => { type => 'IVALUE' },
  INVENTORY_DIFF => { type => 'IVALUE' },
  INVENTORY_WRITE_DIFF => { type => 'IVALUE' },
  INVENTORY_TRANSACTION => { type => 'IVALUE' },
  INVENTORY_CACHE_ENABLED => { type => 'IVALUE' },
  INVENTORY_CACHE_REVALIDATE => { type => 'IVALUE' },
  INVENTORY_FILTER => { type => 'IVALUE' },
  PROXY_REVALIDATE_DELAY => { type => 'IVALUE' },
  LOCK_REUSE_TIME => { type => 'IVALUE' },
  ENABLE_GROUPS => { type => 'IVALUE' },
  GROUPS_CACHE_REVALIDATE => { type => 'IVALUE' },
  GROUPS_CACHE_OFFSET => { type => 'IVALUE' },
  DBI_PRINT_ERROR => { type => 'IVALUE' },
  GENERATE_OCS_FILES => { type => 'IVALUE' },
  OCS_FILES_OVERWRITE => { type => 'IVALUE' },
  OCS_FILES_PATH => { type => 'TVALUE' },
  OCS_FILES_FORMAT => { type => 'TVALUE' },
  SECURITY_LEVEL => { type => 'IVALUE' },
  IPDISCOVER => { type => 'IVALUE' },
  IPDISCOVER_MAX_ALIVE => { type => 'IVALUE' },
  IPDISCOVER_BETTER_THRESHOLD => { type => 'IVALUE' },
  IPDISCOVER_LATENCY => { type => 'IVALUE' },
  IPDISCOVER_USE_GROUPS => { type => 'IVALUE' },
  IPDISCOVER_NO_POSTPONE => { type => 'IVALUE' },
  REGISTRY => { type => 'IVALUE' },
  UPDATE => { type => 'IVALUE' },
  DOWNLOAD => { type => 'IVALUE' },
  DOWNLOAD_FRAG_LATENCY => { type => 'IVALUE' },
  DOWNLOAD_CYCLE_LATENCY => { type => 'IVALUE' },
  DOWNLOAD_PERIOD_LATENCY => { type => 'IVALUE' },
  DOWNLOAD_TIMEOUT => { type => 'IVALUE' },
  DOWNLOAD_GROUPS_TRACE_EVENTS => { type => 'IVALUE' },
  PROLOG_FILTER_ON => { type => 'IVALUE' },
  INVENTORY_FILTER_ON => { type => 'IVALUE' },
  INVENTORY_FILTER_FLOOD_IP => { type => 'IVALUE' },
  INVENTORY_FILTER_FLOOD_IP_CACHE_TIME => { type => 'IVALUE' },
);

sub getSettings{
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
1;
