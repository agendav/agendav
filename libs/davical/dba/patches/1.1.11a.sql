
-- Sort out accessing calendar entries.
-- This alternative patch file is the same in/out revision as 1.1.11 but it works with newer databases (8.x)

BEGIN;
SELECT check_db_revision(1,1,10);

ALTER TABLE caldav_data DROP CONSTRAINT "caldav_data_user_no_fkey";
ALTER TABLE caldav_data ADD CONSTRAINT "caldav_data_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

ALTER TABLE collection DROP CONSTRAINT "collection_user_no_fkey";
ALTER TABLE collection ADD CONSTRAINT "collection_user_no_fkey" FOREIGN KEY (user_no) REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;

SELECT new_db_revision(1,1,11, 'November' );
COMMIT;
ROLLBACK;

