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
mkdir ../deb_packages/tmp
cd ../deb_packages/tmp

mkdir -m 755 DEBIAN
mkdir -m 755 usr
mkdir -m 755 usr/share
mkdir -m 755 usr/share/${NAME}
mkdir -m 755 usr/share/doc
mkdir -m 755 usr/share/doc/${NAME}
mkdir -m 755 etc
mkdir -m 755 etc/freenetis
mkdir -m 755 etc/freenetis/https

# copy content of package ######################################################

cd ..
tar -zcvf /tmp/${NAME}_packaging.tar.gz ../../../../ 1>/dev/null

if [ $? -ne 0 ]; then
	echo "error during packaging"
	exit 2
fi

cd tmp/usr/share/${NAME}

tar -xvf /tmp/${NAME}_packaging.tar.gz 1>/dev/null

if [ $? -ne 0 ]; then
	echo "error during unpackaging"
	exit 3
fi

rm /tmp/${NAME}_packaging.tar.gz

cd ../../../

# remove dev parts of FN
rm -rf usr/share/${NAME}/application/vendors/deb
rm -rf usr/share/${NAME}/application/vendors/unit_tester
rm -rf usr/share/${NAME}/application/vendors/redirection
rm -rf usr/share/${NAME}/application/vendors/monitoring
rm -rf usr/share/${NAME}/application/vendors/qos
rm -rf usr/share/${NAME}/application/vendors/ssh-keys
rm -rf usr/share/${NAME}/application/vendors/dhcp
rm -rf usr/share/${NAME}/application/vendors/axo_doc
rm -rf usr/share/${NAME}/application/controllers/unit_tester.php
rm -rf usr/share/${NAME}/application/views/unit_tester
rm -rf usr/share/${NAME}/application/vendors/phpwhois/testsuite.php
# remove hidden
rm -rf usr/share/${NAME}/.htaccess
rm -rf usr/share/${NAME}/config.php
rm -rf usr/share/${NAME}/upload
rm -rf usr/share/${NAME}/logs
rm -rf usr/share/${NAME}/doc
rm -rf usr/share/${NAME}/tests
# remove .svn
rm -rf `find usr/share/${NAME} -type d -name .svn`
# remove .git
rm -rf usr/share/${NAME}/.git
rm -rf usr/share/${NAME}/.gitignore

# change permissions
find usr/share/${NAME} -type d -exec chmod 0755 {} \;
find usr/share/${NAME} -type f -exec chmod 0644 {} \;
find usr/share/${NAME} -type f -name *.pl -exec chmod +x {} \;

# copy config file
cp ../../freenetis/freenetis.conf etc/freenetis/
chmod 0644 etc/freenetis/freenetis.conf

# doc ##########################################################################

# change log
cat ../../${NAME}/changelog >> usr/share/doc/${NAME}/changelog

# debian change log is same
cp usr/share/doc/${NAME}/changelog usr/share/doc/${NAME}/changelog.Debian

# copyright
echo "This package was debianized by Ondrej Fibich <ondrej.fibich@gmail.com> on `date -R`" >> usr/share/doc/${NAME}/copyright
echo "It was downloaded from <http://freenetis.org/>\n" >> usr/share/doc/${NAME}/copyright
echo "Copyright:" >> usr/share/doc/${NAME}/copyright
cat ../../../../../AUTHORS >> usr/share/doc/${NAME}/copyright
echo "\nLicense:" >> usr/share/doc/${NAME}/copyright
cat ../../../../../COPYING >> usr/share/doc/${NAME}/copyright
echo "\nOn Debian systems, the complete text of the GNU General" >> usr/share/doc/${NAME}/copyright
echo "Public License can be found in \`/usr/share/common-licenses/GPL-3'.\n" >> usr/share/doc/${NAME}/copyright
echo -n "The Debian packaging is (C) `date +%Y`, Ondrej Fibich <ondrej.fibich@gmail.com> and" >> usr/share/doc/${NAME}/copyright
echo " it is licensed under the GPL, see above.\n" >> usr/share/doc/${NAME}/copyright

# rights
chmod 644 usr/share/doc/${NAME}/changelog usr/share/doc/${NAME}/changelog.Debian \
		  usr/share/doc/${NAME}/copyright

# compress doc
gzip --best usr/share/doc/${NAME}/changelog
gzip --best usr/share/doc/${NAME}/changelog.Debian

# count size
SIZE=`du -s usr | cut -f1`

# calculate checksum ###########################################################

find * -type f ! -regex '^DEBIAN/.*' -exec md5sum {} \; >> DEBIAN/md5sums

# create info files ############################################################

# create package info

echo "Package: ${NAME}" >> DEBIAN/control
echo "Version: ${VERSION}-${DEBIAN}" >> DEBIAN/control
echo "Installed-Size: ${SIZE}" >> DEBIAN/control
if [ $DEBIAN = "stretch" ]; then
    cat ../../${NAME}/control.stretch >> DEBIAN/control
else
    cat ../../${NAME}/control >> DEBIAN/control
fi

# scripts ######################################################################

cp -a -f ../../${NAME}/preinst DEBIAN/preinst
if [ $DEBIAN = "jessie" ] || [ $DEBIAN = "stretch" ]; then
    cp -a -f ../../${NAME}/postinst.jessie DEBIAN/postinst
else
    cp -a -f ../../${NAME}/postinst DEBIAN/postinst
fi
cp -a -f ../../${NAME}/prerm DEBIAN/prerm
if [ $DEBIAN = "jessie" ] || [ $DEBIAN = "stretch" ]; then
    cp -a -f ../../${NAME}/postrm.jessie DEBIAN/postrm
else
    cp -a -f ../../${NAME}/postrm DEBIAN/postrm
fi
cp -a -f ../../${NAME}/templates DEBIAN/templates
cp -a -f ../../${NAME}/config DEBIAN/config
cp -a -f ../../${NAME}/conffiles DEBIAN/conffiles

chmod 755 DEBIAN/preinst DEBIAN/postinst DEBIAN/prerm DEBIAN/postrm DEBIAN/config
chmod 0644 DEBIAN/templates DEBIAN/conffiles DEBIAN/md5sums

# create deb ###################################################################

# change owner of files to root (security)
cd ..
fakeroot chown -hR root:root *
cd tmp

# make package
cd ..
fakeroot dpkg-deb -b tmp ${NAME}_${VERSION}+${DEBIAN}.deb

# clean-up mess ################################################################

# clean
rm -rf tmp
