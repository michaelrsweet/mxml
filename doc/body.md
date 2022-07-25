---
title: Mini-XML 3.3 Programming Manual
author: Michael R Sweet
copyright: Copyright © 2003-2022, All Rights Reserved.
version: 3.3
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


## History

Mini-XML was initially developed for the [Gutenprint](http://gutenprint.sf.net/)
project to replace the rather large and unwieldy `libxml2` library with
something substantially smaller and easier-to-use.  It all began one morning in
June of 2003 when Robert posted the following sentence to the developer's list:

> It's bad enough that we require libxml2, but rolling our own XML parser is a
> bit more than we can handle.

I then replied with:

> Given the limited scope of what you use in XML, it should be trivial to code a
> mini-XML API in a few hundred lines of code.

I took my own challenge and coded furiously for two days to produced the initial
public release of Mini-XML, total lines of code: 696.  Robert promptly
integrated Mini-XML into Gutenprint and removed libxml2.

Thanks to lots of feedback and support from various developers, Mini-XML has
evolved since then to provide a more complete XML implementation and now stands
at a whopping 4,300 lines of code, compared to 196,141 lines of code for libxml2
version 2.9.9.


## Resources

The Mini-XML home page can be found at <https://www.msweet.org/mxml>.  From
there you can download the current version of Mini-XML, access the issue
tracker, and find other resources.


## Legal Stuff

The Mini-XML library is copyright © 2003-2021 by Michael R Sweet and is provided
under the Apache License Version 2.0 with an exception to allow linking against
GPL2/LGPL2-only software.  See the files "LICENSE" and "NOTICE" for more
information.


# Using Mini-XML

Mini-XML provides a single header file which you include:

    #include <mxml.h>

The Mini-XML library is included with your program using the `-lmxml` option:

    gcc -o myprogram myprogram.c -lmxml

If you have the `pkg-config` software installed, you can use it to determine the
proper compiler and linker options for your installation:

    gcc `pkg-config --cflags mxml` -o myprogram myprogram.c `pkg-config --libs mxml`


## Loading an XML File

You load an XML file using the `mxmlLoadFile` function:

    mxml_node_t *
    mxmlLoadFile(mxml_node_t *top, FILE *fp,
                 mxml_type_t (*cb)(mxml_node_t *));

The `cb` argument specifies a function that assigns child (value) node types for
each element in the document.  The callback can be a function you provide or one
of the standard functions provided with Mini-XML.  For example, to load the XML
file "filename.xml" containing text strings you can use the
`MXML_OPAQUE_CALLBACK` function:

    FILE *fp;
    mxml_node_t *tree;

    fp = fopen("filename.xml", "r");
    tree = mxmlLoadFile(NULL, fp, MXML_OPAQUE_CALLBACK);
    fclose(fp);

Mini-XML also provides functions to load from a file descriptor or string:

    mxml_node_t *
    mxmlLoadFd(mxml_node_t *top, int fd,
               mxml_type_t (*cb)(mxml_node_t *));

    mxml_node_t *
    mxmlLoadString(mxml_node_t *top, const char *s,
                   mxml_type_t (*cb)(mxml_node_t *));


### Load Callbacks

The last argument to the `mxmlLoad` functions is a callback function which is
used to determine the value type of each data node in an XML document.  Mini-XML
defines several standard callbacks for simple XML data files:

- `MXML_INTEGER_CALLBACK`: All data nodes contain whitespace-separated integers.
- `MXML_OPAQUE_CALLBACK`: All data nodes contain opaque strings with whitespace preserved.
- `MXML_REAL_CALLBACK` - All data nodes contain whitespace-separated floating-point numbers.
- `MXML_TEXT_CALLBACK` - All data nodes contain whitespace-separated strings.

You can provide your own callback functions for more complex XML documents.
Your callback function will receive a pointer to the current element node and
must return the value type of the immediate children for that element node:
`MXML_CUSTOM`, `MXML_INTEGER`, `MXML_OPAQUE`, `MXML_REAL`, or `MXML_TEXT`.  The
function is called *after* the element and its attributes have been read, so you
can look at the element name, attributes, and attribute values to determine the
proper value type to return.

The following callback function looks for an attribute named "type" or the
element name to determine the value type for its child nodes:

    mxml_type_t
    type_cb(mxml_node_t *node)
    {
      const char *type;

     /*
      * You can lookup attributes and/or use the element name,
      * hierarchy, etc...
      */

      type = mxmlElementGetAttr(node, "type");
      if (type == NULL)
        type = mxmlGetElement(node);

      if (!strcmp(type, "integer"))
        return (MXML_INTEGER);
      else if (!strcmp(type, "opaque"))
        return (MXML_OPAQUE);
      else if (!strcmp(type, "real"))
        return (MXML_REAL);
      else
        return (MXML_TEXT);
    }

To use this callback function, simply use the name when you call any of the load
functions:

    FILE *fp;
    mxml_node_t *tree;

    fp = fopen("filename.xml", "r");
    tree = mxmlLoadFile(NULL, fp, type_cb);
    fclose(fp);


## Nodes

Every piece of information in an XML file is stored in memory in "nodes".  Nodes
are defined by the `mxml_node_t` structure.  Each node has a typed value,
optional user data, a parent node, sibling nodes (previous and next), and
potentially child nodes.

For example, if you have an XML file like the following:

    <?xml version="1.0" encoding="utf-8"?>
    <data>
        <node>val1</node>
        <node>val2</node>
        <node>val3</node>
        <group>
            <node>val4</node>
            <node>val5</node>
            <node>val6</node>
        </group>
        <node>val7</node>
        <node>val8</node>
    </data>

the node tree for the file would look like the following in memory:

    ?xml version="1.0" encoding="utf-8"?
      |
    data
      |
    node - node - node - group - node - node
      |      |      |      |       |      |
    val1   val2   val3     |     val7   val8
                           |
                         node - node - node
                           |      |      |
                         val4   val5   val6

where "-" is a pointer to the sibling node and "|" is a pointer to the first
child or parent node.

The `mxmlGetType` function gets the type of a node:

    mxml_type_t
    mxmlGetType(mxml_node_t *node);

- `MXML_CUSTOM` : A custom value defined by your application,
- `MXML_ELEMENT` : An XML element, CDATA, comment, or processing instruction,
- `MXML_INTEGER` : A whitespace-delimited integer value,
- `MXML_OPAQUE` : An opaque string value that preserves all whitespace,
- `MXML_REAL` : A whitespace-delimited floating point value, or
- `MXML_TEXT` : A whitespace-delimited text (fragment) value.

> Note: CDATA, comment, and processing directive nodes are currently stored in
> memory as special elements.  This will be changed in a future major release of
> Mini-XML.

The parent and sibling nodes are accessed using the `mxmlGetParent`,
`mxmlGetNextSibling`, and `mxmlGetPreviousSibling` functions, while the children
of an element node are accessed using the `mxmlGetFirstChild` or
`mxmlGetLastChild` functions:

    mxml_node_t *
    mxmlGetFirstChild(mxml_node_t *node);

    mxml_node_t *
    mxmlGetLastChild(mxml_node_t *node);

    mxml_node_t *
    mxmlGetNextSibling(mxml_node_t *node);

    mxml_node_t *
    mxmlGetParent(mxml_node_t *node);

    mxml_node_t *
    mxmlGetPrevSibling(mxml_node_t *node);

The `mxmlGetUserData` function gets any user (application) data associated with
the node:

    void *
    mxmlGetUserData(mxml_node_t *node);


## Creating XML Documents

You can create and update XML documents in memory using the various `mxmlNew`
functions. The following code will create the XML document described in the
previous section:

    mxml_node_t *xml;    /* <?xml ... ?> */
    mxml_node_t *data;   /* <data> */
    mxml_node_t *node;   /* <node> */
    mxml_node_t *group;  /* <group> */

    xml = mxmlNewXML("1.0");

    data = mxmlNewElement(xml, "data");

        node = mxmlNewElement(data, "node");
        mxmlNewText(node, 0, "val1");
        node = mxmlNewElement(data, "node");
        mxmlNewText(node, 0, "val2");
        node = mxmlNewElement(data, "node");
        mxmlNewText(node, 0, "val3");

        group = mxmlNewElement(data, "group");

            node = mxmlNewElement(group, "node");
            mxmlNewText(node, 0, "val4");
            node = mxmlNewElement(group, "node");
            mxmlNewText(node, 0, "val5");
            node = mxmlNewElement(group, "node");
            mxmlNewText(node, 0, "val6");

        node = mxmlNewElement(data, "node");
        mxmlNewText(node, 0, "val7");
        node = mxmlNewElement(data, "node");
        mxmlNewText(node, 0, "val8");

We start by creating the declaration node common to all XML files using the
`mxmlNewXML` function:

    xml = mxmlNewXML("1.0");

We then create the `<data>` node used for this document using the
`mxmlNewElement` function.  The first argument specifies the parent node
\(`xml`) while the second specifies the element name \(`data`):

    data = mxmlNewElement(xml, "data");

Each `<node>...</node>` in the file is created using the `mxmlNewElement` and
`mxmlNewText` functions.  The first argument of `mxmlNewText` specifies the
parent node \(`node`).  The second argument specifies whether whitespace appears
before the text - 0 or false in this case.  The last argument specifies the
actual text to add:

    node = mxmlNewElement(data, "node");
    mxmlNewText(node, 0, "val1");

The resulting in-memory XML document can then be saved or processed just like
one loaded from disk or a string.


## Saving an XML File

You save an XML file using the `mxmlSaveFile` function:

    int
    mxmlSaveFile(mxml_node_t *node, FILE *fp,
                 mxml_save_cb_t cb);

The `cb` argument specifies a function that returns the whitespace (if any) that
is inserted before and after each element node.  The `MXML_NO_CALLBACK` constant
tells Mini-XML to not include any extra whitespace.  For example, so save an XML
file to the file "filename.xml" with no extra whitespace:

    FILE *fp;

    fp = fopen("filename.xml", "w");
    mxmlSaveFile(xml, fp, MXML_NO_CALLBACK);
    fclose(fp);

Mini-XML also provides functions to save to a file descriptor or strings:

    char *
    mxmlSaveAllocString(mxml_node_t *node, mxml_save_cb_t cb);

    int
    mxmlSaveFd(mxml_node_t *node, int fd, mxml_save_cb_t cb);

    int
    mxmlSaveString(mxml_node_t *node, char *buffer, int bufsize,
                   mxml_save_cb_t cb);


### Controlling Line Wrapping

When saving XML documents, Mini-XML normally wraps output lines at column 75 so
that the text is readable in terminal windows.  The `mxmlSetWrapMargin` function
overrides the default wrap margin for the current thread:

    void mxmlSetWrapMargin(int column);

For example, the following code sets the margin to 132 columns:

    mxmlSetWrapMargin(132);

while the following code disables wrapping by setting the margin to 0:

    mxmlSetWrapMargin(0);


### Save Callbacks

The last argument to the `mxmlSave` functions is a callback function which is
used to automatically insert whitespace in an XML document.  Your callback
function will be called up to four times for each element node with a pointer to
the node and a "where" value of `MXML_WS_BEFORE_OPEN`, `MXML_WS_AFTER_OPEN`,
`MXML_WS_BEFORE_CLOSE`, or `MXML_WS_AFTER_CLOSE`.  The callback function should
return `NULL` if no whitespace should be added or the string to insert (spaces,
tabs, carriage returns, and newlines) otherwise.

The following whitespace callback can be used to add whitespace to XHTML output
to make it more readable in a standard text editor:

    const char *
    whitespace_cb(mxml_node_t *node, int where)
    {
      const char *element;

     /*
      * We can conditionally break to a new line before or after
      * any element.  These are just common HTML elements...
      */

      element = mxmlGetElement(node);

      if (!strcmp(element, "html") ||
          !strcmp(element, "head") ||
          !strcmp(element, "body") ||
          !strcmp(element, "pre") ||
          !strcmp(element, "p") ||
          !strcmp(element, "h1") ||
          !strcmp(element, "h2") ||
          !strcmp(element, "h3") ||
          !strcmp(element, "h4") ||
          !strcmp(element, "h5") ||
          !strcmp(element, "h6"))
      {
       /*
        * Newlines before open and after close...
        */

        if (where == MXML_WS_BEFORE_OPEN ||
            where == MXML_WS_AFTER_CLOSE)
          return ("\n");
      }
      else if (!strcmp(element, "dl") ||
               !strcmp(element, "ol") ||
               !strcmp(element, "ul"))
      {
       /*
        * Put a newline before and after list elements...
        */

        return ("\n");
      }
      else if (!strcmp(element, "dd") ||
               !strcmp(element, "dt") ||
               !strcmp(element, "li"))
      {
       /*
        * Put a tab before <li>'s, <dd>'s, and <dt>'s, and a
        * newline after them...
        */

        if (where == MXML_WS_BEFORE_OPEN)
          return ("\t");
        else if (where == MXML_WS_AFTER_CLOSE)
          return ("\n");
      }

     /*
      * Otherwise return NULL for no added whitespace...
      */

      return (NULL);
    }

To use this callback function, simply use the name when you call any of the save
functions:

    FILE *fp;
    mxml_node_t *tree;

    fp = fopen("filename.xml", "w");
    mxmlSaveFile(tree, fp, whitespace_cb);
    fclose(fp);


## Memory Management

Once you are done with the XML data, use the `mxmlDelete` function to
recursively free the memory that is used for a particular node or the entire
tree:

    void
    mxmlDelete(mxml_node_t *tree);

You can also use reference counting to manage memory usage.  The `mxmlRetain`
and `mxmlRelease` functions increment and decrement a node's use count,
respectively.  When the use count goes to zero, `mxmlRelease` automatically
calls `mxmlDelete` to actually free the memory used by the node tree.  New nodes
start with a use count of 1.


# More About Nodes

## Element Nodes

Element \(`MXML_ELEMENT`) nodes are created using the `mxmlNewElement` function.
Element attributes are set using the `mxmlElementSetAttr` and
`mxmlElementSetAttrf` functions and cleared using the `mxmlElementDeleteAttr`
function:

    mxml_node_t *
    mxmlNewElement(mxml_node_t *parent, const char *name);

    void
    mxmlElementDeleteAttr(mxml_node_t *node, const char *name);

    void
    mxmlElementSetAttr(mxml_node_t *node, const char *name,
                       const char *value);

    void
    mxmlElementSetAttrf(mxml_node_t *node, const char *name,
                        const char *format, ...);

Child nodes are added using the various `mxmlNew` functions.  The top (root)
node must be an element, usually created by the `mxmlNewXML` function:

    mxml_node_t *
    mxmlNewXML(const char *version);

The `mxmlGetElement` function retrieves the element name, the
`mxmlElementGetAttr` function retrieves the value string for a named attribute
associated with the element.  The `mxmlElementGetAttrByIndex` and
`mxmlElementGetAttrCount` functions retrieve attributes by index:

    const char *
    mxmlGetElement(mxml_node_t *node);

    const char *
    mxmlElementGetAttr(mxml_node_t *node, const char *name);

    const char *
    mxmlElementGetAttrByIndex(mxml_node_t *node, int idx,
                              const char **name);

    int
    mxmlElementGetAttrCount(mxml_node_t *node);


## CDATA Nodes

CDATA \(`MXML_ELEMENT`) nodes are created using the `mxmlNewCDATA` function:

    mxml_node_t *mxmlNewCDATA(mxml_node_t *parent, const char *string);

The `mxmlGetCDATA` function retrieves the CDATA string pointer for a node:

    const char *mxmlGetCDATA(mxml_node_t *node);


## Comment Nodes

Because comments are currently stored as element nodes, comment
\(`MXML_ELEMENT`) nodes are created using the `mxmlNewElement` function by
including the surrounding "!--" and "--" characters in the element name, for
example:

    mxml_node_t *node = mxmlNewElement("!-- This is a comment --");

Similarly, the `mxmlGetElement` function retrieves the comment string pointer
for a node, which includes the surrounding "!--" and "--" characters.

    const char *comment = mxmlGetElement(node);
    /* returns "!-- This is a comment --" */


## Processing Instruction Nodes

Because processing instructions are currently stored as element nodes,
processing instruction \(`MXML_ELEMENT`) nodes are created using the
`mxmlNewElement` function including the surrounding "?" characters:

    mxml_node_t *node = mxmlNewElement("?xml-stylesheet type=\"text/css\" href=\"style.css\"?");

The `mxmlGetElement` function retrieves the processing instruction string for a
node, including the surrounding "?" characters:

    const char *instr = mxmlGetElement(node);
    /* returned "?xml-stylesheet type=\"text/css\" href=\"style.css\"?" */


## Integer Nodes

Integer \(`MXML_INTEGER`) nodes are created using the `mxmlNewInteger` function:

    mxml_node_t *
    mxmlNewInteger(mxml_node_t *parent, int integer);

The `mxmlGetInteger` function retrieves the integer value for a node:

    int
    mxmlGetInteger(mxml_node_t *node);


## Opaque String Nodes

Opaque string \(`MXML_OPAQUE`) nodes are created using the `mxmlNewOpaque`
function:

    mxml_node_t *
    mxmlNewOpaque(mxml_node_t *parent, const char *opaque);

The `mxmlGetOpaque` function retrieves the opaque string pointer for a node:

    const char *
    mxmlGetOpaque(mxml_node_t *node);


## Text Nodes

Whitespace-delimited text string \(`MXML_TEXT`) nodes are created using the
`mxmlNewText` and `mxmlNewTextf` functions.  Each text node consists of a text
string and (leading) whitespace flag value.

    mxml_node_t *
    mxmlNewText(mxml_node_t *parent, int whitespace,
                const char *string);

    mxml_node_t *
    mxmlNewTextf(mxml_node_t *parent, int whitespace,
                 const char *format, ...);

The `mxmlGetText` function retrieves the text string pointer and whitespace
flag value for a node:

     const char *
     mxmlGetText(mxml_node_t *node, int *whitespace);


## Real Number Nodes

Real number \(`MXML_REAL`) nodes are created using the `mxmlNewReal` function:

    mxml_node_t *
    mxmlNewReal(mxml_node_t *parent, double real);

The `mxmlGetReal` function retrieves the real number for a node:

    double
    mxmlGetReal(mxml_node_t *node);


# Locating Data in an XML Document

Mini-XML provides many functions for enumerating, searching, and indexing XML
documents.


## Finding Nodes

The `mxmlFindPath` function finds the (first) value node under a specific
element using a "path":

    mxml_node_t *
    mxmlFindPath(mxml_node_t *node, const char *path);

The `path` string can contain the "*" wildcard to match a single element node in
the hierarchy.  For example, the following code will find the first "node"
element under the "group" element, first using an explicit path and then using a
wildcard:

    mxml_node_t *value = mxmlFindPath(xml, "data/group/node");

    mxml_node_t *value = mxmlFindPath(xml, "data/*/node");

The `mxmlFindElement` function can be used to find a named element, optionally
matching an attribute and value:

    mxml_node_t *
    mxmlFindElement(mxml_node_t *node, mxml_node_t *top,
                    const char *element, const char *attr,
                    const char *value, int descend);

The "element", "attr", and "value" arguments can be passed as `NULL` to act as
wildcards, e.g.:

    /* Find the first "a" element */
    node = mxmlFindElement(tree, tree, "a", NULL, NULL,
                           MXML_DESCEND);

    /* Find the first "a" element with "href" attribute */
    node = mxmlFindElement(tree, tree, "a", "href", NULL,
                           MXML_DESCEND);

    /* Find the first "a" element with "href" to a URL */
    node = mxmlFindElement(tree, tree, "a", "href",
                           "http://michaelrsweet.github.io/",
                           MXML_DESCEND);

    /* Find the first element with a "src" attribute*/
    node = mxmlFindElement(tree, tree, NULL, "src", NULL,
                           MXML_DESCEND);

    /* Find the first element with a "src" = "foo.jpg" */
    node = mxmlFindElement(tree, tree, NULL, "src", "foo.jpg",
                           MXML_DESCEND);

You can also iterate with the same function:

    mxml_node_t *node;

    for (node = mxmlFindElement(tree, tree, "element", NULL,
                                NULL, MXML_DESCEND);
         node != NULL;
         node = mxmlFindElement(node, tree, "element", NULL,
                                NULL, MXML_DESCEND))
    {
      ... do something ...
    }

The `descend` argument \(`MXML_DESCEND` in the examples above) can be one of
three constants:

- `MXML_NO_DESCEND`: ignore child nodes in the element hierarchy, instead using
  siblings (same level) or parent nodes (above) until the top (root) node is
  reached.

- `MXML_DESCEND_FIRST`: start the search with the first child of the node, and
  then search siblings.  You'll normally use this when iterating through direct
  children of a parent node, e.g. all of the "node" and "group" elements under
  the "?xml" parent node in the previous example.

- `MXML_DESCEND`: search child nodes first, then sibling nodes, and then parent
  nodes.


## Iterating Nodes

While the `mxmlFindNode` and `mxmlFindPath` functions will find a particular
element node, sometimes you need to iterate over all nodes.  The `mxmlWalkNext`
and `mxmlWalkPrev` functions can be used to iterate through the XML node
tree:

    mxml_node_t *
    mxmlWalkNext(mxml_node_t *node, mxml_node_t *top,
                 int descend);

    mxml_node_t *
    mxmlWalkPrev(mxml_node_t *node, mxml_node_t *top,
                 int descend);

Depending on the value of the `descend` argument, these functions will
automatically traverse child, sibling, and parent nodes until the `top` node is
reached.  For example, the following code will iterate over all of the nodes in
the sample XML document in the previous section:

    mxml_node_t *node;

    for (node = xml;
         node != NULL;
         node = mxmlWalkNext(node, xml, MXML_DESCEND))
    {
      ... do something ...
    }

The nodes will be returned in the following order:

    <?xml version="1.0" encoding="utf-8"?>
    <data>
    <node>
    val1
    <node>
    val2
    <node>
    val3
    <group>
    <node>
    val4
    <node>
    val5
    <node>
    val6
    <node>
    val7
    <node>
    val8


## Indexing

The `mxmlIndexNew` function allows you to create an index of nodes for faster
searching and enumeration:

    mxml_index_t *
    mxmlIndexNew(mxml_node_t *node, const char *element,
                 const char *attr);

The `element` and `attr` arguments control which elements are included in the
index.  If `element` is not `NULL` then only elements with the specified name
are added to the index.  Similarly, if `attr` is not `NULL` then only elements
containing the specified attribute are added to the index.  The nodes are sorted
in the index.

For example, the following code creates an index of all "id" values in an XML
document:

    mxml_index_t *ind = mxmlIndexNew(xml, NULL, "id");

Once the index is created, the `mxmlIndexFind` function can be used to find a
matching node:

    mxml_node_t *
    mxmlIndexFind(mxml_index_t *ind, const char *element,
                  const char *value);

For example, the following code will find the element whose "id" string is "42":

    mxml_node_t *node = mxmlIndexFind(ind, NULL, "42");

Alternately, the `mxmlIndexReset` and `mxmlIndexEnum` functions can be used to
enumerate the nodes in the index:

    mxml_node_t *
    mxmlIndexReset(mxml_index_t *ind);

    mxml_node_t *
    mxmlIndexEnum(mxml_index_t *ind);

Typically these functions will be used in a `for` loop:

    mxml_node_t *node;

    for (node = mxmlIndexReset(ind);
         node != NULL;
         node = mxmlIndexEnum(ind))
    {
      ... do something ...
    }

The `mxmlIndexCount` function returns the number of nodes in the index:

    int
    mxmlIndexGetCount(mxml_index_t *ind);

Finally, the `mxmlIndexDelete` function frees all memory associated with the
index:

    void
    mxmlIndexDelete(mxml_index_t *ind);


# Custom Data Types

Mini-XML supports custom data types via per-thread load and save callbacks.
Only a single set of callbacks can be active at any time for the current thread,
however your callbacks can store additional information in order to support
multiple custom data types as needed.  The `MXML_CUSTOM` node type identifies
custom data nodes.

The `mxmlGetCustom` function retrieves the custom value pointer for a node.

    const void *
    mxmlGetCustom(mxml_node_t *node);

Custom \(`MXML_CUSTOM`) nodes are created using the `mxmlNewCustom` function or
using a custom per-thread load callbacks specified using the
`mxmlSetCustomHandlers` function:

    typedef void (*mxml_custom_destroy_cb_t)(void *);
    typedef int (*mxml_custom_load_cb_t)(mxml_node_t *, const char *);
    typedef char *(*mxml_custom_save_cb_t)(mxml_node_t *);

    mxml_node_t *
    mxmlNewCustom(mxml_node_t *parent, void *data,
                  mxml_custom_destroy_cb_t destroy);

    int
    mxmlSetCustom(mxml_node_t *node, void *data,
                  mxml_custom_destroy_cb_t destroy);

    void
    mxmlSetCustomHandlers(mxml_custom_load_cb_t load,
                          mxml_custom_save_cb_t save);

The load callback receives a pointer to the current data node and a string of
opaque character data from the XML source with character entities converted to
the corresponding UTF-8 characters.  For example, if we wanted to support a
custom date/time type whose value is encoded as "yyyy-mm-ddThh:mm:ssZ" (ISO
format), the load callback would look like the following:

    typedef struct
    {
      unsigned year,    /* Year */
               month,   /* Month */
               day,     /* Day */
               hour,    /* Hour */
               minute,  /* Minute */
               second;  /* Second */
      time_t   unix;    /* UNIX time */
    } iso_date_time_t;

    int
    load_custom(mxml_node_t *node, const char *data)
    {
      iso_date_time_t *dt;
      struct tm tmdata;

     /*
      * Allocate data structure...
      */

      dt = calloc(1, sizeof(iso_date_time_t));

     /*
      * Try reading 6 unsigned integers from the data string...
      */

      if (sscanf(data, "%u-%u-%uT%u:%u:%uZ", &(dt->year),
                 &(dt->month), &(dt->day), &(dt->hour),
                 &(dt->minute), &(dt->second)) != 6)
      {
       /*
        * Unable to read numbers, free the data structure and
        * return an error...
        */

        free(dt);

        return (-1);
      }

     /*
      * Range check values...
      */

      if (dt->month < 1 || dt->month > 12 ||
          dt->day < 1 || dt->day > 31 ||
          dt->hour < 0 || dt->hour > 23 ||
          dt->minute < 0 || dt->minute > 59 ||
          dt->second < 0 || dt->second > 60)
      {
       /*
        * Date information is out of range...
        */

        free(dt);

        return (-1);
      }

     /*
      * Convert ISO time to UNIX time in seconds...
      */

      tmdata.tm_year = dt->year - 1900;
      tmdata.tm_mon  = dt->month - 1;
      tmdata.tm_day  = dt->day;
      tmdata.tm_hour = dt->hour;
      tmdata.tm_min  = dt->minute;
      tmdata.tm_sec  = dt->second;

      dt->unix = gmtime(&tmdata);

     /*
      * Assign custom node data and destroy (free) function
      * pointers...
      */

      mxmlSetCustom(node, data, free);

     /*
      * Return with no errors...
      */

      return (0);
    }

The function itself can return 0 on success or -1 if it is unable to decode the
custom data or the data contains an error.  Custom data nodes contain a `void`
pointer to the allocated custom data for the node and a pointer to a destructor
function which will free the custom data when the node is deleted.  In this
example, we use the standard `free` function since everything is contained in a
single calloc'd block.

The save callback receives the node pointer and returns an allocated string
containing the custom data value.  The following save callback could be used for
our ISO date/time type:

    char *
    save_custom(mxml_node_t *node)
    {
      char data[255];
      iso_date_time_t *dt;


      dt = (iso_date_time_t *)mxmlGetCustom(node);

      snprintf(data, sizeof(data),
               "%04u-%02u-%02uT%02u:%02u:%02uZ",
               dt->year, dt->month, dt->day, dt->hour,
               dt->minute, dt->second);

      return (strdup(data));
    }

You register the callback functions using the `mxmlSetCustomHandlers` function:

    mxmlSetCustomHandlers(load_custom, save_custom);


# SAX (Stream) Loading of Documents

Mini-XML supports an implementation of the Simple API for XML (SAX) which allows
you to load and process an XML document as a stream of nodes.  Aside from
allowing you to process XML documents of any size, the Mini-XML implementation
also allows you to retain portions of the document in memory for later
processing.

The `mxmlSAXLoadFd`, `mxmlSAXLoadFile`, and `mxmlSAXLoadString` functions
provide the SAX loading APIs:

    mxml_node_t *
    mxmlSAXLoadFd(mxml_node_t *top, int fd,
                  mxml_type_t (*cb)(mxml_node_t *),
                  mxml_sax_cb_t sax, void *sax_data);

    mxml_node_t *
    mxmlSAXLoadFile(mxml_node_t *top, FILE *fp,
                    mxml_type_t (*cb)(mxml_node_t *),
                    mxml_sax_cb_t sax, void *sax_data);

    mxml_node_t *
    mxmlSAXLoadString(mxml_node_t *top, const char *s,
                      mxml_type_t (*cb)(mxml_node_t *),
                      mxml_sax_cb_t sax, void *sax_data);

Each function works like the corresponding `mxmlLoad` function but uses a
callback to process each node as it is read.  The callback function receives the
node, an event code, and a user data pointer you supply:

    void
    sax_cb(mxml_node_t *node, mxml_sax_event_t event,
           void *data)
    {
      ... do something ...
    }

The event will be one of the following:

- `MXML_SAX_CDATA`: CDATA was just read.
- `MXML_SAX_COMMENT`: A comment was just read.
- `MXML_SAX_DATA`: Data (custom, integer, opaque, real, or text) was just read.
- `MXML_SAX_DIRECTIVE`: A processing directive/instruction was just read.
- `MXML_SAX_ELEMENT_CLOSE` - A close element was just read \(`</element>`)
- `MXML_SAX_ELEMENT_OPEN` - An open element was just read \(`<element>`)

Elements are *released* after the close element is processed.  All other nodes
are released after they are processed.  The SAX callback can *retain* the node
using the `mxmlRetain` function.  For example, the following SAX callback will
retain all nodes, effectively simulating a normal in-memory load:

    void
    sax_cb(mxml_node_t *node, mxml_sax_event_t event,
           void *data)
    {
      if (event != MXML_SAX_ELEMENT_CLOSE)
        mxmlRetain(node);
    }

More typically the SAX callback will only retain a small portion of the document
that is needed for post-processing.  For example, the following SAX callback
will retain the title and headings in an XHTML file.  It also retains the (parent) elements like `<html>`, `<head>`, and `<body>`, and processing
directives like  `<?xml ... ?>` and  `<!DOCTYPE ... >`:

    void
    sax_cb(mxml_node_t *node, mxml_sax_event_t event,
           void *data)
    {
      if (event == MXML_SAX_ELEMENT_OPEN)
      {
       /*
        * Retain headings and titles...
        */

        const char *element = mxmlGetElement(node);

        if (!strcmp(element, "html") ||
            !strcmp(element, "head") ||
            !strcmp(element, "title") ||
            !strcmp(element, "body") ||
            !strcmp(element, "h1") ||
            !strcmp(element, "h2") ||
            !strcmp(element, "h3") ||
            !strcmp(element, "h4") ||
            !strcmp(element, "h5") ||
            !strcmp(element, "h6"))
          mxmlRetain(node);
      }
      else if (event == MXML_SAX_DIRECTIVE)
        mxmlRetain(node);
      else if (event == MXML_SAX_DATA)
      {
        if (mxmlGetRefCount(mxmlGetParent(node)) > 1)
        {
         /*
          * If the parent was retained, then retain this data
          * node as well.
          */

          mxmlRetain(node);
        }
      }
    }

The resulting skeleton document tree can then be searched just like one loaded
using the `mxmlLoad` functions.  For example, a filter that reads an XHTML
document from stdin and then shows the title and headings in the document would
look like:

    mxml_node_t *doc, *title, *body, *heading;

    doc = mxmlSAXLoadFd(NULL, 0, MXML_TEXT_CALLBACK, sax_cb,
                        NULL);

    title = mxmlFindElement(doc, doc, "title", NULL, NULL,
                            MXML_DESCEND);

    if (title)
      print_children(title);

    body = mxmlFindElement(doc, doc, "body", NULL, NULL,
                           MXML_DESCEND);

    if (body)
    {
      for (heading = mxmlGetFirstChild(body);
           heading;
           heading = mxmlGetNextSibling(heading))
        print_children(heading);
    }

The `print_children` function is:

    void
    print_children(mxml_node_t *parent)
    {
      mxml_node_t *node;
      const char *text;
      int whitespace;

      for (node = mxmlGetFirstChild(parent);
           node != NULL;
           node = mxmlGetNextSibling(node))
      {
        text = mxmlGetText(node, &whitespace);

        if (whitespace)
          putchar(' ');

        fputs(text, stdout);
      }

      putchar('\n');
    }
