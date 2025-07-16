<?php

declare(strict_types = 1);

enum MsgBox_Type: string //submitting form data method (NEVER use GET to send sensitive data!)
{
    case None = ''; //default
    case Alert = 'alert'; //red
    case Warning = 'warning'; //yellow
    case Info = 'info'; //blue
    case Success = 'success'; //green
}

class MsgBox extends Element
{
	public function __construct(string $data, MsgBox_Type $type = MsgBox_Type::None)
	{
		parent::__construct('div', false, $data, class: 'msgbox');
		$this->class($type->value);
		$this->input(type: 'checkbox'); //close button
	}
}
