'use strict';

let debounceTimeout = null;
let currentEventSource = null;

document.addEventListener('DOMContentLoaded', () => {
	const searchToggle = document.getElementById('search-toggle');
	const searchInput = document.getElementById('search-input');
	const searchResults = document.getElementById('search-result');
	if (!searchToggle || !searchInput || !searchResults) return;

	//open search box
	searchToggle.addEventListener('click', () => {
		searchInput.focus();
		searchInput.select();
	});

	//server sent event
	function SSE(query) {
		debounceTimeout = setTimeout(async () => {
			searchResults.innerHTML = ''; //clear

			const header = document.createElement('h4');
			header.textContent = 'Search results for `' + query + '`:';
			searchResults.appendChild(header);

			if (currentEventSource) {
				currentEventSource.close();
				currentEventSource = null;
			}

			currentEventSource = new EventSource('search.php?q=' + encodeURIComponent(query));

			currentEventSource.onmessage = function (event) {
				const data = JSON.parse(event.data);
				//add item
				if (data.path) {
					console.log(data.path);

					const resultItem = document.createElement('a');
					resultItem.href = '?path=' + encodeURIComponent(data.path);
					searchResults.appendChild(resultItem);

					const figure = document.createElement('figure');
					resultItem.appendChild(figure);

					const figimg = document.createElement('img');
					figimg.src = 'images/pics.ico';
					figure.appendChild(figimg);

					const figcap = document.createElement('figcaption');
					figcap.textContent = data.path;
					figure.appendChild(figcap);
				}
				if (data.result) {
					header.textContent += ' (' + data.result + ')';
				}
			};

			currentEventSource.onopen = function () {
				console.log('SSE stream open.');
			};

			currentEventSource.onerror = function (err) {
				if (err.eventPhase === EventSource.CLOSED || err.currentTarget.readyState === EventSource.CLOSED) {
					console.log('SSE stream closed.');
				} else {
					console.error('SSE stream error.', 'error');
				}
				currentEventSource.close();
				currentEventSource = null;
			};
		}, 500); //debouncing - search 500ms after last char
	}

	//new search user input
	searchInput.addEventListener('input', () => {
		if (debounceTimeout) clearTimeout(debounceTimeout);

		const query = searchInput.value.trim();
		if (query.length < 3) {
			localStorage.removeItem('search');
			searchResults.innerHTML = '';
			return;
		}

		localStorage.setItem('search', query);
		SSE(query);
	});

	//old search from storage
	const oldSearch = localStorage.getItem('search');
	if (oldSearch) {
		searchInput.value = oldSearch;
		SSE(oldSearch);
	}
});
