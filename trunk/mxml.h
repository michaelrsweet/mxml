/*
 * "$Id: mxml.h,v 1.6 2003/06/04 17:37:23 mike Exp $"
 *
 * Header file for mini-XML, a small XML-like file parsing library.
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
 */

/*
 * Prevent multiple inclusion...
 */

#ifndef _mxml_h_
#  define _mxml_h_

/*
 * Include necessary headers...
 */

#  include <stdio.h>
#  include <stdlib.h>
#  include <string.h>
#  include <ctype.h>
#  include <errno.h>


/*
 * Constants...
 */

#  define MXML_NO_CALLBACK	0	/* Don't use a type callback */
#  define MXML_WRAP		72	/* Wrap XML output at this column position */

#  define MXML_DESCEND		1	/* Descend when finding/walking */
#  define MXML_NO_DESCEND	0	/* Don't descend when finding/walking */
#  define MXML_DESCEND_FIRST	-1	/* Descend for first find */

#  define MXML_SAVE_OPEN_TAG	0	/* Callback for open tag */
#  define MXML_SAVE_CLOSE_TAG	1	/* Callback for close tag */


#  define MXML_ADD_BEFORE	0	/* Add node before specified node */
#  define MXML_ADD_AFTER	1	/* Add node after specified node */
#  define MXML_ADD_TO_PARENT	NULL	/* Add node relative to parent */


/*
 * Data types...
 */

typedef enum				/**** Node Type ****/
{
  MXML_ELEMENT,				/* XML element with attributes */
  MXML_INTEGER,				/* Integer value */
  MXML_OPAQUE,				/* Opaque string */
  MXML_REAL,				/* Real value */
  MXML_TEXT				/* Text fragment */
} mxml_type_t;

typedef struct				/**** Attribute Value ****/
{
  char	*name,				/* Attribute name */
	*value;				/* Attribute value */
} mxml_attr_t;

typedef struct				/**** Element Value ****/
{
  char		*name;			/* Name of element */
  int		num_attrs;		/* Number of attributes */
  mxml_attr_t	*attrs;			/* Attributes */
} mxml_element_t;

typedef struct mxml_node_str mxml_node_t;

struct mxml_node_str			/**** Node ****/
{
  mxml_type_t	type;			/* Node type */
  mxml_node_t	*next,			/* Next node under same parent */
		*prev,			/* Previous node under same parent */
		*parent,		/* Parent node */
		*child,			/* First child node */
		*last_child;		/* Last child node */
  union
  {
    mxml_element_t	element;	/* Element */
    int			integer;	/* Integer number */
    char		*opaque;	/* Opaque string */
    double		real;		/* Real number */
    struct
    {
      int		whitespace;	/* Leading whitespace? */
      char		*string;	/* Fragment string */
    }			text;		/* Text fragment */
  }		value;			/* Node value */
};


/*
 * C++ support...
 */

#  ifdef __cplusplus
extern "C" {
#  endif /* __cplusplus */

/*
 * Prototypes...
 */

extern void		mxmlAdd(mxml_node_t *parent, int where,
			        mxml_node_t *child, mxml_node_t *node);
extern void		mxmlDelete(mxml_node_t *node);
extern const char	*mxmlElementGetAttr(mxml_node_t *node, const char *name);
extern void		mxmlElementSetAttr(mxml_node_t *node, const char *name,
			                   const char *value);
extern mxml_node_t	*mxmlFindElement(mxml_node_t *node, mxml_node_t *top,
			                 const char *name, const char *attr,
					 const char *value, int descend);
extern mxml_node_t	*mxmlLoadFile(mxml_node_t *top, FILE *fp,
			              mxml_type_t (*cb)(mxml_node_t *));
extern mxml_node_t	*mxmlNewElement(mxml_node_t *parent, const char *name);
extern mxml_node_t	*mxmlNewInteger(mxml_node_t *parent, int integer);
extern mxml_node_t	*mxmlNewOpaque(mxml_node_t *parent, const char *opaque);
extern mxml_node_t	*mxmlNewReal(mxml_node_t *parent, double real);
extern mxml_node_t	*mxmlNewText(mxml_node_t *parent, int whitespace,
			             const char *string);
extern void		mxmlRemove(mxml_node_t *node);
extern int		mxmlSaveFile(mxml_node_t *node, FILE *fp,
			             int (*cb)(mxml_node_t *, int));
extern mxml_node_t	*mxmlWalkNext(mxml_node_t *node, mxml_node_t *top,
			              int descend);
extern mxml_node_t	*mxmlWalkPrev(mxml_node_t *node, mxml_node_t *top,
			              int descend);


/*
 * C++ support...
 */

#  ifdef __cplusplus
}
#  endif /* __cplusplus */
#endif /* !_mxml_h_ */


/*
 * End of "$Id: mxml.h,v 1.6 2003/06/04 17:37:23 mike Exp $".
 */
