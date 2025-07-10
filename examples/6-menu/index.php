<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Start('Menu Example'); //set page title

h1('Menu');

Page::Output(false); //disable direct output (all html functions return only objects without page output)

//create menu items array
$menu_items = array(
	'Only text item',
	a('First', href: 'index.php'), //link to index.php
	[a('Second', href: '#second')], //link to ID
	[a('Third'), //abstract link with submenu
		[
			a('Submenu 1', href: '#section1'),
			[a('Submenu 2', href: '#section2')],
			[a('Submenu 3', href: '#section3'), Permits::Admin], //menu item with user permissons
			[a('Submenu 4', href: '#section4'), Permits::Admin, PermitLevel::Read], //menu item with user permissons and levels
		]],
	a(img('icon.png', ['vertical-align'=>'middle'], width: 10, height: 10), span('img & text in span'), href: '#section5'),
	a('Last', href: '#section3'),
);

Page::Output(true); //enable direct output back

$menu = Page::Menu();
$menu->Load($menu_items);
$menu->Load(User::MenuFilter($menu_items)); //filter menu items by user permissons and levels



br() . br();

h3('Menu procedural style');

nav(true);
ul(true);
li('Only text item');
li(true);
a('First', href: 'index.php');
li(false);
