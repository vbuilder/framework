--
-- Create table: oauth2_tokens
-- Generated: 2014-02-28 11:49:28
--
CREATE TABLE `oauth2_tokens` (
  `token` char(32) NOT NULL DEFAULT '',
  `expires` datetime NOT NULL,
  `parameters` text,
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;