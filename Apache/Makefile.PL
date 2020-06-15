#-------------------------------------------------------
#
# $Id: Makefile.PL,v 1.6 2008-02-27 21:26:27 dliroulet Exp $
#
#-------------------------------------------------------

################################################################################
#
# OCS Inventory NG Communication Server Perl Module Setup
#
# Copyleft 2006 Pascal DANEK
# Web: http://www.ocsinventory-ng.org
#
# This code is open source and may be copied and modified as long as the source
# code is always made freely available.
# Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################

use ExtUtils::MakeMaker;

# See lib/ExtUtils/MakeMaker.pm for details of how to influence
# the contents of the Makefile that is written.

WriteMakefile(
    'NAME'         => 'Apache::Ocsinventory',
    'VERSION_FROM' => 'Ocsinventory.pm',
    'PREREQ_PM'    => {
			'DBI'             => 1.40,   # no DBI can never be any fun.
			'Apache::DBI'     => 0.93,   # no Apache::DBI can never be any fun.
			'Compress::Zlib'  => 1.33,   # no Compress:Zlib can never be any fun.
			'DBD::mysql'      => 2.9004, # no DBD::mysql can never be any fun.
			'XML::Simple'     => 2.12,   # no XML::Simple can never be any fun.
			'Net::IP'         => 1.21,   # no Net::IP can never be any fun.
	},
#    'EXE_FILES'         => ['binutils/ocsinventory-injector.pl'],
#    'INSTALLSCRIPT'     => "/usr/bin",
#    'INSTALLSITESCRIPT' => "/usr/bin",
    ($] >= 5.005 ?      ## Add these new keywords supported since 5.005
      (AUTHOR           => 'Pascal DANEK <hunal@users.sourceforge.net>') : ()),
);

# EOF
