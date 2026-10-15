# Mini-XML basic sample

## Overview

This sample parses an XML document stored in memory, looks up and modifies the
`value` element, adds a `status` element, and prints the serialized XML.

## Building and running

From a west workspace containing this module, run:

```console
west build -b qemu_cortex_m3 mxml/zephyr/samples/basic
west build -t run
```

## Expected output

```console
Mini-XML parsed value: 42
Modified XML: <settings><name>zephyr</name><value>84</value><status>updated</status></settings>
```
