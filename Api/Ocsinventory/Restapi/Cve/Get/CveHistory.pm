package Api::Ocsinventory::Restapi::Cve::Get::CveHistory;

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_cve_history {

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();
    
    $history = $database->selectall_arrayref(
        "SELECT cve_search_history.ID, FLAG_DATE, CVE_NB, PUBLISHER_ID, software_publisher.PUBLISHER FROM cve_search_history LEFT JOIN software_publisher ON cve_search_history.PUBLISHER_ID = software_publisher.ID",
        { Slice => {} }
    );

    return to_json($history);
}

1;
