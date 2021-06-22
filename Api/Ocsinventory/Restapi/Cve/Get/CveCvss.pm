package Api::Ocsinventory::Restapi::Cve::Get::CveCvss;

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_cve_cvss {

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();

    my ($url_params) = @_;

    foreach my $url (@url_params) {
      foreach (keys %{$url}) {
        if(lc($_) eq "limit"){
          $limit = ${$url}{$_};
        }elsif (lc($_) eq "start"){
          $start = ${$url}{$_};
        }
      }
    }

    my $sql = "SELECT SQL_CALC_FOUND_ROWS *, p.PUBLISHER, CONCAT(n.NAME,\";\",v.VERSION) as search, c.LINK as id FROM cve_search c LEFT JOIN software_name n ON n.ID = c.NAME_ID LEFT JOIN software_publisher p ON p.ID = c.PUBLISHER_ID LEFT JOIN software_version v ON v.ID = c.VERSION_ID";

    my $query = Api::Ocsinventory::Restapi::ApiCommon::execute_custom_request($sql, $start, $limit);

    return to_json($query);
}

1;
