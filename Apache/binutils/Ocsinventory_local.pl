#!/usr/bin/perl -w
###############################################################################
##OCS inventory-NG Version 1.0 Beta
##Copyleft Pascal DANEK 2005
##Web : http://ocsinventory.sourceforge.net
##
##This code is open source and may be copied and modified as long as the source
##code is always made freely available.
##Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
#Last modification : 30/08/2005
#Local insertion script
#Just launch it with files name as parameters
use Fcntl qw/:flock/;
use LWP::UserAgent;
use constant VERSION => 1;
my $err=0;
my $success=0;
#To avoid race condition. 
unless($ENV{'USER'}){
	open LOG, ">>Local.log";
	flock(LOG, LOCK_EX|LOCK_NB) or die "Insertions pending !! - Abort\n";
	select(LOG);
}

#If no arguments given, the script handle the current directory
if(@ARGV==0){
	opendir DIR, ".";
	while($name = readdir DIR){
	  push @files, $name if $name=~/\.ocs$/i;
	}
	closedir DIR;
}else{
	@files=@ARGV;
}

exit unless @files;
for $filename (@files){
	unless (-f $filename){next;}
	$ua=LWP::UserAgent->new;
	$ua->agent('OCS_LOCAL_PL'.VERSION);
	#####
	#HTTP
	#####
	$URI="http://localhost/ocsinventory";
	unless(open FILE, "$filename"){
		print "Failed to open $filename : $!";
		next;
	}
	flock (FILE, LOCK_EX|LOCK_NB) or close(FILE),next;
	#Get inventory into the requested file
	undef $data;
	while(<FILE>){$data.=$_};
	#Send inventory on the loopback
	$request = HTTP::Request->new(POST => $URI);
	$request->header('Pragma' => 'no-cache', 'Content-type', 'Application/x-compress');
	$request->content("$data");
	$res=$ua->request($request);
	if($res->is_success){
		print "OK for $filename\n"; 
		$success++;
	}else{
		print "Problem with $filename : ".$res->status_line."\n";
		$err++;
	}
	close FILE;
}

print <<EOF;
---------------------------
Successly inventoried : $success
Errors : $err
:-)
EOF
