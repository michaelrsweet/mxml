from conan import ConanFile
from conan.tools.cmake import CMakeToolchain, CMake, cmake_layout, CMakeDeps

class mxmlRecipe(ConanFile):
    name = "mxml"
    version = "4.0.4"
    description = "Mini-XML - Tiny XML Parsing Library v4"
    homepage = "https://github.com/michaelrsweet/mxml.git"
    url = "https://github.com/michaelrsweet/mxml.git"
    topics = ("xml")
    package_type = "library"

    settings = "os", "compiler", "build_type", "arch"

    options = { "shared": [True, False] }
    default_options = { "shared": False }

    @property
    def _msbuild_configuration(self):
        return "Debug" if self.settings.build_type == "Debug" else "Release"

    def generate(self):
        tc = CMakeToolchain(self)
        tc.generate()

    def layout(self):
        cmake_layout(self)

    def build(self):
        cmake = CMake(self)
        cmake.configure()
        cmake.build()

    def package(self):
        cmake = CMake(self)
        cmake.install()
    
