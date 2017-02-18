#!/bin/sh

# Define constant

# Github base url for download releases
ReleaseBaseUrl="https://github.com/OCSInventory-NG/OCSInventory-ocsreports/releases/download/"
# Last release tag
LastReleaseTag="2.3/"
# Archive name
LastReleaseArchive="OCSNG_UNIX_SERVER-2.3"
# Archive extension
ArchiveExtension=".tar.gz"
# File destination
FileDestination="/tmp/ocs"
# Archive link for release
FullArchiveUrl=$ReleaseBaseUrl$LastReleaseTag$LastReleaseArchive$ArchiveExtension

# Get archive
wget $FullArchiveUrl

# Un tar release archive
tar -xzvf $LastReleaseArchive$ArchiveExtension

# Move to the selected directory
mv $LastReleaseArchive $FileDestination
