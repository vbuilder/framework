--
-- Create table: security_userRoles
-- Generated: 2013-04-17 14:05:39
--
CREATE TABLE `security_userRoles` (
  `user` smallint(6) unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8_czech_ci NOT NULL,
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;