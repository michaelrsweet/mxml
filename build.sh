#!/bin/bash

set -e

cd "`dirname "$0"`"

mkdir -p build/

pushd build

build_type=Release # SET BUILD TYPE HERE

if [[ "$OSTYPE" == "darwin"* ]]; then
	cmake .. -G "Xcode" -DCMAKE_BUILD_TYPE=$build_type
else
	cmake .. -DCMAKE_BUILD_TYPE=$build_type
fi

make
make test

popd > /dev/null

