CREATE TABLE `sessions` (
  `session_id` varchar(40) NOT NULL DEFAULT '0',
  `ip_address` varchar(16) NOT NULL DEFAULT '0',
  `user_agent` varchar(120) DEFAULT NULL,
  `last_activity` int(10) unsigned NOT NULL DEFAULT '0',
  `user_data` mediumtext NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `last_activity_idx` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `shared` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `user_from` varchar(255) NOT NULL,
  `calendar` varchar(255) NOT NULL,
  `user_which` varchar(255) NOT NULL,
  `options` mediumtext NOT NULL,
  `write_access` BOOLEAN NOT NULL DEFAULT '0',
  PRIMARY KEY (`sid`),
  KEY `shareidx` (`user_from`,`calendar`),
  KEY `sharedwithidx` (`user_which`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `migrations` (
  `version` int(3) NOT NULL
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO migrations VALUES ('1');
