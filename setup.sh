#!/bin/sh
################################################################################
#
# OCS Inventory NG Management Server Setup
#
# Copyleft 2006 Didier LIROULET
# Web: http://ocsinventory.sourceforge.net
#
# This code is open source and may be copied and modified as long as the source
# code is always made freely available.
# Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
#

# Which host run database server
DB_SERVER_HOST="localhost"
# On which port run database server
DB_SERVER_PORT="3306"
# Where is Apache daemon binary (if empty, will try to find it)
APACHE_BIN=""
# Where is Apache configuration file (if empty, will try to find it)
APACHE_CONFIG_FILE=""
# Where is Apache includes configuration directory (if emty, will try to find it)
APACHE_CONFIG_DIRECTORY=""
# Which user is running Apache web server (if empty, will try to find it)
APACHE_USER=""
# Which group is running Apache web server (if empty, will try to find it)
APACHE_GROUP=""
# Where is Apache document root directory (if empty, will try to find it)
APACHE_ROOT_DOCUMENT=""
# Which version of mod_perl is apache using,  1 for <= 1.999_21 and 2 for >= 1.999_22 (if empty, user will be asked for)
APACHE_MOD_PERL_VERSION=""
# Where are located OCS Communication server log files
OCS_COM_SRV_LOG="/var/log/ocsinventory-NG"
# Where is located perl interpreter
PERL_BIN=`which perl`
# Where is located make utility
MAKE=`which make`
# Where is located logrotate configuration directory
LOGROTATE_CONF_DIR="/etc/logrotate.d"
# Where is located ed simple editor (not used, because now using perl to replace string in file)
# ED_BIN=`which ed`
# Where to store setup logs
SETUP_LOG=`pwd`/ocs_server_setup.log
 

###################### DO NOT MODIFY BELOW #######################

# Check for Apache web server binaries
echo
echo "+----------------------------------------------------------+"
echo "|                                                          |"
echo "| Welcome to OCS Inventory NG Management server setup !    |"
echo "|                                                          |"
echo "+----------------------------------------------------------+"
echo
echo "CAUTION: If upgrading Communication server from OCS Inventory NG 1.0 RC2 and"
echo "previous, please remove any Apache configuration for Communication Server!"
echo
echo -n "Do you wish to continue ([y]/n)?"
read ligne
if (test -z $ligne) || (test $ligne = "y")
then
    echo "Assuming Communication server 1.0 RC2 or previous is not installed"
    echo "on this computer."
    echo
else
    echo "Installation aborted !"
    echo
    exit 1
fi

echo > $SETUP_LOG
OCS_LOCAL_DATE=`date +%Y-%m-%d-%H-%M-%S`
echo "Starting OCS Inventory NG Management server setup on $OCS_LOCAL_DATE" >> $SETUP_LOG
echo -n "from folder " >> $SETUP_LOG
pwd >> $SETUP_LOG
echo -n "Starting OCS Inventory NG Management server setup from folder "
pwd
echo "Storing log in file $SETUP_LOG" >> $SETUP_LOG
echo "Storing log in file $SETUP_LOG"
echo >> $SETUP_LOG

echo
echo "+----------------------------------------------------------+"
echo "| Checking for database server properties...               |"
echo "+----------------------------------------------------------+"
echo
# Check mysql client distribution version
echo "Checking for database server properties" >> $SETUP_LOG
DB_CLIENT_MAJOR_VERSION=`eval mysql -V | cut -d' ' -f6 | cut -d'.' -f1` >> $SETUP_LOG 2>&1
DB_CLIENT_MINOR_VERSION=`eval mysql -V | cut -d' ' -f6 | cut -d'.' -f2` >> $SETUP_LOG 2>&1
echo "Your MySQL client seems to be part of MySQL version $DB_CLIENT_MAJOR_VERSION.$DB_CLIENT_MINOR_VERSION."
echo "MySQL client distribution version $DB_CLIENT_MAJOR_VERSION.$DB_CLIENT_MINOR_VERSION." >> $SETUP_LOG
# Ensure mysql distribution is 4.1 or higher
if test $DB_CLIENT_MAJOR_VERSION -gt 4
then
    res=1
else
    if test $DB_CLIENT_MAJOR_VERSION -eq 4
    then
        if test $DB_CLIENT_MINOR_VERSION -eq 1
        then
            res=1
        else
            res=0
        fi
    else
        res=0
    fi
fi
if test $res -eq 0
then
    # Not 4.1 or higher, ask user to contnue ?
    echo "Your computer does not seem to be compliant with MySQL 4.1 or higher."
    echo -n "Do you wish to continue (y/[n])?"
    read ligne
    if test "$ligne" = "y"
    then
        echo "Ensure your database server is running MySQL 4.1 or higher !"
        echo "Ensure also this computer is able to connect to your MySQL server !"
    else
        echo "Installation aborted !"
        exit 1
    fi
else
    echo "Your computer seems to be running MySQL 4.1 or higher, good ;-)"
    echo "Computer seems to be running MySQL 4.1 or higher" >> $SETUP_LOG
fi
echo

# Ask user for database server host
res=0
while test $res -eq 0
do
    echo -n "Which host is running database server [$DB_SERVER_HOST] ?"
    read ligne
    if test -z $ligne
    then
        res=1
    else
        DB_SERVER_HOST="$ligne"
        res=1
    fi
done
echo "OK, database server is running on host $DB_SERVER_HOST ;-)"
echo "Database server is running on host $DB_SERVER_HOST" >> $SETUP_LOG
echo

# Ask user for database server port
res=0
while test $res -eq 0
do
    echo -n "On which port is running database server [$DB_SERVER_PORT] ?"
    read ligne
    if test -z $ligne
    then
        res=1
    else
        DB_SERVER_PORT="$ligne"
        res=1
    fi
done
echo "OK, database server is running on port $DB_SERVER_PORT ;-)"
echo "Database server is running on port $DB_SERVER_PORT" >> $SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for Apache web server daemon...                 |"
echo "+----------------------------------------------------------+"
echo
echo "Checking for Apache web server daemon" >> $SETUP_LOG
# Try to find Apache daemon
if test -z $APACHE_BIN
then
    APACHE_BIN_FOUND=`which httpd`
    if test -z $APACHE_BIN_FOUND
    then
        APACHE_BIN_FOUND=`which apache`
        if test -z $APACHE_BIN_FOUND
        then
            APACHE_BIN_FOUND=`which apache2`
        fi
    fi
fi
echo "Found Apache daemon $APACHE_BIN_FOUND" >> $SETUP_LOG
# Ask user's confirmation 
res=0
while test $res -eq 0
do
    echo -n "Where is Apache daemon binary [$APACHE_BIN_FOUND] ?"
    read ligne
    if test -z $ligne
    then
        APACHE_BIN=$APACHE_BIN_FOUND
    else
        APACHE_BIN="$ligne"
    fi
    # Ensure file exists and is executable
    if test -x $APACHE_BIN
    then
        res=1
    else
        echo "*** ERROR: $APACHE_BIN is not executable !"
        res=0
    fi
    # Ensure file is not a directory
    if test -d $APACHE_BIN
    then 
        echo "*** ERROR: $APACHE_BIN is a directory !"
        res=0
    fi
done
echo "OK, using Apache daemon $APACHE_BIN ;-)"
echo "Using Apache daemon $APACHE_BIN" >> $SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for Apache main configuration file...           |"
echo "+----------------------------------------------------------+"
echo
# Try to find Apache main configuration file
echo "Checking for Apache main configuration file" >> $SETUP_LOG
if test -z $APACHE_CONFIG_FILE
then
    APACHE_ROOT=`eval $APACHE_BIN -V | grep "HTTPD_ROOT" | cut -d'=' -f2 | tr -d '"'`
    echo "Found Apache HTTPD_ROOT $APACHE_ROOT" >> $SETUP_LOG
    APACHE_CONFIG=`eval $APACHE_BIN -V | grep "SERVER_CONFIG_FILE" | cut -d'=' -f2 | tr -d '"'`
    echo "Found Apache SERVER_CONFIG_FILE $APACHE_CONFIG" >> $SETUP_LOG
    APACHE_CONFIG_FILE_FOUND="$APACHE_ROOT/$APACHE_CONFIG"
fi
echo "Found Apache main configuration file $APACHE_CONFIG_FILE_FOUND" >> $SETUP_LOG
# Ask user's confirmation 
res=0
while test $res -eq 0
do
    echo -n "Where is Apache main configuration file [$APACHE_CONFIG_FILE_FOUND] ?"
    read ligne
    if test -z $ligne
    then
        APACHE_CONFIG_FILE=$APACHE_CONFIG_FILE_FOUND
    else
        APACHE_CONFIG_FILE="$ligne"
    fi
    # Ensure file is not a directory
    if test -d $APACHE_CONFIG_FILE
    then 
        echo "*** ERROR: $APACHE_CONFIG_FILE is a directory !"
        res=0
    fi
    # Ensure file exists and is readable
    if test -r $APACHE_CONFIG_FILE
    then
        res=1
    else
        echo "*** ERROR: $APACHE_CONFIG_FILE is not readable !"
        res=0
    fi
done
echo "OK, using Apache main configuration file $APACHE_CONFIG_FILE ;-)"
echo "Using Apache main configuration file $APACHE_CONFIG_FILE" >> $SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for Apache user account...                      |"
echo "+----------------------------------------------------------+"
echo
# Try to find Apache main configuration file
echo "Checking for Apache user account" >> $SETUP_LOG
if test -z $APACHE_USER
then
    APACHE_USER_FOUND=`cat $APACHE_CONFIG_FILE | grep "User " | tail -1 | cut -d' ' -f2`
fi
echo "Found Apache user account $APACHE_USER_FOUND" >> $SETUP_LOG
# Ask user's confirmation 
res=0
while test $res -eq 0
do
    echo -n "Which user account is running Apache web server [$APACHE_USER_FOUND] ?"
    read ligne
    if test -z $ligne
    then
        APACHE_USER=$APACHE_USER_FOUND
    else
        APACHE_USER="$ligne"
    fi
    # Ensure group exist in /etc/passwd
    if test `cat /etc/passwd | grep $APACHE_USER | wc -l` -eq 0
    then
        echo "*** ERROR: account $APACHE_USER not found in system table /etc/passwd !"
    else
        res=1
    fi
done
echo "OK, Apache is running under user account $APACHE_USER ;-)"
echo "Using Apache user account $APACHE_USER" >> $SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for Apache group...                             |"
echo "+----------------------------------------------------------+"
echo
# Try to find Apache main configuration file
echo "Checking for Apache group" >> $SETUP_LOG
if test -z $APACHE_GROUP
then
    APACHE_GROUP_FOUND=`cat $APACHE_CONFIG_FILE | grep "Group" | tail -1 | cut -d' ' -f2`
    if test -z $APACHE_GROUP_FOUND
    then
        # No group found, assume group name is the same as account
        echo "No Apache user group found, assuming group name is the same as user account" >> $SETUP_LOG
        APACHE_GROUP_FOUND=$APACHE_USER
    fi
fi
echo "Found Apache user group $APACHE_GROUP_FOUND" >> $SETUP_LOG
# Ask user's confirmation 
res=0
while test $res -eq 0
do
    echo -n "Which user group is running Apache web server [$APACHE_GROUP_FOUND] ?"
    read ligne
    if test -z $ligne
    then
        APACHE_GROUP=$APACHE_GROUP_FOUND
    else
        APACHE_GROUP="$ligne"
    fi
    # Ensure group exist in /etc/group
    if test `cat /etc/group | grep $APACHE_GROUP | wc -l` -eq 0
    then
        echo "*** ERROR: group $APACHE_GROUP not found in system table /etc/group !"
    else
        res=1
    fi
done
echo "OK, Apache is running under users group $APACHE_GROUP ;-)"
echo "Using Apache user group $APACHE_GROUP" >> $SETUP_LOG
echo


echo
echo "+----------------------------------------------------------+"
echo "| Checking for PERL Interpreter...                         |"
echo "+----------------------------------------------------------+"
echo
echo "Checking for PERL Interpreter" >> $SETUP_LOG
if test -z $PERL_BIN
then
	echo "PERL Interpreter not found !"
	echo "PERL Interpreter not found" >> $SETUP_LOG
	echo "OCS Inventory NG is not able to work without PERL Interpreter."
	echo "Setup manually PERL first."
	echo "Installation aborted !"
	echo "installation aborted" >> $SETUP_LOG
	exit 1
else
	echo "Found PERL Intrepreter at <$PERL_BIN> ;-)"
	echo "Found PERL Intrepreter at <$PERL_BIN>" >> $SETUP_LOG
fi
# Ask user's confirmation 
res=0
while test $res -eq 0
do
    echo -n "Where is PERL Intrepreter binary [$PERL_BIN] ?"
    read ligne
    if test ! -z $ligne
    then
        PERL_BIN="$ligne"
    fi
    # Ensure file exists and is executable
    if test -x $PERL_BIN
    then
        res=1
    else
        echo "*** ERROR: $PERL_BIN is not executable !"
        res=0
    fi
    # Ensure file is not a directory
    if test -d $PERL_BIN
    then 
        echo "*** ERROR: $PERL_BIN is a directory !"
        res=0
    fi
done
echo "OK, using PERL Intrepreter $PERL_BIN ;-)"
echo "Using PERL Intrepreter $PERL_BIN" >> $SETUP_LOG
echo


echo
echo -n "Do you wish to setup Communication server on this computer ([y]/n)?"
read ligne
if (test -z $ligne) || (test $ligne = "y")
then
    # Setting up Communication server
    echo >> $SETUP_LOG
    echo "Installing Communication server" >> $SETUP_LOG
    echo
    
	echo
	echo "+----------------------------------------------------------+"
	echo "| Checking for Make utility...                             |"
	echo "+----------------------------------------------------------+"
	echo
	echo "Checking for Make utility" >> $SETUP_LOG
	if test -z $MAKE
	then
		echo "Make utility not found !"
		echo "Make utility not found" >> $SETUP_LOG
		echo "Setup is not able to build Perl module."
		echo "Unable to build Perl module !" >> $SETUP_LOG
		exit 1
	else
		echo "OK, Make utility found at <$MAKE> ;-)"
		echo "Make utility found at <$MAKE>" >> $SETUP_LOG
	fi
	echo


    echo
    echo "+----------------------------------------------------------+"
    echo "| Checking for Apache Include configuration directory...   |"
    echo "+----------------------------------------------------------+"
    echo
    # Try to find Apache includes configuration directory
    echo "Checking for Apache Include configuration directory" >> $SETUP_LOG
    if test -z $APACHE_CONFIG_DIRECTORY
    then
        # Works on RH/Fedora/CentOS
        CONFIG_DIRECTORY_FOUND=`eval cat $APACHE_CONFIG_FILE | grep Include | grep conf.d |head -1 | cut -d' ' -f2 | cut -d'*' -f1`
        if ! test -z $CONFIG_DIRECTORY_FOUND
        then
            APACHE_CONFIG_DIRECTORY_FOUND="$APACHE_ROOT/$CONFIG_DIRECTORY_FOUND"
            echo "Redhat compliant Apache Include configuration directory $CONFIG_DIRECTORY_FOUND" >> $SETUP_LOG
        else
            APACHE_CONFIG_DIRECTORY_FOUND=""
            echo "Not found Redhat compliant Apache Include configuration directory" >> $SETUP_LOG
        fi
        if ! test -d $APACHE_CONFIG_DIRECTORY_FOUND
        then
            # Works on Debian/Ubuntu
            CONFIG_DIRECTORY_FOUND=`eval cat $APACHE_CONFIG_FILE | grep Include | grep conf.d |head -1 | cut -d' ' -f2 | cut -d'[' -f1`
            if ! test -z $CONFIG_DIRECTORY_FOUND
            then
                APACHE_CONFIG_DIRECTORY_FOUND="$APACHE_ROOT/$CONFIG_DIRECTORY_FOUND"
                echo "Debian compliant Apache Include configuration directory $CONFIG_DIRECTORY_FOUND" >> $SETUP_LOG
            else
                APACHE_CONFIG_DIRECTORY_FOUND=""
                echo "Not found Debian compliant Apache Include configuration directory" >> $SETUP_LOG
            fi
        fi
    fi
    echo "Found Apache Include configuration directory $APACHE_CONFIG_DIRECTORY_FOUND" >> $SETUP_LOG
    # Ask user's confirmation 
    res=0
    while test $res -eq 0
    do
        echo "Setup has found Apache Include configuration directory in"
        echo "$APACHE_CONFIG_DIRECTORY_FOUND."
        echo "If you are not using Include directive, please enter 'no'."
        echo -n "Where is Apache Include configuration directory [$APACHE_CONFIG_DIRECTORY_FOUND] ?"
        read ligne
        if test "$ligne" = "no"
        then
            APACHE_CONFIG_DIRECTORY=""
            res=1
        else
            if test -z $ligne
            then
                APACHE_CONFIG_DIRECTORY=$APACHE_CONFIG_DIRECTORY_FOUND
            else
                APACHE_CONFIG_DIRECTORY="$ligne"
            fi
            # Ensure file is not a directory
            if test -d $APACHE_CONFIG_DIRECTORY
            then
                res=1
            else
                echo "*** ERROR: $APACHE_CONFIG_DIRECTORY is not a directory !"
                res=0
            fi
            # Ensure file exists and is writable
            if test -w $APACHE_CONFIG_DIRECTORY
            then
                res=1
            else
                echo "*** ERROR: $APACHE_CONFIG_DIRECTORY is not writable !"
                res=0
            fi
        fi
    done
    if test -z $APACHE_CONFIG_DIRECTORY
    then
        echo "Not using Apache Include configuration directory."
        echo "Configuration will be written to Apache main configuration file"
        echo "$APACHE_CONFIG_FILE."
        echo "Not using Apache Include configuration directory, using file Apache main configuration file $APACHE_CONFIG_FILE." >> $SETUP_LOG
    else
        echo "OK, Apache Include configuration directory $APACHE_CONFIG_DIRECTORY found ;-)"
        echo "Using Apache Include configuration directory $APACHE_CONFIG_DIRECTORY" >> $SETUP_LOG
    fi
    echo

    echo "+----------------------------------------------------------+"
    echo "| Checking for Apache mod_perl version...                  |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Checking for Apache mod_perl version 1.99_22 or higher"
    echo "Checking for Apache mod_perl version 1.99_22 or higher" >> $SETUP_LOG
	$PERL_BIN -mmod_perl2 -e 'print "mod_perl 1.99_22 or higher is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		# mod_perl 2 not found !
		echo "Checking for Apache mod_perl version 1.99_21 or previous"
		echo "Checking for Apache mod_perl version 1.99_21 or previous" >> $SETUP_LOG
		$PERL_BIN -mmod_perl -e 'print "mod_perl 1.99_21 or previous is available\n"' >> $SETUP_LOG 2>&1
		if [ $? != 0 ]
		then
			# mod_perl 1 not found => Ask user 
			res=0
			while test $res -eq 0
			do
				echo "Setup is unable to determine your Apache mod_perl version."
				echo "Apache must have module mod_perl enabled. As configuration differs from"
				echo "mod_perl 1.99_21 or previous AND mod_perl 1.99_22 or higher, Setup must"
				echo "know which release Apache is using."
				echo "You can find which release you are using by running the following command"
				echo "  - On RPM enabled OS, rpm -q mod_perl"
				echo "  - On DPKG enabled OS, dpkg -l libapache*-mod-perl*"
				echo "Enter 1 for mod_perl 1.99_21 or previous."
				echo "Enter 2 for mod_perl 1.99_22 and higher."
				echo -n "Which version of Apache mod_perl the computer is running ([1]/2) ?"
				read ligne
				if test -z $ligne
				then
					APACHE_MOD_PERL_VERSION=1
				else
					APACHE_MOD_PERL_VERSION=$ligne
				fi
				res=1
			done
		else
		    echo "Found that mod_perl version 1.99_21 or previous is available."
			APACHE_MOD_PERL_VERSION=1
		fi
	else
	    echo "Found that mod_perl version 1.99_22 or higher is available."
		APACHE_MOD_PERL_VERSION=2
	fi
    if test $APACHE_MOD_PERL_VERSION -eq 1
    then
        echo "OK, Apache is using mod_perl version 1.99_21 or previous ;-)"
        echo "Using mod_perl version 1.99_21 or previous" >> $SETUP_LOG
	else
        echo "OK, Apache is using mod_perl version 1.99_22 or higher ;-)"
        echo "Using mod_perl version 1.99_22 or higher" >> $SETUP_LOG
	fi
	echo

    echo "+----------------------------------------------------------+"
    echo "| Checking for Communication server log directory...       |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Checking for Communication server log directory" >> $SETUP_LOG
    # Ask user 
    res=0
    while test $res -eq 0
    do
        echo "Communication server can create detailled logs. This logs can be enabled"
        echo "by setting interger value of LOGLEVEL to 1 in Administration console"
        echo "menu Configuration."
        echo -n "Where to put Communication server log directory [$OCS_COM_SRV_LOG] ?"
        read ligne
        if ! test -z $ligne
        then
            OCS_COM_SRV_LOG=$ligne
        fi
        res=1
    done
    echo "OK, Communication server will put logs into directory $OCS_COM_SRV_LOG ;-)"
    echo "Using $OCS_COM_SRV_LOG as Communication server log directory" >> $SETUP_LOG
	echo
	
    # jump to communication server directory
    echo "Entering Apache sub directory" >> $SETUP_LOG
    cd "Apache"
    
    # Check for required Perl Modules (if missing, please install before)
    #    - DBI 1.40 or higher
    #    - Apache::DBI 0.93 or higher
    #    - DBD::mysql 2.9004 or higher
    #    - Compress::Zlib 1.33 or higher
    #    - XML::Simple 2.12 or higher
    #    - Net::IP 1.21 or higher
    #    - SOAP::Lite 0.65, not required.
    #
    echo
    echo "+----------------------------------------------------------+"
    echo "| Checking for required Perl Modules...                    |"
    echo "+----------------------------------------------------------+"
    echo
	echo "Checking for DBI PERL module..."
	echo "Checking for DBI PERL module" >> $SETUP_LOG
	$PERL_BIN -mDBI -e 'print "PERL module DBI is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module DBI is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module DBI is available."
    fi
	echo "Checking for Apache::DBI PERL module..."
	echo "Checking for Apache::DBI PERL module" >> $SETUP_LOG
	$PERL_BIN -mApache::DBI -e 'print "PERL module Apache::DBI is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module Apache::DBI is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module Apache::DBI is available."
    fi
	echo "Checking for DBD::mysql PERL module..."
	echo "Checking for DBD::mysql PERL module" >> $SETUP_LOG
	$PERL_BIN -mDBD::mysql -e 'print "PERL module DBD::mysql is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module DBD::mysql is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module DBD::mysql is available."
    fi
	echo "Checking for Compress::Zlib PERL module..."
	echo "Checking for Compress::Zlib PERL module" >> $SETUP_LOG
	$PERL_BIN -mCompress::Zlib -e 'print "PERL module Compress::Zlib is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module Compress::Zlib is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module Compress::Zlib is available."
    fi
	echo "Checking for XML::Simple PERL module..."
	echo "Checking for XML::Simple PERL module" >> $SETUP_LOG
	$PERL_BIN -mXML::Simple -e 'print "PERL module XML::Simple is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module XML::Simple is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module XML::Simple is available."
    fi
	echo "Checking for Net::IP PERL module..."
	echo "Checking for Net::IP PERL module" >> $SETUP_LOG
	$PERL_BIN -mNet::IP -e 'print "PERL module Net::IP is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module Net::IP is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module Net::IP is available."
    fi
    echo "Checking for SOAP::Lite PERL module..."
    echo "Checking for SOAP::Lite PERL module" >> $SETUP_LOG
  	$PERL_BIN -mSOAP::Lite -e 'print "PERL module SOAP::Lite is available\n"' >> $SETUP_LOG 2>&1
   	if [ $? != 0 ]
   	then
   		echo "*** ERROR: PERL module SOAP::Lite is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
   	else
   		echo "Found that PERL module SOAP::Lite is available."
    fi
	echo


    echo
    echo "+----------------------------------------------------------+"
    echo "| OK, looks good ;-)                                       |"
    echo "|                                                          |"
    echo "| Configuring Communication server Perl modules...         |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Configuring Communication server (perl Makefile.PL)" >> $SETUP_LOG
    $PERL_BIN Makefile.PL
    if [ $? != 0 ]
    then
        echo -n "Warning: Prerequisites too old ! Do you wish to continue (y/[n])?"
        read ligne
        if test $ligne = "y"
        then
            echo "Maybe Communication server will encounter problems. Continuing anyway."
            echo "Warning: Prerequisites too old ! Continuing anyway" >> $SETUP_LOG
        else
            echo "Installation aborted !"
            exit 1
        fi
    fi
    echo
    echo "+----------------------------------------------------------+"
    echo "| OK, looks good ;-)                                       |"
    echo "|                                                          |"
    echo "| Preparing Communication server Perl modules...           |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Preparing Communication server Perl modules (make)" >> $SETUP_LOG
    $MAKE >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Prepare failed, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    
    echo
    echo "+----------------------------------------------------------+"
    echo "| OK, prepare finshed ;-)                                  |"
    echo "|                                                          |"
    echo "| Installing Communication server Perl modules...          |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Installing Communication server Perl modules (make install)" >> $SETUP_LOG
    $MAKE install >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then 
        echo "*** ERROR: Install of Perl modules failed, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi

    echo
    echo "+----------------------------------------------------------+"
    echo "| OK, Communication server Perl modules install finished;-)|"
    echo "|                                                          |"
    echo "| Creating Communication server log directory...           |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Creating Communication server log directory $OCS_COM_SRV_LOG."
    echo "Creating Communication server log directory $OCS_COM_SRV_LOG" >> $SETUP_LOG
    mkdir -p $OCS_COM_SRV_LOG >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to create log directory, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    echo
    echo "Fixing Communication server log directory files permissions."
    echo "Fixing Communication server log directory permissions" >> $SETUP_LOG
    chown -R $APACHE_USER:$APACHE_GROUP $OCS_COM_SRV_LOG >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set log directory permissions, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    chmod -R gu+rwx $OCS_COM_SRV_LOG >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set log directory permissions, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    chmod -R o-w $OCS_COM_SRV_LOG >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set log directory permissions, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    echo "Configuring logrotate for Communication server."
    echo "Configuring logrotate (ed logrotate.ocsinventory-NG)" >> $SETUP_LOG
    cp logrotate.ocsinventory-NG logrotate.ocsinventory-NG.local
#
# Now using perl to replace string in file instead of ed, not available by default in Mandriva Linux
#
#    $ED_BIN logrotate.ocsinventory-NG.local << EOF >> $SETUP_LOG 2>&1
#        1,$ g/^ *PATH_TO_LOG_DIRECTORY*/s#PATH_TO_LOG_DIRECTORY#$OCS_COM_SRV_LOG#
#        w
#        q
#EOF
    $PERL_BIN -pi -e "s#PATH_TO_LOG_DIRECTORY#$OCS_COM_SRV_LOG#g" logrotate.ocsinventory-NG.local
    echo "******** Begin updated logrotate.ocsinventory-NG ***********" >> $SETUP_LOG
    cat logrotate.ocsinventory-NG.local >> $SETUP_LOG
    echo "******** End updated logrotate.ocsinventory-NG ***********" >> $SETUP_LOG
    echo "Writing communication server logrotate to file $LOGROTATE_CONF_DIR/ocsinventory-NG"
    echo "Writing communication server logrotate to file $LOGROTATE_CONF_DIR/ocsinventory-NG" >> $SETUP_LOG
    cp -f logrotate.ocsinventory-NG.local $LOGROTATE_CONF_DIR/ocsinventory-NG >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to configure log rotation, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    echo
    
    echo
    echo "+----------------------------------------------------------+"
    echo "| OK, Communication server log directory created ;-)       |"
    echo "|                                                          |"
    echo "| Now configuring Apache web server...                     |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Configuring Apache web server (ed ocsinventory.conf)" >> $SETUP_LOG
    cp ocsinventory.conf ocsinventory.conf.local
#
# Now using perl to replace string in file instead of ed, not available by default in Mandriva Linux
#
#    $ED_BIN ocsinventory.conf.local << EOF >> $SETUP_LOG 2>&1
#        1,$ g/^ *PerlSetEnv OCS_DB_HOST*/s#DATABASE_SERVER#$DB_SERVER_HOST#
#        1,$ g/^ *PerlSetEnv OCS_DB_PORT*/s#DATABASE_PORT#$DB_SERVER_PORT#
#        1,$ g/^ *PerlSetEnv OCS_MODPERL_VERSION*/s#VERSION_MP#$APACHE_MOD_PERL_VERSION#
#        1,$ g/^ *PerlSetEnv OCS_LOGPATH*/s#PATH_TO_LOG_DIRECTORY#$OCS_COM_SRV_LOG#
#        w
#        q
#EOF
    $PERL_BIN -pi -e "s#DATABASE_SERVER#$DB_SERVER_HOST#g" ocsinventory.conf.local
    $PERL_BIN -pi -e "s#DATABASE_PORT#$DB_SERVER_PORT#g" ocsinventory.conf.local
    $PERL_BIN -pi -e "s#VERSION_MP#$APACHE_MOD_PERL_VERSION#g" ocsinventory.conf.local
    $PERL_BIN -pi -e "s#PATH_TO_LOG_DIRECTORY#$OCS_COM_SRV_LOG#g" ocsinventory.conf.local
    echo "******** Begin updated ocsinventory.conf ***********" >> $SETUP_LOG
    cat ocsinventory.conf.local >> $SETUP_LOG
    echo "******** End updated ocsinventory.conf ***********" >> $SETUP_LOG
    if test -z $APACHE_CONFIG_DIRECTORY
    then
        echo "Setup is not able to replace existing configuration in file"
        echo "$APACHE_CONFIG_FILE."
        echo "But for a fresh install, setup is able to add this configuration."
        echo "Do you wish setup add Communication server configuration to file"
        echo -n "$APACHE_CONFIG_FILE (y/[n]) ?"
        read ligne
        if (test -z $ligne) || (test $ligne = "n")
        then
            echo "Communication server configuration manually added to file $APACHE_CONFIG_FILE" >> $SETUP_LOG
            echo "Setup has prepared configuration in file"
            echo "ocsinventory-NG/ocsinventory.conf.local."
            echo "You must review file content to ensure all is good."
            echo "Then paste file content (at the end generally) into"
            echo "$APACHE_CONFIG_FILE and restart Apache daemon."
        else
            echo "Adding Communication server configuration to end of file $APACHE_CONFIG_FILE..."
            echo "Adding Communication server configuration to end of file $APACHE_CONFIG_FILE" >> $SETUP_LOG
            echo >> $APACHE_CONFIG_FILE
            cat ocsinventory.conf.local >> $APACHE_CONFIG_FILE
            echo
            echo "+----------------------------------------------------------+"
            echo "| OK, Communication server setup sucessfully finished ;-)  |"
            echo "|                                                          |"
            echo "| Please, review $APACHE_CONFIG_FILE"
            echo "| to ensure all is good. Then restart Apache daemon.       |"
            echo "+----------------------------------------------------------+"
        fi
    else
        echo "Writing communication server configuration to file $APACHE_CONFIG_DIRECTORY/ocsinventory.conf"
        echo "Writing communication server configuration to file $APACHE_CONFIG_DIRECTORY/ocsinventory.conf" >> $SETUP_LOG
        cp -f ocsinventory.conf.local $APACHE_CONFIG_DIRECTORY/ocsinventory.conf >> $SETUP_LOG 2>&1
        if [ $? != 0 ]
        then
            echo "*** ERROR: Unable to write $APACHE_CONFIG_DIRECTORY/ocsinventory.conf, please look at error in $SETUP_LOG and fix !"
            echo
            echo "Installation aborted !"
            exit 1
        fi
        echo
        echo "+----------------------------------------------------------+"
        echo "| OK, Communication server setup sucessfully finished ;-)  |"
        echo "|                                                          |"
        echo "| Please, review $APACHE_CONFIG_DIRECTORY/ocsinventory.conf"
        echo "| to ensure all is good. Then restart Apache daemon.       |"
        echo "+----------------------------------------------------------+"
    fi
    echo
    echo "Leaving ocsinventory-NG directory" >> $SETUP_LOG
    cd ".."
    echo "Communication server installation successfull" >> $SETUP_LOG
fi

echo
echo "Do you wish to setup Administration server (web administration console)"
echo -n "on this computer ([y]/n)?"
read ligne
if (test -z $ligne) || (test $ligne = "y")
then
    # Install Administration server
    echo >> $SETUP_LOG
    echo "Installing Administration server" >> $SETUP_LOG
    
    echo
    echo "+----------------------------------------------------------+"
    echo "| Checking for Apache root document directory...           |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Checking for Apache root document directory" >> $SETUP_LOG
    # Try to find Apache root document directory
    if test -z $APACHE_ROOT_DOCUMENT
    then
        APACHE_ROOT_DOCUMENT_FOUND=`cat $APACHE_CONFIG_FILE | grep "DocumentRoot" | tail -1 | cut -d' ' -f2 | tr -d '"'`
    fi
    echo "Found Apache document root $APACHE_ROOT_DOCUMENT_FOUND" >> $SETUP_LOG
    # Ask user's confirmation 
    res=0
    while test $res -eq 0
    do
        echo -n "Where is Apache root document directory [$APACHE_ROOT_DOCUMENT_FOUND] ?"
        read ligne
        if test -z $ligne
        then
            APACHE_ROOT_DOCUMENT=$APACHE_ROOT_DOCUMENT_FOUND
        else
            APACHE_ROOT_DOCUMENT="$ligne"
        fi
        # Ensure group exist in /etc/group
        if test -d $APACHE_ROOT_DOCUMENT
        then
            res=1
        else
            echo "*** ERROR: $APACHE_ROOT_DOCUMENT is not a directory !"
        fi
    done
    echo "OK, Apache root document directory is $APACHE_ROOT_DOCUMENT ;-)"
    echo "Using Apache root document directory $APACHE_ROOT_DOCUMENT" >> $SETUP_LOG
    echo
    
    # Check for required Perl Modules (if missing, please install before)
    #    - DBI 1.40 or higher
    #    - DBD::mysql 2.9004 or higher
    #    - XML::Simple 2.12 or higher
    #    - Net::IP 1.21 or higher
    #
    echo
    echo "+----------------------------------------------------------+"
    echo "| Checking for required Perl Modules...                    |"
    echo "+----------------------------------------------------------+"
    echo
	echo "Checking for DBI PERL module..."
	echo "Checking for DBI PERL module" >> $SETUP_LOG
	$PERL_BIN -mDBI -e 'print "PERL module DBI is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module DBI is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module DBI is available."
    fi
	echo "Checking for DBD::mysql PERL module..."
	echo "Checking for DBD::mysql PERL module" >> $SETUP_LOG
	$PERL_BIN -mDBD::mysql -e 'print "PERL module DBD::mysql is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module DBD::mysql is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module DBD::mysql is available."
    fi
	echo "Checking for XML::Simple PERL module..."
	echo "Checking for XML::Simple PERL module" >> $SETUP_LOG
	$PERL_BIN -mXML::Simple -e 'print "PERL module XML::Simple is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module XML::Simple is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module XML::Simple is available."
    fi
	echo "Checking for Net::IP PERL module..."
	echo "Checking for Net::IP PERL module" >> $SETUP_LOG
	$PERL_BIN -mNet::IP -e 'print "PERL module Net::IP is available\n"' >> $SETUP_LOG 2>&1
	if [ $? != 0 ]
	then
		echo "*** ERROR: PERL module Net::IP is not installed !"
        echo
        echo "Installation aborted !"
        exit 1
	else
		echo "Found that PERL module Net::IP is available."
    fi


    echo
    echo "+----------------------------------------------------------+"
    echo "| Installing files for Administration server...            |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Creating directory $APACHE_ROOT_DOCUMENT/download."
    echo "Creating directory $APACHE_ROOT_DOCUMENT/download" >> $SETUP_LOG
    mkdir -p $APACHE_ROOT_DOCUMENT/download >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to create $APACHE_ROOT_DOCUMENT/download, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    echo
    echo "Creating directory $APACHE_ROOT_DOCUMENT/ocsreports."
    echo "Creating directory $APACHE_ROOT_DOCUMENT/ocsreports" >> $SETUP_LOG
    mkdir -p $APACHE_ROOT_DOCUMENT/ocsreports >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to create $APACHE_ROOT_DOCUMENT/ocsreports, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    mkdir -p $APACHE_ROOT_DOCUMENT/ocsreports/ipd >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to create $APACHE_ROOT_DOCUMENT/ocsreports/ipd, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    
    echo
    echo "Copying files to $APACHE_ROOT_DOCUMENT/ocsreports."
    echo "Copying files to $APACHE_ROOT_DOCUMENT/ocsreports" >> $SETUP_LOG
    cp -Rf ocsreports/* $APACHE_ROOT_DOCUMENT/ocsreports/ >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to copy files in $APACHE_ROOT_DOCUMENT/ocsreports, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    
    echo
    echo "Fixing directories and files permissions."
    echo "Fixing directories and files permissions" >> $SETUP_LOG
    chown -R root:$APACHE_GROUP $APACHE_ROOT_DOCUMENT/ocsreports >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/ocsreports, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    chown -R root:$APACHE_GROUP $APACHE_ROOT_DOCUMENT/download >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/download, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    chmod -R go-w $APACHE_ROOT_DOCUMENT/ocsreports >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/ocsreports, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    chmod -R go-w $APACHE_ROOT_DOCUMENT/download >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/download, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    chmod g+w $APACHE_ROOT_DOCUMENT/ocsreports >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/ocsreports, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    chmod g+w $APACHE_ROOT_DOCUMENT/download >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/download, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    chmod -R g+w $APACHE_ROOT_DOCUMENT/ocsreports/ipd >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/ocsreports, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    if test -r $APACHE_ROOT_DOCUMENT/ocsreports/dbconfig.inc.php
    then
        chmod g+w $APACHE_ROOT_DOCUMENT/ocsreports/dbconfig.inc.php >> $SETUP_LOG 2>&1
        if [ $? != 0 ]
        then
            echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/ocsreports/dbconfig.inc.php, please look at error in $SETUP_LOG and fix !"
            echo
            echo "Installation aborted !"
            exit 1
        fi
    fi
    
    echo
    echo "Configuring IPDISCOVER-UTIL Perl script."
    echo "Configuring IPDISCOVER-UTIL Perl script (ed ipdiscover-util.pl)" >> $SETUP_LOG
    cp ipdiscover-util/ipdiscover-util.pl ipdiscover-util/ipdiscover-util.pl.local >> $SETUP_LOG 2>&1
#
# Now using perl to replace string in file instead of ed, not available by default in Mandriva Linux
#
#    $ED_BIN ipdiscover-util/ipdiscover-util.pl.local << EOF >> $SETUP_LOG 2>&1
#        1,$ g/^ *my $dbhost*/s#localhost#$DB_SERVER_HOST#
#        1,$ g/^ *my $dbp*/s#3306#$DB_SERVER_PORT#
#        w
#        q
#EOF
    $PERL_BIN -pi -e "s#localhost#$DB_SERVER_HOST#g" ipdiscover-util/ipdiscover-util.pl.local
    $PERL_BIN -pi -e "s#3306#$DB_SERVER_PORT#g" ipdiscover-util/ipdiscover-util.pl.local
    echo "******** Begin updated ipdiscover-util.pl script ***********" >> $SETUP_LOG
    cat ipdiscover-util/ipdiscover-util.pl.local >> $SETUP_LOG
    echo "******** End updated ipdiscover-util.pl script ***********" >> $SETUP_LOG
    echo
    echo "Installing IPDISCOVER-UTIL Perl script."
    echo "Installing IPDISCOVER-UTIL Perl script" >> $SETUP_LOG
    cp ipdiscover-util/ipdiscover-util.pl.local $APACHE_ROOT_DOCUMENT/ocsreports/ipdiscover-util.pl >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to copy files in $APACHE_ROOT_DOCUMENT/ocsreports, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    echo
    echo "Fixing permissions on IPDISCOVER-UTIL Perl script."
    echo "Fixing permissions on IPDISCOVER-UTIL Perl script" >> $SETUP_LOG
    chown root:$APACHE_GROUP $APACHE_ROOT_DOCUMENT/ocsreports/ipdiscover-util.pl >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/ocsreports, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    chmod gou+x $APACHE_ROOT_DOCUMENT/ocsreports/ipdiscover-util.pl >> $SETUP_LOG 2>&1
    if [ $? != 0 ]
    then
        echo "*** ERROR: Unable to set permissions on $APACHE_ROOT_DOCUMENT/ocsreports, please look at error in $SETUP_LOG and fix !"
        echo
        echo "Installation aborted !"
        exit 1
    fi
    
    echo
    echo "+----------------------------------------------------------+"
    echo "| OK, Administration server installation finished ;-)      |"
    echo "|                                                          |"
    echo "| Point your browser to http://server/ocsreports to        |"
    echo "| configure database server and create/update schema.      |"
    echo "+----------------------------------------------------------+"
    echo
    echo "Administration server installation successfull" >> $SETUP_LOG
fi

echo
echo "Setup has created a log file $SETUP_LOG. Please, save this file."
echo "If you encounter error while running OCS Inventory NG Management server,"
echo "we can ask you to show us his content !"
echo
echo "DON'T FORGET TO RESTART APACHE DAEMON !"
echo
echo "Enjoy OCS Inventory NG ;-)"
echo
exit 0
