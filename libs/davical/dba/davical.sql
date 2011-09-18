-- DAViCal CalDAV Server - Database Schema
--


-- Given a verbose DAV: or CalDAV: privilege name return the bitmask
CREATE or REPLACE FUNCTION privilege_to_bits( TEXT ) RETURNS BIT(24) AS $$
DECLARE
  raw_priv ALIAS FOR $1;
  in_priv TEXT;
BEGIN
  in_priv := trim(lower(regexp_replace(raw_priv, '^.*:', '')));
  IF in_priv = 'all' THEN
    RETURN ~ 0::BIT(24);
  END IF;

  RETURN (CASE
            WHEN in_priv = 'read'                            THEN  4609 -- 1 + 512 + 4096
            WHEN in_priv = 'write'                           THEN   198 -- 2 + 4 + 64 + 128
            WHEN in_priv = 'write-properties'                THEN     2
            WHEN in_priv = 'write-content'                   THEN     4
            WHEN in_priv = 'unlock'                          THEN     8
            WHEN in_priv = 'read-acl'                        THEN    16
            WHEN in_priv = 'read-current-user-privilege-set' THEN    32
            WHEN in_priv = 'bind'                            THEN    64
            WHEN in_priv = 'unbind'                          THEN   128
            WHEN in_priv = 'write-acl'                       THEN   256
            WHEN in_priv = 'read-free-busy'                  THEN  4608 --  512 + 4096
            WHEN in_priv = 'schedule-deliver'                THEN  7168 -- 1024 + 2048 + 4096
            WHEN in_priv = 'schedule-deliver-invite'         THEN  1024
            WHEN in_priv = 'schedule-deliver-reply'          THEN  2048
            WHEN in_priv = 'schedule-query-freebusy'         THEN  4096
            WHEN in_priv = 'schedule-send'                   THEN 57344 -- 8192 + 16384 + 32768
            WHEN in_priv = 'schedule-send-invite'            THEN  8192
            WHEN in_priv = 'schedule-send-reply'             THEN 16384
            WHEN in_priv = 'schedule-send-freebusy'          THEN 32768
          ELSE 0 END)::BIT(24);
END
$$
LANGUAGE 'PlPgSQL' IMMUTABLE STRICT;

-- Given an array of verbose DAV: or CalDAV: privilege names return the bitmask
CREATE or REPLACE FUNCTION privilege_to_bits( TEXT[] ) RETURNS BIT(24) AS $$
DECLARE
  raw_privs ALIAS FOR $1;
  in_priv TEXT;
  out_bits BIT(24);
  i INT;
  allprivs BIT(24);
  start INT;
  finish INT;
BEGIN
  out_bits := 0::BIT(24);
  allprivs := ~ out_bits;
  SELECT array_lower(raw_privs,1) INTO start;
  SELECT array_upper(raw_privs,1) INTO finish;
  FOR i IN start .. finish  LOOP
    SELECT out_bits | privilege_to_bits(raw_privs[i]) INTO out_bits;
    IF out_bits = allprivs THEN
      RETURN allprivs;
    END IF;
  END LOOP;
  RETURN out_bits;
END
$$
LANGUAGE 'PlPgSQL' IMMUTABLE STRICT;


-- This sequence is used in a number of places so that any DAV resource will have a unique ID
CREATE SEQUENCE dav_id_seq;


-- Not particularly needed, perhaps, except as a way to collect
-- a bunch of valid iCalendar time zone specifications... :-)
CREATE TABLE time_zone (
  tz_id TEXT PRIMARY KEY,
  tz_locn TEXT,
  tz_spec TEXT
);


-- Something that can look like a filesystem hierarchy where we store stuff
CREATE TABLE collection (
  user_no INT references usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  parent_container TEXT,
  dav_name TEXT,
  dav_etag TEXT,
  dav_displayname TEXT,
  is_calendar BOOLEAN,
  created TIMESTAMP WITH TIME ZONE,
  modified TIMESTAMP WITH TIME ZONE,
  public_events_only BOOLEAN NOT NULL DEFAULT FALSE,
  publicly_readable BOOLEAN NOT NULL DEFAULT FALSE,
  collection_id INT8 PRIMARY KEY DEFAULT nextval('dav_id_seq'),
  default_privileges BIT(24),
  is_addressbook BOOLEAN DEFAULT FALSE,
  resourcetypes TEXT DEFAULT '<DAV::collection/>',
  schedule_transp TEXT DEFAULT 'opaque',
  timezone TEXT REFERENCES time_zone(tz_id) ON DELETE SET NULL ON UPDATE CASCADE,
  description TEXT DEFAULT '',
  UNIQUE(user_no,dav_name)
);


-- The main event.  Where we store the things the calendar throws at us.
CREATE TABLE caldav_data (
  user_no INT references usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  dav_name TEXT,
  dav_etag TEXT,
  created TIMESTAMP WITH TIME ZONE,
  modified TIMESTAMP WITH TIME ZONE,
  caldav_data TEXT,
  caldav_type TEXT,
  logged_user INT references usr(user_no) ON UPDATE CASCADE ON DELETE SET DEFAULT DEFERRABLE,
  dav_id INT8 UNIQUE DEFAULT nextval('dav_id_seq'),
  collection_id INT8 REFERENCES collection(collection_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  weak_etag TEXT DEFAULT NULL,

  PRIMARY KEY ( user_no, dav_name )
);
CREATE INDEX caldav_data_collection_id_fkey ON caldav_data(collection_id);

-- The parsed calendar item.  Here we have pulled those events/todos/journals apart somewhat.
CREATE TABLE calendar_item (
  user_no INT references usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
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
  completed TIMESTAMP WITH TIME ZONE,
  dav_id INT8 UNIQUE,
  collection_id INT8 REFERENCES collection(collection_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,

  -- Cascade updates / deletes from the caldav_data table
  CONSTRAINT caldav_exists FOREIGN KEY ( user_no, dav_name )
                REFERENCES caldav_data ( user_no, dav_name )
                MATCH FULL ON DELETE CASCADE ON UPDATE CASCADE DEFERRABLE,

  PRIMARY KEY ( user_no, dav_name )
);
CREATE INDEX calendar_item_collection_id_fkey ON calendar_item(collection_id);



-- Each user can be related to each other user.  This mechanism can also
-- be used to define groups of users, since some relationships are transitive.
CREATE TABLE relationship_type (
  rt_id SERIAL PRIMARY KEY,
  rt_name TEXT,
  rt_togroup BOOLEAN,
  confers TEXT DEFAULT 'RW',
  rt_fromgroup BOOLEAN,
  bit_confers BIT(24) DEFAULT privilege_to_bits(ARRAY['DAV::read','DAV::write'])
);


CREATE TABLE relationship (
  from_user INT REFERENCES usr (user_no) ON UPDATE CASCADE ON DELETE CASCADE,
  to_user INT REFERENCES usr (user_no) ON UPDATE CASCADE ON DELETE CASCADE,
  rt_id INT REFERENCES relationship_type (rt_id) ON UPDATE CASCADE ON DELETE CASCADE,
  confers BIT(24) DEFAULT privilege_to_bits(ARRAY['DAV::read','DAV::write']),

  PRIMARY KEY ( from_user, to_user, rt_id )
);


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


CREATE TABLE property (
  dav_name TEXT,
  property_name TEXT,
  property_value TEXT,
  changed_on TIMESTAMP DEFAULT current_timestamp,
  changed_by INT REFERENCES usr ( user_no ) ON UPDATE CASCADE ON DELETE SET DEFAULT,
  PRIMARY KEY ( dav_name, property_name )
);
CREATE INDEX properties_dav_name_idx ON property(dav_name);


CREATE TABLE freebusy_ticket (
  ticket_id TEXT NOT NULL PRIMARY KEY,
  user_no integer NOT NULL REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE,
  created timestamp with time zone DEFAULT current_timestamp NOT NULL
);



CREATE or REPLACE FUNCTION sync_dav_id ( ) RETURNS TRIGGER AS $$
  DECLARE
  BEGIN

    IF TG_OP = 'DELETE' THEN
      -- Just let the ON DELETE CASCADE handle this case
      RETURN OLD;
    END IF;

    IF NEW.dav_id IS NULL THEN
      NEW.dav_id = nextval('dav_id_seq');
    END IF;

    IF TG_OP = 'UPDATE' THEN
      IF OLD.dav_id != NEW.dav_id OR OLD.collection_id != NEW.collection_id
                 OR OLD.user_no != NEW.user_no OR OLD.dav_name != NEW.dav_name THEN
        UPDATE calendar_item SET dav_id = NEW.dav_id, user_no = NEW.user_no,
                        collection_id = NEW.collection_id, dav_name = NEW.dav_name
            WHERE dav_name = OLD.dav_name OR dav_id = OLD.dav_id;
      END IF;
      RETURN NEW;
    END IF;

    UPDATE calendar_item SET dav_id = NEW.dav_id, user_no = NEW.user_no,
                    collection_id = NEW.collection_id, dav_name = NEW.dav_name
          WHERE dav_name = NEW.dav_name OR dav_id = NEW.dav_id;

    RETURN NEW;

  END
$$ LANGUAGE 'plpgsql';
CREATE TRIGGER caldav_data_sync_dav_id AFTER INSERT OR UPDATE ON caldav_data
    FOR EACH ROW EXECUTE PROCEDURE sync_dav_id();


-- Only needs SELECT access by website.
CREATE TABLE principal_type (
  principal_type_id SERIAL PRIMARY KEY,
  principal_type_desc TEXT
);


-- web needs SELECT,INSERT,UPDATE,DELETE
CREATE TABLE principal (
  principal_id INT8 DEFAULT nextval('dav_id_seq') PRIMARY KEY,
  type_id INT8 NOT NULL REFERENCES principal_type(principal_type_id) ON UPDATE CASCADE ON DELETE RESTRICT DEFERRABLE,
  user_no INT8 NULL REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  displayname TEXT,
  default_privileges BIT(24)
);



-- Allowing identification of group members.
CREATE TABLE group_member (
  group_id INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  member_id INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE
);
CREATE UNIQUE INDEX group_member_pk ON group_member(group_id,member_id);
CREATE INDEX group_member_sk ON group_member(member_id);


CREATE TABLE grants (
  by_principal INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  by_collection INT8 REFERENCES collection(collection_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  to_principal INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  privileges BIT(24),
  is_group BOOLEAN
) WITHOUT OIDS;
CREATE UNIQUE INDEX grants_pk1 ON grants(by_principal,to_principal);
CREATE UNIQUE INDEX grants_pk2 ON grants(by_collection,to_principal);


CREATE TABLE sync_tokens (
  sync_token SERIAL PRIMARY KEY,
  collection_id INT8 REFERENCES collection(collection_id) ON DELETE CASCADE ON UPDATE CASCADE,
  modification_time TIMESTAMP WITH TIME ZONE DEFAULT current_timestamp
);

CREATE TABLE sync_changes (
  sync_time TIMESTAMP WITH TIME ZONE DEFAULT current_timestamp,
  collection_id INT8 REFERENCES collection(collection_id) ON DELETE CASCADE ON UPDATE CASCADE,
  sync_status INT,
  dav_id INT8, -- can't REFERENCES calendar_item(dav_id) ON DELETE SET NULL ON UPDATE RESTRICT
  dav_name TEXT
);
CREATE INDEX sync_processing_index ON sync_changes( collection_id, dav_id, sync_time );

-- Revision 1.2.7 endeth here.

CREATE TABLE access_ticket (
  ticket_id TEXT PRIMARY KEY,
  dav_owner_id INT8 NOT NULL REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE,
  privileges BIT(24),
  target_collection_id INT8 NOT NULL REFERENCES collection(collection_id) ON UPDATE CASCADE ON DELETE CASCADE,
  target_resource_id INT8 REFERENCES caldav_data(dav_id) ON UPDATE CASCADE ON DELETE CASCADE,
  expires TIMESTAMP
);


-- At this point we only support binding collections
CREATE TABLE dav_binding (
  bind_id INT8 DEFAULT nextval('dav_id_seq') PRIMARY KEY,
  bound_source_id INT8 REFERENCES collection(collection_id) ON UPDATE CASCADE ON DELETE CASCADE,
  access_ticket_id TEXT REFERENCES access_ticket(ticket_id) ON UPDATE CASCADE ON DELETE SET NULL,
  dav_owner_id INT8 NOT NULL REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE,
  parent_container TEXT NOT NULL,
  dav_name TEXT UNIQUE NOT NULL,
  dav_displayname TEXT
);


CREATE TABLE addressbook_resource (
  dav_id INT8 NOT NULL REFERENCES caldav_data(dav_id) ON UPDATE CASCADE ON DELETE CASCADE PRIMARY KEY,
  version TEXT,
  uid TEXT,
  nickname TEXT,
  fn TEXT, -- fullname
  n TEXT, -- Name Surname;First names
  note TEXT,
  org TEXT,
  url TEXT,
  fburl TEXT,
  caladruri TEXT,
  caluri TEXT
);

CREATE TABLE addressbook_address_adr (
  dav_id INT8 NOT NULL REFERENCES caldav_data(dav_id) ON UPDATE CASCADE ON DELETE CASCADE,
  type TEXT,
  box_no TEXT,
  unit_no TEXT,
  street_address TEXT,
  locality TEXT,
  region TEXT,
  postcode TEXT,
  country TEXT,
  property TEXT -- The full text of the property
);

CREATE TABLE addressbook_address_tel (
  dav_id INT8 NOT NULL REFERENCES caldav_data(dav_id) ON UPDATE CASCADE ON DELETE CASCADE,
  type TEXT,
  tel TEXT,
  property TEXT -- The full text of the property
);

CREATE TABLE addressbook_address_email (
  dav_id INT8 NOT NULL REFERENCES caldav_data(dav_id) ON UPDATE CASCADE ON DELETE CASCADE,
  type TEXT,
  email TEXT,
  property TEXT -- The full text of the property
);


CREATE TABLE calendar_alarm (
  dav_id INT8 NOT NULL REFERENCES caldav_data(dav_id) ON UPDATE CASCADE ON DELETE CASCADE,
  action TEXT,
  trigger TEXT,
  summary TEXT,
  description TEXT,
  next_trigger TIMESTAMP WITH TIME ZONE,
  component TEXT, -- The full text of the component
  trigger_state CHAR DEFAULT 'N' -- 'N' => 'New/Needs setting', 'A' = 'Active', 'O' = 'Old'
);

CREATE TABLE calendar_attendee (
  dav_id INT8 NOT NULL REFERENCES caldav_data(dav_id) ON UPDATE CASCADE ON DELETE CASCADE,
  status TEXT,
  partstat TEXT,
  cn TEXT,
  attendee TEXT,
  role TEXT,
  rsvp BOOLEAN,
  property TEXT, -- The full text of the property
  attendee_state TEXT, -- Internal DAViCal processing state
  weak_etag TEXT, -- The week_etag applying for this attendee state
  PRIMARY KEY ( dav_id, attendee )
);

SELECT new_db_revision(1,2,9, 'Septembre' );
