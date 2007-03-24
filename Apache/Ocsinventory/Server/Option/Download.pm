################################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://ocsinventory.sourceforge.net
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::Option::Download;

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

use Apache::Ocsinventory::Server::System;
use Apache::Ocsinventory::Server::Communication;
use Apache::Ocsinventory::Server::Constants;

# Initialize option
push @{$Apache::Ocsinventory::OPTIONS_STRUCTURE},{
	'NAME' => 'DOWNLOAD',
	'HANDLER_PROLOG_READ' => undef,
	'HANDLER_PROLOG_RESP' => \&download_prolog_resp,
	'HANDLER_PRE_INVENTORY' => \&download_pre_inventory,
	'HANDLER_POST_INVENTORY' => undef,
	'REQUEST_NAME' => 'DOWNLOAD',
	'HANDLER_REQUEST' => \&download_handler,
	'HANDLER_DUPLICATE' => \&download_duplicate,
	'TYPE' => OPTION_TYPE_ASYNC,
	'XML_PARSER_OPT' => {
			'ForceArray' => ['PACKAGE']
	}
};

# Default
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_DOWNLOAD'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_DOWNLOAD_CYCLE_LATENCY'} = 60;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_DOWNLOAD_FRAG_LATENCY'} = 10;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_DOWNLOAD_PERIOD_LATENCY'} = 0;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_DOWNLOAD_PERIOD_LENGTH'} = 10;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_DOWNLOAD_TIMEOUT'} = 31;
$Apache::Ocsinventory::OPTIONS{'OCS_OPT_DOWNLOAD_GROUPS_TRACE_EVENTS'} = 0;

sub download_prolog_resp{
	
	my $current_context = shift;
	my $resp = shift;
	
	my $dbh = $current_context->{'DBI_HANDLE'};
	my $groups = $current_context->{'MEMBER_OF'};
	my $hardware_id = $current_context->{'DATABASE_ID'};
	
	my($pack_sql, $hist_sql);
	my($pack_req, $hist_req);
	my($hist_row, $pack_row);
	my(@packages, @history, @dont_repeat);
	my $blacklist;
	
	push @packages,{
		'TYPE' 			=> 'CONF',
		'ON' 			=> $ENV{'OCS_OPT_DOWNLOAD'},
		'TIMEOUT' 		=> $ENV{'OCS_OPT_DOWNLOAD_TIMEOUT'},
		'PERIOD_LENGTH' 	=> $ENV{'OCS_OPT_DOWNLOAD_PERIOD_LENGTH'},
		'PERIOD_LATENCY' 	=> $ENV{'OCS_OPT_DOWNLOAD_PERIOD_LATENCY'},
		'FRAG_LATENCY' 		=> $ENV{'OCS_OPT_DOWNLOAD_FRAG_LATENCY'},
		'CYCLE_LATENCY' 	=> $ENV{'OCS_OPT_DOWNLOAD_CYCLE_LATENCY'}
	};
	
	if($ENV{'OCS_OPT_DOWNLOAD'}){
	
# If this option is set, we send only the needed package to the agent
# Can be a performance issue
# Agents prior to 4.0.3.0 do not send history data
		$hist_sql = q {
			SELECT PKG_ID,ID
			FROM download_history
			WHERE HARDWARE_ID=?
		};
		$hist_req = $dbh->prepare( $hist_sql );
		$hist_req->execute( $hardware_id );
		
		while( $hist_row = $hist_req->fetchrow_hashref ){
			push @history, $hist_row->{'PKG_ID'};
		}
		
		if( $current_context->{'EXIST_FL'} && $ENV{'OCS_OPT_ENABLE_GROUPS'} && @$groups ){
			$pack_sql =  q {
				SELECT IVALUE,FILEID,INFO_LOC,PACK_LOC,CERT_PATH,CERT_FILE
				FROM devices,download_enable
				WHERE HARDWARE_ID=? 
				AND devices.NAME='DOWNLOAD'
				AND download_enable.ID=devices.IVALUE
			};
			
			my $verif_affected = 'SELECT HARDWARE_ID FROM devices WHERE HARDWARE_ID=? AND IVALUE=? AND NAME="DOWNLOAD"';
			my $trace_event = 'INSERT INTO devices(HARDWARE_ID,NAME,IVALUE,TVALUE) VALUES(?,"DOWNLOAD",?,NULL)';
			$pack_req = $dbh->prepare( $pack_sql );
						
			for( @$groups ){
				$pack_req->execute( $_ );
				while( $pack_row = $pack_req->fetchrow_hashref ){
					my $fileid = $pack_row->{'FILEID'};
					if( grep /^$fileid$/, @history or grep /^$fileid$/, @dont_repeat){
						next;
					}

					if( $ENV{'OCS_OPT_DOWNLOAD_GROUPS_TRACE_EVENTS'} ){
						# We verify if the package is already traced and not already in history
						if( $dbh->do($verif_affected ,{}, $hardware_id, $pack_row->{'IVALUE'})==0E0 ){
							$dbh->do($trace_event, {}, $hardware_id, $pack_row->{'IVALUE'})
						}
					}
					else{
						push @packages,{
							'TYPE'		=> 'PACK',
							'ID'		=> $pack_row->{'FILEID'},
							'INFO_LOC'	=> $pack_row->{'INFO_LOC'},
							'PACK_LOC'	=> $pack_row->{'PACK_LOC'},
							'CERT_PATH'	=> $pack_row->{'CERT_PATH'}?$pack_row->{'CERT_PATH'}:'INSTALL_PATH',
							'CERT_FILE'	=> $pack_row->{'CERT_FILE'}?$pack_row->{'CERT_FILE'}:'INSTALL_PATH'
						};
					}
					push @dont_repeat, $fileid;
				}
			}
		}
	
		$pack_sql =  q {
			SELECT FILEID, INFO_LOC, PACK_LOC, CERT_PATH, CERT_FILE
			FROM devices,download_enable 
			WHERE HARDWARE_ID=? 
			AND devices.IVALUE=download_enable.ID 
			AND devices.NAME='DOWNLOAD'
			AND (TVALUE IS NULL OR TVALUE='NOTIFIED')
		};
			
		$pack_req = $dbh->prepare( $pack_sql );
		# Retrieving packages associated to the current device
		$pack_req->execute( $hardware_id );
		
		
		while($pack_row = $pack_req->fetchrow_hashref){
			my $fileid = $pack_row->{'FILEID'};
			if( grep /^$fileid$/, @history or grep /^$fileid$/, @dont_repeat){
				next;
			}
			push @packages,{
				'TYPE'		=> 'PACK',
				'ID'		=> $pack_row->{'FILEID'},
				'INFO_LOC'	=> $pack_row->{'INFO_LOC'},
				'PACK_LOC'	=> $pack_row->{'PACK_LOC'},
				'CERT_PATH'	=> $pack_row->{'CERT_PATH'}?$pack_row->{'CERT_PATH'}:'INSTALL_PATH',
				'CERT_FILE'	=> $pack_row->{'CERT_FILE'}?$pack_row->{'CERT_FILE'}:'INSTALL_PATH'
			};
			push @dont_repeat, $fileid;
		}
		$dbh->do(q{ UPDATE devices SET TVALUE='NOTIFIED' WHERE NAME='DOWNLOAD' AND HARDWARE_ID=? AND TVALUE IS NULL }
		,{}, $current_context->{'DATABASE_ID'}) if $pack_req->rows;
	}

	push @{ $resp->{'OPTION'} },{
		'NAME' 	=> ['DOWNLOAD'],
		'PARAM' => \@packages
	};
		
	return 0;
}

sub download_pre_inventory{
	return unless $ENV{'OCS_OPT_DOWNLOAD'};

	my $current_context = shift;
	my $dbh = $current_context->{'DBI_HANDLE'};
	my $hardware_id = $current_context->{'DATABASE_ID'};
	my $result = $current_context->{'XML_ENTRY'};
		
	$dbh->do('DELETE FROM download_history WHERE HARDWARE_ID=(?)', {}, $hardware_id);
	# Reference to the module part

	my $base = $result->{'CONTENT'}->{'DOWNLOAD'}->{'HISTORY'}->{'PACKAGE'};
	my $sth = $dbh->prepare('INSERT INTO download_history(HARDWARE_ID, PKG_ID) VALUE(?,?)');
	for( @{ $base }) {
		$sth->execute( $hardware_id, $_->{'ID'});
	}
	0;
}

sub download_handler{
	# Initialize data
	my $current_context = shift;
	
	my $dbh		= $current_context->{'DBI_HANDLE'};
	my $result	= $current_context->{'XML_ENTRY'};
	my $r 		= $current_context->{'APACHE_OBJECT'};
	my $hardware_id = $current_context->{'DATABASE_ID'};

	my $request;
	
	$request = $dbh->prepare('
		SELECT ID FROM download_enable 
		WHERE FILEID=? 
		AND ID IN (SELECT IVALUE FROM devices WHERE NAME="download" AND HARDWARE_ID=?)');
	$request->execute( $result->{'ID'}, $hardware_id);
	
	if(my $row = $request->fetchrow_hashref()){
		$dbh->do('UPDATE devices SET TVALUE=? 
		WHERE NAME="DOWNLOAD" 
		AND HARDWARE_ID=? 
		AND IVALUE=?',
		{}, $result->{'ERR'}?$result->{'ERR'}:'UNKNOWN_CODE', $hardware_id, $row->{'ID'} ) 
			or return(APACHE_SERVER_ERROR);
		&_set_http_header('content-length', 0, $r);
		&_send_http_headers($r);
		return(APACHE_OK);
	}else{
		&_log(2501, 'download');
		&_set_http_header('content-length', 0, $r);
		&_send_http_headers($r);
		return(APACHE_OK);
	}
}

sub download_duplicate {
	my $current_context = shift;
	my $device = shift;
	
	my $dbh = $current_context->{'DBI_HANDLE'};

	# If we encounter problems, it aborts whole replacement
	return $dbh->do('DELETE FROM download_history WHERE HARDWARE_ID=?', {}, $device);
}

1;

