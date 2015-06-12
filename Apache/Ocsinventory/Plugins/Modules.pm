################################################################################
## OCSINVENTORY-NG
## Copyleft Gilles DUBOIS 2015
## Web : http://www.ocsinventory-ng.org
##
## This code is open source and may be copied and modified as long as the source
## code is always made freely available.
## Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################

package Apache::Ocsinventory::Plugins::Modules;

use SOAP::Lite;
use strict;
use LWP::Simple;
use Archive::Zip;
use File::Copy;
use DBI;
  
sub InstallPlugins {
		
 my $dbh = DBI->connect("dbi:mysql:$ENV{OCS_DB_NAME}","$ENV{OCS_DB_USER}","$ENV{OCS_DB_PWD}")
 or die "Connection Error: $DBI::errstr\n";
 my $sql = "select name from plugins";
 my $sth = $dbh->prepare($sql);
 $sth->execute
 or die "SQL Error: $DBI::errstr\n";

 my @tableau ;

 while (@tableau = $sth->fetchrow_array) 
 {

	# Download the created archive from the ocsreports which contain the communication server code (.conf and map.pm)
	my $url = "http://$ENV{OCS_DB_HOST}/ocsreports/upload/@tableau.zip";
	my $file = "/etc/ocsinventory-server/@tableau.zip";
	
	our $test;
	
	if (-e $file) {
		$test = 1;
		print "Archive existante";
	}
	else
	{
		
		print "$url\n";
		
		my $status = getstore($url, $file);
	
		# If download succes, unzip, create dir, move files.
		if (is_success($status))
		{

			my $pluginsdir = "/etc/ocsinventory-server/plugins";
			my $zipname = $file;
			my $destinationDirectory = $pluginsdir;
			my $zip = Archive::Zip->new($zipname);
			my $member;
				
			foreach my $member ($zip->members)
			{
				next if $member->isDirectory;
				(my $extractName = $member->fileName) =~ s{.*/}{};
				$member->extractToFileNamed("$destinationDirectory/$extractName");
			}

			my $dirtocreate = "$pluginsdir/@tableau";
			mkdir $dirtocreate;

			move("$pluginsdir/Map.pm","$pluginsdir/@tableau/Map.pm");
		}
	}
 }

my $result = "Install OK";
return( SOAP::Data->name( 'Result' => $result )->type( 'string' ) );

}

# Seek for deleted plugins // Delete map.pm and conf entry.

sub DeletePlugins {
	my ( $PluginName ) = @_;
	
	my $result = "Delete OK";
    return( SOAP::Data->name( 'Result' => $result )->type( 'string' ) );
}

1;
