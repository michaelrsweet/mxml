/*
 * "$Id: testmxml.c,v 1.1 2003/06/03 19:46:30 mike Exp $"
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
 *   main()    - Main entry for test program.
 *   type_cb() - XML data type callback for mxmlLoadFile()...
 */

/*
 * Include necessary headers...
 */

#include "mxml.h"


/*
 * Local functions...
 */

mxml_type_t	type_cb(mxml_node_t *node);


/*
 * 'main()' - Main entry for test program.
 */

int					/* O - Exit status */
main(int  argc,				/* I - Number of command-line args */
     char *argv[])			/* I - Command-line args */
{
  FILE		*fp;			/* File to read */
  mxml_node_t	*tree;			/* XML tree */


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

 /*
  * Print the XML tree...
  */

  setbuf(stdout, NULL);

  mxmlSaveFile(tree, stdout);
  puts("");

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
  else if (!strcmp(type, "opaque"))
    return (MXML_OPAQUE);
  else if (!strcmp(type, "real"))
    return (MXML_REAL);
  else
    return (MXML_TEXT);
}


/*
 * End of "$Id: testmxml.c,v 1.1 2003/06/03 19:46:30 mike Exp $".
 */
