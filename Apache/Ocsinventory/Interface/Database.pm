###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2008
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Interface::Database;

use strict;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / 
  database_connect
  get_sth
  get_dbh_write
  get_dbh_read
  do_sql
  get_table_pk
  get_type_name
  untaint_dbstring
  untaint_dbstring_lst
/;

# Database connection
sub database_connect{
  my $dbHost;
  my $dbName;
  my $dbPort;
  my $dbUser;
  my $dbPwd;

  my $mode = shift;
  
  if( $mode eq 'read' && $ENV{'OCS_DB_SL_HOST'} ){
    $dbHost = $ENV{'OCS_DB_SL_HOST'};
    $dbName = $ENV{'OCS_DB_SL_NAME'}||'ocsweb';
    $dbPort = $ENV{'OCS_DB_SL_PORT'}||'3306';
    $dbUser = $ENV{'OCS_DB_SL_USER'};
    $dbPwd  = $Apache::Ocsinventory::SOAP::apache_req->dir_config('OCS_DB_SL_PWD');
  }
  else{
    $dbHost = $ENV{'OCS_DB_HOST'};
    $dbName = $ENV{'OCS_DB_NAME'}||'ocsweb';
    $dbPort = $ENV{'OCS_DB_PORT'}||'3306';
    $dbUser = $ENV{'OCS_DB_USER'};
    $dbPwd  = $Apache::Ocsinventory::SOAP::apache_req->dir_config('OCS_DB_PWD');
  }

  my $dbh = DBI->connect( "DBI:mysql:database=$dbName;host=$dbHost;port=$dbPort", $dbUser, $dbPwd );
  $dbh->do("SET NAMES 'utf8'") if($dbh && $ENV{'OCS_OPT_UNICODE_SUPPORT'});
  return $dbh;  
}

# Process the sql requests (prepare)
sub get_sth {
  my ($sql, @values) = @_;
  my $dbh = database_connect( get_db_mode( $sql ) );
  my $request = $dbh->prepare( $sql );
  $request->execute( @values ) or die("==Bad request==\nSQL:$sql\nDATAS:".join "> <", @values, "\n");
  return $request;
}

# Return dbi handles for particular use
sub get_dbh_write {
  return database_connect('write') ; 
}

sub get_dbh_read {
  return database_connect('read') ;
}

# Process the sql requests (do)
sub do_sql {
  my ($sql, @values) = @_;
  my $dbh = database_connect( get_db_mode($sql) );
  return $dbh->do( $sql, {}, @values );
}

# Return the id field of an inventory section
sub get_table_pk{
  my $section = shift;
  return ($section eq 'hardware')?'ID':'HARDWARE_ID';
}

sub get_type_name{
  my ($section, $field, $value) = @_ ;

  my $table_name = 'type_'.lc $section.'_'.lc $field ;  
  my $name ;
  
  my $existsSql = "SELECT NAME FROM $table_name WHERE ID=?" ;
  my $existsReq = get_sth($existsSql, $value) ;
  my $row = $existsReq->fetchrow_hashref() ;
  $name = $row->{NAME} ;
  $existsReq->finish ; 
  return $name ;
}

sub untaint_dbstring{
  my $string = shift;
  $string =~ s/"/\\"/g;
  $string =~ s/'/\\'/g;
  return $string;
}

sub untaint_dbstring_lst{
  my @list = @_;
  my @quoted;
  for (@list){
    push @quoted, untaint_dbstring($_);
  }
  return @quoted;
}

sub get_db_mode {
  my $sql = shift;
  if( $sql =~ /select|show/i ){
    return 'read';
  }
  else{
    return 'write';
  }
}

1;
