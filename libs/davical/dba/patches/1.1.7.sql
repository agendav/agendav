
-- Adding lock support

BEGIN;
SELECT check_db_revision(1,1,6);

ALTER TABLE relationship_type DROP COLUMN rt_inverse;
ALTER TABLE relationship_type DROP COLUMN prefix_match;
ALTER TABLE relationship_type DROP COLUMN rt_isgroup;

UPDATE relationship_type SET rt_name ='Administers', confers = 'A' WHERE rt_id = 1;
UPDATE relationship_type SET rt_name ='is Assistant to', confers = 'RW' WHERE rt_id = 2;
UPDATE relationship_type SET rt_name ='Can read from', confers = 'R' WHERE rt_id = 3;
UPDATE relationship_type SET rt_name ='Can see free/busy time of', confers = 'F' WHERE rt_id = 4;

UPDATE relationship SET rt_id=1 WHERE rt_id=4;
UPDATE relationship SET rt_id=4 WHERE rt_id=5;

DELETE FROM relationship_type WHERE rt_id = 5;

-- Add a 'status' column to calendar_item which will contain the parsed value of the STATUS property
ALTER TABLE calendar_item ADD COLUMN status TEXT;
UPDATE calendar_item SET status = 'CONFIRMED';
UPDATE calendar_item SET status = 'CANCELLED'    WHERE calendar_item.dav_name IN (SELECT dav_name FROM caldav_data WHERE caldav_data.caldav_data ~ 'STATUS.*:.*CANCELLED');
UPDATE calendar_item SET status = 'TENTATIVE'    WHERE calendar_item.dav_name IN (SELECT dav_name FROM caldav_data WHERE caldav_data.caldav_data ~ 'STATUS.*:.*TENTATIVE');
UPDATE calendar_item SET status = 'NEEDS-ACTION' WHERE calendar_item.dav_name IN (SELECT dav_name FROM caldav_data WHERE caldav_data.caldav_data ~ 'STATUS.*:.*NEEDS-ACTION');
UPDATE calendar_item SET status = 'IN-PROCESS'   WHERE calendar_item.dav_name IN (SELECT dav_name FROM caldav_data WHERE caldav_data.caldav_data ~ 'STATUS.*:.*IN-PROCESS');
UPDATE calendar_item SET status = 'DRAFT'        WHERE calendar_item.dav_name IN (SELECT dav_name FROM caldav_data WHERE caldav_data.caldav_data ~ 'STATUS.*:.*DRAFT');
UPDATE calendar_item SET status = 'FINAL'        WHERE calendar_item.dav_name IN (SELECT dav_name FROM caldav_data WHERE caldav_data.caldav_data ~ 'STATUS.*:.*FINAL');

SELECT new_db_revision(1,1,7, 'July' );
COMMIT;
ROLLBACK;

