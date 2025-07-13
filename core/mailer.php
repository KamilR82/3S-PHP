<?php

declare(strict_types = 1);

require_once('singleton.php');

App::Protect(__FILE__);

//DEFAULT CONSTANTS
//define('MAIL_MAILER', 'mail'); //or smtp
define('MAIL_HOST', '127.0.0.1');
define('MAIL_PORT', 2525);
define('MAIL_USERNAME', null);
define('MAIL_PASSWORD', null);
define('MAIL_FROM_ADDRESS', 'no-reply@'.constant('APP_SERVER'));
define('MAIL_FROM_NAME', constant('APP_NAME'));

class Mailer extends Singleton
{
	protected function __construct()
	{
		//ini_set(sendmail_from, App::Env('MAIL_FROM_ADDRESS'));  //force the From Address to be used
	}

	public static function Send(string $to, string $subject, string ...$strings): void
	{
		$headers = array(
			'MIME-Version' => '1.0',
			'Date' => date('r (T)'),
			'Content-type' => 'text/html; charset='.App::Env('APP_ENCODING'),
			'Content-Transfer-Encoding' => 'base64',
			'X-Mailer' => 'PHP/'.phpversion(),
			'From' => App::Env('MAIL_FROM_ADDRESS')
		);
		
		$text = '';
		foreach($strings as $piece) $text .= $piece."\r\n";
		$message = wordwrap($text, 70, "\r\n");
		$message .= "\r\n"; //extra blank line is needed

		$subject = base64_encode($subject); //Content-Transfer-Encoding: base64
		$message = chunk_split(base64_encode($message)); //format $data using RFC 2045 semantics

		if(!mail($to, $subject, $message, $headers)) echo error_get_last()['message'] ?? 'PHP::Mail Unknown Error';
		//if(!mail($to, $subject, $message, $headers)) Logger::Log('MAIL', $to, error_get_last()['message'] ?? 'PHP::Mail Unknown Error');
	}

	public function __destruct()
	{
		//ini_restore(sendmail_from);
	}
}

//Mailer::Send('k.rumanovsky@gmail.com', 'test', 'mail test');
