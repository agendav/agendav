
-- Add some more cascading updates and deletes.
-- For databases created on or after PostgreSQL 8.0.

BEGIN;
SELECT check_db_revision(1,2,2);

ALTER TABLE role_member DROP CONSTRAINT "role_member_role_no_fkey";
ALTER TABLE role_member ADD CONSTRAINT "role_member_role_no_fkey" FOREIGN KEY (role_no) REFERENCES roles(role_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE role_member DROP CONSTRAINT "role_member_user_no_fkey";
ALTER TABLE role_member ADD CONSTRAINT "role_member_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
 
ALTER TABLE session DROP CONSTRAINT "session_user_no_fkey";
ALTER TABLE session ADD CONSTRAINT "session_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE relationship DROP CONSTRAINT "relationship_from_user_fkey";
ALTER TABLE relationship ADD CONSTRAINT "relationship_from_user_fkey" FOREIGN KEY (from_user) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE relationship DROP CONSTRAINT "relationship_to_user_fkey";
ALTER TABLE relationship ADD CONSTRAINT "relationship_to_user_fkey" FOREIGN KEY (to_user) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE usr_setting DROP CONSTRAINT "usr_setting_user_no_fkey";
ALTER TABLE usr_setting ADD CONSTRAINT "usr_setting_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE tmp_password DROP CONSTRAINT "tmp_password_user_no_fkey";
ALTER TABLE tmp_password ADD CONSTRAINT "tmp_password_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE caldav_data DROP CONSTRAINT "caldav_data_user_no_fkey";
ALTER TABLE caldav_data ADD CONSTRAINT "caldav_data_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE caldav_data DROP CONSTRAINT "caldav_data_logged_user_fkey";
ALTER TABLE caldav_data ADD CONSTRAINT "caldav_data_logged_user_fkey" FOREIGN KEY (logged_user) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE property DROP CONSTRAINT "property_changed_by_fkey";
ALTER TABLE property ADD CONSTRAINT "property_changed_by_fkey" FOREIGN KEY (changed_by) REFERENCES usr(user_no) ON UPDATE CASCADE;

ALTER TABLE calendar_item DROP CONSTRAINT "calendar_item_user_no_fkey";
ALTER TABLE calendar_item ADD CONSTRAINT "calendar_item_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE calendar_item DROP CONSTRAINT "calendar_item_tz_id_fkey";
ALTER TABLE calendar_item ADD CONSTRAINT "calendar_item_tz_id_fkey" FOREIGN KEY (tz_id) REFERENCES time_zone(tz_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

SELECT new_db_revision(1,2,3, 'Mars' );
COMMIT;
ROLLBACK;

