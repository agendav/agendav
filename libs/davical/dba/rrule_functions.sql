/**
* PostgreSQL Functions for RRULE handling
*
* @package rscds
* @subpackage database
* @author Andrew McMillan <andrew@morphoss.com>
* @copyright Morphoss Ltd - http://www.morphoss.com/
* @license   http://gnu.org/copyleft/gpl.html GNU GPL v2 or later
*
* Coverage of this function set
*  - COUNT & UNTIL are handled, generally
*  - DAILY frequency, including BYDAY, BYMONTH, BYMONTHDAY, BYWEEKNO, BYMONTHDAY
*  - WEEKLY frequency, including BYDAY, BYMONTH, BYMONTHDAY, BYWEEKNO, BYSETPOS
*  - MONTHLY frequency, including BYDAY, BYMONTH, BYSETPOS
*  - YEARLY frequency, including BYMONTH, BYMONTHDAY, BYSETPOS, BYDAY
*
* Not covered as yet
*  - DAILY:   BYYEARDAY, BYSETPOS*
*  - WEEKLY:  BYYEARDAY
*  - MONTHLY: BYYEARDAY, BYMONTHDAY, BYWEEKNO
*  - YEARLY:  BYYEARDAY
*  - SECONDLY
*  - MINUTELY
*  - HOURLY
*
*/

-- Create a composite type for the parts of the RRULE.
DROP TYPE rrule_parts CASCADE;
CREATE TYPE rrule_parts AS (
  base TIMESTAMP WITH TIME ZONE,
  until TIMESTAMP WITH TIME ZONE,
  freq TEXT,
  count INT,
  interval INT,
  bysecond INT[],
  byminute INT[],
  byhour INT[],
  bymonthday INT[],
  byyearday INT[],
  byweekno INT[],
  byday TEXT[],
  bymonth INT[],
  bysetpos INT[],
  wkst TEXT
);


-- Create a function to parse the RRULE into it's composite type
CREATE or REPLACE FUNCTION parse_rrule_parts( TIMESTAMP WITH TIME ZONE, TEXT ) RETURNS rrule_parts AS $$
DECLARE
  basedate   ALIAS FOR $1;
  repeatrule ALIAS FOR $2;
  result rrule_parts%ROWTYPE;
  tempstr TEXT;
BEGIN
  result.base       := basedate;
  result.until      := substring(repeatrule from 'UNTIL=([0-9TZ]+)(;|$)');
  result.freq       := substring(repeatrule from 'FREQ=([A-Z]+)(;|$)');
  result.count      := substring(repeatrule from 'COUNT=([0-9]+)(;|$)');
  result.interval   := COALESCE(substring(repeatrule from 'INTERVAL=([0-9]+)(;|$)')::int, 1);
  result.wkst       := substring(repeatrule from 'WKST=(MO|TU|WE|TH|FR|SA|SU)(;|$)');

  result.byday      := string_to_array( substring(repeatrule from 'BYDAY=(([+-]?[0-9]{0,2}(MO|TU|WE|TH|FR|SA|SU),?)+)(;|$)'), ',');

  result.byyearday  := string_to_array(substring(repeatrule from 'BYYEARDAY=([0-9,+-]+)(;|$)'), ',');
  result.byweekno   := string_to_array(substring(repeatrule from 'BYWEEKNO=([0-9,+-]+)(;|$)'), ',');
  result.bymonthday := string_to_array(substring(repeatrule from 'BYMONTHDAY=([0-9,+-]+)(;|$)'), ',');
  result.bymonth    := string_to_array(substring(repeatrule from 'BYMONTH=(([+-]?[0-1]?[0-9],?)+)(;|$)'), ',');
  result.bysetpos   := string_to_array(substring(repeatrule from 'BYSETPOS=(([+-]?[0-9]{1,3},?)+)(;|$)'), ',');

  result.bysecond   := string_to_array(substring(repeatrule from 'BYSECOND=([0-9,]+)(;|$)'), ',');
  result.byminute   := string_to_array(substring(repeatrule from 'BYMINUTE=([0-9,]+)(;|$)'), ',');
  result.byhour     := string_to_array(substring(repeatrule from 'BYHOUR=([0-9,]+)(;|$)'), ',');

  RETURN result;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


-- Return a SETOF dates within the month of a particular date which match a string of BYDAY rule specifications
CREATE or REPLACE FUNCTION rrule_month_byday_set( TIMESTAMP WITH TIME ZONE, TEXT[] ) RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  in_time ALIAS FOR $1;
  byday ALIAS FOR $2;
  dayrule TEXT;
  i INT;
  dow INT;
  index INT;
  first_dow INT;
  each_day TIMESTAMP WITH TIME ZONE;
  this_month INT;
  results TIMESTAMP WITH TIME ZONE[];
BEGIN

  IF byday IS NULL THEN
    -- We still return the single date as a SET
    RETURN NEXT in_time;
    RETURN;
  END IF;

  i := 1;
  dayrule := byday[i];
  WHILE dayrule IS NOT NULL LOOP
    dow := position(substring( dayrule from '..$') in 'SUMOTUWETHFRSA') / 2;
    each_day := date_trunc( 'month', in_time ) + (in_time::time)::interval;
    this_month := date_part( 'month', in_time );
    first_dow := date_part( 'dow', each_day );

    -- Coerce each_day to be the first 'dow' of the month
    each_day := each_day - ( first_dow::text || 'days')::interval
                        + ( dow::text || 'days')::interval
                        + CASE WHEN dow < first_dow THEN '1 week'::interval ELSE '0s'::interval END;

    -- RAISE NOTICE 'From "%", for % finding dates. dow=%, this_month=%, first_dow=%', each_day, dayrule, dow, this_month, first_dow;
    IF length(dayrule) > 2 THEN
      index := (substring(dayrule from '^[0-9-]+'))::int;

      IF index = 0 THEN
        RAISE NOTICE 'Ignored invalid BYDAY rule part "%".', bydayrule;
      ELSIF index > 0 THEN
        -- The simplest case, such as 2MO for the second monday
        each_day := each_day + ((index - 1)::text || ' weeks')::interval;
      ELSE
        each_day := each_day + '5 weeks'::interval;
        WHILE date_part('month', each_day) != this_month LOOP
          each_day := each_day - '1 week'::interval;
        END LOOP;
        -- Note that since index is negative, (-2 + 1) == -1, for example
        index := index + 1;
        IF index < 0 THEN
          each_day := each_day + (index::text || ' weeks')::interval ;
        END IF;
      END IF;

      -- Sometimes (e.g. 5TU or -5WE) there might be no such date in some months
      IF date_part('month', each_day) = this_month THEN
        results[date_part('day',each_day)] := each_day;
        -- RAISE NOTICE 'Added "%" to list for %', each_day, dayrule;
      END IF;

    ELSE
      -- Return all such days that are within the given month
      WHILE date_part('month', each_day) = this_month LOOP
        results[date_part('day',each_day)] := each_day;
        each_day := each_day + '1 week'::interval;
        -- RAISE NOTICE 'Added "%" to list for %', each_day, dayrule;
      END LOOP;
    END IF;

    i := i + 1;
    dayrule := byday[i];
  END LOOP;

  FOR i IN 1..31 LOOP
    IF results[i] IS NOT NULL THEN
      RETURN NEXT results[i];
    END IF;
  END LOOP;

  RETURN;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE;


-- Return a SETOF dates within the month of a particular date which match a string of BYDAY rule specifications
CREATE or REPLACE FUNCTION rrule_month_bymonthday_set( TIMESTAMP WITH TIME ZONE, INT[] ) RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  in_time ALIAS FOR $1;
  bymonthday ALIAS FOR $2;
  month_start TIMESTAMP WITH TIME ZONE;
  daysinmonth INT;
  i INT;
BEGIN

  month_start := date_trunc( 'month', in_time ) + (in_time::time)::interval;
  daysinmonth := date_part( 'days', (month_start + interval '1 month') - interval '1 day' );

  FOR i IN 1..31 LOOP
    EXIT WHEN bymonthday[i] IS NULL;

    CONTINUE WHEN bymonthday[i] > daysinmonth;
    CONTINUE WHEN bymonthday[i] < (-1 * daysinmonth);

    IF bymonthday[i] > 0 THEN
      RETURN NEXT month_start + ((bymonthday[i] - 1)::text || 'days')::interval;
    ELSIF bymonthday[i] < 0 THEN
      RETURN NEXT month_start + ((daysinmonth + bymonthday[i])::text || 'days')::interval;
    ELSE
      RAISE NOTICE 'Ignored invalid BYMONTHDAY part "%".', bymonthday[i];
    END IF;
  END LOOP;

  RETURN;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


-- Return a SETOF dates within the week of a particular date which match a single BYDAY rule specification
CREATE or REPLACE FUNCTION rrule_week_byday_set( TIMESTAMP WITH TIME ZONE, TEXT[] ) RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  in_time ALIAS FOR $1;
  byday ALIAS FOR $2;
  dayrule TEXT;
  dow INT;
  our_day TIMESTAMP WITH TIME ZONE;
  i INT;
BEGIN

  IF byday IS NULL THEN
    -- We still return the single date as a SET
    RETURN NEXT in_time;
    RETURN;
  END IF;

  our_day := date_trunc( 'week', in_time ) + (in_time::time)::interval;

  i := 1;
  dayrule := byday[i];
  WHILE dayrule IS NOT NULL LOOP
    dow := position(dayrule in 'SUMOTUWETHFRSA') / 2;
    RETURN NEXT our_day + ((dow - 1)::text || 'days')::interval;
    i := i + 1;
    dayrule := byday[i];
  END LOOP;

  RETURN;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE;


CREATE or REPLACE FUNCTION event_has_exceptions( TEXT ) RETURNS BOOLEAN AS $$
  SELECT $1 ~ E'\nRECURRENCE-ID(;TZID=[^:]+)?:[[:space:]]*[[:digit:]]{8}(T[[:digit:]]{6})?'
$$ LANGUAGE 'sql' IMMUTABLE STRICT;


------------------------------------------------------------------------------------------------------
-- Test the weekday of this date against the array of weekdays from the BYDAY rule (FREQ=WEEKLY or less)
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION test_byday_rule( TIMESTAMP WITH TIME ZONE, TEXT[] ) RETURNS BOOLEAN AS $$
DECLARE
  testme ALIAS FOR $1;
  byday ALIAS FOR $2;
BEGIN
  -- Note that this doesn't work for MONTHLY/YEARLY BYDAY clauses which might have numbers prepended
  -- so don't call it that way...
  IF byday IS NOT NULL THEN
    RETURN ( substring( to_char( testme, 'DY') for 2 from 1) = ANY (byday) );
  END IF;
  RETURN TRUE;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE;


------------------------------------------------------------------------------------------------------
-- Test the month of this date against the array of months from the rule
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION test_bymonth_rule( TIMESTAMP WITH TIME ZONE, INT[] ) RETURNS BOOLEAN AS $$
DECLARE
  testme ALIAS FOR $1;
  bymonth ALIAS FOR $2;
BEGIN
  IF bymonth IS NOT NULL THEN
    RETURN ( date_part( 'month', testme) = ANY (bymonth) );
  END IF;
  RETURN TRUE;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE;


------------------------------------------------------------------------------------------------------
-- Test the day in month of this date against the array of monthdays from the rule
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION test_bymonthday_rule( TIMESTAMP WITH TIME ZONE, INT[] ) RETURNS BOOLEAN AS $$
DECLARE
  testme ALIAS FOR $1;
  bymonthday ALIAS FOR $2;
BEGIN
  IF bymonthday IS NOT NULL THEN
    RETURN ( date_part( 'day', testme) = ANY (bymonthday) );
  END IF;
  RETURN TRUE;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE;


------------------------------------------------------------------------------------------------------
-- Test the day in year of this date against the array of yeardays from the rule
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION test_byyearday_rule( TIMESTAMP WITH TIME ZONE, INT[] ) RETURNS BOOLEAN AS $$
DECLARE
  testme ALIAS FOR $1;
  byyearday ALIAS FOR $2;
BEGIN
  IF byyearday IS NOT NULL THEN
    RETURN ( date_part( 'doy', testme) = ANY (byyearday) );
  END IF;
  RETURN TRUE;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE;


------------------------------------------------------------------------------------------------------
-- Given a cursor into a set, process the set returning the subset matching the BYSETPOS
--
-- Note that this function *requires* PostgreSQL 8.3 or later for the cursor handling syntax
-- to work.  I guess we could do it with an array, instead, for compatibility with earlier
-- releases, since there's a maximum of 366 positions in a set.
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION rrule_bysetpos_filter( REFCURSOR, INT[] ) RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  curse ALIAS FOR $1;
  bysetpos ALIAS FOR $2;
  valid_date TIMESTAMP WITH TIME ZONE;
  i INT;
BEGIN

  IF bysetpos IS NULL THEN
    LOOP
      FETCH curse INTO valid_date;
      EXIT WHEN NOT FOUND;
      RETURN NEXT valid_date;
    END LOOP;
  ELSE
    FOR i IN 1..366 LOOP
      EXIT WHEN bysetpos[i] IS NULL;
      IF bysetpos[i] > 0 THEN
        FETCH ABSOLUTE bysetpos[i] FROM curse INTO valid_date;
      ELSE
        MOVE LAST IN curse;
        FETCH RELATIVE (bysetpos[i] + 1) FROM curse INTO valid_date;
      END IF;
      IF valid_date IS NOT NULL THEN
        RETURN NEXT valid_date;
      END IF;
    END LOOP;
  END IF;
  CLOSE curse;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE;


------------------------------------------------------------------------------------------------------
-- Return another day's worth of events: i.e. one day that matches the criteria, since we don't
-- currently implement sub-day scheduling.
--
-- This is cheeky:  The incrementing by a day is done outside the call, so we either return the
-- empty set (if the input date fails our filters) or we return a set containing the input date.
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION daily_set( TIMESTAMP WITH TIME ZONE, rrule_parts ) RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  after ALIAS FOR $1;
  rrule ALIAS FOR $2;
BEGIN

  IF rrule.bymonth IS NOT NULL AND NOT date_part('month',after) = ANY ( rrule.bymonth ) THEN
    RETURN;
  END IF;

  IF rrule.byweekno IS NOT NULL AND NOT date_part('week',after) = ANY ( rrule.byweekno ) THEN
    RETURN;
  END IF;

  IF rrule.byyearday IS NOT NULL AND NOT date_part('doy',after) = ANY ( rrule.byyearday ) THEN
    RETURN;
  END IF;

  IF rrule.bymonthday IS NOT NULL AND NOT date_part('day',after) = ANY ( rrule.bymonthday ) THEN
    RETURN;
  END IF;

  IF rrule.byday IS NOT NULL AND NOT substring( to_char( after, 'DY') for 2 from 1) = ANY ( rrule.byday ) THEN
    RETURN;
  END IF;

  -- Since we don't do BYHOUR, BYMINUTE or BYSECOND yet this becomes a trivial
  RETURN NEXT after;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


------------------------------------------------------------------------------------------------------
-- Return another week's worth of events
--
-- Doesn't handle truly obscure and unlikely stuff like BYWEEKNO=5;BYMONTH=1;BYDAY=WE,TH,FR;BYSETPOS=-2
-- Imagine that.
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION weekly_set( TIMESTAMP WITH TIME ZONE, rrule_parts ) RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  after ALIAS FOR $1;
  rrule ALIAS FOR $2;
  valid_date TIMESTAMP WITH TIME ZONE;
  curse REFCURSOR;
  weekno INT;
  i INT;
BEGIN

  IF rrule.byweekno IS NOT NULL THEN
    weekno := date_part('week',after);
    IF NOT weekno = ANY ( rrule.byweekno ) THEN
      RETURN;
    END IF;
  END IF;

  OPEN curse SCROLL FOR SELECT r FROM rrule_week_byday_set(after, rrule.byday ) r;
  RETURN QUERY SELECT d FROM rrule_bysetpos_filter(curse,rrule.bysetpos) d;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


------------------------------------------------------------------------------------------------------
-- Return another month's worth of events
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION monthly_set( TIMESTAMP WITH TIME ZONE, rrule_parts ) RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  after ALIAS FOR $1;
  rrule ALIAS FOR $2;
  valid_date TIMESTAMP WITH TIME ZONE;
  curse REFCURSOR;
  setpos INT;
  i INT;
BEGIN

  /**
  * Need to investigate whether it is legal to set both of these, and whether
  * we are correct to UNION the results, or whether we should INTERSECT them.
  * So at this point, we refer to the specification, which grants us this
  * wonderfully enlightening vision:
  *
  *     If multiple BYxxx rule parts are specified, then after evaluating the
  *     specified FREQ and INTERVAL rule parts, the BYxxx rule parts are
  *     applied to the current set of evaluated occurrences in the following
  *     order: BYMONTH, BYWEEKNO, BYYEARDAY, BYMONTHDAY, BYDAY, BYHOUR,
  *     BYMINUTE, BYSECOND and BYSETPOS; then COUNT and UNTIL are evaluated.
  *
  * My guess is that this means 'INTERSECT'
  */
  IF rrule.byday IS NOT NULL AND rrule.bymonthday IS NOT NULL THEN
    OPEN curse SCROLL FOR SELECT r FROM rrule_month_byday_set(after, rrule.byday ) r
                INTERSECT SELECT r FROM rrule_month_bymonthday_set(after, rrule.bymonthday ) r
                    ORDER BY 1;
  ELSIF rrule.bymonthday IS NOT NULL THEN
    OPEN curse SCROLL FOR SELECT r FROM rrule_month_bymonthday_set(after, rrule.bymonthday ) r ORDER BY 1;
  ELSE
    OPEN curse SCROLL FOR SELECT r FROM rrule_month_byday_set(after, rrule.byday ) r ORDER BY 1;
  END IF;

  RETURN QUERY SELECT d FROM rrule_bysetpos_filter(curse,rrule.bysetpos) d;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


------------------------------------------------------------------------------------------------------
-- If this is YEARLY;BYMONTH, abuse MONTHLY;BYMONTH for everything except the BYSETPOS
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION rrule_yearly_bymonth_set( TIMESTAMP WITH TIME ZONE, rrule_parts ) RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  after ALIAS FOR $1;
  rrule ALIAS FOR $2;
  current_base TIMESTAMP WITH TIME ZONE;
  rr rrule_parts;
  i INT;
BEGIN

  IF rrule.bymonth IS NOT NULL THEN
    -- Ensure we don't pass BYSETPOS down
    rr := rrule;
    rr.bysetpos := NULL;
    FOR i IN 1..12 LOOP
      EXIT WHEN rr.bymonth[i] IS NULL;
      current_base := date_trunc( 'year', after ) + ((rr.bymonth[i] - 1)::text || ' months')::interval + (after::time)::interval;
      RETURN QUERY SELECT r FROM monthly_set(current_base,rr) r;
    END LOOP;
  ELSE
    -- We don't yet implement byweekno, byblah
    RETURN NEXT after;
  END IF;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


------------------------------------------------------------------------------------------------------
-- Return another year's worth of events
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION yearly_set( TIMESTAMP WITH TIME ZONE, rrule_parts ) RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  after ALIAS FOR $1;
  rrule ALIAS FOR $2;
  current_base TIMESTAMP WITH TIME ZONE;
  curse REFCURSOR;
  curser REFCURSOR;
  i INT;
BEGIN

  IF rrule.bymonth IS NOT NULL THEN
    OPEN curse SCROLL FOR SELECT r FROM rrule_yearly_bymonth_set(after, rrule ) r;
    FOR current_base IN SELECT d FROM rrule_bysetpos_filter(curse,rrule.bysetpos) d LOOP
      current_base := date_trunc( 'day', current_base ) + (after::time)::interval;
      RETURN NEXT current_base;
    END LOOP;
  ELSE
    -- We don't yet implement byweekno, byblah
    RETURN NEXT after;
  END IF;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


------------------------------------------------------------------------------------------------------
-- Combine all of that into something which we can use to generate a series from an arbitrary DTSTART/RRULE
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION rrule_event_instances_range( TIMESTAMP WITH TIME ZONE, TEXT, TIMESTAMP WITH TIME ZONE, TIMESTAMP WITH TIME ZONE, INT )
                                         RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  basedate ALIAS FOR $1;
  repeatrule ALIAS FOR $2;
  mindate ALIAS FOR $3;
  maxdate ALIAS FOR $4;
  max_count ALIAS FOR $5;
  loopmax INT;
  loopcount INT;
  base_day TIMESTAMP WITH TIME ZONE;
  current_base TIMESTAMP WITH TIME ZONE;
  current TIMESTAMP WITH TIME ZONE;
  rrule rrule_parts%ROWTYPE;
BEGIN
  loopcount := 0;

  SELECT * INTO rrule FROM parse_rrule_parts( basedate, repeatrule );

  IF rrule.count IS NOT NULL THEN
    loopmax := rrule.count;
  ELSE
    -- max_count is pretty arbitrary, so we scale it somewhat here depending on the frequency.
    IF rrule.freq = 'DAILY' THEN
      loopmax := max_count * 20;
    ELSIF rrule.freq = 'WEEKLY' THEN
      loopmax := max_count * 10;
    ELSE
      loopmax := max_count;
    END IF;
  END IF;

  current_base := basedate;
  base_day := date_trunc('day',basedate);
  WHILE loopcount < loopmax AND current_base <= maxdate LOOP
    IF rrule.freq = 'DAILY' THEN
      FOR current IN SELECT d FROM daily_set(current_base,rrule) d WHERE d >= base_day LOOP
--        IF test_byday_rule(current,rrule.byday) AND test_bymonthday_rule(current,rrule.bymonthday) AND test_bymonth_rule(current,rrule.bymonth) THEN
          EXIT WHEN rrule.until IS NOT NULL AND current > rrule.until;
          IF current >= mindate THEN
            RETURN NEXT current;
          END IF;
          loopcount := loopcount + 1;
          EXIT WHEN loopcount >= loopmax;
--        END IF;
      END LOOP;
      current_base := current_base + (rrule.interval::text || ' days')::interval;
    ELSIF rrule.freq = 'WEEKLY' THEN
      FOR current IN SELECT w FROM weekly_set(current_base,rrule) w WHERE w >= base_day LOOP
        IF test_byyearday_rule(current,rrule.byyearday)
               AND test_bymonthday_rule(current,rrule.bymonthday)
               AND test_bymonth_rule(current,rrule.bymonth)
        THEN
          EXIT WHEN rrule.until IS NOT NULL AND current > rrule.until;
          IF current >= mindate THEN
            RETURN NEXT current;
          END IF;
          loopcount := loopcount + 1;
          EXIT WHEN loopcount >= loopmax;
        END IF;
      END LOOP;
      current_base := current_base + (rrule.interval::text || ' weeks')::interval;
    ELSIF rrule.freq = 'MONTHLY' THEN
      FOR current IN SELECT m FROM monthly_set(current_base,rrule) m WHERE m >= base_day LOOP
--        IF /* test_byyearday_rule(current,rrule.byyearday)
--               AND */ test_bymonth_rule(current,rrule.bymonth)
--        THEN
          EXIT WHEN rrule.until IS NOT NULL AND current > rrule.until;
          IF current >= mindate THEN
            RETURN NEXT current;
          END IF;
          loopcount := loopcount + 1;
          EXIT WHEN loopcount >= loopmax;
--        END IF;
      END LOOP;
      current_base := current_base + (rrule.interval::text || ' months')::interval;
    ELSIF rrule.freq = 'YEARLY' THEN
      FOR current IN SELECT y FROM yearly_set(current_base,rrule) y WHERE y >= base_day LOOP
        EXIT WHEN rrule.until IS NOT NULL AND current > rrule.until;
        IF current >= mindate THEN
          RETURN NEXT current;
        END IF;
        loopcount := loopcount + 1;
        EXIT WHEN loopcount >= loopmax;
      END LOOP;
      current_base := current_base + (rrule.interval::text || ' years')::interval;
    ELSE
      RAISE NOTICE 'A frequency of "%" is not handled', rrule.freq;
      RETURN;
    END IF;
    EXIT WHEN rrule.until IS NOT NULL AND current > rrule.until;
  END LOOP;
  -- RETURN QUERY;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


------------------------------------------------------------------------------------------------------
-- A simplified DTSTART/RRULE only interface which applies some performance assumptions
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION event_instances( TIMESTAMP WITH TIME ZONE, TEXT )
                                         RETURNS SETOF TIMESTAMP WITH TIME ZONE AS $$
DECLARE
  basedate ALIAS FOR $1;
  repeatrule ALIAS FOR $2;
  maxdate TIMESTAMP WITH TIME ZONE;
BEGIN
  maxdate := current_date + '10 years'::interval;
  RETURN QUERY SELECT d FROM rrule_event_instances_range( basedate, repeatrule, basedate, maxdate, 300 ) d;
END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


------------------------------------------------------------------------------------------------------
-- In most cases we just want to know if there *is* an event overlapping the range, so we have a
-- specific function for that.  Note that this is *not* strict, and can be called with NULLs.
------------------------------------------------------------------------------------------------------
CREATE or REPLACE FUNCTION rrule_event_overlaps( TIMESTAMP WITH TIME ZONE, TIMESTAMP WITH TIME ZONE, TEXT, TIMESTAMP WITH TIME ZONE, TIMESTAMP WITH TIME ZONE )
                                         RETURNS BOOLEAN AS $$
DECLARE
  dtstart ALIAS FOR $1;
  dtend ALIAS FOR $2;
  repeatrule ALIAS FOR $3;
  in_mindate ALIAS FOR $4;
  in_maxdate ALIAS FOR $5;
  base_date TIMESTAMP WITH TIME ZONE;
  mindate TIMESTAMP WITH TIME ZONE;
  maxdate TIMESTAMP WITH TIME ZONE;
BEGIN

  IF dtstart IS NULL THEN
    RETURN NULL;
  END IF;
  IF dtend IS NULL THEN
    base_date := dtstart;
  ELSE
    base_date := dtend;
  END IF;

  IF in_mindate IS NULL THEN
    mindate := current_date - '10 years'::interval;
  ELSE
    mindate := in_mindate;
  END IF;

  IF in_maxdate IS NULL THEN
    maxdate := current_date + '10 years'::interval;
  ELSE
    -- If we add the duration onto the event, then an overlap occurs if dtend <= increased end of range.
    maxdate := in_maxdate + (base_date - dtstart);
  END IF;

  IF repeatrule IS NULL THEN
    RETURN (dtstart <= maxdate AND base_date >= mindate);
  END IF;

  SELECT d INTO mindate FROM rrule_event_instances_range( base_date, repeatrule, mindate, maxdate, 60 ) d LIMIT 1;
  RETURN FOUND;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE;


-- Create a composite type for the parts of the RRULE.
DROP TYPE rrule_instance CASCADE;
CREATE TYPE rrule_instance AS (
  dtstart TIMESTAMP WITH TIME ZONE,
  rrule TEXT,
  instance TIMESTAMP WITH TIME ZONE
);

CREATE or REPLACE FUNCTION rrule_event_instances( TIMESTAMP WITH TIME ZONE, TEXT )
                                         RETURNS SETOF rrule_instance AS $$
DECLARE
  basedate ALIAS FOR $1;
  repeatrule ALIAS FOR $2;
  maxdate TIMESTAMP WITH TIME ZONE;
  current TIMESTAMP WITH TIME ZONE;
  result rrule_instance%ROWTYPE;
BEGIN
  maxdate := current_date + '10 years'::interval;

  result.dtstart := basedate;
  result.rrule   := repeatrule;

  FOR current IN SELECT d FROM rrule_event_instances_range( basedate, repeatrule, basedate, maxdate, 300 ) d LOOP
    result.instance := current;
    RETURN NEXT result;
  END LOOP;

END;
$$ LANGUAGE 'plpgsql' IMMUTABLE STRICT;


CREATE or REPLACE FUNCTION icalendar_interval_to_SQL( TEXT ) RETURNS interval AS $function$
  SELECT CASE WHEN substring($1,1,1) = '-' THEN -1 ELSE 1 END * regexp_replace( regexp_replace($1, '[PT-]', '', 'g'), '([A-Z])', E'\\1 ', 'g')::interval;
$function$ LANGUAGE 'SQL' IMMUTABLE STRICT;

