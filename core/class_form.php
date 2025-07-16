<?php

declare(strict_types = 1);

enum Form_Method: string //submitting form data method (NEVER use GET to send sensitive data!)
{
    case Get = 'get'; //Default. Appends the form-data to the URL in name/value pairs: URL?name=value&name=value
    case Post = 'post'; //Sends the form-data as an HTTP post transaction
    case Dialog = 'dialog'; //When the form is inside a <dialog> element, it closes the dialog and causes a submit event to be executed on submission, without submitting data or clearing the form
}

class Form extends Element
{
	use Patterns;

	public function __construct(Form_Method $method = Form_Method::Post, mixed ...$attrib) //NEVER use Form_Method::Get to send sensitive data!
	{
		parent::__construct(strtolower(__CLASS__), false, $attrib);
		$this->attrib['method'] = $method->value; //convert object to string
		if(strcasecmp($this->attrib['method'], 'post') == 0) $this->attrib['enctype'] = 'multipart/form-data'; //this value is necessary if the user will upload a file through the form
		$this->hidden('token', Request::GetToken()); //token
	}

//attributes

	public function action(?string $action = null): void //If the action attribute is omitted, the action is set to the current page.
	{
		$this->attrib['action'] = $action;
	}

	public function target(?string $target = '_self'): void //The default Target is _self which means that the response will open in the current window.
	{
		$this->attrib['target'] = $target;
	}

	public function onsubmit(?string $onsubmit = 'return checkForm(this);'): void
	{
		$this->attrib['onsubmit'] = $onsubmit;
	}

	public function autocomplete(null|bool|string $autocomplete = null): void //browser automatically complete values based on values that the user has entered before
	{
		if($autocomplete === false) $this->attrib['autocomplete'] = 'off'; //Autocomplete is disabled
		elseif($autocomplete === true) $this->attrib['autocomplete'] = 'on'; //Default (enabled)
		else $this->attrib['autocomplete'] = $autocomplete; //custom value
	}

	public function novalidate(?bool $novalidate = null): void //specifies that the form-data (input) should not be validated when submitted
	{
		if($novalidate) array_push($this->attrib, 'novalidate');
		elseif(($key = array_search('novalidate', $this->attrib)) !== false) unset($this->attrib[$key]);
	}

//html tags

	public function fieldset(bool $open, mixed ...$attrib): object
	{
		return $this->add(new Element(__FUNCTION__, $open, $attrib));
	}

	public function legend(string $caption, mixed ...$attrib): object
	{
		return $this->add(new Element(__FUNCTION__, $caption, $attrib));
	}

	public function label(string $caption, ?string $for = null, ?string $tooltip = null, bool $colon = true, mixed ...$attrib): object
	{ //label can also be bound to an element by placing the element inside the <label> element, then no need bind to inout id
		if($colon) $caption .= match(mb_substr($caption, -1)) {'.','!','?',':',';','>' => '', default => ':',}; //add colon
		return $this->add(new Element(__FUNCTION__, $caption, compact('for', 'tooltip'), $attrib));
	}

	public function submit(string $caption = 'Submit', string $name = 'submit', mixed ...$attrib): object
	{
		return $this->add(new Element('input', ['type'=>'submit', 'name'=>$name, 'id'=>$name, 'value'=>$caption], $attrib));
	}

	public function reset(string $caption = 'Reset', string $name = 'clear', mixed ...$attrib): object
	{ //JS function reset() does not work if form contains any field with attribute name='reset' !!!
		return $this->add(new Element('input', ['type'=>'reset', 'name'=>$name, 'id'=>$name, 'value'=>$caption, 'onclick'=>"this.closest('form').reset();"], $attrib)); //'this.parentNode.parentNode.reset();'
	}

	public function reload(string $caption = 'Reload', string $name = 'reload', string $location = '', mixed ...$attrib): object
	{
		return $this->add(new Element('input', ['type'=>'button', 'name'=>$name, 'id'=>$name, 'value'=>$caption, 'onclick'=>'window.location=\''.Request::GetFileName($location).'\';'], $attrib));
	}

	public function button(string $caption = 'Press', string $name = 'press', ?string $onclick = null, mixed ...$attrib): object
	{
		return $this->add(new Element('input', ['type'=>'button', 'name'=>$name, 'id'=>$name, 'value'=>$caption, 'onclick'=>$onclick], $attrib)); //"navigator.clipboard.writeText('".$value."');" etc.
	}

	public function file(string $name = 'file', string $accept = 'text/xml', mixed ...$attrib): object
	{
		return $this->add(new Element('input', ['type'=>'file', 'name'=>$name, 'id'=>$name, 'accept'=>$accept], $attrib));
	}

	public function hidden(string $name = 'hidden', ?string $value = null, mixed ...$attrib): object
	{
		return $this->add(new Element('input', ['type'=>'hidden', 'name'=>$name, 'id'=>$name, 'value'=>$value], $attrib));
	}

	public function checkbox(string $name, ?string $value = null, mixed ...$attrib): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		return $this->add(new Element('input', ['type'=>'checkbox', 'name'=>$name, 'id'=>$name, 'value'=>1, 'checked' => Any::ToBoolOnly($value)], $attrib)); //without value sends $name="on"
	}

	public function email(string $name, ?string $value = null, mixed ...$attrib): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add(new Element('input', ['type'=>'email', 'name'=>$name, 'id'=>$name, 'value'=>$value, 'pattern' => self::pattern_email], $attrib));
	}

	public function password(string $name, ?string $value = null, mixed ...$attrib): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add(new Element('input', ['type'=>'password', 'name'=>$name, 'id'=>$name, 'value'=>$value, 'pattern' => self::pattern_password], $attrib));
	}

	public function textarea(string $name, ?string $value = null, mixed ...$attrib): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval(Str::Sanitize($value)); //strval convert null to empty string (need for paired tag)
		return $this->add(new Element(__FUNCTION__, $value, ['name'=>$name, 'id'=>$name], $attrib));
	}

	public function select(string $name, mixed ...$attrib): object //only open or close
	{
		return $this->add(new Element(__FUNCTION__, ['name'=>$name, 'id'=>$name], $attrib));
	}

	public function option(string $content, ?string $value = null, mixed ...$attrib): object //$name is name of select
	{
		return $this->add(new Element(__FUNCTION__, $content, ['value'=>$value], $attrib));
	}

	public function combo(string $name, array $values, ?string $value = null, mixed ...$attrib): object //select + options
	{//bool $readonly = false, bool $required = false
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval($value); //strval convert null to empty string (for strcmp)
		$this->add(new Element('select', true, ['name'=>$name, 'id'=>$name], $attrib)); //open
		foreach($values as $key => $val)
		{
			if(is_null($val)) continue; //value can be empty, but null is skipped
			$key = strval($key); //strval convert null to empty string (for strcmp)
			$val = strval(Str::Sanitize($val)); //strval convert null to empty string (need for paired tag)
			$this->add(new Element('option', $val, ['value'=>$key, strcmp($key, $value) == 0 ? 'selected' : null])); // (strcmp($key, $value) == 0) ? ['selected'] : ($readonly || ($required && Str::IsEmpty($key)) ? ['disabled'] : [])
		}
		return $this->add(new Element('select', false)); //close
	}

	public function radio(string $name, array $values, null|string|int|float $value = null, bool $readonly = false, bool $required = false, array $attrib = []): void
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval($value); //strval convert null to empty string (for strcmp)
		foreach($values as $key => $val)
		{
			if(is_null($val)) continue; //value can be empty, but null is skipped
			$key = strval($key); //strval convert null to empty string (for strcmp)
			$val = strval(Str::Sanitize($val)); //strval convert null to empty string (need for paired tag)
			$this->add(new Element('input', ['type' => 'radio', 'name' => $name, 'id' => $key, 'value' => $val], strcmp($val, $value) == 0 ? ['checked'] : ($readonly || ($required && Str::IsEmpty($key)) ? ['disabled'] : []), $attrib));
		}
	}

	public function range(string $name, ?string $value = null, mixed ...$attrib): object  //defaults: min=0, max=100, step=1
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		if(!Num::IsInteger($value)) $value = null;
		else $value = Str::Sanitize($value);
		return $this->add(new Element('input', ['type' => 'range', 'name' => $name, 'id' => $name, 'value' => $value], $attrib));
	}

	public function text(string $name, ?string $value = null, mixed ...$attrib): object //e.g. maxlength: 64
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add(new Element('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value], $attrib));
	}

	public function number(string $name, ?string $value = null, mixed ...$attrib): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		if(!Num::IsInteger($value)) $value = null;
		else $value = Str::Sanitize($value);
		return $this->add(new Element('input', ['type' => 'number', 'inputmode' => 'numeric', 'name' => $name, 'id' => $name, 'value' => $value], $attrib)); //number has no pattern !
	}

	public function color(string $name, ?string $value = null, mixed ...$attrib): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add(new Element('input', ['type' => 'color', 'name' => $name, 'id' => $name, 'value' => $value], $attrib));
	}

	public function date(string $name, ?string $value = null, mixed ...$attrib): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add(new Element('input', ['type' => 'date', 'name' => $name, 'id' => $name, 'value' => $value], $attrib));
	}

	//input extended

	public function integer(string $name, ?string $value = null, mixed ...$attrib): object
	{
		return $this->number($name, $value, ['step' => 1], $attrib);
	}

	public function floatP(string $name, ?string $value = null, mixed ...$attrib): object //float positive 12345.67 or 0.005 (not 0 or 0.0)
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->text($name, $value, ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'pattern' => self::pattern_price], $attrib); //numeric keyboard and comma to point replace
	}

	public function floatPZ(string $name, ?string $value = null, mixed ...$attrib): object //float positive-zero 12345.67 (0 or 0.0 too)
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->text($name, $value, ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'pattern' => self::pattern_num], $attrib); //numeric keyboard and comma to point replace
	}

	public function float(string $name, ?string $value = null, mixed ...$attrib): object //float 12345.67 (zero/plus/minus too)
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->text($name, $value, ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'pattern' => self::pattern_float], $attrib); //numeric keyboard and comma to point replace
	}

	public function year(string $name, ?string $value = null, mixed ...$attrib): object
	{
		return $this->integer($name, $value, ['min' => 1, 'max' => 9999], $attrib);
	}

	public function month(string $name, ?string $value = null, bool $whole_year = true, bool $with_numbers = true, mixed ...$attrib): object
	{
		$items = Language::Months($whole_year, $with_numbers);
		if($attrib['required'] ?? false) $items[''] = '('.Language::Get('select_option', 0, 'select option').')'; //required = rename whole_year to select_option
		return $this->combo($name, $items, $value, $attrib);
	}

	public function day(string $name, ?string $value = null, mixed ...$attrib): object //required: true = disallow whole month
	{
		return $this->integer($name, $value, ['min' => 1, 'max' => 31], $attrib);
	}
}
