#
# Linux makefile for Mini-XML, a small XML-like file parsing library.
#
# https://www.msweet.org/mxml
#
# Copyright © 2003-2024 by Michael R Sweet.
#
# Licensed under Apache License v2.0.  See the file "LICENSE" for more
# information.
#

#
# This is a POSIX makefile
#

.POSIX:


#
# Mini-XML version...
#

MXML_VERSION	=	4.0.4


#
# Programs...
#

AR		=	/usr/bin/ar
CC		=	gcc
DSO		=	$(CC)
INSTALL		=	/home/mike/c/mxml/install-sh
LN		=	/usr/bin/ln -sf
MKDIR           =       /usr/bin/mkdir -p
RANLIB		=	ranlib
RM		=	/usr/bin/rm -f
RMDIR		=	/usr/bin/rmdir
SHELL		=	/bin/sh


#
# Installation programs...
#

INSTALL_BIN	=	$(INSTALL) -c -m 755
INSTALL_DATA	=	$(INSTALL) -c -m 444
INSTALL_DIR	=	$(INSTALL) -d -m 755
INSTALL_LIB	=	$(INSTALL) -c -m 755
INSTALL_MAN	=	$(INSTALL) -c -m 444


#
# Libraries...
#

LIBMXML		=	libmxml4.so.2
LIBMXML_BASE	=	libmxml4
LIBMXML_STATIC	=	libmxml4.a
MXML_MAN	=	mxml4.3
MXML_PC		=	mxml4.pc


#
# Install static libraries?
#

INSTALL_STATIC	=	install-libmxml4.a


#
# Code signing...
#

CODE_SIGN	=	/usr/bin/true
CODESIGN_IDENTITY =	-
CSFLAGS		=	-s "$(CODESIGN_IDENTITY)" -o runtime --timestamp


#
# Library archiver...
#

ARFLAGS		=	cr


#
# C compiler and preprocessor...
#

CFLAGS		=	-fPIC  $(CPPFLAGS) $(OPTIM) $(WARNINGS)
CPPFLAGS	=	 -D_THREAD_SAFE -D_REENTRANT -D_FORTIFY_SOURCE=3 -D__USE_MISC -D_GNU_SOURCE
WARNINGS	=	-Wall -Wunused -Wno-char-subscripts -Wno-deprecated-declarations -Wno-format-truncation -Wno-format-y2k -Wno-switch -Wno-unused-result


#
# Linker options...
#

DSOFLAGS	=	 -Wl,-soname,`basename $@` -shared $(OPTIM)
LDFLAGS		=	-fPIE -pie  -Wl,-z,relro,-z,now $(OPTIM)
LIBS		=	-lpthread  -lm


#
# Optimization and architecture options for both the compiler and linker.
#

OPTIM		=	 -g -Os


#
# Directories...
#

bindir		=	${exec_prefix}/bin
datadir		=	${datarootdir}
datarootdir	=	${prefix}/share
docdir		=	${prefix}/share/doc/mxml4
exec_prefix	=	${prefix}
includedir	=	${prefix}/include/libmxml4
infodir		=	${datarootdir}/info
libdir		=	${exec_prefix}/lib
libexecdir	=	${exec_prefix}/libexec
localstatedir	=	${prefix}/var
mandir		=	${datarootdir}/man
oldincludedir	=	/usr/include
prefix		=	/usr/local
sbindir		=	${exec_prefix}/sbin
sharedstatedir	=	${prefix}/com
srcdir		=	.
sysconfdir	=	${prefix}/etc
top_srcdir	=	.

BUILDROOT	=	$(DSTROOT)$(DESTDIR)


#
# Rules...
#

.SILENT:
.SUFFIXES:	.c .man .o
.c.o:
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<


#
# Targets...
#

DOCFILES	=	doc/mxml.epub doc/mxml.html doc/mxml-cover.png \
			CHANGES.md LICENSE NOTICE README.md
PUBLIBOBJS	=	mxml-attr.o mxml-file.o mxml-get.o mxml-index.o \
			mxml-node.o mxml-options.o mxml-search.o mxml-set.o
LIBOBJS		=	$(PUBLIBOBJS) mxml-private.o
OBJS		=	testmxml.o $(LIBOBJS)
ALLTARGETS	=	$(LIBMXML) testmxml
CROSSTARGETS	=	$(LIBMXML)
TARGETS		=	$(ALLTARGETS)


#
# Make everything...
#

all:		$(TARGETS)


#
# Clean everything...
#

clean:
	echo Cleaning build files...
	$(RM) $(OBJS) $(ALLTARGETS)
	$(RM) libmxml4.a
	$(RM) libmxml4.so
	$(RM) libmxml4.so.2


#
# Really clean everything...
#

distclean:	clean
	echo Cleaning distribution files...
	$(RM) test.xmlfd
	$(RM) temp1.xml temp1.xmlfd temp1s.xml
	$(RM) temp2.xml temp2s.xml
	$(RM) -r clang


#
# Install everything...
#

.NOTPARALLEL: install install-$(LIBMXML) $(INSTALL_STATIC)

install:	$(TARGETS) install-$(LIBMXML) $(INSTALL_STATIC)
	echo Installing documentation in $(BUILDROOT)$(docdir)...
	$(INSTALL_DIR) $(BUILDROOT)$(docdir)
	for file in $(DOCFILES); do \
		$(INSTALL_MAN) ../$$file $(BUILDROOT)$(docdir)/`basename $$file .md`; \
	done
	echo Installing header files in $(BUILDROOT)$(includedir)...
	$(INSTALL_DIR) $(BUILDROOT)$(includedir)
	$(INSTALL_DATA) ../mxml.h $(BUILDROOT)$(includedir)
	echo Installing pkgconfig files in $(BUILDROOT)$(libdir)/pkgconfig...
	$(INSTALL_DIR) $(BUILDROOT)$(libdir)/pkgconfig
	$(INSTALL_DATA) mxml4.pc $(BUILDROOT)$(libdir)/pkgconfig/$(MXML_PC)
	echo Installing man pages in $(BUILDROOT)$(mandir)...
	$(INSTALL_DIR) $(BUILDROOT)$(mandir)/man3
	$(INSTALL_MAN) ../doc/mxml.3 $(BUILDROOT)$(mandir)/man3/$(MXML_MAN)

install-libmxml.a:	libmxml.a
	echo Installing libmxml.a to $(BUILDROOT)$(libdir)...
	$(INSTALL_DIR) $(BUILDROOT)$(libdir)
	$(INSTALL_LIB) libmxml.a $(BUILDROOT)$(libdir)
	$(RANLIB) $(BUILDROOT)$(libdir)/libmxml.a

install-libmxml.so.2:	libmxml.so.2
	echo Installing libmxml.so to $(BUILDROOT)$(libdir)...
	$(INSTALL_DIR) $(BUILDROOT)$(libdir)
	$(INSTALL_LIB) libmxml.so.2 $(BUILDROOT)$(libdir)
	$(RM) $(BUILDROOT)$(libdir)/libmxml.so
	$(LN) libmxml.so.2 $(BUILDROOT)$(libdir)/libmxml.so
	$(LDCONFIG)

install-libmxml4.a:	libmxml4.a
	echo Installing libmxml4.a to $(BUILDROOT)$(libdir)...
	$(INSTALL_DIR) $(BUILDROOT)$(libdir)
	$(INSTALL_LIB) libmxml4.a $(BUILDROOT)$(libdir)
	$(RANLIB) $(BUILDROOT)$(libdir)/libmxml4.a

install-libmxml4.so.2:	libmxml4.so.2
	echo Installing libmxml4.so to $(BUILDROOT)$(libdir)...
	$(INSTALL_DIR) $(BUILDROOT)$(libdir)
	$(INSTALL_LIB) libmxml4.so.2 $(BUILDROOT)$(libdir)
	$(RM) $(BUILDROOT)$(libdir)/libmxml4.so
	$(LN) libmxml4.so.2 $(BUILDROOT)$(libdir)/libmxml4.so
	$(LDCONFIG)


#
# Uninstall everything...
#

uninstall: uninstall-$(LIBMXML) uninstall-libmxml4.a
	echo Uninstalling documentation from $(BUILDROOT)$(docdir)...
	$(RM) -r $(BUILDROOT)$(docdir)
	echo Uninstalling headers from $(BUILDROOT)$(includedir)...
	$(RM) $(BUILDROOT)$(includedir)/mxml.h
	echo Uninstalling pkgconfig files from $(BUILDROOT)$(libdir)/pkgconfig...
	$(RM) $(BUILDROOT)$(libdir)/pkgconfig/$(MXML_PC)
	echo Uninstalling man pages from $(BUILDROOT)$(mandir)...
	$(RM) $(BUILDROOT)$(mandir)/man3/$(MXML_MAN)

uninstall-libmxml.a:
	echo Uninstalling libmxml.a from $(BUILDROOT)$(libdir)...
	$(RM) $(BUILDROOT)$(libdir)/libmxml.a

uninstall-libmxml.so.2:
	echo Uninstalling libmxml.so from $(BUILDROOT)$(libdir)...
	$(RM) $(BUILDROOT)$(libdir)/libmxml.so
	$(RM) $(BUILDROOT)$(libdir)/libmxml.so.2
	$(LDCONFIG)

uninstall-libmxml4.a:
	echo Uninstalling libmxml4.a from $(BUILDROOT)$(libdir)...
	$(RM) $(BUILDROOT)$(libdir)/libmxml4.a

uninstall-libmxml4.so.2:
	echo Uninstalling libmxml4.so from $(BUILDROOT)$(libdir)...
	$(RM) $(BUILDROOT)$(libdir)/libmxml4.so
	$(RM) $(BUILDROOT)$(libdir)/libmxml4.so.2
	$(LDCONFIG)


#
# Test everything...
#

test:	testmxml
	@echo Testing library...
	./testmxml ../test.xml temp1s.xml >temp1.xml
	./testmxml temp1.xml temp2s.xml >temp2.xml
	@if cmp temp1.xml temp2.xml; then \
		echo Stdio file test passed!; \
		$(RM) temp2.xml temp2s.xml; \
	else \
		echo Stdio file test failed!; \
		exit 1; \
	fi
	@if cmp temp1.xml temp1s.xml; then \
		echo String test passed!; \
		$(RM) temp1.xml temp1s.xml; \
	else \
		echo String test failed!; \
		exit 1; \
	fi
	@if cmp ../test.xml ../test.xmlfd; then \
		echo File descriptor test passed!; \
		$(RM) test.xmlfd temp1.xmlfd; \
	else \
		echo File descriptor test failed!; \
		exit 1; \
	fi


#
# libmxml.a
#

libmxml.a libmxml4.a:	$(LIBOBJS)
	echo Creating $@...
	$(RM) $@
	$(AR) $(ARFLAGS) $@ $(LIBOBJS)
	$(RANLIB) $@


#
# libmxml2.dll
#

libmxml.dll libmxml4.dll:	$(LIBOBJS)
	echo Creating $@...
	$(DSO) $(DSOFLAGS) -o $@ $(LIBOBJS) $(LIBS)


#
# libmxml.so.2
#

libmxml.so.2 libmxml4.so.2:	$(LIBOBJS)
	echo Creating $@...
	$(DSO) $(DSOFLAGS) -o $@ $(LIBOBJS) $(LIBS)
	$(RM) `basename $@ .2`
	$(LN) $@ `basename $@ .2`


#
# testmxml
#

testmxml:	$(LIBMXML_STATIC) testmxml.o
	echo Linking $@...
	$(CC) $(LDFLAGS) -o $@ testmxml.o $(LIBMXML_STATIC) $(LIBS)

testmxml-vg:	$(LIBOBJS) testmxml.o
	echo Linking $@...
	$(CC) $(LDFLAGS) -o $@ testmxml.o $(LIBOBJS) $(LIBS)


#
# Documentation (depends on separate codedoc utility)
#

.PHONY: doc
doc:	../mxml.h $(PUBLIBOBJS:.o=.c) \
		../doc/body.md ../doc/body.man ../doc/footer.man \
		../doc/mxml-cover.png
	echo Generating API documentation...
	$(RM) mxml.xml
	codedoc --body ../doc/body.md \
		--coverimage ../doc/mxml-cover.png \
		mxml.xml mxml.h $(PUBLIBOBJS:.o=.c) >../doc/mxml.html
	codedoc --body ../doc/body.md \
		--coverimage ../doc/mxml-cover.png \
		--epub ../doc/mxml.epub mxml.xml
	codedoc --man mxml --title "Mini-XML API" \
		--body ../doc/body.man --footer ../doc/footer.man \
		mxml.xml >../doc/mxml.3
	$(RM) mxml.xml


#
# All object files depend on the makefile and config header...
#

mxml-attr.o: ../mxml-attr.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
mxml-file.o: ../mxml-file.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
mxml-get.o: ../mxml-get.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
mxml-index.o: ../mxml-index.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
mxml-node.o: ../mxml-node.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
mxml-options.o: ../mxml-options.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
mxml-private.o: ../mxml-private.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
mxml-search.o: ../mxml-search.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
mxml-set.o: ../mxml-set.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
testmxml.o: ../testmxml.c ../mxml.h ../mxml-private.h Makefile config.h
	echo Compiling $<
	$(CC) $(CFLAGS) -c -o $@ $<
