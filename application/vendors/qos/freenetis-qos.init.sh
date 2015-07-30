#! /bin/bash

### BEGIN INIT INFO
# Provides:          freenetis-qos
# Required-Start:    $remote_fs
# Required-Stop:     $remote_fs
# Should-Start:      $network $syslog
# Should-Stop:       $network $syslog
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Start and stop freenetis QoS daemon
# Description:       FreenetIS initialization QoS synchronization script.
### END INIT INFO

################################################################################
#                                                                              #
# This script serves for initialization of QoS of IS FreenetIS                 #
#                                                                              #
# Author  Michal Kliment 2012                                                  #
# Email   kliment@freenetis.org                                                #
#                                                                              #
# Name    freenetis-qos.init.sh                                                #
# Version 0.9.0                                                                #
#                                                                              #
################################################################################

#Local variable contains path to iptables - mandatory
IPTABLES=/sbin/iptables

#Load variables from config file
CONFIG=/etc/freenetis/freenetis-qos.conf

# Path to QoS synchronization file
QOS_SYNCFILE=/usr/sbin/freenetis-qos-sync

#Path to QoS pid file
QOS_PIDFILE=/var/run/freenetis-qos-sync.pid

#Load variables
if [ -f ${CONFIG} ]; then
	. $CONFIG;
else
	echo "Config file is missing at path $CONFIG."
	echo "Terminating..."
	exit 0
fi

start_qos ()
{
	cat /dev/null > "$LOG_FILE"

	if [ -f ${QOS_PIDFILE} ]; then
		echo "Already started"
		return 0
	fi

	echo -n "Starting FreenetIS QoS deamon: "
	nohup $QOS_SYNCFILE update >> "$LOG_FILE" 2>&1 &

	#Parse PID a save to file
	ps aux | grep $QOS_SYNCFILE | grep -v grep | awk '{print $2}' > $QOS_PIDFILE

	# test if daemon is started
	if [ `ps aux | grep $QOS_SYNCFILE | grep -v grep | wc -l` -gt 0 ];
	then
		echo "OK"
	else
		echo "FAILED!"
	fi

	return 0
}

stop_qos ()
{
	if [ ! -f ${QOS_PIDFILE} ]; then
		echo "Already stopped"
		return 0
	fi

	#Killing of process by sigterm
	echo -n "Stopping FreenetIS QoS deamon: "
	kill -9 `cat $QOS_PIDFILE`

	rm -f $QOS_PIDFILE

	$QOS_SYNCFILE stop >> "$LOG_FILE" 2>&1

	# test if daemon is stopped
	if [ `ps aux | grep $QOS_SYNCFILE | grep -v grep | wc -l` -eq 0 ];
	then
		echo "OK"
	else
		echo "FAILED!";
	fi

	return 0
}

status_qos ()
{
	if [ -f ${QOS_PIDFILE} ]; then
		echo "Freenetis QoS is running with PID `cat $QOS_PIDFILE`"
		return 0
	else
		echo "Freenetis QoS is not running"
		return 0
	fi
}

usage_qos ()
{
	echo "usage : `echo $0` (start|stop|restart|status|help)"
}

help_qos ()
{
	echo "  start - initialization of firewall rules and settings for QoS"
	echo "  stop - clears firewall rules and settings for QoS"
	echo "  restart - restarts firewall rules and settings for QoS"
	echo "  status - returns actual status of QoS"
	echo "  help - prints help for QoS"
}

# Is parameter #1 zero length?
if [ -z "$1" ]; then
	usage_qos
	exit 0
fi;

case "$1" in

	start)
		start_qos
		exit 0
	;;

	restart)
		stop_qos
		start_qos
		exit 0
	;;

	stop)
		stop_qos
		exit 0
	;;

	status)
		status_qos
		exit 0
	;;

	help)
		usage_qos
		help_qos
		exit 0
	;;

	*)
		usage_qos
		exit 0
	;;

esac

exit 0
