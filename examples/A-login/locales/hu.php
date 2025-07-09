<?php declare(strict_types = 1);

return array(
	0 => 'OK',

	//database errors
	1048 => 'Hiba: Null adat írására teszünk kísérletet!', //Write - Cannot be null
	1062 => 'Hiba: Duplikált adat írására teszünk kísérletet!', //Write - Duplicate entry
	1406 => 'Hiba: Túl hosszú adat írására teszünk kísérletet!', //Write - Data too long
	'id' => 'A kért azonosító nem létezik!',

	//pluralization
	'day' => ['nap'], //zero, single, many
	'month' => ['hónap'], //zero, single, many
	'year' => ['év'], //zero, single, many

	//lists
	'days' => ['Vasárnap', 'Hétfő', 'Kedd', 'Szerda', 'Csütörtök', 'Péntek', 'Szombat'],
	'months' => ['(egész évben)', 'Január', 'Február', 'Március', 'Április', 'Május', 'Június', 'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'],

	//forms
	//'form_token' => '',
	//'form_valid' => '',

	//permissions
	'read' => 'Nincs jogosultsága olvasni!',
	'write' => 'Nincs jogosultsága írásra!',
	'lock' => 'Nincs jogosultsága zárolásra!',
/*
	//login
	'wrong_account' => 'Account not found!',
	'wrong_login' => 'Wrong login or password!',
	'wrong_password' => 'Wrong password!',
	'password_changed' => 'Password has been changed!',
	'password_not_changed' => 'Password could not be changed!',
	'password_not_equal' => 'The passwords do not match!',
	'password_empty' => 'Password cannot be empty!',
	'password_reset' => 'The password has been reset! The new password must be changed!',
	'password_off' => 'Password has been canceled! Login with password is disabled!',

	//login form
	'login_caption' => '',
	'login' => '',
	'password' => '',
	'login_button' => '',
*/
	//menu
	'home' => 'Kezdőlap',
	'section' => 'Szakasz',
	'settings' => 'Beállítások',
	'logout' => 'Kijelentkezés',

	//strings
	'footer' => 'Ma %s &diams; Az oldal betöltése %.03f másodperc alatt történt.',
	'footer_today_format' => 'Y-m-d l',
);
