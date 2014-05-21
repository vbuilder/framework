--
-- Create table: security_acl
-- Generated: 2013-08-09 16:00:03
--
CREATE TABLE `security_acl` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('deny','allow') NOT NULL DEFAULT 'allow',
  `role` varchar(256) DEFAULT '',
  `resource` varchar(256) DEFAULT NULL,
  `privilege` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;