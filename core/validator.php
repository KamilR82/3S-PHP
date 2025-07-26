<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

class Validator extends Singleton
{
	private static string|false $md5 = false;

	protected function __construct()
	{
		self::$md5 = md5_file(Request::GetFileName());
		if(self::$md5)
		{
			if(App::Env('APP_DEBUG')) //write MD5
			{
				//DB querry examples:

				//option 1: direct method
				//$query = "INSERT INTO scripts (filename, md5) VALUES('$filename', '$md5') ON DUPLICATE KEY UPDATE md5 = '$md5'";
				//if(!DataBase::Execute($query)) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
				
				//option 2: bind method (number of question marks must be equal to number of bind params)
				//use only for values, counterwise use named params metod with !key = value
				//$query = "INSERT INTO scripts (filename, md5) VALUES(?, ?) ON DUPLICATE KEY UPDATE md5 = ?";
				//if(!DataBase::Execute($query, [$filename, $md5, $md5])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
				
				//option 3: named params method
				$query = "INSERT INTO scripts (filename, md5) VALUES(:filename, :md5) ON DUPLICATE KEY UPDATE md5 = :md5";
				if(!DataBase::Execute($query, ['filename' => Request::GetFileName(), 'md5' => self::$md5])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
				
				//option 4: named params method with array
				//$query = "INSERT INTO scripts (filename, md5) VALUES(:values) ON DUPLICATE KEY UPDATE md5 = :md5";
				//if(!DataBase::Execute($query, ['values' => [$filename, $md5], 'md5' => $md5])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
			}
			else //check MD5
			{
				$query = "SELECT md5 FROM scripts WHERE filename = ? AND md5 = ? LIMIT 1";
				if(!$result = DataBase::Execute($query, [Request::GetFileName(), self::$md5])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
				if($result->num_rows == 0) App::Die(ResponseCode::Unauthorized, 'Unauthorized! This script file not allowed to run or script CRC failed.');
			}
		}
		else App::Die(ResponseCode::Server_Error, 'Script Validation Error!');
	}
}

Validator::Initialize();
