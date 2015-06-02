###############################################################################
## OCSINVENTORY-NG 
## Copyleft Pascal DANEK 2005
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
package Apache::Ocsinventory::Server::System;

use Apache::Ocsinventory::Server::System::Config;

use strict;

BEGIN{
  if($ENV{'OCS_MODPERL_VERSION'} == 1){
    require Apache::Ocsinventory::Server::Modperl1;
    Apache::Ocsinventory::Server::Modperl1->import();
  }elsif($ENV{'OCS_MODPERL_VERSION'} == 2){
    require Apache::Ocsinventory::Server::Modperl2;
    Apache::Ocsinventory::Server::Modperl2->import();
  }
  &check_config();
}

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  _log
  _debug
  _lock
  _unlock
  _send_file
/;

our %EXPORT_TAGS = (
  'server' => [ 
    qw/
    _get_xml_parser_opt
    _get_sys_options
    _database_connect
    _end
    _check_deviceid
    _log
    _debug
    _lock
    _unlock
    _send_file
    _inflate
    _get_spec_params
    _get_spec_params_g
    /
  ]
);

our @EXPORT_OK = (
  qw /
  _log 
  _lock
  _modules_get_request_handler
  _modules_get_pre_inventory_options
  _modules_get_post_inventory_options
  _modules_get_prolog_readers
  _modules_get_prolog_writers
  _modules_get_duplicate_handlers
  /
);

Exporter::export_ok_tags('server');

use Apache::Ocsinventory::Server::Constants;

sub _get_sys_options{

  return 0 if $ENV{OCS_OPT_OPTIONS_NOT_OVERLOADED};

  # Wich options enabled ?
  #############
  # We read the table config looking for the ivalues of these options
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $row;
  my $request = $dbh->prepare('SELECT * FROM config');
  $request->execute;

  # read options defined in ocs GUI
  while($row=$request->fetchrow_hashref){
    for( keys %CONFIG ){
      if( $row->{'NAME'} eq $_){
          $ENV{'OCS_OPT_'.$_} = $row->{ $CONFIG{$_}->{type} };
      }
    }
  }
  $request->finish;
  0;
}

# Try other compress algorythm
sub _inflate{
  my @inflate_subs = (
    # gzip file content
    sub {  my $data_ref = shift; 
      if(my $result = Compress::Zlib::memGunzip( ${$data_ref})){
        $Apache::Ocsinventory::CURRENT_CONTEXT{'DEFLATE_SUB'} = \&Compress::Zlib::memGzip;
        &_log(321,'system', 'gzip') if $ENV{'OCS_OPT_LOGLEVEL'};
        return $result; 
      }
      undef;
    },
    sub {
      my $ref = shift;
      if($$ref =~ /^<\?xml/i){
        $Apache::Ocsinventory::CURRENT_CONTEXT{'DEFLATE_SUB'} = sub {return $_[0]};
        &_log(321,'system', 'not_deflated') if $ENV{'OCS_OPT_LOGLEVEL'};
        return $$ref;
      }
      undef;
        }
  );
  my $data_ref = shift;
  my $inflated_ref = shift;
  
  for( @inflate_subs ){
    last if( $$inflated_ref = &{$_}($data_ref) );
  };
  1;
}

# Database connection
sub _database_connect{
  my $mode = shift;
  my %params;
  my ($host, $database, $port, $user, $password, $params);
  
  if($mode eq 'write'){
    ($host, $database, $port, $user, $password) = ( $ENV{'OCS_DB_HOST'}, $ENV{'OCS_DB_NAME'}, $ENV{'OCS_DB_PORT'}, 
      $ENV{'OCS_DB_USER'}, $Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'}->dir_config('OCS_DB_PWD') );
  }
  # Local Mode
  elsif($mode eq 'local'){
    ($host, $port, $user, $password) = ( $ENV{'OCS_DB_HOST'}, $ENV{'OCS_DB_PORT'}, 
      $ENV{'OCS_DB_USER'}, $Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'}->dir_config('OCS_DB_PWD') );
    $database = $ENV{'OCS_DB_LOCAL'}||$ENV{'OCS_DB_NAME'};
  }
  # Slave mode
  elsif($mode eq 'read'){
    if($ENV{'OCS_DB_SL_HOST'}){
      $host = $ENV{'OCS_DB_SL_HOST'};
      $database = $ENV{'OCS_DB_SL_NAME'}||'ocsweb';
      $port = $ENV{'OCS_DB_SL_PORT'}||'3306';
      $user = $ENV{'OCS_DB_SL_USER'};
      $password  = $Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'}->dir_config('OCS_DB_SL_PWD');
    }
    else{
      $host = $ENV{'OCS_DB_HOST'};
      $database = $ENV{'OCS_DB_NAME'}||'ocsweb';
      $port = $ENV{'OCS_DB_PORT'}||'3306';
      $user = $ENV{'OCS_DB_USER'};
      $password  = $Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'}->dir_config('OCS_DB_PWD');
    }
  }
  else{
    &_log(521,'database_connect', 'invalid_mode') if $ENV{'OCS_OPT_LOGLEVEL'};
    return undef;
  }

  $params{'AutoCommit'} = 0;
  $params{'PrintError'} = $ENV{'OCS_OPT_DBI_PRINT_ERROR'};
  # Optionnaly a mysql socket different than the client's built in
  $params{'mysql_socket'} = $ENV{'OCS_OPT_DBI_MYSQL_SOCKET'} if $ENV{'OCS_OPT_DBI_MYSQL_SOCKET'};

  # Connection...
  my $dbh = DBI->connect("DBI:mysql:database=$database;host=$host;port=$port", $user, $password, \%params);
  $dbh->do("SET NAMES 'utf8'") if($dbh && $ENV{'OCS_OPT_UNICODE_SUPPORT'});
  return $dbh;
}

sub _check_deviceid{
  my $DeviceID = shift;

  # If we do not find it
  unless(defined($DeviceID)){
    return(1);
  }

  # If it is not conform
  unless($DeviceID=~/^.+-\d{4}(?:-\d{2}){5}$/){
    return(1);
  }
  0;
}

sub _lock{
   my $device = shift;
   my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'} || shift;
  
  if($dbh->do('INSERT INTO locks(HARDWARE_ID, ID, SINCE) VALUES(?,?,NULL)', {} , $device, $$ )){
    $Apache::Ocsinventory::CURRENT_CONTEXT{'LOCK_FL'} = 1 
      if $device eq ${Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'}};
    return(0);
  }else{
    if( $ENV{'OCS_OPT_LOCK_REUSE_TIME'} ){
      if( $dbh->do( 'SELECT * FROM locks  WHERE HARDWARE_ID=? AND (UNIX_TIMESTAMP()-UNIX_TIMESTAMP(SINCE))>?', 
        {}, $device, $ENV{'OCS_OPT_LOCK_REUSE_TIME'} ) != '0E0' ) {                      
        &_log(516,'lock', 'reuse_lock') if $ENV{'OCS_OPT_LOGLEVEL'};
        if( $dbh->do('UPDATE locks SET ID=? WHERE HARDWARE_ID=?', {}, $$, $device) ){
          $Apache::Ocsinventory::CURRENT_CONTEXT{'LOCK_FL'} = 1;
          return 0;
        }
        else{
          &_log(516,'lock', 'failed') if $ENV{'OCS_OPT_LOGLEVEL'};
          return 1;
        }
      }
    }
    return 1;
  }
}


sub _unlock{
  my $device = shift;
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'} || shift;

  if($dbh->do('DELETE FROM locks WHERE HARDWARE_ID=? AND ID=?', {}, $device, $$)){
    $Apache::Ocsinventory::CURRENT_CONTEXT{'LOCK_FL'} = 0 if $device eq ${Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'}};
    return(0);
  }else{
    return(1);
  }
}

sub _log{
  my $code = shift;
  my $phase = shift;
  my $comment = shift;
  my $DeviceID = $Apache::Ocsinventory::CURRENT_CONTEXT{'DEVICEID'}||'NA';
  my $ipaddress = $Apache::Ocsinventory::CURRENT_CONTEXT{'IPADDRESS'}||'??';
  our $LOG;
  
  if(!$LOG){
    open LOG, '>>'.$ENV{'OCS_OPT_LOGPATH'}.'/activity.log' or die "Failed to open log file : $! ($ENV{'OCS_OPT_LOGPATH'})\n";
    # We don't want buffer, so we allways flush the handles
    select(LOG);
    $|=1;
    $LOG = \*LOG;
  }
    
  print $LOG localtime().";$$;$code;$DeviceID;$ipaddress;".&_get_http_header('User-agent',$Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'}).";$phase;".($comment?$comment:"")."\n";
}

# Subroutine called at the end of execution
sub _end{
  my $ret = shift;
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $DeviceID = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};

  #Non-transactionnal table
  &_unlock($DeviceID) if $Apache::Ocsinventory::CURRENT_CONTEXT{'LOCK_FL'};
  
  if( $ret==APACHE_SERVER_ERROR ){ 
    &_log(515,'end', 'error') if $ENV{'OCS_OPT_LOGLEVEL'};
    $dbh->rollback;
  }
  elsif( $ret eq APACHE_BAD_REQUEST ){
    &_log(515,'end', 'bad_request') if $ENV{'OCS_OPT_LOGLEVEL'};
  }
  else{
    $dbh->commit;
  }
  close(our $LOG) && undef $LOG if defined $LOG;
  $dbh->disconnect;
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

# Retrieve options with preinventory handler
sub _modules_get_pre_inventory_options{
  return(&_modules_search(undef, 'HANDLER_PRE_INVENTORY'));
}

# Retrieve options with postinventory handler
sub _modules_get_post_inventory_options{
  return(&_modules_search(undef, 'HANDLER_POST_INVENTORY'));
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

sub _get_xml_parser_opt{
  my $hash_ref = shift; 
  $hash_ref->{'SuppressEmpty'} = 1;
  $hash_ref->{'ForceArray'} = [];
  @{$hash_ref->{'ForceArray'}} = &_get_xml_parser_opt_force_array();
}

sub _get_xml_parser_opt_force_array{
  my @ret;
# Core
  push @ret, @{$Apache::Ocsinventory::Server::Inventory::XML_PARSER_OPT{'ForceArray'}};
  
# Options
  push @ret, @Apache::Ocsinventory::XMLParseOptForceArray;
  for my $module (@{$Apache::Ocsinventory::OPTIONS_STRUCTURE}){
    push @ret, @{$module->{'XML_PARSER_OPT'}->{'ForceArray'}} if $module->{'XML_PARSER_OPT'}->{'ForceArray'}=~/ARRAY/;
  }
  return @ret;
}

#
sub _send_file{

  # We want to know if the file is available
  my $context = shift;
  my $request;
  my $row;
  my $r = $Apache::Ocsinventory::CURRENT_CONTEXT{'APACHE_OBJECT'};
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};

  if($context eq 'deploy'){
    my $file = shift;
    $request=$dbh->prepare('SELECT CONTENT FROM deploy WHERE NAME=?');
    $request->execute($file);

    # If not, we return a bad request and log the event
    unless($request->rows){
      &_log(511,'deploy','no_file') if $ENV{'OCS_OPT_LOGLEVEL'};
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
      &_log(302,'deploy','file_transmitted') if $ENV{'OCS_OPT_LOGLEVEL'};
      return APACHE_OK;
    }

  }
}

sub _get_spec_params{
  my $hardwareId = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
  return &_params_from_devices($hardwareId);
}

sub _get_spec_params_g{
  my $groups = $Apache::Ocsinventory::CURRENT_CONTEXT{'MEMBER_OF'};
  my %result;
  for(@$groups){
    $result{$_} = {&_params_from_devices($_)} ;
  }
  return %result;
}

sub _params_from_devices{
  my $hardwareId = shift;
  my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
  my $sth;
  my $row;
  my %result;
  
  $sth = $dbh->prepare('SELECT NAME,IVALUE,TVALUE FROM devices WHERE HARDWARE_ID=?');
  $sth->execute($hardwareId);
  while( $row = $sth->fetchrow_hashref()){
    if(exists($result{ $row->{'NAME'} })){
      if($result{ $row->{'NAME'} } =~ /ARRAY/){
        push @{$result{$row->{'NAME'}}}, 
          { 'IVALUE' => $row->{'IVALUE'}, 'TVALUE' => $row->{'TVALUE'} };
      }
      else{
        my $temp = $result{ $row->{'NAME'} };
        $result{ $row->{'NAME'} } = [];
        push @{$result{$row->{'NAME'}}}, $temp;
      }
    }
    else{
      $result{ $row->{'NAME'} } = { 
        'IVALUE' => $row->{'IVALUE'},
        'TVALUE' => $row->{'TVALUE'}
      };
    }
  }
  return %result;
}
1;
