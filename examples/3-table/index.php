<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Style('main.css'); //add page style
Page::Start('Table Example'); //set page title

//heading
echo h1(Page::Title()); //get page title

//table
$table = new Table('Table', class: 'table_class'); //caption
$table->Caption('Renamed Caption', class: 'caption_class'); //renamed caption
$table->ColGroup(2, 'column_class'); //applying styles to entire columns
$table->Head(['1st', '2nd', '3th', '4th']); //head row
$table->Head(['1st', '2nd', '3th', '4th']); //head row
$table->Body(['1', '2', '3', '4']);
$table->Body([1 => '2', 2 => '3']);
$table->Body(['1', '2', '3', '4']);
$table->Foot(['1st', '2nd', '3th', '4th']); //foot row
$table->Foot([3 => '4st']); //foot row
$table->echo(); //print table
unset($table); //destroy table & free memory

//table sort
$table = new Table('Table Sort', class: 'table_class'); //caption
$table->Head([['1st'], '2nd', ['3th','other','Other'], ['4th']]); //sort header
$table->Body(['1', '2', '3', '4']);
$table->Body(['1', '2', '3', '4']);
$table->Body(['1', '2', '3', '4']);
$table->echo(); //print table
unset($table); //destroy table & free memory
