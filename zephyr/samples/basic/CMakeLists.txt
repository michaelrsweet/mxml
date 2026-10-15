#
# CMakeLists for basic sample program for Zephyr.
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
project(mxml_basic)

target_sources(app PRIVATE src/main.c)

