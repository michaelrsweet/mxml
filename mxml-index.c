/*
 * "$Id: mxml-index.c,v 1.1 2004/05/16 05:25:38 mike Exp $"
 *
 * Index support code for Mini-XML, a small XML-like file parsing library.
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
 *
 * Contents:
 *
 *   mxmlIndexDelete()   - Delete an index.
 *   mxmlIndexEnum()     - Return the next node in the index.
 *   mxmlIndexFind()     - Find the next matching node.
 *   mxmlIndexNew()      - Create a new index.
 *   mxmlIndexReset()    - Return the first node in the index.
 *   sort_attr()         - Sort by attribute value...
 *   sort_attr()         - Sort by element name...
 *   sort_element_attr() - Sort by element name and attribute value...
 */

/*
 * Include necessary headers...
 */

#include "config.h"
#include "mxml.h"


/*
 * Sort functions...
 */

static int	sort_attr(const void *a, const void *b);
static int	sort_element(const void *a, const void *b);
static int	sort_element_attr(const void *a, const void *b);


/*
 * Sort attribute...
 */

static const char *sort_attr_name;


/*
 * 'mxmlIndexDelete()' - Delete an index.
 */

void
mxmlIndexDelete(mxml_index_t *ind)	/* I - Index to delete */
{
 /*
  * Range check input..
  */

  if (!ind)
    return;

 /*
  * Free memory...
  */

  if (ind->attr)
    free(ind->attr);

  if (ind->alloc_nodes)
    free(ind->nodes);

  free(ind);
}


/*
 * 'mxmlIndexEnum()' - Return the next node in the index.
 */

mxml_node_t *				/* O - Next node or NULL if there is none */
mxmlIndexEnum(mxml_index_t *ind)	/* I - Index to enumerate */
{
 /*
  * Range check input...
  */

  if (!ind)
    return (NULL);

 /*
  * Return the next node...
  */

  if (ind->cur_node < ind->num_nodes)
    return (ind->nodes[ind->cur_node ++]);
  else
    return (NULL);
}


/*
 * 'mxmlIndexFind()' - Find the next matching node.
 */

mxml_node_t *				/* O - Node or NULL if none found */
mxmlIndexFind(mxml_index_t *ind,	/* I - Index to search */
              const char   *element,	/* I - Element name to find, if any */
	      const char   *value)	/* I - Attribute value, if any */
{
  int	i, j;				/* Looping vars */


 /*
  * Range check input...
  */

  if (!ind || (!ind->attr && value))
    return (NULL);

 /*
  * If both element and value are NULL, just enumerate the nodes in the
  * index...
  */

  if (!element && !value)
    return (mxmlIndexEnum(ind));

 /*
  * Find the node...
  */

  for (i = ind->cur_node; i < ind->num_nodes; i ++)
  {
   /*
    * Check the element name...
    */

    if (element)
    {
      if ((j = strcmp(element, ind->nodes[i]->value.element.name)) > 0)
      {
       /*
        * No more elements <= name...
	*/

        ind->cur_node = ind->num_nodes;

        return (NULL);
      }
      else if (j < 0)
        continue;
    }

   /*
    * Check the attribute value...
    */

    if (value)
    {
      if ((j = strcmp(value, mxmlElementGetAttr(ind->nodes[i], ind->attr))) > 0)
      {
       /*
        * No more attributes <= value...
	*/

        ind->cur_node = ind->num_nodes;

        return (NULL);
      }
      else if (j != 0)
        continue;
    }

   /*
    * Got a match, return it...
    */

    ind->cur_node = i + 1;

    return (ind->nodes[i]);
  }

 /*
  * If we get this far, then we have no matches...
  */

  ind->cur_node = ind->num_nodes;

  return (NULL);
}


/*
 * 'mxmlIndexNew()' - Create a new index.
 */

mxml_index_t *				/* O - New index */
mxmlIndexNew(mxml_node_t *node,		/* I - XML node tree */
             const char  *element,	/* I - Element to index or NULL for all */
             const char  *attr)		/* I - Attribute to index or NULL for none */
{
  mxml_index_t	*ind;			/* New index */
  mxml_node_t	*current,		/* Current node in index */
  		**temp;			/* Temporary node pointer array */


 /*
  * Range check input...
  */

  if (!node)
    return (NULL);

 /*
  * Create a new index...
  */

  if ((ind = calloc(1, sizeof(mxml_index_t))) != NULL)
    return (NULL);

  for (current = mxmlFindElement(node, node, element, attr, NULL, MXML_DESCEND);
       current;
       current = mxmlFindElement(current, node, element, attr, NULL, MXML_DESCEND))
  {
    if (ind->num_nodes >= ind->alloc_nodes)
    {
      if (!ind->alloc_nodes)
        temp = malloc(64 * sizeof(mxml_node_t *));
      else
        temp = realloc(ind->nodes, (ind->alloc_nodes + 64) * sizeof(mxml_node_t *));

      if (!temp)
      {
       /*
        * Unable to allocate memory for the index, so abort...
	*/

        mxml_error("Unable to allocate %d bytes for index: %s",
	           (ind->alloc_nodes + 64) * sizeof(mxml_node_t *),
		   strerror(errno));

        mxmlIndexDelete(ind);
	return (NULL);
      }

      ind->nodes       = temp;
      ind->alloc_nodes += 64;
    }

    ind->nodes[ind->num_nodes ++] = current;
  }

 /*
  * Sort nodes based upon the search criteria...
  */

  if (ind->num_nodes > 0)
  {
    if (!element && attr)
    {
     /*
      * Sort based upon the element name and attribute value...
      */

      sort_attr_name = attr;
      ind->attr      = strdup(attr);

      qsort(ind->nodes, ind->num_nodes, sizeof(mxml_node_t *),
            sort_element_attr);
    }
    else if (!element && !attr)
    {
     /*
      * Sort based upon the element name...
      */

      qsort(ind->nodes, ind->num_nodes, sizeof(mxml_node_t *),
            sort_element);
    }
    else if (attr)
    {
     /*
      * Sort based upon the attribute value...
      */

      sort_attr_name = attr;
      ind->attr      = strdup(attr);

      qsort(ind->nodes, ind->num_nodes, sizeof(mxml_node_t *),
            sort_attr);
    }
  }

 /*
  * Return the new index...
  */

  return (ind);
}


/*
 * 'mxmlIndexReset()' - Return the first node in the index.
 */

mxml_node_t *				/* O - First node or NULL if there is none */
mxmlIndexReset(mxml_index_t *ind)	/* I - Index to reset */
{
 /*
  * Range check input...
  */

  if (!ind)
    return (NULL);

 /*
  * Set the index to the first element...
  */

  ind->cur_node = 0;

 /*
  * Return the first node...
  */

  if (ind->num_nodes)
    return (ind->nodes);
  else
    return (NULL);
}


/*
 * 'sort_attr()' - Sort by attribute value...
 */

static int				/* O - Result of comparison */
sort_attr(const void *a,		/* I - First node */
          const void *b)		/* I - Second node */
{
  return (strcmp(mxmlElementGetAttr((mxml_node_t *)a, sort_attr_name),
                 mxmlElementGetAttr((mxml_node_t *)b, sort_attr_name)));
}


/*
 * 'sort_element()' - Sort by element name...
 */

static int				/* O - Result of comparison */
sort_element(const void *a,		/* I - First node */
             const void *b)		/* I - Second node */
{
  return (strcmp(((mxml_node_t *)a)->value.element.name,
                 ((mxml_node_t *)b)->value.element.name));
}


/*
 * 'sort_element_attr()' - Sort by element name and attribute value...
 */

static int				/* O - Result of comparison */
sort_element_attr(const void *a,	/* I - First node */
                  const void *b)	/* I - Second node */
{
  int	i;				/* Result of comparison */


  if ((i = strcmp(((mxml_node_t *)a)->value.element.name,
                  ((mxml_node_t *)b)->value.element.name)) != 0)
    return (i);
  else
    return (strcmp(mxmlElementGetAttr((mxml_node_t *)a, sort_attr_name),
                   mxmlElementGetAttr((mxml_node_t *)b, sort_attr_name)));
}


/*
 * End of "$Id: mxml-index.c,v 1.1 2004/05/16 05:25:38 mike Exp $".
 */
