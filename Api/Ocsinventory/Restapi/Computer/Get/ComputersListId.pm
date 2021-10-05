package Api::Ocsinventory::Restapi::Computer::Get::ComputersListId;

=for comment

This function return a array of multiple computers ID

Params: start, limit

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_computers_id {

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();

    my $computers = $database->selectall_arrayref(
        "select ID from hardware",
        { Slice => {} }
    );

    return to_json($computers);
}

1;
