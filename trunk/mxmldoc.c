/*
 * "$Id: mxmldoc.c,v 1.1 2003/06/04 03:07:47 mike Exp $"
 *
 * Documentation generator for mini-XML, a small XML-like file parsing
 * library.
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
 *   main()    - Main entry for documentation generator.
 */

/*
 * Include necessary headers...
 */

#include "mxml.h"


/*
 * Local functions...
 */

static void	insert_func(mxml_node_t *tree, mxml_node_t *func);
static int	scan_file(const char *filename, mxml_node_t *tree);


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

    if ((node = mxmlFindElement(tree, tree, "choice")) == NULL)
    {
      fputs("Unable to find first <choice> element in XML tree!\n", stderr);
      mxmlDelete(tree);
      return (1);
    }

    if ((node = mxmlFindElement(node, tree, "choice")) == NULL)
    {
      fputs("Unable to find second <choice> element in XML tree!\n", stderr);
      mxmlDelete(tree);
      return (1);
    }
  }

 /*
  * Print the XML tree...
  */

  mxmlSaveFile(tree, stdout);
  puts("");

 /*
  * Delete the tree and return...
  */

  mxmlDelete(tree);

  return (0);
}


/*
 * 'insert_func()' - Insert a function into a tree.
 */

static void
insert_func(mxml_node_t *tree,		/* I - Tree to insert into */
            mxml_node_t *func)		/* I - Function to add */
{
  mxml_node_t	*node;			/* Current node */
  const char	*funcname,		/* Name of function */
		*nodename;		/* Name of current node */
  int		diff;			/* Different between names */


  funcname = mxmlElementGetAttr(func, "name");

  for (node = tree->child; node; node = node->next)
  {
    if (node->type != MXML_ELEMENT ||
        strcmp(node->value.element.name, "function"))
      continue;

    if ((nodename = mxmlElementGetAttr(node, "name")) == NULL)
      continue;

    if ((diff = strcmp(funcname, nodename)) == 0)
    {
      mxmlDelete(node);
      insert_func(tree, func);
      return;
    }

    if (diff > 0)
      break;
  }

  if (node)
  {
   /*
    * Insert function before this node...
    */

    func->next = node;
    func->prev = node->prev;

    if (node->prev)
      node->prev->next = func;
    else
      tree->child = func;

    node->prev = func;
  }
  else
  {
   /*
    * Append function to the end...
    */

    func->prev = tree->last_child;

    if (tree->last_child)
      tree->last_child->next = func;
    else
      tree->last_child = func;

    if (!tree->child)
      tree->child = func;
  }
}


/*
 * 'scan_file()' - Scan a source file.
 */

static int				/* O - 0 on success, -1 on error */
scan_file(const char  *filename,	/* I - File to scan */
          mxml_node_t *tree)		/* I - Function tree */
{
}


/*
 * End of "$Id: mxmldoc.c,v 1.1 2003/06/04 03:07:47 mike Exp $".
 */
