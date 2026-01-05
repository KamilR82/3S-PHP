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

final class InlineToast extends Element //private
{
	public function __construct(string $message, Toast_Type $type = Toast_Type::Info)
	{
		parent::__construct('div', false, $message, class: 'toast'); //calling the original element constructor
		$this->class($type->value);
		$this->input(type: 'checkbox'); //close 'X' button
	}
}

final class Toast extends Page //Page::Toast($message);
{
	private static ?object $toaster = null; //shared container

    public function __construct(string $message, Toast_Type $type = Toast_Type::Info, bool $toasting = true)
    {
		if($toasting)
		{
			if(!self::$toaster) self::$toaster = Page::Body()->div(id: 'toaster'); //create container
			if(self::$toaster) self::$toaster->open(new InlineToast($message, $type)); //create toast and move into toaster
		}
		else parent::InlineToast($message, $type); //toast as inline message
    }
}
