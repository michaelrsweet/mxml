#
# Version extraction for Mini-XML on Zephyr.
#
# Copyright © 2026 by Romain Pelletant.
#
# Licensed under Apache License v2.0.  See the file "LICENSE" for more
# information.
#
# SPDX-License-Identifier: Apache-2.0
#

if(NOT DEFINED MXML_ROOT)
  set(MXML_ROOT ${CMAKE_CURRENT_LIST_DIR}/..)
endif()

file(STRINGS ${MXML_ROOT}/configure.ac MXML_AC_INIT
  REGEX "^AC_INIT\\(\\[Mini-XML\\], \\[[0-9]+\\.[0-9]+\\.[0-9]+\\]"
  LIMIT_COUNT 1
)

if(NOT MXML_AC_INIT)
  message(FATAL_ERROR "Unable to read the Mini-XML version from configure.ac")
endif()

string(REGEX REPLACE
  "^AC_INIT\\(\\[Mini-XML\\], \\[([^]]+)\\].*"
  "\\1"
  MXML_PACKAGE_VERSION
  "${MXML_AC_INIT}"
)
set(MXML_VERSION "Mini-XML v${MXML_PACKAGE_VERSION}")

