(function () {
  var SUPABASE_URL = 'https://efmrfxsmgbeikfgtrxjv.supabase.co';
  var SUPABASE_KEY = 'sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk';

  function getJSON(key, fallback) {
    try {
      var value = JSON.parse(window.localStorage.getItem(key) || '');
      return value || fallback;
    } catch (error) {
      return fallback;
    }
  }

  function accountScopedKey(key, accountKey) {
    accountKey = String(accountKey || '').trim();
    return accountKey ? key + '::' + accountKey : key;
  }

  function cleanupLegacyKey(key, accountKey) {
    if (!accountKey) return;
    try {
      window.localStorage.removeItem(key);
    } catch (error) {}
  }

  function getShelf() {
    var primary = getJSON('sssMyShelf', null);
    if (Array.isArray(primary)) return primary;
    var legacy = getJSON('sssShelf', []);
    return Array.isArray(legacy) ? legacy : [];
  }

  function setShelf(items) {
    window.localStorage.setItem('sssMyShelf', JSON.stringify(items));
    window.localStorage.setItem('sssShelf', JSON.stringify(items));
    document.dispatchEvent(new CustomEvent('sss:bookshelf-updated', {
      detail: { count: items.length }
    }));
  }

  function esc(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function decodeHtml(value) {
    var text = String(value || '');
    if (!text) return '';
    var textarea = document.createElement('textarea');
    var decoded = text;
    for (var i = 0; i < 3; i += 1) {
      textarea.innerHTML = decoded;
      if (textarea.value === decoded) break;
      decoded = textarea.value;
    }
    return decoded;
  }

  function bookCoverAlt(book) {
    var title = String((book && book.title) || '').trim();
    var author = String((book && book.author) || '').trim();
    var shelf = String((book && book.shelf) || '').trim();

    if (!title) return 'book cover';
    if (author) title += ' by ' + author;
    if (shelf) title += ' – ' + shelf;
    return title + ' book cover';
  }

  function normalizeKey(value) {
    return String(value || '').trim().toLowerCase();
  }

  function bookIdentityAliases(book, lookup) {
    if (!book) return [];
    var aliases = [];
    var lookupKey = normalizeKey(book.handle || book.book_handle || book.bookKey || book.book_key || book.title || book.book_title);
    var found = lookup && lookupKey ? (lookup[lookupKey] || {}) : {};
    var handle = normalizeKey(book.handle || book.book_handle || found.handle);
    var bookKey = normalizeKey(book.bookKey || book.book_key);
    var title = normalizeKey(book.title || book.book_title || found.title);
    var author = normalizeKey(book.author || found.author);

    [handle, bookKey].forEach(function (value) {
      if (value) aliases.push('key:' + value);
    });
    if (title && author) aliases.push('title-author:' + title + '|' + author);
    if (title) aliases.push('title:' + title);

    return aliases.filter(function (value, index, list) {
      return value && list.indexOf(value) === index;
    });
  }

  function normalizeBool(value) {
    var text = normalizeKey(value);
    return text === 'true' || text === '1' || text === 'yes';
  }

  function parseBookData() {
    var source = document.querySelector('[data-account-library-books]');
    if (!source) return [];
    try {
      return JSON.parse(source.textContent || '[]') || [];
    } catch (error) {
      return [];
    }
  }

  function parseQuoteData() {
    var source = document.querySelector('[data-account-library-quotes]');
    if (!source) return [];
    try {
      return JSON.parse(source.textContent || '[]') || [];
    } catch (error) {
      return [];
    }
  }

  function parseEmbeddedJSON(selector, fallback) {
    var source = document.querySelector(selector);
    if (!source) return fallback;
    try {
      return JSON.parse(source.textContent || '') || fallback;
    } catch (error) {
      return fallback;
    }
  }

  function getBookStatuses() {
    try {
      return JSON.parse(window.localStorage.getItem('sssBookStatuses') || '{}') || {};
    } catch (error) {
      return {};
    }
  }

  function getBookRatings() {
    try {
      return JSON.parse(window.localStorage.getItem('sssBookRatings') || '{}') || {};
    } catch (error) {
      return {};
    }
  }

  function getBookStatusKey(book) {
    return normalizeKey(book && (book.handle || book.bookHandle || book.book_handle || book.title || book.bookTitle || book.book_title));
  }

  function buildLookup(books) {
    var lookup = {};
    books.forEach(function (book) {
      var handle = normalizeKey(book.handle);
      var title = normalizeKey(book.title);
      if (handle) lookup[handle] = book;
      if (title) lookup[title] = book;
    });
    return lookup;
  }

  function normalizeBook(book, lookup) {
    if (!book) return null;
    var lookupKey = normalizeKey(book.handle || book.book_handle || book.title || book.book_title);
    var found = lookup[lookupKey] || {};
    var tropes = Array.isArray(book.tropes) ? book.tropes.join(', ') : (book.tropes || found.tropes || '');
    if (Array.isArray(found.tropes)) tropes = tropes || found.tropes.join(', ');

    return {
      handle: book.handle || book.book_handle || found.handle || '',
      title: book.title || book.book_title || found.title || '',
      author: book.author || found.author || '',
      cover: book.cover || found.cover || '',
      amazon: book.amazon || found.amazon || '',
      bookshop: book.bookshop || found.bookshop || '',
      spice: book.spice || book.spice_level || found.spice || '',
      darkness: book.darkness || book.darkness_level || found.darkness || found.darknessRaw || '',
      tropes: tropes,
      tropesDisplay: book.tropesDisplay || book.tropes_display || book.tropes_display_text || tropes,
      why: book.why || found.why || '',
      newsletter: book.newsletter || found.newsletter || '',
      tension: book.tension || found.tension || '',
      damage: book.damage || found.damage || '',
      yearning: book.yearning || found.yearning || '',
      boyfriend: book.boyfriend || found.boyfriend || '',
      boyfriendName: book.boyfriendName || book.boyfriend_name || found.boyfriendName || '',
      reread: book.reread || found.reread || '',
      ku: book.ku || found.ku || '',
      mini: book.mini || found.mini || '',
      series: book.series || found.series || '',
      seriesName: book.seriesName || book.series_name || found.seriesName || '',
      seriesNumber: book.seriesNumber || book.series_number || found.seriesNumber || '',
      standalone: book.standalone || found.standalone || 'false',
      rating: book.rating || found.rating || '',
      progress: book.progress || book.progress_percent || book.percentComplete || book.percent_complete || found.progress || found.progress_percent || '',
      privateShelf: book.privateShelf || book.private_shelf || 'false'
    };
  }

  function bookRating(book) {
    var ratings = getBookRatings();
    var key = getBookStatusKey(book);
    var rating = parseInt((key && ratings[key]) || book.rating || 0, 10);
    return rating >= 1 && rating <= 5 ? rating : 0;
  }

  function ratingStars(rating) {
    rating = parseInt(rating || 0, 10);
    return rating >= 1 && rating <= 5 ? '★★★★★'.slice(0, rating) : '';
  }

  function getStoredMadeForYouProfile(accountKey) {
    return getJSON(accountScopedKey('sssMadeForYouProfile', accountKey), {});
  }

  function isCurrentMadeForYouProfile(profile, version) {
    return !!(profile && String(profile.mfy_profile_version || profile.profile_version || '') === String(version || ''));
  }

  function isCompleteMadeForYouProfile(profile, version) {
    if (!isCurrentMadeForYouProfile(profile, version)) return false;
    return ['name', 'heat_lane', 'group_chat_text', 'love_interest', 'wall_line'].every(function (key) {
      return String(profile && profile[key] || '').trim() !== '';
    }) && !!(profile && profile.dashboard_built);
  }

  function madeForYouReaderTypeKey(profile) {
    var direct = String(profile && (profile.reader_type_prior || profile.theme) || '').trim();
    if (direct) return direct;

    var picks = [profile && profile.group_chat_text, profile && profile.love_interest, profile && profile.wall_line].filter(Boolean).map(String);
    if (picks.length < 3) return '';

    var counts = {};
    picks.forEach(function (key) {
      counts[key] = (counts[key] || 0) + 1;
    });
    var matched = Object.keys(counts).find(function (key) {
      return counts[key] >= 2;
    });
    if (matched) return matched;

    var order = [
      'sweet_romance_devotee',
      'slow_burn_girlie',
      'fake_dating_fanatic',
      'jersey_chaser',
      'fantasy_girlie',
      'tension_addict',
      'dark_romance_girlie',
      'chaos_reader'
    ];
    var sorted = picks.slice().sort(function (a, b) {
      return order.indexOf(a) - order.indexOf(b);
    });
    var lane = String(profile && profile.heat_lane || '');

    if (lane === 'unhinged' && picks.indexOf('dark_romance_girlie') > -1 && picks.indexOf('chaos_reader') > -1) {
      return 'chaos_reader';
    }
    if (lane === 'closed') return sorted[0] || '';
    if (lane === 'open' || lane === 'unhinged') return sorted[sorted.length - 1] || '';
    return sorted[1] || sorted[0] || '';
  }

  function mergeBooks(localBooks, remoteBooks, lookup) {
    var seen = {};
    return localBooks.concat(remoteBooks).map(function (book) {
      return normalizeBook(book, lookup);
    }).filter(function (book) {
      if (!book || !book.title) return false;
      var aliases = bookIdentityAliases(book, lookup);
      if (!aliases.length) return false;
      if (aliases.some(function (alias) { return seen[alias]; })) return false;
      aliases.forEach(function (alias) { seen[alias] = true; });
      return true;
    });
  }

  function attrs(book) {
    var pairs = {
      'data-book-preview': '',
      'data-handle': book.handle,
      'data-title': book.title,
      'data-author': book.author,
      'data-cover': book.cover,
      'data-amazon': book.amazon,
      'data-bookshop': book.bookshop,
      'data-shelf': '',
      'data-private-shelf': book.privateShelf,
      'data-spice': book.spice,
      'data-tropes': book.tropes,
      'data-tropes-display': book.tropesDisplay || book.tropes,
      'data-trope-urls': '',
      'data-why': book.why,
      'data-newsletter': book.newsletter,
      'data-mini': book.mini,
      'data-series': book.series,
      'data-series-name': book.seriesName,
      'data-series-number': book.seriesNumber,
      'data-rating': book.rating,
      'data-tension': book.tension,
      'data-damage': book.damage,
      'data-yearning': book.yearning,
      'data-boyfriend': book.boyfriend,
      'data-boyfriend-name': book.boyfriendName,
      'data-reread': book.reread,
      'data-standalone': book.standalone,
      'data-ku': normalizeBool(book.ku) ? 'true' : (String(book.ku) === 'false' ? 'false' : book.ku),
      'data-darkness': book.darkness
    };
    return Object.keys(pairs).map(function (key) {
      return key === 'data-book-preview' ? key : key + '="' + esc(pairs[key]) + '"';
    }).join(' ');
  }

  function renderBook(book) {
    var spice = parseInt(book.spice, 10) || 0;
    var spiceMarkup = spice > 0 ? '<div class="sss-lib__floatSpice">' + '🌶'.repeat(Math.min(spice, 5)) + '</div>' : '';
    var cover = book.cover
      ? '<img class="sss-lib__cover bbb-account-shelf__cover" src="' + esc(book.cover) + '" alt="' + esc(bookCoverAlt(book)) + '" loading="lazy">'
      : '<div class="sss-lib__cover bbb-account-shelf__cover" aria-hidden="true"></div>';

    return '<button type="button" class="sss-lib__book sss-lib__book--mini bbb-account-shelf__book" ' + attrs(book) + '>' +
      '<div class="sss-lib__coverWrap">' +
        '<span class="sss-lib__heart is-saved" data-heart role="button" aria-label="remove from your bookshelf">' +
          '<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♥</span>' +
          '<span class="sss-lib__heartLabel" data-heart-label>saved</span>' +
        '</span>' +
        '<span class="sss-lib__noteToggle bbb-account-shelf__noteToggle" data-reader-note-toggle role="button" tabindex="0" aria-label="add your private note">' +
          '<span class="sss-lib__noteIcon" aria-hidden="true">✎</span>' +
        '</span>' +
        spiceMarkup +
        cover +
      '</div>' +
      '<div class="sss-lib__under">' +
        '<div class="sss-lib__name bbb-account-shelf__bookTitle">' + esc(book.title) + '</div>' +
        (book.author ? '<div class="sss-lib__author bbb-account-shelf__bookAuthor">' + esc(book.author) + '</div>' : '') +
        '<div class="sss-lib__notePreview" data-reader-note-preview hidden></div>' +
      '</div>' +
    '</button>';
  }

  function renderReadCover(book, index) {
    var cover = book.cover
      ? '<img src="' + esc(book.cover) + '" alt="' + esc(bookCoverAlt(book)) + '" loading="lazy">'
      : '<span class="bbb-account-shelf__readCoverPlaceholder" aria-hidden="true">' + esc((book.title || 'read').charAt(0)) + '</span>';
    var offset = Math.max(-3, Math.min(3, index - 2));

    return '<button type="button" class="bbb-account-shelf__readCover" style="--i:' + offset + ';" ' + attrs(book) + '>' +
      cover +
      '<span class="bbb-account-shelf__readCoverTitle">' + esc(book.title) + '</span>' +
    '</button>';
  }

  function renderLaneBook(book) {
    var cover = book.cover
      ? '<img src="' + esc(book.cover) + '" alt="' + esc(bookCoverAlt(book)) + '" loading="lazy">'
      : '<span aria-hidden="true">' + esc((book.title || 'book').charAt(0)) + '</span>';

    return '<button type="button" class="bbb-account-shelf__laneBook" ' + attrs(book) + '>' +
      '<span class="bbb-account-shelf__laneCover">' + cover + '</span>' +
      '<span class="bbb-account-shelf__laneTitle">' + esc(book.title) + '</span>' +
      (book.author ? '<span class="bbb-account-shelf__laneAuthor">' + esc(book.author) + '</span>' : '') +
    '</button>';
  }

  function renderRatedBook(book) {
    var rating = bookRating(book);
    var cover = book.cover
      ? '<img class="bbb-account-shelf__ratedCover" src="' + esc(book.cover) + '" alt="' + esc(bookCoverAlt(book)) + '" loading="lazy">'
      : '<span class="bbb-account-shelf__ratedCover bbb-account-shelf__ratedCover--empty" aria-hidden="true">' + esc((book.title || 'rated').charAt(0)) + '</span>';

    return '<button type="button" class="sss-lib__book bbb-account-shelf__ratedBook" ' + attrs(Object.assign({}, book, { rating: rating })) + '>' +
      '<span class="bbb-account-shelf__ratedCoverWrap sss-lib__coverWrap">' + cover + '</span>' +
      '<span class="bbb-account-shelf__ratedBody">' +
        '<span class="bbb-account-shelf__ratedStars" aria-label="' + rating + ' out of 5 stars">' + esc(ratingStars(rating)) + '</span>' +
        '<strong>' + esc(book.title) + '</strong>' +
        (book.author ? '<em>' + esc(book.author) + '</em>' : '') +
      '</span>' +
    '</button>';
  }

  function renderCurrentBook(book) {
    if (!book || !book.title) {
      return '<div class="bbb-account-shelf__currentEmpty">mark a saved book as reading and it will appear here first.</div>';
    }

    var cover = book.cover
      ? '<img src="' + esc(book.cover) + '" alt="' + esc(bookCoverAlt(book)) + '" loading="lazy">'
      : '<span aria-hidden="true">' + esc((book.title || 'book').charAt(0)) + '</span>';
    var progress = parseInt(book.progress || 0, 10);
    var hasProgress = progress >= 1 && progress <= 100;
    var progressLabel = hasProgress ? progress + '% complete' : 'track progress in your notes';
    var progressWidth = hasProgress ? progress : 0;

    return '<button type="button" class="sss-lib__book bbb-account-shelf__currentBook" ' + attrs(book) + '>' +
      '<span class="bbb-account-shelf__currentCover">' + cover + '</span>' +
      '<span class="bbb-account-shelf__currentCopy">' +
        '<strong>' + esc(book.title) + '</strong>' +
        (book.author ? '<em>' + esc(book.author) + '</em>' : '') +
        '<span class="bbb-account-shelf__currentProgress"><b>' + esc(progressLabel) + '</b><i aria-hidden="true"><span style="width:' + progressWidth + '%"></span></i></span>' +
        '<span class="bbb-account-shelf__currentActions">' +
          '<span data-reader-note-toggle role="button" tabindex="0" aria-label="add your private note">update progress</span>' +
          '<span>view book</span>' +
        '</span>' +
      '</span>' +
    '</button>';
  }

  function listText(books) {
    if (!books.length) return '';
    return 'my society reading list\n\n' + books.map(function (book, index) {
      var lines = [(index + 1) + '. ' + book.title];
      if (book.author) lines.push('   by ' + book.author);
      if (book.amazon) lines.push('   amazon: ' + book.amazon);
      if (book.bookshop) lines.push('   bookshop: ' + book.bookshop);
      return lines.join('\n');
    }).join('\n\n');
  }

  function makeSupabase() {
    if (!window.supabase || !window.supabase.createClient) return null;
    return window.supabase.createClient(SUPABASE_URL, SUPABASE_KEY);
  }

  function getAccountApi() {
    var directApi = typeof BBBReaderAccountApi !== 'undefined' ? BBBReaderAccountApi : window.BBBReaderAccountApi;
    var siteData = typeof BBBSiteData !== 'undefined' ? BBBSiteData : window.BBBSiteData;
    var api = directApi || (siteData && siteData.readerAccount) || {};
    return (api.shelfEndpoint || api.spiceEndpoint || api.endpoint) && api.nonce ? api : null;
  }

  function accountApiRequest(url, method, body) {
    return window.fetch(url, {
      method: method || 'GET',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': (getAccountApi() && getAccountApi().nonce) || ''
      },
      body: body ? JSON.stringify(body) : undefined
    }).then(function (response) {
      return response.json().then(function (payload) {
        if (!response.ok) {
          throw payload || new Error('Reader account request failed');
        }
        return payload;
      });
    });
  }

  function remoteBook(row) {
    return {
      book_key: row.book_key || '',
      handle: row.book_handle || '',
      title: row.book_title || '',
      author: row.author || '',
      cover: row.cover || '',
      amazon: row.amazon || '',
      bookshop: row.bookshop || '',
      spice: row.spice_level || '',
      darkness: row.darkness_level || '',
      tropes: Array.isArray(row.tropes) ? row.tropes : [],
      rating: row.rating || (row.metadata && row.metadata.rating) || ''
    };
  }

  function getStoredSpiceProfile(accountKey) {
    var value = parseInt(window.localStorage.getItem(accountScopedKey('bbbReaderSpiceProfile', accountKey)) || (!accountKey ? window.localStorage.getItem('bbbReaderSpiceProfile') : '') || '', 10);
    return value >= 1 && value <= 5 ? value : 0;
  }

  function setStoredSpiceProfile(level, accountKey) {
    window.localStorage.setItem(accountScopedKey('bbbReaderSpiceProfile', accountKey), String(level));
    window.localStorage.setItem('bbbReaderSpiceProfile', String(level));
    var profile = getJSON(accountScopedKey('sssMadeForYouProfile', accountKey), {});
    var tasteProfile = getJSON(accountScopedKey('bbbReaderTasteProfile', accountKey), {});
    var values = {
      1: 'soft_open_door',
      2: 'some_heat',
      3: 'balanced',
      4: 'high_spice',
      5: 'wreck_me'
    };
    if (profile && typeof profile === 'object') {
      profile.spice_profile = level;
      profile.spice_dial = values[level] || profile.spice_dial || 'balanced';
      window.localStorage.setItem(accountScopedKey('sssMadeForYouProfile', accountKey), JSON.stringify(profile));
      cleanupLegacyKey('sssMadeForYouProfile', accountKey);
    }
    if (tasteProfile && typeof tasteProfile === 'object') {
      tasteProfile.spice_profile = level;
      tasteProfile.spice_dial = values[level] || tasteProfile.spice_dial || 'balanced';
      window.localStorage.setItem(accountScopedKey('bbbReaderTasteProfile', accountKey), JSON.stringify(tasteProfile));
      window.localStorage.setItem('bbbReaderTasteProfile', JSON.stringify(tasteProfile));
      cleanupLegacyKey('bbbReaderTasteProfile', accountKey);
    }
  }

  function initSpiceProfile(root) {
    var choices = Array.prototype.slice.call(root.querySelectorAll('[data-spice-choice]'));
    if (!choices.length) return;

    var title = root.querySelector('[data-spice-profile-title]');
    var copy = root.querySelector('[data-spice-profile-copy]');
    var peppers = root.querySelector('[data-spice-profile-peppers]');
    var status = root.querySelector('[data-spice-profile-status]');
    var api = getAccountApi();
    var siteData = typeof BBBSiteData !== 'undefined' ? BBBSiteData : window.BBBSiteData;
    var accountState = (siteData && siteData.BBBReaderAccount) || {};
    var accountRoot = root.closest('[data-account-shelf]');
    var accountKey = String(root.dataset.accountKey || (accountRoot && accountRoot.dataset.accountKey) || (api && api.accountKey) || '').trim();
    var hasReaderAccess = document.body.classList.contains('logged-in') || accountState.hasEmailAccess || (accountRoot && accountRoot.dataset.loggedIn === 'true');
    var initialLevel = parseInt(root.dataset.initialLevel || '', 10) || getStoredSpiceProfile(accountKey);

    function setStatus(message, tone) {
      if (!status) return;
      status.textContent = message || '';
      status.dataset.tone = tone || '';
    }

    function applyChoice(choice, shouldSave) {
      if (!choice) return;
      var level = parseInt(choice.dataset.spiceChoice || '', 10);
      if (!(level >= 1 && level <= 5)) return;

      choices.forEach(function (item) {
        var active = item === choice;
        item.classList.toggle('is-active', active);
        item.setAttribute('aria-checked', active ? 'true' : 'false');
      });

      if (title) title.textContent = choice.dataset.spiceLabel || 'spice profile';
      if (copy) copy.textContent = choice.dataset.spiceDescription || '';
      if (peppers) peppers.textContent = choice.dataset.spicePeppers || (level + '/5');
      setStoredSpiceProfile(level, accountKey);
      root.dataset.initialLevel = String(level);

      if (!shouldSave) return;
      window.dispatchEvent(new CustomEvent('bbb:spice-profile-changed', {
        detail: { level: level, source: 'spice-profile-control' }
      }));
      if (!hasReaderAccess || !api || !api.spiceEndpoint) {
        setStatus('saved on this device.', 'local');
        return;
      }

      setStatus('saving...', 'saving');
      accountApiRequest(api.spiceEndpoint, 'POST', { level: level }).then(function () {
        setStatus('saved to your account.', 'success');
      }).catch(function (error) {
        setStatus('saved on this device. account sync will retry when available.', 'local');
        console.log('Reader spice profile sync failed', error);
      });
    }

    if (initialLevel) {
      var initialChoice = root.querySelector('[data-spice-choice="' + initialLevel + '"]');
      if (initialChoice) applyChoice(initialChoice, false);
    }

    window.addEventListener('bbb:spice-profile-changed', function (event) {
      var level = parseInt(event.detail && event.detail.level || 0, 10);
      if (!(level >= 1 && level <= 5)) return;
      var choice = root.querySelector('[data-spice-choice="' + level + '"]');
      if (choice) applyChoice(choice, false);
      var stat = document.querySelector('[data-account-spice-stat]');
      if (stat) stat.textContent = level + '/5';
    });

    choices.forEach(function (choice) {
      choice.addEventListener('click', function () {
        applyChoice(choice, true);
      });
    });
  }

  function init(root) {
    var grid = root.querySelector('[data-account-shelf-grid]');
    var empty = root.querySelector('[data-account-shelf-empty]');
    var status = root.querySelector('[data-account-shelf-status]');
    var statusCopy = root.querySelector('[data-account-shelf-status-copy]');
    var toolbar = root.querySelector('[data-account-shelf-toolbar]');
    var tools = root.querySelector('[data-account-shelf-tools]');
    var count = root.querySelector('[data-account-shelf-count]');
    var copyBtn = root.querySelector('[data-account-copy]');
    var emailBtn = root.querySelector('[data-account-email]');
    var readFeature = root.querySelector('[data-account-read-feature]');
    var readCovers = root.querySelector('[data-account-read-covers]');
    var readCopy = root.querySelector('[data-account-read-copy]');
    var quoteCard = root.querySelector('[data-account-quote-card]');
    var quoteText = root.querySelector('[data-account-quote-text]');
    var quoteSource = root.querySelector('[data-account-quote-source]');
    var ratingsSection = root.querySelector('[data-account-ratings]');
    var ratingsGrid = root.querySelector('[data-account-ratings-grid]');
    var ratingsCount = root.querySelector('[data-account-ratings-count]');
    var ratingsEmpty = root.querySelector('[data-account-ratings-empty]');
    var currentReading = root.querySelector('[data-account-current-reading]');
    var currentReadingBody = root.querySelector('[data-account-current-reading-body]');
    var shelfTabs = root.querySelector('[data-account-shelf-tabs]');
    var shelfSearch = root.querySelector('[data-shelf-search]');
    var shelfSort = root.querySelector('[data-shelf-sort]');
    var snapshotReaderType = root.querySelector('[data-snapshot-reader-type]');
    var snapshotReaderEmoji = root.querySelector('[data-snapshot-reader-emoji]');
    var snapshotSpice = root.querySelector('[data-snapshot-spice]');
    var snapshotSpicePeppers = root.querySelector('[data-snapshot-spice-peppers]');
    var snapshotSaved = root.querySelector('[data-snapshot-saved]');
    var snapshotReading = root.querySelector('[data-snapshot-reading]');
    var snapshotFinished = root.querySelector('[data-snapshot-finished]');
    var memberBadge = root.querySelector('[data-account-shelf-badge]');
    var memberBadgeLabel = root.querySelector('[data-account-shelf-badge-label]');
    var readerProfileCard = root.querySelector('[data-bookshelf-reader-profile]');
    var readerProfileTitle = root.querySelector('[data-bookshelf-reader-type-title]');
    var readerProfileEmoji = root.querySelector('[data-bookshelf-reader-type-emoji]');
    var isLoggedIn = root.dataset.loggedIn === 'true';
    var customerId = root.dataset.customerId || '';
    var email = normalizeKey(root.dataset.customerEmail);
    var accountKey = String(root.dataset.accountKey || '').trim();
    var libraryBooks = parseBookData();
    var lookup = buildLookup(libraryBooks);
    var quotes = parseQuoteData();
    var readerTypes = parseEmbeddedJSON('[data-account-reader-types]', []);
    var accountMadeForYouProfile = parseEmbeddedJSON('[data-account-made-for-you-profile]', {});
    var madeForYouProfileVersion = root.dataset.mfyProfileVersion || '';
    var current = [];
    var activeShelfTab = 'all';

    function setStatus(title, copy) {
      if (!status) return;
      var strong = status.querySelector('strong');
      if (strong) strong.textContent = title;
      if (statusCopy) statusCopy.textContent = copy;
    }

    function renderTier(accessTier) {
      var isSociety = accessTier === 'society' || root.dataset.isSociety === 'true';
      if (memberBadge) memberBadge.classList.toggle('bbb-account-shelf__memberBadge--secret', isSociety);
      if (memberBadgeLabel) memberBadgeLabel.textContent = isSociety ? 'secret society member' : 'free reader';
    }

    function readerTypeByKey(key) {
      key = String(key || '').trim();
      return readerTypes.find(function (type) {
        return String(type && type.key || '') === key;
      }) || null;
    }

    function madeForYouProfileForDisplay() {
      if (isCompleteMadeForYouProfile(accountMadeForYouProfile, madeForYouProfileVersion)) return accountMadeForYouProfile;
      var stored = getStoredMadeForYouProfile(accountKey);
      if (isCompleteMadeForYouProfile(stored, madeForYouProfileVersion)) return stored;
      return {};
    }

    function applyReaderTypePreview(readerType) {
      if (!readerProfileCard || !readerType) return;

      var theme = readerType.theme || {};
      var accent = theme.accent || '#D4C2CE';
      var border = theme.border || accent;

      readerProfileCard.setAttribute('data-reader-profile-theme', String(readerType.key || 'romance_reader'));
      readerProfileCard.style.setProperty('--reader-profile-accent', accent);
      readerProfileCard.style.setProperty('--reader-profile-accent-soft', 'color-mix(in srgb, ' + accent + ' 16%, transparent)');
      readerProfileCard.style.setProperty('--reader-profile-accent-border', 'color-mix(in srgb, ' + border + ' 42%, transparent)');
      readerProfileCard.style.setProperty('--reader-profile-panel', 'linear-gradient(135deg, color-mix(in srgb, ' + accent + ' 12%, transparent), rgba(255, 255, 255, 0.025))');

      if (readerType.emoji && readerProfileEmoji) {
        var emojiUrl = '/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + readerType.emoji + '.png';
        if (readerProfileEmoji.tagName && readerProfileEmoji.tagName.toLowerCase() === 'img') {
          readerProfileEmoji.src = emojiUrl;
          readerProfileEmoji.alt = '';
        } else {
          var img = document.createElement('img');
          img.src = emojiUrl;
          img.alt = '';
          img.loading = 'lazy';
          img.decoding = 'async';
          img.setAttribute('data-bookshelf-reader-type-emoji', '');
          readerProfileEmoji.replaceWith(img);
          readerProfileEmoji = img;
        }
      }

      if (readerType.emoji && snapshotReaderEmoji) {
        var snapshotEmojiUrl = '/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + readerType.emoji + '.png';
        if (snapshotReaderEmoji.tagName && snapshotReaderEmoji.tagName.toLowerCase() === 'img') {
          snapshotReaderEmoji.src = snapshotEmojiUrl;
          snapshotReaderEmoji.alt = '';
        } else {
          var snapshotImg = document.createElement('img');
          snapshotImg.src = snapshotEmojiUrl;
          snapshotImg.alt = '';
          snapshotImg.loading = 'lazy';
          snapshotImg.decoding = 'async';
          snapshotImg.setAttribute('data-snapshot-reader-emoji', '');
          snapshotReaderEmoji.replaceWith(snapshotImg);
          snapshotReaderEmoji = snapshotImg;
        }
      }
    }

    function renderMadeForYouReaderType() {
      if (!readerProfileCard || !readerProfileTitle) return;

      var profile = madeForYouProfileForDisplay();
      if (!profile || !Object.keys(profile).length) return;

      var readerType = readerTypeByKey(madeForYouReaderTypeKey(profile));
      if (!readerType) return;

      var label = readerType.label || '';

      if (label) readerProfileTitle.textContent = label;
      if (label && snapshotReaderType) snapshotReaderType.textContent = label;
      applyReaderTypePreview(readerType);
    }

    function relatedQuote(readBooks) {
      var readKeys = {};
      readBooks.forEach(function (book) {
        var handle = normalizeKey(book.handle);
        var title = normalizeKey(book.title);
        if (handle) readKeys[handle] = true;
        if (title) readKeys[title] = true;
      });

      return quotes.find(function (quote) {
        return readKeys[normalizeKey(quote.book_handle)] || readKeys[normalizeKey(quote.book_title)];
      }) || quotes[0] || null;
    }

    function booksFromStatus(books, wantedStatus, limit) {
      var statuses = getBookStatuses();
      var keyedBooks = {};
      var seen = {};

      libraryBooks.concat(books).map(function (book) {
        return normalizeBook(book, lookup);
      }).filter(Boolean).forEach(function (book) {
        var key = getBookStatusKey(book);
        if (key && !keyedBooks[key]) keyedBooks[key] = book;
        bookIdentityAliases(book, lookup).forEach(function (alias) {
          if (!keyedBooks[alias]) keyedBooks[alias] = book;
        });
      });

      return Object.keys(statuses).filter(function (key) {
        return statuses[key] === wantedStatus;
      }).map(function (key) {
        return keyedBooks[key] || lookup[key] || null;
      }).filter(function (book) {
        return book && book.title;
      }).filter(function (book) {
        var aliases = bookIdentityAliases(book, lookup);
        var duplicate = aliases.some(function (alias) { return seen[alias]; });
        if (duplicate) return false;
        aliases.forEach(function (alias) { seen[alias] = true; });
        return true;
      }).slice(0, limit || 7);
    }

    function readBooksFromStatuses(books) {
      return booksFromStatus(books, 'read', 7);
    }

    function statusBooks(books, statusName, limit) {
      return booksFromStatus(books, statusName, limit || 80);
    }

    function filteredBooksForTab(books) {
      var displayBooks = activeShelfTab === 'all' ? books.slice() : statusBooks(books, activeShelfTab, 120);
      var query = normalizeKey(shelfSearch && shelfSearch.value);
      var sortMode = String(shelfSort && shelfSort.value || 'recent');

      if (query) {
        displayBooks = displayBooks.filter(function (book) {
          return [
            book.title,
            book.author,
            book.tropes,
            book.tropesDisplay
          ].join(' ').toLowerCase().indexOf(query) > -1;
        });
      }

      if (sortMode === 'title') {
        displayBooks.sort(function (a, b) {
          return String(a.title || '').localeCompare(String(b.title || ''));
        });
      } else if (sortMode === 'rating') {
        displayBooks.sort(function (a, b) {
          return bookRating(b) - bookRating(a) || String(a.title || '').localeCompare(String(b.title || ''));
        });
      } else if (sortMode === 'spice') {
        displayBooks.sort(function (a, b) {
          return (parseInt(b.spice, 10) || 0) - (parseInt(a.spice, 10) || 0) || String(a.title || '').localeCompare(String(b.title || ''));
        });
      }

      return displayBooks;
    }

    function renderSnapshot(books) {
      var readingBooks = statusBooks(books, 'reading', 80);
      var finishedBooks = statusBooks(books, 'read', 80);
      if (snapshotSaved) snapshotSaved.textContent = String(books.length);
      if (snapshotReading) snapshotReading.textContent = String(readingBooks.length);
      if (snapshotFinished) snapshotFinished.textContent = String(finishedBooks.length);

      if (snapshotSpice) {
        var activeSpice = root.querySelector('[data-spice-choice].is-active');
        if (snapshotSpicePeppers && activeSpice) snapshotSpicePeppers.textContent = activeSpice.dataset.spicePeppers || '🌶';
        snapshotSpice.textContent = activeSpice
          ? (activeSpice.dataset.spiceLabel || 'spice reader')
          : (snapshotSpice.textContent || 'spice profile not set');
      }
    }

    function renderCurrentReading(books) {
      if (!currentReading || !currentReadingBody) return;
      var readingBooks = statusBooks(books, 'reading', 1);
      currentReading.hidden = false;
      currentReading.classList.toggle('is-empty', !readingBooks.length);
      currentReadingBody.innerHTML = renderCurrentBook(readingBooks[0] || null);
    }

    function renderStatusLanes(books) {
      ['read', 'reading', 'tbr'].forEach(function (statusName) {
        var lane = root.querySelector('[data-account-status-lane="' + statusName + '"]');
        var row = root.querySelector('[data-account-status-books="' + statusName + '"]');
        var countEl = root.querySelector('[data-account-status-count="' + statusName + '"]');
        if (!lane || !row) return;

        var laneBooks = booksFromStatus(books, statusName, 8);
        lane.classList.toggle('is-empty', !laneBooks.length);
        row.innerHTML = laneBooks.length
          ? laneBooks.map(renderLaneBook).join('')
          : '<div class="bbb-account-shelf__laneEmpty">' + (statusName === 'read' ? 'finished books will stack here.' : statusName === 'reading' ? 'your current read will live here.' : 'your tbr pile will collect here.') + '</div>';

        if (countEl) {
          countEl.textContent = laneBooks.length + (laneBooks.length === 1 ? ' book' : ' books');
        }
      });
    }

    function ratedBooks(books) {
      return books.map(function (book) {
        var normalized = normalizeBook(book, lookup);
        if (!normalized || !normalized.title) return null;
        normalized.rating = bookRating(normalized);
        return normalized.rating ? normalized : null;
      }).filter(Boolean).sort(function (a, b) {
        return bookRating(b) - bookRating(a);
      });
    }

    function renderRatings(books) {
      if (!ratingsSection || !ratingsGrid) return;

      var rated = ratedBooks(books);
      ratingsSection.hidden = false;

      if (ratingsCount) {
        ratingsCount.textContent = rated.length + (rated.length === 1 ? ' rated' : ' rated');
      }

      if (!rated.length) {
        ratingsGrid.innerHTML = '';
        if (ratingsEmpty) ratingsEmpty.hidden = false;
        ratingsSection.hidden = !books.length;
        return;
      }

      if (ratingsEmpty) ratingsEmpty.hidden = true;
      ratingsGrid.innerHTML = rated.map(renderRatedBook).join('');
    }

    function renderReadFeature(books) {
      if (!readFeature || !readCovers) return;

      var readBooks = readBooksFromStatuses(books);
      var quote = relatedQuote(readBooks);
      var displayBooks = readBooks.length ? readBooks : books.slice(0, 5);

      readFeature.hidden = false;
      readFeature.classList.toggle('is-empty', !readBooks.length);
      readCovers.innerHTML = displayBooks.length
        ? displayBooks.map(renderReadCover).join('')
        : '<div class="bbb-account-shelf__readPlaceholder"><span></span><span></span><span></span></div>';

      if (readCopy) {
        readCopy.textContent = readBooks.length
          ? readBooks.length + (readBooks.length === 1 ? ' finished book is' : ' finished books are') + ' sitting face-out on your shelf.'
          : 'tag a saved book as read from its book details and this becomes your finished shelf.';
      }

      if (quoteText && quote) {
        quoteText.innerHTML = '<span>' + esc('"' + decodeHtml(quote.text || '').replace(/^"+|"+$/g, '') + '"') + '</span>';
      }

      if (quoteSource) {
        quoteSource.textContent = quote && (quote.book_title || quote.book_handle)
          ? 'from ' + decodeHtml(quote.book_title || quote.book_handle) + ' → quote wall'
          : 'visit the quote wall →';
      }
    }

    function render(books) {
      current = books;
      renderCurrentReading(books);
      renderSnapshot(books);
      renderReadFeature(books);
      renderStatusLanes(books);
      renderRatings(books);
      if (!grid || !empty) return;
      if (!books.length) {
        grid.innerHTML = '';
        empty.hidden = false;
        if (toolbar) toolbar.hidden = true;
        if (tools) tools.hidden = true;
        return;
      }
      empty.hidden = true;
      if (toolbar) toolbar.hidden = false;
      if (tools) tools.hidden = false;
      var displayBooks = filteredBooksForTab(books);
      if (count) count.textContent = displayBooks.length + (displayBooks.length === 1 ? ' book' : ' books');
      grid.innerHTML = displayBooks.length
        ? displayBooks.map(renderBook).join('')
        : '<div class="bbb-account-shelf__tabEmpty">nothing in this shelf yet.</div>';
      if (window.sssSyncBookStatusUI) window.sssSyncBookStatusUI();
      if (window.bbbReaderNotesRefresh) window.bbbReaderNotesRefresh();
    }

    function renderLocal() {
      render(mergeBooks(getShelf(), [], lookup));
    }

    grid && grid.addEventListener('click', function (event) {
      var heart = event.target.closest('[data-heart]');
      if (!heart || !grid.contains(heart)) return;

      var card = heart.closest('.sss-lib__book');
      if (!card) return;

      event.preventDefault();
      event.stopPropagation();

      var handle = normalizeKey(card.dataset.handle);
      var title = normalizeKey(card.dataset.title);
      var next = getShelf().filter(function (book) {
        var bookHandle = normalizeKey(book.handle);
        var bookTitle = normalizeKey(book.title);
        if (handle && bookHandle && handle === bookHandle) return false;
        if (title && bookTitle && title === bookTitle) return false;
        return true;
      });
      setShelf(next);
      renderLocal();
    });

    copyBtn && copyBtn.addEventListener('click', function () {
      var output = listText(current);
      if (!output || !navigator.clipboard) return;
      navigator.clipboard.writeText(output).then(function () {
        copyBtn.textContent = 'copied';
        window.setTimeout(function () { copyBtn.textContent = 'copy list'; }, 1600);
      });
    });

    emailBtn && emailBtn.addEventListener('click', function () {
      var output = listText(current);
      if (!output) return;
      window.location.href = 'mailto:?subject=' + encodeURIComponent('My Society Reading List') + '&body=' + encodeURIComponent(output);
    });

    shelfTabs && shelfTabs.addEventListener('click', function (event) {
      var tab = event.target.closest('[data-shelf-tab]');
      if (!tab || !shelfTabs.contains(tab)) return;
      activeShelfTab = tab.dataset.shelfTab || 'all';
      shelfTabs.querySelectorAll('[data-shelf-tab]').forEach(function (button) {
        var active = button === tab;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
      render(current);
    });

    shelfSearch && shelfSearch.addEventListener('input', function () {
      render(current);
    });

    shelfSort && shelfSort.addEventListener('change', function () {
      render(current);
    });

    document.addEventListener('bbb:book-status-changed', function () {
      render(current);
    });

    document.addEventListener('bbb:book-statuses-updated', function () {
      render(current);
    });

    document.addEventListener('bbb:book-rating-changed', function () {
      render(current);
    });

    document.addEventListener('bbb:book-ratings-updated', function () {
      render(current);
    });

    renderMadeForYouReaderType();
    renderLocal();

    if (!isLoggedIn) return;

    var api = getAccountApi();
    var localShelf = mergeBooks(getShelf(), [], lookup);
    if (api) {
      accountApiRequest(api.shelfEndpoint, 'POST', {
        items: localShelf,
        statuses: getBookStatuses(),
        ratings: getBookRatings()
      }).then(function (payload) {
        renderTier(payload.accessTier || 'free');
        var remote = (payload.books || []).map(remoteBook);
        var merged = mergeBooks(localShelf, remote, lookup);
        render(merged);
        setStatus(
          merged.length ? 'your bookshelf is synced.' : 'your account shelf is ready.',
          merged.length ? merged.length + (merged.length === 1 ? ' saved book is connected to this account.' : ' saved books are connected to this account.') : 'save a book and it will follow this login.'
        );
      }).catch(function (error) {
        setStatus('local shelf loaded.', 'account sync will retry next time you open this page.');
        console.log('WordPress account bookshelf sync failed', error);
      });
      return;
    }

    var client = makeSupabase();
    if (!client || (!customerId && !email)) {
      setStatus('local shelf loaded.', 'account sync is waiting on the bookshelf connection.');
      return;
    }

    var payload = localShelf.map(function (book) {
      return {
        email_normalized: email || null,
        shopify_customer_id: customerId || null,
        customer_email: email || null,
        book_key: normalizeKey(book.handle || book.title),
        book_handle: book.handle || null,
        book_title: book.title,
        author: book.author || null,
        cover: book.cover || null,
        amazon: book.amazon || null,
        bookshop: book.bookshop || null,
        spice_level: parseInt(book.spice, 10) || null,
        darkness_level: parseInt(book.darkness, 10) || null,
        tropes: book.tropes ? String(book.tropes).split(',').map(function (trope) { return trope.trim(); }).filter(Boolean) : [],
        source: 'wordpress_bookshelf',
        is_active: true,
        removed_at: null
      };
    }).filter(function (book) {
      return book.book_key && book.book_title;
    });

    var sync = payload.length
      ? client.from('bookshelf_saved_books').upsert(payload, { onConflict: 'email_normalized,book_key' })
      : Promise.resolve({ error: null });

    sync.then(function (response) {
      if (response.error) throw response.error;
      var query = client
        .from('bookshelf_saved_books')
        .select('book_key,book_handle,book_title,author,cover,amazon,bookshop,spice_level,darkness_level,tropes,saved_at')
        .eq('is_active', true)
        .order('saved_at', { ascending: false })
        .limit(80);

      query = customerId ? query.eq('shopify_customer_id', customerId) : query.eq('email_normalized', email);
      return query;
    }).then(function (response) {
      if (response.error) throw response.error;
      var remote = (response.data || []).map(remoteBook);
      var merged = mergeBooks(localShelf, remote, lookup);
      render(merged);
      setStatus(
        merged.length ? 'your bookshelf is synced.' : 'your account shelf is ready.',
        merged.length ? merged.length + (merged.length === 1 ? ' saved book is connected to this account.' : ' saved books are connected to this account.') : 'save a book and it will follow this login.'
      );
    }).catch(function (error) {
      setStatus('local shelf loaded.', 'account sync will retry next time you open this page.');
      console.log('Account bookshelf sync failed', error);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-spice-profile]').forEach(initSpiceProfile);
    document.querySelectorAll('[data-account-shelf]').forEach(init);
  });
})();
