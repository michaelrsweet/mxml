Changes in Mini-XML
===================


v4.0.5 (YYYY-MM-DD)
-------------------

- Added support for the "apos" entity (Issue #346)


v4.0.4 (2025-01-19)
-------------------

- Added Linux-specific build files and dropped unused `long long` config tests
  (Issue #335)
- Documentation fixes (Issue #334, Issue #337)
- Fixed an issue when reporting errors with a `NULL` options pointer
  (Issue #329)
- Fixed some compiler warnings (Issue #333)
- Fixed installation on MingW (Issue #336)


v4.0.3 (2024-04-21)
-------------------

- Now default the `DSOFLAGS` value to `LDFLAGS` in the configure script
  (Issue #325)
- Now install the man page as "mxml4" to allow parallel installation of Mini-XML
  4.x and 3.x (Issue #324)
- Added `MXML_ALLOC_SIZE` define to control the allocation increment for
  attributes and indices (Issue #318)
- Fixed `mxmlSetDeclarationf` implementation (Issue #322)


v4.0.2 (2024-03-24)
-------------------

- Fixed an issue with GNU make and parallel builds (Issue #314)


v4.0.1 (2024-03-22)
-------------------

- Fixed missing "docdir" definition in makefile (Issue #313)
- Fixed missing CPPFLAGS, OPTIM, and WARNINGS in CFLAGS in makefile.
- Fixed configure script issues.


v4.0.0 (2024-03-20)
-------------------

- Now require C99 support (Issue #300)
- Now install as "libmxml4" to support installing both Mini-XML 3.x and 4.x at
  the same time (use `--disable-libmxml4-prefix` configure option to disable)
- Added `mxmlLoadIO` and `mxmlSaveIO` functions to load and save XML via
  callbacks (Issue #98)
- Added new `MXML_TYPE_CDATA`, `MXML_TYPE_COMMENT`, `MXML_TYPE_DECLARATION`, and
  `MXML_TYPE_DIRECTIVE` node types (Issue #250)
- Added `mxmlLoadFilename` and `mxmlSaveFilename` functions (Issue #291)
- Added AFL fuzzing support (Issue #306)
- Added `mxmlOptions` APIs to replace the long list of callbacks and options for
  each of the load and save functions (Issue #312)
- Added string copy/free callbacks to support alternate memory management of
  strings.
- Renamed `mxml_type_t` enumerations to `MXML_TYPE_xxx` (Issue #251)
- Updated APIs to use bool type instead of an int representing a boolean value.
- Updated the SAX callback to return a `bool` value to control processing
  (Issue #51)
- Updated the load and save callbacks to include a context pointer (Issue #106)
- Fixed some warnings (Issue #301)
- Fixed real number support in non-English locales (Issue #311)
