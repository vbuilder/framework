--
-- Create table: config
-- Generated: 2013-04-17 14:05:39
--
CREATE TABLE `config` (
  `key` varchar(128) NOT NULL,
  `scope` varchar(64) NOT NULL DEFAULT 'global',
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`key`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;