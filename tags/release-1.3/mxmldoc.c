/*
 * "$Id: mxmldoc.c,v 1.23 2003/12/19 02:56:11 mike Exp $"
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
 *   main()                - Main entry for test program.
 *   add_variable()        - Add a variable or argument.
 *   safe_strcpy()         - Copy a string allowing for overlapping strings.
 *   scan_file()           - Scan a source file.
 *   sort_node()           - Insert a node sorted into a tree.
 *   update_comment()      - Update a comment node.
 *   write_documentation() - Write HTML documentation.
 *   write_element()       - Write an elements text nodes.
 *   write_string()        - Write a string, quoting XHTML special chars
 *                           as needed...
 *   ws_cb()               - Whitespace callback for saving.
 */

/*
 * Include necessary headers...
 */

#include "config.h"
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
 * Local functions...
 */

static mxml_node_t	*add_variable(mxml_node_t *parent, const char *name,
			              mxml_node_t *type);
static void		safe_strcpy(char *dst, const char *src);
static int		scan_file(const char *filename, FILE *fp,
			          mxml_node_t *doc);
static void		sort_node(mxml_node_t *tree, mxml_node_t *func);
static void		update_comment(mxml_node_t *parent,
			               mxml_node_t *comment);
static void		write_documentation(mxml_node_t *doc);
static void		write_element(mxml_node_t *doc, mxml_node_t *element);
static void		write_string(const char *s);
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
 * 'add_variable()' - Add a variable or argument.
 */

static mxml_node_t *			/* O - New variable/argument */
add_variable(mxml_node_t *parent,	/* I - Parent node */
             const char  *name,		/* I - "argument" or "variable" */
             mxml_node_t *type)		/* I - Type nodes */
{
  mxml_node_t	*variable,		/* New variable */
		*node,			/* Current node */
		*next;			/* Next node */
  char		buffer[16384],		/* String buffer */
		*bufptr;		/* Pointer into buffer */


  if (!type || !type->child)
    return (NULL);

  variable = mxmlNewElement(parent, name);

  if (type->last_child->value.text.string[0] == ')')
  {
   /*
    * Handle "type (*name)(args)"...
    */

    for (node = type->child; node; node = node->next)
      if (node->value.text.string[0] == '(')
	break;

    for (bufptr = buffer; node; bufptr += strlen(bufptr))
    {
      if (node->value.text.whitespace)
	*bufptr++ = ' ';

      strcpy(bufptr, node->value.text.string);

      next = node->next;
      mxmlDelete(node);
      node = next;
    }
  }
  else
  {
   /*
    * Handle "type name"...
    */

    strcpy(buffer, type->last_child->value.text.string);
    mxmlDelete(type->last_child);
  }

  mxmlElementSetAttr(variable, "name", buffer);

  mxmlAdd(variable, MXML_ADD_AFTER, MXML_ADD_TO_PARENT, type);

  return (variable);
}


/*
 * 'safe_strcpy()' - Copy a string allowing for overlapping strings.
 */

static void
safe_strcpy(char       *dst,		/* I - Destination string */
            const char *src)		/* I - Source string */
{
  while (*src)
    *dst++ = *src++;

  *dst = '\0';
}


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
  int		ch;			/* Current character */
  char		buffer[65536],		/* String buffer */
		*bufptr;		/* Pointer into buffer */
  mxml_node_t	*comment,		/* <comment> node */
		*constant,		/* <constant> node */
		*enumeration,		/* <enumeration> node */
		*function,		/* <function> node */
		*structclass,		/* <struct> or <class> node */
		*typedefnode,		/* <typedef> node */
		*variable,		/* <variable> or <argument> node */
		*returnvalue,		/* <returnvalue> node */
		*type,			/* <type> node */
		*description;		/* <description> node */
#if DEBUG > 1
  mxml_node_t	*temp;			/* Temporary node */
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
#endif /* DEBUG > 1 */


 /*
  * Initialize the finite state machine...
  */

  state       = STATE_NONE;
  braces      = 0;
  parens      = 0;
  bufptr      = buffer;

  comment     = mxmlNewElement(MXML_NO_PARENT, "temp");
  constant    = NULL;
  enumeration = NULL;
  function    = NULL;
  variable    = NULL;
  returnvalue = NULL;
  type        = NULL;
  description = NULL;
  typedefnode = NULL;
  structclass = NULL;

 /*
  * Read until end-of-file...
  */

  while ((ch = getc(fp)) != EOF)
  {
#if DEBUG > 1
    oldstate = state;
    oldch    = ch;
#endif /* DEBUG > 1 */

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
		{
		  ungetc(ch, fp);

		  if (type)
		  {
#ifdef DEBUG
                    fputs("Identifier: <<<< / >>>\n", stderr);
#endif /* DEBUG */
                    ch = type->last_child->value.text.string[0];
		    mxmlNewText(type, isalnum(ch) || ch == '_', "/");
		  }
		}
		break;

	    case '#' :			/* Preprocessor */
#ifdef DEBUG
	        fputs("    #preprocessor...\n", stderr);
#endif /* DEBUG */
	        state = STATE_PREPROCESSOR;
		break;

            case '\'' :			/* Character constant */
	        state = STATE_CHARACTER;
		break;

            case '\"' :			/* String constant */
	        state = STATE_STRING;
		break;

            case '{' :
#ifdef DEBUG
	        fprintf(stderr, "    open brace, function=%p, type=%p...\n",
		        function, type);
                if (type)
                  fprintf(stderr, "    type->child=\"%s\"...\n",
		          type->child->value.text.string);
#endif /* DEBUG */

	        if (function)
		  sort_node(tree, function);
		else if (type && type->child &&
		         ((!strcmp(type->child->value.text.string, "typedef") &&
			   type->child->next &&
			   (!strcmp(type->child->next->value.text.string, "struct") ||
			    !strcmp(type->child->next->value.text.string, "union") ||
			    !strcmp(type->child->next->value.text.string, "class"))) ||
			  !strcmp(type->child->value.text.string, "union") ||
			  !strcmp(type->child->value.text.string, "struct") ||
			  !strcmp(type->child->value.text.string, "class")))
		{
		 /*
		  * Start of a class or structure...
		  */

		  if (!strcmp(type->child->value.text.string, "typedef"))
		  {
#ifdef DEBUG
                    fputs("    starting typedef...\n", stderr);
#endif /* DEBUG */

		    typedefnode = mxmlNewElement(MXML_NO_PARENT, "typedef");
		    mxmlDelete(type->child);
		  }
		  else
		    typedefnode = NULL;
	
		  structclass = mxmlNewElement(MXML_NO_PARENT,
		                               type->child->value.text.string);

#ifdef DEBUG
                  fprintf(stderr, "%c%s: <<<< %s >>>\n",
		          toupper(type->child->value.text.string[0]),
			  type->child->value.text.string + 1,
			  type->child->next ?
			      type->child->next->value.text.string : "(noname)");
#endif /* DEBUG */

                  if (type->child->next)
		  {
		    mxmlElementSetAttr(structclass, "name",
		                       type->child->next->value.text.string);
		    sort_node(tree, structclass);
		  }

                  if (typedefnode && type->child)
		    type->child->value.text.whitespace = 0;
                  else
		  {
		    mxmlDelete(type);
		    type = NULL;
		  }

		  if (typedefnode && comment->last_child)
		  {
		   /*
		    * Copy comment for typedef as well as class/struct/union...
		    */

		    mxmlNewText(comment, 0,
		                comment->last_child->value.text.string);
		    description = mxmlNewElement(typedefnode, "description");
#ifdef DEBUG
		    fputs("    duplicating comment for typedef...\n", stderr);
#endif /* DEBUG */
		    update_comment(typedefnode, comment->last_child);
		    mxmlAdd(description, MXML_ADD_AFTER, MXML_ADD_TO_PARENT,
		            comment->last_child);
		  }

		  description = mxmlNewElement(structclass, "description");
#ifdef DEBUG
		  fprintf(stderr, "    adding comment to %s...\n",
		          structclass->value.element.name);
#endif /* DEBUG */
		  update_comment(structclass, comment->last_child);
		  mxmlAdd(description, MXML_ADD_AFTER, MXML_ADD_TO_PARENT,
		          comment->last_child);

                  if (scan_file(filename, fp, structclass))
		  {
		    mxmlDelete(comment);
		    return (-1);
		  }

#ifdef DEBUG
                  fputs("    ended typedef...\n", stderr);
#endif /* DEBUG */
                  structclass = NULL;
                  break;
                }
		else if (type && type->child && type->child->next &&
		         (!strcmp(type->child->value.text.string, "enum") ||
			  (!strcmp(type->child->value.text.string, "typedef") &&
			   !strcmp(type->child->next->value.text.string, "enum"))))
                {
		 /*
		  * Enumeration type...
		  */

		  if (!strcmp(type->child->value.text.string, "typedef"))
		  {
#ifdef DEBUG
                    fputs("    starting typedef...\n", stderr);
#endif /* DEBUG */

		    typedefnode = mxmlNewElement(MXML_NO_PARENT, "typedef");
		    mxmlDelete(type->child);
		  }
		  else
		    typedefnode = NULL;
	
		  enumeration = mxmlNewElement(MXML_NO_PARENT, "enumeration");

#ifdef DEBUG
                  fprintf(stderr, "Enumeration: <<<< %s >>>\n",
			  type->child->next ?
			      type->child->next->value.text.string : "(noname)");
#endif /* DEBUG */

                  if (type->child->next)
		  {
		    mxmlElementSetAttr(enumeration, "name",
		                       type->child->next->value.text.string);
		    sort_node(tree, enumeration);
		  }

                  if (typedefnode && type->child)
		    type->child->value.text.whitespace = 0;
                  else
		  {
		    mxmlDelete(type);
		    type = NULL;
		  }

		  if (typedefnode && comment->last_child)
		  {
		   /*
		    * Copy comment for typedef as well as class/struct/union...
		    */

		    mxmlNewText(comment, 0,
		                comment->last_child->value.text.string);
		    description = mxmlNewElement(typedefnode, "description");
#ifdef DEBUG
		    fputs("    duplicating comment for typedef...\n", stderr);
#endif /* DEBUG */
		    update_comment(typedefnode, comment->last_child);
		    mxmlAdd(description, MXML_ADD_AFTER, MXML_ADD_TO_PARENT,
		            comment->last_child);
		  }

		  description = mxmlNewElement(enumeration, "description");
#ifdef DEBUG
		  fputs("    adding comment to enumeration...\n", stderr);
#endif /* DEBUG */
		  update_comment(enumeration, comment->last_child);
		  mxmlAdd(description, MXML_ADD_AFTER, MXML_ADD_TO_PARENT,
		          comment->last_child);
		}
		else if (type && type->child &&
		         !strcmp(type->child->value.text.string, "extern"))
                {
                  if (scan_file(filename, fp, tree))
		  {
		    mxmlDelete(comment);
		    return (-1);
		  }
                }
		else if (type)
		{
		  mxmlDelete(type);
		  type = NULL;
		}

	        braces ++;
		function = NULL;
		variable = NULL;
		break;

            case '}' :
#ifdef DEBUG
	        fputs("    close brace...\n", stderr);
#endif /* DEBUG */

                enumeration = NULL;
		constant    = NULL;
		structclass = NULL;

	        if (braces > 0)
		  braces --;
		else
		{
		  mxmlDelete(comment);
		  return (0);
		}
		break;

            case '(' :
		if (type)
		{
#ifdef DEBUG
                  fputs("Identifier: <<<< ( >>>\n", stderr);
#endif /* DEBUG */
		  mxmlNewText(type, 0, "(");
		}

	        parens ++;
		break;

            case ')' :
	        if (parens > 0)
		  parens --;

		if (type && parens)
		{
#ifdef DEBUG
                  fputs("Identifier: <<<< ) >>>\n", stderr);
#endif /* DEBUG */
		  mxmlNewText(type, 0, ")");
		}

                if (function && type && !parens)
		{
		  variable = add_variable(function, "argument", type);
		  type     = NULL;
		}
		break;

	    case ';' :
#ifdef DEBUG
                fputs("Identifier: <<<< ; >>>\n", stderr);
		fprintf(stderr, "    function=%p, type=%p\n", function, type);
#endif /* DEBUG */

	        if (function)
		{
		  mxmlDelete(function);
		  function = NULL;
		}

		if (type)
		{
		  mxmlDelete(type);
		  type = NULL;
		}
		break;

	    case ':' :
		if (type)
		{
#ifdef DEBUG
                  fputs("Identifier: <<<< : >>>\n", stderr);
#endif /* DEBUG */
		  mxmlNewText(type, 1, ":");
		}
		break;

	    case '*' :
		if (type)
		{
#ifdef DEBUG
                  fputs("Identifier: <<<< * >>>\n", stderr);
#endif /* DEBUG */
                  ch = type->last_child->value.text.string[0];
		  mxmlNewText(type, isalnum(ch) || ch == '_', "*");
		}
		break;

	    case '+' :
		if (type)
		{
#ifdef DEBUG
                  fputs("Identifier: <<<< + >>>\n", stderr);
#endif /* DEBUG */
                  ch = type->last_child->value.text.string[0];
		  mxmlNewText(type, isalnum(ch) || ch == '_', "+");
		}
		break;

	    case '-' :
		if (type)
		{
#ifdef DEBUG
                  fputs("Identifier: <<<< - >>>\n", stderr);
#endif /* DEBUG */
                  ch = type->last_child->value.text.string[0];
		  mxmlNewText(type, isalnum(ch) || ch == '_', "-");
		}
		break;

	    case '=' :
		if (type)
		{
#ifdef DEBUG
                  fputs("Identifier: <<<< = >>>\n", stderr);
#endif /* DEBUG */
                  ch = type->last_child->value.text.string[0];
		  mxmlNewText(type, isalnum(ch) || ch == '_', "=");
		}
		break;

            default :			/* Other */
	        if (isalnum(ch) || ch == '_' || ch == '.')
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
		      {
#ifdef DEBUG
			fprintf(stderr, "    removing comment %p, last comment %p...\n",
				comment->child, comment->last_child);
#endif /* DEBUG */
			mxmlDelete(comment->child);
#ifdef DEBUG
			fprintf(stderr, "    new comment %p, last comment %p...\n",
				comment->child, comment->last_child);
#endif /* DEBUG */
		      }

#ifdef DEBUG
                      fprintf(stderr, "    processing comment, variable=%p, constant=%p, tree=\"%s\"\n",
		              variable, constant, tree->value.element.name);
#endif /* DEBUG */

		      if (variable)
		      {
        		description = mxmlNewElement(variable, "description");
#ifdef DEBUG
			fputs("    adding comment to variable...\n", stderr);
#endif /* DEBUG */
			update_comment(variable,
			               mxmlNewText(description, 0, buffer));
		      }
		      else if (constant)
		      {
        		description = mxmlNewElement(constant, "description");
#ifdef DEBUG
		        fputs("    adding comment to constant...\n", stderr);
#endif /* DEBUG */
			update_comment(constant,
			               mxmlNewText(description, 0, buffer));
		      }
		      else if (typedefnode)
		      {
        		description = mxmlNewElement(typedefnode, "description");
#ifdef DEBUG
			fprintf(stderr, "    adding comment to typedef %s...\n",
			        mxmlElementGetAttr(typedefnode, "name"));
#endif /* DEBUG */
			update_comment(typedefnode,
			               mxmlNewText(description, 0, buffer));
		      }
		      else if (strcmp(tree->value.element.name, "?xml") &&
		               !mxmlFindElement(tree, tree, "description",
			                        NULL, NULL, MXML_DESCEND_FIRST))
                      {
        		description = mxmlNewElement(tree, "description");
#ifdef DEBUG
			fputs("    adding comment to parent...\n", stderr);
#endif /* DEBUG */
			update_comment(tree,
			               mxmlNewText(description, 0, buffer));
		      }
		      else
		      {
#ifdef DEBUG
		        fprintf(stderr, "    before adding comment, child=%p, last_child=%p\n",
			        comment->child, comment->last_child);
#endif /* DEBUG */
        		mxmlNewText(comment, 0, buffer);
#ifdef DEBUG
		        fprintf(stderr, "    after adding comment, child=%p, last_child=%p\n",
			        comment->child, comment->last_child);
#endif /* DEBUG */
                      }
#ifdef DEBUG
		      fprintf(stderr, "C comment: <<<< %s >>>\n", buffer);
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
		  {
#ifdef DEBUG
		    fprintf(stderr, "    removing comment %p, last comment %p...\n",
			    comment->child, comment->last_child);
#endif /* DEBUG */
		    mxmlDelete(comment->child);
#ifdef DEBUG
		    fprintf(stderr, "    new comment %p, last comment %p...\n",
			    comment->child, comment->last_child);
#endif /* DEBUG */
		  }

		  if (variable)
		  {
        	    description = mxmlNewElement(variable, "description");
#ifdef DEBUG
		    fputs("    adding comment to variable...\n", stderr);
#endif /* DEBUG */
		    update_comment(variable,
			           mxmlNewText(description, 0, buffer));
		  }
		  else if (constant)
		  {
        	    description = mxmlNewElement(constant, "description");
#ifdef DEBUG
		    fputs("    adding comment to constant...\n", stderr);
#endif /* DEBUG */
		    update_comment(constant,
			           mxmlNewText(description, 0, buffer));
		  }
		  else if (typedefnode)
		  {
        	    description = mxmlNewElement(typedefnode, "description");
#ifdef DEBUG
		    fprintf(stderr, "    adding comment to typedef %s...\n",
			    mxmlElementGetAttr(typedefnode, "name"));
#endif /* DEBUG */
		    update_comment(typedefnode,
			           mxmlNewText(description, 0, buffer));
		  }
		  else if (strcmp(tree->value.element.name, "?xml") &&
		           !mxmlFindElement(tree, tree, "description",
			                    NULL, NULL, MXML_DESCEND_FIRST))
                  {
        	    description = mxmlNewElement(tree, "description");
#ifdef DEBUG
		    fputs("    adding comment to parent...\n", stderr);
#endif /* DEBUG */
		    update_comment(tree,
			           mxmlNewText(description, 0, buffer));
		  }
		  else
        	    mxmlNewText(comment, 0, buffer);

#ifdef DEBUG
		  fprintf(stderr, "C comment: <<<< %s >>>\n", buffer);
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
	    {
#ifdef DEBUG
	      fprintf(stderr, "    removing comment %p, last comment %p...\n",
		      comment->child, comment->last_child);
#endif /* DEBUG */
	      mxmlDelete(comment->child);
#ifdef DEBUG
	      fprintf(stderr, "    new comment %p, last comment %p...\n",
		      comment->child, comment->last_child);
#endif /* DEBUG */
	    }

	    if (variable)
	    {
              description = mxmlNewElement(variable, "description");
#ifdef DEBUG
	      fputs("    adding comment to variable...\n", stderr);
#endif /* DEBUG */
	      update_comment(variable,
			     mxmlNewText(description, 0, buffer));
	    }
	    else if (constant)
	    {
              description = mxmlNewElement(constant, "description");
#ifdef DEBUG
	      fputs("    adding comment to constant...\n", stderr);
#endif /* DEBUG */
	      update_comment(constant,
			     mxmlNewText(description, 0, buffer));
	    }
	    else if (typedefnode)
	    {
              description = mxmlNewElement(typedefnode, "description");
#ifdef DEBUG
	      fprintf(stderr, "    adding comment to typedef %s...\n",
		      mxmlElementGetAttr(typedefnode, "name"));
#endif /* DEBUG */
	      update_comment(typedefnode,
			     mxmlNewText(description, 0, buffer));
	    }
	    else if (strcmp(tree->value.element.name, "?xml") &&
		     !mxmlFindElement(tree, tree, "description",
			              NULL, NULL, MXML_DESCEND_FIRST))
            {
              description = mxmlNewElement(tree, "description");
#ifdef DEBUG
	      fputs("    adding comment to parent...\n", stderr);
#endif /* DEBUG */
	      update_comment(tree,
			     mxmlNewText(description, 0, buffer));
	    }
	    else
              mxmlNewText(comment, 0, buffer);

#ifdef DEBUG
	    fprintf(stderr, "C++ comment: <<<< %s >>>\n", buffer);
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
	  if (isalnum(ch) || ch == '_' || ch == '[' || ch == ']' ||
	      (ch == ',' && parens > 1) || ch == ':' || ch == '.')
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

#ifdef DEBUG
              fprintf(stderr, "    function=%p (%s), type->child=%p, ch='%c', parens=%d\n",
	              function,
		      function ? mxmlElementGetAttr(function, "name") : "null",
	              type->child, ch, parens);
#endif /* DEBUG */

              if (!function && ch == '(')
	      {
	        if (type->child &&
		    !strcmp(type->child->value.text.string, "extern"))
		{
		 /*
		  * Remove external declarations...
		  */

		  mxmlDelete(type);
		  type = NULL;
		  break;
		}

	        if (type->child &&
		    !strcmp(type->child->value.text.string, "static") &&
		    !strcmp(tree->value.element.name, "?xml"))
		{
		 /*
		  * Remove static functions...
		  */

		  mxmlDelete(type);
		  type = NULL;
		  break;
		}

	        function = mxmlNewElement(MXML_NO_PARENT, "function");
		mxmlElementSetAttr(function, "name", buffer);

#ifdef DEBUG
                fprintf(stderr, "function: %s\n", buffer);
		fprintf(stderr, "    comment=%p\n", comment);
		fprintf(stderr, "    child = (%p) %s\n",
		        comment->child,
			comment->child ?
			    comment->child->value.text.string : "(null)");
		fprintf(stderr, "    last_child = (%p) %s\n",
		        comment->last_child,
			comment->last_child ?
			    comment->last_child->value.text.string : "(null)");
#endif /* DEBUG */

                if (!type->last_child ||
		    strcmp(type->last_child->value.text.string, "void"))
		{
                  returnvalue = mxmlNewElement(function, "returnvalue");

		  description = mxmlNewElement(returnvalue, "description");
#ifdef DEBUG
		  fputs("    adding comment to returnvalue...\n", stderr);
#endif /* DEBUG */
		  update_comment(returnvalue, comment->last_child);
		  mxmlAdd(description, MXML_ADD_AFTER, MXML_ADD_TO_PARENT,
		          comment->last_child);

		  mxmlAdd(returnvalue, MXML_ADD_AFTER, MXML_ADD_TO_PARENT, type);
                }
		else
		  mxmlDelete(type);

		description = mxmlNewElement(function, "description");
#ifdef DEBUG
		  fputs("    adding comment to function...\n", stderr);
#endif /* DEBUG */
		update_comment(function, comment->last_child);
		mxmlAdd(description, MXML_ADD_AFTER, MXML_ADD_TO_PARENT,
		        comment->last_child);

		type = NULL;
	      }
	      else if (function && ((ch == ')' && parens == 1) || ch == ','))
	      {
	       /*
	        * Argument definition...
		*/

	        mxmlNewText(type, type->child != NULL &&
		                  type->last_child->value.text.string[0] != '(' &&
				  type->last_child->value.text.string[0] != '*',
			    buffer);

#ifdef DEBUG
                fprintf(stderr, "Argument: <<<< %s >>>\n", buffer);
#endif /* DEBUG */

	        variable = add_variable(function, "argument", type);
		type     = NULL;
	      }
              else if (type->child && !function && (ch == ';' || ch == ','))
	      {
#ifdef DEBUG
	        fprintf(stderr, "    got semicolon, typedefnode=%p, structclass=%p\n",
		        typedefnode, structclass);
#endif /* DEBUG */

	        if (typedefnode || structclass)
		{
#ifdef DEBUG
                  fprintf(stderr, "Typedef/struct/class: <<<< %s >>>>\n", buffer);
#endif /* DEBUG */

		  if (typedefnode)
		  {
		    mxmlElementSetAttr(typedefnode, "name", buffer);

                    sort_node(tree, typedefnode);
		  }

		  if (structclass && !mxmlElementGetAttr(structclass, "name"))
		  {
#ifdef DEBUG
		    fprintf(stderr, "setting struct/class name to %s!\n",
		            type->last_child->value.text.string);
#endif /* DEBUG */
		    mxmlElementSetAttr(structclass, "name", buffer);

		    sort_node(tree, structclass);
		    structclass = NULL;
		  }

		  if (typedefnode)
		    mxmlAdd(typedefnode, MXML_ADD_AFTER, MXML_ADD_TO_PARENT,
		            type);
                  else
		    mxmlDelete(type);

		  type        = NULL;
		  typedefnode = NULL;
		}
		else if (type->child &&
		         !strcmp(type->child->value.text.string, "typedef"))
		{
		 /*
		  * Simple typedef...
		  */

#ifdef DEBUG
                  fprintf(stderr, "Typedef: <<<< %s >>>\n", buffer);
#endif /* DEBUG */

		  typedefnode = mxmlNewElement(MXML_NO_PARENT, "typedef");
		  mxmlElementSetAttr(typedefnode, "name", buffer);
		  mxmlDelete(type->child);

                  sort_node(tree, typedefnode);

                  if (type->child)
		    type->child->value.text.whitespace = 0;

		  mxmlAdd(typedefnode, MXML_ADD_AFTER, MXML_ADD_TO_PARENT, type);
		  type = NULL;
		}
		else if (!parens)
		{
		 /*
	          * Variable definition...
		  */

	          mxmlNewText(type, type->child != NULL &&
		                    type->last_child->value.text.string[0] != '(' &&
				    type->last_child->value.text.string[0] != '*',
			      buffer);

#ifdef DEBUG
                  fprintf(stderr, "Variable: <<<< %s >>>>\n", buffer);
#endif /* DEBUG */

	          variable = add_variable(MXML_NO_PARENT, "variable", type);
		  type     = NULL;

		  sort_node(tree, variable);
		}
              }
	      else
              {
#ifdef DEBUG
                fprintf(stderr, "Identifier: <<<< %s >>>>\n", buffer);
#endif /* DEBUG */

	        mxmlNewText(type, type->child != NULL &&
		                  type->last_child->value.text.string[0] != '(' &&
				  type->last_child->value.text.string[0] != '*',
			    buffer);
	      }
	    }
	    else if (enumeration && !isdigit(buffer[0]))
	    {
#ifdef DEBUG
	      fprintf(stderr, "Constant: <<<< %s >>>\n", buffer);
#endif /* DEBUG */

	      constant = mxmlNewElement(MXML_NO_PARENT, "constant");
	      mxmlElementSetAttr(constant, "name", buffer);
	      sort_node(enumeration, constant);
	    }
	    else if (type)
	    {
	      mxmlDelete(type);
	      type = NULL;
	    }
	  }
          break;
    }

#if DEBUG > 1
    if (state != oldstate)
    {
      fprintf(stderr, "    changed states from %s to %s on receipt of character '%c'...\n",
              states[oldstate], states[state], oldch);
      if (type)
      {
        fputs("    type =", stderr);
        for (temp = type->child; temp; temp = temp->next)
	  fprintf(stderr, " \"%s\"", temp->value.text.string);
	fputs("\n", stderr);
      }
    }
#endif /* DEBUG > 1 */
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


#if DEBUG > 1
  fprintf(stderr, "    sort_node(tree=%p, node=%p)\n", tree, node);
#endif /* DEBUG > 1 */

 /*
  * Range check input...
  */

  if (!tree || !node || node->parent == tree)
    return;

 /*
  * Get the node name...
  */

  if ((nodename = mxmlElementGetAttr(node, "name")) == NULL)
    return;

#if DEBUG > 1
  fprintf(stderr, "        nodename=%p (\"%s\")\n", nodename, nodename);
#endif /* DEBUG > 1 */

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
#if DEBUG > 1
    fprintf(stderr, "        temp=%p\n", temp);
#endif /* DEBUG > 1 */

    if ((tempname = mxmlElementGetAttr(temp, "name")) == NULL)
      continue;

#if DEBUG > 1
    fprintf(stderr, "        tempname=%p (\"%s\")\n", tempname, tempname);
#endif /* DEBUG > 1 */

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


#ifdef DEBUG
  fprintf(stderr, "update_comment(parent=%p, comment=%p)\n",
          parent, comment);
#endif /* DEBUG */

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

      safe_strcpy(comment->value.text.string, ptr);
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

    safe_strcpy(comment->value.text.string, ptr);
  }

 /*
  * Eliminate leading and trailing *'s...
  */

  for (ptr = comment->value.text.string; *ptr == '*'; ptr ++);
  for (; isspace(*ptr); ptr ++);
  if (ptr > comment->value.text.string)
    safe_strcpy(comment->value.text.string, ptr);

  for (ptr = comment->value.text.string + strlen(comment->value.text.string) - 1;
       ptr > comment->value.text.string && *ptr == '*';
       ptr --)
    *ptr = '\0';
  for (; ptr > comment->value.text.string && isspace(*ptr); ptr --)
    *ptr = '\0';

#ifdef DEBUG
  fprintf(stderr, "    updated comment = %s\n", comment->value.text.string);
#endif /* DEBUG */
}


/*
 * 'write_documentation()' - Write HTML documentation.
 */

static void
write_documentation(mxml_node_t *doc)	/* I - XML documentation */
{
  mxml_node_t	*function,		/* Current function */
		*scut,			/* Struct/class/union/typedef */
		*arg,			/* Current argument */
		*description,		/* Description of function/var */
		*type;			/* Type for argument */
  const char	*name;			/* Name of function/type */
  char		prefix;			/* Prefix character */


 /*
  * Standard header...
  */

  puts("<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" "
       "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">");
  puts("<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">");
  puts("<head>");
  puts("\t<title>Documentation</title>");
  puts("\t<style><!--");
  puts("\th1, h2, h3, p { font-family: sans-serif; text-align: justify; }");
  puts("\ttt, pre a:link, pre a:visited, tt a:link, tt a:visited { font-weight: bold; color: #7f0000; }");
  puts("\tpre { font-weight: bold; color: #7f0000; margin-left: 2em; }");
  puts("\t--></style>");
  puts("</head>");
  puts("<body>");


 /*
  * Table of contents...
  */

  puts("<h1>Contents</h1>");
  puts("<ul>");
  if (mxmlFindElement(doc, doc, "class", NULL, NULL, MXML_DESCEND_FIRST))
    puts("\t<li><a href=\"#_classes\">Classes</a></li>");
  if (mxmlFindElement(doc, doc, "enumeration", NULL, NULL, MXML_DESCEND_FIRST))
    puts("\t<li><a href=\"#_enumerations\">Enumerations</a></li>");
  if (mxmlFindElement(doc, doc, "function", NULL, NULL, MXML_DESCEND_FIRST))
    puts("\t<li><a href=\"#_functions\">Functions</a></li>");
  if (mxmlFindElement(doc, doc, "struct", NULL, NULL, MXML_DESCEND_FIRST))
    puts("\t<li><a href=\"#_structures\">Structures</a></li>");
  if (mxmlFindElement(doc, doc, "typedef", NULL, NULL, MXML_DESCEND_FIRST))
    puts("\t<li><a href=\"#_types\">Types</a></li>");
  if (mxmlFindElement(doc, doc, "union", NULL, NULL, MXML_DESCEND_FIRST))
    puts("\t<li><a href=\"#_unions\">Unions</a></li>");
  if (mxmlFindElement(doc, doc, "variable", NULL, NULL, MXML_DESCEND_FIRST))
    puts("\t<li><a href=\"#_variables\">Variables</a></li>");
  puts("</ul>");

 /*
  * List of classes...
  */

  if (mxmlFindElement(doc, doc, "class", NULL, NULL, MXML_DESCEND_FIRST))
  {
    puts("<hr noshade/>");
    puts("<h1><a name=\"_classes\">Classes</a></h1>");
    puts("<ul>");

    for (scut = mxmlFindElement(doc, doc, "class", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "class", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("\t<li><a href=\"#%s\"><tt>%s</tt></a></li>\n", name, name);
    }

    puts("</ul>");

    for (scut = mxmlFindElement(doc, doc, "class", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "class", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("<h2><a name=\"%s\">%s</a></h2>\n", name, name);

      description = mxmlFindElement(scut, scut, "description", NULL,
                                    NULL, MXML_DESCEND_FIRST);
      if (description)
      {
	fputs("<p>", stdout);
	write_element(NULL, description);
	puts("</p>");
      }

      puts("<h3>Definition</h3>");
      puts("<pre>");

      printf("struct %s\n{\n", name);
      for (arg = mxmlFindElement(scut, scut, "variable", NULL, NULL,
                        	 MXML_DESCEND_FIRST);
	   arg;
	   arg = mxmlFindElement(arg, scut, "variable", NULL, NULL,
                        	 MXML_NO_DESCEND))
      {
	printf("  ");
	write_element(doc, mxmlFindElement(arg, arg, "type", NULL,
                                           NULL, MXML_DESCEND_FIRST));
	printf(" %s;\n", mxmlElementGetAttr(arg, "name"));
      }

      puts("};\n</pre>");

      puts("<h3>Members</h3>");

      puts("<p class=\"table\"><table align=\"center\" border=\"1\" width=\"80%\">");
      puts("<thead><tr><th>Name</th><th>Description</th></tr></thead>");
      puts("<tbody>");

      for (arg = mxmlFindElement(scut, scut, "variable", NULL, NULL,
                        	 MXML_DESCEND_FIRST);
	   arg;
	   arg = mxmlFindElement(arg, scut, "variable", NULL, NULL,
                        	 MXML_NO_DESCEND))
      {
	printf("<tr><td><tt>%s</tt></td><td>", mxmlElementGetAttr(arg, "name"));

	write_element(NULL, mxmlFindElement(arg, arg, "description", NULL,
                                            NULL, MXML_DESCEND_FIRST));

	puts("</td></tr>");
      }

      puts("</tbody></table></p>");
    }
  }

 /*
  * List of enumerations...
  */

  if (mxmlFindElement(doc, doc, "enumeration", NULL, NULL, MXML_DESCEND_FIRST))
  {
    puts("<hr noshade/>");
    puts("<h1><a name=\"_enumerations\">Enumerations</a></h1>");
    puts("<ul>");

    for (scut = mxmlFindElement(doc, doc, "enumeration", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "enumeration", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("\t<li><a href=\"#%s\"><tt>%s</tt></a></li>\n", name, name);
    }

    puts("</ul>");

    for (scut = mxmlFindElement(doc, doc, "enumeration", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "enumeration", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("<h2><a name=\"%s\">%s</a></h2>\n", name, name);

      description = mxmlFindElement(scut, scut, "description", NULL,
                                    NULL, MXML_DESCEND_FIRST);
      if (description)
      {
	fputs("<p>", stdout);
	write_element(NULL, description);
	puts("</p>");
      }

      puts("<h3>Values</h3>");

      puts("<p class=\"table\"><table align=\"center\" border=\"1\" width=\"80%\">");
      puts("<thead><tr><th>Name</th><th>Description</th></tr></thead>");
      puts("<tbody>");

      for (arg = mxmlFindElement(scut, scut, "constant", NULL, NULL,
                        	 MXML_DESCEND_FIRST);
	   arg;
	   arg = mxmlFindElement(arg, scut, "constant", NULL, NULL,
                        	 MXML_NO_DESCEND))
      {
	printf("<tr><td><tt>%s</tt></td><td>", mxmlElementGetAttr(arg, "name"));

	write_element(doc, mxmlFindElement(arg, arg, "description", NULL,
                                           NULL, MXML_DESCEND_FIRST));

	puts("</td></tr>");
      }

      puts("</tbody></table></p>");
    }
  }

 /*
  * List of functions...
  */

  if (mxmlFindElement(doc, doc, "function", NULL, NULL, MXML_DESCEND_FIRST))
  {
    puts("<hr noshade/>");
    puts("<h1><a name=\"_functions\">Functions</a></h1>");
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
      if (description)
      {
	fputs("<p>", stdout);
	write_element(NULL, description);
	puts("</p>");
      }

      puts("<h3>Syntax</h3>");
      puts("<pre>");

      arg = mxmlFindElement(function, function, "returnvalue", NULL,
                            NULL, MXML_DESCEND_FIRST);

      if (arg)
	write_element(doc, mxmlFindElement(arg, arg, "type", NULL,
                                           NULL, MXML_DESCEND_FIRST));
      else
	fputs("void", stdout);

      printf("\n%s", name);
      for (arg = mxmlFindElement(function, function, "argument", NULL, NULL,
                        	 MXML_DESCEND_FIRST), prefix = '(';
	   arg;
	   arg = mxmlFindElement(arg, function, "argument", NULL, NULL,
                        	 MXML_NO_DESCEND), prefix = ',')
      {
        type = mxmlFindElement(arg, arg, "type", NULL, NULL,
	                       MXML_DESCEND_FIRST);

	printf("%c\n    ", prefix);
	write_element(doc, type);
	printf("%s%s", type->child ? " " : "", mxmlElementGetAttr(arg, "name"));
      }

      if (prefix == '(')
	puts("(void);\n</pre>");
      else
	puts(");\n</pre>");

      puts("<h3>Arguments</h3>");

      if (prefix == '(')
	puts("<p>None.</p>");
      else
      {
	puts("<p class=\"table\"><table align=\"center\" border=\"1\" width=\"80%\">");
	puts("<thead><tr><th>Name</th><th>Description</th></tr></thead>");
	puts("<tbody>");

	for (arg = mxmlFindElement(function, function, "argument", NULL, NULL,
                        	   MXML_DESCEND_FIRST);
	     arg;
	     arg = mxmlFindElement(arg, function, "argument", NULL, NULL,
                        	   MXML_NO_DESCEND))
	{
	  printf("<tr><td><tt>%s</tt></td><td>", mxmlElementGetAttr(arg, "name"));

	  write_element(NULL, mxmlFindElement(arg, arg, "description", NULL,
                               		      NULL, MXML_DESCEND_FIRST));

          puts("</td></tr>");
	}

	puts("</tbody></table></p>");
      }

      puts("<h3>Returns</h3>");

      arg = mxmlFindElement(function, function, "returnvalue", NULL,
                            NULL, MXML_DESCEND_FIRST);

      if (!arg)
	puts("<p>Nothing.</p>");
      else
      {
	fputs("<p>", stdout);
	write_element(NULL, mxmlFindElement(arg, arg, "description", NULL,
                                            NULL, MXML_DESCEND_FIRST));
	puts("</p>");
      }
    }
  }

 /*
  * List of structures...
  */

  if (mxmlFindElement(doc, doc, "struct", NULL, NULL, MXML_DESCEND_FIRST))
  {
    puts("<hr noshade/>");
    puts("<h1><a name=\"_structures\">Structures</a></h1>");
    puts("<ul>");

    for (scut = mxmlFindElement(doc, doc, "struct", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "struct", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("\t<li><a href=\"#%s\"><tt>%s</tt></a></li>\n", name, name);
    }

    puts("</ul>");

    for (scut = mxmlFindElement(doc, doc, "struct", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "struct", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("<h2><a name=\"%s\">%s</a></h2>\n", name, name);

      description = mxmlFindElement(scut, scut, "description", NULL,
                                    NULL, MXML_DESCEND_FIRST);
      if (description)
      {
	fputs("<p>", stdout);
	write_element(NULL, description);
	puts("</p>");
      }

      puts("<h3>Definition</h3>");
      puts("<pre>");

      printf("struct %s\n{\n", name);
      for (arg = mxmlFindElement(scut, scut, "variable", NULL, NULL,
                        	 MXML_DESCEND_FIRST);
	   arg;
	   arg = mxmlFindElement(arg, scut, "variable", NULL, NULL,
                        	 MXML_NO_DESCEND))
      {
	printf("  ");
	write_element(doc, mxmlFindElement(arg, arg, "type", NULL,
                                           NULL, MXML_DESCEND_FIRST));
	printf(" %s;\n", mxmlElementGetAttr(arg, "name"));
      }

      puts("};\n</pre>");

      puts("<h3>Members</h3>");

      puts("<p class=\"table\"><table align=\"center\" border=\"1\" width=\"80%\">");
      puts("<thead><tr><th>Name</th><th>Description</th></tr></thead>");
      puts("<tbody>");

      for (arg = mxmlFindElement(scut, scut, "variable", NULL, NULL,
                        	 MXML_DESCEND_FIRST);
	   arg;
	   arg = mxmlFindElement(arg, scut, "variable", NULL, NULL,
                        	 MXML_NO_DESCEND))
      {
	printf("<tr><td><tt>%s</tt></td><td>", mxmlElementGetAttr(arg, "name"));

	write_element(NULL, mxmlFindElement(arg, arg, "description", NULL,
                                            NULL, MXML_DESCEND_FIRST));

	puts("</td></tr>");
      }

      puts("</tbody></table></p>");
    }
  }

 /*
  * List of types...
  */

  if (mxmlFindElement(doc, doc, "typedef", NULL, NULL, MXML_DESCEND_FIRST))
  {
    puts("<hr noshade/>");
    puts("<h1><a name=\"_types\">Types</a></h1>");
    puts("<ul>");

    for (scut = mxmlFindElement(doc, doc, "typedef", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "typedef", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("\t<li><a href=\"#%s\"><tt>%s</tt></a></li>\n", name, name);
    }

    puts("</ul>");

    for (scut = mxmlFindElement(doc, doc, "typedef", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "typedef", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("<h2><a name=\"%s\">%s</a></h2>\n", name, name);

      description = mxmlFindElement(scut, scut, "description", NULL,
                                    NULL, MXML_DESCEND_FIRST);
      if (description)
      {
	fputs("<p>", stdout);
	write_element(NULL, description);
	puts("</p>");
      }

      puts("<h3>Definition</h3>");
      puts("<pre>");

      printf("typedef ");
      write_element(doc, mxmlFindElement(scut, scut, "type", NULL,
                                         NULL, MXML_DESCEND_FIRST));
      printf(" %s;\n</pre>\n", name);
    }
  }

 /*
  * List of unions...
  */

  if (mxmlFindElement(doc, doc, "union", NULL, NULL, MXML_DESCEND_FIRST))
  {
    puts("<hr noshade/>");
    puts("<h1><a name=\"_unions\">Unions</a></h1>");
    puts("<ul>");

    for (scut = mxmlFindElement(doc, doc, "union", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "union", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("\t<li><a href=\"#%s\"><tt>%s</tt></a></li>\n", name, name);
    }

    puts("</ul>");

    for (scut = mxmlFindElement(doc, doc, "union", NULL, NULL,
                        	MXML_DESCEND_FIRST);
	 scut;
	 scut = mxmlFindElement(scut, doc, "union", NULL, NULL,
                        	MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(scut, "name");
      printf("<h2><a name=\"%s\">%s</a></h2>\n", name, name);

      description = mxmlFindElement(scut, scut, "description", NULL,
                                    NULL, MXML_DESCEND_FIRST);
      if (description)
      {
	fputs("<p>", stdout);
	write_element(NULL, description);
	puts("</p>");
      }

      puts("<h3>Definition</h3>");
      puts("<pre>");

      printf("union %s\n{\n", name);
      for (arg = mxmlFindElement(scut, scut, "variable", NULL, NULL,
                        	 MXML_DESCEND_FIRST);
	   arg;
	   arg = mxmlFindElement(arg, scut, "variable", NULL, NULL,
                        	 MXML_NO_DESCEND))
      {
	printf("  ");
	write_element(doc, mxmlFindElement(arg, arg, "type", NULL,
                                           NULL, MXML_DESCEND_FIRST));
	printf(" %s;\n", mxmlElementGetAttr(arg, "name"));
      }

      puts("};\n</pre>");

      puts("<h3>Members</h3>");

      puts("<p class=\"table\"><table align=\"center\" border=\"1\" width=\"80%\">");
      puts("<thead><tr><th>Name</th><th>Description</th></tr></thead>");
      puts("<tbody>");

      for (arg = mxmlFindElement(scut, scut, "variable", NULL, NULL,
                        	 MXML_DESCEND_FIRST);
	   arg;
	   arg = mxmlFindElement(arg, scut, "variable", NULL, NULL,
                        	 MXML_NO_DESCEND))
      {
	printf("<tr><td><tt>%s</tt></td><td>", mxmlElementGetAttr(arg, "name"));

	write_element(NULL, mxmlFindElement(arg, arg, "description", NULL,
                                            NULL, MXML_DESCEND_FIRST));

	puts("</td></tr>");
      }

      puts("</tbody></table></p>");
    }
  }

 /*
  * Variables...
  */

  if (mxmlFindElement(doc, doc, "variable", NULL, NULL, MXML_DESCEND_FIRST))
  {
    puts("<hr noshade/>");
    puts("<h1><a name=\"_variables\">Variables</a></h1>");
    puts("<ul>");

    for (arg = mxmlFindElement(doc, doc, "variable", NULL, NULL,
                               MXML_DESCEND_FIRST);
	 arg;
	 arg = mxmlFindElement(arg, doc, "variable", NULL, NULL,
                               MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(arg, "name");
      printf("\t<li><a href=\"#%s\"><tt>%s</tt></a></li>\n", name, name);
    }

    puts("</ul>");

    for (arg = mxmlFindElement(doc, doc, "variable", NULL, NULL,
                               MXML_DESCEND_FIRST);
	 arg;
	 arg = mxmlFindElement(arg, doc, "variable", NULL, NULL,
                               MXML_NO_DESCEND))
    {
      name = mxmlElementGetAttr(arg, "name");
      printf("<h2><a name=\"%s\">%s</a></h2>\n", name, name);

      description = mxmlFindElement(arg, arg, "description", NULL,
                                    NULL, MXML_DESCEND_FIRST);
      if (description)
      {
	fputs("<p>", stdout);
	write_element(NULL, description);
	puts("</p>");
      }

      puts("<h3>Definition</h3>");
      puts("<pre>");

      write_element(doc, mxmlFindElement(arg, arg, "type", NULL,
                                         NULL, MXML_DESCEND_FIRST));
      printf(" %s;\n", mxmlElementGetAttr(arg, "name"));

      puts("</pre>");
    }
  }

 /*
  * Standard footer...
  */

  puts("</body>");
  puts("</html>");
}


/*
 * 'write_element()' - Write an element's text nodes.
 */

static void
write_element(mxml_node_t *doc,		/* I - Document tree */
              mxml_node_t *element)	/* I - Element to write */
{
  mxml_node_t	*node;			/* Current node */


  if (!element)
    return;

  for (node = element->child;
       node;
       node = mxmlWalkNext(node, element, MXML_NO_DESCEND))
    if (node->type == MXML_TEXT)
    {
      if (node->value.text.whitespace)
	putchar(' ');

      if (mxmlFindElement(doc, doc, "class", "name", node->value.text.string,
                          MXML_DESCEND) ||
	  mxmlFindElement(doc, doc, "enumeration", "name",
	                  node->value.text.string, MXML_DESCEND) ||
	  mxmlFindElement(doc, doc, "struct", "name", node->value.text.string,
                          MXML_DESCEND) ||
	  mxmlFindElement(doc, doc, "typedef", "name", node->value.text.string,
                          MXML_DESCEND) ||
	  mxmlFindElement(doc, doc, "union", "name", node->value.text.string,
                          MXML_DESCEND))
      {
        printf("<a href=\"#");
        write_string(node->value.text.string);
	printf("\">");
        write_string(node->value.text.string);
	printf("</a>");
      }
      else
        write_string(node->value.text.string);
    }
}


/*
 * 'write_string()' - Write a string, quoting XHTML special chars as needed...
 */

static void
write_string(const char *s)		/* I - String to write */
{
  while (*s)
  {
    if (*s == '&')
      fputs("&amp;", stdout);
    else if (*s == '<')
      fputs("&lt;", stdout);
    else if (*s == '>')
      fputs("&gt;", stdout);
    else if (*s == '\"')
      fputs("&quot;", stdout);
    else if (*s & 128)
    {
     /*
      * Convert UTF-8 to Unicode constant...
      */

      int	ch;			/* Unicode character */


      ch = *s & 255;

      if ((ch & 0xe0) == 0xc0)
      {
        ch = ((ch & 0x1f) << 6) | (s[1] & 0x3f);
	s ++;
      }
      else if ((ch & 0xf0) == 0xe0)
      {
        ch = ((((ch * 0x0f) << 6) | (s[1] & 0x3f)) << 6) | (s[2] & 0x3f);
	s += 2;
      }

      if (ch == 0xa0)
      {
       /*
        * Handle non-breaking space as-is...
	*/

        fputs("&nbsp;", stdout);
      }
      else
        printf("&#x%x;", ch);
    }
    else
      putchar(*s);

    s ++;
  }
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
 * End of "$Id: mxmldoc.c,v 1.23 2003/12/19 02:56:11 mike Exp $".
 */