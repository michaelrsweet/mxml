/*
 * "$Id: config.h,v 1.1 2004/06/21 11:17:51 mike Exp $"
 *
 * Configuration file for Mini-XML, a small XML-like file parsing library.
 *
 * Copyright 2003-2004 by Michael Sweet.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Library General Public
 * License as published by the Free Software Foundation; either
 * version 2, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
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

#define MXML_VERSION "Mini-XML v2.0"


/*
 * Do we have the vsnprintf() function?
 */

#define HAVE_VSNPRINTF 1


/*
 * Do we have the strXXX() functions?
 */

#define HAVE_STRDUP 1


/*
 * Define prototypes for string functions as needed...
 */

#  ifndef HAVE_STRDUP
extern char	*mxml_strdup(const char *);
#    define strdup mxml_strdup
#  endif /* !HAVE_STRDUP */

extern char	*mxml_strdupf(const char *, va_list);

#  ifndef HAVE_VSNPRINTF
extern int	mxml_vsnprintf(char *, size_t, const char *, va_list);
#    define vsnprintf mxml_vsnprintf
#  endif /* !HAVE_VSNPRINTF */

/*
 * End of "$Id: config.h,v 1.1 2004/06/21 11:17:51 mike Exp $".
 */
