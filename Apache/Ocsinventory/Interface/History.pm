###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface::History;

use Apache::Ocsinventory::Interface::Database;
use XML::Simple;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  get_history_events
  clear_history_events
/;

sub get_history_events {
  my $offset = shift;
  my @tmp;
  
  $offset = $offset * $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};
  
  my $sth = get_sth( "SELECT DATE,DELETED,EQUIVALENT FROM deleted_equiv ORDER BY DATE,DELETED LIMIT $offset, $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT}" );
    
  while( my $row = $sth->fetchrow_hashref() ){
    push @tmp, {
        'DELETED' => [ $row->{'DELETED'} ],
        'DATE' => [ $row->{'DATE'} ],
        'EQUIVALENT' => [ $row->{'EQUIVALENT'} ]
    }
  }
  $sth->finish();
  return XML::Simple::XMLout( {'EVENT' => \@tmp} , RootName => 'EVENTS' );
}

sub clear_history_events {
  my $offset = shift;
  my $ok = 0;

  my $sth = get_sth( "SELECT * FROM deleted_equiv ORDER BY DATE,DELETED LIMIT 0,$offset" );
  while( my $row = $sth->fetchrow_hashref() ) {
    if( defined $row->{'EQUIVALENT'} ){
      do_sql('DELETE FROM deleted_equiv WHERE DELETED=? AND DATE=? AND EQUIVALENT=?', $row->{'DELETED'}, $row->{'DATE'}, $row->{'EQUIVALENT'}) or die;
    }
    else{
      do_sql('DELETE FROM deleted_equiv WHERE DELETED=? AND DATE=? AND EQUIVALENT IS NULL', $row->{'DELETED'}, $row->{'DATE'}) or die;
    }
    $ok++;
  }
  return $ok;
}
