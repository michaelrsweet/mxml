//
// Test program for Mini-XML, a small XML file parsing library.
//
// Usage:
//
//   ./testmxml input.xml [string-output.xml] >stdio-output.xml
//   ./testmxml "<?xml ..." [string-output.xml] >stdio-output.xml
//
// https://www.msweet.org/mxml
//
// Copyright © 2003-2026 by Michael R Sweet.
//
// Licensed under Apache License v2.0.  See the file "LICENSE" for more
// information.
//

#include "mxml-private.h"
#include "test.h"
#include <math.h>
#include <fcntl.h>
#ifndef O_BINARY
#  define O_BINARY 0
#endif // !O_BINARY


//
// Globals...
//

int		event_counts[7];


//
// Local functions...
//

static int		do_unit_tests(mxml_options_t *options);
static bool		sax_cb(void *cbdata, mxml_node_t *node, mxml_sax_event_t event);
static mxml_type_t	type_cb(void *cbdata, mxml_node_t *node);
static const char	*whitespace_cb(void *cbdata, mxml_node_t *node, mxml_ws_t where);


//
// 'main()' - Main entry for test program.
//

int					// O - Exit status
main(int  argc,				// I - Number of command-line args
     char *argv[])			// I - Command-line args
{
  FILE			*fp;		// File to read
  int			fd;		// File descriptor
  mxml_options_t	*options;	// Load/save options
  mxml_node_t		*xml,		// <?xml ...?> node
			*node;		// Node which should be in test.xml
  char			buffer[16384];	// Save string
  const char		*text;		// Text string


  // Check arguments...
  if (argc > 3)
  {
    fputs("Usage: testmxml [FILENAME.xml [STRING-OUTPUT.xml]]\n", stderr);
    return (1);
  }

  options = mxmlOptionsNew();

  if (argc == 1)
  {
    int ret = do_unit_tests(options);	// Exit status

    mxmlOptionsDelete(options);

    return (ret);
  }

  // Open the file/string using the default callback...
  mxmlOptionsSetTypeValue(options, MXML_TYPE_TEXT);

  if (argv[1][0] == '<')
    xml = mxmlLoadString(/*top*/NULL, options, argv[1]);
  else
    xml = mxmlLoadFilename(/*top*/NULL, options, argv[1]);

  if (!xml)
  {
    fputs("Unable to read XML file with default callback.\n", stderr);
    goto error;
  }

  if (!strcmp(argv[1], "test.xml"))
  {
    // Verify that mxmlFindElement() and indirectly mxmlWalkNext() work properly...
    if ((node = mxmlFindPath(xml, "group/option/keyword")) == NULL)
    {
      fputs("Unable to find group/option/keyword element in XML tree.\n", stderr);
      goto error;
    }

    if (mxmlGetType(node) != MXML_TYPE_TEXT)
    {
      fputs("No child node of group/option/keyword.\n", stderr);
      mxmlSaveFile(xml, options, stderr);
      goto error;
    }

    if ((text = mxmlGetText(node, NULL)) == NULL || strcmp(text, "InputSlot"))
    {
      fprintf(stderr, "Child node of group/option/value has value \"%s\" instead of \"InputSlot\".\n", text ? text : "(null)");
      goto error;
    }
  }

  mxmlDelete(xml);

  // Open the file...
  mxmlOptionsSetTypeCallback(options, type_cb, /*cbdata*/NULL);

  if (argv[1][0] == '<')
    xml = mxmlLoadString(/*top*/NULL, options, argv[1]);
  else
    xml = mxmlLoadFilename(/*top*/NULL, options, argv[1]);

  if (!xml)
  {
    fputs("Unable to read XML file.\n", stderr);
    goto error;
  }

  if (!strcmp(argv[1], "test.xml"))
  {
    // Verify that mxmlFindElement() and indirectly mxmlWalkNext() work properly...
    if ((node = mxmlFindElement(xml, xml, "choice", NULL, NULL, MXML_DESCEND_ALL)) == NULL)
    {
      fputs("Unable to find first <choice> element in XML tree.\n", stderr);
      goto error;
    }

    if (!mxmlFindElement(node, xml, "choice", NULL, NULL, MXML_DESCEND_NONE))
    {
      fputs("Unable to find second <choice> element in XML tree.\n", stderr);
      goto error;
    }
  }

  // Print the XML tree...
  mxmlOptionsSetWhitespaceCallback(options, whitespace_cb, /*cbdata*/NULL);
  mxmlSaveFile(xml, options, stdout);

  // Save the XML tree to a string and print it...
  if (mxmlSaveString(xml, options, buffer, sizeof(buffer)) > 0)
  {
    if (argc == 3)
    {
      fp = fopen(argv[2], "w");
      fputs(buffer, fp);
      fclose(fp);
    }
  }

  // Delete the tree...
  mxmlDelete(xml);
  xml = NULL;

  // Read from/write to file descriptors...
  if (argv[1][0] != '<')
  {
    // Open the file again...
    if ((fd = open(argv[1], O_RDONLY | O_BINARY)) < 0)
    {
      perror(argv[1]);
      goto error;
    }

    // Read the file...
    xml = mxmlLoadFd(/*top*/NULL, options, fd);

    close(fd);

    // Create filename.xmlfd...
    snprintf(buffer, sizeof(buffer), "%sfd", argv[1]);

    if ((fd = open(buffer, O_WRONLY | O_CREAT | O_TRUNC | O_BINARY, 0666)) < 0)
    {
      perror(buffer);
      goto error;
    }

    // Write the file...
    mxmlSaveFd(xml, options, fd);

    close(fd);

    // Delete the tree...
    mxmlDelete(xml);
    xml = NULL;
  }

  // Test SAX methods...
  memset(event_counts, 0, sizeof(event_counts));

  mxmlOptionsSetSAXCallback(options, sax_cb, /*cbdata*/NULL);

  if (argv[1][0] == '<')
    mxmlRelease(mxmlLoadString(/*top*/NULL, options, argv[1]));
  else
    mxmlRelease(mxmlLoadFilename(/*top*/NULL, options, argv[1]));

  if (!strcmp(argv[1], "test.xml"))
  {
    if (event_counts[MXML_SAX_EVENT_CDATA] != 1)
    {
      fprintf(stderr, "MXML_SAX_EVENT_CDATA seen %d times, expected 1 times.\n", event_counts[MXML_SAX_EVENT_CDATA]);
      goto error;
    }

    if (event_counts[MXML_SAX_EVENT_COMMENT] != 1)
    {
      fprintf(stderr, "MXML_SAX_EVENT_COMMENT seen %d times, expected 1 times.\n", event_counts[MXML_SAX_EVENT_COMMENT]);
      goto error;
    }

    if (event_counts[MXML_SAX_EVENT_DATA] != 61)
    {
      fprintf(stderr, "MXML_SAX_EVENT_DATA seen %d times, expected 61 times.\n", event_counts[MXML_SAX_EVENT_DATA]);
      goto error;
    }

    if (event_counts[MXML_SAX_EVENT_DECLARATION] != 0)
    {
      fprintf(stderr, "MXML_SAX_EVENT_DECLARATION seen %d times, expected 0 times.\n", event_counts[MXML_SAX_EVENT_DECLARATION]);
      goto error;
    }

    if (event_counts[MXML_SAX_EVENT_DIRECTIVE] != 1)
    {
      fprintf(stderr, "MXML_SAX_EVENT_DIRECTIVE seen %d times, expected 1 times.\n", event_counts[MXML_SAX_EVENT_DIRECTIVE]);
      goto error;
    }

    if (event_counts[MXML_SAX_EVENT_ELEMENT_CLOSE] != 20)
    {
      fprintf(stderr, "MXML_SAX_EVENT_ELEMENT_CLOSE seen %d times, expected 20 times.\n", event_counts[MXML_SAX_EVENT_ELEMENT_CLOSE]);
      goto error;
    }

    if (event_counts[MXML_SAX_EVENT_ELEMENT_OPEN] != 20)
    {
      fprintf(stderr, "MXML_SAX_EVENT_ELEMENT_OPEN seen %d times, expected 20 times.\n", event_counts[MXML_SAX_EVENT_ELEMENT_OPEN]);
      goto error;
    }
  }

  // Free memory used by options - not explicitly required here since we are
  // just exiting, but long-running programs should do this...
  mxmlOptionsDelete(options);

#ifndef _WIN32
  // Debug hooks...
  if (getenv("TEST_DELAY") != NULL)
    sleep(atoi(getenv("TEST_DELAY")));

#  ifdef __APPLE__
  if (getenv("TEST_LEAKS") != NULL)
  {
    char command[1024];

    snprintf(command, sizeof(command), "leaks %d", (int)getpid());
    if (system(command))
      puts("Unable to check for leaks.");
  }
#  endif // __APPLE__
#endif // !_WIN32

  // Return...
  return (0);


  // If we get here something bad happened...
  error:

  mxmlDelete(xml);
  mxmlOptionsDelete(options);
  return (1);
}


//
// 'do_unit_tests()' - Do basic unit tests.
//

static int				// O - Exit status
do_unit_tests(mxml_options_t *options)	// I - Options
{
  int			ret = 1;	// Return value
  int			i;		// Looping var
  mxml_node_t		*xml,		// <?xml ...?> node
			*tree,		// Element tree
			*node;		// Node which should be in test.xml
  mxml_index_t		*ind;		// XML index
  const char		*text;		// Text string
  bool			whitespace;	// Whitespace before text string
  static const char	*types[] =	// Strings for node types
			{
			  "MXML_TYPE_CDATA",
			  "MXML_TYPE_COMMENT",
			  "MXML_TYPE_DECLARATION",
			  "MXML_TYPE_DIRECTIVE",
			  "MXML_TYPE_ELEMENT",
			  "MXML_TYPE_INTEGER",
			  "MXML_TYPE_OPAQUE",
			  "MXML_TYPE_REAL",
			  "MXML_TYPE_TEXT",
			  "MXML_TYPE_CUSTOM"
			};


  // Test the basic functionality...
  testBegin("mxmlNewXML");
  if ((xml = mxmlNewXML("1.0")) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if (mxmlGetType(xml) != MXML_TYPE_DIRECTIVE)
  {
    testEndMessage(false, "Node has type %d(%s), expected MXML_TYPE_DIRECTIVE.", mxmlGetType(xml), types[mxmlGetType(xml)]);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlNewElement(element)");
  if ((tree = mxmlNewElement(xml, "element")) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if (mxmlGetType(tree) != MXML_TYPE_ELEMENT)
  {
    testEndMessage(false, "Node has type %d(%s), expected MXML_TYPE_ELEMENT.", mxmlGetType(tree), types[mxmlGetType(tree)]);
    goto done;
  }
  else if (strcmp(mxmlGetElement(tree), "element"))
  {
    testEndMessage(false, "Node value is \"%s\", expected \"element\".", mxmlGetElement(tree));
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlNewInteger(123)");
  if ((node = mxmlNewInteger(tree, 123)) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_INTEGER)
  {
    testEndMessage(false, "Node has type %d(%s), expected MXML_TYPE_INTEGER.", mxmlGetType(node), types[mxmlGetType(node)]);
    goto done;
  }
  else if (mxmlGetInteger(node) != 123)
  {
    testEndMessage(false, "Node value is %d, expected 123.", mxmlGetInteger(node));
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlNewOpaque(opaque)");
  if ((node = mxmlNewOpaque(tree, "opaque")) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_OPAQUE)
  {
    testEndMessage(false, "Node has type %d(%s), expected MXML_TYPE_OPAQUE.", mxmlGetType(node), types[mxmlGetType(node)]);
    goto done;
  }
  else if ((text = mxmlGetOpaque(node)) == NULL || strcmp(text, "opaque"))
  {
    testEndMessage(false, "Node value is \"%s\", expected \"element\".", text);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlNewReal(123.4)");
  if ((node = mxmlNewReal(tree, 123.4)) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_REAL)
  {
    testEndMessage(false, "Node has type %d(%s), expected MXML_TYPE_REAL.", mxmlGetType(node), types[mxmlGetType(node)]);
    goto done;
  }
  else if (fabs(mxmlGetReal(node) - 123.4) > 0.01)
  {
    testEndMessage(false, "Node value is %g, expected 123.4.", mxmlGetReal(node));
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlNewText(true, text)");
  if ((node = mxmlNewText(tree, true, "text")) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_TEXT)
  {
    testEndMessage(false, "Node has type %d(%s), expected MXML_TYPE_TEXT.", mxmlGetType(node), types[mxmlGetType(node)]);
    goto done;
  }
  else if ((text = mxmlGetText(node, &whitespace)) == NULL || strcmp(text, "text") || !whitespace)
  {
    testEndMessage(false, "Node value is %s \"%s\", expected true, \"text\".", whitespace ? "true" : "false", text);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlLoadString(string values)");
  mxmlOptionsSetTypeValue(options, MXML_TYPE_TEXT);
  if ((node = mxmlLoadString(tree, options, "<group type='string'>string1 string2 string3</group>")) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if ((text = mxmlGetText(node, &whitespace)) == NULL || whitespace || strcmp(text, "string1"))
  {
    testEndMessage(false, "Node value is %s \"%s\", expected false, \"string1\".", whitespace ? "true" : "false", text);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlLoadString(integer values)");
  mxmlOptionsSetTypeValue(options, MXML_TYPE_INTEGER);
  if ((node = mxmlLoadString(tree, options, "<group type='integer'>1 2 3</group>")) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if (mxmlGetInteger(node) != 1)
  {
    testEndMessage(false, "Node value is %d, expected 1.", mxmlGetInteger(node));
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlLoadString(real values)");
  mxmlOptionsSetTypeValue(options, MXML_TYPE_REAL);
  if ((node = mxmlLoadString(tree, options, "<group type='real'>1.0 2.0 3.0</group>")) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if (fabs(mxmlGetReal(node) - 1.0) > 0.01)
  {
    testEndMessage(false, "Node value is %g, expected 1.0.", mxmlGetReal(node));
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlLoadString(opaque values)");
  mxmlOptionsSetTypeValue(options, MXML_TYPE_OPAQUE);
  if ((node = mxmlLoadString(tree, options, "<group>opaque1 opaque2\nopaque3</group>")) == NULL)
  {
    testEnd(false);
    goto done;
  }
  else if ((text = mxmlGetOpaque(node)) == NULL || strcmp(text, "opaque1 opaque2\nopaque3"))
  {
    testEndMessage(false, "Node value is \"%s\", expected \"opaque1 opaque2\\nopaque3\".", text);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlLoadString(find path values)");
  testEnd(mxmlLoadString(tree, options, "<foo><bar><one><two>value<two>value2</two></two></one></bar></foo>") != NULL);

  // TODO: Annotate these tests...
  mxmlNewCDATA(tree, "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n");
  mxmlNewCDATA(tree,
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n");
  mxmlNewCDATA(tree,
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n"
               "0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n");

  testBegin("mxmlGetFirstChild");
  if ((node = mxmlGetFirstChild(tree)) == NULL)
  {
    testEndMessage(false, "No first child node in basic test.");
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_INTEGER)
  {
    testEndMessage(false, "First child has type %d(%s), expected MXML_TYPE_INTEGER.", mxmlGetType(node), types[mxmlGetType(node)]);
    goto done;
  }
  else if (mxmlGetInteger(node) != 123)
  {
    testEndMessage(false, "First child value is %ld, expected 123.", mxmlGetInteger(node));
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlGetNextSibling(2nd)");
  if ((node = mxmlGetNextSibling(node)) == NULL)
  {
    testEndMessage(false, "No second child node in basic test.");
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_OPAQUE)
  {
    testEndMessage(false, "Second child has type %d(%s), expected MXML_TYPE_OPAQUE.", mxmlGetType(node), types[mxmlGetType(node)]);
    goto done;
  }
  else if ((text = mxmlGetOpaque(node)) == NULL || strcmp(text, "opaque"))
  {
    testEndMessage(false, "Second child value is \"%s\", expected \"opaque\".", text);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlGetNextSibling(3rd)");
  if ((node = mxmlGetNextSibling(node)) == NULL)
  {
    testEndMessage(false, "No third child node in basic test.");
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_REAL)
  {
    testEndMessage(false, "Third child has type %d(%s), expected MXML_TYPE_REAL.", mxmlGetType(node), types[mxmlGetType(node)]);
    goto done;
  }
  else if (fabs(mxmlGetReal(node) - 123.4) > 0.01)
  {
    testEndMessage(false, "Third child value is %g, expected 123.4.", mxmlGetReal(node));
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlGetNextSibling(4th)");

  if ((node = mxmlGetNextSibling(node)) == NULL)
  {
    testEndMessage(false, "No fourth child node in basic test.");
    goto done;
  }
  else  if (mxmlGetType(node) != MXML_TYPE_TEXT)
  {
    testEndMessage(false, "Fourth child has type %d(%s), expected MXML_TYPE_TEXT.", mxmlGetType(node), types[mxmlGetType(node)]);
    goto done;
  }
  else if ((text = mxmlGetText(node, &whitespace)) == NULL || !whitespace || strcmp(text, "text"))
  {
    testEndMessage(false, "Fourth child value is %s \"%s\", expected true \"text\".", whitespace ? "true" : "false", text);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  for (i = 0; i < 4; i ++)
  {
    testBegin("mxmlGetNextSibling(%dth)", i + 5);
    if ((node = mxmlGetNextSibling(node)) == NULL)
    {
      testEndMessage(false, "No group #%d child node in basic test.", i + 1);
      goto done;
    }
    else if (mxmlGetType(node) != MXML_TYPE_ELEMENT)
    {
      testEndMessage(false, "Group child #%d has type %d(%s), expected MXML_TYPE_ELEMENT.", i + 1, mxmlGetType(node), types[mxmlGetType(node)]);
      goto done;
    }
    else
    {
      testEnd(true);
    }
  }

  // Test mxmlFindPath...
  testBegin("mxmlFindPath(*/two)");
  if ((node = mxmlFindPath(tree, "*/two")) == NULL)
  {
    testEndMessage(false, "Unable to find value for \"*/two\".");
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_OPAQUE || (text = mxmlGetOpaque(node)) == NULL || strcmp(text, "value"))
  {
    testEndMessage(false, "Bad value for \"*/two\" - node=%d(%s), opaque=\"%s\"\n", mxmlGetType(node), types[mxmlGetType(node)], text);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlFindPath(foo/*/two)");
  if ((node = mxmlFindPath(tree, "foo/*/two")) == NULL)
  {
    testEndMessage(false, "Unable to find value for \"foo/*/two\".");
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_OPAQUE || (text = mxmlGetOpaque(node)) == NULL || strcmp(text, "value"))
  {
    testEndMessage(false, "Bad value for \"foo/*/two\".");
    goto done;
  }
  else
  {
    testEnd(true);
  }

  node = mxmlFindPath(tree, "foo/bar/one/two");
  if (!node)
  {
    fputs("ERROR: Unable to find value for \"foo/bar/one/two\".\n", stderr);
    goto done;
  }
  else if (mxmlGetType(node) != MXML_TYPE_OPAQUE || strcmp(mxmlGetOpaque(node), "value"))
  {
    fputs("ERROR: Bad value for \"foo/bar/one/two\".\n", stderr);
    goto done;
  }

  testBegin("mxmlFindPath(bar/*/two)");
  if ((node = mxmlFindPath(tree, "bar/*/two")) != NULL)
  {
    testEndMessage(false, "Got a node of type %d(%s).", mxmlGetType(node), types[mxmlGetType(node)]);
    goto done;
  }
  else
  {
    testEndMessage(true, "correctly did not find node");
  }

  // Test indices...
  testBegin("mxmlIndexNew(NULL, NULL)");
  if ((ind = mxmlIndexNew(tree, NULL, NULL)) == NULL)
  {
    testEndMessage(false, "Unable to create index of all nodes.");
    goto done;
  }
  else if (mxmlIndexGetCount(ind) != 10)
  {
    testEndMessage(false, "Index contains %lu nodes; expected 10.", (unsigned long)mxmlIndexGetCount(ind));
    mxmlIndexDelete(ind);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlIndexFind(group)");
  mxmlIndexReset(ind);
  if (!mxmlIndexFind(ind, "group", NULL))
  {
    testEnd(false);
    mxmlIndexDelete(ind);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  mxmlIndexDelete(ind);

  testBegin("mxmlIndexNew(group, NULL)");
  if ((ind = mxmlIndexNew(tree, "group", NULL)) == NULL)
  {
    testEndMessage(false, "Unable to create index of groups.");
    goto done;
  }
  else if (mxmlIndexGetCount(ind) != 4)
  {
    testEndMessage(false, "Index of groups contains %lu nodes; expected 4.", (unsigned long)mxmlIndexGetCount(ind));
    mxmlIndexDelete(ind);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlIndexEnum");
  mxmlIndexReset(ind);
  if (!mxmlIndexEnum(ind))
  {
    testEnd(false);
    mxmlIndexDelete(ind);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  mxmlIndexDelete(ind);

  testBegin("mxmlIndexNew(NULL, type)");
  if ((ind = mxmlIndexNew(tree, NULL, "type")) == NULL)
  {
    testEndMessage(false, "Unable to create index of type attributes.");
    goto done;
  }
  else if (mxmlIndexGetCount(ind) != 3)
  {
    testEndMessage(false, "Index of type attributes contains %lu nodes; expected 3.", (unsigned long)mxmlIndexGetCount(ind));
    mxmlIndexDelete(ind);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlIndexFind(string)");
  mxmlIndexReset(ind);
  if (!mxmlIndexFind(ind, NULL, "string"))
  {
    testEnd(false);
    mxmlIndexDelete(ind);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  mxmlIndexDelete(ind);

  testBegin("mxmlIndexNew(group, type)");
  if ((ind = mxmlIndexNew(tree, "group", "type")) == NULL)
  {
    testEndMessage(false, "Unable to create index of elements and attributes.");
    goto done;
  }
  else if (mxmlIndexGetCount(ind) != 3)
  {
    testEndMessage(false, "Index of elements and attributes contains %lu nodes; expected 3.", (unsigned long)mxmlIndexGetCount(ind));
    mxmlIndexDelete(ind);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  testBegin("mxmlIndexFind(group, string)");
  mxmlIndexReset(ind);
  if (!mxmlIndexFind(ind, "group", "string"))
  {
    testEnd(false);
    mxmlIndexDelete(ind);
    goto done;
  }
  else
  {
    testEnd(true);
  }

  mxmlIndexDelete(ind);

  // Check the mxmlDelete() works properly...
  testBegin("mxmlDelete");
  for (i = 0; i < 12; i ++)
  {
    if (mxmlGetFirstChild(tree))
    {
      mxmlDelete(mxmlGetFirstChild(tree));
    }
    else
    {
      testEndMessage(false, "Child pointer prematurely NULL on child #%d", i + 1);
      goto done;
    }
  }

  if (mxmlGetFirstChild(tree))
  {
    testEndMessage(false, "Child pointer not NULL after deleting all children.");
    goto done;
  }
  else  if (mxmlGetLastChild(tree))
  {
    testEndMessage(false, "Last child pointer not NULL after deleting all children.");
    goto done;
  }
  else
  {
    testEnd(true);
  }

  ret = 0;

  done:

  mxmlDelete(xml);

  return (ret);
}


//
// 'sax_cb()' - Process nodes via SAX.
//

bool					// O - `true` to continue, `false` to stop
sax_cb(void             *cbdata,	// I - SAX callback data (not used)
       mxml_node_t      *node,		// I - Current node
       mxml_sax_event_t event)		// I - SAX event
{
  static const char * const events[] =	// Events
  {
    "MXML_SAX_EVENT_CDATA",			// CDATA node
    "MXML_SAX_EVENT_COMMENT",			// Comment node
    "MXML_SAX_EVENT_DATA",			// Data node
    "MXML_SAX_EVENT_DECLARATION",		// Declaration node
    "MXML_SAX_EVENT_DIRECTIVE",			// Processing directive node
    "MXML_SAX_EVENT_ELEMENT_CLOSE",		// Element closed
    "MXML_SAX_EVENT_ELEMENT_OPEN"		// Element opened
  };


  (void)cbdata;

  // This SAX callback just counts the different events.
  if (!node)
    fprintf(stderr, "ERROR: SAX callback for event %s has NULL node.\n", events[event]);

  event_counts[event] ++;

  return (true);
}


//
// 'type_cb()' - XML data type callback for mxmlLoadFile()...
//

mxml_type_t				// O - Data type
type_cb(void        *cbdata,		// I - Callback data
        mxml_node_t *node)		// I - Element node
{
  const char	*type;			// Type string


  (void)cbdata;

  // You can lookup attributes and/or use the element name, hierarchy, etc...
  if ((type = mxmlElementGetAttr(node, "type")) == NULL)
  {
    if ((type = mxmlGetElement(node)) == NULL)
      type = "";
  }

  if (!strcmp(type, "integer"))
    return (MXML_TYPE_INTEGER);
  else if (!strcmp(type, "opaque") || !strcmp(type, "pre"))
    return (MXML_TYPE_OPAQUE);
  else if (!strcmp(type, "real"))
    return (MXML_TYPE_REAL);
  else
    return (MXML_TYPE_TEXT);
}


//
// 'whitespace_cb()' - Let the mxmlSaveFile() function know when to insert
//                     newlines and tabs...
//

const char *				// O - Whitespace string or NULL
whitespace_cb(void        *cbdata,	// I - Callback data
              mxml_node_t *node,	// I - Element node
              mxml_ws_t   where)	// I - Open or close tag?
{
  mxml_node_t	*parent;		// Parent node
  int		level;			// Indentation level
  const char	*name;			// Name of element
  static const char *tabs = "\t\t\t\t\t\t\t\t";
					// Tabs for indentation


  (void)cbdata;

  // We can conditionally break to a new line before or after any element.
  // These are just common HTML elements...
  if ((name = mxmlGetElement(node)) == NULL)
    name = "";

  if (!strcmp(name, "html") || !strcmp(name, "head") || !strcmp(name, "body") || !strcmp(name, "pre") || !strcmp(name, "p") || !strcmp(name, "h1") || !strcmp(name, "h2") || !strcmp(name, "h3") || !strcmp(name, "h4") || !strcmp(name, "h5") || !strcmp(name, "h6"))
  {
    // Newlines before open and after close...
    if (where == MXML_WS_BEFORE_OPEN || where == MXML_WS_AFTER_CLOSE)
      return ("\n");
  }
  else if (!strcmp(name, "dl") || !strcmp(name, "ol") || !strcmp(name, "ul"))
  {
    // Put a newline before and after list elements...
    return ("\n");
  }
  else if (!strcmp(name, "dd") || !strcmp(name, "dt") || !strcmp(name, "li"))
  {
    // Put a tab before <li>'s, <dd>'s, and <dt>'s, and a newline after them...
    if (where == MXML_WS_BEFORE_OPEN)
      return ("\t");
    else if (where == MXML_WS_AFTER_CLOSE)
      return ("\n");
  }
  else if (mxmlGetType(node) == MXML_TYPE_DIRECTIVE)
  {
    if (where == MXML_WS_AFTER_OPEN)
      return ("\n");
    else
      return (NULL);
  }
  else if (where == MXML_WS_BEFORE_OPEN || ((!strcmp(name, "choice") || !strcmp(name, "option")) && where == MXML_WS_BEFORE_CLOSE))
  {
    for (level = -1, parent = mxmlGetParent(node); parent; level ++, parent = mxmlGetParent(parent));

    if (level > 8)
      level = 8;
    else if (level < 0)
      level = 0;

    return (tabs + 8 - level);
  }
  else if (where == MXML_WS_AFTER_CLOSE || ((!strcmp(name, "group") || !strcmp(name, "option") || !strcmp(name, "choice")) && where == MXML_WS_AFTER_OPEN))
    return ("\n");
  else if (where == MXML_WS_AFTER_OPEN && !mxmlGetFirstChild(node))
    return ("\n");

  // Return NULL for no added whitespace...
  return (NULL);
}
