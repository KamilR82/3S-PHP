<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

HTML::Initialize('Page Title');

echo h1('Hello World !');

echo h2('Hello World !'), h3('Hello World !');

echo p('Congratulations, you have successfully launched ', strong(App::Env('APP_NAME')));
