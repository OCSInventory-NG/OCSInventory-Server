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
package Apache::Ocsinventory::Interface::AssetCategory;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Internals;

use strict;
use warnings;

use DBI qw(:sql_types);

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  set_asset_category
/;

sub set_asset_category{
    my @cats = get_asset_category();
    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
    my $hardware = $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{HARDWARE};

    foreach my $cat (@cats) {
        my @args = split(/,/, $cat->{SQL_ARGS});
        my @part_query = split(/\?/, $cat->{SQL_QUERY});

        my $query;
        my $execution;

        for (my $i = 0; $i < scalar @part_query; $i++){
            if ($args[$i]) {
                $query .= $part_query[$i] . $args[$i];
            } else {
                $query .= $part_query[$i];
            }
        }

        $execution = $dbh->prepare($query);
        $execution->execute();

        while (my $row = $execution->fetchrow_hashref()){
            if ($row->{hardwareNAME} eq $hardware->{NAME}) {
                $Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{HARDWARE}->{CATEGORY_ID} = $cat->{ID};
            }
        }
    }
    return 1;
}

sub get_asset_category{
    my $sth;
    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
    my $sql;
    my @cats;

    $sql = "SELECT ID, SQL_QUERY, SQL_ARGS FROM assets_categories";

    my $result = $dbh->prepare($sql);
    $result->execute;

    while( my $row = $result->fetchrow_hashref() ){
        my $sql_query = $row->{SQL_QUERY};
        $sql_query =~ s/%s/?/g;

        push @cats, {
            'ID' => $row->{ID},
            'SQL_QUERY' => $sql_query,
            'SQL_ARGS' =>  $row->{SQL_ARGS}
        }
    }

    return @cats;
}
1;
