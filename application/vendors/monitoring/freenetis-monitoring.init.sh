#! /bin/bash

### BEGIN INIT INFO
# Provides:          freenetis-monitoring
# Required-Start:    $remote_fs
# Required-Stop:     $remote_fs
# Should-Start:      $network $syslog
# Should-Stop:       $network $syslog
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Start and stop freenetis monitoring daemon
# Description:       FreeNetIS monitoring script.
### END INIT INFO

################################################################################
#                                                                              #
# This script serves for monitoring from IS FreeNetIS                		   #
#                                                                              #
# Author  Kliment Michal 2012                                                  #
# Email   kliment@freenetis.org                                                #
#																		       #
# Name    freenetis-monitoring.init.sh		     							   #
# Version 0.9.3                                                                #
#                                                                              #
################################################################################

# Load variables from config file
CONFIG=/etc/freenetis/freenetis-monitoring.conf

# Name of monitoring daemon
MONITORD="freenetis-monitord"

# Path to monitoring daemon (without name)
MONITORD_PATH="/usr/sbin/"

# Load variables
if [ -e $CONFIG ]; then 
	. $CONFIG || true
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

start_monitor()
{
	# test if daemon is already started
	if [ `ps aux | grep $MONITORD | grep -v grep | wc -l` -gt 0 ];
	then
		echo "Already started."
		return 0
	fi

	echo -n "Starting FreenetIS monitor daemon: "

	# max priority is set
	if [ $MAX_PRIORITY -gt 0 ];
	then
		# create process for all priority from interval <0;MAX_PRIORITY>
		for i in `seq 0 $MAX_PRIORITY`;
		do
			nohup $MONITORD_PATH$MONITORD $i 1>/dev/null 2>>$LOG_FILE &
		done
	else
		# create one process for all priorities
		nohup $MONITORD_PATH$MONITORD 1>/dev/null 2>>$LOG_FILE &
	fi

	# test if daemon is started
	if [ `ps aux | grep $MONITORD | grep -v grep | wc -l` -gt 0 ];
	then
		echo "OK"
	else
		echo "FAILED!";
	fi
}

stop_monitor()
{
	# test if daemon is already stopped
	if [ `ps aux | grep $MONITORD | grep -v grep | wc -l` -eq 0 ];
	then
		echo "Not running."
		return 0
	fi

	echo -n "Stopping FreenetIS monitor daemon: "

	# kill all processes
	killall -q $MONITORD

	# test if daemon is stopped
	if [ `ps aux | grep $MONITORD | grep -v grep | wc -l` -eq 0 ];
	then
		echo "OK"
	else
		echo "FAILED!";
	fi
}

status_monitor()
{
	echo -n "FreenetIS monitor daemon is "

	# test if daemon is already started
	if [ `ps aux | grep $MONITORD | grep -v grep | wc -l` -gt 0 ];
	then
		echo "running."
	else
		echo "not running.";
	fi
}

usage_monitor()
{
	echo "usage: `echo $0` (start|stop|restart|status)"
}

case "$1" in
	start)
		start_monitor
		exit 0
	;;
	stop)
		stop_monitor
		exit 0
	;;
	restart)
		stop_monitor
		start_monitor
		exit 0
	;;
	status)
		status_monitor
		exit 0
	;;
	*)
		usage_monitor
		exit 0
	;;
esac

exit 0