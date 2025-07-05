<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Start('Form Example'); //set page title

h3('Form 1');

$form = Page::Form(Method::Post);
$form->label('Text', 'text');
$form->email('text', 'without fieldset', placeholder: 'background text', required: true, autofocus: true);
$form->submit();

h3('Form 2');

$form = Page::Form(Method::Post);
$form->autocomplete(false); //disable autocomplete
$form->fieldset(true); //add first fieldset
$form->legend('Form 2 - Fieldset 1');
$form->label('Email', 'email');
$form->password('email', required: true);
$form->label('Password', 'password');
$form->password('password', required: true);
$form->reset('Clear'); //clear button
$form->reload('Reload'); //reload button
$form->fieldset(false); //close fieldset

$form->fieldset(true); //add second fieldset
$form->legend('Form 2 - Fieldset 2');
$form->label('Email', 'email');
$form->password('email', required: true);
$form->label('Password', 'password');
$form->password('password', required: true);
$form->submit('Send'); //send button

h3('Form 3');

$form = Page::Form(Method::Post);

$form->fieldset(true); //add first fieldset
$form->legend('Form 3 - Fieldset 1');
$form->label('CheckBox', 'checkbox');
$form->checkbox('checkbox');
$form->br();
$form->label('Textarea', 'textarea');
$form->textarea('textarea', 'text');
$form->br();
$form->label('ComboBox', 'combobox');
$form->combo('combobox', ['1', '2', '3']);
$form->fieldset(false); //close fieldset

$form->fieldset(true); //add second fieldset
$form->legend('Form 3 - Fieldset 2');
$form->label('Range', 'range');
$form->range('range');
$form->br();
$form->label('Radio1-3', 'r1');
$form->radio('radio', ['r1'=>'1', 'r2'=>'2', 'r3'=>'3'], '2');
$form->br();
$form->label('Radio4', 'r4');
$form->radio('radio2', ['r4'=>'4']);
$form->label('Radio5', 'r5');
$form->radio('radio2', ['r5'=>'5'], 5);
$form->label('Radio6', 'r6');
$form->radio('radio2', ['r6'=>'6']);
$form->fieldset(false); //close fieldset

$form->fieldset(true); //add third fieldset
$form->legend('Form 3 - Fieldset 3');
$form->label('Integer', 'int');
$form->integer('int', '123');
$form->br();
$form->label('Float', 'float');
$form->float('float', '0.01');
$form->br();
$form->label('Month', 'month');
$form->month('month', required: true); //required: true = disallow whole year
$form->br();
$form->label('Color', 'color');
$form->color('color');
$form->br();
$form->label('Date', 'date');
$form->date('date');
$form->fieldset(false); //close fieldset

$form->submit('Send outside fieldset'); //button outside fieldset
