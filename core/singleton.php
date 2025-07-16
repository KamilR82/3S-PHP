<?php

declare(strict_types = 1);

//DEFAULT CONSTANTS
define('3S_NAME', '3S PHP Framework');

define('DIR_ROOT', $_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
define('DIR_WORK', dirname($_SERVER['SCRIPT_NAME']));

define('APP_DEBUG', true); //DEVELOPMENT OR PRODUCTION
define('APP_NAME', constant('3S_NAME')); //etc. Company / Information System
define('APP_SERVER', $_SERVER['SERVER_NAME'] ?? ''); //server URL without protocol
define('APP_TIMEZONE', date_default_timezone_get()); //timezone used by all date/time functions
define('APP_ENCODING', 'UTF-8'); //UTF-8 = 1-4 bytes
define('APP_BUFFERING', false); //output buffering

define('SESSION_LIFETIME', 0); //lifetime of the session cookie, defined in seconds. 0 = until the browser is closed
define('SESSION_PATH', '/'); //Path on the domain where the cookie will work. Use a single slash ('/') for all paths on the domain. Use empty ('') for current path.
define('SESSION_DOMAIN', null); //Cookie domain, for example 'www.php.net'. To make cookies visible on all subdomains then the domain must be prefixed with a dot like '.php.net'.
define('SESSION_SECURE', true); //If true cookie will only be sent over secure connections.
define('SESSION_HTTPONLY', false); //This means that the cookie won't be accessible by scripting languages, such as JavaScript. (this reduce identity theft through XSS attacks)

define('COOKIE_EXPIRES', 31536000); //lifetime of the session cookie, defined in seconds. 0 = until the browser is closed
define('COOKIE_PATH', '/'); //Path on the domain where the cookie will work. Use a single slash ('/') for all paths on the domain. Use empty ('') for current path.
define('COOKIE_DOMAIN', null); //Cookie domain, for example 'www.php.net'. To make cookies visible on all subdomains then the domain must be prefixed with a dot like '.php.net'.
define('COOKIE_SECURE', true); //If true cookie will only be sent over secure connections.
define('COOKIE_HTTPONLY', false); //This means that the cookie won't be accessible by scripting languages, such as JavaScript. (this reduce identity theft through XSS attacks)

if(!defined('CONFIG_FILE')) define('CONFIG_FILE', './config.env'); //supported config file types .ini .env .php

//Traits
trait EnumToArray
{
	public static function names(): array
	{
		return array_column(self::cases(), 'name');
	}

	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}

	public static function array(): array
	{
		return array_combine(self::names(), self::values());
	}
}

trait Patterns
{
	const pattern_pin_code = '^\d{4}$'; //4 numbers
	const pattern_pin_code_wide = '^\d{4,}$'; //4 or more numbers
	const pattern_password_weak = '^.{4,}$'; //must contain at least four any chars
	const pattern_password = '^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z]).{6,}$'; //6 chars: at least one UPPER, lower, number
	const pattern_password_strong = '^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*_=+]).{8,}$'; //8 chars: at least one UPPER, lower, number, special char

	const pattern_email = '^[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$'; //who@where.domain
	const pattern_phone = '^[+]?[0-9 ]*$'; //phone number
	const pattern_iban = '^([A-Z]{2}[ \-]?[0-9]{2})(?=(?:[ \-]?[A-Z0-9]){9,30}$)((?:[ \-]?[A-Z0-9]{3,5}){2,7})([ \-]?[A-Z0-9]{1,3})?$';
	const pattern_url = '^https?:\/\/(?:(?:[a-zA-Z\u00a1-\uffff0-9]+-?)*[a-zA-Z\u00a1-\uffff0-9]+)(?:\.(?:[a-zA-Z\u00a1-\uffff0-9]+-?)*[a-zA-Z\u00a1-\uffff0-9]+)*(?:\.(?:[a-zA-Z\u00a1-\uffff]{2,}))(?::\d{2,5})?(?:\/[^\s]*)?$';
	const pattern_dec = '^\d*$'; //DEC string
	const pattern_hex = '^[a-fA-F\d]+$'; //HEX string
	const pattern_code39 = '^[A-Z0-9-. $/+%]+$'; //Barcode 3of9

	const pattern_price = '^(0*[1-9][0-9]*(\.[0-9]+)?|0+\.[0-9]*[1-9][0-9]*)$'; //only positive
	const pattern_num = '^\d+(\.\d{1,2})?$'; //positive or zero
	const pattern_float = '^[-+]?\d+(\.\d{1,2})?$'; //positive or zero or negative

	const pattern_year = '^[1-2][0-9]{3}$'; //YYYY
	const pattern_date_us = '^((19\d{2})|(2\d{3}))-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])$'; //USA dates half validation YYYY-MM-DD
	const pattern_date_eu = '^(?:(?:31(\.)(?:0?[13578]|1[02]))\1|(?:(?:29|30)(\.)(?:0?[1,3-9]|1[0-2])\2))(?:(?:1[6-9]|[2-9]\d)\d{2})$|^(?:29(\.)0?2\3(?:(?:(?:1[6-9]|[2-9]\d)(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\d|2[0-8])(\.)(?:(?:0?[1-9])|(?:1[0-2]))\4(?:(?:1[6-9]|[2-9]\d)\d{2})$'; //Europe dates full validation d.m.yyyy (min 1.1.1600 - max 31.12.9999)
	const pattern_time = '^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$'; //HH:MM:SS
	const pattern_timestamp = '^((19\d{2})|(2\d{3}))-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01]) ([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$'; //pattern_date_us.' '.pattern_time
}

//HTTP response status codes
enum ResponseCode: int
{
    case Nothing = 0; //Do not response.
    case Success = 200; //The request succeeded.
    case Created = 201; //The request succeeded, and a new resource was created as a result. This is typically the response sent after POST requests, or some PUT requests.
    case Moved = 301; //The URL of the requested resource has been changed permanently. The new URL is given in the response.
	//Client error responses (400 – 499)
    case Bad_Request = 400; //The server cannot or will not process the request due to something that is perceived to be a client error (e.g., malformed request syntax, invalid request message framing, or deceptive request routing).
    case Unauthorized = 401; //Although the HTTP standard specifies "unauthorized", semantically this response means "unauthenticated". That is, the client must authenticate itself to get the requested response.
    case Payment = 402; //The initial purpose of this code was for digital payment systems, however this status code is rarely used and no standard convention exists.
    case Forbidden = 403; //Access Denied. The client does not have access rights to the content; that is, it is unauthorized, so the server is refusing to give the requested resource. Unlike 401 Unauthorized, the client's identity is known to the server.
    case Not_Found = 404; //The server cannot find the requested resource.
    case Not_Allowed = 405; //The request method is known by the server but is not supported by the target resource. For example, an API may not allow DELETE on a resource, or the TRACE method entirely.
    case Unsupported = 415; //The media format of the requested data is not supported by the server, so the server is rejecting the request.
	//Server error responses (500 – 599)
    case Server_Error = 500; //The server has encountered a situation it does not know how to handle. This error is generic, indicating that the server cannot find a more appropriate 5XX status code to respond with.
    case Not_Implemented = 501; //The request method is not supported by the server and cannot be handled. The only methods that servers are required to support (and therefore that must not return this code) are GET and HEAD.
}

//Parent Class
interface SingletonInterface
{
	public static function Initialize(): Singleton;
	public static function Instance(): ?Singleton;
}

/** @phpstan-consistent-constructor */
abstract class Singleton implements SingletonInterface
{
	/** @var array<string, Singleton> $instances */
	private static array $instances = []; // array where each subclass of the Singleton stores its own instance

	protected function __construct() { } // private to prevent direct construction calls with the `new` operator.

	protected function __clone() { } // singletons should not be cloneable

	/** @return noreturn */
	public function __wakeup(): never // If both __unserialize() and __wakeup() are defined in the same object, only __unserialize() will be called and __wakeup() will be ignored. 
	{
		throw new \Exception('Cannot unserialize singleton'); // Singletons should not be restorable from strings
	}

	final public static function Initialize(...$args): Singleton
	{
		$subclass = static::class;
		if(!isset(self::$instances[$subclass]))
		{
			// Note that here we use the "static" keyword instead of the actual class name. In this context, the "static" keyword means "the name of the current class".
			// That detail is important because when the method is called on the subclass, we want an instance of that subclass to be created here.
			self::$instances[$subclass] = new static(...$args); //send arguments to class constructor
		}
		return self::$instances[$subclass];
	}

	final public static function Instance(): ?Singleton
	{
		return self::$instances[static::class] ?? null;
	}
}

//Autoloader
class App extends Singleton
{
	const extension = '.php'; //for class files

	/** @var array<string, mixed> $config */
	private static array|false $config = false;

	protected function __construct()
	{
		self::Protect(__FILE__);

		//PHP version check
		if(version_compare(PHP_VERSION, '8.2.0') < 0) self::Die(ResponseCode::Server_Error, 'Upgrade Required! Need PHP version 8.2.0 or greater! Current PHP version: '.PHP_VERSION);

		//load config
		self::$config = self::Load(CONFIG_FILE);

		//setup app
		error_reporting(self::Env('APP_DEBUG') ? E_ALL : 0);
		date_default_timezone_set(self::Env('APP_TIMEZONE'));
		mb_internal_encoding(self::Env('APP_ENCODING'));
		spl_autoload_register(self::class.'::Autoload'); //class autoloader
		if(self::Env('APP_BUFFERING')) ob_start(); //turn on output buffering
		else if(ob_get_level()) ob_end_clean(); //turn off output buffering

		//session
		$options['lifetime'] = self::Env('SESSION_LIFETIME');
		if($options['lifetime'] > 0) $options['lifetime'] += time(); 
		$options['path'] = self::Env('SESSION_PATH');
		$options['secure'] = self::Env('SESSION_SECURE');
		$options['httponly'] = self::Env('SESSION_DOMAIN');
		$options['domain'] = self::Env('SESSION_HTTPONLY');
		session_set_cookie_params($options);
		if(session_status() !== PHP_SESSION_ACTIVE) session_start(); //start new or resume existing session
		if($options['lifetime'] > 0)
		{
			$options['expires'] = $options['lifetime'];
			unset($options['lifetime']);
			setcookie(session_name(), session_id(), $options); //change the session expiry every time the user visits the site
		}
	}

	public static function Die(ResponseCode $code = ResponseCode::Nothing, string $string = ''): void
	{
		if($code->value && !self::Env('APP_DEBUG'))
		{
			http_response_code($code->value);
			$string = 'Error '.$code->value.': '.$string;
		}
		if($code->value >= 400) die($string);
	}

	public static function Protect(string $filename): void //protection against running script files individually
	{
		if(basename($_SERVER['SCRIPT_NAME']) === basename($filename)) self::Die(ResponseCode::Forbidden, 'Forbidden!');
	}

	public static function Load(string $file): array|false
	{
		if(file_exists($file))
		{
			switch(substr($file, -4))
			{
				case '.php';
					return require($file);
					break;
				case '.env';
					$file = file_get_contents($file);
					if($file !== false)
					{
						/* Regex:
						/^   // start
						\s?  // optional whitespace (before #)
						#    // looking for #
						.*   // then anything
						$    // to end of line
						/m   // multilined
						*/
						$file = preg_replace('/^\s?#.+$/m', '', $file); //remove comments (lines starting with #)
						return parse_ini_string($file, scanner_mode: INI_SCANNER_TYPED);
					}
					break;
				case '.ini';
					return parse_ini_file($file, scanner_mode: INI_SCANNER_TYPED); //ignore sections
					break;
				default;
					self::Die(ResponseCode::Unsupported, 'Unsupported Media Type! Loading file: '.$file);
			}
		}
		else self::Die(ResponseCode::Not_Found, 'Not Found! Loading file: '.$file);
		return false;
	}

	public static function Env(string $name): string|int|float|bool|null
	{
		return self::$config[$name] ?? (defined($name) ? constant($name) : null); //1: from config file; 2: from predefined constant 3: not found (null);
	}

	public static function SetSession(?string $key = null, ?string $value = null): void
	{
		if(is_null($value)) unset($_SESSION[$key]);
		else $_SESSION[$key] = $value;
	}

	public static function IsSession(?string $key = null): bool
	{
		if(Str::IsEmpty($key)) return session_id() !== false;
		else return isset($_SESSION[$key]);
	}

	public static function GetSession(?string $key = null): ?string
	{
		if(Str::IsEmpty($key)) return session_id() ?: null; //get current session or null
		else return $_SESSION[$key] ?? null;
	}

	public static function RemSession(?string $key = null): void
	{
		if(is_null($key))
		{
			session_unset(); //free all session local variables, but session id will not be destroy (like $_SESSION=[];)
			session_regenerate_id(true); //reset session id to avoid session fixation
			session_destroy(); //destroy all data registered to a session in storage
		}
		else unset($_SESSION[$key]);
	}

	public static function SetCookie(string $key, ?string $value = null, ?int $minutes = null, ?string $path = null, ?bool $secure = null): bool
	{
		$options = [];
		$options['expires'] = is_null($minutes) ? self::Env('COOKIE_EXPIRES') : ($minutes * 60);
		if($options['expires'] > 0) $options['expires'] += time(); 
		$options['path'] = is_null($path) ? self::Env('COOKIE_PATH') : $path; //available within the entire domain
		$options['secure'] = is_null($secure) ? self::Env('COOKIE_SECURE') : $secure; //cookie will only be set only over a secure HTTPS connection
		$options['httponly'] = self::Env('COOKIE_DOMAIN');
		$options['domain'] = self::Env('COOKIE_HTTPONLY');
		return is_null($value) ? setcookie($key, '', -1) : setcookie($key, $value, $options); //$_COOKIE values may also exist in $_REQUEST array
	}

	public static function IsCookie(string $key): bool
	{
		return isset($_COOKIE[$key]);
	}

	public static function GetCookie(string $key): ?string
	{
		return $_COOKIE[$key] ?? null;
	}

	public static function Autoload(string $class): void
	{
		$filename = __DIR__ . DIRECTORY_SEPARATOR . strtolower(match($class) {
			'Any', 'Num', 'Str', 'Arr', 'DT' => 'utilities', 
			'Request', 'Req' => 'request', 
			'Page', 'Queue' => 'hypertext',
			'Language', 'Lang' => 'language', 
			'Logger', 'Log' => 'logger',
			'DataBase', 'DB' => 'database',
			'Userlogin', 'User', 'Permits', 'PermitLevel' => 'userlogin', 
			default => 'class_'.strtok($class, '_'),
		}) . self::extension;
		if(file_exists($filename)) require_once($filename);
		else self::Die(ResponseCode::Not_Found, 'Not Found! Class `'.$class.'` is missing!'); //Class or trait or enum
	}

	public function __destruct()
	{
		if(self::Env('APP_BUFFERING')) while(ob_get_level()) ob_end_flush(); //send buffer to output and turn buffer off 
		spl_autoload_unregister(self::class.'::Autoload');
	}
}

App::Initialize();

Request::ParseExpected($params ?? [], $defaults ?? []); //initialize request class and parse params with defaults

if(App::IsCookie('language')) Language::Set(App::GetCookie('language')); //set language from cookie
