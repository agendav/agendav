CREATE TABLE IF NOT EXISTS  `sessions` (
		session_id varchar(40) DEFAULT '0' NOT NULL,
		ip_address varchar(16) DEFAULT '0' NOT NULL,
		user_agent varchar(120) NOT NULL,
		last_activity int(10) unsigned DEFAULT 0 NOT NULL,
		user_data text DEFAULT '' NOT NULL,
		PRIMARY KEY (session_id)
);

CREATE TABLE `shared` (
	`sid` INT NOT NULL AUTO_INCREMENT,
	`user_from` VARCHAR(255) NOT NULL,
	`calendar` VARCHAR(255) NOT NULL,
	`user_which` VARCHAR(255) NOT NULL,
	`options` TEXT NOT NULL,

	PRIMARY KEY(sid))
	ENGINE=InnoDB;

CREATE INDEX shareidx ON shared (user_from, calendar);
CREATE INDEX sharedwithidx ON shared (user_which);

CREATE INDEX last_activity_idx ON sessions(last_activity);
