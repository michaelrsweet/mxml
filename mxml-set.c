//
// Node set functions for Mini-XML, a small XML file parsing library.
//
// https://www.msweet.org/mxml
//
// Copyright © 2003-2024 by Michael R Sweet.
//
// Licensed under Apache License v2.0.  See the file "LICENSE" for more
// information.
//

#include "mxml-private.h"


//
// 'mxmlSetCDATA()' - Set the data for a CDATA node.
//
// The node is not changed if it (or its first child) is not a CDATA node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetCDATA(mxml_node_t *node,		// I - Node to set
             const char  *data)		// I - New data string
{
  char	*s;				// New element name


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_CDATA)
    node = node->child;

  if (!node || node->type != MXML_TYPE_CDATA)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!data)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  if (data == node->value.cdata)
  {
    // Don't change the value...
    return (true);
  }

  // Allocate the new value, free any old element value, and set the new value...
  if ((s = strdup(data)) == NULL)
  {
    _mxml_error("Unable to allocate memory for CDATA.");
    return (false);
  }

  free(node->value.cdata);
  node->value.cdata = s;

  return (true);
}


//
// 'mxmlSetCDATAf()' - Set the data for a CDATA to a formatted string.
//

bool					// O - `true` on success, `false` on failure
mxmlSetCDATAf(mxml_node_t *node,	// I - Node
	      const char *format,	// I - `printf`-style format string
	      ...)			// I - Additional arguments as needed
{
  va_list	ap;			// Pointer to arguments
  char		buffer[16384];		// Format buffer
  char		*s;			// Temporary string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_CDATA)
    node = node->child;

  if (!node || node->type != MXML_TYPE_CDATA)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!format)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  // Format the new string, free any old string value, and set the new value...
  va_start(ap, format);
  vsnprintf(buffer, sizeof(buffer), format, ap);
  va_end(ap);

  if ((s = strdup(buffer)) == NULL)
  {
    _mxml_error("Unable to allocate memory for CDATA string.");
    return (false);
  }

  free(node->value.cdata);
  node->value.cdata = s;

  return (true);
}


//
// 'mxmlSetComment()' - Set a comment to a literal string.
//

bool					// O - `true` on success, `false` on failure
mxmlSetComment(mxml_node_t *node,	// I - Node
               const char  *comment)	// I - Literal string
{
  char *s;				// New string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_COMMENT)
    node = node->child;

  if (!node || node->type != MXML_TYPE_COMMENT)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!comment)
  {
    _mxml_error("NULL comment not allowed.");
    return (false);
  }

  if (comment == node->value.comment)
    return (true);

  // Free any old string value and set the new value...
  if ((s = strdup(comment)) == NULL)
  {
    _mxml_error("Unable to allocate memory for comment string.");
    return (false);
  }

  free(node->value.comment);
  node->value.comment = s;

  return (true);
}


//
// 'mxmlSetCommentf()' - Set a comment to a formatted string.
//

bool					// O - `true` on success, `false` on failure
mxmlSetCommentf(mxml_node_t *node,	// I - Node
                const char *format,	// I - `printf`-style format string
		...)			// I - Additional arguments as needed
{
  va_list	ap;			// Pointer to arguments
  char		buffer[16384];		// Format buffer
  char		*s;			// Temporary string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_COMMENT)
    node = node->child;

  if (!node || node->type != MXML_TYPE_COMMENT)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!format)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  // Format the new string, free any old string value, and set the new value...
  va_start(ap, format);
  vsnprintf(buffer, sizeof(buffer), format, ap);
  va_end(ap);

  if ((s = strdup(buffer)) == NULL)
  {
    _mxml_error("Unable to allocate memory for comment string.");
    return (false);
  }

  free(node->value.comment);
  node->value.comment = s;

  return (true);
}


//
// 'mxmlSetCustom()' - Set the data and destructor of a custom data node.
//
// The node is not changed if it (or its first child) is not a custom node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetCustom(
    mxml_node_t              *node,	// I - Node to set
    void                     *data,	// I - New data pointer
    mxml_custom_destroy_cb_t destroy)	// I - New destructor function
{
  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_CUSTOM)
    node = node->child;

  if (!node || node->type != MXML_TYPE_CUSTOM)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }

  if (data == node->value.custom.data)
  {
    node->value.custom.destroy = destroy;
    return (true);
  }

  // Free any old element value and set the new value...
  if (node->value.custom.data && node->value.custom.destroy)
    (*(node->value.custom.destroy))(node->value.custom.data);

  node->value.custom.data    = data;
  node->value.custom.destroy = destroy;

  return (true);
}


//
// 'mxmlSetDeclaration()' - Set a comment to a literal string.
//

bool					// O - `true` on success, `false` on failure
mxmlSetDeclaration(
    mxml_node_t *node,			// I - Node
    const char  *declaration)		// I - Literal string
{
  char *s;				// New string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_DECLARATION)
    node = node->child;

  if (!node || node->type != MXML_TYPE_DECLARATION)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!declaration)
  {
    _mxml_error("NULL declaration not allowed.");
    return (false);
  }

  if (declaration == node->value.declaration)
    return (true);

  // Free any old string value and set the new value...
  if ((s = strdup(declaration)) == NULL)
  {
    _mxml_error("Unable to allocate memory for declaration string.");
    return (false);
  }

  free(node->value.declaration);
  node->value.declaration = s;

  return (true);
}


//
// 'mxmlSetDeclarationf()' - Set a comment to a formatted string.
//

bool					// O - `true` on success, `false` on failure
mxmlSetDeclarationf(mxml_node_t *node,	// I - Node
                    const char *format,	// I - `printf`-style format string
		    ...)		// I - Additional arguments as needed
{
  va_list	ap;			// Pointer to arguments
  char		buffer[16384];		// Format buffer
  char		*s;			// Temporary string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_COMMENT)
    node = node->child;

  if (!node || node->type != MXML_TYPE_COMMENT)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!format)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  // Format the new string, free any old string value, and set the new value...
  va_start(ap, format);
  vsnprintf(buffer, sizeof(buffer), format, ap);
  va_end(ap);

  if ((s = strdup(buffer)) == NULL)
  {
    _mxml_error("Unable to allocate memory for declaration string.");
    return (false);
  }

  free(node->value.declaration);
  node->value.declaration = s;

  return (true);
}


//
// 'mxmlSetDirective()' - Set a directive to a literal string.
//

bool					// O - `true` on success, `false` on failure
mxmlSetDirective(mxml_node_t *node,	// I - Node
                 const char  *directive)// I - Literal string
{
  char *s;				// New string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_DIRECTIVE)
    node = node->child;

  if (!node || node->type != MXML_TYPE_DIRECTIVE)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!directive)
  {
    _mxml_error("NULL directive not allowed.");
    return (false);
  }

  if (directive == node->value.directive)
    return (true);

  // Free any old string value and set the new value...
  if ((s = strdup(directive)) == NULL)
  {
    _mxml_error("Unable to allocate memory for directive string.");
    return (false);
  }

  free(node->value.directive);
  node->value.directive = s;

  return (true);
}


//
// 'mxmlSetDirectivef()' - Set a directive to a formatted string.
//

bool					// O - `true` on success, `false` on failure
mxmlSetDirectivef(mxml_node_t *node,	// I - Node
                  const char *format,	// I - `printf`-style format string
		  ...)			// I - Additional arguments as needed
{
  va_list	ap;			// Pointer to arguments
  char		buffer[16384];		// Format buffer
  char		*s;			// Temporary string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_DIRECTIVE)
    node = node->child;

  if (!node || node->type != MXML_TYPE_DIRECTIVE)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!format)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  // Format the new string, free any old string value, and set the new value...
  va_start(ap, format);
  vsnprintf(buffer, sizeof(buffer), format, ap);
  va_end(ap);

  if ((s = strdup(buffer)) == NULL)
  {
    _mxml_error("Unable to allocate memory for directive string.");
    return (false);
  }

  free(node->value.directive);
  node->value.directive = s;

  return (true);
}


//
// 'mxmlSetElement()' - Set the name of an element node.
//
// The node is not changed if it is not an element node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetElement(mxml_node_t *node,	// I - Node to set
               const char  *name)	// I - New name string
{
  char *s;				// New name string


  // Range check input...
  if (!node || node->type != MXML_TYPE_ELEMENT)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!name)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  if (name == node->value.element.name)
    return (true);

  // Free any old element value and set the new value...
  if ((s = strdup(name)) == NULL)
  {
    _mxml_error("Unable to allocate memory for element name.");
    return (false);
  }

  free(node->value.element.name);
  node->value.element.name = s;

  return (true);
}


//
// 'mxmlSetInteger()' - Set the value of an integer node.
//
// The node is not changed if it (or its first child) is not an integer node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetInteger(mxml_node_t *node,	// I - Node to set
               long        integer)	// I - Integer value
{
  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_INTEGER)
    node = node->child;

  if (!node || node->type != MXML_TYPE_INTEGER)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }

  // Set the new value and return...
  node->value.integer = integer;

  return (true);
}


//
// 'mxmlSetOpaque()' - Set the value of an opaque node.
//
// The node is not changed if it (or its first child) is not an opaque node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetOpaque(mxml_node_t *node,	// I - Node to set
              const char  *opaque)	// I - Opaque string
{
  char *s;				// New opaque string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_OPAQUE)
    node = node->child;

  if (!node || node->type != MXML_TYPE_OPAQUE)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!opaque)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  if (node->value.opaque == opaque)
    return (true);

  // Free any old opaque value and set the new value...
  if ((s = strdup(opaque)) == NULL)
  {
    _mxml_error("Unable to allocate memory for opaque string.");
    return (false);
  }

  free(node->value.opaque);
  node->value.opaque = s;

  return (true);
}


//
// 'mxmlSetOpaquef()' - Set the value of an opaque string node to a formatted string.
//
// The node is not changed if it (or its first child) is not an opaque node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetOpaquef(mxml_node_t *node,	// I - Node to set
               const char  *format,	// I - Printf-style format string
	       ...)			// I - Additional arguments as needed
{
  va_list	ap;			// Pointer to arguments
  char		buffer[16384];		// Format buffer
  char		*s;			// Temporary string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_OPAQUE)
    node = node->child;

  if (!node || node->type != MXML_TYPE_OPAQUE)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!format)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  // Format the new string, free any old string value, and set the new value...
  va_start(ap, format);
  vsnprintf(buffer, sizeof(buffer), format, ap);
  va_end(ap);

  if ((s = strdup(buffer)) == NULL)
  {
    _mxml_error("Unable to allocate memory for opaque string.");
    return (false);
  }

  free(node->value.opaque);
  node->value.opaque = s;

  return (true);
}


//
// 'mxmlSetReal()' - Set the value of a real number node.
//
// The node is not changed if it (or its first child) is not a real number node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetReal(mxml_node_t *node,		// I - Node to set
            double      real)		// I - Real number value
{
 /*
  * Range check input...
  */

  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_REAL)
    node = node->child;

  if (!node || node->type != MXML_TYPE_REAL)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }

  // Set the new value and return...
  node->value.real = real;

  return (true);
}


//
// 'mxmlSetText()' - Set the value of a text node.
//
// The node is not changed if it (or its first child) is not a text node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetText(mxml_node_t *node,		// I - Node to set
            bool        whitespace,	// I - `true` = leading whitespace, `false` = no whitespace
	    const char  *string)	// I - String
{
  char *s;				// New string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_TEXT)
    node = node->child;

  if (!node || node->type != MXML_TYPE_TEXT)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!string)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  if (string == node->value.text.string)
  {
    node->value.text.whitespace = whitespace;
    return (true);
  }

  // Free any old string value and set the new value...
  if ((s = strdup(string)) == NULL)
  {
    _mxml_error("Unable to allocate memory for text string.");
    return (false);
  }

  free(node->value.text.string);

  node->value.text.whitespace = whitespace;
  node->value.text.string     = s;

  return (true);
}


//
// 'mxmlSetTextf()' - Set the value of a text node to a formatted string.
//
// The node is not changed if it (or its first child) is not a text node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetTextf(mxml_node_t *node,		// I - Node to set
             bool        whitespace,	// I - `true` = leading whitespace, `false` = no whitespace
             const char  *format,	// I - Printf-style format string
	     ...)			// I - Additional arguments as needed
{
  va_list	ap;			// Pointer to arguments
  char		buffer[16384];		// Format buffer
  char		*s;			// Temporary string


  // Range check input...
  if (node && node->type == MXML_TYPE_ELEMENT && node->child && node->child->type == MXML_TYPE_TEXT)
    node = node->child;

  if (!node || node->type != MXML_TYPE_TEXT)
  {
    _mxml_error("Wrong node type.");
    return (false);
  }
  else if (!format)
  {
    _mxml_error("NULL string not allowed.");
    return (false);
  }

  // Free any old string value and set the new value...
  va_start(ap, format);
  vsnprintf(buffer, sizeof(buffer), format, ap);
  va_end(ap);

  if ((s = strdup(buffer)) == NULL)
  {
    _mxml_error("Unable to allocate memory for text string.");
    return (false);
  }

  free(node->value.text.string);

  node->value.text.whitespace = whitespace;
  node->value.text.string     = s;

  return (true);
}


//
// 'mxmlSetUserData()' - Set the user data pointer for a node.
//

bool					// O - `true` on success, `false` on failure
mxmlSetUserData(mxml_node_t *node,	// I - Node to set
                void        *data)	// I - User data pointer
{
  // Range check input...
  if (!node)
    return (false);

  // Set the user data pointer and return...
  node->user_data = data;
  return (true);
}
