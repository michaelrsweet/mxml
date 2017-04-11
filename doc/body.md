---
title: Mini-XML API Reference
author: Michael R Sweet
copyright: Copyright (c) 2003-2017, All Rights Reserved.
docversion: 2.11
...

# Introduction

Mini-XML is a small XML parsing library that you can use to read XML data files
or strings in your application without requiring large non-standard libraries.
Mini-XML provides the following functionality:

- Reading of UTF-8 and UTF-16 and writing of UTF-8 encoded XML files and
  strings.
- Data is stored in a linked-list tree structure, preserving the XML data
  hierarchy.
- SAX (streamed) reading of XML files and strings to minimize memory usage.
- Supports arbitrary element names, attributes, and attribute values with no
  preset limits, just available memory.
- Supports integer, real, opaque ("cdata"), and text data types in "leaf"
  nodes.
- Functions for creating and managing trees of data.
- "Find" and "walk" functions for easily locating and navigating trees of
  data.

Mini-XML doesn't do validation or other types of processing on the data based
upon schema files or other sources of definition information.

# Using Mini-XML

Mini-XML provides a single header file which you include:

    #include <mxml.h>

Nodes are defined by the `mxml_node_t` structure; the "type" member defines the
node type (element, integer, opaque, real, or text) which determines which value
you want to look at in the "value" union.  New nodes can be created using the
`mxmlNewElement`, `mxmlNewInteger`, `mxmlNewOpaque`, `mxmlNewReal`, and
`mxmlNewText` functions.  Only elements can have child nodes, and the top node
must be an element, usually "?xml".

You load an XML file using the `mxmlLoadFile` function:

    FILE *fp;
    mxml_node_t *tree;

    fp = fopen("filename.xml", "r");
    tree = mxmlLoadFile(NULL, fp, MXML_NO_CALLBACK);
    fclose(fp);

Similarly, you save an XML file using the `mxmlSaveFile` function:

    FILE *fp;
    mxml_node_t *tree;

    fp = fopen("filename.xml", "w");
    mxmlSaveFile(tree, fp, MXML_NO_CALLBACK);
    fclose(fp);

The `mxmlLoadString`, `mxmlSaveAllocString`, and `mxmlSaveString` functions
load XML node trees from and save XML node trees to strings:

    char buffer[8192];
    char *ptr;
    mxml_node_t *tree;

    ...
    tree = mxmlLoadString(NULL, buffer, MXML_NO_CALLBACK);

    ...
    mxmlSaveString(tree, buffer, sizeof(buffer),
                   MXML_NO_CALLBACK);

    ...
    ptr = mxmlSaveAllocString(tree, MXML_NO_CALLBACK);

You can find a named element/node using the `mxmlFindElement` function:

    mxml_node_t *node = mxmlFindElement(tree, tree, "name",
                                        "attr", "value",
                                        MXML_DESCEND);

The "name", "attr", and "value" arguments can be passed as `NULL` to act as
wildcards, e.g.:

    /* Find the first "a" element */
    node = mxmlFindElement(tree, tree, "a", NULL, NULL,
                           MXML_DESCEND);

    /* Find the first "a" element with "href" attribute */
    node = mxmlFindElement(tree, tree, "a", "href", NULL,
                           MXML_DESCEND);

    /* Find the first "a" element with "href" to a URL */
    node = mxmlFindElement(tree, tree, "a", "href",
                           "http://www.easysw.com/~mike/mxml/",
                           MXML_DESCEND);

    /* Find the first element with a "src" attribute*/
    node = mxmlFindElement(tree, tree, NULL, "src", NULL,
                           MXML_DESCEND);

    /* Find the first element with a "src" = "foo.jpg" */
    node = mxmlFindElement(tree, tree, NULL, "src",
                           "foo.jpg", MXML_DESCEND);

You can also iterate with the same function:

    mxml_node_t *node;

    for (node = mxmlFindElement(tree, tree, "name", NULL,
                                NULL, MXML_DESCEND);
         node != NULL;
         node = mxmlFindElement(node, tree, "name", NULL,
                                NULL, MXML_DESCEND))
    {
      ... do something ...
    }

The `mxmlFindPath` function finds the (first) value node under a specific
element using a "path":

    mxml_node_t *value = mxmlFindPath(tree, "path/to/*/foo/bar");

The `mxmlGetInteger`, `mxmlGetOpaque`, `mxmlGetReal`, and `mxmlGetText`
functions retrieve the value from a node:

    mxml_node_t *node;

    int intvalue = mxmlGetInteger(node);

    const char *opaquevalue = mxmlGetOpaque(node);

    double realvalue = mxmlGetReal(node);

    int whitespacevalue;
    const char *textvalue = mxmlGetText(node, &whitespacevalue);

Finally, once you are done with the XML data, use the `mxmlDelete` function to
recursively free the memory that is used for a particular node or the entire
tree:

    mxmlDelete(tree);
