#!/usr/bin/perl -s

use SOAP::Lite;
use XML::Entities;

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

# Method arguments
$p1 = $p1;
$p2 = $p2;
$p3 = $p3;
$p4 = $p4;
$p5 = $p5;

# By default, get computers
$f = $f||get_computers_V1;

# You can modify some XML tags
$c = $c||131071;
$t=$t||"META";
$o=$o||0;
$w=defined $w?$w:131071;

if( !defined($p1) && $f eq 'get_computers_V1' ){
  $p1=<<EOF;

<REQUEST>
  <ENGINE>FIRST</ENGINE>
  <ASKING_FOR>$t</ASKING_FOR>
  <CHECKSUM>$c</CHECKSUM>
  <OFFSET>$o</OFFSET>
  <WANTED>$w</WANTED>
</REQUEST>

EOF
}

for($p1,$p2,$p3,$p4,$p5){
  push @params, $_ if defined($_);
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
