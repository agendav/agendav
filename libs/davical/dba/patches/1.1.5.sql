
-- Starting to add internationalisation support

BEGIN;
SELECT check_db_revision(1,1,4);

UPDATE relationship_type SET rt_name = 'is Assistant to' WHERE rt_id = 2 AND rt_name = 'Is Assisted by';

SELECT new_db_revision(1,1,5, 'May' );

COMMIT;
ROLLBACK;

