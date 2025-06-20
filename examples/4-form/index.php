<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Start('Form Example'); //set page title

$form = new Form(Method::Post);
$form->label('Text', 'text');
$form->email('text', 'without fieldset', 'placeholder', required: true, attrib: ['autofocus']);
$form->submit();
$form->echo(); //print
unset($form); //destroy & free memory

$form = new Form(Method::Post);
$form->autocomplete(false); //disable autocomplete
$form->fieldset(1); //add first fieldset
$form->legend('Form 2 - Fieldset 1');
$form->label('Email', 'email');
$form->password('email', required: true);
$form->label('Password', 'password');
$form->password('password', required: true);
$form->clear('Clear All'); //clear button
$form->fieldset(2); //add second fieldset
$form->legend('Form 2 - Fieldset 2');
$form->label('Email', 'email');
$form->password('email', required: true);
$form->label('Password', 'password');
$form->password('password', required: true);
//$form->fieldset(2, 1); //remove first and set second fieldset
$form->submit('Send'); //send button
$form->echo(); //print
unset($form); //destroy & free memory

$form = new Form(Method::Post);
$form->fieldset(1); //add first fieldset
$form->legend('Form 3 - Fieldset 1');
$form->label('CheckBox', 'checkbox');
$form->checkbox('checkbox');
$form->add('br');
$form->label('Area', 'area');
$form->area('area', 'text');
$form->add('br');
$form->label('ComboBox', 'combobox');
$form->combobox('combobox', ['1', '2', '3']);
$form->fieldset(2); //add second fieldset
$form->legend('Form 3 - Fieldset 2');
$form->label('Radio1', 'r1');
$form->label('Radio2', 'r2');
$form->label('Radio3', 'r3');
$form->radio('radio', ['r1'=>'1', 'r2'=>'2', 'r3'=>'3'], '2');
$form->add('br');
$form->label('Radio4', 'r4');
$form->radio('radio2', ['r4'=>'4']);
$form->label('Radio5', 'r5');
$form->radio('radio2', ['r5'=>'5']);
$form->label('Radio6', 'r6');
$form->radio('radio2', ['r6'=>'6']);
$form->fieldset(3); //add third fieldset
$form->legend('Form 3 - Fieldset 3');
$form->label('Integer', 'int');
$form->integer('int', '123');
$form->label('Float', 'float');
$form->float('float', '0.01');
$form->add('br');
$form->label('Month', 'month');
$form->month('month', required: true); //required: true = disallow whole year
$form->add('br');
$form->label('Color', 'color');
$form->color('color');
$form->add('br');
$form->label('Date', 'date');
$form->date('date');
$form->fieldset(0);
$form->submit('Send outside fieldset'); //button outside fieldset
$form->echo(); //print
unset($form); //destroy & free memory