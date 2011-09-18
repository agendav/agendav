
-- This database update provides new tables for the Principal, for
-- a consistent dav_resource which a principal, collection or calendar_item
-- all inherit from.

BEGIN;
SELECT check_db_revision(1,2,1);

-- Only needs SELECT access by website.
CREATE TABLE principal_type (
  principal_type_id SERIAL PRIMARY KEY,
  principal_type_desc TEXT
);

-- web needs SELECT,INSERT,UPDATE,DELETE
CREATE TABLE principal (
  principal_id SERIAL PRIMARY KEY,
  type_id INT8 NOT NULL REFERENCES principal_type(principal_type_id) ON UPDATE CASCADE ON DELETE RESTRICT DEFERRABLE,
  user_no INT8 NULL REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  displayname TEXT,
  active BOOLEAN
);

-- Allowing identification of group members.
CREATE TABLE group_member (
  group_id INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  member_id INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE
);
CREATE UNIQUE INDEX group_member_pk ON group_member(group_id,member_id);
CREATE INDEX group_member_sk ON group_member(member_id);


-- Only needs SELECT access by website. dav_resource_type will be 'principal', 'collection', 'CalDAV:calendar' and so forth.
CREATE TABLE dav_resource_type (
  resource_type_id SERIAL PRIMARY KEY,
  dav_resource_type TEXT,
  resource_type_desc TEXT
);

CREATE TABLE dav_resource (
  dav_id INT8 PRIMARY KEY DEFAULT nextval('dav_id_seq'),
  dav_name TEXT,
  resource_type_id INT8 REFERENCES dav_resource_type(resource_type_id) ON UPDATE CASCADE ON DELETE RESTRICT DEFERRABLE,
  owner_id INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE
);


CREATE TABLE privilege (
  granted_to_id INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  resource_id INT8 REFERENCES dav_resource(dav_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  granted_by_id INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE RESTRICT DEFERRABLE,
  can_read BOOLEAN,
  can_write BOOLEAN,
  can_write_properties BOOLEAN,
  can_write_content BOOLEAN,
  can_unlock BOOLEAN,
  can_read_acl BOOLEAN,
  can_read_current_user_privilege_set BOOLEAN,
  can_write_acl BOOLEAN,
  can_bind BOOLEAN,
  can_unbind BOOLEAN,
  can_read_free_busy BOOLEAN,
  PRIMARY KEY (granted_to_id, resource_id)
);

SELECT new_db_revision(1,2,2, 'Fevrier' );

COMMIT;
ROLLBACK;

