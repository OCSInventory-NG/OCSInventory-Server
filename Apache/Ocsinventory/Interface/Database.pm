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
  get_hardware_table_pk
  get_snmp_table_pk
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
  my %params;

  my $mode = shift;
  
  my $env_mode_prefix = 'OCS_DB';

  if( $mode eq 'read' && $ENV{'OCS_DB_SL_HOST'} ){
    $dbHost = $ENV{'OCS_DB_SL_HOST'};
    $dbName = $ENV{'OCS_DB_SL_NAME'}||'ocsweb';
    $dbPort = $ENV{'OCS_DB_SL_PORT'}||'3306';
    $dbUser = $ENV{'OCS_DB_SL_USER'};
    $dbPwd  = $Apache::Ocsinventory::SOAP::apache_req->dir_config('OCS_DB_SL_PWD');
    $env_mode_prefix .= '_SL';
  }
  else{
    $dbHost = $ENV{'OCS_DB_HOST'};
    $dbName = $ENV{'OCS_DB_NAME'}||'ocsweb';
    $dbPort = $ENV{'OCS_DB_PORT'}||'3306';
    $dbUser = $ENV{'OCS_DB_USER'};
    $dbPwd  = $Apache::Ocsinventory::SOAP::apache_req->dir_config('OCS_DB_PWD');
  }

  # Optionnaly a mysql socket different than the client's built in
  $params{'mysql_socket'} = $ENV{'OCS_OPT_DBI_MYSQL_SOCKET'} if $ENV{'OCS_OPT_DBI_MYSQL_SOCKET'};

  my $mysql_ssl_mode = '';
  if( defined($ENV{$env_mode_prefix.'_SSL_ENABLED'}) and $ENV{$env_mode_prefix.'_SSL_ENABLED'} == 1 )
  {
    if( defined($ENV{$env_mode_prefix.'_SSL_MODE'}) and $ENV{$env_mode_prefix.'_SSL_MODE'} eq 'SSL_MODE_PREFERRED' )
    {
        $mysql_ssl_mode = ';mysql_ssl=1;mysql_ssl_optional=1';
    }
    elsif( defined($ENV{$env_mode_prefix.'_SSL_MODE'}) and $ENV{$env_mode_prefix.'_SSL_MODE'} eq 'SSL_MODE_REQUIRED' )
    {
        $mysql_ssl_mode = ';mysql_ssl=1;mysql_ssl_verify_server_cert=0';
    }
    elsif( defined($ENV{$env_mode_prefix.'_SSL_MODE'}) and $ENV{$env_mode_prefix.'_SSL_MODE'} eq 'SSL_MODE_STRICT' )
    {
        $mysql_ssl_mode = ';mysql_ssl=1;mysql_ssl_verify_server_cert=1';
    }
    else
    {
        # SSL Is enabled but mode hasn't been provided. Let's put PREFERRED mode by default
        $mysql_ssl_mode = ';mysql_ssl=1;mysql_ssl_optional=1';
    }

    if( defined( $ENV{$env_mode_prefix.'_SSL_CLIENT_KEY'} ) and defined( $ENV{$env_mode_prefix.'_SSL_CLIENT_CERT'} ) and defined( $ENV{$env_mode_prefix.'_SSL_CA_CERT'} ) )
    {
        $mysql_ssl_mode .= ';mysql_ssl_client_key='.$ENV{$env_mode_prefix.'_SSL_CLIENT_KEY'}.';mysql_ssl_client_cert='.$ENV{$env_mode_prefix.'_SSL_CLIENT_CERT'}.';mysql_ssl_ca_file='.$ENV{$env_mode_prefix.'_SSL_CA_CERT'};
    }
  }

  # Connection...
  my $dbh = DBI->connect( "DBI:mysql:database=$dbName;host=$dbHost;port=$dbPort".$mysql_ssl_mode, $dbUser, $dbPwd, \%params);
  unless($dbh) {
    die DBI->errstr;
  }
  $dbh->do("SET NAMES 'utf8'") if($dbh && $ENV{'OCS_OPT_UNICODE_SUPPORT'});
  $dbh->do("SET sql_mode='NO_ENGINE_SUBSTITUTION'");
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
sub get_hardware_table_pk{
  my $section = shift;
  return ($section eq 'hardware')?'ID':'HARDWARE_ID';
}

sub get_snmp_table_pk{
  my $section = shift;
  return ($section eq 'snmp')?'ID':'SNMP_ID';
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
