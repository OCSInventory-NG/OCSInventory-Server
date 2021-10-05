package Api::Ocsinventory::Restapi::Computer::Get::ComputerId;

=for comment

This function return a computer from his ID

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_computer {

    my ($id) = @_;

    my $computers = Api::Ocsinventory::Restapi::ApiCommon::get_item_table_informations("hardware", $id, "id");
    my $json_return;

    foreach my $computer ( @$computers ) {
        $$json_return{"$computer->{ID}"}{"hardware"} = $computer;
        $json_return = Api::Ocsinventory::Restapi::ApiCommon::generate_item_datamap_json("computer", $computer->{ID}, $json_return, "");
        $json_return = Api::Ocsinventory::Restapi::ApiCommon::generate_item_software_json("computer", $computer->{ID}, $json_return, "");
    }

    return to_json($json_return);
}

1;
