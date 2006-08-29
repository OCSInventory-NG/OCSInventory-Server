#!/usr/bin/perl -w
###############################################################################
##OCS inventory-NG Version 1.0 Beta
##Copyleft Pascal DANEK 2005
##Copyleft Goneri Le Bouder 2006
##Web : http://ocsinventory.sourceforge.net
##
##This code is open source and may be copied and modified as long as the source
##code is always made freely available.
##Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
#Last modification : $Id: Ocsinventory_local.pl,v 1.2 2006-08-29 14:56:39 hunal Exp $
#Local insertion script
use Fcntl qw/:flock/;
use LWP::UserAgent;
use XML::Simple;
use Compress::Zlib;
use Getopt::Long;
use constant VERSION => 2;
use strict;
my $help;
my $directory;
my $file;
my $url;
my $useragent;
my $remove;
my $debug;
my $stdin;


sub loadfile {
    my $file = shift;

    unless ( -r $file ) {
        print STDERR "Can't read $file\n";
        return;
}
    print "Loading $file\n" if $debug;

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

sub checkContent {
    my $content = shift;
    $content = Compress::Zlib::uncompress($content) or return;
    my $ret = XMLin ($content);
    print STDERR $@.'\n' if($@);
    $ret;
}

sub sendContent {
    my $content = shift;

    # report MUST be compressed
    if (!Compress::Zlib::uncompress($content)) {
      $content = Compress::Zlib::compress($content);
      if (!$content) {
        print STDERR "$file Compression error\n";
        return;
        }
}

    print STDERR "Invalid content\n"
        if ($debug && !checkContent($content));
 
    my $ua = LWP::UserAgent->new;
    $ua->agent($useragent);
    my $request = HTTP::Request->new( POST => $url );
    $request->header(
        'Pragma' => 'no-cache',
        'Content-type', 'Application/x-compress'
    );
    $request->content("$content");
    my $res = $ua->request($request);

    print $res->status_line."\n" if ($debug);

    my $ret_msg = Compress::Zlib::uncompress($res->content);
    print "Response from server:\n$ret_msg" if ($ret_msg && $debug);

	if($res->is_success){
        print STDERR "Can't remove $file: $!\n" 
            if ($remove && (!unlink $file));
	}else{
        print "Upload failed\n";
	}
}

sub usage {

    print "
    DESCRIPTION:
    \tA command line tools to import ocsinventory xml file.
    USAGE:
    \t-h --help => this menu\n
    \t-d --directory => load every .ocs files from a directory\n
    \t-f --file => load a speficic file\n
    \t-u --url => ocsinventory backend URL, default is http://ocsinventory-ng/ocsinventory\n
    \t--useragent => HTTP user agent, default is OCS-NG_LOCAL_PL_v".VERSION."\n
    \t-r --remove => remove succesfuly injected files\n
    \t-m --msg => show message returned my ocsinventory\n
    \t--debug => debug mode (more verbose)\n
    \t--stdin => read data from STDIN\n
    You must open a --file or a --directory or STDIN.\n";
    exit 1;
}

GetOptions(
    'h|help'        => \$help,
    'd|directory=s' => \$directory,
    'f|file=s'      => \$file,
    'u|url=s'         => \$url,
    'useragent=s'   => \$useragent,
    'r|remove'   => \$remove,
    'debug'   => \$debug,
    'stdin'   => \$stdin,
);
$url = 'http://ocsinventory-ng/ocsinventory' unless ($url);
$useragent = 'OCS-NG_LOCAL_PL_v'.VERSION unless ($useragent);
$remove = 0 if $stdin;

if ($directory && -d $directory) {
    loaddirectory($directory);
}
elsif ($file && -f $file) {
    loadfile($file);
}
elsif ($stdin) {
    loadstdin();
} else {
usage();
}
