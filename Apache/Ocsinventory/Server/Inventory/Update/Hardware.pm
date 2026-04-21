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
package Apache::Ocsinventory::Server::Inventory::Update::Hardware;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / _hardware /; 

use Apache::Ocsinventory::Server::Inventory::Cache;
use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Server::System qw / :server /;

sub _hardware{
  my $sectionMeta = shift;
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  my $base = $result->{CONTENT}->{HARDWARE};
  my $ua = $Apache::Ocsinventory::CURRENT_CONTEXT{'USER_AGENT'};
  my $deviceId = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  # We replace all data but quality and fidelity. The last come becomes the last date.
  my $userid = '';
  my @userid_value;
  if( $base->{USERID}!~/(system|localsystem)/i ) {
    $userid = "USERID=?,";
    push @userid_value, $base->{USERID};
  }

  my $ipAddress = &_get_default_iface();

  my $checksum = _unsigned_int($base->{CHECKSUM}, CHECKSUM_MAX_VALUE);
  my $processors = _unsigned_int($base->{PROCESSORS}, 0);
  my $processorn = _unsigned_int($base->{PROCESSORN}, 0);
  my $memory = _unsigned_int($base->{MEMORY}, 0);
  my $swap = _unsigned_int($base->{SWAP}, 0);
  my $type = _unsigned_int($base->{TYPE}, 0);

  $dbh->do("UPDATE hardware SET USERAGENT=?,
	LASTDATE=".((defined($base->{LASTDATE})&&($base->{LASTDATE} ne "1970-01-01"))?"?":"NOW()").",
	LASTCOME=NOW(),
	CHECKSUM=(?|CHECKSUM|1),
	NAME=?,
	WORKGROUP=?,
	USERDOMAIN=?,
	OSNAME=?,
	OSVERSION=?,
	OSCOMMENTS=?,
	PROCESSORT=?,
	PROCESSORS=?,
	PROCESSORN=?,
	MEMORY=?,
	SWAP=?,
	IPADDR=?,
	DNS=?,
	DEFAULTGATEWAY=?,
	ETIME=NULL,
	$userid
	TYPE=?,
	DESCRIPTION=?,
	WINCOMPANY=?,
	WINOWNER=?,
	WINPRODID=?,
	WINPRODKEY=?,
	IPSRC=?,
	UUID=?,
	ARCH=?,
	CATEGORY_ID=?
	 WHERE ID=?", {},
    $ua,
    ((defined($base->{LASTDATE})&&($base->{LASTDATE} ne "1970-01-01"))?($base->{LASTDATE}):()),
    $checksum,
    $base->{NAME},
    $base->{WORKGROUP},
    $base->{USERDOMAIN},
    $base->{OSNAME},
    $base->{OSVERSION},
    $base->{OSCOMMENTS},
    $base->{PROCESSORT},
    $processors,
    $processorn,
    $memory,
    $swap,
    $ipAddress,
    $base->{DNS},
    $base->{DEFAULTGATEWAY},
    @userid_value,
    $type,
    $base->{DESCRIPTION},
    $base->{WINCOMPANY},
    $base->{WINOWNER},
    $base->{WINPRODID},
    $base->{WINPRODKEY},
    $Apache::Ocsinventory::CURRENT_CONTEXT{IPADDRESS},
    $base->{UUID},
    $base->{ARCH},
    $base->{CATEGORY_ID},
    $deviceId)
  or return(1);

  #We feed cache tables associated to hardware fields
  if ($ENV{OCS_OPT_INVENTORY_CACHE_ENABLED}) {
    my $cache_values =[];

    for (keys %{ $sectionMeta->{field_cached}} ) {
      #Feeding array for cache values
      $cache_values->[ $sectionMeta->{field_cached}->{$_} ] = $base->{$_};
    }
    &_cache( 'add', 'hardware', $sectionMeta, $cache_values );
  }

  $dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
  0;
}

sub _get_default_iface{
  return undef if !defined $Apache::Ocsinventory::CURRENT_CONTEXT{XML_ENTRY}->{CONTENT}->{NETWORKS};
  my $networks = $Apache::Ocsinventory::CURRENT_CONTEXT{XML_ENTRY}->{CONTENT}->{NETWORKS};
  for( @$networks ){
    if( $_->{IPADDRESS} eq $Apache::Ocsinventory::CURRENT_CONTEXT{IPADDRESS}){
      return $_->{IPADDRESS};
    }
  }
  return $Apache::Ocsinventory::CURRENT_CONTEXT{XML_ENTRY}->{CONTENT}->{HARDWARE}->{IPADDR};
}

sub _unsigned_int {
  my ($value, $default) = @_;

  return $default unless defined($value) && $value =~ /\A\d+\z/;
  return int($value);
}

1;
