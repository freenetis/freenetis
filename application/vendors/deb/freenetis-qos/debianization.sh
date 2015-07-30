#!/bin/sh
################################################################################
# Script for debianization of FreenetIS redirection and QoS package
# (c) Ondrej Fibich, 2012
#
# Takes two arguments (version of package - FreenetIS and debian version).
#
################################################################################

if [ $# -ne 2 ]; then
    echo "Wrong arg count.. Terminating"
    exit 1
fi

NAME=freenetis-qos
VERSION=$1
DEBIAN=$2

# create dirs ##################################################################
mkdir deb_packages/tmp
cd deb_packages/tmp

mkdir DEBIAN
mkdir etc
mkdir etc/init.d
mkdir etc/freenetis
mkdir usr
mkdir usr/sbin

# copy content of package ######################################################
cp ../../../qos/freenetis-qos.init.sh etc/init.d/${NAME}
cp ../../../qos/freenetis-qos-sync.sh usr/sbin/freenetis-qos-sync
cp ../../../qos/freenetis-qos.conf etc/freenetis/

# count size
SIZE=`du -s etc usr | cut -f1 | paste -sd+ | bc`

# calculate checksum ###########################################################

find * -type f ! -regex '^DEBIAN/.*' -exec md5sum {} \; >> DEBIAN/md5sums

# create info files ############################################################

# create package info

echo "Package: ${NAME}" >> DEBIAN/control
echo "Version: ${VERSION}-${DEBIAN}" >> DEBIAN/control
echo "Installed-Size: ${SIZE}" >> DEBIAN/control

if [ "$DEBIAN" = lenny ] || [ "$DEBIAN" = squeeze ]; then
	echo "Depends: coreutils, ipset, wget, procps, iptables, ipset-source, module-assistant, lsb-release" >> DEBIAN/control
else
	echo "Depends: coreutils, ipset, wget, procps, iptables, lsb-release" >> DEBIAN/control
fi

cat ../../${NAME}/control >> DEBIAN/control

# change log

cat ../../${NAME}/changelog >> DEBIAN/changelog

# copywriting

echo "This package was debianized by Ondrej Fibich <ondrej.fibich@gmail.com> on" >> DEBIAN/copyright
date -R >> DEBIAN/copyright
echo "" >> DEBIAN/copyright
echo "It was downloaded from <http://freenetis.org/>" >> DEBIAN/copyright
echo "" >> DEBIAN/copyright
echo "Upstream Author:" >> DEBIAN/copyright
cat ../../../../../AUTHORS >> DEBIAN/copyright
echo "" >> DEBIAN/copyright
echo "License:" >> DEBIAN/copyright
cat ../../../../../COPYING >> DEBIAN/copyright

# scripts ######################################################################

cat ../../${NAME}/postinst >> DEBIAN/postinst
cat ../../${NAME}/prerm >> DEBIAN/prerm
cat ../../${NAME}/postrm >> DEBIAN/postrm
cat ../../${NAME}/templates >> DEBIAN/templates
cat ../../${NAME}/config >> DEBIAN/config

chmod +x DEBIAN/postinst DEBIAN/postrm DEBIAN/prerm DEBIAN/config

# create deb ###################################################################

# change owner of files to root (security)
cd ..
sudo chown -hR root:root *

# make package
sudo dpkg-deb -b tmp ${NAME}_${VERSION}+${DEBIAN}.deb

# clean-up mess ################################################################

# clean
sudo rm -rf tmp
