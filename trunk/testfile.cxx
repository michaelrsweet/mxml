//
// "$Id: testfile.cxx,v 1.2 2004/04/29 20:48:52 mike Exp $"
//
// Mxmldoc test file for mini-XML, a small XML-like file parsing library.
//
// Copyright 2003-2004 by Michael Sweet.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Library General Public
// License as published by the Free Software Foundation; either
// version 2, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//


typedef enum foo_enum_e			/* Sample enumeration type */
{
  FOO_ONE,				/* One fish */
  FOO_TWO,				/* Two fish */
  FOO_RED,				/* Red fish */
  FOO_BLUE				/* Blue fish */
} foo_enum_t;


/*
 * 'foo_void_function()' - Do foo with bar.
 */

void
foo_void_function(int        one,	/* I - Integer */
                  float      *two,	/* O - Real number */
                  const char *three)	/* I - String */
{
  if (one)
  {
    puts("Hello, World!");
  }
  else
    puts(three);

  *two = 2.0f;
}


/*
 * 'foo_float_function()' - Do foo with bar.
 */

float					/* O - Real number */
foo_float_function(int        one,	/* I - Integer */
                   const char *two)	/* I - String */
{
  if (one)
  {
    puts("Hello, World!");
  }
  else
    puts(two);

  return (2.0f);
}


typedef struct foo_s			/* Foo structure */
{
  float	foo;				/* Real number */
  int	bar;				/* Integer */

  foo_s(float f, int b);
  ~foo_s();

  // 'get_bar()' - Get the value of bar.
  int // O - Value of bar
  get_bar()
  {
    return (bar);
  }

  // 'get_foo()' - Get the value of foo.
  float // O - Value of foo
  get_foo()
  {
    return (foo);
  }

  // 'set_bar()' - Set the value of bar.
  void
  set_bar(int b) // I - Value of bar
  {
    bar = b;
  }

  // 'set_foo()' - Set the value of foo.
  void
  set_foo(float f) // I - Value of foo
  {
    foo = f;
  }
} foo_t;

// 'foo_s::foo_s()' - Create a foo_s structure.
foo_s::foo_s(float f, int b)
{
  foo = f;
  bar = b;
}

// 'foo_s::~foo_s()' - Destroy a foo_s structure.
foo_s::~foo_s()
{
}


class foo_c			// Foo class
{
  float	foo;				/* Real number */
  int	bar;				/* Integer */

  public:

  foo_c(float f, int b);
  ~foo_c();

  // 'get_bar()' - Get the value of bar.
  int // O - Value of bar
  get_bar()
  {
    return (bar);
  }

  // 'get_foo()' - Get the value of foo.
  float // O - Value of foo
  get_foo()
  {
    return (foo);
  }

  // 'set_bar()' - Set the value of bar.
  void
  set_bar(int b) // I - Value of bar
  {
    bar = b;
  }

  // 'set_foo()' - Set the value of foo.
  void
  set_foo(float f) // I - Value of foo
  {
    foo = f;
  }
}

// 'foo_c::foo_c()' - Create a foo_c class.
foo_c::foo_c(float f, int b)
{
  foo = f;
  bar = b;
}

// 'foo_c::~foo_c()' - Destroy a foo_c class.
foo_c::~foo_c()
{
}

//
// End of "$Id: testfile.cxx,v 1.2 2004/04/29 20:48:52 mike Exp $".
//
