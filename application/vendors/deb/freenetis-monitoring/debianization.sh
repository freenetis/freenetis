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

NAME=freenetis-monitoring
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
cp ../../../monitoring/freenetis-monitoring.init.sh etc/init.d/${NAME}
cp ../../../monitoring/freenetis-monitord.sh usr/sbin/freenetis-monitord
cp ../../../monitoring/freenetis-monitoring.conf etc/freenetis/

# count size
SIZE=`du -s etc usr | cut -f1 | paste -sd+ | bc`

# calculate checksum ###########################################################

find * -type f ! -regex '^DEBIAN/.*' -exec md5sum {} \; >> DEBIAN/md5sums

# create info files ############################################################

# create package info

echo "Package: ${NAME}" >> DEBIAN/control
echo "Version: ${VERSION}-${DEBIAN}" >> DEBIAN/control
echo "Installed-Size: ${SIZE}" >> DEBIAN/control
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

cp -a -f ../../${NAME}/postinst DEBIAN/postinst
cp -a -f ../../${NAME}/prerm DEBIAN/prerm
cp -a -f ../../${NAME}/postrm DEBIAN/postrm
cp -a -f ../../${NAME}/templates DEBIAN/templates
cp -a -f ../../${NAME}/config DEBIAN/config
cp -a -f ../../${NAME}/conffiles DEBIAN/conffiles

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
