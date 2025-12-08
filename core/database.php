<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

//DEFAULT CONSTANTS
define('DB_SOCKED', NULL);
define('DB_HOST', NULL); //localhost
define('DB_PORT', NULL); //3306
define('DB_TIMEOUT', NULL); //seconds for TCP/IP connect

//SETUP
mysqli_report(App::Env('APP_DEBUG') ? MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT : MYSQLI_REPORT_OFF); //MYSQLI_REPORT_STRICT - Throw exception for errors instead of warnings (only when DEBUG mode is enabled)

class DataBase extends Singleton
{
	private static object|bool $mysqli = false;
		
	private static mysqli_result|bool $result = false;
	
	private static string $query_semi = ''; //for bind params
	private static string $query_full = ''; //for execute

	protected function __construct(bool $connect = true)
	{
		self::$mysqli = mysqli_init(); // returns an object on success or false on failure
		if(!is_object(self::$mysqli)) App::Die(ResponseCode::Server_Error, 'DB Error: Initialization failed!');
		if($connect) self::Connect(App::Env('DB_DATABASE'), App::Env('DB_USERNAME'), App::Env('DB_PASSWORD'), App::Env('DB_HOST'), App::Env('DB_PORT'), App::Env('DB_SOCKED'), App::Env('DB_TIMEOUT'));
	}

	public static function GetError(): string
	{
		if($string = Language::Get(self::$mysqli->errno)) return $string;
		else
		{
			switch(self::$mysqli->errno)
			{
				case 0: //No Error
				case 1048: //Write - Cannot be null
				case 1062: //Write - Duplicate entry
				case 1406: //Write - Data too long
					break;
				case 1054: //Unknown column
				case 1064: //Syntax error
				case 1146: //Table doesn't exist
				default: //others die
					App::Die(ResponseCode::Server_Error, 'DB Error: #'.self::$mysqli->errno.' '.self::$mysqli->error);
			}
			return self::$mysqli->error;
		}
	}

    public static function Connect(?string $db_database = null, ?string $db_username = null, ?string $db_password = null, ?string $db_host = null, ?int $db_port = null, ?string $db_socket = null, ?int $timeout = null): void
    {
		if(Str::IsEmpty($db_database) || Str::IsEmpty($db_username) || Str::IsEmpty($db_password)) App::Die(ResponseCode::Unauthorized, 'DB Error: Missing access data! Set database, username and password.');

		try {
			if(is_int($timeout)) self::$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout) or App::Die(ResponseCode::Server_Error, 'DB Error: Setting MYSQLI_OPT_CONNECT_TIMEOUT failed'); //for TCP/IP connections
			self::$mysqli->real_connect($db_host, $db_username, $db_password, $db_database, $db_port, $db_socket, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT) or App::Die(ResponseCode::Server_Error, 'DB Connect Error: #' . mysqli_connect_errno() . ' ' . mysqli_connect_error());
			self::$mysqli->set_charset('utf8mb4');
		} catch (exception $e) {
			App::Die(ResponseCode::Server_Error, 'DB Connect Error: #' . $e->getCode() . ' ' . $e->getMessage());
		}
    }

	private static function Convert(mixed $value, bool $escaped = true): ?string //value to db string
	{
		return match(true)
		{
			is_bool($value) => $value ? 'TRUE' : 'FALSE',
			is_null($value) => 'NULL', //Any::IsEmpty($value) => 'NULL',
			is_int($value), is_float($value) => strval($value),
			is_string($value) => $escaped ? "'".self::$mysqli->real_escape_string(strval($value))."'" : self::$mysqli->real_escape_string(strval($value)), 
			is_array($value) => self::Serialize($value), //multidimensional array
			default => '?', //unknown
		};
	}

	private static function Serialize(array $data): string //array to db string (comma separated)
	{
		$str = '';
		if(array_is_list($data)) // VALUES(:array) or WHERE IN(:array) or ...
		{
			foreach($data as $value) $str .= self::Convert($value) . ', ';
		}
		else // SET `xx`='y', `zz`=NULL or ...
		{
			foreach($data as $column => $value) $str .= '`'.$column.'`='.self::Convert($value) . ', ';
		}
		return rtrim($str, ', '); //remove last comma
	}

	private static function Implode(array $data): string //array to db string (space separated)
	{
		$query = '';
		foreach($data as $value)
		{
			if(is_array($value)) $query .= self::Serialize($value);
			else $query .= self::Convert($value, false); //don't escape
			$query .= ' ';
		}
		return $query;
	}

	public static function Query(mixed ...$data): mysqli_result|bool
	{
		self::$query_full = self::Implode($data); //set new query
		return self::Run();
	}

	public static function Prepare(string $query): void
	{
		self::$query_semi = $query;
	}

	public static function Bind(array $bind): mysqli_result|bool
	{
		return self::Execute(null, $bind);
	}

	public static function Execute(?string $query = null, ?array $bind = null): mysqli_result|bool
	{
		if(Str::IsEmpty($query)) $query = self::$query_semi; //use last query
		else self::$query_semi = $query; //set new query

		if(!empty($bind))
		{
			if(array_is_list($bind)) return self::Run($bind); //list ($query must contain the same number of question marks!)
			else // key-val
			{
				Arr::SortByKeyLen($bind, SORT_DESC); //longer keys first - very important for replace order!

				//prepare data for str_replace function
				$search = array(); // keys
				$replace = array(); // values
				foreach($bind as $key => $value)
				{
					//key
					array_push($search, ':'.ltrim($key, ' :!`')); //remove special chars and add colon
					//value
					if(is_array($value)) array_push($replace, self::Serialize($value)); //parse array
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
		self::$query_full = $query;
		return self::Run();
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

	private static function Run(?array $bind = null): mysqli_result|bool
	{
		try {
			if(is_null($bind)) self::$result = self::$mysqli->query(self::$query_full); //execute
			else self::$result = self::$mysqli->execute_query(self::$query_semi, $bind); // PHP 8.2 shortcut for prepare + bind_param + execute + get_result
		} catch(Exception $e) { 
			self::Dump(!is_null($bind));
			self::$result = false; //App::Die(ResponseCode::Server_Error, 'DB Error: #'.$e->getCode().' '.$e->getMessage()); //it's the same
		}
		return self::$result; //false on failure, successful queries such as SELECT, SHOW, DESCRIBE or EXPLAIN return a mysqli_result object, other successful queries return true
	}

	public static function Dump(bool $semi = false): void
	{
		echo 'DB Query:<pre><code>'.($semi ? self::$query_semi : self::$query_full).'</code></pre>';
	}

	public static function GetLastQuery(bool $semi = false): string
	{
		return $semi ? self::$query_semi : self::$query_full; // returns last query
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

	public static function FindFirst(string $table, null|string|int|float $value, string $column = 'id'): ?string //looking for cell value and return row first cell (probably ID) or null
	{
		if(Any::NotEmpty($value)) if(self::Query("SELECT * FROM `{$table}` WHERE", [$column => $value], "LIMIT 1")) return self::GetResultValue();
		return null;
	}

	public function __destruct()
	{
		self::$mysqli->close();
	}
}

DataBase::Initialize();

class_alias('DataBase', 'DB');
