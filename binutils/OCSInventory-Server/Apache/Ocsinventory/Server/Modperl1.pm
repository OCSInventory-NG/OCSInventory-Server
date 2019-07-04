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
package Apache::Ocsinventory::Server::Modperl1;

# For compatibilities with apache 1,5
#####################################
use mod_perl;
use constant MP2 => $mod_perl::VERSION >= 1.99;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw/
  APACHE_SERVER_ERROR
  APACHE_FORBIDDEN
  APACHE_OK
  APACHE_BAD_REQUEST
  _set_http_header
  _set_http_content_type
  _get_http_header
  _send_http_headers
/;

BEGIN{
  if(MP2){
    require Apache::compat;
    Apache::compat->import();
    require Apache::Const;
    Apache::Const->import(-compile => qw(:common :http));
  }else{
    require Apache::Constants;
    Apache::Constants->import(qw(:common :response));
  }
}

# retrieve apache constants
use constant APACHE_SERVER_ERROR => MP2?Apache::SERVER_ERROR:Apache::Constants::SERVER_ERROR;
use constant APACHE_FORBIDDEN => MP2?Apache::FORBIDDEN:Apache::Constants::FORBIDDEN;
use constant APACHE_OK => MP2?Apache::OK:Apache::Constants::OK;
use constant APACHE_BAD_REQUEST => MP2?Apache::BAD_REQUEST:Apache::Constants::BAD_REQUEST;

# Wrappers
sub _set_http_header{
  my ($header, $value, $r) = @_;
  $r->header_out($header => $value);
  return(0);
}

sub _set_http_content_type{
  my ($type, $r) = @_;
  $r->content_type($type);
}

sub _get_http_header{
  my ($header, $r) = @_;
  return $r->headers_in->{$header};
}
sub _send_http_headers{
  my $r = shift;
  $r->send_http_header;
}
1;
