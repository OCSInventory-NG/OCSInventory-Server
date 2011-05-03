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

our $s;

#We get the main server's object (useful to get OCS configuration settings)
BEGIN{
  if($ENV{'OCS_MODPERL_VERSION'} == 1){
    require Apache::Ocsinventory::Server::Modperl1;
    Apache::Ocsinventory::Server::Modperl1->import();
    $s = Apache->server;
  }elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
    require Apache::Ocsinventory::Server::Modperl2;
    Apache::Ocsinventory::Server::Modperl2->import();
    $s = Apache2::ServerUtil->server;
  }
}

use Apache::Ocsinventory::Server::System qw /:server/;
use Apache::Ocsinventory;

use Crypt::OpenSSL::X509;

$Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'} = $s;


my ($dbh,$dbh_sl,$request,$row,$db_cert,$subject,$issuer,$email,$notafter,$cert_verified);

#Database connection;
if(!($dbh = &_database_connect( 'write' ))){
    &_writelog(505,'support','Database connection');
}

#if(!($dbh_sl = &_database_connect( 'read' ))){
#    &_writelog(505,'support','Database Slave connection');
    #return &_end(APACHE_SERVER_ERROR);
#}


#Getting certificate from database
my $select_cert=$dbh->prepare("SELECT FILE FROM ssl_store where DESCRIPTION='cert'");
$select_cert->execute();
if ( my $row = $select_cert->fetchrow_hashref) {
  $db_cert=  $row->{'FILE'};

  #Reading certificate
  my $cert = Crypt::OpenSSL::X509->new_from_string($db_cert);
  $subject = $cert->subject();
  $issuer= $cert->issuer();
  $email = $cert->email();
  $notafter = $cert->notAfter(); 

  #Verifiying if certificate has not expired
  $cert_verified = 1;
}

$select_cert->finish();
$dbh->disconnect;


#If ceritificate if verified, we write a special log and will send it in PROLOG 
if ($cert_verified) {
  &_writelog("You have subscribed OCS support :). You can submit a case at http://ocsinventory-ng.com/mantis. You will have to provide your customer ID and your email address $email"); 
  $Apache::Ocsinventory::OCS_SUPPORT_LOG = "You have subscribed OCS support :). You can submit a case at http://ocsinventory-ng.com/mantis. You will have to provide your customer ID and your email address $email"; 
} else {  #Special log if no support
  &_writelog("You don't  have subscribed support for Ocs inventory NG. You can do it connecting to http://ocsinventory-ng.com"); 
  $Apache::Ocsinventory::OCS_SUPPORT_LOG = "You don't  have subscribed support for Ocs inventory NG. You can do it by connecting to http://ocsinventory-ng.com"; 
} 

sub _writelog {
  our $LOG;
  my $message= shift;

  if(!$LOG){
    open LOG, '>>'.$ENV{'OCS_OPT_LOGPATH'}.'/activity.log' or die "Failed to open log file : $! ($ENV{'OCS_OPT_LOGPATH'})\n";
    # We don't want buffer, so we allways flush the handles
    select(LOG);
    $|=1;
    $LOG = \*LOG;
  }

  print LOG localtime().";$message\n";
}

1;
