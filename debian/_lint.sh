#!/bin/bash
A=`grep Version izumi/DEBIAN/control`; B=${A/Version: /}
cd ..
C=../izumi_${B}_i386.changes
echo "Lintian: $C"
lintian -i $C
