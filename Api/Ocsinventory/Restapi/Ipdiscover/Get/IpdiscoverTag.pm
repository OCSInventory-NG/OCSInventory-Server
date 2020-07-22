package Api::Ocsinventory::Restapi::Ipdiscover::Get::IpdiscoverTag;

=for comment

This function return a IPDiscover object from tag

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(decode_json encode_json);

sub get_ipdiscover_tag{

    my ($tag) = @_;
    my $json_return;
    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();

    my $json_return = $database->selectall_arrayref(
        "SELECT * from netmap WHERE TAG = '$tag'",
        { Slice => {} }
    );

    return encode_json($json_return);
}

1;