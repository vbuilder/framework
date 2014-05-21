--
-- Create table: oauth2_clients
-- Generated: 2014-02-28 11:49:28
--
CREATE TABLE `oauth2_clients` (
  `clientId` varchar(128) NOT NULL DEFAULT '',
  `secret` char(40) DEFAULT NULL,
  `note` text NOT NULL,
  PRIMARY KEY (`clientId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;