#!/bin/bash

TMP=/tmp/izu_deb_package

rm -r $TMP
mkdir -p $TMP

pushd $TMP

# checkout
cvs co Izumi

# Get version number
A=`grep "([0-9.-]\+)" Izumi/debian/changelog | head -n 1`
V1=`echo $A | sed 's/.*(\([0-9\.\]\+\).*/\1/'`
V2=`echo $A | sed 's/.*(\([0-9\.\-]\+\)).*/\1/'`

PACK="izumi-$V1"
DEB="izumi_$V2"

# rename folder
mv Izumi $PACK

# build package
cd $PACK
dpkg-buildpackage -rfakeroot

# get deb and archives
popd

cd ../../distribs

mv -v "$TMP/$DEB"* .



