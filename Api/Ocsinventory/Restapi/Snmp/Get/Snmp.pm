package Api::Ocsinventory::Restapi::Snmp::Get::Snmp;

=for comment

This function return a array of multiple SNMP

Params: start, limit

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(decode_json encode_json);

sub get_snmp{

    my ($limit, $start) = @_;
    my $json_return;

    my $snmps = Api::Ocsinventory::Restapi::ApiCommon::get_item_main_table_informations($limit, $start, "snmp");

    foreach my $snmp ( @$snmps ) {
        $$json_return{"$snmp->{ID}"}{"snmp"} = $snmp;
        $json_return = Api::Ocsinventory::Restapi::ApiCommon::generate_item_datamap_json("snmp", $snmp->{ID}, $json_return, "");
    }

    return encode_json($json_return);
}

1;
