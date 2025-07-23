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
		Page::Script('scripts/thumbnail.js');
	}

	private static function Begin(): void //body
	{
		//header
		headr(h1(Page::Title()), button(id: 'theme-toggle'));

		//content
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
	//h4($gallery.DIRECTORY_SEPARATOR);

	$folders = section(false, id: 'folders'); //prerobit vracanie ak dam nieco do vnutra !!!
	hr();
	$pictures = section(false, id: 'pictures');

	if(FS::IsSubPath(GALLERY, $gallery))
	{
		$back = dirname($gallery);
		$folders->href(Request::Modify(['path' => $back]), figure(img('images/back.ico'), figcaption('BACK')), ['data-time' => 0]);
	}

	if(($handle = opendir($gallery)) !== false)
	{
		while(($entry = readdir($handle)) !== false)
		{
			$path = $gallery.'/'.$entry; //path
			$time = filemtime($path); //modification time

			if(is_dir($path)) //folders
			{
				if(($entry !== '.') && ($entry !== '..'))
				{
					$default = 'images/pics.ico'; //folder icon
					$folders->href(Request::Modify(['path' => $path]), figure(img($default), figcaption($entry)), ['data-time' => $time]);
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

		//sort
	}

	comment('modal');
	div(id: 'modal');
	div(id: 'buttons');
	click('imgFirst();', img('images/rewind.ico'));
	click('imgPrev();', img('images/prev.ico'));
	click('closeModal();', img('images/close.ico'));
	click('imgNext();', img('images/next.ico'));
	click('imgLast();', img('images/forward.ico'));
	div(false);
	img(id: 'slideshow');
	div(false);
}
else h1('Gallery not found!');
