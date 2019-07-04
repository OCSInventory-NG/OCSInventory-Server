package Api::Ocsinventory::Restapi::Computer::Get::ComputersSearch;

=for comment

This function return a array of multiple computers ID depending on search params

Params: table.fieldname = value

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(decode_json encode_json);

sub get_computers_search {

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();

    my ($url_params) = @_;

    my $query = Api::Ocsinventory::Restapi::ApiCommon::format_query_for_computer_search($url_params);

    return encode_json($query);
}

1;
