###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
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
