ALTER TABLE `shared`
	ADD `write_access` tinyint(1) NOT NULL DEFAULT '0' 
	AFTER `options`;

UPDATE `shared` SET write_access='1';
