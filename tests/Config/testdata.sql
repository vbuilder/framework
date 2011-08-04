TRUNCATE TABLE `config`;

INSERT INTO `config` (`key`, `scope`, `value`)
VALUES
	('a', 'global', '1'),
	('b', 'user(1)', '11'),
	('b', 'user(2)', '22'),
	('c', 'user(2)', '3'),
	('d', 'user(1)', '4');
