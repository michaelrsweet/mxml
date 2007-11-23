#!/bin/sh
(cd ..; make mxmldoc-static)

if test $# -gt 0; then
	files=$*
else
	files=*.cxx
fi

rm -f test.xml
#../mxmldoc-static test.xml $files >test.html 2>test.log
valgrind --log-fd=3 --leak-check=yes ../mxmldoc-static test.xml \
	$files >test.html 2>test.log 3>test.valgrind

