#!/bin/bash

TMP=/tmp/izu_deb_package

rm -r $TMP
mkdir -p $TMP

pushd $TMP

# Package name
# For local cvs: Izumi
# for SourceForge cvs: izumi
PACKAGE=izumi

# checkout
cvs co $PACKAGE

# Get version number
A=`grep "([0-9.-]\+)" $PACKAGE/debian/changelog | head -n 1`
V1=`echo $A | sed 's/.*(\([0-9\.\]\+\).*/\1/'`
V2=`echo $A | sed 's/.*(\([0-9\.\-]\+\)).*/\1/'`

PACK="izumi-$V1"
DEB="izumi_$V2"

# rename folder
mv $PACKAGE $PACK

# build package
cd $PACK
dpkg-buildpackage -rfakeroot

# get deb and archives
popd

cd ../../distribs

mv -v "$TMP/$DEB"* .



