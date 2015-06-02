###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface::Config;

use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Internals;
use Apache::Ocsinventory::Server::System::Config;

use XML::Simple;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
/;

sub ocs_config_is_supported{
  my $setting = shift;
  return grep { $_ eq $setting } Apache::Ocsinventory::Server::System::Config::get_settings();
}

# Return a config value
sub ocs_config_read{
  my ($key, $legacy) = @_;
  
  unless( ocs_config_is_supported( $key ) ){
    return send_error( 'KEY_NOT_SUPPORTED' );    
  }
  
  my $sth = get_sth('SELECT IVALUE,TVALUE FROM config WHERE NAME=?', $key);
  unless($sth->rows){
    $sth->finish();
    return undef;  
  }
  my ($i,$t) = $sth->fetchrow_array();
  $sth->finish();
  if( $legacy ){
    return $i;
  }
  else{
    return XMLout ( {
        'IVALUE' =>[$i],
        'TVALUE' =>[$t]
      }, 
      RootName => 'RESULT'
    );
  }
}

sub ocs_config_is_valid {
  my ($key, $ivalue, $tvalue ) = @_;
  my $testedValue;
  if( $CONFIG{$key}->{type} eq 'IVALUE' ){
    $testedValue = $ivalue;
  }
  elsif( $CONFIG{$key}->{type} eq 'TVALUE' ){
    $testedValue = $tvalue;
  }
  else{
    return 0;
  }
  return $testedValue =~ $CONFIG{$key}->{filter};
}

# Set a config value in "config" table
# If ocs GUI is not used,
# you have to change parameters in ocsinventory.conf
sub ocs_config_write{
  my( $key, $ivalue, $tvalue ) = @_;
  
  if( ocs_config_is_supported( $key ) ){
    if( !ocs_config_is_valid( $key, $ivalue, $tvalue ) ){
      return (1, send_error( 'VALUE_NOT_VALID' ));
    }
    my $sth = get_sth("SELECT * FROM config WHERE NAME=?", $key);
    if( !$sth->rows ){
      do_sql("INSERT INTO config(NAME) VALUES(?)", $key);
    }
    $sth->finish();
    do_sql("UPDATE config SET IVALUE=? WHERE NAME=?", $ivalue, $key ) if defined $ivalue;
    do_sql("UPDATE config SET TVALUE=? WHERE NAME=?", $tvalue, $key ) if defined $tvalue;
  }
  else{
    return ( 1, send_error( 'KEY_NOT_SUPPORTED' ) );
  }
  0;
}

1;
