#!/bin/sh
#
# Grant permissions to AWL tables for Web application and DBA users
#
# Since we don't know anything about the database connection we echo
# the SQL for our caller to pipe into the database.
#

APPUSER=${1:-"general"}
DBAUSER=${2}

cat <<EOPERMS
GRANT SELECT,INSERT,UPDATE ON
    usr
  , usr_setting
  , roles
  , role_member
  , session
  , tmp_password
  TO ${APPUSER};

GRANT SELECT,UPDATE ON
    usr_user_no_seq
  , session_session_id_seq
  TO ${APPUSER};

GRANT SELECT ON
    supported_locales
  , awl_db_revision
  TO ${APPUSER};

GRANT DELETE ON
    tmp_password
  , role_member
  TO ${APPUSER};

EOPERMS

if [ -n "${DBAUSER}" ]; then
  cat <<EOPERMS
GRANT ALL ON
    usr
  , usr_setting
  , roles
  , role_member
  , session
  , tmp_password
  , usr_user_no_seq
  , session_session_id_seq
  , supported_locales
  , awl_db_revision
  , tmp_password
  , role_member
  TO ${DBAUSER};

EOPERMS

fi