-- Some base data to prime the database...

-- FIXME: Only insert the rows if they are not there already.
INSERT INTO roles ( role_no, role_name ) VALUES( 1, 'Admin');
INSERT INTO roles ( role_no, role_name ) VALUES( 2, 'Group');
INSERT INTO roles ( role_no, role_name ) VALUES( 3, 'Public');
INSERT INTO roles ( role_no, role_name ) VALUES( 4, 'Resource');

-- Set the insert sequence to the next number, with a minimum of 10
SELECT setval('roles_role_no_seq', (SELECT 10 UNION SELECT role_no FROM roles ORDER BY 1 DESC LIMIT 1) );

INSERT INTO principal_type (principal_type_id, principal_type_desc) VALUES( 1, 'Person' );
INSERT INTO principal_type (principal_type_id, principal_type_desc) VALUES( 2, 'Resource' );
INSERT INTO principal_type (principal_type_id, principal_type_desc) VALUES( 3, 'Group' );

-- Create the administrator record.
INSERT INTO usr ( user_no, active, email_ok, updated, username, password, fullname, email )
    VALUES ( 1, TRUE, current_date, current_date, 'admin', '**nimda', 'DAViCal Administrator', 'calendars@example.net' );
INSERT INTO principal ( principal_id, type_id, user_no, displayname, default_privileges )
    VALUES ( 1, 1, 1, 'DAViCal Administrator', 0::BIT(24) );

INSERT INTO role_member (user_no, role_no) VALUES(1, 1);


-- Set the insert sequence to the next number, with a minimum of 1000
SELECT setval('usr_user_no_seq', (SELECT 1000 UNION SELECT user_no FROM usr ORDER BY 1 DESC LIMIT 1) );

INSERT INTO relationship_type ( rt_id, rt_name, confers, bit_confers )
    VALUES( 1, 'Administers', 'A', privilege_to_bits('DAV::all') );

INSERT INTO relationship_type ( rt_id, rt_name, confers, bit_confers )
    VALUES( 2, 'Can read/write to', 'RW', privilege_to_bits( ARRAY['DAV::read','DAV::write']) );

INSERT INTO relationship_type ( rt_id, rt_name, confers, bit_confers )
    VALUES( 3, 'Can read from', 'R', privilege_to_bits( 'DAV::read') );

INSERT INTO relationship_type ( rt_id, rt_name, confers, bit_confers )
    VALUES( 4, 'Can see free/busy time of', 'F', privilege_to_bits( 'caldav:read-free-busy') );


-- Set the insert sequence to the next number, with a minimum of 1000
SELECT setval('relationship_type_rt_id_seq', (SELECT 10 UNION SELECT rt_id FROM relationship_type ORDER BY 1 DESC LIMIT 1) );

