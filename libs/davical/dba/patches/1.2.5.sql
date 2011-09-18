
-- This database update refines the constraint on usr in order to try and be
-- able to actually DELETE FROM usr WHERE user_no = x; and have the database
-- do the right thing...

BEGIN;
SELECT check_db_revision(1,2,4);

ALTER TABLE calendar_item DROP CONSTRAINT "calendar_item_user_no_fkey";
ALTER TABLE calendar_item ADD CONSTRAINT "calendar_item_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no)
    ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE caldav_data DROP CONSTRAINT "caldav_data_logged_user_fkey";
ALTER TABLE caldav_data ADD CONSTRAINT "caldav_data_logged_user_fkey" FOREIGN KEY (logged_user) REFERENCES usr(user_no)
    ON UPDATE CASCADE ON DELETE SET DEFAULT DEFERRABLE;

ALTER TABLE relationship DROP CONSTRAINT "relationship_from_user_fkey";
ALTER TABLE relationship ADD CONSTRAINT "relationship_from_user_fkey" FOREIGN KEY (from_user) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE relationship DROP CONSTRAINT "relationship_to_user_fkey";
ALTER TABLE relationship ADD CONSTRAINT "relationship_to_user_fkey" FOREIGN KEY (to_user) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
ALTER TABLE relationship DROP CONSTRAINT "relationship_rt_id_fkey";
ALTER TABLE relationship ADD CONSTRAINT "relationship_rt_id_fkey" FOREIGN KEY (rt_id) REFERENCES relationship_type(rt_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE property DROP CONSTRAINT "property_changed_by_fkey";
ALTER TABLE property ADD CONSTRAINT "property_changed_by_fkey" FOREIGN KEY (changed_by) REFERENCES usr(user_no)
    ON UPDATE CASCADE ON DELETE SET DEFAULT DEFERRABLE;


SELECT new_db_revision(1,2,5, 'Mai' );

COMMIT;
ROLLBACK;

