/*
 * Xcode configuration file for Mini-XML, a small XML file parsing library.
 *
 * https://www.msweet.org/mxml
 *
 * Copyright © 2003-2020 by Michael R Sweet.
 *
 * Licensed under Apache License v2.0.  See the file "LICENSE" for more
 * information.
 */

/*
 * Include necessary headers...
 */

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdarg.h>
#include <ctype.h>


/*
 * Version number...
 */

#define MXML_VERSION "Mini-XML v3.2"


/*
 * Inline function support...
 */

#define inline


/*
 * Long long support...
 */

#define HAVE_LONG_LONG 1


/*
 * Do we have the *printf() functions?
 */

#define HAVE_SNPRINTF 1
#define HAVE_VASPRINTF 1
#define HAVE_VSNPRINTF 1


/*
 * Do we have the strXXX() functions?
 */

#define HAVE_STRDUP 1
#define HAVE_STRLCAT 1
#define HAVE_STRLCPY 1


/*
 * Do we have threading support?
 */

#define HAVE_PTHREAD_H 1


/*
 * Define prototypes for string functions as needed...
 */

extern char	*_mxml_strdupf(const char *, ...);
extern char	*_mxml_vstrdupf(const char *, va_list);
