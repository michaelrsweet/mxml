//
// Header file for Mini-XML, a small XML file parsing library.
//
// https://www.msweet.org/mxml
//
// Copyright © 2003-2024 by Michael R Sweet.
//
// Licensed under Apache License v2.0.  See the file "LICENSE" for more
// information.
//

#ifndef MXML_H
#  define MXML_H
#  include <stdio.h>
#  include <stdlib.h>
#  include <stdbool.h>
#  include <string.h>
#  include <ctype.h>
#  include <errno.h>
#  include <limits.h>
#  if defined(_WIN32) && !defined(__CUPS_SSIZE_T_DEFINED)
#    define __CUPS_SSIZE_T_DEFINED
#    include <sys/types.h>
// Windows does not provide the ssize_t type, so map it to int64_t... */
typedef int64_t ssize_t;			// @private@
#    define SSIZE_MAX	INT64_MAX
#  endif // _WIN32 && !__CUPS_SSIZE_T_DEFINED
#  ifdef __cplusplus
extern "C" {
#  endif // __cplusplus


//
// Constants...
//

#  define MXML_MAJOR_VERSION	4	// Major version number
#  define MXML_MINOR_VERSION	0	// Minor version number

#  ifdef __GNUC__
#    define MXML_FORMAT(a,b)	__attribute__ ((__format__ (__printf__, a, b)))
#  else
#    define MXML_FORMAT(a,b)
#  endif // __GNUC__

#  define MXML_TAB		8	// Tabs every N columns

#  define MXML_DESCEND		1	// Descend when finding/walking
#  define MXML_NO_DESCEND	0	// Don't descend when finding/walking
#  define MXML_DESCEND_FIRST	-1	// Descend for first find

#  define MXML_ADD_BEFORE	0	// Add node before specified node
#  define MXML_ADD_AFTER	1	// Add node after specified node
#  define MXML_ADD_TO_PARENT	NULL	// Add node relative to parent


//
// Data types...
//

typedef enum mxml_sax_event_e		// SAX event type.
{
  MXML_SAX_EVENT_CDATA,			// CDATA node
  MXML_SAX_EVENT_COMMENT,		// Comment node
  MXML_SAX_EVENT_DATA,			// Data node
  MXML_SAX_EVENT_DECLARATION,		// Declaration node
  MXML_SAX_EVENT_DIRECTIVE,		// Processing instruction node
  MXML_SAX_EVENT_ELEMENT_CLOSE,		// Element closed
  MXML_SAX_EVENT_ELEMENT_OPEN		// Element opened
} mxml_sax_event_t;

typedef enum mxml_type_e		// The XML node type.
{
  MXML_TYPE_IGNORE = -1,		// Ignore/throw away node
  MXML_TYPE_CDATA,			// CDATA value ("<[CDATA[...]]>")
  MXML_TYPE_COMMENT,			// Comment ("<!--...-->")
  MXML_TYPE_DECLARATION,		// Declaration ("<!...>")
  MXML_TYPE_DIRECTIVE,			// Processing instruction ("<?...?>")
  MXML_TYPE_ELEMENT,			// XML element with attributes
  MXML_TYPE_INTEGER,			// Integer value
  MXML_TYPE_OPAQUE,			// Opaque string
  MXML_TYPE_REAL,			// Real value
  MXML_TYPE_TEXT,			// Text fragment
  MXML_TYPE_CUSTOM			// Custom data
} mxml_type_t;

typedef enum mxml_ws_e			// Whitespace periods
{
  MXML_WS_BEFORE_OPEN,			// Callback for before open tag
  MXML_WS_AFTER_OPEN,			// Callback for after open tag
  MXML_WS_BEFORE_CLOSE,			// Callback for before close tag
  MXML_WS_AFTER_CLOSE,			// Callback for after close tag
} mxml_ws_t;

typedef void (*mxml_custom_destroy_cb_t)(void *);
					// Custom data destructor

typedef void (*mxml_error_cb_t)(const char *);
					// Error callback function

typedef struct _mxml_node_s mxml_node_t;// An XML node.

typedef struct _mxml_index_s mxml_index_t;
					// An XML node index.

typedef bool (*mxml_custom_load_cb_t)(mxml_node_t *node, const char *s);
					// Custom data load callback function

typedef char *(*mxml_custom_save_cb_t)(mxml_node_t *node);
					// Custom data save callback function

typedef int (*mxml_entity_cb_t)(const char *name);
					// Entity callback function

typedef mxml_type_t (*mxml_load_cb_t)(void *cbdata, mxml_node_t *node);
					// Load callback function

typedef ssize_t (*mxml_read_cb_t)(void *cbdata, void *buffer, size_t bytes);
					// Read callback function

typedef const char *(*mxml_save_cb_t)(void *cbdata, mxml_node_t *node, mxml_ws_t when);
					// Save callback function

typedef ssize_t (*mxml_write_cb_t)(void *cbdata, const void *buffer, size_t bytes);
					// Write callback function

typedef bool (*mxml_sax_cb_t)(void *cbdata, mxml_node_t *node, mxml_sax_event_t event);
					// SAX callback function


//
// Prototypes...
//

extern void		mxmlAdd(mxml_node_t *parent, int where, mxml_node_t *child, mxml_node_t *node);

extern void		mxmlDelete(mxml_node_t *node);

extern void		mxmlElementDeleteAttr(mxml_node_t *node, const char *name);
extern const char	*mxmlElementGetAttr(mxml_node_t *node, const char *name);
extern const char       *mxmlElementGetAttrByIndex(mxml_node_t *node, int idx, const char **name);
extern size_t		mxmlElementGetAttrCount(mxml_node_t *node);
extern void		mxmlElementSetAttr(mxml_node_t *node, const char *name, const char *value);
extern void		mxmlElementSetAttrf(mxml_node_t *node, const char *name, const char *format, ...) MXML_FORMAT(3,4);
extern bool		mxmlEntityAddCallback(mxml_entity_cb_t cb);
extern int		mxmlEntityGetValue(const char *name);
extern void		mxmlEntityRemoveCallback(mxml_entity_cb_t cb);

extern mxml_node_t	*mxmlFindElement(mxml_node_t *node, mxml_node_t *top, const char *element, const char *attr, const char *value, int descend);
extern mxml_node_t	*mxmlFindPath(mxml_node_t *node, const char *path);

extern const char	*mxmlGetCDATA(mxml_node_t *node);
extern const char	*mxmlGetComment(mxml_node_t *node);
extern const void	*mxmlGetCustom(mxml_node_t *node);
extern const char	*mxmlGetDeclaration(mxml_node_t *node);
extern const char	*mxmlGetDirective(mxml_node_t *node);
extern const char	*mxmlGetElement(mxml_node_t *node);
extern mxml_node_t	*mxmlGetFirstChild(mxml_node_t *node);
extern long		mxmlGetInteger(mxml_node_t *node);
extern mxml_node_t	*mxmlGetLastChild(mxml_node_t *node);
extern mxml_node_t	*mxmlGetNextSibling(mxml_node_t *node);
extern const char	*mxmlGetOpaque(mxml_node_t *node);
extern mxml_node_t	*mxmlGetParent(mxml_node_t *node);
extern mxml_node_t	*mxmlGetPrevSibling(mxml_node_t *node);
extern double		mxmlGetReal(mxml_node_t *node);
extern size_t		mxmlGetRefCount(mxml_node_t *node);
extern const char	*mxmlGetText(mxml_node_t *node, bool *whitespace);
extern mxml_type_t	mxmlGetType(mxml_node_t *node);
extern void		*mxmlGetUserData(mxml_node_t *node);

extern void		mxmlIndexDelete(mxml_index_t *ind);
extern mxml_node_t	*mxmlIndexEnum(mxml_index_t *ind);
extern mxml_node_t	*mxmlIndexFind(mxml_index_t *ind, const char *element, const char *value);
extern size_t		mxmlIndexGetCount(mxml_index_t *ind);
extern mxml_index_t	*mxmlIndexNew(mxml_node_t *node, const char *element, const char *attr);
extern mxml_node_t	*mxmlIndexReset(mxml_index_t *ind);

extern mxml_node_t	*mxmlLoadFd(mxml_node_t *top, int fd, mxml_load_cb_t load_cb, void *load_cbdata, mxml_sax_cb_t sax_cb, void *sax_cbdata);
extern mxml_node_t	*mxmlLoadFile(mxml_node_t *top, FILE *fp, mxml_load_cb_t load_cb, void *load_cbdata, mxml_sax_cb_t sax_cb, void *sax_cbdata);
extern mxml_node_t	*mxmlLoadFilename(mxml_node_t *top, const char *filename, mxml_load_cb_t load_cb, void *load_cbdata, mxml_sax_cb_t sax_cb, void *sax_cbdata);
extern mxml_node_t	*mxmlLoadIO(mxml_node_t *top, mxml_read_cb_t read_cb, void *read_cbdata, mxml_load_cb_t load_cb, void *load_cbdata, mxml_sax_cb_t sax_cb, void *sax_cbdata);
extern mxml_node_t	*mxmlLoadString(mxml_node_t *top, const char *s, mxml_load_cb_t load_cb, void *load_cbdata, mxml_sax_cb_t sax_cb, void *sax_cbdata);

extern mxml_node_t	*mxmlNewCDATA(mxml_node_t *parent, const char *string);
extern mxml_node_t	*mxmlNewCDATAf(mxml_node_t *parent, const char *format, ...) MXML_FORMAT(2,3);
extern mxml_node_t	*mxmlNewComment(mxml_node_t *parent, const char *comment);
extern mxml_node_t	*mxmlNewCommentf(mxml_node_t *parent, const char *format, ...) MXML_FORMAT(2,3);
extern mxml_node_t	*mxmlNewCustom(mxml_node_t *parent, void *data, mxml_custom_destroy_cb_t destroy);
extern mxml_node_t	*mxmlNewDeclaration(mxml_node_t *parent, const char *declaration);
extern mxml_node_t	*mxmlNewDeclarationf(mxml_node_t *parent, const char *format, ...) MXML_FORMAT(2,3);
extern mxml_node_t	*mxmlNewDirective(mxml_node_t *parent, const char *directive);
extern mxml_node_t	*mxmlNewDirectivef(mxml_node_t *parent, const char *format, ...) MXML_FORMAT(2,3);
extern mxml_node_t	*mxmlNewElement(mxml_node_t *parent, const char *name);
extern mxml_node_t	*mxmlNewInteger(mxml_node_t *parent, long integer);
extern mxml_node_t	*mxmlNewOpaque(mxml_node_t *parent, const char *opaque);
extern mxml_node_t	*mxmlNewOpaquef(mxml_node_t *parent, const char *format, ...) MXML_FORMAT(2,3);
extern mxml_node_t	*mxmlNewReal(mxml_node_t *parent, double real);
extern mxml_node_t	*mxmlNewText(mxml_node_t *parent, bool whitespace, const char *string);
extern mxml_node_t	*mxmlNewTextf(mxml_node_t *parent, bool whitespace, const char *format, ...) MXML_FORMAT(3,4);
extern mxml_node_t	*mxmlNewXML(const char *version);

extern int		mxmlRelease(mxml_node_t *node);
extern void		mxmlRemove(mxml_node_t *node);
extern int		mxmlRetain(mxml_node_t *node);

extern char		*mxmlSaveAllocString(mxml_node_t *node, mxml_save_cb_t save_cb, void *save_cbdata);
extern bool		mxmlSaveFd(mxml_node_t *node, int fd, mxml_save_cb_t save_cb, void *save_cbdata);
extern bool		mxmlSaveFile(mxml_node_t *node, FILE *fp, mxml_save_cb_t save_cb, void *save_cbdata);
extern bool		mxmlSaveFilename(mxml_node_t *node, const char *filename, mxml_save_cb_t save_cb, void *save_cbdata);
extern bool		mxmlSaveIO(mxml_node_t *node, mxml_write_cb_t write_cb, void *write_cbdata, mxml_save_cb_t save_cb, void *save_cbdata);
extern size_t		mxmlSaveString(mxml_node_t *node, char *buffer, size_t bufsize, mxml_save_cb_t save_cb, void *save_cbdata);
extern bool		mxmlSetCDATA(mxml_node_t *node, const char *data);
extern bool		mxmlSetCDATAf(mxml_node_t *node, const char *format, ...) MXML_FORMAT(2,3);
extern bool		mxmlSetComment(mxml_node_t *node, const char *comment);
extern bool		mxmlSetCommentf(mxml_node_t *node, const char *format, ...) MXML_FORMAT(2,3);
extern bool		mxmlSetDeclaration(mxml_node_t *node, const char *declaration);
extern bool		mxmlSetDeclarationf(mxml_node_t *node, const char *format, ...) MXML_FORMAT(2,3);
extern bool		mxmlSetDirective(mxml_node_t *node, const char *directive);
extern bool		mxmlSetDirectivef(mxml_node_t *node, const char *format, ...) MXML_FORMAT(2,3);
extern bool		mxmlSetCustom(mxml_node_t *node, void *data, mxml_custom_destroy_cb_t destroy);
extern void		mxmlSetCustomHandlers(mxml_custom_load_cb_t load, mxml_custom_save_cb_t save);
extern bool		mxmlSetElement(mxml_node_t *node, const char *name);
extern void		mxmlSetErrorCallback(mxml_error_cb_t cb);
extern bool		mxmlSetInteger(mxml_node_t *node, long integer);
extern bool		mxmlSetOpaque(mxml_node_t *node, const char *opaque);
extern bool		mxmlSetOpaquef(mxml_node_t *node, const char *format, ...) MXML_FORMAT(2,3);
extern bool		mxmlSetReal(mxml_node_t *node, double real);
extern bool		mxmlSetText(mxml_node_t *node, bool whitespace, const char *string);
extern bool		mxmlSetTextf(mxml_node_t *node, bool whitespace, const char *format, ...) MXML_FORMAT(3,4);
extern bool		mxmlSetUserData(mxml_node_t *node, void *data);
extern void		mxmlSetWrapMargin(int column);

extern mxml_node_t	*mxmlWalkNext(mxml_node_t *node, mxml_node_t *top, int descend);
extern mxml_node_t	*mxmlWalkPrev(mxml_node_t *node, mxml_node_t *top, int descend);


#  ifdef __cplusplus
}
#  endif // __cplusplus
#endif // !MXML_H
