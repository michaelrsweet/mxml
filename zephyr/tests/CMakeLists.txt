#
# CMakeLists for wrapping the Mini-XML test program for Zephyr.
#
# https://www.msweet.org/mxml
#
# Copyright © 2026 by Romain Pelletant.
#
# Licensed under Apache License v2.0.  See the file "LICENSE" for more
# information.
#
# SPDX-License-Identifier: Apache-2.0
#

cmake_minimum_required(VERSION 3.20.0)
find_package(Zephyr REQUIRED HINTS $ENV{ZEPHYR_BASE})
project(mxml_smoke)

set(MXML_ROOT ${CMAKE_CURRENT_LIST_DIR}/../..)
set(MXML_UPSTREAM_TEST ${MXML_ROOT}/testmxml.c)
set(MXML_TEST_INCLUDE_DIR ${CMAKE_CURRENT_BINARY_DIR}/include)

include(${CMAKE_CURRENT_LIST_DIR}/../version.cmake)
if(CONFIG_POSIX_THREADS)
  set(HAVE_PTHREAD_H 1)
endif()
configure_file(
  ${CMAKE_CURRENT_LIST_DIR}/../config.h.in
  ${MXML_TEST_INCLUDE_DIR}/config.h
)

target_sources(app PRIVATE
  src/main.c
  ${MXML_UPSTREAM_TEST}
)
target_include_directories(app PRIVATE ${MXML_TEST_INCLUDE_DIR})

# Keep the upstream test source unchanged and expose its entry point to Ztest.
set_source_files_properties(
  ${MXML_UPSTREAM_TEST}
  PROPERTIES COMPILE_DEFINITIONS "main=testmxml_main"
)

