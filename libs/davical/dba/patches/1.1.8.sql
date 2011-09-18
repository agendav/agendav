
-- Adding freebusy tickets

BEGIN;
SELECT check_db_revision(1,1,7);

CREATE TABLE freebusy_ticket (
  ticket_id TEXT NOT NULL PRIMARY KEY,
  user_no integer NOT NULL REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE,
  created timestamp with time zone DEFAULT current_timestamp NOT NULL
);

GRANT INSERT,SELECT,UPDATE,DELETE ON TABLE freebusy_ticket TO general;

SELECT new_db_revision(1,1,8, 'August' );
COMMIT;
ROLLBACK;

