#!/bin/sh

if test $# -gt 0; then
	files=$*
else
	files=*.cxx
fi

rm -f test.xml
valgrind --logfile-fd=3 --leak-check=yes ../mxmldoc test.xml \
	$files >test.html 2>test.log 3>test.valgrind

