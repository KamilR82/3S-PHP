<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Start('Page Title');

h1('Hello World !');

h2('Hello World !') . h3('Hello World !');

p();
text('Congratulations, you have successfully launched ');
strong(App::Env('APP_NAME'));
