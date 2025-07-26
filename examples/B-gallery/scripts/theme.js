'use strict';

const themeKey = 'theme'; //local storage key
let theme = 'light'; //default theme

const themeChanged = () => {
	const bodyElement = document.body;
	if (theme === 'dark') bodyElement.classList.add('dark');
	else bodyElement.classList.remove('dark');
	console.log('Theme changed to ' + theme);
}

const themeGet = () => {
	if (localStorage.getItem(themeKey))
		return localStorage.getItem(themeKey);
	else
		return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

const themeToggle = () => {
	theme = theme === 'light' ? 'dark' : 'light';
	localStorage.setItem(themeKey, theme);
	themeChanged();
}

document.addEventListener('DOMContentLoaded', () => {
	theme = themeGet();
	themeChanged();

	document
		.querySelector('#theme-toggle')
		.addEventListener('click', themeToggle)
});

window
	.matchMedia('(prefers-color-scheme: dark)')
	.addEventListener('change', ({ matches: isDark }) => {
		theme = isDark ? 'dark' : 'light'
		localStorage.removeItem(themeKey);
		themeChanged();
	})
