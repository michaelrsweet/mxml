#!/bin/sh

rm -f testfile.xml
./mxmldoc testfile.xml testfile.cxx >testfile.html 2>testfile.log

