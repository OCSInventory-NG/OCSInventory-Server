###############################################################################
## OCSINVENTORY-NG 
## Copyleft Guillaume PROTET 2011
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Support;

use strict;

BEGIN{
  if($ENV{'OCS_MODPERL_VERSION'} == 1){
    require Apache::Ocsinventory::Server::Modperl1;
    Apache::Ocsinventory::Server::Modperl1->import();
  }elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
    require Apache::Ocsinventory::Server::Modperl2;
    Apache::Ocsinventory::Server::Modperl2->import();
  }
}

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /_verify_certificate /;


sub _verify_certificate {

  my ($uid,$cert_verified,$support_log);

  if($ENV{'OCS_OPT_SUPPORT'}) {

    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};


    #Getting certificate from database
    my $select_uid=$dbh->prepare("SELECT TVALUE FROM config WHERE NAME='SUPPORT_UID'");
    $select_uid->execute;
    if (my $row = $select_uid->fetchrow_hashref) {
      $uid=  $row->{'TVALUE'};

      #Setting support certificate as valid 
      #TODO: verify if certificate has expired using SUPPORT_TIMESTAMP field 
      $cert_verified = 1;
    }

    #If ceritificate if verified, we write a special log and it will sent it in PROLOG 
    if ($cert_verified && $uid) {
      $support_log = "OCS Inventory NG support registration key is $uid . You can submit a case at https://support.ocsinventory-ng.com.";
    } else {  #Special log if no support
      $support_log = "No support registered for your installation. Check OCS Inventory NG support packages at http://www.ocsinventory-ng.com";
    } 
  }
  return $support_log;
}
1;
