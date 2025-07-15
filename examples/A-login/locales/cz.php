<?php declare(strict_types = 1);

return array(
	0 => 'OK',

	//database errors
	1048 => 'Chyba: Pokus o zápis nulového údaje!', //Write - Cannot be null
	1062 => 'Chyba: Pokus o zápis duplicitního údaje!', //Write - Duplicate entry
	1406 => 'Chyba: Pokus o zápis příliš dlouhého údaje!', //Write - Data too long
	'id' => 'Požadované ID neexistuje!',

	//pluralization
	'day' => ['dnů', 'den', 'dny', 5 => 'dní'], //0=0, 1=1, 2=2-4, 3=5-X
	'month' => ['měsíců', 'měsíc', 'měsíce', 5 => 'měsíců'],
	'year' => ['let', 'rok', 'roky', 5 => 'let'],

	//lists
	'days' => ['Neděle', 'Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek', 'Sobota'],
	'months' => ['(celý rok)', 'Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'September', 'Říjen', 'Listopad', 'Prosinec'],

	//strings
	'theme' => 'Světla / Tmavá',

	//forms
	'form_token' => 'Platnost formuláře se nepodařilo ověřit! Je třeba v nastaveních prohlížeče povolit cookies.',
	'form_valid' => 'Platnost formuláře vypršela! Údaje můžeš uložit jen ze stránky, která byla otevřena jako poslední.',

	//permissions
	'read' => 'Nemáte povolení ke čtení!',
	'write' => 'Nemáte povolení k zápisu!', 
	'lock' => 'Nemáte povolení k uzamčení!',

	//login 
	'wrong_account' => 'Tento účet se nepodařilo najít!', 
	'wrong_login' => 'Nesprávné přihlašovací údaje!',
	'wrong_password' => 'Nesprávné heslo!', 
	'password_changed' => 'Heslo bylo změněno!', 
	'password_not_changed' => 'Heslo se nepodařilo změnit!', 
	'password_not_equal' => 'Hesla se neshodují!', 
	'password_empty' => 'Heslo nemůže být prázdné!', 
	'password_reset' => 'Heslo bylo resetováno! Nové heslo je třeba změnit!', 
	'password_off' => 'Heslo bylo zrušeno! Přihlášení pomocí hesla je vypnuto!',

	//login form
	'login_caption' => 'Přihlášení uživatele',
	'login' => 'Email',
	'password' => 'Heslo',
	'login_button' => 'Přihlásit',

	//menu
	'home' => 'Domů', 
	'section' => 'Sekce', 
	'settings' => 'Nastavení', 
	'logout' => 'Odhlásit',

	//footer
	'footer' => 'Dnes je %s &diams; Stránka načtena za %.03f sekundy.',
	'footer_today_format' => 'j.n.Y l',
);
