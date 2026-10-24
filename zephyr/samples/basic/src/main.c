//
// Sample code for Mini-XML, read and modify XML documents.
//
// https://www.msweet.org/mxml
//
// Copyright © 2026 by Romain Pelletant.
//
// Licensed under Apache License v2.0.  See the file "LICENSE" for more
// information.
//
// SPDX-License-Identifier: Apache-2.0
//

#include <stdio.h>
#include <string.h>

#include <mxml.h>

//
// 'do_type_value()' - Determine the value type for an XML element.
//
// This function determines the type to use when parsing the value of an
// XML node.
//

static mxml_type_t				// O - Value type
do_type_value(void *cbdata,			// I - Callback data
              mxml_node_t *node)		// I - Current XML node
{
  const char *element = mxmlGetElement(node); // Current element name

  (void)cbdata;

  if ((element != NULL) && (strcmp(element, "value") == 0))
    return (MXML_TYPE_INTEGER);

  return (MXML_TYPE_OPAQUE);
}


//
// 'main()' - Main entry for sample program.
//

int
main(void)
{
  static const char document[] =		// XML document to parse
    "<settings>"
    "<name>zephyr</name>"
    "<value>42</value>"
    "</settings>";
  mxml_options_t *options;			// Load and save options
  mxml_node_t *tree;				// Parsed XML tree
  mxml_node_t *value;				// Value element
  mxml_node_t *status;				// Status element
  char output[256];				// Serialized XML document
  size_t output_len;				// Length of serialized XML
  int ret = 0;					// Return value

  // Create the parser options and load the XML document...
  options = mxmlOptionsNew();
  if (options == NULL)
  {
    fprintf(stderr, "Unable to allocate Mini-XML options\n");
    return (1);
  }

  mxmlOptionsSetTypeCallback(options, do_type_value, NULL);
  tree = mxmlLoadString(NULL, options, document);
  if (tree == NULL)
  {
    fprintf(stderr, "Unable to parse the XML document\n");
    ret = 1;
    goto out_options;
  }

  // Find and update the integer value
  value = mxmlFindPath(tree, "value");
  if (value == NULL)
  {
    fprintf(stderr, "Unable to find the value element\n");
    ret = 1;
    goto out_tree;
  }

  printf("Mini-XML parsed value: %ld\n", mxmlGetInteger(value));

  // Set value to 84
  if (!mxmlSetInteger(value, 84))
  {
    fprintf(stderr, "Unable to update the value element\n");
    ret = 1;
    goto out_tree;
  }

  // Add a status element to the document
  status = mxmlNewElement(tree, "status");
  if ((status == NULL) || (mxmlNewOpaque(status, "updated") == NULL))
  {
    fprintf(stderr, "Unable to add the status element\n");
    ret = 1;
    goto out_tree;
  }

  // Serialize and print the modified document
  mxmlOptionsSetWrapMargin(options, 0);
  output_len = mxmlSaveString(tree, options, output, sizeof(output));
  if ((output_len == 0) || (output_len >= sizeof(output)))
  {
    fprintf(stderr, "Unable to serialize the XML document\n");
    ret = 1;
    goto out_tree;
  }

  printf("Modified XML: %s\n", output);

out_tree:
  mxmlDelete(tree);
out_options:
  mxmlOptionsDelete(options);

  return (ret);
}

