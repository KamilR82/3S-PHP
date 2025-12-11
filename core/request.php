<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

class Request extends Singleton //Uniform Resource Locator
{
	private static bool $secured = false; //https?
	private static string $method = '';
	private static string $server = '';
	private static string $filename = '';
	private static string $request = '';
	private static string $clientip = '';
	private static string $token = ''; //unique identifier for every page reload
	private static array $languages = []; //unique identifier for every page reload

	private static array $params = []; //only expected

	protected function __construct()
	{
		self::$secured = Any::ToBoolOnly($_SERVER['HTTPS'] ?? false) || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https');
		self::$method = $_SERVER['REQUEST_METHOD']; //GET / POST
		self::$server = $_SERVER['HTTP_HOST'] ?? App::Env('APP_SERVER');
		self::$filename = basename($_SERVER['SCRIPT_NAME']); //same as PHP_SELF
		self::$request = $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME']; //SCRIPT_NAME + QUERY_STRING
		if(substr(self::$request, -1) === '/') self::$request = self::$filename; //index.php is '' and then REQUEST_URI ending with '/'
		else self::$request = basename(self::$request); //remove path (works fine for '/?bla', but not for '/' !!!)
		self::$clientip = getenv('HTTP_CLIENT_IP') ?:
			getenv('HTTP_X_FORWARDED_FOR') ?:
			getenv('HTTP_X_FORWARDED') ?:
			getenv('HTTP_FORWARDED_FOR') ?:
			getenv('HTTP_FORWARDED') ?:
			getenv('REMOTE_ADDR', true) ?:
			getenv('REMOTE_ADDR');
		self::$languages = self::ParseQuality($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? []); //detects a users language based on the Accept-Language header coming from the browser
		if(!empty(self::$languages)) Language::Set(self::$languages); //try to set language

		//PROTECTION against DOUBLE POSTING FORM (back button, more tabs, refresh, ...)
		if(self::$method === 'POST' && isset($_POST['token']))
		{
			if(isset($_SESSION['token']))
			{
				if($_POST['token'] !== $_SESSION['token']) App::Die(ResponseCode::Bad_Request, Language::Get('form_valid'));
			}
			else App::Die(ResponseCode::Bad_Request, Language::Get('form_token'));
		}
		self::$token = $_SESSION['token'] = md5(uniqid(random_bytes(12), more_entropy: true)); //generate & save new token
	}
	
	private static function ParseQuality(?string $string = null): array
	{
		$output = array();
		if(Str::NotEmpty($string))
		{
			$matches = array();
			preg_match_all('/([a-z]+(?:-[a-z]+)?)(?:\s*;\s*q\s*=\s*(1|0(?:\.\d{1,3})?))?/i', $string, $matches, PREG_SET_ORDER); //https://www.rfc-editor.org/rfc/rfc7231#section-5.3.1
			foreach($matches as $value) $output[$value[1]] = floatval($value[2] ?? 1);
			unset($matches);
			arsort($output, SORT_NUMERIC); //sort by value descending
			$output = array_filter($output); //remove zeros (0 means "not acceptable")
		}
		return array_keys($output);
	}

	public static function ParseExpected(array $params = [], array $defaults = [], bool $hide_password = true): array
	{
		if(count($params)) //expecting something?
		{
			self::$params = array_fill_keys($params, null); //convert values to keys and values to nulls

			//set default values
			foreach(self::$params as $key => $val)
			{
				if(array_key_exists($key, $defaults))
				{
					if(is_array($defaults[$key]) && count($defaults[$key])) self::$params[$key] = $defaults[$key][0]; //first value from the list
					else self::$params[$key] = $defaults[$key]; //copy default value
				}
			}

			//parse
			foreach(self::$params as $key => $val) //expected
			{
				if(array_key_exists($key, $_REQUEST))
				{
					$value =  trim($_REQUEST[$key]); //delivered

					//check for allowed values
					if(array_key_exists($key, $defaults) && is_array($defaults[$key]) && !in_array(strtolower($value), $defaults[$key])) continue; //value not found?

					//convert from request
					if(is_null($val)) //allow any
					{
						if(Num::IsInteger($value)) self::$params[$key] = Num::ToInteger($value);
						elseif(Num::IsFloat($value)) self::$params[$key] = Num::ToFloat($value);
						elseif(Any::NotEmpty($value)) self::$params[$key] = $value;
					}
					elseif(is_numeric($val)) //allow int or float
					{
						if(Num::IsInteger($value)) self::$params[$key] = Num::ToInteger($value);
						elseif(Num::IsFloat($value)) self::$params[$key] = Num::ToFloat($value);
					}
					elseif(is_string($val))
					{
						if(Any::NotEmpty($value)) self::$params[$key] = $value;
					}
				} //key not exists in request = leaves the default value
			}
		}
		else self::$params = $_REQUEST; //copy all

		if($hide_password && isset($_POST['password'])) $_REQUEST['password'] = $_POST['password'] = str_repeat('*', mb_strlen($_POST['password'])); //hide password from raw request

		return self::$params;
	}

	public static function ExtractParams(string $prefix = 'param'): void //extract parsed params to global vars with prefix $param_...
	{
		foreach(self::$params as $key => $value) $GLOBALS[$prefix.'_'.$key] = $value; //extract(self::$params, EXTR_OVERWRITE | EXTR_PREFIX_ALL, $prefix) to globals
	}

	public static function GetToken(): string //nonce
	{
		return self::$token;
	}

	public static function GetClientIP(): string
	{
		return self::$clientip;
	}

	public static function GetURI(): string
	{
		return self::$request; //whole request
	}

	public static function GetMethod(): string
	{
		return self::$method;
	}

	public static function GetParams(array|string|null ...$keys): array //after ParseExpected
	{
		if (empty($keys) || ($keys === [null])) return self::$params; //return all params

		if (count($keys) === 1 && is_array($keys[0])) $keys = $keys[0]; //$keys is array

		$result = array_fill_keys($keys, null); //null default values
		foreach ($keys as $key)
		{
			if (array_key_exists($key, self::$params)) $result[$key] = self::$params[$key];
		}
		return $result; //return requested parameters
	}

	public static function GetParam(string $parameter, bool $raw = false): null|int|float|string|array
	{
		if($raw) return $_REQUEST[$parameter] ?? null;
		else return self::$params[$parameter] ?? null; //after ParseExpected
	}

	public static function IsParam(string $parameter, bool $raw = false): bool
	{
		if($raw) return isset($_REQUEST[$parameter]);
		else return isset(self::$params[$parameter]); //after ParseExpected
	}

	public static function IsPost(?string $parameter = 'submit'): bool
	{
		if(Any::IsEmpty($parameter)) return self::$method === 'POST';
		else return isset($_POST[$parameter]);
	}

	public static function IsGet(?string $parameter = null): bool
	{
		if(Any::IsEmpty($parameter)) return self::$method === 'GET';
		else return isset($_GET[$parameter]);
	}

	public static function IsFileName(string $filename = ''): bool
	{
		return self::$filename === $filename;
	}

	public static function GetFileName(?string $page = ''): ?string //null = bypass
	{
		if(!is_null($page)) if(strlen($page) === 0 || str_starts_with($page, '?') || str_starts_with($page, '#')) $page = self::$filename.$page;
		return $page;
	}

	public static function Redirect(?string $page = null)
	{
		if(App::Env('APP_BUFFERING') && ob_get_level()) ob_clean();
		else if(headers_sent()) trigger_error('Cannot modify location! Headers already sent! Redirects before sending any output or enable output buffering.', E_USER_ERROR);

		header('Location: ' . (self::$secured?'https://':'http://') . self::$server . rtrim(str_replace('\\', '/', DIR_WORK), '/')  . '/' . self::GetFileName($page));
		exit;
	}

	public static function Modify(array $params_new = [], ?string $url = null): string //empty array only delete empty elements
	{
		if($url === null) $url = self::$request;

		$query = parse_url($url, PHP_URL_QUERY); //get ?xxx=yyy&zzz... (without question mark)
		$params = array(); //empty array
		if(Any::NotEmpty($query)) parse_str($query, $params); //parse query string into variables array
		$params = array_merge($params, $params_new); // combine and overwrite same string keys (numeric keys will not overwrite !!!)
		$params = array_filter($params, 'Any::NotEmpty'); //fn($value) => !is_null($value) && $value !== ''); //delete empty elements

		$query_new = http_build_query($params); //http_build_query($params, '', '&amp;')
		if(strlen($query_new)) $query_new = '?'.$query_new ; //add '?'
		if(Any::NotEmpty($query)) return str_ireplace('?'.$query, $query_new, $url); //replace query if exist (ignoring case)
		else//add query if not exist
		{
			$fragment = parse_url($url, PHP_URL_FRAGMENT); //get '#' data
			if(Any::NotEmpty($fragment))//fragment exist?
			{
				$fragment = '#'.$fragment; //add '#'
				$url = str_replace($fragment, '', $url); //remove fragment
				return $url.$query_new.$fragment; //make new url with new query and old fragment
			}
			else return $url.$query_new; //make new url
		}
	}
}

class_alias('Request', 'Req');

Request::Initialize();
