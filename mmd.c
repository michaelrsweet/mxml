/*
 * Implementation of miniature markdown library.
 *
 *     https://github.com/michaelrsweet/mmd
 *
 * Copyright 2017 by Michael R Sweet.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

/*
 * Include necessary headers...
 */

#include "mmd.h"
#include <stdlib.h>
#include <ctype.h>
#include <string.h>


/*
 * Structures...
 */

struct _mmd_s
{
  mmd_type_t    type;                   /* Node type */
  int           whitespace;             /* Leading whitespace? */
  char          *text,                  /* Text */
                *url;                   /* Reference URL (image/link/etc.) */
  mmd_t         *parent,                /* Parent node */
                *first_child,           /* First child node */
                *last_child,            /* Last child node */
                *prev_sibling,          /* Previous sibling node */
                *next_sibling;          /* Next sibling node */
};


/*
 * Local functions...
 */


static mmd_t    *mmd_add(mmd_t *parent, mmd_type_t type, int whitespace, char *text, char *url);
static void     mmd_free(mmd_t *node);
static void     mmd_parse_inline(mmd_t *parent, char *line);
static char     *mmd_parse_link(char *lineptr, char **text, char **url);
static void     mmd_remove(mmd_t *node);


/*
 * 'mmdFree()' - Free a markdown tree.
 */

void
mmdFree(mmd_t *node)                    /* I - First node */
{
  mmd_t *current,		        /* Current node */
	*next;			        /* Next node */


  mmd_remove(node);

  for (current = node->first_child; current; current = next)
  {
   /*
    * Get the next node...
    */

    if ((next = current->first_child) != NULL)
    {
     /*
      * Free parent nodes after child nodes have been freed...
      */

      current->first_child = NULL;
      continue;
    }

    if ((next = current->next_sibling) == NULL)
    {
     /*
      * Next node is the parent, which we'll free as needed...
      */

      if ((next = current->parent) == node)
        next = NULL;
    }

   /*
    * Free child...
    */

    mmd_free(current);
  }

 /*
  * Then free the memory used by the parent node...
  */

  mmd_free(node);
}


/*
 * 'mmdGetFirstChild()' - Return the first child of a node, if any.
 */

mmd_t *                                 /* O - First child or @code NULL@ if none */
mmdGetFirstChild(mmd_t *node)           /* I - Node */
{
  return (node ? node->first_child : NULL);
}


/*
 * 'mmdGetLastChild()' - Return the last child of a node, if any.
 */

mmd_t *                                 /* O - Last child or @code NULL@ if none */
mmdGetLastChild(mmd_t *node)            /* I - Node */
{
  return (node ? node->last_child : NULL);
}


/*
 * 'mmdGetMetadata()' - Return the metadata for the given keyword.
 */

const char *                            /* O - Value or @code NULL@ if none */
mmdGetMetadata(mmd_t      *doc,         /* I - Document */
               const char *keyword)     /* I - Keyword */
{
  mmd_t         *metadata,              /* Metadata node */
                *current;               /* Current node */
  char          prefix[256];            /* Prefix string */
  size_t        prefix_len;             /* Length of prefix string */
  const char    *value;                 /* Pointer to value */


  if (!doc || (metadata = doc->first_child) == NULL || metadata->type != MMD_TYPE_METADATA)
    return (NULL);

  snprintf(prefix, sizeof(prefix), "%s:", keyword);
  prefix_len = strlen(prefix);

  for (current = metadata->first_child; current; current = current->next_sibling)
  {
    if (strncmp(current->text, prefix, prefix_len))
      continue;

    value = current->text + prefix_len;
    while (isspace(*value & 255))
      value ++;

    return (value);
  }

  return (NULL);
}


/*
 * 'mmdGetNextSibling()' - Return the next sibling of a node, if any.
 */

mmd_t *                                 /* O - Next sibling or @code NULL@ if none */
mmdGetNextSibling(mmd_t *node)          /* I - Node */
{
  return (node ? node->next_sibling : NULL);
}


/*
 * 'mmdGetParent()' - Return the parent of a node, if any.
 */

mmd_t *                                 /* O - Parent node or @code NULL@ if none */
mmdGetParent(mmd_t *node)               /* I - Node */
{
  return (node ? node->parent : NULL);
}


/*
 * 'mmdGetPrevSibling()' - Return the previous sibling of a node, if any.
 */

mmd_t *                                 /* O - Previous sibling or @code NULL@ if none */
mmdGetPrevSibling(mmd_t *node)          /* I - Node */
{
  return (node ? node->prev_sibling : NULL);
}


/*
 * 'mmdGetText()' - Return the text associated with a node, if any.
 */

const char *                            /* O - Text or @code NULL@ if none */
mmdGetText(mmd_t *node)                 /* I - Node */
{
  return (node ? node->text : NULL);
}


/*
 * 'mmdGetType()' - Return the type of a node, if any.
 */

mmd_type_t                              /* O - Type or @code MMD_TYPE_NONE@ if none */
mmdGetType(mmd_t *node)                 /* I - Node */
{
  return (node ? node->type : MMD_TYPE_NONE);
}


/*
 * 'mmdGetURL()' - Return the URL associated with a node, if any.
 */

const char *                            /* O - URL or @code NULL@ if none */
mmdGetURL(mmd_t *node)                  /* I - Node */
{
  return (node ? node->url : NULL);
}


/*
 * 'mmdGetWhitespace()' - Return whether whitespace preceded a node.
 */

int                                     /* O - 1 for whitespace, 0 for none */
mmdGetWhitespace(mmd_t *node)           /* I - Node */
{
  return (node ? node->whitespace : 0);
}


/*
 * 'mmdIsBlock()' - Return whether the node is a block.
 */

int                                     /* O - 1 for block nodes, 0 otherwise */
mmdIsBlock(mmd_t *node)                 /* I - Node */
{
  return (node ? node->type < MMD_TYPE_NORMAL_TEXT : 0);
}


/*
 * 'mmdLoad()' - Load a markdown file into nodes.
 */

mmd_t *                                 /* O - First node in markdown */
mmdLoad(const char *filename)           /* I - File to load */
{
  FILE          *fp;                    /* File */
  mmd_t         *doc;                   /* Document */


 /*
  * Open the file and create an empty document...
  */

  if ((fp = fopen(filename, "r")) == NULL)
    return (NULL);

  doc = mmdLoadFile(fp);

  fclose(fp);

  return (doc);
}


/*
 * 'mmdLoadFile()' - Load a markdown file into nodes from a stdio file.
 */

mmd_t *                                 /* O - First node in markdown */
mmdLoadFile(FILE *fp)                   /* I - File to load */
{
  mmd_t         *doc,                   /* Document */
                *current,               /* Current parent block */
                *block = NULL;          /* Current block */
  mmd_type_t    type;                   /* Type for line */
  char          line[65536],            /* Line from file */
                *lineptr,               /* Pointer into line */
                *lineend;               /* End of line */
  int           blank_code = 0;         /* Saved indented blank code line */


 /*
  * Create an empty document...
  */

  doc = current = mmd_add(NULL, MMD_TYPE_DOCUMENT, 0, NULL, NULL);

  if (!doc)
  {
    fclose(fp);
    return (NULL);
  }

 /*
  * Read lines until end-of-file...
  */

  while (fgets(line, sizeof(line), fp))
  {
    lineptr = line;

    while (isspace(*lineptr & 255))
      lineptr ++;

    if ((lineptr - line) >= 4 && !block && (current == doc || current->type == MMD_TYPE_CODE_BLOCK))
    {
     /*
      * Indented code block.
      */

      if (current == doc)
        current = mmd_add(doc, MMD_TYPE_CODE_BLOCK, 0, NULL, NULL);

      if (blank_code)
        mmd_add(current, MMD_TYPE_CODE_TEXT, 0, "\n", NULL);

      mmd_add(current, MMD_TYPE_CODE_TEXT, 0, line + 4, NULL);

      blank_code = 0;
      continue;
    }
    else if (*lineptr == '`' && (!lineptr[1] || lineptr[1] == '`'))
    {
      if (block)
      {
        if (block->type == MMD_TYPE_CODE_BLOCK)
          block = NULL;
        else if (block->type == MMD_TYPE_LIST_ITEM)
          block = mmd_add(block, MMD_TYPE_CODE_BLOCK, 0, NULL, NULL);
        else if (block->parent->type == MMD_TYPE_LIST_ITEM)
          block = mmd_add(block->parent, MMD_TYPE_CODE_BLOCK, 0, NULL, NULL);
        else
          block = mmd_add(current, MMD_TYPE_CODE_BLOCK, 0, NULL, NULL);
      }
      else
        block = mmd_add(current, MMD_TYPE_CODE_BLOCK, 0, NULL, NULL);

      continue;
    }

    if (block && block->type == MMD_TYPE_CODE_BLOCK)
    {
      mmd_add(block, MMD_TYPE_CODE_TEXT, 0, line, NULL);
      continue;
    }
    else if (!strncmp(lineptr, "---", 3) && doc->first_child == NULL)
    {
     /*
      * Document metadata...
      */

      block = mmd_add(doc, MMD_TYPE_METADATA, 0, NULL, NULL);

      while (fgets(line, sizeof(line), fp))
      {
        lineptr = line;

        while (isspace(*lineptr & 255))
          lineptr ++;

        if (!strncmp(line, "---", 3) || !strncmp(line, "...", 3))
          break;

        lineend = lineptr + strlen(lineptr) - 1;
        if (lineend > lineptr && *lineend == '\n')
          *lineend = '\0';

        mmd_add(block, MMD_TYPE_METADATA_TEXT, 0, lineptr, NULL);
      }

      block = NULL;
      continue;
    }
    else if (!block && (!strncmp(lineptr, "---", 3) || !strncmp(lineptr, "***", 3) || !strncmp(lineptr, "___", 3)))
    {
      int ch = *lineptr;

      lineptr += 3;
      while (*lineptr && (*lineptr == ch || isspace(*lineptr & 255)))
        lineptr ++;

      if (!*lineptr)
      {
        block = NULL;
        mmd_add(current, MMD_TYPE_THEMATIC_BREAK, 0, NULL, NULL);
        continue;
      }
    }

    if (*lineptr == '>')
    {
     /*
      * Block quote.  See if the parent of the current node is already a block
      * quote...
      */

      if (current == doc || current->type != MMD_TYPE_BLOCK_QUOTE)
        current = mmd_add(doc, MMD_TYPE_BLOCK_QUOTE, 0, NULL, NULL);

     /*
      * Skip whitespace after the ">"...
      */

      lineptr ++;
      while (isspace(*lineptr & 255))
        lineptr ++;
    }
    else if (current->type == MMD_TYPE_BLOCK_QUOTE)
      current = current->parent;

    if (!*lineptr)
    {
      blank_code = current->type == MMD_TYPE_CODE_BLOCK;
      block      = NULL;
      continue;
    }
    else if (!strcmp(lineptr, "+"))
    {
      if (block)
      {
        if (block->type == MMD_TYPE_LIST_ITEM)
          block = mmd_add(block, MMD_TYPE_PARAGRAPH, 0, NULL, NULL);
        else if (block->parent->type == MMD_TYPE_LIST_ITEM)
          block = mmd_add(block->parent, MMD_TYPE_PARAGRAPH, 0, NULL, NULL);
        else
          block = NULL;
      }
      continue;
    }
    else if (block && block->type == MMD_TYPE_PARAGRAPH && (!strncmp(lineptr, "---", 3) || !strncmp(lineptr, "===", 3)))
    {
      int ch = *lineptr;

      lineptr += 3;
      while (*lineptr == ch)
        lineptr ++;
      while (isspace(*lineptr & 255))
        lineptr ++;

      if (!*lineptr)
      {
        if (ch == '=')
          block->type = MMD_TYPE_HEADING_1;
        else
          block->type = MMD_TYPE_HEADING_2;

        block = NULL;
        continue;
      }

      type = MMD_TYPE_PARAGRAPH;
    }
    else if ((*lineptr == '-' || *lineptr == '+' || *lineptr == '*') && isspace(lineptr[1] & 255))
    {
     /*
      * Bulleted list...
      */

      lineptr += 2;
      while (isspace(*lineptr & 255))
        lineptr ++;

      if (current == doc && doc->last_child && doc->last_child->type == MMD_TYPE_UNORDERED_LIST)
        current = doc->last_child;
      else if (current->type != MMD_TYPE_UNORDERED_LIST)
        current = mmd_add(current, MMD_TYPE_UNORDERED_LIST, 0, NULL, NULL);

      type  = MMD_TYPE_LIST_ITEM;
      block = NULL;
    }
    else if (isdigit(*lineptr & 255))
    {
     /*
      * Ordered list?
      */

      char *temp = lineptr + 1;

      while (isdigit(*temp & 255))
        temp ++;

      if (*temp == '.' && isspace(temp[1] & 255))
      {
       /*
        * Yes, ordered list.
        */

        lineptr = temp + 2;
        while (isspace(*lineptr & 255))
          lineptr ++;

        if (current == doc && doc->last_child && doc->last_child->type == MMD_TYPE_ORDERED_LIST)
          current = doc->last_child;
        else if (current->type != MMD_TYPE_ORDERED_LIST)
          current = mmd_add(current, MMD_TYPE_ORDERED_LIST, 0, NULL, NULL);

        type  = MMD_TYPE_LIST_ITEM;
        block = NULL;
      }
      else
      {
       /*
        * No, just a regular paragraph...
        */

        type = block ? block->type : MMD_TYPE_PARAGRAPH;
      }
    }
    else if (*lineptr == '#')
    {
     /*
      * Heading, count the number of '#' for the heading level...
      */

      char *temp = lineptr + 1;

      while (*temp == '#')
        temp ++;

      if ((temp - lineptr) <= 6)
      {
       /*
        * Heading 1-6...
        */

        type  = MMD_TYPE_HEADING_1 + (temp - lineptr - 1);
        block = NULL;

       /*
        * Skip whitespace after "#"...
        */

        lineptr = temp;
        while (isspace(*lineptr & 255))
          lineptr ++;

       /*
        * Strip trailing "#" characters...
        */

        for (temp = lineptr + strlen(lineptr) - 1; temp > lineptr && *temp == '#'; temp --)
          *temp = '\0';
      }
      else
      {
       /*
        * More than 6 #'s, just treat as a paragraph...
        */

        type = MMD_TYPE_PARAGRAPH;
      }

      if (current->type != MMD_TYPE_BLOCK_QUOTE)
        current = doc;
    }
    else if (!block)
    {
      type = MMD_TYPE_PARAGRAPH;

      if (lineptr == line)
        current = doc;
    }
    else
      type = block->type;

    if (!block || block->type != type)
    {
      if (current->type == MMD_TYPE_CODE_BLOCK)
        current = doc;

      block = mmd_add(current, type, 0, NULL, NULL);
    }

    mmd_parse_inline(block, lineptr);
  }

  return (doc);
}


/*
 * 'mmd_add()' - Add a new markdown node.
 */

static mmd_t *                          /* O - New node */
mmd_add(mmd_t      *parent,             /* I - Parent node */
        mmd_type_t type,                /* I - Node type */
        int        whitespace,          /* I - 1 if whitespace precedes this node */
        char       *text,               /* I - Text, if any */
        char       *url)                /* I - URL, if any */
{
  mmd_t         *temp;                  /* New node */


  if ((temp = calloc(1, sizeof(mmd_t))) != NULL)
  {
    if (parent)
    {
     /*
      * Add node to the parent...
      */

      temp->parent = parent;

      if (parent->last_child)
      {
        parent->last_child->next_sibling = temp;
        temp->prev_sibling               = parent->last_child;
        parent->last_child               = temp;
      }
      else
      {
        parent->first_child = parent->last_child = temp;
      }
    }

   /*
    * Copy the node values...
    */

    temp->type       = type;
    temp->whitespace = whitespace;

    if (text)
      temp->text = strdup(text);

    if (url)
      temp->url = strdup(url);
  }

  return (temp);
}


/*
 * 'mmd_free()' - Free memory used by a node.
 */

static void
mmd_free(mmd_t *node)                   /* I - Node */
{
  if (node->text)
    free(node->text);

  if (node->url)
    free(node->url);

  free(node);
}


/*
 * 'mmd_parse_inline()' - Parse inline formatting.
 */

static void
mmd_parse_inline(mmd_t *parent,         /* I - Parent node */
                 char  *line)           /* I - Line from file */
{
  mmd_type_t    type;                   /* Current node type */
  int           whitespace;             /* Whitespace precedes? */
  char          *lineptr,               /* Pointer into line */
                *text,                  /* Text fragment in line */
                *url;                   /* URL in link */


  whitespace = parent->last_child != NULL;

  for (lineptr = line, text = NULL, type = MMD_TYPE_NORMAL_TEXT; *lineptr; lineptr ++)
  {
    if (isspace(*lineptr & 255) && type != MMD_TYPE_CODE_TEXT)
    {
      if (text)
      {
        *lineptr = '\0';
        mmd_add(parent, type, whitespace, text, NULL);
        text = NULL;
      }

      whitespace = 1;
    }
    else if (*lineptr == '!' && lineptr[1] == '[' && type != MMD_TYPE_CODE_TEXT)
    {
     /*
      * Image...
      */

      if (text)
      {
        mmd_add(parent, type, whitespace, text, NULL);

        text       = NULL;
        whitespace = 0;
      }

      lineptr = mmd_parse_link(lineptr + 1, &text, &url);

      if (url)
        mmd_add(parent, MMD_TYPE_IMAGE, whitespace, text, url);

      if (!*lineptr)
        return;

      text = url = NULL;
      whitespace = 0;
      lineptr --;
    }
    else if (*lineptr == '[' && type != MMD_TYPE_CODE_TEXT)
    {
     /*
      * Link...
      */

      if (text)
      {
        mmd_add(parent, type, whitespace, text, NULL);

        text       = NULL;
        whitespace = 0;
      }

      lineptr = mmd_parse_link(lineptr, &text, &url);

      if (text && *text == '`')
      {
        char *end = text + strlen(text) - 1;

        text ++;
        if (end > text && *end == '`')
          *end = '\0';

        mmd_add(parent, MMD_TYPE_CODE_TEXT, whitespace, text, url);
      }
      else if (text)
        mmd_add(parent, MMD_TYPE_LINKED_TEXT, whitespace, text, url);

      if (!*lineptr)
        return;

      text = url = NULL;
      whitespace = 0;
      lineptr --;
    }
    else if (*lineptr == '<' && type != MMD_TYPE_CODE_TEXT && strchr(lineptr + 1, '>'))
    {
     /*
      * Autolink...
      */

      if (text)
      {
        mmd_add(parent, type, whitespace, text, NULL);

        text       = NULL;
        whitespace = 0;
      }

      url     = lineptr + 1;
      lineptr = strchr(lineptr + 1, '>');

      *lineptr++ = '\0';

      mmd_add(parent, MMD_TYPE_LINKED_TEXT, whitespace, url, url);

      text = url = NULL;
      whitespace = 0;
      lineptr --;
    }
    else if (*lineptr == '*' && type != MMD_TYPE_CODE_TEXT)
    {
      if (text)
      {
        *lineptr = '\0';

        mmd_add(parent, type, whitespace, text, NULL);

        *lineptr   = '*';
        text       = NULL;
        whitespace = 0;
      }

      if (type == MMD_TYPE_NORMAL_TEXT)
      {
        if (lineptr[1] == '*' && !isspace(lineptr[2] & 255))
        {
          type = MMD_TYPE_STRONG_TEXT;
          lineptr ++;
        }
        else if (!isspace(lineptr[1] & 255))
        {
          type = MMD_TYPE_EMPHASIZED_TEXT;
        }

        text = lineptr + 1;
      }
      else
      {
        if (lineptr[1] == '*')
          lineptr ++;

        type = MMD_TYPE_NORMAL_TEXT;
      }
    }
    else if (*lineptr == '`')
    {
      if (text)
      {
        *lineptr = '\0';
        mmd_add(parent, type, whitespace, text, NULL);

        text       = NULL;
        whitespace = 0;
      }

      if (type == MMD_TYPE_CODE_TEXT)
      {
        type = MMD_TYPE_NORMAL_TEXT;
      }
      else
      {
        type = MMD_TYPE_CODE_TEXT;
        text = lineptr + 1;
      }
    }
    else if (!text)
    {
      if (*lineptr == '\\' && lineptr[1])
      {
       /*
        * Escaped character...
        */

        lineptr ++;
      }

      text = lineptr;
    }
    else if (*lineptr == '\\' && lineptr[1])
    {
     /*
      * Escaped character...
      */

      memmove(lineptr, lineptr + 1, strlen(lineptr));
    }
  }

  if (text)
    mmd_add(parent, type, whitespace, text, NULL);
}


/*
 * 'mmd_parse_link()' - Parse a link.
 */

static char *                           /* O - End of link text */
mmd_parse_link(char       *lineptr,     /* I - Pointer into line */
               char       **text,       /* O - Text */
               char       **url)        /* O - URL */
{
  lineptr ++; /* skip "[" */

  *text = lineptr;
  *url  = NULL;

  while (*lineptr && *lineptr != ']')
  {
    if (*lineptr == '\"')
    {
      lineptr ++;
      while (*lineptr && *lineptr != '\"')
        lineptr ++;

      if (!*lineptr)
        return (lineptr);
    }

    lineptr ++;
  }

  if (!*lineptr)
    return (lineptr);

  *lineptr++ = '\0';

  while (isspace(*lineptr & 255))
    lineptr ++;

  if (*lineptr == '(')
  {
   /*
    * Get URL...
    */

    lineptr ++;
    *url = lineptr;

    while (*lineptr && *lineptr != ')')
    {
      if (isspace(*lineptr & 255))
        *lineptr = '\0';
      else if (*lineptr == '\"')
      {
        lineptr ++;
        while (*lineptr && *lineptr != '\"')
          lineptr ++;

        if (!*lineptr)
          return (lineptr);
      }

      lineptr ++;
    }

    *lineptr++ = '\0';
  }

  return (lineptr);
}


/*
 * 'mmd_remove()' - Remove a node from its parent.
 */

static void
mmd_remove(mmd_t *node)                 /* I - Node */
{
  if (node->parent)
  {
    if (node->prev_sibling)
      node->prev_sibling->next_sibling = node->next_sibling;
    else
      node->parent->first_child = node->next_sibling;

    if (node->next_sibling)
      node->next_sibling->prev_sibling = node->prev_sibling;
    else
      node->parent->last_child = node->prev_sibling;

    node->parent       = NULL;
    node->prev_sibling = NULL;
    node->next_sibling = NULL;
  }
}
