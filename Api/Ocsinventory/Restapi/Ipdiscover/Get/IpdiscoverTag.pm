package Api::Ocsinventory::Restapi::Ipdiscover::Get::IpdiscoverTag;

=for comment

This function return a IPDiscover object from tag

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_ipdiscover_tag{

    my ($tag) = @_;

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();

    my $query = "SELECT * from `netmap` WHERE TAG = ?";
    my @args = ($tag);

    my $netmaps = Api::Ocsinventory::Restapi::ApiCommon::execute_custom_request($query, "", "", @args);

    return to_json($netmaps);
}

1;
