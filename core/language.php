<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

//DEFAULT CONSTANTS
define('APP_LANGUAGE', 'en'); //default language (if a key doesn’t exist in the set variant, always fall back to the default language file)
define('APP_LOCALE', ''); //geographical location helps control how content is displayed (currency and date formatting) //'' = set from the system's regional/language settings

define('PATH_LOCALE', 'locales/');

define('TRANSLATE_PREFIX', '_');
define('TRANSLATE_POSTFIX', '');

class Language extends Singleton
{
	/** @var array<int|string, string> $lang_default */
	private static array|false $lang_default = false;

	/** @var array<int|string, string> $language */
	private static array|false $lang_active = false;
	
	const days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'); //default weekdays
	const months = array('(whole year)', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'); //default months

	protected function __construct()
	{
		$file = constant('PATH_LOCALE').constant('APP_LANGUAGE').'.php';
		if(file_exists($file))
		{
			self::$lang_default = App::Load($file);
			if(self::$lang_default === false) App::Die(ResponseCode::Server_Error, 'Default language file loading error! File: '.$file);
		}
		//else App::Die(ResponseCode::Not_Found, 'Default language file does not exist! File: '.$file);

		self::Locale(constant('APP_LOCALE'));
	}

	public static function Locale(string $locale = '0', string $code_page = 'utf8' /* not utf-8 */, int $category = LC_ALL): string|false
	{
		if($locale !== '0')
		{
			//"<language>_<country>.<code_page>"
			if(!str_contains($locale, '.'))
			{
				if(!str_contains($locale, '_'))
				{
					$locale .= '_' . match($locale)
					{
						'be' => 'BY', //belarusian
						'cs' => 'CZ', //czech
						'da' => 'DK', //danish
						'en' => 'US', //american
						'ja' => 'JP', //japanese
						'ko' => 'KR', //korean
						'lb' => 'LU', //luxembourgish
						'nb' => 'NO', //norway
						'sl' => 'SI', //slovenian
						'sv' => 'SE', //swedish
						'uk' => 'UA', //ukrainian
						'vi' => 'VN', //vietnamese
						'zh' => 'CN', //chinese
						default => strtoupper($locale), //language = country (slovak, polish, etc.)
					};
				}
				$locale .= '.' . $code_page;
			}
		}
		return setlocale($category, $locale); //$locale = '0' - setting is not affected, only the current setting is returned
	}

	public static function Set(null|string|array $lang): string|false //set language
	{
		if(is_null($lang)) return false;
		if(is_array($lang))
		{
			foreach($lang as $value) if(self::Set($value) !== false) return $value;
		}
		else if(Str::NotEmpty($lang))
		{
			$file = constant('PATH_LOCALE').$lang.'.php';
			if(file_exists($file))
			{
				$language = App::Load($file);
				if($language !== false)
				{
					App::SetCookie('language', $lang); //save actual language to cookie
					self::$lang_active = $language; //use it
					self::Locale($lang); //try to set locale
					return $lang;
				}
			}
		}
		return false;
	}

	public static function Get(string|int $id, int $plural = 0): string //get localized string
	{
		//active
		if(self::$lang_active !== false)
		{
			if(isset(self::$lang_active[$id])) //exist?
			{
				if(is_array(self::$lang_active[$id])) //array?
				{
					if(count(self::$lang_active[$id])) //one or more items
					{
						if(isset(self::$lang_active[$id][$plural])) return self::$lang_active[$id][$plural]; //$plural found exactly
						else
						{
							for($value = end(self::$lang_active[$id]); ($key = key(self::$lang_active[$id])) !== null; $value = prev(self::$lang_active[$id])) //reverse loop array(self::$lang_active[$id])
							{
								if($key <= $plural) return $value; //nearest smaller $plural
							}
							return self::$lang_active[$id][0]; //return first item
						}
					}
				}
				else return self::$lang_active[$id]; //string
			}
		}
		//default
		if(self::$lang_default !== false)
		{
			if(isset(self::$lang_default[$id])) //exist?
			{
				if(is_array(self::$lang_default[$id])) //array?
				{
					if(count(self::$lang_default[$id])) //one or more items
					{
						if(isset(self::$lang_default[$id][$plural])) return self::$lang_default[$id][$plural]; //$plural found exactly
						else
						{
							for($value = end(self::$lang_default[$id]); ($key = key(self::$lang_default[$id])) !== null; $value = prev(self::$lang_default[$id])) //reverse loop array(self::$lang_default[$id])
							{
								if($key <= $plural) return $value; //nearest smaller $plural
							}
							return self::$lang_default[$id][0]; //return first item
						}
					}
				}
				else return self::$lang_default[$id]; //string
			}
		}
		//not found
		return App::Env('APP_DEBUG') ? "&iquest;{$id}?" : '???';
	}

	public static function Translate(string $text, string $prefix = TRANSLATE_PREFIX, string $postfix = TRANSLATE_POSTFIX): string //translate string
	{
		$language = array_merge(self::$lang_default, self::$lang_active); //join - same string keys will be overwritten (numeric keys not, but will be appended !)
		$language = array_filter($language, fn($v, $k) => is_string($k) && is_string($v), ARRAY_FILTER_USE_BOTH); //delete not string elements
		Arr::SortByKeyLen($language, SORT_DESC); //longer keys first

		$patterns = array_keys($language); //get keys
		if(Str::NotEmpty($prefix) || Str::NotEmpty($postfix)) array_walk($patterns, fn(&$v, $k) => $v = $prefix.$v.$postfix); //apply delimiters
		$replacements = array_values($language); //get values

		return str_replace($patterns, $replacements, $text);
	}

	public static function Date(string $str): string //localize date
	{
		if(isset(self::$lang_active['days'])) $str = str_replace(self::days, self::$lang_active['days'], $str); //weekdays
		if(isset(self::$lang_active['months'])) $str = str_replace(self::months, self::$lang_active['months'], $str); //months
		return $str;
	}

	public static function Day(int $day): string //get day name
	{
		return self::$lang_active['days'][$day] ?? self::days[$day];
	}

	public static function Month(int $month): string //get month name
	{
		return self::$lang_active['months'][$month] ?? self::months[$month];
	}

	public static function Months(bool $whole_year = false, bool $with_numbers = false): array //get array of months
	{
		$months = self::$lang_active['months'] ?? self::months;

		$whole_year_name = $months[0]; //save first
		Arr::Shift($months); //remove first
		if($with_numbers) foreach($months as $key => $val) $months[$key] = $key.' - '.$val; //month numbers + names
		if($whole_year) Arr::Unshift($months, ['' => $whole_year_name]); //add first with empty key for html select
		return $months;
	}
}

Language::Initialize();

class_alias('Language', 'Lang');

//function _() - alias of built-in php function gettext()
function _L(string $text, int $plural = 0): string { return Language::Get($text, $plural); } //use function Lang::Get as _L; //alias
function _T(string $text, string $prefix = TRANSLATE_PREFIX, string $postfix = TRANSLATE_POSTFIX): string { return Language::Translate($text, $prefix, $postfix); } //use function Lang::Translate as _T; //alias
