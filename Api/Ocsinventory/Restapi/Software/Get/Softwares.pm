package Api::Ocsinventory::Restapi::Software::Get::Softwares;

=for comment

This function return a array of multiple softwares

Params: start, limit

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(decode_json encode_json);

sub get_softwares {

    my ($limit, $start, $soft) = @_;
    my $json_return;

    my $softwares = Api::Ocsinventory::Restapi::ApiCommon::generate_item_all_softwares_json($limit, $start, $soft);

    return encode_json($softwares);
}

1;
