/*
 * "$Id: mxml-string.c,v 1.1 2003/07/20 13:41:17 mike Exp $"
 *
 * String functions for mini-XML, a small XML-like file parsing library.
 *
 * Copyright 2003 by Michael Sweet.
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
 *
 * Contents:
 *
 *   mxml_strdup() - Duplicate a string.
 */

/*
 * Include necessary headers...
 */

#include "config.h"


/*
 * 'mxml_strdup()' - Duplicate a string.
 */

#ifndef HAVE_STRDUP
char 	*			/* O - New string pointer */
mxml_strdup(const char *s)	/* I - String to duplicate */
{
  char	*t;			/* New string pointer */


  if (s == NULL)
    return (NULL);

  if ((t = malloc(strlen(s) + 1)) == NULL)
    return (NULL);

  return (strcpy(t, s));
}
#endif /* !HAVE_STRDUP */


/*
 * End of "$Id: mxml-string.c,v 1.1 2003/07/20 13:41:17 mike Exp $".
 */
