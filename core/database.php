<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

//DEFAULT CONSTANTS
define('DB_SOCKED', '/run/mysqld/mysqld10.sock'); //MariaDB10 / NULL=MariaDB5
define('DB_HOST', NULL); //localhost
define('DB_PORT', NULL); //3306
define('DB_TIMEOUT', NULL); //seconds for TCP/IP connect

//SETUP
mysqli_report(App::Env('APP_DEBUG') ? MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT : MYSQLI_REPORT_OFF); //MYSQLI_REPORT_STRICT - Throw exception for errors instead of warnings (only when DEBUG mode is enabled)

class DataBase extends Singleton
{
	private static object|bool $mysqli = false;
		
	private static mysqli_result|bool $result = false;
	
	private static string $query = '';

	protected function __construct(bool $connect = true)
	{
		self::$mysqli = mysqli_init(); // returns an object on success or false on failure
		if(!is_object(self::$mysqli)) App::Die(ResponseCode::Server_Error, 'DB Error: Initialization failed!');
		if($connect) self::Connect(App::Env('DB_DATABASE'), App::Env('DB_USERNAME'), App::Env('DB_PASSWORD'), App::Env('DB_HOST'), App::Env('DB_PORT'), App::Env('DB_SOCKED'), App::Env('DB_TIMEOUT'));
	}

	public static function GetError(): string
	{
		if($string = Lang::Get(self::$mysqli->errno)) return $string;
		else
		{
			switch(self::$mysqli->errno)
			{
				case 0: //No Error
				case 1048: //Write - Cannot be null
				case 1062: //Write - Duplicate entry
				case 1406: //Write - Data too long
					return self::$mysqli->error;
				case 1054: //Unknown column
				case 1146: //Table doesn't exist
				default: //others die
					App::Die(ResponseCode::Server_Error, 'DB Error: #'.self::$mysqli->errno.' '.self::$mysqli->error);
			}
		}
	}

    public static function Connect(?string $db_database = null, ?string $db_username = null, ?string $db_password = null, ?string $db_host = null, ?int $db_port = null, ?string $db_socket = '/run/mysqld/mysqld10.sock', ?int $timeout = null): void
    {
		if(Any::IsEmpty($db_database) || Any::IsEmpty($db_username) || Any::IsEmpty($db_password)) App::Die(ResponseCode::Unauthorized, 'DB Error: Missing access data! Set database, username and password.');
		
		if(is_int($timeout)) self::$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout) or App::Die(ResponseCode::Server_Error, 'DB Error: Setting MYSQLI_OPT_CONNECT_TIMEOUT failed'); //for TCP/IP connections

		try {
			self::$mysqli->real_connect($db_host, $db_username, $db_password, $db_database, $db_port, $db_socket, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT) or App::Die(ResponseCode::Server_Error, 'DB Connect Error: #' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
		} catch (exception $e) {
			App::Die(ResponseCode::Server_Error, 'DB Connect Error: #' . $e->getCode() . ' ' . $e->getMessage());
		}

		self::$mysqli->set_charset('utf8mb4') or App::Die(ResponseCode::Server_Error, GetError());
    }

	public static function Prepare(string $query): void
	{
		self::$query = $query;
	}

	public static function Execute(?string $query = null, ?array $bind = null): mysqli_result|bool
	{
		if(Any::IsEmpty($query)) $query = self::$query; //use last query
		else self::$query = $query; //set new query

		if(!empty($bind))
		{
			if(array_is_list($bind)) //list ($query must contain the same number of question marks!)
			{
				try {
					self::$result = self::$mysqli->execute_query($query, $bind); // PHP 8.2 shortcut for prepare + bind_param + execute + get_result
				} catch(exception $e) {
					echo 'DB Query:<pre><code>'.$query.'</code></pre>';
					self::$result = false; //App::Die(ResponseCode::Server_Error, 'DB Error: #'.$e->getCode().' '.$e->getMessage()); //it's the same
				}
				return self::$result; //false on failure, successful queries such as SELECT, SHOW, DESCRIBE or EXPLAIN return a mysqli_result object, other successful queries return true
			}
			else // key-val
			{
				Arr::SortByKeyLen($bind, SORT_DESC); //sort array by key length (longer first) very important for replace order!

				//prepare data for str_replace function
				$search = array(); // keys
				$replace = array(); // values
				foreach($bind as $key => $value)
				{
					//key
					array_push($search, ':'.ltrim($key, ' :!`')); //remove special chars and add colon
					//value
					if(is_array($value))
					{
						$str = '';
						if(array_is_list($value)) // VALUES(:array) or WHERE IN(:array) or ...
						{
							foreach($value as $val)
							{
								if(Any::IsEmpty($val)) $str .= 'NULL';
								else $str .= "'".self::$mysqli->real_escape_string(strval($val))."'";
								$str .= ',';
							}
						}
						else // SET `xx`='y', `zz`=NULL or ...
						{
							foreach($value as $col => $val)
							{
								$str .= '`'.$col.'`=';
								if(Any::IsEmpty($val)) $str .= 'NULL';
								else $str .= "'".self::$mysqli->real_escape_string(strval($val))."'";
								$str .= ',';
							}
						}
						array_push($replace, rtrim($str, ',')); //remove last comma
					}
					else
					{
						if(str_starts_with($key, '!order')) array_push($replace, Any::IsEmpty($value)?'':$value.(Str::IsCapitalLetter($value)?' DESC,':' ASC,')); //special key '!order' is only for ORDER BY clausule
						else if(str_starts_with($key, '`')) array_push($replace, '`'.$value.'`'); //value with backquote (columns ...)
						else if(str_starts_with($key, '!')) array_push($replace, $value); //value without apostrophe (LIMIT ...)
						else if(Any::IsEmpty($value)) array_push($replace, 'NULL'); //NULL without apostrophe
						else array_push($replace, "'".self::$mysqli->real_escape_string(strval($value))."'"); //value with apostrophe
					}
				}
				$query = str_replace($search, $replace, $query); //replace named params with values
			}
		}

		try {
			self::$result = self::$mysqli->query($query); //execute
		} catch(Exception $e) { 
			echo 'DB Query:<pre><code>'.$query.'</code></pre>';
			self::$result = false; //App::Die(ResponseCode::Server_Error, 'DB Error: #'.$e->getCode().' '.$e->getMessage()); //it's the same
		}
		return self::$result; //false on failure, successful queries such as SELECT, SHOW, DESCRIBE or EXPLAIN return a mysqli_result object, other successful queries return true
		/*
		https://www.php.net/manual/en/class.mysqli-result.php
		$result->num_rows; // number of rows in the result set of last SELECT query
		$result->data_seek($row); // adjusts the result pointer to an arbitrary row in the result
		$result->fetch_object(); // object representing the fetched row, where each property represents the name of the result set's column, null if there are no more rows in the result set, or false on failure
		$result->fetch_row(); // enumerated array representing the fetched row, null if there are no more rows in the result set, or false on failure
		$result->fetch_array(); // array representing the fetched row, null if there are no more rows in the result set, or false on failure
		$result->fetch_assoc(); // associative array representing the fetched row, where each key in the array represents the name of one of the result set's columns, null if there are no more rows in the result set, or false on failure
		$result->free(); // frees the memory associated with the result
		*/
	}

	public static function StoreResult(): mysqli_result|false // transfers a result set from the last query
	{
		return self::$mysqli->store_result(); // returns a buffered result object or false if an error occurred
	}

	public static function AffectedRows(): int|string // If the number of rows is greater than PHP_INT_MAX, the number will be returned as a string.
	{
		return self::$mysqli->affected_rows; // number of rows affected by the last INSERT, UPDATE, REPLACE or DELETE query
	}

	public static function GetLastID(): int|string // If the number of rows is greater than PHP_INT_MAX, the number will be returned as a string.
	{
		return self::$mysqli->insert_id; // value generated for an AUTO_INCREMENT column by the last INSERT or UPDATE query. Returns 0 if the previous statement did not change an AUTO_INCREMENT value.
	}

	public static function GetResultValue(int|string $col = 0, int $row = 0): ?string
	{
		if(self::$result->num_rows && $row >= 0 && $row < self::$result->num_rows && self::$result->field_count)
		{
			self::$result->data_seek($row); //adjusts the result pointer to an arbitrary row in the result
			$resrow = is_int($col) ? self::$result->fetch_row() : self::$result->fetch_assoc(); //row to array of column numbers or column names
			if(isset($resrow[$col])) return htmlspecialchars(strval($resrow[$col]), ENT_QUOTES | ENT_XHTML);
		}
		return null;
	}

	public static function FindFirst(string $table, null|string|int $value, string $column = 'id'): ?string //looking for cell value and return row first cell (probably ID) or null
	{
		if(Any::NotEmpty($value))
		{
			$query = "SELECT * FROM :table WHERE :column = :value LIMIT 1";
			if(self::Execute($query, ['`table' => $table, '`column' => $column, 'value' => $value])) return self::GetResultValue();
		}
		return null;
	}

	public function __destruct()
	{
		self::$mysqli->close();
	}
}

DataBase::Initialize();

class_alias('DataBase', 'DB');
