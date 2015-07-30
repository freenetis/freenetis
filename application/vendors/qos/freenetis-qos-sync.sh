#!/bin/bash
################################################################################
#                                                                              #
# This script serves for QoS synchronization of IS FreenetIS                   #
#                                                                              #
# Author  Michal Kliment 2012                                                  #
# Email   kliment@freenetis.org                                                #
#                                                                              #
# name    freenetis-qos-sync.sh                                                #
# version 0.9.0                                                                #
#                                                                              #
################################################################################

#Load variables from config file
CONFIG=/etc/freenetis/freenetis-qos.conf

PATH_QOS_MEMBERS=/tmp/qos_members
PATH_QOS_IP_ADDRESSES=/tmp/qos_ip_addresses
PATH_QOS_IPSETS=/tmp/qos_ipsets

IPTABLES=/sbin/iptables

LOG_PREFIX=`date "+%Y-%m-%d %H:%M"`" QoS: "

ROOT="1:"

#Load variables
if [ -f ${CONFIG} ]; then
  . $CONFIG;
else
	echo "Config file is missing at path $CONFIG."
	echo "Terminating..."
	exit 0
fi

stop ()
{
	# for each current ipsets, list is stored in file
	cat $PATH_QOS_IPSETS | while read line
	do
		ID=`echo $line | awk '{print $1}'`
		IPSET=`echo $line | awk '{print $2}'`

		# flush ipset
		ipset -F $IPSET
		echo $LOG_PREFIX"Emptied ipset $IPSET";

		# remove its iptables rules
		#$IPTABLES -t mangle -D POSTROUTING -o $OUTPUT_INTERFACE -m set --set $IPSET src -j CLASSIFY --set-class $ROOT$ID
		$IPTABLES -t mangle -D POSTROUTING -m set --set $IPSET src -j CLASSIFY --set-class $ROOT$ID
		$IPTABLES -t mangle -D POSTROUTING -m set --set $IPSET src -j RETURN
		echo $LOG_PREFIX"Deleted iptables rule for assignment upload tc class $ROOT$ID to ipset $IPSET"

		#$IPTABLES -t mangle -D POSTROUTING -o $INPUT_INTERFACE -m set --set $IPSET dst -j CLASSIFY --set-class $ROOT$ID
		$IPTABLES -t mangle -D POSTROUTING -m set --set $IPSET dst -j CLASSIFY --set-class $ROOT$ID
		$IPTABLES -t mangle -D POSTROUTING -m set --set $IPSET dst -j RETURN
		echo $LOG_PREFIX"Deleted iptables rule for assignment download tc class $ROOT$ID to ipset $IPSET"

		# remove ipset
		ipset -X $IPSET
		echo $LOG_PREFIX"Removed ipset $IPSET"
	done

	# clear file with ipset list
	cat /dev/null > $PATH_QOS_IPSETS

	echo $LOG_PREFIX"Deleting old tc classes"

	# deletes all old qdiscs, its remove all children classes, qdisc, etc.
	tc qdisc del dev $OUTPUT_INTERFACE root 2> /dev/null
	tc qdisc del dev $INPUT_INTERFACE root 2> /dev/null
}

start ()
{
	echo $LOG_PREFIX"Downloading data"

    wget -q -O $PATH_QOS_MEMBERS $SET_URL_QOS_MEMBERS --no-check-certificate
    wget -q -O $PATH_QOS_IP_ADDRESSES $SET_URL_QOS_IP_ADDRESSES --no-check-certificate

	# creates default qdiscs (first for upload, second for download)
	tc qdisc add dev $OUTPUT_INTERFACE root handle $ROOT htb default 2
	echo $LOG_PREFIX"Added root tc qdisc for upload"

	tc qdisc add dev $INPUT_INTERFACE root handle $ROOT htb default 2
	echo $LOG_PREFIX"Added root tc qdisc for download"

	# line number counter
	LNR=1

	cat $PATH_QOS_MEMBERS | while read line
	do
		ID=`echo $line | awk '{print $1}'`

		UPLOAD_CEIL=`echo $line | awk '{print $2}'`
		DOWNLOAD_CEIL=`echo $line | awk '{print $3}'`

		UPLOAD_RATE=`echo $line | awk '{print $4}'`
        DOWNLOAD_RATE=`echo $line | awk '{print $5}'`

		PRIORITY=`echo $line | awk '{print $6}'`

		PROTOCOL=`echo $line | awk '{print $7}'`

		PARENT=`echo $line | awk '{print $8}'`

		IPSET=`echo $line | awk '{print $9}'`

		if [ "$UPLOAD_CEIL" != "0M" ]; then
			UPLOAD_CEIL=" ceil "$UPLOAD_CEIL"bit"
		else
			UPLOAD_CEIL=""
		fi

		if [ "$UPLOAD_RATE" != "0M" ]; then
			UPLOAD_RATE=" rate "$UPLOAD_RATE"bit"
        else
			UPLOAD_RATE=""
        fi

		if [ "$DOWNLOAD_CEIL" != "0M" ]; then
			DOWNLOAD_CEIL=" ceil "$DOWNLOAD_CEIL"bit"
		else
			DOWNLOAD_CEIL=""
		fi

		if [ "$DOWNLOAD_RATE" != "0M" ]; then
			DOWNLOAD_RATE=" rate "$DOWNLOAD_RATE"bit"
		else
			DOWNLOAD_RATE=""
		fi

		# creates classes (first for upload, second for download)
		tc class add dev $OUTPUT_INTERFACE parent $ROOT$PARENT classid $ROOT$ID htb $UPLOAD_RATE $UPLOAD_CEIL
		echo $LOG_PREFIX"Created tc class $ROOT$ID for upload"

		tc class add dev $INPUT_INTERFACE parent $ROOT$PARENT classid $ROOT$ID htb $DOWNLOAD_RATE $DOWNLOAD_CEIL
		echo $LOG_PREFIX"Created tc class $ROOT$ID for download"

		if [ "$LNR" -gt 1 ]; then

			tc qdisc add dev $OUTPUT_INTERFACE parent $ROOT$ID handle $ID: sfq
			echo $LOG_PREFIX"Created tc qdisc for upload tc class $ROOT$ID"

			tc qdisc add dev $INPUT_INTERFACE parent $ROOT$ID handle $ID: sfq
			echo $LOG_PREFIX"Created tc qdisc for download tc class $ROOT$ID"

			tc filter add dev $OUTPUT_INTERFACE parent $ID: prio $PRIORITY handle $ID protocol $PROTOCOL flow hash keys nfct-src divisor 1024
			echo $LOG_PREFIX"Created filter for upload tc class $ROOT$ID with priority $PRIORITY and protocol $PROTOCOL"

			tc filter add dev $INPUT_INTERFACE parent $ID: prio $PRIORITY handle $ID protocol $PROTOCOL flow hash keys dst divisor 1024
			echo $LOG_PREFIX"Created filter for download tc class $ROOT$ID with priority $PRIORITY and protocol $PROTOCOL"

		fi

		if [ "$IPSET" != "" ]; then

			ipset -N $IPSET iphash --hashsize 10000 --probes 8 --resize 50
			echo $LOG_PREFIX"Created ipset $IPSET for tc class $ROOT$ID"

			#$IPTABLES -t mangle -A POSTROUTING -o $OUTPUT_INTERFACE -m set --set $IPSET src -j CLASSIFY --set-class $ROOT$ID
			$IPTABLES -t mangle -A POSTROUTING -m set --set $IPSET src -j CLASSIFY --set-class $ROOT$ID
			$IPTABLES -t mangle -A POSTROUTING -m set --set $IPSET src -j RETURN
			echo $LOG_PREFIX"Added iptables rule for assignment upload tc class $ROOT$ID to ipset $IPSET"

			#$IPTABLES -t mangle -A POSTROUTING -o $INPUT_INTERFACE -m set --set $IPSET dst -j CLASSIFY --set-class $ROOT$ID
			$IPTABLES -t mangle -A POSTROUTING -m set --set $IPSET dst -j CLASSIFY --set-class $ROOT$ID
			$IPTABLES -t mangle -A POSTROUTING -m set --set $IPSET dst -j RETURN
			echo $LOG_PREFIX"Added iptables rule for assignment download tc class $ROOT$ID to ipset $IPSET"

			awk '{ if ($1=='$ID') print $2 }' $PATH_QOS_IP_ADDRESSES | while read IP_ADDRESS
			do
				ipset -A $IPSET $IP_ADDRESS
				echo $LOG_PREFIX"Added ip address $IP_ADDRESS to ipset $IPSET"
			done

			echo "$ID	$IPSET" >> $PATH_QOS_IPSETS
		fi

		LNR=$(($LNR+1))
	done

	echo $LOG_PREFIX"Sleeping"
	sleep $DELAY
}

update()
{
	stop
	start
}

case "$1" in
	update)
		while (true);
		do
			update
		done
	;;
	stop)
		stop
	;;
esac