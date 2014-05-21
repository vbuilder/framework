<?php
// TODO: Parametricke zadani vstupnich dat
// TODO: Podpora pro uziv. role

use vBuilder\Security\User,
	vBuilder\Utils\CliArgsParser,
	vBuilder\Utils\Strings,
	vBuilder\Security\IdentityFactory;

$container = require __DIR__ . '/bootstrap.php';

// -----------------------------------------------------------------------------
// ARGUMENTS
// -----------------------------------------------------------------------------

$args = new CliArgsParser();
$args
	->addOption('password', 'secret', 'user password', NULL)
	->setNumRequiredArgs(1, 1)
	->setArgumentHelp('username');

if(!$args->parse()) {
	echo "\n" . $args->getErrorMsg() . "\n\n";
	$args->printUsage();
	echo "\n";
	exit;
}

list($user) = $args->getArguments();
$password = $args->get('password') !== FALSE ? $args->get('password') : Strings::randomHumanToken();


// -----------------------------------------------------------------------------
// INIT
// -----------------------------------------------------------------------------

$db = $container->getByType('DibiConnection');
$authn = $container->user->getAuthenticator(User::AUTHN_METHOD_PASSWORD, User::AUTHN_SOURCE_DATABASE);
$rolesTable = $authn->identityFactory->getTableName(IdentityFactory::TABLE_ROLES);

$roles = array('Administrator');


// -----------------------------------------------------------------------------
// DB
// -----------------------------------------------------------------------------

$data = array(
	$authn->getColumn($authn::USERNAME) => $user,
	$authn->getColumn($authn::PASSWORD) => $authn->getPasswordHasher()->hashPassword($password)
);

try {
	$db->query("INSERT INTO %n", $authn->tableName, $data);
	$uid = $db->getInsertId();
	echo "ID noveho uzivatele: $uid\n";
	if($args->get('password') === FALSE)
		echo "Heslo: $password\n";

} catch(DibiException $e) {
	if($e->getCode() == 1062) {
		echo "Uzivatel jiz existuje\n";
		$uid = $db->query("SELECT [id] FROM %n", $authn->tableName, "WHERE %n = %s", $authn->getColumn($authn::USERNAME), $user)->fetchSingle();
		if(!$uid) throw new Nette\InvalidStateException;
		echo "ID uzivatele: $uid\n";

	} else
		throw $e;
}

$db->query("DELETE FROM %n WHERE [user] = %i", $rolesTable, $uid);

$roleInsert = array();
foreach($roles as $role) $roleInsert[] = array('user' => $uid, 'role' => $role);
$db->query("INSERT INTO %n %ex", $rolesTable, $roleInsert);

echo "Ok :-)\n";
echo "\n";
