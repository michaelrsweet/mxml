cmake_minimum_required(VERSION 3.8)

set(VERSION 3.2)

project(
  mxml
  VERSION ${VERSION}
  LANGUAGES C)

set(SOURCES
    mxml-attr.c
    mxml-entity.c
    mxml-file.c
    mxml-get.c
    mxml-index.c
    mxml-node.c
    mxml-private.c
    mxml-search.c
    mxml-set.c
    mxml-string.c)

configure_file(config.h.in config.h)
set(HEADERS mxml-private.h ${CMAKE_CURRENT_BINARY_DIR}/config.h)

add_library(${PROJECT_NAME} ${SOURCES} ${HEADERS})
target_include_directories(${PROJECT_NAME} PRIVATE ${CMAKE_CURRENT_SOURCE_DIR})
add_library(MSweet::${PROJECT_NAME} ALIAS ${PROJECT_NAME})

# install library
install(
  TARGETS ${PROJECT_NAME}
  EXPORT ${PROJECT_NAME}Targets
  LIBRARY DESTINATION lib
  ARCHIVE DESTINATION lib
  RUNTIME DESTINATION bin
  INCLUDES
  DESTINATION include)

install(
  EXPORT ${PROJECT_NAME}Targets
  DESTINATION lib/cmake/${PROJECT_NAME}
  FILE ${PROJECT_NAME}Targets.cmake
  NAMESPACE MSweet::)

install(FILES mxml.h DESTINATION include)

include(CMakePackageConfigHelpers)
write_basic_package_version_file(${PROJECT_NAME}ConfigVersion.cmake
                                 COMPATIBILITY SameMajorVersion)
install(FILES ${PROJECT_NAME}Config.cmake
              ${CMAKE_CURRENT_BINARY_DIR}/${PROJECT_NAME}ConfigVersion.cmake
        DESTINATION lib/cmake/${PROJECT_NAME})
