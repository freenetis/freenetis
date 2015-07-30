#!/bin/bash
################################################################################
#                                                                              #
#  Author: Michal Kliment, Ondrej Fibich                                       #
#  Description: This script updates public SSH keys of admins of the device    #
#  given by his freenetIS ID.                                                  #
#                                                                              #
#  Version: 0.2.0                                                              #
#                                                                              # 
################################################################################

CONFIG=/etc/freenetis/freenetis-ssh-keys.conf

# Load variables
if [ -e $CONFIG ]; then 
	. $CONFIG || true
	TMPFILE="/tmp/"$AUTHORIZED_KEYS
else
	echo "`date -R`   Config file is missing at path $CONFIG. Terminating..."
	exit 0
fi

# check config
if [[ ! "$DEVICE_ID" =~ ^[0-9]+$ ]] || [ $DEVICE_ID -lt 1 ]; then
	echo "[ERROR] `date -R`   Wrong configuration (ID not set properly)"
	exit 1
fi

# SSH config folder
mkdir -p "$HOME/.ssh/"
chmod 0700 "$HOME/.ssh/"

# download
rm -f "$TMPFILE"
echo "[INFO] `date -R`   Downloading public SSH keys from (${PATH_FN})"
status=`wget --no-check-certificate --server-response -q "$FULL_PATH" -O "$TMPFILE" 2>&1 | awk '/^  HTTP/{print $2}'`

# check download
if [ "$status" = "200" ]; then
	# change keys
	if [ $(cat "$TMPFILE" 2> /dev/null | wc -l) -gt 2 ]; then
		echo "[INFO] `date -R`   Downloaded (code: $status)"
		echo "[INFO] `date -R`   Backuping old keys to $HOME/.ssh/$AUTHORIZED_KEYS.old"
		mv -f "$HOME/.ssh/$AUTHORIZED_KEYS" "$HOME/.ssh/$AUTHORIZED_KEYS.old"
		echo "[INFO] `date -R`   Loading bew keys to $HOME/.ssh/$AUTHORIZED_KEYS..."
		mv -f "$TMPFILE" "$HOME/.ssh/$AUTHORIZED_KEYS"
	else
		echo "[ERROR] `date -R`   Empty response body -> keeping old configuration"
	fi
else
	echo "[ERROR] `date -R`   Download failed (code: $status)"
fi
