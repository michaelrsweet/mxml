#!/bin/sh
(cd ..; make mxmldoc-static)

files=""
mode=""

while test $# -gt 0; do
	arg="$1"
	shift

	case "$arg" in
		-g) mode="gdb" ;;
		-v) mode="valgrind" ;;
		*.h | *.c | *.cxx) files="$files $arg" ;;
		*)
			echo "Usage: ./dotest.sh [-g] [-v] [files]"
			exit 1
			;;
	esac
done

if test "$files" = ""; then
	files=*.cxx
fi

rm -f test.xml

case "$mode" in
	gdb)
		echo "run test.xml $files >test.html 2>test.log" >.gdbcmds
		gdb -x .gdbcmds ../mxmldoc-static
		;;

	valgrind)
		valgrind --log-fd=3 --leak-check=yes \
			../mxmldoc-static test.xml $files \
			>test.html 2>test.log 3>test.valgrind
		;;

	*)
		../mxmldoc-static test.xml $files >test.html 2>test.log
		;;
esac

