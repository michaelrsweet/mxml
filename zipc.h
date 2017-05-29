/*
 * Header file for ZIP container mini-library.
 *
 *     https://github.com/michaelrsweet/zipc
 *
 * Copyright 2017 by Michael R Sweet.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

#ifndef ZIPC_H
#  define ZIPC_H

/*
 * Include necessary headers...
 */

#  include <stdlib.h>
#  include <sys/types.h>


/*
 * Types...
 */

typedef struct _zipc_s zipc_t;		/* ZIP container */
typedef struct _zipc_file_s zipc_file_t;/* File/directory in ZIP container */


/*
 * Functions...
 */

#  ifdef __cplusplus
extern "C" {
#  endif /* __cplusplus */

extern int		zipcClose(zipc_t *zc);
extern int              zipcCopyFile(zipc_t *zc, const char *dstname, const char *srcname, int text, int compressed);
extern int		zipcCreateDirectory(zipc_t *zc, const char *filename);
extern zipc_file_t	*zipcCreateFile(zipc_t *zc, const char *filename, int compressed);
extern int		zipcCreateFileWithString(zipc_t *zc, const char *filename, const char *contents);
extern const char	*zipcError(zipc_t *zc);
extern int		zipcFileFinish(zipc_file_t *zf);
extern int		zipcFilePrintf(zipc_file_t *zf, const char *format, ...)
#  ifdef __GNUC__
__attribute__ ((__format__ (__printf__, 2, 3)))
#  endif /* __GNUC__ */
;
extern int		zipcFilePuts(zipc_file_t *zf, const char *s);
extern int		zipcFileWrite(zipc_file_t *zf, const void *data, size_t bytes);
extern int		zipcFileXMLPrintf(zipc_file_t *zf, const char *format, ...)
#  ifdef __GNUC__
__attribute__ ((__format__ (__printf__, 2, 3)))
#  endif /* __GNUC__ */
;
extern zipc_t		*zipcOpen(const char *filename, const char *mode);

#  ifdef __cplusplus
}
#  endif /* __cplusplus */

#endif /* !ZIPC_H */
