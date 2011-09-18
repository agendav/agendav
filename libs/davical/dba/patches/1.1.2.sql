
-- Bleah.  Missed another permission.

BEGIN;
SELECT check_db_revision(1,1,1);

GRANT DELETE ON
    tmp_password
  , role_member
  TO general;

SELECT new_db_revision(1,1,2, 'February' );

COMMIT;
ROLLBACK;

