#!/bin/sh
#
# Script to make documentation...
#
# Copyright 2003-2010 by Michael R Sweet.
#
# These coded instructions, statements, and computer programs are the
# property of Michael R Sweet and are protected by Federal copyright
# law.  Distribution and use rights are outlined in the file "COPYING"
# which should have been included with this file.  If this file is
# missing or damaged, see the license at:
#
#     https://michaelrsweet.github.io/mxml
#

htmldoc --verbose --path "hires;." --batch mxml.book -f mxml.pdf

htmldoc --verbose --batch mxml.book --no-title -f mxml.html

rm -rf mxml.d
mkdir mxml.d
htmldoc --verbose --batch mxml.book --no-title -t html -d mxml.d
