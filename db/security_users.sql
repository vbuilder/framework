--
-- Create table: security_users
-- Generated: 2013-04-17 14:05:39
--
CREATE TABLE `security_users` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(254) COLLATE utf8_czech_ci NOT NULL,
  `password` char(40) COLLATE utf8_czech_ci NOT NULL,
  `registrationTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(254) COLLATE utf8_czech_ci NOT NULL,
  `surname` varchar(254) COLLATE utf8_czech_ci NOT NULL,
  `email` varchar(254) COLLATE utf8_czech_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;