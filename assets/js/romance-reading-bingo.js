(function () {
	var root = document.querySelector('[data-bbb-bingo]');
	if (!root) {
		return;
	}

	var storageKey = 'bbb-romance-reading-bingo-summer-2026-v2';
	var squares = Array.prototype.slice.call(root.querySelectorAll('[data-bbb-bingo-square]'));
	var countEl = root.querySelector('[data-bbb-bingo-count]');
	var resultEl = root.querySelector('[data-bbb-bingo-result]');
	var copyBtn = root.querySelector('[data-bbb-bingo-copy]');
	var shareBtn = root.querySelector('[data-bbb-bingo-share]');
	var shareToast = root.querySelector('[data-bbb-bingo-share-toast]');
	var resetBtn = root.querySelector('[data-bbb-bingo-reset]');
	var modal = root.querySelector('[data-bbb-bingo-modal]');
	var modalType = root.querySelector('[data-bbb-bingo-modal-type]');
	var modalCopy = root.querySelector('[data-bbb-bingo-modal-copy]');
	var copyCardBtn = root.querySelector('[data-bbb-bingo-copy-card]');
	var pinLink = root.querySelector('[data-bbb-bingo-pin]');
	var recDataEl = root.querySelector('[data-bbb-bingo-recs]');
	var bookLink = root.querySelector('[data-bbb-bingo-book-link]');
	var bookMedia = root.querySelector('[data-bbb-bingo-book-media]');
	var bookTitle = root.querySelector('[data-bbb-bingo-book-title]');
	var manLink = root.querySelector('[data-bbb-bingo-man-link]');
	var manMedia = root.querySelector('[data-bbb-bingo-man-media]');
	var manTitle = root.querySelector('[data-bbb-bingo-man-title]');
	var closeButtons = Array.prototype.slice.call(root.querySelectorAll('[data-bbb-bingo-close]'));
	var lastHadBingo = false;
	var currentShareText = '';
	var resultRecs = {};
	var bingoLines = [
		[0, 1, 2, 3, 4],
		[5, 6, 7, 8, 9],
		[10, 11, 12, 13, 14],
		[15, 16, 17, 18, 19],
		[20, 21, 22, 23, 24],
		[0, 5, 10, 15, 20],
		[1, 6, 11, 16, 21],
		[2, 7, 12, 17, 22],
		[3, 8, 13, 18, 23],
		[4, 9, 14, 19, 24],
		[0, 6, 12, 18, 24],
		[4, 8, 12, 16, 20],
	];

	try {
		resultRecs = recDataEl ? JSON.parse(recDataEl.textContent || '{}') : {};
	} catch (error) {
		resultRecs = {};
	}

	function loadMarked() {
		try {
			var saved = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
			return Array.isArray(saved) ? saved : [];
		} catch (error) {
			return [];
		}
	}

	function saveMarked(marked) {
		try {
			window.localStorage.setItem(storageKey, JSON.stringify(marked));
		} catch (error) {
			// Local storage can be unavailable in private browsing.
		}
	}

	function hasBingo(marked) {
		return bingoLines.some(function (line) {
			return line.every(function (index) {
				return marked.indexOf(index) !== -1;
			});
		});
	}

	function categoryCount(marked, category) {
		return marked.filter(function (index) {
			return squares[index] && squares[index].getAttribute('data-bbb-bingo-type') === category;
		}).length;
	}

	function selectedSquares(marked) {
		return marked.map(function (index) {
			var square = squares[index];
			return square ? {
				index: index,
				type: square.getAttribute('data-bbb-bingo-type') || '',
				text: (square.textContent || '').trim().toLowerCase(),
				url: square.getAttribute('data-bbb-bingo-url') || '',
			} : null;
		}).filter(Boolean);
	}

	function keywordParts(text) {
		return String(text || '').toLowerCase().replace(/^fell for\s+/i, '').split(/[^a-z0-9]+/).filter(function (part) {
			return part.length > 2;
		});
	}

	function recScore(candidate, picks, typeName, kind) {
		var keywords = Array.isArray(candidate.keywords) ? candidate.keywords.join(' ').toLowerCase() : '';
		var title = String(candidate.title || '').toLowerCase();
		var haystack = keywords + ' ' + title;
		var score = 0;

		picks.forEach(function (pick) {
			if (pick.url && candidate.url === pick.url) {
				score -= 1000;
			}
			if (pick.type === 'trope') {
				score += keywordParts(pick.text).some(function (part) {
					return haystack.indexOf(part) !== -1;
				}) ? 8 : 0;
			}
			if (kind === 'book' && pick.type === 'new release') {
				score += 3;
			}
			if (kind === 'man' && pick.type === 'fictional man') {
				score += 3;
			}
		});

		if (kind === 'book' && typeName === 'the new-release hunter') {
			score += 4;
		}
		if (kind === 'man' && typeName === 'the book boyfriend archivist') {
			score += 4;
		}
		if (typeName === 'the shadow-season reader' && /(dark|mafia|monster|villain|gothic|romantasy|danger)/.test(haystack)) {
			score += 7;
		}
		if (typeName === 'the trope loyalist' && keywords) {
			score += 2;
		}

		return score;
	}

	function boardUrlMap() {
		return squares.reduce(function (urls, square) {
			var url = square.getAttribute('data-bbb-bingo-url') || '';
			if (url) {
				urls[url] = true;
			}
			return urls;
		}, {});
	}

	function rankedRecs(pool, picks, typeName, kind) {
		pool = Array.isArray(pool) ? pool : [];
		var boardUrls = boardUrlMap();
		return pool.filter(function (candidate) {
			return candidate && candidate.url && !boardUrls[candidate.url];
		}).map(function (candidate, index) {
			return {
				candidate: candidate,
				score: recScore(candidate, picks, typeName, kind),
				index: index,
			};
		}).sort(function (a, b) {
			if (b.score !== a.score) {
				return b.score - a.score;
			}
			return a.index - b.index;
		});
	}

	function chooseRec(pool, picks, typeName, kind, fallback) {
		var ranked = rankedRecs(pool, picks, typeName, kind);
		return ranked.length ? ranked[0].candidate : fallback;
	}

	function normalizeMatchText(text) {
		return String(text || '').toLowerCase().replace(/&[#a-z0-9]+;/g, ' ').replace(/[^a-z0-9]+/g, ' ').replace(/\s+/g, ' ').trim();
	}

	function manMatchesBook(man, book) {
		var bookTitle = normalizeMatchText(book.title || book.source || '');
		var manSource = normalizeMatchText(man.source || '');
		var manSeries = Array.isArray(man.series) ? man.series.map(normalizeMatchText) : [];
		var bookSeries = Array.isArray(book.series) ? book.series.map(normalizeMatchText) : [];

		if (bookTitle && manSource && (manSource.indexOf(bookTitle) !== -1 || bookTitle.indexOf(manSource) !== -1)) {
			return 100;
		}

		if (bookSeries.some(function (series) {
			return series && manSeries.some(function (candidate) {
				return candidate && (candidate === series || candidate.indexOf(series) !== -1 || series.indexOf(candidate) !== -1);
			});
		})) {
			return 60;
		}

		return 0;
	}

	function chooseManForBook(pool, book, picks, typeName, fallback) {
		var ranked = rankedRecs(pool, picks, typeName, 'man').map(function (rankedCandidate) {
			var candidate = rankedCandidate.candidate;
			var pairScore = manMatchesBook(candidate, book || {});
			return {
				candidate: candidate,
				score: pairScore + rankedCandidate.score,
				pairScore: pairScore,
				index: rankedCandidate.index,
			};
		}).sort(function (a, b) {
			if (b.pairScore !== a.pairScore) {
				return b.pairScore - a.pairScore;
			}
			if (b.score !== a.score) {
				return b.score - a.score;
			}
			return a.index - b.index;
		});

		return ranked.length ? ranked[0].candidate : fallback;
	}

	function choosePairedResult(bookPool, manPool, picks, typeName) {
		var books = rankedRecs(bookPool, picks, typeName, 'book');
		var men = rankedRecs(manPool, picks, typeName, 'man');
		var best = null;
		var boardUrls = boardUrlMap();

		books.forEach(function (bookRank) {
			men.forEach(function (manRank) {
				var pairScore = manMatchesBook(manRank.candidate, bookRank.candidate);
				if (pairScore <= 0) {
					return;
				}

				var score = pairScore + bookRank.score + (manRank.score * 0.25);
				if (!best || score > best.score) {
					best = {
						book: bookRank.candidate,
						man: manRank.candidate,
						score: score,
					};
				}
			});
		});

		men.forEach(function (manRank) {
			var sourceBook = manRank.candidate && manRank.candidate.sourceBook ? manRank.candidate.sourceBook : null;
			if (!sourceBook || !sourceBook.url || boardUrls[sourceBook.url]) {
				return;
			}

			var sourceBookScore = recScore(sourceBook, picks, typeName, 'book');
			var score = 90 + sourceBookScore + (manRank.score * 0.25);
			if (!best || score > best.score) {
				best = {
					book: sourceBook,
					man: manRank.candidate,
					score: score,
				};
			}
		});

		return best;
	}

	function resultRecommendations(type, marked) {
		var picks = selectedSquares(marked || []);
		var fallback = resultRecs.fallback || {};
		var pools = resultRecs.pools || {};
		var paired = choosePairedResult(pools.books, pools.men, picks, type[0]);
		if (paired) {
			return {
				book: paired.book,
				man: paired.man,
			};
		}

		var book = chooseRec(pools.books, picks, type[0], 'book', fallback.book || {});

		return {
			book: book,
			man: chooseManForBook(pools.men, book, picks, type[0], fallback.man || {}),
		};
	}

	function readerType(marked, bingo) {
		if (marked.length >= 18) {
			return ['the full-card chaos reader', 'you are not playing bingo. you are building evidence. romance reading bingo 2026 has met its most committed reader.'];
		}
		if (categoryCount(marked, 'fictional man') >= 3) {
			return ['the book boyfriend archivist', 'your fictional men have categories, subcategories, and probably a notes app entry.'];
		}
		if (categoryCount(marked, 'new release') >= 3) {
			return ['the new-release hunter', 'your TBR has a publication calendar, a preorder strategy, and absolutely no mercy.'];
		}
		if (marked.indexOf(5) !== -1 || marked.indexOf(17) !== -1 || marked.indexOf(20) !== -1) {
			return ['the shadow-season reader', 'dark romance, romantasy bingo, danger with feelings: your October board is already calling.'];
		}
		if (categoryCount(marked, 'trope') >= 4 || marked.length >= 8) {
			return ['the trope loyalist', 'you know what you like, you respect the formula, and you still want it to ruin you in a fresh way.'];
		}
		if (bingo) {
			return ['the seasonal sampler', 'you like a balanced board: a little trope, a little new release, a little fictional-man problem. very summer, very booked.'];
		}
		return ['the TBR flirt', 'you are circling the board, collecting possibilities, and pretending this is not about to become a full summer romance reading challenge 2026.'];
	}

	function buildShareText(type, rec) {
		rec = rec || {};
		var book = rec.book && rec.book.title ? ' My read-next is ' + rec.book.title + '.' : '';
		var man = rec.man && rec.man.title ? ' The fictional man who would ruin me is ' + rec.man.title + '.' : '';
		return 'I got ' + type[0] + ' on the bybookishbabe romance reading bingo 2026 board.' + book + man + ' #bookishbabebingo';
	}

	function publicBingoUrl(path) {
		var isLocal = /(^localhost$|^127\.0\.0\.1$|\.local$)/.test(window.location.hostname);
		if (isLocal) {
			return 'https://bybookishbabe.com' + path;
		}

		return window.location.origin + path;
	}

	function buildPinMediaUrl(type, rec) {
		rec = rec || {};
		var book = rec.book || {};
		var man = rec.man || {};
		var mediaUrl = new URL(publicBingoUrl('/'));
		mediaUrl.searchParams.set('bbb_bingo_pin', '1');
		mediaUrl.searchParams.set('type', type[0]);
		mediaUrl.searchParams.set('copy', type[1]);
		mediaUrl.searchParams.set('book', book.title || 'browse the library');
		mediaUrl.searchParams.set('man', man.title || 'take the fictional boyfriend quiz');
		return mediaUrl.toString();
	}

	function updatePinLink(type, rec) {
		if (!pinLink) {
			return;
		}

		var pageUrl = /(^localhost$|^127\.0\.0\.1$|\.local$)/.test(window.location.hostname) ? publicBingoUrl('/romance-reading-bingo/') : window.location.href.split('#')[0];
		var description = buildShareText(type, rec);
		var media = '&media=' + encodeURIComponent(buildPinMediaUrl(type, rec));
		pinLink.href = 'https://www.pinterest.com/pin/create/button/?url=' + encodeURIComponent(pageUrl) + media + '&description=' + encodeURIComponent(description);
	}

	function setMatch(linkEl, mediaEl, titleEl, rec, fallbackText) {
		rec = rec || {};
		if (linkEl && rec.url) {
			linkEl.href = rec.url;
		}
		if (titleEl) {
			titleEl.textContent = rec.title || fallbackText;
		}
		if (mediaEl) {
			if (rec.image) {
				mediaEl.innerHTML = '<img src="' + rec.image.replace(/"/g, '&quot;') + '" alt="" loading="lazy">';
			} else {
				mediaEl.innerHTML = '<span aria-hidden="true">♡</span>';
			}
		}
	}

	function openModal(type, marked) {
		if (!modal) {
			return;
		}

		if (modalType) {
			modalType.textContent = type[0];
		}
		if (modalCopy) {
			modalCopy.textContent = type[1];
		}
		var rec = resultRecommendations(type, marked || []);
		setMatch(bookLink, bookMedia, bookTitle, rec.book, 'browse the library');
		setMatch(manLink, manMedia, manTitle, rec.man, 'take the fictional boyfriend quiz');
		currentShareText = buildShareText(type, rec);
		updatePinLink(type, rec);
		modal.hidden = false;
		modal.classList.remove('is-celebrating');
		window.setTimeout(function () {
			modal.classList.add('is-celebrating');
		}, 20);
	}

	function closeModal() {
		if (modal) {
			modal.hidden = true;
			modal.classList.remove('is-celebrating');
		}
	}

	function copyText(text, button) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function () {
				if (button) {
					button.textContent = 'copied';
				}
			});
		} else if (button) {
			button.textContent = text;
		}
	}

	function copyBoardLink(url) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(url);
		}

		var field = document.createElement('textarea');
		field.value = url;
		field.setAttribute('readonly', '');
		field.style.position = 'fixed';
		field.style.left = '-9999px';
		document.body.appendChild(field);
		field.select();

		try {
			document.execCommand('copy');
		} finally {
			document.body.removeChild(field);
		}

		return Promise.resolve();
	}

	function showShareToast(message) {
		if (!shareToast) {
			return;
		}

		shareToast.textContent = message || 'link copied';
		shareToast.classList.add('is-visible');
		window.clearTimeout(showShareToast.timeout);
		showShareToast.timeout = window.setTimeout(function () {
			shareToast.classList.remove('is-visible');
		}, 1800);
	}

	function shareBoard() {
		var url = window.location.href.split('#')[0];
		var shareData = {
			title: 'romance reading bingo 2026',
			text: 'mark the board with me and tell me what kind of romance reader you are.',
			url: url,
		};

		if (navigator.share) {
			navigator.share(shareData).catch(function (error) {
				if (!error || error.name === 'AbortError') {
					return;
				}
				copyBoardLink(url).then(function () {
					showShareToast('link copied');
				});
			});
			return;
		}

		copyBoardLink(url).then(function () {
			showShareToast('link copied');
		});
	}

	function update() {
		var marked = squares.reduce(function (selected, square, index) {
			if (square.classList.contains('is-marked')) {
				selected.push(index);
			}
			return selected;
		}, []);
		var bingo = hasBingo(marked);
		var type = readerType(marked, bingo);
		var rec = resultRecommendations(type, marked);
		currentShareText = buildShareText(type, rec);

		if (countEl) {
			countEl.textContent = String(marked.length);
		}

		if (resultEl) {
			resultEl.classList.toggle('has-bingo', bingo);
			resultEl.innerHTML = '<p class="bbb-bingo__kicker">' + (bingo ? 'bingo earned' : 'current result') + '</p><h3>' + type[0] + '</h3><p>' + type[1] + '</p>' + (bingo ? '<button class="bbb-bingo__resultButton" type="button" data-bbb-bingo-open-result>open share card</button>' : '');
			var resultButton = resultEl.querySelector('[data-bbb-bingo-open-result]');
			if (resultButton) {
				resultButton.addEventListener('click', function () {
					openModal(type, marked);
				});
			}
		}

		saveMarked(marked);
		updatePinLink(type, rec);

		if (bingo && !lastHadBingo) {
			openModal(type, marked);
		}
		lastHadBingo = bingo;
	}

	loadMarked().concat([12]).forEach(function (index) {
		if (squares[index]) {
			squares[index].classList.add('is-marked');
			squares[index].setAttribute('aria-pressed', 'true');
		}
	});

	squares.forEach(function (square, index) {
		square.addEventListener('click', function () {
			if (index === 12) {
				return;
			}
			var pressed = square.classList.toggle('is-marked');
			square.setAttribute('aria-pressed', pressed ? 'true' : 'false');
			update();
		});
	});

	if (resetBtn) {
		resetBtn.addEventListener('click', function () {
			squares.forEach(function (square, index) {
				var isFree = index === 12;
				square.classList.toggle('is-marked', isFree);
				square.setAttribute('aria-pressed', isFree ? 'true' : 'false');
			});
			lastHadBingo = false;
			closeModal();
			update();
		});
	}

	if (copyBtn) {
		copyBtn.addEventListener('click', function () {
			copyText(currentShareText, copyBtn);
		});
	}

	if (shareBtn) {
		shareBtn.addEventListener('click', shareBoard);
	}

	if (copyCardBtn) {
		copyCardBtn.addEventListener('click', function () {
			copyText(currentShareText, copyCardBtn);
		});
	}

	closeButtons.forEach(function (button) {
		button.addEventListener('click', closeModal);
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closeModal();
		}
	});

	update();
}());
