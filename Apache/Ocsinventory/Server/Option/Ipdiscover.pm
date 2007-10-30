###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Option::Ipdiscover;

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

use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::System;
use Apache::Ocsinventory::Server::Constants;

use constant IPD_NEVER => 0;
use constant IPD_ON => 1;
use constant IPD_MAN => 2;

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
	'NAME' => 'IPDISCOVER',
	'HANDLER_PROLOG_READ' => undef,
	'HANDLER_PROLOG_RESP' => \&_ipdiscover_prolog_resp,
	'HANDLER_PRE_INVENTORY' => undef,
	'HANDLER_POST_INVENTORY' => \&_ipdiscover_main,
	'REQUEST_NAME' => undef,
	'HANDLER_REQUEST' => undef,
	'HANDLER_DUPLICATE' => undef,
	'TYPE' => OPTION_TYPE_SYNC,
	'XML_PARSER_OPT' => {
			'ForceArray' => ['H', 'NETWORKS']
	}
};

# Default
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_IPDISCOVER'} = 1;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_IPDISCOVER_LATENCY'} = 100;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_IPDISCOVER_MAX_ALIVE'} = 14;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_IPDISCOVER_USE_GROUPS'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_IPDISCOVER_BETTER_THRESHOLD'} = 1;

sub _ipdiscover_prolog_resp{

	return unless $ENV{'OCS_OPT_IPDISCOVER'};
	
	my $current_context = shift;
	
	return unless $current_context->{'EXIST_FL'};
	
	my $resp = shift;
	
	my $request;
	my $row;
	my $dbh = $current_context->{'DBI_HANDLE'};
	my $DeviceID = $current_context->{'DATABASE_ID'};
	# To handle the agent versions
	my ($ua, $os, $v);

	################################
	#IPDISCOVER
	###########
	# What is the current state of this option ?

	#ipdiscover for this device ?
	$request=$dbh->prepare('SELECT TVALUE FROM devices WHERE HARDWARE_ID=? AND NAME="IPDISCOVER" AND (IVALUE=? OR IVALUE=?)');
	$request->execute($DeviceID, IPD_ON, IPD_MAN);
	if($request->rows){
		# We can use groups to prevent some computers to be elected
		if( $ENV{'OCS_OPT_ENABLE_GROUPS'} && $ENV{'OCS_OPT_IPDISCOVER_USE_GROUPS'} ){
			my $groups = $current_context->{'MEMBER_OF'};
			for(@$groups){
				if( $dbh->do('SELECT IVALUE FROM devices WHERE NAME="IPDISCOVER" AND IVALUE=? AND HARDWARE_ID=?',{},IPD_NEVER,$_)!=0E0 ){
					$dbh->do('DELETE FROM devices WHERE HARDWARE_ID=? AND NAME="IPDISCOVER"', {}, $DeviceID);
					return;
				}
			}
		}

		$resp->{'RESPONSE'} = [ 'SEND' ];
		$row = $request->fetchrow_hashref();
	# Agents newer than 13(linux) ans newer than 4027(Win32) receive new xml formatting (including ipdisc_lat)
		$ua = _get_http_header('User-agent', $current_context->{'APACHE_OBJECT'});

		my $legacymode;
		if( $ua=~/OCS-NG_(\w+)_client_v(\d+)/ ){
		  $legacymode = 1 if ($1 eq "windows" && $2<=4027) or ($1 eq "linux" && $2<=13);
		}
		
		if( $legacymode ){		
			push @{$$resp{'OPTION'}}, { 'NAME' => [ 'IPDISCOVER' ], 'PARAM' => [ $row->{'TVALUE'} ] };
		}
		else{
			push @{$$resp{'OPTION'}}, { 
						'NAME' => [ 'IPDISCOVER' ], 
						'PARAM' => { 
								'IPDISC_LAT' => $ENV{'OCS_OPT_IPDISCOVER_LATENCY'}?$ENV{'OCS_OPT_IPDISCOVER_LATENCY'}:'0', 
								'content' => $row->{'TVALUE'} 
						} 
			};
		}
		
		&_set_http_header('Connection', 'close', $current_context->{'APACHE_OBJECT'});
		return 1;
	}else{
		return 0;
	}
}

sub _ipdiscover_main{

	my $request;
	my $row;
	my $subnet;
	my $remove;
	my $ivalue;

	return unless $ENV{'OCS_OPT_IPDISCOVER'};
	
	my $current_context = shift;
	my $DeviceID = $current_context->{'DATABASE_ID'};
	my $dbh = $current_context->{'DBI_HANDLE'};
	my $result = $current_context->{'XML_ENTRY'};
	
	# We can use groups to prevent some computers to be elected
	if( $ENV{'OCS_OPT_ENABLE_GROUPS'} && $ENV{'OCS_OPT_IPDISCOVER_USE_GROUPS'} ){
		my $groups = $current_context->{'MEMBER_OF'};
		for(@$groups){
			if( $dbh->do('SELECT IVALUE FROM devices WHERE NAME="IPDISCOVER" AND IVALUE=? AND HARDWARE_ID=?',{},IPD_NEVER,$_)!=0E0 ){
				return;
			}
		}
	}

	# Is the device already have the ipdiscover function ?
	$request=$dbh->prepare('SELECT IVALUE, TVALUE FROM devices WHERE HARDWARE_ID=? AND NAME="IPDISCOVER"');
	$request->execute($DeviceID);
	if($request->rows){
		$row = $request->fetchrow_hashref;
		#IVALUE = 0 means that computer will not ever be elected
		if( ($ivalue = $row->{IVALUE}) == IPD_NEVER ){
			return 0;
		}
		# get 1 on removing and 0 if ok
		$remove = &_ipdiscover_read_result($dbh, $result, $row->{'TVALUE'});
		if( $ivalue == IPD_MAN ){
			$remove = 0;
		}
		$request->finish;
		if(!defined($remove)){
			return 1;
		}
	}else{
		if($result->{CONTENT}->{HARDWARE}->{OSNAME}!~/xp|2000|linux|2003|vista/i){
			return 0;
		}
		
		# Get quality and fidelity
		$request = $dbh->prepare('SELECT QUALITY,FIDELITY FROM hardware WHERE ID=?');
		$request->execute($DeviceID);

		if($row = $request->fetchrow_hashref){
			if( ($row->{'FIDELITY'} > 2 and $row->{'QUALITY'} != 0) || $ENV{'OCS_OPT_IPDISCOVER_NO_POSTPONE'} ){
				$subnet = &_ipdiscover_find_iface($result, $current_context->{'DBI_HANDLE'});
				if(!$subnet){
					return &_ipdiscover_evaluate($result, $row->{'FIDELITY'}, $row->{'QUALITY'}, $dbh, $DeviceID);
				}elsif($subnet =~ /^(\d{1,3}(?:\.\d{1,3}){3})$/){
					# The computer is elected, we have to write it in devices
					$dbh->do('INSERT INTO devices(HARDWARE_ID, NAME, IVALUE, TVALUE, COMMENTS) VALUES(?,?,?,?,?)',{},$DeviceID,'IPDISCOVER',1,$subnet,'') or return 1;
					&_log(1001,'ipdiscover','Elected'."($subnet)") if $ENV{'OCS_OPT_LOGLEVEL'};
					return 0;
				}else{
					return 0;
				}
			}else{
				return 0;
			}
		}
	}
	


	# If needed, we remove
	if($remove){
		if(!$dbh->do('DELETE FROM devices WHERE HARDWARE_ID=? AND NAME="IPDISCOVER"', {}, $DeviceID)){
			return 1;
		}
		$dbh->commit;
		&_log(1002,'ipdiscover','Removed') if $ENV{'OCS_OPT_LOGLEVEL'};
	}
	0;
}

sub _ipdiscover_read_result{

	my ($dbh, $result, $subnet) = @_;
	my $mask;
	my $update_req;
	my $insert_req;
	my $request;

	if(exists($result->{CONTENT}->{IPDISCOVER})){
		my $base = $result->{CONTENT}->{NETWORKS};
		
		# Retrieve netmask
		for(@$base){
			if($_->{IPSUBNET} eq $subnet){
				$mask = $_->{IPMASK};
				last;
			}		
		}
		
		# We insert the results (MAC/IP)
		$update_req = $dbh->prepare('UPDATE netmap SET IP=?,MASK=?,NETID=?,DATE=NULL, NAME=? WHERE MAC=?');
		$insert_req = $dbh->prepare('INSERT INTO netmap(IP, MAC, MASK, NETID, NAME) VALUES(?,?,?,?,?)');
		
		$base = $result->{CONTENT}->{IPDISCOVER}->{H};
		for(@$base){
			unless($_->{I}=~/^(\d{1,3}(?:\.\d{1,3}){3})$/ and $_->{M}=~/.{2}(?::.{2}){5}/){
				&_log(1003,'ipdiscover','Bad result') if $ENV{'OCS_OPT_LOGLEVEL'};
				next;
			}
			$update_req->execute($_->{I}, $mask, $subnet, $_->{N}, $_->{M});
			unless($update_req->rows){
				$insert_req->execute($_->{I}, $_->{M}, $mask, $subnet, $_->{N});
			}
		}
		$dbh->commit;
	}else{
		return 1;
	}

	# Maybe There are too much ipdiscover per subnet ?
	$request=$dbh->prepare('SELECT HARDWARE_ID FROM devices WHERE TVALUE=? AND NAME="IPDISCOVER"');
	$request->execute($subnet);
	if($request->rows > $ENV{'OCS_OPT_IPDISCOVER'}){
		$request->finish;
		return 1;
	}
	
	return 0;
}

sub _ipdiscover_find_iface{

	my $result = shift;
	my $base = $result->{CONTENT}->{NETWORKS};
	
	my $dbh = shift;
	
	my $request;
	my @worth;
	
	for(@$base){
		if($_->{DESCRIPTION}!~/ppp/i){
			if($_->{STATUS}=~/up/i){
				if($_->{IPMASK}=~/^(?:255\.){2}/){
					if($_->{IPSUBNET}=~/^(\d{1,3}(?:\.\d{1,3}){3})$/){
	
		# Looking for a need of ipdiscover
		$request = $dbh->prepare('SELECT HARDWARE_ID FROM devices WHERE TVALUE=? AND NAME="IPDISCOVER"');
		$request->execute($_->{IPSUBNET});
		if($request->rows < $ENV{'OCS_OPT_IPDISCOVER'}){
			$request->finish;
			return $_->{IPSUBNET};
		}
		$request->finish;
		
		}}}}	
		# Looking for ipdiscover older than ipdiscover_max_value
		# and compare current computer with actual ipdiscover
	}
	return 0;
	
}

sub _ipdiscover_evaluate{

	my ($result, $fidelity, $quality, $dbh, $DeviceID) = @_;
	
	my $request;
	my $row;
	my $time = time();
	my $max_age = $ENV{'OCS_OPT_IPDISCOVER_MAX_ALIVE'}*86400;
	
	my $over;
	my @worth;

	my $base = $result->{CONTENT}->{NETWORKS};
	
	for(@$base){
		if(defined($_->{IPSUBNET}) and $_->{IPSUBNET}=~/^(\d{1,3}(?:\.\d{1,3}){3})$/ ){

			$request = $dbh->prepare('
			SELECT h.ID AS ID, h.QUALITY AS QUALITY, UNIX_TIMESTAMP(h.LASTDATE) AS LAST 
			FROM hardware h,devices d 
			WHERE d.HARDWARE_ID=h.ID AND d.TVALUE=? AND h.ID<>? AND d.IVALUE<>? AND d.NAME="IPDISCOVER"');
			$request->execute($_->{IPSUBNET}, $DeviceID, IPD_MAN);

			while($row = $request->fetchrow_hashref){
				# If we find an ipdiscover that is older than IP_MAX_ALIVE, we replace it with the current
				if( (($time - $row->{'LAST'}) > $max_age) and $max_age){
					@worth = ($row->{'ID'}, $row->{'QUALITY'} );
					$over = 1;
					last;
				}
				# For the first round
				unless(@worth){
					@worth = ($row->{'ID'}, $row->{'QUALITY'} );
					next;
				}
				# Put the worth in @worth
				@worth = ( $row->{'ID'}, $row->{'QUALITY'} ) if $worth[1] < $row->{'QUALITY'};
			}

			# If not over, we compare our quality with the one of the worth on this subnet.
			# If it is better more than one, we replace it
			if(@worth){
				if(($quality < $worth[1] and (($worth[1]-$quality)>$ENV{'OCS_OPT_IPDISCOVER_BETTER_THRESHOLD'})) or $over){
					# Compare to the current and replace it if needed
					if(!$dbh->do('UPDATE devices SET HARDWARE_ID=? WHERE HARDWARE_ID=? AND NAME="IPDISCOVER"', {}, $DeviceID, $worth[0])){
						return 1;
					}
					$dbh->commit;
					&_log(1001,'ipdiscover',($over?'over':'better')."($_->{IPSUBNET})") if $ENV{'OCS_OPT_LOGLEVEL'};
					return 0;
				}
			}
		}else{
				next;
		}
	}
	return 0;
}
1;
