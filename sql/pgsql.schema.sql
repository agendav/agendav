CREATE TABLE sessions (
  session_id varchar(40) NOT NULL DEFAULT 0,
  ip_address varchar(16) NOT NULL DEFAULT 0,
  user_agent varchar(120) DEFAULT NULL,
  last_activity bigint NOT NULL DEFAULT 0,
  user_data text NOT NULL,
  PRIMARY KEY (session_id)
);
CREATE INDEX last_activity_idx ON sessions (last_activity);

CREATE SEQUENCE shared_sid_seq;
CREATE TABLE shared (
  sid int NOT NULL DEFAULT nextval('shared_sid_seq'),
  user_from varchar(255) NOT NULL,
  calendar varchar(255) NOT NULL,
  user_which varchar(255) NOT NULL,
  options text NOT NULL DEFAULT '',
  write_access boolean NOT NULL DEFAULT '0',
  PRIMARY KEY (sid)
);
CREATE INDEX shareidx ON shared (user_from,calendar);
CREATE INDEX sharedwithidx ON shared (user_which);
