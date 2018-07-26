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

    # Retrieve env var
    $dbHost = $ENV{'OCS_DB_HOST'};
    $dbName = $ENV{'OCS_DB_NAME'}||'ocsweb';
    $dbPort = $ENV{'OCS_DB_PORT'}||'3306';
    $dbUser = $ENV{'OCS_DB_USER'};
    $dbPwd  = $ENV{'OCS_DB_PWD'};

    # Connection...
    my $dbh = DBI->connect( "DBI:mysql:database=$dbName;host=$dbHost;port=$dbPort", $dbUser, $dbPwd, {RaiseError => 1}) or die $DBI::errstr;

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

    my ($item_type, $computer_id, $json_string, $specific_map) = @_;
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
                  $query_data = get_item_table_informations($key, $computer_id, "HARDWARE_ID");
                  $$json_string{"$computer_id"}{"$key"} = $query_data;
              }
          }

      }

    }

    return $json_string;

}

# Return table item data
sub get_item_table_informations{

  my ($table_name, $condition, $ref_column) = @_;
  my $database = api_database_connect();

  my $items = $database->selectall_arrayref(
      "select * from $table_name where $ref_column = $condition",
      { Slice => {} }
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

    my $sth = $database->prepare($query);

    if(@args ne ""){
      $sth->execute( @args );
    }else{
      $sth->execute();
    }

    return $sth->fetchall_arrayref();

}

# Format search depending on url parmeters
# ATM : only on main hardware table
sub format_query_for_computer_search{

  my ($args_array) = @_;

  my $query_string = "SELECT ID from hardware WHERE";
  my @args;
  my $start = "";
  my $limit = "";

  while(my($field_name, $searched_value) = each @args_array) {
    if(lc($field_name) eq "limit"){
      $limit = $searched_value;
    }elsif (lc($field_name) eq "start"){
      $start = $searched_value;
    }else{
      $field_name =~ tr/a-zA-Z//dc ;
      $query_string .= " $field_name = ? AND";
      push @args, $searched_value;
    }
  }

  $query_string = substr($query_string, 0, -3);

  return execute_custom_request($query_string, $start, $limit, @args);

}

1;
