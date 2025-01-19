Mini-XML - Tiny XML Parsing Library v4
======================================

![Version](https://img.shields.io/github/v/release/michaelrsweet/mxml?include_prereleases)
![Apache 2.0](https://img.shields.io/github/license/michaelrsweet/mxml)
![Build](https://github.com/michaelrsweet/mxml/workflows/Build/badge.svg)
[![Coverity Scan Status](https://img.shields.io/coverity/scan/23959.svg)](https://scan.coverity.com/projects/michaelrsweet-mxml)


Mini-XML is a small XML parsing library that you can use to read XML data files
or strings in your application without requiring large non-standard libraries.
Mini-XML only requires a "make" program and a C99 compatible compiler - GCC
works, as do most vendors' C compilers.

Mini-XML provides the following functionality:

- Reading of UTF-8 and UTF-16 and writing of UTF-8 encoded XML files and
  strings.
- Data is stored in a linked-list tree structure, preserving the XML data
  hierarchy.
- SAX (streamed) reading of XML files and strings to minimize memory usage.
- Supports arbitrary element names, attributes, and attribute values with no
  preset limits, just available memory.
- Supports integer, real, opaque, text, and custom data types in "leaf" nodes.
- Functions for creating and managing trees of data.
- "Find" and "walk" functions for easily locating and navigating trees of data.
- Support for custom string memory management functions to implement string
  pools and other schemes for reducing memory usage.

Mini-XML doesn't do validation or other types of processing on the data
based upon schema files or other sources of definition information.


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


Installing Mini-XML
-------------------

The `install` target will install Mini-XML in the lib and include
directories:

    sudo make install

Once you have installed it, use the `-lmxml` option to link your application
against it.


Documentation
-------------

The documentation is available in the `doc` subdirectory in the files
`mxml.html` (HTML) and `mxml.epub` (EPUB).  You can also look at the
`testmxml.c` source file for examples of using Mini-XML.

Mini-XML provides a single header file which you include:

    #include <mxml.h>

Nodes (elements, comments, declarations, integers, opaque strings, processing
instructions, real numbers, and text strings) are represented by `mxml_node_t`
pointers.  New nodes can be created using the mxmlNewXxx functions.  The top
node must be the `<?xml ...?>` processing instruction.

You load an XML file using the mxmlLoadFilename function:

    mxml_node_t *tree;

    tree = mxmlLoadFilename(/*top*/NULL, /*options*/NULL,
                            "example.xml");

Similarly, you save an XML file using the mxmlSaveFilename function:

    mxml_node_t *tree;

    mxmlSaveFilename(tree, /*options*/NULL,
                     "filename.xml");

There are variations of these functions for loading from or saving to file
descriptors, `FILE` pointers, strings, and IO callbacks.

You can find a named element/node using the mxmlFindElement function:

    mxml_node_t *node = mxmlFindElement(tree, tree, "name", "attr",
					"value", MXML_DESCEND_ALL);

The `name`, `attr`, and `value` arguments can be passed as `NULL` to act as
wildcards, e.g.:

    /* Find the first "a" element */
    node = mxmlFindElement(tree, tree, "a", NULL, NULL, MXML_DESCEND_ALL);

    /* Find the first "a" element with "href" attribute */
    node = mxmlFindElement(tree, tree, "a", "href", NULL, MXML_DESCEND_ALL);

    /* Find the first "a" element with "href" to a URL */
    node = mxmlFindElement(tree, tree, "a", "href",
                           "https://www.msweet.org/mxml", MXML_DESCEND_ALL);

    /* Find the first element with a "src" attribute*/
    node = mxmlFindElement(tree, tree, NULL, "src", NULL, MXML_DESCEND_ALL);

    /* Find the first element with a "src" = "foo.jpg" */
    node = mxmlFindElement(tree, tree, NULL, "src", "foo.jpg",
                           MXML_DESCEND_ALL);

You can also iterate with the same function:

    mxml_node_t *node;

    for (node = mxmlFindElement(tree, tree, "name", NULL, NULL,
				MXML_DESCEND_ALL);
	 node != NULL;
	 node = mxmlFindElement(node, tree, "name", NULL, NULL,
				MXML_DESCEND_ALL))
    {
      ... do something ...
    }

The mxmlFindPath function finds the (first) value node under a specific
element using an XPath:

    mxml_node_t *value = mxmlFindPath(tree, "path/to/*/foo/bar");

The mxmlGetInteger, mxmlGetOpaque, mxmlGetReal, and mxmlGetText functions
retrieve the corresponding value from a node:

    mxml_node_t *node;

    int intvalue = mxmlGetInteger(node);

    const char *opaquevalue = mxmlGetOpaque(node);

    double realvalue = mxmlGetReal(node);

    bool whitespacevalue;
    const char *textvalue = mxmlGetText(node, &whitespacevalue);

Finally, once you are done with the XML data, use the mxmlDelete function to
recursively free the memory that is used for a particular node or the entire
tree:

    mxmlDelete(tree);


Getting Help And Reporting Problems
-----------------------------------

The [Mini-XML project page](https://www.msweet.org/mxml) provides access to the
current version of this software, documentation, and Github issue tracking page.


Legal Stuff
-----------

Copyright © 2003-2025 by Michael R Sweet

The Mini-XML library is licensed under the Apache License Version 2.0 with an
*optional* exception to allow linking against GPL2/LGPL2-only software.  See the
files "LICENSE" and "NOTICE" for more information.

> Note: The exception listed in the NOTICE file only applies when linking
> against GPL2/LGPL2-only software.  Some Apache License purists have objected
> to linking Apache Licensed code against Mini-XML with these exceptions on the
> grounds that it makes Mini-XML somehow incompatible with the Apache License.
> For that reason, people wishing to retain their Apache License purity may
> omit the exception from their copy of Mini-XML.
>
> Note 2: IANAL, but I am beginning to dislike them!
