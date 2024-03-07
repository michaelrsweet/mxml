//
// Attribute support code for Mini-XML, a small XML file parsing library.
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
// Local functions...
//

static bool	mxml_set_attr(mxml_node_t *node, const char *name, char *value);


//
// 'mxmlElementDeleteAttr()' - Delete an attribute.
//

void
mxmlElementDeleteAttr(mxml_node_t *node,// I - Element
                      const char  *name)// I - Attribute name
{
  size_t	i;			// Looping var
  _mxml_attr_t	*attr;			// Cirrent attribute


  MXML_DEBUG("mxmlElementDeleteAttr(node=%p, name=\"%s\")\n", node, name ? name : "(null)");

  // Range check input...
  if (!node || node->type != MXML_TYPE_ELEMENT || !name)
    return;

  // Look for the attribute...
  for (i = node->value.element.num_attrs, attr = node->value.element.attrs; i > 0; i --, attr ++)
  {
    MXML_DEBUG("mxmlElementDeleteAttr: %s=\"%s\"\n", attr->name, attr->value);

    if (!strcmp(attr->name, name))
    {
      // Delete this attribute...
      _mxml_strfree(attr->name);
      _mxml_strfree(attr->value);

      i --;
      if (i > 0)
        memmove(attr, attr + 1, i * sizeof(_mxml_attr_t));

      node->value.element.num_attrs --;

      if (node->value.element.num_attrs == 0)
        free(node->value.element.attrs);
      return;
    }
  }
}


//
// 'mxmlElementGetAttr()' - Get an attribute.
//
// This function returns @code NULL@ if the node is not an element or the
// named attribute does not exist.
//

const char *				// O - Attribute value or @code NULL@
mxmlElementGetAttr(mxml_node_t *node,	// I - Element node
                   const char  *name)	// I - Name of attribute
{
  size_t	i;			// Looping var
  _mxml_attr_t	*attr;			// Cirrent attribute


  MXML_DEBUG("mxmlElementGetAttr(node=%p, name=\"%s\")\n", node, name ? name : "(null)");

  // Range check input...
  if (!node || node->type != MXML_TYPE_ELEMENT || !name)
    return (NULL);

  // Look for the attribute...
  for (i = node->value.element.num_attrs, attr = node->value.element.attrs; i > 0; i --, attr ++)
  {
    MXML_DEBUG("mxmlElementGetAttr: %s=\"%s\"\n", attr->name, attr->value);

    if (!strcmp(attr->name, name))
    {
      MXML_DEBUG("mxmlElementGetAttr: Returning \"%s\".\n", attr->value);
      return (attr->value);
    }
  }

  // Didn't find attribute, so return NULL...
  MXML_DEBUG("mxmlElementGetAttr: Returning NULL.\n");

  return (NULL);
}


//
// 'mxmlElementGetAttrByIndex()' - Get an element attribute by index.
//
// The index ("idx") is 0-based.  @code NULL@ is returned if the specified index
// is out of range.
//

const char *                            // O - Attribute value
mxmlElementGetAttrByIndex(
    mxml_node_t *node,                  // I - Node
    int         idx,                    // I - Attribute index, starting at 0
    const char  **name)                 // O - Attribute name
{
  if (!node || node->type != MXML_TYPE_ELEMENT || idx < 0 || idx >= node->value.element.num_attrs)
    return (NULL);

  if (name)
    *name = node->value.element.attrs[idx].name;

  return (node->value.element.attrs[idx].value);
}


//
// 'mxmlElementGetAttrCount()' - Get the number of element attributes.
//

size_t					// O - Number of attributes
mxmlElementGetAttrCount(
    mxml_node_t *node)			// I - Node
{
  if (node && node->type == MXML_TYPE_ELEMENT)
    return (node->value.element.num_attrs);
  else
    return (0);
}


//
// 'mxmlElementSetAttr()' - Set an attribute.
//
// If the named attribute already exists, the value of the attribute
// is replaced by the new string value. The string value is copied
// into the element node. This function does nothing if the node is
// not an element.
//

void
mxmlElementSetAttr(mxml_node_t *node,	// I - Element node
                   const char  *name,	// I - Name of attribute
                   const char  *value)	// I - Attribute value
{
  char	*valuec;			// Copy of value


  MXML_DEBUG("mxmlElementSetAttr(node=%p, name=\"%s\", value=\"%s\")\n", node, name ? name : "(null)", value ? value : "(null)");

  // Range check input...
  if (!node || node->type != MXML_TYPE_ELEMENT || !name)
    return;

  if (value)
  {
    if ((valuec = _mxml_strcopy(value)) == NULL)
    {
      _mxml_error("Unable to allocate memory for attribute '%s' in element %s.", name, node->value.element.name);
      return;
    }
  }
  else
  {
    valuec = NULL;
  }

  if (!mxml_set_attr(node, name, valuec))
    _mxml_strfree(valuec);
}


//
// 'mxmlElementSetAttrf()' - Set an attribute with a formatted value.
//
// If the named attribute already exists, the value of the attribute
// is replaced by the new formatted string. The formatted string value is
// copied into the element node. This function does nothing if the node
// is not an element.
//

void
mxmlElementSetAttrf(mxml_node_t *node,	// I - Element node
                    const char  *name,	// I - Name of attribute
                    const char  *format,// I - Printf-style attribute value
		    ...)		// I - Additional arguments as needed
{
  va_list	ap;			// Argument pointer
  char		buffer[16384];		// Format buffer
  char		*value;			// Value


  MXML_DEBUG("mxmlElementSetAttrf(node=%p, name=\"%s\", format=\"%s\", ...)\n", node, name ? name : "(null)", format ? format : "(null)");

  // Range check input...
  if (!node || node->type != MXML_TYPE_ELEMENT || !name || !format)
    return;

  // Format the value...
  va_start(ap, format);
  vsnprintf(buffer, sizeof(buffer), format, ap);
  va_end(ap);

  if ((value = _mxml_strcopy(buffer)) == NULL)
    _mxml_error("Unable to allocate memory for attribute '%s' in element %s.", name, node->value.element.name);
  else if (!mxml_set_attr(node, name, value))
    _mxml_strfree(value);
}


//
// 'mxml_set_attr()' - Set or add an attribute name/value pair.
//

static bool				// O - `true` on success, `false` on failure
mxml_set_attr(mxml_node_t *node,	// I - Element node
              const char  *name,	// I - Attribute name
              char        *value)	// I - Attribute value
{
  int		i;			// Looping var
  _mxml_attr_t	*attr;			// New attribute


  // Look for the attribute...
  for (i = node->value.element.num_attrs, attr = node->value.element.attrs; i > 0; i --, attr ++)
  {
    if (!strcmp(attr->name, name))
    {
      // Free the old value as needed...
      _mxml_strfree(attr->value);
      attr->value = value;

      return (true);
    }
  }

  // Add a new attribute...
  if ((attr = realloc(node->value.element.attrs, (node->value.element.num_attrs + 1) * sizeof(_mxml_attr_t))) == NULL)
  {
    _mxml_error("Unable to allocate memory for attribute '%s' in element %s.", name, node->value.element.name);
    return (false);
  }

  node->value.element.attrs = attr;
  attr += node->value.element.num_attrs;

  if ((attr->name = _mxml_strcopy(name)) == NULL)
  {
    _mxml_error("Unable to allocate memory for attribute '%s' in element %s.", name, node->value.element.name);
    return (false);
  }

  attr->value = value;

  node->value.element.num_attrs ++;

  return (true);
}
