
-- This database update provides new tables for the Principal, for
-- a consistent dav_resource which a principal, collection or calendar_item
-- all inherit from.

BEGIN;
SELECT check_db_revision(1,1,12);

-- Rename the caldav_data_dav_id_seq to dav_id_seq because we will use it
-- for more tables than just caldav_data
CREATE SEQUENCE dav_id_seq;
SELECT setval('dav_id_seq', nextval('caldav_data_dav_id_seq'));
ALTER TABLE caldav_data ALTER COLUMN dav_id SET DEFAULT nextval('dav_id_seq');
ALTER TABLE calendar_item ALTER COLUMN dav_id SET DEFAULT nextval('dav_id_seq');

CREATE or REPLACE FUNCTION sync_dav_id ( ) RETURNS TRIGGER AS '
  DECLARE
  BEGIN

    IF TG_OP = ''DELETE'' THEN
      -- Just let the ON DELETE CASCADE handle this case
      RETURN OLD;
    END IF;

    IF NEW.dav_id IS NULL THEN
      NEW.dav_id = nextval(''dav_id_seq'');
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

-- CREATE TRIGGER caldav_data_sync_dav_id AFTER INSERT OR UPDATE ON caldav_data
--     FOR EACH ROW EXECUTE PROCEDURE sync_dav_id();

-- CREATE TRIGGER calendar_item_sync_dav_id AFTER INSERT OR UPDATE ON calendar_item
--     FOR EACH ROW EXECUTE PROCEDURE sync_dav_id();


-- Add a numeric collection_id to collection
ALTER TABLE collection ADD COLUMN collection_id INT8;
UPDATE collection SET collection_id = nextval('dav_id_seq');
ALTER TABLE collection ALTER COLUMN collection_id SET DEFAULT nextval('dav_id_seq');
ALTER TABLE collection DROP CONSTRAINT collection_pkey CASCADE;
ALTER TABLE collection ADD UNIQUE (user_no,dav_name);
ALTER TABLE collection ADD CONSTRAINT collection_pkey PRIMARY KEY (collection_id);

ALTER TABLE calendar_item ADD COLUMN collection_id INT8;
INSERT INTO collection ( user_no, parent_container, dav_name, dav_etag, dav_displayname, is_calendar, created, modified)
  SELECT user_no, '/'||username||'/', '/'||username||'/home/', md5(user_no::text||'/'||username||'/home/'),
         fullname, TRUE, current_timestamp, current_timestamp
    FROM usr
   WHERE NOT EXISTS (SELECT 1 FROM collection WHERE dav_name ~ ('^/'||username||'/'));

UPDATE caldav_data SET dav_name = (select collection.dav_name FROM collection WHERE collection.user_no = caldav_data.user_no limit 1)
                                  || regexp_replace( caldav_data.dav_name, '^.*/([^/]+)$', 'ex-\\1')
            WHERE dav_name ~ '^/[^/]+/[^/]+$';
UPDATE calendar_item SET collection_id = collection.collection_id
          FROM collection WHERE collection.dav_name = regexp_replace( calendar_item.dav_name, '/[^/]+$', '/');
ALTER TABLE calendar_item ALTER COLUMN collection_id SET NOT NULL;
ALTER TABLE calendar_item ADD CONSTRAINT
       calendar_item_collection_id_fkey FOREIGN KEY (collection_id) REFERENCES collection(collection_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
CREATE INDEX calendar_item_collection_id_fkey ON calendar_item(collection_id);

ALTER TABLE caldav_data ADD COLUMN collection_id INT8;
UPDATE caldav_data SET collection_id = collection.collection_id
          FROM collection WHERE collection.dav_name = regexp_replace( caldav_data.dav_name, '/[^/]+$', '/');
ALTER TABLE caldav_data ALTER COLUMN collection_id SET NOT NULL;
ALTER TABLE caldav_data ADD CONSTRAINT
       caldav_data_collection_id_fkey FOREIGN KEY (collection_id) REFERENCES collection(collection_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE;
CREATE INDEX caldav_data_collection_id_fkey ON caldav_data(collection_id);

SELECT new_db_revision(1,2,1, 'Janvier' );

COMMIT;
ROLLBACK;

