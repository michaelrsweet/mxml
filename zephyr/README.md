# Mini-XML Zephyr (as external module)

## Zephyr integration: Getting started

Add this project as an external module by appending it to your `west.yml`
project file:

```yaml
manifest:
  remotes:
    - ...
    - name: michaelrsweet
      url-base: https://github.com/michaelrsweet

  projects:
    - ...
    - name: mxml
      remote: michaelrsweet
      revision: master
      path: modules/lib/mxml
```

Then update your Zephyr workspace:

```console
west update
```

## How to use Mini-XML in your Zephyr application

Enable the library in the application configuration:

```kconfig
CONFIG_MXML=y
```

Applications can then include the public API with `#include <mxml.h>`.
Enabling Mini-XML also enables the libc allocator with a default 4-KiB malloc
arena. Applications can adjust it with
`CONFIG_COMMON_LIBC_MALLOC_ARENA_SIZE` according to their XML workload.

When `CONFIG_POSIX_THREADS=y`, Mini-XML keeps its string allocator callbacks
in POSIX thread-local storage. Without POSIX threads, those callbacks are
shared globally.

## File input/output

To use `mxmlLoadFilename()`, `mxmlSaveFilename()`, or the `FILE`-based APIs,
enable a supported Zephyr file system and the optional integration:

```kconfig
CONFIG_FILE_SYSTEM=y
CONFIG_FILE_SYSTEM_LITTLEFS=y
CONFIG_POSIX_API=y
CONFIG_MXML_FILE_IO=y
```

FAT can be selected with `CONFIG_FAT_FILESYSTEM_ELM=y` instead of LittleFS.
The application must configure and mount the selected backend before accessing
XML files.

A sample application is located in `zephyr/samples/basic` and the Zephyr test
suite is located in `zephyr/tests` (wrapper to the Mini-XML test suite).
