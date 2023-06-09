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
  _remove_special_char
  set_category
  _clean_software_version
  _trim_value
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

# Removed special characters that could break the regex
sub remove_special_char{
    my ($string) = @_;

    if(($string =~ m/\(/)){
      $string = $string =~ s/\(//gr;
    }

    if(($string =~ m/\)/)){
      $string = $string =~ s/\)//gr;
    }

    if(($string =~ m/\[/)){
      $string = $string =~ s/\[//gr;
    }

    if(($string =~ m/\]/)){
      $string = $string =~ s/\]//gr;
    }

    if(($string =~ m/\{/)){
      $string = $string =~ s/\{//gr;
    }

    if(($string =~ m/\}/)){
      $string = $string =~ s/\}//gr;
    }

    if(($string =~ m/\|/)){
      $string = $string =~ s/\|//gr;
    }

    if(($string =~ m/\+/)){
      $string = $string =~ s/\+//gr;
    }

    return $string;
}

sub trim_value {
    my ($toTrim) = @_;

    $toTrim =~ s/^\s+|\s+$//g;

    return $toTrim;
}

sub clean_software_version {
    my ($version) = @_;

    # Remove int: if it found
    if(length($version) >= 2 && substr($version, 1, 1) eq ':') {
        $version = substr($version, 2);
    }

    $version =~ s/[\$#@~!&*()\[\];,:?^\-\+\_`a-zA-Z\\\/].*//g;

    return $version;
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
            my $softVersion = $soft->{VERSION};
            my $response;
            my $minV;
            my $majV;

            $regex = remove_special_char($regex);
            $regex = regex($regex);

            if(defined($os) && ($os eq 'ALL' || $Apache::Ocsinventory::CURRENT_CONTEXT{'USER_AGENT'} =~ m/$os/)){
              if (defined $softName) {
                
                $softName = remove_special_char($softName);

                if(defined $softVersion && trim_value($softVersion) ne '') {
                  $softVersion = clean_software_version($softVersion);
                }
                
                if(defined $version) {
                  $version = clean_software_version($version);
                }
                
                if ($softName =~ $regex) {
                  if( ( defined $sign ) && ( $sign ne '' )){
                      switch ($sign) {
                        case "EQUAL" {
                            my @catVersions = split /\./, $version;
                            my @softVersions = split /\./, $softVersion;

                            # First compare major version
                            $response = compare('equal', $catVersions[0], $publisher, $softVersions[0], $soft->{PUBLISHER});

                            # Next, compare minor version
                            if(defined $response && defined $catVersions[1]) {
                              $softVersions[1] = defined $softVersions[1] ? $softVersions[1] : '0';
                              $response = compare('equal', $catVersions[1], $publisher, $softVersions[1], $soft->{PUBLISHER});
                            }

                            # Then, compare patch version
                            if(defined $response && defined $catVersions[2]) {
                              $softVersions[2] = defined $softVersions[2] ? $softVersions[2] : '0';
                              $response = compare('equal', $catVersions[2], $publisher, $softVersions[2], $soft->{PUBLISHER});
                            }

                            # To finish, compare build version
                            if(defined $response && defined $catVersions[3]) {
                              $softVersions[3] = defined $softVersions[3] ? $softVersions[3] : '0';
                              $response = compare('equal', $catVersions[3], $publisher, $softVersions[3], $soft->{PUBLISHER});
                            }

                            if(defined $response) {
                              $soft_cat = $cat->{ID};
                            }
                        }
                        case "LESS" {
                            my @catVersions = split /\./, $version;
                            my @softVersions = split /\./, $softVersion;

                            # First compare major version
                            $response = compare('less', $catVersions[0], $publisher, $softVersions[0], $soft->{PUBLISHER});

                            # Next, compare minor version only if major are equal
                            if(defined $response && defined $catVersions[1] && $catVersions[0] == $softVersions[0]) {
                              $softVersions[1] = defined $softVersions[1] ? $softVersions[1] : '0';
                              $response = compare('less', $catVersions[1], $publisher, $softVersions[1], $soft->{PUBLISHER});
                            }

                            # Then, compare patch version only if major and minor are equal
                            if(defined $response && defined $catVersions[2] && $catVersions[0] == $softVersions[0] && $catVersions[1] == $softVersions[1]) {
                              $softVersions[2] = defined $softVersions[2] ? $softVersions[2] : '0';
                              $response = compare('less', $catVersions[2], $publisher, $softVersions[2], $soft->{PUBLISHER});
                            }

                            # To finish, compare build version
                            if(defined $response && defined $catVersions[3] && $catVersions[0] == $softVersions[0] && $catVersions[1] == $softVersions[1] && $catVersions[2] == $softVersions[2]) {
                              $softVersions[3] = defined $softVersions[3] ? $softVersions[3] : '0';
                              $response = compare('less', $catVersions[3], $publisher, $softVersions[3], $soft->{PUBLISHER});
                            }

                            if(defined $response) {
                              $soft_cat = $cat->{ID};
                            }
                        }
                        case "MORE" {
                            my @catVersions = split /\./, $version;
                            my @softVersions = split /\./, $softVersion;

                            # First compare major version
                            $response = compare('bigger', $catVersions[0], $publisher, $softVersions[0], $soft->{PUBLISHER});

                            # Next, compare minor version only if major are equal
                            if(defined $response && defined $catVersions[1] && $catVersions[0] == $softVersions[0]) {
                              $softVersions[1] = defined $softVersions[1] ? $softVersions[1] : '0';
                              $response = compare('bigger', $catVersions[1], $publisher, $softVersions[1], $soft->{PUBLISHER});
                            }

                            # Then, compare patch version only if major and minor are equal
                            if(defined $response && defined $catVersions[2] && $catVersions[0] == $softVersions[0] && $catVersions[1] == $softVersions[1]) {
                              $softVersions[2] = defined $softVersions[2] ? $softVersions[2] : '0';
                              $response = compare('bigger', $catVersions[2], $publisher, $softVersions[2], $soft->{PUBLISHER});
                            }

                            # To finish, compare build version
                            if(defined $response && defined $catVersions[3] && $catVersions[0] == $softVersions[0] && $catVersions[1] == $softVersions[1] && $catVersions[2] == $softVersions[2]) {
                              $softVersions[3] = defined $softVersions[3] ? $softVersions[3] : '0';
                              $response = compare('bigger', $catVersions[3], $publisher, $softVersions[3], $soft->{PUBLISHER});
                            }

                            if(defined $response) {
                              $soft_cat = $cat->{ID};
                            }
                        }
                      }
                  }
  		            if ( (defined $publisher) && ( $publisher ne '' ) && ((!defined $sign) || ($sign eq '')) ) {
                      my $softPublisher = $soft->{PUBLISHER};
                      if ( (defined $softPublisher) && ($publisher eq $softPublisher) ) {
                          $soft_cat = $cat->{ID};
                      }
                  } if( ((!defined $sign) && (!defined $publisher)) || (($publisher eq '') && ($sign eq ''))) {
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
