<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

HTML::Initialize();

echo HTML::H('Hello World !');

echo HTML::H('Hello World !', 2);

echo HTML::H('Hello World !', 3);

echo HTML::P('Congratulations, you have successfully launched ' . HTML::Strong(App::Env('APP_NAME')));
