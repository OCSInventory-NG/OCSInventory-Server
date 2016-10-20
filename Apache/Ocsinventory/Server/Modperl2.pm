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
package Apache::Ocsinventory::Server::Modperl2;

use strict;

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

# mod-perl2
############
use Apache2::Connection (); 
use Apache2::SubRequest (); 
use Apache2::Access (); 
use Apache2::RequestIO (); 
use Apache2::RequestUtil ();
use Apache2::RequestRec (); 
use Apache2::ServerUtil (); 
use Apache2::Log; 
use APR::Table (); 
use Apache2::Const -compile => qw(OK HTTP_FORBIDDEN SERVER_ERROR HTTP_BAD_REQUEST);

# retrieve apache constants
use constant APACHE_SERVER_ERROR => Apache2::Const::SERVER_ERROR;
use constant APACHE_FORBIDDEN => Apache2::Const::HTTP_FORBIDDEN;
use constant APACHE_OK => Apache2::Const::OK;
use constant APACHE_BAD_REQUEST => Apache2::Const::HTTP_BAD_REQUEST;

# Wrappers
sub _set_http_header{
  my ($header, $value, $r) = @_;
  $r->headers_out->{$header} = $value;
  
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
  return;
}
1;
