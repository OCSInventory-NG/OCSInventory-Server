#!/usr/bin/perl -s

use SOAP::Lite;
use XML::Entities;

# Parameters
# -s='' 			: server to query
# -u=''				: user to authenticate
# -pw=''			: user's password
# -params='...,...,...,...' 	: Method's args															 
# -proto='http|https'		: Transport protocol
#
# get_computers V1 secific parameters (enable you to easily modify XML values)
# -o=''				: offset value (to iterate if whome result is upper than OCS_OPT_WEB_SERVICE_RESULTS_LIMIT (see ocsinventory-server.conf)
# -c=''				: checksum to compare with
# -w=''				: same principle than checksum but for other sections (dico_soft and accountinfos for the moment)
# -t=''				: type (META || INVENTORY)) See web service documentation
#
# Checksum decimal values
#'hardware'      => 1,
#'bios'          => 2,
#'memories'      => 4,
#'slots'         => 8,
#'registry'      => 16,
#'controllers'   => 32,
#'monitors'      => 64,
#'ports'         => 128,
#'storages'      => 256,
#'drives'        => 512,
#'inputs'        => 1024,
#'modems'        => 2048,
#'networks'      => 4096,
#'printers'      => 8192,
#'sounds'        => 16384,
#'videos'        => 32768,
#'softwares'     => 65536

$s = $s||'localhost';
$u = $u||'';
$pw = $pw||'';
$proto = $proto||'http';
@params = split ',', $params;
$f = $f||get_computers_V1;

# You can modify some XML tags
$c = $c||131071;
$t=$t||"META";
$o=$o||0;
$w=defined $w?$w:131071;

if( !defined(@params) && $f eq 'get_computers_V1' ){
  @params=(<<EOF);

<REQUEST>
  <ENGINE>FIRST</ENGINE>
  <ASKING_FOR>$t</ASKING_FOR>
  <CHECKSUM>$c</CHECKSUM>
  <OFFSET>$o</OFFSET>
  <WANTED>$w</WANTED>
</REQUEST>

EOF
}

print "Launching soap request to proxy:\n";
print "$proto://$u".($u?':':'').($u?"$pw\@":'')."$s/ocsinterface\n";

print "Function: <$f>\n";
$i++, print "Function Arg $i: `$_'\n" for @params;
print "\nIn progress... \n\n";


$lite = SOAP::Lite
  ->uri("$proto://$s/Apache/Ocsinventory/Interface")
  ->proxy("$proto://$u".($u?':':'').($u?"$pw\@":'')."$s/ocsinterface\n")
  ->$f(@params);

if($lite->fault){
  print "ERROR:\n\n",XML::Entities::decode( 'all', $lite->fault->{faultstring} ),"\nEND\n\n";
}
else{
  my $i = 0;
  for( $lite->paramsall ){
    print "===== RESULT $i ===== \n".XML::Entities::decode( 'all', $_ )."\n";
    $i++;
  }
}  
