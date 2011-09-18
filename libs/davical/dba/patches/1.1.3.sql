
-- Bleah.  Missed another permission.

BEGIN;
SELECT check_db_revision(1,1,2);

UPDATE relationship_type SET rt_isgroup = TRUE WHERE rt_id = 3 AND NOT rt_isgroup;

SELECT new_db_revision(1,1,3, 'March' );

COMMIT;
ROLLBACK;

