package Api::Ocsinventory::Restapi::Ipdiscover::Get::IpdiscoverNetwork;

=for comment

This function return a IPDiscover object from network

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(decode_json encode_json);

sub get_ipdiscover_network{

    my ($network) = @_;
    my $json_return;
    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();
    my $query = "SELECT * FROM `netmap` WHERE NETID = ? ";
    my @args = ($network);
    my $netmaps = Api::Ocsinventory::Restapi::ApiCommon::execute_custom_request($query, "", "", @args);

    return encode_json($json_return);
}

1;
