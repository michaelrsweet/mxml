/*
 * "$Id: mxmldoc.c,v 1.7 2003/06/05 12:11:53 mike Exp $"
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
 * <?xml version="1.0"?>
 * <namespace name="">                        [optional...]
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
 *     <argument name="" direction="I|O|IO">
 *       <description>descriptive text</description>
 *       <type>type string</type>
 *     </argument>
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
static void		update_comment(mxml_node_t *parent,
			               mxml_node_t *comment);
static void		write_documentation(mxml_node_t *doc);
static int		ws_cb(mxml_node_t *node, int where);


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

    doc = mxmlNewElement(NULL, "?xml");
    mxmlElementSetAttr(doc, "version", "1.0");
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

  if (argc > 2)
  {
   /*
    * Save the updated XML documentation file...
    */

    if ((fp = fopen(argv[1], "w")) != NULL)
    {
     /*
      * Write over the existing XML file...
      */

      if (mxmlSaveFile(doc, fp, ws_cb))
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
  }

 /*
  * Write HTML documentation...
  */

  write_documentation(doc);

 /*
  * Delete the tree and return...
  */

  mxmlDelete(doc);

  return (0);
}


/*
 * Basic states for file parser...
 */

#define STATE_NONE		0	/* No state - whitespace, etc. */
#define STATE_PREPROCESSOR	1	/* Preprocessor directive */
#define STATE_C_COMMENT		2	/* Inside a C comment */
#define STATE_CXX_COMMENT	3	/* Inside a C++ comment */
#define STATE_STRING		4	/* Inside a string constant */
#define STATE_CHARACTER		5	/* Inside a character constant */
#define STATE_IDENTIFIER	6	/* Inside a keyword/identifier */


/*
 * 'scan_file()' - Scan a source file.
 */

static int				/* O - 0 on success, -1 on error */
scan_file(const char  *filename,	/* I - Filename */
          FILE        *fp,		/* I - File to scan */
          mxml_node_t *tree)		/* I - Function tree */
{
  int		state,			/* Current parser state */
		braces,			/* Number of braces active */
		parens;			/* Number of active parenthesis */
  int		ch;
  char		buffer[16384],
		*bufptr;
  mxml_node_t	*comment,		/* <comment> node */
		*function,		/* <function> node */
		*parent,		/* <struct> or <class> node */
		*variable,		/* <variable> or <argument> node */
		*returnvalue,		/* <returnvalue> node */
		*type,			/* <type> node */
		*description;		/* <description> node */
#ifdef DEBUG
  int		oldstate,		/* Previous state */
		oldch;			/* Old character */
  static const char *states[] =		/* State strings */
		{
		  "STATE_NONE",
		  "STATE_PREPROCESSOR",
		  "STATE_C_COMMENT",
		  "STATE_CXX_COMMENT",
		  "STATE_STRING",
		  "STATE_CHARACTER",
		  "STATE_IDENTIFIER"
		};
#endif /* DEBUG */


 /*
  * Initialize the finite state machine...
  */

  state       = STATE_NONE;
  braces      = 0;
  parens      = 0;
  bufptr      = buffer;

  comment     = mxmlNewElement(MXML_NO_PARENT, "temp");
  parent      = tree;
  function    = NULL;
  variable    = NULL;
  returnvalue = NULL;
  type        = NULL;
  description = NULL;

 /*
  * Read until end-of-file...
  */

  while ((ch = getc(fp)) != EOF)
  {
#ifdef DEBUG
    oldstate = state;
    oldch    = ch;
#endif /* DEBUG */

    switch (state)
    {
      case STATE_NONE :			/* No state - whitespace, etc. */
          switch (ch)
	  {
	    case '/' :			/* Possible C/C++ comment */
	        ch     = getc(fp);
		bufptr = buffer;

		if (ch == '*')
		  state = STATE_C_COMMENT;
		else if (ch == '/')
		  state = STATE_CXX_COMMENT;
		else
		  ungetc(ch, fp);
		break;

	    case '#' :			/* Preprocessor */
	        state = STATE_PREPROCESSOR;
		break;

            case '\'' :			/* Character constant */
	        state = STATE_CHARACTER;
		break;

            case '\"' :			/* String constant */
	        state = STATE_STRING;
		break;

            case '{' :
	        if (function)
		  sort_node(parent, function);

	        braces ++;
		function = NULL;
		variable = NULL;
		break;

            case '}' :
	        if (braces > 0)
		  braces --;
		break;

            case '(' :
	        parens ++;
		break;

            case ')' :
	        if (parens > 0)
		  parens --;
		break;

	    case ';' :
	        if (function)
		  mxmlDelete(function);

		function = NULL;
		variable = NULL;
		break;

	    case '*' :
		if (type)
		{
#ifdef DEBUG
                  fputs("Identifier: <<< * >>>\n", stderr);
#endif /* DEBUG */
		  mxmlNewText(type, 1, "*");
		}
		break;

            default :			/* Other */
	        if (isalpha(ch) || ch == '_')
		{
		  state     = STATE_IDENTIFIER;
		  bufptr    = buffer;
		  *bufptr++ = ch;
		}
		break;
          }
          break;

      case STATE_PREPROCESSOR :		/* Preprocessor directive */
          if (ch == '\n')
	    state = STATE_NONE;
	  else if (ch == '\\')
	    getc(fp);
          break;

      case STATE_C_COMMENT :		/* Inside a C comment */
          switch (ch)
	  {
	    case '\n' :
	        while ((ch = getc(fp)) != EOF)
		  if (ch == '*')
		  {
		    ch = getc(fp);

		    if (ch == '/')
		    {
		      *bufptr = '\0';

        	      if (comment->child != comment->last_child)
			mxmlDelete(comment->child);

		      if (variable)
		      {
        		description = mxmlNewElement(variable, "description");
			update_comment(variable,
			               mxmlNewText(description, 0, buffer));
		      }
		      else
        		mxmlNewText(comment, 0, buffer);

#ifdef DEBUG
		      fprintf(stderr, "C comment: <<< %s >>>\n", buffer);
#endif /* DEBUG */

		      state = STATE_NONE;
		      break;
		    }
		    else
		      ungetc(ch, fp);
		  }
		  else if (ch == '\n' && bufptr > buffer &&
		           bufptr < (buffer + sizeof(buffer) - 1))
		    *bufptr++ = ch;
		  else if (!isspace(ch))
		    break;

		if (ch != EOF)
		  ungetc(ch, fp);

                if (bufptr > buffer && bufptr < (buffer + sizeof(buffer) - 1))
		  *bufptr++ = '\n';
		break;

	    case '/' :
	        if (ch == '/' && bufptr > buffer && bufptr[-1] == '*')
		{
		  while (bufptr > buffer &&
		         (bufptr[-1] == '*' || isspace(bufptr[-1])))
		    bufptr --;
		  *bufptr = '\0';

        	  if (comment->child != comment->last_child)
		    mxmlDelete(comment->child);

		  if (variable)
		  {
        	    description = mxmlNewElement(variable, "description");
		    update_comment(variable,
			           mxmlNewText(description, 0, buffer));
		  }
		  else
        	    mxmlNewText(comment, 0, buffer);

#ifdef DEBUG
		  fprintf(stderr, "C comment: <<< %s >>>\n", buffer);
#endif /* DEBUG */

		  state = STATE_NONE;
		  break;
		}

	    default :
	        if (ch == ' ' && bufptr == buffer)
		  break;

	        if (bufptr < (buffer + sizeof(buffer) - 1))
		  *bufptr++ = ch;
		break;
          }
          break;

      case STATE_CXX_COMMENT :		/* Inside a C++ comment */
          if (ch == '\n')
	  {
	    *bufptr = '\0';

            if (comment->child != comment->last_child)
	      mxmlDelete(comment->child);

	    if (variable)
	    {
              description = mxmlNewElement(variable, "description");
	      update_comment(variable,
			     mxmlNewText(description, 0, buffer));
	    }
	    else
              mxmlNewText(comment, 0, buffer);

#ifdef DEBUG
	    fprintf(stderr, "C++ comment: <<< %s >>>\n", buffer);*/
#endif /* DEBUG */
	  }
	  else if (ch == ' ' && bufptr == buffer)
	    break;
	  else if (bufptr < (buffer + sizeof(buffer) - 1))
	    *bufptr++ = ch;
          break;

      case STATE_STRING :		/* Inside a string constant */
          if (ch == '\\')
	    getc(fp);
	  else if (ch == '\"')
	    state = STATE_NONE;
          break;

      case STATE_CHARACTER :		/* Inside a character constant */
          if (ch == '\\')
	    getc(fp);
	  else if (ch == '\'')
	    state = STATE_NONE;
          break;

      case STATE_IDENTIFIER :		/* Inside a keyword or identifier */
	  if (isalnum(ch) || ch == '_' || ch == '[' || ch == ']')
	  {
	    if (bufptr < (buffer + sizeof(buffer) - 1))
	      *bufptr++ = ch;
	  }
	  else
	  {
	    ungetc(ch, fp);
	    *bufptr = '\0';
	    state   = STATE_NONE;

            if (!braces)
	    {
	      if (!type)
                type = mxmlNewElement(MXML_NO_PARENT, "type");

              if (!function && ch == '(')
	      {
	        if (type->child &&
		    !strcmp(type->child->value.text.string, "extern"))
		{
		  mxmlDelete(type);
		  type = NULL;
		  break;
		}

	        function = mxmlNewElement(MXML_NO_PARENT, "function");
		mxmlElementSetAttr(function, "name", buffer);

                if (!type->last_child ||
		    strcmp(type->last_child->value.text.string, "void"))
		{
                  returnvalue = mxmlNewElement(function, "returnvalue");

		  description = mxmlNewElement(returnvalue, "description");
		  update_comment(returnvalue, comment->last_child);
		  mxmlAdd(description, MXML_ADD_AFTER, MXML_ADD_TO_PARENT,
		          comment->last_child);

		  mxmlAdd(returnvalue, MXML_ADD_AFTER, MXML_ADD_TO_PARENT, type);
                }
		else
		  mxmlDelete(type);

		description = mxmlNewElement(function, "description");
		update_comment(function, comment->last_child);
		mxmlAdd(description, MXML_ADD_AFTER, MXML_ADD_TO_PARENT,
		        comment->last_child);

		type = NULL;
	      }
	      else if (function && (ch == ')' || ch == ','))
	      {
	       /*
	        * Argument definition...
		*/

	        variable = mxmlNewElement(function, "argument");
		mxmlElementSetAttr(variable, "name", buffer);

		mxmlAdd(variable, MXML_ADD_AFTER, MXML_ADD_TO_PARENT, type);
		type = NULL;
	      }
              else if (!function && (ch == ';' || ch == ','))
	      {
	       /*
	        * Variable definition...
		*/

	        variable = mxmlNewElement(MXML_NO_PARENT, "variable");
		mxmlElementSetAttr(variable, "name", buffer);

		sort_node(parent, variable);

		mxmlAdd(variable, MXML_ADD_AFTER, MXML_ADD_TO_PARENT, type);
		type = NULL;
              }
	      else if (ch == '{' && type->child &&
	               (!strcmp(type->child->value.text.string, "class") ||
			!strcmp(type->child->value.text.string, "enum") ||
			!strcmp(type->child->value.text.string, "struct") ||
			!strcmp(type->child->value.text.string, "typedef")))
              {
		/* Handle structure/class/enum/typedef... */
		mxmlDelete(type);
		type = NULL;
	      }
	      else
	        mxmlNewText(type, type->child != NULL, buffer);
	    }
	    else if (type)
	    {
	      mxmlDelete(type);
	      type = NULL;
	    }
	  }
          break;
    }

#ifdef DEBUG
    if (state != oldstate)
      fprintf(stderr, "changed states from %s to %s on receipt of character '%c'...\n",
              states[oldstate], states[state], oldch);
#endif /* DEBUG */
  }

  mxmlDelete(comment);

 /*
  * All done, return with no errors...
  */

  return (0);
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

    if (strcmp(nodename, tempname) < 0)
      break;
  }

  if (temp)
    mxmlAdd(tree, MXML_ADD_BEFORE, temp, node);
  else
    mxmlAdd(tree, MXML_ADD_AFTER, MXML_ADD_TO_PARENT, node);
}


/*
 * 'update_comment()' - Update a comment node.
 */

static void
update_comment(mxml_node_t *parent,	/* I - Parent node */
               mxml_node_t *comment)	/* I - Comment node */
{
  char	*ptr;				/* Pointer into comment */


 /*
  * Range check the input...
  */

  if (!parent || !comment)
    return;
 
 /*
  * Update the comment...
  */

  ptr = comment->value.text.string;

  if (*ptr == '\'')
  {
   /*
    * Convert "'name()' - description" to "description".
    */

    for (ptr ++; *ptr && *ptr != '\''; ptr ++);

    if (*ptr == '\'')
    {
      ptr ++;
      while (isspace(*ptr))
        ptr ++;

      if (*ptr == '-')
        ptr ++;

      while (isspace(*ptr))
        ptr ++;

      strcpy(comment->value.text.string, ptr);
    }
  }
  else if (!strncmp(ptr, "I ", 2) || !strncmp(ptr, "O ", 2) ||
           !strncmp(ptr, "IO ", 3))
  {
   /*
    * 'Convert "I - description", "IO - description", or "O - description"
    * to description + directory attribute.
    */

    ptr = strchr(ptr, ' ');
    *ptr++ = '\0';

    if (!strcmp(parent->value.element.name, "argument"))
      mxmlElementSetAttr(parent, "direction", comment->value.text.string);

    while (isspace(*ptr))
      ptr ++;

    if (*ptr == '-')
      ptr ++;

    while (isspace(*ptr))
      ptr ++;

    strcpy(comment->value.text.string, ptr);
  }
}


/*
 * 'write_documentation()' - Write HTML documentation.
 */

static void
write_documentation(mxml_node_t *doc)	/* I - XML documentation */
{
  mxml_node_t	*node,			/* Current node */
		*function,		/* Current function */
		*arg,			/* Current argument */
		*description,		/* Description of function/var */
		*type;			/* Type of returnvalue/var */
  const char	*name;			/* Name of function/type */


  puts("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" "
       "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">");
  puts("<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">");
  puts("<head>");
  puts("\t<title>Mini-XML Home Page</title>");
  puts("\t<style><!--");
  puts("\th1, h2, h3, p { font-family: sans-serif; text-align: justify; }");
  puts("\ttt, pre, pre a:link, pre a:visited, tt a:link, tt a:visited { font-weight: bold; color: #7f0000; }");
  puts("\t--></style>");
  puts("</head>");
  puts("<body>");

  puts("<h1>Functions</h1>");
  puts("<ul>");

  for (function = mxmlFindElement(doc, doc, "function", NULL, NULL,
                                  MXML_DESCEND_FIRST);
       function;
       function = mxmlFindElement(function, doc, "function", NULL, NULL,
                                  MXML_NO_DESCEND))
  {
    name = mxmlElementGetAttr(function, "name");
    printf("\t<li><a href=\"#%s\"><tt>%s()</tt></a></li>\n", name, name);
  }

  puts("</ul>");

  for (function = mxmlFindElement(doc, doc, "function", NULL, NULL,
                                  MXML_DESCEND_FIRST);
       function;
       function = mxmlFindElement(function, doc, "function", NULL, NULL,
                                  MXML_NO_DESCEND))
  {
    name = mxmlElementGetAttr(function, "name");
    printf("<h2><a name=\"%s\">%s()</a></h2>\n", name, name);

    description = mxmlFindElement(function, function, "description", NULL,
                                  NULL, MXML_DESCEND_FIRST);
    fputs("<p>", stdout);
    for (node = mxmlWalkNext(description, description, MXML_DESCEND);
         node;
	 node = mxmlWalkNext(node, description, MXML_DESCEND))
      if (node->type == MXML_TEXT)
      {
        if (node->value.text.whitespace)
	  putchar(' ');

	fputs(node->value.text.string, stdout);
      }

    puts("</p>");
  }

  puts("</body>");
  puts("</html>");

}


/*
 * 'ws_cb()' - Whitespace callback for saving.
 */

static int				/* O - Whitespace char or 0 for none */
ws_cb(mxml_node_t *node,		/* I - Element node */
      int         where)		/* I - Where value */
{
  const char	*name;			/* Name of element */


  name = node->value.element.name;

  if ((!strcmp(name, "namespace") || !strcmp(name, "enumeration") ||
       !strcmp(name, "typedef") || !strcmp(name, "function") ||
       !strcmp(name, "variable") || !strcmp(name, "struct") ||
       !strcmp(name, "class") || !strcmp(name, "constant") ||
       !strcmp(name, "argument") || !strcmp(name, "returnvalue")) &&
      where == MXML_WS_AFTER_CLOSE)
    return ('\n');

  return (0);
}


/*
 * End of "$Id: mxmldoc.c,v 1.7 2003/06/05 12:11:53 mike Exp $".
 */
