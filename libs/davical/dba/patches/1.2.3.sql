
-- Add some more cascading updates and deletes.
-- For databases created before PostgreSQL 8.0.

-- I screwed up with version 0.9.6.1 and 0.9.6.2 in that the davical.sql
-- file specified them as version 1.2.2 when in reality they were version
-- 1.2.4 so we are going to clean that up here...

CREATE TEMP TABLE db_version_check AS SELECT * FROM awl_db_revision;
DELETE FROM db_version_check WHERE ((schema_major * 1000000) + (schema_minor * 1000) + schema_patch) < 1002002;
INSERT INTO db_version_check (schema_id, schema_major, schema_minor, schema_patch, schema_name, applied_on )
   SELECT (SELECT max(schema_id) + 1 FROM awl_db_revision WHERE ((schema_major * 1000000) + (schema_minor * 1000) + schema_patch) <= 1002002),
            1, 2, 4, 'Avril', current_timestamp FROM pg_class JOIN pg_attribute ON (pg_class.oid = pg_attribute.attrelid)
      WHERE pg_class.relname = 'calendar_item' AND attname = 'completed';
DELETE FROM db_version_check
       WHERE EXISTS( SELECT 1 FROM db_version_check
                             WHERE schema_major < db_version_check.schema_major
                                  OR (schema_major = db_version_check.schema_major AND schema_minor < db_version_check.schema_minor )
                                  OR (schema_major = db_version_check.schema_major AND schema_minor = db_version_check.schema_minor AND schema_patch < db_version_check.schema_patch) );

INSERT INTO awl_db_revision
    SELECT * FROM db_version_check WHERE schema_major=1 AND schema_minor=2 AND schema_patch=4;

BEGIN;
SELECT check_db_revision(1,2,2);

ALTER TABLE role_member DROP CONSTRAINT "$1";
ALTER TABLE role_member ADD CONSTRAINT "role_member_role_no_fkey" FOREIGN KEY (role_no) REFERENCES roles(role_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE role_member DROP CONSTRAINT "$2";
ALTER TABLE role_member ADD CONSTRAINT "role_member_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE session DROP CONSTRAINT "$1";
ALTER TABLE session ADD CONSTRAINT "session_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE relationship DROP CONSTRAINT "$1";
ALTER TABLE relationship ADD CONSTRAINT "relationship_from_user_fkey" FOREIGN KEY (from_user) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE relationship DROP CONSTRAINT "$2";
ALTER TABLE relationship ADD CONSTRAINT "relationship_to_user_fkey" FOREIGN KEY (to_user) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE usr_setting DROP CONSTRAINT "$1";
ALTER TABLE usr_setting ADD CONSTRAINT "usr_setting_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE tmp_password DROP CONSTRAINT "$1";
ALTER TABLE tmp_password ADD CONSTRAINT "tmp_password_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE caldav_data DROP CONSTRAINT "$1";
ALTER TABLE caldav_data ADD CONSTRAINT "caldav_data_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE caldav_data DROP CONSTRAINT "$2";
ALTER TABLE caldav_data ADD CONSTRAINT "caldav_data_logged_user_fkey" FOREIGN KEY (logged_user) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE property DROP CONSTRAINT "$1";
ALTER TABLE property ADD CONSTRAINT "property_changed_by_fkey" FOREIGN KEY (changed_by) REFERENCES usr(user_no) ON UPDATE CASCADE;

ALTER TABLE calendar_item DROP CONSTRAINT "$1";
ALTER TABLE calendar_item ADD CONSTRAINT "calendar_item_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE calendar_item DROP CONSTRAINT "$2";
ALTER TABLE calendar_item ADD CONSTRAINT "calendar_item_tz_id_fkey" FOREIGN KEY (tz_id) REFERENCES time_zone(tz_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

SELECT new_db_revision(1,2,3, 'Mars' );
COMMIT;
ROLLBACK;

