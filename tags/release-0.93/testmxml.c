/*
 * "$Id: testmxml.c,v 1.7 2003/06/04 21:19:00 mike Exp $"
 *
 * Test program for mini-XML, a small XML-like file parsing library.
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
 *   main()          - Main entry for test program.
 *   type_cb()       - XML data type callback for mxmlLoadFile()...
 *   whitespace_cb() - Let the mxmlSaveFile() function know when to insert
 *                     newlines and tabs...
 */

/*
 * Include necessary headers...
 */

#include "mxml.h"


/*
 * Local functions...
 */

mxml_type_t	type_cb(mxml_node_t *node);
int		whitespace_cb(mxml_node_t *node, int where);


/*
 * 'main()' - Main entry for test program.
 */

int					/* O - Exit status */
main(int  argc,				/* I - Number of command-line args */
     char *argv[])			/* I - Command-line args */
{
  FILE		*fp;			/* File to read */
  mxml_node_t	*tree,			/* XML tree */
		*node;			/* Node which should be in test.xml */


 /*
  * Check arguments...
  */

  if (argc != 2)
  {
    fputs("Usage: testmxml filename.xml\n", stderr);
    return (1);
  }

 /*
  * Open the file...
  */

  if ((fp = fopen(argv[1], "r")) == NULL)
  {
    perror(argv[1]);
    return (1);
  }

 /*
  * Read the file...
  */

  tree = mxmlLoadFile(NULL, fp, type_cb);

  fclose(fp);

  if (!tree)
  {
    fputs("Unable to read XML file!\n", stderr);
    return (1);
  }

  if (!strcmp(argv[1], "test.xml"))
  {
   /*
    * Verify that mxmlFindElement() and indirectly mxmlWalkNext() work
    * properly...
    */

    if ((node = mxmlFindElement(tree, tree, "choice", NULL, NULL,
                                MXML_DESCEND)) == NULL)
    {
      fputs("Unable to find first <choice> element in XML tree!\n", stderr);
      mxmlDelete(tree);
      return (1);
    }

    if ((node = mxmlFindElement(node, tree, "choice", NULL, NULL,
                                MXML_NO_DESCEND)) == NULL)
    {
      fputs("Unable to find second <choice> element in XML tree!\n", stderr);
      mxmlDelete(tree);
      return (1);
    }
  }

 /*
  * Print the XML tree...
  */

  mxmlSaveFile(tree, stdout, whitespace_cb);

 /*
  * Delete the tree and return...
  */

  mxmlDelete(tree);

  return (0);
}


/*
 * 'type_cb()' - XML data type callback for mxmlLoadFile()...
 */

mxml_type_t				/* O - Data type */
type_cb(mxml_node_t *node)		/* I - Element node */
{
  const char	*type;			/* Type string */


 /*
  * You can lookup attributes and/or use the element name, hierarchy, etc...
  */

  if ((type = mxmlElementGetAttr(node, "type")) == NULL)
    type = node->value.element.name;

  if (!strcmp(type, "integer"))
    return (MXML_INTEGER);
  else if (!strcmp(type, "opaque") || !strcmp(type, "pre"))
    return (MXML_OPAQUE);
  else if (!strcmp(type, "real"))
    return (MXML_REAL);
  else
    return (MXML_TEXT);
}


/*
 * 'whitespace_cb()' - Let the mxmlSaveFile() function know when to insert
 *                     newlines and tabs...
 */

int					/* O - Whitespace char or 0 */
whitespace_cb(mxml_node_t *node,	/* I - Element node */
              int         where)	/* I - Open or close tag? */
{
  const char *name;			/* Name of element */

 /*
  * We can conditionally break to a new line before or after any element.
  * These are just common HTML elements...
  */

  name = node->value.element.name;

  if (!strcmp(name, "html") || !strcmp(name, "head") || !strcmp(name, "body") ||
      !strcmp(name, "pre") || !strcmp(name, "p") ||
      !strcmp(name, "h1") || !strcmp(name, "h2") || !strcmp(name, "h3") ||
      !strcmp(name, "h4") || !strcmp(name, "h5") || !strcmp(name, "h6"))
  {
   /*
    * Newlines before open and after close...
    */

    if (where == MXML_WS_BEFORE_OPEN || where == MXML_WS_AFTER_CLOSE)
      return ('\n');
  }
  else if (!strcmp(name, "dl") || !strcmp(name, "ol") || !strcmp(name, "ul"))
  {
   /*
    * Put a newline before and after list elements...
    */

    return ('\n');
  }
  else if (!strcmp(name, "dd") || !strcmp(name, "dt") || !strcmp(name, "li"))
  {
   /*
    * Put a tab before <li>'s, <dd>'s, and <dt>'s, and a newline after them...
    */

    if (where == MXML_WS_BEFORE_OPEN)
      return ('\t');
    else if (where == MXML_WS_AFTER_CLOSE)
      return ('\n');
  }

 /*
  * Return 0 for no added whitespace...
  */

  return (0);
}


/*
 * End of "$Id: testmxml.c,v 1.7 2003/06/04 21:19:00 mike Exp $".
 */
