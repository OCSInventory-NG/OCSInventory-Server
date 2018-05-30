#!/bin/sh

# Define constant
VERSION=$1

# Github base url for download releases
ReleaseBaseUrl="https://github.com/OCSInventory-NG/OCSInventory-ocsreports/releases/download/"
# Last release tag
LastReleaseTag="${VERSION}/"
# Archive name
LastReleaseArchive="OCSNG_UNIX_SERVER_${VERSION}"
# Archive extension
ArchiveExtension=".tar.gz"
# File destination
FileDestination="/tmp/ocs"
# Archive link for release
FullArchiveUrl=$ReleaseBaseUrl$LastReleaseTag$LastReleaseArchive$ArchiveExtension

# Get archive

if wget $FullArchiveUrl; then
	echo $LastReleaseArchive
else
	LastReleaseArchive="OCSNG_UNIX_SERVER-${VERSION}"
	FullArchiveUrl=$ReleaseBaseUrl$LastReleaseTag$LastReleaseArchive$ArchiveExtension
	wget $FullArchiveUrl
	echo $FullArchiveUrl
fi

tar -xzvf $LastReleaseArchive$ArchiveExtension

mv $LastReleaseArchive $FileDestination
