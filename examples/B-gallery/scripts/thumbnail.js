'use strict';

const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms)); //helper function for pause

//run thumbnail loader
window.onload = async () => {
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
		const prevColor = btn.style.borderColor;
		btn.style.borderColor = 'green';
		const src = btn.getAttribute('data-src');
		const img = btn.getElementsByTagName('img')[0];
		if (!src || !img) {
			btn.style.borderColor = 'red';
			continue;
		}
		img.src = 'thumbnail.php?path=' + src;
		await wait(100); //wait
		btn.style.borderColor = prevColor;
	}

	const endDate = new Date();
	const duration = (endDate - startDate) / 1000;
	console.log(`Thumbnail loader end at ${endDate.toLocaleTimeString()}. Duration: ${duration} seconds.`);
}
