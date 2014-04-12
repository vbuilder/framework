--
-- Create table: security_log
-- Generated: 2014-02-14 21:39:03
--
CREATE TABLE `security_log` (
  `event` varchar(64) NOT NULL DEFAULT '',
  `uid` varchar(128) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '1',
  `lastTime` datetime NOT NULL,
  PRIMARY KEY (`event`,`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;