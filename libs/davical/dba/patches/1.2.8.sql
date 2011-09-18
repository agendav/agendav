
-- This database update adds support for tickets to be handed out to grant
-- specific access to a collection or individual resource, as read-only or
-- read-write.  A table is also added to manage WebDAV binding, in line
-- with http://tools.ietf.org/html/draft-ietf-webdav-bind.

BEGIN;
SELECT check_db_revision(1,2,7);

ALTER TABLE caldav_data ADD COLUMN weak_etag TEXT DEFAULT NULL;
ALTER TABLE collection DROP COLUMN in_freebusy_set;

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
  url TEXT
);

CREATE TABLE addressbook_address_adr (
  dav_id INT8 NOT NULL REFERENCES caldav_data(dav_id) ON UPDATE CASCADE ON DELETE CASCADE,
  type TEXT,
  adr TEXT,
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
  component TEXT -- The full text of the component
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
  PRIMARY KEY ( dav_id, attendee )
);

SELECT new_db_revision(1,2,8, 'Août' );

COMMIT;
ROLLBACK;

