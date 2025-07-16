<?php declare(strict_types = 1);

require_once('main.php');

if(User::IsLoggedIn()) Request::Redirect('home.php');

Page::Start('Login Page'); //title

div(class: 'popup');

comment('messagebox holder');
div(id: 'top');
if(Request::IsPost()) User::Login(Request::GetParam('login'), Request::GetParam('password')); //try login
div(false);

comment('corner theme button');
div(button(id: 'theme-toggle', title: _L('theme'), width: 24, height: 24), id: 'corner');

comment('login form');
$form = Page::Form(); //post is default
$form->fieldset(true);
$form->legend(_L('login_caption'));
$form->label(_L('login'), 'login');
$form->email('login', required: true, autofocus: true);
$form->label(_L('password'), 'password');
$form->password('password', required: true);
$form->submit(_L('login_button'));

/*
// same in classic style:
comment('login form');
form(method: 'post',  enctype: 'multipart/form-data');
input(type: 'hidden', name: 'token', id: 'token', value: Request::GetToken());
fieldset(true);
legend(_L('login_caption'));
label(_L('login'), for: 'login') . input(type: 'email', name: 'login', id: 'login', pattern: Form::pattern_email, required: true, autofocus: true);
label(_L('password'), for: 'password') . input(type: 'password', name: 'password', id: 'password', pattern: Form::pattern_password, required: true);
input(type: 'submit', name: 'submit', id: 'submit', value: _L('login_button'));
*/
