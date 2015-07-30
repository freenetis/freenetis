#! /bin/bash

### BEGIN INIT INFO
# Provides:          freenetis-redirection
# Required-Start:    $remote_fs
# Required-Stop:     $remote_fs
# Should-Start:      $network $syslog
# Should-Stop:       $network $syslog
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: Start and stop freenetis synchronization daemon
# Description:       FreenetIS synchronization script.
### END INIT INFO

################################################################################
#                                                                              #
# This script serves for redirection ip policy and QoS of IS FreenetIS		   #
#                                                                              #
# Author  Sevcik Roman 2011                                                    #
# Email   sevcik.roman@slfree.net                                              #
#																		       #
# Name    freenetis-redirection.init.sh		     							   #
# Version 1.9.2                                                                #
#                                                                              #
################################################################################

#Local variable contains path to iptables - mandatory
IPTABLES=/sbin/iptables

#Load variables from config file
CONFIG=/etc/freenetis/freenetis-redirection.conf

# Path to redirection synchronization file
REDIRECTION_SYNCFILE=/usr/sbin/freenetis-redirection-sync

# Path to HTTP 302 redirector
REDIRECTION_HTTP_REDIRECTOR=/usr/sbin/freenetis-http-302-redirection.py

#Path to redirection pid file
REDIRECTION_PIDFILE=/var/run/freenetis-redirection.pid

# Path to HTTP 302 redirector
REDIRECTION_HTTP_REDIRECTOR_PIDFILE=/var/run/freenetis-http-302-redirection.pid

#Load variables
if [ -f ${CONFIG} ]; then
	. $CONFIG;
else
	echo "Config file is missing at path $CONFIG."
	echo "Terminating..."
	exit 0
fi

start_redirection ()
{
    if [ -f ${REDIRECTION_PIDFILE} ] && [ -f ${REDIRECTION_HTTP_REDIRECTOR_PIDFILE} ]; then
		echo "Already started"
		return 0
    fi

	if [ ! -f ${REDIRECTION_PIDFILE} ]; then
		cat /dev/null > "$LOG_FILE"
		echo -n "Starting FreenetIS redirection sync daemon: "

		ipset -N allowed iphash --hashsize 10000 --probes 8 --resize 50
		ipset -N self_cancel iphash --hashsize 10000 --probes 8 --resize 50
		ipset -N ranges nethash --hashsize 1024 --probes 4 --resize 50

		#Rule for allowing access. If come packet to $IP_TARGET then we add source address do set allowed and to set seen
		#Set seen is used for ip synchronization with FreenetIS.
		$IPTABLES -i $INPUT_INTERFACE -t nat -A PREROUTING -m set --set self_cancel src -d $IP_TARGET -p tcp --dport $PORT_SELF_CANCEL -j SET --add-set allowed src

		#If IP is allowed then it is not redirected
		$IPTABLES -i $INPUT_INTERFACE -t nat -A PREROUTING -m set --set allowed src -j ACCEPT

		#Redirect everything trafic what has destination port $PORT_WEB to $PORT_REDIRECT
		$IPTABLES -i $INPUT_INTERFACE -t nat -A PREROUTING -m set --set ranges src -p tcp --dport $PORT_WEB -j REDIRECT --to-port $PORT_REDIRECT

		#If IP is allowed then it is not redirected
		$IPTABLES -i $INPUT_INTERFACE -I FORWARD 1 -m set --set allowed src -j ACCEPT

		#Else everything drop
		$IPTABLES -i $INPUT_INTERFACE -I FORWARD 2 -m set --set ranges src -j DROP

		#Run update script on background
		nohup $REDIRECTION_SYNCFILE >> "$LOG_FILE" 2>&1 &

		#Parse PID a save to file
		ps aux | grep $REDIRECTION_SYNCFILE | grep -v grep | awk '{print $2}' > $REDIRECTION_PIDFILE

		# test if daemon is started
		if [ `ps aux | grep $REDIRECTION_SYNCFILE | grep -v grep | wc -l` -gt 0 ]; then
			echo "OK"
		else
			echo "FAILED!"
		fi
	else
		echo "Starting FreenetIS redirection sync daemon: Already started"
	fi

	if [ ! -f ${REDIRECTION_HTTP_REDIRECTOR_PIDFILE} ]; then
		cat /dev/null > "$LOG_FILE_REDIRECTOR"
		echo -n "Starting FreenetIS redirection HTTP deamon: "

		#Run update script on background
		nohup $REDIRECTION_HTTP_REDIRECTOR "$PORT_REDIRECT" "$PATH_FN" > "$LOG_FILE_REDIRECTOR" 2>&1 &

		#Parse PID a save to file
		ps aux | grep $REDIRECTION_HTTP_REDIRECTOR | grep -v grep | awk '{print $2}' > $REDIRECTION_HTTP_REDIRECTOR_PIDFILE

		# test if daemon is started
		if [ `ps aux | grep $REDIRECTION_HTTP_REDIRECTOR | grep -v grep | wc -l` -gt 0 ]; then
			echo "OK"
		else
			echo "FAILED!"
		fi
	else
		echo "Starting FreenetIS redirection HTTP deamon: Already started"
	fi

    return 0
}

stop_redirection ()
{
    if [ ! -f ${REDIRECTION_PIDFILE} ] && [ ! -f ${REDIRECTION_HTTP_REDIRECTOR_PIDFILE} ]; then
		echo "Already stopped."
		return 0
    fi

	if [ -f ${REDIRECTION_PIDFILE} ]; then
		#Killing of process by sigterm
		echo -n "Stopping FreenetIS redirection deamon: "
		kill -9 `cat $REDIRECTION_PIDFILE`

		rm -f $REDIRECTION_PIDFILE

		#Rule for allowing access. If come packet to $IP_TARGET then we add souce address do set allowed and to set seen
		#Set seen is used for ip synchronization with FreenetIS.
		$IPTABLES -i $INPUT_INTERFACE -t nat -D PREROUTING -m set --set self_cancel src -d $IP_TARGET -p tcp --dport $PORT_SELF_CANCEL -j SET --add-set allowed src

		#If IP is allowed then it is not redirected
		$IPTABLES -i $INPUT_INTERFACE -t nat -D PREROUTING -m set --set allowed src -j ACCEPT

		#Redirect everything traffic what has destination port $PORT_WEB to $PORT_REDIRECT
		$IPTABLES -i $INPUT_INTERFACE -t nat -D PREROUTING -m set --set ranges src -p tcp --dport $PORT_WEB -j REDIRECT --to-port $PORT_REDIRECT

		#If IP is allowed then it is not redirected
		$IPTABLES -i $INPUT_INTERFACE -D FORWARD -m set --set allowed src -j ACCEPT

		#Else everything drop
		$IPTABLES -i $INPUT_INTERFACE -D FORWARD -m set --set ranges src -j DROP

		ipset -X allowed
		ipset -X self_cancel
		ipset -X ranges

		# test if daemon is stopped
		if [ `ps aux | grep $REDIRECTION_SYNCFILE | grep -v grep | wc -l` -eq 0 ]; then
			echo "OK"
		else
			echo "FAILED!";
		fi
	else
		echo "Stopping FreenetIS redirection deamon: Already stopped"
	fi

	if [ -f ${REDIRECTION_HTTP_REDIRECTOR_PIDFILE} ]; then
		#Killing of process by sigterm
		echo -n "Stopping FreenetIS redirection HTTP deamon: "
		kill `cat $REDIRECTION_HTTP_REDIRECTOR_PIDFILE`
		rm -f $REDIRECTION_HTTP_REDIRECTOR_PIDFILE

		# test if daemon is stopped
		if [ `ps aux | grep $REDIRECTION_HTTP_REDIRECTOR | grep -v grep | wc -l` -eq 0 ]; then
			echo "OK"
		else
			echo "FAILED!";
		fi
	fi

    return 0
}

status_redirection ()
{
    if [ -f ${REDIRECTION_PIDFILE} ]; then
		echo "FreenetIS redirection is running with PID `cat $REDIRECTION_PIDFILE`"
    else
		echo "FreenetIS redirection is not running"
    fi

    if [ -f ${REDIRECTION_HTTP_REDIRECTOR_PIDFILE} ]; then
		echo "FreenetIS redirection HTTP is running with PID `cat $REDIRECTION_HTTP_REDIRECTOR_PIDFILE`"
    else
		echo "FreenetIS redirection HTTP is not running"
    fi

	return 0
}

usage_redirection ()
{
   echo "usage : `echo $0` (start|stop|restart|status|help)"
}

help_redirection ()
{
   echo "  start - initialization of firewall rules and settings for redirection"
   echo "  stop - clears firewall rules and settings for redirection"
   echo "  restart - restarts firewall rules and settings for redirection"
   echo "  status - returns actual status of redirection"
   echo "  help - prints help for redirection"
}

# Is parameter #1 zero length?
if [ -z "$1" ]; then
	usage_redirection
	exit 0
fi;

case "$1" in

   start)
		start_redirection
		exit 0
   ;;

   restart)
		stop_redirection
		start_redirection
		exit 0
   ;;

   stop)
		stop_redirection
		exit 0
   ;;

   status)
		status_redirection
		exit 0
   ;;

   help)
		usage_redirection
		help_redirection
		exit 0
   ;;

   *)
		usage_redirection
		exit 0
   ;;

esac

exit 0
