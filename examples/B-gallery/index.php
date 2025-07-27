<?php declare(strict_types = 1);

define('GALLERY', 'gallery');

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
		Page::Script('scripts/timer.js');
		Page::Script('scripts/thumbnail.js');
	}

	private static function Begin(): void //body
	{
		headr(h1(Page::Title()), button(id: 'theme-toggle'));
		main(true);
	}

	private static function Finish(): void //end
	{
		main(false);
	}
}

//START PAGE
Page::Start('Gallery'); //title

$gallery = Request::GetParam('path') ?: GALLERY;

if(is_dir($gallery) && FS::IsSubPath(GALLERY, $gallery, true))
{
	h4($gallery.DIRECTORY_SEPARATOR);
	hr();
	$folders = section(false, id: 'folders');
	hr();
	$pictures = section(false, id: 'pictures');

	if(FS::IsSubPath(GALLERY, $gallery))
	{
		$back = dirname($gallery);
		$folders->href(Request::Modify(['path' => $back]), figure(img('images/back.ico'), figcaption('BACK')), ['data-name' => '', 'data-time' => 0]);
	}

	if(($handle = @opendir($gallery)) !== false)
	{
		while(($entry = readdir($handle)) !== false)
		{
			$path = $gallery.'/'.$entry; //path
			$time = @filemtime($path); //modification time
			if($time === false) h2('Failed to read directory!');

			if(is_dir($path)) //folders
			{
				if(($entry !== '.') && ($entry !== '..') && ($entry[0] !== '#') && ($entry[0] !== '@'))
				{
					$default = 'images/pics.ico'; //folder icon
					$folders->href(Request::Modify(['path' => $path]), figure(img($default), figcaption($entry)), ['data-name' => $entry, 'data-time' => $time]);
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
					$default = 'images/'.$ext.'.ico'; //image icon
					$pictures->button(figure(img($default), figcaption($entry, title: date('Y F d H:i:s', $time))), ['data-src' => $path, 'data-time' => $time], onclick: 'openModal(this);');
				}
			}
		}
		closedir($handle);

		//sort dirs by name (case insensitive natural order)
		$folders->sortby('data-name');
	}
	else h1('Failed to open directory!'); //Permission denied?

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
