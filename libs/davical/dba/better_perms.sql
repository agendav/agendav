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
  all BIT(24);
  start INT;
  finish INT;
BEGIN
  out_bits := 0::BIT(24);
  all := ~ out_bits;
  SELECT array_lower(raw_privs,1) INTO start;
  SELECT array_upper(raw_privs,1) INTO finish; 
  FOR i IN start .. finish  LOOP
    SELECT out_bits | privilege_to_bits(raw_privs[i]) INTO out_bits;   
    IF out_bits = all THEN
      RETURN all;
    END IF;
  END LOOP;
  RETURN out_bits;
END 
$$
LANGUAGE 'PlPgSQL' IMMUTABLE STRICT;


-- This legacy conversion function will eventually be removed, once all logic
-- has been converted to use bitmaps, or to use the bits_to_priv() output.
--
-- NOTE: Round-trip through this and then back through privilege_to_bits
--       function is lossy!  Through privilege_to_bits() and back through
--       this one is not.
--
CREATE or REPLACE FUNCTION bits_to_privilege( BIT(24) ) RETURNS TEXT[] AS $$
DECLARE
  in_bits ALIAS FOR $1;
  out_priv TEXT[];
BEGIN
  out_priv := ARRAY[]::text[];
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
