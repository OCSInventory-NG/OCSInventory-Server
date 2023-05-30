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
package Apache::Ocsinventory::Server::Inventory::Export;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /_generate_ocs_file _generate_ocs_file_snmp/;

use Apache::Ocsinventory::Server::System qw / :server /;

sub _generate_ocs_file{
  return if !$ENV{'OCS_OPT_GENERATE_OCS_FILES'};
  my $ocs_path = $ENV{'OCS_OPT_OCS_FILES_PATH'};
  my $ocs_file_name = $Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'};
  my $ocs_file = $ocs_path.'/'.$ocs_file_name.'.ocs';
  my $format;
  my $v=1;
  $format = 'ocs' unless $format = $ENV{'OCS_OPT_OCS_FILES_FORMAT'};
  
  if(!$ENV{'OCS_OPT_OCS_FILES_OVERWRITE'}){
    while(-e $ocs_file){
      $ocs_file=~s/(.+-\d{4}(-\d{2}){5})(?:-\d+)?\.ocs/$1-$v.ocs/;
      $v++;
    }
  }
  
  if( !open FILE, ">$ocs_file" ){
    &_log(520,'postinventory',"$ocs_file: $!") if $ENV{'OCS_OPT_LOGLEVEL'};
  }
  else{
    if($format=~/^ocs$/i){
      binmode FILE ;
      print FILE ${$Apache::Ocsinventory::CURRENT_CONTEXT{'RAW_DATA'}};
    }
    elsif($format=~/^xml$/i){
      print FILE ${$Apache::Ocsinventory::CURRENT_CONTEXT{'DATA'}};
    }
    else{
      &_log(521,'postinventory','wrong file format') if $ENV{'OCS_OPT_LOGLEVEL'};
    }
    close(FILE);
  }
  return;
}

sub _generate_ocs_file_snmp{
  return if !$ENV{'OCS_OPT_GENERATE_OCS_FILES_SNMP'};
  my $ocs_path = $ENV{'OCS_OPT_OCS_FILES_PATH'};
  my $ocs_file_name = $Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'};
  my $ocs_file = $ocs_path.'/'.$ocs_file_name.'-SNMP.ocs';
  my $format;
  my $v=1;
  $format = 'ocs' unless $format = $ENV{'OCS_OPT_OCS_FILES_FORMAT'};
  
  if(!$ENV{'OCS_OPT_OCS_FILES_OVERWRITE'}){
    while(-e $ocs_file){
      $ocs_file=~s/(.+-\d{4}(-\d{2}){5})(?:-\d+)?\.ocs/$1-$v.ocs/;
      $v++;
    }
  }
  
  if( !open FILE, ">$ocs_file" ){
    &_log(520,'postinventory',"$ocs_file: $!") if $ENV{'OCS_OPT_LOGLEVEL'};
  }
  else{
    if($format=~/^ocs$/i){
      binmode FILE ;
      print FILE ${$Apache::Ocsinventory::CURRENT_CONTEXT{'RAW_DATA'}};
    }
    elsif($format=~/^xml$/i){
      print FILE ${$Apache::Ocsinventory::CURRENT_CONTEXT{'DATA'}};
    }
    else{
      &_log(521,'postinventory','wrong file format') if $ENV{'OCS_OPT_LOGLEVEL'};
    }
    close(FILE);
  }
  return;
}
1;
