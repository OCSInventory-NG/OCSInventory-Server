package Api::Ocsinventory::Restapi::Snmp::Get::SnmpIdField;

=for comment

This function return a snmp field from his ID and field

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(decode_json encode_json);

sub get_snmp_field {

    my ($id, $field) = @_;

    my $snmps = Api::Ocsinventory::Restapi::ApiCommon::get_item_table_informations("snmp", "id", $id);
    my $json_return;

    foreach my $snmp ( @$snmps ) {
        $$json_return{"$snmp->{ID}"}{"snmp"} = $snmp;
        $json_return = Api::Ocsinventory::Restapi::ApiCommon::generate_item_datamap_json("snmp", $snmp->{ID}, $json_return, $field);
    }

    return encode_json($json_return);
}

1;
