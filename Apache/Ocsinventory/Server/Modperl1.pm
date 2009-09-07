###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
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
