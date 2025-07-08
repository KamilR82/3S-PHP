<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

enum PermitLevel: int
{
	use EnumToArray;

    case None = 0;
    case Read = 1 << 0; //1
    case Write = 1 << 1; //2
    case Lock = 1 << 2; //4

    public function column(): string //DB column name
	{
        return match($this)
		{
			static::Read => 'read',
			static::Write => 'write',
			static::Lock => 'lock',
        };
    }
}

enum Permits: string //after change save to DB permission SETs !!!
{
	use EnumToArray;

    case Employees = 'E';
    case Companies = 'C';
    case Production = 'P';
    case Machines = 'M';
    case Orders = 'O';
    case Sales = 'S';
    case Quality = 'Q';
    case Documents = 'D';
    case Finances = 'F';
    case Wages = 'W';
    case Admin = 'A';
}

class Userlogin extends Singleton
{
	private static ?int $id = null;
	private static string $fullname = '';

	protected function __construct()
	{
		if(!App::IsSession()) App::Die(ResponseCode::Server_Error, 'Error: Session ID missing! Cookies disabled?');

		//get user by session
		$query = "SELECT `id`, TRIM(CONCAT_WS(' ', `first`, `last`)) AS `fullname` FROM `user` WHERE session = ? LIMIT 1";
		if(!$result = DataBase::Execute($query, [App::GetSession()])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
		if($result->num_rows)
		{
			self::$id = intval(DataBase::GetResultValue('id'));
			self::$fullname = DataBase::GetResultValue('fullname');

			//save last activity
			$query = "UPDATE `user` SET `activity` = NOW() WHERE `id` = ? LIMIT 1";
			if(!DataBase::Execute($query, [self::$id])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
		}
	}

	public static function IsLoggedIn(): bool
	{
		return !is_null(self::$id);
	}

	public static function Login(?string $login, ?string $password): void
	{
		self::Logout();
		
		if(!App::IsSession()) App::Die(ResponseCode::Server_Error, 'Error: Session ID missing! Cookies disabled?');
		
		if(Str::IsEmpty($login) || Str::IsEmpty($password)) return;

		$query = "SELECT `id`, `password` FROM `user` WHERE `login` LIKE ? LIMIT 1";
		if(!$result = DataBase::Execute($query, [$login])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
		if($result->num_rows)
		{
			$id = DataBase::GetResultValue('id');
			$hash = DataBase::GetResultValue('password');
			if(Str::NotEmpty($hash))
			{
				if(password_verify($password, $hash))
				{
					$query = "UPDATE `user` SET `activity` = NOW(), `session` = ? WHERE `id` = ? LIMIT 1";
					if(!DataBase::Execute($query, [App::GetSession(), $id])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
					Request::Redirect(); //user data load after redirect
				}
			}
		}
		//somethings wrong
		sleep(1); //wait a second
		echo HTML::MsgBox(Language::Get('wrong_login'), MsgBoxType::Warning);
	}

	public static function Password(string $old, string $new, string $retry): bool
	{
		if(Str::NotEmpty($old) && Str::NotEmpty($new) && Str::NotEmpty($retry))
		{
			$query = "SELECT `password` FROM `user` WHERE `id` = ? LIMIT 1";
			if(!$result = DataBase::Execute($query, [self::$id])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
			if($result->num_rows)
			{
				$hash = DataBase::GetResultValue();
				if(password_verify($old, $hash))
				{
					if(strcmp($new, $retry) == 0) //equal?
					{
						$hash = password_hash($new, PASSWORD_DEFAULT);
						$query = "UPDATE `user` SET `password` = ? WHERE `id` = ? LIMIT 1";
						if(!DataBase::Execute($query, [$hash, self::$id])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
						elseif(DataBase::AffectedRows())
						{
							echo HTML::MsgBox(Language::Get('password_changed'), MsgBoxType::Success);
							return true;
						}
						else echo HTML::MsgBox(Language::Get('password_not_changed'), MsgBoxType::Alert);
					}
					else echo HTML::MsgBox(Language::Get('password_not_equal'), MsgBoxType::Warning);					
				}
				else echo HTML::MsgBox(Language::Get('wrong_password'), MsgBoxType::Alert);
			}
			else echo HTML::MsgBox(Language::Get('wrong_account'), MsgBoxType::Warning);
		}
		else echo HTML::MsgBox(Language::Get('password_empty'), MsgBoxType::Alert);

		return false;
	}

	public static function PasswordReset(?int $id = null): bool
	{
		if(self::GetPermission(Permits::Admin) & PermitLevel::Write->value)
		{
			if(is_null($id)) $id = self::$id;

			$query = "SELECT CONV(karta, 16, 10) FROM `user` WHERE id = ? LIMIT 1";
			if(!$result = DataBase::Execute($query, [self::$id])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
			if($result->num_rows)
			{
				$karta = DataBase::GetResultValue();
				$hash = password_hash($karta, PASSWORD_DEFAULT);
				$query = "UPDATE `user` SET `password` = ? WHERE `id` = ? LIMIT 1";
				if(!DataBase::Execute($query, [$hash, $id])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
				elseif(DataBase::AffectedRows())
				{
					echo HTML::MsgBox(Language::Get('password_reset'), MsgBoxType::Success);
					return true;
				}
				else echo HTML::MsgBox(Language::Get('password_not_changed'), MsgBoxType::Alert);
			}
		}
		else echo HTML::MsgBox(Language::Get('write'), MsgBoxType::Alert);

		return false;
	}

	public static function PasswordRemove(?int $id = null): bool
	{
		if(self::GetPermission(Permits::Admin) & PermitLevel::Write->value)
		{
			if(is_null($id)) $id = self::$id;

			$query = "UPDATE `user` SET `password` = NULL WHERE `id` = ? LIMIT 1";
			if(!DataBase::Execute($query, [$id])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
			elseif(DataBase::AffectedRows())
			{
				echo HTML::MsgBox(Language::Get('password_off'), MsgBoxType::Success);
				return true;
			}
			else echo HTML::MsgBox(Language::Get('password_not_changed'), MsgBoxType::Alert);
		}
		else echo HTML::MsgBox(Language::Get('write'), MsgBoxType::Alert);

		return false;
	}

	public static function Logout(): void
	{
		if(self::IsLoggedIn())
		{
			$query = "UPDATE `user` SET `activity` = NOW(), `session` = NULL WHERE `id` = ? LIMIT 1";
			if(!DataBase::Execute($query, [self::$id])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
			self::$id = null;
			self::$fullname = '';

			App::RemSession();
		}
	}

	public static function GetUserID(): ?int
	{
		return self::$id;
	}

	public static function GetFullName(?int $id = null): ?string
	{
		if(Num::IsInteger($id)) //other user
		{
			$query = "SELECT TRIM(CONCAT_WS(' ', `first`, `last`)) AS fullname FROM `user` WHERE `id` = ? LIMIT 1";
			if(!$result = DataBase::Execute($query, [$id])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
			if($result->num_rows) return DataBase::GetResultValue();
			else return null;
		}
		else return self::$fullname;
	}

	//Permissions
	public static function GetPermission(Permits $permission, ?int $id = null): int
	{
		if(is_null($id)) $id = self::$id;

		$query = "SELECT IF(FIND_IN_SET(:permission, `:read`) > 0, :read_val, 0) + IF(FIND_IN_SET(:permission, `:write`) > 0, :write_val, 0) + IF(FIND_IN_SET(:permission, `:lock`) > 0, :lock_val, 0) FROM `user` WHERE `id` = :id LIMIT 1";
		if(!$result = DataBase::Execute($query, ['id' => $id, 
			'!read' => PermitLevel::Read->column(), '!read_val' => PermitLevel::Read->value, 
			'!write' => PermitLevel::Write->column(), '!write_val' => PermitLevel::Write->value, 
			'!lock' => PermitLevel::Lock->column(), '!lock_val' => PermitLevel::Lock->value, 
			'permission' => $permission->value])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
		if($result->num_rows) return intval(DataBase::GetResultValue());

		return 0;
	}

	/*
	bitwise cheat sheet:
	num = (num >> i) & 1 //Get bit
	num = num | (1 << i) //Set bit
	num = num & ~(1 << i) //Clear bit
	num = num ^ (1 << i) //Toggle bit
	*/
	public static function TogglePermission(Permits $permission, PermitLevel $level = PermitLevel::None, ?int $id = null): bool
	{
		if(is_null($id)) $id = self::$id;
		if($level->value) // not PermitLevel::None
		{
			$actual = self::GetPermission($permission, $id);
			foreach(PermitLevel::array() as $name => $value) //string => int
			{
				if($value == 0) continue;//skip PermitLevel::None

				if($level->value & $value) //is permission wanted?
				{
					if($actual & $value) //is permission enabled?
					{
						$query = "UPDATE `user` SET `:column` = `:column` & ~(1 << (FIND_IN_SET(:permission, `:column`) - 1)) WHERE `id`=:id LIMIT 1"; //remove from SET
						if(!DataBase::Execute($query, ['id' => $id, '!column' => constant("PermitLevel::{$name}")->column(), 'permission' => $permission->value])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
					}
					else
					{
						$query = "UPDATE `user` SET `:column` = CONCAT_WS(',', `:column`, :permission) WHERE `id`=:id LIMIT 1"; //add to SET
						if(!DataBase::Execute($query, ['id' => $id, '!column' => constant("PermitLevel::{$name}")->column(), 'permission' => $permission->value])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
					}
				}
			}
			if(DataBase::AffectedRows()) return true;
		}
		else //remove all levels
		{
			foreach(PermitLevel::array() as $name => $value) //string => int
			{
				if($value == 0) continue;//skip PermitLevel::None

				$query = "UPDATE `user` SET `:column` = `:column` & ~(1 << (FIND_IN_SET(:permission, `:column`) - 1)) WHERE `id` = :id LIMIT 1"; //remove from SET
				if(!DataBase::Execute($query, ['id' => $id, '!column' => constant("PermitLevel::{$name}")->column(), 'permission' => $permission->value])) echo HTML::MsgBox(DataBase::GetError(), MsgBoxType::Alert);
			}
			return true;
		}
		return false;
	}

	public static function MenuFilter(array &$menu): array
	{
		foreach($menu as $key => &$item)
		{
			if(is_array($item))
			{
				$p = null; //Permits
				$pl = null; //PermitLevel
				foreach($item as $chunk)
				{
					if(is_array($chunk)) $item = self::MenuFilter($chunk); //has submenu -> recursive filter submenu
					else if(is_object($chunk)) //object
					{
						if($chunk instanceof Permits) $p = $chunk;
						if($chunk instanceof PermitLevel) $pl = $chunk;
					}
				}
				if($p)
				{
					if($pl)
					{
						if(!(self::GetPermission($p) & $pl->value)) unset($menu[$key]);
					}
					else if(!self::GetPermission($p)) unset($menu[$key]);
				}
			}
		}
		return $menu;
	}
}

Userlogin::Initialize();

class_alias('Userlogin', 'User');
