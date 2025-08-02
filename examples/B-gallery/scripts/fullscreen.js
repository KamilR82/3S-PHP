'use strict';

let wakeLock = null;
let fullscreenMode = null;

const requestWakeLock = async () => {
	try {
		wakeLock = await navigator.wakeLock.request('screen');
		console.log('Screen Wake Lock active!');
	} catch (err) {
		console.error(`Screen Wake Lock: ${err.message}`);
	}
};

const releaseWakeLock = () => {
	if (wakeLock) {
		wakeLock.release();
		wakeLock = null;
		console.log('Screen Wake Lock released!');
	}
};

const fullscreen = (element = null) => {
	fullscreenMode = element;
	if (element) {
		console.log('Try to enter fullscreen mode.');
		if (element.requestFullscreen) {
			element.requestFullscreen();
		} else if (element.mozRequestFullScreen) {
			element.mozRequestFullScreen();
		} else if (element.webkitRequestFullscreen) {
			element.webkitRequestFullscreen();
		} else if (element.msRequestFullscreen) {
			element.msRequestFullscreen();
		}
	}
	else if (document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
		console.log('Try to exit fullscreen mode.');
		if (document.exitFullscreen) {
			document.exitFullscreen();
		} else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen();
		} else if (document.webkitExitFullscreen) {
			document.webkitExitFullscreen();
		} else if (document.msExitFullscreen) {
			document.msExitFullscreen();
		}
	}
}

document.addEventListener('fullscreenchange', () => {
    console.log('Fullscreen mode changed to: ' + !!document.fullscreenElement);
});

document.addEventListener('visibilitychange', () => {
	if (document.visibilityState === 'visible') {
		if (wakeLock !== null) {
			requestWakeLock();
		}
		if (fullscreenMode !== null) {
			fullscreen(fullscreenMode);
		}
	}
});
