###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory;


# For compatibilities with apache 1,5
#####################################
use mod_perl;
use constant MP2 => $mod_perl::VERSION >= 1.99;

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

our %CURRENT_CONTEXT;

# Wrappers
sub _set_http_header{
	my $header = shift;
	my $value = shift;
	$CURRENT_CONTEXT{"APACHE_OBJECT"}->header_out($header => $value);
	return(0);
}

sub _set_http_content_type{
	my $type = shift;
	$CURRENT_CONTEXT{"APACHE_OBJECT"}->content_type($type);
}

sub _get_http_header{
	my $header = shift;
	return $CURRENT_CONTEXT{"APACHE_OBJECT"}->headers_in->{$header};
}
sub _send_http_headers{
	$CURRENT_CONTEXT{"APACHE_OBJECT"}->send_http_header;
}
1;
