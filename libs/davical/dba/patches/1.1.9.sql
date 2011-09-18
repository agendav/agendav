
-- Adding a primary key to the calendar_item table

BEGIN;
SELECT check_db_revision(1,1,8);

ALTER TABLE calendar_item ADD PRIMARY KEY (user_no, dav_name );

SELECT new_db_revision(1,1,9, 'September' );
COMMIT;
ROLLBACK;

VACUUM FULL ANALYZE;