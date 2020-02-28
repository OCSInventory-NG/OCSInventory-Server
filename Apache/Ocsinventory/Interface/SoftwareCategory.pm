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
package Apache::Ocsinventory::Interface::SoftwareCategory;

use Apache::Ocsinventory::Map;
use Apache::Ocsinventory::Interface::Database;
use Apache::Ocsinventory::Interface::Internals;

use strict;
use warnings;
use Switch;

require Exporter;

our @ISA = qw /Exporter/;

our @EXPORT = qw /
  _get_category_software
  _compare
  _regex
  set_category
/;

{   no strict 'refs';
    # When called like __PACKAGE__->$op( ... ),  __PACKAGE__ is $_[0]
    *{'bigger'}  = sub { return $_[1] >= $_[2]; };
    *{'less'}  = sub { return $_[1] <= $_[2]; };
    *{'equal'} = sub { return $_[1] == $_[2]; };
}


sub get_category_software{
    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};
    my $sql;
    my @cats;
    my $result;

    $sql = "SELECT c.ID, c.CATEGORY_NAME, c.OS, s.SOFTWARE_EXP, s.SIGN_VERSION, s.VERSION, s.PUBLISHER FROM software_categories c, software_category_exp s WHERE s.CATEGORY_ID = c.ID";
    $result = $dbh->prepare($sql);
    $result->execute;

    while( my $row = $result->fetchrow_hashref() ){
        push @cats, {
            'ID' => $row->{ID},
            'CATEGORY_NAME' => $row->{CATEGORY_NAME},
            'OS' => $row->{OS},
            'SOFTWARE_EXP' =>  $row->{SOFTWARE_EXP},
            'SIGN_VERSION' => $row->{SIGN_VERSION},
            'VERSION' =>  $row->{VERSION},
            'PUBLISHER' => $row->{PUBLISHER}
        }
    }

    return @cats;
}

sub compare{
    my ($sign,$version,$publisher,$v,$p) = @_;

    if ( (defined $publisher) && ($publisher ne '')) {
      if ( (__PACKAGE__->$sign($v, $version)) && ($publisher eq $p) ) {
        return 2;
      } else {
        return undef;
      }
    } else {
      if ( __PACKAGE__->$sign($v, $version) ) {
        return 2;
      } else {
        return undef;
      }
    }
}

sub regex{
    my ($regex) = @_;

    if(($regex !~ m/\?/) && ($regex !~ m/\*/)){
      $regex = "\^".$regex."\$";
    }
    if((substr( $regex, -1) eq '*') && (substr( $regex, 0, 1) eq '*')){
      $regex = $regex =~ s/\*//gr;
    }
    if((substr( $regex, 0, 1 ) eq '*') && (substr( $regex, -1) ne '*')){
      $regex = $regex =~ s/\*//gr;
      $regex = $regex."\$";
    }
    if((substr( $regex, -1) eq '*') && (substr( $regex, 0, 1) ne '*')){
      $regex = $regex =~ s/\*//gr;
      $regex = "\^".$regex;
    }
    if(($regex =~ m/\?/)){
      $regex = $regex =~ s/\?/./gr;
    }

    return $regex;
}

sub set_category{
    my $dbh = $Apache::Ocsinventory::CURRENT_CONTEXT{'DBI_HANDLE'};

    my @cats = get_category_software();
    my $soft_cat;
    my $default_cat;

    my $sql = $dbh->prepare("SELECT ivalue FROM config WHERE config.name='DEFAULT_CATEGORY'");
    $sql->execute;
    while (my $row = $sql->fetchrow_hashref()){
        $default_cat = $row->{ivalue};
    }

    foreach my $soft (@{$Apache::Ocsinventory::CURRENT_CONTEXT{'XML_ENTRY'}->{CONTENT}->{SOFTWARES}}){
        foreach my $cat (@cats){
            my $regex = $cat->{SOFTWARE_EXP};
            my $sign = $cat->{SIGN_VERSION};
            my $version = $cat->{VERSION};
            my $publisher = $cat->{PUBLISHER};
            my $os = $cat->{OS};
            my $softName = $soft->{NAME};
            my $response;
            my $minV;
            my $majV;

            $regex = regex($regex);

            if(( $os eq 'ALL' ) || ( $Apache::Ocsinventory::CURRENT_CONTEXT{'USER_AGENT'} =~ m/$os/ )){
              if (defined $softName) {
                if ($softName =~ $regex) {
                  if( ( defined $sign ) && ( $sign ne '' )){
                      switch ($sign) {
                        case "EQUAL" {
                            $minV = $version =~ s/\.//gr;
                            $majV = $soft->{VERSION} =~ s/\.//gr;
                            $response = compare('equal',$minV,$publisher,$majV,$soft->{PUBLISHER});
                            if( defined $response) {
                                $soft_cat = $cat->{ID};
                            }
                        }
                        case "LESS" {
                            $minV = $version =~ s/\.//gr;
                            $majV = $soft->{VERSION} =~ s/\.//gr;
                            $response = compare('less',$minV,$publisher,$majV,$soft->{PUBLISHER});
                            if( defined $response) {
                                $soft_cat = $cat->{ID};
                            }
                        }
                        case "MORE" {
                            $minV = $version =~ s/\.//gr;
                            $majV = $soft->{VERSION} =~ s/\.//gr;
                            $response = compare('bigger',$minV,$publisher,$majV,$soft->{PUBLISHER});
                            if( defined $response) {
                                $soft_cat = $cat->{ID};
                            }
                        }
                      }
                  }
  		            if ( (defined $publisher) && ( $publisher ne '' ) && ( $sign eq '' ) ) {
                      my $softPublisher = $soft->{PUBLISHER};
                      if ( (defined $softPublisher) && ($publisher eq $softPublisher) ) {
                          $soft_cat = $cat->{ID};
                      }
                  } if( ($publisher eq '') && ($sign eq '')) {
                      $soft_cat = $cat->{ID};
                  }
                }
              }
            }
          }
        if (!defined $soft_cat) {
            $soft_cat = $default_cat;
        }

        $soft->{CATEGORY} = $soft_cat;
        $soft_cat = undef;
    }
    return 1;
}
1;
