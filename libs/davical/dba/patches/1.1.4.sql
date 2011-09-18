
-- Starting to add internationalisation support

BEGIN;
SELECT check_db_revision(1,1,3);

CREATE TABLE supported_locales (
  locale TEXT PRIMARY KEY,
  locale_name_en TEXT,
  locale_name_locale TEXT
);
GRANT SELECT ON
    supported_locales
  TO general;


ALTER TABLE usr ADD COLUMN locale TEXT;

-- I should be able to find people to translate into these base locales
INSERT INTO supported_locales ( locale, locale_name_en, locale_name_locale )
    VALUES( 'en', 'English', 'English' );
INSERT INTO supported_locales ( locale, locale_name_en, locale_name_locale )
    VALUES( 'de', 'German',  'Deutsch' );
INSERT INTO supported_locales ( locale, locale_name_en, locale_name_locale )
    VALUES( 'es', 'Spanish', 'Español' );
INSERT INTO supported_locales ( locale, locale_name_en, locale_name_locale )
    VALUES( 'fr', 'French',  'Français' );

SELECT new_db_revision(1,1,4, 'April' );

COMMIT;
ROLLBACK;

