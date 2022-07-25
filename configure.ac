dnl
dnl Configuration script for Mini-XML, a small XML file parsing library.
dnl
dnl https://www.msweet.org/mxml
dnl
dnl Copyright © 2003-2022 by Michael R Sweet.
dnl
dnl Licensed under Apache License v2.0.  See the file "LICENSE" for more
dnl information.
dnl

dnl We need at least autoconf 2.70 for --runstatedir...
AC_PREREQ([2.70])


dnl Package name and version...
AC_INIT([Mini-XML], [3.3.1], [https://github.com/michaelrsweet/mxml/issues], [mxml], [https://www.msweet.org/mxml])


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


dnl Set the name of the config header file...
AC_CONFIG_HEADERS([config.h])


dnl Version number...
VERSION="AC_PACKAGE_VERSION"
AC_SUBST(VERSION)
AC_DEFINE_UNQUOTED(MXML_VERSION, "Mini-XML v$VERSION")


dnl Clear default debugging options and set normal optimization by
dnl default unless the user asks for debugging specifically.
CFLAGS="${CFLAGS:=}"
CPPFLAGS="${CPPFLAGS:=}"
LDFLAGS="${LDFLAGS:=}"
AC_SUBST([LDFLAGS])
LIBS="${LIBS:=}"


dnl Options...
AC_ARG_WITH([ansi], AS_HELP_STRING([--with-ansi], [set full ANSI C mode, default=no]), [
    use_ansi="$withval"
], [
    use_ansi="no"
])

AC_ARG_WITH([archflags], AS_HELP_STRING([--with-archflags], [set additional architecture flags, default=none]), [
    ARCHFLAGS="$withval"
], [
    AS_CASE(["$host_os_name"], [darwin*], [
        AS_IF([test "$host_os_version" -ge 200 -a x$enable_debug != xyes], [
            # macOS 11.0 and higher support the Apple Silicon (arm64) CPUs
	    ARCHFLAGS="-mmacosx-version-min=10.14 -arch x86_64 -arch arm64"
	], [test x$enable_debug != xyes], [
	    ARCHFLAGS="-mmacosx-version-min=10.14 -arch x86_64"
	])
    ], [*], [
	ARCHFLAGS=""
    ])
])
AC_SUBST([ARCHFLAGS])

AC_ARG_WITH([optim], AS_HELP_STRING([--with-optim], [set additional optimization flags, default=none]), [
    OPTIM="$withval"
], [
    OPTIM=""
])
AC_SUBST([OPTIM])

AC_ARG_ENABLE([debug], AS_HELP_STRING([--enable-debug], [turn on debugging, default=no]))
AC_ARG_ENABLE([maintainer], AS_HELP_STRING([--enable-maintainer], [turn on maintainer mode, default=no]))
AC_ARG_ENABLE([sanitizer], AS_HELP_STRING([--enable-sanitizer], [build with AddressSanitizer, default=no]))

AC_ARG_WITH([docdir], AS_HELP_STRING([--with-docdir], [set directory for documentation, default=${prefix}/share/doc/mxml]), [
    docdir="$withval"
], [
    docdir="NONE"
])
AC_SUBST(docdir)

AC_ARG_WITH([vsnprintf], AS_HELP_STRING([--with-vsnprintf], [use vsnprintf emulation functions, default=auto]), [
    use_vsnprintf="$withval"
], [
    use_vsnprintf="no"
])


dnl Checks for programs...
AC_PROG_CC
AC_PROG_CXX
AC_PROG_INSTALL
AS_IF([test "$INSTALL" = "$ac_install_sh"], [
    # Use full path to install-sh script...
    INSTALL="`pwd`/install-sh -c"
])
AC_PROG_RANLIB
AC_CHECK_TOOL(AR,ar)
AC_PATH_PROG(CP,cp)
AC_PATH_PROGS(LDCONFIG,ldconfig false)
AC_PATH_PROG(LN,ln)
AC_PATH_PROG(MKDIR,mkdir)
AC_PATH_PROG(RM,rm)


dnl Flags for "ar" command...
AS_CASE(["$host_os_name"], [darwin* | *bsd], [
    ARFLAGS="-rcv"
], [*], [
    ARFLAGS="crvs"
])
AC_SUBST(ARFLAGS)


dnl Inline functions...
AC_C_INLINE


dnl Checks for string functions.
AS_IF([test "x$use_ansi" != xyes], [
    AC_CHECK_FUNCS([strdup strlcat strlcpy])
])

AS_IF([test "x$use_vsnprintf" != xyes], [
    AC_CHECK_FUNCS([snprintf vasprintf vsnprintf])
])


dnl Check for "long long" support...
AC_TYPE_LONG_LONG_INT


dnl Threading support
AC_ARG_ENABLE([threads], AS_HELP_STRING([--disable-threads], [disable multi-threading support, default=no]))

have_pthread=no
AS_IF([test "x$enable_threads" != xno], [
    AC_CHECK_HEADER([pthread.h], [
        AC_DEFINE([HAVE_PTHREAD_H], [Have <pthread.h>?])
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


dnl Shared library support...
DSO="${DSO:=:}"
DSOFLAGS="${DSOFLAGS:=}"

AC_ARG_ENABLE([shared], AS_HELP_STRING([--disable-shared], [turn off shared libraries, default=no]))

AS_IF([test x$enable_shared != xno], [
    AC_MSG_CHECKING([for shared library support])
    PICFLAG=1

    AS_CASE(["$host_os_name"], [sunos | unix_s], [
	AC_MSG_RESULT([yes])
	LIBMXML="libmxml.so.1.6"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -Wl,-h,libmxml.so.1 -G -R\$(libdir) \$(OPTIM)"
	LDFLAGS="$LDFLAGS -R\$(libdir)"
    ], [linux*], [
	AC_MSG_RESULT([yes])
	LIBMXML="libmxml.so.1.6"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -Wl,-soname,libmxml.so.1 -shared \$(OPTIM)"
    ], [osf | gnu], [
	AC_MSG_RESULT([yes])
	LIBMXML="libmxml.so.1.6"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -Wl,-soname,libmxml.so.1,-rpath,\$(libdir) -shared \$(OPTIM)"
	LDFLAGS="$LDFLAGS -Wl,-rpath,\$(libdir)"
    ], [*bsd | haiku*], [
	AC_MSG_RESULT([yes])
	LIBMXML="libmxml.so.1.6"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -Wl,-soname,libmxml.so.1,-R\$(libdir) -shared \$(OPTIM)"
	LDFLAGS="$LDFLAGS -Wl,-R\$(libdir)"
    ], [darwin], [
	AC_MSG_RESULT([yes])
	LIBMXML="libmxml.1.dylib"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS \$(RC_CFLAGS) -dynamiclib -lc"
    ], [mingw], [
	AC_MSG_RESULT([yes])
	LIBMXML="mxml1.dll"
	DSO="\$(CC)"
	DSOFLAGS="$DSOFLAGS -shared -Wl,--out-implib,libmxml1.a,--no-undefined,--enable-runtime-pseudo-reloc"
    ], [*], [
	AC_MSG_RESULT([no])
	AC_MSG_WARN([shared libraries not supported on this platform.])
	PICFLAG=0
	LIBMXML="libmxml.a"
    ])
], [
    PICFLAG=0
    LIBMXML="libmxml.a"
])

AC_SUBST([DSO])
AC_SUBST([DSOFLAGS])
AC_SUBST([LIBMXML])
AC_SUBST([PICFLAG])


dnl Compiler options...
WARNINGS=""
AC_SUBST([WARNINGS])

AS_IF([test -n "$GCC"], [
    CFLAGS="-D_GNU_SOURCE $CFLAGS"

    AS_IF([test "x$OPTIM" = x], [
	AS_IF([test x$enable_debug = xyes], [
	    OPTIM="-g"
	], [
	    OPTIM="-g -Os"
	])
    ], [test x$enable_debug = xyes], [
	OPTIM="$OPTIM -g"
    ])

    AS_IF([test x$enable_sanitizer = xyes], [
	# Use -fsanitize=address with debugging...
	OPTIM="$OPTIM -fsanitize=address"
    ], [
	# Otherwise use the Fortify enhancements to catch any unbounded
	# string operations...
	CPPFLAGS="$CPPFLAGS -D_FORTIFY_SOURCE=2"
    ])

    AS_IF([test "x$use_ansi" = xyes], [
	CFLAGS="-ansi -pedantic $CFLAGS"
    ])

    dnl Show all standard warnings + unused variables when compiling...
    WARNINGS="-Wall -Wunused"

    dnl Drop some not-useful/unreliable warnings...
    for warning in char-subscripts format-truncation format-y2k switch unused-result; do
	AC_MSG_CHECKING([whether compiler supports -Wno-$warning])

	OLDCFLAGS="$CFLAGS"
	CFLAGS="$CFLAGS -Wno-$warning -Werror"

	AC_COMPILE_IFELSE([AC_LANG_PROGRAM()], [
	    AC_MSG_RESULT(yes)
	    WARNINGS="$WARNINGS -Wno-$warning"
        ], [
	    AC_MSG_RESULT(no)
	])

	CFLAGS="$OLDCFLAGS"
    done

    dnl Maintainer mode enables -Werror...
    AS_IF([test x$enable_maintainer = xyes], [
	WARNINGS="$WARNINGS -Werror"
    ])

    AS_IF([test $PICFLAG = 1 -a "$host_os_name" != aix], [
	OPTIM="-fPIC $OPTIM"
    ])
], [
    AS_IF([test "x$OPTIM" = x], [
	AS_IF([test x$enable_debug = xyes], [
	    OPTIM="-g"
	], [
	    OPTIM="-O"
	])
    ])

    AS_CASE(["$host_os_name"], [hp-ux], [
	CFLAGS="-Ae $CFLAGS"

	OPTIM="+DAportable $OPTIM"

	AS_IF([test $PICFLAG = 1], [
	    OPTIM="+z $OPTIM"
	])
    ], [unix_svr | sunos], [
	AS_IF([test $PICFLAG = 1], [
	    OPTIM="-KPIC $OPTIM"
	])
    ])
])


dnl Determine whether we are cross-compiling...
AS_IF([test "$build" = "$host"], [
    TARGETS="ALLTARGETS"
], [
    TARGETS="CROSSTARGETS"
])
AC_SUBST([TARGETS])


dnl Fix installation directories...
AS_IF([test "$prefix" = "NONE"], [
    prefix="/usr/local"
])

AS_IF([test "$exec_prefix" = "NONE"], [
    exec_prefix="$prefix"
])

AS_IF([test "$docdir" = "NONE"], [
    docdir="$datadir/doc/mxml"
])

AS_IF([test "$mandir" = "\${prefix}/man" -a "$prefix" = "/usr"], [
    mandir="/usr/share/man"
])


dnl pkg-config stuff...
AS_IF([test "$includedir" != /usr/include], [
    PC_CFLAGS="-I$includedir"
], [
    PC_CFLAGS=""
])
AC_SUBST([PC_CFLAGS])

AS_IF([test "$libdir" != /usr/lib], [
    PC_LIBS="-L$libdir -lmxml"
], [
    PC_LIBS="-lmxml"
])
AC_SUBST([PC_LIBS])


dnl Output the makefile, etc...
AC_CONFIG_FILES([Makefile mxml.pc])
AC_OUTPUT
