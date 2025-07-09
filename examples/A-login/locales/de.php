<?php declare(strict_types = 1);

return array(
	0 => 'OK',

	//database errors
	1048 => 'Fehler: Versuch, Nulldaten zu schreiben!', //Write - Cannot be null
	1062 => 'Fehler: Versuch, doppelte Daten zu schreiben!', //Write - Duplicate entry
	1406 => 'Fehler: Versuch, Daten zu schreiben, die zu lang sind!', //Write - Data too long
	'id' => 'Die angeforderte ID existiert nicht!',

	//pluralization
	'day' => ['Tage', 'Tag', 'Tage'], //zero, single, many
	'month' => ['Monate', 'Monat', 'Monate'], //zero, single, many
	'year' => ['Jahre', 'Jahr', 'Jahre'], //zero, single, many

	//lists
	'days' => ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
	'months' => ['(ganzes Jahr)', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],

	//forms
	//'form_token' => '',
	//'form_valid' => '',

	//permissions
	'read' => 'Sie haben keine Leseberechtigung!',
	'write' => 'Sie haben keine Schreibberechtigung!',
	'lock' => 'Sie haben keine Sperrberechtigung!',

	//login
	'wrong_account' => 'Konto nicht gefunden!',
	'wrong_login' => 'Falscher Benutzername oder falsches Passwort!',
	'wrong_password' => 'Falsches Passwort!',
	'password_changed' => 'Passwort wurde geändert!',
	'password_not_changed' => 'Passwort konnte nicht geändert werden!',
	'password_not_equal' => 'Die Passwörter stimmen nicht überein!',
	'password_empty' => 'Passwort darf nicht leer sein!',
	'password_reset' => 'Das Passwort wurde zurückgesetzt! Das neue Passwort muss geändert werden!',
	'password_off' => 'Passwort wurde abgebrochen! Die Anmeldung mit Passwort ist deaktiviert!',

	//login form
	'login_caption' => 'Benutzeranmeldung',
	'login' => 'Email',
	'password' => 'Passwort',
	'login_button' => 'Einloggen',

	//menu
	'home' => 'Startseite',
	'section' => 'Bereich',
	'settings' => 'Einstellungen',
	'logout' => 'Abmelden',

	//strings
	'footer' => 'Heute %s &diams; Seite in %.03f Sekunden geladen.',
	'footer_today_format' => 'j.n.Y l',
);
