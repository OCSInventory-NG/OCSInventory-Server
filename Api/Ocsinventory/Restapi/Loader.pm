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
require Api::Ocsinventory::Restapi::Computer::Get::ComputersLastUpdate; # Get list of computers udpated since specific date

## Software section
require Api::Ocsinventory::Restapi::Software::Get::Softwares; # Get list of softwares

## IPDiscover section
require Api::Ocsinventory::Restapi::Ipdiscover::Get::Ipdiscover;
require Api::Ocsinventory::Restapi::Ipdiscover::Get::IpdiscoverNetwork;
require Api::Ocsinventory::Restapi::Ipdiscover::Get::IpdiscoverTag;

## SNMP section
require Api::Ocsinventory::Restapi::Snmp::Get::SnmpId; # Get specific Snmp type informations
require Api::Ocsinventory::Restapi::Snmp::Get::SnmpIdField; # Get specific information by id of Snmp type
require Api::Ocsinventory::Restapi::Snmp::Get::SnmpListId; # Get list of Snmp Type

## CVE section
require Api::Ocsinventory::Restapi::Cve::Get::CveCvss;
require Api::Ocsinventory::Restapi::Cve::Get::CveSoftware;
require Api::Ocsinventory::Restapi::Cve::Get::CveComputer;
require Api::Ocsinventory::Restapi::Cve::Get::CveComputersList;
require Api::Ocsinventory::Restapi::Cve::Get::CveHistory;

## Routes

get '/v1/computers/listID' => sub {
        my $c = shift;
        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Computer::Get::ComputersListId::get_computers_id());
};

get '/v1/computer/:id' => sub {
        my $c = shift;
        my $id = $c->stash('id');
        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Computer::Get::ComputerId::get_computer($id));
};

get '/v1/computer/:id/:field' => sub {
        my $c = shift;
        my $id = $c->stash('id');
        my $field = $c->stash('field');
        
        my $where = $c->param('where')|| "";
        my $operator = $c->param('operator')|| "";
        my $value = $c->param('value')|| "";

        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Computer::Get::ComputerIdField::get_computer_field($id, $field, $where, $operator, $value));
};

get '/v1/computers' => sub {
        my $c = shift;
        my $id = $c->stash('id');

        my $start = $c->param('start')||0;
        my $limit = $c->param('limit')||0;

        $c->render(format => 'json', text => Api::Ocsinventory::Restapi::Computer::Get::Computers::get_computers($limit, $start));
};

get '/v1/computers/lastupdate/:timestamp' => sub {
        my $c = shift;
        my $timestamp = $c->param('timestamp') || 0;

        $c->render(format => 'json', text => Api::Ocsinventory::Restapi::Computer::Get::ComputersLastUpdate::get_computers_last_update($timestamp));
};

get '/v1/computers/lastupdate' => sub {
        my $c = shift;
        my $timestamp = 0;

        $c->render(format => 'json', text => Api::Ocsinventory::Restapi::Computer::Get::ComputersLastUpdate::get_computers_last_update($timestamp));
};

get '/v1/computers/search' => sub {
        my $c = shift;

        my $params_hash = $c->req->params->to_hash;

        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Computer::Get::ComputersSearch::get_computers_search($params_hash));
};

get '/v1/softwares' => sub {
        my $c = shift;
       my $id = $c->stash('id');

        my $start = $c->param('start')||0;
        my $limit = $c->param('limit')||0;
        my $soft = $c->param('soft')||'';

        $c->render(format => 'json', text => Api::Ocsinventory::Restapi::Software::Get::Softwares::get_softwares($limit, $start, $soft));
};

get '/v1/ipdiscover' => sub {
        my $c = shift;

        my $start = $c->param('start')||0;
        my $limit = $c->param('limit')||0;

        $c->render(format => 'json', text => Api::Ocsinventory::Restapi::Ipdiscover::Get::Ipdiscover::get_ipdiscovers($start, $limit));
};

get '/v1/ipdiscover/tag/:tag' => sub {
        my $c = shift;
        my $tag = $c->stash('tag');

        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Ipdiscover::Get::IpdiscoverTag::get_ipdiscover_tag($tag));
};

get '/v1/ipdiscover/network/#network' => sub {
        my $c = shift;

        my $network = $c->stash('network');
        	
        $c->render(format => 'json', text => Api::Ocsinventory::Restapi::Ipdiscover::Get::IpdiscoverNetwork::get_ipdiscover_network($network));
};

get '/v1/snmps/typeList' => sub {
        my $c = shift;
        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Snmp::Get::SnmpListId::get_snmps_id());
};

get '/v1/snmp/:type' => sub {
        my $c = shift;
        my $type = $c->stash('type');
        my $start = $c->param('start')||0;
        my $limit = $c->param('limit')||0;

        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Snmp::Get::SnmpId::get_snmp_id($type, $start, $limit));
};

get '/v1/snmp/:type/:id' => sub {
        my $c = shift;
        my $type = $c->stash('type');
        my $id = $c->stash('id');
        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Snmp::Get::SnmpIdField::get_snmp_field($type, $id));
};

get '/v1/cve/cvss' => sub {
        my $c = shift;

        my $start = $c->param('start')||0;
        my $limit = $c->param('limit')||0;

        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Cve::Get::CveCvss::get_cve_cvss($start, $limit));
};

get '/v1/cve/software' => sub {
        my $c = shift;

        my $start = $c->param('start')||0;
        my $limit = $c->param('limit')||0;

        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Cve::Get::CveSoftware::get_cve_software($start, $limit));
};

get '/v1/cve/computer' => sub {
        my $c = shift;

        my $start = $c->param('start')||0;
        my $limit = $c->param('limit')||0;

        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Cve::Get::CveComputer::get_cve_computer($start, $limit));
};

get '/v1/cve/computerslist' => sub {
        my $c = shift;

        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Cve::Get::CveComputersList::get_cve_computers_list());
};

get '/v1/cve/history' => sub {
        my $c = shift;

        $c->render(format => 'json', text  => Api::Ocsinventory::Restapi::Cve::Get::CveHistory::get_cve_history());
};


app->start;
