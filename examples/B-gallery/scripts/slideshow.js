'use strict';

//function for load image (null = buffered / element = direct)
function loadImg(url, img = null) {
	console.log('Loading image: ' + url);
	return new Promise((resolve, reject) => {
		if (!img) img = new Image();
		img.onload = () => resolve(img);
		img.onerror = () => reject(new Error(`Failed to load image: ${url}`));
		img.src = url; //run
	});
};

//modal slideshow
let modal;
let modalImg;
let actualBtn;

let startX, startY;

document.addEventListener('DOMContentLoaded', () => {
	modal = document.getElementById('modal');
	modalImg = document.getElementById('zoomed');

	//buttons on modal
	const buttonsDiv = modal.querySelector('#buttons'); //document.getElementById('buttons'); //same
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

	//touch screen swipe
	modalImg.addEventListener('touchstart', (e) => {
		startX = e.touches[0].clientX;
		startY = e.touches[0].clientY;
	});
	modalImg.addEventListener('touchmove', (e) => {
		e.preventDefault(); //prevent default scrolling behavior
	});
	modalImg.addEventListener('touchend', (e) => {
		const diffX = e.changedTouches[0].clientX - startX;
		const diffY = e.changedTouches[0].clientY - startY;
		const threshold = 50; //minimum distance in pixels for a swipe to be recognized
		if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > threshold) { //horizontal swipe
			if (diffX > 0) imgPrev(); //swipe right (go left)
			else imgNext(); //swipe left (go right)
		}
	});
});

document.addEventListener('keydown', function (event) {
	if (actualBtn) //open modal
	{
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
				closeModal();
				break;
			default:
				return;
		}
		event.preventDefault();
	}
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
	actualBtn = null;
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
	if (!element) return;
	const src = element.getAttribute('data-src');
	if (!src) return;
	//counter
	const imgCount = getElementCount(element);
	const counter = document.getElementById('counter');
	if (counter) counter.textContent = '? / ' + imgCount;
	//buttons off
	const btnPlay = document.getElementById('slideshow');
	const btnFirst = document.getElementById('rewind');
	const btnPrev = document.getElementById('prev');
	const btnNext = document.getElementById('next');
	const btnLast = document.getElementById('forward');
	btnPlay.classList.add('grayed');
	btnFirst.classList.add('grayed');
	btnPrev.classList.add('grayed');
	btnNext.classList.add('grayed');
	btnLast.classList.add('grayed');
	//pause slideshow
	const slideshowState = timer.isRunning(); //get slideshow state
	timer.pause(); //start loading progress
	timer.reset();
	//try to load image
	//loadImg(src, modalImg) //load image directly (full size)
	const w = document.documentElement.clientWidth; //viewport width
	const h = document.documentElement.clientHeight; //viewport height
	loadImg('thumbnail.php?w=' + w + '&h=' + h + '&path=' + src, modalImg) //load image directly (size by viewport)
		.then(loadedImage => { //done
			console.log('Image Done.');
			actualBtn = element;
			//show index/count
			const imgIndex = getElementIndex(element) + 1;
			if (counter) counter.textContent = imgIndex + ' / ' + imgCount;
			//slideshow
			if (slideshowState && imgIndex < imgCount) timer.start(); //resume slideshow
			else timer.stop(); //end of slideshow or stop loading progress
			//buttons on
			if (imgCount > 1) {
				if (imgIndex > 1) {
					btnFirst.classList.remove('grayed');
					btnPrev.classList.remove('grayed');
				}
				if (imgIndex < imgCount) {
					btnPlay.classList.remove('grayed');
					btnNext.classList.remove('grayed');
					btnLast.classList.remove('grayed');
				}
			}
		})
		.catch(error => {
			console.error(error.message);
		});
}

function imgFirst() {
	const parent = document.getElementById('pictures');
	if (parent) imgChange(parent.firstElementChild);
}

function imgPrev() {
	if (actualBtn) {
		const prevSibling = actualBtn.previousElementSibling;
		if (prevSibling) imgChange(prevSibling);
	}
}

function imgNext() {
	if (actualBtn) {
		const nextSibling = actualBtn.nextElementSibling;
		if (nextSibling) imgChange(nextSibling);
	}
}

function imgLast() {
	const parent = document.getElementById('pictures');
	if (parent) imgChange(parent.lastElementChild);
}

//slideshow
timer.setDuration(6); //seconds
timer.onTick(updateProgress);
timer.onComplete(imgNext);
timer.onStart(timerStart);
timer.onStop(timerStop);
timer.onPause(timerPause);

function updateProgress(percentage) {
	const timerBar = document.getElementById('progress');
	if (timerBar) timerBar.value = percentage;
}

function timerStart() {
	const slideshow = document.getElementById('slideshow'); //button (img)
	if (slideshow) slideshow.src = 'images/pause.ico';
	const timerBar = document.getElementById('progress');
	if (timerBar) {
		timerBar.max = 100;
		timerBar.value = 0;
	}
}

function timerStop() {
	const slideshow = document.getElementById('slideshow'); //button (img)
	if (slideshow) slideshow.src = 'images/play.ico';
	const timerBar = document.getElementById('progress');
	if (timerBar) {
		timerBar.max = 100;
		timerBar.value = 0;
	}
}

function timerPause() {
	const timerBar = document.getElementById('progress');
	if (timerBar) {
		timerBar.removeAttribute('max');
		timerBar.removeAttribute('value');
	}
}
