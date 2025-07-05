<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Icon('favicon.ico'); //set page icon
Page::Start('Basics Example'); //set page title

//DON'T USE ECHO !!! Use HTML Tags like a PHP functions:

//heading
h1(Page::Title()); //get page title

//links in section
section(class: 'links');
href();
a('Link to nowhere.');
a('Link to this file.', href: '');
a('Link to other file.', href: 'home.php');
href('Link to nowhere.');
href('Link to this file.', '');
href('Link to other file.', 'home.php');
section(false);

br(); //create empty tag

section(true) . strong('dot separated elements') . a('Link to nowhere.') . t('inside section') . section(false); //elements in section (inline)

br() . br();

//div
div(true) . t('Image in `div` tag') . img('test.png') . t(' and some text around.') . div(false); //div with img and text

br() . br();

div('Test', 'Test', class: 'basic_div', id: 'basic_div'); //<div class="basic_div" id="basic_div">TestTest</div>
//or
div(['class'=>'basic_div', 'id'=>'basic_div'], 'Test', 'Test'); //same
//or
div('Test', ['class'=>'basic_div', 'id'=>'basic_div'], 'Test'); //same
//or
div('Test', 'Test', ['class'=>'basic_div', 'id'=>'basic_div']); //same
//or
div('Test', ['class'=>'basic_div'], 'Test', ['id'=>'basic_div']); //same


//closing tags
section(true) . div(true) . div(true) . div(true) . p('...and now close all open tags inside <section> tag'). section(false);

section(); //create empty paired tag (open and close)

//image
section(id: 'images'); //open tag (if there are attributes, no need to set `true` and the tag will remain open)
img('test.png') . br();
//or
img('test.png', 'Alternate text for an image', width: 200, height: 200) . br();
//or
img(src: 'test.png', alt: 'Alternate text for an image', width: 200, height: 200) . br();
section(false); //close tag

//text
p('End of ', Page::Title());
p('End of '. Page::Title()); //same with dot
