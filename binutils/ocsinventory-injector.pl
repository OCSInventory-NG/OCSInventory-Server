#!/usr/bin/perl -w
###############################################################################
##OCS inventory-NG Version 1.02
##Copyleft Pascal DANEK 2005
##Copyleft Goneri Le Bouder 2006
##Web : http://www.ocsinventory-ng.org
##
##This code is open source and may be copied and modified as long as the source
##code is always made freely available.
##Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
#Last modification : $Id: ocsinventory-injector.pl,v 1.3 2008-02-18 07:17:52 hunal Exp $
#Local insertion script
use Fcntl qw/:flock/;
use LWP::UserAgent;
use XML::Simple;
use Compress::Zlib;
use Getopt::Long;
use constant VERSION => 3;
use strict;
my $help;
my $directory;
my $file;
my $url;
my $sslmode;
my $cafile;
my $useragent;
my $remove;
my $verbose;
my $stdin;
my $timeout;

sub loadfile {
    $file = shift;

    unless ( -r $file ) {
        print STDERR "Can't read $file\n";
        return;
    }
    print "Loading $file..." if $verbose;

    unless ( open( FILE, "$file" ) && flock( FILE, LOCK_EX | LOCK_NB ) ) {
        print STDERR "Failed to access $file : $!";
        return;
    }

    local $/;
    my $content = <FILE>;
    close FILE or die "Can't close file $file: $!";
    
    sendContent($content);

}

sub loaddirectory {
    my $directory = shift;

    unless ( -r $directory ) {
        print STDERR "Can't read $directory: $!\n";
        return;
    }

    opendir( DIR, $directory ) || die "can't opendir $directory: $!";
    foreach ( readdir(DIR) ) {
        loadfile("$directory/$_") if (/\.ocs$/);
	}
	closedir DIR;

}

sub loadstdin {
    my $content;
    undef $/;
    $content = <STDIN>;
    sendContent($content);
}

sub sendContent {
    my $content = shift;

    my $ua = LWP::UserAgent->new(
        protocols_allowed => ['http', 'https'],
        timeout           => $timeout,
        ssl_opts => { 
            verify_hostname => $sslmode,
            SSL_ca_file => $cafile
        },
    );

    $ua->agent($useragent);
    my $request = HTTP::Request->new( POST => $url );
    $request->header(
        'Pragma' => 'no-cache',
        'Content-type', 'Application/x-compress'
    );
    $request->content("$content");
    my $res = $ua->request($request);

    if($res->is_success){
      print "OK\n" if $verbose;
      print STDERR "Can't remove $file: $!\n"
	      if ($remove && (!unlink $file));
    }else{
    	if($verbose){
	 		 print "ERROR: ";
	 		 print $res->status_line(), "\n";
			}
    }
}

sub usage {

    print <<EOF;

    DESCRIPTION:
        A command line tools to import .ocs files.

    USAGE:
    -h --help	: this menu
    -d --directory	: load every .ocs files from a directory
    -f --file\	: load a speficic file
    -u --url	: ocsinventory backend URL, default is http://ocsinventory-ng/ocsinventory
    --sslmode   : 1 or 0, enable SSL inventory injection
    --cafile    : path to certificate
    --useragent	: HTTP user agent, default is OCS-NG_LOCAL_PL_v".VERSION."
    -r --remove	: remove successfully injected files
    -v --verbose	: verbose mode
    -t --timeout	: injector timeout value 
    --stdin		: read data from STDIN

    You can specify a --file or a --directory or STDIN. Current directory is the default

EOF
    exit 1;
}

GetOptions(
    'h|help'		=> \$help,
    'd|directory=s'	=> \$directory,
    'f|file=s'		=> \$file,
    'u|url=s'		=> \$url,
    'sslmode=s'     => \$sslmode,
    'cafile=s'		=> \$cafile,
    'useragent=s'	=> \$useragent,
    'r|remove'		=> \$remove,
    'v|verbose'		=> \$verbose,
    't|timeout'		=> \$timeout,
    'stdin'		=> \$stdin,
);

# Default values
$url		= 'http://localhost/ocsinventory' unless $url;
$useragent	= 'OCS-NG_INJECTOR_PL_v'.VERSION unless $useragent;
$directory	= '.' unless $directory;
$sslmode	= 0 unless $sslmode;
$cafile	    = "cacert.pem" unless $cafile;
$timeout	= 10 unless $timeout;
###

$|=1;

if ($file && -f $file) {
    loadfile($file);
}
elsif ($stdin) {
    loadstdin();
} 
elsif($help){
    usage();
}
else{
   if ($directory && -d $directory) {
     loaddirectory($directory);
   }
   else{
     die("Directory does not exist. Abort.");
   }
}
