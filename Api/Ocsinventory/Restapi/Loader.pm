package Api::Ocsinventory::Restapi;

# For dev purpose only
# use lib "/usr/local/share/OCSInventory-Server/";
# use Data::Dumper;

# Framework uses
use Mojolicious::Lite;

# Common sub for api
require Api::Ocsinventory::Restapi::ApiCommon;

## Computer section
require Api::Ocsinventory::Restapi::Computer::Get::ComputerId; # Get specific Computer informations
require Api::Ocsinventory::Restapi::Computer::Get::ComputerIdField; # Get specific field of Computer
require Api::Ocsinventory::Restapi::Computer::Get::Computers; # Get list of Computers
require Api::Ocsinventory::Restapi::Computer::Get::ComputersListId; # Get list of Computers ID
require Api::Ocsinventory::Restapi::Computer::Get::ComputersSearch; # Get list of ID depending on search

## IPDiscover section
require Api::Ocsinventory::Restapi::Ipdiscover::Get::Ipdiscover;
require Api::Ocsinventory::Restapi::Ipdiscover::Get::IpdiscoverNetwork;

## SNMP section
require Api::Ocsinventory::Restapi::Snmp::Get::Snmp; # Get list of Snmp
require Api::Ocsinventory::Restapi::Snmp::Get::SnmpId; # Get specific Snmp informations
require Api::Ocsinventory::Restapi::Snmp::Get::SnmpIdField; # Get specific field of Snmp
require Api::Ocsinventory::Restapi::Snmp::Get::SnmpListId; # Get list of Snmp ID

## Routes

get '/v1/computers/listID' => sub {
        my $c = shift;
        $c->render(json => Api::Ocsinventory::Restapi::Computer::Get::ComputersListId::get_computers_id());
};

get '/v1/computer/:id' => sub {
        my $c = shift;
        my $id = $c->stash('id');
        $c->render(json => Api::Ocsinventory::Restapi::Computer::Get::ComputerId::get_computer($id));
};

get '/v1/computer/:id/:field' => sub {
        my $c = shift;
        my $id = $c->stash('id');
        my $field = $c->stash('field');
        $c->render(json => Api::Ocsinventory::Restapi::Computer::Get::ComputerIdField::get_computer_field($id, $field));
};

get '/v1/computers' => sub {
        my $c = shift;
        my $id = $c->stash('id');

        my $start = $c->param('start')||0;
        my $limit = $c->param('limit')||0;

        $c->render(json => Api::Ocsinventory::Restapi::Computer::Get::Computers::get_computers($limit, $start));
};

get '/v1/computers/search' => sub {
        my $c = shift;

        my $params_hash = $c->req->params->to_hash;

        $c->render(json => Api::Ocsinventory::Restapi::Computer::Get::ComputersSearch::get_computers_search($params_hash));
};

get '/v1/ipdiscover' => sub {
        my $c = shift;

        my $start = $c->param('start');
        my $limit = $c->param('limit');

        $c->render(json => Api::Ocsinventory::Restapi::Ipdiscover::Get::Ipdiscover::get_ipdiscovers($start, $limit));
};

get '/v1/ipdiscover/:network' => sub {
        my $c = shift;
        my $network = $c->stash('network');

        $c->render(json => Api::Ocsinventory::Restapi::Ipdiscover::Get::IpdiscoverNetwork::get_ipdiscover_network($network));
};

get '/v1/snmps/listID' => sub {
        my $c = shift;
        $c->render(json => Api::Ocsinventory::Restapi::Snmp::Get::SnmpListId::get_snmps_id());
};

get '/v1/snmp' => sub {
        my $c = shift;
        my $id = $c->stash('id');

        my $start = $c->param('start')||0;
        my $limit = $c->param('limit')||0;

        $c->render(json => Api::Ocsinventory::Restapi::Snmp::Get::Snmp::get_snmp($limit, $start));
};

get '/v1/snmp/:id' => sub {
        my $c = shift;
        my $id = $c->stash('id');
        $c->render(json => Api::Ocsinventory::Restapi::Snmp::Get::SnmpId::get_snmp_id($id));
};

get '/v1/snmp/:id/:field' => sub {
        my $c = shift;
        my $id = $c->stash('id');
        my $field = $c->stash('field');
        $c->render(json => Api::Ocsinventory::Restapi::Snmp::Get::SnmpIdField::get_snmp_field($id, $field));
};

app->start;
