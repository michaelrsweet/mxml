Mini-XML - Tiny XML Parsing Library
===================================

![Version](https://img.shields.io/github/v/release/michaelrsweet/mxml?include_prereleases)
![Apache 2.0](https://img.shields.io/github/license/michaelrsweet/mxml)
![Build](https://github.com/michaelrsweet/mxml/workflows/Build/badge.svg)
[![Coverity Scan Status](https://img.shields.io/coverity/scan/23959.svg)](https://scan.coverity.com/projects/michaelrsweet-mxml)
[![LGTM Grade](https://img.shields.io/lgtm/grade/cpp/github/michaelrsweet/mxml)](https://lgtm.com/projects/g/michaelrsweet/mxml/context:cpp)
[![LGTM Alerts](https://img.shields.io/lgtm/alerts/github/michaelrsweet/mxml)](https://lgtm.com/projects/g/michaelrsweet/mxml/)

Mini-XML is a small XML parsing library that you can use to read XML data files
or strings in your application without requiring large non-standard libraries.
Mini-XML only requires a "make" program and an ANSI C compatible compiler - GCC
works, as do most vendors' ANSI C compilers.

Mini-XML provides the following functionality:

- Reading of UTF-8 and UTF-16 and writing of UTF-8 encoded XML files and
  strings.
- Data is stored in a linked-list tree structure, preserving the XML data
  hierarchy.
- SAX (streamed) reading of XML files and strings to minimize memory usage.
- Supports arbitrary element names, attributes, and attribute values with no
  preset limits, just available memory.
- Supports integer, real, opaque ("cdata"), and text data types in "leaf" nodes.
- Functions for creating and managing trees of data.
- "Find" and "walk" functions for easily locating and navigating trees of data.

Mini-XML doesn't do validation or other types of processing on the data
based upon schema files or other sources of definition information.

> Note: Version 3.0 hides the definition of the `mxml_node_t` structure,
> requiring the use of the various accessor functions that were introduced in
> version 2.0.


Building Mini-XML
-----------------

Mini-XML comes with an autoconf-based configure script; just type the
following command to get things going:

    ./configure

The default install prefix is `/usr/local`, which can be overridden using the
`--prefix` option:

    ./configure --prefix=/foo

Other configure options can be found using the `--help` option:

    ./configure --help

Once you have configured the software, type `make` to do the build and run
the test program to verify that things are working, as follows:

    make

If you are using Mini-XML under Microsoft Windows with Visual C++, use the
included project files in the `vcnet` subdirectory to build the library
instead.  Note: The static library on Windows is NOT thread-safe.


## Installing Mini-XML

The `install` target will install Mini-XML in the lib and include
directories:

    make install

Once you have installed it, use the `-lmxml` option to link your application
against it.


Documentation
-------------

The documentation is available in the `doc` subdirectory in the files
`mxml.html` (HTML) and `mxml.epub` (EPUB).  You can also look at the
`testmxml.c` source file for examples of using Mini-XML.

Mini-XML provides a single header file which you include:

    #include <mxml.h>

Nodes (elements, comments, processing directives, integers, opaque strings, real
numbers, and text strings) are represented by `mxml_node_t` objects.  New nodes
can be created using the `mxmlNewElement()`, `mxmlNewInteger()`,
`mxmlNewOpaque()`, `mxmlNewReal()`, and `mxmlNewText()` functions.  Only
elements can have child nodes, and the top node must be the "?xml" processing
directive.

You load an XML file using the `mxmlLoadFile()` function:

    FILE *fp;
    mxml_node_t *tree;

    fp = fopen("filename.xml", "r");
    tree = mxmlLoadFile(NULL, fp, MXML_OPAQUE_CALLBACK);
    fclose(fp);

Similarly, you save an XML file using the `mxmlSaveFile()` function:

    FILE *fp;
    mxml_node_t *tree;

    fp = fopen("filename.xml", "w");
    mxmlSaveFile(tree, fp, MXML_NO_CALLBACK);
    fclose(fp);

The `mxmlLoadString()`, `mxmlSaveAllocString()`, and `mxmlSaveString()`
functions load XML node trees from and save XML node trees to strings:

    char buffer[8192];
    char *ptr;
    mxml_node_t *tree;

    ...
    tree = mxmlLoadString(NULL, buffer, MXML_OPAQUE_CALLBACK);

    ...
    mxmlSaveString(tree, buffer, sizeof(buffer), MXML_NO_CALLBACK);

    ...
    ptr = mxmlSaveAllocString(tree, MXML_NO_CALLBACK);

You can find a named element/node using the `mxmlFindElement()` function:

    mxml_node_t *node = mxmlFindElement(tree, tree, "name", "attr",
					"value", MXML_DESCEND);

The `name`, `attr`, and `value` arguments can be passed as `NULL` to act as
wildcards, e.g.:

    /* Find the first "a" element */
    node = mxmlFindElement(tree, tree, "a", NULL, NULL, MXML_DESCEND);

    /* Find the first "a" element with "href" attribute */
    node = mxmlFindElement(tree, tree, "a", "href", NULL, MXML_DESCEND);

    /* Find the first "a" element with "href" to a URL */
    node = mxmlFindElement(tree, tree, "a", "href",
			   "http://www.minixml.org/",
			   MXML_DESCEND);

    /* Find the first element with a "src" attribute*/
    node = mxmlFindElement(tree, tree, NULL, "src", NULL, MXML_DESCEND);

    /* Find the first element with a "src" = "foo.jpg" */
    node = mxmlFindElement(tree, tree, NULL, "src", "foo.jpg",
			   MXML_DESCEND);

You can also iterate with the same function:

    mxml_node_t *node;

    for (node = mxmlFindElement(tree, tree, "name", NULL, NULL,
				MXML_DESCEND);
	 node != NULL;
	 node = mxmlFindElement(node, tree, "name", NULL, NULL,
				MXML_DESCEND))
    {
      ... do something ...
    }

The `mxmlFindPath()` function finds the (first) value node under a specific
element using an XPath:

    mxml_node_t *value = mxmlFindPath(tree, "path/to/*/foo/bar");

The `mxmlGetInteger()`, `mxmlGetOpaque()`, `mxmlGetReal()`, and
`mxmlGetText()` functions retrieve the corresponding value from a node:

    mxml_node_t *node;

    int intvalue = mxmlGetInteger(node);

    const char *opaquevalue = mxmlGetOpaque(node);

    double realvalue = mxmlGetReal(node);

    int whitespacevalue;
    const char *textvalue = mxmlGetText(node, &whitespacevalue);

Finally, once you are done with the XML data, use the `mxmlDelete()`
function to recursively free the memory that is used for a particular node
or the entire tree:

    mxmlDelete(tree);


Getting Help And Reporting Problems
-----------------------------------

The [Mini-XML project page](https://www.msweet.org/mxml) provides access to the
current version of this software, documentation, and Github issue tracking page.


Legal Stuff
-----------

Copyright © 2003-2022 by Michael R Sweet

The Mini-XML library is licensed under the Apache License Version 2.0 with an
*optional* exception to allow linking against GPL2/LGPL2-only software.  See the
files "LICENSE" and "NOTICE" for more information.

> Note: The exception listed in the NOTICE file only applies when linking
> against GPL2/LGPL2-only software.  Some Apache License purists have objected
> to linking Apache Licensed code against Mini-XML with these exceptions on the
> grounds that it makes Mini-XML somehow incompatible with the Apache License.
> For that reason, people wishing to retain their Apache License purity may
> omit the exception from their copy of Mini-XML.

> Note 2: IANAL, but I am beginning to dislike them!
