#!/bin/bash
################################################################################
#                                                                              #
# This script serves for monitoring from IS FreeNetIS                          #
#                                                                              #
# author  Kliment Michal 2012                                                  #
# email   kliment@freenetis.org                                                #
#                                                                              #
# name    freenetis-monitord.sh                                                #
# version 0.9.3                                                                #
#                                                                              #
################################################################################

#Load variables from config file
CONFIG=/etc/freenetis/freenetis-monitoring.conf

# temporary file with list of IP addresses to monitor
HOSTS_INPUT=`mktemp`

# temporary file with result to send
HOSTS_OUTPUT=`mktemp`

FPING="/usr/bin/fping"

# -------------------------------------------------------------------------
# Copyright (c) 2005 nixCraft project <http://cyberciti.biz/fb/>
# This script is licensed under GNU GPL version 2.0 or above
# -------------------------------------------------------------------------
# This script is part of nixCraft shell script collection (NSSC)
# Visit http://bash.cyberciti.biz/ for more information.
# -------------------------------------------------------------------------
# Get OS name
function get_ip()
{
	OS=`uname`
	IO="" # store IP

	case $OS in
	   Linux) IP=`ifconfig  | grep 'inet addr:'| grep -v '127.0.0.1' | cut -d: -f2 | awk '{ print $1}'`;;
	   FreeBSD|OpenBSD) IP=`ifconfig  | grep -E 'inet.[0-9]' | grep -v '127.0.0.1' | awk '{ print $2}'` ;;
	   SunOS) IP=`ifconfig -a | grep inet | grep -v '127.0.0.1' | awk '{ print $2} '` ;;
	   *) IP="Unknown";;
	esac

	echo -n "$IP"
}


# Load variables
if [ -e $CONFIG ]; then 
	. $CONFIG
else
	echo "Config file is missing at path $CONFIG."
	echo "Terminating..."
	exit 0
fi

# try to create log file if not exists
if [ ! -e "$LOG_FILE" ] ; then
	set +e
    touch "$LOG_FILE"
	set -e
fi

# disable logging if log cannot be written
if [ ! -w "$LOG_FILE" ]; then
	echo "Cannot write to $LOG_FILE file => disabling logging"
	LOG_FILE=/dev/null
fi

# endless loop
while true;
do
	echo "Downloading list of IP addresses to monitor..."

	# get ip addresses from FreenetIS
	http_code=`wget --no-check-certificate --server-response -q "$HOSTS_INPUT_URL$1" -O "$HOSTS_INPUT" 2>&1 | awk '/^  HTTP/{print $2}'`

	## access forbidden
	if [ "$http_code" = "403" ]; then

		# write to log if not already written
		if [ -z "$ERROR_WRITEN" ]; then
			echo -n "`date -R`   " 1>&2
			echo -n "Wrong configuration of FreenetIS - access from monitoring server not allowed. " 1>&2
			echo -n "Set IP address of monnitoring server to \"" 1>&2
			get_ip 1>&2
			echo "\" in FreenetIS settings." 1>&2
			ERROR_WRITEN=1
		fi

		echo "Wrong configuration of FreenetIS - access from monitoring server not allowed."
		echo "Set IP address of monnitoring server to \"" $(get_ip) "\" in FreenetIS settings."
		echo ""

		continue
	fi

	# use fping to get ip addresses states
	$FPING -e -f "$HOSTS_INPUT" 2>>$LOG_FILE | while read host
	do
		# ip address
		ip=`echo $host | awk '{print $1}'`

		# state of host (alive or unreachable)
		state=`echo $host | awk '{print $3}'`

		# latency of host (only for alive state)
		lat=`echo $host | awk '{print $4}' | sed 's/(//'`

		# do not add ampersand to beginning of file with result
		if [ -s "$HOSTS_OUTPUT" ];
		then
				echo -n "&" >> "$HOSTS_OUTPUT";
		fi

		# add variables to file with result
		echo "ip[]=$ip&state[]=$state&lat[]=$lat" >> "$HOSTS_OUTPUT"
	done

	# remove temporary file with IP addresses to monitor
	rm -f "$HOSTS_INPUT"

	echo "Sending result data back to FreenetIS..."
	echo ""

	# send file with result back to FreenetIS
	wget -qO- --no-check-certificate --post-file="$HOSTS_OUTPUT" "$HOSTS_OUTPUT_URL"

	# remove temporary file with result
	rm -f "$HOSTS_OUTPUT"
done
