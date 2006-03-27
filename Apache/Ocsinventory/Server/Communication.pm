###############################################################################
## OCSINVENTORY-NG
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Communication;

use strict;

BEGIN{
	if($ENV{'OCS_MODPERL_VERSION'} == 1){
		require Apache::Ocsinventory::Server::Modperl1;
		Apache::Ocsinventory::Server::Modperl1->import();
	}elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
		require Apache::Ocsinventory::Server::Modperl2;
		Apache::Ocsinventory::Server::Modperl2->import();
	}
}

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw / _send_response _prolog/;

use Apache::Ocsinventory::Server::Constants;

use Apache::Ocsinventory::Server::System(qw/
 	:server
 	_modules_get_prolog_readers
 	_modules_get_prolog_writers
/);

# Subroutine wich answer to client prolog
sub _prolog{

	my $frequency;
	my $quality;
	my $now;
	my $lastdate;
	my $request;
	my $row;
	
	my $DeviceID = $Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'};
	my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
	my $info = $Apache::Ocsinventory::CURRENT_CONTEXT{'DETAILS'};
	
	
	&_prolog_read();

	$frequency = $ENV{'OCS_OPT_FREQUENCY'};

	# If we do not have the default frequency
	unless(defined($frequency)){
		&_prolog_resp(PROLOG_RESP_STOP);
		&_log(503,'prolog','No frequency') if $ENV{'OCS_OPT_LOGLEVEL'};
		return APACHE_OK;
	}

	# We have this computer in the database
	if($Apache::Ocsinventory::CURRENT_CONTEXT{'EXIST_FL'}){
		# Get the current timestamp
		$now = time();
		
		# Compute quality 
		if($info->{'FIDELITY'} > 1){
			$quality = ((($now-$info->{'LCOME'})/86400) + ($info->{'QUALITY'}*$info->{'FIDELITY'}))/(($info->{'FIDELITY'})+1);
		}else{
			# We increment the number of visits
			$quality = (($now-$info->{'LCOME'})/86400);
		}
		
		# We update device data
		if(!$dbh->do('UPDATE hardware SET FIDELITY=FIDELITY+1,QUALITY=?,LASTCOME=NOW() WHERE DEVICEID=?', {}, $quality, $DeviceID)){
			return APACHE_SERVER_ERROR;
		}


		##########
		# If special value 0, we allways accept
		if($frequency==0){
			&_prolog_resp(PROLOG_RESP_SEND);
			return APACHE_OK;
		# If -1, we allways reject
		}elsif($frequency==(-1)){
			&_prolog_resp(PROLOG_RESP_BREAK);
			return APACHE_OK;
		}
		
		# Saving lastdate
		$lastdate = $info->{'LCOME'};

		# Maybe there are computer's special frequency
		$request=$dbh->prepare('SELECT IVALUE FROM devices WHERE HARDWARE_ID=? AND NAME="FREQUENCY"');
		$request->execute($DeviceID);
		if($row=$request->fetchrow_hashref()){
			$frequency=$row->{'IVALUE'};
		}
		$request->finish;
		
		# If special values...
		if($frequency==0){
			&_prolog_resp(PROLOG_RESP_SEND);
			return APACHE_OK;
		}elsif($frequency==(-1)){
			&_prolog_resp(PROLOG_RESP_BREAK);
			return APACHE_OK;
		}

		unless ($lastdate){
			&_prolog_resp(PROLOG_RESP_SEND);
			return APACHE_OK;
		}

		# Have we override the period ?
		if((($lastdate-$now)+$frequency*86400)<0){
			&_prolog_resp(PROLOG_RESP_SEND);
			return APACHE_OK;
		}else{
			&_prolog_resp(PROLOG_RESP_STOP);
			return APACHE_OK;
		}
	}else{#This is a new Device ID
		if($frequency==(-1)){
			&_prolog_resp(PROLOG_RESP_BREAK);
			return APACHE_OK;
		}else{
			&_log(103,'prolog','Accepted') if $ENV{'OCS_OPT_LOGLEVEL'};
			&_prolog_resp(PROLOG_RESP_SEND);
			return APACHE_OK;
		}	
	}
	
}

sub _send_response{

	my( $xml, $message, $d, $status );
	my $r = $Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'};

	# Generate the response
	# Generation of xml message
	$message = XML::Simple::XMLout( $_[0], RootName => 'REPLY', XMLDecl => "<?xml version='1.0' encoding='ISO-8859-1'?>",
	                 NoSort => 1, SuppressEmpty => undef);
	# send
	unless($message = Compress::Zlib::compress( $message )){
		&_log(506,'send_response','Compress stage') if $ENV{'OCS_OPT_LOGLEVEL'};
		return APACHE_BAD_REQUEST;
	}
	
	&_set_http_header('content-length', length($message),$r);
	&_set_http_header('Cache-control', 'no-cache',$r);
	&_set_http_content_type('application/x-compressed',$r);
	&_send_http_headers($r);
	$r->print($message);
	return(0);
}

sub _prolog_resp{
	my $decision = shift;
	my %resp;
	
	&_prolog_build_resp($decision, \%resp);

	if($resp{'RESPONSE'}[0] eq 'STOP'){
		&_log(102,'prolog','Declined') if $ENV{'OCS_OPT_LOGLEVEL'};
	}elsif($resp{'RESPONSE'}[0] eq 'SEND'){
		&_log(100,'prolog','Accepted') if $ENV{'OCS_OPT_LOGLEVEL'};
	}elsif($resp{'RESPONSE'}[0] eq 'OTHER'){
		&_log(105,'prolog','') if $ENV{'OCS_OPT_LOGLEVEL'};
	}
	&_send_response(\%resp);
	return(0);
}

sub _prolog_build_resp{
	my $decision = shift;
	my $resp = shift;
	my $module;
	my $state;

	if($decision == PROLOG_RESP_BREAK){
		$resp->{'RESPONSE'} = [ 'STOP' ];
		return(0);
	}elsif($decision == PROLOG_RESP_STOP){
		$resp->{'RESPONSE'} = [ 'STOP' ];
	}elsif($decision == PROLOG_RESP_SEND){
		$resp->{'RESPONSE'} = [ 'SEND' ];
	}

	for(&_modules_get_prolog_writers()){
		last if $_ == 0;
		&$_(\%Apache::Ocsinventory::CURRENT_CONTEXT, $resp);
	}
	return(0);
}

sub _prolog_read{
	for(&_modules_get_prolog_readers()){
		last if $_==0;
		&$_(\%Apache::Ocsinventory::CURRENT_CONTEXT);
	}
	return(0);
}
1;
