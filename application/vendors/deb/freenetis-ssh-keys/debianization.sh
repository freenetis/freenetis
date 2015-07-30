#!/bin/sh
################################################################################
# Script for debianization of FreenetIS rSSH keys
# (c) Ondrej Fibich, 2012
#
# Takes two arguments (version of package - FreenetIS).
#
################################################################################

if [ $# -ne 1 ]; then
    echo "Wrong arg count.. Terminating"
    exit 1
fi

NAME=freenetis-ssh-keys
VERSION=$1

# create dirs ##################################################################
mkdir deb_packages/tmp
cd deb_packages/tmp

mkdir DEBIAN
mkdir etc
mkdir etc/freenetis
mkdir etc/cron.d
mkdir usr
mkdir usr/sbin

# copy content of package ######################################################
cp ../../../ssh-keys/freenetis-ssh-keys-sync.sh usr/sbin/freenetis-ssh-keys-sync
cp ../../../ssh-keys/freenetis-ssh-keys.conf etc/freenetis/
cp ../../freenetis-ssh-keys/freenetis-ssh-keys.cron etc/cron.d/freenetis-ssh-keys

# count size
SIZE=`du -s etc usr | cut -f1 | paste -sd+ | bc`

# calculate checksum ###########################################################

find * -type f ! -regex '^DEBIAN/.*' -exec md5sum {} \; >> DEBIAN/md5sums

# create info files ############################################################

# create package info

echo "Package: ${NAME}" >> DEBIAN/control
echo "Version: ${VERSION}" >> DEBIAN/control
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

cat ../../${NAME}/postinst >> DEBIAN/postinst
cat ../../${NAME}/postrm >> DEBIAN/postrm
cat ../../${NAME}/templates >> DEBIAN/templates
cat ../../${NAME}/config >> DEBIAN/config

chmod +x DEBIAN/postinst DEBIAN/postrm DEBIAN/config

# create deb ###################################################################

# change owner of files to root (security)
cd ..
sudo chown -hR root:root *
sudo chmod g-w tmp/etc/cron.d/freenetis-ssh-keys

# make package
sudo dpkg-deb -b tmp ${NAME}_${VERSION}_all.deb

# clean-up mess ################################################################

# clean
sudo rm -rf tmp
