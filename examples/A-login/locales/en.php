<?php declare(strict_types = 1);

return array(
	0 => 'OK', //successful

	//database errors
	1048 => 'Error: Attempting to write null data!', //Write - Cannot be null
	1062 => 'Error: Attempting to write duplicate data!', //Write - Duplicate entry
	1406 => 'Error: Attempting to write data that is too long!', //Write - Data too long
	'id' => 'The requested ID does not exist!',

	//pluralization
	'day' => ['days', 'day', 'days'], //zero, single, many
	'month' => ['months', 'month', 'months'], //zero, single, many
	'year' => ['years', 'year', 'years'], //zero, single, many

	//lists
	'days' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'], //weekdays
	'months' => ['(whole year)', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'], //months

	//strings
	'theme' => 'Light / Dark',

	//forms
	'form_token' => 'Form validation error! Token missing. It is necessary to enable cookies in your browser settings.',
	'form_valid' => 'Form validation error! Token does not match. You can only save data from the page that was last opened.',

	//permissions
	'read' => 'You do not have permission to read!',
	'write' => 'You do not have permission to write!',
	'lock' => 'You do not have permission to lock!',

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
	'login_caption' => 'User login',
	'login' => 'Email', //Username
	'password' => 'Password',
	'login_button' => 'Login',
	
	//menu
	'home' => 'Home',
	'section' => 'Section',
	'settings' => 'Settings',
	'logout' => 'Logout',

	//footer
	'footer' => 'Today %s &diams; Page loaded in %.03f seconds.',
	'footer_today_format' => 'Y-m-d l',
);
