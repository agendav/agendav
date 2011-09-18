/**
* PostgreSQL Functions for CalDAV handling
*
* @package rscds
* @subpackage database
* @author Andrew McMillan <andrew@catalyst.net.nz>
* @copyright Catalyst IT Ltd
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2
*/

CREATE or REPLACE FUNCTION apply_month_byday( TIMESTAMP WITH TIME ZONE, TEXT ) RETURNS TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  in_time ALIAS FOR $1;
  byday ALIAS FOR $2;
  weeks INT;
  dow INT;
  temp_txt TEXT;
  dd INT;
  mm INT;
  yy INT;
  our_dow INT;
  our_answer TIMESTAMP WITH TIME ZONE;
BEGIN
  dow := position(substring( byday from '..$') in 'SUMOTUWETHFRSA') / 2;
  temp_txt   := substring(byday from '([0-9]+)');
  weeks      := temp_txt::int;

  -- RAISE NOTICE 'DOW: %, Weeks: %(%s)', dow, weeks, temp_txt;

  IF substring(byday for 1) = '-' THEN
    -- Last XX of month, or possibly second-to-last, but unlikely
    mm := extract( 'month' from in_time);
    yy := extract( 'year' from in_time);

    -- Start with the last day of the month
    our_answer := (yy::text || '-' || (mm+1)::text || '-01')::timestamp - '1 day'::interval;
    dd := extract( 'dow' from our_answer);
    dd := dd - dow;
    IF dd < 0 THEN
      dd := dd + 7;
    END IF;

    -- Having calculated the right day of the month, we now apply that back to in_time
    -- which contains the otherwise-unobtainable timezone detail (and the time)
    our_answer = our_answer - (dd::text || 'days')::interval;
    dd := extract( 'day' from our_answer) - extract( 'day' from in_time);
    our_answer := in_time + (dd::text || 'days')::interval;

    IF weeks > 1 THEN
      weeks := weeks - 1;
      our_answer := our_answer - (weeks::text || 'weeks')::interval;
    END IF;

  ELSE

    -- Shift our date to the correct day of week..
    our_dow := extract( 'dow' from in_time);
    our_dow := our_dow - dow;
    dd := extract( 'day' from in_time);
    IF our_dow >= dd THEN
      our_dow := our_dow - 7;
    END IF;
    our_answer := in_time - (our_dow::text || 'days')::interval;
    dd = extract( 'day' from our_answer);

    -- Shift the date to the correct week...
    dd := weeks - ((dd+6) / 7);
    IF dd != 0 THEN
      our_answer := our_answer + ((dd::text || 'weeks')::interval);
    END IF;

  END IF;

  RETURN our_answer;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


CREATE or REPLACE FUNCTION calculate_later_timestamp( TIMESTAMP WITH TIME ZONE, TIMESTAMP WITH TIME ZONE, TEXT ) RETURNS TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  earliest ALIAS FOR $1;
  basedate ALIAS FOR $2;
  repeatrule ALIAS FOR $3;
  frequency TEXT;
  temp_txt TEXT;
  length INT;
  count INT;
  byday TEXT;
  bymonthday INT;
  basediff INTERVAL;
  past_repeats INT8;
  units TEXT;
  dow TEXT;
  our_answer TIMESTAMP WITH TIME ZONE;
  loopcount INT;
BEGIN
  IF basedate > earliest THEN
    RETURN basedate;
  END IF;

  temp_txt   := substring(repeatrule from 'UNTIL=([0-9TZ]+)(;|$)');
  IF temp_txt IS NOT NULL AND temp_txt::timestamp with time zone < earliest THEN
    RETURN NULL;
  END IF;

  frequency  := substring(repeatrule from 'FREQ=([A-Z]+)(;|$)');
  IF frequency IS NULL THEN
    RETURN NULL;
  END IF;

  past_repeats = 0;
  length = 1;
  temp_txt   := substring(repeatrule from 'INTERVAL=([0-9]+)(;|$)');
  IF temp_txt IS NOT NULL THEN
    length     := temp_txt::int;
    basediff   := earliest - basedate;

    -- RAISE NOTICE 'Frequency: %, Length: %(%), Basediff: %', frequency, length, temp_txt, basediff;

    -- Calculate the number of past periods between our base date and our earliest date
    IF frequency = 'WEEKLY' OR frequency = 'DAILY' THEN
      past_repeats := extract('epoch' from basediff)::INT8 / 86400;
      -- RAISE NOTICE 'Days: %', past_repeats;
      IF frequency = 'WEEKLY' THEN
        past_repeats := past_repeats / 7;
      END IF;
    ELSE
      past_repeats = extract( 'years' from basediff );
      IF frequency = 'MONTHLY' THEN
        past_repeats = (past_repeats *12) + extract( 'months' from basediff );
      END IF;
    END IF;
    IF length IS NOT NULL THEN
      past_repeats = (past_repeats / length) + 1;
    END IF;
  END IF;

  -- Check that we have not exceeded the COUNT= limit
  temp_txt := substring(repeatrule from 'COUNT=([0-9]+)(;|$)');
  IF temp_txt IS NOT NULL THEN
    count := temp_txt::int;
    -- RAISE NOTICE 'Periods: %, Count: %(%), length: %', past_repeats, count, temp_txt, length;
    IF ( count <= past_repeats ) THEN
      RETURN NULL;
    END IF;
  ELSE
    count := NULL;
  END IF;

  temp_txt := substring(repeatrule from 'BYSETPOS=([0-9-]+)(;|$)');
  byday := substring(repeatrule from 'BYDAY=([0-9A-Z,]+-)(;|$)');
  IF byday IS NOT NULL AND frequency = 'MONTHLY' THEN
    -- Since this could move the date around a month we go back one
    -- period just to be extra sure.
    past_repeats = past_repeats - 1;

    IF temp_txt IS NOT NULL THEN
      -- Crudely hack the BYSETPOS onto the front of BYDAY.  While this
      -- is not as per rfc2445, RRULE syntax is so complex and overblown
      -- that nobody correctly uses comma-separated BYDAY or BYSETPOS, and
      -- certainly not within a MONTHLY RRULE.
      byday := temp_txt || byday;
    END IF;
  END IF;

  past_repeats = past_repeats * length;

  units := CASE
    WHEN frequency = 'DAILY' THEN 'days'
    WHEN frequency = 'WEEKLY' THEN 'weeks'
    WHEN frequency = 'MONTHLY' THEN 'months'
    WHEN frequency = 'YEARLY' THEN 'years'
  END;

  temp_txt   := substring(repeatrule from 'BYMONTHDAY=([0-9,]+)(;|$)');
  bymonthday := temp_txt::int;

  -- With all of the above calculation, this date should be close to (but less than)
  -- the target, and we should only loop once or twice.
  our_answer := basedate + (past_repeats::text || units)::interval;

  IF our_answer IS NULL THEN
    RAISE EXCEPTION 'our_answer IS NULL! basedate:% past_repeats:% units:%', basedate, past_repeats, units;
  END IF;


  loopcount := 500;  -- Desirable to stop an infinite loop if there is something we cannot handle
  LOOP
    -- RAISE NOTICE 'Testing date: %', our_answer;
    IF frequency = 'DAILY' THEN
      IF byday IS NOT NULL THEN
        LOOP
          dow = substring( to_char( our_answer, 'DY' ) for 2);
          EXIT WHEN byday ~* dow;
          -- Increment for our next time through the loop...
          our_answer := our_answer + (length::text || units)::interval;
        END LOOP;
      END IF;
    ELSIF frequency = 'WEEKLY' THEN
      -- Weekly repeats are only on specific days
      -- This is really not right, since a WEEKLY on MO,WE,FR should
      -- occur three times each week and this will only be once a week.
      dow = substring( to_char( our_answer, 'DY' ) for 2);
    ELSIF frequency = 'MONTHLY' THEN
      IF byday IS NOT NULL THEN
        -- This works fine, except that maybe there are multiple BYDAY
        -- components.  e.g. 1TU,3TU might be 1st & 3rd tuesdays.
        our_answer := apply_month_byday( our_answer, byday );
      ELSE
        -- If we did not get a BYDAY= then we kind of have to assume it is the same day each month
        our_answer := our_answer + '1 month'::interval;
      END IF;
    ELSIF bymonthday IS NOT NULL AND frequency = 'MONTHLY' AND bymonthday < 1 THEN
      -- We do not deal with this situation at present
      RAISE NOTICE 'The case of negative BYMONTHDAY is not handled yet.';
    END IF;

    EXIT WHEN our_answer >= earliest;

    -- Give up if we have exceeded the count
    IF ( count IS NOT NULL AND past_repeats > count ) THEN
      RETURN NULL;
    ELSE
      past_repeats := past_repeats + 1;
    END IF;

    loopcount := loopcount - 1;
    IF loopcount < 0 THEN
      RAISE NOTICE 'Giving up on repeat rule "%" - after 100 increments from % we are still not after %', repeatrule, basedate, earliest;
      RETURN NULL;
    END IF;

    -- Increment for our next time through the loop...
    our_answer := our_answer + (length::text || units)::interval;

  END LOOP;

  RETURN our_answer;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


CREATE or REPLACE FUNCTION usr_is_role( INT, TEXT ) RETURNS BOOLEAN AS $$
  SELECT EXISTS( SELECT 1 FROM role_member JOIN roles USING(role_no) WHERE role_member.user_no=$1 AND roles.role_name=$2 )
$$ LANGUAGE 'sql' IMMUTABLE STRICT;

CREATE or REPLACE FUNCTION legacy_get_permissions( INT, INT ) RETURNS TEXT AS $$
DECLARE
  in_from ALIAS FOR $1;
  in_to   ALIAS FOR $2;
  out_confers TEXT;
  tmp_confers1 TEXT;
  tmp_confers2 TEXT;
  tmp_txt TEXT;
  dbg TEXT DEFAULT '';
  r RECORD;
  counter INT;
BEGIN
  -- Self can always have full access
  IF in_from = in_to THEN
    RETURN 'A';
  END IF;

  -- dbg := 'S-';
  SELECT rt1.confers INTO out_confers FROM relationship r1 JOIN relationship_type rt1 USING ( rt_id )
                    WHERE r1.from_user = in_from AND r1.to_user = in_to AND NOT usr_is_role(r1.to_user,'Group');
  IF FOUND THEN
    RETURN dbg || out_confers;
  END IF;
  -- RAISE NOTICE 'No simple relationships between % and %', in_from, in_to;

  out_confers := '';
  FOR r IN SELECT rt1.confers AS r1, rt2.confers AS r2 FROM relationship r1 JOIN relationship_type rt1 USING(rt_id)
              JOIN relationship r2 ON r1.to_user=r2.from_user JOIN relationship_type rt2 ON r2.rt_id=rt2.rt_id
         WHERE r1.from_user=in_from AND r2.to_user=in_to
           AND EXISTS( SELECT 1 FROM role_member JOIN roles USING(role_no) WHERE role_member.user_no=r1.to_user AND roles.role_name='Group')
           AND NOT EXISTS( SELECT 1 FROM role_member JOIN roles USING(role_no) WHERE role_member.user_no=r2.to_user AND roles.role_name='Group')
           AND NOT EXISTS( SELECT 1 FROM role_member JOIN roles USING(role_no) WHERE role_member.user_no=r1.from_user AND roles.role_name='Group')
  LOOP
    -- RAISE NOTICE 'Permissions to group % from group %', r.r1, r.r2;
    -- FIXME: This is an oversimplification
    -- dbg := 'C-';
    tmp_confers1 := r.r1;
    tmp_confers2 := r.r2;
    IF tmp_confers1 != tmp_confers2 THEN
      IF tmp_confers1 ~* 'A' THEN
        -- Ensure that A is expanded to all supported privs before being used as a mask
        tmp_confers1 := 'AFBRWU';
      END IF;
      IF tmp_confers2 ~* 'A' THEN
        -- Ensure that A is expanded to all supported privs before being used as a mask
        tmp_confers2 := 'AFBRWU';
      END IF;
      -- RAISE NOTICE 'Expanded permissions to group % from group %', tmp_confers1, tmp_confers2;
      tmp_txt = '';
      FOR counter IN 1 .. length(tmp_confers2) LOOP
        IF tmp_confers1 ~* substring(tmp_confers2,counter,1) THEN
          tmp_txt := tmp_txt || substring(tmp_confers2,counter,1);
        END IF;
      END LOOP;
      tmp_confers2 := tmp_txt;
    END IF;
    FOR counter IN 1 .. length(tmp_confers2) LOOP
      IF NOT out_confers ~* substring(tmp_confers2,counter,1) THEN
        out_confers := out_confers || substring(tmp_confers2,counter,1);
      END IF;
    END LOOP;
  END LOOP;
  IF out_confers ~* 'A' OR (out_confers ~* 'B' AND out_confers ~* 'F' AND out_confers ~* 'R' AND out_confers ~* 'W' AND out_confers ~* 'U') THEN
    out_confers := 'A';
  END IF;
  IF out_confers != '' THEN
    RETURN dbg || out_confers;
  END IF;

  -- RAISE NOTICE 'No complex relationships between % and %', in_from, in_to;

  SELECT rt1.confers INTO out_confers, tmp_confers1 FROM relationship r1 JOIN relationship_type rt1 ON ( r1.rt_id = rt1.rt_id )
              LEFT OUTER JOIN relationship r2 ON ( rt1.rt_id = r2.rt_id )
       WHERE r1.from_user = in_from AND r2.from_user = in_to AND r1.from_user != r2.from_user AND r1.to_user = r2.to_user
         AND NOT EXISTS( SELECT 1 FROM relationship r3 WHERE r3.from_user = r1.to_user )
          AND usr_is_role(r1.to_user,'Group');

  IF FOUND THEN
    -- dbg := 'H-';
    -- RAISE NOTICE 'Permissions to shared group % ', out_confers;
    RETURN dbg || out_confers;
  END IF;

  -- RAISE NOTICE 'No common group relationships between % and %', in_from, in_to;

  RETURN '';
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


-- Function to convert a PostgreSQL date into UTC + the format used by iCalendar
CREATE or REPLACE FUNCTION to_ical_utc( TIMESTAMP WITH TIME ZONE ) RETURNS TEXT AS $$
  SELECT to_char( $1 at time zone 'UTC', 'YYYYMMDD"T"HH24MISS"Z"' )
$$ LANGUAGE 'sql' IMMUTABLE STRICT;

-- Function to set an arbitrary DAV property
CREATE or REPLACE FUNCTION set_dav_property( TEXT, INTEGER, TEXT, TEXT ) RETURNS BOOLEAN AS $$
DECLARE
  path ALIAS FOR $1;
  change_user ALIAS FOR $2;
  key ALIAS FOR $3;
  value ALIAS FOR $4;
  tmp_int INT;
BEGIN
  -- Check that there is either a resource, collection or user at this location.
  IF NOT EXISTS(        SELECT 1 FROM caldav_data WHERE dav_name = path
                  UNION SELECT 1 FROM collection WHERE dav_name = path
                  UNION SELECT 1 FROM dav_principal WHERE dav_name = path
                  UNION SELECT 1 FROM dav_binding WHERE dav_name = path
               ) THEN
    RETURN FALSE;
  END IF;
  SELECT changed_by INTO tmp_int FROM property WHERE dav_name = path AND property_name = key;
  IF FOUND THEN
    UPDATE property SET changed_by=change_user, changed_on=current_timestamp, property_value=value WHERE dav_name = path AND property_name = key;
  ELSE
    INSERT INTO property ( dav_name, changed_by, changed_on, property_name, property_value ) VALUES( path, change_user, current_timestamp, key, value );
  END IF;
  RETURN TRUE;
END;
$$ LANGUAGE 'plpgsql' STRICT;

-- List a user's relationships as a text string
CREATE or REPLACE FUNCTION relationship_list( INT8 ) RETURNS TEXT AS $$
DECLARE
  user ALIAS FOR $1;
  r RECORD;
  rlist TEXT;
BEGIN
  rlist := '';
  FOR r IN SELECT rt_name, fullname FROM relationship
                          LEFT JOIN relationship_type USING(rt_id) LEFT JOIN usr tgt ON to_user = tgt.user_no
                          WHERE from_user = user
  LOOP
    rlist := rlist
             || CASE WHEN rlist = '' THEN '' ELSE ', ' END
             || r.rt_name || '(' || r.fullname || ')';
  END LOOP;
  RETURN rlist;
END;
$$ LANGUAGE 'plpgsql';

DROP FUNCTION rename_davical_user( TEXT, TEXT );
DROP TRIGGER usr_modified ON usr CASCADE;
CREATE or REPLACE FUNCTION usr_modified() RETURNS TRIGGER AS $$
DECLARE
  oldpath TEXT;
  newpath TEXT;
BEGIN
  -- in case we trigger on other events in future
  IF TG_OP = 'UPDATE' THEN
    IF NEW.username != OLD.username THEN
      oldpath := '/' || OLD.username || '/';
      newpath := '/' || NEW.username || '/';
      UPDATE collection
        SET parent_container = replace( parent_container, oldpath, newpath),
            dav_name = replace( dav_name, oldpath, newpath)
      WHERE substring(dav_name from 1 for char_length(oldpath)) = oldpath;
    END IF;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER usr_modified AFTER UPDATE ON usr
    FOR EACH ROW EXECUTE PROCEDURE usr_modified();


DROP TRIGGER collection_modified ON collection CASCADE;
CREATE or REPLACE FUNCTION collection_modified() RETURNS TRIGGER AS $$
DECLARE
BEGIN
  -- in case we trigger on other events in future
  IF TG_OP = 'UPDATE' THEN
    IF NEW.dav_name != OLD.dav_name THEN
      UPDATE caldav_data
        SET dav_name = replace( dav_name, OLD.dav_name, NEW.dav_name),
            user_no = NEW.user_no
      WHERE substring(dav_name from 1 for char_length(OLD.dav_name)) = OLD.dav_name;
    END IF;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER collection_modified AFTER UPDATE ON collection
    FOR EACH ROW EXECUTE PROCEDURE collection_modified();


DROP TRIGGER caldav_data_modified ON caldav_data CASCADE;
CREATE or REPLACE FUNCTION caldav_data_modified() RETURNS TRIGGER AS $$
DECLARE
  coll_id caldav_data.collection_id%TYPE;
BEGIN
  IF TG_OP = 'UPDATE' THEN
    IF NEW.caldav_data = OLD.caldav_data AND NEW.collection_id = OLD.collection_id THEN
      -- Nothing for us to do
      RETURN NEW;
    END IF;
  END IF;

  IF TG_OP = 'INSERT' OR TG_OP = 'UPDATE' THEN
    -- On insert or update modified, we set the NEW collection tag to the md5 of the
    -- etag of the updated row which gives us something predictable for our regression
    -- tests, but something different from the actual etag of the new event.
    UPDATE collection
       SET modified = current_timestamp, dav_etag = md5(NEW.dav_etag)
     WHERE collection_id = NEW.collection_id;
    IF TG_OP = 'INSERT' THEN
      RETURN NEW;
    END IF;
  END IF;

  IF TG_OP = 'DELETE' THEN
    -- On delete we set the OLD collection tag to the md5 of the old path & the old
    -- etag, which again gives us something predictable for our regression tests.
    UPDATE collection
       SET modified = current_timestamp, dav_etag = md5(OLD.dav_name::text||OLD.dav_etag)
     WHERE collection_id = OLD.collection_id;
    RETURN OLD;
  END IF;

  IF NEW.collection_id != OLD.collection_id THEN
    -- If we've switched the collection_id of this event, then we also need to update
    -- the etag of the old collection - as we do for delete.
    UPDATE collection
       SET modified = current_timestamp, dav_etag = md5(OLD.dav_name::text||OLD.dav_etag)
     WHERE collection_id = OLD.collection_id;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER caldav_data_modified AFTER INSERT OR UPDATE OR DELETE ON caldav_data
    FOR EACH ROW EXECUTE PROCEDURE caldav_data_modified();


DROP TRIGGER caldav_data_sync_dav_id ON caldav_data CASCADE;
DROP TRIGGER calendar_item_sync_dav_id ON calendar_item CASCADE;
CREATE or REPLACE FUNCTION sync_dav_id ( ) RETURNS TRIGGER AS $$
  DECLARE
  BEGIN

    IF TG_OP = 'DELETE' THEN
      -- Just let the ON DELETE CASCADE handle this case
      RETURN OLD;
    END IF;

    IF NEW.dav_id IS NULL THEN
      NEW.dav_id = nextval('dav_id_seq');
    END IF;

    IF TG_OP = 'UPDATE' THEN
      IF OLD.dav_id != NEW.dav_id OR OLD.collection_id != NEW.collection_id
                 OR OLD.user_no != NEW.user_no OR OLD.dav_name != NEW.dav_name THEN
        UPDATE calendar_item SET dav_id = NEW.dav_id, user_no = NEW.user_no,
                        collection_id = NEW.collection_id, dav_name = NEW.dav_name
            WHERE dav_name = OLD.dav_name OR dav_id = OLD.dav_id;
      END IF;
      RETURN NEW;
    END IF;

    UPDATE calendar_item SET dav_id = NEW.dav_id, user_no = NEW.user_no,
                    collection_id = NEW.collection_id, dav_name = NEW.dav_name
          WHERE dav_name = NEW.dav_name OR dav_id = NEW.dav_id;

    RETURN NEW;

  END
$$ LANGUAGE 'plpgsql';
CREATE TRIGGER caldav_data_sync_dav_id AFTER INSERT OR UPDATE ON caldav_data
    FOR EACH ROW EXECUTE PROCEDURE sync_dav_id();



-- New in 1.2.6

CREATE or REPLACE FUNCTION legacy_privilege_to_bits( TEXT ) RETURNS BIT(24) AS $$
DECLARE
  in_priv ALIAS FOR $1;
  out_bits BIT(24);
BEGIN
  out_bits := 0::BIT(24);
  IF in_priv ~* 'A' THEN
    out_bits = ~ out_bits;
    RETURN out_bits;
  END IF;

  -- The CALDAV:read-free-busy privilege MUST be aggregated in the DAV:read privilege.
  --    1 DAV:read
  --  512 CalDAV:read-free-busy
  -- 4096 CALDAV:schedule-query-freebusy
  IF in_priv ~* 'R' THEN
    out_bits := out_bits | 4609::BIT(24);
  END IF;

  -- DAV:write => DAV:write MUST contain DAV:bind, DAV:unbind, DAV:write-properties and DAV:write-content
  --    2 DAV:write-properties
  --    4 DAV:write-content
  --   64 DAV:bind
  --  128 DAV:unbind
  IF in_priv ~* 'W' THEN
    out_bits := out_bits |   198::BIT(24);
  END IF;

  --   64 DAV:bind
  IF in_priv ~* 'B' THEN
    out_bits := out_bits | 64::BIT(24);
  END IF;

  --  128 DAV:unbind
  IF in_priv ~* 'U' THEN
    out_bits := out_bits | 128::BIT(24);
  END IF;

  --  512 CalDAV:read-free-busy
  -- 4096 CALDAV:schedule-query-freebusy
  IF in_priv ~* 'F' THEN
    out_bits := out_bits | 4608::BIT(24);
  END IF;

  RETURN out_bits;
END
$$
LANGUAGE 'PlPgSQL' IMMUTABLE STRICT;

-- This legacy conversion function will eventually be removed, once all logic
-- has been converted to use bitmaps, or to use the bits_to_priv() output.
--
-- NOTE: Round-trip through this and then back through legacy_privilege_to_bits
--       function is lossy!  Through legacy_privilege_to_bits() and back through
--       this one is not.
--
CREATE or REPLACE FUNCTION bits_to_legacy_privilege( BIT(24) ) RETURNS TEXT AS $$
DECLARE
  in_bits ALIAS FOR $1;
  out_priv TEXT;
BEGIN
  out_priv := '';
  IF in_bits = (~ 0::BIT(24)) THEN
    out_priv = 'A';
    RETURN out_priv;
  END IF;

  -- The CALDAV:read-free-busy privilege MUST be aggregated in the DAV:read privilege.
  --    1 DAV:read
  --  512 CalDAV:read-free-busy
  -- 4096 CALDAV:schedule-query-freebusy
  IF (in_bits & 4609::BIT(24)) != 0::BIT(24) THEN
    IF (in_bits & 1::BIT(24)) != 0::BIT(24) THEN
      out_priv := 'R';
    ELSE
      out_priv := 'F';
    END IF;
  END IF;

  -- DAV:write => DAV:write MUST contain DAV:bind, DAV:unbind, DAV:write-properties and DAV:write-content
  --    2 DAV:write-properties
  --    4 DAV:write-content
  --   64 DAV:bind
  --  128 DAV:unbind
  IF (in_bits & 198::BIT(24)) != 0::BIT(24) THEN
    IF (in_bits & 6::BIT(24)) != 0::BIT(24) THEN
      out_priv := out_priv || 'W';
    ELSE
      IF (in_bits & 64::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || 'B';
      END IF;
      IF (in_bits & 128::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || 'U';
      END IF;
    END IF;
  END IF;

  RETURN out_priv;
END
$$
LANGUAGE 'PlPgSQL' IMMUTABLE STRICT;

CREATE or REPLACE FUNCTION get_permissions( INT, INT ) RETURNS TEXT AS $$
DECLARE
  in_from ALIAS FOR $1;
  in_to   ALIAS FOR $2;
  out_confers TEXT;
  bit_confers BIT(24);
  group_role_no INT;
  tmp_txt TEXT;
  dbg TEXT DEFAULT '';
  r RECORD;
  counter INT;
BEGIN
  -- Self can always have full access
  IF in_from = in_to THEN
    RETURN 'A';
  END IF;

  -- dbg := 'S-';
  SELECT bits_to_legacy_privilege(r1.confers) INTO out_confers FROM relationship r1
                    WHERE r1.from_user = in_from AND r1.to_user = in_to AND NOT usr_is_role(r1.to_user,'Group');
  IF FOUND THEN
    RETURN dbg || out_confers;
  END IF;
  -- RAISE NOTICE 'No simple relationships between % and %', in_from, in_to;

  SELECT bit_or(r1.confers & r2.confers) INTO bit_confers
              FROM relationship r1
              JOIN relationship r2 ON r1.to_user=r2.from_user
         WHERE r1.from_user=in_from AND r2.to_user=in_to
           AND r2.from_user IN (SELECT user_no FROM roles LEFT JOIN role_member USING(role_no) WHERE role_name='Group');
  IF bit_confers != 0::BIT(24) THEN
    RETURN dbg || bits_to_legacy_privilege(bit_confers);
  END IF;

  RETURN '';
  -- RAISE NOTICE 'No complex relationships between % and %', in_from, in_to;

  SELECT bits_to_legacy_privilege(r1.confers) INTO out_confers FROM relationship r1 LEFT OUTER JOIN relationship r2 ON(r1.to_user = r2.to_user)
       WHERE r1.from_user = in_from AND r2.from_user = in_to AND r1.from_user != r2.from_user
         AND NOT EXISTS( SELECT 1 FROM relationship r3 WHERE r3.from_user = r1.to_user ) ;

  IF FOUND THEN
    -- dbg := 'H-';
    -- RAISE NOTICE 'Permissions to shared group % ', out_confers;
    RETURN dbg || out_confers;
  END IF;

  -- RAISE NOTICE 'No common group relationships between % and %', in_from, in_to;

  RETURN '';
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


CREATE or REPLACE FUNCTION get_group_role_no() RETURNS INT AS $$
  SELECT role_no FROM roles WHERE role_name = 'Group'
$$ LANGUAGE 'SQL' IMMUTABLE;

CREATE or REPLACE FUNCTION has_legacy_privilege( INT, TEXT, INT ) RETURNS BOOLEAN AS $$
DECLARE
  in_from ALIAS FOR $1;
  in_legacy_privilege ALIAS FOR $2;
  in_to   ALIAS FOR $3;
  in_confers BIT(24);
  group_role_no INT;
BEGIN
  -- Self can always have full access
  IF in_from = in_to THEN
    RETURN TRUE;
  END IF;

  SELECT get_group_role_no() INTO group_role_no;
  SELECT legacy_privilege_to_bits(in_legacy_privilege) INTO in_confers;

  IF EXISTS(SELECT 1 FROM relationship WHERE from_user = in_from AND to_user = in_to
                      AND (in_confers & confers) = in_confers
                      AND NOT EXISTS(SELECT 1 FROM role_member WHERE to_user = user_no AND role_no = group_role_no) ) THEN
    -- A direct relationship from A to B that grants sufficient
    -- RAISE NOTICE 'Permissions directly granted';
    RETURN TRUE;
  END IF;

  IF EXISTS( SELECT 1 FROM relationship r1 JOIN relationship r2 ON r1.to_user=r2.from_user
         WHERE (in_confers & r1.confers & r2.confers) = in_confers
           AND r1.from_user=in_from AND r2.to_user=in_to
           AND r2.from_user IN (SELECT user_no FROM role_member WHERE role_no=group_role_no) ) THEN
    -- An indirect relationship from A to B via group G that grants sufficient
    -- RAISE NOTICE 'Permissions mediated via group';
    RETURN TRUE;
  END IF;

  IF EXISTS( SELECT 1 FROM relationship r1 JOIN relationship r2 ON r1.to_user=r2.to_user
         WHERE (in_confers & r1.confers & r2.confers) = in_confers
           AND r1.from_user=in_from AND r2.from_user=in_to
           AND r2.to_user IN (SELECT user_no FROM role_member WHERE role_no=group_role_no)
           AND NOT EXISTS(SELECT 1 FROM relationship WHERE from_user=r2.to_user) ) THEN
    -- An indirect reflexive relationship from both A & B to group G which grants sufficient
    -- RAISE NOTICE 'Permissions to shared group';
    RETURN TRUE;
  END IF;

  -- RAISE NOTICE 'No common group relationships between % and %', in_from, in_to;

  RETURN FALSE;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


-- Given a verbose DAV: or CalDAV: privilege name return the bitmask
CREATE or REPLACE FUNCTION privilege_to_bits( TEXT ) RETURNS BIT(24) AS $$
DECLARE
  raw_priv ALIAS FOR $1;
  in_priv TEXT;
BEGIN
  in_priv := trim(lower(regexp_replace(raw_priv, '^.*:', '')));
  IF in_priv = 'all' THEN
    RETURN ~ 0::BIT(24);
  END IF;

  RETURN (CASE
            WHEN in_priv = 'read'                            THEN  4609 -- 1 + 512 + 4096
            WHEN in_priv = 'write'                           THEN   198 -- 2 + 4 + 64 + 128
            WHEN in_priv = 'write-properties'                THEN     2
            WHEN in_priv = 'write-content'                   THEN     4
            WHEN in_priv = 'unlock'                          THEN     8
            WHEN in_priv = 'read-acl'                        THEN    16
            WHEN in_priv = 'read-current-user-privilege-set' THEN    32
            WHEN in_priv = 'bind'                            THEN    64
            WHEN in_priv = 'unbind'                          THEN   128
            WHEN in_priv = 'write-acl'                       THEN   256
            WHEN in_priv = 'read-free-busy'                  THEN  4608 --  512 + 4096
            WHEN in_priv = 'schedule-deliver'                THEN  7168 -- 1024 + 2048 + 4096
            WHEN in_priv = 'schedule-deliver-invite'         THEN  1024
            WHEN in_priv = 'schedule-deliver-reply'          THEN  2048
            WHEN in_priv = 'schedule-query-freebusy'         THEN  4096
            WHEN in_priv = 'schedule-send'                   THEN 57344 -- 8192 + 16384 + 32768
            WHEN in_priv = 'schedule-send-invite'            THEN  8192
            WHEN in_priv = 'schedule-send-reply'             THEN 16384
            WHEN in_priv = 'schedule-send-freebusy'          THEN 32768
          ELSE 0 END)::BIT(24);
END
$$
LANGUAGE 'PlPgSQL' IMMUTABLE STRICT;


-- Given an array of verbose DAV: or CalDAV: privilege names return the bitmask
CREATE or REPLACE FUNCTION privilege_to_bits( TEXT[] ) RETURNS BIT(24) AS $$
DECLARE
  raw_privs ALIAS FOR $1;
  in_priv TEXT;
  out_bits BIT(24);
  i INT;
  all_privs BIT(24);
  start INT;
  finish INT;
BEGIN
  out_bits := 0::BIT(24);
  all_privs := ~ out_bits;
  SELECT array_lower(raw_privs,1) INTO start;
  SELECT array_upper(raw_privs,1) INTO finish;
  FOR i IN start .. finish  LOOP
    SELECT out_bits | privilege_to_bits(raw_privs[i]) INTO out_bits;
    IF out_bits = 65535::BIT(24) THEN
      RETURN all_privs;
    END IF;
  END LOOP;
  RETURN out_bits;
END
$$
LANGUAGE 'PlPgSQL' IMMUTABLE STRICT;


-- NOTE: Round-trip through this and then back through privilege_to_bits
--       function is lossy!  Through privilege_to_bits() and back through
--       this one is not.
--
CREATE or REPLACE FUNCTION bits_to_privilege( BIT(24) ) RETURNS TEXT[] AS $$
DECLARE
  in_bits ALIAS FOR $1;
  out_priv TEXT[];
BEGIN
  IF in_bits = (~ 0::BIT(24)) THEN
    out_priv := out_priv || ARRAY['DAV:all'];
  END IF;

  IF (in_bits & 513::BIT(24)) != 0::BIT(24) THEN
    IF (in_bits & 1::BIT(24)) != 0::BIT(24) THEN
      out_priv := out_priv || ARRAY['DAV:read'];
    END IF;
    IF (in_bits & 512::BIT(24)) != 0::BIT(24) THEN
      out_priv := out_priv || ARRAY['caldav:read-free-busy'];
    END IF;
  END IF;

  IF (in_bits & 198::BIT(24)) != 0::BIT(24) THEN
    IF (in_bits & 198::BIT(24)) = 198::BIT(24) THEN
      out_priv := out_priv || ARRAY['DAV:write'];
    ELSE
      IF (in_bits & 2::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['DAV:write-properties'];
      END IF;
      IF (in_bits & 4::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['DAV:write-content'];
      END IF;
      IF (in_bits & 64::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['DAV:bind'];
      END IF;
      IF (in_bits & 128::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['DAV:unbind'];
      END IF;
    END IF;
  END IF;

  IF (in_bits & 8::BIT(24)) != 0::BIT(24) THEN
    out_priv := out_priv || ARRAY['DAV:unlock'];
  END IF;

  IF (in_bits & 16::BIT(24)) != 0::BIT(24) THEN
    out_priv := out_priv || ARRAY['DAV:read-acl'];
  END IF;

  IF (in_bits & 32::BIT(24)) != 0::BIT(24) THEN
    out_priv := out_priv || ARRAY['DAV:read-current-user-privilege-set'];
  END IF;

  IF (in_bits & 256::BIT(24)) != 0::BIT(24) THEN
    out_priv := out_priv || ARRAY['DAV:write-acl'];
  END IF;

  IF (in_bits & 7168::BIT(24)) != 0::BIT(24) THEN
    IF (in_bits & 7168::BIT(24)) = 7168::BIT(24) THEN
      out_priv := out_priv || ARRAY['caldav:schedule-deliver'];
    ELSE
      IF (in_bits & 1024::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['caldav:schedule-deliver-invite'];
      END IF;
      IF (in_bits & 2048::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['caldav:schedule-deliver-reply'];
      END IF;
      IF (in_bits & 4096::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['caldav:schedule-query-freebusy'];
      END IF;
    END IF;
  END IF;

  IF (in_bits & 57344::BIT(24)) != 0::BIT(24) THEN
    IF (in_bits & 57344::BIT(24)) = 57344::BIT(24) THEN
      out_priv := out_priv || ARRAY['caldav:schedule-send'];
    ELSE
      IF (in_bits & 8192::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['caldav:schedule-send-invite'];
      END IF;
      IF (in_bits & 16384::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['caldav:schedule-send-reply'];
      END IF;
      IF (in_bits & 32768::BIT(24)) != 0::BIT(24) THEN
        out_priv := out_priv || ARRAY['caldav:schedule-send-freebusy'];
      END IF;
    END IF;
  END IF;

  RETURN out_priv;
END
$$
LANGUAGE 'PlPgSQL' IMMUTABLE STRICT;


-- Expanded group memberships out to some depth
CREATE or REPLACE FUNCTION expand_memberships( INT8, INT ) RETURNS SETOF INT8 AS $$
  SELECT group_id FROM group_member WHERE member_id = $1
      UNION
  SELECT expanded.g_id FROM (SELECT CASE WHEN $2 > 0 THEN expand_memberships( group_id, $2 - 1) END AS g_id
                               FROM group_member WHERE member_id = $1) AS expanded
                       WHERE expanded.g_id IS NOT NULL;
$$ LANGUAGE 'SQL' STABLE STRICT;

-- Expanded group members out to some depth
CREATE or REPLACE FUNCTION expand_members( INT8, INT ) RETURNS SETOF INT8 AS $$
  SELECT member_id FROM group_member WHERE group_id = $1
      UNION
  SELECT expanded.m_id FROM (SELECT CASE WHEN $2 > 0 THEN expand_members( member_id, $2 - 1) END AS m_id
                               FROM group_member WHERE group_id = $1) AS expanded
                       WHERE expanded.m_id IS NOT NULL;
$$ LANGUAGE 'SQL' STABLE STRICT;




-- Privileges from accessor to grantor, by principal_id
CREATE or REPLACE FUNCTION pprivs( INT8, INT8, INT ) RETURNS BIT(24) AS $$
DECLARE
  in_accessor ALIAS FOR $1;
  in_grantor  ALIAS FOR $2;
  in_depth    ALIAS FOR $3;
  out_conferred BIT(24);
BEGIN
  out_conferred := 0::BIT(24);
  -- Self can always have full access
  IF in_grantor = in_accessor THEN
    RETURN ~ out_conferred;
  END IF;

  SELECT bit_or(subquery.privileges) INTO out_conferred FROM
    (
      SELECT privileges FROM grants WHERE by_principal=in_grantor AND by_collection IS NULL
                                          AND (to_principal=in_accessor OR to_principal IN (SELECT expand_memberships(in_accessor,in_depth)))
            UNION
      SELECT 32::BIT(24) AS privileges FROM expand_memberships(in_accessor,in_depth) WHERE expand_memberships = in_grantor
    ) AS subquery ;

  IF out_conferred IS NULL THEN
    SELECT default_privileges INTO out_conferred FROM principal WHERE principal_id = in_grantor;
  END IF;

  RETURN out_conferred;
END;
$$ LANGUAGE 'plpgsql' STABLE STRICT;


-- Privileges from accessor to grantor, by user_no
CREATE or REPLACE FUNCTION uprivs( INT8, INT8, INT ) RETURNS BIT(24) AS $$
DECLARE
  in_accessor ALIAS FOR $1;
  in_grantor  ALIAS FOR $2;
  in_depth    ALIAS FOR $3;
  out_conferred BIT(24);
BEGIN
  out_conferred := 0::BIT(24);
  -- Self can always have full access
  IF in_grantor = in_accessor THEN
    RETURN ~ out_conferred;
  END IF;

  SELECT pprivs( p1.principal_id, p2.principal_id, in_depth ) INTO out_conferred
          FROM principal p1, principal p2
          WHERE p1.user_no = in_accessor AND p2.user_no = in_grantor;

  RETURN out_conferred;
END;
$$ LANGUAGE 'plpgsql' STABLE STRICT;


-- Privileges from accessor (by principal_id) to path
CREATE or REPLACE FUNCTION path_privs( INT8, TEXT, INT ) RETURNS BIT(24) AS $$
DECLARE
  in_accessor ALIAS FOR $1;
  in_path  ALIAS FOR $2;
  in_depth    ALIAS FOR $3;

  alt1_path TEXT;
  alt2_path TEXT;
  grantor_collection    INT8;
  grantor_principal     INT8;
  collection_path       TEXT;
  collection_privileges BIT(24);
  out_conferred         BIT(24);
BEGIN
  out_conferred := 0::BIT(24);

  IF in_path ~ '^/?$' THEN
    -- RAISE NOTICE 'Collection is root: Collection: %', in_path;
    RETURN 1; -- basic read privileges on root directory
  END IF;

  -- We need to canonicalise the path, so:
  -- If it matches '/' + some characters (+ optional '/')  => a principal URL
  IF in_path ~ '^/[^/]+/?$' THEN
    alt1_path := replace(in_path, '/', '');
    SELECT pprivs(in_accessor,principal_id, in_depth) INTO out_conferred FROM usr JOIN principal USING(user_no) WHERE username = alt1_path;
    -- RAISE NOTICE 'Path is Principal: Principal: %, Collection: %, Permissions: %', in_accessor, in_path, out_conferred;
    RETURN out_conferred;
  END IF;

  -- Otherwise look for the longest segment matching up to the last '/', or if we append one, or if we replace a final '.ics' with one.
  alt1_path := in_path;
  IF alt1_path ~ E'\\.ics$' THEN
    alt1_path := substr(alt1_path, 1, length(alt1_path) - 4) || '/';
  END IF;
  alt2_path := regexp_replace( in_path, '[^/]*$', '');
  SELECT collection.collection_id, grantor.principal_id, collection.dav_name, collection.default_privileges
    INTO grantor_collection, grantor_principal, collection_path, collection_privileges
                      FROM collection JOIN principal grantor USING (user_no)
                      WHERE dav_name = in_path || '/' OR dav_name = alt1_path OR dav_name = alt2_path
                      ORDER BY LENGTH(collection.dav_name) DESC LIMIT 1;

  -- Self will always need full access to their own collections!
  IF grantor_principal = in_accessor THEN
    -- RAISE NOTICE 'Principal IS owner: Principal: %, Collection: %', in_accessor, in_path;
    RETURN ~ out_conferred;
  END IF;

  SELECT privileges INTO out_conferred FROM grants
                   WHERE by_collection = grantor_collection
                     AND (to_principal=in_accessor OR to_principal IN (SELECT expand_memberships(in_accessor,in_depth)));

  IF out_conferred IS NULL THEN
    IF collection_privileges IS NULL THEN
      IF grantor_principal IS NULL THEN
        alt1_path := regexp_replace( in_path, '/[^/]+/?$', '/');
        SELECT path_privs(in_accessor,alt1_path,in_depth) INTO out_conferred;
        -- RAISE NOTICE 'Collection is NULL: Principal: %, Collection: %, Permissions: %', in_accessor, in_path, out_conferred;
      ELSE
        SELECT pprivs(in_accessor,grantor_principal,in_depth) INTO out_conferred;
        -- RAISE NOTICE 'Collection priveleges are NULL: Principal: %, Collection: %, Permissions: %', in_accessor, in_path, out_conferred;
      END IF;
    ELSE
      out_conferred := collection_privileges;
      -- RAISE NOTICE 'Default Collection priveleges apply: Principal: %, Collection: %, Permissions: %', in_accessor, in_path, out_conferred;
    END IF;
  END IF;

  RETURN out_conferred;
END;
$$ LANGUAGE 'plpgsql' STABLE STRICT;


-- List a user's memberships as a text string
CREATE or REPLACE FUNCTION is_member_of_list( INT8 ) RETURNS TEXT AS $$
DECLARE
  in_member_id ALIAS FOR $1;
  m RECORD;
  mlist TEXT;
BEGIN
  mlist := '';
  FOR m IN SELECT displayname, group_id FROM group_member JOIN principal ON (group_id = principal_id)
                          WHERE member_id = in_member_id
  LOOP
    mlist := mlist
             || CASE WHEN mlist = '' THEN '' ELSE ', ' END
             || COALESCE( m.displayname, m.group_id::text);
  END LOOP;
  RETURN mlist;
END;
$$ LANGUAGE 'plpgsql' STRICT;


-- List a user's members as a text string
CREATE or REPLACE FUNCTION has_members_list( INT8 ) RETURNS TEXT AS $$
DECLARE
  in_member_id ALIAS FOR $1;
  m RECORD;
  mlist TEXT;
BEGIN
  mlist := '';
  FOR m IN SELECT displayname, group_id FROM group_member JOIN principal ON (member_id = principal_id)
                          WHERE group_id = in_member_id
  LOOP
    mlist := mlist
             || CASE WHEN mlist = '' THEN '' ELSE ', ' END
             || COALESCE( m.displayname, m.group_id::text);
  END LOOP;
  RETURN mlist;
END;
$$ LANGUAGE 'plpgsql' STRICT;


-- List the privileges as a text string
CREATE or REPLACE FUNCTION privileges_list( BIT(24) ) RETURNS TEXT AS $$
DECLARE
  in_privileges ALIAS FOR $1;
  privileges TEXT[];
  plist TEXT;
  start INT;
  finish INT;
  i INT;
BEGIN
  plist := '';

  privileges := bits_to_privilege(in_privileges);
  SELECT array_lower(privileges,1) INTO start;
  IF start IS NOT NULL THEN
    SELECT array_upper(privileges,1) INTO finish;
    FOR i IN start .. finish  LOOP
      plist := plist
              || CASE WHEN plist = '' THEN '' ELSE ', ' END
              || privileges[i];
    END LOOP;
  END IF;
  RETURN plist;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


DROP TRIGGER principal_modified ON principal CASCADE;
CREATE or REPLACE FUNCTION principal_modified() RETURNS TRIGGER AS $$
DECLARE
BEGIN
  -- in case we trigger on other events in future
  IF TG_OP = 'UPDATE' THEN
    IF NEW.type_id != OLD.type_id THEN
      UPDATE grants
        SET is_group = (NEW.type_id = 3)
      WHERE grants.to_principal = NEW.principal_id;
    END IF;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER principal_modified AFTER UPDATE ON principal
    FOR EACH ROW EXECUTE PROCEDURE principal_modified();


DROP TRIGGER grants_modified ON grants CASCADE;
CREATE or REPLACE FUNCTION grants_modified() RETURNS TRIGGER AS $$
DECLARE
  old_to_principal INT8;
  new_is_group BOOL;
BEGIN
  -- in case we trigger on other events in future
  IF TG_OP = 'INSERT' THEN
    old_to_principal := NULL;
  ELSE
    old_to_principal := OLD.to_principal;
  END IF;
  IF TG_OP = 'INSERT' OR NEW.to_principal != old_to_principal THEN
    SELECT (type_id = 3) INTO new_is_group FROM principal WHERE principal_id = NEW.to_principal;
    IF NEW.is_group != new_is_group THEN
      NEW.is_group := new_is_group;
    END IF;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER grants_modified AFTER INSERT OR UPDATE ON grants
    FOR EACH ROW EXECUTE PROCEDURE grants_modified();



-- An expanded list of the grants this principal has access to
CREATE or REPLACE FUNCTION p_has_proxy_access_to( INT8, INT ) RETURNS SETOF INT8 AS $$
  SELECT by_principal
    FROM (
      SELECT by_principal FROM grants
           WHERE to_principal IN (SELECT $1 UNION SELECT expand_memberships($1,$2))
             AND (privileges & 5::BIT(24)) != 0::BIT(24)
             AND by_collection IS NULL
             AND by_principal != $1
      UNION
      SELECT principal_id AS by_principal FROM principal
           WHERE (default_privileges & 5::BIT(24)) != 0::BIT(24)
             AND principal_id != $1
    ) subquery;
$$ LANGUAGE 'SQL' STABLE STRICT;


-- A list of the principals who can proxy to this principal
CREATE or REPLACE FUNCTION grants_proxy_access_from_p( INT8, INT ) RETURNS SETOF INT8 AS $$
  SELECT DISTINCT by_principal
    FROM grants
   WHERE by_collection IS NULL AND by_principal != $1
     AND by_principal IN (SELECT expand_members(g2.to_principal,$2) FROM grants g2 WHERE g2.by_principal = $1)
   ;
$$ LANGUAGE 'SQL' STABLE STRICT;



-- New in 1.2.7

CREATE or REPLACE FUNCTION write_sync_change( INT8, INT, TEXT ) RETURNS BOOLEAN AS $$
DECLARE
  in_collection_id ALIAS FOR $1;
  in_status ALIAS FOR $2;
  in_dav_name ALIAS FOR $3;
  tmp_int INT8;
BEGIN
  SELECT 1 INTO tmp_int FROM sync_tokens
           WHERE collection_id = in_collection_id
           LIMIT 1;
  IF NOT FOUND THEN
    RETURN FALSE;
  END IF;
  SELECT dav_id INTO tmp_int FROM caldav_data WHERE dav_name = in_dav_name;
  INSERT INTO sync_changes ( collection_id, sync_status, dav_id, dav_name)
                     VALUES( in_collection_id, in_status, tmp_int, in_dav_name);
  RETURN TRUE;
END
$$ LANGUAGE 'PlPgSQL' VOLATILE STRICT;


CREATE or REPLACE FUNCTION new_sync_token( INT8, INT8 ) RETURNS INT8 AS $$
DECLARE
  in_old_sync_token ALIAS FOR $1;
  in_collection_id ALIAS FOR $2;
  tmp_int INT8;
  old_modification_time sync_tokens.modification_time%TYPE;
BEGIN
  IF in_old_sync_token > 0 THEN
    SELECT modification_time INTO old_modification_time FROM sync_tokens WHERE sync_token = in_old_sync_token;
    IF NOT FOUND THEN
      -- They are in an inconsistent state: we return NULL so they can re-start the process
      RETURN NULL;
    END IF;
    SELECT 1 INTO tmp_int FROM sync_changes WHERE collection_id = in_collection_id AND sync_time > old_modification_time LIMIT 1;
    IF NOT FOUND THEN
      -- Ensure we return the latest sync_token we have for this collection, since there are
      -- no changes.
	  SELECT sync_token INTO tmp_int FROM sync_tokens WHERE collection_id = in_collection_id ORDER BY modification_time DESC LIMIT 1;
      RETURN tmp_int;
    END IF;
  END IF;
  SELECT nextval('sync_tokens_sync_token_seq') INTO tmp_int;
  INSERT INTO sync_tokens(collection_id, sync_token) VALUES( in_collection_id, tmp_int );
  RETURN tmp_int;
END
$$ LANGUAGE 'PlPgSQL' STRICT;


DROP TRIGGER alarm_changed ON calendar_alarm CASCADE;
CREATE or REPLACE FUNCTION alarm_changed() RETURNS TRIGGER AS $$
DECLARE
  oldcomponent TEXT;
  newcomponent TEXT;
BEGIN
  -- in case we trigger on other events in future
  IF TG_OP = 'UPDATE' THEN
    IF NEW.component != OLD.component THEN
      UPDATE caldav_data
         SET caldav_data = replace( caldav_data, OLD.component, NEW.component ),
             dav_etag = md5(replace( caldav_data, OLD.component, NEW.component ))
       WHERE caldav_data.dav_id = NEW.dav_id;
    END IF;
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER alarm_changed AFTER UPDATE ON calendar_alarm
    FOR EACH ROW EXECUTE PROCEDURE alarm_changed();
