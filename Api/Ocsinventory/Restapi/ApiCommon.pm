package Api::Ocsinventory::Restapi::ApiCommon;

# For dev purpose only
# use lib "/usr/local/share/OCSInventory-Server/";
# use Data::Dumper;

# External imports
use DBI;
use Mojo::JSON qw(decode_json encode_json);

# Basics use for Common Sub
use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Internals;

my %SOFTWARE_FILTER_COLUMNS = (
    id => 's.ID',
    hardware_id => 's.HARDWARE_ID',
    name => 'n.NAME',
    publisher => 'p.PUBLISHER',
    version => 'v.VERSION',
    folder => 's.FOLDER',
    comments => 's.COMMENTS',
    filename => 's.FILENAME',
    filesize => 's.FILESIZE',
    source => 's.SOURCE',
    guid => 's.GUID',
    language => 's.LANGUAGE',
    installdate => 's.INSTALLDATE',
    bitswidth => 's.BITSWIDTH',
);

# Connect api to ocs database
sub api_database_connect{

    my $dbHost;
    my $dbName;
    my $dbPort;
    my $dbUser;
    my $dbPwd;
    my $dbSslEnabled;
    my $dbSslClientCert;
    my $dbSslClientKey;
    my $dbSslCaCert;
    my $dbSslMode;

    # Retrieve env var
    $dbHost = $ENV{'OCS_DB_HOST'};
    $dbName = $ENV{'OCS_DB_LOCAL'}||$ENV{'OCS_DB_NAME'}||'ocsweb';
    $dbPort = $ENV{'OCS_DB_PORT'}||'3306';
    $dbUser = $ENV{'OCS_DB_USER'};
    $dbPwd  = $ENV{'OCS_DB_PWD'};

    $dbSslEnabled       = $ENV{'OCS_DB_SSL_ENABLED'} || 0;
    $dbSslClientKey     = $ENV{'OCS_DB_SSL_CLIENT_KEY'};
    $dbSslClientCert    = $ENV{'OCS_DB_SSL_CLIENT_CERT'};
    $dbSslCaCert        = $ENV{'OCS_DB_SSL_CA_CERT'};
    $dbSslMode          = $ENV{'OCS_DB_SSL_MODE'} || 'SSL_MODE_PREFERRED';

    my $sslMode = '';

    if( $dbSslEnabled == 1 )
    {
        if( $dbSslMode eq 'SSL_MODE_PREFERRED' )
        {
            $sslMode = ';mysql_ssl=1;mysql_ssl_optional=1';
        }
        elsif( $dbSslMode eq 'SSL_MODE_REQUIRED' )
        {
            $sslMode = ';mysql_ssl=1;mysql_ssl_verify_server_cert=0';
        }
        elsif( $dbSslMode eq 'SSL_MODE_STRICT' )
        {
            $sslMode = ';mysql_ssl=1;mysql_ssl_verify_server_cert=1';
        }
        else
        {
            $sslMode = ';mysql_ssl=1;mysql_ssl_optional=1';
        }
    }

    if( defined( $dbSslClientKey ) and defined( $dbSslClientCert ) and defined( $dbSslCaCert ) )
    {
        $sslMode .= ';mysql_ssl_client_key='.$dbSslClientKey.';mysql_ssl_client_cert='.$dbSslClientCert.';mysql_ssl_ca_file='.$dbSslCaCert;
    }

    # Connection...
    my $dbh = DBI->connect( "DBI:mysql:database=$dbName;host=$dbHost;port=$dbPort".$sslMode, $dbUser, $dbPwd, {RaiseError => 1, mysql_enable_utf8 => 1}) or die $DBI::errstr;

    return $dbh;

}

# Depending on input code, return error code
sub error_return{

  my ($err_code) = @_;

  if($err_code == 001){
    print "Arguments missing";
  }elsif($err_code == 002){
    print "Arguments not valid";
  }elsif($err_code == 003){
    print "Function arguments not valid ...";
  }elsif($err_code == 004){
    print "Arguments ...";
  }else{
    print "Unknown error";
  }

}

# Generate query based on datamap depending on computer id
sub generate_item_datamap_json{

    my ($item_type, $computer_id, $json_string, $specific_map, $where, $operator, $values) = @_;
    my $map_type;
    my $query_data;

    # Iterate on datamap
    while ( ($key, $value) = each %DATA_MAP )
    {

        $map_type = "computer";
        my $snmp_check = substr $key, 0, 4;
        if($snmp_check eq "snmp"){
            $map_type = "snmp";
        }

        # IF specific map key provided
        if($specific_map eq "" || $key eq $specific_map){
            if($key ne "hardware" && $key ne "snmp"){
                if( $map_type eq "snmp" && $item_type eq "snmp"){
                    # SNMP query processing
                    $query_data = get_item_table_informations($key, $computer_id, "SNMP_ID");
                    $$json_string{"$computer_id"}{"$key"} = $query_data;
                }elsif($map_type eq "computer" && $item_type eq "computer"){
                    # COMPUTER query processing
                    $query_data = get_item_table_informations($key, $computer_id, "HARDWARE_ID", $where, $operator, $values);
                    $$json_string{"$computer_id"}{"$key"} = $query_data;
                }
            }
        }
    }

    return $json_string;

}

# Generate query based on software depending on computer id
sub generate_item_software_json{

    my ($item_type, $computer_id, $json_string, $specific_map, $where, $operator, $value) = @_;
    my $query_data;
    my $database = api_database_connect();
    my @args = ();

    $query_data = "SELECT s.ID, s.HARDWARE_ID, 
                n.NAME, p.PUBLISHER, v.VERSION, 
                s.FOLDER, s.COMMENTS, s.FILENAME, 
                s.FILESIZE, s.SOURCE, s.GUID, s.LANGUAGE, 
                s.INSTALLDATE, s.BITSWIDTH
                FROM software s
                LEFT JOIN software_name n ON s.NAME_ID = n.ID
                LEFT JOIN software_publisher p ON s.PUBLISHER_ID = p.ID
                LEFT JOIN software_version v ON s.VERSION_ID = v.ID
                WHERE HARDWARE_ID = ?";

    @args = ($computer_id);

    if($where ne "" && $operator ne "" && $value ne "") {
        my $where_column = _software_filter_column($where);
        my $sql_operator = _sql_operator($operator);
        return [] unless defined($where_column) && defined($sql_operator);

        if($sql_operator eq "LIKE" || $sql_operator eq "NOT LIKE") {
            $value = "%$value%";
        }
        $query_data .= " AND $where_column $sql_operator ?";
        
        push @args, $value;
    }

    my $items = $database->selectall_arrayref(
        $query_data,
        { Slice => {} },
        @args
    );

    $$json_string{"$computer_id"}{"$specific_map"} = $items;

    return $json_string;

}

# Generate query based on all softwares, optionaly filter by software name
sub generate_item_all_softwares_json{

    my ($limit, $start, $soft) = @_;
    my $query;
    my $database = api_database_connect();
    my @args = ();

    $query = "SELECT sn.NAME,sp.PUBLISHER,sv.VERSION
    	FROM software_link AS soft
        LEFT JOIN software_name AS sn ON sn.id=soft.NAME_ID
        LEFT JOIN software_version AS sv ON sv.id=soft.VERSION_ID
        LEFT JOIN software_publisher AS sp ON sp.id=soft.PUBLISHER_ID";

    # Only find softwares starting by $soft
    if($soft ne "") {
        $query .= " WHERE sn.NAME LIKE ?";
        @args = ("$soft%");
    }

    $query .= _limit_offset_clause($limit, $start);

    my $items = $database->selectall_arrayref(
        $query,
        { Slice => {} },
        @args
    );

    return $items;

}

# Return table item data
sub get_item_table_informations{

    my ($table_name, $condition, $ref_column, $where, $operator, $value) = @_;
    my $database = api_database_connect();
    my $query_data;
    my @args = ();

    return [] unless _is_valid_table($table_name);
    return [] unless _is_valid_column($table_name, $ref_column);

    $query_data = "SELECT * FROM $table_name WHERE $ref_column = ?";
    push @args, $condition;

    if($where ne "" && $operator ne "" && $value ne "") {
        my $sql_operator = _sql_operator($operator);
        return [] unless defined($sql_operator) && _is_valid_column($table_name, $where);

        if($sql_operator eq "LIKE" || $sql_operator eq "NOT LIKE") {
            $value = "%$value%";
        }
        $query_data .= " AND $where $sql_operator ?";
        
        push @args, $value;
    }

    my $items = $database->selectall_arrayref(
        $query_data,
        { Slice => {} },
        @args
    );

    return $items;
}


# Get computers / snmp base informations
sub get_item_main_table_informations{

    my ($limit, $start, $item_type) = @_;
    my $items;

    my $database = api_database_connect();

    if($item_type eq "computer"){
        $item_type = "hardware";
    }elsif($item_type eq "snmp"){
        $item_type = "snmp";
    }else{
        return error_return(003);
    }

    $start = _unsigned_int($start, 0);
    $limit = _unsigned_int($limit, 0);

    if($limit > 0 && $start >= 0){
        $items = $database->selectall_arrayref(
            "SELECT * from $item_type LIMIT $limit OFFSET $start",
            { Slice => {} }
        );
    }else{
        # error handling here
        error_return(001);
    }

    return $items;

}


sub execute_custom_request{

    my ($query, $start, $limit, $orderby, @args) = @_;

    my $database = api_database_connect();

    if($orderby ne "") {
        $query .= $orderby;
    }

    if($start ne "" && $limit ne ""){
        $query .= _limit_offset_clause($limit, $start);
    }

    $items = $database->selectall_arrayref(
            $query,
            { Slice => {} },
            @args
        );

    return $items;

}

sub _unsigned_int {
    my ($value, $default) = @_;

    return $default unless defined($value) && $value =~ /\A\d+\z/;
    return int($value);
}

sub _limit_offset_clause {
    my ($limit, $start) = @_;

    $limit = _unsigned_int($limit, 0);
    $start = _unsigned_int($start, 0);

    return " LIMIT $limit OFFSET $start";
}

sub _sql_operator {
    my ($operator) = @_;

    return undef unless defined($operator);
    $operator = lc($operator);
    $operator =~ s/\A\s+|\s+\z//g;

    return '=' if $operator eq '=';
    return '!=' if $operator eq '!=' || $operator eq '<>';
    return '<' if $operator eq '<';
    return '>' if $operator eq '>';
    return '<=' if $operator eq '<=';
    return '>=' if $operator eq '>=';
    return 'LIKE' if $operator eq 'like';
    return 'NOT LIKE' if $operator eq 'not like';

    return undef;
}

sub _is_valid_table {
    my ($table_name) = @_;

    return 0 unless defined($table_name) && $table_name =~ /\A[A-Za-z_][A-Za-z0-9_]*\z/;
    return exists($Apache::Ocsinventory::Map::DATA_MAP{$table_name}) ? 1 : 0;
}

sub _is_valid_snmp_table {
    my ($table_name) = @_;

    return _is_valid_table($table_name) && $table_name =~ /\Asnmp/ ? 1 : 0;
}

sub _is_valid_column {
    my ($table_name, $column) = @_;

    return 0 unless _is_valid_table($table_name);
    return 0 unless defined($column) && $column =~ /\A[A-Za-z_][A-Za-z0-9_]*\z/;
    return 1 if lc($column) eq 'id';
    return 1 if uc($column) eq 'HARDWARE_ID';
    return 1 if uc($column) eq 'SNMP_ID';
    return exists($Apache::Ocsinventory::Map::DATA_MAP{$table_name}->{fields}->{uc($column)}) ? 1 : 0;
}

sub _software_filter_column {
    my ($column) = @_;

    return undef unless defined($column);
    return $SOFTWARE_FILTER_COLUMNS{lc($column)};
}

# Format search depending on url parmeters
# ATM : only on main hardware table
sub format_query_for_computer_search{

  my (@args_array) = @_;

  my $query_string = "SELECT ID from hardware WHERE";
  my @args;
  my $start = "";
  my $limit = "";
  my $orderby = "";

  foreach my $hash_ref (@args_array) {
      foreach (keys %{$hash_ref}) {
        if(lc($_) eq "limit"){
          $limit = ${$hash_ref}{$_};
        }elsif (lc($_) eq "start"){
          $start = ${$hash_ref}{$_};
        }elsif (lc($_) eq "orderby"){
            my ($field, $order) = split /;/, ${$hash_ref}{$_};
            $field =~ tr/a-zA-Z//dc ;
            $order =~ tr/a-zA-Z//dc ;
            $orderby = " ORDER BY $field $order ";
        }elsif (${$hash_ref}{$_} eq "null"){
          $field_name = $_;
          $field_name =~ tr/a-zA-Z//dc ;
          $query_string .= " $field_name IS NULL AND";
        }else{
          $field_name = $_;
          $field_name =~ tr/a-zA-Z//dc ;
          $query_string .= " $field_name = ? AND";
          push @args, ${$hash_ref}{$_};
        }
      }
  }

  # Exclude group from the result
  $query_string .= " deviceid <> ? AND";
  push @args, "_SYSTEMGROUP_";

  $query_string = substr($query_string, 0, -3);

  return execute_custom_request($query_string, $start, $limit, $orderby, @args);

}

1;
