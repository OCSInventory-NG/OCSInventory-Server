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

our %CURRENT_CONTEXT;

sub _init_sys_options{
	# If there is no defined value in ENV for an option, we define it with its default
	for(keys(%Apache::Ocsinventory::OPTIONS)){
		if(!defined($ENV{$_})){
			$ENV{$_} = $Apache::Ocsinventory::OPTIONS{$_};
		}
	}
}

sub _get_sys_options{

	# Wich options enabled ?
	#############
	# We read the table config looking for the ivalues of these options
 	my $dbh = $CURRENT_CONTEXT{'DBI_HANDLE'};
	my %options = %Apache::Ocsinventory::OPTIONS;
	my $row;
	my $request = $dbh->prepare('SELECT * FROM config');
	$request->execute;

	&_init_sys_options;

	# read options defined in ocs GUI
	while($row=$request->fetchrow_hashref){
		for(keys(%options)){
			if('OCS_OPT_'.$row->{'NAME'} eq $_){
				$ENV{$_} = $row->{'IVALUE'};
				next;
			}
		}
	}
	$request->finish;
	0;
}

# Database connection
sub _database_connect{

	my $Database;
	my $Port;
	my $Host;
	my $DBuser;
	my $DBpassword;

	# Get the variables declared in httpd.conf
	# Login
	$DBuser = $ENV{'OCS_DB_USER'};
	# Password
	$DBpassword = $CURRENT_CONTEXT{'APACHE_OBJECT'}->dir_config('OCS_DB_PWD');
	# Port
	$Port = $ENV{'OCS_DB_PORT'};
	# Host
	$Host = $ENV{'OCS_DB_HOST'};
	#
	# To manage A specific database for the non connected computers
	# If no database specified, we take the httpd DBNAME one
	if(&_get_http_header('User-agent',$CURRENT_CONTEXT{'APACHE_OBJECT'}) =~ /local/i){
	    if($ENV{'OCS_DB_LOCAL'}){
	      $Database = $ENV{'OCS_DB_LOCAL'};
	    }else{
	      $Database = $ENV{'OCS_DB_NAME'};
	    }
	}else{
	    $Database = $ENV{'OCS_DB_NAME'};
	}

	# Connection...
	return DBI->connect("DBI:mysql:database=$Database;host=$Host;port=$Port", $DBuser, $DBpassword, { 'AutoCommit' => 0 });
}

sub _check_deviceid{
	my $DeviceID = shift;

	# If we do not find it
	unless(defined($DeviceID)){
		return(1);
	}

	# If it is not conform
	unless($DeviceID=~/.+-\d{4}(?:-\d{2}){5}/){
		return(1);
	}
	0;
}

sub _lock{
 	my $device = shift;
	if(${CURRENT_CONTEXT{'DBI_HANDLE'}}->do('INSERT INTO locks(HARDWARE_ID, SINCE) VALUES(?,NULL)', {} , $device )){
		return(0);
	}else{
		return(1);
	}
}


sub _unlock{
	my $device = shift;
	if(${CURRENT_CONTEXT{'DBI_HANDLE'}}->do('DELETE FROM locks WHERE HARDWARE_ID=?', {}, $device)){
		return(0);
	}else{
		return(1);
	}
}

sub _log{
	my $code = shift;
	my $phase = shift;
	my $comment = shift;
	my $DeviceID = $CURRENT_CONTEXT{'DEVICEID'};

	print LOG localtime().";$code;$DeviceID;".$ENV{'REMOTE_ADDR'}.";".&_get_http_header('User-agent',$CURRENT_CONTEXT{'APACHE_OBJECT'}).";$phase;".($comment?$comment:"")."\n";
}

# Subroutine called at the end of execution
sub _end{
	
	my $ret = shift;
 	my $dbh = $CURRENT_CONTEXT{'DBI_HANDLE'};
	my $DeviceID = $CURRENT_CONTEXT{'DATABASE_ID'};

	if($ret == APACHE_SERVER_ERROR){
		&_log(515,"end") if $ENV{'OCS_OPT_LOGLEVEL'};
		$dbh->rollback;
	}else{
		$dbh->commit;
	}
	#Non-transactionnal table
	&_unlock($DeviceID) if $CURRENT_CONTEXT{'LOCK_FL'};
	close(LOG);
	#$dbh->disconnect;
	return $ret;

}

# Retrieve option request handler
sub _modules_get_request_handler{
	my $request = shift;
	my %search = (
		'REQUEST_NAME' => $request
	);
	my @ret = &_modules_search(\%search, 'HANDLER_REQUEST');
	return($ret[0]);
}

# Retrieve options with inventory handler
sub _modules_get_inventory_options{
	return(&_modules_search(undef, 'HANDLER_INVENTORY'));
}

# Retrieve options with prolog_read
sub _modules_get_prolog_readers{
	return(&_modules_search(undef, 'HANDLER_PROLOG_READ'));
}

# Retrieve options with prolog_resp
sub _modules_get_prolog_writers{
	return(&_modules_search(undef, 'HANDLER_PROLOG_RESP'));
}

# Retrieve duplicate handlers
sub _modules_get_duplicate_handlers{
	return(&_modules_search(undef, 'HANDLER_DUPLICATE'));
}

# Read options structures
sub _modules_search{
	# Take a hash ref and return an array
	# The hash indicate the desire handler is the second arg

	my $search = shift;
	my $handler = shift;

	my @ret;
	my $count;

	my $module;
	my $search_key;
	my $module_key;

	for $module (@{$Apache::Ocsinventory::OPTIONS_STRUCTURE}){
		$count = 0;
		if($search){
			for $search_key (keys(%$search)){

				if($search_key eq 'REQUEST_NAME'){

					$count ++ if defined($module->{$search_key}) and ($module->{$search_key} eq $search->{$search_key});

				}elsif($search_key eq 'TYPE'){

					$count ++ if defined($module->{$search_key}) and ($module->{$search_key} == $search->{$search_key});
				}

			}
			if($count == keys(%$search)){
				push @ret, $module->{$handler} if $module->{$handler};
				$count = 0;
			}
		}else{
			push @ret, $module->{$handler} if $module->{$handler};
		}
	}

	if(@ret){
		return(@ret);
	}else{
		return(0);
	}
}

#
sub _send_file{

	# We want to know if the file is available
	my $context = shift;
	my $request;
	my $row;
	my $r = $CURRENT_CONTEXT{'APACHE_OBJECT'};
	my $dbh = $CURRENT_CONTEXT{'DBI_HANDLE'};

	if($context eq 'deploy'){
		my $file = shift;
		$request=$dbh->prepare('SELECT CONTENT FROM deploy WHERE NAME=?');
		$request->execute($file);

		# If not, we return a bad request and log the event
		unless($request->rows){
			&_log(511,'deploy') if $ENV{'OCS_OPT_LOGLEVEL'};
			return APACHE_BAD_REQUEST;
		}else{
			# We extract the file and send it
			$row = $request->fetchrow_hashref();
			# We force this content type to avoid the direct interpretation of, for example, a plain text file
			&_set_http_header('Cache-control' => $ENV{'OCS_OPT_PROXY_REVALIDATE_DELAY'},$r);
			&_set_http_header('Content-length' => length($row->{'CONTENT'}),$r);
			&_set_http_content_type('Application/octet-stream',$r);
			&_send_http_headers($r);
			$r->print($row->{'CONTENT'});

			# We log it
			&_log(302,'deploy') if $ENV{'OCS_OPT_LOGLEVEL'};
			return APACHE_OK;
		}

	}elsif($context eq 'update'){


		my $platform = shift;
		my $name = shift;
		my $version = shift;

		unless($platform and $name and $version){
			&_log(512,'update') if $ENV{'OCS_OPT_LOGLEVEL'};
			return APACHE_BAD_REQUEST;
		}

		$request = $dbh->prepare('SELECT CONTENT FROM files WHERE OS=? AND NAME=? AND VERSION=?');
		$request->execute($platform, $name, $version);

		unless($request->rows){
			$request->finish;
			&_log(512,'deploy') if $ENV{'OCS_OPT_LOGLEVEL'};
			return APACHE_BAD_REQUEST;
		}else{
			$row=$request->fetchrow_hashref();
			# Sending
			$row->{'CONTENT'}=Compress::Zlib::compress($row->{'CONTENT'}) or &_log(506,'update'),return APACHE_BAD_REQUEST;
			&_set_http_content_type('Application/octet-stream',$r);
			&_set_http_header('Cache-control', $ENV{'OCS_OPT_PROXY_REVALIDATE_DELAY'},$r);
			&_set_http_header('Content-length', length($row->{'CONTENT'}),$r);
			&_send_http_headers($r);
			$r->print($row->{'CONTENT'});
			&_log(305,'update') if $ENV{'OCS_OPT_LOGLEVEL'};
			$request->finish;
			return APACHE_OK;
		}
	}
}
1;
