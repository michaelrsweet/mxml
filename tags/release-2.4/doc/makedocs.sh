#!/bin/sh
#
# "$Id$"
#
# Script to make documentation...
#
# Copyright 2003-2007 by Michael Sweet.
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU Library General Public
# License as published by the Free Software Foundation; either
# version 2, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#

htmldoc --verbose --path "hires;." --batch mxml.book -f mxml.pdf

htmldoc --verbose --batch mxml.book --no-title -f mxml.html

rm -rf mxml.d
mkdir mxml.d
htmldoc --verbose --batch mxml.book --no-title -t html -d mxml.d

#
# End of "$Id$".
#
