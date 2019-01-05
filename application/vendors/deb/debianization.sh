#!/bin/bash
################################################################################
# Script for debianization of FreenetIS
# (c) Ondrej Fibich, 2012
#
# Takes one or two arguments (version of package and package - if empty do it all)
# and it generates all FreenetIS packages to directory deb_packages.
#
################################################################################

if [ $# -lt 1 ]; then
    echo "Wrong arg count.. Terminating"
    exit 1
fi

CONFIG="./debianization.conf"

if [ -r $CONFIG ]; then
	echo "Loading configuration file"
	. $CONFIG
fi

NAMES=(freenetis freenetis-monitoring freenetis-redirection freenetis-dhcp \
	   freenetis-ssh-keys freenetis-qos)
DEBIANS=(squeeze wheezy jessie stretch)
VERSION=$1

if [ $# -eq 2 ] || [ $# -eq 3 ]; then
	NAMES=($2)
fi

if [ $# -eq 3 ]; then
	DEBIANS=($3)
fi

# functions ####################################################################

function red_echo() {
	echo -e "\e[01;31m$1\e[0m"
}

function green_echo() {
	echo -e "\e[01;32m$1\e[0m"
}

# create dirs ##################################################################
rm -rf deb_packages
mkdir deb_packages

# call all debianization utils #################################################

root_dir=`pwd`

for name in ${NAMES[*]}
do
	name_mod=`echo $name | sed 's/-/_/g'`
	for debian in ${DEBIANS[*]}
	do
		# get dir from config or default
		conf_var_name="${name_mod}_debianization"
		eval_str="echo \${${conf_var_name}}"
		deb_dir_sh=`eval "$eval_str"`
		if [[ -z "$deb_dir_sh" ]]; then
			deb_dir_sh="./$name/"
		fi

		deb_sh="$deb_dir_sh/debianization.sh"

		# run debianization
		if [ -f "$deb_sh" ]; then
			cd "$deb_dir_sh"
			./debianization.sh "$VERSION" "$debian"

			if [ $? -eq 0 ]; then
				green_echo ">>>> [$name+$debian] debianized"
				# move builded packages
				if [ -d "deb_packages" ]; then
					mkdir -p "$root_dir/deb_packages"
					mv -f deb_packages/* "$root_dir/deb_packages"
				fi
			else
				red_echo ">>>> [$name+$debian] an error occured during debianization"
			fi

			cd "$root_dir"
		else
			red_echo ">>>> [$name+$debian] not debianized (debianization utility is missing)"
		fi
	done
done

