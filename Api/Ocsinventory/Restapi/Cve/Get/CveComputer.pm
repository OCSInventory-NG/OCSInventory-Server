package Api::Ocsinventory::Restapi::Cve::Get::CveComputer;

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(decode_json encode_json);

sub get_cve_computer {

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

    my $sql = "SELECT SQL_CALC_FOUND_ROWS *, p.PUBLISHER, CONCAT(n.NAME,\";\",v.VERSION) as search, c.LINK as id, h.NAME as computer, h.ID as computerid, n.NAME as softname FROM cve_search c LEFT JOIN software_name n ON n.ID = c.NAME_ID LEFT JOIN software_publisher p ON p.ID = c.PUBLISHER_ID LEFT JOIN software_version v ON v.ID = c.VERSION_ID LEFT JOIN software s ON s.NAME_ID = n.ID LEFT JOIN hardware h ON h.ID = s.HARDWARE_ID";

    my $query = Api::Ocsinventory::Restapi::ApiCommon::execute_custom_request($sql, $start, $limit);

    return encode_json($query);
}

1;
