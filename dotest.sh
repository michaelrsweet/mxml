#!/bin/sh

rm -f testfile.xml
valgrind --logfile-fd=3 --leak-check=yes ./mxmldoc testfile.xml testfile.cxx >testfile.html 2>testfile.log 3>testfile.valgrind

