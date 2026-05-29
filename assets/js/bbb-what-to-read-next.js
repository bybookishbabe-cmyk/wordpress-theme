(function() {
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function normalizeText(value) {
    return String(value || '').toLowerCase().trim();
  }

  function includesAny(value, needles) {
    var text = normalizeText(value);
    return (needles || []).some(function(needle) {
      return text.indexOf(normalizeText(needle)) !== -1;
    });
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function(char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
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

  function customTropeKey(trope) {
    var value = normalizeText(trope).replace(/^[^\w\s]+/i, '').replace(/\s+/g, ' ').trim();
    var key = value.replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

    if (key === 'billionaire') return 'billionaire-romance';
    if (key === 'bodyguard') return 'bodyguard-romance';
    if (key === 'bully') return 'bully-romance';
    if (key === 'brother-s-best-friend' || key === 'brother-best-friend' || value.indexOf("brother's best friend") !== -1) return 'brothers-best-friend';
    if (key === 'captor-captive-romance' || value.indexOf('captor') !== -1 || value.indexOf('captive') !== -1) return 'captor-x-captive';
    if (key === 'fake-dating-romance') return 'fake-dating';
    if (key === 'forbidden-romance') return 'forbidden-love';
    if (key === 'grumpy-sunshine') return 'grumpy-x-sunshine';
    if (key === 'nanny-romance') return 'nanny';
    if (key === 'paranormal') return 'paranormal-romance';
    if (key === 'single-dad-romance') return 'single-dad';
    if (key === 'small-town-romance') return 'small-town';
    if (key === 'sports') return 'sports-romance';
    if (key === 'stalker') return 'stalker-romance';
    if (key === 'stepsiblings') return 'step-siblings';
    if (key === 'villain-romance') return 'villain-gets-the-girl';
    if (key === 'workplace') return 'boss-x-employee';

    return key;
  }

  function customTropeEmojiHtml(trope) {
    var map = window.BBBSiteData && window.BBBSiteData.customTropeEmojis ? window.BBBSiteData.customTropeEmojis : {};
    var src = map[customTropeKey(trope)];
    if (!src) return '';

    return '<img class="bbb-custom-emoji" src="' + escapeHtml(src) + '" alt="" aria-hidden="true" loading="lazy" decoding="async">';
  }

  function tropeEmoji(trope) {
    var value = normalizeText(trope);
    if (includesAny(value, ['slow burn', 'yearning'])) return '\uD83D\uDD6F\uFE0F';
    if (includesAny(value, ['enemies to lovers', 'rivals', 'banter', 'hate to love'])) return '\u2694\uFE0F';
    if (includesAny(value, ['friends to lovers', 'comfort', 'healing', 'found family'])) return '\uD83E\uDD0D';
    if (includesAny(value, ['forced proximity', 'one bed'])) return '\uD83D\uDECF\uFE0F';
    if (includesAny(value, ['fake dating', 'marriage of convenience'])) return '\uD83D\uDC8D';
    if (includesAny(value, ['second chance', 'emotional damage', 'angst'])) return '\uD83D\uDC94';
    if (includesAny(value, ['dark', 'morally gray', 'villain', 'mafia'])) return '\uD83E\uDD40';
    if (includesAny(value, ['obsession', 'stalker', 'possessive', 'touch her'])) return '\uD83D\uDDA4';
    if (includesAny(value, ['sports', 'hockey'])) return '\uD83C\uDFD2';
    if (includesAny(value, ['forbidden'])) return '\uD83C\uDF52';
    if (includesAny(value, ['grumpy'])) return '\u2615';
    if (includesAny(value, ['small town'])) return '\uD83C\uDF42';
    if (includesAny(value, ['romantasy', 'fantasy', 'fated mates', 'paranormal'])) return '\uD83C\uDF19';
    if (includesAny(value, ['workplace', 'billionaire'])) return '\uD83D\uDC8B';
    return '\uD83D\uDCDA';
  }

  function tropeEmojiEntity(trope) {
    var value = normalizeText(trope);
    if (includesAny(value, ['slow burn', 'yearning'])) return '&#x1f56f;&#xfe0f;';
    if (includesAny(value, ['enemies to lovers', 'rivals', 'banter', 'hate to love'])) return '&#x2694;&#xfe0f;';
    if (includesAny(value, ['friends to lovers', 'comfort', 'healing', 'found family'])) return '&#x1f90d;';
    if (includesAny(value, ['forced proximity', 'one bed'])) return '&#x1f6cf;&#xfe0f;';
    if (includesAny(value, ['fake dating', 'marriage of convenience'])) return '&#x1f48d;';
    if (includesAny(value, ['second chance', 'emotional damage', 'angst'])) return '&#x1f494;';
    if (includesAny(value, ['dark', 'morally gray', 'villain', 'mafia'])) return '&#x1f940;';
    if (includesAny(value, ['obsession', 'stalker', 'possessive', 'touch her'])) return '&#x1f5a4;';
    if (includesAny(value, ['sports', 'hockey'])) return '&#x1f3d2;';
    if (includesAny(value, ['forbidden'])) return '&#x1f352;';
    if (includesAny(value, ['grumpy'])) return '&#x2615;';
    if (includesAny(value, ['small town'])) return '&#x1f342;';
    if (includesAny(value, ['romantasy', 'fantasy', 'fated mates', 'paranormal'])) return '&#x1f319;';
    if (includesAny(value, ['workplace', 'billionaire'])) return '&#x1f48b;';
    return '&#x1f4da;';
  }

  function tropeLabel(trope) {
    var value = String(trope || '').trim().toLowerCase().replace(/^[^\w\s]+\s*/i, '').trim();
    if (!value) return '';
    return tropeEmoji(value) + ' ' + value;
  }

  function tropeLabelHtml(trope) {
    var value = String(trope || '').trim().toLowerCase().replace(/^[^\w\s]+\s*/i, '').trim();
    if (!value) return '';
    var custom = customTropeEmojiHtml(value);
    if (custom) return custom + ' <span class="bbb-custom-emoji-label">' + escapeHtml(value) + '</span>';
    return tropeEmojiEntity(value) + ' ' + escapeHtml(value);
  }

  function tropeList(tropes, separator) {
    return (tropes || []).map(tropeLabel).filter(Boolean).join(separator || ', ');
  }

  function tropeListHtml(tropes, separator) {
    return (tropes || []).map(tropeLabelHtml).filter(Boolean).join(escapeHtml(separator || ', '));
  }

  var preferenceMap = {
    vibe: {
      emotional: { shelf: 'contemporary romance', tropes: ['second chance romance', 'slow burn', 'friends to lovers', 'trauma bonding'] },
      danger: { shelf: 'dark romance', tropes: ['stalker romance', 'touch her and die', 'mafia romance', 'enemies to lovers'] },
      fantasy: { shelf: 'romantasy', tropes: ['fated mates', 'enemies to lovers', 'slow burn', 'found family'] },
      banter: { shelf: 'contemporary romance', tropes: ['fake dating romance', 'opposites attract', 'forced proximity', 'friends to lovers'] }
    },
    heat: {
      soft: 1,
      warm: 2,
      spicy: 4,
      feral: 5
    },
    darkness: {
      soft: 0,
      messy: 2,
      dark: 4,
      unhinged: 5
    }
  };

  function answersFromForm(form) {
    var data = new window.FormData(form);
    return {
      vibe: String(data.get('vibe') || ''),
      heat: String(data.get('heat') || ''),
      darkness: String(data.get('darkness') || ''),
      access: String(data.get('access') || 'any')
    };
  }

  function buildSeedBook(anchor, answers) {
    var vibe = preferenceMap.vibe[answers.vibe] || {};
    var seed = Object.assign({}, anchor || {});
    var seedTropes = (anchor && anchor.tropes ? anchor.tropes : []).concat(vibe.tropes || []);

    seed.handle = anchor && anchor.handle ? anchor.handle : '';
    seed.title = anchor && anchor.title ? anchor.title : 'your custom reader mood';
    seed.author = anchor && anchor.author ? anchor.author : '';
    seed.shelf = anchor && anchor.shelf ? anchor.shelf : (vibe.shelf || '');
    seed.tropes = uniq(seedTropes);
    seed.spice = preferenceMap.heat[answers.heat] || anchor && anchor.spice || 0;
    seed.darkness = preferenceMap.darkness[answers.darkness] != null ? preferenceMap.darkness[answers.darkness] : (anchor && anchor.darkness || 0);
    seed.preferences = {
      access: answers.access,
      shelf: vibe.shelf || '',
      tropes: vibe.tropes || [],
      vibe: answers.vibe,
      hasAnchor: Boolean(anchor && anchor.handle)
    };

    return seed;
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
    var preferences = baseBook.preferences || {};
    var preferredTropes = (preferences.tropes || []).map(normalizeText);
    var candidateTropes = (candidate.tropes || []).map(normalizeText);
    var preferredOverlap = preferredTropes.filter(function(trope) {
      return candidateTropes.indexOf(trope) !== -1;
    }).length;
    var preferredShelf = preferences.shelf && candidate.shelf && normalizeText(preferences.shelf) === normalizeText(candidate.shelf);
    var kuScore = preferences.access === 'ku' ? (candidate.ku === 'true' ? 55 : -25) : 0;
    var matchScore = (shared.length * 100) + (preferredOverlap * 42) + (relatedScore * 28) + (sameShelf ? 150 : 0) + (preferredShelf ? 70 : 0) + boyfriendScore + kuScore - (spiceDiff * 10) - (darknessDiff * 8);
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
      return !selected.handle || book.handle !== selected.handle;
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
    return [first, second, third].filter(Boolean);
  }

  function metaText(book, shared) {
    var pieces = [];
    if (book.shelf) pieces.push(book.shelf);
    if (book.spice) pieces.push(book.spice + '/5 spice');
    return pieces.join(' · ');
  }

  function reasonText(match, index) {
    if (!match) return '';
    var book = match.book || {};
    var tropes = uniq((match.shared || []).concat(book.tropes || []));
    return tropes.length ? 'tropes: ' + tropeList(tropes.slice(0, 5), ', ') : '';
  }

  function reasonHtml(match, index) {
    if (!match) return '';
    var book = match.book || {};
    var tropes = uniq((match.shared || []).concat(book.tropes || [])).slice(0, 5);
    return tropes.length ? '<span>tropes: </span>' + tropeListHtml(tropes, ', ') : '';
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
    var modePanel = section.querySelector('[data-next-mode-panel]');
    var libraryPanel = section.querySelector('[data-next-library-panel]');
    var quizForm = section.querySelector('[data-next-quiz]');
    var quizKicker = section.querySelector('[data-next-quiz-kicker]');
    var pickerInput = section.querySelector('[data-next-picker]');
    var searchModal = section.querySelector('[data-next-search-modal]');
    var searchResults = section.querySelector('[data-next-search-results]');
    var sourceWrap = section.querySelector('[data-next-source]');
    var resultsWrap = section.querySelector('[data-next-results]');
    var actionsWrap = section.querySelector('[data-next-actions]');
    var clearButton = section.querySelector('[data-next-clear]');
    var refreshButton = section.querySelector('[data-next-refresh]');
    var resetButton = section.querySelector('[data-next-reset]');
    var takeAgainButton = section.querySelector('[data-next-take-again]');
    var shareButton = section.querySelector('[data-next-share]');
    var resultsSub = section.querySelector('[data-next-results-sub]');
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
    var selectedAnchor = null;
    var currentAnswers = null;
    var currentMode = '';
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

    function showQuiz(mode) {
      currentMode = mode || currentMode || 'specific';
      setHidden(quizForm, false);
      setHidden(resultsWrap, true);
      setHidden(actionsWrap, true);
      if (quizKicker) {
        quizKicker.textContent = currentMode === 'library' ? 'tune the match' : 'tiny reader interview';
      }
    }

    function renderCard(card, match, index) {
      if (!card || !match) {
        setHidden(card, true);
        card.classList.remove('is-swipe-visible');
        return;
      }
      var book = match.book;
      setHidden(card, false);
      card.classList.remove('is-swipe-visible');
      card.style.setProperty('--bbb-next-card-delay', String(index * 140) + 'ms');
      var open = card.querySelector('[data-next-open]');
      var cover = card.querySelector('[data-next-cover]');
      var spice = card.querySelector('[data-next-spice]');
      var amazonLink = card.querySelector('[data-next-amazon]');
      var bookshopLink = card.querySelector('[data-next-bookshop]');
      var grabLinks = card.querySelector('[data-next-grab-links]');
      setBookAttrs(open, book);
      card.querySelector('[data-next-mood]').textContent = match.shared.length ? match.shared.slice(0, 2).join(' + ') : (book.shelf || 'library match');
      card.querySelector('[data-next-title]').textContent = book.title || '';
      card.querySelector('[data-next-author]').textContent = book.author ? 'by ' + book.author : '';
      card.querySelector('[data-next-meta]').textContent = metaText(book, match.shared);
      card.querySelector('[data-next-reason]').innerHTML = reasonHtml(match, index);
      if (cover) {
        cover.src = book.cover || '';
        cover.alt = book.title || '';
      }
      if (spice) {
        spice.hidden = !book.spice;
        spice.textContent = book.spice ? '🌶'.repeat(Math.max(0, Math.min(5, Number(book.spice)))) : '';
      }
      if (amazonLink) {
        amazonLink.hidden = !book.amazon;
        amazonLink.href = book.amazon || '#';
      }
      if (bookshopLink) {
        bookshopLink.hidden = !book.bookshop;
        bookshopLink.href = book.bookshop || '#';
      }
      if (grabLinks) {
        grabLinks.hidden = !book.amazon && !book.bookshop;
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

    function renderResults(book, answers) {
      selected = book;
      currentAnswers = answers || currentAnswers || {};
      if (book.handle && pickerLabel) {
        pickerLabel.textContent = book.label || book.title;
      }
      setHidden(resultsWrap, false);
      setHidden(actionsWrap, false);
      if (resultsSub) {
        resultsSub.textContent = 'start with the closest shelf twin, then move into the trope twin, then the spice-mood wildcard.';
      }
      var matches = getMatches(books, book, rotationStep);
      var renderedCards = [];
      Array.prototype.slice.call(section.querySelectorAll('[data-next-card]')).forEach(function(card, index) {
        renderCard(card, matches[index], index);
        if (matches[index]) renderedCards.push(card);
      });
      window.setTimeout(function() {
        renderedCards.forEach(function(card, index) {
          window.setTimeout(function() {
            card.classList.add('is-swipe-visible');
          }, index * 140);
        });
      }, 30);
    }

    function collapsePrompt() {
      setHidden(modePanel, true);
      setHidden(libraryPanel, true);
      setHidden(sourceWrap, true);
      setHidden(quizForm, true);
      section.classList.add('has-results');
    }

    function resetFlow() {
      selected = null;
      selectedAnchor = null;
      currentAnswers = null;
      currentMode = '';
      pickerLabel.textContent = 'pick a book to start';
      if (quizForm) quizForm.reset();
      setHidden(modePanel, false);
      setHidden(libraryPanel, true);
      setHidden(quizForm, true);
      setHidden(sourceWrap, true);
      setHidden(resultsWrap, true);
      setHidden(actionsWrap, true);
      section.classList.remove('has-results');
    }

    function shareResults() {
      var url = new URL(window.location.href);
      if (selected && selected.handle) {
        url.searchParams.set('book', selected.handle);
      }
      var shareData = {
        title: 'what to read next',
        text: 'here are my bybookishbabe next-read picks',
        url: url.toString()
      };
      var shareLabel = shareButton ? shareButton.querySelector('[data-next-share-label]') : null;
      var originalLabel = shareLabel ? shareLabel.textContent : (shareButton ? shareButton.textContent : '');
      var markShared = function(label) {
        if (!shareButton) return;
        if (shareLabel) shareLabel.textContent = label;
        else shareButton.textContent = label;
        window.setTimeout(function() {
          if (shareLabel) shareLabel.textContent = originalLabel || 'share result';
          else shareButton.textContent = originalLabel || 'share result';
        }, 1800);
      };

      if (navigator.share) {
        navigator.share(shareData).catch(function() {});
        return;
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(shareData.url).then(function() {
          markShared('copied');
        }).catch(function() {
          markShared('copy failed');
        });
      }
    }

    if (modePanel) {
      modePanel.addEventListener('click', function(event) {
        var button = event.target.closest('[data-next-mode]');
        if (!button) return;
        currentMode = button.getAttribute('data-next-mode') || '';
        setHidden(modePanel, true);
        setHidden(resultsWrap, true);
        setHidden(actionsWrap, true);
        if (currentMode === 'library') {
          setHidden(libraryPanel, false);
          setHidden(quizForm, true);
          setHidden(sourceWrap, true);
        } else {
          setHidden(libraryPanel, true);
          setHidden(sourceWrap, true);
          selectedAnchor = null;
          showQuiz('specific');
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
        selectedAnchor = byHandle[handle];
        pickerLabel.textContent = selectedAnchor.label || selectedAnchor.title;
        currentAnswers = null;
        rotationStep = 0;
        renderResults(selectedAnchor);
        collapsePrompt();
        resultsWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
    if (quizForm) {
      quizForm.addEventListener('submit', function(event) {
        event.preventDefault();
        var answers = answersFromForm(quizForm);
        currentAnswers = answers;
        rotationStep = 0;
        renderResults(buildSeedBook(selectedAnchor, answers), answers);
        collapsePrompt();
        resultsWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }
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
        renderResults(selected, currentAnswers);
      }
    });
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && !searchModal.hidden) closeSearch();
    });
    if (clearButton) {
      clearButton.addEventListener('click', function() {
        selected = null;
        selectedAnchor = null;
        pickerLabel.textContent = 'pick a book to start';
        setHidden(sourceWrap, true);
        setHidden(resultsWrap, true);
        setHidden(actionsWrap, true);
        setHidden(quizForm, true);
      });
    }
    if (resetButton) {
      resetButton.addEventListener('click', resetFlow);
    }
    if (takeAgainButton) {
      takeAgainButton.addEventListener('click', resetFlow);
    }
    if (shareButton) {
      shareButton.addEventListener('click', shareResults);
    }
    if (refreshButton) {
      refreshButton.addEventListener('click', function() {
        if (!selected) return;
        rotationStep += 1;
        renderResults(selected, currentAnswers);
      });
    }

    var url = new URL(window.location.href);
    var initial = url.searchParams.get('book') || url.searchParams.get('source') || '';
    if (initial && byHandle[initial]) {
      selectedAnchor = byHandle[initial];
      pickerLabel.textContent = selectedAnchor.label || selectedAnchor.title;
      currentAnswers = null;
      renderResults(selectedAnchor);
      collapsePrompt();
    }
  });
})();
