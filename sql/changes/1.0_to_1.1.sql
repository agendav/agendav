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
ALTER TABLE sessions MODIFY user_agent VARCHAR(120); 
