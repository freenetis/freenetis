#!/bin/bash
################################################################################
#                                                                              #
# This script serves for redirection IP policy of IS FreenetIS                 #
#                                                                              #
# author  Sevcik Roman 2011                                                    #
# email   sevcik.roman@slfree.net                                              #
#                                                                              #
# name    freenetis-redirection-sync.sh                                        #
# version 1.9.2                                                                #
#                                                                              #
################################################################################

#Load variables from config file
CONFIG=/etc/freenetis/freenetis-redirection.conf

#Paths where temporary data will be saved.
PATH_RANGES=/tmp/ranges
PATH_ALLOWED=/tmp/allowed
PATH_SELF_CANCEL=/tmp/self_cancel

LOG_PREFIX=`date "+%Y-%m-%d %H:%M"`" Redirection: "

#Load variables
if [ -f ${CONFIG} ]; then
	. $CONFIG;
else
	echo "Config file is missing at path $CONFIG."
	echo "Terminating..."
	exit 0
fi

# Function returns 1 if is IP valid
# @param IP address
# return 1 on true  or other number on false
valid_ip ()
{
    local  ip=$1
    local  stat=1

    if [[ $ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
		OIFS=$IFS
		IFS='.'
		ip=($ip)
		IFS=$OIFS
		[[ ${ip[0]} -le 255 && ${ip[1]} -le 255 && ${ip[2]} -le 255 && ${ip[3]} -le 255 ]]
		stat=$?
    fi;
	
    return $stat
}

update ()
{
    echo $LOG_PREFIX"Updating..."

    OIFS=$IFS
    export IFS=";"
    IFS=$OIFS

    echo $LOG_PREFIX"Downloading data...";
    wget -q -O $PATH_ALLOWED            $SET_URL_ALLOWED --no-check-certificate
    wget -q -O $PATH_SELF_CANCEL        $SET_URL_SELF_CANCEL --no-check-certificate
    wget -q -O $PATH_RANGES             $SET_URL_RANGES --no-check-certificate

    ipset -F ranges
    ipset -F allowed
    ipset -F self_cancel

    for i in $(cat $PATH_ALLOWED);
    do
        echo $LOG_PREFIX"$i - added to set allowed"
        ipset -A allowed $i
    done

    for i in $(cat $PATH_SELF_CANCEL);
    do
        echo $LOG_PREFIX"$i - added to set self_cancel"
        ipset -A self_cancel $i
    done

    for i in $(cat $PATH_RANGES);
    do
        echo $LOG_PREFIX"$i - added to set ranges"
        ipset -A ranges $i
    done

    #Cleaning up...
    rm -f $PATH_RANGES
    rm -f $PATH_ALLOWED
    rm -f $PATH_SELF_CANCEL

    echo $LOG_PREFIX"Sleeping..."
    sleep $DELAY;
	
	LOG_PREFIX=`date "+%Y-%m-%d %H:%M"`" Redirection: "
}

while (true);
do
    update
done
