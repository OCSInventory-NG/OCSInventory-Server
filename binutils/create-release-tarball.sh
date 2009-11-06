#!/bin/sh

set -e

RELEASE=$1

if [ -z $RELEASE ]; then
	echo "$0 VERSION"
	exit 1
fi

bzr branch https://code.launchpad.net/ocsinventory-server/trunk OCSNG_UNIX_SERVER-$RELEASE
rm -rf OCSNG_UNIX_SERVER-$RELEASE/.bzr
cd OCSNG_UNIX_SERVER-$RELEASE
bzr branch https://code.launchpad.net/ocsinventory-ocsreports/stable ocsreports
rm -rf ocsreports/.bzr
cd ..
tar cfz OCSNG_UNIX_SERVER-$RELEASE.tar.gz OCSNG_UNIX_SERVER-$RELEASE
