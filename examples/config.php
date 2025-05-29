<?php declare(strict_types = 1);

//CONFIG FILE EXAMPLE

return array(
	'APP_NAME' => '3S PHP Framework', 
	'APP_DEBUG' => true, 
	'APP_BUFFERING' => true, 
	'APP_TIMEZONE' => 'Europe/Vienna', 
	'APP_ENCODING' => 'utf-8', 
	'APP_LOCALE' => 'en_US', 
	'APP_LANGUAGE' => 'en', 

	'LOG_DB' => false, 
	'LOG_FILE' => true, 
	'LOG_EMPTY' => false, 
	'LOG_USER_UNKNOWN' => true, 
	'LOG_USER_REGISTERED' => true, 
	'LOG_FULL_POST' => true, 

	'DB_CONNECTION' => 'mysqli', 
	'DB_HOST' => null, 
	'DB_PORT' => null, 
	'DB_SOCKET' => '/run/mysqld/mysqld10.sock', 
	'DB_DATABASE' => 'test3s', 
	'DB_USERNAME' => 'test3s', 
	'DB_PASSWORD' => ''
);
