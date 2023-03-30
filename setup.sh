#!/bin/sh
################################################################################
#
# OCS Inventory NG Management Server Setup
#
# Copyleft 2006 Didier LIROULET
# Web: http://www.ocsinventory-ng.org
#
# This code is open source and may be copied and modified as long as the source
# code is always made freely available.
# Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
################################################################################
#

if [[ -e setup.answers ]]; then
	source setup.answers
fi

# Which host run database server
DB_SERVER_HOST="${DB_SERVER_HOST:-localhost}"
# On which port run database server
DB_SERVER_PORT="${DB_SERVER_PORT:-3306}"
# Database server credentials
DB_SERVER_USER="${DB_SERVER_USER:-ocs}"
DB_SERVER_PWD="${DB_SERVER_PWD:-ocs}"
# Where is Apache daemon binary (if empty, will try to find it)
APACHE_BIN="${APACHE_BIN:-}"
# Where is Apache configuration file (if empty, will try to find it)
APACHE_CONFIG_FILE="${APACHE_CONFIG_FILE:-}"
# Where is Apache includes configuration directory (if emty, will try to find it)
APACHE_CONFIG_DIRECTORY="${APACHE_CONFIG_DIRECTORY:-}"
# Which user is running Apache web server (if empty, will try to find it)
APACHE_USER="${APACHE_USER:-}"
# Which group is running Apache web server (if empty, will try to find it)
APACHE_GROUP="${APACHE_GROUP:-}"
# Where is Apache document root directory (if empty, will try to find it)
APACHE_ROOT_DOCUMENT="${APACHE_ROOT_DOCUMENT:-}"
# Which version of mod_perl is apache using,  1 for <= 1.999_21 and 2 for >= 1.999_22 (if empty, user will be asked for)
APACHE_MOD_PERL_VERSION="${APACHE_MOD_PERL_VERSION:-}"
# Where are located OCS Communication server log files
OCS_COM_SRV_LOG="${OCS_COM_SRV_LOG:-/var/log/ocsinventory-server}"
# Where are located OCS Communication server plugins configuration files
OCS_COM_SRV_PLUGINS_CONFIG_DIR="${OCS_COM_SRV_PLUGINS_CONFIG_DIR:-/etc/ocsinventory-server/plugins}"
# Where are located OCS Communication server plugins perl files
OCS_COM_SRV_PLUGINS_PERL_DIR="${OCS_COM_SRV_PLUGINS_PERL_DIR:-/etc/ocsinventory-server/perl}"
# Where is located perl interpreter
PERL_BIN=${PERL_BIN:-$(which perl 2>/dev/null)}
# Where is located make utility
MAKE=${MAKE:-$(which make 2>/dev/null)}
# Where is located logrotate configuration directory
LOGROTATE_CONF_DIR="${LOGROTATE_CONF_DIR:-/etc/logrotate.d}"
# Where is located newsyslog.conf
NEWSYSLOG_CONF_FILE="${NEWSYSLOG_CONF_FILE:-/etc/newsyslog.conf}"
# Where to store setup logs
SETUP_LOG=${SETUP_LOG:-$(pwd)/ocs_server_setup.log}
# Communication Server Apache configuration file
COM_SERVER_APACHE_CONF_FILE="${COM_SERVER_APACHE_CONF_FILE:-ocsinventory-server.conf}"
# Rest API configuration file
API_REST_APACHE_CONF_FILE="${API_REST_APACHE_CONF_FILE:-ocsinventory-restapi.conf}"
# Communication Server logrotate configuration file
COM_SERVER_LOGROTATE_CONF_FILE="${COM_SERVER_LOGROTATE_CONF_FILE:-ocsinventory-server}"
# Administration Console Apache configuration file
ADM_SERVER_APACHE_CONF_FILE="${ADM_SERVER_APACHE_CONF_FILE:-ocsinventory-reports.conf}"
# Administration console read only files directory
ADM_SERVER_STATIC_DIR="${ADM_SERVER_STATIC_DIR:-/usr/share/ocsinventory-reports}"
ADM_SERVER_STATIC_REPORTS_DIR="${ADM_SERVER_STATIC_REPORTS_DIR:-ocsreports}"
ADM_SERVER_REPORTS_ALIAS="${ADM_SERVER_REPORTS_ALIAS:-/ocsreports}"
# Administration console read/write files dir
ADM_SERVER_VAR_DIR="${ADM_SERVER_VAR_DIR:-/var/lib/ocsinventory-reports}"
# Administration default packages directory and Apache alias
ADM_SERVER_VAR_PACKAGES_DIR="${ADM_SERVER_VAR_PACKAGES_DIR:-download}"
ADM_SERVER_PACKAGES_ALIAS="${ADM_SERVER_PACKAGES_ALIAS:-/download}"
# Administration default snmp directory and Apache alias
ADM_SERVER_VAR_SNMP_DIR="${ADM_SERVER_VAR_SNMP_DIR:-snmp}"
ADM_SERVER_SNMP_ALIAS="${ADM_SERVER_SNMP_ALIAS:-/snmp}"
# Administration console tmp files dir
ADM_SERVER_VAR_TMP_DIR="${ADM_SERVER_VAR_TMP_DIR:-tmp_dir}"
# Administration console log files dir
ADM_SERVER_VAR_LOGS_DIR="${ADM_SERVER_VAR_LOGS_DIR:-logs}"
# Administration console scripts log files dir
ADM_SERVER_VAR_SCRIPTS_LOGS_DIR="${ADM_SERVER_VAR_SCRIPTS_LOGS_DIR:-scripts}"
# Administration console default ipdsicover-util.pl cache dir
ADM_SERVER_VAR_IPD_DIR="${ADM_SERVER_VAR_IPD_DIR:-ipd}"
# OS or linux distribution from automatic detection
UNIX_DISTRIBUTION="${UNIX_DISTRIBUTION:-}"
# Default install directory for rest api
REST_API_DIRECTORY="${REST_API_DIRECTORY:-}"

###################### DO NOT MODIFY BELOW #######################

# Check for Apache web server binaries
echo
echo "+----------------------------------------------------------+"
echo "|                                                          |"
echo "|  Welcome to OCS Inventory NG Management server setup !   |"
echo "|                                                          |"
echo "+----------------------------------------------------------+"
echo

# Check for OS or linux distribution
echo "Trying to determine which OS or Linux distribution you use"

if [ -f /etc/redhat-release ]; then
	UNIX_DISTRIBUTION="redhat"
elif [ -f /etc/debian_version ]; then
	UNIX_DISTRIBUTION="debian"
elif [ -f /etc/SuSE-release ]; then
	UNIX_DISTRIBUTION="suse"
fi

# Check for Apache web server binaries
echo "+----------------------------------------------------------+"
echo "| Checking for Apache web server binaries !				|"
echo "+----------------------------------------------------------+"
echo

echo "CAUTION: If upgrading Communication server from OCS Inventory NG 1.0 RC2 and"
echo "previous, please remove any Apache configuration for Communication Server!"
echo
echo -n "Do you wish to continue ([y]/n)?"
read ligne
if [ -z "$ligne" ] || [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
	echo "Assuming Communication server 1.0 RC2 or previous is not installed"
	echo "on this computer."
	echo
else
	echo "Installation aborted !"
	echo
	exit 1
fi

echo >$SETUP_LOG
OCS_LOCAL_DATE=$(date +%Y-%m-%d-%H-%M-%S)
echo "Starting OCS Inventory NG Management server setup on $OCS_LOCAL_DATE" >>$SETUP_LOG
echo -n "from folder " >>$SETUP_LOG
pwd >>$SETUP_LOG
echo -n "Starting OCS Inventory NG Management server setup from folder "
pwd
echo "Storing log in file $SETUP_LOG" >>$SETUP_LOG
echo "Storing log in file $SETUP_LOG"
echo >>$SETUP_LOG

echo "============================================================" >>$SETUP_LOG
echo "Checking OCS Inventory NG Management Server requirements..." >>$SETUP_LOG
echo "============================================================" >>$SETUP_LOG
echo
echo "+----------------------------------------------------------+"
echo "| Checking for database server properties...			  |"
echo "+----------------------------------------------------------+"
echo

# Check mysql client distribution version
echo "Checking for database server properties" >>$SETUP_LOG
DB_CLIENT_MAJOR_VERSION=$(eval mysql -V | cut -d' ' -f6 | cut -d'.' -f1) >>$SETUP_LOG 2>&1
DB_CLIENT_MINOR_VERSION=$(eval mysql -V | cut -d' ' -f6 | cut -d'.' -f2) >>$SETUP_LOG 2>&1

if [ "$DB_CLIENT_MAJOR_VERSION" = "Linux" ]; then
	DB_CLIENT_MAJOR_VERSION=$(eval mysql -V | cut -d' ' -f4 | cut -d'.' -f1) >>$SETUP_LOG 2>&1
	DB_CLIENT_MINOR_VERSION=$(eval mysql -V | cut -d' ' -f4 | cut -d'.' -f2) >>$SETUP_LOG 2>&1
fi
echo "Your MySQL client seems to be part of MySQL version $DB_CLIENT_MAJOR_VERSION.$DB_CLIENT_MINOR_VERSION."
echo "MySQL client distribution version $DB_CLIENT_MAJOR_VERSION.$DB_CLIENT_MINOR_VERSION." >>$SETUP_LOG

# Ensure mysql distribution is 4.1 or higher
if [ $DB_CLIENT_MAJOR_VERSION -gt 4 ]; then
	res=1
else
	if [ $DB_CLIENT_MAJOR_VERSION -eq 4 ]; then
		if [ $DB_CLIENT_MINOR_VERSION -eq 1 ]; then
			res=1
		else
			res=0
		fi
	else
		res=0
	fi
fi
if [ $res -eq 0 ]; then
	# Not 4.1 or higher, ask user to contnue ?
	echo "Your computer does not seem to be compliant with MySQL 4.1 or higher."
	echo -n "Do you wish to continue (y/[n])?"
	read ligne
	if [ "$ligne" = "y" ]; then
		echo "Ensure your database server is running MySQL 4.1 or higher !"
		echo "Ensure also this computer is able to connect to your MySQL server !"
	else
		echo "Installation aborted !"
		exit 1
	fi
else
	echo "Your computer seems to be running MySQL 4.1 or higher, good ;-)"
	echo "Computer seems to be running MySQL 4.1 or higher" >>$SETUP_LOG
fi
echo

# Ask user for database server host
res=0
while [ $res -eq 0 ]; do
	echo -n "Which host is running database server [$DB_SERVER_HOST] ?"
	read ligne
	if [ -z "$ligne" ]; then
		res=1
	else
		DB_SERVER_HOST="$ligne"
		res=1
	fi
done

echo "OK, database server is running on host $DB_SERVER_HOST ;-)"
echo "Database server is running on host $DB_SERVER_HOST" >>$SETUP_LOG
echo

# Ask user for database server port
res=0
while [ $res -eq 0 ]; do
	echo -n "On which port is running database server [$DB_SERVER_PORT] ?"
	read ligne
	if [ -z "$ligne" ]; then
		res=1
	else
		DB_SERVER_PORT="$ligne"
		res=1
	fi
done

echo "OK, database server is running on port $DB_SERVER_PORT ;-)"
echo "Database server is running on port $DB_SERVER_PORT" >>$SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for Apache web server daemon...				|"
echo "+----------------------------------------------------------+"
echo
echo "Checking for Apache web server daemon" >>$SETUP_LOG

# Try to find Apache daemon
if [ -z "$APACHE_BIN" ]; then
	APACHE_BIN_FOUND=$(which httpd 2>/dev/null)
	if [ -z "$APACHE_BIN_FOUND" ]; then
		APACHE_BIN_FOUND=$(which apache2ctl 2>/dev/null)
		if [ -z "$APACHE_BIN_FOUND" ]; then
			APACHE_BIN_FOUND=$(which apachectl 2>/dev/null)
			if [ -z "$APACHE_BIN_FOUND" ]; then
				APACHE_BIN_FOUND=$(which httpd2 2>/dev/null)
			fi
		fi
	fi
fi
echo "Found Apache daemon $APACHE_BIN_FOUND" >>$SETUP_LOG

# Ask user's confirmation
res=0
while [ $res -eq 0 ]; do
	echo -n "Where is Apache daemon binary [$APACHE_BIN_FOUND] ?"
	read ligne
	if [ -z "$ligne" ]; then
		APACHE_BIN=$APACHE_BIN_FOUND
	else
		APACHE_BIN="$ligne"
	fi
	# Ensure file exists and is executable
	if [ -x $APACHE_BIN ]; then
		res=1
	else
		echo "*** ERROR: $APACHE_BIN is not executable !"
		res=0
	fi
	# Ensure file is not a directory
	if [ -d $APACHE_BIN ]; then
		echo "*** ERROR: $APACHE_BIN is a directory !"
		res=0
	fi
done
echo "OK, using Apache daemon $APACHE_BIN ;-)"
echo "Using Apache daemon $APACHE_BIN" >>$SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for Apache main configuration file...		  |"
echo "+----------------------------------------------------------+"
echo

# Try to find Apache main configuration file
echo "Checking for Apache main configuration file" >>$SETUP_LOG
if [ -z "$APACHE_CONFIG_FILE" ]; then
	APACHE_ROOT=$(eval $APACHE_BIN -V | grep "HTTPD_ROOT" | cut -d'=' -f2 | tr -d '"')
	echo "Found Apache HTTPD_ROOT $APACHE_ROOT" >>$SETUP_LOG
	APACHE_CONFIG=$(eval $APACHE_BIN -V | grep "SERVER_CONFIG_FILE" | cut -d'=' -f2 | tr -d '"')
	echo "Found Apache SERVER_CONFIG_FILE $APACHE_CONFIG" >>$SETUP_LOG
	if [ -e $APACHE_CONFIG ]; then
		APACHE_CONFIG_FILE_FOUND="$APACHE_CONFIG"
	else
		APACHE_CONFIG_FILE_FOUND="$APACHE_ROOT/$APACHE_CONFIG"
	fi
fi
echo "Found Apache main configuration file $APACHE_CONFIG_FILE_FOUND" >>$SETUP_LOG

# Ask user's confirmation
res=0
while [ $res -eq 0 ]; do
	echo -n "Where is Apache main configuration file [$APACHE_CONFIG_FILE_FOUND] ?"
	read ligne
	if [ -z "$ligne" ]; then
		APACHE_CONFIG_FILE=$APACHE_CONFIG_FILE_FOUND
	else
		APACHE_CONFIG_FILE="$ligne"
	fi
	# Ensure file is not a directory
	if [ -d $APACHE_CONFIG_FILE ]; then
		echo "*** ERROR: $APACHE_CONFIG_FILE is a directory !"
		res=0
	fi
	# Ensure file exists and is readable
	if [ -r $APACHE_CONFIG_FILE ]; then
		res=1
	else
		echo "*** ERROR: $APACHE_CONFIG_FILE is not readable !"
		res=0
	fi
done
echo "OK, using Apache main configuration file $APACHE_CONFIG_FILE ;-)"
echo "Using Apache main configuration file $APACHE_CONFIG_FILE" >>$SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for Apache user account...					 |"
echo "+----------------------------------------------------------+"
echo

# Try to find Apache main configuration file
echo "Checking for Apache user account" >>$SETUP_LOG
if [ -z "$APACHE_USER" ]; then
	case $UNIX_DISTRIBUTION in
	"debian")
		if [ -f /etc/apache2/envvars ]; then
			. /etc/apache2/envvars
		fi
		APACHE_USER_FOUND=$APACHE_RUN_USER
		;;
	"suse")
		if [ -f /etc/apache2/uid.conf ]; then
			APACHE_USER_FOUND=$(cat /etc/apache2/uid.conf | grep "User" | tail -1 | cut -d' ' -f2)
		fi
		;;
	"redhat")
		APACHE_USER_FOUND=$(cat $APACHE_CONFIG_FILE | grep "User " | tail -1 | cut -d' ' -f2)
		;;
	esac
fi
echo "Found Apache user account $APACHE_USER_FOUND" >>$SETUP_LOG

# Ask user's confirmation
res=0
while [ $res -eq 0 ]; do
	echo -n "Which user account is running Apache web server [$APACHE_USER_FOUND] ?"
	read ligne
	if [ -z "$ligne" ]; then
		APACHE_USER=$APACHE_USER_FOUND
	else
		APACHE_USER="$ligne"
	fi
	# Ensure group exist in /etc/passwd
	if [ $(cat /etc/passwd | grep $APACHE_USER | wc -l) -eq 0 ]; then
		echo "*** ERROR: account $APACHE_USER not found in system table /etc/passwd !"
	else
		res=1
	fi
done
echo "OK, Apache is running under user account $APACHE_USER ;-)"
echo "Using Apache user account $APACHE_USER" >>$SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for Apache group...							|"
echo "+----------------------------------------------------------+"
echo

# Try to find Apache main configuration file
echo "Checking for Apache group" >>$SETUP_LOG
if [ -z "$APACHE_GROUP" ]; then
	case $UNIX_DISTRIBUTION in
	"debian")
		if [ -f /etc/apache2/envvars ]; then
			. /etc/apache2/envvars
		fi
		APACHE_GROUP_FOUND=$APACHE_RUN_USER
		;;
	"suse")
		if [ -f /etc/apache2/uid.conf ]; then
			APACHE_GROUP_FOUND=$(cat /etc/apache2/uid.conf | grep "Group" | tail -1 | cut -d' ' -f2)
		fi
		;;
	"redhat")
		APACHE_GROUP_FOUND=$(cat $APACHE_CONFIG_FILE | grep "Group " | tail -1 | cut -d' ' -f2)
		;;
	esac

	if [ -z "$APACHE_GROUP_FOUND" ]; then
		# No group found, assume group name is the same as account
		echo "No Apache user group found, assuming group name is the same as user account" >>$SETUP_LOG
		APACHE_GROUP_FOUND=$APACHE_USER
	fi
fi
echo "Found Apache user group $APACHE_GROUP_FOUND" >>$SETUP_LOG

# Ask user's confirmation
res=0
while [ $res -eq 0 ]; do
	echo -n "Which user group is running Apache web server [$APACHE_GROUP_FOUND] ?"
	read ligne
	if [ -z "$ligne" ]; then
		APACHE_GROUP=$APACHE_GROUP_FOUND
	else
		APACHE_GROUP="$ligne"
	fi
	# Ensure group exist in /etc/group
	if [ $(cat /etc/group | grep $APACHE_GROUP | wc -l) -eq 0 ]; then
		echo "*** ERROR: group $APACHE_GROUP not found in system table /etc/group !"
	else
		res=1
	fi
done
echo "OK, Apache is running under users group $APACHE_GROUP ;-)"
echo "Using Apache user group $APACHE_GROUP" >>$SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for Apache Include configuration directory...   |"
echo "+----------------------------------------------------------+"
echo

# Try to find Apache includes configuration directory
echo "Checking for Apache Include configuration directory" >>$SETUP_LOG
if [ -z "$APACHE_CONFIG_DIRECTORY" ]; then
	if [ -d "$APACHE_ROOT/conf.d" ]; then
		APACHE_CONFIG_DIRECTORY_FOUND="$APACHE_ROOT/conf.d"
	elif [ -d "$APACHE_ROOT/conf-available" ]; then
		APACHE_CONFIG_DIRECTORY_FOUND="$APACHE_ROOT/conf-available"
	else
		APACHE_CONFIG_DIRECTORY_FOUND=""
	fi

	if [ -d "$APACHE_CONFIG_DIRECTORY_FOUND" ]; then
		echo "Found Apache Include configuration directory $APACHE_CONFIG_DIRECTORY_FOUND" >>$SETUP_LOG
	fi
fi

# Ask user's confirmation
echo "Setup found Apache Include configuration directory in"
echo "$APACHE_CONFIG_DIRECTORY_FOUND."
echo "Setup will put OCS Inventory NG Apache configuration in this directory."
res=0
while [ $res -eq 0 ]; do
	echo -n "Where is Apache Include configuration directory [$APACHE_CONFIG_DIRECTORY_FOUND] ?"
	read ligne
	if [ -z "$ligne" ]; then
		APACHE_CONFIG_DIRECTORY=$APACHE_CONFIG_DIRECTORY_FOUND
	else
		APACHE_CONFIG_DIRECTORY="$ligne"
	fi

	# Ensure file is a directory
	if [ -d $APACHE_CONFIG_DIRECTORY ]; then
		res=1
	else
		echo "*** ERROR: $APACHE_CONFIG_DIRECTORY is not a directory !"
		res=0
	fi

	# Ensure directory exists and is writable
	if [ -w $APACHE_CONFIG_DIRECTORY ]; then
		res=1
	else
		echo "*** ERROR: $APACHE_CONFIG_DIRECTORY is not writable !"
		res=0
	fi
done

echo "OK, Apache Include configuration directory $APACHE_CONFIG_DIRECTORY found ;-)"
echo "Using Apache Include configuration directory $APACHE_CONFIG_DIRECTORY" >>$SETUP_LOG
echo

echo
echo "+----------------------------------------------------------+"
echo "| Checking for PERL Interpreter...						|"
echo "+----------------------------------------------------------+"
echo

echo "Checking for PERL Interpreter" >>$SETUP_LOG
if [ -z "$PERL_BIN" ]; then
	echo "PERL Interpreter not found !"
	echo "PERL Interpreter not found" >>$SETUP_LOG
	echo "OCS Inventory NG is not able to work without PERL Interpreter."
	echo "Setup manually PERL first."
	echo "Installation aborted !"
	echo "installation aborted" >>$SETUP_LOG
	exit 1
else
	echo "Found PERL interpreter at <$PERL_BIN> ;-)"
	echo "Found PERL interpreter at <$PERL_BIN>" >>$SETUP_LOG
fi

# Ask user's confirmation
res=0
while [ $res -eq 0 ]; do
	echo -n "Where is PERL interpreter binary [$PERL_BIN] ?"
	read ligne
	if [ -n "$ligne" ]; then
		PERL_BIN="$ligne"
	fi

	# Ensure file exists and is executable
	if [ -x $PERL_BIN ]; then
		res=1
	else
		echo "*** ERROR: $PERL_BIN is not executable !"
		res=0
	fi

	# Ensure file is not a directory
	if [ -d $PERL_BIN ]; then
		echo "*** ERROR: $PERL_BIN is a directory !"
		res=0
	fi
done

echo "OK, using PERL interpreter $PERL_BIN ;-)"
echo "Using PERL interpreter $PERL_BIN" >>$SETUP_LOG
echo

echo
echo -n "Do you wish to setup Communication server on this computer ([y]/n)?"
read ligne
if [ -z "$ligne" ] || [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
	# Setting up Communication server
	echo >>$SETUP_LOG
	echo "============================================================" >>$SETUP_LOG
	echo "Installing Communication server" >>$SETUP_LOG
	echo "============================================================" >>$SETUP_LOG
	echo

	echo
	echo "+----------------------------------------------------------+"
	echo "|             Checking for Make utility...                 |"
	echo "+----------------------------------------------------------+"
	echo

	echo "Checking for Make utility" >>$SETUP_LOG
	if [ -z "$MAKE" ]; then
		echo "Make utility not found !"
		echo "Make utility not found" >>$SETUP_LOG
		echo "Setup is not able to build OCS Inventory NG Perl module."
		echo "Unable to build OCS Inventory NG Perl module !" >>$SETUP_LOG
		exit 1
	else
		echo "OK, Make utility found at <$MAKE> ;-)"
		echo "Make utility found at <$MAKE>" >>$SETUP_LOG
	fi
	echo

	echo "+----------------------------------------------------------+"
	echo "|        Checking for Apache mod_perl version...           |"
	echo "+----------------------------------------------------------+"
	echo

	echo "Checking for Apache mod_perl version 1.99_22 or higher"
	echo "Checking for Apache mod_perl version 1.99_22 or higher" >>$SETUP_LOG
	$PERL_BIN -mmod_perl2 -e 'print "mod_perl 1.99_22 or higher is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		# mod_perl 2 not found !
		echo "Checking for Apache mod_perl version 1.99_21 or previous"
		echo "Checking for Apache mod_perl version 1.99_21 or previous" >>$SETUP_LOG
		$PERL_BIN -mmod_perl -e 'print "mod_perl 1.99_21 or previous is available\n"' >>$SETUP_LOG 2>&1
		if [ $? -ne 0 ]; then
			# mod_perl 1 not found => Ask user
			res=0
			while [ $res -eq 0 ]; do
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
				if [ -z "$ligne" ]; then
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
	if [ $APACHE_MOD_PERL_VERSION -eq 1 ]; then
		echo "OK, Apache is using mod_perl version 1.99_21 or previous ;-)"
		echo "Using mod_perl version 1.99_21 or previous" >>$SETUP_LOG
	else
		echo "OK, Apache is using mod_perl version 1.99_22 or higher ;-)"
		echo "Using mod_perl version 1.99_22 or higher" >>$SETUP_LOG
	fi
	echo

	echo "+----------------------------------------------------------+"
	echo "|    Checking for Communication server log directory...    |"
	echo "+----------------------------------------------------------+"
	echo
	echo "Checking for Communication server log directory" >>$SETUP_LOG

	# Ask user
	res=0
	while [ $res -eq 0 ]; do
		echo "Communication server can create detailed logs. This logs can be enabled"
		echo "by setting integer value of LOGLEVEL to 1 in Administration console"
		echo "menu Configuration."
		echo -n "Where to put Communication server log directory [$OCS_COM_SRV_LOG] ?"
		read ligne
		if [ -n "$ligne" ]; then
			OCS_COM_SRV_LOG=$ligne
		fi
		res=1
	done

	echo "OK, Communication server will put logs into directory $OCS_COM_SRV_LOG ;-)"
	echo "Using $OCS_COM_SRV_LOG as Communication server log directory" >>$SETUP_LOG
	echo

	echo "+----------------------------------------------------------------------------+"
	echo "|    Checking for Communication server plugins configuration directory...    |"
	echo "+----------------------------------------------------------------------------+"
	echo
	echo "Checking for Communication server plugins configuration directory" >>$SETUP_LOG

	# Ask user
	res=0
	while [ $res -eq 0 ]; do
		echo "Communication server need a directory for plugins configuration files. "
		echo -n "Where to put Communication server plugins configuration files [$OCS_COM_SRV_PLUGINS_CONFIG_DIR] ?"
		read ligne
		if [ -n "$ligne" ]; then
			OCS_COM_SRV_PLUGINS_CONFIG_DIR=$ligne
		fi
		res=1
	done
	echo "OK, Communication server will put plugins configuration files into directory $OCS_COM_SRV_PLUGINS_CONFIG_DIR ;-)"
	echo "Using $OCS_COM_SRV_PLUGINS_CONFIG_DIR as Communication server plugins configuration directory" >>$SETUP_LOG
	echo

	echo "+-------------------------------------------------------------------+"
	echo "|   Checking for Communication server plugins perl directory...     |"
	echo "+-------------------------------------------------------------------+"
	echo
	echo "Checking for Communication server perl directory" >>$SETUP_LOG

	# Ask user
	res=0
	while [ $res -eq 0 ]; do
		echo "Communication server need a directory for plugins Perl modules files."
		echo -n "Where to put Communication server plugins Perl modules files [$OCS_COM_SRV_PLUGINS_PERL_DIR] ?"
		read ligne
		if [ -n "$ligne" ]; then
			OCS_COM_SRV_PLUGINS_PERL_DIR=$ligne
		fi
		res=1
	done
	echo "OK, Communication server will put plugins Perl modules files into directory $OCS_COM_SRV_PLUGINS_PERL_DIR ;-)"
	echo "Using $OCS_COM_SRV_PLUGINS_PERL_DIR as Communication server plugins perl directory" >>$SETUP_LOG
	echo

	# jump to communication server directory
	echo "Entering Apache sub directory" >>$SETUP_LOG

	# Check for required Perl Modules (if missing, please install before)
	#	- DBI 1.40 or higher
	#	- Apache::DBI 0.93 or higher
	#	- DBD::mysql 2.9004 or higher
	#	- Compress::Zlib 1.33 or higher
	#	- XML::Simple 2.12 or higher
	#	- Net::IP 1.21 or higher
	#	- Archive::Zip
	echo
	echo "+----------------------------------------------------------+"
	echo "| Checking for required Perl Modules...					|"
	echo "+----------------------------------------------------------+"
	echo

	REQUIRED_PERL_MODULE_MISSING=0
	DBI=0
	APACHE_DBI=0
	DBD_MYSQL=0
	COMPRESS_ZLIB=0
	XML_SIMPLE=0
	NET_IP=0
	ARCHIVE_ZIP=0

	echo "Checking for DBI PERL module..."
	echo "Checking for DBI PERL module" >>$SETUP_LOG
	$PERL_BIN -mDBI -e 'print "PERL module DBI is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module DBI is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
		DBI=1
	else
		echo "Found that PERL module DBI is available."
	fi

	echo "Checking for Apache::DBI PERL module..."
	echo "Checking for Apache::DBI PERL module" >>$SETUP_LOG
	$PERL_BIN -mApache::DBI -e 'print "PERL module Apache::DBI is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module Apache::DBI is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
		APACHE_DBI=1
	else
		echo "Found that PERL module Apache::DBI is available."
	fi

	echo "Checking for DBD::mysql PERL module..."
	echo "Checking for DBD::mysql PERL module" >>$SETUP_LOG
	$PERL_BIN -mDBD::mysql -e 'print "PERL module DBD::mysql is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module DBD::mysql is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
		DBD_MYSQL=1
	else
		echo "Found that PERL module DBD::mysql is available."
	fi

	echo "Checking for Compress::Zlib PERL module..."
	echo "Checking for Compress::Zlib PERL module" >>$SETUP_LOG
	$PERL_BIN -mCompress::Zlib -e 'print "PERL module Compress::Zlib is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module Compress::Zlib is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
		COMPRESS_ZLIB=1
	else
		echo "Found that PERL module Compress::Zlib is available."
	fi

	echo "Checking for XML::Simple PERL module..."
	echo "Checking for XML::Simple PERL module" >>$SETUP_LOG
	$PERL_BIN -mXML::Simple -e 'print "PERL module XML::Simple is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module XML::Simple is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
		XML_SIMPLE=1
	else
		echo "Found that PERL module XML::Simple is available."
	fi

	echo "Checking for Net::IP PERL module..."
	echo "Checking for Net::IP PERL module" >>$SETUP_LOG
	$PERL_BIN -mNet::IP -e 'print "PERL module Net::IP is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module Net::IP is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
		NET_IP=1
	else
		echo "Found that PERL module Net::IP is available."
	fi

	# Check for Zip::Archive
	echo "Checking for Archive::Zip Perl module..."
	echo "Checking for Archive::Zip Perl module" >>$SETUP_LOG
	$PERL_BIN -mArchive::Zip -e 'print "PERL module Archive::Zip is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module Archive::Zip is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
		ARCHIVE_ZIP=1
	else
		echo "Found that PERL module Archive::Zip is available."
	fi

	if [ $REQUIRED_PERL_MODULE_MISSING -ne 0 ]; then
		echo "*** ERROR: There is one or more required PERL modules missing on your computer !"
		echo "Please, install missing PERL modules first."
		echo " "
		echo "OCS setup.sh can install perl module from packages for you"
		echo "The script will use the native package from your operating system like apt or rpm"
		echo -n "Do you wish to continue (y/[n])?"
		read ligne
		if [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
			case $UNIX_DISTRIBUTION in
			"redhat")
				echo "RedHat based automatic installation"
				if [ $DBI -eq 1 ]; then
					PACKAGE="$PACKAGE perl-DBI"
				fi
				if [ $APACHE_DBI -eq 1 ]; then
					PACKAGE="$PACKAGE perl-Apache-DBI"
				fi
				if [ $DBD_MYSQL -eq 1 ]; then
					PACKAGE="$PACKAGE perl-DBD-MySQL"
				fi
				if [ $COMPRESS_ZLIB -eq 1 ]; then
					PACKAGE="$PACKAGE perl-Compress-Zlib"
				fi
				if [ $XML_SIMPLE -eq 1 ]; then
					PACKAGE="$PACKAGE perl-XML-Simple"
				fi
				if [ $NET_IP -eq 1 ]; then
					PACKAGE="$PACKAGE perl-Net-IP"
				fi
				if [ $ARCHIVE_ZIP -eq 1 ]; then
					PACKAGE="$PACKAGE perl-Archive-Zip"
				fi

				yum install $PACKAGE
				if [ $? != 0 ]; then
					echo "Installation aborted !"
					echo "Installation script encounter problems to install packages !"
					echo "One or more required PERL modules missing !" >>$SETUP_LOG
					echo "Installation aborted" >>$SETUP_LOG
					exit 1
				fi
				echo "All packages have been installed on this computer"
				;;

			"debian")
				echo "Debian based automatic installation"
				if [ $DBI -eq 1 ]; then
					PACKAGE="$PACKAGE libdbi-perl"
				fi
				if [ $APACHE_DBI -eq 1 ]; then
					PACKAGE="$PACKAGE libapache-dbi-perl"
				fi
				if [ $DBD_MYSQL -eq 1 ]; then
					PACKAGE="$PACKAGE libdbd-mysql-perl"
				fi
				if [ $COMPRESS_ZLIB -eq 1 ]; then
					PACKAGE="$PACKAGE libcompress-zlib-perl"
				fi
				if [ $XML_SIMPLE -eq 1 ]; then
					PACKAGE="$PACKAGE libxml-simple-perl"
				fi
				if [ $NET_IP -eq 1 ]; then
					PACKAGE="$PACKAGE libnet-ip-perl"
				fi
				if [ $ARCHIVE_ZIP -eq 1 ]; then
					PACKAGE="$PACKAGE libarchive-zip-perl"
				fi

				apt-get update
				apt-get install $PACKAGE
				if [ $? -ne 0 ]; then
					echo "Installation aborted !"
					echo "Installation script encounter problems to install packages !"
					echo "One or more required PERL modules missing !" >>$SETUP_LOG
					echo "Installation aborted" >>$SETUP_LOG
					exit 1
				fi
				echo "All packages have been installed on this computer"
				;;

			*)
				echo "Installation aborted !"
				echo "Installation script cannot find missing packages for your distribution"
				echo "One or more required PERL modules missing !" >>$SETUP_LOG
				echo "Installation aborted" >>$SETUP_LOG
				exit 1
				;;
			esac
		else
			echo "Installation aborted !"
			echo "Please, install missing PERL modules first."
			echo "One or more required PERL modules missing !" >>$SETUP_LOG
			echo "Installation aborted" >>$SETUP_LOG
			exit 1
		fi
	fi
	echo

	echo
	echo -n "Do you wish to setup Rest API server on this computer ([y]/n)?"
	read ligne
	if [ -z "$ligne" ] || [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
		echo
		echo "+----------------------------------------------------------+"
		echo "| Checking for REST API Dependencies ...              		 |"
		echo "+----------------------------------------------------------+"
		echo

		# Dependencies :
		# => Mojolicious::Lite
		# => Plack
		# => Switch

		$PERL_BIN -mMojolicious::Lite -e 'print "PERL module Mojolicious::Lite is available\n"' >>$SETUP_LOG 2>&1
		if [ $? -ne 0 ]; then
			echo "*** ERROR: PERL module Mojolicious::Lite is not installed !"
			echo -n "Do you wish to continue (y/[n])?"
			read ligne
			if [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
				echo "User choose to continue setup without PERL module Mojolicious::Lite" >>$SETUP_LOG
			else
				echo
				echo "Installation aborted !"
				echo "User choose to abort installation !" >>$SETUP_LOG
				exit 1
			fi
		else
			echo "Found that PERL module Mojolicious::Lite is available."
		fi

		$PERL_BIN -mSwitch -e 'print "PERL module Switch is available\n"' >>$SETUP_LOG 2>&1
		if [ $? -ne 0 ]; then
			echo "*** ERROR: PERL module Switch is not installed !"
			echo -n "Do you wish to continue (y/[n])?"
			read ligne
			if [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
				echo "User choose to continue setup without PERL module Switch" >>$SETUP_LOG
			else
				echo
				echo "Installation aborted !"
				echo "User choose to abort installation !" >>$SETUP_LOG
				exit 1
			fi
		else
			echo "Found that PERL module Switch is available."
		fi

		$PERL_BIN -mPlack::Handler -e 'print "PERL module Plack::Handler is available\n"' >>$SETUP_LOG 2>&1
		if [ $? -ne 0 ]; then
			echo "*** ERROR: PERL module Plack::Handler is not installed !"
			echo -n "Do you wish to continue (y/[n])?"
			read ligne
			if [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
				echo "User choose to continue setup without PERL module Plack::Handler" >>$SETUP_LOG
			else
				echo
				echo "Installation aborted !"
				echo "User choose to abort installation !" >>$SETUP_LOG
				exit 1
			fi
		else
			echo "Found that PERL module Plack::Handler is available."
		fi

		echo
		echo "+----------------------------------------------------------+"
		echo "| Configuring REST API Server files ...               		 |"
		echo "+----------------------------------------------------------+"
		echo

		# Get first INC path to determine a valid path
		REST_API_DIRECTORY=$($PERL_BIN -e "print \"@INC[2]\"")

		echo -n "Where do you want the API code to be store [$REST_API_DIRECTORY] ?"
		read ligne
		if [ -z "$ligne" ]; then
			REST_API_DIRECTORY=$REST_API_DIRECTORY
		else
			REST_API_DIRECTORY="$ligne"
		fi

		echo "Copying files to $REST_API_DIRECTORY"
		echo "Copying files to $REST_API_DIRECTORY" >>$SETUP_LOG

		echo
		echo "+----------------------------------------------------------+"
		echo "| Configuring REST API Server configuration files ...  		 |"
		echo "+----------------------------------------------------------+"
		echo

		echo "Configuring Rest API server (file $API_REST_APACHE_CONF_FILE)" >>$SETUP_LOG
		cp etc/ocsinventory/$API_REST_APACHE_CONF_FILE $API_REST_APACHE_CONF_FILE.local
		$PERL_BIN -pi -e "s#'REST_API_PATH'#'$REST_API_DIRECTORY'#g" $API_REST_APACHE_CONF_FILE.local
		$PERL_BIN -pi -e "s#'REST_API_LOADER_PATH'#'$REST_API_DIRECTORY/Api/Ocsinventory/Restapi/Loader.pm'#g" $API_REST_APACHE_CONF_FILE.local
		echo "Writing Rest API configuration to file $APACHE_CONFIG_DIRECTORY/$API_REST_APACHE_CONF_FILE" >>$SETUP_LOG
		cp -f $API_REST_APACHE_CONF_FILE.local $APACHE_CONFIG_DIRECTORY/zz-$API_REST_APACHE_CONF_FILE >>$SETUP_LOG 2>&1
		cp -r Api/ $REST_API_DIRECTORY

	fi
	echo
	echo "+----------------------------------------------------------+"
	echo "|                 OK, looks good ;-)                       |"
	echo "|                                                          |"
	echo "|     Configuring Communication server Perl modules...     |"
	echo "+----------------------------------------------------------+"
	echo
	echo "Configuring Communication server (perl Makefile.PL)" >>$SETUP_LOG
	cd "Apache"
	$PERL_BIN Makefile.PL
	if [ $? -ne 0 ]; then
		echo -n "Warning: Prerequisites too old ! Do you wish to continue (y/[n])?"
		read ligne
		if [ "$ligne" = "y" ]; then
			echo "Maybe Communication server will encounter problems. Continuing anyway."
			echo "Warning: Prerequisites too old ! Continuing anyway" >>$SETUP_LOG
		else
			echo "Installation aborted !"
			exit 1
		fi
	fi

	echo
	echo "+----------------------------------------------------------+"
	echo "|                 OK, looks good ;-)                       |"
	echo "|                                                          |"
	echo "|      Preparing Communication server Perl modules...      |"
	echo "+----------------------------------------------------------+"
	echo
	echo "Preparing Communication server Perl modules (make)" >>$SETUP_LOG
	$MAKE >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Prepare failed, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo
	echo "+----------------------------------------------------------+"
	echo "|                 OK, prepare finshed ;-)                  |"
	echo "|                                                          |"
	echo "|     Installing Communication server Perl modules...      |"
	echo "+----------------------------------------------------------+"
	echo
	echo "Installing Communication server Perl modules (make install)" >>$SETUP_LOG
	$MAKE install >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Install of Perl modules failed, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi
	cd ".."

	echo
	echo "+----------------------------------------------------------+"
	echo "| OK, Communication server Perl modules install finished;-)|"
	echo "|                                                          |"
	echo "|     Creating Communication server log directory...       |"
	echo "+----------------------------------------------------------+"
	echo
	echo "Creating Communication server log directory $OCS_COM_SRV_LOG."
	echo "Creating Communication server log directory $OCS_COM_SRV_LOG" >>$SETUP_LOG
	mkdir -p $OCS_COM_SRV_LOG >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to create log directory, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo
	echo "Fixing Communication server log directory files permissions."
	echo "Fixing Communication server log directory permissions" >>$SETUP_LOG
	chown -R $APACHE_USER:$APACHE_GROUP $OCS_COM_SRV_LOG >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set log directory permissions, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	chmod -R gu+rwx $OCS_COM_SRV_LOG >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set log directory permissions, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	chmod -R o-w $OCS_COM_SRV_LOG >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set log directory permissions, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Log rotation, BSD style
	if [ -f $NEWSYSLOG_CONF_FILE ]; then
		echo "*** WARNING Please configure log rotation for files in $OCS_COM_SRV_LOG"
	fi

	# Log rotation, Linux flavor
	if [ -d $LOGROTATE_CONF_DIR ]; then
		echo "Configuring logrotate for Communication server."
		echo "Configuring logrotate (ed logrotate.ocsinventory-NG)" >>$SETUP_LOG
		cp etc/logrotate.d/$COM_SERVER_LOGROTATE_CONF_FILE logrotate.$COM_SERVER_LOGROTATE_CONF_FILE.local
		$PERL_BIN -pi -e "s#PATH_TO_LOG_DIRECTORY#$OCS_COM_SRV_LOG#g" logrotate.$COM_SERVER_LOGROTATE_CONF_FILE.local
		echo "******** Begin updated logrotate.$COM_SERVER_LOGROTATE_CONF_FILE.local ***********" >>$SETUP_LOG
		cat logrotate.$COM_SERVER_LOGROTATE_CONF_FILE.local >>$SETUP_LOG
		echo "******** End updated logrotate.COM_SERVER_LOGROTATE_CONF_FILE.local ***********" >>$SETUP_LOG
		echo "Removing old communication server logrotate file $LOGROTATE_CONF_DIR/ocsinventory-NG"
		echo "Removing old communication server logrotate file $LOGROTATE_CONF_DIR/ocsinventory-NG" >>$SETUP_LOG
		rm -f "$LOGROTATE_CONF_DIR/ocsinventory-NG"
		echo "Writing communication server logrotate to file $LOGROTATE_CONF_DIR/$COM_SERVER_LOGROTATE_CONF_FILE"
		echo "Writing communication server logrotate to file $LOGROTATE_CONF_DIR/$COM_SERVER_LOGROTATE_CONF_FILE" >>$SETUP_LOG
		cp -f logrotate.$COM_SERVER_LOGROTATE_CONF_FILE.local $LOGROTATE_CONF_DIR/$COM_SERVER_LOGROTATE_CONF_FILE >>$SETUP_LOG 2>&1
		if [ $? -ne 0 ]; then
			echo "*** ERROR: Unable to configure log rotation, please look at error in $SETUP_LOG and fix !"
			echo
			echo "Installation aborted !"
			exit 1
		fi
	fi
	echo

	echo
	echo "+----------------------------------------------------------------------+"
	echo "|        OK, Communication server log directory created ;-)            |"
	echo "|                                                                      |"
	echo "|   Creating Communication server plugins configuration directory...   |"
	echo "+----------------------------------------------------------------------+"
	echo
	echo "Creating Communication server plugins configuration directory $OCS_COM_SRV_PLUGINS_CONFIG_DIR."
	echo "Creating Communication server plugins configuration directory $OCS_COM_SRV_PLUGINS_CONFIG_DIR" >>$SETUP_LOG
	mkdir -p $OCS_COM_SRV_PLUGINS_CONFIG_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to create plugins confguration directory, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi
	echo

	echo
	echo "+----------------------------------------------------------------------+"
	echo "| OK, Communication server plugins configuration directory created ;-) |"
	echo "|                                                                      |"
	echo "|        Creating Communication server plugins Perl directory...       |"
	echo "+----------------------------------------------------------------------+"
	echo
	echo "Creating Communication server plugins Perl directory $OCS_COM_SRV_PLUGINS_PERL_DIR."
	echo "Creating Communication server plugins Perl directory $OCS_COM_SRV_PLUGINS_PERL_DIR" >>$SETUP_LOG
	mkdir -p "$OCS_COM_SRV_PLUGINS_PERL_DIR/Apache/Ocsinventory/Plugins" >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to create plugins Perl directory, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi
	echo

	#Fix permissions on server side for plugin engine (perl / plugins) dir usually in etc/ocsinventory-server..
	# Where are located OCS Communication server plugins configuration files
	chown -R $APACHE_USER:$APACHE_GROUPE $OCS_COM_SRV_PLUGINS_CONFIG_DIR
	# Where are located OCS Communication server plugins perl files
	chown -R $APACHE_USER:$APACHE_GROUPE $OCS_COM_SRV_PLUGINS_PERL_DIR

	echo
	echo "+----------------------------------------------------------------------+"
	echo "|     OK, Communication server plugins Perl directory created ;-)      |"
	echo "|                                                                      |"
	echo "|               Now configuring Apache web server...                   |"
	echo "+----------------------------------------------------------------------+"
	echo
	echo "To ensure Apache loads mod_perl before OCS Inventory NG Communication Server,"
	echo "Setup can name Communication Server Apache configuration file"
	echo "'z-$COM_SERVER_APACHE_CONF_FILE' instead of '$COM_SERVER_APACHE_CONF_FILE'."
	echo "Do you allow Setup renaming Communication Server Apache configuration file"
	echo -n "to 'z-$COM_SERVER_APACHE_CONF_FILE' ([y]/n) ?"
	read ligne
	if [ -z $ligne ] || [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
		echo "OK, using 'z-$COM_SERVER_APACHE_CONF_FILE' as Communication Server Apache configuration file"
		echo "OK, using 'z-$COM_SERVER_APACHE_CONF_FILE' as Communication Server Apache configuration file" >>$SETUP_LOG
		FORCE_LOAD_AFTER_PERL_CONF=1
	else
		echo "OK, using '$COM_SERVER_APACHE_CONF_FILE' as Communication Server Apache configuration file"
		echo "OK, using '$COM_SERVER_APACHE_CONF_FILE' as Communication Server Apache configuration file" >>$SETUP_LOG
		FORCE_LOAD_AFTER_PERL_CONF=0
	fi

	echo "Configuring Apache web server (file $COM_SERVER_APACHE_CONF_FILE)" >>$SETUP_LOG
	cp etc/ocsinventory/$COM_SERVER_APACHE_CONF_FILE $COM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#DATABASE_SERVER#$DB_SERVER_HOST#g" $COM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#DATABASE_PORT#$DB_SERVER_PORT#g" $COM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#VERSION_MP#$APACHE_MOD_PERL_VERSION#g" $COM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#PATH_TO_LOG_DIRECTORY#$OCS_COM_SRV_LOG#g" $COM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#PATH_TO_PLUGINS_CONFIG_DIRECTORY#$OCS_COM_SRV_PLUGINS_CONFIG_DIR#g" $COM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#PATH_TO_PLUGINS_PERL_DIRECTORY#$OCS_COM_SRV_PLUGINS_PERL_DIR#g" $COM_SERVER_APACHE_CONF_FILE.local
	echo "******** Begin updated $COM_SERVER_APACHE_CONF_FILE.local ***********" >>$SETUP_LOG
	cat $COM_SERVER_APACHE_CONF_FILE.local >>$SETUP_LOG
	echo "******** End updated $COM_SERVER_APACHE_CONF_FILE.local ***********" >>$SETUP_LOG
	echo "Removing old communication server configuration to file $APACHE_CONFIG_DIRECTORY/ocsinventory.conf"
	echo "Removing old communication server configuration to file $APACHE_CONFIG_DIRECTORY/ocsinventory.conf" >>$SETUP_LOG
	rm -f "$APACHE_CONFIG_DIRECTORY/ocsinventory.conf"
	if [ $FORCE_LOAD_AFTER_PERL_CONF -eq 1 ]; then
		rm -f "$APACHE_CONFIG_DIRECTORY/$COM_SERVER_APACHE_CONF_FILE"
		echo "Writing communication server configuration to file $APACHE_CONFIG_DIRECTORY/z-$COM_SERVER_APACHE_CONF_FILE"
		echo "Writing communication server configuration to file $APACHE_CONFIG_DIRECTORY/z-$COM_SERVER_APACHE_CONF_FILE" >>$SETUP_LOG
		cp -f $COM_SERVER_APACHE_CONF_FILE.local $APACHE_CONFIG_DIRECTORY/z-$COM_SERVER_APACHE_CONF_FILE >>$SETUP_LOG 2>&1
		res=$?
		COM_SERVER_APACHE_CONF_FILE="z-$COM_SERVER_APACHE_CONF_FILE"
	else
		echo "Writing communication server configuration to file $APACHE_CONFIG_DIRECTORY/$COM_SERVER_APACHE_CONF_FILE"
		echo "Writing communication server configuration to file $APACHE_CONFIG_DIRECTORY/$COM_SERVER_APACHE_CONF_FILE" >>$SETUP_LOG
		cp -f $COM_SERVER_APACHE_CONF_FILE.local $APACHE_CONFIG_DIRECTORY/$COM_SERVER_APACHE_CONF_FILE >>$SETUP_LOG 2>&1
		res=$?
	fi
	if [ $res -ne 0 ]; then
		echo "*** ERROR: Unable to write $APACHE_CONFIG_DIRECTORY/$COM_SERVER_APACHE_CONF_FILE, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi
	echo

	echo "+----------------------------------------------------------------------+"
	echo "|       OK, Communication server setup successfully finished ;-)       |"
	echo "|                                                                      |"
	echo "| Please, review $APACHE_CONFIG_DIRECTORY/$COM_SERVER_APACHE_CONF_FILE |"
	echo "|         to ensure all is good. Then restart Apache daemon.           |"
	echo "+----------------------------------------------------------------------+"
	echo
	echo "Leaving Apache directory" >>$SETUP_LOG
	echo "Communication server installation successful" >>$SETUP_LOG
fi

echo
echo "Do you wish to setup Administration Server (Web Administration Console)"
echo -n "on this computer ([y]/n)?"
read ligne
if [ -z "$ligne" ] || [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
	# Install Administration server
	echo >>$SETUP_LOG
	echo "============================================================" >>$SETUP_LOG
	echo "Installing Administration server" >>$SETUP_LOG
	echo "============================================================" >>$SETUP_LOG

	echo
	echo "+----------------------------------------------------------+"
	echo "|    Checking for Administration Server directories...     |"
	echo "+----------------------------------------------------------+"
	echo
	echo "CAUTION: Setup now install files in accordance with Filesystem Hierarchy"
	echo "Standard. So, no file is installed under Apache root document directory"
	echo "(Refer to Apache configuration files to locate it)."
	echo "If you're upgrading from OCS Inventory NG Server 1.01 and previous, YOU"
	echo "MUST REMOVE (or move) directories 'ocsreports' and 'download' from Apache"
	echo "root document directory."
	echo "If you choose to move directory, YOU MUST MOVE 'download' directory to"
	echo "Administration Server writable/cache directory (by default"
	echo "$ADM_SERVER_VAR_DIR), especially if you use deployment feature."
	echo
	echo -n "Do you wish to continue ([y]/n)?"
	read ligne
	if [ -z "$ligne" ] || [ "$ligne" = "y" ] || [ "$ligne" = "Y" ]; then
		echo "Assuming directories 'ocsreports' and 'download' removed from"
		echo "Apache root document directory."
		echo
	else
		echo "Installation aborted !"
		echo
		exit 1
	fi

	echo "Checking for Administration Server directories..." >>$SETUP_LOG
	echo "Where to copy Administration Server static files for PHP Web Console"
	echo -n "[$ADM_SERVER_STATIC_DIR] ?"
	read ligne
	if test -z $ligne; then
		ADM_SERVER_STATIC_DIR=$ADM_SERVER_STATIC_DIR
	else
		ADM_SERVER_STATIC_DIR="$ligne"
	fi

	echo "OK, using directory $ADM_SERVER_STATIC_DIR to install static files ;-)"
	echo "Using directory $ADM_SERVER_STATIC_DIR for static files" >>$SETUP_LOG
	echo
	echo "Where to create writable/cache directories for deployment packages,"
	echo -n "administration console logs, IPDiscover and SNMP [$ADM_SERVER_VAR_DIR] ?"
	read ligne
	if test -z $ligne; then
		ADM_SERVER_VAR_DIR=$ADM_SERVER_VAR_DIR
	else
		ADM_SERVER_VAR_DIR="$ligne"
	fi

	echo "OK, writable/cache directory is $ADM_SERVER_VAR_DIR ;-)"
	echo "Using $ADM_SERVER_VAR_DIR as writable/cache directory" >>$SETUP_LOG
	echo

	# Check for required Perl Modules (if missing, please install before)
	#	- DBI 1.40 or higher
	#	- DBD::mysql 2.9004 or higher
	#	- XML::Simple 2.12 or higher
	#	- Net::IP 1.21 or higher
	#
	echo
	echo "+----------------------------------------------------------+"
	echo "|         Checking for required Perl Modules...            |"
	echo "+----------------------------------------------------------+"
	echo
	REQUIRED_PERL_MODULE_MISSING=0
	echo "Checking for DBI PERL module..."
	echo "Checking for DBI PERL module" >>$SETUP_LOG
	$PERL_BIN -mDBI -e 'print "PERL module DBI is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module DBI is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
	else
		echo "Found that PERL module DBI is available."
	fi

	echo "Checking for DBD::mysql PERL module..."
	echo "Checking for DBD::mysql PERL module" >>$SETUP_LOG
	$PERL_BIN -mDBD::mysql -e 'print "PERL module DBD::mysql is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module DBD::mysql is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
	else
		echo "Found that PERL module DBD::mysql is available."
	fi

	echo "Checking for XML::Simple PERL module..."
	echo "Checking for XML::Simple PERL module" >>$SETUP_LOG
	$PERL_BIN -mXML::Simple -e 'print "PERL module XML::Simple is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module XML::Simple is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
	else
		echo "Found that PERL module XML::Simple is available."
	fi

	echo "Checking for Net::IP PERL module..."
	echo "Checking for Net::IP PERL module" >>$SETUP_LOG
	$PERL_BIN -mNet::IP -e 'print "PERL module Net::IP is available\n"' >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: PERL module Net::IP is not installed !"
		REQUIRED_PERL_MODULE_MISSING=1
	else
		echo "Found that PERL module Net::IP is available."
	fi

	if [ $REQUIRED_PERL_MODULE_MISSING -ne 0 ]; then
		echo "*** ERROR: There is one or more required PERL modules missing on your computer !"
		echo "Please, install missing PERL modules first."
		echo "Installation aborted !"
		echo "One or more required PERL modules missing !" >>$SETUP_LOG
		echo "Installation aborted" >>$SETUP_LOG
		exit 1
	fi

	echo
	echo "+----------------------------------------------------------+"
	echo "|      Installing files for Administration server...       |"
	echo "+----------------------------------------------------------+"
	echo
	echo "Creating PHP directory $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR."
	echo "Creating PHP directory $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR" >>$SETUP_LOG
	mkdir -p $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR >>$SETUP_LOG 2>&1
	if [ $? != 0 ]; then
		echo "*** ERROR: Unable to create $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Copying PHP files to $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR."
	echo "Copying PHP files to $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR" >>$SETUP_LOG
	cp -Rf ocsreports/* $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/ >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to copy files in $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Fixing permissions on directory $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR."
	echo "Fixing permissions on directory $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR" >>$SETUP_LOG
	# Set PHP pages directory owned by root, group Apache
	chown -R root:$APACHE_GROUP $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR >>$SETUP_LOG 2>&1
	# Set "download/" "upload/" "plugins/main_section" "plugins/computer_detail" "plugins/language" "config/" own to apache
	chown -R $APACHE_USER:$APACHE_GROUPE $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/config >>$SETUP_LOG 2>&1
	chown -R $APACHE_USER:$APACHE_GROUPE $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/plugins/computer_detail >>$SETUP_LOG 2>&1
	chown -R $APACHE_USER:$APACHE_GROUPE $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/plugins/main_sections >>$SETUP_LOG 2>&1
	chown -R $APACHE_USER:$APACHE_GROUPE $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/plugins/language >>$SETUP_LOG 2>&1
	chown -R $APACHE_USER:$APACHE_GROUPE $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/download >>$SETUP_LOG 2>&1
	chown -R $APACHE_USER:$APACHE_GROUPE $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/upload >>$SETUP_LOG 2>&1
	chown $APACHE_USER:$APACHE_GROUPE $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR >>$SETUP_LOG 2>&1

	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Set PHP pages writable by root only
	chmod -R go-w $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Set database configuration file dbconfig.inc.php writable by Apache
	echo "Creating database configuration file $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php."
	echo "Creating database configuration file $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php" >>$SETUP_LOG
	rm -f $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo "<?php" >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo -n '$_SESSION["SERVEUR_SQL"]="' >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo -n "$DB_SERVER_HOST" >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo '";' >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo -n '$_SESSION["COMPTE_BASE"]="' >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo -n "$DB_SERVER_USER" >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo '";' >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo -n '$_SESSION["PSWD_BASE"]="' >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo -n "$DB_SERVER_PWD" >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo '";' >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	echo "?>" >>$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	chown root:$APACHE_GROUP $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php
	chmod g+w $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/dbconfig.inc.php, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Creating IPDiscover directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR."
	echo "Creating IPDiscover directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR" >>$SETUP_LOG
	mkdir -p $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR >>$SETUP_LOG 2>&1
	if [ $? != 0 ]; then
		echo "*** ERROR: Unable to create $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR."
	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR" >>$SETUP_LOG
	# Set IPD area owned by root, group Apache
	chown -R root:$APACHE_GROUP $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Set IPD area writable by root only
	chmod -R go-w $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Set IPD area writable by Apache group
	chmod g+w $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	#Create packages directory
	echo "Creating packages directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR."
	echo "Creating packages directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR" >>$SETUP_LOG
	mkdir -p $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR >>$SETUP_LOG 2>&1
	if [ $? != 0 ]; then
		echo "*** ERROR: Unable to create $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR."
	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR" >>$SETUP_LOG
	# Set package area owned by root, group Apache
	chown -R root:$APACHE_GROUP $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Set package area writable by root and Apache group only
	chmod -R g+w,o-w $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Create snmp custom mibs directory
	echo "Creating snmp mibs directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR."
	echo "Creating snmp mibs directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR" >>$SETUP_LOG
	mkdir -p $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR >>$SETUP_LOG 2>&1
	if [ $? != 0 ]; then
		echo "*** ERROR: Unable to create $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR."
	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR" >>$SETUP_LOG
	# Set snmp area owned by root, group Apache
	chown -R root:$APACHE_GROUP $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Set snmp area writable by root and Apache group only
	chmod -R g+w,o-w $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Create logs directory
	echo "Creating Administration server log files directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR."
	echo "Creating Administration server log files directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR" >>$SETUP_LOG
	mkdir -p $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR >>$SETUP_LOG 2>&1
	if [ $? != 0 ]; then
		echo "*** ERROR: Unable to create $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR."
	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR" >>$SETUP_LOG
	# Set log files area owned by root, group Apache
	chown -R root:$APACHE_GROUP $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Set log files area writable by root and Apache group only
	chmod -R g+w,o-w $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_LOGS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Create tmp files directory
	echo "Creating Administration server temporary files directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR."
	echo "Creating Administration server temporary files directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR" >>$SETUP_LOG
	mkdir -p $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR >>$SETUP_LOG 2>&1
	if [ $? != 0 ]; then
		echo "*** ERROR: Unable to create $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR."
	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR" >>$SETUP_LOG
	# Set log files area owned by root, group Apache
	chown -R root:$APACHE_GROUP $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Set log files area writable by root and Apache group only
	chmod -R g+w,o-w $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_TMP_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Creating Administration server scripts log files directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR."
	echo "Creating Administration server scripts log files directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR" >>$SETUP_LOG
	mkdir -p $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR >>$SETUP_LOG 2>&1
	if [ $? != 0 ]; then
		echo "*** ERROR: Unable to create $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR."
	echo "Fixing permissions on directory $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR" >>$SETUP_LOG
	# Set scripts log files area owned by root, group Apache
	chown -R root:$APACHE_GROUP $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	# Set scripts log files area writable by root and Apache group only
	chmod -R g+w,o-w $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SCRIPTS_LOGS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Configuring IPDISCOVER-UTIL Perl script."
	echo "Configuring IPDISCOVER-UTIL Perl script (ed ipdiscover-util.pl)" >>$SETUP_LOG
	cp binutils/ipdiscover-util.pl ipdiscover-util.pl.local >>$SETUP_LOG 2>&1
	$PERL_BIN -pi -e "s#localhost#$DB_SERVER_HOST#g" ipdiscover-util.pl.local
	$PERL_BIN -pi -e "s#3306#$DB_SERVER_PORT#g" ipdiscover-util.pl.local
	# echo "******** Begin updated ipdiscover-util.pl.local script ***********" >> $SETUP_LOG
	# cat ipdiscover-util.pl.local >> $SETUP_LOG
	# echo "******** End updated ipdiscover-util.pl.local script ***********" >> $SETUP_LOG
	echo "Installing IPDISCOVER-UTIL Perl script."
	echo "Installing IPDISCOVER-UTIL Perl script" >>$SETUP_LOG
	cp ipdiscover-util.pl.local $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/ipdiscover-util.pl >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to copy files in $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Fixing permissions on IPDISCOVER-UTIL Perl script."
	echo "Fixing permissions on IPDISCOVER-UTIL Perl script" >>$SETUP_LOG
	chown root:$APACHE_GROUP $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/ipdiscover-util.pl >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi
	chmod gou+x $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR/ipdiscover-util.pl >>$SETUP_LOG 2>&1
	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to set permissions on $ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi

	echo "Configuring Apache web server (file $ADM_SERVER_APACHE_CONF_FILE)" >>$SETUP_LOG
	cp etc/ocsinventory/$ADM_SERVER_APACHE_CONF_FILE $ADM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#OCSREPORTS_ALIAS#$ADM_SERVER_REPORTS_ALIAS#g" $ADM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#PATH_TO_OCSREPORTS_DIR#$ADM_SERVER_STATIC_DIR/$ADM_SERVER_STATIC_REPORTS_DIR#g" $ADM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#IPD_ALIAS#$ADM_SERVER_IPD_ALIAS#g" $ADM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#PATH_TO_IPD_DIR#$ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_IPD_DIR#g" $ADM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#PACKAGES_ALIAS#$ADM_SERVER_PACKAGES_ALIAS#g" $ADM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#PATH_TO_PACKAGES_DIR#$ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_PACKAGES_DIR#g" $ADM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#SNMP_ALIAS#$ADM_SERVER_SNMP_ALIAS#g" $ADM_SERVER_APACHE_CONF_FILE.local
	$PERL_BIN -pi -e "s#PATH_TO_SNMP_DIR#$ADM_SERVER_VAR_DIR/$ADM_SERVER_VAR_SNMP_DIR#g" $ADM_SERVER_APACHE_CONF_FILE.local
	echo "******** Begin updated $ADM_SERVER_APACHE_CONF_FILE.local ***********" >>$SETUP_LOG
	cat $ADM_SERVER_APACHE_CONF_FILE.local >>$SETUP_LOG
	echo "******** End updated $ADM_SERVER_APACHE_CONF_FILE.local ***********" >>$SETUP_LOG
	echo "Writing Administration server configuration to file $APACHE_CONFIG_DIRECTORY/$ADM_SERVER_APACHE_CONF_FILE"
	echo "Writing communication server configuration to file $APACHE_CONFIG_DIRECTORY/$ADM_SERVER_APACHE_CONF_FILE" >>$SETUP_LOG
	cp -f $ADM_SERVER_APACHE_CONF_FILE.local $APACHE_CONFIG_DIRECTORY/$ADM_SERVER_APACHE_CONF_FILE >>$SETUP_LOG 2>&1

	if [ $? -ne 0 ]; then
		echo "*** ERROR: Unable to write $APACHE_CONFIG_DIRECTORY/$ADM_SERVER_APACHE_CONF_FILE, please look at error in $SETUP_LOG and fix !"
		echo
		echo "Installation aborted !"
		exit 1
	fi
	echo

	echo "+----------------------------------------------------------------------+"
	echo "|        OK, Administration server installation finished ;-)           |"
	echo "|                                                                      |"
	echo "| Please, review $APACHE_CONFIG_DIRECTORY/$ADM_SERVER_APACHE_CONF_FILE"
	echo "|          to ensure all is good and restart Apache daemon.            |"
	echo "|                                                                      |"
	echo "| Then, point your browser to http://server/$ADM_SERVER_REPORTS_ALIAS"
	echo "|        to configure database server and create/update schema.        |"
	echo "+----------------------------------------------------------------------+"
	echo
	echo "Administration server installation successful" >>$SETUP_LOG
fi

echo
echo "Setup has created a log file $SETUP_LOG. Please, save this file."
echo "If you encounter error while running OCS Inventory NG Management server,"
echo "we can ask you to show us its content !"
echo
echo "DON'T FORGET TO RESTART APACHE DAEMON !"
echo
echo "Enjoy OCS Inventory NG ;-)"
echo
exit 0
