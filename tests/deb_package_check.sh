#!/bin/bash

set -e

MODULE=freenetis
VERSION=`cat version.php | grep "FREENETIS_VERSION" | cut -d"'" -f 4`
DEB_PREFIX=deb_packages/freenetis_${VERSION}
DEBIANS=(lenny squeeze wheezy jessie)

cd application/vendors/deb/freenetis
mkdir ../deb_packages || true # fix in source

for OS in ${DEBIANS[*]}
do
    echo "Debianization for $VERSION $OS"
    ./debianization.sh "$VERSION" "$OS"
    echo "Checking package for $VERSION $OS"
    lintian ../${DEB_PREFIX}+${OS}.deb
done
