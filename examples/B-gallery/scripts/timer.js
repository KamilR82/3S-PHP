'use strict';

class Timer {
	static #STEP = 100; //ms

	constructor(seconds = 60) {
		this.totalDuration = seconds * 1000;
		this.timeRemaining = this.totalDuration;
		this.intervalStep = Timer.#STEP;
		this.intervalId = null; //setInterval ID (null = not running)

		//event callback functions
		this.onTickCallback = (percentage) => { };
		this.onCompleteCallback = () => { };
		this.onStartCallback = () => { };
		this.onPauseCallback = () => { };
		this.onStopCallback = () => { };
	}

	setStep(ms) {
		if (typeof ms !== 'number' || ms < 10) {
			console.error('The timer step must be a positive number.');
			return;
		}

		this.intervalStep = ms;
	}

	setDuration(seconds) {
		if (typeof seconds !== 'number' || seconds <= 0) {
			console.error('The timer length must be a positive number.');
			return;
		}

		this.totalDuration = seconds * 1000;
		if (this.timeRemaining > this.totalDuration) this.timeRemaining = this.totalDuration;
	}

	getProgressPercentage() {
		if (this.totalDuration === 0 || this.timeRemaining <= 0) return 0;
		return ((this.totalDuration - this.timeRemaining) / this.totalDuration) * 100;
	}

	getTimeRemaining() {
		const minutes = Math.floor(this.timeRemaining / 60);
		const seconds = this.timeRemaining % 60;
		return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
	}

	start() {
		if (this.intervalId) return; //running
		this.onStartCallback();
		this.intervalId = setInterval(() => {
			this.timeRemaining -= this.intervalStep;
			if (this.timeRemaining <= 0) {
				this.onCompleteCallback(); //complete callback
				this.reset();
			}
			else this.onTickCallback(Math.floor(this.getProgressPercentage())); //tick callback
		}, this.intervalStep);
	}

	pause() {
		if (this.intervalId) clearInterval(this.intervalId);
		this.intervalId = null;
		this.onPauseCallback();
	}

	stop() {
		if (this.intervalId) clearInterval(this.intervalId);
		this.intervalId = null;
		timer.reset();
		this.onStopCallback();
	}

	toggle() {
		if (this.intervalId) this.stop();
		else this.start();
	}

	reset() {
		this.timeRemaining = this.totalDuration;
	}

	isRunning() {
		return this.intervalId ? true : false;
	}

	//callback methods
	onTick(callback) {
		if (typeof callback === 'function') this.onTickCallback = callback;
	}
	onComplete(callback) {
		if (typeof callback === 'function') this.onCompleteCallback = callback;
	}
	onStart(callback) {
		if (typeof callback === 'function') this.onStartCallback = callback;
	}
	onPause(callback) {
		if (typeof callback === 'function') this.onPauseCallback = callback;
	}
	onStop(callback) {
		if (typeof callback === 'function') this.onStopCallback = callback;
	}
}

//initialize one instance
const timer = new Timer();
