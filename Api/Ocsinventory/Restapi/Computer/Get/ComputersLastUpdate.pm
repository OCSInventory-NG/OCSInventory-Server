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

    foreach my $computer ( @$computers ) {
        $$json_return{"$computer->{ID}"}{"hardware"} = $computer;
        $json_return = Api::Ocsinventory::Restapi::ApiCommon::generate_item_datamap_json("computer", $computer->{ID}, $json_return, "");
    }

    return to_json($json_return);
}

1;
