<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

class Element implements \Countable //of HTML DOM (data object model)
{
	private object $active; //active element for add child
	private ?object $parent = null; //parent element or none

	private string $tag = ''; //name ('' = only text in content)
	private bool $singular = false; //false = paired
	private ?bool $flag = null; //false = only abstract close element / true = keep open for add child elements (return this, no parent)

	protected array $attrib = []; //attributes
	private array $container = []; //inside data (text or child elements)

//Countable interface

	public function count() : int //child elements count
	{
		return count($this->container);
	}

//Construct

	public function __construct(string $tag, mixed ...$data)
	{
		$this->active = $this;
		$this->tag = $tag;
		$this->singular = array_search($this->tag, ['img', 'br', 'hr', 'input', 'link', 'col', 'area', 'meta', 'base', //list of all singular tags
		/*svg*/ 'path', 'rect', 'circle', 'ellipse', 'line', 'polygon', 'polyline', 'image', 'stop', 'set', 'animate', 'animateMotion', 'animateTransform'], true) !== false;

		foreach($data as $key => $val) //parse data
		{
			if(is_string($key)) $this->attrib[$key] = $val; //attributes (by named variables)
			elseif(is_array($val)) $this->attrib = array_merge($this->attrib, $val); //attributes (by array)
			elseif(is_string($val) || $val instanceof Element) array_push($this->container, $val); //text or child element
			elseif(is_bool($val)) $this->flag = $val;
		}
	}

	public function add(string|object ...$objects): object //add text(s) or element object(s) to active element node
	{
		foreach($objects as $obj)
		{
			if(empty($obj)) continue; //skip empty('') strings
			if(is_string($obj)) array_push($this->active->container, $obj); //add text
			elseif($obj instanceof Element)
			{
				if($obj->flag === false && empty($obj->container) && empty($obj->attrib))
				{
					//echo('<!-- / '.$this->active->tag.' <- '.$obj->tag.' -->'.PHP_EOL); //DEBUG - Close Tag
					$parent = $this->active->parent($obj->tag); //try to close
					if($parent) $this->active = $parent; //parent tag found
					else array_push($this->active->container, new Element('!', 'Error: Tag not found! (`'.$obj->tag.'` not open)')); //html comment
				}
				else //if($obj->flag === true || !empty($obj->container) || !empty($obj->attrib)) //same
				{
					//echo('<!-- + '.$this->active->tag.' -> '.$obj->tag.' -->'.PHP_EOL); //DEBUG - Open Tag
					$obj->parent = $this->active; //set parent to child
					array_push($this->active->container, $obj); //add child
					if($obj->flag === false) continue; //don't keep open when is forced close
					if($obj->singular) continue; //don't keep open when is singular
					if($obj->flag !== true && !empty($obj->container)) continue; //don't keep open when contain data and is not explicitly open
					if($obj->flag === true || !empty($obj->attrib)) $this->active = $obj; //keep open when is explicitly open or attributes is set
				}
			}
			else throw new \Exception('Element::add - Unsupported object type. Expected object `Element` or its child.');
		}
		return $this->active; //parent of added child
	}

	public function parent(string $tag = ''): ?object //get parent element (by name)
	{
		//if(is_null($this->parent)) return $this; //has no parent = return self
		if(!is_null($this->parent) && strlen($tag)) //want exact tag
		{
			if(strcasecmp($tag, $this->tag) != 0) return $this->parent->parent($tag); //recursion
		}
		return $this->parent;
	}

	public function activate(): object //reset this active element to self (this)
	{
		return $this->active = $this; //set self to active
	}

	public function close(): object //close self - set parent active element to self (parent)
	{
		if($this->parent) return $this->parent->activate();
		else return $this; //no parent = no close = return self
	}

	public function first(?string $tag = null, ?string $id = null): ?object //get child (by name and id)
	{
		foreach($this->container as $obj)
		{
			if($obj instanceof Element)
			{
				if(is_null($tag)) return $obj; //first object
				elseif(strcasecmp($tag, $obj->tag) == 0)
				{
					if(is_null($id)) return $obj; //first object with tag
					elseif(isset($obj->attrib['id']) && strcasecmp($id, $obj->attrib['id']) == 0) return $obj; //first object with tag and id
				}
			}
		}
		return null;
	}

	public function remove(string $tag, ?string $id = null): void
	{
		foreach($this->container as $key => $obj)
		{
			if($obj instanceof Element)
			{
				if(strcasecmp($tag, $obj->tag) == 0)
				{
					if(is_null($id)) unset($this->container[$key]);
					elseif(isset($obj->attrib['id']) && strcasecmp($id, $obj->attrib['id']) == 0) unset($this->container[$key]);
				}
			}
		}
	}

	public function clear(): void
	{
		$this->container = []; //destroy all children objects
	}

	public function attrib(?array $attrib = null): void //attributes set or clear
	{
		if(is_null($attrib)) $this->attrib = [];
		else $this->attrib = array_merge($this->attrib, $attrib);
	}

//output

	private function attributes(?array $attrib = null): string //attributes array to string
	{
		if(is_null($attrib)) $attrib = $this->attrib;
		$output = '';
		foreach($attrib as $key => $val)
		{
			if(is_null($val) || $val === false) continue; //value can be empty, but null or false is skipped
			if(is_array($val)) $output .= $this->attributes($val); //array recursion
			elseif(is_int($key)) $output .= ' '.htmlspecialchars(strval($val), ENT_NOQUOTES); //value only
			else //$key is string
			{
				$key = htmlspecialchars($key, ENT_NOQUOTES); // convert <>& (does not convert any quotes)
				if($val === true) $output .= ' '.$key; //key only
				else $output .= ' '.$key.'="'.htmlspecialchars(strval($val), ENT_COMPAT).'"'; //key="val" (convert only double quotes)
			}
		}
		return $output;
	}

	public function __toString(): string //magic method -> convert object to HTML string
	{
		$output = '';
		if($this->tag === '!') //html comment
		{
			$output .= '<!-- '.implode(' ', $this->container).' -->';
		}
		else //html tags
		{
			$output .= '<' . $this->tag . $this->attributes($this->attrib); //open tag + attributes
			if($this->singular) $output .= ' />'; //Syntactic sugar for self-closing void element. In html 5 is optional, but recommended.
			else //paired tag (others)
			{
				$output .= '>';
				//container
				foreach($this->container as $value)
				{
					if($value instanceof Element) $output .= strval($value);
					elseif(is_string($value)) $output .= str_replace(['<','>'], ['&lt;','&gt;'], $value); //htmlspecialchars($value, ENT_NOQUOTES); //no &
				}
				//closing paired tag
				$output .= '</'.$this->tag.'>'; //attributes are ignored
			}
		}
		return $output;
	}

	public function echo(): void
	{
		if($this->tag === '!') //html comment
		{
			$output = '<!-- '.implode(' ', $this->container).' -->';
			Page::echo($output, null);
		}
		else //html tags
		{
			$output = '<' . $this->tag . $this->attributes($this->attrib); //open tag + attributes
			if($this->singular)
			{
				$output .= ' />'; //Syntactic sugar for self-closing void element. In html 5 is optional, but recommended.
				Page::echo($output, null, $this->tag); //singular
			}
			else //paired tag (others)
			{
				$output .= '>';
				Page::echo($output, true, $this->tag); //open paired tag
				//container
				foreach($this->container as $value)
				{
					if($value instanceof Element) $value->echo();
					elseif(is_string($value)) Page::echo(str_replace(['<','>'], ['&lt;','&gt;'], $value)); //htmlspecialchars($value, ENT_NOQUOTES); //no &
				}
				//closing paired tag
				$output = '</'.$this->tag.'>'; //attributes are ignored
				Page::echo($output, false, $this->tag);
			}
		}
	}

    public function __debugInfo(): array
	{
        return array_merge([$this->tag], $this->container); //return array_merge([$this->tag], $this->attrib, $this->container);
    }

	public function __destruct()
	{
		unset($this->container); //destroy inside data
		unset($this->attrib); //destroy attributes
	}

//html tag aliases

	public function __call($name, $data): ?object //html tag function missing
	{
		if(strcasecmp($name, 't') == 0) return $this->active->add(implode('', ...$data)); //without tag name is only text
		else return $this->active->add(new Element($name, ...$data)); //create it :)
	}

	function href(bool|string $content = true, ?string $href = null, mixed ...$data): object //a hyperlink
	{
		return $this->active->add(new Element('a', $content, ['href' => Request::GetFileName($href)], $data));
	}

	function click(bool|string $content = true, ?string $onclick = null, mixed ...$data): object //a onclick
	{
		return $this->active->add(new Element('a', $content, ['href' => 'javascript:;', 'onclick' => $onclick], $data)); //run only onclick javascript
	}

	function img(string|array $src, string|array $alt = [], mixed ...$data): object //singular - image
	{
		if(is_string($src)) $src = array('src' => $src);
		if(is_string($alt)) $alt = array('alt' => $alt);
		return $this->active->add(new Element(__FUNCTION__, $src, $alt, $data));
	}
}

if(!trait_exists('PageTemplate', false)) { trait PageTemplate {} } //abstract trait (workaround for: function trait_exists() cannot be used inside a class)

class Page extends Singleton
{
	use PageTemplate; //implements user defined page customization

	private static array $open_tags = []; //control strings

	private static object $html; //master element

	private static float $starttime = 0; //loading page start time
	private static string $title = ''; //page title
	private static array $links = []; //page links

	protected function __construct()
	{
		self::$starttime = microtime(true);
		self::$html = new Element('html', true, lang: App::Env('APP_LANGUAGE')); //language declaration meant to assist search engines and browsers
	}

	public static function Start(?string $title = null): void
	{
		header('Content-Type: text/html; charset='.strtolower(App::Env('APP_ENCODING')));
		if(method_exists(__CLASS__, 'Finish')) self::Headers(); //replace or add custom headers

		if(Str::NotEmpty($title)) self::Title($title);

		//head
		head(true);
		title(Page::Title()); //required only once in every HTML document (must be text-only)
		meta(charset: strtolower(App::Env('APP_ENCODING')));
		meta(name: 'title', content: Page::Title());
		if(method_exists(__CLASS__, 'Metadata')) self::Metadata(true); //may contain links
		foreach(self::$links as $link) lnk($link); //add links
		head(false);

		//body
		body(true);
		if(method_exists(__CLASS__, 'Begin')) self::Begin();
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

	public static function Icon(string $href = 'favicon.ico', string $type = 'image/x-icon'): void
	{
		array_push(self::$links, ['rel' => 'icon', 'type' => $type, 'href' => $href]);
	}

	public static function Style(string $href = 'style.css', string $type = 'text/css'): void
	{
		array_push(self::$links, ['rel' => 'stylesheet', 'type' => $type, 'href' => $href]);
	}

	public static function Time(): float
	{
		return microtime(true) - self::$starttime;
	}

	//$type:
	//null - singular tag - void element - <$name />
	//true - paired tag - only open element - <$name>
	//false - paired tag - only close element - </$name> - attributes are ignored
	public static function echo(string $data, ?bool $type = null, string $tag = ''): void
	{
		if(App::Env('APP_DEBUG'))
		{
			static $prev_tag = '';

			if($type === true)
			{
				if(strlen($prev_tag)) echo PHP_EOL . str_repeat("\t", count(self::$open_tags)); //pretty print for debug HTML code
				array_push(self::$open_tags, $tag);
			}
			elseif($type === false) //closing paired tag
			{
				while($last_tag = array_pop(self::$open_tags))
				{
					if(strlen($prev_tag)) echo PHP_EOL . str_repeat("\t", count(self::$open_tags)); //pretty print for debug HTML code

					echo '</'.$last_tag.'>';
					$prev_tag = $tag;
					if($last_tag === $tag) return; //required tag reached
				}
				echo '<!-- Debug: Tag `'.$tag.'` not found! (not open) -->'; //tag not open
			}
			else if(strlen($prev_tag) && strlen($tag)) echo PHP_EOL . str_repeat("\t", count(self::$open_tags)); //pretty print for debug HTML code

			$prev_tag = $tag;
		}
		else //without pretty print - only DOM control
		{
			if($type === true) array_push(self::$open_tags, $tag);
			elseif($type === false) //closing paired tag
			{
				while($last_tag = array_pop(self::$open_tags))
				{
					echo '</'.$last_tag.'>';
					if($last_tag === $tag) return; //required tag reached
				}
				echo '<!-- Debug: Tag `'.$tag.'` not found! (not open) -->'; //tag not open
			}
		}
		echo $data; //output
	}

	public static function tag(string $tag, mixed ...$data): object 
	{
		if(empty($tag)) return self::$html->add(implode('', $data)); //add text (directly)
		else
		{
			$obj = new Element($tag, ...$data); //make object
			self::$html->add($obj); //add object
			return $obj; //return new object
		}
	}

	public static function __callStatic($name, $data): object //Page::{method} (is_callable([__CLASS__, 'method_name']) also triggers this function)
	{
		$obj = new $name(...$data); //try to load class and create object
		self::$html->add($obj); //add object
		return $obj; //return new object
	}

	public function __destruct()
	{
		//finish html
		if(method_exists(__CLASS__, 'Finish')) self::Finish();

		//output
		echo '<!DOCTYPE html>'.PHP_EOL; //HTML 5 declaration
		echo '<!-- Created with '.App::Env('3S_NAME').' -->'.PHP_EOL; //about
		if(App::Env('APP_DEBUG')) echo '<!-- Debug mode is enabled! (Disabled debug mode shortens the execution time.) -->'.PHP_EOL;
		echo sprintf('<!-- Page loaded in %.04f seconds. -->', self::Time()).PHP_EOL;
		//all html tags
		self::$html->echo(); //DEBUG: print_r(self::$html);
		//last info
		echo PHP_EOL.sprintf('<!-- Page done in %.04f seconds. -->', self::Time());
	}
}

Page::Initialize();

 //replace of `echo` command
function t(string $data): object { return Page::tag('', $data); } //text - not HTML tag (empty tag name is only text)

//html tag aliases
function title(string $data): object { return Page::tag(__FUNCTION__, $data); }
function script(string $data): object { return Page::tag(__FUNCTION__, $data); }
function noscript(string $data = 'Your browser does not support JavaScript!'): object { return Page::tag(__FUNCTION__, $data); }

//head tags
function base(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - specifies the base URL and/or target for all relative URLs
function lnk(mixed ...$data): object { return Page::tag('link', ...$data); } //singular - defines the relationship between the current document and an external resource (style sheets or to add a favicon) (keyword link is used by php)
function meta(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - metadata (information data) about an HTML document

function html(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //root of an HTML document
function head(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //container for metadata
function style(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function body(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //contains all the contents of an HTML document
function headr(mixed ...$data): object { return Page::tag('header', ...$data); } //represents a container for introductory content (keyword header is used by php)
function nav(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //defines a major navigation links or menu
function main(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //main content of the document
function footer(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //defines a footer for a document or section
function dialog(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //defines a dialog box or subwindow (popup dialogs and modals)
function section(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //block document section
function article(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //block self-contained article
function div(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //block division container
function p(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //block paragraph of content
function pre(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //block preformatted text
function blockquote(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //block long quotation
function figure(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //block self-contained content, like illustrations, diagrams, photos, etc.
function figcaption(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //<figcaption> element is FIRST or LAST child of the <figure> element
function span(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline part of content
function code(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline text as computer code
function h1(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //heading 1
function h2(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //heading 2
function h3(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //heading 3
function h4(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //heading 4 
function h5(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //heading 5
function h6(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //heading 6
function strong(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline important text (bold)
function small(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline smaller text
function mark(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline marked/highlighted text
function cite(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline title of a work (italic)
function dfn(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline definition term
function del(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline deleted part
function ins(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline inserted part
function sub(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline subscript
function sup(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline superscript
function em(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline emphasized (italic)
function s(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline strikethrough (incorrect text)
function q(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline short quotation
function i(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline alternate text, technical term, a phrase from another language (italic)
function b(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inline bold text without any extra importance (bold)
function a(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //anchor
function href(bool|string $content = '', ?string $href = null, mixed ...$data): object //a hyperlink
{
	return Page::tag('a', $content, ['href' => Request::GetFileName($href)], $data);
}
function click(bool|string $content = '', ?string $onclick = null, mixed ...$data): object //a onclick
{
	return Page::tag('a', $content, ['href' => 'javascript:;', 'onclick' => $onclick], $data); //run only onclick javascript
}

function template(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //container to hold hidden content when the page loads. can be rendered later with a JavaScript
function canvas(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //container for a script graphics
function svg(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //container for Scalable Vector Graphics

function menu(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //unordered list (same as ul)
function ul(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //unordered list (same as menu)
function ol(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //ordered list
function li(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //list item
function dl(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //description list
function dt(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //term
function dd(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //description

function table(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function caption(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function colgroup(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function col(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular
function thead(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function tbody(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function tfoot(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function tr(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function th(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function td(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }

function form(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function fieldset(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function legend(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function label(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function button(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function select(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function option(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function optgroup(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function textarea(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function input(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular
function radio(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular

function br(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - single line break
function hr(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - horizontal rule - defines a thematic break
function img(string|array $src = [], string|array $alt = [], mixed ...$data): object //singular - image
{
	if(is_string($src)) $src = array('src' => $src);
	if(is_string($alt)) $alt = array('alt' => $alt);
	return Page::tag(__FUNCTION__, $src, $alt, $data);
}
function map(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //img usemap="#name"
function area(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - defines an area inside an image map
