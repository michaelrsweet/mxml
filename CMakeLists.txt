# Minimal CMake build for library, header, and *.pc - mostly to
# simplify cross-compilation

# Keeping in sync with autotools
#   - review changes to `LIBOBJS` in `Makefile.in` and add the
#     corresponding source files to `SOURCES` below
#   - run `./config.h.cmake.in.gen` and add new platform checks[1]
#     or third-party dependencies using `find_package`[2] or
#     `FindPkgConfig`[3]
#   - ensure new cflags/libs are added to `pkg-config` below
#
#[1] https://cmake.org/Wiki/CMake:How_To_Write_Platform_Checks
#[2] https://cmake.org/cmake/help/latest/command/find_package.html
#[3] https://cmake.org/cmake/help/latest/module/FindPkgConfig.html

cmake_minimum_required(VERSION 3.1)
project(mxml C)

set(VERSION_MAJOR 2)
set(VERSION_MINOR 11)
set(SO_VERSION 1)
set(VERSION "${VERSION_MAJOR}.${VERSION_MINOR}")
set(VERSION_H "Mini-XML v${VERSION_MAJOR}.${VERSION_MINOR}")

option(ENABLE_THREADS "Enable multi-threading support" ON)
if(ENABLE_THREADS)
    set(CMAKE_THREAD_PREFER_PTHREAD ON)
    find_package(Threads REQUIRED)
    set(HAVE_PTHREAD_H ON)
endif()

find_package(ZLIB)
if(ZLIB_FOUND)
    set(HAVE_ZLIB_H ON)
endif()

include(CheckFunctionExists)
check_function_exists(snprintf  HAVE_SNPRINTF)
check_function_exists(vasprintf HAVE_VASPRINTF)
check_function_exists(vsnprintf HAVE_VSNPRINTF)
check_function_exists(strdup    HAVE_STRDUP)
check_function_exists(strlcat   HAVE_STRLCAT)
check_function_exists(strlcpy   HAVE_STRLCPY)

include(CheckTypeSize)
check_type_size("long long" LONG_LONG)

include_directories("${PROJECT_SOURCE_DIR}")
include_directories("${CMAKE_CURRENT_BINARY_DIR}")
include_directories("${ZLIB_INCLUDE_DIRS}")

set(SOURCES mxml-attr.c
            mxml-entity.c
            mxml-file.c
            mxml-get.c
            mxml-index.c
            mxml-node.c
            mxml-search.c
            mxml-set.c
            mxml-private.c
            mxml-string.c
)

add_library(mxml ${SOURCES})
set_target_properties(mxml PROPERTIES
     SOVERSION ${SO_VERSION})

if(WIN32 AND BUILD_SHARED_LIBS)
    set(PC_LIB_NAME mxml-${SO_VERSION})
    set_target_properties(mxml PROPERTIES
         OUTPUT_NAME ${PC_LIB_NAME})
else()
    set(PC_LIB_NAME mxml)
endif()

if(ENABLE_THREADS)
    target_link_libraries(mxml Threads::Threads)
endif()
target_link_libraries(mxml ZLIB::ZLIB)

# pkg-config variables
set(prefix ${CMAKE_INSTALL_PREFIX})
set(exec_prefix "\$\{prefix\}")
set(libdir "\$\{exec_prefix\}/lib")
set(includedir "\$\{prefix\}/include")
set(PC_LIBS "-L\$\{libdir\} -l${PC_LIB_NAME} ${ZLIB_LIBRARIES}")
set(PTHREAD_LIBS ${CMAKE_THREAD_LIBS_INIT})
set(PC_CFLAGS "-I\$\{includedir\} ${CMAKE_C_FLAGS} -I${ZLIB_INCLUDE_DIRS}")
set(PTHREAD_FLAGS "")

configure_file(${CMAKE_CURRENT_SOURCE_DIR}/mxml.pc.in ${CMAKE_CURRENT_BINARY_DIR}/mxml.pc)
configure_file(${CMAKE_CURRENT_SOURCE_DIR}/config.h.cmake.in ${CMAKE_CURRENT_BINARY_DIR}/config.h)

install(FILES "mxml.h" DESTINATION include)
install(FILES "${CMAKE_CURRENT_BINARY_DIR}/mxml.pc" DESTINATION lib/pkgconfig)
install(TARGETS mxml
        DESTINATION lib
        RUNTIME DESTINATION bin)
