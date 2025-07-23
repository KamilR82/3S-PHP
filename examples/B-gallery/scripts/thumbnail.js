'use strict';

window.onload = async () => {

	//helper functions

	const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms)); //helper function for pause

	const loadImg = (btn) => { //helper function for load image

		const newSrc = btn.getAttribute('data-src');
		if (!newSrc) return reject(new Error('Attribute `data-src` not found.'));

		const childImg = btn.getElementsByTagName('img')[0];
		if (!childImg) return reject(new Error('Child `img` not found.'));

		return new Promise((resolve, reject) => {
			childImg.onload = () => {
				resolve();
			};
			childImg.onerror = () => {
				reject(new Error(`Failed to load image: ${newSrc}`));
			};
			childImg.src = 'thumbnail.php?path=' + newSrc;
		});
	};

	//run thumbnail loader

	const parent = document.getElementById('pictures');
	if (!parent) {
		console.log('No element `pictures` found.');
		return;
	}

	const btns = parent.querySelectorAll('button');
	if (btns.length === 0) {
		console.log('No images found.');
		return;
	}

	const startDate = new Date();
	console.log(`Found ${btns.length} images. Thumbnail loader started at ${startDate.toLocaleTimeString()}`);

	//loop
	for (const btn of btns) {
		try {
			const prevBorder = btn.style.border;
			btn.style.border = '1px solid green';
			await wait(20); //wait
			await loadImg(btn); //try to load image
			await wait(20); //wait
			btn.style.border = prevBorder;
		} catch (error) {
			btn.style.border = '1px solid red';
			console.error(error.message);
		}
	}

	const endDate = new Date();
	const duration = (endDate - startDate) / 1000;
	console.log(`Thumbnail loader end at ${endDate.toLocaleTimeString()}. Duration: ${duration} seconds.`);
}

//modal zoom / slideshow

let modal;
let modalImg;
let actualBtn;

document.addEventListener('DOMContentLoaded', () => {
	modal = document.getElementById('modal');
	modalImg = document.getElementById('slideshow');
});

document.addEventListener('keydown', function (event) {
	if (event.key === 'Escape' || event.key === 'Backspace') closeModal();
});

window.addEventListener('popstate', function (event) { /* history back */
	closeModal();
});

function openModal(btn) {
	actualBtn = btn;
	const newSrc = btn.getAttribute('data-src');
	if (!newSrc) return;

	history.pushState({ modalOpen: true }, 'Image Modal Open', '#modal');

	modalImg.src = newSrc;
	modal.style.display = 'flex';
}

function closeModal() {
	modal.style.display = 'none';
	modalImg.src = '';

	if (history.state && history.state.modalOpen) history.back();
}

function imgFirst() {
	const parent = document.getElementById('pictures');
	const firstSibling = parent.firstElementChild;
	if (firstSibling) {
		modalImg.src = '';
		actualBtn = firstSibling;
		const newSrc = actualBtn.getAttribute('data-src');
		if (newSrc) modalImg.src = newSrc;
	}
}

function imgPrev() {
	const prevSibling = actualBtn.previousElementSibling;
	if (prevSibling) {
		modalImg.src = '';
		actualBtn = prevSibling;
		const newSrc = actualBtn.getAttribute('data-src');
		if (newSrc) modalImg.src = newSrc;
	}
}

function imgNext() {
	const nextSibling = actualBtn.nextElementSibling;
	if (nextSibling) {
		modalImg.src = '';
		actualBtn = nextSibling;
		const newSrc = actualBtn.getAttribute('data-src');
		if (newSrc) modalImg.src = newSrc;
	}
}

function imgLast() {
	const parent = document.getElementById('pictures');
	const lastSibling = parent.lastElementChild;
	if (lastSibling) {
		modalImg.src = '';
		actualBtn = lastSibling;
		const newSrc = actualBtn.getAttribute('data-src');
		if (newSrc) modalImg.src = newSrc;
	}
}
/*
function getElementIndex(element) {
    if (!element || !element.parentElement) return -1;
    const parent = element.parentElement;
    const children = Array.from(parent.children);
    return children.indexOf(element);
}

function getElementCount(element) {
    if (!element || !element.parentElement) return -1;
    const parent = element.parentElement;
    return parent.childNode.lengths;
}
*/