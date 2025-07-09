<?php declare(strict_types = 1);

return array(
	0 => 'OK',

	//database errors
	1048 => 'Chyba: Pokus o zápis nulového údaju!', //Write - Cannot be null
	1062 => 'Chyba: Pokus o zápis duplicitného údaju!', //Write - Duplicate entry
	1406 => 'Chyba: Pokus o zápis príliš dlhého údaju!', //Write - Data too long
	'id' => 'Požadované ID neexistuje!',

	//pluralization
	'day' => ['dní', 'deň', 'dni', 5 => 'dní'], //0=0, 1=1, 2=2-4, 3=5-X
	'month' => ['mesiacov', 'mesiac', 'mesiace', 5 => 'mesiacov'],
	'year' => ['rokov', 'rok', 'roky', 5 => 'rokov'],

	//lists
	'days' => ['Nedeľa', 'Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota'],
	'months' => ['(celý rok)', 'Január', 'Február', 'Marec', 'Apríl', 'Máj', 'Jún', 'Júl', 'August', 'September', 'Október', 'November', 'December'],
	
	//forms
	'form_token' => 'Platnosť formulára sa nepodarilo overiť! Je potrebné v nastaveniach prehliadača povoliť cookies.',
	'form_valid' => 'Platnosť formulára vypršala! Údaje môžeš uložiť len zo stránky, ktorá bola otvorená ako posledná.',

	//permissions
	'read' => 'Nemáte povolenie na čítanie!',
	'write' => 'Nemáte povolenie na zápis!',
	'lock' => 'Nemáte povolenie na uzamknutie!',

	//login
	'wrong_account' => 'Tento účet sa nepodarilo nájsť!',
	'wrong_login' => 'Nesprávne prihlasovacie údaje!',
	'wrong_password' => 'Nesprávne heslo!',
	'password_changed' => 'Heslo bolo zmenené!',
	'password_not_changed' => 'Heslo sa nepodarilo zmeniť!',
	'password_not_equal' => 'Heslá sa nezhodujú!',
	'password_empty' => 'Heslo nemôže byť prázdne!',
	'password_reset' => 'Heslo bolo resetované! Nové heslo treba zmeniť!',
	'password_off' => 'Heslo bolo zrušené! Prihlásenie pomocou hesla je vypnuté!',

	//login form
	'login_caption' => 'Prihlásenie používateľa',
	'login' => 'Email',
	'password' => 'Heslo',
	'login_button' => 'Prihlásiť',

	//menu
	'home' => 'Domov',
	'section' => 'Sekcia',
	'settings' => 'Nastavenia',
	'logout' => 'Odhlásiť',

	//strings
	'footer' => 'Dnes je %s &diams; Stránka načítaná za %.03f sekundy.',
	'footer_today_format' => 'j.n.Y l',
);
