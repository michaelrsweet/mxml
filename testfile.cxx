//
// "$Id: testfile.cxx,v 1.1 2004/04/29 12:38:39 mike Exp $"
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


//
// End of "$Id: testfile.cxx,v 1.1 2004/04/29 12:38:39 mike Exp $".
//
