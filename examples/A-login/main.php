<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

//CUSTOM START CODE FOR EVERY PAGE
App::Protect(__FILE__); //can't run this file individually

Log::Request(); //log request

if(!User::IsLoggedIn() && !Request::IsFileName('index.php')) Request::Redirect(); //login check (not logged in = redirect to index.php)

if(Request::IsParam('lang', raw: true)) Lang::Set(Request::GetParam('lang', raw: true)); //set language from user request

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
		if(User::IsLoggedIn())
		{
			//header
			headr(true);
			input(['type'=>'checkbox', 'name'=>'burger', 'id'=>'burger', 'aria-label'=>'Toggle navigation']);
			h1(Page::Title()) . h5('You are logged in as '.User::GetFullName());
			headr(false);

			//navigation menu
			Page::Output(false); //disable direct output
			$side_top = array(
				a(img('images/home.png'), _L('home'), href: 'home.php'),
				[a(img('images/right.png'), _L('section').'1', href: '#section1')],
				[a(img('images/boxes.png'), _L('section').'2', input(type: 'checkbox')),
					[
						a(img('images/boxes.png'), _L('section').'2a', href: '#section2a'),
						[a(img('images/boxes.png'), _L('section').'2b', href: '#section2b')],
						[a(img('images/boxes.png'), _L('section').'2c', href: '#section2c'), Permits::Admin],
						[a(img('images/boxes.png'), _L('section').'2d', href: '#section2d'), Permits::Admin, PermitLevel::Lock],
					]],
				a(img('images/truck.png'), _L('section').'3', href: '#section3'),
			);
			$side_bottom = array(
				a(img('images/settings.png'), _L('settings'), href: 'settings.php'),
				a(img('images/power.png'), _L('logout'), href: 'logout.php'),
			);
			Page::Output(true); //enable direct output

			$menu = Page::Menu();
			$menu->Load(User::MenuFilter($side_top)); //filter menu by user permissions
			$menu->Load($side_bottom);

			//content
			div(['id' => 'content']); //scrollable
		}
		main(true);
	}

	private static function Finish(): void //end
	{
		main(false);
		footer(sprintf(_L('footer'), DT::Conv(format: _L('footer_today_format')), Page::Time()));
	}
}
