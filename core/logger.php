<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

//DEFAULT CONSTANTS
define('LOG_DB', constant('APP_DEBUG')); //log to database
define('LOG_FILE', constant('APP_DEBUG')); //log to files
define('LOG_EMPTY', true); //log only with more parameters
define('LOG_USER_UNKNOWN', true); //log unknown users (not logged in users)
define('LOG_USER_REGISTERED', true); //log known users (logged in users)
define('LOG_FULL_POST', false); //log all post params (inline)

class Logger extends Singleton
{
	const folder = 'log'; //or '3s-log'
	const extension = '.log';

	private static string $directory = DIR_ROOT.DIRECTORY_SEPARATOR.self::folder; //or DIR_WORK.DIRECTORY_SEPARATOR.self::folder
	private static bool $hourly = false; //separate log for every hour (false = every day)
	private static int $delete_days = 0; //0 = dont delete

	protected function __construct()
	{
		if(!is_dir(self::$directory)) if(!@mkdir(self::$directory, 0664, true)) App::Die(ResponseCode::Forbidden, 'Forbidden! Failed to create directory: '.self::$directory); //create directory/folder uploads
		
		set_error_handler(self::class.'::ErrorHandler');
	}

    public static function ErrorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
		if(!is_dir(self::$directory)) return false;
        //if(!(error_reporting() & $errno)) return false; //this error code is not included in error_reporting

		$type = match($errno)
		{
			E_ERROR, E_USER_ERROR => 'ERROR',
			E_WARNING, E_USER_WARNING => 'WARNING',
			E_NOTICE, E_USER_NOTICE => 'NOTICE',
			E_DEPRECATED, E_USER_DEPRECATED => 'DEPRECATED',
			default => 'UNKNOWN',
		};

        $filename = basename($errfile);

		$text = date('Y-m-d H:i:s');
		$text .= "\t[".Request::GetClientIP().']';
		if(User::GetUserID()) $text .= "\t(".User::GetUserID().') '.User::GetFullName();
		$text .= "\t[{$type}] {$filename}:{$errline}\t{$errstr}";
		$text .= PHP_EOL;

		$log_file = self::$directory.'/err_'.date(self::$hourly ? 'Y-m-d_H' : 'Y-m-d').self::extension;
		file_put_contents($log_file, $text, FILE_APPEND | LOCK_EX);

        return false; //true = don't execute PHP internal error handler
    }

	public static function Log(string $type, ?string ...$strings): void
	{
		if(!is_dir(self::$directory)) return;
		if(!App::Env('LOG_EMPTY') && count($strings) && Any::IsEmpty($strings[0])) return; //dont log random hits
		if(!App::Env('LOG_USER_UNKNOWN') && !User::IsLoggedIn()) return; //dont log unknown users (not logged in)
		if(!App::Env('LOG_USER_REGISTERED') && User::IsLoggedIn()) return; //dont log logged in users
		if(!App::Env('APP_DEBUG') && User::IsLoggedIn() && User::GetPermission(Permits::Admin)) return; //dont log admin (for security reasons)

		if(App::Env('LOG_FILE'))
		{
			$text = date('Y-m-d H:i:s'); //timestamp
			$text .= "\t[".Request::GetClientIP().']'; //client ip
			$text .= "\t(".(User::IsLoggedIn()?User::GetUserID():'-').')'; //user id
			$text .= "\t[{$type}]";
			foreach($strings as $piece) if(Any::NotEmpty($piece)) $text .= "\t".$piece;
			$text .= PHP_EOL;

			$log_file = self::$directory.'/log_'.date(self::$hourly ? 'Y-m-d_H' : 'Y-m-d').self::extension;
			file_put_contents($log_file, $text, FILE_APPEND | LOCK_EX);
		}

		if(App::Env('LOG_DB'))
		{
			//log to database
		}
	}

	public static function Request(): void
	{
		self::Log(Request::GetMethod(), Request::GetURI(), App::Env('LOG_FULL_POST') && Request::IsPost() ? serialize($_POST) : null); //log request
	}

	public function __destruct()
	{
		if(self::$delete_days)
		{
			$now = time();
			$files = glob(self::$directory.'/*'.self::extension);
			foreach($files as $file)
				if(is_file($file))
					if($now - filemtime($file) >= 86400 * self::$delete_days) //86400 = 1 day
						unlink($file); //delete
		}
	}
}

Logger::Initialize();

class_alias('Logger', 'Log');
