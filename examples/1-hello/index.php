<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Start('Page Title');

//Just remember only one important rule: DON'T USE ECHO !!! Use HTML Tags like a PHP functions:

h1('Hello World !'); //heading

h2('Hello World !') . h3('Hello World !'); //concatenate tags

p('This is some text in a paragraph.');

p(true, 'Congratulations, you have successfully launched '); //true = keep tag open
strong(App::Env('APP_NAME')); //text with strong importance
p(false); //close tag

t('Simple text at the end.'); //when no tag is needed, this is a replacement for the echo function
