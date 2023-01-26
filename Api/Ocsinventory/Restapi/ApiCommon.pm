package Api::Ocsinventory::Restapi::ApiCommon;

# For dev purpose only
# use lib "/usr/local/share/OCSInventory-Server/";
# use Data::Dumper;

# External imports
use DBI;
use Switch;
use Mojo::JSON qw(decode_json encode_json);

# Basics use for Common Sub
use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Server::Constants;
use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Internals;

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

  # Switch depending on the error code
	switch ($err_code) {
		case 001		{ print "Arguments missing" }
		case 002		{ print "Arguments not valid" }
		case 003		{ print "Function arguments not valid ..." }
		case 004		{ print "Arguments ..." }
		else		{ print "Unknown error" }
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
        if($operator == "like" || $operator == "not like") {
            $value = "%$value%";
        }
        $query_data .= " AND $where $operator ?";
        
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

    $query .= " LIMIT $limit OFFSET $start";

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

    $query_data = "SELECT * FROM $table_name WHERE $ref_column = ?";
    push @args, $condition;

    if($where ne "" && $operator ne "" && $value ne "") {
        if($operator == "like" || $operator == "not like") {
            $value = "%$value%";
        }
        $query_data .= " AND $where $operator ?";
        
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

    $start =~ s/\D//g;
    $limit =~ s/\D//g;

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

    my ($query, $start, $limit, @args) = @_;

    my $database = api_database_connect();

    if($start ne "" && $limit ne ""){
        $start =~ s/\D//g;
        $limit =~ s/\D//g;
        $query .= "LIMIT $limit OFFSET $start";
    }

    $items = $database->selectall_arrayref(
            $query,
            { Slice => {} },
            @args
        );

    return $items;

}

# Format search depending on url parmeters
# ATM : only on main hardware table
sub format_query_for_computer_search{

  my (@args_array) = @_;

  my $query_string = "SELECT ID from hardware WHERE";
  my @args;
  my $start = "";
  my $limit = "";


  foreach my $hash_ref (@args_array) {
      foreach (keys %{$hash_ref}) {
        if(lc($_) eq "limit"){
          $limit = ${$hash_ref}{$_};
        }elsif (lc($_) eq "start"){
          $start = ${$hash_ref}{$_};
        }else{
          $field_name = $_;
          $field_name =~ tr/a-zA-Z//dc ;
          $query_string .= " $field_name = ? AND";
          push @args, ${$hash_ref}{$_};
        }
      }
  }

  $query_string = substr($query_string, 0, -3);

  return execute_custom_request($query_string, $start, $limit, @args);

}

1;
