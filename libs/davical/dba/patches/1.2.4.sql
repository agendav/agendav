
-- This database update provides new tables for the Principal, for
-- a consistent dav_resource which a principal, collection or calendar_item
-- all inherit from.

BEGIN;
SELECT check_db_revision(1,2,3);

-- Add a column to hold the 'COMPLETED' property from the caldav_data
ALTER TABLE calendar_item ADD COLUMN completed TIMESTAMP WITH TIME ZONE;

SELECT new_db_revision(1,2,4, 'Avril' );

COMMIT;
ROLLBACK;

