<?php

$SQL_CREDENTIALS = array(
	'dbname' => 'song-manager',
	'user' => 'root',
	'password' => '',
	'host' => '127.0.0.1',
	'driver' => 'pdo_mysql',
	'charset' => 'utf8',
	'driverOptions' => array(
		1002=>'SET NAMES utf8'
	)
);

//$API_BASE_PATH = '/path/to/song-manager/src/api';

date_default_timezone_set('Europe/Zurich');
