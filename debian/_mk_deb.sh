#!/bin/bash
cd ..
dpkg-buildpackage -rfakeroot -us -uc
