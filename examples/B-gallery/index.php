<?php declare(strict_types = 1);

define('GALLERY', './gallery'); //default path
//define('GIF1X1', 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs='); //valid 1x1 pixel GIF
define('GIF1X1', 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEAAAAALAAAAAABAAEAAAIBAAA='); //valid 1x1 pixel transparent GIF

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

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

		Page::Icon('images/pics.ico');
		Page::Style('styles/main.css');
		Page::Script('scripts/theme.js');
		Page::Script('scripts/search.js');
		Page::Script('scripts/thumbnail.js');
		Page::Script('scripts/timer.js');
		Page::Script('scripts/slideshow.js');
	}

	private static function Begin(): void //body
	{
		headr(true);
			h1(Page::Title());
			input(type: 'search', id: 'search-input',  placeholder: 'Search...');
			button(img('images/search.ico'), id: 'search-toggle');
			button(img('images/gear.ico'), id: 'theme-toggle');
		headr(false);
		main(true);
	}

	private static function Finish(): void //end
	{
		main(false);
	}
}

//START PAGE
$gallery = Request::GetParam('path') ?: GALLERY;

Page::Start($gallery); //title

if(is_dir($gallery) && FS::IsSubPath(GALLERY, $gallery, true))
{
	section(false, id: 'search-result');
	hr();
	$folders = section(false, id: 'folders');
	hr();
	$pictures = section(false, id: 'pictures');

	if(FS::IsSubPath(GALLERY, $gallery))
	{
		$folders->href(Request::Modify(['path' => GALLERY]), figure(img('images/back.ico'), figcaption('<HOME>')), ['data-name' => '']);
		$folders->href(Request::Modify(['path' => dirname($gallery)]), figure(img('images/back.ico'), figcaption('<DIR UP>')), ['data-name' => '']);
	}

	if(($handle = @opendir($gallery)) !== false)
	{
		while(($entry = readdir($handle)) !== false)
		{
			$path = $gallery.'/'.$entry; //path
			if(is_dir($path)) //folders
			{
				if(($entry !== '.') && ($entry !== '..') && ($entry[0] !== '#') && ($entry[0] !== '@'))
				{
					$folders->href(Request::Modify(['path' => $path]), figure(img('images/pics.ico', alt: $entry), figcaption($entry)), ['data-name' => $entry]);
				}
			}
			else //files
			{
				$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
				$ext = match($ext)
				{
					'jpg', 'jpeg' => 'jpeg',
					'png' => 'png',
					'bmp' => 'bmp',
					default => '',
				};
				if(!empty($ext))
				{
					$time = @filemtime($path); //modification time
					if($time === false) $time = 0; //permission denied?
					$pictures->button(figure(img(GIF1X1, alt: $entry, class: $ext, loading: 'lazy'), figcaption($entry, title: date('Y F d H:i:s', $time))), ['data-src' => $path, 'data-name' => $entry, 'data-time' => $time], onclick: 'openModal(this);');
				}
			}
		}
		closedir($handle);

		//sort
		$folders->sortby('data-name'); //by filename (case insensitive natural order)
		$pictures->sortby('data-name'); //by filename
		//$pictures->sortby('data-time'); //by date-time (integer)
	}
	else h1('Failed to open directory!'); //permission denied?

	comment('modal');
	div(id: 'modal');
	div(id: 'buttons');
	img('images/play.ico', id: 'slideshow');
	txt('&nbsp;');
	img('images/rewind.ico', id: 'rewind');
	img('images/prev.ico', id: 'prev');
	strong(false, id: 'counter');
	img('images/next.ico', id: 'next');
	img('images/forward.ico', id: 'forward');
	txt('&nbsp;');
	img('images/close.ico', id: 'close');
	div(false); //buttons
	img(id: 'zoomed');
	progress(id: 'progress', max: 100, value: 0);
	div(false);

}
else h1('Gallery not found!');
