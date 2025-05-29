<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

enum MsgBoxType: string
{
    case Nothing = '';
    case Info = ' info';
    case Success = ' success';
    case Alert = ' alert';
    case Warning = ' warning';
}

enum InputAreaSize: string
{
	use MaxLength;

    case Lite = 'lite';
    case Medium = 'medium';
    case Large = 'large';

    public function maxlength(): int
	{
        return match($this)
		{
			static::Lite => self::maxlen_lite,
			static::Medium => self::maxlen_area,
			static::Large => self::maxlen_area,
        };
    }
}

if(!trait_exists('PageTemplate', false)) { trait PageTemplate {} } //abstract trait (workaround for: function trait_exists() cannot be used inside a class)

trait MaxLength
{
	const maxlen_date = 10;
	const maxlen_line = 80;
	const maxlen_lite = 255; //area
	const maxlen_area = 8000; //area
}

trait Markers
{
	const mark_up = '&#9650;'; //&uarr;
	const mark_down = '&#9660;'; //&darr;

	const mark_ok = '&#10004;';
	const mark_no = '&#10008;';

	const mark_eye = '&#128065;';
}

trait HypertextCommon //only most used
{
	//Singular Tags (only for head)

	public static function Meta(array $attributes = []): string //metadata (information data) about an HTML document
	{
		return self::Tag('meta', null, $attributes);
	}

	public static function Link(array $attributes = []): string //defines the relationship between the current document and an external resource (style sheets or to add a favicon)
	{
		return self::Tag('link', null, $attributes);
	}

	public static function Base(string $href, string $target = '_blank'): string //specifies the base URL and/or target for all relative URLs
	{
		return self::Tag('base', null, ['href' => $href, 'target' => $target]);
	}

	//Singular Tags (only for body)

	public static function Br(?string $id = null, ?string $class = null): string //single line break
	{
		return self::Tag('br', null, ['id' => $id, 'class' => $class]);
	}

	public static function Hr(?string $id = null, ?string $class = null): string //horizontal rule - defines a thematic break
	{
		return self::Tag('hr', null, [ 'id' => $id, 'class' => $class]);
	}

	public static function Img(string $source, string $caption = '', null|string|int $width = null, null|string|int $height = null, ?string $id = null, ?string $class = null): string //Image
	{
		if(is_null($height) && !is_null($width)) $height = $width; //default ratio 1:1
		return self::Tag('img', null, ['src' => $source, 'alt' => $caption, 'width' => $width, 'height' => $height, 'id' => $id, 'class' => $class]);
	}

	//Paired Tags (main)

	public static function Html(bool|string $content = true, ?string $lang = null, ?string $id = null, ?string $class = null): string //root of an HTML document
	{
		return self::Tag('html', $content, ['lang' => $lang, 'id' => $id, 'class' => $class]);
	}

	public static function Head(bool|string $content = true, ?string $id = null, ?string $class = null): string //container for metadata
	{
		return self::Tag('head', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Body(bool|string $content = true, ?string $id = null, ?string $class = null): string //contains all the contents of an HTML document
	{
		return self::Tag('body', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Header(bool|string $content = true, ?string $id = null, ?string $class = null): string //represents a container for introductory content
	{
		return self::Tag('header', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Nav(bool|string $content = true, ?string $id = null, ?string $class = null): string //defines a major navigation links or menu
	{
		return self::Tag('nav', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Main(bool|string $content = true, ?string $id = null, ?string $class = null): string //main content of the document
	{
		return self::Tag('main', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Footer(bool|string $content = true, ?string $id = null, ?string $class = null): string //defines a footer for a document or section
	{
		return self::Tag('footer', $content, ['id' => $id, 'class' => $class]);
	}

	//Paired Tags

	public static function Section(bool|string $content = true, ?string $id = null, ?string $class = null): string //block document section
	{
		return self::Tag('section', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Article(bool|string $content = true, ?string $id = null, ?string $class = null): string //block self-contained article
	{
		return self::Tag('article', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Div(bool|string $content = true, ?string $id = null, ?string $class = null): string //block division container
	{
		return self::Tag('div', $content, ['id' => $id, 'class' => $class]);
	}

	public static function P(bool|string $content = true, ?string $id = null, ?string $class = null): string //block paragraph of content
	{
		return self::Tag('p', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Pre(bool|string $content = true, ?string $id = null, ?string $class = null): string //block preformatted text
	{
		return self::Tag('pre', $content, ['id' => $id, 'class' => $class]);
	}

	public static function BlockQuote(bool|string $content = true, ?string $id = null, ?string $class = null): string //block long quotation
	{
		return self::Tag('blockquote', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Figure(bool|string $content = true, ?string $caption = null, ?string $id = null, ?string $class = null): string //block self-contained content, like illustrations, diagrams, photos, etc.
	{
		$output = '';
		if($content === false) //close <figure> element
		{
			if(Str::NotEmpty($caption)) $output .= self::Tag('figcaption', $caption); //<figcaption> element is LAST child of the <figure> element
			$output .= self::Tag('figure', $content);
		}
		else //open <figure> element
		{
			$output .= self::Tag('figure', $content, ['id' => $id, 'class' => $class]);
			if(Str::NotEmpty($caption)) $output .= self::Tag('figcaption', $caption); //<figcaption> element is FIRST child of the <figure> element
		}
		return $output;
	}

	public static function Span(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline part of content
	{
		return self::Tag('span', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Code(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline text as computer code
	{
		return self::Tag('code', $content, ['id' => $id, 'class' => $class]);
	}

	public static function H(bool|string $content = true, int $index = 1, ?string $id = null, ?string $class = null): string //Heading
	{
		if($index < 1) $index = 1;
		if($index > 6) $index = 6;
		return self::Tag('h'.strval($index), $content, ['id' => $id, 'class' => $class]);
	}

	public static function A(bool|string $content = true, ?string $href = null, ?string $target = null, ?string $onclick = null, ?string $title = null, ?string $id = null, ?string $class = null): string //Anchor (hyperlink)
	{
		if(Str::IsEmpty($href) && Str::NotEmpty($onclick)) $href = 'javascript:;'; //run only onclick javascript
		$href = Request::GetFileName($href);
		return self::Tag('a', $content, ['href' => $href, 'title' => $title, 'target' => $target, 'onclick' => $onclick, 'id' => $id, 'class' => $class]);
	}

	public static function Label(bool|string $content = true, ?string $for = null, ?string $tooltip = null, bool $colon = true): string //label can also be bound to an element by placing the element inside the <label> element, then no need bind to inout id
	{
		if($colon) $content .= match(mb_substr($content, -1)) {'.','!','?',':',';','>' => '', default => ':',}; //add colon
		return self::Tag('label', $content, ['for' => $for, 'title' => $tooltip]);
	}

	//Paired Tags (lists)

	public static function Menu(bool|string $content = true, ?string $id = null, ?string $class = null): string //unordered list (same as UL)
	{
		return self::Tag('menu', $content, ['id' => $id, 'class' => $class]);
	}

	public static function UL(bool|string $content = true, ?string $id = null, ?string $class = null): string //unordered list (same as Menu)
	{
		return self::Tag('ul', $content, ['id' => $id, 'class' => $class]);
	}

	public static function OL(bool|string $content = true, ?string $id = null, ?string $class = null): string //ordered list
	{
		return self::Tag('ol', $content, ['id' => $id, 'class' => $class]);
	}

	public static function LI(bool|string $content = true, ?string $id = null, ?string $class = null): string //list item
	{
		return self::Tag('li', $content, ['id' => $id, 'class' => $class]);
	}

	public static function DL(bool|string $content = true, ?string $id = null, ?string $class = null): string //description list
	{
		return self::Tag('dl', $content, ['id' => $id, 'class' => $class]);
	}

	public static function DT(bool|string $content = true, ?string $id = null, ?string $class = null): string //term
	{
		return self::Tag('dt', $content, ['id' => $id, 'class' => $class]);
	}

	public static function DD(bool|string $content = true, ?string $id = null, ?string $class = null): string //description
	{
		return self::Tag('dd', $content, ['id' => $id, 'class' => $class]);
	}

	//Paired Tags (others)

	public static function Em(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline emphasized (italic)
	{
		return self::Tag('em', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Strong(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline strong (bold)
	{
		return self::Tag('strong', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Small(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline smaller text
	{
		return self::Tag('small', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Sub(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline subscript
	{
		return self::Tag('sub', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Sup(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline superscript
	{
		return self::Tag('sup', $content, ['id' => $id, 'class' => $class]);
	}

	public static function S(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline strikethrough (incorrect)
	{
		return self::Tag('s', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Q(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline short quotation
	{
		return self::Tag('q', $content, ['id' => $id, 'class' => $class]);
	}

	public static function Mark(bool|string $content = true, ?string $id = null, ?string $class = null): string //inline highlighted
	{
		return self::Tag('mark', $content, ['id' => $id, 'class' => $class]);
	}
}

trait HypertextInput //`name` and `id` are the same
{
	public static function InputReset(string $name = 'reset', ?string $value = 'Reset', string $additional = ''): string
	{
		return self::Tag('input', null, ['type' => 'reset', 'name' => $name, 'id' => $name, 'value' => $value], $additional);
	}

	public static function InputSubmit(string $name = 'sent', ?string $value = 'Submit', string $additional = ''): string
	{
		return self::Tag('input', null, ['type' => 'submit', 'name' => $name, 'id' => $name, 'value' => $value], $additional);
	}

	public static function InputButton(string $name = 'button', ?string $value = 'Press', ?string $onclick = null, string $additional = ''): string
	{
		if(Str::NotEmpty($onclick)) $additional .= ' onclick="'.$onclick.'"'; //"navigator.clipboard.writeText('".$value."');" etc.

		return self::Tag('input', null, ['type' => 'button', 'name' => $name, 'id' => $name, 'value' => $value], $additional);
	}

	public static function InputFile(string $name = 'file', string $accept = 'text/xml'): string
	{
		return self::Tag('input', null, ['type' => 'file', 'name' => $name, 'id' => $name, 'accept' => $accept]);
	}

	public static function InputHidden(string $name, ?string $value = null): string
	{
		$value = Str::Sanitize($value);
		return self::Tag('input', null, ['type' => 'hidden', 'name' => $name, 'id' => $name, 'value' => $value]); //or type="text" hidden="hidden"
	}

	private static function InputAdditional(bool $readonly = false, bool $required = false, string $placeholder = '', string $additional = ''): string
	{
		if(Str::NotEmpty($placeholder))  $additional .= ' placeholder="('.$placeholder.')"';
		if($readonly) $additional .= ' readonly="readonly"'; //' disabled="disabled"';
		if($required) $additional .= ' required="required"';
		return $additional;
	}

	public static function InputCheckBox(string $name, ?string $value = null, bool $readonly = false, string $additional = ''): string
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		if(Any::ToBoolOnly($value)) $additional .= ' checked="checked"'; //checked?
		$additional = self::InputAdditional($readonly, additional: $additional);
		return self::Tag('input', null, ['type' => 'checkbox', 'name' => $name, 'id' => $name, 'value' => 1], $additional); //without value sends $name="on"
	}

	public static function InputEmail(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false, string $additional = ''): string
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$additional = self::InputAdditional($readonly, $required, $placeholder, $additional);
		return self::Tag('input', null, ['type' => 'email', 'name' => $name, 'id' => $name, 'value' => $value, 'pattern' => self::pattern_email, 'maxlength' => self::maxlen_line], $additional);
	}

	public static function InputPassword(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = true, string $additional = ''): string
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$additional = self::InputAdditional($readonly, $required, $placeholder, $additional);
		return self::Tag('input', null, ['type' => 'password', 'name' => $name, 'id' => $name, 'value' => $value, 'pattern' => self::pattern_password, 'maxlength' => self::maxlen_line], $additional);
	}

	public static function InputArea(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false, string $additional = '', InputAreaSize $size = InputAreaSize::Lite): string
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval(Str::Sanitize($value)); //strval convert null to empty string (need for paired tag)
		$additional = self::InputAdditional($readonly, $required, $placeholder, $additional);
		return self::Tag('textarea', $value, ['name' => $name, 'id' => $name, 'class' => $size->value, 'maxlength' => $size->maxlength()], $additional);
	}

	public static function InputOption(string $name, array $values, ?string $value = '', bool $readonly = false, bool $required = false, string $additional = '', string $href = ''): string //combo box
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval($value); //convert null to empty string

		$output = '';
		if(Str::NotEmpty($value) && Str::NotEmpty($href)) $output .= self::Tag('div', true); //for href

		$additional = self::InputAdditional($readonly, $required, additional: $additional);
		$output .= self::Tag('select', true, ['name' => $name, 'id' => $name], $additional);
		foreach($values as $key => $val)
		{
			if(is_null($val)) continue; //value can be empty, but null is discarded
			
			$key = strval($key);
			$val = strval($val);

			$additional = '';
			if(strcmp($key, $value) == 0) $additional = ' selected="selected"'; //selected
			else if($readonly || ($required && Str::IsEmpty($key))) $additional = ' disabled="disabled"'; //disabled

			$output .= self::Tag('option', Str::Sanitize($val), ['value' => $key], $additional);
		}
		$output .= self::Tag('select', false);

		if(Str::NotEmpty($value) && Str::NotEmpty($href)) $output .= self::A(self::mark_eye, $href) . self::Tag('div', false); //href
		return $output;
	}

	public static function InputNumber(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false, string $additional = ''): string
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		if(!Num::IsInteger($value)) $value = null;
		$value = Str::Sanitize($value);
		$additional = self::InputAdditional($readonly, $required, $placeholder, $additional); //e.g. min="1" max="99" step="1"
		return self::Tag('input', null, ['type' => 'number', 'name' => $name, 'id' => $name, 'value' => $value], $additional); //number has no pattern !
	}

	public static function InputText(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false, string $additional = ''): string
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$additional = self::InputAdditional($readonly, $required, $placeholder, $additional);
		if(!str_contains(strtolower($additional), 'maxlength')) $additional .= ' maxlength="'.self::maxlen_line.'"'; //overwrite maxlength
		return self::Tag('input', null, ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value], $additional);
	}

	//INPUT extended

	public static function InputFloatPositive(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false): string //12345.67 or 0.005 (not 0 or 0.0)
	{
		if($required && Str::IsEmpty($placeholder)) $placeholder = 'nezadané číslo nie je povolené';
		$additional = 'inputmode="numeric" maxlength="'.self::maxlen_line.'" pattern="'.self::pattern_price.'" onkeyup="COMMAtoPOINT(this);"';
		if(App::Env('APP_FLOAT_RIGHT')) $additional .= ' class="align-right"';
		return self::InputText($name, $value, $placeholder, $readonly, $required, $additional);
	}

	public static function InputFloatPositiveZero(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false): string //12345.67 (0 or 0.0 too)
	{
		if($required && Str::IsEmpty($placeholder)) $placeholder = 'nezadané číslo nie je povolené';
		$additional = 'inputmode="numeric" maxlength="'.self::maxlen_line.'" pattern="'.self::pattern_num.'" onkeyup="COMMAtoPOINT(this);"';
		if(App::Env('APP_FLOAT_RIGHT')) $additional .= ' class="align-right"';
		return self::InputText($name, $value, $placeholder, $readonly, $required, $additional);
	}

	public static function InputFloat(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false): string //12345.67 (zero plus minus too)
	{
		if($required && Str::IsEmpty($placeholder)) $placeholder = 'nezadané číslo nie je povolené';
		$additional = 'inputmode="numeric" maxlength="'.self::maxlen_line.'" pattern="'.self::pattern_float.'" onkeyup="COMMAtoPOINT(this);"';
		if(App::Env('APP_FLOAT_RIGHT')) $additional .= ' class="align-right"';
		return self::InputText($name, $value, $placeholder, $readonly, $required, $additional);
	}

	public static function InputDate(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false): string
	{
		$additional = 'maxlength="'.self::maxlen_date.'" pattern="'.self::pattern_date_eu.'" onkeyup="COMMAtoPOINT(this);"';
		return self::InputText($name, DT::Conv($value), $placeholder, $readonly, $required, $additional);
	}

	public static function InputYear(string $name, ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false, string $additional = ''): string
	{
		if(Str::IsEmpty($additional)) $additional = 'min="1900" max="2800" step="1"';
		if($required && Str::IsEmpty($placeholder)) $placeholder = 'nezadaný rok nie je povolený';
		return self::InputNumber($name, $value, $placeholder, $readonly, $required, $additional);
	}

	public static function InputMonth(string $name, ?string $value = '', bool $readonly = false, bool $required = false, string $additional = ''): string //required: true = disallow whole year
	{
		return self::InputOption($name, Lang::Months(!$required, with_numbers: true), $value, $readonly, $required, $additional);
	}

	public static function InputDay(string $name = 'den', ?string $value = null, string $placeholder = '', bool $readonly = false, bool $required = false, string $additional = ''): string //required: false = allow whole month
	{
		if($value == '0') $value = null;
		if(Str::IsEmpty($additional)) $additional = 'min="1" max="31" step="1"';
		if(Str::IsEmpty($placeholder)) $placeholder = $required ? 'celý mesiac nie je povolený' : 'nezadaný deň = celý mesiac';
		return self::InputNumber($name, $value, $placeholder, $readonly, $required, $additional);
	}
}

trait HypertextForm
{
	public static function FormBegin(string $legend = '', string $action = '', string $method = 'post', string $onsubmit = '', string $additional = ''): string
	{
		if(Str::NotEmpty($onsubmit)) $additional .= ' onsubmit="'.$onsubmit.'"'; //$onsubmit = 'return checkForm(this);' etc.

		$output = self::Tag('form', true, ['action' => Request::GetFileName($action), 'method' => $method], $additional);
		if(Str::NotEmpty($legend)) $output .= self::FormFieldset($legend);
		return $output;
	}

	public static function FormFieldset(string $legend = ''): string
	{
		$output = self::Tag('fieldset', true);
		if(Str::NotEmpty($legend)) $output .= self::Tag('legend', $legend);
		return $output;
	}

	public static function FormEnd(?string $submit = 'Submit', ?string $reset = null, ?string $reset_location = null, bool $token = true): string
	{
		$additional = '';
		if(Str::NotEmpty($reset_location)) $additional = 'onclick="window.location=\''.Request::GetFileName($reset_location).'\';"';

		$output = '';
		if(Str::NotEmpty($submit)) $output .= self::InputSubmit(value: $submit);
		if(Str::NotEmpty($reset)) $output .= self::InputReset(value: $reset, additional: $additional);
		if($token) $output .= self::InputHidden('token', Request::GetToken());
		$output .= self::Tag('form', false);
		return $output;
	}
}

trait HypertextCustom
{
	public static function Button(string $caption, ?string $href = null, ?string $target = null, ?string $onclick = null, bool $lite = false): void
	{
		echo self::A($caption, $href, $target, $onclick, class: $lite?'button lite':'button');
	}

	public static function CheckMark(?bool $check, string $yes = '', string $no = ''): string // green check / red cross
	{
		if(Str::NotEmpty($yes)) $yes = '&nbsp;'.$yes;
		if(Str::NotEmpty($no)) $no = '&nbsp;'.$no;
		return $check ? HTML::Span(self::mark_ok, class: 'green-text').$yes : HTML::Span(self::mark_no, class: 'red-text').$no;
	}

	public static function MsgBox(string $text, MsgBoxType $type = MsgBoxType::Nothing): string
	{
		return HTML::Div(HTML::InputCheckBox('close').$text, class: 'msgbox'.$type->value);
	}
}

class HTML extends Singleton
{
	/** @var array<int, string> $open_tags */
	private static array $open_tags = [];

	use Patterns;
	use MaxLength;
	use Markers;

	use HypertextCommon;
	use HypertextInput;
	use HypertextForm;
	use HypertextCustom;

	use PageTemplate; //implements user defined page customization

	protected function __construct(?string $title = null)
	{
		if(Str::NotEmpty($title)) Page::Title($title);

		//declaration
		echo '<!DOCTYPE html>'.PHP_EOL; //HTML 5
		echo '<!-- Created with '.App::Env('3S_NAME').' -->'.PHP_EOL;
		if(App::Env('APP_DEBUG')) echo '<!-- Debug mode is enabled. -->'.PHP_EOL;

		//html
		echo self::html(lang: App::Env('APP_LANGUAGE')); //language declaration meant to assist search engines and browsers

		//head
		echo self::head(true);
		echo self::Tag('title', Page::Title()); //required only once in every HTML document (must be text-only)
		echo self::meta(['charset' => strtolower(App::Env('APP_ENCODING'))]);
		echo self::meta(['name' => 'title', 'content' => Page::Title()]);
		if(is_callable([__CLASS__, 'Metadata'])) self::Metadata(true); //may contain links
		foreach(Page::Links() as $link) echo HTML::Link($link); //add links
		echo self::head(false);

		//body
		echo self::Body(true);
		if(is_callable([__CLASS__, 'Begin'])) self::Begin();
	}

	//$content:
	//null - singular tag - void element - <$name />
	//true - paired tag - only open element - <$name>
	//false - paired tag - only close element - </$name> - attributes are ignored
	//'string' - complete paired tag - <$name>$content</$name>
	public static function Tag(array|string $name, null|bool|string $content = null, array $attributes = [], string $additional = ''): string
	{
		if(is_array($name)) //more tags at once
		{
			$output = '';
			foreach($name as $chunk) $output .= self::Tag($chunk, $content, $attributes, $additional); //recursive parse tags
			return $output;
		}

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
				if(App::Env('APP_DEBUG')) $output .= PHP_EOL . str_repeat("\t", count(self::$open_tags)); //pretty print for debug HTML code

				$output .= '<'.$name;
				foreach($attributes as $key => $val) if(!is_null($val)) $output .= ' '.$key.'="'.$val.'"'; //value can be empty, but null is discarded
				if(Str::NotEmpty($additional)) $output .= ' '.trim($additional);

				if(is_null($content)) $output .= ' /'; //Syntactic sugar for self-closing void element. In html 5 is optional, but recommended.
				$output .= '>';

				if(is_string($content)) $output .= $content . '</'.$name.'>'; //complete paired tag
				else if($content === true) array_push(self::$open_tags, $name); //save open tag
			}
		}

		return $output;
	}

	public static function Menu(array $menu, string $selected = 'selected'): string
	{
		$output = self::Tag('ul', true);
		foreach($menu as $item)
		{
			if(is_string($item)) //only label
			{
				$output .= self::Tag('li', self::A($item));
			}
			else if(is_array($item) && !empty($item))
			{
				if(count($item) > 1) //label & (link or submenu)
				{
					$output .= self::Tag('li', true);
					if(is_string($item[1])) //only item
					{
						$output .= self::A($item[0], $item[1], class: (Str::NotEmpty($selected) && Request::IsFileName($item[1])) ? $selected : '');
					}
					else if(is_array($item[1])) //has submenu
					{
						$output .= self::A($item[0]); //label only
						$output .= self::Menu($item[1]); //recursive parse submenu
					}
					else throw new \Exception('Unsupported Menu Item');
					$output .= self::Tag('li', false);
				}
				else $output .= self::Tag('li', self::A($item[0])); //only label
			}
			else throw new \Exception('Unsupported Menu Item');
		}
		$output .= self::Tag('ul', false);
		return $output;
	}

	public function __destruct()
	{
		if(is_callable([__CLASS__, 'Finish'])) self::Finish();
		echo self::Tag('', false); //close all open tags (...,body,html)
	}
}

class Table
{
	private ?string $caption = null;
	private ?string $id = null;
	private ?string $class = null;

	private ?string $sort = null;

	private int $columns = 0;

	private array $colgroup = []; //[[span, class], [span, class]]
	private array $head = []; //[[r0c0, r0c1], [r1c0, r1c1]]
	private array $body = []; //[[r0c0, r0c1], [r1c0, r1c1], [r2c0, r2c1]]
	private array $foot = []; //[[r0c0, r0c1]]

	private function Row(array $values): string
	{
		$output = HTML::Tag('tr', true);
		if($this->columns)
		{
			for ($i = 0; $i < $this->columns; $i++) $output .= HTML::Tag('td', $values[$i] ?? '');
		}
		else //columns counter not set
		{
			foreach($values as $value) $output .= HTML::Tag('td', $value);
		}
		$output .= HTML::Tag('tr', false);
		return $output;
	}

	private function RowHead(array $values): string
	{

		$output = HTML::Tag('tr', true);
		if($this->columns)
		{
			for ($i = 0; $i < $this->columns; $i++) $output .= $this->HeadTh($values[$i] ?? '');
		}
		else //columns counter not set
		{
			foreach($values as $value) $output .= HTML::Tag($value);
		}
		$output .= HTML::Tag('tr', false);
		return $output;
	}

	private function HeadTh(string|array $value): string
	{
		$label = '';
		$column = '';
		$additional = '';

		if(is_array($value)) //sortable caption
		{
			if(array_is_list($value))
			{
				switch(count($value))
				{
					case 4: //label, column, title, additional
						$additional = rtrim(' '.$value[3]);
					case 3: //label, column, title
						$additional = ' title="'.$value[2].'"'; //title
					case 2: //label, column
						$column = $value[1];
					case 1: //only label (column = label)
						$label = $value[0];
						if(Str::IsEmpty($column)) $column = $label;
				}
			}
			else
			{
				$additional = rtrim(' '.$value['additional'] ?? $value['a'] ?? ''); //additional
				if(isset($value['title'])) $additional = ' title="'.$value['title'].'"'; //title
				else if(isset($value['t'])) $additional = ' title="'.$value['t'].'"'; //title
				$column = $value['column'] ?? $value['c'] ?? ''; //column
				$label = $value['label'] ?? $value['l'] ?? $value[0] ?? ''; //label
			}
		}
		else $label = $value; //only string

		//output
		$output = '<th'.$additional.'>';
		if(Str::NotEmpty($column))
		{
			if(Str::NotEmpty($this->sort))
			{
				if(strcasecmp($this->sort, $column) == 0) //active sort on this column?
				{
					if(Str::IsCapitalLetter($this->sort))
					{
						$output .= HTML::mark_down.' ';
						$column = lcfirst($column);
					}
					else
					{
						$output .= HTML::mark_up.' ';
						$column = ucfirst($column);
					}
				}
			}
			$output .= HTML::A($label, Request::Modify(['sort' => $column]));
		}
		else $output .= $label;
		$output .= '</th>';
		return $output;
	}

//public
	public function __construct(?string $caption = null, ?string $id = null, ?string $class = null)
	{
		$this->caption = $caption;
		$this->id = $id;
		$this->class = $class;
	}

	public function Caption(?string $caption = null): void
	{
		$this->caption = $caption;
	}

	public function ColGroup(?int $span = null, ?string $class = null): void
	{
		array_push($this->colgroup, ['span' => $span, 'class' => $class]);
	}

	public function Head(array $values = []): void //add head row
	{
		$this->columns = max($this->columns, count($values)); //set columns counter
		array_push($this->head, $values);
	}

	public function Body(array $values = []): void //add body row
	{
		array_push($this->body, $values);
	}

	public function Foot(array $values = []): void //add foot row
	{
		array_push($this->foot, $values);
	}

	public function Clear(): void
	{
		$this->columns = 0;
		$this->colgroup = [];
		$this->head = [];
		$this->body = [];
		$this->foot = [];
	}

	public function Data(array $values = []): void
	{
		$this->Clear();
		if(is_array($values[0] ?? null))
		{
			if(!array_is_list($values[0])) $this->Head(array_keys($values[0])); //set keys as header cells
			else $this->columns = count($values[0]); //set only column count
			$this->body = $values; //replace body content
		}
	}

	public function echo(): void
	{
		//table elements must be used in the following context:
		//<table><caption> <colgroup><col> <thead><tr><th> <tbody><tr><td> <tfoot><tr><td>
		echo HTML::Tag('table', true, ['id' => $this->id, 'class' => $this->class]);
		//caption
		if(Str::NotEmpty($this->caption)) echo HTML::Tag('caption', $this->caption);
		//colgroup - specifies a group of one or more columns in a table for formatting
		if(count($this->colgroup))
		{
			echo HTML::Tag('colgroup', true);
			foreach($this->colgroup as $col) echo HTML::Tag('col', null, $col);
			echo HTML::Tag('colgroup', false);
		}
		//head
		if(count($this->head))
		{
			echo HTML::Tag('thead', true);
			foreach($this->head as $row) echo $this->RowHead($row);
			echo HTML::Tag('thead', false);
		}
		//body
		if(count($this->body))
		{
			echo HTML::Tag('tbody', true);
			foreach($this->body as $row) echo $this->Row($row);
			echo HTML::Tag('tbody', false);
		}
		//foot
		if(count($this->foot))
		{
			echo HTML::Tag('tfoot', true);
			foreach($this->foot as $row) echo $this->Row($row);
			echo HTML::Tag('tfoot', false);
		}
		//close
		echo HTML::Tag('table', false);
	}
}

class Page extends Singleton
{
	private static float $starttime = 0; //loading page start time
	private static string $title = '';
	private static array $links = [];

	use PageTemplate; //implements user defined page customization

	protected function __construct()
	{
		self::$starttime = microtime(true);

		header('Content-Type: text/html; charset='.strtolower(App::Env('APP_ENCODING')));
		if(is_callable([__CLASS__, 'Headers'])) self::Headers(); //replace or add custom headers
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
}

Page::Initialize();
