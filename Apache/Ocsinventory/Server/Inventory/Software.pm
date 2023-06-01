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
use POSIX qw(strftime);

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  _get_info_software
  _insert_software
  _prepare_sql
  _insert_software_categories_link
  _del_category_soft
  _trim_value
  _verif_software_exists
  _verif_software_already_in_cat
  _clean_software_version
  _get_info_software_version
  _split_version_number
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

    $query->execute or return undef;

    return $query;   
}

sub _insert_software_categories_link {
    my ($name, $publisher, $version, $category) = @_;
    my $sql;
    my $result;

    my @argInsert = ();

    # Insert if undef
    $sql = "INSERT INTO software_categories_link (NAME_ID, PUBLISHER_ID, VERSION_ID, CATEGORY_ID) VALUES(?,?,?,?)";
    push @argInsert, $name;
    push @argInsert, $publisher;
    push @argInsert, $version;
    push @argInsert, $category;

    $result = _prepare_sql($sql, @argInsert);
    if(!defined $result) { return undef; }

    return $result;
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
    if(!defined $resultVerif) { return undef; }

    while(my $row = $resultVerif->fetchrow_hashref()){
        $valueResult = $row->{ID};
    }

    if(!defined $valueResult) {
        my @argInsert = ();

        # Insert if undef
        $sql = "INSERT INTO $table ($column) VALUES(?)";
        push @argInsert, $value;

        $result = _prepare_sql($sql, @argInsert);
        if(!defined $result) { return undef; }

        # Get last Insert or Update ID
        my @argSelect = ();
        $sql = "SELECT ID FROM $table WHERE $column = ?";
        push @argSelect, $value;
        $result = _prepare_sql($sql, @argSelect);
        if(!defined $result) { return undef; }

        while(my $row = $result->fetchrow_hashref()){
            $valueResult = $row->{ID};
        }
    }

    return $valueResult;
}

sub _get_info_software_version {
    my ($value, $prettyValue, $table, $column, $column2) = @_;
    my $sql;
    my $valueResult = undef;
    my $prettyValueResult = undef;
    my $result;
    my $resultVerif;
    my $resultUpdate;

    # Split pretty version number (default set it to 0)
    my %splitVersion = (
        "MAJOR" => 0,
        "MINOR" => 0,
        "PATCH" => 0
    );

    # Verif if value exist
    my @argVerif = ();
    $sql = "SELECT ID, PRETTYVERSION FROM $table WHERE $column = ?";
    push @argVerif, $value;
    $resultVerif = _prepare_sql($sql, @argVerif);
    if(!defined $resultVerif) { return undef; }

    while(my $row = $resultVerif->fetchrow_hashref()){
        $valueResult = $row->{ID};
        $prettyValueResult = $row->{PRETTYVERSION};
    }

    if(defined $prettyValue && $prettyValue ne "Unavailable") {
        %splitVersion = _split_version_number($prettyValue);
    }

    # The update query is only at the first inventory after added PRETTYVERSION column
    if(defined $valueResult && !defined $prettyValueResult) {
        my @argUpdate = ();

        # Update version with pretty version
        $sql = "UPDATE $table SET PRETTYVERSION = ?, MAJOR = ?, MINOR = ?, PATCH = ? WHERE ID = ?";
        push @argUpdate, $prettyValue;
        push @argUpdate, $splitVersion{MAJOR};
        push @argUpdate, $splitVersion{MINOR};
        push @argUpdate, $splitVersion{PATCH};
        push @argUpdate, $valueResult;

        $resultUpdate = _prepare_sql($sql, @argUpdate);
        if(!defined $resultUpdate) { return undef; }
    }

    if(!defined $valueResult) {
        my @argInsert = ();

        # Insert if undef
        $sql = "INSERT INTO $table ($column,PRETTYVERSION,MAJOR,MINOR,PATCH) VALUES(?,?,?,?,?)";
        push @argInsert, $value;
        push @argInsert, $prettyValue;
        push @argInsert, $splitVersion{MAJOR};
        push @argInsert, $splitVersion{MINOR};
        push @argInsert, $splitVersion{PATCH};

        $result = _prepare_sql($sql, @argInsert);
        if(!defined $result) { return undef; }

        # Get last Insert or Update ID
        my @argSelect = ();
        $sql = "SELECT ID FROM $table WHERE $column = ?";
        push @argSelect, $value;
        $result = _prepare_sql($sql, @argSelect);
        if(!defined $result) { return undef; }

        while(my $row = $result->fetchrow_hashref()){
            $valueResult = $row->{ID};
        }
    }

    return $valueResult;
}

sub _verif_software_exists {
    my ($hardware_id, $name, $publisher, $version) = @_;
    my $sql;
    my @arg = ();
    my $result;
    my $id;

    $sql = "SELECT ID FROM software WHERE HARDWARE_ID = ? AND NAME_ID = ? AND PUBLISHER_ID = ? AND VERSION_ID = ?";
    push @arg, $hardware_id;
    push @arg, $name;
    push @arg, $publisher;
    push @arg, $version;

    $result = _prepare_sql($sql, @arg);
    if(!defined $result) { return 1; }

    while(my $row = $result->fetchrow_hashref()){
        $id = $row->{ID};
    }

    return $id;
}

sub _verif_software_already_in_cat {
    my ($name, $publisher, $version, $category) = @_;
    my $sql;
    my @arg = ();
    my $result;
    my $id;

    $sql = "SELECT ID FROM software_categories_link WHERE CATEGORY_ID = ? AND NAME_ID = ? AND PUBLISHER_ID = ? AND VERSION_ID = ?";
    push @arg, $category;
    push @arg, $name;
    push @arg, $publisher;
    push @arg, $version;

    $result = _prepare_sql($sql, @arg);
    if(!defined $result) { return 1; }

    while(my $row = $result->fetchrow_hashref()){
        $id = $row->{ID};
    }

    return $id;
}

sub _del_category_soft {
    my ($name, $publisher, $version) = @_;
    my $sql;
    my @arg = ();
    my $result;

    $sql = "DELETE FROM software_categories_link WHERE NAME_ID = ? AND PUBLISHER_ID = ? AND VERSION_ID = ?";
    push @arg, $name;
    push @arg, $publisher;
    push @arg, $version;
    $result = _prepare_sql($sql, @arg);

    if(!defined $result) { return 1; }

    return 0;
}

sub _trim_value {
    my ($toTrim) = @_;

    $toTrim =~ s/^\s+|\s+$//g;

    return $toTrim;
}

sub _clean_software_version {
    my ($version) = @_;

    # Remove int: if it found
    if(length($version) >= 2 && substr($version, 1, 1) eq ':') {
        $version = substr($version, 2);
    }

    $version =~ s/[\$#@~!&*()\[\];,:?^\-\+\_`a-zA-Z\\\/].*//g;

    if(_trim_value($version) eq '' || $version eq '0') {
        $version = "Unavailable";
    }

    return $version;
}

sub _split_version_number {
    my ($prettyVersion) = @_;

    my @versions = split /\./, $prettyVersion;

    my %splitVersion = (
        "MAJOR" => defined $versions[0] ? $versions[0] : 0,
        "MINOR" => defined $versions[1] ? $versions[1] : 0,
        "PATCH" => defined $versions[2] ? $versions[2] : 0,
    );

    return %splitVersion;
}

sub _insert_software {
    my $sql;
    my $hardware_id = $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'};
    my @arrayRef = ('HARDWARE_ID', 'NAME_ID', 
                    'PUBLISHER_ID', 'VERSION_ID', 
                    'FOLDER', 'COMMENTS', 'FILENAME', 
                    'FILESIZE', 'SOURCE', 'GUID', 
                    'LANGUAGE', 'INSTALLDATE', 'BITSWIDTH', 'ARCHITECTURE');
    my @softIdAlreadyExists;

    foreach my $software (@{$Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{SOFTWARES}}) {

        # Check install date format
        if(!defined $software->{INSTALLDATE} || $software->{INSTALLDATE} !~ /^\d{4}\/\d\d\/\d\d/ && $software->{INSTALLDATE} !~ /[1-9]{1}[0-9]{3}\/[0-9]{2}\/[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/) {
            $software->{INSTALLDATE} = strftime "%Y/%m/%d", localtime;
        }

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
            "BITSWIDTH"     => $software->{BITSWIDTH} // 0,
            "ARCHITECTURE"  => $software->{ARCHITECTURE} // ""
        );

        my $name = $software->{NAME};
        my $publisher = $software->{PUBLISHER};
        my $version = $software->{VERSION};
        my $category = $software->{CATEGORY};
        my @bind_num;
        my @bind_update;
        
        # Get software Name ID if exists
        if(defined $name) {
            $arrayValue{NAME_ID} = _get_info_software($name, "software_name", "NAME");
            if(!defined $arrayValue{NAME_ID}) { return 1; }
        }
        
        # Get software Publisher ID if exists
        if(defined $publisher) {
            my $trimPublisher = _trim_value($publisher);
            if($trimPublisher ne '') {
                $arrayValue{PUBLISHER_ID} = _get_info_software($publisher, "software_publisher", "PUBLISHER");
                if(!defined $arrayValue{PUBLISHER_ID}) { return 1; }
            }
        }

        # Get software Version ID if exists
        if(defined $version) {
            my $trimVersion = _trim_value($version);
            if($trimVersion ne '') {
                my $prettyVersion = _clean_software_version($version);
                $arrayValue{VERSION_ID} = _get_info_software_version($version, $prettyVersion, "software_version", "VERSION", "PRETTYVERSION");
                if(!defined $arrayValue{VERSION_ID}) { return 1; }
            }
        }

        # Verify if the software already exists on DB
        my $softId = _verif_software_exists($arrayValue{HARDWARE_ID}, $arrayValue{NAME_ID}, $arrayValue{PUBLISHER_ID}, $arrayValue{VERSION_ID});

        # If return id : save the id in array
        # If return undef : insert the software and save the id in array 
        if(defined $softId) { 
            push @softIdAlreadyExists, $softId; 
        } else {
            my $arrayRefString = join ',', @arrayRef;
            my @arg = ();

            foreach my $arrayKey(@arrayRef) {
                push @bind_num, '?';
                push @bind_update, $arrayKey.' = ?';
                push @arg, $arrayValue{$arrayKey};
            }

            $sql = "INSERT INTO software ($arrayRefString) VALUES(";
            $sql .= (join ',', @bind_num).') ';
            my $result = _prepare_sql($sql, @arg);

            if(!defined $result) { return 1; }

            # Verify if the software already exists on DB
            my $softIdInsert = _verif_software_exists($arrayValue{HARDWARE_ID}, $arrayValue{NAME_ID}, $arrayValue{PUBLISHER_ID}, $arrayValue{VERSION_ID});

            if(!defined $softIdInsert) { return 1; }

            push @softIdAlreadyExists, $softIdInsert;
        }

        # Check if software already in the correct software category
        # If return id : skip
        # If return undef : delete and insert
        if(defined $category && $category != 0) {
            my $softIdInCat = _verif_software_already_in_cat($arrayValue{NAME_ID}, $arrayValue{PUBLISHER_ID}, $arrayValue{VERSION_ID}, $category);
            if(!defined $softIdInCat) {
                # Delete software from software categories link
                if(_del_category_soft($arrayValue{NAME_ID}, $arrayValue{PUBLISHER_ID}, $arrayValue{VERSION_ID})) { return 1; }
                # Insert software in software categories link
                if(!_insert_software_categories_link($arrayValue{NAME_ID}, $arrayValue{PUBLISHER_ID}, $arrayValue{VERSION_ID}, $category)) { return 1; }
            }
        }
    }

    # Delete all softwares who are not in softIdAlreadyExists with this hardware_id
    if(@softIdAlreadyExists && defined $hardware_id) {
        my @arg = ();
        $sql = "DELETE FROM software WHERE HARDWARE_ID = ? AND ID NOT IN (";
        $sql .= (join ',', @softIdAlreadyExists).")";
        push @arg, $hardware_id;

        my $result = _prepare_sql($sql, @arg);

        if(!defined $result) { return 1; }
    }

    return 0;
}

1;
