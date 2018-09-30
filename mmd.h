/*
 * Header file for miniature markdown library.
 *
 *     https://github.com/michaelrsweet/mmd
 *
 * Copyright © 2017-2018 by Michael R Sweet.
 *
 * Licensed under Apache License v2.0.  See the file "LICENSE" for more
 * information.
 */

#ifndef MMD_H
#  define MMD_H

/*
 * Include necessary headers...
 */

#  include <stdio.h>


/*
 * Constants...
 */

typedef enum mmd_type_e
{
  MMD_TYPE_NONE = -1,
  MMD_TYPE_DOCUMENT,
  MMD_TYPE_METADATA,
  MMD_TYPE_BLOCK_QUOTE,
  MMD_TYPE_ORDERED_LIST,
  MMD_TYPE_UNORDERED_LIST,
  MMD_TYPE_LIST_ITEM,
  MMD_TYPE_TABLE,
  MMD_TYPE_TABLE_HEADER,
  MMD_TYPE_TABLE_BODY,
  MMD_TYPE_TABLE_ROW,
  MMD_TYPE_HEADING_1 = 10,
  MMD_TYPE_HEADING_2,
  MMD_TYPE_HEADING_3,
  MMD_TYPE_HEADING_4,
  MMD_TYPE_HEADING_5,
  MMD_TYPE_HEADING_6,
  MMD_TYPE_PARAGRAPH,
  MMD_TYPE_CODE_BLOCK,
  MMD_TYPE_THEMATIC_BREAK,
  MMD_TYPE_TABLE_HEADER_CELL,
  MMD_TYPE_TABLE_BODY_CELL_LEFT,
  MMD_TYPE_TABLE_BODY_CELL_CENTER,
  MMD_TYPE_TABLE_BODY_CELL_RIGHT,
  MMD_TYPE_NORMAL_TEXT = 100,
  MMD_TYPE_EMPHASIZED_TEXT,
  MMD_TYPE_STRONG_TEXT,
  MMD_TYPE_STRUCK_TEXT,
  MMD_TYPE_LINKED_TEXT,
  MMD_TYPE_CODE_TEXT,
  MMD_TYPE_IMAGE,
  MMD_TYPE_HARD_BREAK,
  MMD_TYPE_SOFT_BREAK,
  MMD_TYPE_METADATA_TEXT
} mmd_type_t;


/*
 * Types...
 */

typedef struct _mmd_s mmd_t;


/*
 * Functions...
 */

#  ifdef __cplusplus
extern "C" {
#  endif /* __cplusplus */

extern char       *mmdCopyAllText(mmd_t *node);
extern void       mmdFree(mmd_t *node);
extern mmd_t      *mmdGetFirstChild(mmd_t *node);
extern mmd_t      *mmdGetLastChild(mmd_t *node);
extern const char *mmdGetMetadata(mmd_t *doc, const char *keyword);
extern mmd_t      *mmdGetNextSibling(mmd_t *node);
extern mmd_t      *mmdGetParent(mmd_t *node);
extern mmd_t      *mmdGetPrevSibling(mmd_t *node);
extern const char *mmdGetText(mmd_t *node);
extern mmd_type_t mmdGetType(mmd_t *node);
extern const char *mmdGetURL(mmd_t *node);
extern int        mmdGetWhitespace(mmd_t *node);
extern int        mmdIsBlock(mmd_t *node);
extern mmd_t      *mmdLoad(const char *filename);
extern mmd_t      *mmdLoadFile(FILE *fp);

#  ifdef __cplusplus
}
#  endif /* __cplusplus */

#endif /* !MMD_H */
