dnl
dnl Configuration script for Mini-XML, a small XML file parsing library.
dnl
dnl https://www.msweet.org/mxml
dnl
dnl Copyright © 2003-2024 by Michael R Sweet.
dnl
dnl Licensed under Apache License v2.0.  See the file "LICENSE" for more
dnl information.
dnl

dnl We need at least autoconf 2.70 for --runstatedir...
AC_PREREQ([2.70])


dnl Package name and version...
AC_INIT([Mini-XML], [4.0.1], [https://github.com/michaelrsweet/mxml/issues], [mxml], [https://www.msweet.org/mxml])
AC_CONFIG_HEADERS([config.h])

MXML_VERSION="AC_PACKAGE_VERSION"
AC_SUBST([MXML_VERSION])
AC_DEFINE_UNQUOTED([MXML_VERSION], "Mini-XML v$MXML_VERSION", [Version number])

dnl This line is provided to ensure that you don't run the autoheader program
dnl against this project.  Doing so is completely unsupported and WILL cause
dnl problems!
AH_TOP([#error "Somebody ran autoheader on this project which is unsupported and WILL cause problems."])


dnl Get the build and host platforms and split the host_os value
AC_CANONICAL_BUILD
AC_CANONICAL_HOST

[host_os_name=`echo $host_os | sed -e '1,$s/[0-9.]*$//g'`]
[host_os_version="$(echo $host_os | sed -e '1,$s/^[^0-9.]*//g' | awk -F. '{print $1 $2}')"]
# Linux often does not yield an OS version we can use...
AS_IF([test "x$host_os_version" = x], [
    host_os_version="0"
])


dnl Compiler options...
CFLAGS="${CFLAGS:=}"
CPPFLAGS="${CPPFLAGS:=}"
DSO=""
DSOFLAGS="${DSOFLAGS:=}"
LDFLAGS="${LDFLAGS:=}"
LIBS="${LIBS:=}"
OPTIM="${OPTIM:=}"

AC_SUBST([DSO])
AC_SUBST([DSOFLAGS])
AC_SUBST([LDFLAGS])
AC_SUBST([OPTIM])


dnl Standard programs...
AC_PROG_CC
AC_PROG_RANLIB
AC_PATH_PROG([AR], [ar])
AC_PATH_PROGS([CODE_SIGN], [codesign true])
AC_MSG_CHECKING([for install-sh script])
INSTALL="`pwd`/install-sh"
AC_SUBST([INSTALL])
AC_MSG_RESULT([using $INSTALL])
AC_PATH_PROGS([LDCONFIG],ldconfig true)
AC_PATH_PROG([MKDIR], [mkdir])
AC_PATH_PROG([RM], [rm])
AC_PATH_PROG([RMDIR], [rmdir])
AC_PATH_PROG([LN], [ln])


dnl Figure out the correct "ar" command flags...
AS_IF([test "$ac_cv_prog_ranlib" = ":"], [
    ARFLAGS="crs"
], [
    ARFLAGS="cr"
])
AC_SUBST([ARFLAGS])


dnl Library prefix...
AC_ARG_ENABLE([libmxml4-prefix], AS_HELP_STRING([--disable-libmxml4-prefix], [do not add libmxml4 prefix to header/library files]))

AS_IF([test x$enable_libmxml4_prefix != xno], [
    MXML_PC="mxml4.pc"
    INCLUDE_PREFIX="/libmxml4"
    LIBMXML_BASE="libmxml4"
    LINK_MXML="-lmxml4"
], [
    MXML_PC="mxml.pc"
    INCLUDE_PREFIX=""
    LIBMXML_BASE="libmxml"
    LINK_MXML="-lmxml"
])
AC_SUBST([LIBMXML_BASE])
AC_SUBST([MXML_PC])


dnl Inline functions...
AC_C_INLINE


dnl Check for "long long" support...
AC_TYPE_LONG_LONG_INT


dnl Threading support
AC_ARG_ENABLE([threads], AS_HELP_STRING([--disable-threads], [disable multi-threading support, default=no]))

have_pthread=no
AS_IF([test "x$enable_threads" != xno], [
    AC_CHECK_HEADER([pthread.h], [
        AC_DEFINE([HAVE_PTHREAD_H], [1], [Have <pthread.h>?])
    ])

    AS_IF([test x$ac_cv_header_pthread_h = xyes], [
	dnl Check various threading options for the platforms we support
	for flag in -lpthreads -lpthread -pthread; do
	    AC_MSG_CHECKING([for pthread_create using $flag])
	    SAVELIBS="$LIBS"
	    LIBS="$flag $LIBS"
	    AC_LANG([C])
	    AC_LINK_IFELSE([AC_LANG_PROGRAM([#include <pthread.h>],[pthread_create(0, 0, 0, 0);])], [
		have_pthread=yes
	    ], [
		LIBS="$SAVELIBS"
	    ])

	    AS_IF([test x$have_pthread = xyes], [
		AC_MSG_RESULT([yes])
		CPPFLAGS="$CPPFLAGS -D_THREAD_SAFE -D_REENTRANT"
		break
	    ], [
		AC_MSG_RESULT([no])
	    ])
	done
    ])
])


dnl Library targets...
AC_ARG_ENABLE([static], AS_HELP_STRING([--disable-static], [do not install static library]))
AC_ARG_ENABLE([shared], AS_HELP_STRING([--disable-shared], [do not install shared library]))

LIBMXML_STATIC="$LIBMXML_BASE.a"
AS_IF([test x$enable_shared != xno], [
    AS_CASE(["$host_os_name"], [linux* | gnu*], [
	LIBMXML="$LIBMXML_BASE.so.2"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -Wl,-soname,\`basename \$@\` -shared"
    ], [*bsd*], [
	LIBMXML="$LIBMXML_BASE.so.2"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -Wl,-soname,\`basename \$@\` -shared -lc"
    ], [darwin*], [
	LIBMXML="$LIBMXML_BASE.2.dylib"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -Wl,-no_warn_inits -dynamiclib -lc"
    ], [sunos*], [
	LIBMXML="$LIBMXML_BASE.so.2"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -Wl,-h,\`basename \$@\` -G"
    ], [mingw], [
	AC_MSG_RESULT([yes])
	LIBMXML="$LIBMXML_BASE.dll"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -shared -Wl,--out-implib,$LIBMXML_BASE.a,--no-undefined,--enable-runtime-pseudo-reloc"
    ], [*], [
	AC_MSG_NOTICE([Warning: Shared libraries may not work, trying -shared option.])
	LIBMXML="$LIBMXML_BASE.so.2"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -Wl,-soname,\`basename \$@\` -shared"
    ])

    AS_IF([test x$enable_static != xno], [
	AC_MSG_NOTICE([Installing static libraries...])
	INSTALL_STATIC="install-$LIBMXML_BASE.a"
	UNINSTALL_STATIC="uninstall-$LIBMXML_BASE.a"
    ], [
	INSTALL_STATIC=""
	UNINSTALL_STATIC=""
    ])
], [
    INSTALL_STATIC=""
    LIBMXML="$LIBMXML_BASE.a"
    UNINSTALL_STATIC=""
])

AC_SUBST([INSTALL_STATIC])
AC_SUBST([LIBMXML])
AC_SUBST([LIBMXML_STATIC])
AC_SUBST([UNINSTALL_STATIC])


dnl Extra compiler options...
AC_ARG_ENABLE([debug], AS_HELP_STRING([--enable-debug], [turn on debugging, default=no]))
AC_ARG_ENABLE([maintainer], AS_HELP_STRING([--enable-maintainer], [turn on maintainer mode, default=no]))
AC_ARG_WITH([sanitizer], AS_HELP_STRING([--with-sanitizer=...], [build with address, leak, memory, thread, or undefined sanitizer, default=no]), [], [with_sanitizer=no])
AS_IF([test "x$with_sanitizer" = xyes], [
    with_sanitizer="address"
], [test "$with_sanitizer" != address -a "$with_sanitizer" != leak -a "$with_sanitizer" != memory -a "$with_sanitizer" != no -a "$with_sanitizer" != thread -a "$with_sanitizer" != undefined], [
    AC_MSG_ERROR([Unsupported --with-sanitizer value "$with_sanitizer" specified.])
])

AS_IF([test x$enable_debug = xyes], [
    CSFLAGS=""
    OPTIM="$OPTIM -g"
    OPTIONS="-DDEBUG -DDEBUG_GUARDS"
], [
    CSFLAGS="-o runtime"
    OPTIM="$OPTIM -g -Os"
    OPTIONS=""
])

AC_SUBST([CSFLAGS])
AC_SUBST([OPTIONS])

WARNINGS=""
AC_SUBST([WARNINGS])

AS_IF([test -n "$GCC"], [
    AS_IF([test x$with_sanitizer != xno], [
	# Use -fsanitize=FOO with debugging...
	OPTIM="$OPTIM -fsanitize=$with_sanitizer"
    ], [echo "$CPPFLAGS $CFLAGS" | grep -q _FORTIFY_SOURCE], [
        # Don't add _FORTIFY_SOURCE if it is already there
    ], [
	# Otherwise use the Fortify enhancements to catch any unbounded
	# string operations...
	CPPFLAGS="$CPPFLAGS -D_FORTIFY_SOURCE=3"
    ])

    dnl Show all standard warnings + unused variables when compiling...
    WARNINGS="-Wall -Wunused"

    dnl Drop some not-useful/unreliable warnings...
    for warning in char-subscripts deprecated-declarations format-truncation format-y2k switch unused-result; do
	AC_MSG_CHECKING([whether compiler supports -Wno-$warning])

	OLDCFLAGS="$CFLAGS"
	CFLAGS="$CFLAGS -Wno-$warning -Werror"

	AC_COMPILE_IFELSE([AC_LANG_PROGRAM()], [
	    AC_MSG_RESULT([yes])
	    WARNINGS="$WARNINGS -Wno-$warning"
        ], [
	    AC_MSG_RESULT([no])
	])

	CFLAGS="$OLDCFLAGS"
    done

    dnl Maintainer mode enables -Werror...
    AS_IF([test x$enable_maintainer = xyes], [
	WARNINGS="$WARNINGS -Werror"
    ])

    dnl See if PIE options are supported...
    AC_MSG_CHECKING(whether compiler supports -fPIE)
    OLDCFLAGS="$CFLAGS"
    AS_CASE(["$host_os_name"],
    [darwin*], [
	CFLAGS="$CFLAGS -fPIC -fPIE -Wl,-pie"
	AC_COMPILE_IFELSE([AC_LANG_PROGRAM([[]], [[]])],[
	    OLDCFLAGS="-fPIC $OLDCFLAGS"
	    LDFLAGS="-fPIE -Wl,-pie $LDFLAGS"
	    AC_MSG_RESULT([yes])
	],[
	    AC_MSG_RESULT([no])
	])
    ], [*], [
	CFLAGS="$CFLAGS -fPIC -fPIE -pie"
	AC_COMPILE_IFELSE([AC_LANG_PROGRAM([[]], [[]])],[
	    OLDCFLAGS="-fPIC $OLDCFLAGS"
	    LDFLAGS="-fPIE -pie $LDFLAGS"
	    AC_MSG_RESULT([yes])
	],[
	    AC_MSG_RESULT([no])
	])
    ])
    CFLAGS="$OLDCFLAGS"

    dnl OS-specific compiler options...
    AC_MSG_CHECKING([for OS-specific compiler options])
    AS_CASE(["$host_os_name"], [linux*], [
    	# Make sure we get the full set of Linux APIs from the headers...
	CPPFLAGS="$CPPFLAGS -D__USE_MISC -D_GNU_SOURCE"

	# Mark read-only sections as relocatable to random addresses...
	LDFLAGS="$LDFLAGS -Wl,-z,relro,-z,now"

        AC_MSG_RESULT([-D__USE_MISC -D_GNU_SOURCE -Wl,-z,relro,-z,now])
    ], [darwin*], [
        # When not building for debug, target macOS 11 or later, "universal"
        # binaries when possible...
        AS_IF([echo "$CPPFLAGS $CFLAGS $LDFLAGS $OPTIM" | grep -q "\\-arch "], [
            # Don't add architecture/min-version flags if they are already present
            AC_MSG_RESULT([none])
        ], [echo "$CPPFLAGS $CFLAGS $LDFLAGS $OPTIM" | grep -q "\\-mmacosx-version-"], [
            # Don't add architecture/min-version flags if they are already present
            AC_MSG_RESULT([none])
        ], [test "$host_os_version" -ge 200 -a x$enable_debug != xyes], [
	    # macOS 11.0 and higher support the Apple Silicon (arm64) CPUs
	    OPTIM="$OPTIM -mmacosx-version-min=11.0 -arch x86_64 -arch arm64"
            AC_MSG_RESULT([-mmacosx-version-min=11.0 -arch x86_64 -arch arm64])
	], [
	    # Don't add architecture/min-version flags if debug enabled
            AC_MSG_RESULT([none])
	])
    ], [*], [
        AC_MSG_RESULT([none])
    ])
])


dnl Extra linker options...
AC_ARG_WITH([dsoflags], AS_HELP_STRING([--with-dsoflags=...], [Specify additional DSOFLAGS]), [
    DSOFLAGS="$withval $DSOFLAGS"
])
AC_ARG_WITH([ldflags], AS_HELP_STRING([--with-ldflags=...], [Specify additional LDFLAGS]), [
    LDFLAGS="$withval $LDFLAGS"
])


dnl Directories...
AC_ARG_WITH([docdir], AS_HELP_STRING([--with-docdir], [set directory for documentation, default=${prefix}/share/doc/mxml4]), [
    docdir="$withval"
], [
    AS_IF([test x$enable_libmxml4_prefix != xno], [
	docdir="\${prefix}/share/doc/mxml4"
    ], [
	docdir="\${prefix}/share/doc/mxml"
    ])
])
AC_SUBST([docdir])

AS_IF([test "$prefix" = NONE], [
    # Default prefix isn't bound until AC_OUTPUT...
    realprefix="/usr/local"
], [
    realprefix="$prefix"
])

AS_IF([test "$includedir" = NONE -o "$includedir" = "\${prefix}/include"], [
    includedir="\${prefix}/include$INCLUDE_PREFIX"
])


dnl Determine whether we are cross-compiling...
AS_IF([test "$build" = "$host"], [
    TARGETS="ALLTARGETS"
], [
    TARGETS="CROSSTARGETS"
])
AC_SUBST([TARGETS])


dnl pkg-config flags...
AS_IF([test "$includedir" != "/usr/include" -a "$includedir" != "/usr/local/include"], [
    PKGCONFIG_CFLAGS="-I\${includedir} $PKGCONFIG_CFLAGS"
], [
    PKGCONFIG_CFLAGS=""
])
PKGCONFIG_LIBS="$LINK_MXML"
AS_IF([test "$realprefix" != "/usr" -a "$realprefix" != "/usr/local"], [
    PKGCONFIG_LIBS="-L\${libdir} $PKGCONFIG_LIBS"
])

AC_SUBST([PKGCONFIG_CFLAGS])
AC_SUBST([PKGCONFIG_LIBS])


dnl Output generated files...
AC_CONFIG_FILES([Makefile mxml4.pc])
AC_OUTPUT
