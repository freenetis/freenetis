#!/bin/sh
################################################################################
# Script for debianization of FreenetIS base package
# (c) Ondrej Fibich, 2012
#
# Takes two arguments (version of package - FreenetIS and debian version).
#
################################################################################

if [ $# -ne 2 ]; then
    echo "Wrong arg count.. Terminating"
    exit 1
fi

NAME=freenetis
VERSION=$1
DEBIAN=$2

# create dirs ##################################################################
mkdir deb_packages/tmp
cd deb_packages/tmp

mkdir DEBIAN
mkdir var
mkdir var/www
mkdir var/www/${NAME}
mkdir etc
mkdir etc/freenetis
mkdir etc/freenetis/https
mkdir etc/cron.d

# copy content of package ######################################################

cd ..
tar -zcvf /tmp/${NAME}_packaging.tar.gz ../../../../ 1>/dev/null

if [ $? -ne 0 ]; then
	echo "error during packaging"
	exit 2
fi

cd tmp/var/www/${NAME}

tar -xvf /tmp/${NAME}_packaging.tar.gz 1>/dev/null

if [ $? -ne 0 ]; then
	echo "error during unpackaging"
	exit 3
fi

rm /tmp/${NAME}_packaging.tar.gz

cd ../../../

# remove dev parts of FN
rm -rf var/www/${NAME}/application/vendors/deb
rm -rf var/www/${NAME}/application/vendors/unit_tester
rm -rf var/www/${NAME}/application/vendors/redirection
rm -rf var/www/${NAME}/application/vendors/qos
rm -rf var/www/${NAME}/application/controllers/unit_tester.php
rm -rf var/www/${NAME}/application/views/unit_tester
# remove hidden
rm -rf var/www/${NAME}/.htaccess
rm -rf var/www/${NAME}/config.php
rm -rf var/www/${NAME}/upload/*
rm -rf var/www/${NAME}/logs
rm -rf var/www/${NAME}/doc
# remove .svn
rm -rf `find var/www/${NAME} -type d -name .svn`

# copy config file
cp ../../freenetis/freenetis.conf etc/freenetis/

# copy cron file
cp ../../freenetis/freenetis.cron etc/cron.d/freenetis

# count size
SIZE=`du -s var | cut -f1`

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
cat var/www/${NAME}/AUTHORS >> DEBIAN/copyright
echo "" >> DEBIAN/copyright
echo "License:" >> DEBIAN/copyright
cat var/www/${NAME}/COPYING >> DEBIAN/copyright

# scripts ######################################################################

cp -a -f ../../${NAME}/postinst DEBIAN/postinst
cp -a -f ../../${NAME}/postrm DEBIAN/postrm
cp -a -f ../../${NAME}/templates DEBIAN/templates
cp -a -f ../../${NAME}/config DEBIAN/config
cp -a -f ../../${NAME}/conffiles DEBIAN/conffiles

chmod +x DEBIAN/postinst DEBIAN/postrm DEBIAN/config

# create deb ###################################################################

# change owner of files to root (security)
cd ..
sudo chown -hR root:root *
cd tmp
sudo chmod ugo+w var/www/${NAME}
sudo chmod ugo+w var/www/${NAME}/upload
sudo mkdir -m 0777 var/www/${NAME}/logs
sudo chmod g-w etc/cron.d/freenetis

# make package
cd ..
sudo dpkg-deb -b tmp ${NAME}_${VERSION}+${DEBIAN}.deb

# clean-up mess ################################################################

# clean
sudo rm -rf tmp
