/*
 * "$Id: mxml-private.c,v 1.1 2003/09/28 21:09:04 mike Exp $"
 *
 * Private functions for mini-XML, a small XML-like file parsing library.
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
 *   mxml_integer_cb() - Default callback for integer values.
 *   mxml_opaque_cb()  - Default callback for opaque values.
 *   mxml_real_cb()    - Default callback for real number values.
 */

/*
 * Include necessary headers...
 */

#include "mxml.h"


/*
 * 'mxml_integer_cb()' - Default callback for integer values.
 */

mxml_type_t				/* O - Node type */
mxml_integer_cb(mxml_node_t *node)	/* I - Current node */
{
  (void)node;

  return (MXML_INTEGER);
}


/*
 * 'mxml_opaque_cb()' - Default callback for opaque values.
 */

mxml_type_t				/* O - Node type */
mxml_opaque_cb(mxml_node_t *node)	/* I - Current node */
{
  (void)node;

  return (MXML_OPAQUE);
}


/*
 * 'mxml_real_cb()' - Default callback for real number values.
 */

mxml_type_t				/* O - Node type */
mxml_real_cb(mxml_node_t *node)		/* I - Current node */
{
  (void)node;

  return (MXML_REAL);
}


/*
 * End of "$Id: mxml-private.c,v 1.1 2003/09/28 21:09:04 mike Exp $".
 */
