# Changes in Mini-XML 3.3.2

- Updated the autoconf `config.guess` and `config.sub` scripts to support cross
  compilation for newer platforms (Issue #296)


# Changes in Mini-XML 3.3.1

- Fixed POSIX thread cleanup bugs (Issue #293)


# Changes in Mini-XML 3.3

- Cleaned up usage of `free` throughout the library (Issue #276)
- Added more error handling to the library (Issue #277)
- Fixed potential memory leak in `mxmlLoad*` functions (Issue #278, Issue #279)
- Fixed `mxmlSaveString` with a buffer size of 0 (Issue #284)
- Fixed `MXML_MINOR_VERSION` value in "mxml.h" (Issue #285)
- Fixed POSIX threading support for MingW (Issue #287)
- Fixed some minor memory leaks found by Coverity.


# Changes in Mini-XML 3.2

- Added support for shared libraries on Haiku (Issue #262)
- Fixed handling of unquoted attribute values that start with a Unicode
  character (Issue #264)
- Fixed handling of elements that start with a Unicode character (Issue #267)
- Fixed some minor issues identified by the LGTM security scanner.


# Changes in Mini-XML 3.1

- The `mxmlLoad*` functions now print an error when the XML does not start with
  `<` and no parent node is supplied (Issue #256, Issue #259)
- Fixed an issue with "make install" trying to install old files (Issue #257)
- Fixed some DSO installation issues on Linux.


# Changes in Mini-XML 3.0

- Changed the license to Apache 2.0 with exceptions (Issue #239)
- All of the internal node structures are now moved out of the public header
  (Issue #240)
- Fixed a potential buffer overflow when writing floating point data
  (Issue #233)
- Moved `mxmldoc` to a new `codedoc` project whose focus is on generating
  code documentation (Issue #235, Issue #236, Issue #237)
- Error messages now include the line number of the error (Issue #230)
- The `mxmlSetCDATA`, `mxmlSetElement`, `mxmlSetOpaque`, `mxmlSetOpaquef`,
  `mxmlSetText`, and `mxmlSetTextf` functions caused a use-after-free bug if
  the value came from the same node (Issue #241)
- The `mxmlSetOpaquef` and `mxmlSetTextf` functions did not work (Issue #244)
- The `_mxml_strdupf` function did not work on Windows (Issue #245)


# Changes in Mini-XML 2.12

- Added yet more documentation about using `MXML_OPAQUE_CALLBACK` when you want
  to get full strings for inline text instead of separated words (Issue #190)
- No longer build documentation sets on macOS since Xcode no longer supports
  them (Issue #198)
- Updated the `va_copy` macro for use with BCC (Issue #211)
- The `mxmlNewCDATA` and `mxmlSetCDATA` functions incorrectly added the XML
  trailer "]]" to the string (Issue #216)
- Cross-compiling failed on install (Issue #218)
- Fixed a crash bug in the `mxmlWrite` functions (Issue #228)
- The `mxmlWrite` functions no longer write the siblings of the passed node
  (Issue #228)
- Updated the markdown and ZIP container libraries used for mxmldoc.


# Changes in Mini-XML 2.11

- CDATA nodes now omit the trailing "]]" for convenience (Issue #170)
- Fixed a memory leak in mxmlDelete (Issue #183)
- `mxmlElementSetAttrf` did not work with some versions of Visual Studio
  (Issue #184)
- Added `mxmlElementGetAttrByIndex` and `mxmlELementGetAttrCount` functions
  (Issue #185)
- The configure script now properly supports cross-compilation (Issue #188)
- The mxmldoc utility now supports generation of EPUB files (Issue #189)
- The mxmldoc utility now supports the `SOURCE_DATE_EPOCH` environment
  variable for reproducible builds (Issue #193)
- The mxmldoc utility now supports Markdown (Issue #194)
- Fixed writing of custom data values (Issue #201)
- Added `mxmlNewOpaquef` and `mxmlSetOpaquef` functions to add and set formatted
  opaque string values.
- The mxmldoc utility scanned and loaded descriptive text differently, causing
  the detailed descriptions ("discussion") to be lost in generated
  documentation.
- The mxmldoc utility now supports `@exclude format@` comments to exclude
  documentation based on the output format.  The format string can be `all` to
  exclude documentation for all formats or a comma-delimited list such as
  `@exclude man,html@`.


# Changes in Mini-XML 2.10

- The version number in mxml.h was wrong.
- The mxml.spec file was out of date.
- Mini-XML no longer allows malformed element names.
- `mxmlLoad*` and `mxmlSAXLoad*` did not properly create text nodes when
  MXML_TEXT_CALLBACK was specified.
- `mxmlDelete` used a recursive algorithm which could require large amounts of
  stack space depending on the file. (CVE-2016-4570)
- `mxmlWrite*` used a recursive algorithm which could require large amounts of
  stack space depending on the file. (CVE-2016-4571)


# Changes in Mini-XML 2.9

- `mxmlLoad*` did not correctly load value nodes with `MXML_NO_CALLBACK` or
  `MXML_TEXT_CALLBACK`.


# Changes in Mini-XML 2.8

- Now call docsetutil using xcrun on macOS.
- mxmldoc did not escape special HTML characters inside @code foo@ comments.
- Fixed a memory leak in `mxmlElementDeleteAttr`.
- Added `MXML_MAJOR/MINOR_VERSION` definitions to mxml.h.
- Fixed a bug reading UTF-16 characters from a file.
- Fixed a memory leak when loading invalid XML.
- Fixed an XML fragment loading problem.


# Changes in Mini-XML 2.7

- Added 64-bit configurations to the VC++ project files.
- Fixed conformance of mxmldoc's HTML and CSS output.
- Added data accessor ("get") functions and made the `mxml_node_t` and
  `mxml_index_t` structures private but still available in the Mini-XML header to
  preserve source compatibility.
- Updated the source headers to reference the Mini-XML license and its
  exceptions to the LGPL2.
- Fixed a memory leak when loading a badly-formed XML file.
- Added a new mxmlFindPath function to find the value node of a named element.
- Building a static version of the library did not work on Windows.
- The shared library did not include a destructor for the thread-specific data
  key on UNIX-based operating systems.
- mxmlLoad* did not error out on XML with multiple root nodes.
- Fixed an issue with the `_mxml_vstrdupf` function.
- `mxmlSave*` no longer write all siblings of the passed node, just that node
  and its children.


# Changes in Mini-XML 2.6

- Documentation fixes.
- The mxmldoc program did not handle typedef comments properly.
- Added support for "long long" printf formats.
- The XML parser now ignores BOMs in UTF-8 XML files.
- The mxmldoc program now supports generating Xcode documentation sets.
- `mxmlSave*` did not output UTF-8 correctly on some platforms.
- `mxmlNewXML` now adds encoding="utf-8" in the ?xml directive to avoid
  problems with non-conformant XML parsers that assume something other
  than UTF-8 as the default encoding.
- Wrapping was not disabled when mxmlSetWrapMargin(0) was called, and
  "<?xml ... ?>" was always followed by a newline.
- The mxml.pc.in file was broken.
- The mxmldoc program now handles "typedef enum name {} name" correctly.


# Changes in Mini-XML 2.5

- The mxmldoc program now makes greater use of CSS and supports a `--css` option
  to embed an alternate stylesheet.
- The mxmldoc program now supports `--header` and `--footer` options to insert
  documentation content before and after the generated content.
- The mxmldoc program now supports a `--framed` option to generate framed HTML
  output.
- The mxmldoc program now creates a table of contents including any headings in
  the `--intro` file when generating HTML output.
- The man pages and man page output from mxmldoc did not use "\-" for dashes.
- The debug version of the Mini-XML DLL could not be built.
- Processing instructions and directives did not work when not at the top level
  of a document.
- Spaces around the "=" in attributes were not supported.


# Changes in Mini-XML 2.4

- Fixed shared library build problems on HP-UX and Mac macOS.
- The mxmldoc program did not output argument descriptions for functions
  properly.
- All global settings (custom, error, and entity callbacks and the wrap margin)
  are now managed separately for each thread.
- Added `mxmlElementDeleteAttr` function.
- `mxmlElementSetAttrf` did not work.
- `mxmlLoad*` incorrectly treated declarations as parent elements.
- `mxmlLoad*` incorrectly allowed attributes without values.
- Fixed Visual C++ build problems.
- `mxmlLoad*` did not return NULL when an element contained an error.
- Added support for the apos character entity.
- Fixed whitespace detection with Unicode characters.
- `mxmlWalkNext` and `mxmlWalkPrev` did not work correctly when called with a
  node with no children as the top node.


# Changes in Mini-XML 2.3

- Added two exceptions to the LGPL to support static linking of applications
  against Mini-XML.
- The mxmldoc utility can now generate man pages, too.
- Added a mxmlNewXML function.
- Added a mxmlElementSetAttrf function.
- Added snprintf() emulation function for test program.
- Added the _CRT_SECURE_NO_DEPRECATE definition when building on VC++ 2005.
- mxmlLoad* did not detect missing > characters in elements.
- mxmlLoad* did not detect missing close tags at the end of an XML document.
- Added user_data and ref_count members to mxml_node_t structure.
- Added mxmlReleaseNode() and mxmlRetainNode() APIs for reference-counted nodes.
- Added mxmlSetWrapMargin() to control the wrapping of XML output.
- Added conditional check for EINTR error code for certain Windows compilers
  that do not define it.
- The mxmldoc program now generates correct HTML 4.0 output - previously it
  generated invalid XHTML.
- The mxmldoc program now supports "@deprecated@, "@private@", and "@since
  version@" comments.
- Fixed function and enumeration type bugs in mxmldoc.
- Fixed the XML schema for mxmldoc.
- The mxmldoc program now supports `--intro`, `--section`, and `--title`
  options.
- The `mxmlLoad*` functions could leak a node on an error.
- The `mxml_vsnprintf` function could get in an infinite loop on a buffer
  overflow.
- Added new `mxmlNewCDATA` and `mxmlSetCDATA` functions to create and set CDATA
  nodes, which are really just special element nodes.
- Added new `MXML_IGNORE` type and `MXML_IGNORE_CB` callback to ignore non-
  element nodes, e.g. whitespace.
- `mxmlLoad*` crashed when reporting an error in some invalid XML.


# Changes in Mini-XML 2.2.2

- `mxmlLoad*` did not treat custom data as opaque, so whitespace characters
  would be lost.


# Changes in Mini-XML 2.2.1

- `mxmlLoad*` now correctly return NULL on error.
- `mxmlNewInteger`, `mxmlNewOpaque`, `mxmlNewReal`, `mxmlNewText`, and
  `mxmlNewTextf` incorrectly required a parent node.
- Fixed an XML output bug in mxmldoc.
- The "make install" target now uses the install command to set the proper
  permissions on UNIX/Linux/macOS.
- Fixed a MingW/Cygwin compilation problem.


# Changes in Mini-XML 2.2

- Added shared library support.
- `mxmlLoad*` now return an error when an XML stream contains illegal control
  characters.
- `mxmlLoad*` now return an error when an element contains two attributes with
  the same name in conformance with the XML spec.
- Added support for CDATA.
- Updated comment and processing instruction handling - no entity support per
  XML specification.
- Added checking for invalid comment termination: "--->" is not allowed.


# Changes in Mini-XML 2.1

- Added support for custom data nodes.
- Now treat UTF-8 sequences which are longer than necessary as an error.
- Fixed entity number support.
- Fixed mxmlLoadString() bug with UTF-8.
- Fixed entity lookup bug.
- Added `mxmlLoadFd` and `mxmlSaveFd` functions.
- Fixed multi-word UTF-16 handling.


# Changes in Mini-XML 2.0

- New programmers manual.
- Added Visual C++ project files for Microsoft Windows users.
- Added optimizations to mxmldoc, `mxmlSaveFile`, and `mxmlIndexNew`.
- `mxmlEntityAddCallback` now returns an integer status.
- Added UTF-16 support (input only; all output is UTF-8).
- Added index functions to build a searchable index of XML nodes.
- Added character entity callback interface to support additional character
  entities beyond those defined in the XHTML specification.
- Added support for XHTML character entities.
- The mxmldoc utility now produces XML output which conforms to an updated XML
  schema, described in the file "doc/mxmldoc.xsd".
- Changed the whitespace callback interface to return strings instead of a
  single character, allowing for greater control over the formatting of XML
  files written using Mini-XML. THIS CHANGE WILL REQUIRE CHANGES TO YOUR 1.x
  CODE IF YOU USE WHITESPACE CALLBACKS.
- The mxmldoc utility is now capable of documenting C++ classes, functions, and
  structures, and correctly handles C++ comments.
- Added new modular tests for mxmldoc.
- Updated the mxmldoc output to be more compatible with embedding in manuals
  produced with HTMLDOC.
- The makefile incorrectly included a "/" separator between the destination path
  and install path. This caused problems when building and installing with
  MingW.
