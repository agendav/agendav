#!/bin/sh
#
# Apply the current version numbers into always.php from always.php.in
#

DAVICAL_VERSION="`head -n1 VERSION`"
DB_VERSION="`grep 'SELECT new_db_revision' dba/davical.sql | cut -f2 -d'(' | cut -f1-3 -d,`"
AWL_VERSION="`head -n1 ../awl/VERSION`"
if [ -z "${AWL_VERSION}" ] ; then
  AWL_VERSION="`grep 'want_awl_version' htdocs/always.php | cut -f2 -d= | cut -f1 -d';'`"  
fi

sed -e "/^ *.c->version_string *= *'[^']*' *;/ s/^ *.c->version_string *= *'[^']*' *;/\$c->version_string = '${DAVICAL_VERSION}';/" \
    -e "/^ *.c->want_dbversion *=.*$/ s/^ *.c->want_dbversion *=.*$/\$c->want_dbversion = array(${DB_VERSION});/" \
    -e "/^ *.c->want_awl_version *=.*$/ s/^ *.c->want_awl_version *=.*$/\$c->want_awl_version = ${AWL_VERSION};/"
