<?php

use vBuilder\Security\Authorizators\AclAuthorizator,
	Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

// Implicit inheritance
test(function() {

	$acl = new AclAuthorizator;
	$acl->addResource('file');

	// Explicit inheritance (implicit deny)
	Assert::false($acl->isAllowed(
		'user',
		'file:a'
	));

	// Explicit inheritance (allow)
	$acl->allow('user', 'file');
	Assert::true($acl->isAllowed(
		'user',
		'file:a'
	));

});

// Explicit inheritance
test(function() {

	$acl = new AclAuthorizator;
	$acl->addResource('file');

	// Explicit inheritance (implicit deny)
	Assert::false($acl->isAllowed(
		'user',
		array('file:a/b', 'file:a')
	));

	// Explicit inheritance (allow)
	$acl->allow('user', 'file:a');
	Assert::true($acl->isAllowed(
		'user',
		array('file:a/b', 'file:a')
	));

	// Explicit inheritance (deny)
	$acl->deny('user', 'file:a/b');
	Assert::false($acl->isAllowed(
		'user',
		array('file:a/b', 'file:a')
	));

});