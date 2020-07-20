package Api::Ocsinventory::Restapi::Snmp::Get::SnmpId;

=for comment

This function return a snmp from his ID

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(decode_json encode_json);

sub get_snmp_id {

    my ($type) = @_;

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();

    my $snmps = $database->selectall_arrayref(
        "SELECT * from $type",
        { Slice => {} }
    );

    return encode_json($snmps);
}

1;
