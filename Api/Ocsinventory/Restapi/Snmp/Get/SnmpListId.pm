package Api::Ocsinventory::Restapi::Snmp::Get::SnmpListId;

=for comment

This function return a array of multiple snmp Types

Params: start, limit

=cut

# Common sub for api
use Api::Ocsinventory::Restapi::ApiCommon;
use Mojo::JSON qw(to_json);

sub get_snmps_id {

    my $database = Api::Ocsinventory::Restapi::ApiCommon::api_database_connect();

    my $snmps = $database->selectall_arrayref(
        "SELECT * from snmp_types",
        { Slice => {} }
    );

    return to_json($snmps);
}

1;
