/*
 * "$Id: mxml-file.c,v 1.2 2003/06/04 00:25:59 mike Exp $"
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
 *   mxml_write_string()  - Write a string, escaping & and < as needed.
 */

/*
 * Include necessary headers...
 */

#include "mxml.h"


/*
 * Local functions...
 */

static int	mxml_parse_element(mxml_node_t *node, FILE *fp);
static int	mxml_write_string(const char *s, FILE *fp);


/*
 * 'mxmlLoadFile()' - Load a file into an XML node tree.
 */

mxml_node_t *				/* O - First node */
mxmlLoadFile(mxml_node_t *top,		/* I - Top node */
             FILE        *fp,		/* I - File to read from */
             mxml_type_t (*cb)(mxml_node_t *))
					/* I - Callback function */
{
  mxml_node_t	*node,			/* Current node */
		*parent;		/* Current parent node */
  int		ch,			/* Character from file */
		whitespace;		/* Non-zero if whitespace seen */
  char		buffer[16384],		/* String buffer */
		*bufptr;		/* Pointer into buffer */
  mxml_type_t	type;			/* Current node type */


 /*
  * Read elements and other nodes from the file...
  */

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
      bufptr  = buffer;

      switch (type)
      {
	case MXML_INTEGER :
            node = mxmlNewInteger(parent, strtol(buffer, NULL, 0));
	    break;

	case MXML_OPAQUE :
            node = mxmlNewOpaque(parent, buffer);
	    break;

	case MXML_REAL :
            node = mxmlNewReal(parent, strtod(buffer, NULL));
	    break;

	case MXML_TEXT :
            node = mxmlNewText(parent, whitespace, buffer);
	    break;

        default : /* Should never happen... */
	    node = NULL;
	    break;
      }	  

      whitespace = isspace(ch) != 0;

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

    if (ch == '<')
    {
     /*
      * Start of open/close tag...
      */

      bufptr = buffer;

      while ((ch = getc(fp)) != EOF)
        if (isspace(ch) || ch == '>' || (ch == '/' && bufptr > buffer))
	  break;
	else if (bufptr < (buffer + sizeof(buffer) - 1))
	  *bufptr++ = ch;

      *bufptr = '\0';
      bufptr  = buffer;

      if (buffer[0] == '/')
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

	if (cb)
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

	  if (cb)
	    type = (*cb)(parent);
	}
      }
    }
    else if (ch == '&')
    {
     /*
      * Add character entity to current buffer...  Currently we only
      * support &lt;, &amp;, &#nnn;, and &#xXXXX;...
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
      else if (!strcmp(entity, "&lt"))
        ch = '<';
      else if (!strcmp(entity, "&amp"))
        ch = '&';
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

	if (bufptr < (buffer + sizeof(buffer) - 1))
          *bufptr++ = ch;
	else
	{
          fprintf(stderr, "String too long in file under parent <%s>!\n",
	          parent ? parent->value.element.name : "null");
	  break;
	}
      }
      else
      {
       /*
        * Use UTF-8 encoding for the Unicode char...
	*/

	if (bufptr < (buffer + sizeof(buffer) - 5))
	{
	  if (ch < 2048)
	  {
	    *bufptr++ = 0xc0 | (ch >> 6);
	    *bufptr++ = 0x80 | (ch & 63);
          }
	  else if (ch < 65536)
	  {
	    *bufptr++ = 0xe0 | (ch >> 12);
	    *bufptr++ = 0x80 | ((ch >> 6) & 63);
	    *bufptr++ = 0x80 | (ch & 63);
	  }
	  else
	  {
	    *bufptr++ = 0xf0 | (ch >> 18);
	    *bufptr++ = 0x80 | ((ch >> 12) & 63);
	    *bufptr++ = 0x80 | ((ch >> 6) & 63);
	    *bufptr++ = 0x80 | (ch & 63);
	  }
	}
	else
	{
          fprintf(stderr, "String too long in file under parent <%s>!\n",
	          parent ? parent->value.element.name : "null");
	  break;
	}
      }
    }
    else if (type == MXML_OPAQUE || !isspace(ch))
    {
     /*
      * Add character to current buffer...
      */

      if (bufptr < (buffer + sizeof(buffer) - 1))
        *bufptr++ = ch;
      else
      {
        fprintf(stderr, "String too long in file under parent <%s>!\n",
	        parent ? parent->value.element.name : "null");
	break;
      }
    }
  }

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
 */

int					/* O - 0 on success, -1 on error */
mxmlSaveFile(mxml_node_t *node,		/* I - Node to write */
             FILE        *fp)		/* I - File to write to */
{
  int	i;				/* Looping var */


  while (node != NULL)
  {
   /*
    * Print the node value...
    */

    switch (node->type)
    {
      case MXML_ELEMENT :
          if (fprintf(fp, "<%s", node->value.element.name) < 0)
	    return (-1);

	  for (i = 0; i < node->value.element.num_attrs; i ++)
	    if (fprintf(fp, " %s=\"%s\"", node->value.element.attrs[i].name,
	                node->value.element.attrs[i].value) < 0)
              return (-1);

	  if (node->child)
	  {
           /*
	    * The ?xml element is a special-case and has no end tag...
	    */

	    if (node->value.element.name[0] == '?')
	    {
	      if (fputs("?>\n", fp) < 0)
	        return (-1);
            }
	    else if (putc('>', fp) < 0)
	      return (-1);

	    if (mxmlSaveFile(node->child, fp) < 0)
	      return (-1);

            if (node->value.element.name[0] != '?')
	      if (fprintf(fp, "</%s>", node->value.element.name) < 0)
	        return (-1);
	  }
	  else if (fputs("/>", fp) < 0)
	    return (-1);
          break;

      case MXML_INTEGER :
	  if (node->prev)
	    if (putc(' ', fp) < 0)
	      return (-1);

          if (fprintf(fp, "%d", node->value.integer) < 0)
	    return (-1);
          break;

      case MXML_OPAQUE :
          if (mxml_write_string(node->value.opaque, fp) < 0)
	    return (-1);
          break;

      case MXML_REAL :
	  if (node->prev)
	    if (putc(' ', fp) < 0)
	      return (-1);

          if (fprintf(fp, "%f", node->value.real) < 0)
	    return (-1);
          break;

      case MXML_TEXT :
	  if (node->value.text.whitespace)
	    if (putc(' ', fp) < 0)
	      return (-1);

          if (mxml_write_string(node->value.text.string, fp) < 0)
	    return (-1);
          break;
    }

   /*
    * Next node...
    */

    node = node->next;
  }

 /*
  * Return 0 (success)...
  */

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
  char	name[256],			/* Attribute name */
	value[256],			/* Attribute value */
	*ptr;				/* Pointer into name/value */


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
      else if (ptr < (name + sizeof(name) - 1))
        *ptr++ = ch;
      else
      {
        fprintf(stderr, "Attribute name too long for element %s!\n",
	        node->value.element.name);
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
	  else if (ptr < (value + sizeof(value) - 1))
            *ptr++ = ch;
	  else
	  {
            fprintf(stderr, "Attribute value too long for attribute '%s' in element %s!\n",
	            name, node->value.element.name);
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
	  else if (ptr < (value + sizeof(value) - 1))
            *ptr++ = ch;
	  else
	  {
            fprintf(stderr, "Attribute value too long for attribute '%s' in element %s!\n",
	            name, node->value.element.name);
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

  return (ch);
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
    else if (putc(*s, fp) < 0)
      return (-1);

    s ++;
  }

  return (0);
}



/*
 * End of "$Id: mxml-file.c,v 1.2 2003/06/04 00:25:59 mike Exp $".
 */
