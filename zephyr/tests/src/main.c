//
// Zephyr test suite wrapped to testmxml.c.
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

#include <mxml.h>
#include <zephyr/sys/util.h>
#include <zephyr/ztest.h>

extern int testmxml_main(int argc, char *argv[]);

//
// 'test_parse_string()' - Test parsing an XML string. Smoke test.
//

ZTEST(mxml, test_parse_string)
{
  mxml_options_t *options = mxmlOptionsNew(); // Load options
  mxml_node_t *tree;                         // Parsed XML tree
  mxml_node_t *answer;                       // Answer element

  // Parse an integer value from a small XML document...
  zassert_not_null(options, "failed to allocate Mini-XML options");
  mxmlOptionsSetTypeValue(options, MXML_TYPE_INTEGER);

  tree = mxmlLoadString(NULL, options, "<root><answer>42</answer></root>");
  zassert_not_null(tree, "failed to parse XML");

  answer = mxmlFindPath(tree, "answer");
  zassert_not_null(answer, "failed to find parsed element");
  zassert_equal(mxmlGetInteger(answer), 42, "unexpected parsed value");

  mxmlDelete(tree);
  mxmlOptionsDelete(options);
}

//
// 'test_upstream_suite()' - Run the upstream test program.
//

ZTEST(mxml, test_upstream_suite)
{
  static char document[] =                    // XML test document
    "<?xml version=\"1.0\"?>"
    "<root><value type=\"integer\">42</value></root>";
  char *argv[] =                              // Test program arguments
  {
    "testmxml",
    document,
  };

  // Run the upstream tests using the in-memory document
  zassert_equal(testmxml_main(ARRAY_SIZE(argv), argv), 0,
                "upstream testmxml suite failed");
}

ZTEST_SUITE(mxml, NULL, NULL, NULL, NULL, NULL);

