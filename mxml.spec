#
# RPM "spec" file for Mini-XML, a small XML file parsing library.
#
# Copyright 2003-2017 by Michael R Sweet.
#
# These coded instructions, statements, and computer programs are the
# property of Michael R Sweet and are protected by Federal copyright
# law.  Distribution and use rights are outlined in the file "COPYING"
# which should have been included with this file.  If this file is
# missing or damaged, see the license at:
#
#     https://michaelrsweet.github.io/mxml
#

Summary: Small XML file parsing library
Name: mxml
Version: 2.11
Release: 1
License: LGPL
Group: Development/Libraries
Source: https://github.com/michaelrsweet/mxml/releases/download/release-%{version}/mxml-%{version}.tar.gz
Url: https://michaelrsweet.github.io/mxml
Packager: John Doe <johndoe@example.com>
Vendor: Michael R Sweet

# Use buildroot so as not to disturb the version already installed
BuildRoot: /var/tmp/%{name}-root

%description
Mini-XML is a small XML parsing library that you can use to read XML data files
or strings in your application without requiring large non-standard libraries.
Mini-XML provides the following functionality:

- Reading of UTF-8 and UTF-16 and writing of UTF-8 encoded XML files and
  strings.
- Data is stored in a linked-list tree structure, preserving the XML data
  hierarchy.
- SAX (streamed) reading of XML files and strings to minimize memory usage.
- Supports arbitrary element names, attributes, and attribute values with no
  preset limits, just available memory.
- Supports integer, real, opaque ("cdata"), and text data types in "leaf" nodes.
- Functions for creating and managing trees of data.
- "Find" and "walk" functions for easily locating and navigating trees of data.

Mini-XML doesn't do validation or other types of processing on the data
based upon schema files or other sources of definition information.

%prep
%setup

%build
CFLAGS="$RPM_OPT_FLAGS" CXXFLAGS="$RPM_OPT_FLAGS" LDFLAGS="$RPM_OPT_FLAGS" ./configure --enable-shared --prefix=/usr

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
/usr/include/mxml.h
%dir /usr/lib
/usr/lib/*
%dir /usr/lib/pkgconfig
/usr/lib/pkgconfig/mxml.pc
%dir /usr/share/doc/mxml
/usr/share/doc/mxml/*
%dir /usr/share/man/man1
/usr/share/man/man1/*
%dir /usr/share/man/man3
/usr/share/man/man3/*
