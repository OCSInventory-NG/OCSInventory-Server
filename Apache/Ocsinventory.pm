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

use strict;

BEGIN{
	if($ENV{'OCS_MODPERL_VERSION'} == 1){
		require Apache::Ocsinventory::Server::Modperl1;
		Apache::Ocsinventory::Server::Modperl1->import();
	}elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
		require Apache::Ocsinventory::Server::Modperl2;
		Apache::Ocsinventory::Server::Modperl2->import();
	}else{
		if(!defined($ENV{'OCS_MODPERL_VERSION'})){
			die("OCS_MODPERL_VERSION not defined. Abort\n");
		}else{
			die("OCS_MODPERL_VERSION set to, a bad parameter. Must be '1' or '2'. Abort\n");
		}
	}
}

$Apache::Ocsinventory::VERSION = '0.80';

# Defaults
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_FREQUENCY'} = 3;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_PROLOG_FREQ'} = 24;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_DEPLOY'} = 1;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_TRACE_DELETED'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_AUTO_DUPLICATE_LVL'} = 7;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_LOGLEVEL'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_PROXY_REVALIDATE_DELAY'} = 3600;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_UPDATE'} = 1;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_INVENTORY_DIFF'} = 1;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_INVENTORY_TRANSACTION'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_LOCK_REUSE_TIME'} = 60;

# Ocs modules
use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Server::System qw /:server _modules_get_request_handler/;
use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::Inventory;

# To compress the tx and read the rx
use Compress::Zlib;

# Globale structure
our %CURRENT_CONTEXT;

sub handler{

	my $d;
	my $status;
	my $r;
	my $data;
	my $query;

	# current context
	# Will be used to handle all globales
	%CURRENT_CONTEXT = (
		'APACHE_OBJECT' => undef,
		#'DBI_HANDLE' => undef,
		'DEVICEID' => undef,
		'DATABASE_ID' => undef,
		'DATA' => undef,
		'XML_ENTRY' => undef,
		'XML_INVENTORY' => undef,
		'LOCK_FL' => 0,
		'EXIST_FL' => 0
	);

	#LOG FILE
	##########
	#
	# All events will be stored in this file in the csv format(See the errors code in the documentation)
	open LOG, '>>'.LOGPATH.'/ocsinventory-NG.log' or die "Failed to open log file : $!\n";
	# We don't want buffer, so we allways flush the handles
	select(LOG);
	$|=1;
	select(STDOUT);
	$|=1;
	
	# Get the data and the apache object
	$r=shift;
	$CURRENT_CONTEXT{'APACHE_OBJECT'} = $r;

	#Connect to database
	if(!($CURRENT_CONTEXT{'DBI_HANDLE'} = &_database_connect())){
		&_log(505,'handler','Database connection');
		return APACHE_SERVER_ERROR;
	}

	#Retrieve server options
	if(&_get_sys_options()){
		&_log(503,'handler', 'System options');
		return APACHE_SERVER_ERROR;
	}
	
	# First, we determine the http method
	# The get method will be only available for the bootstrap to manage the deploy, and maybe, sometime to give files wich will be stored in the database
	if($r->method() eq 'GET'){

		# To manage the first contact with the bootstrap
		# The uri must be '/ocsinventory/deploy/[filename]'
		if($r->uri()=~/deploy\/(.+)\/?$/){
			if($ENV{'OCS_OPT_DEPLOY'}){
				return(&_send_file('deploy',$1));
			}else{
				return APACHE_FORBIDDEN;
			}
		}elsif($r->uri()=~/update\/(.+)\/(.+)\/(\d+)\/?/){
		# We use the GET method for the update to use the proxies
		# The URL is built like that : [OCSFSERVER]/ocsinventory/[os]/[name]/[version]
			if($ENV{'OCS_OPT_UPDATE'}){
				return(&_send_file('update',$1,$2,$3));
			}else{
				return APACHE_FORBIDDEN;
			}
		}else{
		# If the url is invalid
			return APACHE_BAD_REQUEST;
		}

	# Here is the post method management
	}elsif($r->method eq 'POST'){
	
		unless(&_get_http_header('Content-type', $r) =~ /Application\/x-compress/i){
		# Our discussion is compressed stream, nothing else
			&_log(510,'handler', 'Bad content type') if $ENV{'OCS_OPT_LOGLEVEL'};
			return APACHE_FORBIDDEN;

		}
		
		# Get the data
		if( read(STDIN, $data, $ENV{'CONTENT_LENGTH'}) == undef ){
			&_log(512,'handler','Reading request') if $ENV{'OCS_OPT_LOGLEVEL'};
			return APACHE_SERVER_ERROR
		}
		$CURRENT_CONTEXT{'DATA'} = \$data;

		# Debug level for Apache::DBI (apache/error.log)
		# $Apache::DBI::DEBUG=2;
	
		# Read the request
		# Possibilities :
		# prolog : The agent wants to know if he have to send an inventory(and with wich options)
		# update : The agent wants to know if there is a newer version available
		# inventory : It is an inventory
		# system : Request to know the server's time response (and if it's alive) not yet implemented
		# file : Download files when upgrading (For the moment, only when upgrading)
		##################################################
		#
		# Inflate the data
		unless($d = Compress::Zlib::inflateInit()){
			&_log(506,'handler','Compress stage') if $ENV{'OCS_OPT_LOGLEVEL'};
			return APACHE_BAD_REQUEST;
		}

		($data, $status) = $d->inflate($data);
		unless( $status == Z_OK or $status == Z_STREAM_END){
			&_log(506,'handler','Compress stage');
			return APACHE_SERVER_ERROR;
		}
		##########################
		# Parse the XML request
		unless($query = XML::Simple::XMLin( $data, SuppressEmpty => 1 )){
			&_log(507,'handler','Xml stage');
			return APACHE_BAD_REQUEST;
		}
		$CURRENT_CONTEXT{'XML_ENTRY'} = $query;

		# Get the request type
		my $request=$query->{QUERY};
		$CURRENT_CONTEXT{'DEVICEID'} = $query->{DEVICEID} or $CURRENT_CONTEXT{'DEVICEID'} = $query->{CONTENT}->{DEVICEID};
		
		unless($request eq 'UPDATE'){
			if(&_check_deviceid($Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'})){
				&_log(502,'inventory','Bad deviceid') if $ENV{'OCS_OPT_LOGLEVEL'};
				return(APACHE_BAD_REQUEST);
			}
		}
		
		 # Must be filled
		unless($request){
			&_log(500,'handler','Request not defined');
			return APACHE_BAD_REQUEST;
		}

		# Init global structure
		my $err = &_init();
		return($err) if $err;
		
		# The three above are hardcoded
		if($request eq 'PROLOG'){
			my $ret = &_prolog();
			return(&_end($ret));
		}elsif($request eq 'INVENTORY'){
			my $ret = &_inventory_handler();
			return(&_end($ret))
		}elsif($request eq 'SYSTEM'){
			my $ret = &_system_handler();
			return(&_end($ret));
		}else{
			# Other request are handled by options
			my $handler = &_modules_get_request_handler($request);
			if($handler == 0){
				&_log(500,'handler', 'No handler');
				return APACHE_BAD_REQUEST;
			}else{
				my $ret = &{$handler}(\%CURRENT_CONTEXT);
				return(&_end($ret));
			}

		}

	}else{ return APACHE_FORBIDDEN }

}

sub _init{
	my $request;
	
	# Retrieve Device if exists
	$request = $CURRENT_CONTEXT{'DBI_HANDLE'}->prepare('SELECT DEVICEID,ID,UNIX_TIMESTAMP(LASTCOME) AS LCOME,UNIX_TIMESTAMP(LASTDATE) AS LDATE,QUALITY,FIDELITY FROM hardware WHERE DEVICEID=?');
	unless($request->execute($CURRENT_CONTEXT{'DEVICEID'})){
		return(APACHE_SERVER_ERROR);
	}
	
	if($request->rows){
		my $row = $request->fetchrow_hashref;
		
		$CURRENT_CONTEXT{'EXIST_FL'} = 1;
		$CURRENT_CONTEXT{'DATABASE_ID'} = $row->{'ID'};
		$CURRENT_CONTEXT{'DETAILS'} = {
			'LCOME' => $row->{'LCOME'},
			'LDATE' => $row->{'LDATE'},
			'QUALITY' => $row->{'QUALITY'},
			'FIDELITY' => $row->{'FIDELITY'},
		}
	}else{
		$CURRENT_CONTEXT{'EXIST_FL'} = 0;
	}
	
	$request->finish;
	return(undef);
}
1;

