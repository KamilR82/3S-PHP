<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

//CUSTOM START CODE FOR EVERY PAGE
App::Protect(__FILE__); //can't run this file individually

//CUSTOM PAGE TEMPLATE
trait PageTemplate
{
	private static function Headers(): void
	{
		header('X-Robots-Tag: none'); //equivalent to noindex, nofollow
	}

	private static function Metadata(): void
	{
		meta(name: 'robots', content: 'noindex, nofollow, noarchive');
		meta(name: 'viewport', content: 'width=device-width, initial-scale=1.0');

		Page::Icon('images/favicon.ico');
		Page::Style('main.css');
	}

	private static function Begin(): void //body
	{
			//header
			headr(true);
			h1(Page::Title());
			headr(false);

			//navigation menu
			Page::Output(false); //disable direct output
			$side_top = array(
				a('home', href: 'index.php'),
				[a('section1', href: '#section1')],
				[a('section2 (drop down)', href: '#section2'),
					[
						a('section2a', href: '#section2a'),
						[a('section2b', href: '#section2b')],
					]],
				a('section3', href: '#section3'),
				a('section4', href: '#section4'),
				a('section5', href: '#section5'),
			);
			Page::Output(true); //enable direct output

			$menu = Page::Menu();
			$menu->Load($side_top);


		main(true);
	}

	private static function Finish(): void //end
	{
		main(false);
		footer(true);
			hr();
			small('End of every pages.');
		footer(false);
	}
}
