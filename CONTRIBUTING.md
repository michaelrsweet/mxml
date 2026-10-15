Contributing to Mini-XML
========================

Mini-XML is developed and distributed as open source software under the Apache
License, Version 2.0.  Contributions should be submitted as pull requests on
the Github site:

    http://github.com/michaelrsweet/mxml/pulls


Contents
--------

- [Build System](#build-system)
- [Version Numbering](#version-numbering)
- [Coding Guidelines](#coding-guidelines)
  - [Using Coding AI Tools](#using-ai-coding-tools)
  - [Source Files](#source-files)
  - [Header Files](#header-files)
  - [Comments](#comments)
  - [Indentation](#indentation)
  - [Spacing](#spacing)
  - [Return Values](#return-values)
  - [Functions](#functions)
  - [Variables](#variables)
  - [Types](#types)
  - [Structures](#structures)
  - [Constants](#constants)
- [Shell Script Guidelines](#shell-script-guidelines)
- [Makefile Guidelines](#makefile-guidelines)
  - [General Organization](#general-organization)
  - [Makefile Documentation](#makefile-documentation)
  - [Portable Makefile Construction](#portable-makefile-construction)
  - [Standard Variables](#standard-variables)
  - [Standard Targets](#standard-targets)
  - [Object Files](#object-files)
  - [Programs](#programs)
  - [Static Libraries](#static-libraries)
  - [Shared Libraries](#shared-libraries)
  - [Dependencies](#dependencies)
  - [Install/Uninstall Support](#installuninstall-support)


Build System
------------

The build system uses GNU autoconf to tailor the library to the local operating
system.  Visual Studio and Xcode projects are also provided in the "vcnet" and
"xcode" directories for Windows and macOS, respectively.  To improve
portability, makefiles *must not* make use of features unique to GNU make.  See
the [Makefile Guidelines](#makefile-guidelines) section for a description of the
allowed make features and makefile guidelines.

Additional GNU build programs such as GNU automake and GNU libtool *must not* be
used.  GNU automake produces non-portable makefiles which depend on GNU-specific
extensions, and GNU libtool is not portable or reliable enough for Mini-XML.


Version Numbering
-----------------

Mini-XML uses a three-part version number separated by periods to represent the
major, minor, and patch release numbers.  Major release numbers indicate large
design changes or backwards-incompatible changes to the library.  Minor release
numbers indicate new features and other smaller changes which are backwards-
compatible with previous releases.  Patch numbers indicate bug fixes to the
previous feature or patch release.

> Note:
>
> When we talk about compatibility, we are talking about binary compatibility
> for public APIs and output format compatibility for program interfaces.
> Changes to configuration file formats or the default behavior of programs
> are not generally considered incompatible as the upgrade process can
> normally address such changes gracefully.

Production releases use the plain version numbers:

    MAJOR.MINOR.PATCH
    1.0.0
    1.0.1
    1.0.2
    ...
    1.1.0
    ...
    2.0.0

The first production release in a MAJOR.MINOR series (MAJOR.MINOR.0) is called
a feature release.  Feature releases are the only releases that may contain new
features.  Subsequent production releases in a MAJOR.MINOR series may only
contain bug fixes.

Beta-test releases are identified by appending the letter B to the major and
minor version numbers followed by the beta release number:

    MAJOR.MINORbNUMBER
    1.0b1

Release candidates are identified by appending the letters RC to the major and
minor version numbers followed by the release candidate number:

    MAJOR.MINORrcNUMBER
    1.0rc1


Coding Guidelines
-----------------

Contributed source code must follow the guidelines below.  While the examples
are for C source files, source code for other languages should conform to the
same guidelines as allowed by the language.

Mini-XML requires at least C99 support for all C code.  Aside from adding the
`bool` type and `//` comments, C99 also clarifies the `NULL` behavior of certain
standard functions such as `free` and `realloc`.


### Using AI Coding Tools

The use of AI coding tools must be disclosed when you submit an issue or pull
request.  In the interest of standardization, please include the Linux Kernel
["Assisted-by"](https://docs.kernel.org/process/coding-assistants.html) tag in
the description:

    Assisted-by: AGENT_NAME:MODEL_VERSION [TOOL1] [TOOL2]

> *Note:* Purely AI-generated code cannot be accepted for legal and ethical
> reasons.  AI code contributions must be manually reviewed and submitted by
> a person with the legal capacity to provide such code under the terms of the
> Mini-XML license.


### Source Files

All source files names must be 16 characters or less in length to ensure
compatibility with older UNIX filesystems.  Source files containing functions
have an extension of ".c" for C files and ".m" for Objective C files.  All
"include" files have an extension of ".h".  Tabs are set to 8 characters or
columns.

The top of each source file contains a header giving the purpose or nature of
the source file and the copyright and licensing notice:

    //
    // Description of file contents.
    //
    // Copyright YYYY by AUTHOR.
    //
    // Licensed under Apache License v2.0.  See the file "LICENSE" for more
    // information.
    //


### Header Files

Private API header files must be named with the suffix "-private", for example
the "mxml.h" header file defines all of the public APIs while the
"mxml-private.h" header file defines all of the private APIs.  Typically a
private API header file will include the corresponding public API header file.


### Comments

All source code utilizes block comments within functions to describe the
operations being performed by a group of statements; avoid putting a comment
per line unless absolutely necessary, and then consider refactoring the code
so that it is not necessary.  C source files use the C99 comment format
("// comment"):

    // Clear the state array before we begin...
    for (i = 0; i < (sizeof(array) / sizeof(sizeof(array[0])); i ++)
      array[i] = Mini-XML_STATE_IDLE;

    // Wait for state changes on another thread...
    do
    {
      for (i = 0; i < (sizeof(array) / sizeof(sizeof(array[0])); i ++)
        if (array[i] != Mini-XML_STATE_IDLE)
          break;

      if (i == (sizeof(array) / sizeof(array[0])))
        sleep(1);
    } while (i == (sizeof(array) / sizeof(array[0])));


### Indentation

All code blocks enclosed by brackets begin with the opening brace on a new
line.  The code then follows starting on a new line after the brace and is
indented 2 spaces.  The closing brace is then placed on a new line following
the code at the original indentation:

    {
      int i; // Looping var

      // Process foobar values from 0 to 999...
      for (i = 0; i < 1000; i ++)
      {
        do_this(i);
        do_that(i);
      }
    }

Single-line statements following "do", "else", "for", "if", and "while" are
indented 2 spaces as well.  Blocks of code in a "switch" block are indented 4
spaces after each "case" and "default" case:

    switch (array[i])
    {
      case Mini-XML_STATE_IDLE :
          do_this(i);
          do_that(i);
          break;

      default :
          do_nothing(i);
          break;
    }


### Spacing

A space follows each reserved word such as `if`, `while`, etc.  Spaces are not
inserted between a function name and the arguments in parenthesis.


### Return Values

Parenthesis surround values returned from a function:

    return (Mini-XML_STATE_IDLE);


### Functions

Functions with a global scope have a lowercase prefix followed by capitalized
words, e.g., `mxmlDoThis`, `mxmlDoThat`, `mxmlDoSomethingElse`, etc.  Private
global functions begin with a leading underscore, e.g., `_mxmlDoThis`,
`_mxmlDoThat`, etc.

Functions with a local scope are declared static with lowercase names and
underscores between words, e.g., `do_this`, `do_that`, `do_something_else`, etc.

Function names follow the following pattern:

- "mxmlFooCreate" to create a Foo object,
- "mxmlFooDelete" to destroy (free) a Foo object,
- "mxmlFooGetBar" to get data element Bar from object Foo,
- "mxmlFooIsBar" to test condition Bar for object Foo, and
- "mxmlFooSetBar" to set data element Bar in object Foo.
- "mxmlFooVerb" to take an action with object Foo.

Each function begins with a comment header describing what the function does,
the possible input limits (if any), the possible output values (if any), and
any special information needed:

    //
    // 'mxmlDoThis()' - Short description of function.
    //
    // Longer documentation for function with examples using a subset of
    // markdown.  This is a bulleted list:
    //
    // - One fish
    // - Two fish
    // - Red fish
    // - Blue fish
    //
    // > *Note:* Special notes for developer should be markdown block quotes.
    //

    float                  // O - Inverse power value, 0.0 <= y <= 1.1
    mxmlDoThis(float x)   // I - Power value (0.0 <= x <= 1.1)
    {
      ...
      return (y);
    }

Return/output values are indicated using an "O" prefix, input values are
indicated using the "I" prefix, and values that are both input and output use
the "IO" prefix for the corresponding in-line comment.

The [`codedoc` documentation generator][1] also understands the following
special text in the function description comment:

    @deprecated@         - Marks the function as deprecated (not recommended
                           for new development and scheduled for removal)
    @since version@      - Marks the function as new in the specified version.
    @private@            - Marks the function as private (same as starting the
                           function name with an underscore)

[1]: https://www.msweet.org/codedoc


### Variables

Variables with a global scope are capitalized, e.g., `ThisVariable`,
`ThatVariable`, `ThisStateVariable`, etc.  Globals *must not* be used in the
Mini-XML library.

Variables with a local scope are lowercase with underscores between words,
e.g., `this_variable`, `that_variable`, etc.  Any "local global" variables
shared by functions within a source file are declared static.

Each variable is declared on a separate line and is immediately followed by a
comment block describing the variable:

    int         ThisVariable;    // The current state of this
    static int  that_variable;   // The current state of that


### Types

All type names are lowercase with underscores between words and `_t` appended
to the end of the name, e.g., `mxml_this_type_t`, `mxml_that_type_t`, etc.
Type names start with the "mxml\_" prefix to avoid conflicts with system types.
Private type names start with an underscore, e.g., `_mxml_this_t`,
`_mxml_that_t`, etc.

Each type has a comment block immediately after the typedef:

    typedef int mxml_this_type_t;  // This type is for foobar options.


### Structures

All structure names are lowercase with underscores between words and `_s`
appended to the end of the name, e.g., `mxml_this_s`, `mxml_that_s`, etc.
Structure names start with the "mxml\_" prefix to avoid conflicts with system
types.  Private structure names start with an underscore, e.g., `_mxml_this_s`,
`_mxml_that_s`, etc.

Each structure has a comment block immediately after the struct and each member
is documented similar to the variable naming policy above:

    struct mxml_this_struct_s // This structure is for foobar options.
    {
      int this_member;         // Current state for this
      int that_member;         // Current state for that
    };

One common design pattern is to define a private structure with a public
typedef, for example:

    // In public header
    typedef struct _mxml_foo_s mxml_foo_t // Foo object

    // In private header
    struct _mxml_foo_s        // Foo object
    {
      int this_member;         // Current state for this
      int that_member;         // Current state for that
    };


### Constants

All constant names are uppercase with underscores between words, e.g.,
`Mini-XML_THIS_CONSTANT`, `Mini-XML_THAT_CONSTANT`, etc.  Constants begin with the
"Mini-XML\_" prefix to avoid conflicts with system constants.  Private constants
start with an underscore, e.g., `_Mini-XML_THIS_CONSTANT`,
`_Mini-XML_THAT_CONSTANT`, etc.

Typed enumerations should be used whenever possible to allow for type checking
by the compiler.  The constants for typed enumerations must match the type name
in uppercase, for example a `mxml_foo_e` enumeration has constant names
starting with `Mini-XML_FOO_`.

Comment blocks immediately follow each constant:

    typedef enum mxml_tray_e  // Tray enumerations
    {
      Mini-XML_TRAY_THIS,         // This tray
      Mini-XML_TRAY_THAT          // That tray
    } mxml_tray_t;


Shell Script Guidelines
-----------------------

All shell scripts in Mini-XML must conform to the [POSIX shell][POSIX-SHELL]
command language and should restrict their dependence on non-POSIX utility
commands.

[POSIX-SHELL]: https://pubs.opengroup.org/onlinepubs/9699919799/utilities/V3_chap02.html#tag_18


Makefile Guidelines
-------------------

The following is a guide to the [POSIX makefile-based][POSIX-MAKE] build system.
These standards have been developed over the years to allow the Mini-XML to be
built on as many systems and environments as possible.

[POSIX-MAKE]: https://pubs.opengroup.org/onlinepubs/9699919799/utilities/make.html


### General Organization

The source code is organized functionally into a top-level makefile, include
file, and subdirectories each with their own makefile and dependencies files.
The ".in" files are template files for the autoconf software and are used to
generate a static version of the corresponding file.


### Makefile Documentation

Each makefile starts with the standard header containing the description
of the file, and Mini-XML copyright and license notice:

    #
    # Makefile for ...
    #
    # Copyright YYYY by AUTHOR.
    #
    # Licensed under Apache License v2.0.  See the file "LICENSE" for more
    # information.
    #


### Portable Makefile Construction

We use a common subset of make program syntax to ensure that the software can be
compiled "out of the box" on as many systems as possible.  The following is a
list of assumptions we follow when constructing makefiles:

- Targets; we assume that the make program supports the notion of simple
  targets of the form "name:" that perform tab-indented commands that follow
  the target, e.g.:

      target:
      TAB target commands

- Dependencies; we assume that the make program supports recursive dependencies
  on targets, e.g.:

      target: foo bar
      TAB target commands

      foo: bla
      TAB foo commands

      bar:
      TAB bar commands

      bla:
      TAB bla commands

- Variable Definition; we assume that the make program supports variable
  definition on the command-line or in the makefile using the following form:

      name=value

- Variable Substitution; we assume that the make program supports variable
  substitution using the following forms:

      - `$(name)`; substitutes the value of "name",
      - `$(name:.old=.new)`; substitutes the value of "name" with the filename
        extension ".old" changed to ".new",
      - `$(MAKEFLAGS)`; substitutes the command-line options passed to the
        program without the leading hyphen (-),
      - `$$`; substitutes a single $ character,
      - `$<`; substitutes the current source file or dependency, and
      - `$@`; substitutes the current target name.

- Suffixes; we assume that the make program supports filename suffixes with
  assumed dependencies, e.g.:

      .SUFFIXES: .c .o
      .c.o:
      TAB $(CC) $(CFLAGS) -o $@ -c $<

- Include Files; we assume that the make program supports POSIX include lines,
  e.g.:

      include ../Makedefs
      include Dependencies

- Comments; we assume that comments begin with a # character and proceed to the
  end of the current line.

- Line Length; we assume that there is no practical limit to the length of
  lines.

- Continuation of long lines; we assume that the `\` character may be placed at
  the end of a line to concatenate two or more lines in a makefile to form a
  single long line.

- Shell; we assume a POSIX-compatible shell is present on the build system.


### Standard Variables

The following variables are defined in the "Makedefs" file generated by the
autoconf software:

- `AR`; the static library archiver command,
- `ARFLAGS`; options for the static library archiver command,
- `BUILDROOT`; optional installation prefix (defaults to `DESTDIR`, `DSTROOT`,
   or `RPM_BUILD_ROOT`, as defined in the environment),
- `CC`; the C compiler command,
- `CFLAGS`; options for the C compiler command,
- `CODE_SIGN`: the code signing utility,
- `CODESIGN_IDENTITY`: the code signing identity,
- `CPPFLAGS`; options for the C preprocessor,
- `CSFLAGS`; options for the code signing utility,
- `DSOFLAGS`; options for the shared library building command,
- `INSTALL`; the install command,
- `LIBMini-XML`; the name of the Mini-XML library file,
- `LIBMini-XML_STATIC`; the name of the Mini-XML static library file (if different
  from `LIBMini-XML`),
- `LDFLAGS`; options for the linker,
- `LIBS`; libraries for all programs,
- `LN`; the ln command,
- `MKDIR`; the mkdir command,
- `OPTIM`; common compiler optimization options,
- `Mini-XML_VERSION`; the full Mini-XML version number,
- `Mini-XML_VERSION_MAJOR`; the major number from the Mini-XML version,
- `Mini-XML_VERSION_MINOR`; the minor number from the Mini-XML version,
- `RANLIB`; the static library indexing command,
- `RM`; the rm command,
- `RMDIR`; the rmdir command,
- `SHELL`; the sh (POSIX shell) command,
- `SYSTEM_STATUS`; the native system status UI plug-in (if any),
- `WARNINGS`; common compiler warning options,
- `bindir`; the user command installation directory,
- `datadir`; the data file installation directory,
- `datarootdir`; the root data file installation directory,
- `exec_prefix`; the executable installation prefix directory,
- `includedir`; the public header file installation directory,
- `libdir`; the library installation directory,
- `libexecdir`; the library executables installation directory,
- `localestatedir`; the local state directory,
- `mandir`; the man page installation directory,
- `oldincludedir`; a historical variable required for autoconf,
- `prefix`; the installation prefix directory,
- `sbindir`; the administrative command installation directory,
- `sharedstatedir`; the shared state directory,
- `srcdir`; the source directory,
- `sysconfdir`; the system configuration directory, and
- `top_srcdir`; the top-level source directory.


### Standard Targets

The following standard targets are defined in each makefile:

- `all`; creates all target programs, libraries, and documentation files,
- `clean`; removes all target programs libraries, documentation files, and
  object files,
- `depend`; generates automatic dependencies for any C source files (also
  see "DEPENDENCIES"),
- `distclean`; removes autoconf-generated files in addition to those removed by
  the "clean" target,
- `install`; installs all distribution files in their corresponding locations
  (also see "INSTALL/UNINSTALL SUPPORT"),
- `test`; performs all unit and combined tests, and
- `uninstall`; removes all distribution files from their corresponding locations
  (also see "INSTALL/UNINSTALL SUPPORT").


### Object Files

Object files (the result of compiling a C source file) have the extension ".o".


### Programs

Program files are the result of linking object files and libraries together to
form an executable file.  A typical program target looks like:

    program: $(OBJS)
    TAB echo Linking $@...
    TAB $(CC) $(LDFLAGS) -o $@ $(OBJS) $(LIBS)
    TAB $(CODE_SIGN) $(CSFLAGS) -i org.msweet.mxml.$@ $@

### Static Libraries

Static libraries have a prefix of "lib" and the extension ".a". A typical
static library target looks like:

    libname.a: $(OBJECTS)
    TAB echo Creating $@...
    TAB $(RM) $@
    TAB $(AR) $(ARFLAGS) $@ $(OBJECTS)
    TAB $(RANLIB) $@


### Shared Libraries

Shared libraries have a prefix of "lib" and the extension ".dylib" or ".so"
depending on the operating system.  A typical shared library is composed of
several targets that look like:

    libname.so.1: $(OBJECTS)
    TAB echo $(CC) $(DSOFLAGS) -o $@ ...
    TAB $(CC) $(DSOFLAGS) -o $@ $(OBJECTS)
    TAB $(RM) `basename $@ .1`
    TAB $(LN) $@ `basename $@ .1`

    libname.1.dylib: $(OBJECTS)
    TAB echo $(CC) $(DSOFLAGS) -o $@ ...
    TAB $(CC) $(DSOFLAGS) -o $@ \
    TAB TAB -install_name $(libdir)/$@ \
    TAB TAB -current_version 1.0.0 \
    TAB TAB -compatibility_version 1 \
    TAB TAB $(OBJECTS) $(LIBS)
    TAB $(CODE_SIGN) $(CSFLAGS) -i org.msweet.mxml.`basename $@ .1.dylib` $@
    TAB $(RM) `basename $@ .1.dylib`.dylib
    TAB $(LN) $@ `basename $@ .1.dylib`.dylib


### Dependencies

Static dependencies are expressed in each makefile following the target, for
example:

    foo: bar

Static dependencies are only used when it is not possible to automatically
generate them.  Automatic dependencies are stored in a file named
"Dependencies" and included at the end of the makefile.  The following "depend"
target rule is used to create the automatic dependencies:

    depend:
    TAB $(CC) -MM $(CFLAGS) $(OBJS:.o=.c) | \
    TAB TAB sed -e '1,$$s/ \/usr\/include\/[^ ]*//g' \
    TAB TAB -e '1,$$s/ \/usr\/local\/include\/[^ ]*//g' >Dependencies


### Install/Uninstall Support

All makefiles contains install and uninstall rules which install or remove the
corresponding software.  These rules must use the $(BUILDROOT) variable as a
prefix to any installation directory so that Mini-XML can be installed in a
temporary location for packaging by programs like `rpmbuild`.

The `$(RANLIB)` command must be run on any static libraries after installation
since the symbol table is invalidated when the library is copied on some
platforms.
