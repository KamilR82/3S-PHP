<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

class Tree
{
	private array $data = [];

	public function add(object|string $name, mixed ...$data): void
	{
		if(is_object($name)) array_push($this->data, $name);
		else array_push($this->data, [$name, ...$data]);
	}

	public function echo()
	{
		foreach($this->data as $item)
		{
			if(is_object($item)) $item->echo();
			else Page::echo(...$item);
		}
	}

	public function __destruct()
	{
		unset($this->data);
	}
}

class Queue
{
	private array $data = [];

	public function add(object|string $name, mixed ...$data): void
	{
		if(is_object($name)) $name->explode($this); //explode object into data and add rows separately (object in $data is unwanted)
		else array_push($this->data, [$name, ...$data]);
	}

	public function first(string $name, mixed ...$data): void //set first
	{
		if(($key = array_key_first($this->data)) !== false) $this->data[$key] = [$name, ...$data]; //replace first
		else array_push($this->data, [$name, ...$data]); //$data is empty - add first
	}

	public function explode(object $obj): void //explode object to data
	{
		foreach($this->data as $row) $obj->add(...$row);
	}

	public function count()
	{
		return count($this->data);
	}

	public function echo()
	{
		foreach($this->data as $row) Page::echo(...$row);
	}

	public function __destruct()
	{
		unset($this->data);
	}
}

if(!trait_exists('PageTemplate', false)) { trait PageTemplate {} } //abstract trait (workaround for: function trait_exists() cannot be used inside a class)

class Page extends Singleton
{
	/** @var array<int, string> $open_tags */
	private static array $open_tags = [];

	private static bool $mode = true; //true = send tag to output queue / false = only translate tag to string and return it (no output)
	private static object $output; //output tags queue

	private static float $starttime = 0; //loading page start time
	private static string $title = ''; //page title
	private static array $links = []; //page links

	use PageTemplate; //implements user defined page customization

	protected function __construct(?string $title = null)
	{
		self::$starttime = microtime(true);
		self::$output = new Queue();
	}

	public static function Start(?string $title = null): void
	{
		header('Content-Type: text/html; charset='.strtolower(App::Env('APP_ENCODING')));
		if(is_callable([__CLASS__, 'Headers'])) self::Headers(); //replace or add custom headers

		if(Str::NotEmpty($title)) self::Title($title);

		//html
		self::Add('html', ['lang' => App::Env('APP_LANGUAGE')]); //language declaration meant to assist search engines and browsers

		//head
		self::Add('head', true);
		self::Add('title', Page::Title()); //required only once in every HTML document (must be text-only)
		self::Add('meta', ['charset' => strtolower(App::Env('APP_ENCODING'))]);
		self::Add('meta', ['name' => 'title', 'content' => Page::Title()]);
		if(is_callable([__CLASS__, 'Metadata'])) self::Metadata(true); //may contain links
		foreach(Page::Links() as $link) self::Add('link', $link); //add links
		self::Add('head', false);

		//body
		self::Add('body', true);
		if(is_callable([__CLASS__, 'Begin'])) self::Begin();
	}

	public static function Title(?string $title = null, bool $only = false): string //$only = don't append app name
	{
		if($title !== null || Str::IsEmpty(self::$title))
		{
			if(Str::NotEmpty($title))
			{
				self::$title = $title;
				if(!$only) self::$title .= ' - ' . App::Env('APP_NAME'); //append app name
			}
			else self::$title = App::Env('APP_NAME'); //default - only app name
		}
		return self::$title;
	}

	public static function Links(): array
	{
		return self::$links;
	}

	public static function Icon(string $href, string $type = 'image/x-icon'): void
	{
		array_push(self::$links, ['rel' => 'icon', 'type' => $type, 'href' => $href]);
	}

	public static function Style(string $href, string $type = 'text/css'): void
	{
		array_push(self::$links, ['rel' => 'stylesheet', 'type' => $type, 'href' => $href]);
	}

	public static function Time(): float
	{
		return microtime(true) - self::$starttime;
	}

	public static function Menu(array $menu, string $selected = 'selected'): void //shortcut for make menu
	{
		new Menu($menu, $selected); //create, load, echo and free memory at function end
	}

	public static function Output(bool $mode = true): void
	{
		self::$mode = $mode;
	}

	//for all functions bellow $data:
	//null - singular tag - void element - <$name />
	//true - paired tag - only open element - <$name>
	//false - paired tag - only close element - </$name> - attributes are ignored
	//'string' - complete paired tag - <$name>$content</$name>

	public static function Data(string $name, mixed ...$data): ?string
	{
		if(self::$mode) Page::Add($name, ...$data); //output mode - send tag to output queue
		else return Page::Tag($name, ...$data); //prepare mode - only translate tag to string and return it (no output)
		return null;
	}

	public static function Add(object|string $name, mixed ...$data): void
	{
		self::$output->add($name, ...$data);
	}

	private static function Parse(string $name, array & $data): null|bool|string //data -> content & attributes
	{
		$content = null; //tag data or type
		$attrib = []; //tag attributes
		//parse data
		foreach($data as $key => $val)
		{
			if(is_array($val))
			{
				$attrib = array_merge($attrib, $val); //add to attributes
				unset($data[$key]); //remove attrib from content
			}
			elseif(is_bool($val) || is_null($val))
			{
				$content = $val; //set tag type (open/close/singular)
				unset($data[$key]); //remove
			}
		}
		if(count($data) > 0) $content = implode('', $data); //get content (if exists)
		$data = $attrib;
		//additional input control for paired tags (if user not set true for empty paired tag to open)
		if(is_null($content) && array_search($name, ['img', 'br', 'hr', 'input', 'link', 'col', 'meta', 'base']) === false) $content = true; //list of singular tags
		return $content;
	}

	public static function Tag(string $name, mixed ...$data): string //data -> HTML string
	{
		$content = self::Parse($name, $data);
		//make output
		$output = '';
		if($content === false) $output .= '</'.$name.'>'; //closing paired tag (attributes are ignored)
		else
		{
			if(Str::NotEmpty($name))
			{
				$output .= '<'.$name;
				foreach($data as $key => $val) //attributes
				{
					if(is_null($val) || $val === false) continue; //value can be empty, but null or false is discarded
					if(is_int($key)) $output .= ' '.$val; //val only
					else //$key is string
					{
						if($val === true) $output .= ' '.$key; //key only
						else $output .= ' '.$key.'="'.$val.'"'; //key="val"
					}
				}

				if(is_null($content)) $output .= ' /'; //Syntactic sugar for self-closing void element. In html 5 is optional, but recommended.
				$output .= '>';

				if(is_string($content)) $output .= $content . '</'.$name.'>'; //complete paired tag
			}
			else $output = $content; //only text without tag
		}
		return $output;
	}

	public static function echo(string $name, mixed ...$data): void //data -> echo with check HTML DOM (Document Object Model)
	{
		static $prev_tag = '';

		$content = self::Parse($name, $data);
		//make output
		$output = '';
		if($content === false) //closing paired tag(s)
		{
			if(Str::IsEmpty($name) || in_array($name, self::$open_tags)) //tag open?
			{
				while($last_tag = array_pop(self::$open_tags))
				{
					if(App::Env('APP_DEBUG')) $output .= PHP_EOL . str_repeat("\t", count(self::$open_tags)); //pretty print for debug HTML code

					$output .= '</'.$last_tag.'>'; //attributes are ignored
					if($last_tag === $name) break; //required tag reached
				}
			}
			else if(App::Env('APP_DEBUG')) $output .= '<!-- Debug: Tag `'.$name.'` not found! -->'; //tag not open
		}
		else
		{
			if(Str::NotEmpty($name))
			{
				if(App::Env('APP_DEBUG') && strlen($prev_tag)) $output .= PHP_EOL . str_repeat("\t", count(self::$open_tags)); //pretty print for debug HTML code

				$output .= '<'.$name;
				foreach($data as $key => $val) //attributes
				{
					if(is_null($val) || $val === false) continue; //value can be empty, but null or false is discarded
					if(is_int($key)) $output .= ' '.$val; //val only
					else //$key is string
					{
						if($val === true) $output .= ' '.$key; //key only
						else $output .= ' '.$key.'="'.$val.'"'; //key="val"
					}
				}

				if(is_null($content)) $output .= ' /'; //Syntactic sugar for self-closing void element. In html 5 is optional, but recommended.
				$output .= '>';

				if(is_string($content)) $output .= $content . '</'.$name.'>'; //complete paired tag
				else if($content === true) array_push(self::$open_tags, $name); //save open tag
			}
			else $output = $content; //only text without tag
		}
		$prev_tag = $name;
		echo $output;
	}

	public function __destruct()
	{
		if(self::$output->count())
		{
			//declaration
			echo '<!DOCTYPE html>'.PHP_EOL; //HTML 5
			echo '<!-- Created with '.App::Env('3S_NAME').' -->'.PHP_EOL;
			if(App::Env('APP_DEBUG')) echo '<!-- Debug mode is enabled! -->'.PHP_EOL;
			//finish html
			if(is_callable([__CLASS__, 'Finish'])) self::Finish();
			self::Add('', false); //close all open tags (...,body,html)
			//END
			self::$output->echo(); //and now echo all html tags
		}
	}
}

Page::Initialize();

 //replace of command `echo`
function t(string $data): ?string { return Page::Data('', $data); } //without tag name is only text
function txt(string $data): ?string { return Page::Data('', $data); } //without tag name is only text
function text(string $data): ?string { return Page::Data('', $data); } //without tag name is only text

//html tag aliases
function title(string $data): ?string { return Page::Data(__FUNCTION__, $data); }
function script(string $data): ?string { return Page::Data(__FUNCTION__, $data); }
function noscript(string $data = 'Your browser does not support JavaScript!'): ?string { return Page::Data(__FUNCTION__, $data); }

//Singular Tags (only for head)
function base(array $attrib = []): ?string { return Page::Data(__FUNCTION__, $attrib); } //specifies the base URL and/or target for all relative URLs
function lnk(array $attrib = []): ?string { return Page::Data('link', $attrib); } //defines the relationship between the current document and an external resource (style sheets or to add a favicon) (keyword link is used by php)
function meta(array $attrib = []): ?string { return Page::Data(__FUNCTION__, $attrib); } //metadata (information data) about an HTML document

function html(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //root of an HTML document
function head(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //container for metadata
function style(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function body(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //contains all the contents of an HTML document
function headr(mixed ...$data): ?string { return Page::Data('header', ...$data); } //represents a container for introductory content (keyword header is used by php)
function nav(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //defines a major navigation links or menu
function main(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //main content of the document
function footer(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //defines a footer for a document or section

function section(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //block document section
function article(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //block self-contained article
function div(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //block division container
function p(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //block paragraph of content
function pre(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //block preformatted text
function blockquote(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //block long quotation
function figure(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //block self-contained content, like illustrations, diagrams, photos, etc.
function figcaption(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //<figcaption> element is FIRST or LAST child of the <figure> element
function span(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline part of content
function code(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline text as computer code
function h1(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //heading 1
function h2(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //heading 2
function h3(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //heading 3
function h4(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //heading 4 
function h5(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //heading 5
function h6(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //heading 6
function em(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline emphasized (italic)
function strong(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline strong (bold)
function small(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline smaller text
function mark(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline highlighted
function sub(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline subscript
function sup(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline superscript
function s(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline strikethrough (incorrect)
function q(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //inline short quotation
function i(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function b(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function a(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //anchor
function href(bool|string $content = true, ?string $href = null, ?string $title = null, ?string $target = null, array $attrib = []): ?string //a hyperlink
{
	$href = Request::GetFileName($href);
	return Page::Data('a', $content, ['href' => $href, 'title' => $title, 'target' => $target], $attrib);
}
function click(bool|string $content = true, ?string $onclick = null, ?string $title = null, array $attrib = []): ?string //a onclick
{
	return Page::Data('a', $content, ['href' => 'javascript:;', 'title' => $title, 'onclick' => $onclick], $attrib); //run only onclick javascript
}

function menu(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //unordered list (same as ul)
function ul(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //unordered list (same as menu)
function ol(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //ordered list
function li(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //list item
function dl(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //description list
function dt(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //term
function dd(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); } //description

function table(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function caption(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function colgroup(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function col(array $attrib = []): ?string { return Page::Data(__FUNCTION__, $attrib); } //singular tag
function thead(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function tbody(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function tfoot(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function tr(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function th(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function td(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }

function form(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function fieldset(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function legend(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function label(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function button(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function select(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function option(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function optgroup(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function textarea(mixed ...$data): ?string { return Page::Data(__FUNCTION__, ...$data); }
function input(array $attrib = []): ?string { return Page::Data(__FUNCTION__, $attrib); } //singular tag
function radio(array $attrib = []): ?string { return Page::Data(__FUNCTION__, $attrib); } //singular tag

function br(array $attrib = []): ?string { return Page::Data(__FUNCTION__, $attrib); } //single line break
function hr(array $attrib = []): ?string { return Page::Data(__FUNCTION__, $attrib); } //horizontal rule - defines a thematic break
function img(string|array $src, null|string|array $alt = null, null|string|int $w = null, null|string|int $h = null, array $attrib = []): ?string //image
{
	if(is_string($src)) $src = array('src' => $src);
	if(is_string($alt)) $alt = array('alt' => $alt);
	if(Any::NotEmpty($w)) $attrib['width'] = $h;
	if(Any::NotEmpty($h)) $attrib['height'] = $h;
	return Page::Data(__FUNCTION__, $src, $alt, $attrib);
}
