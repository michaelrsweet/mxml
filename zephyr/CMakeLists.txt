#
# CMakeLists for building Mini-XML as a Zephyr module.
#
# Copyright © 2026 by Romain Pelletant.
#
# Licensed under Apache License v2.0.  See the file "LICENSE" for more
# information.
#
# SPDX-License-Identifier: Apache-2.0
#

if(CONFIG_MXML)
  set(MXML_ROOT ${CMAKE_CURRENT_LIST_DIR}/..)
  set(MXML_GENERATED_INCLUDE_DIR ${CMAKE_CURRENT_BINARY_DIR}/include)

  include(${CMAKE_CURRENT_LIST_DIR}/version.cmake)
  if(CONFIG_POSIX_THREADS)
    set(HAVE_PTHREAD_H 1)
  endif()

  configure_file(
    ${CMAKE_CURRENT_LIST_DIR}/config.h.in
    ${MXML_GENERATED_INCLUDE_DIR}/config.h
  )

  zephyr_library()
  zephyr_library_compile_definitions(_POSIX_C_SOURCE=200809L)
  zephyr_library_sources(
    ${MXML_ROOT}/mxml-attr.c
    ${MXML_ROOT}/mxml-file.c
    ${MXML_ROOT}/mxml-get.c
    ${MXML_ROOT}/mxml-index.c
    ${MXML_ROOT}/mxml-node.c
    ${MXML_ROOT}/mxml-options.c
    ${MXML_ROOT}/mxml-private.c
    ${MXML_ROOT}/mxml-search.c
    ${MXML_ROOT}/mxml-set.c
  )
  zephyr_include_directories(${MXML_ROOT})
  zephyr_library_include_directories(${MXML_GENERATED_INCLUDE_DIR})
endif()

