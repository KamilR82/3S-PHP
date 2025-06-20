<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Icon('favicon.ico'); //set page icon
Page::Start('Basics Example'); //set page title

//heading
h1(Page::Title()); //get page title

//links in section
section(true);
a('Link to nowhere.');
a('Link to this file.', ['href' => '']);
a('Link to other file.', ['href' => 'home.php']);
href('Link to nowhere.');
href('Link to this file.', '');
href('Link to other file.', 'home.php');
section(false);

//elements in section (inline)
section(true) . strong('comma separated elements') . a('Link to nowhere.') . text('inside section') . section(false); //t,txt,text is equivalent

//image
section(true); //open tag
img('test.png', 'Alternate text for an image') . br();
//or
img('test.png', 'Alternate text for an image', 200, 200) . br();
//or
img('test.png', ['alt' => 'Alternate text for an image', 'width' => '200', 'height' => '200']) . br();
//or
img(['src' => 'test.png', 'alt' => 'Alternate text for an image', 'width' => '200', 'height' => '200']) . br();
section(false); //close tag

br() . br();

//div
div(true) . t('Image in `div` tag') . img('test.png') . div(false); //div with img

div(['class'=>'basic_div', 'id'=>'basic_div'], 'Test', 'Test'); //<div class="basic_div" id="basic_div">TestTest</div>
//or
div('Test', ['class'=>'basic_div', 'id'=>'basic_div'], 'Test'); //same
//or
div('Test', 'Test', ['class'=>'basic_div', 'id'=>'basic_div']); //same
//or
div('Test', ['class'=>'basic_div'], 'Test', ['id'=>'basic_div']); //same
