<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Style('main.css'); //add page style
Page::Start('Table Example'); //set page title

//heading
h1(Page::Title()); //get page title



//table
$table = Page::Table('Table', class: 'table_class'); //caption

$table->caption('Renamed Caption2', class: 'caption_class'); //rename caption + set class

$table->colgroup(); //first column without style
$table->colgroup(2, 'column_class'); //applying styles to entire columns
$table->colgroup(1, 'column_class'); //applying styles to entire columns

$table->head(['1st', '2nd', '3th', '4th']); //head row
$table->head(['1st', '2nd', '3th', '4th']); //head row

$table->body(['n', 'o', 'n', 'e']);
$table->body(['n', 'o', 'n', 'e']);
$table->clear(); //clear table data

$table->body(['1', '2', '3', '4']);
$table->body([1 => '2', 2 => '3']); //add items to column index
$table->body(['1', '2', '3', '4']);

$table->foot(['1st', '2nd', '3th', '4th']); //foot row
$table->foot([3 => '4st']); //foot row


br() . br();


//table sort
$table = Page::Table('Table Sort', id: '2nd_table'); //ID is required to sort multiple tables
//$table->mark(false); //disable sort mark
//$table->marks(element_asc, element_desc); //set custom sort marks
//use css if you want sort mark on the side of column label: th span {float: right;} (or left)
$table->head([['One'], 'Two', ['Three','other',['title'=>'Other']], ['Fourth']]); //sort header
$table->body(['1', '2', '3', '4']);
$table->body(['1', '2', '3', '4']);
$table->body(['1', '2', '3', '4']);


br() . br();


table(true); //open table
caption('Table procedural style'); //caption
tbody(true); //open body
for ($i = 0; $i < 3; $i++)
{
	tr(true); //open row
	td('1') . td('2') . td('3') . td('4'); //insert data
	tr(false); //close row
}
table(false); //close table

