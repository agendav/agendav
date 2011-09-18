-- Tables needed for AWL Libraries

BEGIN;

CREATE TABLE supported_locales (
  locale TEXT PRIMARY KEY,
  locale_name_en TEXT,
  locale_name_locale TEXT
);

-- This is the table of users for the system
CREATE TABLE usr (
  user_no SERIAL PRIMARY KEY,
  active BOOLEAN DEFAULT TRUE,
  email_ok TIMESTAMPTZ,
  joined TIMESTAMPTZ DEFAULT current_timestamp,
  updated TIMESTAMPTZ,
  last_used TIMESTAMPTZ,
  username TEXT NOT NULL,  -- Note UNIQUE INDEX below constains case-insensitive uniqueness
  password TEXT,
  fullname TEXT,
  email TEXT,
  config_data TEXT,
  date_format_type TEXT DEFAULT 'E', -- default to english date format dd/mm/yyyy
  locale TEXT
);
CREATE FUNCTION max_usr() RETURNS INT4 AS 'SELECT max(user_no) FROM usr' LANGUAGE 'sql';
CREATE UNIQUE INDEX usr_sk1_unique_username ON usr ( lower(username) );

CREATE TABLE usr_setting (
  user_no INT4 REFERENCES usr ( user_no ),
  setting_name TEXT,
  setting_value TEXT,
  PRIMARY KEY ( user_no, setting_name )
);

CREATE FUNCTION get_usr_setting(INT4,TEXT)
    RETURNS TEXT
    AS 'SELECT setting_value FROM usr_setting
            WHERE usr_setting.user_no = $1
            AND usr_setting.setting_name = $2 ' LANGUAGE 'sql';

CREATE TABLE roles (
    role_no SERIAL PRIMARY KEY,
    role_name TEXT
);
CREATE FUNCTION max_roles() RETURNS INT4 AS 'SELECT max(role_no) FROM roles' LANGUAGE 'sql';


CREATE TABLE role_member (
    role_no INT4 REFERENCES roles ( role_no ),
    user_no INT4 REFERENCES usr ( user_no )
);


CREATE TABLE session (
    session_id SERIAL PRIMARY KEY,
    user_no INT4 REFERENCES usr ( user_no ),
    session_start TIMESTAMPTZ DEFAULT current_timestamp,
    session_end TIMESTAMPTZ DEFAULT current_timestamp,
    session_key TEXT,
    session_config TEXT
);
CREATE FUNCTION max_session() RETURNS INT4 AS 'SELECT max(session_id) FROM session' LANGUAGE 'sql';

CREATE TABLE tmp_password (
  user_no INT4 REFERENCES usr ( user_no ),
  password TEXT,
  valid_until TIMESTAMPTZ DEFAULT (current_timestamp + '1 day'::interval)
);
COMMIT;

BEGIN;
GRANT SELECT,INSERT,UPDATE ON
    usr
  , usr_setting
  , roles
  , role_member
  , session
  , tmp_password
  TO general;
GRANT SELECT,UPDATE ON
    usr_user_no_seq
  , session_session_id_seq
  TO general;

GRANT SELECT ON
    supported_locales
  TO general;

GRANT DELETE ON
    tmp_password
  , role_member
  TO general;

COMMIT;