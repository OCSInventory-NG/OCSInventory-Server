package Apache::Ocsinventory::Interface::Updates;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Server::System;
use Apache::Ocsinventory::Interface::Database;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw //;

sub delete_computers_by_id {
  my $computerIds = shift ;
  my $dbh = &get_dbh_write ;

  for my $hardwareId (@{$computerIds}){
    # We lock the computer to avoid race condition
    next if &_lock($hardwareId, $dbh) ;

    for my $section ( keys %DATA_MAP ){
      my $hardwareIdField = get_table_pk($section) ;

      # delOnReplace is used here even if the section is not "auto"
      # "auto" is only useful for the import phases
      next if !$DATA_MAP{ $section }->{delOnReplace} ;
      do_sql("DELETE FROM $section WHERE $hardwareIdField=$hardwareId") ;
    }
    &_unlock($hardwareId, $dbh) ;
  }
  return 'OK' ;
}
1;
