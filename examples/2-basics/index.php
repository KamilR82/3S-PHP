<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Icon('favicon.ico'); //set page icon

HTML::Initialize('Basics Example'); //set page title


//heading
echo h1(Page::Title()); //get page title


//link
echo section(true);
echo a('Link to nowhere.');
echo a('Link to this file.', ['href' => '']);
echo a('Link to other file.', ['href' => 'home.php']);
echo href('Link to nowhere.');
echo href('Link to this file.', '');
echo href('Link to other file.', 'home.php');
echo section(false);
//or
echo section(href('Link to this file.', '')); //element inside element
//or
echo section(a('Link to nowhere.'), a('Link to this file.', ['href' => ''])); //more elements inside section element
//or
echo section(true), 'comma separated elements', a('Link to nowhere.'), 'inside section', section(false); //comma separated elements

//image
echo section(true); //open tag
echo img('test.png', 'Alternate text for an image'), br();
//or
echo img('test.png', 'Alternate text for an image', 200, 200), br();
//or
echo img('test.png', ['alt' => 'Alternate text for an image', 'width' => '200', 'height' => '200']), br();
//or
echo img(['src' => 'test.png', 'alt' => 'Alternate text for an image', 'width' => '200', 'height' => '200']), br();
echo section(false); //close tag

//div
echo div('Image in `div` tag', img('test.png'), 'test.'); //div with img

echo div(['class'=>'basic_div', 'id'=>'basic_div'], 'Test', 'Test'); //<div class="basic_div" id="basic_div">TestTest</div>
//or
echo div('Test', ['class'=>'basic_div', 'id'=>'basic_div'], 'Test'); //same
//or
echo div('Test', 'Test', ['class'=>'basic_div', 'id'=>'basic_div']); //same
//or
echo div('Test', ['class'=>'basic_div'], 'Test', ['id'=>'basic_div']); //same
