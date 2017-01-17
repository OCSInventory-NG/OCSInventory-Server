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
package Apache::Ocsinventory::Interface::Internals;

use strict;

require Exporter;

# If SOAP lite doesn't decode xml entities
eval {
  require XML::Entities;
};
if($@){
  print STDERR "[".localtime()."] OCSINVENTORY: (SOAP): Cannot find XML::Entities\n";
}

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Interface::Database;
use XML::Simple;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  build_xml_standard_section
  decode_xml
  encode_xml
  search_engine
  send_error
  get_custom_field_name_map
  get_custom_fields_values_map
/;

sub decode_xml{
  my $data = shift;
  unless( $data =~ /^</ ){
    return XML::Entities::decode('all', $data);
  }
  return $data;
}

sub search_engine{
  # Available search engines
  my $engine = shift;
  my %search_engines = (
    'first'  => \&engine_first,
  );
  &{ $search_engines{ (lc $engine) } }( @_ );
}

sub engine_first {
  my ($request, $ids, $begin, $main_table, $accountinfo_table, $deviceid_column, $pk, $sort_by, $sort_dir) = @_;
  my $parsed_request = XML::Simple::XMLin( $request, ForceArray => ['ID', 'EXCLUDE_ID', 'TAG', 'EXCLUDE_TAG', 'USERID'], SuppressEmpty => 1 ) or die;
  my ($id, $name, $userid, $checksum, $tag);

  # Database ids criteria
  die("BAD_REQUEST") if ( $parsed_request->{ID} and $parsed_request->{EXCLUDE_ID} );

  if( $parsed_request->{ID} ){
    if( my @ids = untaint_int_lst( @{ $parsed_request->{ID} } )){
      $id .= ' AND';
      $id .= ' '.$main_table.'.ID IN('.join(',', @ids ).')';
    }
  }

  if( $parsed_request->{EXCLUDE_ID} ){
    if( my @exclude_ids = untaint_int_lst( @{ $parsed_request->{EXCLUDE_ID} } )){
      $id .= ' AND';
      $id .= ' '.$main_table.'.ID NOT IN('.join(',', @exclude_ids ).')';
    }
  }

  # Tag criteria
  die("BAD_REQUEST") if ( $parsed_request->{TAG} and $parsed_request->{EXCLUDE_TAG} );

  if( $parsed_request->{TAG} ){
    if( my @tags = untaint_dbstring_lst( @{ $parsed_request->{TAG} } )){
      $tag .= ' AND';
      $tag .= ' '.$accountinfo_table.'.TAG IN("'.join('","', @tags ).'")';
    }
  }

  if( $parsed_request->{EXCLUDE_TAG} ){
    if( my @exclude_tags = untaint_dbstring_lst( @{ $parsed_request->{EXCLUDE_TAG} } )){
      $tag .= ' AND';
      $tag .= ' '.$accountinfo_table.'.TAG NOT IN("'.join('","', @exclude_tags ).'")';
    }
  }

  # Checksum criteria (only positive "&" will match
  if( $parsed_request->{CHECKSUM} ){
    die("BAD_CHECKSUM") if !untaint_int( $parsed_request->{CHECKSUM} );
    $checksum = ' AND ('.$parsed_request->{CHECKSUM}.' & '.$main_table.'.CHECKSUM)';
  }
  # Associated user criteria
  if( $parsed_request->{USERID} && $main_table =~ /^hardware$/){
    if( my @users_id =  untaint_dbstring_lst( @{ $parsed_request->{USERID} } ) ){
      $userid .= ' AND';
      $userid .= ' '.$main_table.'.USERID IN("'.join('","', @users_id ).'")';
    }
  }
  # Generate sql string
  my $search_string = "SELECT DISTINCT $main_table.ID FROM $main_table,$accountinfo_table WHERE $main_table.$deviceid_column NOT LIKE '\\_%' AND $main_table.ID=$accountinfo_table.$pk $id $name $userid $checksum $tag ORDER BY hardware.$sort_by $sort_dir limit $begin,$ENV{OCS_OPT_WEB_SERVICE_RESULTS_LIMIT}";
  # Play it
  my $sth = get_sth($search_string);
  # Get ids
  while( my $row = $sth->fetchrow_hashref() ){
    push @{$ids}, $row->{ID};
  }
  # Destroy request object
  $sth->finish();
}


# Build a database mapped inventory section
sub build_xml_standard_section{
  my ($id, $main_table, $xml_ref, $section) = @_;
  my %element;
  my @tmp;

  my %get_table_pk_functions = (
    'hardware' => \&get_hardware_table_pk,
    'snmp' => \&get_snmp_table_pk
  );

  # Request database
  my $deviceid = &{ $get_table_pk_functions{ $main_table } }($section);
  my $sth = get_sth("SELECT * FROM $section WHERE $deviceid=?", $id);

  # Build data structure...
  while ( my $row = $sth->fetchrow_hashref() ){
    for( keys(%{$DATA_MAP{ $section }->{fields}}) ){
      next if $DATA_MAP{ $section }->{fields}->{$_}->{noSql};
      # New DB schema support
      if( $DATA_MAP{ $section }->{fields}->{$_}->{type} ){
        my $field = $_;
        $field =~ s/_ID//g;    #We delete the _ID pattern to be in concordance with table name
        $row->{ $_ } = get_type_name($section, $field, $row->{ $_ });
        $element{$field} = [ $row->{ $_ } ];
      }
      else {
        $element{$_} = [ $row->{ $_ } ];
      }
    }

    push @tmp, { %element };
    %element = ();
  }
  $section =~ s/$section/\U$&/g;
  $xml_ref->{$section}=[ @tmp ];
  @tmp = ();
  $sth->finish;
}

sub reset_checksum {
  my( $checksum, $ref ) = @_;
  my $where = join(',', @$ref);
  return do_sql("UPDATE hardware SET CHECKSUM=? WHERE ID IN ($where)", $checksum);
}
sub send_error{
  my $error = shift;
  return XMLout (
    { 'ERROR' => [ $error ] },
    RootName => 'RESULT'
  );
}

sub untaint_int_lst{
  my @list = @_;
  my @cleared;
  for (@list){
    push @cleared, $_ if untaint_int($_);
  }
  return @cleared;
}

sub untaint_int{
  my $int = shift;
  return $int =~ /^\d+$/;
}

# Helper for resolving custom field names in ACCOUNTINFO
sub get_custom_field_name_map {
    my $account_type = shift;
    my $name_map = {};

    # Request database
    my $sth = get_sth('SELECT ID, NAME FROM accountinfo_config WHERE NAME_ACCOUNTINFO IS NULL AND ACCOUNT_TYPE=?', $account_type);
    # Build data structure...
    my $rows = $sth->fetchall_arrayref();
    foreach my $row ( @$rows ) {
      $name_map->{ "fields_" . $row->[0] } = $row->[1];
    }
    $sth->finish;
    return $name_map;
}

# Helper for resolving textual values of custom fields
# of type CHECKBOX, RADIOBUTTON, SELECT
sub get_custom_fields_values_map {
  my $account_value = shift;
  my $values_map = {};

  # Request database
  my $sth = get_sth('SELECT NAME, IVALUE, TVALUE FROM config WHERE NAME LIKE ?', $account_value."_%_%");
  my $rows = $sth->fetchall_arrayref();

  my $regexp = $account_value."_(.*)_[0-9]+";

  foreach my $row ( @$rows ) {
    if ($row->[0] =~ /^$regexp$/) {
      $values_map->{ $1 }->{ $row->[1] } = $row->[2];
    }
  }
  $sth->finish;
  return $values_map;
}

1;
