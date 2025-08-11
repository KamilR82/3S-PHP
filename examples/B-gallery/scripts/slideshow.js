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

let initialMidPoint = null;
let initialDistance = null;
let currentScale = 1.0;
let currentTranslate = { x: 0, y: 0 };
let hSwipe = null; //horizontal swipe distance

function applyTransform() {
	modalImg.style.transform = `translate(${currentTranslate.x}px, ${currentTranslate.y}px) scale(${currentScale})`;
	//need upscale image?
	console.log('Transform applied. Current scale: ' + currentScale);
	if (currentScale > 1.0 && modalImg.naturalWidth > 0 && modalImg.naturalHeight > 0) {
		const scaledRect = modalImg.getBoundingClientRect(); //get image size and position after transform
		const ratioW = scaledRect.width / modalImg.naturalWidth;
		const ratioH = scaledRect.height / modalImg.naturalHeight;
		const ratio = Math.max(ratioW, ratioH).toFixed(1); //use the larger ratio
		console.log('Need reload? Ratio: ' + ratio);
		if(ratio > 1.2) imgChange(actualBtn, currentScale); //if image is too small, reload it
	}
}

document.addEventListener('DOMContentLoaded', () => {
	modal = document.getElementById('modal');
	modalImg = document.getElementById('zoomable');

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

	//mouse drag
	modalImg.addEventListener('mousedown', (e) => {
		if (currentScale <= 1.0) return; //only drag when zoomed in
		e.preventDefault();
		initialMidPoint = { x: e.clientX - currentTranslate.x, y: e.clientY - currentTranslate.y }; //set start position
		modalImg.style.cursor = 'grabbing'; //change cursor
	});

	modal.addEventListener('mousemove', (e) => {
		if (currentScale <= 1.0 || !initialMidPoint) return; //only drag when zoomed in
		e.preventDefault();
		currentTranslate = { x: e.clientX - initialMidPoint.x, y: e.clientY - initialMidPoint.y }; //calculate new position
		applyTransform();
	});

	modal.addEventListener('mouseup', (e) => {
		initialMidPoint = null; //reset start position
		modalImg.style.cursor = 'grab'; //reset cursor
	});

	modal.addEventListener('mouseleave', (e) => {
		initialMidPoint = null; //reset start position
	});

	//mouse zoom
	modal.addEventListener('wheel', (e) => {
		e.preventDefault();
		const oldScale = currentScale;
		//scale
		const scaleFactor = 0.2;
		if (e.deltaY < 0) {
			currentScale += scaleFactor; //zoom in
		} else {
			currentScale -= scaleFactor; //zoom out
		}
		currentScale = Math.max(1, Math.min(3, currentScale)); //limit zoom 1-3
		//origin
		if (currentScale > 1.0) {
			const rect = modalImg.getBoundingClientRect(); //get image size and position after transform
			const cursorX = e.clientX - rect.left;
			const cursorY = e.clientY - rect.top;
			currentTranslate.x = (currentTranslate.x - cursorX) * (currentScale / oldScale) + cursorX;
			currentTranslate.y = (currentTranslate.y - cursorY) * (currentScale / oldScale) + cursorY;
		} else currentTranslate = { x: 0, y: 0 }; //reset translate when zoom out
		//apply
		applyTransform();
	});

	//double click or double touch
	modalImg.addEventListener('dblclick', (e) => {
		e.preventDefault();
		if (currentScale > 1) {
			currentTranslate = { x: 0, y: 0 }; //reset translate
			modalImg.style.transformOrigin = 'center center'; //reset origin
			currentScale = 1.0; //zoom out
		} else {
			const originX = e.offsetX;
			const originY = e.offsetY;
			modalImg.style.transformOrigin = `${originX}px ${originY}px`;
			currentScale = 2.0; //zoom in
		}
		applyTransform();
	});

	//touch screen
	function getDistance(touches) {
		if (touches.length < 2) return 0;
		const dx = touches[0].clientX - touches[1].clientX;
		const dy = touches[0].clientY - touches[1].clientY;
		return Math.sqrt(dx * dx + dy * dy);
	}

	function getMidPoint(touches) {
		if (touches.length === 0) return null;
		let midX = 0;
		let midY = 0;
		for (let i = 0; i < touches.length; i++) {
			midX += touches[i].clientX;
			midY += touches[i].clientY;
		}
		midX /= touches.length;
		midY /= touches.length;
		return { x: midX, y: midY };
	}

	modalImg.addEventListener('touchstart', (e) => {
		if (e.touches.length > 1) e.preventDefault(); //only for more fingers because dblclick
		initialDistance = getDistance(e.touches);
		initialMidPoint = getMidPoint(e.touches);
	});
	modalImg.addEventListener('touchmove', (e) => {
		e.preventDefault(); //prevent default scrolling behavior
		const currentMidPoint = getMidPoint(e.touches);
		const deltaX = currentMidPoint.x - initialMidPoint.x;
		const deltaY = currentMidPoint.y - initialMidPoint.y;
		initialMidPoint = currentMidPoint; //update initial mid point
		currentTranslate.x += deltaX;
		currentTranslate.y += deltaY;
		if (e.touches.length > 1) { //more fingers - pinch zoom & move
			if (initialDistance !== null) { //zoom
				const currentDistance = getDistance(e.touches);
				const scaleFactor = currentDistance / initialDistance;
				initialDistance = currentDistance;
				//scale
				currentScale = currentScale * scaleFactor;
				currentScale = Math.max(1, Math.min(5, currentScale)); //limit zoom 1-3 (5 for pinch zoom)
			}
		} else { //one finger
			if (currentScale <= 1.0) { //swipe
				currentTranslate.y = 0; //vertical lock
				if (Math.abs(deltaX) > Math.abs(deltaY)) { //horizontal swipe
					if (Math.abs(deltaX) > 5) hSwipe = deltaX; //minimum distance in pixels for a swipe to be recognized
					else hSwipe = null; //not a swipe
				}
			}
		}
		applyTransform();
	});
	modalImg.addEventListener('touchend', (e) => {
		if (e.touches.length === 0 && currentScale === 1.0) { //last finger up - swipe end
			if (hSwipe !== null) { //swipe detected
				if (hSwipe > 0) imgPrev(); //swipe right (go left)
				else imgNext(); //swipe left (go right)
				hSwipe = null; //reset swipe
			}
			currentTranslate = { x: 0, y: 0 }; //reset translate
			applyTransform();
		}
		initialDistance = getDistance(e.touches);
		initialMidPoint = getMidPoint(e.touches);
	});
});

document.addEventListener('keydown', function (event) {
	if (actualBtn) //open modal
	{
		switch (event.key) {
			case 'Enter':
				timer.toggle();
				break;
			case '+':
			case 'Add':
				currentScale += 0.5; //zoom in
				if (currentScale > 3.0) currentScale = 3.0; //limit zoom
				applyTransform();
				break;
			case '-':
			case 'Subtract':
				currentScale -= 0.5; //zoom out
				if (currentScale < 1.0) currentScale = 1.0; //limit zoom
				applyTransform();
				break;
			case 'ArrowUp':
				if (currentScale > 1.0) {
					currentTranslate.y += 50; //move up
					applyTransform();
				} else imgFirst();
				break;
			case 'ArrowDown':
				if (currentScale > 1.0) {
					currentTranslate.y -= 50; //move down
					applyTransform();
				} else imgLast();
				break;
			case 'ArrowLeft':
				if (currentScale > 1.0) {
					currentTranslate.x += 50; //move left
					applyTransform();
				} else imgPrev();
				break;
			case 'ArrowRight':
				if (currentScale > 1.0) {
					currentTranslate.x -= 50; //move right
					applyTransform();
				} else imgNext();
				break;
			case 'Space':
			case ' ':
				imgNext();
				break;
			case 'i':
			case 'I':
				imgInfo();
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
	modal.style.display = 'grid'; //show
	fullscreen(modal);
	imgChange(btn);
}

function closeModal() {
	fullscreen();
	timer.stop();
	actualBtn = null;
	modalImg.src = '';
	modal.style.display = 'none'; //hide
	currentScale = 1.0; //reset zoom
	currentTranslate = { x: 0, y: 0 }; //reset translate
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

function imgChange(element, scale = 1.0) {
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
	if (scale === 1.0) modalImg.style.transform = ''; //reset zoom
	const w = modal.clientWidth * scale; //document.documentElement.clientWidth; //viewport width
	const h = modal.clientHeight * scale; //document.documentElement.clientHeight; //viewport height
	loadImg('thumbnail.php?w=' + w + '&h=' + h + '&path=' + src, modalImg) //load image directly (size by demand)
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
