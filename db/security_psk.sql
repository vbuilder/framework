--
-- Create table: security_psk
-- Generated: 2014-03-02 13:29:05
--
CREATE TABLE `security_psk` (
  `key` char(16) NOT NULL DEFAULT '',
  `expiration` date DEFAULT NULL,
  `note` varchar(256) NOT NULL DEFAULT '',
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;