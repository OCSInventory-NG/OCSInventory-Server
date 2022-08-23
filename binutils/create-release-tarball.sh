#!/bin/sh
set -e

# Branch name (first args)
BRANCH=$1

# Release name (second args)
RELEASE_NAME=$2

# URL to the ocs server git repository
SRV_REPO=https://github.com/OCSInventory-NG/OCSInventory-Server.git
# URL to the ocs reports git repository
WEB_REPO=https://github.com/OCSInventory-NG/OCSInventory-OCSReports.git
# Composer path
COMPOSER_PATH=/usr/bin/composer

# If no branch is supplied exit
if [ -z $BRANCH ]; then
	echo "Please set a source branch (first argument)"
	exit 1
fi

if [ -z $RELEASE_NAME ]; then
	echo "Please set a release name (second argument)"
	exit 1
fi
echo "$COMPOSER_PATH"
# Check if composer bin is available, otherwise exit and display an error
if [ ! -f $COMPOSER_PATH ]; then
	echo "Error retreving composer executable. Please verify if composer is intalled or the COMPOSER_PATH configuration at the beginning of the file (Currently used COMPOSER_PATH = $COMPOSER_PATH)"
	exit 1
fi

# Clone server repository and remove git related files
git clone $SRV_REPO -b $BRANCH
rm -rf OCSInventory-Server/.git
rm -rf OCSInventory-Server/.github
rm -rf OCSInventory-Server/.gitignore

# Clone reports repository and remove git related files
cd OCSInventory-Server && git clone $WEB_REPO -b $BRANCH ocsreports
rm -rf ocsreports/.git
rm -rf ocsreports/.github
rm -rf ocsreports/.gitignore

# Reset the default settings so install.php will be correctly loaded
rm ocsreports/dbconfig.inc.php
cd ocsreports/

# Run composer on ocsreports
$COMPOSER_PATH install

# Go back to the base path
cd ../..

# Change name to comply with OCS' naming convention
mv OCSInventory-Server OCSNG_UNIX_SERVER-$RELEASE_NAME
tar cfz OCSNG_UNIX_SERVER-$RELEASE_NAME.tar.gz OCSNG_UNIX_SERVER-$RELEASE_NAME
rm -rf OCSNG_UNIX_SERVER-$RELEASE_NAME

# Release created !
echo "Release has been successfully created"
