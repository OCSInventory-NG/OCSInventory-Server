###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Inventory::Update;

use Apache::Ocsinventory::Server::Inventory::Update::Hardware;
use Apache::Ocsinventory::Server::Inventory::Update::AccountInfos;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / _update_inventory /;

use Apache::Ocsinventory::Server::System qw / :server /;
use Apache::Ocsinventory::Server::Inventory::Data;

sub _update_inventory{
  my ($sectionsMeta, $sectionsList ) = @_;
  
  my $section;
   
  # Call special sections update
  if(&_hardware() or &_accountinfo()){
    return 1;
  }
  
  # Call the _update_inventory_section for each section
  for $section (@{$sectionsList}){
    if(_update_inventory_section($section, $sectionsMeta->{$section})){
      return 1;
    }
  }
}

sub _update_inventory_section{
  my ($section, $sectionMeta) = @_;

  my @bind_values;
  my $deviceId = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
  my $result = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
	
  # The computer exists. 
  # We check if this section has changed since the last inventory (only if activated)
  # We delete the previous entries
  if($Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'}){
    if($ENV{'OCS_OPT_INVENTORY_DIFF'}){
      if( _has_changed($section) ){
        $sectionMeta->{hasChanged} = 1;
      }
      else{
        return 0;
      }
    }
    if( $sectionMeta->{delOnReplace} && !($sectionMeta->{writeDiff} && $ENV{'OCS_OPT_INVENTORY_WRITE_DIFF'}) ){
      if(!$dbh->do("DELETE FROM $section WHERE HARDWARE_ID=?", {}, $deviceId)){
        return(1);
      }
    }
  }

  # DEL AND REPLACE, or detect diff on elements of the section (more load on frontends, less on DB backend)
  if($Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'} && $ENV{'OCS_OPT_INVENTORY_WRITE_DIFF'} && $sectionMeta->{writeDiff}){
    my @fromDb;
    my @fromXml;
    my $refXml = $result->{CONTENT}->{uc $section};
    my $sth = $dbh->prepare($sectionMeta->{sql_select_string});
    $sth->execute($deviceId) or return 1;
    while(my @row = $sth->fetchrow_array){
      push @fromDb, [ @row ];
    }	  
    for my $line (@$refXml){
      &_get_bind_values($line, $sectionMeta, \@bind_values);
      push @fromXml, [ @bind_values ];
      @bind_values = ();
    }
    #TODO: Sorting XML entries, to compare more quickly with DB elements
    for my $l_xml (@fromXml){
      my $found = 0;
      for my $i_db (0..$#fromDb){
        next unless $fromDb[$i_db];
        my @line = @{$fromDb[$i_db]};
        my $dbdebug = join ';', @$l_xml;
        my $xmldebug = join ';', @line[2..$#line];
        if( sprintf("%s", @$l_xml) eq sprintf("%s", @line[2..$#line]) ){
          $found = 1;
          # The value has been found, we have to delete it from the db list
          # (elements remaining will be deleted)
          delete $fromDb[$i_db];
          last;
        }
      }
      if(!$found){
        $dbh->do( $sectionMeta->{sql_insert_string}, {}, $deviceId, @$l_xml );
      }
    }
    # Now we have to delete from DB elements that still remain in fromDb
    for(@fromDb){
      next if !defined (${$_}[0]);
      $dbh->do( $sectionMeta->{sql_delete_string}, {}, $deviceId, ${$_}[0]);
    }
  }
  else{
    # Processing values	
    my $sth = $dbh->prepare( $sectionMeta->{sql_insert_string} );
    # Multi lines (forceArray)
    my $refXml = $result->{CONTENT}->{uc $section};
    
    if($sectionMeta->{multi}){
      for my $line (@$refXml){
        &_get_bind_values($line, $sectionMeta, \@bind_values);
        if(!$sth->execute($deviceId, @bind_values)){
          return(1);
        }
        @bind_values = ();
      }
    }
    # One line (hash)
    else{
      &_get_bind_values($refXml, $sectionMeta, \@bind_values);
      if( !$sth->execute($deviceId, @bind_values) ){
        return(1);
      }
    }
  }
  $dbh->commit unless $ENV{'OCS_OPT_INVENTORY_TRANSACTION'};
  0;
}
1;
