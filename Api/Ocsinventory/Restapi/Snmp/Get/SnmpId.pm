package Api::Ocsinventory::Restapi::Snmp::Get::SnmpId;

=for comment

This function return a snmp from his ID

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(decode_json encode_json);

sub get_snmp_id {

    my ($type, $start, $limit) = @_;

    my $json_return;
    my $query = "SELECT * from $type ";

    my $json_return = Api::Ocsinventory::Restapi::ApiCommon::execute_custom_request($query, $start, $limit);

    return encode_json($json_return);
}

1;
