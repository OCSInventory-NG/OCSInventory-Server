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
package Apache::Ocsinventory::Interface::Saas;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Internals;

use strict;
use warnings;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  _get_saas
  set_saas
/;


sub set_saas{
    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
    my @saas_exp = get_saas();
    my $saas_en = 0;
    my $sql;

    $sql = $dbh->prepare("SELECT ivalue FROM config WHERE config.name='INVENTORY_SAAS_ENABLED'");
    $sql->execute;

    while (my $row = $sql->fetchrow_hashref()){
        $saas_en = $row->{ivalue};
    }

    if ($saas_en == 1) {
        foreach my $saas (@{$Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{SAAS}}){
            my $entry = $saas->{ENTRY};
            foreach my $exp (@saas_exp) {
                my $dnsExp = $exp->{DNS_EXP};
                $dnsExp =~ s/\?/\./g;
                $dnsExp =~ s/\*/\.\*/g;
                if ($entry =~ /$dnsExp/ || $dnsExp =~ /$entry/ ) {
                    $dbh->do("INSERT INTO saas (SAAS_EXP_ID, HARDWARE_ID, ENTRY, DATA, TTL) VALUES (?,?,?,?,?)", {},  $exp->{ID}, $Apache::Ocsinventory::CURRENT_CONTEXT{'DATABASE_ID'}, $entry, $saas->{DATA}, $saas->{TTL});
                }

            }

        }

    }

    return 1;
}

sub get_saas{
    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
    my $sql;
    my @saas_exp;
    my $result;

    $sql = "SELECT s.ID, s.NAME, s.DNS_EXP FROM saas_exp s";
    $result = $dbh->prepare($sql);
    $result->execute;

    while( my $row = $result->fetchrow_hashref() ){
        push @saas_exp, {
            'ID' => $row->{ID},
            'NAME' => $row->{NAME},
            'DNS_EXP' =>  $row->{DNS_EXP}
        }
    }

    return @saas_exp;
}
1;

