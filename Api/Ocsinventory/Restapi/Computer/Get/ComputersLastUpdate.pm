package Api::Ocsinventory::Restapi::Computer::Get::ComputersLastUpdate;

=for comment

This function returns an array of computers updated since timestamp (or default of 1 day) 

Params: timestamp

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_computers_last_update {

    my ($timestamp) = @_;
    my $json_return;

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();
    # if timestamp was not provided by user, defaults to current datetime - 1 day
    if ($timestamp eq 0) {
        $timestamp = time - 86400;
    }

    # get all computers updated since timestamp
    $computers = $database->selectall_arrayref(
        "SELECT ID FROM hardware WHERE LASTDATE > FROM_UNIXTIME($timestamp)",
        { Slice => {} }
    );

    return to_json($computers);
}

1;
