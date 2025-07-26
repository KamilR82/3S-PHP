<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

//DEFAULT CONSTANTS
define('APP_PRETTY_PRINT', true); //pretty output html code
//define('HARD_DEBUG_MODE', true); //output tags add,rem,mov,del

class Element implements \Countable, \ArrayAccess, \IteratorAggregate //Element of HTML DOM (data object model)
{
	private string $tag = ''; //name ('' = only text, ! = html comment)
	private object $active; //active element for add child (pointer)
	private ?object $parent = null; //parent element or none

	private ?bool $flag = null; //false = abstract close element / true = keep open for add child elements

	protected array $attrib = []; //attributes
	private ?array $container = []; //inside data (text or child elements), null = singular

//Interfaces
	public function count(): int { return is_null($this->container) ? -1 : count($this->container); } //Countable
	public function getIterator(): Traversable { return new ArrayIterator($this->container ?: []); } //IteratorAggregate (foreach)
	//ArrayAccess (data[key])
    public function offsetExists($offset): bool { return boolval($this->first($offset)); } //isset() or empty()
    public function offsetUnset($offset): void { $this->remove($offset); } //remove object
    public function offsetGet($offset): ?object { return $this->first($offset); } //get object
    public function offsetSet($offset, $value): void { throw new \Exception('Element[key] set is unsupported. Use `Element->add` or `Element->open`'); }

//Construct

	public function __construct(string $tag, mixed ...$data)
	{
		$this->tag = $tag; //tag name
		$this->active = $this; //activate self

		foreach($data as $key => $val) //parse data
		{
			if(is_string($key)) $this->attrib[$key] = $val; //attributes (by named variables)
			elseif(is_bool($val)) $this->flag = $val;
			elseif(is_array($val)) $this->attrib = array_merge($this->attrib, $val); //attributes (by array)
			elseif(is_string($val)) array_push($this->container, $val); //pure text
			elseif($val instanceof Element) //child element
			{
				if($val->parent) //child exists in DOM -> move (same object can exist only once)
				{
					if(defined('HARD_DEBUG_MODE')) echo('<!-- = '.$val.' = '.$val->parent.' <-> '.$this.' -->'.PHP_EOL); //DEBUG - Move Tag

					$key = array_search($val, $val->parent->container, true); //find in parent container
					if($key !== false) unset($val->parent->container[$key]); //remove from parent container

					//change parents
					$this->active = $val->parent; //set old parent to temporary
					$val->parent = $this; //set new parent
				}
				array_push($this->container, $val); //add
			}
		}

		//singular?
		if(array_search($this->tag, ['img', 'br', 'hr', 'input', 'link', 'col', 'meta', 'base', 'area', 'source', 'track', //list of all singular tags
		/*svg*/ 'path', 'rect', 'circle', 'ellipse', 'line', 'polygon', 'polyline', 'image', 'stop', 'set', 'animate', 'animateMotion', 'animateTransform'], true) !== false) $this->container = null;
	}

	public function add(string|object ...$objects): object //add text(s) or element object(s) to active element node
	{
		foreach($objects as $obj)
		{
			if(empty($obj)) continue; //skip empty('') strings
			if(is_string($obj)) array_push($this->active->container, $obj); //add text
			elseif($obj instanceof Element)
			{
				if($obj->flag === false && empty($obj->container) && empty($obj->attrib)) $this->close($obj->tag); //try to close
				else $this->open($obj); //if($obj->flag === true || !empty($obj->container) || !empty($obj->attrib)) //open
			}
			else throw new \Exception('Element::add - Unsupported object type. Expected object `Element` or its child.');
		}
		return $this->active; //parent of added child
	}

	public function open(object $obj): object //add ONE child element OBJECT
	{
		//prevent possible infinite loop when a tag remains open in another tag inline: div(button(id: 'btn_still_active')); 
		if($this->active->parent === $obj) // throw new \Exception('Element::open - Child === Parent.');
		{
			$this->activate($obj->active); //set caller active from temporaty 
			$obj->active = $obj; //set temporary back to self active
		}

		if(defined('HARD_DEBUG_MODE')) echo('<!-- + '.$this->active.' -> '.$obj.' -->'.PHP_EOL); //DEBUG - Open Tag
		if(is_null($this->active->container)) throw new \Exception('Element::open - Parent is singular. Expected paired tag.');

		if(App::Env('APP_DEBUG')) //add tag attribute `data-tag-caller` and `data-tag-id`
		{
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); //arguments not needed
			$last = end($backtrace); //array last item
			if(isset($last['file'])) $obj->attrib(['data-tag-caller' => basename($last['file']).':'.($last['line'])]); //__destruct has no file & line
			else $obj->attrib(['data-tag-caller' => ($last['class']??'') . ($last['type']??'') . $last['function']]); //class & function
			$obj->attrib(['data-tag-id' => strval($obj)]); //element object id
		}

		$obj->parent = $this->active; //set parent to child
		array_push($this->active->container, $obj); //add child

		//set (or not) active
		if($obj->flag === false || is_null($obj->container)) return $this->active; //don't keep open when is singular or forced close
		if($obj->flag !== true && !empty($obj->container)) return $this->active; //don't keep open when contain data and is not explicitly open
		if($obj->flag === true || !empty($obj->attrib)) $this->active = $obj; //SET - keep open when is explicitly open or attributes is set
		return $this->active;
	}

	public function close(string $tag = ''): object //close child 
	{
		if(defined('HARD_DEBUG_MODE')) echo('<!-- / '.$this->active.' <- `'.$tag.'` -->'.PHP_EOL); //DEBUG - Close Tag

		$parent = $this->active->parent($tag); //try to close
		if($parent) $this->active = $parent; //parent tag found
		else array_push($this->active->container, new Element('!', 'Error: Tag not found! (`'.$tag.'` not open)')); //error as html comment
		return $this->active;
	}

	public function parent(string $tag = ''): ?object //get parent element (by name)
	{
		if($this->parent && !empty($tag)) //want exact tag
		{
			if(!$this->is($tag)) return $this->parent->parent($tag); //recursion
		}
		return $this->parent;
	}

	public function activate(?object $obj = null): object //reset active element to self (or other)
	{
		return $this->active = $obj ?: $this;
	}

	public function active(): object //get
	{
		return $this->active;
	}

	public function tag(): string //get
	{
		return $this->tag;
	}

	public function is(?string $tag, ?string $id = null): bool //compare tag name and id (null,null = retrun true)
	{
		if((is_null($tag) || (strcasecmp($this->tag, $tag) === 0)) &&
			(is_null($id) || (isset($this->attrib['id']) && strcasecmp($this->attrib['id'], $id) === 0))) return true;
		return false;
	}

	public function first(?string $tag = null, ?string $id = null): ?object //get child (by name and id)
	{
		foreach($this->container as $obj)
		{
			if($obj instanceof Element && $obj->is($tag, $id)) return $obj;
		}
		return null;
	}

	public function remove(string $tag, ?string $id = null): void
	{
		foreach($this->container as $key => $obj)
		{
			if($obj instanceof Element && $obj->is($tag, $id))
			{
				if(defined('HARD_DEBUG_MODE')) echo('<!-- - '.$this.' <- '.$obj.' -->'.PHP_EOL); //DEBUG - remove tag
				unset($this->container[$key]);
			}
		}
	}

	public function clear(): void
	{
		if(!is_null($this->container)) $this->container = []; //if not singular - clear content (destroy all objects and texts inside element)
	}

	public function class(null|string|array $add = null, null|string|array $rem = null): ?array
	{
		$classes = isset($this->attrib['class']) ? explode(' ', $this->attrib['class']) : []; //exists classes?
		if(!is_null($add)) //append
		{
			if(is_string($add)) $add = explode(' ', $add);
			$classes = array_unique(array_merge($classes, $add));
		}
		if(!is_null($rem)) //remove
		{
			if(is_string($rem)) $rem = explode(' ', $rem);
			$classes = array_diff($classes, $rem);
		}
		if(empty($classes)) unset($this->attrib['class']); //unset
		else $this->attrib['class'] = implode(' ', $classes); //set
		return $classes;
	}

	public function attrib(?array $attrib = null): void //attributes set or clear
	{
		if(is_null($attrib)) $this->attrib = [];
		else $this->attrib = array_merge($this->attrib, $attrib);
	}

	public function sortby(string $attrib_name, bool $asc = true): void //sort children (case insensitive natural order)
	{
		usort($this->container, function($a, $b) use ($attrib_name, $asc) 
		{
			if($a instanceof Element && $b instanceof Element)
			{
				if(isset($a->attrib[$attrib_name]) && isset($b->attrib[$attrib_name]))
				{
					return $asc ? strnatcasecmp($a->attrib[$attrib_name], $b->attrib[$attrib_name]) : strnatcasecmp($b->attrib[$attrib_name], $a->attrib[$attrib_name]);
				}
			}
			return 0; //equal or incomparable
		});
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
			elseif(is_int($key)) $output .= ' '.preg_replace('/[^a-zA-Z0-9_-]/', '', strval($val)); //value only (without key)
			else //$key is string
			{
				$key = preg_replace('/[^a-zA-Z0-9_-]/', '', $key); //sanitize key
				if($val === true) $output .= ' '.$key; //key only
				else $output .= ' '.$key.'="'.str_replace('"', '&quot;', strval($val)).'"'; //key="val" (convert double quotes only)
			}
		}
		return $output;
	}

	public function echo(): void //tree traverse
	{
		if($this->tag === '!') //html comment
		{
			$output = '<!-- '.implode(' ', $this->container).' -->';
			Page::echo($output, null, $this->tag); //without tag = do not break the line
		}
		else //html tags
		{
			$output = '<' . $this->tag . $this->attributes($this->attrib); //open tag + attributes
			if(is_null($this->container)) //singular
			{
				$output .= ' />'; //Syntactic sugar for self-closing void element. In html 5 is optional, but recommended.
				Page::echo($output, null, $this->tag); //singular
			}
			else //paired tag (others)
			{
				$output .= '>';
				if(empty($this->container)) //no children
				{
					$output .= '</'.$this->tag.'>'; //close tag on same line
					Page::echo($output, null, $this->tag); //send like a singular
				}
				else
				{
					Page::echo($output, true, $this->tag); //open paired tag
					//container
					foreach($this->container as $value)
					{
						if($value instanceof Element) $value->echo();
						elseif(is_string($value)) Page::echo(str_replace(['<','>'], ['&lt;','&gt;'], $value)); //do not translate `&`
					}
					//closing paired tag
					$output = '</'.$this->tag.'>'; //attributes are ignored
					Page::echo($output, false, $this->tag);
				}
			}
		}
	}

	public function __toString(): string //echo($obj) / strval($obj)
	{
		return $this->tag . ':' . spl_object_id($this);
	}

    public function __debugInfo(): array //var_dump($obj) / print_r($obj)
	{
		return array(
			'tag:id' => $this->tag . ':' . spl_object_id($this),
			'parent:id' => isset($this->parent) ? $this->parent->tag . ':' . spl_object_id($this->parent) : null,
			'active:id' => isset($this->active) ? $this->active->tag . ':' . spl_object_id($this->active) : null,
			'children' => count($this), //singular = -1
		);
    }

	public function __destruct()
	{
		unset($this->container); //destroy inside objects
	}

//html tag aliases

	public function __call($name, $data): ?object //html tag function missing
	{
		if(strcasecmp($name, 't') == 0) return $this->active->add(implode('', ...$data)); //without tag name is only text
		else return $this->active->add(new Element($name, ...$data)); //create it :)
	}

	//custom tag aliases

	function href(?string $href = null, mixed ...$data): object //a hyperlink
	{
		return $this->active->add(new Element('a', ['href' => Request::GetFileName($href)], ...$data));
	}

	function click(?string $onclick = null, mixed ...$data): object //a onclick
	{
		return $this->active->add(new Element('a', ['href' => 'javascript:;', 'onclick' => $onclick], ...$data)); //run only onclick javascript
	}

	function img(string|array $src, string|array $alt = [], mixed ...$data): object //singular - image
	{
		if(is_string($src)) $src = array('src' => $src);
		if(is_string($alt)) $alt = array('alt' => $alt);
		return $this->active->add(new Element(__FUNCTION__, $src, $alt, ...$data));
	}
}

if(!trait_exists('PageTemplate', false)) { trait PageTemplate {} } //abstract trait (workaround for: function trait_exists() cannot be used inside a class)

class Page extends Singleton
{
	use PageTemplate; //implements user defined page customization

	private static array $open_tags = []; //control strings

	private static object $html; //master element
	private static bool $output = true; //true = add tags to dom, false = only return object

	private static float $starttime = 0; //loading page start time
	private static string $title = ''; //page title
	private static array $links = []; //page elements in head

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
		if(method_exists(__CLASS__, 'Metadata')) self::Metadata(true);
		self::$html->add(...self::$links); //add links
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
		array_push(self::$links, new Element('link', ['rel' => 'icon', 'type' => $type, 'href' => $href]));
	}

	public static function Style(string $href = 'style.css', string $type = 'text/css'): void
	{
		array_push(self::$links, new Element('link', ['rel' => 'stylesheet', 'type' => $type, 'href' => $href]));
	}

	public static function Script(string $src = 'style.css', string $type = 'text/javascript'): void
	{
		array_push(self::$links, new Element('script', ['type' => $type, 'src' => $src], false)); //`false` because `script` is not singleton
	}

	public static function Time(): float
	{
		return microtime(true) - self::$starttime;
	}

	public static function Output(bool $output): void
	{
		self::$output = $output;
	}

	//$type:
	//null - singular tag - void element - <$name />
	//true - paired tag - only open element - <$name>
	//false - paired tag - only close element - </$name> - attributes are ignored
	public static function echo(string $data, ?bool $type = null, string $tag = ''): void //output string
	{
		if(App::Env('APP_PRETTY_PRINT'))
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
				echo '<!-- Tag `'.$tag.'` not found! (not open) -->'; //tag not open
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
				echo '<!-- Tag `'.$tag.'` not found! (not open) -->'; //tag not open
			}
		}
		echo $data; //output
	}

	public static function tag(string $tag, mixed ...$data): ?object 
	{
		if(empty($tag))
		{
			self::$html->add(implode('', $data)); //add text (directly)
			return null;
		}
		else
		{
			$obj = new Element($tag, ...$data); //make object
			if(self::$output) self::$html->add($obj); //add object
			return $obj; //return new object
		}
	}

	public static function __callStatic($name, $data): object //Page::{method} (is_callable([__CLASS__, 'method_name']) also triggers this function)
	{
		$obj = new $name(...$data); //try to load class and create object
		if(self::$output) self::$html->open($obj); //add object
		return $obj; //return new object
	}

	public function __destruct()
	{
		if(method_exists(__CLASS__, 'Finish')) self::Finish(); //finish html

		//output
		echo '<!DOCTYPE html>'.PHP_EOL; //HTML 5 declaration
		echo '<!-- Created with '.App::Env('3S_NAME').' -->'.PHP_EOL; //about
		if(App::Env('APP_DEBUG')) echo '<!-- Debug mode is enabled! (Disabled debug mode shortens the execution time.) -->'.PHP_EOL;
		echo sprintf('<!-- Page loaded in %.04f seconds. -->', self::Time()).PHP_EOL;
		self::$html->echo(); //whole DOM tree (all html tags)
		echo PHP_EOL.sprintf('<!-- Page done in %.04f seconds. -->', self::Time()); //last info
	}
}

Page::Initialize();

//text and comment
function txt(string $data): void { Page::tag('', $data); } //text-only, not HTML tag (empty tag name is only text)
function text(string $data): void { Page::tag('', $data); } //text-only, not HTML tag (empty tag name is only text)
function rem(string $data = ''): void { Page::tag('!', $data); } //text-only,  <!-- comment -->
function comment(string $data = ''): void { Page::tag('!', $data); } //same as rem

//main tags
function html(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //root of an HTML document
function head(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //container for metadata
function body(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //contains all the contents of an HTML document
function headr(mixed ...$data): object { return Page::tag('header', ...$data); } //represents a container for introductory content (keyword header is used by php)
function nav(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //defines a major navigation links or menu
function main(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //main content of the document
function footer(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //defines a footer for a document or section
function dialog(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //defines a dialog box or subwindow (popup dialogs and modals)

//head tags
function meta(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - metadata (information data) about an HTML document
function title(string $data): object { return Page::tag(__FUNCTION__, $data); } //text-only, required in HTML document, only once
function style(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function lnk(mixed ...$data): object { return Page::tag('link', ...$data); } //singular - defines the relationship between the current document and an external resource (style sheets or to add a favicon) (keyword link is used by php)
function script(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function noscript(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //can be used in both <head> and <body>. When used inside <head>, could only contain <link>, <style>, and <meta> elements.
function base(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - specifies the base URL and/or target for all relative URLs

//blocks
function section(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //document section
function article(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //self-contained article
function div(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //division container
function p(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //paragraph of content
function pre(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //preformatted text
function blockquote(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //long quotation
function figure(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //self-contained content, like illustrations, diagrams, photos, etc.
function figcaption(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //<figcaption> element is FIRST or LAST child of the <figure> element

//headings
function h1(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function h2(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function h3(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function h4(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function h5(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function h6(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }

//breaks
function br(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - single line break
function hr(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - horizontal rule - defines a thematic break

//inlines
function data(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //machine-readable translation of a given content
function span(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //part of content
function code(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //text as computer code
function samp(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //sample output from a computer program
function strong(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //important text (bold)
function small(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //smaller text
function mark(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //marked/highlighted text
function cite(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //title of a work (italic)
function dfn(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //definition term
function del(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //deleted part
function ins(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //inserted part
function sub(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //subscript
function sup(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //superscript
function em(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //emphasized (italic)
function s(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //strikethrough (incorrect text)
function q(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //short quotation
function i(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //alternate text, technical term, a phrase from another language (italic)
function b(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //bold text without any extra importance (bold)
function a(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //anchor
function href(?string $href = null, mixed ...$data): object //a hyperlink
{
	return Page::tag('a', ['href' => Request::GetFileName($href)], ...$data);
}
function click(?string $onclick = null, mixed ...$data): object //a onclick
{
	return Page::tag('a', ['href' => 'javascript:;', 'onclick' => $onclick], ...$data); //run only onclick javascript
}

//container
function template(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //container to hold hidden content when the page loads. can be rendered later with a JavaScript
function canvas(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //container for a script graphics
function svg(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //container for Scalable Vector Graphics

//list
function menu(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //unordered list (same as ul)
function ul(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //unordered list (same as menu)
function ol(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //ordered list
function li(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //list item
function dl(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //description list
function dt(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //term
function dd(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //description

//table
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

//form
function form(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function fieldset(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function legend(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function label(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function button(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function select(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function datalist(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //pre-defined options for an <input> element
function option(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //unisex (can be paired or singular (in datalist))
function optgroup(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function textarea(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); }
function input(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular
function radio(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular

function meter(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //guage
function progress(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //completion progress of a task

//audio/video
function img(string|array $src = [], string|array $alt = [], mixed ...$data): object //singular - image
{
	if(is_string($src)) $src = array('src' => $src);
	if(is_string($alt)) $alt = array('alt' => $alt);
	return Page::tag(__FUNCTION__, $src, $alt, ...$data);
}
function map(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //img usemap="#name"
function area(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - defines an area inside an image map
function picture(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //image resources - contains one or more <source> tags and one <img> tag
function audio(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //embed sound content such as music or other audio streams
function video(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //embed video content such as a movie clip or other video streams
function source(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - specify multiple media resources for media elements
function track(mixed ...$data): object { return Page::tag(__FUNCTION__, ...$data); } //singular - specifies text tracks for <audio> or <video> elements
