<?php

declare(strict_types = 1);

enum Method: string
{
    case Get = 'get'; //Default. Appends the form-data to the URL in name/value pairs: URL?name=value&name=value
    case Post = 'post'; //Sends the form-data as an HTTP post transaction
    case Dialog = 'dialog'; //When the form is inside a <dialog> element, it closes the dialog and causes a submit event to be executed on submission, without submitting data or clearing the form
}

class Form
{
	use Patterns; //pattern date missing !!!

	private array $form_attrib = [
		'method' => null, //The default HTTP method when submitting form data is GET (NEVER use GET to send sensitive data!)
		'action' => null, //If the action attribute is omitted, the action is set to the current page.
		'target' => null, //The default Target is _self which means that the response will open in the current window.
		'autocomplete' => null, //browser automatically complete values based on values that the user has entered before
		'onsubmit' => null,
	];

	private int $fieldset = 0; //current
	private array $fieldsets = []; //list of queues

	public function __construct(Method $method = Method::Get, ?string $action = null, ?string $target = null, ?string $class = null, ?string $id = null)
	{
		$method = $method->value; //convert object to string
		$this->form_attrib = array_merge($this->form_attrib, compact('action', 'method', 'target', 'class', 'id'));
		if(strcasecmp($this->form_attrib['method'], 'post')) $this->form_attrib['enctype'] = 'multipart/form-data'; //this value is necessary if the user will upload a file through the form
		$this->fieldset();
	}

	public function onsubmit(?string $onsubmit = 'return checkForm(this);'): void
	{
		$this->form_attrib['onsubmit'] = $onsubmit;
	}

	public function autocomplete(null|bool|string $autocomplete = null): void
	{
		if($autocomplete === false) $this->form_attrib['autocomplete'] = 'off'; //Autocomplete is disabled
		elseif($autocomplete === true) $this->form_attrib['autocomplete'] = 'on'; //Default (enabled)
		else $this->form_attrib['autocomplete'] = $autocomplete; //custom value
	}

	public function novalidate(?bool $novalidate = null): void //specifies that the form-data (input) should not be validated when submitted
	{
		if($novalidate) array_push($this->form_attrib, 'novalidate');
		elseif(($key = array_search('novalidate', $this->form_attrib)) !== false) unset($this->form_attrib[$key]);
	}

	public function fieldset(int $index = 0, int|array $rem_or_attrib = 0): object
	{
		if(is_int($rem_or_attrib)) unset($this->fieldsets[$rem_or_attrib]); //remove fieldset
		if(!isset($this->fieldsets[$index])) $this->fieldsets[$index] = new Queue();
		if($index > 0) $this->fieldsets[$index]->first('fieldset', true, is_array($rem_or_attrib) ? $rem_or_attrib : []); //add or replace fieldset with attributes
		$this->fieldset = $index; //set pointer
		return $this;
	}

	//input

	public function add(string $name, mixed ...$data): object
	{
		$this->fieldsets[$this->fieldset]->add($name, ...$data);
		return $this;
	}

	public function legend(string $caption): object
	{
		return $this->add('legend', $caption);
	}

	public function label(string $caption, ?string $for = null, ?string $tooltip = null, bool $colon = true): object
	{ //label can also be bound to an element by placing the element inside the <label> element, then no need bind to inout id
		if($colon) $caption .= match(mb_substr($caption, -1)) {'.','!','?',':',';','>' => '', default => ':',}; //add colon
		return $this->add('label', ['for'=>$for, 'title' => $tooltip], $caption);
	}

	public function submit(string $caption = 'Submit', string $name = 'submit'): object
	{
		return $this->add('input', ['type'=>'submit', 'name'=>$name, 'id'=>$name, 'value'=>$caption]);
	}

	public function reset(string $caption = 'Reload', string $name = 'reload', string $location = ''): object //don't set name='reset' because JS function reset() will not work !!!
	{
		return $this->add('input', ['type'=>'reset', 'name'=>$name, 'id'=>$name, 'value'=>$caption, 'onclick'=>'window.location=\''.Request::GetFileName($location).'\';']);
	}

	public function clear(string $caption = 'Clear', string $name = 'clear'): object
	{
		//JS function reset() does not work if form contains any field with attribute name='reset' !!!
		return $this->add('input', ['type'=>'button', 'name'=>$name, 'id'=>$name, 'value'=>$caption, 'onclick'=>"this.closest('form').reset();"]); //'this.parentNode.parentNode.reset();'
	}

	public function button(string $caption = 'Press', string $name = 'press', ?string $onclick = null): object
	{
		return $this->add('input', ['type'=>'button', 'name'=>$name, 'id'=>$name, 'value'=>$caption, 'onclick'=>$onclick]); //"navigator.clipboard.writeText('".$value."');" etc.
	}

	public function file(string $name = 'file', string $accept = 'text/xml'): object
	{
		return $this->add('input', ['type'=>'file', 'name'=>$name, 'id'=>$name, 'accept'=>$accept]);
	}

	public function hidden(string $name = 'file', ?string $value = null): object
	{
		return $this->add('input', ['type'=>'hidden', 'name'=>$name, 'id'=>$name, 'value' => $value]);
	}

	public function checkbox(string $name, ?string $value = null, bool $readonly = false, array $attrib = []): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		return $this->add('input', ['type' => 'checkbox', 'name' => $name, 'id' => $name, 'value' => 1, 'readonly' => $readonly, 'checked' => Any::ToBoolOnly($value)], $attrib); //without value sends $name="on"
	}

	public function email(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add('input', ['type' => 'email', 'name' => $name, 'id' => $name, 'value' => $value, 'pattern' => self::pattern_email, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib);
	}

	public function password(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add('input', ['type' => 'password', 'name' => $name, 'id' => $name, 'value' => $value, 'pattern' => self::pattern_password, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib);
	}

	public function area(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval(Str::Sanitize($value)); //strval convert null to empty string (need for paired tag)
		return $this->add('textarea', $value, ['name' => $name, 'id' => $name, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib);
	}

	public function combobox(string $name, array $values, ?string $value = null, bool $readonly = false, bool $required = false, array $attrib = []): object //select/option
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval($value); //strval convert null to empty string (for strcmp)
		$this->add('select', true, ['name' => $name, 'id' => $name, 'required' => $required, 'readonly' => $readonly], $attrib); //open
		foreach($values as $key => $val)
		{
			if(is_null($val)) continue; //value can be empty, but null is discarded
			$key = strval($key); //strval convert null to empty string (for strcmp)
			$val = strval(Str::Sanitize($val)); //strval convert null to empty string (need for paired tag)
			$this->add('option', $val, ['value' => $key], (strcmp($key, $value) == 0) ? ['selected'] : ($readonly || ($required && Str::IsEmpty($key)) ? ['disabled'] : []));
		}
		return $this->add('select', false); //close
	}

	public function radio(string $name, array $values, ?string $value = null, bool $readonly = false, bool $required = false, array $attrib = []): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval($value); //strval convert null to empty string (for strcmp)
		foreach($values as $key => $val)
		{
			if(is_null($val)) continue; //value can be empty, but null is discarded
			$key = strval($key); //strval convert null to empty string (for strcmp)
			$val = strval(Str::Sanitize($val)); //strval convert null to empty string (need for paired tag)
			$this->add('input', ['type' => 'radio', 'name' => $name, 'id' => $key, 'value' => $val], (strcmp($val, $value) == 0) ? ['checked'] : ($readonly || ($required && Str::IsEmpty($key)) ? ['disabled'] : []));
		}
		return $this;
	}

	public function text(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object //e.g. maxlength="64"
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib); //number has no pattern !
	}

	public function number(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		if(!Num::IsInteger($value)) $value = null;
		else $value = Str::Sanitize($value);
		return $this->add('input', ['type' => 'number', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib); //number has no pattern !
	}

	public function color(string $name, ?string $value = null, bool $readonly = false, bool $required = false, array $attrib = []): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add('input', ['type' => 'color', 'name' => $name, 'id' => $name, 'value' => $value, 'required' => $required, 'readonly' => $readonly], $attrib);
	}

	public function date(string $name, ?string $value = null, bool $readonly = false, bool $required = false, array $attrib = []): object
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		return $this->add('input', ['type' => 'date', 'name' => $name, 'id' => $name, 'value' => $value, 'required' => $required, 'readonly' => $readonly], $attrib);
	}

	//input extended

	public function integer(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object
	{
		$attrib = array_merge(['inputmode' => 'numeric', 'step' => 1], $attrib);
		return $this->Number($name, $value, $placeholder, $readonly, $required, $attrib);
	}

	public function floatP(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object //float positive 12345.67 or 0.005 (not 0 or 0.0)
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$attrib_float = ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'pattern' => self::pattern_price]; //numeric keyboard and comma to point replace 
		return $this->add('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib_float, $attrib);
	}

	public function floatPZ(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object //float positive-zero 12345.67 (0 or 0.0 too)
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$attrib_float = ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'pattern' => self::pattern_num]; //numeric keyboard and comma to point replace 
		return $this->add('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib_float, $attrib);
	}

	public function float(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object //float 12345.67 (zero/plus/minus too)
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$attrib_float = ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'pattern' => self::pattern_float]; //numeric keyboard and comma to point replace 
		return $this->add('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib_float, $attrib);
	}

	public function year(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object
	{
		$attrib = array_merge(['min' => 1, 'max' => 9999], $attrib);
		return $this->Integer($name, $value, $placeholder, $readonly, $required, $attrib);
	}

	public function month(string $name, ?string $value = null, bool $readonly = false, bool $required = false, array $attrib = []): object //required: true = disallow whole year
	{
		return $this->ComboBox($name, Language::Months(!$required, with_numbers: true), $value, $readonly, $required, $attrib);
	}

	public function day(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): object //required: false = allow whole month
	{
		$attrib = array_merge(['min' => 1, 'max' => 31], $attrib);
		return $this->Integer($name, $value, $placeholder, $readonly, $required, $attrib);
	}

	//output

	//$what:
	//null = all
	//bool = open/close only
	//int = fieldset[index] only
	public function echo(null|bool|int $what = null): void
	{
		if(is_null($what) || $what === true) Page::Add('form', true, $this->form_attrib); //open form
		if(is_null($what)) //all fieldsets
		{
			foreach($this->fieldsets as $index => $fieldset) 
			{
				Page::Add($fieldset);
				if($index > 0) Page::Add('fieldset', false); //close fieldset (zero is not fieldset)
			}
		}
		elseif(is_int($what)) //only selected fieldset
		{
			if(isset($this->fieldsets[$what]))
			{
				Page::Add($this->fieldsets[$what]);
				if($what > 0) Page::Add('fieldset', false); //close fieldset (zero is not fieldset)
			}
		}
		if(is_null($what) || $what === false) //close
		{
			Page::Add('input', ['type'=>'hidden', 'name'=>'token', 'id'=>'token', 'value' => Request::GetToken()]); //token
			Page::Add('form', false); //close form
		}
	}
}
