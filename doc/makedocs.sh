#!/bin/sh
#
# "$Id: makedocs.sh,v 1.1 2004/05/03 03:21:16 mike Exp $"
#
# Script to make documentation...
#
# Copyright 2003-2004 by Michael Sweet.
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

htmldoc --verbose --batch mxml.book -f mxml.pdf
htmldoc --verbose --batch mxml.book -f mxml.html

rm -rf mxml.d
mkdir mxml.d
htmldoc --verbose --batch mxml.book -t htmlsep -d mxml.d

#
# End of "$Id: makedocs.sh,v 1.1 2004/05/03 03:21:16 mike Exp $".
#
