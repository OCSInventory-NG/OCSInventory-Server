package Api::Ocsinventory::Restapi::Cve::Get::CveComputersList;

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_cve_computers_list {

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();
    
    $computers = $database->selectall_arrayref(
        "SELECT DISTINCT HARDWARE_NAME, HARDWARE_ID FROM cve_search_computer",
        { Slice => {} }
    );

    return to_json($computers);
}

1;
