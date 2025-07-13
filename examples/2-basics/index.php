<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Icon('favicon.ico'); //set page icon
Page::Start('Basics Example'); //set page title

//DON'T USE ECHO !!! Use HTML Tags like a PHP functions:

//heading
h1(Page::Title()); //get page title

//links in section
section(class: 'links'); //open
a('Link to nowhere.');
a('Link to this file.', href: '');
a('Link to other file.', href: 'home.php');
href('', 'Link to this file.');
href('home.php', 'Link to other file.');
click('alert("Hello! I am an alert box from `" + location.hostname + "`");', 'Link to JS'); //javascript
click("alert('Hello! I am an alert box from `' + location.hostname + '`');", 'Link to JS'); //javascript (single and double quotes exchanged, look at html code) 
section(false); //close

br(); //create empty tag

section(true) . strong('dot separated elements') . a('Link to nowhere.') . txt('inside section') . section(false); //elements in section (inline)
section() . strong('dot separated elements') . a('Link to nowhere.') . txt('inside section') . section(false); //true not needed but recommended
section(['id'=>'section']) . strong('dot separated elements') . a('Link to nowhere.') . txt('inside section') . section(false); //same with attributes
section(id: 'section') . strong('dot separated elements') . a('Link to nowhere.') . txt('inside section') . section(false); //same
section(strong('dot separated elements'), a('Link to nowhere.'), 'inside section', id: 'section'); //same (but positional argument must be after named arguments)

br() . rem('comment') . br();

//div
div(true) . txt('Image in `div` tag') . img('test.png') . txt(' and some text around.') . div(false); //div with img and text
div('Image in `div` tag', img('test.png'), ' and some text around.'); //same

br() . br();

div('Test', 'Test', class: 'basic_div', id: 'basic_div'); //<div class="basic_div" id="basic_div">TestTest</div>
div(['class'=>'basic_div', 'id'=>'basic_div'], 'Test', 'Test'); //same
div('Test', ['class'=>'basic_div', 'id'=>'basic_div'], 'Test'); //same
div('Test', 'Test', ['class'=>'basic_div', 'id'=>'basic_div']); //same
div('Test', ['class'=>'basic_div'], 'Test', ['id'=>'basic_div']); //same

br() . comment('comment too') . br();

//closing tags
section(true) . div(true) . div(true) . div(true) . p('...and now close all open tags inside <section> tag'). section(false);
section(true, div(div(div(p('same as above'))))). section(false); //same
section(div(div(div(p('same as above'))))); //same

br() . comment() . br(); //empty comment

//image
section(id: 'images'); //open tag (if there are attributes, no need to set `true` and the tag will remain open)
img('test.png') . br();
//or
img('test.png', 'Alternate text for an image', width: 200, height: 200) . br();
//or
img(src: 'test.png', alt: 'Alternate text for an image', width: 200, height: 200) . br();
section(false); //close tag

rem(); //empty comment too (script delimiter)

//text
p('End of ', Page::Title());
p('End of '. Page::Title()); //same with dot
