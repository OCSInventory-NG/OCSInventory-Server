###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Inventory::Update::AccountInfos;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw/_get_account_fields  _accountinfo/;

use Apache::Ocsinventory::Server::System qw/ :server /;

sub _get_account_fields{
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $request = $dbh->prepare('SHOW COLUMNS FROM accountinfo');
  my @accountFields;
  
  $request->execute;
  while(my $row=$request->fetchrow_hashref){
    push @accountFields, $row->{'Field'} if($row->{'Field'} ne 'HARDWARE_ID');
  }
  return @accountFields;
}

sub _accountinfo{
  my $lost=shift; 
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  my $hardwareId = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'}; 
  
  # We have to look for the field's names because this table has a dynamic structure
  my ($row, $request, $accountkey, @accountFields);
  @accountFields = _get_account_fields();

  # The default behavior of the server is to ignore TAG changes from the
  # agent
  if(
  $ENV{OCS_OPT_ACCEPT_TAG_UPDATE_FROM_CLIENT}
  ||
  !$Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'}
  ||
  $lost
  ){
  # writing (if new id, but duplicate, it will be erased at the end of the execution)
    $dbh->do('INSERT INTO accountinfo(HARDWARE_ID) VALUES(?)', {}, $hardwareId);
# Now, we know what are the account info name fields
# We can insert the client's data. This data will be kept only one time, in the first inventory
    if( exists ($result->{CONTENT}->{ACCOUNTINFO}) ){
      for $accountkey (@accountFields){
        my $array = $result->{CONTENT}->{ACCOUNTINFO};
        for(@$array){
          if($_->{KEYNAME} eq $accountkey){
            if(!$dbh->do('UPDATE accountinfo SET '.$accountkey."=".$dbh->quote($_->{KEYVALUE}).' WHERE HARDWARE_ID='.$hardwareId)){
  	      return 1;
	    }
	  }
        }
      }
    }
    else{
      &_log(528,'accountinfos','missing') if $ENV{'OCS_OPT_LOGLEVEL'};
    }
  }
  if($lost){
    if(!$dbh->do('UPDATE accountinfo SET TAG = "LOST" WHERE HARDWARE_ID=?', {}, $hardwareId)){
      return(1);
    }
  }
	
  $dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
  0;
}
1;
