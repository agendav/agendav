
-- Adding lock support

BEGIN;
SELECT check_db_revision(1,1,5);

CREATE TABLE locks (
  dav_name TEXT,
  opaquelocktoken TEXT UNIQUE NOT NULL,
  type TEXT,
  scope TEXT,
  depth INT,
  owner TEXT,
  timeout INTERVAL,
  start TIMESTAMP DEFAULT current_timestamp
);

CREATE INDEX locks_dav_name_idx ON locks(dav_name);
GRANT SELECT,INSERT,UPDATE,DELETE ON locks TO general;

CREATE TABLE property (
  dav_name TEXT,
  property_name TEXT,
  property_value TEXT,
  changed_on TIMESTAMP DEFAULT current_timestamp,
  changed_by INT REFERENCES usr ( user_no ),
  PRIMARY KEY ( dav_name, property_name )
);

CREATE INDEX properties_dav_name_idx ON property(dav_name);
GRANT SELECT,INSERT,UPDATE,DELETE ON property TO general;

UPDATE relationship_type SET confers = 'A' WHERE rt_id = 1;
UPDATE relationship_type SET confers = 'RW' WHERE rt_id = 2;
UPDATE relationship_type SET confers = 'R' WHERE rt_id = 3;
UPDATE relationship_type SET confers = 'A' WHERE rt_id = 4;

INSERT INTO relationship_type ( rt_id, rt_name, rt_isgroup, confers, prefix_match )
    VALUES( 5, 'Can see free/busy time of', FALSE, 'F', '' );

SELECT new_db_revision(1,1,6, 'June' );
COMMIT;
ROLLBACK;

