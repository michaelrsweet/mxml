/*
 * "$Id: mxml-node.c,v 1.1 2003/06/03 19:46:30 mike Exp $"
 *
 * Node support code for mini-XML, a small XML-like file parsing library.
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
 *   mxmlDelete()     - Delete a node and all of its children.
 *   mxmlNewElement() - Create a new element node.
 *   mxmlNewInteger() - Create a new integer node.
 *   mxmlNewOpaque()  - Create a new opaque string.
 *   mxmlNewReal()    - Create a new real number node.
 *   mxmlNewText()    - Create a new text fragment node.
 *   mxml_new()       - Create a new node.
 */

/*
 * Include necessary headers...
 */

#include "mxml.h"


/*
 * Local functions...
 */

static mxml_node_t	*mxml_new(mxml_node_t *parent, mxml_type_t type);


/*
 * 'mxmlDelete()' - Delete a node and all of its children.
 */

void
mxmlDelete(mxml_node_t *node)		/* I - Node */
{
  int	i;				/* Looping var */


 /*
  * Range check input...
  */

  if (!node)
    return;

 /*
  * Delete children first...
  */

  while (node->child)
    mxmlDelete(node->child);

 /*
  * Now delete any node data...
  */

  switch (node->type)
  {
    case MXML_ELEMENT :
        if (node->value.element.name)
	  free(node->value.element.name);

	if (node->value.element.num_attrs)
	{
	  for (i = 0; i < node->value.element.num_attrs; i ++)
	  {
	    if (node->value.element.attrs[i].name)
	      free(node->value.element.attrs[i].name);
	    if (node->value.element.attrs[i].value)
	      free(node->value.element.attrs[i].value);
	  }

          free(node->value.element.attrs);
	}
        break;
    case MXML_INTEGER :
       /* Nothing to do */
        break;
    case MXML_OPAQUE :
        if (node->value.opaque)
	  free(node->value.opaque);
        break;
    case MXML_REAL :
       /* Nothing to do */
        break;
    case MXML_TEXT :
        if (node->value.text.string)
	  free(node->value.text.string);
        break;
  }

 /*
  * Remove from parent, if any...
  */

  if (node->parent)
  {
    if (node->prev)
      node->prev->next = node->next;
    else
      node->parent->child = node->next;

    if (node->next)
      node->next->prev = node->prev;
    else
      node->parent->last_child = node->prev;
  }

 /*
  * Free this node...
  */

  free(node);
}


/*
 * 'mxmlNewElement()' - Create a new element node.
 */

mxml_node_t *				/* O - New node */
mxmlNewElement(mxml_node_t *parent,	/* I - Parent node */
               const char  *name)	/* I - Name of element */
{
  mxml_node_t	*node;			/* New node */


 /*
  * Range check input...
  */

  if (!name)
    return (NULL);

 /*
  * Create the node and set the element name...
  */

  if ((node = mxml_new(parent, MXML_ELEMENT)) != NULL)
    node->value.element.name = strdup(name);

  return (node);
}


/*
 * 'mxmlNewInteger()' - Create a new integer node.
 */

mxml_node_t *				/* O - New node */
mxmlNewInteger(mxml_node_t *parent,	/* I - Parent node */
               int         integer)	/* I - Integer value */
{
  mxml_node_t	*node;			/* New node */


 /*
  * Range check input...
  */

  if (!parent)
    return (NULL);

 /*
  * Create the node and set the element name...
  */

  if ((node = mxml_new(parent, MXML_INTEGER)) != NULL)
    node->value.integer = integer;

  return (node);
}


/*
 * 'mxmlNewOpaque()' - Create a new opaque string.
 */

mxml_node_t *				/* O - New node */
mxmlNewOpaque(mxml_node_t *parent,	/* I - Parent node */
              const char  *opaque)	/* I - Opaque string */
{
  mxml_node_t	*node;			/* New node */


 /*
  * Range check input...
  */

  if (!parent || !opaque)
    return (NULL);

 /*
  * Create the node and set the element name...
  */

  if ((node = mxml_new(parent, MXML_OPAQUE)) != NULL)
    node->value.opaque = strdup(opaque);

  return (node);
}


/*
 * 'mxmlNewReal()' - Create a new real number node.
 */

mxml_node_t *				/* O - New node */
mxmlNewReal(mxml_node_t *parent,	/* I - Parent node */
            double      real)		/* I - Real number value */
{
  mxml_node_t	*node;			/* New node */


 /*
  * Range check input...
  */

  if (!parent)
    return (NULL);

 /*
  * Create the node and set the element name...
  */

  if ((node = mxml_new(parent, MXML_REAL)) != NULL)
    node->value.real = real;

  return (node);
}


/*
 * 'mxmlNewText()' - Create a new text fragment node.
 */

mxml_node_t *				/* O - New node */
mxmlNewText(mxml_node_t *parent,	/* I - Parent node */
            int         whitespace,	/* I - Leading whitespace? */
	    const char  *string)	/* I - String */
{
  mxml_node_t	*node;			/* New node */


 /*
  * Range check input...
  */

  if (!parent || !string)
    return (NULL);

 /*
  * Create the node and set the text value...
  */

  if ((node = mxml_new(parent, MXML_TEXT)) != NULL)
  {
    node->value.text.whitespace = whitespace;
    node->value.text.string     = strdup(string);
  }

  return (node);
}


/*
 * 'mxml_new()' - Create a new node.
 */

static mxml_node_t *			/* O - New node */
mxml_new(mxml_node_t *parent,		/* I - Parent node */
         mxml_type_t type)		/* I - Node type */
{
  mxml_node_t	*node;			/* New node */


 /*
  * Allocate memory for the node...
  */

  if ((node = calloc(1, sizeof(mxml_node_t))) == NULL)
    return (NULL);

 /*
  * Set the node type...
  */

  node->type = type;

 /*
  * Add to the parent if present...
  */

  if (parent)
  {
    node->parent = parent;
    node->prev   = parent->last_child;

    if (parent->last_child)
      parent->last_child->next = node;
    else
      parent->child = node;

    parent->last_child = node;
  }

 /*
  * Return the new node...
  */

  return (node);
}


/*
 * End of "$Id: mxml-node.c,v 1.1 2003/06/03 19:46:30 mike Exp $".
 */
