###############################################################################
## Copyright 2005-2020 OCSInventory-NG/OCSInventory-Server contributors.
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
package Apache::Ocsinventory::Server::Inventory::Software;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Internals;

use strict;
use warnings;
use Switch;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  _get_info_software
  _del_all_soft
  _insert_software
  _prepare_sql
  _insert_software_name
/;

sub _prepare_sql {
    my ($sql, @arguments) = @_;
    my $query;
    my $i = 1;

    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};

    $query = $dbh->prepare($sql);
    foreach my $value (@arguments) {
        $query->bind_param($i, $value);
        $i++;
    }
    $query->execute; 

    return $query;   
}

sub _insert_software_name {
    my ($name, $cat) = @_;
    my $sql;
    my $categoryVerif = undef;
    my $valueResult = undef;
    my $result;

    # Verif if value exist
    my @argVerif = ();
    $sql = "SELECT ID, CATEGORY FROM software_name WHERE NAME = ?";
    push @argVerif, $name;
    $result = _prepare_sql($sql, @argVerif);

    while(my $row = $result->fetchrow_hashref()){
        $valueResult = $row->{ID};
        $categoryVerif = $row->{CATEGORY};
    }

    if(!defined $valueResult) {
        my @argInsert = ();
        if(!defined $cat) {
            # Insert if undef
            $sql = "INSERT INTO software_name (NAME) VALUES(?)";
            push @argInsert, $name;
        } else {
            # Insert if undef
            $sql = "INSERT INTO software_name (NAME,CATEGORY) VALUES(?,?)";
            push @argInsert, $name;
            push @argInsert, $cat;
        }
        _prepare_sql($sql, @argInsert);

        # Get last Insert or Update ID
        my @argSelect = ();
        $sql = "SELECT ID FROM software_name WHERE NAME = ?";
        push @argSelect, $name;
        $result = _prepare_sql($sql, @argSelect);

        while(my $row = $result->fetchrow_hashref()){
            $valueResult = $row->{ID};
        }
    }

    if(defined $cat) {
        if((!defined $categoryVerif) || ($cat != $categoryVerif)) {
            my @argUpdate = ();
            my $sqlUpdate = "UPDATE software_name SET CATEGORY = ? WHERE ID = ?";
            push @argUpdate, $cat;
            push @argUpdate, $valueResult;
            _prepare_sql($sqlUpdate, @argUpdate);
        }
    }

    return $valueResult;
}

sub _get_info_software {
    my ($value, $table, $column) = @_;
    my $sql;
    my $valueResult = undef;
    my $result;
    my $resultVerif;

    # Verif if value exist
    my @argVerif = ();
    $sql = "SELECT ID FROM $table WHERE $column = ?";
    push @argVerif, $value;
    $resultVerif = _prepare_sql($sql, @argVerif);

    while(my $row = $resultVerif->fetchrow_hashref()){
        $valueResult = $row->{ID};
    }

    if(!defined $valueResult) {
        my @argInsert = ();

        # Insert if undef
        $sql = "INSERT INTO $table ($column) VALUES(?)";
        push @argInsert, $value;

        _prepare_sql($sql, @argInsert);

        # Get last Insert or Update ID
        my @argSelect = ();
        $sql = "SELECT ID FROM $table WHERE $column = ?";
        push @argSelect, $value;
        $result = _prepare_sql($sql, @argSelect);

        while(my $row = $result->fetchrow_hashref()){
            $valueResult = $row->{ID};
        }
    }

    return $valueResult;
}

sub _del_all_soft {
    my ($hardware_id) = @_;
    my $sql;
    my @arg = ();
    my $result;
    my $id = 0;

    $sql = "DELETE FROM software WHERE HARDWARE_ID = ?";
    push @arg, $hardware_id;
    $result = _prepare_sql($sql, @arg);
}

sub _insert_software {
    my $sql;
    my $hardware_id = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
    my @arrayRef = ('HARDWARE_ID', 'NAME_ID', 
                    'PUBLISHER_ID', 'VERSION_ID', 
                    'FOLDER', 'COMMENTS', 'FILENAME', 
                    'FILESIZE', 'SOURCE', 'GUID', 
                    'LANGUAGE', 'INSTALLDATE', 'BITSWIDTH');

    _del_all_soft($hardware_id);
    
    foreach my $software (@{$Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{SOFTWARES}}) {
        my %arrayValue = (
            "HARDWARE_ID"   => $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'},
            "NAME_ID"       => 1,
            "PUBLISHER_ID"  => 1,
            "VERSION_ID"    => 1, 
            "FOLDER"        => $software->{FOLDER} // "",
            "COMMENTS"      => $software->{COMMENTS} // "",
            "FILENAME"      => $software->{FILENAME} // "",
            "FILESIZE"      => $software->{FILESIZE} // 0,
            "SOURCE"        => $software->{SOURCE} // 0,
            "GUID"          => $software->{GUID} // "",
            "LANGUAGE"      => $software->{LANGUAGE} // "",
            "INSTALLDATE"   => $software->{INSTALLDATE},
            "BITSWIDTH"     => $software->{BITSWIDTH} // 0
        );
        my $name = $software->{NAME};
        my $publisher = $software->{PUBLISHER};
        my $version = $software->{VERSION};
        my @bind_num;
        my @bind_update;
        
        # Get software Name ID if exists
        if(defined $name) {
            $arrayValue{NAME_ID} = _insert_software_name($name, $software->{CATEGORY});
        }
        
        # Get software Publisher ID if exists
        if(defined $publisher && $publisher ne '') {
            $arrayValue{PUBLISHER_ID} = _get_info_software($publisher, "software_publisher", "PUBLISHER");
        }

        # Get software Version ID if exists
        if(defined $version && $version ne '') {
            $arrayValue{VERSION_ID} = _get_info_software($version, "software_version", "VERSION");
        }

        my $arrayRefString = join ',', @arrayRef;
        my @arg = ();
        foreach my $arrayKey(@arrayRef) {
            push @bind_num, '?';
            push @bind_update, $arrayKey.' = ?';
            push @arg, $arrayValue{$arrayKey};
        }

        $sql = "INSERT INTO software ($arrayRefString) VALUES(";
        $sql .= (join ',', @bind_num).') ';
        _prepare_sql($sql, @arg);
    }
}

1;
