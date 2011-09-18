
-- Add a numeric foreign key link between caldav_data and calendar_item to
-- provide more efficient linking when the db has been initialised with a
-- non POSIX collation.


BEGIN;
SELECT check_db_revision(1,1,11);

-- Add a column to the collection table to allow us to mark collections
-- as publicly readable
ALTER TABLE collection ADD COLUMN publicly_readable BOOLEAN DEFAULT FALSE;

-- Add a numeric dav_id to link the caldav_data and calendar_item tables
ALTER TABLE caldav_data ADD COLUMN dav_id INT8;
ALTER TABLE calendar_item ADD COLUMN dav_id INT8;
CREATE SEQUENCE caldav_data_dav_id_seq;
GRANT SELECT,UPDATE ON caldav_data_dav_id_seq TO general;

CREATE or REPLACE FUNCTION sync_dav_id ( ) RETURNS TRIGGER AS '
  DECLARE
  BEGIN

    IF TG_OP = ''DELETE'' THEN
      -- Just let the ON DELETE CASCADE handle this case
      RETURN OLD;
    END IF;

    IF NEW.dav_id IS NULL THEN
      NEW.dav_id = nextval(''caldav_data_dav_id_seq'');
    END IF;

    IF TG_OP = ''UPDATE'' THEN
      IF OLD.dav_id = NEW.dav_id THEN
        -- Nothing to do
        RETURN NEW;
      END IF;
    END IF;

    IF TG_RELNAME = ''caldav_data'' THEN
      UPDATE calendar_item SET dav_id = NEW.dav_id WHERE user_no = NEW.user_no AND dav_name = NEW.dav_name;
    ELSE
      UPDATE caldav_data SET dav_id = NEW.dav_id WHERE user_no = NEW.user_no AND dav_name = NEW.dav_name;
    END IF;

    RETURN NEW;

  END
' LANGUAGE 'plpgsql';

CREATE TRIGGER caldav_data_sync_dav_id AFTER INSERT OR UPDATE ON caldav_data
    FOR EACH ROW EXECUTE PROCEDURE sync_dav_id();

CREATE TRIGGER calendar_item_sync_dav_id AFTER INSERT OR UPDATE ON calendar_item
    FOR EACH ROW EXECUTE PROCEDURE sync_dav_id();

-- Now, using the trigger, magically assign dav_id to all rows in caldav_data and calendar_item
UPDATE caldav_data SET dav_id = dav_id;

ALTER TABLE caldav_data ALTER COLUMN dav_id SET DEFAULT nextval('caldav_data_dav_id_seq');
ALTER TABLE caldav_data ALTER COLUMN dav_id SET NOT NULL;
ALTER TABLE caldav_data ADD CONSTRAINT caldav_data_dav_id_key UNIQUE (dav_id);

ALTER TABLE calendar_item ADD CONSTRAINT calendar_item_dav_id_key UNIQUE (dav_id);

SELECT new_db_revision(1,1,12, 'December' );

COMMIT;
ROLLBACK;

