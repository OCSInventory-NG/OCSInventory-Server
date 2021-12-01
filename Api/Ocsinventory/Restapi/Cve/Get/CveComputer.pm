package Api::Ocsinventory::Restapi::Cve::Get::CveComputer;

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

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

    my $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM cve_search_computer";
    
    my $query = Api::Ocsinventory::Restapi::ApiCommon::execute_custom_request($sql, $start, $limit);

    return to_json($query);
}

1;
