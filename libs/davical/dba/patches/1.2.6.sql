
-- This database update converts the permissions into a bitmap stored
-- as an integer to make calculation of merged permissions simpler
-- through simple binary 'AND'

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
            WHEN in_priv = 'read'                            THEN     1
            WHEN in_priv = 'write'                           THEN   198 -- 2 + 4 + 64 + 128
            WHEN in_priv = 'write-properties'                THEN     2
            WHEN in_priv = 'write-content'                   THEN     4
            WHEN in_priv = 'unlock'                          THEN     8
            WHEN in_priv = 'read-acl'                        THEN    16
            WHEN in_priv = 'read-current-user-privilege-set' THEN    32
            WHEN in_priv = 'bind'                            THEN    64
            WHEN in_priv = 'unbind'                          THEN   128
            WHEN in_priv = 'write-acl'                       THEN   256
            WHEN in_priv = 'read-free-busy'                  THEN   512
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











BEGIN;
SELECT check_db_revision(1,2,5);


-- DAV Privileges implementation
--
-- RFC 3744 - DAV ACLs
--      1 DAV:read
--   DAV:write (aggregate = 198 = write-properties & write-content & bind & unbind)
--      2 DAV:write-properties
--      4 DAV:write-content
--      8 DAV:unlock
--     16 DAV:read-acl
--     32 DAV:read-current-user-privilege-set
--     64 DAV:bind
--    128 DAV:unbind
--    256 DAV:write-acl

-- RFC 4791 - CalDAV
--    512 CalDAV:read-free-busy

-- RFC ???? - Scheduling Extensions for CalDAV
-- CALDAV:schedule-deliver (aggregate) => 7168
--   1024 CALDAV:schedule-deliver-invite
--   2048 CALDAV:schedule-deliver-reply
--   4096 CALDAV:schedule-query-freebusy
-- CALDAV:schedule-send (aggregate) => 57344
--   8192 CALDAV:schedule-send-invite
--  16384 CALDAV:schedule-send-reply
--  32768 CALDAV:schedule-send-freebusy

-- RFC 3744 - DAV ACLs
-- DAV:all => all of the above and any new ones someone might invent!

-- DAV:read-acl MUST NOT contain DAV:read, DAV:write, DAV:write-acl, DAV:write-properties, DAV:write-content, or DAV:read-current-user-privilege-set.
-- DAV:write-acl MUST NOT contain DAV:write, DAV:read, DAV:read-acl, DAV:read-current-user-privilege-set.
-- DAV:read-current-user-privilege-set MUST NOT contain DAV:write, DAV:read, DAV:read-acl, or DAV:write-acl.
-- DAV:write MUST NOT contain DAV:read, DAV:read-acl, or DAV:read-current-user-privilege-set.
-- DAV:read MUST NOT contain DAV:write, DAV:write-acl, DAV:write-properties, or DAV:write-content.
-- DAV:write-acl COULD contain DAV:write-properties DAV:write-content DAV:unlock DAV:bind DAV:unbind BUT why would it?

-- DAV:write => DAV:bind, DAV:unbind, DAV:write-properties and DAV:write-content

-- RFC 4791 - CalDAV
-- The CALDAV:read-free-busy privilege MUST be aggregated in the DAV:read privilege.

-- RFC ???? - Scheduling Extensions for CalDAV
--  DAV:all MUST contain CALDAV:schedule-send and CALDAV:schedule-deliver
--  CALDAV:schedule-send MUST contain CALDAV:schedule-send-invite, CALDAV:schedule-send-reply, and CALDAV:schedule-send-freebusy;
--  CALDAV:schedule-deliver MUST contain CALDAV:schedule-deliver-invite, CALDAV:schedule-deliver-reply, and CALDAV:schedule-query-freebusy.


-- Me!!!
-- CalDAV:read-free-busy privilege SHOULD contain CALDAV:schedule-query-freebusy
-- => DAV:read privilege SHOULD contain CALDAV:schedule-query-freebusy
-- We do this outside of these privileges though.


-- This legacy conversion function will eventually be removed, once all logic
-- has been converted to use bitmaps, or to use the bits_to_priv() output.
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


ALTER TABLE relationship_type ADD COLUMN bit_confers BIT(24) DEFAULT privilege_to_bits(ARRAY['DAV::read','DAV::write']);
UPDATE relationship_type SET bit_confers = legacy_privilege_to_bits(confers);

ALTER TABLE relationship ADD COLUMN confers BIT(24) DEFAULT privilege_to_bits('caldav:read-free-busy');
UPDATE relationship SET confers = (SELECT bit_confers FROM relationship_type AS rt WHERE rt.rt_id=relationship.rt_id);

ALTER TABLE collection ADD COLUMN default_privileges BIT(24);

INSERT INTO principal_type (principal_type_id, principal_type_desc) VALUES( 1, 'Person' );
INSERT INTO principal_type (principal_type_id, principal_type_desc) VALUES( 2, 'Resource' );
INSERT INTO principal_type (principal_type_id, principal_type_desc) VALUES( 3, 'Group' );

-- web needs SELECT,INSERT,UPDATE,DELETE
DROP TABLE principal CASCADE;
CREATE TABLE principal (
  principal_id INT8 DEFAULT nextval('dav_id_seq') PRIMARY KEY,
  type_id INT8 NOT NULL REFERENCES principal_type(principal_type_id) ON UPDATE CASCADE ON DELETE RESTRICT DEFERRABLE,
  user_no INT8 NULL REFERENCES usr(user_no) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  displayname TEXT,
  default_privileges BIT(24)
);

INSERT INTO principal (type_id, user_no, displayname, default_privileges)
         SELECT 1, user_no, fullname, privilege_to_bits(ARRAY['read-free-busy','schedule-send','schedule-deliver']) FROM usr
                 WHERE NOT EXISTS(SELECT 1 FROM role_member JOIN roles USING(role_no) WHERE role_name = 'Group' AND role_member.user_no = usr.user_no)
                   AND NOT EXISTS(SELECT 1 FROM role_member JOIN roles USING(role_no) WHERE role_name = 'Resource' AND role_member.user_no = usr.user_no) ;

INSERT INTO principal (type_id, user_no, displayname, default_privileges)
         SELECT 2, user_no, fullname, privilege_to_bits(ARRAY['read','schedule-send','schedule-deliver']) FROM usr
                 WHERE EXISTS(SELECT 1 FROM role_member JOIN roles USING(role_no) WHERE role_name = 'Resource' AND role_member.user_no = usr.user_no);

INSERT INTO principal (type_id, user_no, displayname, default_privileges)
         SELECT 3, user_no, fullname, privilege_to_bits(ARRAY['read-free-busy','schedule-send','schedule-deliver']) FROM usr
                 WHERE EXISTS(SELECT 1 FROM role_member JOIN roles USING(role_no) WHERE role_name = 'Group' AND role_member.user_no = usr.user_no);

UPDATE collection SET default_privileges = CASE
                        WHEN publicly_readable THEN privilege_to_bits(ARRAY['read'])
                        ELSE NULL
                  END;

INSERT INTO group_member ( group_id, member_id)
              SELECT g.principal_id, m.principal_id
                FROM relationship JOIN principal g ON(to_user=g.user_no AND g.type_id = 3)    -- Group
                                  JOIN principal m ON(from_user=m.user_no AND m.type_id IN (1, 2) ); -- Person or Resource

DROP TABLE dav_resource_type CASCADE;
DROP TABLE dav_resource CASCADE;
DROP TABLE privilege CASCADE;

CREATE TABLE grants (
  by_principal INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  by_collection INT8 REFERENCES collection(collection_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  to_principal INT8 REFERENCES principal(principal_id) ON UPDATE CASCADE ON DELETE CASCADE DEFERRABLE,
  privileges BIT(24),
  is_group BOOLEAN
) WITHOUT OIDS;
CREATE UNIQUE INDEX grants_pk1 ON grants(by_principal,to_principal);
CREATE UNIQUE INDEX grants_pk2 ON grants(by_collection,to_principal);


INSERT INTO grants ( by_principal, to_principal, privileges, is_group )
   SELECT pby.principal_id AS by_principal, pto.principal_id AS to_principal,
                                  confers AS privileges, pto.type_id > 2 AS is_group
     FROM relationship r JOIN usr f ON(f.user_no=r.from_user)
                         JOIN usr t ON(t.user_no=r.to_user)
                         JOIN principal pby ON(t.user_no=pby.user_no)
                         JOIN principal pto ON(pto.user_no=f.user_no)
     WHERE rt_id < 4 AND pby.type_id < 3;

-- It's always safe to kill these collections, so they will be recreated with the correct resourcetype
DELETE FROM collection WHERE dav_name ~ E'/\.(in|out)/$';

SELECT new_db_revision(1,2,6, 'Juin' );

COMMIT;
ROLLBACK;

