cmake_minimum_required(VERSION 3.19) # First to support Apple Silicon.
project(mxml LANGUAGES C VERSION 4.0.4)
enable_testing()

# Don't allow source tree pollution.
if(" ${CMAKE_SOURCE_DIR}" STREQUAL " ${CMAKE_BINARY_DIR}")
  message(FATAL_ERROR "
FATAL: In-source builds are not allowed.
       You should create a separate directory for build files.
")
endif()

if(WIN32)
    # set(CMAKE_WINDOWS_EXPORT_ALL_SYMBOLS ON) # C++

    add_compile_definitions(
        _WIN32_WINNT=0x0601
    )
endif(WIN32)

# ==================== CONFIG.H ====================
# For now this is not thorough, but enough to work for 
# the vast majority of cases.

include(CheckIncludeFile)
include(CheckTypeSize)

set(MXML_VERSION ${CMAKE_PROJECT_VERSION})

check_type_size("long long" SIZEOF_LONG_LONG)
if(SIZEOF_LONG_LONG EQUAL "8")
    option(HAVE_LONG_LONG_INT "Enable LONG LONG" ON)
else()
    option(HAVE_LONG_LONG_INT "Enable LONG LONG" OFF)
endif()

if (WIN32)
    option(HAVE_PTHREAD_H "Disable PTHREAD for windows." OFF)
else()
    option(HAVE_PTHREAD_H "Enable PTHREAD as it's probably available." ON)
endif()

# For now assume inline on.
option(inline "Enable inline function support." ON)

configure_file(cmakeconfig.h.in config.h
    @ONLY
)

# ==================== /CONFIG.H ====================

set(MXML_PRIVATE
    mxml-attr.c
    mxml-file.c
    mxml-get.c
    mxml-index.c
    mxml-node.c
    mxml-options.c
    mxml-private.c
    mxml-search.c
    mxml-set.c
)

set(MXML_PUBLIC
    mxml.h

    ${CMAKE_CURRENT_BINARY_DIR}/config.h
)

message(STATUS ${CMAKE_CURRENT_BINARY_DIR})

set(MXML_COLLECTIVE
    ${MXML_PRIVATE}
    ${MXML_PUBLIC}
)

add_library(${PROJECT_NAME} 
    ${MXML_COLLECTIVE}
)

# For config.h
include_directories(${CMAKE_CURRENT_BINARY_DIR})

# Define the header(s) for this library and add to build path.
target_include_directories(${PROJECT_NAME} PUBLIC
    $<BUILD_INTERFACE:${CMAKE_CURRENT_SOURCE_DIR}/>
)

install(TARGETS ${PROJECT_NAME} EXPORT ${PROJECT_NAME}LibraryConfig
    ARCHIVE DESTINATION ${CMAKE_INSTALL_LIBDIR}
    LIBRARY DESTINATION ${CMAKE_INSTALL_LIBDIR}
    RUNTIME DESTINATION ${CMAKE_INSTALL_BINDIR} # For Windows
)

# Importable from the install directory.
# Config file per-project dir or into "cmake".
install(EXPORT ${PROJECT_NAME}LibraryConfig DESTINATION share/${PROJECT_NAME}Library/cmake)

# Importable from the build directory.
export(TARGETS ${PROJECT_NAME} FILE ${PROJECT_NAME}/LibraryConfig.cmake)

# ==================== TESTS ====================
add_executable(${PROJECT_NAME}-test
    testmxml.c
)

add_custom_command(
    TARGET ${PROJECT_NAME}-test
    COMMAND ${CMAKE_COMMAND} -E copy
    ${CMAKE_CURRENT_SOURCE_DIR}/test.xml
    $<TARGET_FILE_DIR:${PROJECT_NAME}-test>
)

target_link_libraries(${PROJECT_NAME}-test
    ${PROJECT_NAME}
)

include(ExternalData)
ExternalData_Add_Test(
    NAME test-mxml
    ${PROJECT_NAME}-test
    DATA{test.xml}
)

