#!/bin/sh

DATABASE="$1"
USER="$2"
PERMISSION="${3:-SELECT}"

if [ "$USER" = "" ] ; then
  echo "Usage: $0 <database> <username> [permissions]"
  exit
fi

TABLES="`psql \"$DATABASE\" -qt -c \"select relname  from pg_class where relowner > 50 AND relkind in( 'r', 'S');\"`"

for T in ${TABLES} ; do
  psql "$DATABASE" -c "grant ${PERMISSION} on ${T} to ${USER};"
done
