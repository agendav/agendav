-- Define an updateable view for dav_principal which conbines the AWL usr
-- record 1:1 with the principal table


DROP VIEW dav_principal CASCADE;
CREATE OR REPLACE VIEW dav_principal AS
  SELECT user_no, usr.active AS user_active, joined AS created, updated AS modified,
         username, password, fullname, email,
         email_ok, date_format_type, locale,
         principal_id, type_id, displayname, default_privileges,
         TRUE AS is_principal,
         FALSE AS is_calendar,
         principal_id AS collection_id,
         FALSE AS is_addressbook,
          '/' || username || '/' AS dav_name,
         '<DAV::collection/><DAV::principal/>'::text AS resourcetypes
    FROM usr JOIN principal USING(user_no);

CREATE or REPLACE RULE dav_principal_insert AS ON INSERT TO dav_principal
DO INSTEAD
(
  INSERT INTO usr ( user_no, active, joined, updated, username, password, fullname, email, email_ok, date_format_type, locale )
    VALUES(
      COALESCE( NEW.user_no, nextval('usr_user_no_seq')),
      COALESCE( NEW.user_active, TRUE),
      current_timestamp,
      current_timestamp,
      NEW.username, NEW.password,
      COALESCE( NEW.fullname, NEW.displayname ),
      NEW.email, NEW.email_ok,
      COALESCE( NEW.date_format_type, 'E'),
      NEW.locale
    );
  INSERT INTO principal ( user_no, principal_id, type_id, displayname, default_privileges )
    VALUES(
      COALESCE( NEW.user_no, currval('usr_user_no_seq')),
      COALESCE( NEW.principal_id, nextval('dav_id_seq')),
      NEW.type_id,
      COALESCE( NEW.displayname, NEW.fullname ),
      COALESCE( NEW.default_privileges, 0::BIT(24))
    );
);


CREATE or REPLACE RULE dav_principal_update AS ON UPDATE TO dav_principal
DO INSTEAD
(
  UPDATE usr
    SET
      user_no=NEW.user_no,
      active=NEW.user_active,
      updated=current_timestamp,
      username=NEW.username,
      password=NEW.password,
      fullname=NEW.fullname,
      email=NEW.email,
      email_ok=NEW.email_ok,
      date_format_type=NEW.date_format_type,
      locale=NEW.locale
    WHERE user_no=OLD.user_no;

  UPDATE principal
    SET
      principal_id = NEW.principal_id,
      type_id = NEW.type_id,
      displayname = NEW.displayname,
      default_privileges = NEW.default_privileges
    WHERE principal_id=OLD.principal_id;
);

CREATE or REPLACE RULE dav_principal_delete AS ON DELETE TO dav_principal
DO INSTEAD
(
  DELETE FROM usr WHERE user_no=OLD.user_no;
  DELETE FROM principal WHERE principal_id=OLD.principal_id;
);

