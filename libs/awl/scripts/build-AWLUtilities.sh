#!/bin/sh
#
# Apply the current version numbers into always.php from always.php.in
#

AWL_VERSION="`head -n1 VERSION`"

sed -e "/^ *.c->awl_library_version *=.*$/ s/^ *.c->awl_library_version *=.*$/\$c->awl_library_version = ${AWL_VERSION};/"

