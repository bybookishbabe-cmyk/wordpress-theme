(function () {
	function copyText(text, button) {
		if (!text) {
			return;
		}

		function markCopied() {
			var oldLabel = button.getAttribute('data-label') || button.textContent;
			button.setAttribute('data-label', oldLabel);
			button.classList.add('is-copied');
			button.setAttribute('aria-label', 'copied ' + text);
			window.setTimeout(function () {
				button.classList.remove('is-copied');
				button.removeAttribute('aria-label');
			}, 1400);
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(markCopied).catch(function () {});
			return;
		}

		var input = document.createElement('textarea');
		input.value = text;
		input.setAttribute('readonly', 'readonly');
		input.style.position = 'fixed';
		input.style.left = '-9999px';
		document.body.appendChild(input);
		input.select();
		try {
			document.execCommand('copy');
			markCopied();
		} catch (error) {}
		document.body.removeChild(input);
	}

	document.querySelectorAll('[data-copy-color]').forEach(function (button) {
		button.addEventListener('click', function () {
			copyText(button.getAttribute('data-copy-color'), button);
		});
	});

	function readShelf() {
		try {
			return JSON.parse(window.localStorage.getItem('sssMyShelf')) || [];
		} catch (error) {
			return [];
		}
	}

	function writeShelf(shelf) {
		try {
			window.localStorage.setItem('sssMyShelf', JSON.stringify(shelf));
		} catch (error) {}
		if (typeof window.CustomEvent === 'function') {
			document.dispatchEvent(new CustomEvent('sss:bookshelf-updated', {
				detail: { count: shelf.length }
			}));
		}
	}

	function setHeartState(heart, isSaved) {
		var icon = heart.querySelector('[data-heart-icon]');
		var label = heart.querySelector('[data-heart-label]');
		heart.classList.toggle('is-saved', isSaved);
		heart.setAttribute('aria-label', isSaved ? 'remove from your bookshelf' : 'save to your bookshelf');
		if (icon) {
			icon.textContent = isSaved ? '♥' : '♡';
		}
		if (label) {
			label.textContent = isSaved ? 'saved' : 'save';
		}
	}

	function bookDataFromCard(card) {
		return {
			handle: card.dataset.handle || '',
			url: card.dataset.url || '',
			title: card.dataset.title || '',
			author: card.dataset.author || '',
			cover: card.dataset.cover || '',
			amazon: card.dataset.amazon || '',
			bookshop: card.dataset.bookshop || '',
			spice: card.dataset.spice || '',
			darkness: card.dataset.darkness || '',
			tropes: card.dataset.tropes || '',
			tropesDisplay: card.dataset.tropesDisplay || card.dataset.tropes || '',
			why: card.dataset.why || '',
			newsletter: card.dataset.newsletter || '',
			tension: card.dataset.tension || '',
			damage: card.dataset.damage || '',
			yearning: card.dataset.yearning || '',
			reread: card.dataset.reread || '',
			ku: card.dataset.ku || '',
			mini: card.dataset.mini || '',
			privateShelf: card.dataset.privateShelf || 'false'
		};
	}

	window.bbbBurnToggleBookSave = function (event, heart) {
		var root = heart ? heart.closest('.bbb-burn-books[data-sss-lib]') : null;
		if (!root) {
			return true;
		}
		var card = heart.closest('.bbb-burn-book');
		if (!card || !card.dataset.title) {
			return true;
		}
		if (event) {
			event.preventDefault();
			event.stopPropagation();
		}
		var shelf = readShelf();
		var exists = shelf.some(function (book) {
			return book.title === card.dataset.title;
		});
		if (exists) {
			shelf = shelf.filter(function (book) {
				return book.title !== card.dataset.title;
			});
		} else {
			shelf.push(bookDataFromCard(card));
		}
		writeShelf(shelf);
		setHeartState(heart, !exists);
		return false;
	};

	function bindBurnBookSaves() {
		var root = document.querySelector('.bbb-burn-books[data-sss-lib]');
		if (!root) {
			return;
		}

		root.querySelectorAll('.bbb-burn-book [data-heart]').forEach(function (heart) {
			if (heart.__burnSaveBound) {
				return;
			}
			var card = heart.closest('.bbb-burn-book');
			if (!card || !card.dataset.title) {
				return;
			}
			heart.__burnSaveBound = true;
			setHeartState(heart, readShelf().some(function (book) {
				return book.title === card.dataset.title;
			}));

			heart.addEventListener('click', function (event) {
				event.preventDefault();
				event.stopPropagation();
				var shelf = readShelf();
				var exists = shelf.some(function (book) {
					return book.title === card.dataset.title;
				});
				if (exists) {
					shelf = shelf.filter(function (book) {
						return book.title !== card.dataset.title;
					});
				} else {
					shelf.push(bookDataFromCard(card));
				}
				writeShelf(shelf);
				setHeartState(heart, !exists);
			});
		});
	}

	document.addEventListener('click', function (event) {
		var heart = event.target.closest('.bbb-burn-books [data-heart]');
		if (!heart) {
			return;
		}
		var root = heart.closest('.bbb-burn-books[data-sss-lib]');
		if (!root) {
			return;
		}
		window.bbbBurnToggleBookSave(event, heart);
	}, true);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			window.setTimeout(bindBurnBookSaves, 0);
		});
	} else {
		window.setTimeout(bindBurnBookSaves, 0);
	}

	document.querySelectorAll('[data-burn-quotes]').forEach(function (root) {
		var track = root.querySelector('.bbb-burn-quotes__track');
		var prev = root.querySelector('[data-burn-quote-prev]');
		var next = root.querySelector('[data-burn-quote-next]');
		if (!track || !prev || !next) {
			return;
		}

		function move(direction) {
			track.scrollBy({
				left: direction * track.clientWidth,
				behavior: 'smooth'
			});
		}

		prev.addEventListener('click', function () {
			move(-1);
		});

		next.addEventListener('click', function () {
			move(1);
		});
	});

	function bindMobileAutoSwipe() {
		var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var mobile = window.matchMedia && window.matchMedia('(max-width: 760px)').matches;

		if (reducedMotion || !mobile) {
			return;
		}

		var lanes = Array.prototype.slice.call(document.querySelectorAll([
			'.bbb-burn-designs',
			'.bbb-burn-calendar',
			'.bbb-burn-playlist__tracks',
			'.bbb-burn-wallpapers__grid',
			'.bbb-burn-lower',
			'.bbb-burn-downloads__grid'
		].join(','))).filter(function (lane) {
			return lane.scrollWidth > lane.clientWidth + 12;
		});

		if (!lanes.length) {
			return;
		}

		lanes.forEach(function (lane, index) {
			var pausedUntil = 0;
			var timer = null;
			var nudged = false;
			var step = Math.max(140, Math.round(lane.clientWidth * 0.58));
			var intervalDelay = 1900 + (index * 170);

			function pauseBriefly() {
				pausedUntil = Date.now() + 1800;
			}

			function advance() {
				if (Date.now() < pausedUntil || document.hidden) {
					return;
				}

				var maxScroll = Math.max(0, lane.scrollWidth - lane.clientWidth);
				if (maxScroll < 12) {
					return;
				}

				if (lane.scrollLeft >= maxScroll - 8) {
					lane.scrollTo({
						left: 0,
						behavior: 'smooth'
					});
					return;
				}

				lane.scrollBy({
					left: step,
					behavior: 'smooth'
				});
			}

			['touchstart', 'pointerdown', 'wheel', 'keydown'].forEach(function (eventName) {
				lane.addEventListener(eventName, pauseBriefly, { passive: true });
			});

			if ('IntersectionObserver' in window) {
				var laneObserver = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (entry.isIntersecting && !timer) {
							if (!nudged) {
								nudged = true;
								window.setTimeout(advance, 450 + (index * 80));
							}
							timer = window.setInterval(advance, intervalDelay);
						} else if (!entry.isIntersecting && timer) {
							window.clearInterval(timer);
							timer = null;
						}
					});
				}, {
					rootMargin: '28% 0px 28% 0px',
					threshold: 0.08
				});
				laneObserver.observe(lane);
				return;
			}

			window.setTimeout(advance, 450 + (index * 80));
			timer = window.setInterval(advance, intervalDelay);
		});
	}

	bindMobileAutoSwipe();

	var animatedSections = document.querySelectorAll('.bbb-burn-bright > section');
	if (!animatedSections.length) {
		return;
	}

	if (!('IntersectionObserver' in window)) {
		animatedSections.forEach(function (section) {
			section.classList.add('is-burn-visible');
		});
		return;
	}

	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) {
				return;
			}
			entry.target.classList.add('is-burn-visible');
			observer.unobserve(entry.target);
		});
	}, {
		rootMargin: '0px 0px -14% 0px',
		threshold: 0.16
	});

	animatedSections.forEach(function (section) {
		observer.observe(section);
	});
}());
