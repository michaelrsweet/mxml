#
# "$Id: mxml.spec,v 1.7 2003/07/27 23:14:22 mike Exp $"
#
# RPM "spec" file for mini-XML, a small XML-like file parsing library.
#
# Copyright 2003 by Michael Sweet.
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

Summary: Miniature XML development library
Name: mxml
Version: 1.1.3
Release: 1
Copyright: GPL
Group: Development/Libraries
Source: http://www.easysw.com/~mike/mxml/mxml-%{version}.tar.gz
Url: http://www.easysw.com/~mike/mxml/
Packager: Mike Sweet <mike@easysw.com>
Vendor: Mike Sweet

# Use buildroot so as not to disturb the version already installed
BuildRoot: /var/tmp/%{name}-root

%description
Mini-XML is a small XML parsing library that you can use to read
XML and XML-like data files in your application without
requiring large non-standard libraries.  Mini-XML provides the
following functionality:

    - Reading and writing of UTF-8 encoded XML files.
    - Reading and writing of UTF-8 encoded XML strings.
    - Data is stored in a linked-list tree structure, preserving
      the XML data hierarchy.
    - Supports arbitrary element names, attributes, and
      attribute values with no preset limits, just available
      memory.
    - Supports integer, real, opaque ("cdata"), and text data
      types in "leaf" nodes.
    - Functions for creating and managing trees of data.
    - "Find" and "walk" functions for easily locating and
      navigating trees of data.

Mini-XML doesn't do validation or other types of processing on
the data based upon schema files or other sources of definition
information, nor does it support character entities other than
those required by the XML specification.  Also, since Mini-XML
does not support the UTF-16 encoding, it is technically not a
conforming XML consumer/client.

%prep
%setup

%build
CFLAGS="$RPM_OPT_FLAGS" CXXFLAGS="$RPM_OPT_FLAGS" LDFLAGS="$RPM_OPT_FLAGS" ./configure --prefix=/usr

# If we got this far, all prerequisite libraries must be here.
make

%install
# Make sure the RPM_BUILD_ROOT directory exists.
rm -rf $RPM_BUILD_ROOT

make BUILDROOT=$RPM_BUILD_ROOT install

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)

%dir /usr/bin
/usr/bin/*
%dir /usr/include
/usr/include/*
%dir /usr/lib
/usr/lib/*
%dir /usr/share/doc/mxml
/usr/share/doc/mxml/*
%dir /usr/share/man/cat1
/usr/share/man/cat1/*
%dir /usr/share/man/cat3
/usr/share/man/cat3/*
%dir /usr/share/man/man1
/usr/share/man/man1/*
%dir /usr/share/man/man3
/usr/share/man/man3/*

#
# End of "$Id: mxml.spec,v 1.7 2003/07/27 23:14:22 mike Exp $".
#
