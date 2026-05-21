(function() {
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function normalizeText(value) {
    return String(value || '').toLowerCase().trim();
  }

  function uniq(items) {
    var seen = {};
    return (items || []).filter(function(item) {
      var key = normalizeText(item);
      if (!key || seen[key]) return false;
      seen[key] = true;
      return true;
    });
  }

  function setHidden(el, hidden) {
    if (!el) return;
    el.hidden = hidden;
    el.classList.toggle('is-hidden', hidden);
  }

  function dataAttrValue(book, key) {
    var value = book[key];
    if (Array.isArray(value)) return value.join(', ');
    return value == null ? '' : String(value);
  }

  function setBookAttrs(el, book) {
    if (!el || !book) return;
    var tropes = uniq(book.tropes || []);
    var attrs = {
      handle: book.handle,
      title: book.title,
      author: book.author,
      cover: book.cover,
      amazon: book.amazon,
      bookshop: book.bookshop,
      shelf: book.shelf,
      'private-shelf': 'false',
      spice: book.spice || '',
      tropes: tropes.join(', '),
      'tropes-display': tropes.join(', '),
      'trope-urls': tropes.map(function(trope) { return '/' + String(trope).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') + '-books/'; }).join(', '),
      why: book.why || '',
      newsletter: book.newsletter || '',
      mini: book.mini || '',
      series: book.series || '',
      'series-name': book.seriesName || '',
      'series-number': book.seriesNumber || '',
      tension: book.tension || '',
      damage: book.damage || '',
      yearning: book.yearning || '',
      boyfriend: book.boyfriend || '',
      'boyfriend-name': book.boyfriendName || '',
      reread: book.reread || '',
      standalone: 'true',
      ku: book.ku || '',
      darkness: book.darkness || ''
    };
    Object.keys(attrs).forEach(function(key) {
      el.setAttribute('data-' + key, dataAttrValue(attrs, key));
    });
  }

  function sharedTropes(baseBook, candidate) {
    var selected = (baseBook.tropes || []).map(normalizeText);
    return uniq((candidate.tropes || []).filter(function(trope) {
      return selected.indexOf(normalizeText(trope)) !== -1;
    }));
  }

  function relatedTropeScore(baseBook, candidate) {
    var relatedMap = {
      'touch her and die': ['trauma bonding', 'forced proximity', 'who did this to you', 'enemies to lovers', 'protective hero'],
      'trauma bonding': ['touch her and die', 'forced proximity', 'who did this to you', 'enemies to lovers'],
      'forced proximity': ['touch her and die', 'trauma bonding', 'marriage of convenience', 'enemies to lovers'],
      'enemies to lovers': ['forced proximity', 'touch her and die', 'hate to love', 'rivals to lovers'],
      'slow burn': ['yearning', 'forced proximity', 'enemies to lovers'],
      'who did this to you': ['touch her and die', 'protective hero', 'trauma bonding']
    };
    var selected = (baseBook.tropes || []).map(normalizeText);
    var candidateTropes = (candidate.tropes || []).map(normalizeText);
    var score = 0;
    selected.forEach(function(trope) {
      (relatedMap[trope] || []).forEach(function(related) {
        if (candidateTropes.indexOf(related) !== -1) score += 1;
      });
    });
    return score;
  }

  function scoreCandidate(baseBook, candidate) {
    var shared = sharedTropes(baseBook, candidate);
    var sameShelf = baseBook.shelf && candidate.shelf && normalizeText(baseBook.shelf) === normalizeText(candidate.shelf);
    var spiceDiff = Math.abs(Number(baseBook.spice || 0) - Number(candidate.spice || 0));
    var darknessDiff = Math.abs(Number(baseBook.darkness || 0) - Number(candidate.darkness || 0));
    var relatedScore = relatedTropeScore(baseBook, candidate);
    var boyfriendScore = baseBook.boyfriend && normalizeText(baseBook.boyfriend) === normalizeText(candidate.boyfriend) ? 24 : 0;
    var matchScore = (shared.length * 100) + (relatedScore * 28) + (sameShelf ? 150 : 0) + boyfriendScore - (spiceDiff * 10) - (darknessDiff * 8);
    return { book: candidate, shared: shared, sameShelf: sameShelf, spiceDiff: spiceDiff, darknessDiff: darknessDiff, relatedScore: relatedScore, matchScore: matchScore };
  }

  function sortByStrength(a, b) {
    if (b.matchScore !== a.matchScore) return b.matchScore - a.matchScore;
    if (b.sameShelf !== a.sameShelf) return b.sameShelf ? 1 : -1;
    if (b.shared.length !== a.shared.length) return b.shared.length - a.shared.length;
    if (b.relatedScore !== a.relatedScore) return b.relatedScore - a.relatedScore;
    if (a.darknessDiff !== b.darknessDiff) return a.darknessDiff - b.darknessDiff;
    if (a.spiceDiff !== b.spiceDiff) return a.spiceDiff - b.spiceDiff;
    return String(a.book.title).localeCompare(String(b.book.title));
  }

  function pick(pool, used, offset) {
    if (!pool.length) return null;
    var start = offset % pool.length;
    for (var i = 0; i < pool.length; i += 1) {
      var candidate = pool[(start + i) % pool.length];
      if (used.indexOf(candidate.book.handle) === -1) return candidate;
    }
    return null;
  }

  function getMatches(books, selected, rotationStep) {
    var candidates = books.filter(function(book) {
      return book.handle !== selected.handle;
    }).map(function(book) {
      return scoreCandidate(selected, book);
    }).sort(sortByStrength);
    var used = [];
    var sameShelfPool = candidates.filter(function(candidate) {
      return candidate.sameShelf && candidate.shared.length >= 2;
    });
    if (!sameShelfPool.length) {
      sameShelfPool = candidates.filter(function(candidate) {
        return candidate.sameShelf;
      });
    }
    var tropePool = candidates.filter(function(candidate) {
      return candidate.shared.length >= 2;
    });
    if (!tropePool.length) {
      tropePool = candidates.filter(function(candidate) {
        return candidate.shared.length >= 1;
      });
    }
    var spicePool = candidates.filter(function(candidate) {
      return candidate.shared.length >= 1 && candidate.spiceDiff <= 1;
    });
    if (!spicePool.length) spicePool = candidates;

    var first = pick(sameShelfPool, used, rotationStep);
    if (first) used.push(first.book.handle);
    var second = pick(tropePool, used, rotationStep + 1);
    if (second) used.push(second.book.handle);
    var third = pick(spicePool, used, rotationStep + 2);
    if (third) used.push(third.book.handle);
    var fourth = pick(candidates, used, rotationStep + 3);
    return [first, second, third, fourth].filter(Boolean);
  }

  function metaText(book, shared) {
    var pieces = [];
    if (book.shelf) pieces.push(book.shelf);
    if (book.spice) pieces.push(book.spice + '/5 spice');
    if (shared && shared.length) pieces.push(shared.slice(0, 2).join(' + '));
    return pieces.join(' · ');
  }

  function reasonText(match, index) {
    if (!match) return '';
    if (index === 0 && match.sameShelf && match.shared.length) {
      return 'same shelf, familiar trope chemistry, and close enough heat to keep the mood intact.';
    }
    if (index === 1 && match.shared.length) {
      return 'this one follows the trope thread: ' + match.shared.slice(0, 3).join(', ') + '.';
    }
    if (match.spiceDiff <= 1) {
      return 'a moodier wildcard with a similar spice level and enough shared texture to make sense.';
    }
    return 'a wildcard pick from the library that still has the strongest available overlap.';
  }

  function readStatusStore() {
    try {
      return JSON.parse(window.localStorage.getItem('sssBookStatuses') || '{}') || {};
    } catch (error) {
      return {};
    }
  }

  function shelfKey(book) {
    return normalizeText(book && (book.handle || book.title));
  }

  function readShelf() {
    try {
      var primary = JSON.parse(window.localStorage.getItem('sssMyShelf') || 'null');
      if (Array.isArray(primary)) return primary;
    } catch (error) {}

    try {
      var legacy = JSON.parse(window.localStorage.getItem('sssShelf') || '[]');
      return Array.isArray(legacy) ? legacy : [];
    } catch (error) {
      return [];
    }
  }

  function writeShelf(items) {
    try {
      window.localStorage.setItem('sssMyShelf', JSON.stringify(items));
      window.localStorage.setItem('sssShelf', JSON.stringify(items));
    } catch (error) {}

    document.dispatchEvent(new CustomEvent('sss:bookshelf-updated', {
      detail: { count: Array.isArray(items) ? items.length : 0 }
    }));
  }

  function shelfBook(book) {
    var tropes = uniq(book.tropes || []);
    return {
      handle: book.handle || '',
      title: book.title || '',
      author: book.author || '',
      cover: book.cover || '',
      amazon: book.amazon || '',
      bookshop: book.bookshop || '',
      spice: book.spice || '',
      darkness: book.darkness || '',
      tropes: tropes.join(', '),
      tropesDisplay: tropes.join(', '),
      why: book.why || '',
      newsletter: book.newsletter || '',
      mini: book.mini || '',
      series: book.series || '',
      seriesName: book.seriesName || '',
      seriesNumber: book.seriesNumber || '',
      tension: book.tension || '',
      damage: book.damage || '',
      yearning: book.yearning || '',
      boyfriend: book.boyfriend || '',
      boyfriendName: book.boyfriendName || '',
      reread: book.reread || '',
      ku: book.ku || '',
      standalone: 'true',
      privateShelf: 'false',
      saved_at: Date.now()
    };
  }

  function saveBookToShelf(book) {
    if (!book || !book.title) return;
    var key = shelfKey(book);
    var shelf = readShelf();
    var savedBook = shelfBook(book);
    var exists = shelf.some(function(item) {
      return shelfKey(item) === key || normalizeText(item.title) === normalizeText(book.title);
    });

    if (!exists) {
      shelf.unshift(savedBook);
      writeShelf(shelf);
    } else {
      writeShelf(shelf);
    }

    document.dispatchEvent(new CustomEvent('bbb:shelf-saved', {
      detail: {
        count: shelf.length,
        bookTitle: savedBook.title,
        bookHandle: savedBook.handle,
        book: savedBook,
        source: 'what-to-read-next'
      }
    }));
  }

  function isBookSaved(book) {
    var key = shelfKey(book);
    return readShelf().some(function(item) {
      return shelfKey(item) === key || normalizeText(item.title) === normalizeText(book && book.title);
    });
  }

  function writeStatus(handle, status) {
    if (!handle) return;
    var store = readStatusStore();
    store[handle] = status;
    try {
      window.localStorage.setItem('sssBookStatuses', JSON.stringify(store));
    } catch (error) {}

    document.dispatchEvent(new CustomEvent('bbb:book-statuses-updated', { detail: { statuses: store } }));
    document.dispatchEvent(new CustomEvent('bbb:book-status-changed', { detail: { key: handle, status: status || '', source: 'what-to-read-next' } }));
  }

  ready(function() {
    var section = document.querySelector('[data-sss-lib].bbb-next');
    if (!section) return;

    var dataNode = section.querySelector('[data-next-books]');
    var pickerTrigger = section.querySelector('[data-next-picker-trigger]');
    var pickerLabel = section.querySelector('[data-next-picker-label]');
    var pickerInput = section.querySelector('[data-next-picker]');
    var searchModal = section.querySelector('[data-next-search-modal]');
    var searchResults = section.querySelector('[data-next-search-results]');
    var sourceWrap = section.querySelector('[data-next-source]');
    var resultsWrap = section.querySelector('[data-next-results]');
    var clearButton = section.querySelector('[data-next-clear]');
    var refreshButton = section.querySelector('[data-next-refresh]');
    if (!dataNode || !pickerTrigger || !pickerInput || !searchModal || !searchResults || !sourceWrap || !resultsWrap) return;

    var books = [];
    try {
      books = JSON.parse(dataNode.textContent || '[]').filter(function(book) {
        return book.handle && book.title;
      });
    } catch (error) {
      return;
    }
    if (!books.length) {
      pickerLabel.textContent = 'waiting on imported books';
      return;
    }

    var byHandle = {};
    var rotationStep = 0;
    var selected = null;
    books.forEach(function(book) {
      byHandle[book.handle] = book;
    });

    function openSearch() {
      searchModal.hidden = false;
      pickerTrigger.setAttribute('aria-expanded', 'true');
      renderSearch('');
      window.setTimeout(function() { pickerInput.focus(); }, 20);
    }

    function closeSearch() {
      searchModal.hidden = true;
      pickerTrigger.setAttribute('aria-expanded', 'false');
      pickerInput.value = '';
    }

    function renderSearch(query) {
      var q = normalizeText(query);
      var matches = books.filter(function(book) {
        return !q || normalizeText(book.title + ' ' + book.author + ' ' + book.shelf + ' ' + (book.tropes || []).join(' ')).indexOf(q) !== -1;
      }).slice(0, 60);
      searchResults.innerHTML = matches.map(function(book) {
        return '<button type="button" class="bbb-next__searchResult" data-next-handle="' + encodeURIComponent(book.handle) + '">' +
          (book.cover ? '<img src="' + book.cover.replace(/"/g, '&quot;') + '" alt="" loading="lazy">' : '<span></span>') +
          '<span><strong>' + book.title + '</strong><em>' + (book.author || 'unknown author') + '</em></span>' +
          '</button>';
      }).join('');
    }

    function renderSource(book) {
      setHidden(sourceWrap, false);
      var cover = sourceWrap.querySelector('[data-next-source-cover]');
      var spice = sourceWrap.querySelector('[data-next-source-spice]');
      sourceWrap.querySelector('[data-next-source-title]').textContent = book.title || '';
      sourceWrap.querySelector('[data-next-source-author]').textContent = book.author ? 'by ' + book.author : '';
      sourceWrap.querySelector('[data-next-source-meta]').textContent = metaText(book, book.tropes || []);
      sourceWrap.querySelector('[data-next-source-why]').textContent = book.mini || book.why || '';
      if (cover) {
        cover.src = book.cover || '';
        cover.alt = book.title || '';
      }
      if (spice) {
        spice.hidden = !book.spice;
        spice.textContent = book.spice ? '🌶'.repeat(Math.max(0, Math.min(5, Number(book.spice)))) : '';
      }
    }

    function renderCard(card, match, index) {
      if (!card || !match) {
        setHidden(card, true);
        return;
      }
      var book = match.book;
      setHidden(card, false);
      var open = card.querySelector('[data-next-open]');
      var cover = card.querySelector('[data-next-cover]');
      var spice = card.querySelector('[data-next-spice]');
      setBookAttrs(open, book);
      card.querySelector('[data-next-mood]').textContent = match.shared.length ? match.shared.slice(0, 2).join(' + ') : (book.shelf || 'library match');
      card.querySelector('[data-next-title]').textContent = book.title || '';
      card.querySelector('[data-next-author]').textContent = book.author ? 'by ' + book.author : '';
      card.querySelector('[data-next-meta]').textContent = metaText(book, match.shared);
      card.querySelector('[data-next-reason]').textContent = reasonText(match, index);
      if (cover) {
        cover.src = book.cover || '';
        cover.alt = book.title || '';
      }
      if (spice) {
        spice.hidden = !book.spice;
        spice.textContent = book.spice ? '🌶'.repeat(Math.max(0, Math.min(5, Number(book.spice)))) : '';
      }
      var link = card.querySelector('[data-next-link]');
      if (link) {
        link.hidden = !book.url;
        link.href = book.url || '#';
      }
      Array.prototype.slice.call(card.querySelectorAll('[data-next-status]')).forEach(function(button) {
        button.setAttribute('data-next-status-handle', book.handle || '');
        button.classList.toggle('is-selected', readStatusStore()[book.handle] === button.getAttribute('data-next-status'));
        button.setAttribute('aria-pressed', button.classList.contains('is-selected') ? 'true' : 'false');
      });
      var heart = card.querySelector('[data-next-heart]');
      if (heart) {
        var saved = isBookSaved(book);
        var icon = heart.querySelector('[data-heart-icon]');
        var label = heart.querySelector('[data-heart-label]');
        heart.classList.toggle('is-saved', saved);
        heart.setAttribute('aria-label', saved ? 'saved to your bookshelf' : 'save to your bookshelf');
        if (icon) icon.textContent = saved ? '♥' : '♡';
        if (label) label.textContent = saved ? 'saved' : 'save';
      }
    }

    function renderResults(book) {
      selected = book;
      pickerLabel.textContent = book.label || book.title;
      renderSource(book);
      setHidden(resultsWrap, false);
      var matches = getMatches(books, book, rotationStep);
      Array.prototype.slice.call(section.querySelectorAll('[data-next-card]')).forEach(function(card, index) {
        card.classList.remove('is-entering');
        renderCard(card, matches[index], index);
        if (!card.hidden) {
          card.style.setProperty('--next-card-index', String(index));
          void card.offsetWidth;
          card.classList.add('is-entering');
        }
      });
    }

    pickerTrigger.addEventListener('click', openSearch);
    pickerInput.addEventListener('input', function() { renderSearch(pickerInput.value); });
    searchModal.addEventListener('click', function(event) {
      if (event.target.matches('[data-next-search-close]')) closeSearch();
      var result = event.target.closest('[data-next-handle]');
      if (!result) return;
      var handle = decodeURIComponent(result.getAttribute('data-next-handle') || '');
      if (byHandle[handle]) {
        closeSearch();
        renderResults(byHandle[handle]);
      }
    });
    section.addEventListener('click', function(event) {
      var statusButton = event.target.closest('[data-next-status]');
      if (!statusButton) return;
      event.preventDefault();
      event.stopPropagation();
      var handle = statusButton.getAttribute('data-next-status-handle') || '';
      var status = statusButton.getAttribute('data-next-status') || '';
      statusButton.classList.add('is-selected');
      statusButton.setAttribute('aria-pressed', 'true');
      Array.prototype.slice.call(statusButton.parentNode.querySelectorAll('[data-next-status]')).forEach(function(sibling) {
        if (sibling !== statusButton) {
          sibling.classList.remove('is-selected');
          sibling.setAttribute('aria-pressed', 'false');
        }
      });
      if (byHandle[handle]) {
        saveBookToShelf(byHandle[handle]);
      }
      writeStatus(handle, status);
      if (byHandle[handle]) {
        renderResults(selected);
      }
    });
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && !searchModal.hidden) closeSearch();
    });
    if (clearButton) {
      clearButton.addEventListener('click', function() {
        selected = null;
        pickerLabel.textContent = 'pick a book to start';
        setHidden(sourceWrap, true);
        setHidden(resultsWrap, true);
      });
    }
    if (refreshButton) {
      refreshButton.addEventListener('click', function() {
        if (!selected) return;
        rotationStep += 1;
        renderResults(selected);
      });
    }

    var url = new URL(window.location.href);
    var initial = url.searchParams.get('book') || url.searchParams.get('source') || '';
    if (initial && byHandle[initial]) {
      renderResults(byHandle[initial]);
    }
  });
})();
