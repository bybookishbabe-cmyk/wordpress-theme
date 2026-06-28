(function () {
	'use strict';

	var root = document.querySelector('.bbb-fb-single, .sss-book-page');
	if (!root) {
		return;
	}

	Array.prototype.slice.call(root.querySelectorAll('[data-bbb-quote-rotator]')).forEach(function (tile) {
		var quotes = [];
		var index = 0;
		var image = tile.querySelector('img');
		var pin = tile.querySelector('[data-pin-media]');
		var rotateQuote = null;
		var startRotator = null;

		if (tile.dataset.bbbQuoteRotatorReady === 'true') {
			return;
		}

		try {
			quotes = JSON.parse(tile.getAttribute('data-bbb-quote-rotator') || '[]');
		} catch (error) {
			quotes = [];
		}

		if (!image || !pin || quotes.length < 2) {
			return;
		}

		tile.dataset.bbbQuoteRotatorReady = 'true';
		rotateQuote = function () {
			index = (index + 1) % quotes.length;
			var quote = quotes[index] || {};
			if (!quote.media) {
				return;
			}

			image.src = quote.media;
			image.alt = quote.alt || quote.title || image.alt;
			pin.setAttribute('data-pin-media', quote.media);
			pin.setAttribute('data-pin-title', quote.title || '');
			pin.setAttribute('data-pin-description', quote.description || '');
			pin.href = 'https://www.pinterest.com/pin/create/button/?' + new URLSearchParams({
				url: pin.getAttribute('data-pin-url') || window.location.href,
				media: quote.media,
				title: quote.title || '',
				description: quote.description || ''
			}).toString();
			pin.setAttribute('aria-label', 'save ' + (quote.title || 'quote') + ' to Pinterest');
		};
		startRotator = function () {
			if (tile.dataset.bbbQuoteRotatorInterval) {
				return;
			}

			tile.dataset.bbbQuoteRotatorInterval = String(window.setInterval(rotateQuote, 3000));
		};

		if ('IntersectionObserver' in window) {
			var quoteObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) {
						return;
					}

					startRotator();
					quoteObserver.unobserve(tile);
				});
			}, { threshold: 0.35 });

			quoteObserver.observe(tile);
		} else {
			startRotator();
		}
	});

	Array.prototype.slice.call(root.querySelectorAll('[data-fb-carousel]')).forEach(function (track) {
		var startX = 0;
		var startScroll = 0;
		var isDragging = false;
		var didDrag = false;

		track.addEventListener('pointerdown', function (event) {
			if (event.button !== 0) {
				return;
			}

			if (event.target.closest('a, button, input, textarea, select, label')) {
				return;
			}

			isDragging = true;
			didDrag = false;
			startX = event.clientX;
			startScroll = track.scrollLeft;
			track.classList.add('is-dragging');
			track.setPointerCapture(event.pointerId);
		});

		track.addEventListener('pointermove', function (event) {
			if (!isDragging) {
				return;
			}

			if (Math.abs(event.clientX - startX) > 5) {
				didDrag = true;
			}
			track.scrollLeft = startScroll - (event.clientX - startX);
		});

		var endDrag = function (event) {
			if (!isDragging) {
				return;
			}

			isDragging = false;
			track.classList.remove('is-dragging');
			if (track.hasPointerCapture && track.hasPointerCapture(event.pointerId)) {
				track.releasePointerCapture(event.pointerId);
			}
		};

		track.addEventListener('pointerup', endDrag);
		track.addEventListener('pointercancel', endDrag);
		track.addEventListener('pointerleave', endDrag);
		track.addEventListener('click', function (event) {
			if (!didDrag) {
				return;
			}

			event.preventDefault();
			event.stopPropagation();
			didDrag = false;
		}, true);
	});

	var revealItems = Array.prototype.slice.call(root.querySelectorAll('[data-fb-reveal]'));
	if (!revealItems.length) {
		return;
	}

	var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (prefersReducedMotion || !('IntersectionObserver' in window)) {
		revealItems.forEach(function (item) {
			item.classList.add('is-fb-visible');
		});
		return;
	}

	window.requestAnimationFrame(function () {
		root.classList.add('bbb-fb-animate-ready');
	});

	var revealItem = function (item) {
		if (!item || item.classList.contains('is-fb-visible')) {
			return;
		}

		item.classList.add('is-fb-visible');
		observer.unobserve(item);
	};

	var revealPassedItems = function () {
		var viewportTrigger = window.innerHeight * 0.92;

		revealItems.forEach(function (item) {
			var rect = item.getBoundingClientRect();
			if (rect.top <= viewportTrigger || rect.bottom < 0) {
				revealItem(item);
			}
		});
	};

	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) {
				return;
			}

			revealItem(entry.target);
		});
	}, {
		rootMargin: '0px 0px -8% 0px',
		threshold: 0.14
	});

	revealItems.forEach(function (item, index) {
		item.style.setProperty('--fb-reveal-index', String(index));
		observer.observe(item);
	});

	window.addEventListener('scroll', revealPassedItems, { passive: true });
	window.addEventListener('resize', revealPassedItems);
	revealPassedItems();
}());
