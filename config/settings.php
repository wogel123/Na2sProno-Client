<?php

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);//E_ALL

// Timezone
date_default_timezone_set('Europe/Paris');

// Settings
$settings = [];

$settings['root']        = dirname(__DIR__);
$settings['storage']     = $settings['root'] . '/storage/';

$settings['auth']['token_key'] = 'TOKENKEY';

$settings['db'] = [
	'driver'    => 'mysql',
	'host'      => 'HOST',
	'database'  => 'DB',
	'username'  => 'USER',
	'password'  => 'PASS',
	'charset'   => 'utf8',
	'collation' => 'utf8_unicode_ci',
	'prefix'    => '',
];

$settings['mail'] = [
	'host'          => 'HOST',
	'port'          => 587,
	'mail'          => 'MAIL',
	'mail_password' => 'PASS',
	'mail_from'     => 'USER',
	'token_key'     => 'TOKENKEY'
];

return $settings;