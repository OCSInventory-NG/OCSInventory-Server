package Api::Ocsinventory::Restapi::Computer::Get::Computers;

=for comment

This function return a array of multiple computers

Params: start, limit

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_computers {

    my ($limit, $start) = @_;
    my $json_return;

    my $computers = Api::Ocsinventory::Restapi::ApiCommon::get_item_main_table_informations($limit, $start, "computer");

    foreach my $computer ( @$computers ) {
        $$json_return{"$computer->{ID}"}{"hardware"} = $computer;
        $json_return = Api::Ocsinventory::Restapi::ApiCommon::generate_item_datamap_json("computer", $computer->{ID}, $json_return, "");
    }

    return to_json($json_return);
}

1;
