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


/*
 * 'foo_default_string()' - Do something with a defaulted string arg.
 */

int					/* O - Integer value */
foo_default_string(int one,		/* I - Integer */
                   const char *two = "2")
					/* I - String */
{
  if (one)
  {
    puts("Hello, World!");
  }
  else
    puts(two);

  return (2);
}


/*
 * 'foo_default_int()' - Do something with a defaulted int arg.
 */

int					/* O - Integer value */
foo_default_int(int one,		/* I - Integer */
                int two = 2)		/* I - Integer */
{
  if (one)
  {
    puts("Hello, World!");
  }
  else
    puts(two);

  return (2);
}
