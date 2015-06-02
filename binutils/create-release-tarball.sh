#!/bin/sh

set -e

RELEASE=$1

if [ -z $RELEASE ]; then
	echo "$0 VERSION"
	exit 1
fi

bzr branch lp:ocsinventory-server/trunk OCSNG_UNIX_SERVER-$RELEASE
rm -rf OCSNG_UNIX_SERVER-$RELEASE/.bzr
cd OCSNG_UNIX_SERVER-$RELEASE
bzr branch lp:ocsinventory-ocsreports/stable ocsreports
rm -rf ocsreports/.bzr
# Reset the default settings so install.php will be correctly loaded
rm ocsreports/dbconfig.inc.php
cd ..
tar cfz OCSNG_UNIX_SERVER-$RELEASE.tar.gz OCSNG_UNIX_SERVER-$RELEASE
