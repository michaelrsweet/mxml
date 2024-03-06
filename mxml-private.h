//
// Private definitions for Mini-XML, a small XML file parsing library.
//
// https://www.msweet.org/mxml
//
// Copyright © 2003-2024 by Michael R Sweet.
//
// Licensed under Apache License v2.0.  See the file "LICENSE" for more
// information.
//

#ifndef MXML_PRIVATE_H
#  define MXML_PRIVATE_H
#  include "config.h"
#  include "mxml.h"


//
// Private macros...
//

#  ifdef DEBUG
#    define MXML_DEBUG(...)	fprintf(stderr, __VA_ARGS__)
#  else
#    define MXML_DEBUG(...)
#  endif // DEBUG


//
// Private structures...
//

typedef struct _mxml_attr_s		// An XML element attribute value.
{
  char			*name;		// Attribute name
  char			*value;		// Attribute value
} _mxml_attr_t;

typedef struct _mxml_element_s		// An XML element value.
{
  char			*name;		// Name of element
  size_t		num_attrs;	// Number of attributes
  _mxml_attr_t		*attrs;		// Attributes
} _mxml_element_t;

typedef struct _mxml_text_s		// An XML text value.
{
  bool			whitespace;	// Leading whitespace?
  char			*string;	// Fragment string
} _mxml_text_t;

typedef struct _mxml_custom_s		// An XML custom value.
{
  void			*data;		// Pointer to (allocated) custom data
  mxml_custom_destroy_cb_t destroy;	// Pointer to destructor function
} _mxml_custom_t;

typedef union _mxml_value_u		// An XML node value.
{
  char			*cdata;		// CDATA string
  char			*comment;	// Common string
  char			*declaration;	// Declaration string
  char			*directive;	// Processing instruction string
  _mxml_element_t	element;	// Element
  long			integer;	// Integer number
  char			*opaque;	// Opaque string
  double		real;		// Real number
  _mxml_text_t		text;		// Text fragment
  _mxml_custom_t	custom;		// Custom data
} _mxml_value_t;

struct _mxml_node_s			// An XML node.
{
  mxml_type_t		type;		// Node type
  struct _mxml_node_s	*next;		// Next node under same parent
  struct _mxml_node_s	*prev;		// Previous node under same parent
  struct _mxml_node_s	*parent;	// Parent node
  struct _mxml_node_s	*child;		// First child node
  struct _mxml_node_s	*last_child;	// Last child node
  _mxml_value_t		value;		// Node value
  size_t		ref_count;	// Use count
  void			*user_data;	// User data
};

struct _mxml_index_s			 // An XML node index.
{
  char			*attr;		// Attribute used for indexing or NULL
  size_t		num_nodes;	// Number of nodes in index
  size_t		alloc_nodes;	// Allocated nodes in index
  size_t		cur_node;	// Current node
  mxml_node_t		**nodes;	// Node array
};

typedef struct _mxml_global_s		// Global, per-thread data

{
  void		(*error_cb)(const char *);
  size_t	num_entity_cbs;
  int		(*entity_cbs[100])(const char *name);
  int		wrap;
  mxml_custom_load_cb_t	custom_load_cb;
  mxml_custom_save_cb_t	custom_save_cb;
} _mxml_global_t;


//
// Private functions...
//

extern _mxml_global_t	*_mxml_global(void);
extern int		_mxml_entity_cb(const char *name);
extern const char	*_mxml_entity_string(int ch);
extern void		_mxml_error(const char *format, ...) MXML_FORMAT(1,2);


#endif // !MXML_PRIVATE_H
