#!/bin/sh

set -e

BRANCH=$1

if [ -z $BRANCH ]; then
	echo "$0 VERSION"
	exit 1
fi

git clone https://github.com/OCSInventory-NG/OCSInventory-Server.git -b $BRANCH
rm -rf OCSInventory-Server/.git
cd OCSInventory-Server
git clone https://github.com/OCSInventory-NG/OCSInventory-OCSReports.git -b $BRANCH
mv OCSInventory-OCSReports ocsreports
rm -rf ocsreports/.git
# Reset the default settings so install.php will be correctly loaded
rm ocsreports/dbconfig.inc.php
cd ..
mv OCSInventory-Server OCSNG_UNIX_SERVER-$BRANCH
tar cfz OCSNG_UNIX_SERVER-$BRANCH.tar.gz OCSNG_UNIX_SERVER-$BRANCH
