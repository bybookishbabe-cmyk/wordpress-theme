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

  function normalizeKey(value) {
    return String(value || '').trim().toLowerCase();
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
      privateShelf: book.privateShelf || book.private_shelf || 'false'
    };
  }

  function mergeBooks(localBooks, remoteBooks, lookup) {
    var seen = {};
    return localBooks.concat(remoteBooks).map(function (book) {
      return normalizeBook(book, lookup);
    }).filter(function (book) {
      if (!book || !book.title) return false;
      var key = normalizeKey(book.handle || book.title);
      if (!key || seen[key]) return false;
      seen[key] = true;
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
      ? '<img class="sss-lib__cover bbb-account-shelf__cover" src="' + esc(book.cover) + '" alt="' + esc(book.title) + '" loading="lazy">'
      : '<div class="sss-lib__cover bbb-account-shelf__cover" aria-hidden="true"></div>';

    return '<button type="button" class="sss-lib__book sss-lib__book--mini bbb-account-shelf__book" ' + attrs(book) + '>' +
      '<div class="sss-lib__coverWrap">' +
        '<span class="sss-lib__heart is-saved" data-heart role="button" aria-label="remove from your bookshelf">' +
          '<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♥</span>' +
          '<span class="sss-lib__heartLabel" data-heart-label>saved</span>' +
        '</span>' +
        spiceMarkup +
        cover +
      '</div>' +
      '<div class="sss-lib__under">' +
        '<div class="sss-lib__name bbb-account-shelf__bookTitle">' + esc(book.title) + '</div>' +
        (book.author ? '<div class="sss-lib__author bbb-account-shelf__bookAuthor">' + esc(book.author) + '</div>' : '') +
      '</div>' +
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

  function remoteBook(row) {
    return {
      handle: row.book_handle || '',
      title: row.book_title || '',
      author: row.author || '',
      cover: row.cover || '',
      amazon: row.amazon || '',
      bookshop: row.bookshop || '',
      spice: row.spice_level || '',
      darkness: row.darkness_level || '',
      tropes: Array.isArray(row.tropes) ? row.tropes : []
    };
  }

  function init(root) {
    var grid = root.querySelector('[data-account-shelf-grid]');
    var empty = root.querySelector('[data-account-shelf-empty]');
    var status = root.querySelector('[data-account-shelf-status]');
    var statusCopy = root.querySelector('[data-account-shelf-status-copy]');
    var toolbar = root.querySelector('[data-account-shelf-toolbar]');
    var count = root.querySelector('[data-account-shelf-count]');
    var copyBtn = root.querySelector('[data-account-copy]');
    var emailBtn = root.querySelector('[data-account-email]');
    var isLoggedIn = root.dataset.loggedIn === 'true';
    var customerId = root.dataset.customerId || '';
    var email = normalizeKey(root.dataset.customerEmail);
    var lookup = buildLookup(parseBookData());
    var current = [];

    function setStatus(title, copy) {
      if (!status) return;
      var strong = status.querySelector('strong');
      if (strong) strong.textContent = title;
      if (statusCopy) statusCopy.textContent = copy;
    }

    function render(books) {
      current = books;
      if (!grid || !empty) return;
      if (!books.length) {
        grid.innerHTML = '';
        empty.hidden = false;
        if (toolbar) toolbar.hidden = true;
        return;
      }
      empty.hidden = true;
      if (toolbar) toolbar.hidden = false;
      if (count) count.textContent = books.length + (books.length === 1 ? ' book saved' : ' books saved');
      grid.innerHTML = books.map(renderBook).join('');
      if (window.sssSyncBookStatusUI) window.sssSyncBookStatusUI();
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

    renderLocal();

    if (!isLoggedIn) return;

    var client = makeSupabase();
    if (!client || (!customerId && !email)) {
      setStatus('local shelf loaded.', 'account sync is waiting on the bookshelf connection.');
      return;
    }

    var localShelf = mergeBooks(getShelf(), [], lookup);
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
        .select('book_handle,book_title,author,cover,amazon,bookshop,spice_level,darkness_level,tropes,saved_at')
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
    document.querySelectorAll('[data-account-shelf]').forEach(init);
  });
})();
