#!/bin/sh

set -e

MODULE=freenetis
VERSION=`cat version.php | grep "FREENETIS_VERSION" | cut -d"'" -f 4`
DEB_PREFIX=deb_packages/freenetis_${VERSION}
DEBIANS="squeeze wheezy jessie stretch"

cd application/vendors/deb/freenetis
mkdir ../deb_packages 2>/dev/null || true # fix in source

echo "$DEBIANS" | tr " " "\n" | while read OS
do
    echo "Debianization for $VERSION $OS"
    ./debianization.sh "$VERSION" "$OS"
    echo "Checking package for $VERSION $OS"
    lintian ../${DEB_PREFIX}+${OS}.deb
done
