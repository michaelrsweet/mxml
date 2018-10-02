/*
 * Implementation of ZIP container mini-library.
 *
 *     https://github.com/michaelrsweet/zipc
 *
 * Define ZIPC_ONLY_READ to compile with just the ZIP reading code, or
 * ZIPC_ONLY_WRITE to compile with just the ZIP writing code.  Otherwise both
 * ZIP reader and writer code is built.
 *
 * Copyright 2017-2018 by Michael R Sweet.
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

/*
 * Include necessary headers...
 */

#ifdef WIN32
#  define _CRT_SECURE_NO_WARNINGS	/* Disable warnings for standard library functions */
#endif /* WIN32 */

#include "zipc.h"
#include <stdio.h>
#include <stdarg.h>
#include <string.h>
#include <ctype.h>
#include <errno.h>
#include <time.h>
#include <zlib.h>


/*
 * Local constants...
 */

#define ZIPC_LOCAL_HEADER  0x04034b50	/* Start of a local file header */
#define ZIPC_DIR_HEADER    0x02014b50	/* File header in central directory */
#define ZIPC_END_RECORD    0x06054b50	/* End of central directory record */

#define ZIPC_MADE_BY       0x031e	/* Version made by UNIX using zip 2.0 */
#define ZIPC_DIR_VERSION   0x000a	/* Version needed: 1.0 */
#define ZIPC_FILE_VERSION  0x0014	/* Version needed: 2.0 */

#define ZIPC_FLAG_CMAX     0x0002	/* Maximum compression */
#define ZIPC_FLAG_MASK     0x7fff       /* Mask for "standard" flags we want to write */
#define ZIPC_FLAG_STREAMED 0x8000       /* Internal bit used to flag when we need to update the CRC and length fields */

#define ZIPC_COMP_STORE    0		/* No compression */
#define ZIPC_COMP_DEFLATE  8		/* Deflate compression */

#define ZIPC_INTERNAL_BIN  0x0000	/* Internal attributes = binary */
#define ZIPC_INTERNAL_TEXT 0x0001	/* Internal attributes = text */

#define ZIPC_EXTERNAL_DIR  0x41ed0010	/* External attributes = directory */
#define ZIPC_EXTERNAL_FILE 0x81a40000	/* External attributes = file */

#define ZIPC_READ_SIZE     8192         /* Size of buffered read buffer */


/*
 * Local types...
 */

struct _zipc_s
{
  FILE		*fp;			/* ZIP file */
  char          mode;                   /* Open mode - 'r' or 'w' */
  const char	*error;			/* Last error message */
  char          error_msg[256];         /* Formatted error message */
  size_t	alloc_files,		/* Allocated file entries in ZIP */
		num_files;		/* Number of file entries in ZIP */
  zipc_file_t	*files;			/* File entries in ZIP */
  z_stream	stream;			/* Deflate stream for current file */
  unsigned int	modtime;		/* MS-DOS modification date/time */
  char		buffer[16384];		/* Deflate buffer */
#ifndef ZIPC_ONLY_WRITE
  char          *readbuffer,            /* Read buffer */
                *readptr,               /* Current character in read buffer */
                *readend;               /* End of read buffer */
#endif /* !ZIPC_ONLY_WRITE */
};

struct _zipc_file_s
{
  zipc_t	*zc;			/* ZIP container */
  char		filename[256];		/* File/directory name */
  unsigned short flags;			/* General purpose bit flags */
  unsigned short method;		/* Compression method */
  unsigned int	crc32;			/* 32-bit CRC */
  size_t	compressed_size;	/* Size of file (compressed) */
  size_t	uncompressed_size;	/* Size of file (uncompressed) */
  size_t	offset;			/* Offset of this entry in file */
  unsigned short internal_attrs;	/* Internal attributes */
  unsigned int	external_attrs;		/* External attributes */
  size_t        local_size;             /* Size of local header */
  size_t        compressed_pos;         /* Current read position in stream */
  size_t        uncompressed_pos;       /* Current read position in file */
};


/*
 * Local functions...
 */

#ifndef ZIPC_ONLY_WRITE
static ssize_t		zipc_read(zipc_t *zc, void *buffer, size_t bytes);
static int              zipc_read_char(zipc_file_t *zf);
static ssize_t		zipc_read_file(zipc_file_t *zf, void *buffer, size_t bytes);
static unsigned		zipc_read_u16(zipc_t *zc);
static unsigned		zipc_read_u32(zipc_t *zc);
static void             zipc_xml_unescape(char *buffer);
#endif /* !ZIPC_ONLY_WRITE */
#ifndef ZIPC_ONLY_READ
static zipc_file_t	*zipc_add_file(zipc_t *zc, const char *filename, int compression);
static int		zipc_write(zipc_t *zc, const void *buffer, size_t bytes);
static int		zipc_write_dir_header(zipc_t *zc, zipc_file_t *zf);
static int		zipc_write_local_header(zipc_t *zc, zipc_file_t *zf);
static int		zipc_write_local_trailer(zipc_t *zc, zipc_file_t *zf);
static int		zipc_write_u16(zipc_t *zc, unsigned value);
static int		zipc_write_u32(zipc_t *zc, unsigned value);
#endif /* !ZIPC_ONLY_READ */
static const char       *zipc_zlib_status(int zstatus);


/*
 * 'zipcClose()' - Close a ZIP container, writing out the central directory as
 *                 needed.
 */

int					/* O - 0 on success, -1 on error */
zipcClose(zipc_t *zc)			/* I - ZIP container */
{
  int	status = 0;		        /* Return status */


#ifndef ZIPC_ONLY_READ
  if (zc->mode == 'w')
  {
    size_t	i;			/* Looping var */
    zipc_file_t	*zf;			/* Current file */
    long	start, end;		/* Central directory offsets */

   /*
    * First write the central directory headers...
    */

    start = ftell(zc->fp);

    for (i = zc->num_files, zf = zc->files; i > 0; i --, zf ++)
      status |= zipc_write_dir_header(zc, zf);

    end = ftell(zc->fp);

   /*
    * Then write the end of central directory block...
    */

    status |= zipc_write_u32(zc, ZIPC_END_RECORD);
    status |= zipc_write_u16(zc, 0); /* Disk number */
    status |= zipc_write_u16(zc, 0); /* Disk number containing directory */
    status |= zipc_write_u16(zc, (unsigned)zc->num_files);
    status |= zipc_write_u16(zc, (unsigned)zc->num_files);
    status |= zipc_write_u32(zc, (unsigned)(end - start));
    status |= zipc_write_u32(zc, (unsigned)start);
    status |= zipc_write_u16(zc, 0); /* file comment length */
  }
#endif /* !ZIPC_ONLY_READ */

#ifndef ZIPC_ONLY_WRITE
  if (zc->readbuffer)
    free(zc->readbuffer);
#endif /* !ZIPC_ONLY_WRITE */

  if (fclose(zc->fp))
    status = -1;

  if (zc->alloc_files)
    free(zc->files);

  free(zc);

  return (status);
}


#ifndef ZIPC_ONLY_READ
/*
 * 'zipcCopyFile()' - Copy a file into a ZIP container.
 *
 * The file referenced by "srcname" will be efficiently copied into the ZIP
 * container with the name "dstname".
 *
 * The "compressed" value determines whether the file is compressed within the
 * container.
 */

int                                     /* O - 0 on success, -1 on error */
zipcCopyFile(zipc_t *zc,                /* I - ZIP container */
             const char *dstname,       /* I - Destination file (in ZIP container) */
             const char *srcname,       /* I - Source file (on disk) */
             int        text,           /* I - 0 for binary, 1 for text */
             int        compressed)     /* I - 0 for uncompressed, 1 for compressed */
{
  zipc_file_t   *dstfile;               /* Destination file */
  FILE          *srcfile;               /* Source file */
  char          buffer[65536];          /* Copy buffer */
  size_t        length;                 /* Number of bytes read */


  if (zc->mode != 'w')
  {
    zc->error = "Not opened for writing.";
    return (-1);
  }

  if ((srcfile = fopen(srcname, text ? "r" : "rb")) == NULL)
  {
    zc->error = strerror(errno);
    return (-1);
  }

  if ((dstfile = zipcCreateFile(zc, dstname, compressed)) == NULL)
  {
    fclose(srcfile);
    return (-1);
  }

  if (text)
  {
   /*
    * Copy as text...
    */

    while (fgets(buffer, sizeof(buffer), srcfile))
    {
      if (zipcFilePuts(dstfile, buffer))
      {
        fclose(srcfile);
        zipcFileFinish(dstfile);
        return (-1);
      }
    }
  }
  else
  {
   /*
    * Copy as binary...
    */

    while ((length = fread(buffer, 1, sizeof(buffer), srcfile)) > 0)
    {
      if (zipcFileWrite(dstfile, buffer, length))
      {
        fclose(srcfile);
        zipcFileFinish(dstfile);
        return (-1);
      }
    }
  }

  fclose(srcfile);

  return (zipcFileFinish(dstfile));
}


/*
 * 'zipcCreateDirectory()' - Create a directory in a ZIP container.
 *
 * The "filename" value is the path within the ZIP container.  Directories are
 * separated by the forward slash ("/").
 */

int					/* O - 0 on success, -1 on error */
zipcCreateDirectory(
    zipc_t     *zc,			/* I - ZIP container */
    const char *filename)		/* I - Directory name */
{
  zipc_file_t	*zf;    		/* ZIP container file */
  int		status = 0;		/* Return status */


  if (zc->mode != 'w')
  {
    zc->error = "Not opened for writing.";
    return (-1);
  }

  if ((zf = zipc_add_file(zc, filename, 0)) != NULL)
  {
    char *end = zf->filename + strlen(zf->filename);

    if (end > zf->filename && end < (zf->filename + sizeof(zf->filename) - 1) && end[-1] != '/')
      *end = '/';

    zf->crc32          = 0;
    zf->external_attrs = ZIPC_EXTERNAL_DIR;

    status |= zipc_write_local_header(zc, zf);
  }
  else
    status = -1;

  return (status);
}


/*
 * 'zipcCreateFile()' - Create a ZIP container file.
 *
 * The "filename" value is the path within the ZIP container.  Directories are
 * separated by the forward slash ("/").
 *
 * The "compressed" value determines whether the file is compressed within the
 * container.
 */

zipc_file_t *				/* I - ZIP container file */
zipcCreateFile(
    zipc_t     *zc,			/* I - ZIP container */
    const char *filename,		/* I - Filename in container */
    int        compressed)		/* I - 0 for uncompressed, 1 for compressed */
{
  zipc_file_t	*zf;    		/* ZIP container file */


  if (zc->mode != 'w')
  {
    zc->error = "Not opened for writing.";
    return (NULL);
  }

 /*
  * Add the file and write the header...
  */

  zf = zipc_add_file(zc, filename, compressed);
  zf->flags |= ZIPC_FLAG_STREAMED;
  zf->external_attrs = ZIPC_EXTERNAL_FILE;

  if (zipc_write_local_header(zc, zf))
    return (NULL);
  else
    return (zf);
}


/*
 * 'zipcCreateFileWithString()' - Add a file whose contents are a string.
 *
 * This function should be used for short ZIP container files like mimetype
 * or container descriptions.
 *
 * Note: Files added this way are not compressed.
 */

int					/* O - 0 on success, -1 on failure */
zipcCreateFileWithString(
    zipc_t     *zc,			/* I - ZIP container */
    const char *filename,		/* I - Filename in container */
    const char *contents)		/* I - Contents of file */
{
  zipc_file_t	*zf;			/* ZIP container file */
  size_t	len = strlen(contents);	/* Length of contents */
  int		status = 0;		/* Return status */


  if (zc->mode != 'w')
  {
    zc->error = "Not opened for writing.";
    return (-1);
  }

  if ((zf = zipc_add_file(zc, filename, 0)) != NULL)
  {
    zf->uncompressed_size = len;
    zf->compressed_size   = len;
    zf->crc32             = crc32(zf->crc32, (const Bytef *)contents, (unsigned)len);
    zf->internal_attrs    = ZIPC_INTERNAL_TEXT;
    zf->external_attrs    = ZIPC_EXTERNAL_FILE;

    status |= zipc_write_local_header(zc, zf);
    status |= zipc_write(zc, contents, len);
  }
  else
    status = -1;

  return (status);
}
#endif /* !ZIPC_ONLY_READ */


/*
 * 'zipcError()' - Return a message describing the last detected error.
 */

const char *				/* O - Error string or NULL */
zipcError(zipc_t *zc)			/* I - ZIP container */
{
  return (zc ? zc->error : NULL);
}


/*
 * 'zipcFileFinish()' - Finish writing to a file in a ZIP container.
 */

int					/* O - 0 on success, -1 on error */
zipcFileFinish(zipc_file_t *zf)		/* I - ZIP container file */
{
  int		status = 0;		/* Return status */
  zipc_t	*zc = zf->zc;		/* ZIP container */


#ifndef ZIPC_ONLY_READ
  if (zc->mode == 'w')
  {
    if (zf->method != ZIPC_COMP_STORE)
    {
      int zstatus;			/* Deflate status */

      while ((zstatus = deflate(&zc->stream, Z_FINISH)) != Z_STREAM_END)
      {
        if (zstatus < Z_OK && zstatus != Z_BUF_ERROR)
        {
          zc->error = zipc_zlib_status(zstatus);
          status = -1;
          break;
        }

        status |= zipc_write(zf->zc, zc->buffer, (size_t)((char *)zc->stream.next_out - zc->buffer));
        zf->compressed_size += (size_t)((char *)zc->stream.next_out - zc->buffer);

        zc->stream.next_out  = (Bytef *)zc->buffer;
        zc->stream.avail_out = sizeof(zc->buffer);
      }

      if ((char *)zc->stream.next_out > zc->buffer)
      {
        status |= zipc_write(zf->zc, zc->buffer, (size_t)((char *)zc->stream.next_out - zc->buffer));
        zf->compressed_size += (size_t)((char *)zc->stream.next_out - zc->buffer);
      }

      deflateEnd(&zc->stream);
    }

    status |= zipc_write_local_trailer(zc, zf);
  }
#endif /* !ZIPC_ONLY_READ */

#ifndef ZIPC_ONLY_WRITE
#  ifndef ZIPC_ONLY_READ
  else
#  endif /* !ZIPC_ONLY_READ */
  if (zc->mode == 'r' && zf->method != ZIPC_COMP_STORE)
    inflateEnd(&zc->stream);
#endif /* !ZIPC_ONLY_WRITE */

  return (status);
}


#ifndef ZIPC_ONLY_WRITE
/*
 * 'zipcFileGets()' - Read a line from a file.
 *
 * The trailing newline is omitted from the string for the line.
 */

int                                     /* O - 0 on success, -1 on error */
zipcFileGets(zipc_file_t *zf,           /* I - ZIP container file */
             char        *line,         /* I - Line string buffer */
             size_t      linesize)      /* I - Size of buffer */
{
  char  ch,                             /* Current character */
        *lineptr,                       /* Pointer into buffer */
        *lineend;                       /* Pointer to end of buffer */


 /*
  * Read the line...
  */

  lineptr = line;
  lineend = line + linesize - 1;

  while ((ch = zipc_read_char(zf)) > 0)
  {
    if (ch == '\n')
      break;
    else if (ch == '\r')
      continue;
    else if (lineptr < lineend)
      *lineptr++ = ch;
  }

  *lineptr = '\0';

  if (lineptr == line)
    return (-1);
  else
    return (0);
}
#endif /* !ZIPC_ONLY_WRITE */


#ifndef ZIPC_ONLY_READ
/*
 * 'zipcFilePrintf()' - Write a formatted string to a file.
 *
 * The "zf" value is the one returned by the @link zipcCreateFile@ function
 * used to create the ZIP container file.
 *
 * The "format" value is a standard printf format string and is followed by
 * any arguments needed by the string.
 */

int					/* O - 0 on success, -1 on error */
zipcFilePrintf(zipc_file_t *zf,		/* I - ZIP container file */
	       const char  *format,	/* I - Printf-style format string */
	       ...)			/* I - Additional arguments as needed */
{
  char		buffer[8192];		/* Format buffer */
  va_list	ap;			/* Pointer to additional arguments */


  if (zf->zc->mode != 'w')
  {
    zf->zc->error = "Not opened for writing.";
    return (-1);
  }

  va_start(ap, format);
  if (vsnprintf(buffer, sizeof(buffer), format, ap) < 0)
  {
    zf->zc->error = strerror(errno);
    va_end(ap);
    return (-1);
  }
  va_end(ap);

  zf->internal_attrs = ZIPC_INTERNAL_TEXT;

  return (zipcFileWrite(zf, buffer, strlen(buffer)));
}


/*
 * 'zipcFilePuts()' - Write a string to a file.
 *
 * The "zf" value is the one returned by the @link zipcCreateFile@ function
 * used to create the ZIP container file.
 *
 * The "s" value is literal string that is written to the file.  No newline is
 * added.
 */

int					/* O - 0 on success, -1 on error */
zipcFilePuts(zipc_file_t *zf,		/* I - ZIP container file */
               const char  *s)		/* I - String to write */
{
  if (zf->zc->mode != 'w')
  {
    zf->zc->error = "Not opened for writing.";
    return (-1);
  }

  zf->internal_attrs = ZIPC_INTERNAL_TEXT;

  return (zipcFileWrite(zf, s, strlen(s)));
}
#endif /* !ZIPC_ONLY_READ */


#ifndef ZIPC_ONLY_WRITE
/*
 * 'zipcFileRead()' - Read data from a ZIP container file.
 *
 * The "zf" value is the one returned by the @link zipcOpenFile@ function used
 * to open the ZIP container file.
 *
 * The "data" value points to a buffer to hold the bytes that are read.
 */

ssize_t                                 /* O - Number of bytes read or -1 on error */
zipcFileRead(zipc_file_t *zf,           /* I - ZIP container file */
             void        *data,         /* I - Read buffer */
             size_t      bytes)         /* I - Maximum number of bytes to read */
{
  ssize_t       rbytes;                 /* Bytes read */
  zipc_t        *zc = zf->zc;           /* ZIP container */


  if (zc->mode != 'r')
  {
    zc->error = "Not opened for reading.";
    return (-1);
  }

  if ((zf->uncompressed_pos + bytes) > zf->uncompressed_size)
    bytes = zf->uncompressed_size - zf->uncompressed_pos;

  if (zc->readptr && zc->readptr < zc->readend)
  {
    rbytes = zc->readend - zc->readptr;
    if (rbytes > bytes)
      rbytes = bytes;

    memcpy(data, zc->readptr, rbytes);
    zc->readptr += rbytes;
  }
  else
    rbytes = zipc_read_file(zf, data, bytes);

  if (rbytes > 0)
    zf->uncompressed_pos += rbytes;

  return (rbytes);
}
#endif /* !ZIPC_ONLY_WRITE */


#ifndef ZIPC_ONLY_READ
/*
 * 'zipcFileWrite()' - Write data to a ZIP container file.
 *
 * The "zf" value is the one returned by the @link zipcCreateFile@ function
 * used to create the ZIP container file.
 *
 * The "data" value points to the bytes to be written.
 *
 * The "bytes" value specifies the number of bytes to write.
 */

int					/* O - 0 on success, -1 on error */
zipcFileWrite(zipc_file_t *zf,		/* I - ZIP container file */
	      const void  *data,	/* I - Data to write */
	      size_t      bytes)	/* I - Number of bytes to write */
{
  int		status = 0;		/* Return status */
  zipc_t	*zc = zf->zc;		/* ZIP container */


  if (zc->mode != 'w')
  {
    zc->error = "Not opened for writing.";
    return (-1);
  }

  zf->uncompressed_size += bytes;
  zf->crc32             = crc32(zf->crc32, (const Bytef *)data, (unsigned)bytes);

  if (zf->method == ZIPC_COMP_STORE)
  {
   /*
    * Store the contents as literal data...
    */

    status = zipc_write(zc, data, bytes);
    zf->compressed_size += bytes;
  }
  else
  {
   /*
    * Deflate (compress) the contents...
    */

    int	zstatus;			/* Deflate status */

    zc->stream.next_in  = (Bytef *)data;
    zc->stream.avail_in = (unsigned)bytes;

    while (zc->stream.avail_in > 0)
    {
      if (zc->stream.avail_out < (int)(sizeof(zc->buffer) / 8))
      {
	status |= zipc_write(zf->zc, zc->buffer, (size_t)((char *)zc->stream.next_out - zc->buffer));
	zf->compressed_size += (size_t)((char *)zc->stream.next_out - zc->buffer);

	zc->stream.next_out  = (Bytef *)zc->buffer;
	zc->stream.avail_out = sizeof(zc->buffer);
      }

      zstatus = deflate(&zc->stream, Z_NO_FLUSH);

      if (zstatus < Z_OK && zstatus != Z_BUF_ERROR)
      {
        zc->error = zipc_zlib_status(zstatus);
        status = -1;
        break;
      }
    }
  }

  return (status);
}


#ifndef ZIPC_ONLY_WRITE
/*
 * 'zipcFileXMLGets()' - Read an XML fragment from a file.
 *
 * An XML fragment is an element like "<element attr='value'>", "some text", and
 * "</element>".
 */

int                                     /* O - 0 on success, -1 on error */
zipcFileXMLGets(zipc_file_t *zf,        /* I - ZIP container file */
                char        *fragment,  /* I - Fragment string buffer */
                size_t      fragsize)   /* I - Size of buffer */
{
  char  ch,                             /* Current character */
        *fragptr,                       /* Pointer into buffer */
        *fragend;                       /* Pointer to end of buffer */


 /*
  * Read the fragment...
  */

  fragptr = fragment;
  fragend = fragment + fragsize - 1;

  if ((ch = zipc_read_char(zf)) <= 0)
  {
    *fragment = '\0';
    return (-1);
  }

  *fragptr++ = ch;

  if (ch == '<')
  {
   /*
    * Read element...
    */

    while ((ch = zipc_read_char(zf)) > 0)
    {
      if (fragptr < fragend)
        *fragptr++ = ch;

      if (ch == '>')
        break;
      else if (ch == '\"' || ch == '\'')
      {
       /*
        * Read quoted string...
        */

        char quote = ch;

        while ((ch = zipc_read_char(zf)) > 0)
        {
          if (fragptr < fragend)
            *fragptr++ = ch;

          if (ch == quote)
            break;
        }
      }
    }

    *fragptr++ = '\0';
  }
  else
  {
   /*
    * Read text...
    */

    while ((ch = zipc_read_char(zf)) > 0)
    {
      if (ch == '<')
      {
        zf->zc->readptr --;
        break;
      }
      else if (fragptr < fragend)
        *fragptr++ = ch;
    }

    *fragptr++ = '\0';

    zipc_xml_unescape(fragment);
  }

  return (0);
}
#endif /* !ZIPC_ONLY_WRITE */


/*
 * 'zipcFileXMLPrintf()' - Write a formatted XML string to a file.
 *
 * The "zf" value is the one returned by the @link zipcCreateFile@ function
 * used to create the ZIP container file.
 *
 * The "format" value is a printf-style format string supporting "%d", "%f",
 * "%s", and "%%" and is followed by any arguments needed by the string.
 * Strings ("%s") are escaped as needed.
 */

int					/* O - 0 on success, -1 on error */
zipcFileXMLPrintf(
    zipc_file_t *zf,			/* I - ZIP container file */
    const char  *format,		/* I - Printf-style format string */
    ...)				/* I - Additional arguments as needed */
{
  int		status = 0;		/* Return status */
  va_list	ap;			/* Pointer to additional arguments */
  char		buffer[65536],		/* Buffer */
		*bufend = buffer + sizeof(buffer) - 6,
					/* End of buffer less "&quot;" */
		*bufptr = buffer;	/* Pointer into buffer */
  const char	*s;			/* String pointer */
  int		d;			/* Integer */
  double        f;                      /* Real/floating point number */


  if (zf->zc->mode != 'w')
  {
    zf->zc->error = "Not opened for writing.";
    return (-1);
  }

  va_start(ap, format);

  while (*format && bufptr < bufend)
  {
    if (*format == '%')
    {
      format ++;

      switch (*format)
      {
        case '%' : /* Substitute a single % */
            format ++;

            *bufptr++ = '%';
            break;

        case 'd' : /* Substitute a single integer */
            format ++;

            d = va_arg(ap, int);
            snprintf(bufptr, bufend - bufptr, "%d", d);
            bufptr += strlen(bufptr);
            break;

        case 'f' : /* Substitute a single real number */
            format ++;

            f = va_arg(ap, double);
            snprintf(bufptr, bufend - bufptr, "%f", f);
            bufptr += strlen(bufptr);
            break;

        case 's' : /* Substitute a single string */
            format ++;

            s = va_arg(ap, const char *);
            while (*s && bufptr < bufend)
            {
              switch (*s)
              {
                case '&' : /* &amp; */
                    *bufptr++ = '&';
                    *bufptr++ = 'a';
                    *bufptr++ = 'm';
                    *bufptr++ = 'p';
                    *bufptr++ = ';';
                    break;
                case '<' : /* &lt; */
                    *bufptr++ = '&';
                    *bufptr++ = 'l';
                    *bufptr++ = 't';
                    *bufptr++ = ';';
                    break;
                case '>' : /* &gt; */
                    *bufptr++ = '&';
                    *bufptr++ = 'g';
                    *bufptr++ = 't';
                    *bufptr++ = ';';
                    break;
                case '\"' : /* &quot; */
                    *bufptr++ = '&';
                    *bufptr++ = 'q';
                    *bufptr++ = 'u';
                    *bufptr++ = 'o';
                    *bufptr++ = 't';
                    *bufptr++ = ';';
                    break;
                default :
                    *bufptr++ = *s;
                    break;
              }

              s ++;
            }

            if (*s)
            {
	      format += strlen(format);
              status = -1;
              zf->zc->error = "Not enough memory to hold XML string.";
            }
            break;

        default : /* Something else we don't support... */
            format += strlen(format);
            status = -1;
            zf->zc->error = "Unsupported format character - only %%, %d, %f, and %s are supported.";
            break;
      }
    }
    else
      *bufptr++ = *format++;
  }

  va_end(ap);

  if (*format)
  {
    status = -1;
    zf->zc->error = "Not enough memory to hold XML string.";
  }

  if (bufptr > buffer)
  {
    zf->internal_attrs = ZIPC_INTERNAL_TEXT;

    status |= zipcFileWrite(zf, buffer, (size_t)(bufptr - buffer));
  }

  return (status);
}
#endif /* !ZIPC_ONLY_READ */


/*
 * 'zipcOpen()' - Open a ZIP container.
 *
 * Currently the only supported "mode" values are "r" to read a ZIP container
 * and "w" to write a ZIP container.
 */

zipc_t *				/* O - ZIP container */
zipcOpen(const char *filename,		/* I - Filename of container */
         const char *mode)		/* I - Open mode ("w") */
{
  zipc_t	*zc;			/* ZIP container */


 /*
  * Only support read and write mode for now...
  */

#ifdef ZIPC_ONLY_READ
  if (strcmp(mode, "r"))
#elif defined(ZIPC_ONLY_WRITE)
  if (strcmp(mode, "w"))
#else
  if (strcmp(mode, "r") && strcmp(mode, "w"))
#endif /* ZIPC_ONLY_READ */
  {
    errno = EINVAL;
    return (NULL);
  }

 /*
  * Allocate memory...
  */

  if ((zc = calloc(1, sizeof(zipc_t))) == NULL)
    return (NULL);

  zc->mode = *mode;

#ifndef ZIPC_ONLY_WRITE
  if (zc->mode == 'r')
  {
   /*
    * Open for reading...
    */

    zipc_file_t *zf;                    /* Current file */
    long        offset;                 /* Current offset */
    unsigned    signature,              /* Header signature */
                version,                /* Version needed to extract */
                flags,                  /* General purpose flags */
                method,                 /* Compression method */
                modtime,                /* Last modification date/time */
                crc32,                  /* CRC-32 of file data */
                compressed_size,        /* Compressed file size */
                uncompressed_size,      /* Uncompressed file size */
                cfile_len,              /* Length of container filename */
                extra_field_len;        /* Length of extra data */
//  unsigned      internal_attrs,         /* Internal attributes */
//                external_attrs;         /* External attributes */
    char        cfile[256];             /* Container filename from header */
    int         done = 0;               /* Done reading? */


   /*
    * Open the container file...
    */

    if ((zc->fp = fopen(filename, "rb")) == NULL)
    {
      free(zc);
      return (NULL);
    }

   /*
    * Read all of the file headers...
    */

    while (!done && (signature = zipc_read_u32(zc)) != 0xffffffff)
    {
      switch (signature)
      {
        case ZIPC_LOCAL_HEADER :
            offset            = ftell(zc->fp) - 4;
            version           = zipc_read_u16(zc);
            flags             = zipc_read_u16(zc);
            method            = zipc_read_u16(zc);
            modtime           = zipc_read_u32(zc);
            crc32             = zipc_read_u32(zc);
            compressed_size   = zipc_read_u32(zc);
            uncompressed_size = zipc_read_u32(zc);
            cfile_len         = zipc_read_u16(zc);
            extra_field_len   = zipc_read_u16(zc);

            if (cfile_len > (sizeof(cfile) - 1))
            {
              zc->error = "Filename too long.";
              done = 1;
              break;
            }

            if (zipc_read(zc, cfile, cfile_len) < cfile_len)
            {
              done = 1;
              break;
            }

            cfile[cfile_len] = '\0';

            if (extra_field_len > 0)
              fseek(zc->fp, extra_field_len, SEEK_CUR);

            if (zc->num_files >= zc->alloc_files)
            {
              zc->alloc_files += 10;

              if (!zc->files)
                zf = malloc(zc->alloc_files * sizeof(zipc_file_t));
              else
                zf = realloc(zc->files, zc->alloc_files * sizeof(zipc_file_t));

              if (!zf)
              {
                zc->error = strerror(errno);
                return (NULL);
              }

              zc->files = zf;
            }

            zf = zc->files + zc->num_files;
            zc->num_files ++;

            memset(zf, 0, sizeof(zipc_file_t));

            strncpy(zf->filename, cfile, sizeof(zf->filename) - 1);
            zf->zc                = zc;
            zf->flags             = flags;
            zf->method            = method;
            zf->crc32             = crc32;
            zf->compressed_size   = compressed_size;
            zf->uncompressed_size = uncompressed_size;
            zf->offset            = (size_t)offset;
            zf->local_size        = (size_t)(ftell(zc->fp) - offset);

            fseek(zc->fp, compressed_size, SEEK_CUR);
            break;

       default :
           snprintf(zc->error_msg, sizeof(zc->error_msg), "Unknown ZIP signature 0x%08x.", signature);
           zc->error = zc->error_msg;

           fprintf(stderr, "zipcOpen: %s\n", zc->error_msg);

       case ZIPC_DIR_HEADER :
       case ZIPC_END_RECORD :
           done = 1;
           break;
      }
    }
  }
#endif /* !ZIPC_ONLY_WRITE */

#ifndef ZIPC_ONLY_READ
#  ifndef ZIPC_ONLY_WRITE
  else
#  endif /* !ZIPC_ONLY_WRITE */
  if (zc->mode == 'w')
  {
   /*
    * Open for writing...
    */

    time_t	curtime;		/* Current timestamp */
    struct tm	*curdate;		/* Current date/time */

   /*
    * Open the container file...
    */

    if ((zc->fp = fopen(filename, "w+b")) == NULL)
    {
      free(zc);
      return (NULL);
    }

   /*
    * Get the current date/time and convert it to the packed MS-DOS format:
    *
    *     Bits    Description
    *     ------  -----------
    *     0-4     Seconds / 2 (0-29)
    *     5-10    Minute (0-59)
    *     11-15   Hour (0-23)
    *     16-20   Day (1-31)
    *     21-24   Month (1-12)
    *     25-31   Years since 1980
    */

    curtime = time(NULL);
    curdate = localtime(&curtime);

    zc->modtime = (unsigned)(curdate->tm_sec / 2) |
                  ((unsigned)curdate->tm_min << 5) |
                  ((unsigned)curdate->tm_hour << 11) |
                  ((unsigned)curdate->tm_mday << 16) |
                  ((unsigned)(curdate->tm_mon + 1) << 21) |
                  ((unsigned)(curdate->tm_year - 80) << 25);
  }
#endif /* !ZIPC_ONLY_READ */

  return (zc);
}


#ifndef ZIPC_ONLY_WRITE
/*
 * 'zipcOpenFile()' - Open a file in a ZIP container.
 */

zipc_file_t *                           /* O - File */
zipcOpenFile(zipc_t     *zc,            /* I - ZIP container */
             const char *filename)      /* I - Name of file */
{
  size_t        count;                  /* Number of files */
  zipc_file_t   *current;               /* Current file */


  for (count = zc->num_files, current = zc->files; count > 0; count --, current ++)
  {
    if (!strcmp(filename, current->filename))
    {
      fseek(zc->fp, current->offset + current->local_size, SEEK_SET);

      if (current->method == ZIPC_COMP_DEFLATE)
      {
        zc->stream.zalloc = (alloc_func)0;
        zc->stream.zfree  = (free_func)0;
        zc->stream.opaque = (voidpf)0;

        inflateInit2(&zc->stream, -15);

        zc->stream.next_in  = (Bytef *)zc->buffer;
        zc->stream.avail_in = 0;
      }

      current->compressed_pos   = 0;
      current->uncompressed_pos = 0;

      zc->readptr = NULL;

      return (current);
    }
  }

 /*
  * If we get here we didn't find the file...
  */

  zc->error = strerror(ENOENT);

  return (NULL);
}


/*
 * 'zipcXMLGetAttribute()' - Get the value of an attribute in an XML fragment.
 *
 * "element" is an XML element fragment returned by @link zipcFileXMLGets@.
 */

const char *                            /* O - Attribute value string or @code NULL@ */
zipcXMLGetAttribute(
    const char *element,                /* I - XML element fragment */
    const char *attrname,               /* I - Attribute name */
    char       *buffer,                 /* I - Value buffer */
    size_t     bufsize)                 /* I - Size of value buffer */
{
  size_t        attrlen;                /* Length of attribute name */
  char          quote,                  /* Quote character to skip */
                *bufptr,                /* Pointer into buffer */
                *bufend;                /* End of buffer */


 /*
  * Make sure we have an element fragment...
  */

  if (*element != '<' || element[1] == '/')
  {
    *buffer = '\0';

    return (NULL);
  }

 /*
  * Skip the element name...
  */

  while (*element && !isspace(*element & 255))
    element ++;

 /*
  * Find the named attribute...
  */

  attrlen = strlen(attrname);

  while (*element && *element != '>')
  {
   /*
    * Skip leading whitespace...
    */

    while (*element && isspace(*element & 255))
      element ++;

   /*
    * See if we have the attribute...
    */

    if (!strncmp(element, attrname, attrlen) && element[attrlen] == '=')
    {
     /*
      * Yes, copy the value...
      */

      element += attrlen + 1;

      quote = *element++;

      if (quote != '\"' && quote != '\'')
        break;                          /* Bad value - must be quoted */

      for (bufptr = buffer, bufend = buffer + bufsize - 1; *element && *element != quote; element ++)
      {
        if (bufptr < bufend)
          *bufptr++ = *element;
      }

      *bufptr = '\0';

      zipc_xml_unescape(buffer);

      return (buffer);
    }
    else
    {
     /*
      * Not a matching attribute, skip it...
      */

      if ((element = strchr(element, '=')) == NULL)
        break;

      element ++;
      quote = *element++;

      if (quote != '\"' && quote != '\'')
        break;                          /* Bad value - must be quoted */

      if ((element = strchr(element, quote)) == NULL)
        break;

      element ++;
    }
  }

 /*
  * If we get this far we didn't find it...
  */

  *buffer = '\0';

  return (NULL);
}
#endif /* !ZIPC_ONLY_WRITE */


#ifndef ZIPC_ONLY_READ
/*
 * 'zipc_add_file()' - Add a file to the ZIP container.
 */

static zipc_file_t *			/* O - New file */
zipc_add_file(zipc_t     *zc,		/* I - ZIP container */
              const char *filename,	/* I - Name of file in container */
              int        compression)	/* I - 0 = uncompressed, 1 = compressed */
{
  zipc_file_t	*temp;			/* File(s) */


  if (zc->num_files >= zc->alloc_files)
  {
   /*
    * Allocate more files...
    */

    zc->alloc_files += 10;

    if (!zc->files)
      temp = malloc(zc->alloc_files * sizeof(zipc_file_t));
    else
      temp = realloc(zc->files, zc->alloc_files * sizeof(zipc_file_t));

    if (!temp)
    {
      zc->error = strerror(errno);
      return (NULL);
    }

    zc->files = temp;
  }

  temp = zc->files + zc->num_files;
  zc->num_files ++;

  memset(temp, 0, sizeof(zipc_file_t));

  strncpy(temp->filename, filename, sizeof(temp->filename) - 1);

  temp->zc     = zc;
  temp->crc32  = crc32(0, NULL, 0);
  temp->offset = (size_t)ftell(zc->fp);

  if (compression)
  {
    temp->flags  = ZIPC_FLAG_CMAX;
    temp->method = ZIPC_COMP_DEFLATE;

    zc->stream.zalloc = (alloc_func)0;
    zc->stream.zfree  = (free_func)0;
    zc->stream.opaque = (voidpf)0;

    deflateInit2(&zc->stream, Z_BEST_COMPRESSION, Z_DEFLATED, -15, 8, Z_DEFAULT_STRATEGY);

    zc->stream.next_out  = (Bytef *)zc->buffer;
    zc->stream.avail_out = sizeof(zc->buffer);
  }

  return (temp);
}
#endif /* !ZIPC_ONLY_READ */


#ifndef ZIPC_ONLY_WRITE
/*
 * 'zipc_read()' - Read from the ZIP container and capture any error.
 */

static ssize_t                          /* O - Bytes read or -1 on error */
zipc_read(zipc_t *zc,                   /* I - ZIP container */
          void   *buffer,               /* I - Read buffer */
          size_t bytes)                 /* I - Maximum bytes to read */
{
  ssize_t       rbytes;                 /* Bytes read */


  if ((rbytes = (ssize_t)fread(buffer, 1, bytes, zc->fp)) > 0)
    return (rbytes);

  zc->error = strerror(ferror(zc->fp));

  return (-1);
}


/*
 * 'zipc_read_char()' - Read a character from a ZIP container file.
 */

static int                              /* O - Character from file or -1 on EOF */
zipc_read_char(zipc_file_t *zf)         /* I - ZIP container file */
{
  zipc_t        *zc = zf->zc;           /* ZIP container */


  if (zc->readptr >= zc->readend || !zc->readptr)
  {
    ssize_t     bytes;                  /* Bytes read */

    if (!zc->readbuffer)
      zc->readbuffer = malloc(ZIPC_READ_SIZE);

    if ((bytes = zipc_read_file(zf, zc->readbuffer, ZIPC_READ_SIZE)) <= 0)
      return (-1);

    zc->readend = zc->readbuffer + bytes;
    zc->readptr = zc->readbuffer;
  }

  zf->uncompressed_pos ++;

  return (*zc->readptr++);
}


/*
 * 'zipc_read_file()' - Read data from a ZIP container file.
 */

static ssize_t                          /* O - Number of bytes read or -1 on error */
zipc_read_file(zipc_file_t *zf,         /* I - ZIP container file */
               void        *data,       /* I - Read buffer */
               size_t      bytes)       /* I - Maximum number of bytes to read */
{
  ssize_t       rbytes;                 /* Bytes read */
  zipc_t        *zc = zf->zc;           /* ZIP container */


  if ((zf->uncompressed_pos + bytes) > zf->uncompressed_size)
    bytes = zf->uncompressed_size - zf->uncompressed_pos;

  if (zf->method == ZIPC_COMP_STORE)
  {
   /*
    * Read literal data...
    */

    rbytes = zipc_read(zc, data, bytes);
  }
  else
  {
   /*
    * Read deflated (compressed) data...
    */

    int	zstatus;			/* Deflate status */

    zc->stream.next_out  = (Bytef *)data;
    zc->stream.avail_out = (unsigned)bytes;

    do
    {
      if (zc->stream.avail_in == 0)
      {
        size_t  cbytes = zf->compressed_size - zf->compressed_pos;
                                        /* Compressed bytes left */

        if (cbytes > sizeof(zc->buffer))
          cbytes = sizeof(zc->buffer);

        if ((rbytes = zipc_read(zc, zc->buffer, cbytes)) < 0)
        {
          zc->error = strerror(errno);
          return (-1);
        }

        zc->stream.next_in  = (Bytef *)zc->buffer;
        zc->stream.avail_in = (unsigned)rbytes;
      }

      zstatus = inflate(&zc->stream, Z_NO_FLUSH);

      if (zstatus < Z_OK && zstatus != Z_BUF_ERROR)
      {
        zc->error = zipc_zlib_status(zstatus);
        return (-1);
      }
    }
    while (zc->stream.avail_out > 0 && zf->compressed_pos < zf->compressed_size);

    rbytes = bytes - zc->stream.avail_out;
  }

  return (rbytes);
}


/*
 * 'zipc_read_u16()' - Read a 16-bit unsigned integer from the ZIP container.
 */

static unsigned                         /* O - Integer or 0xffff on error */
zipc_read_u16(zipc_t *zc)               /* I - ZIP container */
{
  unsigned char	buffer[2];		/* Buffer */


  if (zipc_read(zc, buffer, sizeof(buffer)) != sizeof(buffer))
    return (0xffff);

  return ((buffer[1] << 8) | buffer[0]);
}


/*
 * 'zipc_read_u32()' - Read a 32-bit unsigned integer from the ZIP container.
 */

static unsigned                         /* O - Integer or 0xffffffff on error */
zipc_read_u32(zipc_t *zc)               /* I - ZIP container */
{
  unsigned char	buffer[4];		/* Buffer */


  if (zipc_read(zc, buffer, sizeof(buffer)) != sizeof(buffer))
    return (0xffff);

  return ((buffer[3] << 24) | (buffer[2] << 16) | (buffer[1] << 8) | buffer[0]);
}
#endif /* !ZIPC_ONLY_WRITE */


#ifndef ZIPC_ONLY_READ
/*
 * 'zipc_write()' - Write bytes to a ZIP container.
 */

static int				/* I - 0 on success, -1 on error */
zipc_write(zipc_t     *zc,		/* I - ZIP container */
           const void *buffer,		/* I - Buffer to write */
           size_t     bytes)		/* I - Number of bytes */
{
  if (fwrite(buffer, bytes, 1, zc->fp))
    return (0);

  zc->error = strerror(ferror(zc->fp));

  return (-1);
}


/*
 * 'zipc_write_dir_header()' - Write a central directory file header.
 */

static int				/* I - 0 on success, -1 on error */
zipc_write_dir_header(
    zipc_t      *zc,			/* I - ZIP container */
    zipc_file_t *zf)			/* I - ZIP container file */
{
  int		status = 0;		/* Return status */
  size_t	filenamelen = strlen(zf->filename);
					/* Length of filename */

  status |= zipc_write_u32(zc, ZIPC_DIR_HEADER);
  status |= zipc_write_u16(zc, ZIPC_MADE_BY);
  status |= zipc_write_u16(zc, zf->external_attrs == ZIPC_EXTERNAL_DIR ? ZIPC_DIR_VERSION : ZIPC_FILE_VERSION);
  status |= zipc_write_u16(zc, zf->flags);
  status |= zipc_write_u16(zc, zf->method);
  status |= zipc_write_u32(zc, zc->modtime);
  status |= zipc_write_u32(zc, zf->crc32);
  status |= zipc_write_u32(zc, (unsigned)zf->compressed_size);
  status |= zipc_write_u32(zc, (unsigned)zf->uncompressed_size);
  status |= zipc_write_u16(zc, (unsigned)filenamelen);
  status |= zipc_write_u16(zc, 0); /* extra field length */
  status |= zipc_write_u16(zc, 0); /* comment length */
  status |= zipc_write_u16(zc, 0); /* disk number start */
  status |= zipc_write_u16(zc, zf->internal_attrs);
  status |= zipc_write_u32(zc, zf->external_attrs);
  status |= zipc_write_u32(zc, (unsigned)zf->offset);
  status |= zipc_write(zc, zf->filename, filenamelen);

  return (status);
}


/*
 * 'zipc_write_local_header()' - Write a local file header.
 */

static int				/* I - 0 on success, -1 on error */
zipc_write_local_header(
    zipc_t      *zc,			/* I - ZIP container */
    zipc_file_t *zf)			/* I - ZIP container file */
{
  int		status = 0;		/* Return status */
  size_t	filenamelen = strlen(zf->filename);
					/* Length of filename */

  status |= zipc_write_u32(zc, ZIPC_LOCAL_HEADER);
  status |= zipc_write_u16(zc, zf->external_attrs == ZIPC_EXTERNAL_DIR ? ZIPC_DIR_VERSION : ZIPC_FILE_VERSION);
  status |= zipc_write_u16(zc, zf->flags & ZIPC_FLAG_MASK);
  status |= zipc_write_u16(zc, zf->method);
  status |= zipc_write_u32(zc, zc->modtime);
  status |= zipc_write_u32(zc, zf->uncompressed_size == 0 ? 0 : zf->crc32);
  status |= zipc_write_u32(zc, (unsigned)zf->compressed_size);
  status |= zipc_write_u32(zc, (unsigned)zf->uncompressed_size);
  status |= zipc_write_u16(zc, (unsigned)filenamelen);
  status |= zipc_write_u16(zc, 0); /* extra field length */
  status |= zipc_write(zc, zf->filename, filenamelen);

  return (status);
}


/*
 * 'zipc_write_local_trailer()' - Write a local file trailer.
 */

static int				/* I - 0 on success, -1 on error */
zipc_write_local_trailer(
    zipc_t      *zc,			/* I - ZIP container */
    zipc_file_t *zf)			/* I - ZIP container file */
{
  int	status = 0;			/* Return status */


  if (zf->flags & ZIPC_FLAG_STREAMED)
  {
   /*
    * Update the CRC-32, compressed size, and uncompressed size fields...
    */

    fseek(zc->fp, (long)(zf->offset + 14), SEEK_SET);

    status |= zipc_write_u32(zc, zf->crc32);
    status |= zipc_write_u32(zc, (unsigned)zf->compressed_size);
    status |= zipc_write_u32(zc, (unsigned)zf->uncompressed_size);

    fseek(zc->fp, 0, SEEK_END);

    zf->flags &= ZIPC_FLAG_MASK;
  }

  return (status);
}


/*
 * 'zipc_write_u16()' - Write a 16-bit unsigned integer.
 */

static int				/* I - 0 on success, -1 on error */
zipc_write_u16(zipc_t   *zc,		/* I - ZIP container */
               unsigned value)		/* I - Value to write */
{
  unsigned char	buffer[2];		/* Buffer */


  buffer[0] = (unsigned char)value;
  buffer[1] = (unsigned char)(value >> 8);

  return (zipc_write(zc, buffer, sizeof(buffer)));
}


/*
 * 'zipc_write_u32()' - Write a 32-bit unsigned integer.
 */

static int				/* I - 0 on success, -1 on error */
zipc_write_u32(zipc_t   *zc,		/* I - ZIP container */
               unsigned value)		/* I - Value to write */
{
  unsigned char	buffer[4];		/* Buffer */


  buffer[0] = (unsigned char)value;
  buffer[1] = (unsigned char)(value >> 8);
  buffer[2] = (unsigned char)(value >> 16);
  buffer[3] = (unsigned char)(value >> 24);

  return (zipc_write(zc, buffer, sizeof(buffer)));
}
#endif /* !ZIPC_ONLY_READ */


#ifndef ZIPC_ONLY_WRITE
/*
 * 'zipc_xml_unescape()' - Replace &foo; with corresponding characters.
 */

static void
zipc_xml_unescape(char *buffer)         /* I - Buffer */
{
  char  *inptr,                         /* Current input pointer */
        *outptr;                        /* Current output pointer */


 /*
  * See if there are any escaped characters to work with...
  */

  if ((inptr = strchr(buffer, '&')) == NULL)
    return;                             /* Nope */

  for (outptr = inptr; *inptr;)
  {
    if (*inptr == '&' && strchr(inptr + 1, ';'))
    {
     /*
      * Figure out what kind of escaped character we have...
      */

      inptr ++;
      if (!strncmp(inptr, "amp;", 4))
      {
        inptr += 4;
        *outptr++ = '&';
      }
      else if (!strncmp(inptr, "lt;", 3))
      {
        inptr += 3;
        *outptr++ = '<';
      }
      else if (!strncmp(inptr, "gt;", 3))
      {
        inptr += 3;
        *outptr++ = '>';
      }
      else if (!strncmp(inptr, "quot;", 5))
      {
        inptr += 5;
        *outptr++ = '\"';
      }
      else if (!strncmp(inptr, "apos;", 5))
      {
        inptr += 5;
        *outptr++ = '\'';
      }
      else if (*inptr == '#')
      {
       /*
        * Numeric, copy character over as UTF-8...
        */

        int ch;                         /* Numeric character value */

        inptr ++;
        if (*inptr == 'x')
          ch = (int)strtol(inptr, NULL, 16);
        else
          ch = (int)strtol(inptr, NULL, 10);

        if (ch < 0x80)
        {
         /*
          * US ASCII
          */

          *outptr++ = ch;
        }
        else if (ch < 0x800)
        {
         /*
          * Two-byte UTF-8
          */

          *outptr++ = 0xc0 | (ch >> 6);
          *outptr++ = 0x80 | (ch & 0x3f);
        }
        else if (ch < 0x10000)
        {
         /*
          * Three-byte UTF-8
          */

          *outptr++ = 0xe0 | (ch >> 12);
          *outptr++ = 0x80 | ((ch >> 6) & 0x3f);
          *outptr++ = 0x80 | (ch & 0x3f);
        }
        else
        {
         /*
          * Four-byte UTF-8
          */

          *outptr++ = 0xf0 | (ch >> 18);
          *outptr++ = 0x80 | ((ch >> 12) & 0x3f);
          *outptr++ = 0x80 | ((ch >> 6) & 0x3f);
          *outptr++ = 0x80 | (ch & 0x3f);
        }

        inptr = strchr(inptr, ';') + 1;
      }
      else
      {
       /*
        * Something else not supported by XML...
        */

        *outptr++ = '&';
      }
    }
    else
    {
     /*
      * Copy literal...
      */

      *outptr++ = *inptr++;
    }
  }

  *outptr = '\0';
}
#endif /* !ZIPC_ONLY_WRITE */


/*
 * 'zipc_zlib_status()' - Return a string corresponding to the given ZLIB status.
 */

static const char *                     /* O - Error string */
zipc_zlib_status(int zstatus)           /* I - Status */
{
  switch (zstatus)
  {
    case Z_ERRNO :
        return (strerror(errno));

    case Z_OK :
        return ("OK");

    case Z_STREAM_END :
        return ("End of stream.");

    case Z_NEED_DICT :
        return ("Need dictionary.");

    case Z_STREAM_ERROR :
        return ("Error in stream.");

    case Z_DATA_ERROR :
        return ("Error in data");

    case Z_MEM_ERROR :
        return ("Unable to allocate memory.");

    case Z_BUF_ERROR :
        return ("Unable to fill buffer.");

    case Z_VERSION_ERROR :
        return ("Version mismatch.");

    default :
        return ("Unknown status");
  }
}
