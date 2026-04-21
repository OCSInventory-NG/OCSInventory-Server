package Api::Ocsinventory::Restapi::Snmp::Get::SnmpIdField;

=for comment

This function return a snmp field from his ID and field

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_snmp_field {

    my ($type, $id) = @_;

    return to_json([]) unless Api::Ocsinventory::Restapi::ApiCommon::_is_valid_snmp_table($type);
    $id = Api::Ocsinventory::Restapi::ApiCommon::_unsigned_int($id, undef);
    return to_json([]) unless defined($id);

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();

    my $snmps = $database->selectall_arrayref(
        "SELECT * from $type WHERE ID = ?",
        { Slice => {} },
        $id
    );

    return to_json($snmps);
}

1;
