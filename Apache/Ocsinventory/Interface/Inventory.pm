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
package Apache::Ocsinventory::Interface::Inventory;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Internals;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
/;

sub get_computers {
  my $request = decode_xml( shift );

  my %build_functions = (
    'INVENTORY' => \&build_computers_xml_inventory,
    'META' => \&build_computers_xml_meta
  );

  # Specific values for this inventory
  my $main_table = "hardware";
  my $accountinfo_table = "accountinfo";
  my $deviceid_column = "DEVICEID";
  my $pk = "HARDWARE_ID";

  # Returned values
  my @result;
  # Remove backslash from xml
  $request =~ s/\\$// ;
  # First xml parsing
  my $parsed_request;
  eval {
    $parsed_request = XML::Simple::XMLin( $request );
  } or do {
    return "You've malformed XML please check your SOAP Request";
  };
  # Max number of responses sent back to client
  my $max_responses = $ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};
  my @ids;
  # Generate boundaries
  my $begin;
  if( defined $parsed_request->{'OFFSET'} ){
    return send_error('BAD_OFFSET') unless $parsed_request->{'OFFSET'} =~ /^\d+$/;
    $begin = $parsed_request->{'OFFSET'}*$ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT};
  }
  elsif( defined $parsed_request->{'BEGIN'}){
    return send_error('BAD_BEGIN_VALUE') unless $parsed_request->{'BEGIN'} =~ /^\d+$/;
    $begin = $parsed_request->{'BEGIN'};
  }
  else{
    $begin = 0;
  }

  my $sort_by = "ID";
  if( defined $parsed_request->{'SORT_BY'} and ($parsed_request->{'SORT_BY'} =~ m/(LASTCOME|LASTDATE)/)) {
    $sort_by = $parsed_request->{'SORT_BY'};
  }

  my $sort_dir = "ASC";
  if( defined $parsed_request->{'SORT_DIR'} and ($parsed_request->{'SORT_DIR'} eq "DESC")) {
    $sort_dir = "DESC";
  }

  # Call search_engine sub
  search_engine($parsed_request->{ENGINE}, $request, \@ids, $begin, $main_table, $accountinfo_table, $deviceid_column, $pk, $sort_by, $sort_dir);
  # Type of requested data (meta datas, inventories, special features..
  my $type=$parsed_request->{'ASKING_FOR'}||'INVENTORY';
  $type =~ s/^(.+)$/\U$1/;

  # Generate xml responses
  for(@ids){
      push @result, &{ $build_functions{ $type } }($_, $main_table, $parsed_request->{CHECKSUM}, $parsed_request->{WANTED});#Wanted=>special sections bitmap
  }
  # Send
  return "<COMPUTERS>\n", @result, "</COMPUTERS>\n";
}

# Build whole inventory (sections specified using checksum)
sub build_computers_xml_inventory {
  my ($computer, $main_table, $checksum, $wanted) = @_;
  my %xml;
  my %special_sections = (
    accountinfo => 1,
     dico_soft => 2
  );
  # Whole inventory by default
  $checksum = CHECKSUM_MAX_VALUE unless $checksum=~/\d+/;
  # Build each section using ...standard_section
  for( keys(%DATA_MAP) ){
    #Don't process the sections that are use for capacities special inventory
    next if $DATA_MAP{$_}->{capacities};

    if( ($checksum & $DATA_MAP{$_}->{mask} ) ){
      &build_xml_standard_section($computer, $main_table, \%xml, $_) or die;
    }
  }
  # Accountinfos
  for( keys( %special_sections ) ){
    &build_computers_xml_special_section($computer, \%xml, $_) if $special_sections{$_} & $wanted;
  }
  # Return the xml response to interface
  return XML::Simple::XMLout( \%xml, 'RootName' => 'COMPUTER' ) or die;
}

# Build metadata of a computer
sub build_computers_xml_meta {
  my $id = shift;
  my %xml;
  # For mapped fields
  my @mapped_fields = qw / NAME TAG DEVICEID LASTDATE LASTCOME CHECKSUM DATABASEID/;
  # For others
  my @other_fields = qw //;

  my $sql_str = qq/
    SELECT
      hardware.DEVICEID AS DEVICEID,
      hardware.LASTDATE AS LASTDATE,
      hardware.LASTCOME AS LASTCOME,
      hardware.checksum AS CHECKSUM,
      hardware.ID AS DATABASEID,
      hardware.NAME AS NAME,
      accountinfo.TAG AS TAG
    FROM hardware,accountinfo
    WHERE accountinfo.HARDWARE_ID=hardware.ID
    AND ID=?
  /;
  my $sth = get_sth( $sql_str, $id);
  while( my $row = $sth->fetchrow_hashref ){
    for( @mapped_fields ){
      $xml{ $_ }=[$row->{ $_ }];
    }
  }
  $sth->finish;
  return XML::Simple::XMLout( \%xml, 'RootName' => 'COMPUTER' ) or die;
}

# For non-standard sections
sub build_computers_xml_special_section {
  my ($id, $xml_ref, $section) = @_;
  # Accountinfos retrieving
  if($section eq 'accountinfo'){
    my $custom_field_names = get_custom_field_name_map('COMPUTERS');
    my $custom_fields_values = get_custom_fields_values_map('ACCOUNT_VALUE');
    my %element;
    my @tmp;
    # Request database
    my $sth = get_sth('SELECT * FROM accountinfo WHERE HARDWARE_ID=?', $id);
    # Build data structure...
    my $row = $sth->fetchrow_hashref();
    foreach my $akey ( keys( %$row ) ) {
      next if $akey eq get_hardware_table_pk('accountinfo');
      my $field_name = (exists $custom_field_names->{ $akey }) ? $custom_field_names->{ $akey } : $akey;
      if (! exists $custom_fields_values->{ $field_name }) {
        push @tmp, ( { Name => $field_name,  content => $row->{ $akey } } );
      } else {
        foreach my $codepoint ( split( /&&&/, $row->{ $akey } ) ) {
          push @tmp, ( { Name => $field_name, content => $custom_fields_values->{ $field_name }->{ $codepoint } } );
        }
      }
    }
    $xml_ref->{'ACCOUNTINFO'}{'ENTRY'} = [ @tmp ];
    $sth->finish;
  }
  elsif($section eq 'dico_soft'){
    my @tmp;
    my $sth = get_sth('SELECT DISTINCT d.`FORMATTED` AS FORMAT FROM `software` s JOIN `software_name` n ON (n.`ID` = s.`NAME_ID`) JOIN `dico_soft` d ON (d.`EXTRACTED` = n.`NAME`) WHERE s.`HARDWARE_ID`=?', $id);
    while( my $row = $sth->fetchrow_hashref() ){
      push @tmp, $row->{FORMAT};
    }
    $xml_ref->{'DICO_SOFT'}{WORD} = [ @tmp ];
    $sth->finish;
  }
}

# Return software name alias
sub get_dico_soft_extracted{
  my $extracted = shift;
  my $sth = get_sth('SELECT DISTINCT FORMATTED FROM dico_soft WHERE EXTRACTED=?', $extracted);
  unless($sth->rows){
    $sth->finish();
    return undef;
  }
  my ($formatted) = $sth->fetchrow_array;
  $sth->finish();
  return $formatted;
}
1;
