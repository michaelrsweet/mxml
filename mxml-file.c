/*
 * "$Id: mxml-file.c,v 1.11 2003/06/15 21:31:45 mike Exp $"
 *
 * File loading code for mini-XML, a small XML-like file parsing library.
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
 *   mxmlLoadFile()       - Load a file into an XML node tree.
 *   mxmlSaveFile()       - Save an XML tree to a file.
 *   mxml_parse_element() - Parse an element for any attributes...
 *   mxml_write_node()    - Save an XML node to a file.
 *   mxml_write_string()  - Write a string, escaping & and < as needed.
 *   mxml_write_ws()      - Do whitespace callback...
 */

/*
 * Include necessary headers...
 */

#include "mxml.h"


/*
 * Local functions...
 */

static int	mxml_add_char(int ch, char **ptr, char **buffer, int *bufsize);
static int	mxml_parse_element(mxml_node_t *node, FILE *fp);
static int	mxml_write_node(mxml_node_t *node, FILE *fp,
		                int (*cb)(mxml_node_t *, int), int col);
static int	mxml_write_string(const char *s, FILE *fp);
static int	mxml_write_ws(mxml_node_t *node, FILE *fp, 
                              int (*cb)(mxml_node_t *, int), int ws, int col);


/*
 * 'mxmlLoadFile()' - Load a file into an XML node tree.
 *
 * The nodes in the specified file are added to the specified top node.
 * If no top node is provided, the XML file MUST be well-formed with a
 * single parent node like <?xml> for the entire file. The callback
 * function returns the value type that should be used for child nodes.
 * If MXML_NO_CALLBACK is specified then all child nodes will be either
 * MXML_ELEMENT or MXML_TEXT nodes.
 */

mxml_node_t *				/* O - First node or NULL if the file could not be read. */
mxmlLoadFile(mxml_node_t *top,		/* I - Top node */
             FILE        *fp,		/* I - File to read from */
             mxml_type_t (*cb)(mxml_node_t *))
					/* I - Callback function or MXML_NO_CALLBACK */
{
  mxml_node_t	*node,			/* Current node */
		*parent;		/* Current parent node */
  int		ch,			/* Character from file */
		whitespace;		/* Non-zero if whitespace seen */
  char		*buffer,		/* String buffer */
		*bufptr;		/* Pointer into buffer */
  int		bufsize;		/* Size of buffer */
  mxml_type_t	type;			/* Current node type */


 /*
  * Read elements and other nodes from the file...
  */

  if ((buffer = malloc(64)) == NULL)
  {
    fputs("Unable to allocate string buffer!\n", stderr);
    return (NULL);
  }

  bufsize    = 64;
  bufptr     = buffer;
  parent     = top;
  whitespace = 0;

  if (cb && parent)
    type = (*cb)(parent);
  else
    type = MXML_TEXT;

  while ((ch = getc(fp)) != EOF)
  {
    if ((ch == '<' || (isspace(ch) && type != MXML_OPAQUE)) && bufptr > buffer)
    {
     /*
      * Add a new value node...
      */

      *bufptr = '\0';

      switch (type)
      {
	case MXML_INTEGER :
            node = mxmlNewInteger(parent, strtol(buffer, &bufptr, 0));
	    break;

	case MXML_OPAQUE :
            node = mxmlNewOpaque(parent, buffer);
	    break;

	case MXML_REAL :
            node = mxmlNewReal(parent, strtod(buffer, &bufptr));
	    break;

	case MXML_TEXT :
            node = mxmlNewText(parent, whitespace, buffer);
	    break;

        default : /* Should never happen... */
	    node = NULL;
	    break;
      }	  

      if (*bufptr)
      {
       /*
        * Bad integer/real number value...
	*/

        fprintf(stderr, "Bad %s value '%s' in parent <%s>!\n",
	        type == MXML_INTEGER ? "integer" : "real", buffer,
		parent ? parent->value.element.name : "null");
	break;
      }

      bufptr     = buffer;
      whitespace = isspace(ch) && type == MXML_TEXT;

      if (!node)
      {
       /*
	* Just print error for now...
	*/

	fprintf(stderr, "Unable to add value node of type %d to parent <%s>!\n",
	        type, parent ? parent->value.element.name : "null");
	break;
      }
    }
    else if (isspace(ch) && type == MXML_TEXT)
      whitespace = 1;

   /*
    * Add lone whitespace node if we are starting a new element and have
    * existing whitespace...
    */

    if (ch == '<' && whitespace && type == MXML_TEXT)
    {
     /*
      * Peek at the next character and only do this if we are starting
      * an open tag...
      */

      ch = getc(fp);
      ungetc(ch, fp);

      if (ch != '/')
      {
	mxmlNewText(parent, whitespace, "");
	whitespace = 0;
      }

      ch = '<';
    }

    if (ch == '<')
    {
     /*
      * Start of open/close tag...
      */

      bufptr = buffer;

      while ((ch = getc(fp)) != EOF)
        if (isspace(ch) || ch == '>' || (ch == '/' && bufptr > buffer))
	  break;
	else if (mxml_add_char(ch, &bufptr, &buffer, &bufsize))
	{
          free(buffer);
	  return (NULL);
	}
	else if ((bufptr - buffer) == 3 && !strncmp(buffer, "!--", 3))
	  break;

      *bufptr = '\0';

      if (!strcmp(buffer, "!--"))
      {
       /*
        * Gather rest of comment...
	*/

	while ((ch = getc(fp)) != EOF)
	{
	  if (ch == '>' && bufptr > (buffer + 4) &&
	      !strncmp(bufptr - 2, "--", 2))
	    break;
	  else if (mxml_add_char(ch, &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	}

       /*
        * Error out if we didn't get the whole comment...
	*/

        if (ch != '>')
	  break;

       /*
        * Otherwise add this as an element under the current parent...
	*/

	*bufptr = '\0';

	if (!mxmlNewElement(parent, buffer))
	{
	 /*
	  * Just print error for now...
	  */

	  fprintf(stderr, "Unable to add comment node to parent <%s>!\n",
	          parent ? parent->value.element.name : "null");
	  break;
	}
      }
      else if (buffer[0] == '!')
      {
       /*
        * Gather rest of declaration...
	*/

	do
	{
	  if (ch == '>')
	    break;
	  else if (mxml_add_char(ch, &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	}
        while ((ch = getc(fp)) != EOF);

       /*
        * Error out if we didn't get the whole declaration...
	*/

        if (ch != '>')
	  break;

       /*
        * Otherwise add this as an element under the current parent...
	*/

	*bufptr = '\0';

	node = mxmlNewElement(parent, buffer);
	if (!node)
	{
	 /*
	  * Just print error for now...
	  */

	  fprintf(stderr, "Unable to add declaration node to parent <%s>!\n",
	          parent ? parent->value.element.name : "null");
	  break;
	}

       /*
	* Descend into this node, setting the value type as needed...
	*/

	parent = node;

	if (cb && parent)
	  type = (*cb)(parent);
      }
      else if (buffer[0] == '/')
      {
       /*
        * Handle close tag...
	*/

        if (!parent || strcmp(buffer + 1, parent->value.element.name))
	{
	 /*
	  * Close tag doesn't match tree; print an error for now...
	  */

	  fprintf(stderr, "Mismatched close tag <%s> under parent <%s>!\n",
	          buffer, parent->value.element.name);
          break;
	}

       /*
        * Keep reading until we see >...
	*/

        while (ch != '>' && ch != EOF)
	  ch = getc(fp);

       /*
	* Ascend into the parent and set the value type as needed...
	*/

	parent = parent->parent;

	if (cb && parent)
	  type = (*cb)(parent);
      }
      else
      {
       /*
        * Handle open tag...
	*/

        node = mxmlNewElement(parent, buffer);

	if (!node)
	{
	 /*
	  * Just print error for now...
	  */

	  fprintf(stderr, "Unable to add element node to parent <%s>!\n",
	          parent ? parent->value.element.name : "null");
	  break;
	}

        if (isspace(ch))
          ch = mxml_parse_element(node, fp);
        else if (ch == '/')
	{
	  if ((ch = getc(fp)) != '>')
	  {
	    fprintf(stderr, "Expected > but got '%c' instead for element <%s/>!\n",
	            ch, buffer);
            break;
	  }

	  ch = '/';
	}

	if (ch == EOF)
	  break;

        if (ch != '/')
	{
	 /*
	  * Descend into this node, setting the value type as needed...
	  */

	  parent = node;

	  if (cb && parent)
	    type = (*cb)(parent);
	}
      }

      bufptr  = buffer;
    }
    else if (ch == '&')
    {
     /*
      * Add character entity to current buffer...  Currently we only
      * support &lt;, &amp;, &gt;, &nbsp;, &quot;, &#nnn;, and &#xXXXX;...
      */

      char	entity[64],		/* Entity string */
		*entptr;		/* Pointer into entity */


      entity[0] = ch;
      entptr    = entity + 1;

      while ((ch = getc(fp)) != EOF)
        if (!isalnum(ch) && ch != '#')
	  break;
	else if (entptr < (entity + sizeof(entity) - 1))
	  *entptr++ = ch;
	else
	{
	  fprintf(stderr, "Entity name too long under parent <%s>!\n",
	          parent ? parent->value.element.name : "null");
          break;
	}

      *entptr = '\0';

      if (ch != ';')
      {
	fprintf(stderr, "Entity name \"%s\" not terminated under parent <%s>!\n",
	        entity, parent ? parent->value.element.name : "null");
        break;
      }

      if (entity[1] == '#')
      {
	if (entity[2] == 'x')
	  ch = strtol(entity + 3, NULL, 16);
	else
	  ch = strtol(entity + 2, NULL, 10);
      }
      else if (!strcmp(entity, "&amp"))
        ch = '&';
      else if (!strcmp(entity, "&gt"))
        ch = '>';
      else if (!strcmp(entity, "&lt"))
        ch = '<';
      else if (!strcmp(entity, "&nbsp"))
        ch = 0xa0;
      else if (!strcmp(entity, "&quot"))
        ch = '\"';
      else
      {
	fprintf(stderr, "Entity name \"%s;\" not supported under parent <%s>!\n",
	        entity, parent ? parent->value.element.name : "null");
        break;
      }

      if (ch < 128)
      {
       /*
        * Plain ASCII doesn't need special encoding...
	*/

	if (mxml_add_char(ch, &bufptr, &buffer, &bufsize))
	{
          free(buffer);
	  return (NULL);
	}
      }
      else
      {
       /*
        * Use UTF-8 encoding for the Unicode char...
	*/

	if (ch < 2048)
	{
	  if (mxml_add_char(0xc0 | (ch >> 6), &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	  if (mxml_add_char(0x80 | (ch & 63), &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
        }
	else if (ch < 65536)
	{
	  if (mxml_add_char(0xe0 | (ch >> 12), &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	  if (mxml_add_char(0x80 | ((ch >> 6) & 63), &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	  if (mxml_add_char(0x80 | (ch & 63), &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	}
	else
	{
	  if (mxml_add_char(0xf0 | (ch >> 18), &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	  if (mxml_add_char(0x80 | ((ch >> 12) & 63), &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	  if (mxml_add_char(0x80 | ((ch >> 6) & 63), &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	  if (mxml_add_char(0x80 | (ch & 63), &bufptr, &buffer, &bufsize))
	  {
            free(buffer);
	    return (NULL);
	  }
	}
      }
    }
    else if (type == MXML_OPAQUE || !isspace(ch))
    {
     /*
      * Add character to current buffer...
      */

      if (mxml_add_char(ch, &bufptr, &buffer, &bufsize))
      {
        free(buffer);
	return (NULL);
      }
    }
  }

 /*
  * Free the string buffer - we don't need it anymore...
  */

  free(buffer);

 /*
  * Find the top element and return it...
  */

  if (parent)
  {
    while (parent->parent != top)
      parent = parent->parent;
  }

  return (parent);
}


/*
 * 'mxmlSaveFile()' - Save an XML tree to a file.
 *
 * The callback argument specifies a function that returns a whitespace
 * character or nul (0) before and after each element. If MXML_NO_CALLBACK
 * is specified, whitespace will only be added before MXML_TEXT nodes
 * with leading whitespace and before attribute names inside opening
 * element tags.
 */

int					/* O - 0 on success, -1 on error. */
mxmlSaveFile(mxml_node_t *node,		/* I - Node to write */
             FILE        *fp,		/* I - File to write to */
	     int         (*cb)(mxml_node_t *, int))
					/* I - Whitespace callback or MXML_NO_CALLBACK */
{
  int	col;				/* Final column */


 /*
  * Write the node...
  */

  if ((col = mxml_write_node(node, fp, cb, 0)) < 0)
    return (-1);

  if (col > 0)
    if (putc('\n', fp) < 0)
      return (-1);

 /*
  * Return 0 (success)...
  */

  return (0);
}


/*
 * 'mxml_add_char()' - Add a character to a buffer, expanding as needed.
 */

static int				/* O  - 0 on success, -1 on error */
mxml_add_char(int  ch,			/* I  - Character to add */
              char **bufptr,		/* IO - Current position in buffer */
	      char **buffer,		/* IO - Current buffer */
	      int  *bufsize)		/* IO - Current buffer size */
{
  char	*newbuffer;			/* New buffer value */


  if (*bufptr >= (*buffer + *bufsize - 1))
  {
   /*
    * Increase the size of the buffer...
    */

    if (*bufsize < 1024)
      (*bufsize) *= 2;
    else
      (*bufsize) += 1024;

    if ((newbuffer = realloc(*buffer, *bufsize)) == NULL)
    {
      free(*buffer);

      fprintf(stderr, "Unable to expand string buffer to %d bytes!\n",
	      *bufsize);

      return (-1);
    }

    *bufptr = newbuffer + (*bufptr - *buffer);
  }

  *(*bufptr)++ = ch;

  return (0);
}


/*
 * 'mxml_parse_element()' - Parse an element for any attributes...
 */

static int				/* O - Terminating character */
mxml_parse_element(mxml_node_t *node,	/* I - Element node */
                   FILE        *fp)	/* I - File to read from */
{
  int	ch,				/* Current character in file */
	quote;				/* Quoting character */
  char	*name,				/* Attribute name */
	*value,				/* Attribute value */
	*ptr;				/* Pointer into name/value */
  int	namesize,			/* Size of name string */
	valsize;			/* Size of value string */


 /*
  * Initialize the name and value buffers...
  */

  if ((name = malloc(64)) == NULL)
  {
    fputs("Unable to allocate memory for name!\n", stderr);
    return (EOF);
  }

  namesize = 64;

  if ((value = malloc(64)) == NULL)
  {
    free(name);
    fputs("Unable to allocate memory for value!\n", stderr);
    return (EOF);
  }

  valsize = 64;

 /*
  * Loop until we hit a >, /, ?, or EOF...
  */

  while ((ch = getc(fp)) != EOF)
  {
   /*
    * Skip leading whitespace...
    */

    if (isspace(ch))
      continue;

   /*
    * Stop at /, ?, or >...
    */

    if (ch == '/' || ch == '?')
    {
     /*
      * Grab the > character and print an error if it isn't there...
      */

      quote = getc(fp);

      if (quote != '>')
      {
        fprintf(stderr, "Expected '>' after '%c' for element %s, but got '%c'!\n",
	        ch, node->value.element.name, quote);
        ch = EOF;
      }

      break;
    }
    else if (ch == '>')
      break;

   /*
    * Read the attribute name...
    */

    name[0] = ch;
    ptr     = name + 1;

    while ((ch = getc(fp)) != EOF)
      if (isspace(ch) || ch == '=' || ch == '/' || ch == '>' || ch == '?')
        break;
      else if (mxml_add_char(ch, &ptr, &name, &namesize))
      {
        free(name);
	free(value);
	return (EOF);
      }

    *ptr = '\0';

    if (ch == '=')
    {
     /*
      * Read the attribute value...
      */

      if ((ch = getc(fp)) == EOF)
      {
        fprintf(stderr, "Missing value for attribute '%s' in element %s!\n",
	        name, node->value.element.name);
        return (EOF);
      }

      if (ch == '\'' || ch == '\"')
      {
       /*
        * Read quoted value...
	*/

        quote = ch;
	ptr   = value;

        while ((ch = getc(fp)) != EOF)
	  if (ch == quote)
	    break;
	  else if (mxml_add_char(ch, &ptr, &value, &valsize))
	  {
            free(name);
	    free(value);
	    return (EOF);
	  }

        *ptr = '\0';
      }
      else
      {
       /*
        * Read unquoted value...
	*/

	value[0] = ch;
	ptr      = value + 1;

	while ((ch = getc(fp)) != EOF)
	  if (isspace(ch) || ch == '=' || ch == '/' || ch == '>')
            break;
	  else if (mxml_add_char(ch, &ptr, &value, &valsize))
	  {
            free(name);
	    free(value);
	    return (EOF);
	  }

        *ptr = '\0';
      }
    }
    else
      value[0] = '\0';

   /*
    * Save last character in case we need it...
    */

    if (ch == '/' || ch == '>' || ch == '?')
      ungetc(ch, fp);

   /*
    * Set the attribute...
    */

    mxmlElementSetAttr(node, name, value);
  }

 /*
  * Free the name and value buffers and return...
  */

  free(name);
  free(value);

  return (ch);
}


/*
 * 'mxml_write_node()' - Save an XML node to a file.
 */

static int				/* O - Column or -1 on error */
mxml_write_node(mxml_node_t *node,	/* I - Node to write */
                FILE        *fp,	/* I - File to write to */
	        int         (*cb)(mxml_node_t *, int),
					/* I - Whitespace callback */
		int         col)	/* I - Current column */
{
  int		i;			/* Looping var */
  int		n;			/* Chars written */
  mxml_attr_t	*attr;			/* Current attribute */


  while (node != NULL)
  {
   /*
    * Print the node value...
    */

    switch (node->type)
    {
      case MXML_ELEMENT :
          col = mxml_write_ws(node, fp, cb, MXML_WS_BEFORE_OPEN, col);

          if ((n = fprintf(fp, "<%s", node->value.element.name)) < 0)
	    return (-1);

          col += n;

	  for (i = node->value.element.num_attrs, attr = node->value.element.attrs;
	       i > 0;
	       i --, attr ++)
	  {
	    if ((col + strlen(attr->name) + strlen(attr->value) + 3) > MXML_WRAP)
	    {
	      if (putc('\n', fp) < 0)
	        return (-1);

	      col = 0;
	    }
	    else
	    {
	      if (putc(' ', fp) < 0)
	        return (-1);

	      col ++;
	    }

	    if ((n = fprintf(fp, "%s=\"%s\"", attr->name, attr->value)) < 0)
              return (-1);

            col += n;
	  }

	  if (node->child)
	  {
           /*
	    * The ? and ! elements are special-cases and have no end tags...
	    */

	    if (node->value.element.name[0] == '?')
	    {
	      if (fputs("?>\n", fp) < 0)
	        return (-1);

              col = 0;
            }
	    else if (putc('>', fp) < 0)
	      return (-1);
	    else
	      col ++;

            col = mxml_write_ws(node, fp, cb, MXML_WS_AFTER_OPEN, col);

	    if ((col = mxml_write_node(node->child, fp, cb, col)) < 0)
	      return (-1);

            if (node->value.element.name[0] != '?' &&
	        node->value.element.name[0] != '!')
	    {
              col = mxml_write_ws(node, fp, cb, MXML_WS_BEFORE_CLOSE, col);

	      if ((n = fprintf(fp, "</%s>", node->value.element.name)) < 0)
	        return (-1);

              col += n;

              col = mxml_write_ws(node, fp, cb, MXML_WS_AFTER_CLOSE, col);
	    }
	  }
	  else if (node->value.element.name[0] == '!')
	  {
	    if (putc('>', fp) < 0)
	      return (-1);
	    else
	      col ++;

            col = mxml_write_ws(node, fp, cb, MXML_WS_AFTER_OPEN, col);
          }
	  else if (fputs("/>", fp) < 0)
	    return (-1);
	  else
	  {
	    col += 2;

            col = mxml_write_ws(node, fp, cb, MXML_WS_AFTER_OPEN, col);
	  }
          break;

      case MXML_INTEGER :
	  if (node->prev)
	  {
	    if (col > MXML_WRAP)
	    {
	      if (putc('\n', fp) < 0)
	        return (-1);

	      col = 0;
	    }
	    else if (putc(' ', fp) < 0)
	      return (-1);
	    else
	      col ++;
          }

          if ((n = fprintf(fp, "%d", node->value.integer)) < 0)
	    return (-1);

	  col += n;
          break;

      case MXML_OPAQUE :
          if (mxml_write_string(node->value.opaque, fp) < 0)
	    return (-1);

          col += strlen(node->value.opaque);
          break;

      case MXML_REAL :
	  if (node->prev)
	  {
	    if (col > MXML_WRAP)
	    {
	      if (putc('\n', fp) < 0)
	        return (-1);

	      col = 0;
	    }
	    else if (putc(' ', fp) < 0)
	      return (-1);
	    else
	      col ++;
          }

          if ((n = fprintf(fp, "%f", node->value.real)) < 0)
	    return (-1);

	  col += n;
          break;

      case MXML_TEXT :
	  if (node->value.text.whitespace && col > 0)
	  {
	    if (col > MXML_WRAP)
	    {
	      if (putc('\n', fp) < 0)
	        return (-1);

	      col = 0;
	    }
	    else if (putc(' ', fp) < 0)
	      return (-1);
	    else
	      col ++;
          }

          if (mxml_write_string(node->value.text.string, fp) < 0)
	    return (-1);

	  col += strlen(node->value.text.string);
          break;
    }

   /*
    * Next node...
    */

    node = node->next;
  }

  return (col);
}


/*
 * 'mxml_write_string()' - Write a string, escaping & and < as needed.
 */

static int				/* O - 0 on success, -1 on failure */
mxml_write_string(const char *s,	/* I - String to write */
                  FILE       *fp)	/* I - File to write to */
{
  while (*s)
  {
    if (*s == '&')
    {
      if (fputs("&amp;", fp) < 0)
        return (-1);
    }
    else if (*s == '<')
    {
      if (fputs("&lt;", fp) < 0)
        return (-1);
    }
    else if (*s == '>')
    {
      if (fputs("&gt;", fp) < 0)
        return (-1);
    }
    else if (*s == '\"')
    {
      if (fputs("&quot;", fp) < 0)
        return (-1);
    }
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

        if (fputs("&nbsp;", fp) < 0)
	  return (-1);
      }
      else if (fprintf(fp, "&#x%x;", ch) < 0)
	return (-1);
    }
    else if (putc(*s, fp) < 0)
      return (-1);

    s ++;
  }

  return (0);
}


/*
 * 'mxml_write_ws()' - Do whitespace callback...
 */

static int				/* O - New column */
mxml_write_ws(mxml_node_t *node,	/* I - Current node */
              FILE        *fp,		/* I - File to write to */
              int         (*cb)(mxml_node_t *, int),
					/* I - Callback function */
	      int         ws,		/* I - Where value */
	      int         col)		/* I - Current column */
{
  int	ch;				/* Whitespace character */


  if (cb && (ch = (*cb)(node, ws)) != 0)
  {
    if (putc(ch, fp) < 0)
      return (-1);
    else if (ch == '\n')
      col = 0;
    else if (ch == '\t')
    {
      col += MXML_TAB;
      col = col - (col % MXML_TAB);
    }
    else
      col ++;
  }

  return (col);
}


/*
 * End of "$Id: mxml-file.c,v 1.11 2003/06/15 21:31:45 mike Exp $".
 */
