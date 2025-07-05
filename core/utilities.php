<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

class Any extends Singleton
{
	public static function IsEmpty(mixed $value, bool $trim = true): bool
	{
		return $value === null || ($trim ? trim(strval($value)) : strval($value)) === ''; //NOT like empty(0)
	}

	public static function NotEmpty(mixed $value, bool $trim = true): bool
	{
		return $value !== null && ($trim ? trim(strval($value)) : strval($value)) !== ''; //NOT like !empty(0)
	}

	//Returns true boolean values. Return false for non-boolean values.
	public static function IsBool(mixed $value): bool
	{
		return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_REQUIRE_SCALAR | FILTER_NULL_ON_FAILURE) !== null;
	}

	//Returns true for "1", "true", "on", and "yes". Returns false for "0", "false", "off", "no", and "". Return null for non-boolean values.
	public static function ToBool(mixed $value, ?bool $default = null): ?bool
	{
		return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_REQUIRE_SCALAR | FILTER_NULL_ON_FAILURE) ?? $default;
	}

	//Returns true for "1", "true", "on", and "yes". Otherwise returns false.
	public static function ToBoolOnly(mixed $value): bool
	{
		return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_REQUIRE_SCALAR);
	}

	//allow integers in hexadecimal notation (start with 0x)
	public static function IsInt(mixed $value): bool
	{
		return filter_var($value, FILTER_VALIDATE_INT, FILTER_REQUIRE_SCALAR | FILTER_NULL_ON_FAILURE | FILTER_FLAG_ALLOW_HEX) !== null;
	}

	//allow integers in hexadecimal notation (start with 0x)
	public static function ToInt(mixed $value, ?int $default = null): ?int
	{
		return filter_var($value, FILTER_VALIDATE_INT, FILTER_REQUIRE_SCALAR | FILTER_NULL_ON_FAILURE | FILTER_FLAG_ALLOW_HEX) ?? $default;
	}

	//accept commas which usually represent the thousand separator
	public static function IsFloat(mixed $value): bool
	{
		return filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_REQUIRE_SCALAR | FILTER_NULL_ON_FAILURE | FILTER_FLAG_ALLOW_THOUSAND) !== null;
	}

	//accept commas which usually represent the thousand separator
	public static function ToFloat(mixed $value, ?float $default = null): ?float
	{
		return filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_REQUIRE_SCALAR | FILTER_NULL_ON_FAILURE | FILTER_FLAG_ALLOW_THOUSAND) ?? $default;
	}
}

class Str extends Singleton
{
	public static function IsEmpty(?string $value, bool $trim = true): bool
	{
		return $value === null || ($trim ? trim($value) : $value) === '';
	}

	public static function NotEmpty(?string $value, bool $trim = true): bool
	{
		return $value !== null && ($trim ? trim($value) : $value) !== '';
	}

	public static function Length(?string $str, bool $trim = true): int
	{
		return $str === null ? 0 : mb_strlen($trim ? trim($str) : $str);
	}

	public static function IsCapitalLetter(?string $str, bool $trim = true): bool
	{
		if(Str::IsEmpty($str, trim: $trim)) return false;
		if($trim) $str = trim($str);
		$first = mb_substr($str, 0, 1);
		return mb_strtolower($first) !== $first;
	}

	public static function Truncate(?string $str, int $length = 32, string $append = '&hellip;', bool $trim = true): string
	{
		if(Str::IsEmpty($str, trim: $trim)) return '';
		if($trim) $str = trim($str);
		if(Str::Length($str) > $length)
		{
			$str = wordwrap($str, $length);
			$str = explode("\n", $str);
			$str = array_shift($str) . $append;
		}
		return $str;
	}

	public static function Sanitize(?string $str): ?string
	{
		return is_null($str) ? null : htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
	}
}

class Num extends Singleton
{
	public static function IsInteger(string|float|int|null $input, bool $sign = true): bool //only integer
	{
		if(is_numeric($input))
		{
			$input = trim(strval($input));
			if($sign) $input = ltrim($input, '+-');
			return ctype_digit(strval($input)); // checks if all of the characters is digits
		}
		return false;
	}

	public static function IsFloat(string|float|int|null $input, bool $sign = true): bool //only float
	{
		if(is_numeric($input))
		{
			$input = trim(strval($input));
			if($sign) $input = ltrim($input, '+-');
			if(preg_match('/^(\d{1,})\.(\d{1,})$/', strval($input))) return true;
		}
		return false;
	}

	public static function ToInteger(string $num): int
	{
		return intval($num);
	}

	public static function ToFloat(string $num): float //'1.999,369€' or '126,564,789.33 m²'
	{
		$num = preg_replace('/[^0-9-.,]/', '', $num); //remove ballast
		$posMinus = strpos($num, '-'); //looking for minus
		$posComma = strrpos($num, ','); //looking for comma
		$posDot = strrpos($num, '.'); //looking for dot
		$sep = (($posDot > $posComma) && $posDot) ? $posDot : ((($posComma > $posDot) && $posComma) ? $posComma : false);
		if(!$sep) $num = preg_replace('/[^0-9]/', '', $num);
		else $num = preg_replace('/[^0-9]/', '', substr($num, 0, $sep)).'.'.preg_replace('/[^0-9]/', '', substr($num, $sep+1, strlen($num)));
		if($posMinus === 0) $num = '-'.$num; //add minus sign
		return floatval($num); //floatval allow only decimal dot
	}

	public static function ToPercent(float $num, float $denominator, bool $plus_sign = false, int $decimals = 2): string
	{
		if($denominator > 0)
		{
			$percent = 100 * $num / $denominator;
			if($plus_sign && $percent != 0.0) return sprintf('%+.'.$decimals.'f&nbsp;%%', $percent); //with plus sign
			else return number_format($percent, $decimals, ',', '&nbsp;').'&nbsp;%'; //without plus sign
		}
		else return '&mdash;';
	}

	public static function ToCurrency(string|int|float|null $amount, ?string $currency = null, bool $dash = false): string
	{
		if(Any::IsEmpty($amount)) $amount = '&mdash;';
		elseif(is_numeric($amount))
		{
			$amount = floatval($num);
			if($dash && $amount == 0) $amount = '&mdash;';
			else
			{
				$fmt = new \NumberFormatter(App::Env('APP_LOCALE'), \NumberFormatter::CURRENCY);
				if(is_null($currency)) $currency = $fmt->getSymbol(\NumberFormatter::INTL_CURRENCY_SYMBOL); //3-letter ISO 4217
				$amount = $fmt->formatCurrency($amount, $currency);
			}
		}
		return $amount;
	}
}

class Arr extends Singleton
{
	public static function SortByKeyLen(array &$array, int $order = SORT_DESC): bool //SORT_DESC = longer keys first
	{
		return array_multisort(array_map('strlen', array_keys($array)), $order, $array); //return uksort($array, function ($a,$b) {return strlen($b) - strlen($a);}); //almost same execution time
	}

	public static function Push(array &$array, array $add): void //add element(s)
	{
		$array = $array + $add; //array_push
	}

	public static function Pop(array &$array): void //remove last element
	{
		unset($array[array_key_last($array)]); //array_pop
	}

	public static function Shift(array &$array): void //remove first element
	{
		unset($array[array_key_first($array)]); //array_shift WITHOUT REINDEX KEYS !!!
	}

	public static function Unshift(array &$array, array $add): void //add first element(s)
	{
		$array = $add + $array; //array_unshift WITHOUT REINDEX KEYS !!!
	}
}

class DT extends Singleton //Date & Time
{
	//Be careful when using the strtotime() function because produces different output on 32 and 64 bit systems.
	//strtotime("0000-00-00 00:00:00") returns FALSE on a 32 bit system and returns -62169955200 on a 64 bit system.
	//Range is from 1.1.1970 00:00:00 UTC to 19.1.2038 03:14:07 UTC on a 32 bit system (Minimum is 13.12.1901 20:45:54 UTC for some platforms and php versions)
	//For 64-bit versions of PHP, the valid range of a timestamp is effectively infinite (thats ok)

	use Patterns;

	public static function IsDate(?string $str, bool $trim = true): bool
	{
		if(Str::IsEmpty($str, trim: $trim)) return false;
		if($trim) $str = trim($str);
		if(preg_match('/'.self::pattern_date_eu.'/', $str)) return true;
		if(preg_match('/'.self::pattern_date_us.'/', $str)) return true;
		return false; //not date
	}

	public static function Diff(?string $origin = 'now', ?string $target = 'now', bool $strict = true): ?int //$strict = compare only dates without time (set false for compare timestamps)
	{
		$origin = date_create($origin ?: 'now');
		$target = date_create($target ?: 'now');
		if($origin && $target)
		{
			if($strict)
			{
				$origin->setTime(0, 0);
				$target->setTime(0, 0);
			}
			$interval = date_diff($origin, $target);
			return intval($interval->format('%R%a'));
		}
		else return null;
	}

	//https://en.wikipedia.org/wiki/List_of_date_formats_by_country
	//https://www.w3schools.com/php/func_date_date_format.asp
	//$format:
	//j.n.Y = d.m.year //DMY - EU, Africa, Central & South America
	//Y-m-d = YEAR-MM-DD //YMD - US, China
	//m/d/Y = MM/DD/YEAR //MDY - some countries
	//W l(lowercase 'L') = week no. Monday
	public static function Conv(?string $input = 'now', string $format = 'Y-m-d', string $invalid = '', string $modify = ''): string
	{
		if(Str::IsEmpty($input)) return $invalid;

		$date = date_create($input); //accept EU or US date format or 'now' ('today' not set time)
		if($date === false) return $invalid;
		if(date_format($date, 'y') < 0) return $invalid; // date_create('0000-00-00') set year to -1 on some PHP versions

		if($date)
		{
			if(Str::NotEmpty($modify)) date_modify($date, $modify); //'+1 week' etc
			$output_date = date_format($date, $format);
			return Language::Date($output_date);
		}
		else return $invalid;
	}

	public static function IsWeekend(string $date): bool
	{
		return intval(DT::Conv($date, 'N', '0')) > 5;
	}

	public static function ToHours(int $seconds, string $append = 'h', bool $dash = true): string //seconds to hours
	{
		if($dash && $seconds == 0) return '&mdash;';
		return number_format($seconds / 3600, 2, ',', ' ').'&nbsp;'.$append;
	}

	public static function Seconds(?int $seconds, bool $negative = false): string //replacement for SQL function SEC_TO_TIME because have limit 838:59:59 (1 month) !!!
	{
		$output = '';
		if(is_null($seconds)) $output = sprintf('%02d:%02d:%02d', 0, 0, 0);
		else
		{
			if($seconds < 0)
			{
				$negative = true;
				$seconds = abs($seconds);
			}

			if($seconds >= 432000) //5 or more days (5*24*60*60)
			{
				return self::Days(intdiv($seconds, 86400), $negative); //divint will round to the closest number
			}
			elseif($seconds >= 172800) //2 or more days (2*24*60*60)
			{
				$hours = intval(floor($seconds / 3600));
				$days = intval(floor($hours / 24));
				$hours %= 24;
				$output = sprintf('%d %s', $days, Language::Get('day', $days));
				if($hours) $output .= sprintf(' %02d h', $hours);
			}
			else //less than 2 days
			{
				$hours = intval(floor($seconds / 3600));
				$mins = intval(floor($seconds / 60)) % 60;
				$secs = $seconds % 60;
				$output = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
			}
		}
		if($negative) $output = '-'.$output; //dont use &minus;
		return $output;
	}

	public static function Days(?int $days, bool $negative = false): string
	{
		$output = '';
		if(!is_null($days))
		{
			if($days == 0) return sprintf('%d %s', $days, Language::Get('day', $days));

			if($days < 0)
			{
				$negative = true;
				$days = abs($days);
			}

			if($days >= 730) //2 or more years (2*365)
			{
				$years = intval(floor($days / 365)); //$years = round($days / 365, 1);
				$output = sprintf('%d %s', $years, Language::Get('year', $years)); //return sprintf('%.1f %s', $years, Language::Get('year', $years));
			}
			elseif($days >= 56) //2 or more months (2*4*7)
			{
				$months = intval(floor($days / 28)); //$months = round($days / 28, 1);
				$output = sprintf('%d %s', $months, Language::Get('month', $months)); //return sprintf('%.1f %s', $months, Language::Get('month', $months));
			}
			else
			{
				$output = sprintf('%d %s', $days, Language::Get('day', $days));
			}
		}
		if($negative) $output = '-'.$output; //dont use &minus;
		return $output;
	}
}
