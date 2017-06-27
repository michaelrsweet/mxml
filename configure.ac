dnl
dnl Configuration script for Mini-XML, a small XML-like file parsing library.
dnl
dnl Copyright 2003-2017 by Michael R Sweet.
dnl
dnl These coded instructions, statements, and computer programs are the
dnl property of Michael R Sweet and are protected by Federal copyright
dnl law.  Distribution and use rights are outlined in the file "COPYING"
dnl which should have been included with this file.  If this file is
dnl missing or damaged, see the license at:
dnl
dnl     https://michaelrsweet.github.io/mxml
dnl

dnl Package name and version...
AC_INIT([Mini-XML], [2.11], [https://github.com/michaelrsweet/mxml/issues], [mxml], [https://michaelrsweet.github.io/mxml])

dnl Get the build and host platforms and split the host_os value
AC_CANONICAL_BUILD
AC_CANONICAL_HOST

[host_os_name=`echo $host_os | sed -e '1,$s/[0-9.]*$//g'`]
[host_os_version=`echo $host_os | sed -e '1,$s/^[^0-9.]*//g'`]

dnl Set the name of the config header file...
AC_CONFIG_HEADER(config.h)

dnl Version number...
VERSION="AC_PACKAGE_VERSION"
AC_SUBST(VERSION)
AC_DEFINE_UNQUOTED(MXML_VERSION, "Mini-XML v$VERSION")

dnl Clear default debugging options and set normal optimization by
dnl default unless the user asks for debugging specifically.
CFLAGS="${CFLAGS:=}"
CXXFLAGS="${CXXFLAGS:=}"
LDFLAGS="${LDFLAGS:=}"
AC_SUBST(LDFLAGS)
OPTIM=""
AC_SUBST(OPTIM)

AC_ARG_WITH(ansi, [  --with-ansi             set full ANSI C mode, default=no],
	use_ansi="$withval",
	use_ansi="no")

AC_ARG_WITH(archflags, [  --with-archflags        set additional architecture flags, default=none],
	ARCHFLAGS="$withval",
	ARCHFLAGS="")
AC_SUBST(ARCHFLAGS)

AC_ARG_ENABLE(debug, [  --enable-debug          turn on debugging, default=no],
if eval "test x$enable_debug = xyes"; then
 	OPTIM="-g"
fi)

AC_ARG_WITH(docdir, [  --with-docdir           set directory for documentation, default=${prefix}/share/doc/mxml],
	docdir="$withval",
	docdir="NONE")

AC_SUBST(docdir)

AC_ARG_WITH(vsnprintf, [  --with-vsnprintf        use vsnprintf emulation functions, default=auto],
	use_vsnprintf="$withval",
	use_vsnprintf="no")

dnl Checks for programs...
AC_PROG_CC
AC_PROG_CXX
AC_PROG_INSTALL
if test "$INSTALL" = "$ac_install_sh"; then
	# Use full path to install-sh script...
	INSTALL="`pwd`/install-sh -c"
fi
AC_PROG_RANLIB
AC_CHECK_TOOL(AR,ar)
AC_PATH_PROG(CP,cp)
AC_PATH_PROG(LN,ln)
AC_PATH_PROG(MKDIR,mkdir)
AC_PATH_PROG(RM,rm)

dnl Flags for "ar" command...
case "$host_os_name" in
        darwin* | *bsd)
                ARFLAGS="-rcv"
                ;;
        *)
                ARFLAGS="crvs"
                ;;
esac

AC_SUBST(ARFLAGS)

dnl Inline functions...
AC_C_INLINE

dnl Checks for string functions.
if test "x$use_ansi" != xyes; then
	AC_CHECK_FUNCS(strdup strlcat strlcpy)
fi

if test "x$use_vsnprintf" != xyes; then
	AC_CHECK_FUNCS(snprintf vasprintf vsnprintf)
fi

dnl Check for "long long" support...
AC_CACHE_CHECK(for long long int, ac_cv_c_long_long,
	[if test "$GCC" = yes; then
		ac_cv_c_long_long=yes
	else
		AC_TRY_COMPILE(,[long long int i;],
			ac_cv_c_long_long=yes,
			ac_cv_c_long_long=no)
	fi])

if test $ac_cv_c_long_long = yes; then
	AC_DEFINE(HAVE_LONG_LONG)
fi

dnl EPUB support (via libz and zipc)
MXML_EPUB=""
AC_SUBST(MXML_EPUB)
ZIPC=""
AC_SUBST(ZIPC)
AC_SEARCH_LIBS(gzgets,z,[
	AC_DEFINE(HAVE_ZLIB_H)
	ZIPC="zipc.o"
	MXML_EPUB="mxml.epub"
	LIBS="-lz $LIBS"])

dnl Threading support
AC_ARG_ENABLE(threads, [  --enable-threads        enable multi-threading support])

have_pthread=no
PTHREAD_FLAGS=""
PTHREAD_LIBS=""

if test "x$enable_threads" != xno; then
	AC_CHECK_HEADER(pthread.h, AC_DEFINE(HAVE_PTHREAD_H))

	if test x$ac_cv_header_pthread_h = xyes; then
		dnl Check various threading options for the platforms we support
		for flag in -lpthreads -lpthread -pthread; do
        		AC_MSG_CHECKING([for pthread_create using $flag])
			SAVELIBS="$LIBS"
			LIBS="$flag $LIBS"
        		AC_TRY_LINK([#include <pthread.h>],
				[pthread_create(0, 0, 0, 0);],
        			have_pthread=yes)
        		AC_MSG_RESULT([$have_pthread])
			LIBS="$SAVELIBS"

			if test $have_pthread = yes; then
				PTHREAD_FLAGS="-D_THREAD_SAFE -D_REENTRANT"
				PTHREAD_LIBS="$flag"

				# Solaris requires -D_POSIX_PTHREAD_SEMANTICS to
				# be POSIX-compliant... :(
				case "$host_os_name" in
					sunos)
					PTHREAD_FLAGS="$PTHREAD_FLAGS -D_POSIX_PTHREAD_SEMANTICS"
					;;
				esac
				break
			fi
		done
	fi
fi

AC_SUBST(PTHREAD_FLAGS)
AC_SUBST(PTHREAD_LIBS)

dnl Shared library support...
DSO="${DSO:=:}"
DSOFLAGS="${DSOFLAGS:=}"

AC_ARG_ENABLE(shared, [  --enable-shared         turn on shared libraries, default=no])

if test x$enable_shared != xno; then
	AC_MSG_CHECKING(for shared library support)
	PICFLAG=1

	case "$host_os_name" in
		sunos | unix_s)
			AC_MSG_RESULT(yes)
			LIBMXML="libmxml.so.1.6"
			DSO="\$(CC)"
			DSOFLAGS="$DSOFLAGS -Wl,-h,libmxml.so.1 -G -R\$(libdir) \$(OPTIM)"
			LDFLAGS="$LDFLAGS -R\$(libdir)"
                	;;

		hp-ux)
			AC_MSG_RESULT(yes)
			LIBMXML="libmxml.sl.1"
			DSO="ld"
			DSOFLAGS="$DSOFLAGS -b -z +h libmxml.sl.1 +s +b \$(libdir)"
			LDFLAGS="$LDFLAGS -Wl,+s,+b,\$(libdir)"
			;;

		irix)
			AC_MSG_RESULT(yes)
			LIBMXML="libmxml.so.1.6"
			DSO="\$(CC)"
			DSOFLAGS="$DSOFLAGS -Wl,-rpath,\$(libdir),-set_version,sgi1.0,-soname,libmxml.so.1 -shared \$(OPTIM)"
			;;

		osf | linux* | gnu)
			AC_MSG_RESULT(yes)
			LIBMXML="libmxml.so.1.6"
			DSO="\$(CC)"
			DSOFLAGS="$DSOFLAGS -Wl,-soname,libmxml.so.1,-rpath,\$(libdir) -shared \$(OPTIM)"
                        LDFLAGS="$LDFLAGS -Wl,-rpath,\$(libdir)"
			;;

		*bsd)
			AC_MSG_RESULT(yes)
			LIBMXML="libmxml.so.1.6"
			DSO="\$(CC)"
			DSOFLAGS="$DSOFLAGS -Wl,-soname,libmxml.so.1,-R\$(libdir) -shared \$(OPTIM)"
			LDFLAGS="$LDFLAGS -Wl,-R\$(libdir)"
                        ;;

		darwin)
			AC_MSG_RESULT(yes)
			LIBMXML="libmxml.1.dylib"
			DSO="\$(CC)"
			DSOFLAGS="$DSOFLAGS \$(RC_CFLAGS) -dynamiclib -lc"
			;;

                mingw)
                        AC_MSG_RESULT(yes)
                        LIBMXML="mxml1.dll"
                        DSO="\$(CC)"
                        DSOFLAGS="$DSOFLAGS -shared -Wl,--out-implib,libmxml1.a,--no-undefined,--enable-runtime-pseudo-reloc"
                        ;;

		*)
			AC_MSG_RESULT(no)
			AC_MSG_WARN(shared libraries not supported on this platform.)
			PICFLAG=0
			LIBMXML="libmxml.a"
			;;
	esac
else
	PICFLAG=0
	LIBMXML="libmxml.a"
fi

AC_SUBST(DSO)
AC_SUBST(DSOFLAGS)
AC_SUBST(LIBMXML)
AC_SUBST(PICFLAG)

dnl Add -Wall for GCC...
if test -n "$GCC"; then
	CFLAGS="-Wall -D_GNU_SOURCE $CFLAGS"

	if test "x$OPTIM" = x; then
		OPTIM="-Os -g"
	fi

	if test "x$use_ansi" = xyes; then
		CFLAGS="-ansi -pedantic $CFLAGS"
	fi

	if test $PICFLAG = 1 -a "$host_os_name" != aix; then
    		OPTIM="-fPIC $OPTIM"
	fi
else
	case "$host_os_name" in
		hp-ux)
			CFLAGS="-Ae $CFLAGS"

			if test "x$OPTIM" = x; then
				OPTIM="-O"
			fi

			OPTIM="+DAportable $OPTIM"

			if test $PICFLAG = 1; then
				OPTIM="+z $OPTIM"
			fi
			;;

		unix_svr | sunos)
			if test "x$OPTIM" = x; then
				OPTIM="-O"
			fi

			if test $PICFLAG = 1; then
				OPTIM="-KPIC $OPTIM"
			fi
			;;

		*)
			if test "x$OPTIM" = x; then
				OPTIM="-O"
			fi
			;;
	esac
fi

dnl Determine whether we are cross-compiling...
if test "$build" = "$host"; then
	TARGETS="ALLTARGETS"
else
	TARGETS="CROSSTARGETS"
fi
AC_SUBST(TARGETS)

dnl Fix "prefix" variable if it hasn't been specified...
if test "$prefix" = "NONE"; then
	prefix="/usr/local"
fi

dnl Fix "exec_prefix" variable if it hasn't been specified...
if test "$exec_prefix" = "NONE"; then
	exec_prefix="$prefix"
fi

dnl Fix "docdir" variable if it hasn't been specified...
if test "$docdir" = "NONE"; then
	docdir="$datadir/doc/mxml"
fi

dnl Fix "mandir" variable if it hasn't been specified...
if test "$mandir" = "\${prefix}/man" -a "$prefix" = "/usr"; then
	case "$host_os_name" in
        	*bsd | darwin | linux*)
        		# *BSD, Darwin (macOS), and Linux
        		mandir="/usr/share/man"
        		;;
        	irix)
        		# SGI IRIX
        		mandir="/usr/share/catman/u_man"
        		;;
        	*)
        		# All others
        		mandir="/usr/man"
        		;;
	esac
fi

dnl pkg-config stuff...
if test "$includedir" != /usr/include; then
	PC_CFLAGS="-I$includedir"
else
	PC_CFLAGS=""
fi

if test "$libdir" != /usr/lib; then
	PC_LIBS="-L$libdir -lmxml"
else
	PC_LIBS="-lmxml"
fi

AC_SUBST(PC_CFLAGS)
AC_SUBST(PC_LIBS)

dnl Output the makefile, etc...
AC_OUTPUT(Makefile mxml.pc)
