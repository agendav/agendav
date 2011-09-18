#!/bin/bash
# Calls Google Closure Compiler on *.css and *.js.
# Heavily based on jsshrink.sh from Roundcube

PUBLIC_DIR=`dirname "$0"`/../web/public
JS_DIR=$PUBLIC_DIR/js
CSS_DIR=$PUBLIC_DIR/css
CLOSURE_COMPILER_URL='http://closure-compiler.googlecode.com/files/compiler-latest.zip'

if [ ! -d "$JS_DIR" ] || [ ! -d "$CSS_DIR" ]; then
	echo "$JS_DIR" or "$CSS_DIR" not found
	exit 1
fi

# Closure compiler
if [ ! -r "compiler.jar" ]; then
	wget "$CLOSURE_COMPILER_URL" -O "/tmp/$$.zip"
	unzip "/tmp/$$.zip" "compiler.jar"
	rm -f "/tmp/$$.zip"
fi

# JS shrinking
find $JS_DIR -type f -name '*.js' \
	-not -name '*.min.*' -not -name '*.pack.*' | while read i; do

	NEW_FILE=`echo "$i"|sed 's_\.js$_.min.js_g'`
	java -jar compiler.jar \
		--compilation_level SIMPLE_OPTIMIZATIONS \
		--js $i \
		--js_output_file $NEW_FILE
done


