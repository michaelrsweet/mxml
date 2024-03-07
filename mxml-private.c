//
// Private functions for Mini-XML, a small XML file parsing library.
//
// https://www.msweet.org/mxml
//
// Copyright © 2003-2024 by Michael R Sweet.
//
// Licensed under Apache License v2.0.  See the file "LICENSE" for more
// information.
//

#include "mxml-private.h"


//
// Some crazy people think that unloading a shared object is a good or safe
// thing to do.  Unfortunately, most objects are simply *not* safe to unload
// and bad things *will* happen.
//
// The following mess of conditional code allows us to provide a destructor
// function in Mini-XML for our thread-global storage so that it can possibly
// be unloaded safely, although since there is no standard way to do so I
// can't even provide any guarantees that you can do it safely on all platforms.
//
// This code currently supports AIX, HP-UX, Linux, macOS, Solaris, and
// Windows.  It might work on the BSDs and IRIX, but I haven't tested that.
//

#if defined(__sun) || defined(_AIX)
#  pragma fini(_mxml_fini)
#  define _MXML_FINI _mxml_fini
#elif defined(__hpux)
#  pragma FINI _mxml_fini
#  define _MXML_FINI _mxml_fini
#elif defined(__GNUC__) // Linux and macOS
#  define _MXML_FINI __attribute((destructor)) _mxml_fini
#else
#  define _MXML_FINI _fini
#endif // __sun


//
// 'mxmlSetCustomHandlers()' - Set the custom data callbacks.
//
// This function sets the callbacks that are used for loading and saving custom
// data types. The load callback `load_cb` accepts the callback data pointer
// `cbdata`, a node pointer, and a data string and returns `true` on success and
// `false` on error, for example:
//
// ```c
// typedef struct
// {
//   unsigned year,    /* Year */
//            month,   /* Month */
//            day,     /* Day */
//            hour,    /* Hour */
//            minute,  /* Minute */
//            second;  /* Second */
//   time_t   unix;    /* UNIX time */
// } iso_date_time_t;
//
// bool
// my_custom_load_cb(void *cbdata, mxml_node_t *node, const char *data)
// {
//   iso_date_time_t *dt;
//   struct tm tmdata;
//
//   /* Allocate custom data structure ... */
//   dt = calloc(1, sizeof(iso_date_time_t));
//
//   /* Parse the data string... */
//   if (sscanf(data, "%u-%u-%uT%u:%u:%uZ", &(dt->year), &(dt->month),
//              &(dt->day), &(dt->hour), &(dt->minute), &(dt->second)) != 6)
//   {
//     /* Unable to parse date and time numbers... */
//     free(dt);
//     return (false);
//   }
//
//   /* Range check values... */
//   if (dt->month < 1 || dt->month > 12 || dt->day < 1 || dt->day > 31 ||
//       dt->hour < 0 || dt->hour > 23 || dt->minute < 0 || dt->minute > 59 ||
//       dt->second < 0 || dt->second > 60)
//   {
//     /* Date information is out of range... */
//     free(dt);
//     return (false);
//   }
//
//   /* Convert ISO time to UNIX time in seconds... */
//   tmdata.tm_year = dt->year - 1900;
//   tmdata.tm_mon  = dt->month - 1;
//   tmdata.tm_day  = dt->day;
//   tmdata.tm_hour = dt->hour;
//   tmdata.tm_min  = dt->minute;
//   tmdata.tm_sec  = dt->second;
//
//   dt->unix = gmtime(&tmdata);
//
//   /* Set custom data and free function... */
//   mxmlSetCustom(node, data, free);
//
//   /* Return with no errors... */
//   return (true);
// }
// ```
//
// The save callback `save_cb` accepts the callback data pointer `cbdata` and a
// node pointer and returns a malloc'd string on success and `NULL` on error,
// for example:
//
// ```c
// char *
// my_custom_save_cb(void *cbdata, mxml_node_t *node)
// {
//   char data[255];
//   iso_date_time_t *dt;
//
//   /* Get the custom data structure */
//   dt = (iso_date_time_t *)mxmlGetCustom(node);
//
//   /* Generate string version of the date/time... */
//   snprintf(data, sizeof(data), "%04u-%02u-%02uT%02u:%02u:%02uZ",
//            dt->year, dt->month, dt->day, dt->hour, dt->minute, dt->second);
//
//   /* Duplicate the string and return... */
//   return (strdup(data));
// }
// ```
//


void
mxmlSetCustomCallbacks(
    mxml_custom_load_cb_t load_cb,	// I - Load callback function
    mxml_custom_save_cb_t save_cb,	// I - Save callback function
    void                  *cbdata)	// I - Callback data
{
  _mxml_global_t *global = _mxml_global();
					// Global data


  global->custom_load_cb = load_cb;
  global->custom_save_cb = save_cb;
  global->custom_cbdata  = cbdata;
}


//
// 'mxmlSetErrorCallback()' - Set the error message callback.
//
// This function sets a function to use when reporting errors.  The callback
// `cb` accepts the data pointer `cbdata` and a string pointer containing the
// error message:
//
// ```c
// void my_error_cb(void *cbdata, const char *message)
// {
//   fprintf(stderr, "myprogram: %s\n", message);
// }
// ```
//
// The default error callback writes the error message to the `stderr` file.
//

void
mxmlSetErrorCallback(
    mxml_error_cb_t cb,			// I - Error callback function
    void            *cbdata)		// I - Error callback data
{
  _mxml_global_t *global = _mxml_global();
					// Global data


  global->error_cb     = cb;
  global->error_cbdata = cbdata;
}


//
// 'mxmlSetStringCallbacks()' - Set the string copy/free callback functions.
//
// This function sets the string copy/free callback functions for the current
// thread.  The `strcopy_cb` function makes a copy of the provided string while
// the `strfree_cb` function frees the copy.  Each callback accepts the
// `str_cbdata` pointer along with the pointer to the string:
//
// ```c
// char *my_strcopy_cb(void *cbdata, const char *s)
// {
//   ... make a copy of "s" ...
// }
//
// void my_strfree_cb(void *cbdata, char *s)
// {
//   ... release the memory used by "s" ...
// }
// ```
//
// The default `strcopy_cb` function calls `strdup` while the default
// `strfree_cb` function calls `free`.
//

void
mxmlSetStringCallbacks(
    mxml_strcopy_cb_t strcopy_cb,	// I - String copy callback function
    mxml_strfree_cb_t strfree_cb,	// I - String free callback function
    void              *str_cbdata)	// I - String callback data
{
  _mxml_global_t *global = _mxml_global();
					// Global data


  global->strcopy_cb = strcopy_cb;
  global->strfree_cb = strfree_cb;
  global->str_cbdata = str_cbdata;
}


//
// '_mxml_error()' - Display an error message.
//

void
_mxml_error(const char *format,		// I - Printf-style format string
            ...)			// I - Additional arguments as needed
{
  va_list	ap;			// Pointer to arguments
  char		s[1024];		// Message string
  _mxml_global_t *global = _mxml_global();
					// Global data


  // Range check input...
  if (!format)
    return;

  // Format the error message string...
  va_start(ap, format);
  vsnprintf(s, sizeof(s), format, ap);
  va_end(ap);

  // And then display the error message...
  if (global->error_cb)
    (*global->error_cb)(global->error_cbdata, s);
  else
    fprintf(stderr, "%s\n", s);
}


//
// '_mxml_strcopy()' - Copy a string.
//

char *					// O - Copy of string
_mxml_strcopy(const char *s)		// I - String
{
  _mxml_global_t *global = _mxml_global();
					// Global data


  if (!s)
    return (NULL);

  if (global->strcopy_cb)
    return ((global->strcopy_cb)(global->str_cbdata, s));
  else
    return (strdup(s));
}


//
// '_mxml_strfree()' - Free a string.
//

void
_mxml_strfree(char *s)			// I - String
{
  _mxml_global_t *global = _mxml_global();
					// Global data


  if (!s)
    return;

  if (global->strfree_cb)
    (global->strfree_cb)(global->str_cbdata, s);
  else
    free((void *)s);
}


#ifdef HAVE_PTHREAD_H			// POSIX threading
#  include <pthread.h>

static int		_mxml_initialized = 0;
					// Have we been initialized?
static pthread_key_t	_mxml_key;	// Thread local storage key
static pthread_once_t	_mxml_key_once = PTHREAD_ONCE_INIT;
					// One-time initialization object
static void		_mxml_init(void);
static void		_mxml_destructor(void *g);


//
// '_mxml_destructor()' - Free memory used for globals...
//

static void
_mxml_destructor(void *g)		// I - Global data
{
  free(g);
}


//
// '_mxml_fini()' - Clean up when unloaded.
//

static void
_MXML_FINI(void)
{
  if (_mxml_initialized)
    pthread_key_delete(_mxml_key);
}


//
// '_mxml_global()' - Get global data.
//

_mxml_global_t *			// O - Global data
_mxml_global(void)
{
  _mxml_global_t	*global;	// Global data


  pthread_once(&_mxml_key_once, _mxml_init);

  if ((global = (_mxml_global_t *)pthread_getspecific(_mxml_key)) == NULL)
  {
    global = (_mxml_global_t *)calloc(1, sizeof(_mxml_global_t));
    pthread_setspecific(_mxml_key, global);

    global->num_entity_cbs = 1;
    global->entity_cbs[0]  = _mxml_entity_cb;
    global->wrap           = 72;
  }

  return (global);
}


//
// '_mxml_init()' - Initialize global data...
//

static void
_mxml_init(void)
{
  _mxml_initialized = 1;
  pthread_key_create(&_mxml_key, _mxml_destructor);
}


#elif defined(_WIN32) && defined(MXML1_EXPORTS) // WIN32 threading
#  include <windows.h>

static DWORD _mxml_tls_index;		// Index for global storage


//
// 'DllMain()' - Main entry for library.
//

BOOL WINAPI				// O - Success/failure
DllMain(HINSTANCE hinst,		// I - DLL module handle
        DWORD     reason,		// I - Reason
        LPVOID    reserved)		// I - Unused
{
  _mxml_global_t	*global;	// Global data


  (void)hinst;
  (void)reserved;

  switch (reason)
  {
    case DLL_PROCESS_ATTACH :		// Called on library initialization
        if ((_mxml_tls_index = TlsAlloc()) == TLS_OUT_OF_INDEXES)
          return (FALSE);
        break;

    case DLL_THREAD_DETACH :		// Called when a thread terminates
        if ((global = (_mxml_global_t *)TlsGetValue(_mxml_tls_index)) != NULL)
          free(global);
        break;

    case DLL_PROCESS_DETACH :		// Called when library is unloaded
        if ((global = (_mxml_global_t *)TlsGetValue(_mxml_tls_index)) != NULL)
          free(global);

        TlsFree(_mxml_tls_index);
        break;

    default:
        break;
  }

  return (TRUE);
}


//
// '_mxml_global()' - Get global data.
//

_mxml_global_t *			// O - Global data
_mxml_global(void)
{
  _mxml_global_t	*global;	// Global data


  if ((global = (_mxml_global_t *)TlsGetValue(_mxml_tls_index)) == NULL)
  {
    global = (_mxml_global_t *)calloc(1, sizeof(_mxml_global_t));

    global->num_entity_cbs = 1;
    global->entity_cbs[0]  = _mxml_entity_cb;
    global->wrap           = 72;

    TlsSetValue(_mxml_tls_index, (LPVOID)global);
  }

  return (global);
}


#else					// No threading
//
// '_mxml_global()' - Get global data.
//

_mxml_global_t *			// O - Global data
_mxml_global(void)
{
  static _mxml_global_t	global =	// Global data
  {
    NULL,				// error_cb
    1,					// num_entity_cbs
    { _mxml_entity_cb },		// entity_cbs
    72,					// wrap
    NULL,				// custom_load_cb
    NULL				// custom_save_cb
  };


  return (&global);
}
#endif // HAVE_PTHREAD_H
