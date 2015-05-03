#!/usr/bin/perl 
###############################################################################
##OCSINVENTORY-NG
##Copyleft Pascal DANEK 2005
##Web : http://www.ocsinventory-ng.org
##
##This code is open source and may be copied and modified as long as the source
##code is always made freely available.
##Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
#
#
use DBI;
use XML::Simple;
use Net::IP qw(:PROC);
use strict;
use Fcntl qw/:flock/;
#Command line options : 
#-analyse : nmap analyse and files for grep, human readable and xml generation
#-net : Treat machine for the specified subnet
#
#
################
#OPTIONS READING
################
#
my $option;
#Specify a subnet
my $net;
#Subnet name
my $filter;
#Analyse and class the computers
my $analyse;
#Net for a given ip
my $iptarget;
my $masktarget;
#If auto flag, running for all the subnet.csv subnet and generate files
my $auto;
#Search for  problems with computer election
my $ipd;
my $list;
my $xml;
my $cache;
my $path;
#Default values for database connection
#
my $dbhost = 'localhost';
my $dbuser = 'ocs';
my $dbpwd = 'ocs';
my $db = 'ocsweb';
my $dbp = '3306';
my $dbsocket = '';

#
my %xml;
my $ipdiscover;

#Cleanup the directory
&_cleanup();

for $option (@ARGV){
  if($option=~/-a$/){
    $analyse = 1;
  }elsif($option=~/-auto$/){
    $auto = 1;
  }elsif($option=~/-cache$/){
    $cache = 1;
    $analyse = 1;
    $xml = 1;
  }elsif($option=~/-path=(\S*)$/){
    $path = $1;
  }elsif($option=~/-ipdiscover=(\d+)$/){
    $ipdiscover = 1;
    $ipd = $1;
  }elsif($option=~/-xml$/){
    $xml = 1;
  }elsif($option=~/-h=(\S+)/){
    $dbhost = $1;
  }elsif($option=~/-d=(\S+)/){
    $db = $1;
  }elsif($option=~/-u=(\S+)/){
    $dbuser = $1;
  }elsif($option=~/-p=(\S+)/){
    $dbpwd = $1;
  }elsif($option=~/-P=(\S+)/){
    $dbp = $1;
  }elsif($option=~/-list$/){
      $list = 1;
  }elsif($option=~/-s=(\S+)/){
    $dbsocket = $1;
  }elsif($option=~/-net=(\S+)/){
    die "Invalid subnet. Abort...\n" unless $1=~/^(\d{1,3}(?:\.\d{1,3}){3})$/;
    $net = 1;
    $filter = $1;
  }elsif($option=~/-ip=(\S+)/){
    die "Invalid address => [IP/MASK]. Abort...\n" unless $1=~/^(\d{1,3}(?:\.\d{1,3}){3})\/(.+)$/;
    $iptarget = $1;
    $masktarget = $2;
  }else{
    print <<EOF;
Usage :
-ip=X.X.X.X/X.X.X.X (ex: 10.1.1.1/255.255.240.0 or 10.1.1.1/20)-> Looks for the ip in a subnet
-net=X.X.X.X -> Specify a network
-a -> Launch the analyze
-ipdiscover=X -> Show all the subnet with up to XX ipdiscover
-xml -> xml output
-list=show all the networks present in the database with "connected"/"discovered" computers
#DATABASE OPTION
-p=xxxx password (default ocs)
-P=xxxx port (default 3306)
-d=xxxx database name (default ocsweb)
-u=xxxx user (default ocs)
-h=xxxx host (default localhost)
-s=xxxx socket (default from default mysql configuration)

EOF
    die "Invalid options. Abort..\n";
  }
}
if($analyse and !$net){
  die "Which subnet do you want to analyse ?\n";
} 
if($cache or $auto){
  unless(-d "$path/ipd"){
      mkdir("$path/ipd") 
        or die $!;
  }
}
#Date of the day
my $date = localtime();


#######################
#Database connection...
########
#
my $request;
my $row;
my $dbparams = {};

$dbparams->{'mysql_socket'} = $dbsocket if $dbsocket;

my $dbh = DBI->connect("DBI:mysql:database=$db;host=$dbhost;port=$dbp", $dbuser, $dbpwd, $dbparams)
 or die $!;

#############################
#We get the subnet/name pairs
####
#
my @subnet;
$request = $dbh->prepare("SELECT * FROM subnet");
$request->execute;
while($row = $request->fetchrow_hashref){
  push @subnet, [ $row->{'NETID'}, $row->{'NAME'}, $row->{'ID'}, $row->{'MASK'} ];
}

###########
#PROCESSING
###########
#
if($auto){
  print "\n\n########################\n";
  print "Starting scan of subnets\n";
  print "########################\n";
  my %subnet;
  for(@subnet){
     my $name = $_->[1];
     my $netn  = $_->[0];
     $name=~s/ /_/g;
     $name=~s/\//\\\//g;
     $subnet{$name} = $netn;
     print "Retrieving $name (".$subnet{$name}.")\n";
  }
  my $i;
  print "\n\n##################\n";
  print "PROCESSING SUBNETS \n";
  print "##################\n\n";
  for(keys(%subnet)){
    print "Processing $_ (".$subnet{$_}."). ".(keys(%subnet)-$i)." networks left.\n";
    open OUT, ">$path/ipd/".$subnet{$_}.".ipd" or die $!;
    unless(flock(OUT, LOCK_EX|LOCK_NB)){
      if($xml){
        print "<ERROR><MESSAGE>345</MESSAGE></ERROR>";
	exit(0);
      }else{
        die "An other analyse is in progress\n";
      }
    }
    system("./ipdiscover-util.pl -net=".$subnet{$_}.($xml?' -xml':'')." -a > $path/ipd/'".$subnet{$_}.".ipd'");
    $i++;
  }
  system ("rm -f ipdiscover-analyze.*");
  print "Done.\n";
  exit(0);
}

#Host subnet
my $network;
#Subnet mask in binary format
my $binmask;



if($ipdiscover){
  my @networks;
  #get the subnets
  my $result;
  die "Invalid value\n" if $ipd<0;
  $request = $dbh->prepare('select distinct(ipsubnet) from networks left outer join devices on tvalue=ipsubnet where tvalue is null');
  $request->execute;
  while($row = $request->fetchrow_hashref){
    push @networks, [ $row->{'ipsubnet'}, '0' ];
  }
  $request->finish;
  #If positive value, it includes other subnet
  if($ipd){
    $request = $dbh->prepare('select count(*) nb,tvalue,name from devices group by tvalue having nb<='.$ipd.' and name="ipdiscover"');
    $request->execute;
    while($row = $request->fetchrow_hashref){
      push @networks, [ $row->{'tvalue'}, $row->{'nb'} ];
    }
    $request->finish;
  }
  print <<EOF unless($xml);
############################
#IP DISCOVER IP/SUBNET REPORTS
#$date
#Subnets with max $ipd 
#  ipdiscover computers
############################


EOF
#
  my $output;
  my ($i,$j);
  for $network (@networks){
    my $ip = $network->[0];
    my $nbipd = $network->[1];
    next unless $ip =~ /^\d{1,3}(?:\.\d{1,3}){3}$/;
    my $req = $dbh->prepare('select h.deviceid, h.id, h.name, h.quality,h.fidelity,h.lastcome,h.lastdate,osname, n.ipmask, n.ipaddress from hardware h,networks n where n.hardware_id=h.id and n.ipsubnet='.$dbh->quote($ip).' order by lastdate'); 
    $req->execute;
    #Get the subnet label
    unless($xml){
      print "#######\n";
      my ($nname, $nuid) = &_getnetname($ip, '');
      print "SUBNET = ".$ip."-> $nbipd ipdiscover, ".($req->rows?$req->rows:0)." host(s) connected \n[ $nname ($nuid) ]\n";
      print "#\n\n";
      printf("     %-25s %-9s %-9s %-25s %-15s %-15s %s\n", "<Name>","<Quality>","<Fidelity>","<LastInventory>","<IP>","<Netmask>","<OS>");
      print "-----------------------------------------------------------------------------------------------------------------------\n";
      while($result = $req->fetchrow_hashref){
        my $r = $dbh->prepare('select * from devices where hardware_id='.$dbh->quote($result->{'id'}).' and tvalue='.$dbh->quote($ip).' and name="ipdiscover"');
	$r->execute;
	printf("#-> %-25s %-9s %-9s %-25s %-15s %15s %s %s\n",$result->{'name'},$result->{'quality'},$result->{'fidelity'},$result->{'lastdate'},$result->{'ipaddress'}, $result->{'ipmask'},$result->{'osname'} ,$r->rows?'*':'');
	$r->finish;
      }
      print "\n\n\n\n";
    }else{
      $xml{'SUBNET'}[$i]{'IP'} = [ $ip ];
      $xml{'SUBNET'}[$i]{'IPDISCOVER'} = [ $nbipd ];
      $xml{'SUBNET'}[$i]{'HOSTS'} = [ $req->rows?$req->rows:0 ];
      $j = 0;
      while($result = $req->fetchrow_hashref){
        $xml{'SUBNET'}[$i]{'HOST'}[$j]{'NAME'} = [ $result->{'name'} ];
	$xml{'SUBNET'}[$i]{'HOST'}[$j]{'QUALITY'} = [ $result->{'quality'} ];
	$xml{'SUBNET'}[$i]{'HOST'}[$j]{'FIDELITY'} = [ $result->{'fidelity'} ];
	$xml{'SUBNET'}[$i]{'HOST'}[$j]{'LASTDATE'} = [ $result->{'lastdate'} ];
	$xml{'SUBNET'}[$i]{'HOST'}[$j]{'OSNAME'} = [ $result->{'osname'} ];
	$xml{'SUBNET'}[$i]{'HOST'}[$j]{'IPMASK'} = [ $result->{'ipmask'} ];
	$j++;
      }
      $i++;
    }
  }
if($xml){
  $output=XML::Simple::XMLout( \%xml, RootName => 'NET', SuppressEmpty => undef);
  print $output;
}
exit(0);
}

#############
##IP resolving
##############
##
if($iptarget){
  my $netname;
  #If necessary, ascii conversion of a binary format
  $masktarget = _bintoascii($masktarget) if($masktarget=~/^\d\d$/);
  die "Invalid netmask. Abort.\n" unless $masktarget=~/^\d{1,3}(\.\d{1,3}){3}$/;
  $network = _network($iptarget, $masktarget);
  my $uid;
  ($netname, $uid)= &_getnetname($network, '-');
  my @nmb =  `nmblookup -A $iptarget`;
  #DNS name
  my $dnsname = &_getdns($iptarget);
  #Netbios name
  my $nmbname;
  my $inv;
  my $type;
  
  for(@nmb){
    $nmbname = $1,last if /\s+(\S+).*<00>/;
  }
  $request = $dbh->prepare('SELECT * FROM networks WHERE IPADDRESS='.$dbh->quote($iptarget));
  $request->execute;
  if($request->rows){
  	$inv = 1;
	$type = 'Computer';
  }else{
  	$request = $dbh->prepare('SELECT IP,TYPE FROM netmap, network_devices WHERE MAC=MACADDR AND IP='.$dbh->quote($iptarget));
  	$request->execute; 
	if(my $row = $request->fetchrow_hashref){
		$inv = 1, $type = $row->{'TYPE'};
	}
  }
  
  $request = $dbh->prepare('SELECT MAC FROM netmap WHERE IP='.$dbh->quote($iptarget));
  $request->execute;
  my $exist = 1 if $request->rows;
  
unless($xml){
    print <<EOF;


#########################
#IPDISCOVER Ver 1.0b
#IP DISCOVER REPORT
#$date
##########################


EOF

    print "\n--> ".($netname).". ($uid) ($network)\n";
    print <<EOF;

Netbios name : $nmbname
DNS name     : $dnsname
EOF

    printf("Inventoried  : %s\n",$inv?'Yes':'No');
    printf("Discovered   : %s\n",$exist?'Yes':'No');
    print "Type   : $type\n" if $inv;
    print "\nDone.\n\n";
    exit(0);
  }else{
    my $output;
    $xml{'IP'} = [ $iptarget ];
    $xml{'NETNAME'} = [ $netname ]; 
    $xml{'NETNUM'} = [ $network ];
    $xml{'NETBIOS'} = [ $nmbname ]; 
    $xml{'DNS'} = [ $dnsname ]; 
    $xml{'INVENTORIED'} = [ $inv?'yes':'no' ]; 
    $xml{'DISCOVERED'} = [ $exist?'yes':'no' ]; 
    $xml{'TYPE'} = [ $type ] if $inv; 
    
    $output=XML::Simple::XMLout( \%xml, RootName => 'IP', SuppressEmpty => undef);
    print $output;
    exit(0);
  }
}


#
#Searching non-inventoried mac addresses
#
my %network;
my @hosts;
my $null;
#   

# Filter out already flagged MAC addresses - drich
$request = $dbh->prepare('SELECT IP,MASK,MAC,DATE FROM netmap LEFT JOIN networks ON MACADDR=MAC where MACADDR IS NULL AND MAC NOT IN (SELECT MACADDR FROM network_devices)');  
$request->execute;
#  
#Determine the subnets
#
#
my %network_ipd;
#
while($row = $request->fetchrow_hashref){
  my $ip;
  my $netmask;
  #
  if($row->{'MASK'}){
    $ip = $row->{'IP'};
    $netmask = $row->{'MASK'};
    $network = _network($ip, $netmask);
    $binmask = _binmask($netmask);
    if($net){
      next unless $network eq $filter;
    }
    #Hosts count per subnet
    if($list and !$analyse and !$net){
      $network_ipd{$network}++;
    }else{
      $network{$network}++;
      push @hosts, [ $row->{'MAC'}, $ip , $row->{'DATE'}];
    }
  }else{
    $null++;
  }
}

my $total = 0;
#We want ALL subnets in the database, even those that are not discovered
if($list and !$analyse and !$net){
  $request = $dbh->prepare('SELECT IPADDRESS,IPMASK FROM networks');
  $request->execute;
  while($row = $request->fetchrow_hashref){
      my $ip;
      my $netmask;
      #
      if($row->{'IPMASK'}=~/^\d{1,3}(\.\d{1,3}){3}$/ and $row->{'IPADDRESS'}=~/^\d{1,3}(\.\d{1,3}){3}$/){
        $ip = $row->{'IPADDRESS'};
        $netmask = $row->{'IPMASK'};
        $network = _network($ip, $netmask);
        $network{$network}++;
      }
      #Hosts count per subnet
  }
  #We show the part of non-inventoried computers
  my $netnum;
  for $netnum (keys(%network)){
    $total+=$network{$netnum};
    for(keys(%network_ipd)){
      $network{$netnum}.= "/".$network_ipd{$_} if($_ eq $netnum);
    }
  }
}

########
#RESULTS
########
#
print <<EOF unless $xml;


#########################
IPDISCOVER Ver 1.0b
IP DISCOVER REPORT
$date 
#########################



EOF
unless($xml){
  printf("%-35s %-5s %-20s %-4s\n", "<Name>","<UID>","<Subnet>","<Nb>");
  print "-------------------------------------------------------------------\n";
}
#net UID
my $dep;
#net name
my $lib;
#
my $line;
my $netn;
#
my $i;
my $output;
for $netn (keys(%network)){
  ($lib, $dep) = &_getnetname($netn,'-');  
  if($xml and !$analyse){
    $xml{'NETWORK'}[$i]{'LABEL'} = [ $lib ];
    $xml{'NETWORK'}[$i]{'UID'} = [ $dep ]; 
    $xml{'NETWORK'}[$i]{'NETNAME'} = [ $netn ]; 
    $xml{'NETWORK'}[$i]{'NETNUMBER'} = [ $network{$netn} ];
    $i++;
  }elsif(!$xml){
    printf("%-35s %-5s %-20s %-4s\n",$lib,$dep,$netn,$network{$netn});
    $total += $network{$netn} unless $list;
  }
}
if($xml and !$net){
  $output=XML::Simple::XMLout( \%xml, RootName => 'IPDISCOVER', SuppressEmpty => undef);
  print $output;
}

if($net){
  #
  my($n, $i);
  #Host names
  my @names;
  #
  unless($xml){
    print "\n---------------------------------------------\n";
    print "Unknown host(s) on $filter \n";
    print "---------------------------------------------\n\n";
  }
  my $output;
  for $n (@hosts){
    #Trying a DNS resolution
    push @$n, &_getdns($$n[1]);
    unless($analyse){
      if($xml){
        $xml{'HOST'}[$i]{'IP'} = [ $$n[1] ];
        $xml{'HOST'}[$i]{'MAC'} = [ $$n[0] ]; 
	$xml{'HOST'}[$i]{'DATE'} = [ $$n[2] ]; 
        $xml{'HOST'}[$i]{'NAME'} = [ $$n[3] ]; 
        $i++;
      }else{
        printf("=> %-20s %-20s %-20s %s\n",$$n[1],$$n[0],$$n[2],$$n[3]);
      }
    }
   }
    
  if(!$analyse and $xml){
    $output=XML::Simple::XMLout( \%xml, RootName => 'NET', SuppressEmpty => undef);
    print $output;
  }
}

########
#ANALYZE
########
#
#
#
#windows computers
my @PCW;
#Linux computers
my @PCL;
#Mac computers
my @PCM;
#net peripherals
my @PR;
#Phantom computers
my @PH; 
#At least one port filtered with one port open or closed
my @PF;
if($analyse){
  #directory creation for analyses file
  if($cache){
    open CACHE, ">$path/ipd/$filter.ipd" or die $!;
    unless(flock(CACHE, LOCK_EX|LOCK_NB)){
      if($xml){
        print "<ERROR><MESSAGE>346</MESSAGE></ERROR>";
	exit(0);
      }else{
        die "An other analyse is in progress\n";
      }
    }
  }
  
  unless(@hosts){
    print "No unknown hosts of this network. Stop.\n\n" unless $xml;
    exit(0);
  }
  #If it's a global analyze, we don't display the results but just generate the files
  #Using nmap :
  # -> Connection on ports 135(msrpc), 80(web), 22(ssh), 23(telnet)
  # -> files ipdiscover-analyze.* generated (3 formats : .nmap(human), .gnmap(pour grep), .xml(xml)
  # -> No 'host up' detection
  #
  my @ips;
  push @ips, $$_[1] for(@hosts);
  #Check that there is no analyses of this network pending
  open NMAP, "+>$path/$filter.gnmap";
  unless(flock(NMAP, LOCK_EX|LOCK_NB)){
      if($xml){
        print "<ERROR><MESSAGE>347</MESSAGE></ERROR>";
	exit(0);
      }else{
        die "An other analyse is in progress\n";
      }
    }
  #Analyse
  system("nmap -R -v @ips -p 135,80,22,23 -oG $path/$filter.gnmap -P0 -O > /dev/null");
  #
  my @gnmap;
  if($net){
    @gnmap = <NMAP>;
    close NMAP;
    unlink "$path/$filter.gnmap";
    #
    ###########
    #
    my $ref;
    my ($h, $w, $j, $r, $f, $l);
    REF:
    for $ref (@hosts){
      $h = $$ref[1];
         for(@gnmap){
	   next if /^#/;        # Skip comments
	   if(/Host: $h\b/){
            if (/Status: Down/){
               $PH[$j] = $ref;
               $j++;
               next REF;
            }elsif(/Status: /){        # status up is meaningless to us
              next;
            }
            # Try OS detection first
            if(/OS: .*Windows/){
               $PCW[$w] = $ref;
              $w++;
               next REF;
            }elsif(/OS: .*Apple Mac/){
               $PCM[$l] = $ref;
               $l++;
               next REF;
            }elsif(/OS: .*Linux/ and !/OS: .*embedded/){
               $PCL[$l] = $ref;
              $l++;
               next REF;
            }elsif(/OS: /){    # Something else, call it network
               $PR[$r] = $ref;
              $r++;
              next REF;
            }

	     if(/135\/o/){
               $PCW[$w] = $ref;
	       $w++;
               next REF;
             }elsif( (/22\/c.+23\/c.+80\/o.+135\/c/) or (/22\/c.+23\/o.+80\/c.+135\/c/) or (/22\/c.+23\/o.+80\/o.+135\/c/) or (/22\/o.+23\/o/) ){
               $PR[$r] = $ref;
	       $r++;
      	       next REF;
             }elsif(/(22\/f.+23\/f.+80\/f.+135\/c|22\/c.+23\/c.+80\/c.+135\/c)/){
               $PH[$j] = $ref;
	       $j++;
               next REF;
	     }elsif(/(22\/f.+23\/f.+80\/f.+135\/f)/){
  	       $PF[$f] = $ref;
	       $f++;
  	       next REF;
             }else{
               $PCL[$l] = $ref;
	       $l++;
               next REF;
             }
	   }
         }
     }
   #Display results
   print "<NETANALYSE>\n" if $xml;
   print CACHE "<NETANALYSE>\n" if $xml;
   &_print(\@PCW, 'WINDOWS COMPUTERS');
   &_print(\@PCL, 'LINUX COMPUTERS');
   &_print(\@PCM, 'MAC COMPUTERS');
   &_print(\@PR, 'NETWORK DEVICES');
   &_print(\@PF, 'FILTERED HOSTS');
   &_print(\@PH, 'PHANTOM HOSTS');
   print "</NETANALYSE>\n" if $xml;   
   print CACHE "</NETANALYSE>\n" if $xml;
  }
}

##################
#DISLAY SUBROUTINE
##################
#
sub _print{
   my $ref = shift;
   my $lib = shift;
   if(@$ref){
     my $nb;
     unless($xml){
       print "#" for(0..(length($lib)+3));
       print "\n";
       print "# ".$lib." #";
       print "\n";
       print "#" for(0..(length($lib)+3));
       print "\n";
       print "#----------------------------------------------------------------------------\n";
       printf("#%-20s %-20s %-25s %s\n", "IP address",  "MAC address", "DNS/Netbios", "Date");
       print "#----------------------------------------------------------------------------\n#\n";
     }
          
     $nb = 0;
     my $output;
     %xml = undef;
     for(@$ref){
	$$_[3] = &_smbname($$_[1]) if $$_[3] eq "-";
	if($xml){
	  $xml{'IP'} = [ $$_[1] ];
          $xml{'MAC'} = [ $$_[0] ]; 
          $xml{'NAME'} = [ $$_[3] ];
	  $xml{'DATE'} = [ $$_[2] ];
	  $xml{'TYPE'} = ['WINDOWS'] if $lib =~/windows/i;
	  $xml{'TYPE'} = ['LINUX'] if $lib =~/linux/i;
	  $xml{'TYPE'} = ['MAC'] if $lib =~/mac/i;
	  $xml{'TYPE'} = ['FILTERED'] if $lib =~/filtered/i;
	  $xml{'TYPE'} = ['NETWORK'] if $lib =~/network/i;
	  $xml{'TYPE'} = ['PHANTOM'] if $lib =~/phantom/i;
	  $output=XML::Simple::XMLout( \%xml, RootName => 'HOST', SuppressEmpty => undef);
          print $output;
	  print CACHE $output if $cache;
	}else{
	  printf("#-> %-15s %-20s %-20s %s\n",$$_[1],$$_[0],$$_[3],$$_[2]);
	  $nb++;
	}
     }
   unless($xml){
     print "#\n#==========> $nb host(s)\n#\n\n\n";
   }
  }
}
#
#########################################
#ROUTINE DE RECUPERATION DES NOMS NETBIOS
#########################################
#
sub _smbname{
  my $flag = 0;
  my $name;
  my $ip = shift;
  #If no dns name, we try  a netbios resolution
  my @smb = `nmblookup -A $ip`;
  #On cherche un enregistrment <00> pour l'ip passee en argument
  for(@smb){
    $name = "-";
    /Looking\D+(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/ unless $flag;
    if(/<00>/){
      /^\s+(\S+)/;
      $name = $1;
      return $name;
    }
  }
  return "-";
}

##############
#CLEAN *.gnmap
##############
#
sub _cleanup{
  my($name, @files);
  opendir DIR, $path;
  while($name = readdir DIR){
    push @files, $name if $name=~/\.gnmap$/i;
  }
  closedir DIR;
  for(@files){
    open FILE, $_ or next;
    unlink "$path/$_" if(flock(FILE, LOCK_EX|LOCK_NB));
  }
}

print "|\n|\n---------------------------------------------\n|" unless $xml;
if($list){
  print "\n|--> TOTAL = $total valid (ips/netmask) in the database\n" unless $xml;
  exit(0);
}

unless($xml){
  print "\n|--> TOTAL = $total unknown hosts\n";
  print "\n|--> WARNING: $null discovered computers without netmask on all discovered machine\n\n" if $null;
}
#Try to get dns name
sub _getdns{
  my $ip = shift;
  my $name;
  chomp(my @names = `host $ip 2>/dev/null`);
  for(@names){
   return $1 if /.+pointer (.+)/i;
   return $1 if /Name:\s*(\S+)/i;
  }
  return("-");
}

#Retrieve the name of the subnet if available
sub _getnetname{
  my $network = shift;
  my $r = shift;
  for(@subnet){
      if($_->[0] eq $network){
      return ($_->[1], $_->[2]);
      last;
    }
  }
  return($r, $r);

}

#To retrieve the net ip    
sub _network{
  my $ip = shift;
  my $netmask = shift;
  my $binip = &ip_iptobin($ip, 4);
  my $binmask = &ip_iptobin($netmask, 4);
  my $subnet = $binip & $binmask;
  return(&ip_bintoip($subnet, 4)) or die(Error());
} 

sub _bintoascii{
  my $binmask = shift;
  my $binstring = "1" x $binmask;
  $binstring .= "0" for(1..(32 - $binmask));
  
  return(&ip_bintoip($binstring, 4)) or die(Error());
}

sub _binmask{
  my $ip = shift;
  return(&ip_iptobin($ip, 4)) or die(Error());
}






