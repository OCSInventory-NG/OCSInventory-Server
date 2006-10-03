###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2006
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface::Internals;

use Apache::Ocsinventory::Map;

use strict;

use constant CHECKSUM_MAX_VALUE => 131071;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / search_engine build_xml_inventory /;

sub search_engine{
	my ($request, $parsed_request) = @_;
#Available search engines
	my %search_engines = (
		'first'	=> \&engine_first
	);
	return &{ $search_engines{ $parsed_request->{ENGINE} } }( $request, $parsed_request );
}

sub engine_first {
	my @computers;
	my ($request, $parsed_request) = @_;
	my $parsed_request = XML::Simple::XMLin( $request, ForceArray => ['ID', 'TAG', 'USERID'] ) or die;
	my ($id, $name, $userid, $checksum, $tag);
	  
  if( $parsed_request->{ID} ){
  	$id .= ' AND';
		$id .= ' hardware.ID IN('.join(',', @{ $parsed_request->{ID} }).')';
	}
  
	if( $parsed_request->{TAG} ){
		s/^(.*)$/\"$1\"/ for @{ $parsed_request->{TAG} };
		$tag .= ' AND';
		$tag .= ' accountinfo.TAG IN('.join(',', @{ $parsed_request->{TAG} }).')';
	}
	
	if( $parsed_request->{CHECKSUM} ){
		$checksum = ' AND ('.$parsed_request->{CHECKSUM}.' & hardware.CHECKSUM)';
	}

	if( $parsed_request->{USERID} ){
		s/^(.*)$/\"$1\"/ for @{ $parsed_request->{USERID} };
		$userid .= ' AND';
		$userid .= ' hardware.USERID IN('.join(',', @{ $parsed_request->{USERID} } ).')';
	}
  
  my $search_string = "SELECT DISTINCT hardware.ID FROM hardware,accountinfo WHERE hardware.ID=accountinfo.HARDWARE_ID $id $name $userid $checksum $tag";
	
	my $sth = get_sth($search_string);
	
	while( my $row = $sth->fetchrow_hashref() ){
		push @computers, $row->{ID};
	}
  $sth->finish();
	return @computers;
}

# Database connection
sub database_connect{
	my $cstr = "DBI:mysql:database=$ENV{OCS_DB_NAME};host=$ENV{OCS_DB_HOST};port=$ENV{OCS_DB_PORT}";
	
	return DBI->connect(
		$cstr, $ENV{OCS_DB_USER},
		$Apache::Ocsinventory::SOAP::apache_req->dir_config('OCS_DB_PWD')
	);
}

sub get_sth {
	my ($sql, @values) = @_;
	my $dbh = database_connect();
	my $request = $dbh->prepare( $sql );
	$request->execute( @values ) or die;
	return $request;
}

sub build_xml_inventory {
	my ($computer, $checksum) = @_;
	my %xml;
	
	$checksum = CHECKSUM_MAX_VALUE unless $checksum=~/\d+/;
	
	for( keys(%data_map) ){
		if( ($checksum & $data_map{$_}->{mask} ) ){
			&build_xml_standard_section($computer, \%xml, $_) or die;
		}
	}
	return XML::Simple::XMLout( \%xml, 'RootName' => 'COMPUTER' ) or die;
}

sub build_xml_standard_section{
	my ($id, $xml_ref, $section) = @_;
	my %element;
	my @tmp;

	my $deviceid = get_table_pk($section);
	my $sth = get_sth("SELECT * FROM $section WHERE $deviceid=?", $id);
	
	while ( my $row = $sth->fetchrow_hashref() ){		
		for( @{ $data_map{ $section }->{fields} } ){
			$element{$_} = [ $row->{ $_ } ];
		}
		
		push @tmp, { %element };
		%element = ();
	}
	$section =~ s/$section/\U$&/g;
	$xml_ref->{$section}=[ @tmp ];
	@tmp = ();
	$sth->finish;
	return 1;
}

sub get_table_pk{
	my $section = shift;
	return ($section eq 'hardware')?'ID':'HARDWARE_ID';
	
}

1;