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

    my $computers = Api::Ocsinventory::Restapi::ApiCommon::get_last_updated_computers($timestamp, "computer");

    return to_json($computers);
}

1;
