--
-- Create table: security_lastLoginInfo
-- Generated: 2013-04-17 14:05:39
--
CREATE TABLE `security_lastLoginInfo` (
  `userId` int(11) NOT NULL DEFAULT '0',
  `time` datetime DEFAULT NULL,
  `ip` varchar(15) COLLATE utf8_czech_ci DEFAULT NULL,
  `time2` datetime DEFAULT NULL,
  `ip2` varchar(15) COLLATE utf8_czech_ci DEFAULT NULL,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;