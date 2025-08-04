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

async function getdata(url, element) {
	console.log('Loading image info: ' + url);
	element.innerHTML = '';
	try {
		const response = await fetch(url);
		if (!response.ok) throw new Error(`Network error: ${response.status} ${response.statusText}`);
		const data = await response.text();
		element.innerHTML = data;

	} catch (error) {
		console.error('Loading image info: ', error);
	}
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
	const buttonsDiv = modal.querySelector('#buttons'); //document.getElementById('buttons'); //same
	if (buttonsDiv) {
		buttonsDiv.addEventListener('click', function (event) {
			if (event.target.tagName === 'IMG' && !event.target.classList.contains('grayed')) {
				switch (event.target.id) {
					case 'info':
						imgInfo();
						break;
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
					case 'download':
						imgDownload();
						break;
					case 'close':
						closeModal();
						break;
				}
			}
		});
	}

	//zoom
	function getScale() {
		const match = modalImg.style.transform.match(/scale\(([^)]*)\)/);
		if (match && match[1]) return parseFloat(match[1]) || 1;
		return 1;
	}

	function setScale(newScale) {
		newScale = Math.min(Math.max(newScale, 1), 3); //zoom limitation 1 to 3
		const updatedTransform = modalImg.style.transform.replace(/scale\([^)]*\)/g, '').trim();
		modalImg.style.transform = `${updatedTransform} scale(${newScale})`;
	}

	modalImg.addEventListener('dblclick', (e) => {
		const x = e.offsetX;
		const y = e.offsetY;
		modalImg.style.transformOrigin = `${x}px ${y}px`;
		if (getScale() > 1) setScale(1.0);
		else setScale(1.5);
	});

	//touch screen
	function getDistance(touches) {
		if (touches.length < 2) return null;
		const dx = touches[0].clientX - touches[1].clientX;
		const dy = touches[0].clientY - touches[1].clientY;
		return Math.sqrt(dx * dx + dy * dy);
	}

	function getMidPoint(touches) {
		if (touches.length < 2) return null;
		const midX = (touches[0].clientX + touches[1].clientX) / 2;
		const midY = (touches[0].clientY + touches[1].clientY) / 2;
		return { x: midX, y: midY };
	}

	let scale = 1;
	let initialDistance = null;
	const threshold = 50; //minimum distance in pixels for a swipe to be recognized
	modalImg.addEventListener('touchstart', (e) => {
		startX = e.touches[0].clientX;
		startY = e.touches[0].clientY;
		if (e.touches.length > 1) { //more fingers
			const mid = getMidPoint(e.touches);
			modalImg.style.transformOrigin = `${mid.x}px ${mid.y}px`;
			initialDistance = getDistance(e.touches);
		}
		else { //one finger
			modalImg.style.transformOrigin = `${startX}px ${startY}px`;
			initialDistance = null;
		}
	});
	modalImg.addEventListener('touchmove', (e) => {
		e.preventDefault(); //prevent default scrolling behavior
		if (e.touches.length > 1) { //more fingers - pinch zoom
			const currentDistance = getDistance(e.touches);
			setScale(scale * (currentDistance / initialDistance)); //new scale
		} else { //one finger - swipe
			const diffX = e.touches[0].clientX - startX;
			const diffY = e.touches[0].clientY - startY;
			if (Math.abs(diffX) > Math.abs(diffY)) { //horizontal swipe
				modalImg.style.transform = `translateX(${diffX}px)`;
			}
		}
	});
	modalImg.addEventListener('touchend', (e) => {
		modalImg.style.transform = '';
		const diffX = e.changedTouches[0].clientX - startX;
		const diffY = e.changedTouches[0].clientY - startY;
		if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > threshold) { //horizontal swipe
			if (diffX > 0) imgPrev(); //swipe right (go left)
			else imgNext(); //swipe left (go right)
		}
		scale = 1;
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
			case 'i':
			case 'I':
				imgInfo();
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
	modal.style.display = 'grid';
	fullscreen(modal);
	imgChange(btn);
}

function closeModal() {
	fullscreen();
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
	//reset img
	//try to load image
	//loadImg(src, modalImg) //load image directly (full size)
	const w = modal.clientWidth; //document.documentElement.clientWidth; //viewport width
	const h = modal.clientHeight; //document.documentElement.clientHeight; //viewport height
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
			//info
		})
		.catch(error => {
			console.error(error.message);
		});
	//info
	const exif = document.getElementById('exif');
	if (exif) getdata('imageinfo.php?path=' + src, exif);
}

function imgInfo() {
	const exif = document.getElementById('exif');
	if (exif) {
		if (exif.style.display === 'none' || exif.style.display === '') {
			exif.style.display = 'block';
		} else {
			exif.style.display = 'none';
		}
	}
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

function getFilenameFromPath(filePath) {
	const lastSlashIndex = filePath.lastIndexOf('/');
	const lastBackslashIndex = filePath.lastIndexOf('\\');
	const lastDelimiterIndex = Math.max(lastSlashIndex, lastBackslashIndex);
	return lastDelimiterIndex === -1 ? filePath : filePath.substring(lastDelimiterIndex + 1);
}

function imgDownload() {
	if (actualBtn) {
		const src = actualBtn.getAttribute('data-src');
		if (!src) return;

		const maxWorH = 2000; //max W or H
		const link = document.createElement('a'); //hidden link
		link.href = 'thumbnail.php?dl=true&w=' + maxWorH + '&h=' + maxWorH + '&path=' + src;
		link.download = getFilenameFromPath(src);
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
	}
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
	requestWakeLock();
}

function timerStop() {
	const slideshow = document.getElementById('slideshow'); //button (img)
	if (slideshow) slideshow.src = 'images/play.ico';
	const timerBar = document.getElementById('progress');
	if (timerBar) {
		timerBar.max = 100;
		timerBar.value = 0;
	}
	releaseWakeLock();
}

function timerPause() {
	const timerBar = document.getElementById('progress');
	if (timerBar) {
		timerBar.removeAttribute('max');
		timerBar.removeAttribute('value');
	}
}
