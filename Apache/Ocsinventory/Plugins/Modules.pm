###############################################################################
## Copyright 2005-2016 OCSInventory-NG/OCSInventory-Server contributors.
## See the Contributors file for more details about them.
## 
## This file is part of OCSInventory-NG/OCSInventory-ocsreports.
##
## OCSInventory-NG/OCSInventory-Server is free software: you can redistribute
## it and/or modify it under the terms of the GNU General Public License as
## published by the Free Software Foundation, either version 2 of the License,
## or (at your option) any later version.
##
## OCSInventory-NG/OCSInventory-Server is distributed in the hope that it
## will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
## of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
## GNU General Public License for more details.
##
## You should have received a copy of the GNU General Public License
## along with OCSInventory-NG/OCSInventory-ocsreports. if not, write to the
## Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
## MA 02110-1301, USA.
################################################################################

package Apache::Ocsinventory::Plugins::Modules;

use SOAP::Lite;
use strict;
use LWP::Simple;
use Archive::Zip;
use File::Copy;
use File::Path;
use DBI;
  
sub InstallPlugins {

    my $pluginName = $_[1];

    # Download the created archive from the ocsreports which contain the communication server code (.conf and map.pm)
    my $url = "http://$ENV{OCS_DB_HOST}/ocsreports/upload/$pluginName.zip";
    my $file = "$ENV{OCS_PLUGINS_CONF_DIR}/$pluginName.zip";
    our $result;
    our $perm = 1;

    #Up case plugin directory in OCS server for match with actual template
    our $pluginNameUc = ucfirst($pluginName);

    if (-e "$ENV{OCS_PLUGINS_CONF_DIR}/$pluginName.conf") {
        $result = "Err_01";
    }
    elsif(-e "$ENV{OCS_PLUGINS_PERL_DIR}/Apache/Ocsinventory/Plugins/$pluginNameUc"){
        $result = "Err_05";
    }
    else
    {

        my $status = getstore($url, $file);

        # If download succes, unzip, create dir, move files.
        if (is_success($status))
        {

            # Check for write perm in plugins dir
            if(!(-w "$ENV{OCS_PLUGINS_CONF_DIR}"))
            {
                $result = "Err_03";
                $perm = 0;
            }
            # Check for write perm in perl dir
            if(!(-w "$ENV{OCS_PLUGINS_PERL_DIR}/Apache/Ocsinventory/Plugins"))
            {
                $result = "Err_04";
                $perm = 0;
            }

            if($perm){
                my $pluginsdir = "$ENV{OCS_PLUGINS_CONF_DIR}";
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

                my $dirtocreate = "$ENV{OCS_PLUGINS_PERL_DIR}/Apache/Ocsinventory/Plugins/$pluginNameUc";
                mkdir $dirtocreate;

                unlink $file;
                move("$pluginsdir/Map.pm","$ENV{OCS_PLUGINS_PERL_DIR}/Apache/Ocsinventory/Plugins/$pluginNameUc/Map.pm");

                $result = "Install_OK";
            }

        }else{
            $result = "Err_02";
        }

    }

    return( SOAP::Data->name( 'Result' => $result )->type( 'string' ) );

}

# Seek for deleted plugins // Delete map.pm and conf entry.
sub DeletePlugins {
	
    my $pluginName = $_[1];

    #Up case plugin directory in OCS server for match with actual template for deletion
    my $pluginNameUc = ucfirst($pluginName);

    my $pluginsdir = "$ENV{OCS_PLUGINS_CONF_DIR}";

    if (-e "$ENV{OCS_PLUGINS_CONF_DIR}/$pluginName.conf"){
            unlink "$ENV{OCS_PLUGINS_CONF_DIR}/$pluginName.conf";
    }

    if($pluginNameUc != ""){
    	my $dirToDel = "$ENV{OCS_PLUGINS_PERL_DIR}/Apache/Ocsinventory/Plugins/$pluginNameUc";
	rmtree($dirToDel);
    }

    my $result = "Delete_OK";

    return( SOAP::Data->name( 'Result' => $result )->type( 'string' ) );
}

1;
