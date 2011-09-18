-- Really Simple CalDAV Store - Database Schema
--

-- Use the usr, group and schema management stufffrom libawl-php
\i awl-tables.sql
\i schema-management.sql

-- The main event.  Where we store the things the calendar throws at us.
CREATE TABLE caldav_data (
  user_no INT references usr(user_no),
  dav_name TEXT,
  dav_etag TEXT,
  created TIMESTAMP WITH TIME ZONE,
  modified TIMESTAMP WITH TIME ZONE,
  caldav_data TEXT,
  caldav_type TEXT,
  logged_user INT references usr(user_no),

  PRIMARY KEY ( user_no, dav_name )
);

GRANT SELECT,INSERT,UPDATE,DELETE ON caldav_data TO general;

-- Not particularly needed, perhaps, except as a way to collect
-- a bunch of valid iCalendar time zone specifications... :-)
CREATE TABLE time_zone (
  tz_id TEXT PRIMARY KEY,
  tz_locn TEXT,
  tz_spec TEXT
);
GRANT SELECT,INSERT ON time_zone TO general;

-- The parsed calendar item.  Here we have pulled those events/todos/journals apart somewhat.
CREATE TABLE calendar_item (
  user_no INT references usr(user_no),
  dav_name TEXT,
  dav_etag TEXT,

  -- Extracted vEvent/vTodo data
  uid TEXT,
  created TIMESTAMP,
  last_modified TIMESTAMP,
  dtstamp TIMESTAMP,
  dtstart TIMESTAMP WITH TIME ZONE,
  dtend TIMESTAMP WITH TIME ZONE,
  due TIMESTAMP WITH TIME ZONE,
  summary TEXT,
  location TEXT,
  description TEXT,
  priority INT,
  class TEXT,
  transp TEXT,
  rrule TEXT,
  url TEXT,
  percent_complete NUMERIC(7,2),
  tz_id TEXT REFERENCES time_zone( tz_id ),
  status TEXT,

  -- Cascade updates / deletes from the caldav_data table
  CONSTRAINT caldav_exists FOREIGN KEY ( user_no, dav_name )
                REFERENCES caldav_data ( user_no, dav_name )
                MATCH FULL ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE
);

GRANT SELECT,INSERT,UPDATE,DELETE ON calendar_item TO general;


-- Something that can look like a filesystem hierarchy where we store stuff
CREATE TABLE collection (
  user_no INT references usr(user_no),
  parent_container TEXT,
  dav_name TEXT,
  dav_etag TEXT,
  dav_displayname TEXT,
  is_calendar BOOLEAN,
  created TIMESTAMP WITH TIME ZONE,
  modified TIMESTAMP WITH TIME ZONE,

  PRIMARY KEY ( user_no, dav_name )
);

GRANT SELECT,INSERT,UPDATE,DELETE ON collection TO general;

-- Each user can be related to each other user.  This mechanism can also
-- be used to define groups of users, since some relationships are transitive.
CREATE TABLE relationship_type (
  rt_id SERIAL PRIMARY KEY,
  rt_name TEXT,
  rt_togroup BOOLEAN,
  confers TEXT DEFAULT 'RW',
  rt_fromgroup BOOLEAN
);

GRANT SELECT,INSERT,UPDATE,DELETE ON relationship_type TO general;
GRANT SELECT,UPDATE ON relationship_type_rt_id_seq TO general;

CREATE TABLE relationship (
  from_user INT REFERENCES usr (user_no) ON UPDATE CASCADE,
  to_user INT REFERENCES usr (user_no) ON UPDATE CASCADE,
  rt_id INT REFERENCES relationship_type (rt_id) ON UPDATE CASCADE,

  PRIMARY KEY ( from_user, to_user, rt_id )
);

GRANT SELECT,INSERT,UPDATE,DELETE ON relationship TO general;


CREATE TABLE locks (
  dav_name TEXT,
  opaquelocktoken TEXT UNIQUE NOT NULL,
  type TEXT,
  scope TEXT,
  depth INT,
  owner TEXT,
  timeout INTERVAL,
  start TIMESTAMP DEFAULT current_timestamp
);

CREATE INDEX locks_dav_name_idx ON locks(dav_name);
GRANT SELECT,INSERT,UPDATE,DELETE ON locks TO general;

CREATE TABLE property (
  dav_name TEXT,
  property_name TEXT,
  property_value TEXT,
  changed_on TIMESTAMP DEFAULT current_timestamp,
  changed_by INT REFERENCES usr ( user_no ),
  PRIMARY KEY ( dav_name, property_name )
);

CREATE INDEX properties_dav_name_idx ON property(dav_name);
GRANT SELECT,INSERT,UPDATE,DELETE ON property TO general;


SELECT new_db_revision(1,1,7, 'July' );
