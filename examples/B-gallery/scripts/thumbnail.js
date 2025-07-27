'use strict';

const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms)); //helper function for pause

//helper function for load image (null = buffered / element = direct)
const loadImg = (url, img = null) => {
	console.log('Loading image: ' + url);
	return new Promise((resolve, reject) => {
		if (!img) img = new Image();
		img.onload = () => resolve(img);
		img.onerror = () => reject(new Error(`Failed to load image: ${url}`));
		img.src = url;
	});
};

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
		try {
			const prevBorder = btn.style.border;
			btn.style.border = '1px solid green';
			await wait(50); //wait
			const src = btn.getAttribute('data-src');
			const img = btn.getElementsByTagName('img')[0];
			if (!src || !img) continue;
			img.setAttribute('loading', 'lazy');
			const loadedImage = await loadImg('thumbnail.php?path=' + src); //try to load image to buffer 
			if (loadedImage) img.src = loadedImage.src; //copy from buffer
			await wait(50); //wait
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

//modal slideshow
let modal;
let modalImg;
let actualBtn;

let startX, startY;

document.addEventListener('DOMContentLoaded', () => {
	modal = document.getElementById('modal');
	modalImg = document.getElementById('zoomed');

	//buttons on modal
	const buttonsDiv = document.getElementById('buttons');
	if (buttonsDiv) {
		buttonsDiv.addEventListener('click', function (event) {
			if (event.target.tagName === 'IMG' && !event.target.classList.contains('grayed')) {
				switch (event.target.id) {
					case 'slideshow':
						timer.toggle();
						break;
					case 'rewind':
						imgFirst();
						break;
					case 'prev':
						imgPrev();
						break;
					case 'next':
						imgNext();
						break;
					case 'forward':
						imgLast();
						break;
					case 'close':
						closeModal();
						break;
				}
			}
		});
	}

	//swipe
	modalImg.addEventListener('touchstart', (e) => {
		startX = e.touches[0].clientX;
		startY = e.touches[0].clientY;
	});
	modalImg.addEventListener('touchmove', (e) => {
		e.preventDefault();//prevent default scrolling behavior
	});
	modalImg.addEventListener('touchend', (e) => {
		const diffX = e.changedTouches[0].clientX - startX;
		const diffY = e.changedTouches[0].clientY - startY;
		const threshold = 50; // minimum distance in pixels for a swipe to be recognized
		if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > threshold) { //horizontal swipe
			if (diffX > 0) imgPrev(); //swipe right (go left)
			else imgNext(); //swipe left (go right)
		}
	});
});

document.addEventListener('keydown', function (event) {
	switch (event.key) {
		case 'Enter':
			timer.toggle();
			break;
		case 'ArrowUp':
			imgFirst();
			break;
		case 'ArrowLeft':
			imgPrev();
			break;
		case 'ArrowRight':
		case 'Space':
		case ' ':
			imgNext();
			break;
		case 'ArrowDown':
			imgLast();
			break;
		case 'Escape':
		case 'Backspace':
			closeModal();
			break;
		default:
			return;
	}
	event.preventDefault();
});

window.addEventListener('popstate', function (event) { /* history back */
	closeModal();
});

function openModal(btn) {
	history.pushState({ modalOpen: true }, 'Image Modal Open', '#modal');
	modal.style.display = 'flex';
	imgChange(btn);
}

function closeModal() {
	timer.stop();
	modalImg.src = '';
	modal.style.display = 'none';
	if (history.state && history.state.modalOpen) history.back();
}

function getElementIndex(element) {
	if (!element || !element.parentElement) return -1;
	const children = Array.from(element.parentElement.children);
	return children.indexOf(element);
}

function getElementCount(element) {
	if (!element || !element.parentElement) return -1;
	return element.parentElement.children.length;
}

function imgChange(element) {
	//pause slideshow
	const slideshowState = timer.isRunning();
	if (slideshowState) timer.pause();
	//try to load image
	if (element) {
		modalImg.src = '';
		actualBtn = element;
		const src = actualBtn.getAttribute('data-src');
		if (src) {
			//loadImg(src, modalImg) //load image directly (full size)
			const w = document.documentElement.clientWidth; //viewport width
			const h = document.documentElement.clientHeight; //viewport height
			loadImg('thumbnail.php?w=' + w + '&h=' + h + '&path=' + src, modalImg) //load image directly (size by viewport)
				.then(loadedImage => { //done
					console.log('Image Done.');
				})
				.catch(error => {
					console.error(error.message);
				});
		}
	}
	//show index/count
	const imgIndex = getElementIndex(actualBtn) + 1;
	const imgCount = getElementCount(actualBtn);
	const counter = document.getElementById('counter');
	if (counter) counter.textContent = imgIndex + ' / ' + imgCount;
	//buttons on/off
	if (imgIndex === imgCount) timer.stop(); //end of slideshow
	else if (slideshowState) timer.start(); //resume slideshow
	const btnPlay = document.getElementById('slideshow');
	const btnFirst = document.getElementById('rewind');
	const btnPrev = document.getElementById('prev');
	const btnNext = document.getElementById('next');
	const btnLast = document.getElementById('forward');
	if (imgCount < 2) {
		btnPlay.classList.add('grayed');
		btnFirst.classList.add('grayed');
		btnPrev.classList.add('grayed');
		btnNext.classList.add('grayed');
		btnLast.classList.add('grayed');
	} else {
		if (imgIndex > 1) {
			btnFirst.classList.remove('grayed');
			btnPrev.classList.remove('grayed');
		} else {
			btnFirst.classList.add('grayed');
			btnPrev.classList.add('grayed');
		}
		if (imgIndex < imgCount) {
			btnPlay.classList.remove('grayed');
			btnNext.classList.remove('grayed');
			btnLast.classList.remove('grayed');
		} else {
			btnPlay.classList.add('grayed');
			btnNext.classList.add('grayed');
			btnLast.classList.add('grayed');
		}
	}
}

function imgFirst() {
	const parent = document.getElementById('pictures');
	if (parent) imgChange(parent.firstElementChild);
}

function imgPrev() {
	const prevSibling = actualBtn.previousElementSibling;
	if (prevSibling) imgChange(prevSibling);
}

function imgNext() {
	const nextSibling = actualBtn.nextElementSibling;
	if (nextSibling) imgChange(nextSibling);
}

function imgLast() {
	const parent = document.getElementById('pictures');
	if (parent) imgChange(parent.lastElementChild);
}

//slideshow
timer.setDuration(5);
timer.onTick(updateProgress);
timer.onComplete(imgNext);
timer.onStart(timerStart);
timer.onStop(timerStop);

function updateProgress(percentage) {
	const timerBar = document.getElementById('progress');
	if (timerBar) timerBar.value = percentage;
}

function timerStart() {
	const slideshow = document.getElementById('slideshow'); //button (img)
	if (slideshow) slideshow.src = 'images/pause.ico';
	const timerBar = document.getElementById('progress');
	if (timerBar) timerBar.style.display = 'block';
}

function timerStop() {
	const slideshow = document.getElementById('slideshow'); //button (img)
	if (slideshow) slideshow.src = 'images/play.ico';
	const timerBar = document.getElementById('progress');
	if (timerBar) timerBar.style.display = 'none';
}
