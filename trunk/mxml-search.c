/*
 * "$Id: mxml-search.c,v 1.2 2003/06/03 20:40:01 mike Exp $"
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
 *   mxml_walk_next()  - Walk to the next logical node in the tree.
 */

/*
 * Include necessary headers...
 */

#include "mxml.h"


/*
 * Local functions...
 */

mxml_node_t	*mxml_walk_next(mxml_node_t *node, mxml_node_t *top,
		                int descend);


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
  return (mxml_walk_next(node, top, 1));
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
 * 'mxml_walk_next()' - Walk to the next logical node in the tree.
 */

mxml_node_t *				/* O - Next node or NULL */
mxml_walk_next(mxml_node_t *node,	/* I - Current node */
               mxml_node_t *top,	/* I - Top node */
               int         descend)	/* I - 1 = descend, 0 = don't */
{
  if (!node)
    return (NULL);
  else if (node->child && descend)
    return (node->child);
  else if (node->next)
    return (node->next);
  else if (node->parent != top)
    return (mxml_walk_next(node->parent, top, 0));
  else
    return (NULL);
}


/*
 * End of "$Id: mxml-search.c,v 1.2 2003/06/03 20:40:01 mike Exp $".
 */
