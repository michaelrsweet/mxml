---
title: Mini-XML 4.0 Programming Manual
author: Michael R Sweet
copyright: Copyright © 2003-2025, All Rights Reserved.
version: 4.0
...


Introduction
============

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
- Supports integer, real, opaque ("CDATA"), text, and custom data types in
  "leaf" nodes.
- Functions for creating and managing trees of data.
- "Find" and "walk" functions for easily locating and navigating trees of
  data.
- Support for custom string memory management functions to implement string
  pools and other schemes for reducing memory usage.

Mini-XML doesn't do validation or other types of processing on the data based
upon schema files or other sources of definition information.


History
-------

Mini-XML was initially developed for the [Gutenprint](http://gutenprint.sf.net/)
project to replace the rather large and unwieldy `libxml2` library with
something substantially smaller and easier-to-use.  It all began one morning in
June of 2003 when Robert posted the following sentence to the developer's list:

> It's bad enough that we require libxml2, but rolling our own XML parser is a
> bit more than we can handle.

I then replied with:

> Given the limited scope of what you use in XML, it should be trivial to code a
> mini-XML API in a few hundred lines of code.

I took my own challenge and coded furiously for two days to produce the initial
public release of Mini-XML, total lines of code: 696.  Robert promptly
integrated Mini-XML into Gutenprint and removed libxml2.

Thanks to lots of feedback and support from various developers, Mini-XML has
evolved since then to provide a more complete XML implementation and now stands
at a whopping 3,491 lines of code, compared to 175,808 lines of code for libxml2
version 2.11.7.


Resources
---------

The Mini-XML home page can be found at <https://www.msweet.org/mxml>.  From
there you can download the current version of Mini-XML, access the issue
tracker, and find other resources.

Mini-XML v4 has a slightly different API than prior releases. See the
[Migrating from Mini-XML v3.x](@) chapter for details.


Legal Stuff
-----------

The Mini-XML library is copyright © 2003-2024 by Michael R Sweet and is provided
under the Apache License Version 2.0 with an (optional) exception to allow
linking against GPL2/LGPL2-only software.  See the files "LICENSE" and "NOTICE"
for more information.


Using Mini-XML
==============

Mini-XML provides a single header file which you include:

```c
#include <mxml.h>
```

The Mini-XML library is included with your program using the `-lmxml4` option:

    gcc -o myprogram myprogram.c -lmxml4

If you have the `pkg-config` software installed, you can use it to determine the
proper compiler and linker options for your installation:

    gcc `pkg-config --cflags mxml4` -o myprogram myprogram.c `pkg-config --libs mxml4`

> Note: The library name "mxml4" is a configure-time option. If you use the
> `--disable-libmxml4-prefix` configure option the library is named "mxml".


API Basics
----------

Every piece of information in an XML file is stored in memory in "nodes".  Nodes
are represented by `mxml_node_t` pointers.  Each node has an associated type,
value(s), a parent node, sibling nodes (previous and next), potentially first
and last child nodes, and an optional user data pointer.

For example, if you have an XML file like the following:

```xml
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
```

the node tree for the file would look like the following in memory:

```
<?xml version="1.0" encoding="utf-8"?>
  |
<data>
  |
<node> - <node> - <node> - <group> - <node> - <node>
  |        |        |         |        |        |
 val1     val2     val3       |       val7     val8
                              |
                            <node> - <node> - <node>
                              |        |        |
                             val4     val5     val6
```

where "-" is a pointer to the sibling node and "|" is a pointer to the first
child or parent node.

The [mxmlGetType](@@) function gets the type of a node which is represented as a
`mxml_type_t` enumeration value:

- `MXML_TYPE_CDATA`: CDATA such as `<![CDATA[...]]>`,
- `MXML_TYPE_COMMENT`: A comment such as `<!-- my comment -->`,
- `MXML_TYPE_CUSTOM`: A custom value defined by your application,
- `MXML_TYPE_DECLARATION`: A declaration such as `<!DOCTYPE html>`,
- `MXML_TYPE_DIRECTIVE`: A processing instruction such as
  `<?xml version="1.0" encoding="utf-8"?>`,
- `MXML_TYPE_ELEMENT`: An XML element with optional attributes such as
  `<element name="value">`,
- `MXML_TYPE_INTEGER`: A whitespace-delimited integer value such as `42`,
- `MXML_TYPE_OPAQUE`: An opaque string value that preserves all whitespace
  such as `All work and no play makes Johnny a dull boy.`,
- `MXML_TYPE_REAL`: A whitespace-delimited floating point value such as
  `123.4`, or
- `MXML_TYPE_TEXT`: A whitespace-delimited text (fragment) value such as
  `Word`.

The parent, sibling, and child nodes are accessed using the [mxmlGetParent](@@),
[mxmlGetNextSibling](@@), [mxmlGetPreviousSibling](@@), [mxmlGetFirstChild](@@),
and [mxmlGetLastChild](@@) functions.

The value(s) of a node are accessed using the [mxmlGetCDATA](@@),
[mxmlGetComment](@@), [mxmlGetDeclaration](@@), [mxmlGetDirective](@@),
[mxmlGetElement](@@), [mxmlElementGetAttr](@@), [mxmlGetInteger](@@),
[mxmlGetOpaque](@@), [mxmlGetReal](@@), and [mxmlGetText](@@) functions.


Loading an XML File
-------------------

You load an XML file using the [mxmlLoadFilename](@@) function:

```c
mxml_node_t *
mxmlLoadFilename(mxml_node_t *top, mxml_options_t *options,
                 const char *filename);
```

Mini-XML also provides functions to load from a `FILE` pointer, a file
descriptor, a string, or using a callback:

```c
mxml_node_t *
mxmlLoadFd(mxml_node_t *top, mxml_options_t *options,
           int fd);

mxml_node_t *
mxmlLoadFile(mxml_node_t *top, mxml_options_t *options,
             FILE *fp);

mxml_node_t *
mxmlLoadIO(mxml_node_t *top, mxml_options_t *options,
           mxml_io_cb_t io_cb, void *io_cbdata);

mxml_node_t *
mxmlLoadString(mxml_node_t *top, mxml_options_t *options,
               const char *s);
```

Each accepts a pointer to the top-most ("root") node (usually `NULL`) you want
to add the XML data to, any load options, and the content to be loaded.  For
example, the following code will load an XML file called "example.xml" using the
default load options:

```c
mxml_node_t *xml;

xml = mxmlLoadFilename(/*top*/NULL, /*options*/NULL,
                       "example.xml");
```


### Load Options

Load options are specified using a `mxml_options_t` pointer, which you create
using the [mxmlOptionsNew](@@) function:

```c
mxml_options_t *options = mxmlOptionsNew();
```

The default load options will treat any values in your XML as whitespace-
delimited text (`MXML_TYPE_TEXT`).  You can specify a different type of values
using the [mxmlOptionsSetTypeValue](@@) function.  For example, the following
will specify that values are opaque text strings, including whitespace
(`MXML_TYPE_OPAQUE`):

```c
mxmlOptionsSetTypeValue(options, MXML_TYPE_OPAQUE);
```

For more complex XML documents, you can specify a callback that returns the type
of value for a given element node using the [mxmlOptionsSetTypeCallback](@@)
function.  For example, to specify a callback function called `my_type_cb` that
has no callback data:

```c
mxmlOptionsSetTypeCallback(options, my_type_cb, /*cbdata*/NULL);
```

The `my_type_cb` function accepts the callback data pointer (`NULL` in this
case) and the `mxml_node_t` pointer for the current element and returns a
`mxml_type_t` enumeration value specifying the value type for child nodes.  For
example, the following function looks at the "type" attribute and the element
name to determine the value types of the node's children:

```c
mxml_type_t
my_load_cb(void *cbdata, mxml_node_t *node)
{
  const char *type;

 /*
  * You can lookup attributes and/or use the element name,
  * hierarchy, etc...
  */

  type = mxmlElementGetAttr(node, "type");
  if (type == NULL)
    type = mxmlGetElement(node);
  if (type == NULL)
    type = "text";

  if (!strcmp(type, "integer"))
    return (MXML_TYPE_INTEGER);
  else if (!strcmp(type, "opaque"))
    return (MXML_TYPE_OPAQUE);
  else if (!strcmp(type, "real"))
    return (MXML_TYPE_REAL);
  else
    return (MXML_TYPE_TEXT);
}
```


Finding Nodes
-------------

The [mxmlFindPath](@@) function finds the (first) value node under a specific
element using a path.  The path string can contain the "*" wildcard to match a
single element node in the hierarchy.  For example, the following code will find
the first "node" element under the "group" element, first using an explicit path
and then using a wildcard:

```c
mxml_node_t *directnode = mxmlFindPath(xml, "data/group/node");

mxml_node_t *wildnode = mxmlFindPath(xml, "data/*/node");
```

The [mxmlFindElement](@@) function can be used to find a named element,
optionally matching an attribute and value:

```c
mxml_node_t *
mxmlFindElement(mxml_node_t *node, mxml_node_t *top,
                const char *element, const char *attr,
                const char *value, int descend);
```

The `element`, `attr`, and `value` arguments can be passed as `NULL` to act as
wildcards, e.g.:

```c
mxml_node_t *node;

/* Find the first "a" element */
node = mxmlFindElement(tree, tree, "a", NULL, NULL,
                       MXML_DESCEND_ALL);

/* Find the first "a" element with "href" attribute */
node = mxmlFindElement(tree, tree, "a", "href", NULL,
                       MXML_DESCEND_ALL);

/* Find the first "a" element with "href" to a URL */
node = mxmlFindElement(tree, tree, "a", "href",
                       "http://msweet.org/",
                       MXML_DESCEND_ALL);

/* Find the first element with a "src" attribute*/
node = mxmlFindElement(tree, tree, NULL, "src", NULL,
                       MXML_DESCEND_ALL);

/* Find the first element with a "src" = "foo.jpg" */
node = mxmlFindElement(tree, tree, NULL, "src", "foo.jpg",
                       MXML_DESCEND_ALL);
```

You can also iterate with the same function:

```c
mxml_node_t *node;

for (node = mxmlFindElement(tree, tree, "element", NULL,
                            NULL, MXML_DESCEND_ALL);
     node != NULL;
     node = mxmlFindElement(node, tree, "element", NULL,
                            NULL, MXML_DESCEND_ALL))
{
  ... do something ...
}
```

The `descend` argument \(`MXML_DESCEND_ALL` in the previous examples) can be one
of three constants:

- `MXML_DESCEND_NONE`: ignore child nodes in the element hierarchy, instead
  using siblings (same level) or parent nodes (above) until the top (root) node
  is reached.

- `MXML_DESCEND_FIRST`: start the search with the first child of the node, and
  then search siblings.  You'll normally use this when iterating through direct
  children of a parent node, e.g. all of the `<node>` and `<group>` elements
  under the `<?xml ...?>` parent node in the previous example.

- `MXML_DESCEND_ALL`: search child nodes first, then sibling nodes, and then
  parent nodes.


Getting the Value(s) from Nodes
-------------------------------

Once you have the node you can use one of the mxmlGetXxx functions to retrieve
its value(s).

Element \(`MXML_TYPE_ELEMENT`) nodes have an associated name and zero or more
named attributes with (string) values.  The [mxmlGetElement](@@) function
retrieves the element name while the [mxmlElementGetAttr](@@) function retrieves
the value string for a named attribute.  For example, the following code looks
for HTML heading elements and, when found, displays the "id" attribute for the
heading:

```c
const char *elemname = mxmlGetElement(node);
const char *id_value = mxmlElementGetAttr(node, "id");

if ((*elemname == 'h' || *elemname == 'H') &&
    elemname[1] >= '1' && elemname[1] <= '6' &&
    id_value != NULL)
  printf("%s: %s\n", elemname, id_value);
```

The [mxmlElementGetAttrByIndex](@@) and [mxmlElementGetAttrCount](@@) functions
allow you to iterate all attributes of an element.  For example, the following
code prints the element name and each of its attributes:

```c
const char *elemname = mxmlGetElement(node);
printf("%s:\n", elemname);

size_t i, count;
for (i = 0, count = mxmlElementGetAttrCount(node); i < count; i ++)
{
  const char *attrname, *attrvalue;

  attrvalue = mxmlElementGetAttrByIndex(node, i, &attrname);

  printf("    %s=\"%s\"\n", attrname, attrvalue);
}
```

CDATA \(`MXML_TYPE_CDATA`) nodes have an associated string value consisting of
the text between the `<![CDATA[` and `]]>` delimiters.  The [mxmlGetCDATA](@@)
function retrieves the CDATA string pointer for a node.  For example, the
following code gets the CDATA string value:

```c
const char *cdatavalue = mxmlGetCDATA(node);
```

Comment \(`MXML_TYPE_COMMENT`) nodes have an associated string value consisting
of the text between the `<!--` and `-->` delimiters.  The [mxmlGetComment](@@)
function retrieves the comment string pointer for a node.  For example, the
following code gets the comment string value:

```c
const char *commentvalue = mxmlGetComment(node);
```

Processing instruction \(`MXML_TYPE_DIRECTIVE`) nodes have an associated string
value consisting of the text between the `<?` and `?>` delimiters.  The
[mxmlGetDirective](@@) function retrieves the processing instruction string
for a node.  For example, the following code gets the processing instruction
string value:

```c
const char *instrvalue = mxmlGetDirective(node);
```

Integer \(`MXML_TYPE_INTEGER`) nodes have an associated `long` value.  The
[mxmlGetInteger](@@) function retrieves the integer value for a node.  For
example, the following code gets the integer value:

```c
long intvalue = mxmlGetInteger(node);
```

Opaque string \(`MXML_TYPE_OPAQUE`) nodes have an associated string value
consisting of the text between elements.  The [mxmlGetOpaque](@@) function
retrieves the opaque string pointer for a node.  For example, the following
code gets the opaque string value:

```c
const char *opaquevalue = mxmlGetOpaque(node);
```

Real number \(`MXML_TYPE_REAL`) nodes have an associated `double` value.  The
[mxmlGetReal](@@) function retrieves the real number for a node.  For example,
the following code gets the real value:

```c
double realvalue = mxmlGetReal(node);
```

Whitespace-delimited text string \(`MXML_TYPE_TEXT`) nodes have an associated
whitespace indicator and string value extracted from the text between elements.
The [mxmlGetText](@@) function retrieves the text string pointer and whitespace
boolean value for a node.  For example, the following code gets the text and
whitespace indicator:

```c
const char *textvalue;
bool whitespace;

textvalue = mxmlGetText(node, &whitespace);
```


Saving an XML File
------------------

You save an XML file using the [mxmlSaveFilename](@@) function:

```c
bool
mxmlSaveFilename(mxml_node_t *node, mxml_options_t *options,
                 const char *filename);
```

Mini-XML also provides functions to save to a `FILE` pointer, a file descriptor,
a string, or using a callback:

```c
char *
mxmlSaveAllocString(mxml_node_t *node, mxml_options_t *options);

bool
mxmlSaveFd(mxml_node_t *node, mxml_options_t *options,
           int fd);

bool
mxmlSaveFile(mxml_node_t *node, mxml_options_t *options,
             FILE *fp);

bool
mxmlSaveIO(mxml_node_t *node, mxml_options_t *options,
           mxml_io_cb_t *io_cb, void *io_cbdata);

size_t
mxmlSaveString(mxml_node_t *node, mxml_options_t *options,
               char *buffer, size_t bufsize);
```

Each accepts a pointer to the top-most ("root") node, any save options, and (as
needed) the destination.  For example, the following code saves an XML file to
the file "example.xml" with the default options:

```c
mxmlSaveFile(xml, /*options*/NULL, "example.xml");
```


### Save Options

Save options are specified using a `mxml_options_t` pointer, which you create
using the [mxmlOptionsNew](@@) function:

```c
mxml_options_t *options = mxmlOptionsNew();
```

The default save options will wrap output lines at column 72 but not add any
additional whitespace otherwise.  You can change the wrap column using the
[mxmlOptionsSetWrapMargin](@@) function.  For example, the following will set
the wrap column to 0 which disables wrapping:

```c
mxmlOptionsSetWrapMargin(options, 0);
```

To add additional whitespace to the output, set a whitespace callback using the
[mxmlOptionsSetWhitespaceCallback](@@) function.  A whitespace callback accepts
a callback data pointer, the current node, and a whitespace position value of
`MXML_WS_BEFORE_OPEN`, `MXML_WS_AFTER_OPEN`, `MXML_WS_BEFORE_CLOSE`, or
`MXML_WS_AFTER_CLOSE`.  The callback should return `NULL` if no whitespace
is to be inserted or a string of spaces, tabs, carriage returns, and newlines to
insert otherwise.

The following whitespace callback can be used to add whitespace to XHTML output
to make it more readable in a standard text editor:

```c
const char *
whitespace_cb(void *cbdata, mxml_node_t *node, mxml_ws_t where)
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
```

The following code will set the whitespace callback for the save options:

```c
mxmlOptionsSetWhitespaceCallback(options, whitespace_cb, /*cbdata*/NULL);
```


Freeing Memory
--------------

Once you are done with the XML data, use the [mxmlDelete](@@) function to
free the memory that is used for a particular node and its children.  For
example, the following code frees the XML data loaded by the previous examples:

```c
mxmlDelete(xml);
```


Creating New XML Documents
==========================

You can create new and update existing XML documents in memory using the various
mxmlNewXxx functions. The following code will create the XML document described
in the [Using Mini-XML](@) chapter:

```c
mxml_node_t *xml;    /* <?xml version="1.0" charset="utf-8"?> */
mxml_node_t *data;   /* <data> */
mxml_node_t *node;   /* <node> */
mxml_node_t *group;  /* <group> */

xml = mxmlNewXML("1.0");

data = mxmlNewElement(xml, "data");

  node = mxmlNewElement(data, "node");
  mxmlNewText(node, false, "val1");
  node = mxmlNewElement(data, "node");
  mxmlNewText(node, false, "val2");
  node = mxmlNewElement(data, "node");
  mxmlNewText(node, false, "val3");

  group = mxmlNewElement(data, "group");

    node = mxmlNewElement(group, "node");
    mxmlNewText(node, false, "val4");
    node = mxmlNewElement(group, "node");
    mxmlNewText(node, false, "val5");
    node = mxmlNewElement(group, "node");
    mxmlNewText(node, false, "val6");

  node = mxmlNewElement(data, "node");
  mxmlNewText(node, false, "val7");
  node = mxmlNewElement(data, "node");
  mxmlNewText(node, false, "val8");
```

We start by creating the processing instruction node common to all XML files
using the [mxmlNewXML](@@) function:

```c
xml = mxmlNewXML("1.0");
```

We then create the `<data>` node used for this document using the
[mxmlNewElement](@@) function.  The first argument specifies the parent node
\(`xml`) while the second specifies the element name \(`data`):

```c
data = mxmlNewElement(xml, "data");
```

Each `<node>...</node>` in the file is created using the [mxmlNewElement](@@)
and [mxmlNewText](@@) functions.  The first argument of [mxmlNewText](@@)
specifies the parent node \(`node`).  The second argument specifies whether
whitespace appears before the text - `false` in this case.  The last argument
specifies the actual text to add:

```c
node = mxmlNewElement(data, "node");
mxmlNewText(node, false, "val1");
```

The resulting in-memory XML document can then be saved or processed just like
one loaded from disk or a string.


Element Nodes
-------------

Element \(`MXML_TYPE_ELEMENT`) nodes are created using the [mxmlNewElement](@@)
function.  Element attributes are set using the [mxmlElementSetAttr](@@) and
[mxmlElementSetAttrf](@@) functions and cleared using the
[mxmlElementClearAttr](@@) function:

```c
mxml_node_t *
mxmlNewElement(mxml_node_t *parent, const char *name);

void
mxmlElementClearAttr(mxml_node_t *node, const char *name);

void
mxmlElementSetAttr(mxml_node_t *node, const char *name,
                   const char *value);

void
mxmlElementSetAttrf(mxml_node_t *node, const char *name,
                    const char *format, ...);
```


CDATA Nodes
-----------

CDATA \(`MXML_TYPE_CDATA`) nodes are created using the [mxmlNewCDATA](@@)
and [mxmlNewCDATAf](@@) functions and set using the [mxmlSetCDATA](@@) and
[mxmlSetCDATAf](@@) functions:

```c
mxml_node_t *
mxmlNewCDATA(mxml_node_t *parent, const char *string);

mxml_node_t *
mxmlNewCDATAf(mxml_node_t *parent, const char *format, ...);

void
mxmlSetCDATA(mxml_node_t *node, const char *string);

void
mxmlSetCDATAf(mxml_node_t *node, const char *format, ...);
```


Comment Nodes
-------------

Comment \(`MXML_TYPE_COMMENT`) nodes are created using the [mxmlNewComment](@@)
and [mxmlNewCommentf](@@) functions and set using the [mxmlSetComment](@@)
and [mxmlSetCommentf](@@) functions:

```c
mxml_node_t *
mxmlNewComment(mxml_node_t *parent, const char *string);

mxml_node_t *
mxmlNewCommentf(mxml_node_t *parent, const char *format, ...);

void
mxmlSetComment(mxml_node_t *node, const char *string);

void
mxmlSetCommentf(mxml_node_t *node, const char *format, ...);
```


Processing Instruction Nodes
----------------------------

Processing instruction \(`MXML_TYPE_DIRECTIVE`) nodes are created using the
[mxmlNewDirective](@@) and [mxmlNewDirectivef](@@) functions and set using the
[mxmlSetDirective](@@) and [mxmlSetDirectivef](@@) functions:

```c
mxml_node_t *node = mxmlNewDirective("xml-stylesheet type=\"text/css\" href=\"style.css\"");

mxml_node_t *node = mxmlNewDirectivef("xml version=\"%s\"", version);
```

The [mxmlNewXML](@@) function can be used to create the top-level "xml"
processing instruction with an associated version number:

```c
mxml_node_t *
mxmlNewXML(const char *version);
```


Integer Nodes
-------------

Integer \(`MXML_TYPE_INTEGER`) nodes are created using the [mxmlNewInteger](@@)
function and set using the [mxmlSetInteger](@@) function:

```c
mxml_node_t *
mxmlNewInteger(mxml_node_t *parent, long integer);

void
mxmlSetInteger(mxml_node_t *node, long integer);
```


Opaque String Nodes
-------------------

Opaque string \(`MXML_TYPE_OPAQUE`) nodes are created using the
[mxmlNewOpaque](@@) and [mxmlNewOpaquef](@@) functions and set using the
[mxmlSetOpaque](@@) and [mxmlSetOpaquef](@@) functions:

```c
mxml_node_t *
mxmlNewOpaque(mxml_node_t *parent, const char *opaque);

mxml_node_t *
mxmlNewOpaquef(mxml_node_t *parent, const char *format, ...);

void
mxmlSetOpaque(mxml_node_t *node, const char *opaque);

void
mxmlSetOpaquef(mxml_node_t *node, const char *format, ...);
```


Real Number Nodes
-----------------

Real number \(`MXML_TYPE_REAL`) nodes are created using the [mxmlNewReal](@@)
function and set using the [mxmlSetReal](@@) function:

```c
mxml_node_t *
mxmlNewReal(mxml_node_t *parent, double real);

void
mxmlSetReal(mxml_node_t *node, double real);
```


Text Nodes
----------

Whitespace-delimited text string \(`MXML_TYPE_TEXT`) nodes are created using the
[mxmlNewText](@@) and [mxmlNewTextf](@@) functions and set using the
[mxmlSetText](@@) and [mxmlSetTextf](@@) functions.  Each text node consists of
a text string and (leading) whitespace boolean value.

```c
mxml_node_t *
mxmlNewText(mxml_node_t *parent, bool whitespace,
            const char *string);

mxml_node_t *
mxmlNewTextf(mxml_node_t *parent, bool whitespace,
             const char *format, ...);

void
mxmlSetText(mxml_node_t *node, bool whitespace,
            const char *string);

void
mxmlSetTextf(mxml_node_t *node, bool whitespace,
             const char *format, ...);
```


Iterating and Indexing the Tree
===============================


Iterating Nodes
---------------

While the [mxmlFindNode](@@) and [mxmlFindPath](@@) functions will find a
particular element node, sometimes you need to iterate over all nodes.  The
[mxmlWalkNext](@@) and [mxmlWalkPrev](@@) functions can be used to iterate
through the XML node tree:

```c
mxml_node_t *
mxmlWalkNext(mxml_node_t *node, mxml_node_t *top,
             int descend);

mxml_node_t *
mxmlWalkPrev(mxml_node_t *node, mxml_node_t *top,
             int descend);
```

Depending on the value of the `descend` argument, these functions will
automatically traverse child, sibling, and parent nodes until the `top` node is
reached.  For example, the following code will iterate over all of the nodes in
the sample XML document in the [Using Mini-XML](@) chapter:

```c
mxml_node_t *node;

for (node = xml;
     node != NULL;
     node = mxmlWalkNext(node, xml, MXML_DESCEND_ALL))
{
  ... do something ...
}
```

The nodes will be returned in the following order:

```
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
```


Indexing
--------

The [mxmlIndexNew](@@) function allows you to create an index of nodes for
faster searching and enumeration:

```c
mxml_index_t *
mxmlIndexNew(mxml_node_t *node, const char *element,
             const char *attr);
```

The `element` and `attr` arguments control which elements are included in the
index.  If `element` is not `NULL` then only elements with the specified name
are added to the index.  Similarly, if `attr` is not `NULL` then only elements
containing the specified attribute are added to the index.  The nodes are sorted
in the index.

For example, the following code creates an index of all "id" values in an XML
document:

```c
mxml_index_t *ind = mxmlIndexNew(xml, NULL, "id");
```

Once the index is created, the [mxmlIndexFind](@@) function can be used to find a
matching node:

```c
mxml_node_t *
mxmlIndexFind(mxml_index_t *ind, const char *element,
              const char *value);
```

For example, the following code will find the element whose "id" string is "42":

```c
mxml_node_t *node = mxmlIndexFind(ind, NULL, "42");
```

Alternately, the [mxmlIndexReset](@@) and [mxmlIndexEnum](@@) functions can be used to
enumerate the nodes in the index:

```c
mxml_node_t *
mxmlIndexReset(mxml_index_t *ind);

mxml_node_t *
mxmlIndexEnum(mxml_index_t *ind);
```

Typically these functions will be used in a `for` loop:

```c
mxml_node_t *node;

for (node = mxmlIndexReset(ind);
     node != NULL;
     node = mxmlIndexEnum(ind))
{
  ... do something ...
}
```

The [mxmlIndexCount](@@) function returns the number of nodes in the index:

```c
size_t
mxmlIndexGetCount(mxml_index_t *ind);
```

Finally, the [mxmlIndexDelete](@@) function frees all memory associated with the
index:

```c
void
mxmlIndexDelete(mxml_index_t *ind);
```


Advanced Usage
==============


Custom Data Types
-----------------

Mini-XML supports custom data types via load and save callback options.
Only a single set of callbacks can be active at any time for a `mxml_options_t`
pointer, however your callbacks can store additional information in order to
support multiple custom data types as needed.  The `MXML_TYPE_CUSTOM` node type
identifies custom data nodes.

The [mxmlGetCustom](@@) function retrieves the custom value pointer for a node.

```c
const void *
mxmlGetCustom(mxml_node_t *node);
```

Custom \(`MXML_TYPE_CUSTOM`) nodes are created using the [mxmlNewCustom](@@)
function or using the custom load callback specified using the
[mxmlOptionsSetCustomCallbacks](@@) function:

```c
typedef void (*mxml_custfree_cb_t)(void *cbdata, void *data);
typedef bool (*mxml_custload_cb_t)(void *cbdata, mxml_node_t *, const char *);
typedef char *(*mxml_custsave_cb_t)(void *cbdata, mxml_node_t *);

mxml_node_t *
mxmlNewCustom(mxml_node_t *parent, void *data,
              mxml_custfree_cb_t free_cb, void *free_cbdata);

int
mxmlSetCustom(mxml_node_t *node, void *data,
              mxml_custfree_cb_t free_cb, void *free_cbdata);

void
mxmlOptionsSetCustomCallbacks(mxml_option_t *options,
                              mxml_custload_cb_t load_cb,
                              mxml_custsave_cb_t save_cb,
                              void *cbdata);
```

The load callback receives the callback data pointer, a pointer to the current
data node, and a string of opaque character data from the XML source with
character entities converted to the corresponding UTF-8 characters.  For
example, if we wanted to support a custom date/time type whose value is encoded
as "yyyy-mm-ddThh:mm:ssZ" (ISO 8601 format), the load callback would look like
the following:

```c
typedef struct iso_date_time_s
{
  unsigned year,    /* Year */
           month,   /* Month */
           day,     /* Day */
           hour,    /* Hour */
           minute,  /* Minute */
           second;  /* Second */
  time_t   unix;    /* UNIX time */
} iso_date_time_t;

bool
custom_load_cb(void *cbdata, mxml_node_t *node, const char *data)
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

    return (false);
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

    return (false);
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
  * Assign custom node data and free callback function/data...
  */

  mxmlSetCustom(node, data, custom_free_cb, cbdata);

 /*
  * Return with no errors...
  */

  return (true);
}
```

The function itself can return `true` on success or `false` if it is unable to
decode the custom data or the data contains an error.  Custom data nodes contain
a `void` pointer to the allocated custom data for the node and a pointer to a
destructor function which will free the custom data when the node is deleted.
In this example, we use the standard `free` function since everything is
contained in a single calloc'd block.

The save callback receives the node pointer and returns an allocated string
containing the custom data value.  The following save callback could be used for
our ISO date/time type:

```c
char *
custom_save_cb(void *cbdata, mxml_node_t *node)
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
```

You register these callback functions using the
[mxmlOptionsSetCustomCallbacks](@@) function:

```c
mxmlOptionsSetCustomCallbacks(options, custom_load_cb,
                              custom_save_cb, /*cbdata*/NULL);
```


SAX (Stream) Loading of Documents
---------------------------------

Mini-XML supports an implementation of the Simple API for XML (SAX) which allows
you to load and process an XML document as a stream of nodes.  Aside from
allowing you to process XML documents of any size, the Mini-XML implementation
also allows you to retain portions of the document in memory for later
processing.

The mxmlLoadXxx functions support a SAX option that is enabled by setting a
callback function and data pointer with the [mxmlOptionsSetSAXCallback](@@)
function.  The callback function receives the data pointer you supplied, the
node, and an event code and returns `true` to continue processing or `false`
to stop:

```c
bool
sax_cb(void *cbdata, mxml_node_t *node,
       mxml_sax_event_t event)
{
  ... do something ...

  // Continue processing...
  return (true);
}
```

The event will be one of the following:

- `MXML_SAX_EVENT_CDATA`: CDATA was just read.
- `MXML_SAX_EVENT_COMMENT`: A comment was just read.
- `MXML_SAX_EVENT_DATA`: Data (integer, opaque, real, or text) was just read.
- `MXML_SAX_EVENT_DECLARATION`: A declaration was just read.
- `MXML_SAX_EVENT_DIRECTIVE`: A processing directive/instruction was just read.
- `MXML_SAX_EVENT_ELEMENT_CLOSE` - A close element was just read \(`</element>`)
- `MXML_SAX_EVENT_ELEMENT_OPEN` - An open element was just read \(`<element>`)

Elements are *released* after the close element is processed.  All other nodes
are released after they are processed.  The SAX callback can *retain* the node
using the [mxmlRetain](@@) function.  For example, the following SAX callback
will retain all nodes, effectively simulating a normal in-memory load:

```c
bool
sax_cb(void *cbdata, mxml_node_t *node, mxml_sax_event_t event)
{
  if (event != MXML_SAX_ELEMENT_CLOSE)
    mxmlRetain(node);

  return (true);
}
```

More typically the SAX callback will only retain a small portion of the document
that is needed for post-processing.  For example, the following SAX callback
will retain the title and headings in an XHTML file.  It also retains the
(parent) elements like `<html>`, `<head>`, and `<body>`, and processing
directives like  `<?xml ... ?>` and declarations like `<!DOCTYPE ... >`:

```c
bool
sax_cb(void *cbdata, mxml_node_t *node,
       mxml_sax_event_t event)
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
  else if (event == MXML_SAX_DECLARATION)
    mxmlRetain(node);
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

  return (true);
}
```

The resulting skeleton document tree can then be searched just like one loaded
without the SAX callback function.  For example, a filter that reads an XHTML
document from stdin and then shows the title and headings in the document would
look like:

```c
mxml_options_t *options;
mxml_node_t *xml, *title, *body, *heading;

options = mxmlOptionsNew();
mxmlOptionsSetSAXCallback(options, sax_cb,
                          /*cbdata*/NULL);

xml = mxmlLoadFd(/*top*/NULL, options, /*fd*/0);

title = mxmlFindElement(doc, doc, "title", NULL, NULL,
                        MXML_DESCEND_ALL);

if (title)
  print_children(title);

body = mxmlFindElement(doc, doc, "body", NULL, NULL,
                       MXML_DESCEND_ALL);

if (body)
{
  for (heading = mxmlGetFirstChild(body);
       heading;
       heading = mxmlGetNextSibling(heading))
    print_children(heading);
}

mxmlDelete(xml);
mxmlOptionsDelete(options);
```

The `print_children` function is:

```c
void
print_children(mxml_node_t *parent)
{
  mxml_node_t *node;
  const char *text;
  bool whitespace;

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
```


User Data
---------

Each node has an associated user data pointer that can be used to store useful
information for your application.  The memory used by the data pointer is *not*
managed by Mini-XML so it is up to you to free it as necessary.

The [mxmlSetUserData](@@) function sets any user (application) data associated
with the node while the [mxmlGetUserData](@@) function gets any user
(application) data associated with the node:

```c
void *
mxmlGetUserData(mxml_node_t *node);

void
mxmlSetUserData(mxml_node_t *node, void *user_data);
```


Memory Management
-----------------

Nodes support reference counting to manage memory usage.  The [mxmlRetain](@@)
and [mxmlRelease](@@) functions increment and decrement a node's reference
count, respectively.  When the reference count goes to zero, [mxmlRelease](@@)
calls [mxmlDelete](@@) to actually free the memory used by the node tree.  New
nodes start with a reference count of `1`.  You can get a node's current
reference count using the [mxmlGetRefCount](@@) function.

Strings can also support different kinds of memory management.  The default is
to use the standard C library strdup and free functions.  To use alternate an
alternate mechanism, call the [mxmlSetStringCallbacks](@@) function to set
string copy and free callbacks.  The copy callback receives the callback data
pointer and the string to copy, and returns a new string that will persist for
the life of the XML data.  The free callback receives the callback data pointer
and the copied string and potentially frees the memory used for it.  For
example, the following code implements a simple string pool that eliminates
duplicate strings:

```c
typedef struct string_pool_s
{
  size_t num_strings;   // Number of strings
  size_t alloc_strings; // Allocated strings
  char   **strings;      // Array of strings
} string_pool_t;

char *
copy_string(string_pool_t *pool, const char *s)
{
  size_t i;     // Looping var
  char   *news; // Copy of string


  // See if the string is already in the pool...
  for (i = 0; i < pool->num_strings; i ++)
  {
    if (!strcmp(pool->strings[i], s))
      return (pool->strings[i]);
  }

  // Not in the pool, add new string
  if (pool->num_strings >= pool->alloc_strings)
  {
    // Expand the string pool...
    char **temp; // New strings array

    temp = realloc(pool->strings,
                   (pool->alloc_strings + 32) *
                       sizeof(char *));

    if (temp == NULL)
      return (NULL);

    pool->alloc_strings += 32;
    pool->strings = temp;
  }

  if ((news = strdup(s)) != NULL)
    pool->strings[pool->num_strings ++] = news;

  return (news);
}

void
free_string(string_pool_t *pool, char *s)
{
  // Do nothing here...
}

void
free_all_strings(string_pool_t *pool)
{
  size_t i; // Looping var


  for (i = 0; i < pool->num_strings; i ++)
    free(pool->strings[i]);
  free(pool->strings);
}

...

// Setup the string pool...
string_pool_t pool = { 0, 0, NULL };

mxmlSetStringCallbacks((mxml_strcopy_cb_t)copy_string,
                       (mxml_strfree_cb_t)free_string,
                       &pool);

// Load an XML file...
mxml_node_t *xml;

xml = mxmlLoadFilename(/*top*/NULL, /*options*/NULL,
                       "example.xml");

// Process the XML file...
...

// Free memory used by the XML file...
mxmlDelete(xml);

// Free all strings in the pool...
free_all_strings(&pool);
```


Migrating from Mini-XML v3.x
============================

The following incompatible API changes were made in Mini-XML v4.0:

- Load and save callbacks and options are now managed using `mxml_options_t`
  values.
- The mxmlSAXLoadXxx functions have been removed in favor of setting the SAX
  callback function and data pointers of the `mxml_options_t` value prior to
  calling the corresponding mxmlLoadXxx functions.
- SAX events are now named `MXML_SAX_EVENT_foo` instead of `MXML_SAX_foo`.
- SAX callbacks now return a boolean value.
- Node types are now named `MXML_TYPE_foo` instead of `MXML_foo`.
- Descend values are now normalized to `MXML_DESCEND_ALL`, `MXML_DESCEND_FIRST`,
  and `MXML_DESCEND_NONE`.
- Functions that returned `0` on success and `-1` on error now return `true` on
  success and `false` on error.
- CDATA nodes ("`<![CDATA[...]]>`") now have their own type (`MXML_TYPE_CDATA`).
- Comment nodes ("`<!-- ... -->`") now have their own type
  (`MXML_TYPE_COMMENT`).
- Declaration nodes ("`<!...>`") now have their own type
  (`MXML_TYPE_DECLARATION`).
- Element attributes are now cleared with the [mxmlElementClearAttr](@@)
  function instead of mxmlElementDeleteAttr.
- Processing instruction/directive nodes ("`<?...?>`") now have their own type
  (`MXML_TYPE_DIRECTIVE`).
- Integer nodes (`MXML_TYPE_INTEGER`) now use the `long` type.
- Text nodes (`MXML_TYPE_TEXT`) now use the `bool` type for the whitespace
  value.
- Custom node callbacks are now set using the
  [mxmlOptionsSetCustomCallbacks](@@) function instead of the thread-global
  mxmlSetCustomHandlers function.
