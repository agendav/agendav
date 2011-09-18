#!/bin/sh
#
# Build the DAViCal database
#

DBNAME="${1:-davical}"
ADMINPW="${2}"

DBADIR="`dirname \"$0\"`"

INSTALL_NOTE_FN="`mktemp -t tmp.XXXXXXXXXX`"

testawldir() {
  [ -f "${1}/dba/awl-tables.sql" ]
}

#
# Attempt to locate the AWL directory
AWLDIR="${DBADIR}/../../awl"
if ! testawldir "${AWLDIR}"; then
  AWLDIR="/usr/share/awl"
  if ! testawldir "${AWLDIR}"; then
    AWLDIR="/usr/local/share/awl"
    if ! testawldir "${AWLDIR}"; then
      echo "Unable to find AWL libraries"
      exit 1
    fi
  fi
fi

export AWL_DBAUSER=davical_dba
export AWL_APPUSER=davical_app

# Get the major version for PostgreSQL
export DBVERSION="`psql -qXAt -c "SELECT version();" template1 | cut -f2 -d' ' | cut -f1-2 -d'.'`"

install_note() {
  cat >>"${INSTALL_NOTE_FN}"
}

db_users() {
  psql -qXAt -c "SELECT usename FROM pg_user;" template1
}

create_db_user() {
  if ! db_users | grep "^${1}$" >/dev/null ; then
    psql -qXAt -c "CREATE USER ${1} NOCREATEDB NOCREATEROLE;" template1
    cat <<EONOTE | install_note
*  You will need to edit the PostgreSQL pg_hba.conf to allow the
   '${1}' database user access to the 'davical' database.

EONOTE
  fi
}

create_plpgsql_language() {
  if ! psql ${DBA} -qXAt -c "SELECT lanname FROM pg_language;" "${DBNAME}" | grep "^plpgsql$" >/dev/null; then
    createlang plpgsql "${DBNAME}"
  fi
}

try_db_user() {
  [ "XtestX`psql -U "${1}" -qXAt -c \"SELECT usename FROM pg_user;\" \"${DBNAME}\" 2>/dev/null`" != "XtestX" ]
}

# Hide all the annoying NOTICE... messages
export PGOPTIONS='--client-min-messages=warning'

create_db_user "${AWL_DBAUSER}"
create_db_user "${AWL_APPUSER}"

# FIXME: Need to check that the database was actually created.
if ! createdb --encoding UTF8 --template template0 --owner "${AWL_DBAUSER}" "${DBNAME}" ; then
  echo "Unable to create database"
  exit 1
fi

#
# Try a few alternatives for a database user or give up...
if try_db_user "${AWL_DBAUSER}" ; then
  export DBA="-U ${AWL_DBAUSER}"
else
  if try_db_user "postgres" ; then
    export DBA="-U postgres"
  else
    if try_db_user "${USER}" ; then
      export DBA=""
    else
      if try_db_user "${PGUSER}" ; then
        export DBA=""
      else
        cat <<EOFAILURE
* * * * ERROR * * * *
I cannot find a usable database user to construct the DAViCal database with, but
may have successfully created the davical_app and davical_dba users (I tried :-).

You should edit your pg_hba.conf file to give permissions to the davical_app and
davical_dba users to access the database and run this script again.  If you still
continue to see this message then you will need to make sure you run the script
as a user with full permissions to access the local PostgreSQL database.

If your PostgreSQL database is non-standard then you will need to set the PGHOST,
PGPORT and/or PGCLUSTER environment variables before running this script again.

See:  http://wiki.davical.org/w/Install_Errors/No_Database_Rights

EOFAILURE
        exit 1
      fi
    fi
  fi
fi

create_plpgsql_language

#
# Load the AWL base tables and schema management tables
psql -qXAt ${DBA} -f "${AWLDIR}/dba/awl-tables.sql" "${DBNAME}" 2>&1
psql -qXAt ${DBA} -f "${AWLDIR}/dba/schema-management.sql" "${DBNAME}" 2>&1

#
# Load the DAViCal tables
psql -qXAt ${DBA} -f "${DBADIR}/davical.sql" "${DBNAME}" 2>&1

#
# Set permissions for the application DB user on the database
if ! ${DBADIR}/update-davical-database --dbname "${DBNAME}" --appuser "${AWL_APPUSER}" --nopatch --owner "${AWL_DBAUSER}" ; then
        cat <<EOFAILURE
* * * * ERROR * * * *
The database administration utility failed.  This may be due to database
permissions for the davical_dba user, or because the Perl DBD::Pg or YAML
libraries are not available.

Check that your pg_hba.conf allows the davical_dba user to connect to the
database (and make sure you've reloaded PostgreSQL since changing that).

Also see:  http://wiki.davical.org/w/Install_Errors/No_Perl_YAML

EOFAILURE
  exit 1
fi
#
# Load the required base data
psql -qXAt ${DBA} -f "${DBADIR}/base-data.sql" "${DBNAME}" | egrep -v '^10'

#
# We can override the admin password generation for regression testing predictability
if [ "${ADMINPW}" = "" ] ; then
  #
  # Generate a random administrative password.  If pwgen is available we'll use that,
  # otherwise try and hack something up using a few standard utilities
  ADMINPW="`pwgen -Bcny 2>/dev/null | tr \"\\\\\'\" '^='`"
fi

if [ "$ADMINPW" = "" ] ; then
  # OK.  They didn't supply one, and pwgen didn't work, so we hack something
  # together from /dev/random ...
  export LC_ALL=C
  ADMINPW="`dd if=/dev/urandom bs=512 count=1 2>/dev/null | tr -c -d 'a-km-zA-HJ-NP-Y0-9' | cut -c2-9`"
fi

if [ "$ADMINPW" = "" ] ; then
  # Right.  We're getting desperate now.  We'll have to use a default password
  # and hope that they change it to something more sensible.
  ADMINPW="please change this password"
fi

psql -qX -c "UPDATE usr SET password = '**${ADMINPW}' WHERE user_no = 1;" "${DBNAME}"

echo "NOTE"
echo "===="
cat "${INSTALL_NOTE_FN}"
rm "${INSTALL_NOTE_FN}"

cat <<FRIENDLY
*  The password for the 'admin' user has been set to '${ADMINPW}'

Thanks for trying DAViCal!  Check in /usr/share/doc/davical/examples/ for
some configuration examples.  For help, visit #davical on irc.oftc.net.

FRIENDLY
