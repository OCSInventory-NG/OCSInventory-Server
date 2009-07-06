###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Inventory::Export;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / _generate_ocs_file /;

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
1;
