/*
 * "$Id: mxmldoc.c,v 1.3 2003/06/04 17:37:23 mike Exp $"
 *
 * Documentation generator using mini-XML, a small XML-like file parsing
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
 * This program scans source and header files and produces public API
 * documentation for code that conforms to the CUPS Configuration
 * Management Plan (CMP) coding standards.  Please see the following web
 * page for details:
 *
 *     http://www.cups.org/cmp.html
 *
 * Using Mini-XML, this program creates and maintains an XML representation
 * of the public API code documentation which can then be converted to HTML
 * as desired.  The following is a poor-man's schema:
 *
 * <namespace name="">                        [name is usually "std"...]
 *   <constant name="">
 *     <description>descriptive text</description>
 *   </constant>
 *
 *   <enumeration name="">
 *     <constant name="">...</constant>
 *   </enumeration>
 *
 *   <typedef name="">
 *     <description>descriptive text</description>
 *     <type>type string</type>
 *   </typedef>
 *
 *   <function name="">
 *     <description>descriptive text</description>
 *     <iargument name="">
 *       <description>descriptive text</description>
 *       <type>type string</type>
 *     </iargument>
 *     <oargument name="">
 *       <description>descriptive text</description>
 *       <type>type string</type>
 *     </oargument>
 *     <ioargument name="">
 *       <description>descriptive text</description>
 *       <type>type string</type>
 *     </ioargument>
 *     <returnvalue>
 *       <description>descriptive text</description>
 *       <type>type string</type>
 *     </returnvalue>
 *     <seealso>function names separated by spaces</seealso>
 *   </function>
 *
 *   <variable name="">
 *     <description>descriptive text</description>
 *     <type>type string</type>
 *   </variable>
 *
 *   <struct name="">
 *     <description>descriptive text</description>
 *     <variable name="">...</variable>
 *     <function name="">...</function>
 *   </struct>
 *
 *   <class name="" parent="">
 *     <description>descriptive text</description>
 *     <class name="">...</class>
 *     <enumeration name="">...</enumeration>
 *     <function name="">...</function>
 *     <struct name="">...</struct>
 *     <variable name="">...</variable>
 *   </class>
 * </namespace>
 */
 

/*
 * Local functions...
 */

static int		scan_file(const char *filename, FILE *fp,
			          mxml_node_t *doc);
static void		sort_node(mxml_node_t *tree, mxml_node_t *func);


/*
 * 'main()' - Main entry for test program.
 */

int					/* O - Exit status */
main(int  argc,				/* I - Number of command-line args */
     char *argv[])			/* I - Command-line args */
{
  int		i;			/* Looping var */
  FILE		*fp;			/* File to read */
  mxml_node_t	*doc;			/* XML documentation tree */


 /*
  * Check arguments...
  */

  if (argc < 2)
  {
    fputs("Usage: mxmldoc filename.xml [source files] >filename.html\n", stderr);
    return (1);
  }

 /*
  * Read the XML documentation file, if it exists...
  */

  if ((fp = fopen(argv[1], "r")) != NULL)
  {
   /*
    * Read the existing XML file...
    */

    doc = mxmlLoadFile(NULL, fp, MXML_NO_CALLBACK);

    fclose(fp);

    if (!doc)
    {
      fprintf(stderr, "Unable to read the XML documentation file \"%s\"!\n",
              argv[1]);
      return (1);
    }
  }
  else
  {
   /*
    * Create an empty XML documentation file...
    */

    doc = mxmlNewElement(NULL, "namespace");

    mxmlElementSetAttr(doc, "name", "std");
  }

 /*
  * Loop through all of the source files...
  */

  for (i = 2; i < argc; i ++)
    if ((fp = fopen(argv[i], "r")) == NULL)
    {
      fprintf(stderr, "Unable to open source file \"%s\": %s\n", argv[i],
              strerror(errno));
      mxmlDelete(doc);
      return (1);
    }
    else if (scan_file(argv[i], fp, doc))
    {
      fclose(fp);
      mxmlDelete(doc);
      return (1);
    }
    else
      fclose(fp);

 /*
  * Save the updated XML documentation file...
  */

  if ((fp = fopen(argv[1], "w")) != NULL)
  {
   /*
    * Write over the existing XML file...
    */

    if (mxmlSaveFile(doc, fp, MXML_NO_CALLBACK))
    {
      fprintf(stderr, "Unable to write the XML documentation file \"%s\": %s!\n",
              argv[1], strerror(errno));
      fclose(fp);
      mxmlDelete(doc);
      return (1);
    }

    fclose(fp);
  }
  else
  {
    fprintf(stderr, "Unable to create the XML documentation file \"%s\": %s!\n",
            argv[1], strerror(errno));
    mxmlDelete(doc);
    return (1);
  }

 /*
  * Delete the tree and return...
  */

  mxmlDelete(doc);

  return (0);
}


/*
 * 'scan_file()' - Scan a source file.
 */

static int				/* O - 0 on success, -1 on error */
scan_file(const char  *filename,	/* I - Filename */
          FILE        *fp,		/* I - File to scan */
          mxml_node_t *tree)		/* I - Function tree */
{
}


/*
 * 'sort_node()' - Insert a node sorted into a tree.
 */

static void
sort_node(mxml_node_t *tree,		/* I - Tree to sort into */
          mxml_node_t *node)		/* I - Node to add */
{
  mxml_node_t	*temp;			/* Current node */
  const char	*tempname,		/* Name of current node */
		*nodename;		/* Name of node */


 /*
  * Get the node name...
  */

  nodename = mxmlElementGetAttr(node, "name");

 /*
  * Delete any existing definition at this level, if one exists...
  */

  if ((temp = mxmlFindElement(tree, tree, node->value.element.name,
                              "name", nodename, MXML_DESCEND_FIRST)) != NULL)
    mxmlDelete(temp);

 /*
  * Add the node into the tree at the proper place...
  */

  for (temp = tree->child; temp; temp = temp->next)
  {
    if ((tempname = mxmlElementGetAttr(temp, "name")) == NULL)
      continue;

    if (strcmp(nodename, tempname) > 0)
      break;
  }

  mxmlAdd(tree, MXML_ADD_AFTER, temp, node);
}


/*
 * End of "$Id: mxmldoc.c,v 1.3 2003/06/04 17:37:23 mike Exp $".
 */
