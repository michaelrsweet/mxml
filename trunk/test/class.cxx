class foo_c			// Foo class
{
  float	foo;				/* Real number */
  int	bar;				/* Integer */

  static int global;			/* Global integer */

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

  // 'set_foobar()' - Set foo and optionally bar (should show default args).
  void
  set_foobar(float f, int b = 0)
  {
    foo = f;
    bar = b;
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
