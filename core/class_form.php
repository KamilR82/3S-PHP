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
	use Patterns;

	private array $form_attrib = [
		'method' => null, //The default HTTP method when submitting form data is GET (NEVER use GET to send sensitive data!)
		'action' => null, //If the action attribute is omitted, the action is set to the current page.
		'target' => null, //The default Target is _self which means that the response will open in the current window.
		'autocomplete' => null, //browser automatically complete values based on values that the user has entered before
		'onsubmit' => null,
	];

	private array $fieldsets = array([]); //2D list
	private int $fieldset = 0; //current

	public function __construct(Method $method = Method::Get, ?string $action = null, ?string $target = null, ?string $class = null, ?string $id = null)
	{
		$method = $method->value; //convert object to string
		$this->form_attrib = array_merge($this->form_attrib, compact('action', 'method', 'target', 'class', 'id'));
		if(strcasecmp($this->form_attrib['method'], 'post')) $this->form_attrib['enctype'] = 'multipart/form-data'; //this value is necessary if the user will upload a file through the form
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

	public function fieldset(int $index = 0): void
	{
		if(!isset($this->fieldsets[$index])) $this->fieldsets[$index] = array();
		$this->fieldset = $index;
	}

	//input

	public function add(string ...$data): void
	{
		array_push($this->fieldsets[$this->fieldset], implode('', $data));
	}

	public function Legend(string $caption): void
	{
		$this->add(legend($caption));
	}

	public function Label(string $caption, ?string $for = null, ?string $tooltip = null, bool $colon = true): void
	{ //label can also be bound to an element by placing the element inside the <label> element, then no need bind to inout id
		if($colon) $caption .= match(mb_substr($caption, -1)) {'.','!','?',':',';','>' => '', default => ':',}; //add colon
		$this->add(label(['for'=>$for, 'title' => $tooltip], $caption));
	}

	public function Submit(string $caption = 'Submit', string $name = 'submit'): void
	{
		$this->add(input(['type'=>'submit', 'name'=>$name, 'id'=>$name, 'value'=>$caption]));
	}

	public function Reset(string $caption = 'Reload', string $name = 'reload', string $location = ''): void //don't set name='reset' because JS function reset() will not work !!!
	{
		$this->add(input(['type'=>'reset', 'name'=>$name, 'id'=>$name, 'value'=>$caption, 'onclick'=>'window.location=\''.Request::GetFileName($location).'\';']));
	}

	public function Clear(string $caption = 'Clear', string $name = 'clear'): void
	{
		//JS function reset() does not work if form contains any field with attribute name='reset' !!!
		$this->add(input(['type'=>'button', 'name'=>$name, 'id'=>$name, 'value'=>$caption, 'onclick'=>'this.parentNode.parentNode.reset();'])); //form.reset();
	}

	public function Button(string $caption = 'Press', string $name = 'press', ?string $onclick = null): void
	{
		$this->add(input(['type'=>'button', 'name'=>$name, 'id'=>$name, 'value'=>$caption, 'onclick'=>$onclick])); //"navigator.clipboard.writeText('".$value."');" etc.
	}

	public function File(string $name = 'file', string $accept = 'text/xml'): void
	{
		$this->add(input(['type'=>'file', 'name'=>$name, 'id'=>$name, 'accept'=>$accept]));
	}

	public function Hidden(string $name = 'file', ?string $value = null): void
	{
		$this->add(input(['type'=>'hidden', 'name'=>$name, 'id'=>$name, 'value' => $value]));
	}

	public function CheckBox(string $name, ?string $value = null, bool $readonly = false, array $attrib = []): void
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$this->add(HTML::Tag('input', ['type' => 'checkbox', 'name' => $name, 'id' => $name, 'value' => 1, 'readonly' => $readonly, 'checked' => Any::ToBoolOnly($value)], $attrib)); //without value sends $name="on"
	}

	public function Email(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$this->add(HTML::Tag('input', ['type' => 'email', 'name' => $name, 'id' => $name, 'value' => $value, 'pattern' => self::pattern_email, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib));
	}

	public function Password(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$this->add(HTML::Tag('input', ['type' => 'password', 'name' => $name, 'id' => $name, 'value' => $value, 'pattern' => self::pattern_password, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib));
	}

	public function Area(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval(Str::Sanitize($value)); //strval convert null to empty string (need for paired tag)
		$this->add(HTML::Tag('textarea', $value, ['name' => $name, 'id' => $name, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib));
	}

	public function ComboBox(string $name, array $values, ?string $value = null, bool $readonly = false, bool $required = false, array $attrib = []): void //select/option
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = strval($value); //strval convert null to empty string (for strcmp)
		$output = HTML::Tag('select', true, ['name' => $name, 'id' => $name, 'required' => $required, 'readonly' => $readonly], $attrib); //open
		foreach($values as $key => $val)
		{
			if(is_null($val)) continue; //value can be empty, but null is discarded
			$key = strval($key); //strval convert null to empty string (for strcmp)
			$val = strval(Str::Sanitize($val)); //strval convert null to empty string (need for paired tag)
			$output .= HTML::Tag('option', $val, ['value' => $key], (strcmp($key, $value) == 0) ? ['selected'] : ($readonly || ($required && Str::IsEmpty($key)) ? ['disabled'] : []));
		}
		$output .= HTML::Tag('select', false); //close
		$this->add($output);
	}

	public function Text(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void //e.g. maxlength="64"
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$this->add(HTML::Tag('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib)); //number has no pattern !
	}

	public function Number(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		if(!Num::IsInteger($value)) $value = null;
		else $value = Str::Sanitize($value);
		$this->add(HTML::Tag('input', ['type' => 'number', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib)); //number has no pattern !
	}

	//input extended

	public function Integer(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void
	{
		$attrib = array_merge(['inputmode' => 'numeric', 'step' => 1], $attrib);
		$this->Number($name, $value, $placeholder, $readonly, $required, $attrib);
	}

	public function FloatP(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void //float positive 12345.67 or 0.005 (not 0 or 0.0)
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$attrib_float = ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'pattern' => self::pattern_price]; //numeric keyboard and comma to point replace 
		$this->add(HTML::Tag('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib_float, $attrib));
	}

	public function FloatPZ(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void //float positive-zero 12345.67 (0 or 0.0 too)
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$attrib_float = ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'pattern' => self::pattern_num]; //numeric keyboard and comma to point replace 
		$this->add(HTML::Tag('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib_float, $attrib));
	}

	public function Float(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void //float 12345.67 (zero/plus/minus too)
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$attrib_float = ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'pattern' => self::pattern_float]; //numeric keyboard and comma to point replace 
		$this->add(HTML::Tag('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib_float, $attrib));
	}

	public function Date(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void
	{
		if(is_null($value)) $value = Request::GetParam($name); //try get value from params
		$value = Str::Sanitize($value);
		$attrib_date = ['inputmode' => 'numeric', 'onkeyup' => "this.value=this.value.replace(',', '.');", 'maxlength' => 10, 'pattern' => self::pattern_date];
		$this->add(HTML::Tag('input', ['type' => 'text', 'name' => $name, 'id' => $name, 'value' => $value, 'placeholder' => $placeholder, 'required' => $required, 'readonly' => $readonly], $attrib_date, $attrib));
	}

	public function Year(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void
	{
		$attrib = array_merge(['min' => 1, 'max' => 9999], $attrib);
		$this->Integer($name, $value, $placeholder, $readonly, $required, $attrib);
	}

	public function Month(string $name, ?string $value = null, bool $readonly = false, bool $required = false, array $attrib = []): void //required: true = disallow whole year
	{
		$this->ComboBox($name, Language::Months(!$required, with_numbers: true), $value, $readonly, $required, $attrib);
	}

	public function Day(string $name, ?string $value = null, ?string $placeholder = null, bool $readonly = false, bool $required = false, array $attrib = []): void //required: false = allow whole month
	{
		$attrib = array_merge(['min' => 1, 'max' => 31], $attrib);
		$this->Integer($name, $value, $placeholder, $readonly, $required, $attrib);
	}

	//output

	//$what:
	//null = all
	//bool = open/close only
	//int = fieldset[index] only
	public function echo(null|bool|int $what = null): void
	{
		if(is_null($what) || $what === true) echo HTML::Tag('form', true, $this->form_attrib); //open
		if(is_null($what)) //all fieldsets
		{
			foreach($this->fieldsets as $fieldset)
			{
				echo HTML::Tag('fieldset', true);
				foreach($fieldset as $item) echo $item;
				echo HTML::Tag('fieldset', false);
			}
		}
		elseif(is_int($what)) //only selected fieldset
		{
			if(isset($this->fieldsets[$what]))
			{
				echo HTML::Tag('fieldset', true);
				foreach($this->fieldsets[$what] as $item) echo $item;
				echo HTML::Tag('fieldset', false);
			}
		}
		if(is_null($what) || $what === false) //close
		{
			echo HTML::Tag('input', ['type'=>'hidden', 'name'=>'token', 'id'=>'token', 'value' => Request::GetToken()]);
			echo HTML::Tag('form', false);
		}
	}
}
