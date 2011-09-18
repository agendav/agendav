-- SQL file for AWL tables

-- Table for holding the schema version so we can be more structured in future
CREATE TABLE awl_db_revision (
   schema_id INT4,
   schema_major INT4,
   schema_minor INT4,
   schema_patch INT4,
   schema_name TEXT,
   applied_on TIMESTAMP WITH TIME ZONE DEFAULT current_timestamp
);

CREATE or REPLACE FUNCTION check_db_revision( INT, INT, INT ) RETURNS BOOLEAN AS '
   DECLARE
      major ALIAS FOR $1;
      minor ALIAS FOR $2;
      patch ALIAS FOR $3;
      matching INT;
   BEGIN
      SELECT COUNT(*) INTO matching FROM awl_db_revision
             WHERE (schema_major = major AND schema_minor = minor AND schema_patch > patch)
                OR (schema_major = major AND schema_minor > minor)
                OR (schema_major > major)
             ;
      IF matching >= 1 THEN
        RAISE EXCEPTION ''Database revisions after %.%.% have already been applied.'', major, minor, patch;
        RETURN FALSE;
      END IF;
      SELECT COUNT(*) INTO matching FROM awl_db_revision
                      WHERE schema_major = major AND schema_minor = minor AND schema_patch = patch;
      IF matching >= 1 THEN
        RETURN TRUE;
      END IF;
      RAISE EXCEPTION ''Database has not been upgraded to %.%.%'', major, minor, patch;
      RETURN FALSE;
   END;
' LANGUAGE 'plpgsql';

-- The schema_id should always be incremented.  The major / minor / patch level should
-- be incremented as seems appropriate...
CREATE or REPLACE FUNCTION new_db_revision( INT, INT, INT, TEXT ) RETURNS VOID AS '
   DECLARE
      major ALIAS FOR $1;
      minor ALIAS FOR $2;
      patch ALIAS FOR $3;
      blurb ALIAS FOR $4;
      new_id INT;
   BEGIN
      SELECT MAX(schema_id) + 1 INTO new_id FROM awl_db_revision;
      IF NOT FOUND OR new_id IS NULL THEN
        new_id := 1;
      END IF;
      INSERT INTO awl_db_revision (schema_id, schema_major, schema_minor, schema_patch, schema_name)
                    VALUES( new_id, major, minor, patch, blurb );
      RETURN;
   END;
' LANGUAGE 'plpgsql';
SELECT new_db_revision(1,1,0, 'Dawn' );
