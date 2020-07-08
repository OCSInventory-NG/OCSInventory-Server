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
  _verif_soft_exists
  _insert_software
  _add_category
  _prepare_sql
/;

sub _prepare_sql {
    my ($sql, @arguments) = @_;
    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
    my $query;
    my $i = 1;

    $query = $dbh->prepare($sql);
    foreach my $value (@arguments) {
        $query->bind_param($i, $value);
        $i++;
    }
    $query->execute; 

    return $query;   
}

sub _get_info_software {
    my ($value, $table, $column) = @_;
    my $sql;
    my $valueResult;
    my $result;
    my $resultVerif;
    my $valueVerif = undef;

    # Verif if value exist
    my @argVerif = ();
    $sql = "SELECT ID FROM $table WHERE $column = ?";
    push @argVerif, $value;
    $resultVerif = _prepare_sql($sql, @argVerif);

    while(my $row = $resultVerif->fetchrow_hashref()){
        $valueVerif = $row->{ID};
    }

    my @argInsert = ();

    if(!defined $valueVerif) {
        # Insert if undef
        $sql = "INSERT INTO $table ($column) VALUES(?)";
        push @argInsert, $value;
    }

    _prepare_sql($sql, @argInsert);

    # Get last Insert or Update ID
    my @argSelect = ();
    $sql = "SELECT ID FROM $table WHERE $column = ?";
    push @argSelect, $value;
    $result = _prepare_sql($sql, @argSelect);

    while(my $row = $result->fetchrow_hashref()){
        $valueResult = $row->{ID};
    }

    return $valueResult;
}

sub _verif_soft_exists {
    my %softValue = @_;
    my $sql;
    my @arg = ();
    my $result;
    my $id = 0;

    $sql = "SELECT ID FROM software WHERE HARDWARE_ID = ? AND NAME_ID = ? AND PUBLISHER_ID = ? AND VERSION_ID = ?";
    push @arg, $softValue{HARDWARE_ID};
    push @arg, $softValue{NAME_ID};
    push @arg, $softValue{PUBLISHER_ID};
    push @arg, $softValue{VERSION_ID};
    $result = _prepare_sql($sql, @arg);

    while(my $row = $result->fetchrow_hashref()){
        $id = $row->{ID};
    }

    return $id;
}

sub _add_category {
    my ($name, $category) = @_;
    my $sql;
    my @arg = ();

    $sql = "UPDATE software_name SET CATEGORY = ? WHERE NAME = ?";
    push @arg, $category;
    push @arg, $name;
    _prepare_sql($sql, @arg);
}

sub _insert_software {
    my $sql;
    my @arrayRef = ('HARDWARE_ID', 'NAME_ID', 
                    'PUBLISHER_ID', 'VERSION_ID', 
                    'FOLDER', 'COMMENTS', 'FILENAME', 
                    'FILESIZE', 'SOURCE', 'GUID', 
                    'LANGUAGE', 'INSTALLDATE', 'BITSWIDTH');
    
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
            $arrayValue{NAME_ID} = _get_info_software($name, "software_name", "NAME");
            if(defined $software->{CATEGORY}) {
                _add_category($name, $software->{CATEGORY});
            }
        }
        
        # Get software Publisher ID if exists
        if(defined $publisher && $publisher ne '') {
            $arrayValue{PUBLISHER_ID} = _get_info_software($publisher, "software_publisher", "PUBLISHER");
        }

        # Get software Version ID if exists
        if(defined $version && $version ne '') {
            $arrayValue{VERSION_ID} = _get_info_software($version, "software_version", "VERSION");
        }
  
        my $verif = _verif_soft_exists(%arrayValue);

        my $arrayRefString = join ',', @arrayRef;
        my @arg = ();
        foreach my $arrayKey(@arrayRef) {
            push @bind_num, '?';
            push @bind_update, $arrayKey.' = ?';
            push @arg, $arrayValue{$arrayKey};
        }  
  
        if($verif == 0) {
            $sql = "INSERT INTO software ($arrayRefString) VALUES(";
            $sql .= (join ',', @bind_num).') ';
            _prepare_sql($sql, @arg);
        } else {
            $sql = "UPDATE software SET ";
            $sql .= join ',', @bind_update;
            $sql .= " WHERE HARDWARE_ID = ? AND NAME_ID = ?
            AND PUBLISHER_ID = ? AND VERSION_ID = ?";
            push @arg, $arrayValue{HARDWARE_ID};
            push @arg, $arrayValue{NAME_ID};
            push @arg, $arrayValue{PUBLISHER_ID};
            push @arg, $arrayValue{VERSION_ID};
            _prepare_sql($sql, @arg);
        }
    }
}