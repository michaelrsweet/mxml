/*
 * "$Id: mxml-search.c,v 1.1 2003/06/03 19:46:30 mike Exp $"
 *
 * Search/navigation functions for mini-XML, a small XML-like file
 * parsing library.
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
 *   mxmlFindElement() - Find the named element.
 *   mxmlWalkNext()    - Walk to the next logical node in the tree.
 *   mxmlWalkPrev()    - Walk to the previous logical node in the tree.
 */

/*
 * Include necessary headers...
 */

#include "mxml.h"


/*
 * 'mxmlFindElement()' - Find the named element.
 */

mxml_node_t *				/* O - Element node or NULL */
mxmlFindElement(mxml_node_t *node,	/* I - Current node */
                mxml_node_t *top,	/* I - Top node */
                const char  *name)	/* I - Element name */
{
 /*
  * Start with the next node...
  */

  node = mxmlWalkNext(node, top);

 /*
  * Loop until we find a matching element...
  */

  while (node != NULL)
  {
   /*
    * See if this node matches...
    */

    if (node->type == MXML_ELEMENT &&
        node->value.element.name &&
	!strcmp(node->value.element.name, name))
      return (node);

   /*
    * Nope, move on to the next...
    */

    node = mxmlWalkNext(node, top);
  }

  return (NULL);
}


/*
 * 'mxmlWalkNext()' - Walk to the next logical node in the tree.
 */

mxml_node_t *				/* O - Next node or NULL */
mxmlWalkNext(mxml_node_t *node,		/* I - Current node */
             mxml_node_t *top)		/* I - Top node */
{
  if (!node)
    return (NULL);
  else if (node->child)
    return (node->child);
  else if (node->next)
    return (node->next);
  else if (node->parent != top)
    return (mxmlWalkNext(node->parent, top));
  else
    return (NULL);
}


/*
 * 'mxmlWalkPrev()' - Walk to the previous logical node in the tree.
 */

mxml_node_t *				/* O - Previous node or NULL */
mxmlWalkPrev(mxml_node_t *node,		/* I - Current node */
             mxml_node_t *top)		/* I - Top node */
{
  if (!node)
    return (NULL);
  else if (node->prev)
    return (node->prev);
  else if (node->parent != top)
    return (node->parent);
  else
    return (NULL);
}


/*
 * End of "$Id: mxml-search.c,v 1.1 2003/06/03 19:46:30 mike Exp $".
 */
