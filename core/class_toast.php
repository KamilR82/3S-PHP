<?php

declare(strict_types = 1);

enum Toast_Type: string
{
    case None = ''; //default
    case Error = 'error'; //red
    case Alert = 'alert'; //red
    case Warning = 'warning'; //yellow
    case Info = 'info'; //blue
    case Success = 'success'; //green
}

final class MsgBox extends Element //private
{
	public function __construct(string $message, Toast_Type $type = Toast_Type::Info)
	{
		parent::__construct('div', false, $message, class: 'msgbox'); // volanie pôvodného konštruktora
		$this->class($type->value);
		$this->input(type: 'checkbox'); //close button
	}
}

final class Toast extends Page
{
	private static ?object $toaster = null; //shared toast container of notification messages

    public function __construct(string $message, Toast_Type $type = Toast_Type::Info, bool $toast = true)
    {
		if($toast)
		{
			if(!self::$toaster) self::$toaster = Page::Body()->div(id: 'toaster'); //create container
			if(self::$toaster) //add toast
			{
				$toast = self::$toaster->div(true, $message, class: 'msgbox');
				$toast->class($type->value);
				$toast->input(type: 'checkbox'); //close button
				self::$toaster->div(false);
			}
		}
		else parent::MsgBox($message, $type);
    }
}