 /* 
Smut & Sentiment Society Library
Main interaction + trending system
*/
 
 const SUPABASE_URL = "https://efmrfxsmgbeikfgtrxjv.supabase.co";
const SUPABASE_KEY = "sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk";
const SITE_EVENTS_TABLE = "site_events";

const supabaseClient = window.supabase.createClient(
  SUPABASE_URL,
  SUPABASE_KEY
);

function isAnalyticsExcluded(){

  try {
    var params = new URLSearchParams(window.location.search);

    if (params.get("analytics") === "off"){
      localStorage.setItem("sssAnalyticsExcluded", "true");
    }

    if (params.get("analytics") === "on"){
      localStorage.removeItem("sssAnalyticsExcluded");
    }

    return localStorage.getItem("sssAnalyticsExcluded") === "true";
  } catch(err) {
    return false;
  }

}

window.sssAnalytics = window.sssAnalytics || {};
window.sssAnalytics.exclude = function(){
  try { localStorage.setItem("sssAnalyticsExcluded", "true"); } catch(err) {}
};
window.sssAnalytics.include = function(){
  try { localStorage.removeItem("sssAnalyticsExcluded"); } catch(err) {}
};
window.sssAnalytics.isExcluded = isAnalyticsExcluded;

function bbbSeriesDisplayLabel(name){
  var value = String(name || '').trim();
  if (!value) return '';
  return /\b(series|duet|trilogy|saga)\s*$/i.test(value) ? value : value + ' series';
}

function bookCoverAlt(title, author, shelf){
  var cleanTitle = String(title || '').trim();
  var cleanAuthor = String(author || '').trim();
  var cleanShelf = String(shelf || '').trim();

  if (!cleanTitle) return 'book cover';

  var alt = cleanTitle;
  if (cleanAuthor) alt += ' by ' + cleanAuthor;
  if (cleanShelf) alt += ' – ' + cleanShelf;
  return alt + ' book cover';
}

function closestFromTarget(target, selector){
  return target && typeof target.closest === 'function' ? target.closest(selector) : null;
}

function getAnalyticsSessionId(){

  try {
    var existing = localStorage.getItem("sssAnalyticsSessionId");
    if (existing) return existing;

    var created = "sss-" + Date.now() + "-" + Math.random().toString(36).slice(2, 10);
    localStorage.setItem("sssAnalyticsSessionId", created);
    return created;
  } catch(err) {
    return "sss-" + Date.now() + "-" + Math.random().toString(36).slice(2, 10);
  }

}

function getAnalyticsPacificDateKey(){

  try {
    return new Intl.DateTimeFormat("en-CA", {
      timeZone: "America/Los_Angeles",
      year: "numeric",
      month: "2-digit",
      day: "2-digit"
    }).format(new Date());
  } catch(err) {
    return new Date().toISOString().slice(0, 10);
  }

}

async function trackSiteEvent(eventType, payload){

  if (!eventType) return;
  if (isAnalyticsExcluded()) return;

  try {
    await supabaseClient
      .from(SITE_EVENTS_TABLE)
      .insert([
        {
          session_id: getAnalyticsSessionId(),
          event_type: eventType,
          page_path: window.location.pathname,
          page_title: document.title,
          book_handle: payload && payload.bookHandle ? payload.bookHandle : null,
          book_title: payload && payload.bookTitle ? payload.bookTitle : null,
          series_handle: payload && payload.seriesHandle ? payload.seriesHandle : null,
          ui_location: payload && payload.uiLocation ? payload.uiLocation : null,
          metadata: payload && payload.metadata ? payload.metadata : {}
        }
      ]);
  } catch(err) {
    console.log("Supabase event tracking failed", err);
  }

}

function trackDailyVisit(){

  if (isAnalyticsExcluded()) return;

  try {
    var dateKey = getAnalyticsPacificDateKey();
    var storageKey = "sssAnalyticsDailyVisit:" + dateKey;

    if (localStorage.getItem(storageKey) === "true") return;

    localStorage.setItem(storageKey, "true");

    trackSiteEvent("daily_visit", {
      uiLocation: document.body.dataset.template || "site",
      metadata: {
        tracked_date_pacific: dateKey
      }
    });
  } catch(err) {
    trackSiteEvent("daily_visit", {
      uiLocation: document.body.dataset.template || "site"
    });
  }

}

async function trackBookSave(title, bookHandle){

  if (isAnalyticsExcluded()) return;

  try {

    await supabaseClient
      .from('book_saves')
      .insert([
        {
          book_title: title,
          book_handle: bookHandle || null
        }
      ]);

  } catch(err) {

    console.log("Supabase save failed", err);

  }

}
(function(){

trackDailyVisit();

function openSharedBookFromUrl(attempt){
  if (window.__sssOpenedSharedBook) return;
  var params = new URLSearchParams(window.location.search);
  var book = decodeURIComponent(params.get("book") || "").toLowerCase().trim();

  if(!book) return;

  var cards = Array.from(document.querySelectorAll(".sss-lib__book[data-title]"));
  if(!cards.length){
    if ((attempt || 0) < 12){
      window.setTimeout(function(){
        openSharedBookFromUrl((attempt || 0) + 1);
      }, 250);
    }
    return;
  }

  var match = cards.find(function(card){
    return (
      (card.dataset.handle && card.dataset.handle.toLowerCase() === book) ||
      (card.dataset.title && card.dataset.title.toLowerCase() === book) ||
      (card.dataset.title && card.dataset.title.toLowerCase().includes(book))
    );
  });

  if(!match){
    if ((attempt || 0) < 12){
      window.setTimeout(function(){
        openSharedBookFromUrl((attempt || 0) + 1);
      }, 250);
    }
    return;
  }

  if (!match.__sssModalBound && (attempt || 0) < 12){
    window.setTimeout(function(){
      openSharedBookFromUrl((attempt || 0) + 1);
    }, 250);
    return;
  }

  window.setTimeout(function(){
    window.__sssOpenedSharedBook = true;
    match.click();
    if (window.history && window.history.replaceState){
      window.history.replaceState({}, "", window.location.pathname + window.location.hash);
    }
  }, 120);
}
const backTopBtn = document.querySelector(".sss-lib__backTopBtn");
const backTopWrap = document.getElementById("sssBackToTop");

function updateBackTopVisibility(){
  if (!backTopWrap) return;

  var scrollTop = window.scrollY || window.pageYOffset || 0;
  var doc = document.documentElement;
  var scrollableHeight = doc.scrollHeight - window.innerHeight;

  if (scrollableHeight <= 0){
    backTopWrap.classList.remove('is-visible');
    return;
  }

  var progress = scrollTop / scrollableHeight;
  backTopWrap.classList.toggle('is-visible', progress >= 0.3);
}

if(backTopBtn){
  backTopBtn.addEventListener("click", function(){
    window.scrollTo({
      top:0,
      behavior:"smooth"
    });
  });
}

window.addEventListener('scroll', updateBackTopVisibility, { passive:true });
window.addEventListener('resize', updateBackTopVisibility);
updateBackTopVisibility();

function initMobileGridPagination(){

  var isMobile = window.innerWidth <= 768;
  var grids = document.querySelectorAll('.sss-lib__grid');

  grids.forEach(function(grid){

    if (grid.classList.contains('sss-lib__grid--swipeable')){
      return;
    }

    var cards = Array.from(grid.querySelectorAll('.sss-lib__book'));
    var existingWrap = grid.nextElementSibling;

    if (
      existingWrap &&
      existingWrap.classList &&
      existingWrap.classList.contains('sss-lib__showMoreWrap')
    ){
      existingWrap.remove();
    }

    cards.forEach(function(card){
      card.classList.remove('is-mobile-hidden');
    });

    var isArchiveGrid = !!grid.closest('[data-archive-section]');
    var isBrowsePageGrid = grid.classList.contains('sss-lib__grid--browsePage');
    var isSpicePageGrid = grid.classList.contains('sss-lib__grid--spicePage');
    var desktopInitialCount = 36;
    var desktopIncrement = 20;
    var mobileInitialCount = (isBrowsePageGrid || isSpicePageGrid) ? 12 : 10;
    var mobileIncrement = isSpicePageGrid ? 6 : (isBrowsePageGrid ? 12 : 10);
    var shouldPaginate = false;
    var visibleCount = cards.length;
    var increment = cards.length;

    if (isArchiveGrid && !isMobile && cards.length > desktopInitialCount){
      shouldPaginate = true;
      visibleCount = desktopInitialCount;
      increment = desktopIncrement;
    } else if (isMobile && cards.length > mobileInitialCount){
      shouldPaginate = true;
      visibleCount = mobileInitialCount;
      increment = mobileIncrement;
    }

    if (!shouldPaginate) return;

    function updateVisibleBooks(){
      var matchingCards = cards.filter(function(card){
        return !card.hidden && card.style.display !== 'none';
      });

      cards.forEach(function(card){
        if (card.style.display === 'none'){
          card.classList.remove('is-mobile-hidden');
        }
      });

      matchingCards.forEach(function(card, index){
        card.classList.toggle('is-mobile-hidden', index >= visibleCount);
      });

      if (wrap){
        wrap.style.display = matchingCards.length > visibleCount ? '' : 'none';
      }
    }

    var wrap = document.createElement('div');
    wrap.className = 'sss-lib__showMoreWrap';
    if (isArchiveGrid && !isMobile){
      wrap.classList.add('sss-lib__showMoreWrap--desktop');
    }

    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'sss-lib__showMoreBtn';
    button.textContent = 'show more';

    button.addEventListener('click', function(){
      visibleCount += increment;
      updateVisibleBooks();

      var remainingVisibleCards = cards.filter(function(card){
        return card.style.display !== 'none';
      });

      if (visibleCount >= remainingVisibleCards.length){
        wrap.remove();
      }
    });

    wrap.appendChild(button);
    grid.insertAdjacentElement('afterend', wrap);

    grid.__sssPagination = {
      updateVisibleBooks: updateVisibleBooks
    };

    updateVisibleBooks();
  });
}

function refreshPaginatedGridVisibility(){
  document.querySelectorAll('.sss-lib__grid').forEach(function(grid){
    if (grid.__sssPagination && typeof grid.__sssPagination.updateVisibleBooks === 'function'){
      grid.__sssPagination.updateVisibleBooks();
    }
  });
}
/* ======================
   PERSONAL SHELF STORAGE
====================== */

function getAccountSnapshot(){
  var el = document.getElementById('sssMadeForYouAccountData');
  if (!el) return {};
  try {
    return JSON.parse(el.textContent || '{}') || {};
  } catch(e){
    return {};
  }
}

function normalizeAccountBook(book){
  if (!book) return null;
  var handle = book.handle || book.book_handle || '';
  var title = book.title || book.book_title || '';
  if (!handle && !title) return null;

  return {
    handle: handle,
    title: title,
    author: book.author || '',
    cover: book.cover || '',
    amazon: book.amazon || '',
    bookshop: book.bookshop || '',
    spice: book.spice || book.spice_level || '',
    darkness: book.darkness || book.darkness_level || '',
    tropes: Array.isArray(book.tropes) ? book.tropes : [],
    status: book.status || ''
  };
}

function mergeAccountBooks(localBooks){
  var account = getAccountSnapshot();
  var remoteBooks = Array.isArray(account.books) ? account.books : [];
  if (!remoteBooks.length) return Array.isArray(localBooks) ? localBooks : [];

  var seen = {};
  var merged = [];
  (Array.isArray(localBooks) ? localBooks : []).forEach(function(book){
    var key = getBookStatusKey(book);
    if (!key || seen[key]) return;
    seen[key] = true;
    merged.push(book);
  });

  remoteBooks.forEach(function(book){
    var normalized = normalizeAccountBook(book);
    var key = getBookStatusKey(normalized);
    if (!key || seen[key]) return;
    seen[key] = true;
    merged.push(normalized);
  });

  return merged;
}

function getAccountStatuses(){
  var account = getAccountSnapshot();
  var rows = Array.isArray(account.bookStatuses) ? account.bookStatuses : [];
  var statuses = {};
  rows.forEach(function(row){
    var key = getBookStatusKey(row);
    var status = row && row.status ? String(row.status).trim().toLowerCase() : '';
    if (key && status) {
      statuses[key] = status;
    }
  });
  return statuses;
}

function getAccountRatings(){
  var account = getAccountSnapshot();
  var rows = Array.isArray(account.bookStatuses) ? account.bookStatuses : [];
  var ratings = {};
  rows.forEach(function(row){
    var key = getBookStatusKey(row);
    var metadata = row && row.metadata && typeof row.metadata === 'object' ? row.metadata : {};
    var rating = parseInt(row && row.rating ? row.rating : (metadata.rating || 0), 10);
    if (key && rating >= 1 && rating <= 5) {
      ratings[key] = rating;
    }
  });
  return ratings;
}

function getShelf(){
  try {
    var params = new URLSearchParams(window.location.search || '');
    if ((window.location.hostname === 'localhost' || window.location.hostname.indexOf('.local') > -1) && params.get('mfy_empty_shelf') === '1'){
      return [];
    }
  } catch(e){}
  try {
    return mergeAccountBooks(JSON.parse(localStorage.getItem('sssMyShelf')) || []);
  } catch(e){
    return mergeAccountBooks([]);
  }
}

function setShelf(data){
  localStorage.setItem('sssMyShelf', JSON.stringify(data));
  document.dispatchEvent(new CustomEvent('sss:bookshelf-updated', {
    detail: { count: Array.isArray(data) ? data.length : 0 }
  }));
}

function getSavedQuotes(){
  try {
    return JSON.parse(localStorage.getItem('sssSavedQuotes')) || [];
  } catch(e){
    return [];
  }
}

function decodeHtmlText(value){
  var text = String(value || '');
  if (!text) return '';
  var textarea = document.createElement('textarea');
  var decoded = text;
  for (var i = 0; i < 3; i += 1){
    textarea.innerHTML = decoded;
    if (textarea.value === decoded) break;
    decoded = textarea.value;
  }
  return decoded;
}

function normalizeQuoteData(quoteData){
  quoteData = quoteData || {};
  return {
    handle: String(quoteData.handle || '').trim(),
    title: decodeHtmlText(quoteData.title || quoteData.bookTitle || ''),
    author: decodeHtmlText(quoteData.author || ''),
    text: decodeHtmlText(quoteData.text || quoteData.quote || ''),
    shelf: decodeHtmlText(quoteData.shelf || ''),
    tropes: Array.isArray(quoteData.tropes)
      ? quoteData.tropes.map(decodeHtmlText).filter(Boolean)
      : String(quoteData.tropes || '').split(',').map(function(item){ return decodeHtmlText(item.trim()); }).filter(Boolean),
    saved_at: quoteData.saved_at || Date.now()
  };
}

function setSavedQuotes(data){
  localStorage.setItem('sssSavedQuotes', JSON.stringify(data));
  document.dispatchEvent(new CustomEvent('sss:quote-saves-updated', {
    detail: { count: Array.isArray(data) ? data.length : 0 }
  }));
}

function getSavedQuoteKey(quoteData){
  if (!quoteData) return '';
  quoteData = normalizeQuoteData(quoteData);
  var title = quoteData.title || '';
  var text = quoteData.text || '';
  return (String(title).trim().toLowerCase() + '::' + String(text).trim().toLowerCase()).trim();
}

function isQuoteSaved(quoteData){
  var key = getSavedQuoteKey(quoteData);
  if (!key) return false;
  return getSavedQuotes().some(function(item){
    return getSavedQuoteKey(item) === key;
  });
}

function toggleSavedQuote(quoteData){
  var key = getSavedQuoteKey(quoteData);
  if (!key) return false;

  var saved = getSavedQuotes();
  var exists = false;
  var next = saved.filter(function(item){
    var same = getSavedQuoteKey(item) === key;
    if (same) exists = true;
    return !same;
  });

  if (!exists){
    next.unshift(normalizeQuoteData(quoteData));
  }

  setSavedQuotes(next);
  return !exists;
}

function addQuoteNote(quoteData){
  if (!quoteData) return '';
  quoteData = normalizeQuoteData(quoteData);

  var formatted = [
    '"' + String(quoteData.text || '').trim() + '"',
    [quoteData.title || '', quoteData.author || ''].filter(Boolean).join(' by ')
  ].filter(Boolean).join('\n');

  try {
    var existing = JSON.parse(localStorage.getItem('sssQuoteNotes')) || [];
    existing.unshift({
      key: getSavedQuoteKey(quoteData),
      text: formatted,
      saved_at: Date.now()
    });
    localStorage.setItem('sssQuoteNotes', JSON.stringify(existing.slice(0, 60)));
  } catch(e){}

  return formatted;
}

window.sssQuoteStorage = {
  getSavedQuotes: getSavedQuotes,
  isQuoteSaved: isQuoteSaved,
  toggleSavedQuote: toggleSavedQuote,
  addQuoteNote: addQuoteNote
};

function getBookStatusKey(bookData){
  if (!bookData) return '';

  var rawKey = bookData.handle || bookData.bookHandle || bookData.title || bookData.bookTitle || '';
  return String(rawKey).trim().toLowerCase();
}

function getBookStatusKeys(bookData){
  if (!bookData) return [];

  var seen = {};
  return [
    bookData.handle,
    bookData.bookHandle,
    bookData.book_handle,
    bookData.title,
    bookData.bookTitle,
    bookData.book_title
  ].map(function(value){
    return String(value || '').trim().toLowerCase();
  }).filter(function(key){
    if (!key || seen[key]) return false;
    seen[key] = true;
    return true;
  });
}

function getBookMapValue(bookData, map){
  var keys = getBookStatusKeys(bookData);
  for (var i = 0; i < keys.length; i += 1){
    if (Object.prototype.hasOwnProperty.call(map || {}, keys[i])){
      return map[keys[i]];
    }
  }
  return undefined;
}

function getBookStatuses(){
  try {
    var params = new URLSearchParams(window.location.search || '');
    if ((window.location.hostname === 'localhost' || window.location.hostname.indexOf('.local') > -1) && params.get('mfy_empty_shelf') === '1'){
      return {};
    }
  } catch(e){}
  try {
    return Object.assign(getAccountStatuses(), JSON.parse(localStorage.getItem('sssBookStatuses')) || {});
  } catch(e){
    return getAccountStatuses();
  }
}

function getBookReactions(){
  try {
    return JSON.parse(localStorage.getItem('sssBookReactions')) || {};
  } catch(e){
    return {};
  }
}

function setBookReactions(data){
  localStorage.setItem('sssBookReactions', JSON.stringify(data));
}

function getBookRatings(){
  try {
    return Object.assign(getAccountRatings(), JSON.parse(localStorage.getItem('sssBookRatings')) || {});
  } catch(e){
    return getAccountRatings();
  }
}

function setBookRatings(data){
  localStorage.setItem('sssBookRatings', JSON.stringify(data || {}));
  document.dispatchEvent(new CustomEvent('bbb:book-ratings-updated', {
    detail: {
      ratings: data || {}
    }
  }));
}

function setBookStatuses(data){
  localStorage.setItem('sssBookStatuses', JSON.stringify(data));
  document.dispatchEvent(new CustomEvent('bbb:book-statuses-updated', {
    detail: {
      statuses: data || {}
    }
  }));
}

function getBookStatus(bookData){
  var key = getBookStatusKey(bookData);
  if (!key) return '';

  var statuses = getBookStatuses();
  return getBookMapValue(bookData, statuses) || '';
}

function setBookStatus(bookData, status){
  var keys = getBookStatusKeys(bookData);
  var key = keys[0] || '';
  if (!key) return;

  var statuses = getBookStatuses();

  keys.forEach(function(statusKey){
    if (status){
      statuses[statusKey] = status;
    } else {
      delete statuses[statusKey];
    }
  });

  setBookStatuses(statuses);
  document.dispatchEvent(new CustomEvent('bbb:book-status-changed', {
    detail: {
      key: key,
      status: status || '',
      book: {
        handle: bookData.handle || bookData.bookHandle || '',
        title: bookData.title || bookData.bookTitle || '',
        author: bookData.author || '',
        cover: bookData.cover || '',
        amazon: bookData.amazon || '',
        bookshop: bookData.bookshop || ''
      },
      source: document.body.dataset.template || 'library'
    }
  }));
}

function getBookRating(bookData){
  var key = getBookStatusKey(bookData);
  if (!key && !bookData) return 0;

  var ratings = getBookRatings();
  var rating = parseInt(getBookMapValue(bookData, ratings) || bookData.rating || 0, 10);
  return rating >= 1 && rating <= 5 ? rating : 0;
}

function updateBookRatingOnShelf(bookData, rating){
  var keys = getBookStatusKeys(bookData);
  if (!keys.length) return;

  var shelf = getShelf();
  var changed = false;
  shelf = shelf.map(function(item){
    var itemKeys = getBookStatusKeys(item);
    var matches = itemKeys.some(function(itemKey){
      return keys.indexOf(itemKey) > -1;
    });
    if (!matches) return item;
    changed = true;
    return Object.assign({}, item, { rating: rating || '' });
  });

  if (changed){
    setShelf(shelf);
    renderMyShelf();
  }
}

function setBookRating(bookData, rating){
  var keys = getBookStatusKeys(bookData);
  var key = keys[0] || '';
  if (!key) return;

  var normalizedRating = parseInt(rating || 0, 10);
  if (!(normalizedRating >= 1 && normalizedRating <= 5)){
    normalizedRating = 0;
  }

  var ratings = getBookRatings();
  keys.forEach(function(ratingKey){
    if (normalizedRating){
      ratings[ratingKey] = normalizedRating;
    } else {
      delete ratings[ratingKey];
    }
  });

  setBookRatings(ratings);
  updateBookRatingOnShelf(bookData, normalizedRating);
  document.dispatchEvent(new CustomEvent('bbb:book-rating-changed', {
    detail: {
      key: key,
      rating: normalizedRating,
      status: getBookStatus(bookData),
      book: {
        handle: bookData.handle || bookData.bookHandle || '',
        title: bookData.title || bookData.bookTitle || '',
        author: bookData.author || '',
        cover: bookData.cover || '',
        amazon: bookData.amazon || '',
        bookshop: bookData.bookshop || ''
      },
      source: document.body.dataset.template || 'library'
    }
  }));
}

function getBookReaction(bookData){
  var key = getBookStatusKey(bookData);
  if (!key) return '';

  var reactions = getBookReactions();
  return reactions[key] || '';
}

function setBookReaction(bookData, reaction){
  var key = getBookStatusKey(bookData);
  if (!key) return;

  var reactions = getBookReactions();
  if (reaction){
    reactions[key] = reaction;
  } else {
    delete reactions[key];
  }

  setBookReactions(reactions);
}

function ensureBookOnShelf(bookData){
  if (!bookData || !bookData.title) return;

  var shelf = getShelf();
  var exists = shelf.find(function(item){
    return item.title === bookData.title;
  });

  if (exists){
    var existingRating = getBookRating(bookData);
    if (existingRating && String(exists.rating || '') !== String(existingRating)){
      exists.rating = existingRating;
      setShelf(shelf);
      renderMyShelf();
    }
    syncAllLibraryHearts();
    return;
  }

  shelf.push({
    handle: bookData.handle || '',
    url: bookData.url || '',
    title: bookData.title || '',
    author: bookData.author || '',
    cover: bookData.cover || '',
    amazon: bookData.amazon || '',
    bookshop: bookData.bookshop || '',
    spice: bookData.spice || '',
    darkness: bookData.darkness || '',
    tropes: bookData.tropes || '',
    tropesDisplay: bookData.tropesDisplay || bookData.tropes || '',
    why: bookData.why || '',
    newsletter: bookData.newsletter || '',
    tension: bookData.tension || '',
    damage: bookData.damage || '',
    yearning: bookData.yearning || '',
    boyfriend: bookData.boyfriend || '',
    boyfriendName: bookData.boyfriendName || '',
    reread: bookData.reread || '',
    ku: bookData.ku || '',
    mini: bookData.mini || '',
    series: bookData.series || '',
    seriesName: bookData.seriesName || '',
    seriesNumber: bookData.seriesNumber || '',
    standalone: bookData.standalone || '',
    rating: getBookRating(bookData) || bookData.rating || '',
    privateShelf: bookData.privateShelf || 'false'
  });

  setShelf(shelf);
  syncAllLibraryHearts();
  renderMyShelf();
  triggerShelfSparkle();
}

function getBookStatusMeta(status){
  var map = {
    read: { label: 'read', className: 'is-read' },
    reading: { label: 'reading', className: 'is-reading' },
    tbr: { label: 'tbr', className: 'is-tbr' },
    dnf: { label: 'dnf', className: 'is-dnf' }
  };

  return map[status] || null;
}

function isPrivateShelfName(value){
  return String(value || '').trim().toLowerCase() === 'private shelf';
}

function isPrivateBookData(data){
  if (!data) return false;

  if (data.privateShelf === true || data.privateShelf === 'true'){
    return true;
  }

  return isPrivateShelfName(data.shelf);
}

function sanitizeBookDataForLibraryType(data, libraryType){
  if (!data || libraryType === 'society') return data;

  var next = Object.assign({}, data);

  if (isPrivateBookData(next)){
    next.shelf = '';
  }

  return next;
}

function ensureStatusRibbon(target){
  if (!target) return null;

  var ribbon = target.querySelector('[data-book-status-ribbon]');

  if (!ribbon){
    ribbon = document.createElement('div');
    ribbon.className = 'sss-lib__statusRibbon';
    ribbon.setAttribute('data-book-status-ribbon', '');
    target.appendChild(ribbon);
  }

  return ribbon;
}

function ratingStampText(rating){
  rating = parseInt(rating || 0, 10);
  if (!(rating >= 1 && rating <= 5)) return '';
  return Array(rating + 1).join('★');
}

function ensureRatingStamp(target){
  if (!target) return null;

  var stamp = target.querySelector('[data-book-rating-stamp]');

  if (!stamp){
    stamp = document.createElement('div');
    stamp.className = 'sss-lib__ratingStamp';
    stamp.setAttribute('data-book-rating-stamp', '');
    target.appendChild(stamp);
  }

  return stamp;
}

function applyRatingStamp(target, rating){
  if (!target) return;

  var stamp = target.querySelector('[data-book-rating-stamp]');
  var stampText = ratingStampText(rating);

  if (!stampText){
    if (stamp) stamp.remove();
    return;
  }

  stamp = ensureRatingStamp(target);
  stamp.textContent = stampText;
  stamp.setAttribute('aria-label', rating + ' out of 5 stars');
}

function applyBookStatusToCard(card){
  if (!card || card.classList.contains('sss-lib__book--placeholder')) return;

  var coverWrap = card.querySelector('.sss-lib__coverWrap');
  if (!coverWrap) return;

  var bookData = {
    handle: card.dataset.handle,
    title: card.dataset.title
  };
  var status = getBookStatus(bookData);
  var rating = getBookRating(bookData);

  var ribbon = coverWrap.querySelector('[data-book-status-ribbon]');
  applyRatingStamp(coverWrap, rating);

  if (!status){
    if (ribbon) ribbon.remove();
    return;
  }

  var meta = getBookStatusMeta(status);
  if (!meta) return;

  ribbon = ensureStatusRibbon(coverWrap);
  ribbon.className = 'sss-lib__statusRibbon ' + meta.className;
  ribbon.textContent = meta.label;
}

function applyBookRatingToCover(cover){
  if (!cover) return;

  var rating = getBookRating({
    handle: cover.dataset.handle || cover.dataset.bookHandle || '',
    title: cover.dataset.title || cover.dataset.bookTitle || '',
    rating: cover.dataset.rating || ''
  });

  applyRatingStamp(cover, rating);
}

function bookDataFromElement(bookBtn){
  if (!bookBtn || !bookBtn.dataset) return null;

  return {
    handle: bookBtn.dataset.handle || '',
    url: bookBtn.dataset.url || '',
    title: bookBtn.dataset.title || '',
    author: bookBtn.dataset.author || '',
    cover: bookBtn.dataset.cover || '',
    amazon: bookBtn.dataset.amazon || '',
    bookshop: bookBtn.dataset.bookshop || '',
    spice: bookBtn.dataset.spice || '',
    darkness: bookBtn.dataset.darkness || '',
    tropes: bookBtn.dataset.tropes || '',
    tropesDisplay: bookBtn.dataset.tropesDisplay || bookBtn.dataset.tropes || '',
    why: bookBtn.dataset.why || '',
    newsletter: bookBtn.dataset.newsletter || '',
    tension: bookBtn.dataset.tension || '',
    damage: bookBtn.dataset.damage || '',
    yearning: bookBtn.dataset.yearning || '',
    boyfriend: bookBtn.dataset.boyfriend || '',
    boyfriendName: bookBtn.dataset.boyfriendName || '',
    reread: bookBtn.dataset.reread || '',
    ku: bookBtn.dataset.ku || '',
    mini: bookBtn.dataset.mini || '',
    series: bookBtn.dataset.series || '',
    seriesName: bookBtn.dataset.seriesName || '',
    seriesNumber: bookBtn.dataset.seriesNumber || '',
    standalone: bookBtn.dataset.standalone || '',
    rating: bookBtn.dataset.rating || '',
    privateShelf: bookBtn.dataset.privateShelf || 'false'
  };
}

function findBookPageSource(root){
  var page = root ? root.closest('.sss-book-page') : document.querySelector('.sss-book-page');
  var hero = root ? root.closest('.sss-book-page__hero') : null;

  if (hero){
    return hero.querySelector('.sss-book-page__coverWrap.sss-lib__book');
  }

  if (page){
    return page.querySelector('.sss-book-page__coverWrap.sss-lib__book')
      || page.querySelector('.sss-book-page__titleRow.sss-lib__book');
  }

  return null;
}

function renderBookPageRatingControls(root){
  if (!root) return;

  var source = findBookPageSource(root);
  var bookData = bookDataFromElement(source);
  if (!bookData || !bookData.title) return;

  var rating = getBookRating(bookData);
  var status = getBookStatus(bookData);
  var meta = getBookStatusMeta(status);
  var coverWrap = source && source.classList.contains('sss-book-page__coverWrap') ? source : null;
  var ribbon = coverWrap ? coverWrap.querySelector('[data-book-status-ribbon]') : null;

  if (coverWrap){
    applyRatingStamp(coverWrap, rating);

    if (!status){
      if (ribbon) ribbon.remove();
    } else if (meta){
      ribbon = ensureStatusRibbon(coverWrap);
      ribbon.className = 'sss-lib__statusRibbon ' + meta.className;
      ribbon.textContent = meta.label;
    }
  }

  root.querySelectorAll('[data-book-page-rating-option]').forEach(function(button){
    var buttonRating = parseInt(button.getAttribute('data-book-page-rating-option') || '0', 10);
    var active = rating >= buttonRating;
    button.classList.toggle('is-active', active);
    button.setAttribute('aria-checked', rating === buttonRating ? 'true' : 'false');
  });

  var summary = root.querySelector('[data-book-page-rating-summary]');
  if (summary){
    summary.textContent = rating
      ? rating + '/5 saved. this book is marked read.'
      : 'rating marks it read and saves it to your bookshelf.';
  }
}

function bindBookPageRatingControls(){
  var controls = document.querySelectorAll('[data-book-page-rating-controls]');
  if (!controls.length) return;

  if (!document.__sssBookPageRatingSyncBound){
    document.__sssBookPageRatingSyncBound = true;
    ['bbb:book-rating-changed', 'bbb:book-ratings-updated', 'bbb:book-status-changed', 'bbb:book-statuses-updated'].forEach(function(eventName){
      document.addEventListener(eventName, function(){
        document.querySelectorAll('[data-book-page-rating-controls]').forEach(renderBookPageRatingControls);
      });
    });
  }

  controls.forEach(function(root){
    if (root.__sssBookPageRatingBound) return;
    root.__sssBookPageRatingBound = true;

    root.querySelectorAll('[data-book-page-rating-option]').forEach(function(button){
      button.addEventListener('click', function(){
        var source = findBookPageSource(root);
        var bookData = bookDataFromElement(source);
        var nextRating = parseInt(button.getAttribute('data-book-page-rating-option') || '0', 10);

        if (!bookData || !bookData.title || !(nextRating >= 1 && nextRating <= 5)) return;

        setBookStatus(bookData, 'read');
        setBookRating(bookData, nextRating);
        ensureBookOnShelf(bookData);
        syncBookStatusUI();
        syncAllLibraryHearts();
        renderBookPageRatingControls(root);

        document.querySelectorAll('.sss-lib__madeForYou').forEach(function(mfyRoot){
          if (typeof mfyRoot.__refreshMadeForYou === 'function'){
            mfyRoot.__refreshMadeForYou();
          }
        });
      });
    });

    renderBookPageRatingControls(root);
  });
}

function ensureModalStatusControls(modal){
  if (!modal) return null;

  var controls = modal.querySelector('[data-modal-status-controls]');
  var cta = modal.querySelector('.sss-lib__mcta');
  var below = modal.querySelector('.sss-lib__mbelow');

  if (controls){
    if (below && controls.parentNode !== below){
      below.insertAdjacentElement('afterbegin', controls);
    }
    return controls;
  }

  if (!cta || !cta.parentNode) return null;

  controls = document.createElement('div');
  controls.className = 'sss-lib__mstatus';
  controls.setAttribute('data-modal-status-controls', '');
  controls.innerHTML = [
    '<div class="sss-lib__mstatusLabel">tag this book</div>',
    '<div class="sss-lib__mstatusButtons">',
    '<button type="button" class="sss-lib__mstatusBtn is-read" data-status-option="read">read</button>',
    '<button type="button" class="sss-lib__mstatusBtn is-reading" data-status-option="reading">reading</button>',
    '<button type="button" class="sss-lib__mstatusBtn is-tbr" data-status-option="tbr">tbr</button>',
    '<button type="button" class="sss-lib__mstatusBtn is-dnf" data-status-option="dnf">dnf</button>',
    '</div>',
    '<div class="sss-lib__mreaction" data-modal-reaction-controls hidden>',
    '<div class="sss-lib__mstatusLabel">how did it hit?</div>',
    '<div class="sss-lib__mreactionButtons">',
    '<button type="button" class="sss-lib__mstatusBtn is-obsessed" data-reaction-option="obsessed">obsessed</button>',
    '<button type="button" class="sss-lib__mstatusBtn is-liked" data-reaction-option="liked_it">liked it</button>',
    '<button type="button" class="sss-lib__mstatusBtn is-notforme" data-reaction-option="not_for_me">not for me</button>',
    '</div>',
    '</div>',
    '<div class="sss-lib__mreaderRating" data-modal-rating-controls>',
    '<div class="sss-lib__mstatusLabel">your rating</div>',
    '<div class="sss-lib__mstarButtons" role="radiogroup" aria-label="rate this book">',
    '<button type="button" class="sss-lib__mstarBtn" data-rating-option="1" aria-label="rate 1 star" aria-checked="false" role="radio">★</button>',
    '<button type="button" class="sss-lib__mstarBtn" data-rating-option="2" aria-label="rate 2 stars" aria-checked="false" role="radio">★</button>',
    '<button type="button" class="sss-lib__mstarBtn" data-rating-option="3" aria-label="rate 3 stars" aria-checked="false" role="radio">★</button>',
    '<button type="button" class="sss-lib__mstarBtn" data-rating-option="4" aria-label="rate 4 stars" aria-checked="false" role="radio">★</button>',
    '<button type="button" class="sss-lib__mstarBtn" data-rating-option="5" aria-label="rate 5 stars" aria-checked="false" role="radio">★</button>',
    '</div>',
    '<div class="sss-lib__mratingNote" data-modal-rating-note>rating marks it read and saves it to your bookshelf.</div>',
    '</div>'
  ].join('');

  if (below){
    below.insertAdjacentElement('afterbegin', controls);
  } else {
    cta.insertAdjacentElement('afterend', controls);
  }

  controls.querySelectorAll('[data-status-option]').forEach(function(button){
    button.addEventListener('click', function(){
      var modalBook = modal.__currentBook;
      if (!modalBook) return;

      var nextStatus = button.getAttribute('data-status-option');
      var currentStatus = getBookStatus(modalBook);
      var resolvedStatus = currentStatus === nextStatus ? '' : nextStatus;

      setBookStatus(modalBook, resolvedStatus);
      if (resolvedStatus !== 'read' && resolvedStatus !== 'dnf'){
        setBookReaction(modalBook, '');
      } else if (resolvedStatus === 'dnf' && !getBookReaction(modalBook)){
        setBookReaction(modalBook, 'not_for_me');
      }
      if (resolvedStatus === 'tbr'){
        ensureBookOnShelf(modalBook);
      }
      if (resolvedStatus !== 'read'){
        setBookRating(modalBook, 0);
      }
      syncBookStatusUI();
      document.querySelectorAll('.sss-lib__madeForYou').forEach(function(mfyRoot){
        if (typeof mfyRoot.__refreshMadeForYou === 'function'){
          mfyRoot.__refreshMadeForYou();
        }
      });
    });
  });

  controls.querySelectorAll('[data-reaction-option]').forEach(function(button){
    button.addEventListener('click', function(){
      var modalBook = modal.__currentBook;
      if (!modalBook) return;

      var nextReaction = button.getAttribute('data-reaction-option');
      var currentReaction = getBookReaction(modalBook);
      var resolvedReaction = currentReaction === nextReaction ? '' : nextReaction;

      setBookReaction(modalBook, resolvedReaction);
      syncBookStatusUI();
      document.querySelectorAll('.sss-lib__madeForYou').forEach(function(mfyRoot){
        if (typeof mfyRoot.__refreshMadeForYou === 'function'){
          mfyRoot.__refreshMadeForYou();
        }
      });
    });
  });

  controls.querySelectorAll('[data-rating-option]').forEach(function(button){
    button.addEventListener('click', function(){
      var modalBook = modal.__currentBook;
      if (!modalBook) return;

      var nextRating = parseInt(button.getAttribute('data-rating-option') || '0', 10);
      if (!(nextRating >= 1 && nextRating <= 5)) return;

      setBookStatus(modalBook, 'read');
      setBookRating(modalBook, nextRating);
      ensureBookOnShelf(modalBook);
      syncBookStatusUI();
      document.querySelectorAll('.sss-lib__madeForYou').forEach(function(mfyRoot){
        if (typeof mfyRoot.__refreshMadeForYou === 'function'){
          mfyRoot.__refreshMadeForYou();
        }
      });
    });
  });

  return controls;
}

function renderModalBookStatus(modal, bookData){
  if (!modal) return;

  modal.__currentBook = bookData || null;

  var coverFrame = modal.querySelector('.sss-lib__mcoverFrame');
  var coverWrap = modal.querySelector('.sss-lib__mcoverWrap');
  var ribbon = coverFrame
    ? coverFrame.querySelector('[data-book-status-ribbon]')
    : (coverWrap ? coverWrap.querySelector('[data-book-status-ribbon]') : null);
  var status = getBookStatus(bookData);
  var meta = getBookStatusMeta(status);
  var rating = getBookRating(bookData);
  applyRatingStamp(coverFrame || coverWrap, rating);

  if (!status){
    if (ribbon) ribbon.remove();
  } else if ((coverFrame || coverWrap) && meta) {
    ribbon = ensureStatusRibbon(coverFrame || coverWrap);
    ribbon.className = 'sss-lib__statusRibbon sss-lib__statusRibbon--modal ' + meta.className;
    ribbon.textContent = meta.label;
  }

  var controls = ensureModalStatusControls(modal);
  if (!controls) return;

  var reactionWrap = controls.querySelector('[data-modal-reaction-controls]');
  var reaction = getBookReaction(bookData);
  if (reactionWrap){
    reactionWrap.hidden = !(status === 'read' || status === 'dnf');
  }

  controls.querySelectorAll('[data-status-option]').forEach(function(button){
    var buttonStatus = button.getAttribute('data-status-option');
    button.classList.toggle('is-active', buttonStatus === status);
  });

  controls.querySelectorAll('[data-reaction-option]').forEach(function(button){
    var buttonReaction = button.getAttribute('data-reaction-option');
    button.classList.toggle('is-active', buttonReaction === reaction);
  });

  controls.querySelectorAll('[data-rating-option]').forEach(function(button){
    var buttonRating = parseInt(button.getAttribute('data-rating-option') || '0', 10);
    var active = rating >= buttonRating;
    button.classList.toggle('is-active', active);
    button.setAttribute('aria-checked', rating === buttonRating ? 'true' : 'false');
  });

  var ratingNote = controls.querySelector('[data-modal-rating-note]');
  if (ratingNote){
    ratingNote.textContent = rating
      ? rating + '/5 saved. this book is marked read.'
      : 'rating marks it read and saves it to your bookshelf.';
  }
}

function syncBookStatusUI(){
  document.querySelectorAll('.sss-lib__book').forEach(applyBookStatusToCard);
  document.querySelectorAll('[data-book-rating-cover]').forEach(applyBookRatingToCover);

  document.querySelectorAll('.sss-lib__modal').forEach(function(modal){
    if (modal.__currentBook){
      renderModalBookStatus(modal, modal.__currentBook);
    }
  });
}

window.sssRenderModalBookStatus = renderModalBookStatus;
window.sssSyncBookStatusUI = syncBookStatusUI;

function triggerShelfSparkle(){
  var section = document.getElementById('sssMyShelfSection');
  if (!section) return;

  section.classList.remove('is-sparkling');
  void section.offsetWidth;
  section.classList.add('is-sparkling');

  window.setTimeout(function(){
    section.classList.remove('is-sparkling');
  }, 1400);
}

function toggleSave(heartEl, bookBtn){

  var shelf = getShelf();

var bookData = bookDataFromElement(bookBtn);

if (!bookData.title) return; // prevent broken saves

  var exists = shelf.find(function(b){
    return b.title === bookData.title;
  });

  if (exists){
    shelf = shelf.filter(function(b){
      return b.title !== bookData.title;
    });
    trackSiteEvent("book_unsaved", {
      bookHandle: bookBtn.dataset.handle || '',
      bookTitle: bookData.title,
      seriesHandle: bookBtn.dataset.series || '',
      uiLocation: document.body.dataset.template || "library",
      metadata: {
        author: bookBtn.dataset.author || ''
      }
    });
    applyHeartSavedState(heartEl, false);
    document.dispatchEvent(new CustomEvent('bbb:shelf-unsaved', {
      detail: {
        count: shelf.length,
        bookTitle: bookData.title,
        bookHandle: bookBtn.dataset.handle || '',
        book: bookData,
        source: document.body.dataset.template || 'library'
      }
    }));
  } else {
    shelf.push(bookData);
    trackBookSave(bookData.title, bookBtn.dataset.handle || '');
    trackSiteEvent("book_saved", {
      bookHandle: bookBtn.dataset.handle || '',
      bookTitle: bookData.title,
      seriesHandle: bookBtn.dataset.series || '',
      uiLocation: document.body.dataset.template || "library",
      metadata: {
        author: bookBtn.dataset.author || ''
      }
    });
    showSaveToast();
    applyHeartSavedState(heartEl, true);
    document.dispatchEvent(new CustomEvent('bbb:shelf-saved', {
      detail: {
        count: shelf.length,
        bookTitle: bookData.title,
        bookHandle: bookBtn.dataset.handle || '',
        book: bookData,
        source: document.body.dataset.template || 'library'
      }
    }));
  }

  setShelf(shelf);
  renderMyShelf();   // ← ADD THIS
  if (!exists){
    triggerShelfSparkle();
  }
}

function applyHeartSavedState(heartEl, isSaved){
  if (!heartEl) return;

  heartEl.classList.toggle('is-saved', !!isSaved);

  var icon = heartEl.querySelector('[data-heart-icon]');
  var label = heartEl.querySelector('[data-heart-label]');

  if (icon){
    icon.textContent = isSaved ? '♥' : '♡';
  } else {
    heartEl.textContent = isSaved ? '♥' : '♡';
  }

  if (label){
    label.textContent = heartEl.classList.contains('sss-book-page__addTbr')
      ? (isSaved ? 'in tbr' : 'add to tbr')
      : (isSaved ? 'saved' : 'save');
  }

  heartEl.setAttribute('aria-label', heartEl.classList.contains('sss-book-page__addTbr')
    ? (isSaved ? 'remove from tbr' : 'add to tbr')
    : (isSaved ? 'remove from your bookshelf' : 'save to your bookshelf'));
}

function syncAllLibraryHearts(){
  var shelf = getShelf();
  document.querySelectorAll('.sss-lib__book [data-heart]').forEach(function(heart){
    var bookBtn = heart.closest('.sss-lib__book');
    if (!bookBtn) return;

    var saved = shelf.find(function(b){
      return b.title === bookBtn.dataset.title;
    });

    applyHeartSavedState(heart, !!saved);
  });
}

function bindStandaloneBookPageSaveControls(){
  document.querySelectorAll('.sss-book-page .sss-lib__book [data-heart]').forEach(function(heart){
    if (heart.__sssStandaloneSaveBound) return;

    var bookBtn = heart.closest('.sss-lib__book');
    if (!bookBtn) return;

    heart.__sssStandaloneSaveBound = true;
    applyHeartSavedState(heart, !!getShelf().find(function(b){
      return b.title === bookBtn.dataset.title;
    }));

    function handleSave(e){
      e.preventDefault();
      e.stopPropagation();
      toggleSave(heart, bookBtn);
      syncAllLibraryHearts();
    }

    heart.addEventListener('click', handleSave);
    heart.addEventListener('keydown', function(e){
      if (e.key !== 'Enter' && e.key !== ' ') return;
      handleSave(e);
    });
  });
}

function stringifyBookDatasetValue(value){
  if (value === null || typeof value === 'undefined') return '';
  if (Array.isArray(value)){
    return value.map(function(item){
      if (item === null || typeof item === 'undefined') return '';
      if (typeof item === 'object'){
        return item.name || item.label || item.title || item.slug || item.handle || '';
      }
      return item;
    }).filter(function(item){
      return String(item || '').trim() !== '';
    }).join(',');
  }
  if (typeof value === 'object'){
    return value.name || value.label || value.title || value.slug || value.handle || '';
  }
  return String(value);
}

function hydrateShelfBook(book){
  if (!book) return null;

  var bookHandle = String(book.handle || '').trim().toLowerCase();
  var bookTitle = String(book.title || '').trim().toLowerCase();
  var sourceCard = null;
  var match = null;

  sourceCard = Array.from(document.querySelectorAll('.sss-lib__book[data-title]')).find(function(card){
    if (card.closest('#sssMyShelfSection')) return false;
    var cardHandle = String(card.dataset.handle || '').trim().toLowerCase();
    var cardTitle = String(card.dataset.title || '').trim().toLowerCase();
    if (bookHandle && cardHandle && cardHandle === bookHandle) return true;
    if (bookTitle && cardTitle && cardTitle === bookTitle) return true;
    return false;
  });

  if (sourceCard){
    return {
      handle: sourceCard.dataset.handle || book.handle || '',
      title: sourceCard.dataset.title || book.title || '',
      author: sourceCard.dataset.author || book.author || '',
      cover: sourceCard.dataset.cover || book.cover || '',
      amazon: sourceCard.dataset.amazon || book.amazon || '',
      bookshop: sourceCard.dataset.bookshop || book.bookshop || '',
      spice: sourceCard.dataset.spice || '',
      tropes: sourceCard.dataset.tropes || '',
      tropesDisplay: sourceCard.dataset.tropesDisplay || sourceCard.dataset.tropes || '',
      why: sourceCard.dataset.why || '',
      newsletter: sourceCard.dataset.newsletter || '',
      tension: sourceCard.dataset.tension || '',
      damage: sourceCard.dataset.damage || '',
      yearning: sourceCard.dataset.yearning || '',
      boyfriend: sourceCard.dataset.boyfriend || '',
      reread: sourceCard.dataset.reread || '',
      ku: sourceCard.dataset.ku || '',
      mini: sourceCard.dataset.mini || '',
      series: sourceCard.dataset.series || '',
      seriesName: sourceCard.dataset.seriesName || '',
      seriesNumber: sourceCard.dataset.seriesNumber || '',
      rating: sourceCard.dataset.rating || book.rating || '',
      privateShelf: sourceCard.dataset.privateShelf || 'false'
    };
  }

  if (typeof books !== 'undefined' && Array.isArray(books) && books.length){
    match = books.find(function(entry){
      var entryHandle = String(entry && entry.handle || '').trim().toLowerCase();
      var entryTitle = String(entry && entry.title || '').trim().toLowerCase();
      if (bookHandle && entryHandle && entryHandle === bookHandle) return true;
      if (bookTitle && entryTitle && entryTitle === bookTitle) return true;
      return false;
    });
  }

  if (!match) return book;

  return {
    handle: match.handle || book.handle || '',
    title: match.title || book.title || '',
    author: match.author || book.author || '',
    cover: match.cover || book.cover || '',
    amazon: match.amazon || book.amazon || '',
    bookshop: match.bookshop || book.bookshop || '',
    spice: match.spice || '',
    tropes: match.tropes || '',
    tropesDisplay: match.tropesDisplay || match.tropes || '',
    why: match.why || '',
    newsletter: match.newsletter || '',
    tension: match.tension || '',
    damage: match.damage || '',
    yearning: match.yearning || '',
    boyfriend: match.boyfriend || '',
    reread: match.reread || '',
    ku: match.ku || '',
    mini: match.mini || '',
    series: match.series || '',
    seriesName: match.seriesName || '',
    seriesNumber: match.seriesNumber || '',
    rating: book.rating || '',
    privateShelf: match.privateShelf || 'false'
  };
}

function renderMyShelf(){

  var shelf = getShelf();
  var recentShelf = shelf.slice().reverse().slice(0, 3);
  var shouldSwipe = shelf.length >= 5;
  var displayShelf = shouldSwipe ? shelf.slice().reverse() : recentShelf;
  var section = document.getElementById('sssMyShelfSection');
  var grid = document.getElementById('sssMyShelfGrid');
  var placeholderCount = shouldSwipe ? 0 : Math.max(0, 3 - recentShelf.length);
  var placeholderMarkup = '';

  if (!section || !grid) return;

  section.hidden = false;

  for (var i = 0; i < placeholderCount; i += 1){
    placeholderMarkup += `
      <div class="sss-lib__book sss-lib__book--mini sss-lib__book--placeholder" aria-hidden="true">
        <div class="sss-lib__coverWrap sss-lib__coverWrap--placeholder">
          <div class="sss-lib__emptyShelfPlaceholder">
            <span class="sss-lib__emptyShelfLabel">book here</span>
          </div>
        </div>
      </div>
    `;
  }

  grid.classList.toggle('sss-lib__grid--swipeable', shouldSwipe);

  var savedMarkup = displayShelf.map(function(book){
    var hydratedBook = hydrateShelfBook(book) || book;
    return `
      <div 
        class="sss-lib__book sss-lib__book--mini"
        data-handle="${stringifyBookDatasetValue(hydratedBook.handle || book.handle)}"
        data-url="${stringifyBookDatasetValue(hydratedBook.url || book.url || ((hydratedBook.handle || book.handle) ? '/books/' + encodeURIComponent(hydratedBook.handle || book.handle) + '/' : ''))}"
        data-title="${stringifyBookDatasetValue(hydratedBook.title || book.title)}"
        data-author="${stringifyBookDatasetValue(hydratedBook.author || book.author)}"
        data-cover="${stringifyBookDatasetValue(hydratedBook.cover || book.cover)}"
        data-amazon="${stringifyBookDatasetValue(hydratedBook.amazon || book.amazon)}"
        data-bookshop="${stringifyBookDatasetValue(hydratedBook.bookshop || book.bookshop)}"
        data-spice="${stringifyBookDatasetValue(hydratedBook.spice)}"
        data-tropes="${stringifyBookDatasetValue(hydratedBook.tropes)}"
        data-tropes-display="${stringifyBookDatasetValue(hydratedBook.tropesDisplay)}"
        data-why="${stringifyBookDatasetValue(hydratedBook.why)}"
        data-newsletter="${stringifyBookDatasetValue(hydratedBook.newsletter)}"
        data-tension="${stringifyBookDatasetValue(hydratedBook.tension)}"
        data-damage="${stringifyBookDatasetValue(hydratedBook.damage)}"
        data-yearning="${stringifyBookDatasetValue(hydratedBook.yearning)}"
        data-boyfriend="${stringifyBookDatasetValue(hydratedBook.boyfriend)}"
        data-reread="${stringifyBookDatasetValue(hydratedBook.reread)}"
        data-ku="${stringifyBookDatasetValue(hydratedBook.ku)}"
        data-mini="${stringifyBookDatasetValue(hydratedBook.mini)}"
        data-series="${stringifyBookDatasetValue(hydratedBook.series)}"
        data-series-name="${stringifyBookDatasetValue(hydratedBook.seriesName)}"
        data-series-number="${stringifyBookDatasetValue(hydratedBook.seriesNumber)}"
        data-rating="${stringifyBookDatasetValue(hydratedBook.rating || book.rating)}"
        data-private-shelf="${stringifyBookDatasetValue(hydratedBook.privateShelf)}"
      >

        <div class="sss-lib__coverWrap">

          <span
            class="sss-lib__heart is-saved"
            data-heart
            role="button"
            aria-label="remove from shelf"
          >
            <span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♥</span>
            <span class="sss-lib__heartLabel" data-heart-label>saved</span>
          </span>

          <img 
            class="sss-lib__cover"
            src="${stringifyBookDatasetValue(hydratedBook.cover || book.cover)}"
            alt="${stringifyBookDatasetValue(bookCoverAlt(hydratedBook.title || book.title, hydratedBook.author || book.author, hydratedBook.shelf || book.shelf))}"
          >

        </div>
        
        <div class="sss-lib__under">
          <div class="sss-lib__name" style="text-transform:none !important;">${stringifyBookDatasetValue(hydratedBook.title || book.title)}</div>
          <div class="sss-lib__author" style="text-transform:none !important;">${stringifyBookDatasetValue(hydratedBook.author || book.author)}</div>
        </div>

      </div>
    `;
  }).join('');

  if (!shelf.length){
    grid.classList.remove('sss-lib__grid--swipeable');
    grid.innerHTML = placeholderMarkup;
    initMobileGridPagination();
    return;
  }

  grid.innerHTML = savedMarkup + placeholderMarkup;
  syncBookStatusUI();

  /* enable unsave from saved shelf */

  grid.querySelectorAll('[data-heart]').forEach(function(heart){

    var bookBtn = heart.closest('.sss-lib__book');
    if (!bookBtn) return;

    heart.addEventListener('click', function(e){

      e.stopPropagation();

      var title = bookBtn.dataset.title;
      var shelf = getShelf();
      var bookData = {
        handle: bookBtn.dataset.handle || '',
        url: bookBtn.dataset.url || '',
        title: bookBtn.dataset.title || '',
        author: bookBtn.dataset.author || '',
        cover: bookBtn.dataset.cover || '',
        amazon: bookBtn.dataset.amazon || '',
        bookshop: bookBtn.dataset.bookshop || '',
        spice: bookBtn.dataset.spice || '',
        darkness: bookBtn.dataset.darkness || '',
        tropes: bookBtn.dataset.tropes || '',
        tropesDisplay: bookBtn.dataset.tropesDisplay || bookBtn.dataset.tropes || '',
        why: bookBtn.dataset.why || '',
        newsletter: bookBtn.dataset.newsletter || '',
        tension: bookBtn.dataset.tension || '',
        damage: bookBtn.dataset.damage || '',
        yearning: bookBtn.dataset.yearning || '',
        boyfriend: bookBtn.dataset.boyfriend || '',
        boyfriendName: bookBtn.dataset.boyfriendName || '',
        reread: bookBtn.dataset.reread || '',
        ku: bookBtn.dataset.ku || '',
        mini: bookBtn.dataset.mini || '',
        series: bookBtn.dataset.series || '',
        seriesName: bookBtn.dataset.seriesName || '',
        seriesNumber: bookBtn.dataset.seriesNumber || '',
        standalone: bookBtn.dataset.standalone || '',
        privateShelf: bookBtn.dataset.privateShelf || 'false'
      };

      shelf = shelf.filter(function(b){
        return b.title !== title;
      });

      setShelf(shelf);
      document.dispatchEvent(new CustomEvent('bbb:shelf-unsaved', {
        detail: {
          count: shelf.length,
          bookTitle: bookData.title,
          bookHandle: bookData.handle,
          book: bookData,
          source: document.body.dataset.template || 'bookshelf'
        }
      }));

      /* update hearts everywhere else */
      document.querySelectorAll('.sss-lib__book').forEach(function(btn){

        if(btn.dataset.title === title){

          var h = btn.querySelector('[data-heart]');
          if(h){
            applyHeartSavedState(h, false);
          }

        }

      });

      /* re-render shelf */
      renderMyShelf();

    });

  });

  initMobileGridPagination();

}
function buildShelfText(){

  var shelf = getShelf();
  if (!shelf.length) return '';

  var text = "🖤 my society reading list\n\n";

  shelf.forEach(function(book, index){

    text += (index + 1) + ". " + book.title + "\n";
    text += "   by " + book.author + "\n";

    if (book.amazon){
      text += "   amazon: " + book.amazon + "\n";
    }

    if (book.bookshop){
      text += "   bookshop: " + book.bookshop + "\n";
    }

    text += "\n";
  });

  return text;
}

function openNotepad(){

  var shelf = getShelf();
  if (!shelf.length) return;

  var body = document.getElementById('sssNotepadBody');
  var pad = document.getElementById('sssNotepad');

  if (!body || !pad) return;

  body.innerHTML = shelf.map(function(book, index){
    return `
      <div style="margin-bottom:18px;">
        <strong>${index + 1}. ${book.title}</strong><br>
        by ${book.author}
      </div>
    `;
  }).join('');

  pad.hidden = false;
}

function closeNotepad(){
  var pad = document.getElementById('sssNotepad');
  if (pad) pad.hidden = true;
}

/* Buttons */

var notesBtn = document.getElementById('sssExportNotes');
if (notesBtn){
  notesBtn.addEventListener('click', function(){
    var text = buildShelfText();
    if (!text) return;

    navigator.clipboard.writeText(text).then(function(){
      notesBtn.textContent = "copied ✨";
      setTimeout(function(){
        notesBtn.textContent = "copy list";
      }, 2000);
    });
  });
}

var emailBtn = document.getElementById('sssEmailShelf');
if (emailBtn){
  emailBtn.addEventListener('click', function(){
    var text = buildShelfText();
    if (!text) return;

    var subject = "🖤 My Society Reading List";

    window.location.href =
      "mailto:?subject=" +
      encodeURIComponent(subject) +
      "&body=" +
      encodeURIComponent(text);
  });
}

var notepadClose = document.getElementById('sssNotepadClose');
if (notepadClose){
  notepadClose.addEventListener('click', closeNotepad);
}

var notepadOverlay = document.getElementById('sssNotepad');
if (notepadOverlay){
  notepadOverlay.addEventListener('click', function(e){
    if (e.target === notepadOverlay){
      closeNotepad();
    }
  });
}
function showSaveToast(){

  const toast = document.getElementById("sssSaveToast");
  const link = document.getElementById("sssToastShelfLink");

  if(!toast) return;

  function openBookshelfDestination(){
    const shelf = document.getElementById("sssMyShelfSection");
    const libraryShelfUrl = "/library/?shelf=open";

    if (shelf){
      shelf.hidden = false;

      setTimeout(function(){
        shelf.scrollIntoView({
          behavior: "smooth",
          block: "start"
        });
      }, 100);

      return;
    }

    window.location.href = libraryShelfUrl;
  }

  toast.classList.add("is-visible");

  if(link){
    link.setAttribute("href", "/library/?shelf=open");

    link.onclick = function(e){

      e.preventDefault();
      openBookshelfDestination();

    };

  }

  setTimeout(()=>{
    toast.classList.remove("is-visible");
  }, 3000);

}

window.addEventListener('resize', initMobileGridPagination);
renderMyShelf();
/* ======================
   OPEN SHELF VIA URL
====================== */

(function(){

const params = new URLSearchParams(window.location.search);
const openShelf = params.get("shelf");

if(openShelf !== "open") return;

window.addEventListener("load", function(){

  const shelf = document.getElementById("sssMyShelfSection");

  if(!shelf) return;

  shelf.hidden = false;

  setTimeout(()=>{
    shelf.scrollIntoView({
      behavior:"smooth",
      block:"start"
    });

    if (window.history && window.history.replaceState){
      window.history.replaceState({}, "", window.location.pathname + window.location.hash);
    }
  },300);

});

})();
/* ======================
   INIT
====================== */

function init(){

  document.querySelectorAll('[data-sss-lib]').forEach(function(root){
    var libraryType = root.getAttribute('data-sss-lib');

    if (root.__bound) return;
    root.__bound = true;

    var modal = document.querySelector('.sss-lib__modal');
    var titleEl = document.querySelector('[data-mtitle]');
    var authorEl = document.querySelector('[data-mauthor]');
    var coverEl = document.querySelector('[data-mcover]');
    var kuBtn = document.querySelector('[data-ku-btn]');
    var amazonBtn = document.querySelector('[data-amazon-btn]');
    var bookshopBtn = document.querySelector('[data-bookshop-btn]');
    var tropesEl = document.querySelector('[data-mtropes]');
    var whyEl = document.querySelector('[data-mwhy]');
    var miniEl = document.querySelector('[data-mmini]');
    var standaloneEl = document.querySelector('[data-mstandalone]');
    var tensionEl = document.querySelector('[data-mtension]');
    var damageEl = document.querySelector('[data-mdamage]');
    var yearningEl = document.querySelector('[data-myearning]');
    var kuEl = document.querySelector('[data-mku]');
    var darknessEl = document.querySelector('[data-mdarkness]');
    var boyfriendEl = document.querySelector('[data-mboyfriend]');
    var rereadEl = document.querySelector('[data-mreread]');
    var spiceEl = modal ? modal.querySelector('[data-mspice]') : null;
    var modalShareBtn = modal ? modal.querySelector('[data-modal-share-btn]') : null;
    var modalShareLabel = modal ? modal.querySelector('[data-modal-share-label]') : null;
    var modalShareIcon = modal ? modal.querySelector('.sss-lib__mshareIcon') : null;
    var modalFullLink = modal ? modal.querySelector('[data-modal-full-link]') : null;
    var seriesOrderEl = modal ? modal.querySelector('[data-mseries-order]') : null;
    var modalRestoreTarget = null;

    function lockModalScroll(){
      var y = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
      var lock = window.__sssBookModalScrollLock || {};
      if (!lock.active){
        lock = {
          active: true,
          y: y,
          htmlOverflow: document.documentElement.style.overflow || '',
          bodyOverflow: document.body.style.overflow || ''
        };
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
      } else {
        lock.y = y || lock.y || 0;
      }
      window.__sssBookModalScrollLock = lock;
      return lock.y || y || 0;
    }

    function unlockModalScroll(){
      var lock = window.__sssBookModalScrollLock || {};
      var y = Number(lock.y || 0);
      document.documentElement.style.overflow = lock.htmlOverflow || '';
      document.body.style.overflow = lock.bodyOverflow || '';
      window.__sssBookModalScrollLock = { active: false, y: y };
      return y;
    }

    function ensureModalSpiceBadge(){
      if (spiceEl || !modal) return spiceEl;
      var coverFrame = modal.querySelector('.sss-lib__mcoverFrame');
      if (!coverFrame) return null;
      spiceEl = document.createElement('div');
      spiceEl.className = 'sss-lib__floatSpice sss-lib__mspice';
      spiceEl.setAttribute('data-mspice', '');
      spiceEl.hidden = true;
      coverFrame.appendChild(spiceEl);
      return spiceEl;
    }

    function findModalSourceCard(btn){
      var handle = String(btn.dataset.handle || '').trim().toLowerCase();
      var title = String(btn.dataset.title || '').trim().toLowerCase();
      if (!handle && !title) return null;

      return Array.from(document.querySelectorAll('.sss-lib__book[data-title]')).find(function(card){
        if (card === btn) return false;
        var cardHandle = String(card.dataset.handle || '').trim().toLowerCase();
        var cardTitle = String(card.dataset.title || '').trim().toLowerCase();
        var isMatch = (handle && cardHandle && cardHandle === handle) || (title && cardTitle && cardTitle === title);
        return isMatch && (card.dataset.amazon || card.dataset.bookshop || card.dataset.ku);
      }) || null;
    }

    function modalDatasetValue(btn, sourceCard, key){
      return btn.dataset[key] || (sourceCard && sourceCard.dataset ? sourceCard.dataset[key] : '') || '';
    }

    function getModalBookData(btn){
      var sourceCard = findModalSourceCard(btn);
      return {
        handle: modalDatasetValue(btn, sourceCard, 'handle'),
        url: modalDatasetValue(btn, sourceCard, 'url'),
        title: modalDatasetValue(btn, sourceCard, 'title'),
        author: modalDatasetValue(btn, sourceCard, 'author'),
        cover: modalDatasetValue(btn, sourceCard, 'cover'),
        amazon: modalDatasetValue(btn, sourceCard, 'amazon'),
        bookshop: modalDatasetValue(btn, sourceCard, 'bookshop'),
        spice: modalDatasetValue(btn, sourceCard, 'spice'),
        tropes: modalDatasetValue(btn, sourceCard, 'tropes'),
        tropesDisplay: modalDatasetValue(btn, sourceCard, 'tropesDisplay'),
        why: modalDatasetValue(btn, sourceCard, 'why'),
        newsletter: modalDatasetValue(btn, sourceCard, 'newsletter'),
        tension: modalDatasetValue(btn, sourceCard, 'tension'),
        damage: modalDatasetValue(btn, sourceCard, 'damage'),
        darkness: modalDatasetValue(btn, sourceCard, 'darkness'),
        yearning: modalDatasetValue(btn, sourceCard, 'yearning'),
        boyfriend: modalDatasetValue(btn, sourceCard, 'boyfriend'),
        boyfriendName: modalDatasetValue(btn, sourceCard, 'boyfriendName'),
        reread: modalDatasetValue(btn, sourceCard, 'reread'),
        ku: modalDatasetValue(btn, sourceCard, 'ku'),
        mini: modalDatasetValue(btn, sourceCard, 'mini'),
        series: modalDatasetValue(btn, sourceCard, 'series'),
        seriesName: modalDatasetValue(btn, sourceCard, 'seriesName'),
        seriesNumber: modalDatasetValue(btn, sourceCard, 'seriesNumber'),
        standalone: modalDatasetValue(btn, sourceCard, 'standalone'),
        privateShelf: modalDatasetValue(btn, sourceCard, 'privateShelf') || 'false'
      };
    }

    function modalEscape(value){
      return String(value || '').replace(/[&<>"']/g, function(char){
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
      });
    }

    function modalTropeEmoji(tropeName){
      var trope = String(tropeName || '').toLowerCase();
      if (trope.indexOf('slow burn') > -1 || trope.indexOf('yearning') > -1) return '🕯️';
      if (trope.indexOf('enemies to lovers') > -1 || trope.indexOf('rivals') > -1 || trope.indexOf('banter') > -1) return '⚔️';
      if (trope.indexOf('friends to lovers') > -1 || trope.indexOf('comfort') > -1 || trope.indexOf('healing') > -1 || trope.indexOf('found family') > -1) return '🤍';
      if (trope.indexOf('forced proximity') > -1 || trope.indexOf('one bed') > -1) return '🛏️';
      if (trope.indexOf('fake dating') > -1 || trope.indexOf('marriage of convenience') > -1) return '💍';
      if (trope.indexOf('second chance') > -1 || trope.indexOf('emotional damage') > -1 || trope.indexOf('angst') > -1) return '💔';
      if (trope.indexOf('dark') > -1 || trope.indexOf('morally gray') > -1 || trope.indexOf('villain') > -1) return '🥀';
      if (trope.indexOf('obsession') > -1 || trope.indexOf('stalker') > -1 || trope.indexOf('possessive') > -1 || trope.indexOf('touch her') > -1) return '🖤';
      if (trope.indexOf('sports') > -1 || trope.indexOf('hockey') > -1) return '🏒';
      if (trope.indexOf('forbidden') > -1) return '🍒';
      if (trope.indexOf('grumpy') > -1) return '☕';
      if (trope.indexOf('small town') > -1) return '🍂';
      if (trope.indexOf('romantasy') > -1 || trope.indexOf('fantasy') > -1 || trope.indexOf('fated mates') > -1 || trope.indexOf('paranormal') > -1) return '🌙';
      if (trope.indexOf('workplace') > -1 || trope.indexOf('billionaire') > -1) return '💋';
      return '📚';
    }

	    function modalTropeLabel(tropeName){
	      var trope = String(tropeName || '').trim();
	      if (!trope) return '';
	      return /^[^a-z0-9]+ /i.test(trope) ? trope : modalTropeEmoji(trope) + ' ' + trope;
	    }

	    function modalTropeNameWithoutEmoji(tropeName){
	      var raw = String(tropeName || '').trim();
	      var lower = raw.toLowerCase();
	      var knownTropes = [
	        'touch her and die',
	        'why choose',
	        'who did this to you',
	        'mafia romance',
	        'slow burn',
	        'enemies to lovers',
	        'fated mates'
	      ];
	      for (var i = 0; i < knownTropes.length; i += 1) {
	        if (lower.indexOf(knownTropes[i]) !== -1) return knownTropes[i];
	      }
	      return raw.replace(/^[^a-z0-9]+/i, '').trim();
	    }

	    function modalTropeCustomKey(tropeName){
	      var name = modalTropeNameWithoutEmoji(tropeName);
	      var key = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
	      var haystack = (key + ' ' + name.toLowerCase()).replace(/\s+/g, ' ');
	      var aliases = [
	        ['mafia-romance', ['mafia']],
	        ['slow-burn', ['slow burn', 'slow-burn']],
	        ['enemies-to-lovers', ['enemies to lovers', 'enemies-to-lovers']],
	        ['friends-to-lovers', ['friends to lovers', 'friends-to-lovers']],
	        ['he-falls-first', ['he falls first', 'he-falls-first', 'falls first']],
	        ['billionaire-romance', ['billionaire romance', 'billionaire-romance', 'billionaire']],
	        ['stalker-romance', ['stalker romance', 'stalker-romance', 'stalker']],
	        ['dystopian-romance', ['dystopian romance', 'dystopian-romance']],
	        ['sports-romance', ['sports romance', 'sports-romance', 'sports']],
	        ['bully-romance', ['bully romance', 'bully-romance', 'bully']],
	        ['forced-proximity', ['forced proximity', 'forced-proximity']],
	        ['villain-gets-the-girl', ['villain gets the girl', 'villain-gets-the-girl', 'villain romance']],
	        ['historical-romance', ['historical romance', 'historical-romance']],
	        ['bodyguard-romance', ['bodyguard romance', 'bodyguard-romance', 'bodyguard']],
	        ['opposites-attract', ['opposites attract', 'opposites-attract']],
	        ['marriage-of-convenience', ['marriage of convenience', 'marriage-of-convenience']],
	        ['found-family', ['found family', 'found-family']],
	        ['dark-academia', ['dark academia', 'dark-academia']],
	        ['captor-x-captive', ['captor x captive', 'captor-x-captive', 'captor captive', 'captor', 'captive']],
	        ['boss-x-employee', ['boss x employee', 'boss-x-employee', 'boss employee']],
	        ['age-gap', ['age gap', 'age-gap']],
	        ['trauma-bonding', ['trauma bonding', 'trauma-bonding']],
	        ['baseball-romance', ['baseball romance', 'baseball-romance', 'baseball']],
	        ['hockey-romance', ['hockey romance', 'hockey-romance', 'hockey']],
	        ['contemporary-romance', ['contemporary romance', 'contemporary-romance']],
	        ['dark-romance', ['dark romance', 'dark-romance']],
	        ['forbidden-love', ['forbidden love', 'forbidden-love', 'forbidden romance']],
	        ['step-siblings', ['step siblings', 'step-siblings', 'stepsiblings']],
	        ['nanny', ['nanny romance', 'nanny']],
	        ['single-dad', ['single dad', 'single-dad']],
	        ['small-town', ['small town', 'small-town']],
	        ['grumpy-x-sunshine', ['grumpy x sunshine', 'grumpy-x-sunshine', 'grumpy sunshine']],
	        ['one-bed', ['one bed', 'one-bed']],
	        ['brothers-best-friend', ['brother best friend', 'brothers best friend', "brother's best friend", 'brothers-best-friend', 'brother-s-best-friend']],
	        ['second-chance', ['second chance', 'second-chance']],
	        ['fake-dating', ['fake dating', 'fake-dating']],
	        ['fated-mates', ['fated mates', 'fated-mates']],
	        ['who-did-this-to-you', ['who did this to you', 'who-did-this-to-you']],
	        ['touch-her-and-die', ['touch her and die', 'touch-her-and-die']],
	        ['why-choose', ['why choose', 'why-choose']],
	        ['paranormal-romance', ['paranormal romance', 'paranormal-romance', 'paranormal']],
	        ['romantasy', ['romantasy', 'fantasy romance']]
	      ];
	      for (var i = 0; i < aliases.length; i += 1) {
	        for (var j = 0; j < aliases[i][1].length; j += 1) {
	          if (haystack.indexOf(aliases[i][1][j]) !== -1) return aliases[i][0];
	        }
	      }
	      return '';
	    }

	    function modalTropeCustomEmojiHtml(tropeName){
	      var name = modalTropeNameWithoutEmoji(tropeName);
	      var key = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
	      var customKey = modalTropeCustomKey(tropeName);
	      if (customKey) {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + customKey + '.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name);
	      }
	      if (key === 'mafia' || key === 'mafia-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/mafia-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'mafia romance');
	      }
	      if (key === 'slow-burn') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/slow-burn.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'slow burn');
	      }
	      if (key === 'enemies-to-lovers') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/enemies-to-lovers.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'enemies to lovers');
	      }
	      if (key === 'friends-to-lovers') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/friends-to-lovers.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'friends to lovers');
	      }
	      if (key === 'he-falls-first' || key === 'falls-first') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/he-falls-first.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'he falls first');
	      }
	      if (key === 'billionaire-romance' || key === 'billionaire') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/billionaire-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'billionaire romance');
	      }
	      if (key === 'stalker-romance' || key === 'stalker') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/stalker-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'stalker romance');
	      }
	      if (key === 'dystopian-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/dystopian-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'dystopian romance');
	      }
	      if (key === 'sports-romance' || key === 'sports') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/sports-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'sports romance');
	      }
	      if (key === 'bully-romance' || key === 'bully') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/bully-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'bully romance');
	      }
	      if (key === 'forced-proximity') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/forced-proximity.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'forced proximity');
	      }
	      if (key === 'villain-gets-the-girl' || key === 'villain-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/villain-gets-the-girl.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'villain gets the girl');
	      }
	      if (key === 'historical-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/historical-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'historical romance');
	      }
	      if (key === 'bodyguard-romance' || key === 'bodyguard') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/bodyguard-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'bodyguard romance');
	      }
	      if (key === 'opposites-attract') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/opposites-attract.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'opposites attract');
	      }
	      if (key === 'marriage-of-convenience') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/marriage-of-convenience.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'marriage of convenience');
	      }
	      if (key === 'found-family') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/found-family.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'found family');
	      }
	      if (key === 'dark-academia' || key === 'dark-academia-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/dark-academia.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'dark academia');
	      }
	      if (key === 'captor-x-captive' || key === 'captor-captive-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/captor-x-captive.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'captor x captive');
	      }
	      if (key === 'boss-x-employee' || key === 'boss-employee') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/boss-x-employee.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'boss x employee');
	      }
	      if (key === 'age-gap') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/age-gap.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'age gap');
	      }
	      if (key === 'trauma-bonding') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/trauma-bonding.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'trauma bonding');
	      }
	      if (key === 'baseball-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/baseball-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'baseball romance');
	      }
	      if (key === 'hockey-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/hockey-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'hockey romance');
	      }
	      if (key === 'contemporary-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/contemporary-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'contemporary romance');
	      }
	      if (key === 'dark-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/dark-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'dark romance');
	      }
	      if (key === 'forbidden-love' || key === 'forbidden-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/forbidden-love.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'forbidden love');
	      }
	      if (key === 'step-siblings' || key === 'stepsiblings') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/step-siblings.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'step siblings');
	      }
	      if (key === 'nanny' || key === 'nanny-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/nanny.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'nanny');
	      }
	      if (key === 'single-dad' || key === 'single-dad-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/single-dad.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'single dad');
	      }
	      if (key === 'small-town' || key === 'small-town-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/small-town.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'small town');
	      }
	      if (key === 'grumpy-x-sunshine' || key === 'grumpy-sunshine') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/grumpy-x-sunshine.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape('grumpy x sunshine');
	      }
	      if (key === 'one-bed') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/one-bed.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'one bed');
	      }
	      if (key === 'brothers-best-friend' || key === 'brother-s-best-friend') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/brothers-best-friend.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || "brother's best friend");
	      }
	      if (key === 'second-chance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/second-chance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'second chance');
	      }
	      if (key === 'fake-dating' || key === 'fake-dating-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/fake-dating.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'fake dating');
	      }
	      if (key === 'fated-mates') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/fated-mates.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'fated mates');
	      }
	      if (key === 'who-did-this-to-you') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/who-did-this-to-you.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'who did this to you');
	      }
	      if (key === 'touch-her-and-die') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/touch-her-and-die.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'touch her and die');
	      }
	      if (key === 'why-choose') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/why-choose.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'why choose');
	      }
	      if (key === 'paranormal' || key === 'paranormal-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/paranormal-romance.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'paranormal romance');
	      }
	      if (key === 'romantasy' || key === 'fantasy-romance') {
	        return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/romantasy.png" alt="" aria-hidden="true" loading="lazy" decoding="async"> ' + modalEscape(name || 'romantasy');
	      }

	      return '';
	    }

	    function modalTropesHtml(value){
	      var tropeValue = Array.isArray(value) ? stringifyBookDatasetValue(value) : value;
	      var tropes = String(tropeValue || '').split(',').map(function(trope){
	        return String(trope || '').trim();
	      }).filter(Boolean);

	      if (!tropes.length) return '';

	      return '<span>tropes: </span>' + tropes.map(function(trope){
	        return '<em>' + (modalTropeCustomEmojiHtml(trope) || modalEscape(modalTropeLabel(trope))) + '</em>';
	      }).join('<span>, </span>');
	    }

    function openModal(data){

      if (!modal) return;
      data = sanitizeBookDataForLibraryType(data, libraryType);

      trackSiteEvent("book_modal_opened", {
        bookHandle: data.handle || '',
        bookTitle: data.title || '',
        seriesHandle: data.series || '',
        uiLocation: document.body.dataset.template || "library",
        metadata: {
          author: data.author || '',
          tropes: data.tropes || '',
          spice: data.spice || ''
        }
      });

      if (titleEl) titleEl.textContent = data.title || '';
      if (authorEl) authorEl.textContent = data.author ? ('by ' + data.author) : '';
      if (modalFullLink){
        var fullLinkUrl = data.url || (data.handle ? '/books/' + encodeURIComponent(data.handle) + '/' : '');
        if (fullLinkUrl){
          modalFullLink.href = fullLinkUrl;
          modalFullLink.hidden = false;
        } else {
          modalFullLink.hidden = true;
          modalFullLink.removeAttribute('href');
        }
      }
      renderModalBookStatus(modal, data);

      var modalNoteToggles = modal.querySelectorAll('[data-modal-note-toggle]');
      modalNoteToggles.forEach(function(modalNoteToggle){
        [
          'handle',
          'url',
          'title',
          'author',
          'cover',
          'amazon',
          'bookshop',
          'spice',
          'tropes',
          'mini',
          'series',
          'seriesName',
          'seriesNumber',
          'ku'
        ].forEach(function(key){
          if (data[key] !== undefined && data[key] !== null && String(data[key]).trim() !== ''){
            modalNoteToggle.dataset[key] = String(data[key]);
          } else {
            delete modalNoteToggle.dataset[key];
          }
        });
        modalNoteToggle.setAttribute('aria-label', 'add your private note for ' + (data.title || 'this book'));
      });

      if (window.bbbReaderNotesRefresh) window.bbbReaderNotesRefresh();

var modalHeart = modal.querySelector('[data-modal-heart]');

if(modalHeart){

  var saved = getShelf().find(function(b){
    return b.title === data.title;
  });

  applyHeartSavedState(modalHeart, !!saved);

      modalHeart.onclick = function(e){
    e.stopPropagation();

        var fakeBtn = {
          dataset:{
            handle:data.handle,
            url:data.url,
            title:data.title,
            author:data.author,
            cover:data.cover,
            amazon:data.amazon,
            bookshop:data.bookshop,
            spice:data.spice,
            darkness:data.darkness,
            tropes:data.tropes,
            tropesDisplay:data.tropesDisplay,
            why:data.why,
            newsletter:data.newsletter,
            tension:data.tension,
            damage:data.damage,
            yearning:data.yearning,
            boyfriend:data.boyfriend,
            boyfriendName:data.boyfriendName,
            reread:data.reread,
            ku:data.ku,
            mini:data.mini,
            series:data.series,
            seriesName:data.seriesName,
            seriesNumber:data.seriesNumber,
            standalone:data.standalone,
            privateShelf:data.privateShelf
          }
        };

    toggleSave(modalHeart, fakeBtn);
  };

}

      if (modalShareBtn){
        var shareBookKey = data.handle || data.title || '';
        var shareBasePath = '/library/';
        var shareUrl = window.location.origin + shareBasePath + '?book=' + encodeURIComponent(shareBookKey);
        var shareTitle = data.title || document.title;
        var shareText = data.author
          ? 'next book club read: ' + data.title + ' by ' + data.author
          : 'share this book with your book bestie';

        if (modalShareLabel){
          modalShareLabel.textContent = 'share';
        }
        if (modalShareIcon){
          modalShareIcon.textContent = '📲';
        }

        modalShareBtn.onclick = function(e){
          e.preventDefault();
          e.stopPropagation();

          trackSiteEvent("book_shared", {
            bookHandle: data.handle || '',
            bookTitle: data.title || '',
            seriesHandle: data.series || '',
            uiLocation: "book_modal",
            metadata: {
              author: data.author || '',
              method: navigator.share ? "native_share" : "clipboard"
            }
          });

          if (navigator.share){
            navigator.share({
              title: shareTitle,
              text: shareText,
              url: shareUrl
            }).catch(function(){});
            return;
          }

          if (navigator.clipboard && navigator.clipboard.writeText){
            navigator.clipboard.writeText(shareUrl).then(function(){
              if (modalShareIcon){
                modalShareIcon.textContent = '✓';
              }
              if (modalShareLabel) modalShareLabel.textContent = 'copied';
              window.setTimeout(function(){
                if (modalShareIcon){
                  modalShareIcon.textContent = '📲';
                }
                if (modalShareLabel){
                  modalShareLabel.textContent = 'share';
                }
              }, 1600);
            }).catch(function(){});
          }
        };
      }

      if (coverEl){
        if (data.cover){
          coverEl.src = data.cover;
          coverEl.style.display = '';
        } else {
          coverEl.removeAttribute('src');
          coverEl.style.display = 'none';
        }
        coverEl.alt = bookCoverAlt(data.title, data.author, data.shelf);
      }

      var modalSpiceEl = ensureModalSpiceBadge();
      if (modalSpiceEl){
        var spiceCount = parseInt(data.spice, 10) || 0;
        modalSpiceEl.textContent = spiceCount > 0 ? Array(spiceCount + 1).join('🌶') : '';
        modalSpiceEl.hidden = spiceCount <= 0;
      }

      if (kuEl){
        var kuState = String(data.ku || '').toLowerCase().trim() === 'true';
        kuEl.className = 'sss-lib__mku ' + (kuState ? 'is-yes' : 'is-no');
        kuEl.style.display = kuState ? '' : 'none';
        kuEl.textContent = kuState ? 'included in your kindle unlimited subscription — no extra cost' : 'not currently included in kindle unlimited';
      }

	      if (darknessEl) darknessEl.textContent = '';

var seriesEl = modal.querySelector('[data-mseries]');

if (seriesEl){

  if (data.seriesName || data.seriesNumber){

var slug = (data.series || '').toLowerCase().trim();
    var url = slug ? "/series/" + encodeURIComponent(slug) + "/" : "#";

    var name = data.seriesName ? bbbSeriesDisplayLabel(data.seriesName) + " →" : "";

    if (name){
      if (seriesEl.tagName && seriesEl.tagName.toLowerCase() === 'a') {
        seriesEl.href = url;
        seriesEl.textContent = name;
      } else {
        seriesEl.innerHTML =
          "<a href='" + url + "' target='_blank' rel='noopener' class='sss-lib__seriesLink'>" + name + "</a>";
      }

      seriesEl.style.display = '';
      if (seriesEl.hasAttribute('hidden')) {
        seriesEl.removeAttribute('hidden');
      }
    } else {
      seriesEl.style.display = 'none';
      if (seriesEl.tagName && seriesEl.tagName.toLowerCase() === 'a') {
        seriesEl.removeAttribute('href');
        seriesEl.textContent = '';
      }
      seriesEl.setAttribute('hidden', '');
    }

  } else {

    seriesEl.style.display = 'none';
    if (seriesEl.tagName && seriesEl.tagName.toLowerCase() === 'a') {
      seriesEl.removeAttribute('href');
      seriesEl.textContent = '';
    }
    seriesEl.setAttribute('hidden', '');

  }

}

if (seriesOrderEl){
  if (data.seriesNumber){
    seriesOrderEl.textContent = "book " + data.seriesNumber;
  } else {
    seriesOrderEl.textContent = '';
  }
}


      if (tropesEl){
        tropesEl.innerHTML = modalTropesHtml(data.tropesDisplay || data.tropes);
      }

      if (standaloneEl){
        if (String(data.standalone || '').toLowerCase().trim() === 'true'){
          standaloneEl.textContent = '✓ can be read as a standalone';
        } else if (data.seriesName || data.seriesNumber){
          standaloneEl.textContent = '⚠ highly recommend starting the series from book 1';
        } else {
          standaloneEl.textContent = '';
        }
      }

      if (whyEl){
        whyEl.textContent = data.why || '';
      }

      var modalKuState = String(data.ku || '').toLowerCase().trim() === 'true';
      if (kuBtn){
        kuBtn.style.display = data.amazon && modalKuState ? '' : 'none';
        if (data.amazon) kuBtn.href = data.amazon;
        kuBtn.onclick = data.amazon && modalKuState ? function(){
          trackSiteEvent("book_link_clicked", {
            bookHandle: data.handle || '',
            bookTitle: data.title || '',
            seriesHandle: data.series || '',
            uiLocation: "book_modal",
            metadata: {
              destination: "kindle_unlimited"
            }
          });
        } : null;
      }

      if (amazonBtn){
        amazonBtn.style.display = data.amazon ? '' : 'none';
        if (data.amazon) amazonBtn.href = data.amazon;
        amazonBtn.innerHTML = modalKuState ? 'buy on amazon <span>· own it forever</span>' : 'buy on amazon';
        amazonBtn.classList.remove('sss-lib__mbtn--primary');
        amazonBtn.onclick = data.amazon ? function(){
          trackSiteEvent("book_link_clicked", {
            bookHandle: data.handle || '',
            bookTitle: data.title || '',
            seriesHandle: data.series || '',
            uiLocation: "book_modal",
            metadata: {
              destination: "amazon"
            }
          });
        } : null;
      }

      if (bookshopBtn){
        bookshopBtn.style.display = data.bookshop ? '' : 'none';
        if (data.bookshop) bookshopBtn.href = data.bookshop;
        bookshopBtn.innerHTML = 'prefer indie? bookshop.org →';
        bookshopBtn.onclick = data.bookshop ? function(){
          trackSiteEvent("book_link_clicked", {
            bookHandle: data.handle || '',
            bookTitle: data.title || '',
            seriesHandle: data.series || '',
            uiLocation: "book_modal",
            metadata: {
              destination: "bookshop"
            }
          });
        } : null;
      }

      if (miniEl){
  miniEl.textContent = data.mini
    ? ("quick summary: " + data.mini)
    : '';
}
     if (libraryType === "society") {

  if (tensionEl){
    tensionEl.textContent = data.tension
      ? "🔥 tension: " + data.tension + "/5"
      : '';
  }

  if (damageEl){
    damageEl.textContent = data.damage
      ? "💔 emotional damage: " + data.damage + "/5"
      : '';
  }

  if (darknessEl){
    darknessEl.textContent = data.darkness
      ? "💀 darkness: " + data.darkness + "/5"
      : '';
  }

if (yearningEl){

  if (!data.yearning){
    yearningEl.innerHTML = '';
  } else {

    const level = parseInt(data.yearning);

    const labels = {
      1: "soft",
      2: "intrigued",
      3: "feral"
    };

    const label = labels[level] || '';

    yearningEl.innerHTML =
      'yearning <span class="sss-lib__yearningLabel sss-lib__yearningLabel--' +
      level +
      '">' +
      label +
      '</span>';

  }

}


  if (rereadEl){
    rereadEl.textContent = data.reread ? "reread worthy" : '';
  }

  if (boyfriendEl){
    boyfriendEl.textContent = data.boyfriend
      ? "book boyfriend: " + (data.boyfriendName ? data.boyfriendName + " · " : "") + data.boyfriend
      : '';
  }

} else {
  if (tensionEl) tensionEl.textContent = '';
  if (damageEl) damageEl.textContent = '';
  if (yearningEl) yearningEl.innerHTML = '';
  if (boyfriendEl) boyfriendEl.textContent = '';
  if (darknessEl) darknessEl.textContent = '';
  if (rereadEl) rereadEl.textContent = '';
}

      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('bbb-book-modal-open');
      lockModalScroll();
    }

    window.sssOpenBookModal = function(bookData, restoreTarget){
      if (!modal || !bookData) return false;
      modalRestoreTarget = restoreTarget || null;
      openModal(bookData);
      return true;
    };

    function closeModal(){
      if (!modal || modal.hidden) return;
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('bbb-book-modal-open');
      if (document.activeElement && typeof document.activeElement.blur === 'function'){
        document.activeElement.blur();
      }
      unlockModalScroll();
    }

    if (!window.__sssBookModalCloseCaptureBound){
      window.__sssBookModalCloseCaptureBound = true;
      document.addEventListener('click', function(e){
        var closeTrigger = closestFromTarget(e.target, '[data-close]');
        var openBookModal = document.querySelector('.sss-lib__modal:not([hidden])');
        if (!closeTrigger || !openBookModal || !openBookModal.contains(closeTrigger)) return;
        e.preventDefault();
        e.stopPropagation();
        closeModal();
      }, true);
    }
/* ======================
   TOAST → SCROLL TO SHELF
====================== */

const toastShelfLink = document.getElementById("sssToastShelfLink");

if (toastShelfLink){
  toastShelfLink.setAttribute("href", "/library/?shelf=open");

  toastShelfLink.addEventListener("click", function(e){

    e.preventDefault();

    const shelf = document.getElementById("sssMyShelfSection");

    if (!shelf){
      window.location.href = "/library/?shelf=open";
      return;
    }

    /* ensure shelf is visible */
    shelf.hidden = false;

    /* scroll smoothly */
    shelf.scrollIntoView({
      behavior: "smooth",
      block: "start"
    });

  });

}
    /* ======================
   NATIVE SHARE
====================== */

var shareBtn = document.getElementById("sssShareLibrary");

if (shareBtn){

  shareBtn.addEventListener("click", function(){

    trackSiteEvent("library_shared", {
      uiLocation: "library_page",
      metadata: {
        method: navigator.share ? "native_share" : "clipboard"
      }
    });

    if (navigator.share){

      navigator.share({
        title: "The Smut & Sentiment Society Library",
        text: "you need to see this romance library 👀",
        url: window.location.href
      });

    } else {

      // fallback for desktop
      navigator.clipboard.writeText(window.location.href);

      shareBtn.textContent = "link copied ✨";

      setTimeout(function(){
        shareBtn.textContent = "share this library";
      },2000);

    }

  });

}

    /* ======================
       BOOK CLICK (MODAL)
    ====================== */


root.addEventListener('click', function(e){

  const btn = closestFromTarget(e.target, '[data-title]');
  if(!btn) return;

  if (closestFromTarget(e.target, '[data-heart]')) return;
  if (closestFromTarget(e.target, '[data-reader-note-toggle]')) return;
  if (closestFromTarget(e.target, '[data-book-page-link]')) return;
  if (closestFromTarget(e.target, '.sss-lib__seriesBadge')) return;
  if (window.getSelection && String(window.getSelection()).trim()) return;

  e.preventDefault();
  e.stopPropagation();

  if (btn.hasAttribute('disabled')) return;

  modalRestoreTarget = btn;
  openModal(getModalBookData(btn));

});

    root.querySelectorAll('.sss-lib__book[data-title]').forEach(function(btn){
      if (btn.__sssModalBound) return;
      btn.__sssModalBound = true;

      function handleOpen(e){
        if (closestFromTarget(e.target, '[data-heart]')) return;
        if (closestFromTarget(e.target, '[data-reader-note-toggle]')) return;
        if (closestFromTarget(e.target, '[data-book-page-link]')) return;
        if (closestFromTarget(e.target, '.sss-lib__seriesBadge')) return;
        if (btn.hasAttribute('disabled')) return;
        if (window.getSelection && String(window.getSelection()).trim()) return;

        e.preventDefault();
        e.stopPropagation();
        modalRestoreTarget = btn;
        openModal(getModalBookData(btn));
      }

      btn.addEventListener('click', function(e){
        handleOpen(e);
      });

      btn.addEventListener('pointerup', function(e){
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        handleOpen(e);
      });

      btn.addEventListener('keydown', function(e){
        if (e.key !== 'Enter' && e.key !== ' ') return;
        handleOpen(e);
      });
    });

    if (!window.__sssOpenedSharedBook){
      var requestedBook = decodeURIComponent((new URLSearchParams(window.location.search)).get("book") || "").toLowerCase().trim();
      if (requestedBook){
        var requestedBtn = Array.from(root.querySelectorAll('.sss-lib__book[data-title]')).find(function(btn){
          return (
            (btn.dataset.handle && btn.dataset.handle.toLowerCase() === requestedBook) ||
            (btn.dataset.title && btn.dataset.title.toLowerCase() === requestedBook) ||
            (btn.dataset.title && btn.dataset.title.toLowerCase().includes(requestedBook))
          );
        });

        if (requestedBtn){
          window.__sssOpenedSharedBook = true;
          openModal(getModalBookData(requestedBtn));
          if (window.history && window.history.replaceState){
            window.history.replaceState({}, "", window.location.pathname + window.location.hash);
          }
        }
      }
    }

    /* ======================
       HEART BINDING
    ====================== */

    root.querySelectorAll('[data-heart]').forEach(function(heart){

      var bookBtn = heart.closest('.sss-lib__book');
      if (!bookBtn) return;

      var saved = getShelf().find(function(b){
        return b.title === bookBtn.dataset.title;
      });

      if (saved){
        applyHeartSavedState(heart, true);
      } else {
        applyHeartSavedState(heart, false);
      }

      heart.addEventListener('click', function(e){
        e.stopPropagation();
        toggleSave(heart, bookBtn);
      });

    });

    syncAllLibraryHearts();

    root.querySelectorAll('.sss-lib__seriesBadge[data-series-url]').forEach(function(badge){
      if (badge.__sssSeriesBound) return;
      badge.__sssSeriesBound = true;

      function openSeriesPage(e){
        e.preventDefault();
        e.stopPropagation();

        var url = badge.getAttribute('data-series-url');
        if (!url) return;
        window.location.href = url;
      }

      badge.addEventListener('click', openSeriesPage);
      badge.addEventListener('keydown', function(e){
        if (e.key === 'Enter' || e.key === ' '){
          openSeriesPage(e);
        }
      });
    });

document.querySelectorAll('[data-close]').forEach(function(el){
  el.addEventListener('click', closeModal);
});

    document.addEventListener('keydown', function(e){
      if (e.key === 'Escape') closeModal();
    });

  });

  syncBookStatusUI();
}

/* ======================
   TRENDING BOOKS
====================== */

async function loadTrending(){

  try {

    const row = document.getElementById("sssTrendingRow");
    if(!row) return;

    const initialFallbackCards = Array.from(row.querySelectorAll('.sss-lib__book'));

    function trendingKey(value){
      return (value || "").trim().toLowerCase();
    }

function normalizeRecentTrending(data){
  return (data || [])
  .map(function(item){
    var key = trendingKey(item.book_key) || trendingKey(item.book_title);
    var title = trendingKey(item.book_title);
    var saves = Number(item.saves_last_7_days || 0);

    return {
      key: key,
      title: title,
      saves: saves
    };
  })
  .filter(function(item){
    return (!!item.key || !!item.title) && item.saves > 0;
  });
}

function normalizeAggregateTrending(data, countField){
  return (data || [])
  .map(function(item){
    var key = trendingKey(item.book_key) || trendingKey(item.book_handle) || trendingKey(item.book_title);
    var title = trendingKey(item.book_title) || trendingKey(item.book_label);
    var saves = Number(item[countField] || 0);

    return {
      key: key,
      title: title,
      saves: saves
    };
  })
  .filter(function(item){
    return (!!item.key || !!item.title) && item.saves > 0;
  });
}

function normalizeAllTimeTrending(data){
  var counts = {};

  (data || []).forEach(function(item){
    var key = trendingKey(item.book_handle) || trendingKey(item.book_title);
    var title = trendingKey(item.book_title);
    var identity = key || title;

    if(!identity) return;

    if(!counts[identity]){
      counts[identity] = {
        key: key,
        title: title,
        saves: 0
      };
    }

    counts[identity].saves += 1;
  });

  return Object.keys(counts)
    .map(function(identity){
      return counts[identity];
    })
    .sort(function(a, b){
      if(b.saves !== a.saves) return b.saves - a.saves;
      return (a.title || a.key).localeCompare(b.title || b.key);
    });
}

const recentResponse = await supabaseClient
  .from('book_saves_recent_rollup')
  .select('book_key,book_title,saves_last_7_days,saves_last_30_days,last_saved_at')
  .gt('saves_last_30_days', 0)
  .order('saves_last_7_days', { ascending: false })
  .order('saves_last_30_days', { ascending: false })
  .order('last_saved_at', { ascending: false })
  .limit(20);

    if(recentResponse.error){
      console.log(recentResponse.error);
    }

let sorted = normalizeRecentTrending(recentResponse.data);

if(sorted.length < 5){
  const allTimeRollupResponse = await supabaseClient
    .from('book_saves_all_time_rollup')
    .select('book_key,book_title,total_saves,last_saved_at')
    .order('total_saves', { ascending: false })
    .order('last_saved_at', { ascending: false })
    .limit(20);

  if(!allTimeRollupResponse.error){
    sorted = normalizeAggregateTrending(allTimeRollupResponse.data, 'total_saves');
  } else {
    console.log(allTimeRollupResponse.error);
    const allTimeRawResponse = await supabaseClient
      .from('book_saves')
      .select('book_title,created_at')
      .order('created_at', { ascending: false })
      .limit(5000);

    if(!allTimeRawResponse.error){
      sorted = normalizeAllTimeTrending(allTimeRawResponse.data);
    } else {
      console.log(allTimeRawResponse.error);
      sorted = normalizeAggregateTrending(recentResponse.data, 'saves_last_30_days');
    }
  }

}

if(sorted.length < 5 && recentResponse.data){
  var recentThirtyDaySorted = normalizeAggregateTrending(recentResponse.data, 'saves_last_30_days');
  var usedRecent = {};
  sorted.forEach(function(item){
    usedRecent[item.key || item.title] = true;
  });
  recentThirtyDaySorted.forEach(function(item){
    if(sorted.length >= 5) return;
    var identity = item.key || item.title;
    if(!usedRecent[identity]){
      usedRecent[identity] = true;
      sorted.push(item);
    }
  });
}

    /* build a map of all books on page */
const bookMap = {};
const fallbackCards = [];

document.querySelectorAll('.sss-lib__book').forEach(card => {

  if (card.closest('#sssTrendingRow')) return;

  const title = trendingKey(card.dataset.title);
  const handle = trendingKey(card.dataset.handle);

  if (title && !bookMap[title]) {
    bookMap[title] = card;
  }

  if (handle && !bookMap[handle]) {
    bookMap[handle] = card;
  }

  fallbackCards.push(card);

    });

initialFallbackCards.forEach(function(card){
  fallbackCards.push(card);
});

const selectedCards = [];
const selectedIds = {};

function addTrendingCard(card){
  if(!card) return;

  const identity = trendingKey(card.dataset.handle) || trendingKey(card.dataset.title);
  if(!identity || selectedIds[identity]) return;

  selectedIds[identity] = true;
  selectedCards.push(card);
}

sorted.forEach(function(item){
  if(selectedCards.length >= 5) return;
  addTrendingCard(bookMap[item.key] || bookMap[item.title]);
});

fallbackCards.forEach(function(card){
  if(selectedCards.length >= 5) return;
  addTrendingCard(card);
});

if(!selectedCards.length){
  const shelf = document.getElementById("sssTrendingShelf");
  if(shelf) shelf.style.display = "none";
  return;
}

    /* render trending */
row.innerHTML = "";
selectedCards.forEach((card, index)=>{

const clone = card.cloneNode(true);
/* add spice badge */


/* normalize card type */
clone.classList.remove('sss-lib__topshelfItem');
clone.classList.add('sss-lib__book');
clone.classList.add('sss-lib__book--mini');

/* convert topshelf structure → normal card */
const coverWrap = clone.querySelector('.sss-lib__topshelfCoverWrap');
if(coverWrap){
  coverWrap.classList.remove('sss-lib__topshelfCoverWrap');
  coverWrap.classList.add('sss-lib__coverWrap');
}

const cover = clone.querySelector('.sss-lib__topshelfCover');
if(cover){
  cover.classList.remove('sss-lib__topshelfCover');
  cover.classList.add('sss-lib__cover');
}

const under = clone.querySelector('.sss-lib__topshelfUnder');
if(under){
  under.classList.remove('sss-lib__topshelfUnder');
  under.classList.add('sss-lib__under');
}
clone.classList.add("sss-lib__book--mini");

/* ensure title + author block exists */
if(!clone.querySelector('.sss-lib__under')){

  const under = document.createElement("div");
  under.className = "sss-lib__under";

  const name = document.createElement("div");
  name.className = "sss-lib__name";
  name.style.setProperty("text-transform", "none", "important");
  name.textContent = card.dataset.title || "";

  const author = document.createElement("div");
  author.className = "sss-lib__author";
  author.style.setProperty("text-transform", "none", "important");
  author.textContent = card.dataset.author || "";

  under.appendChild(name);
  if(card.dataset.author) under.appendChild(author);

  clone.appendChild(under);

}

row.appendChild(clone);

if(index === 0){

  const badge = document.createElement("div");
  badge.className = "sss-lib__trendingBadge";
  badge.textContent = "#1 trending";

  const under = clone.querySelector('.sss-lib__under');

  if(under){
    under.appendChild(badge);
  }

}

/* enable heart save toggle */
const heart = clone.querySelector('[data-heart]');
if(heart){

  var cloneSaved = getShelf().find(function(b){
    return b.title === clone.dataset.title;
  });

  applyHeartSavedState(heart, !!cloneSaved);

  heart.addEventListener("click", function(e){
    e.stopPropagation();
    toggleSave(heart, clone);
    window.setTimeout(function(){
      syncAllLibraryHearts();
    }, 0);
  });

}

/* bind modal click */
clone.addEventListener('click', function(e){

  if(closestFromTarget(e.target, '[data-heart]')) return;
  if(closestFromTarget(e.target, '[data-reader-note-toggle]')) return;

  card.click();

});

    });

syncBookStatusUI();

    window.requestAnimationFrame(function(){
      row.scrollLeft = 0;
      window.setTimeout(function(){
        row.scrollLeft = 0;
      }, 80);
      window.setTimeout(function(){
        row.scrollLeft = 0;
      }, 240);
    });

  } catch(err){
    console.log("Trending load failed", err);
  }

}

/* ======================
   INIT RUN
====================== */

document.addEventListener("DOMContentLoaded", function(){

	  init();
	  bindStandaloneBookPageSaveControls();
	  bindBookPageRatingControls();
	  initMadeForYou();
	  initArchiveFilters();
	  syncBookStatusUI();
  loadTrending();
  openSharedBookFromUrl(0);

  const books = document.querySelectorAll(".bbb-trending__book");
  const observer = new IntersectionObserver((entries)=>{
    entries.forEach((entry)=>{
      if(entry.isIntersecting){
        const index = [...books].indexOf(entry.target);
        setTimeout(()=> {
          entry.target.classList.add("is-visible");
        }, index * 120);
      }
    });
  }, { threshold: 0.25 });

  books.forEach((book)=> observer.observe(book));

});

document.addEventListener('shopify:section:load', function(){
  init();
  initMadeForYou();
  initArchiveFilters();
  syncBookStatusUI();
  loadTrending();
  openSharedBookFromUrl(0);
});

document.addEventListener('sss:bookshelf-updated', function(){
  syncAllLibraryHearts();
});

/* ======================
   RANKING SYSTEM
====================== */

var rankInputs = document.querySelectorAll('[data-rank]');
var archiveTropeSelect = document.getElementById('sssArchiveTropeFilter');
var searchInput = document.getElementById('sssSearchInput');

function bindArchiveSliderTouchLock(){
  if (!rankInputs.length) return;

  var originalOverflow = '';
  var originalOverscroll = '';
  var originalUserSelect = '';
  var lockDepth = 0;

  function lockPageScroll(){
    lockDepth += 1;
    if (lockDepth > 1) return;

    originalOverflow = document.documentElement.style.overflow;
    originalOverscroll = document.documentElement.style.overscrollBehavior;
    originalUserSelect = document.body.style.userSelect;
    document.documentElement.style.overflow = 'hidden';
    document.documentElement.style.overscrollBehavior = 'none';
    document.body.style.userSelect = 'none';
  }

  function unlockPageScroll(){
    if (lockDepth === 0) return;
    lockDepth -= 1;
    if (lockDepth > 0) return;

    document.documentElement.style.overflow = originalOverflow;
    document.documentElement.style.overscrollBehavior = originalOverscroll;
    document.body.style.userSelect = originalUserSelect;
  }

  rankInputs.forEach(function(input){
    if (input.__sssTouchLockBound) return;
    input.__sssTouchLockBound = true;
    input.addEventListener('pointerdown', lockPageScroll);
    input.addEventListener('pointerup', unlockPageScroll);
    input.addEventListener('pointercancel', unlockPageScroll);
    input.addEventListener('mousedown', lockPageScroll);
    input.addEventListener('mouseup', unlockPageScroll);
    input.addEventListener('blur', unlockPageScroll);
    input.addEventListener('mouseleave', function(){
      if ((input.matches(':active') || document.activeElement === input) && window.matchMedia('(pointer:fine)').matches) return;
      unlockPageScroll();
    });
    input.addEventListener('touchstart', lockPageScroll, { passive:true });
    input.addEventListener('touchend', unlockPageScroll, { passive:true });
    input.addEventListener('touchcancel', unlockPageScroll, { passive:true });
    input.addEventListener('change', unlockPageScroll);
  });

  document.addEventListener('mouseup', unlockPageScroll);
  document.addEventListener('pointerup', unlockPageScroll);
}

bindArchiveSliderTouchLock();

function getArchiveBooks(){
  var archiveSection = document.querySelector('[data-archive-section]');
  return archiveSection
    ? archiveSection.querySelectorAll('.sss-lib__grid .sss-lib__book')
    : document.querySelectorAll('.sss-lib__grid .sss-lib__book');
}
var hasInteracted = false;

function getArchiveSection(){
  return document.querySelector('[data-archive-section]');
}

function restoreArchiveViewport(anchorEl, anchorTop){
  window.requestAnimationFrame(function(){
    window.requestAnimationFrame(function(){
      if (!anchorEl) return;

      var nextAnchorTop = anchorEl.getBoundingClientRect().top;
      var delta = nextAnchorTop - anchorTop;
      if (Math.abs(delta) <= 1) return;

      var doc = document.documentElement;
      var currentScrollTop = window.scrollY || window.pageYOffset || 0;
      var targetScrollTop = currentScrollTop + delta;
      var maxScrollTop = Math.max(0, doc.scrollHeight - window.innerHeight);

      window.scrollTo(0, Math.max(0, Math.min(targetScrollTop, maxScrollTop)));
    });
  });
}

function updateArchiveViewportSnapshot(){
  var anchorEl = getArchiveSection();
  if (!anchorEl){
    anchorEl = document.querySelector('.sss-lib__ranker') ||
      document.querySelector('.sss-lib__searchWrap--obsession') ||
      document.getElementById('archive');
  }

  return {
    anchorEl: anchorEl,
    anchorTop: anchorEl ? anchorEl.getBoundingClientRect().top : 0
  };
}

function withStableArchiveViewport(updateFn){
  var snapshot = updateArchiveViewportSnapshot();

  updateFn();

  restoreArchiveViewport(snapshot.anchorEl, snapshot.anchorTop);
}

function restoreArchiveViewportLegacy(scrollTop){
  window.requestAnimationFrame(function(){
    var doc = document.documentElement;
    var maxScrollTop = Math.max(0, doc.scrollHeight - window.innerHeight);
    var currentScrollTop = window.scrollY || window.pageYOffset || 0;
    var targetScrollTop = Math.min(scrollTop, maxScrollTop);

    if (Math.abs(currentScrollTop - targetScrollTop) > 1){
      window.scrollTo(0, targetScrollTop);
    }
  });
}

function emojiRepeat(emoji, count){
  return count === 0 ? "any" : emoji.repeat(count);
}

function updateRanking(){
  var spiceEl = document.querySelector('[data-rank="spice"]');
  var darknessEl = document.querySelector('[data-rank="darkness"]');
  var tensionEl = document.querySelector('[data-rank="tension"]');
  var damageEl = document.querySelector('[data-rank="damage"]');
var yearningActive = document.querySelector('[data-yearning].active');
var kuActive = document.querySelector('[data-ku-filter].active');
var kuFilter = kuActive ? kuActive.getAttribute('data-ku-filter') : null;
var tropeFilter = archiveTropeSelect ? archiveTropeSelect.value.toLowerCase().trim() : '';
var searchQuery = searchInput ? searchInput.value.toLowerCase().trim() : '';


if (!spiceEl || !tensionEl || !damageEl) return;

var yearningLevel = yearningActive
  ? parseInt(yearningActive.getAttribute('data-yearning')) || 0
  : 0;

  var spiceLevel = parseInt(spiceEl.value) || 0;
  var tensionLevel = parseInt(tensionEl.value) || 0;
  var damageLevel = parseInt(damageEl.value) || 0;
  var darknessLevel = parseInt(darknessEl?.value) || 0;

  // Restore emoji display
  var spiceValue = document.querySelector('[data-rank-value="spice"]');
  var tensionValue = document.querySelector('[data-rank-value="tension"]');
  var damageValue = document.querySelector('[data-rank-value="damage"]');
var darknessValue = document.querySelector('[data-rank-value="darkness"]');
if (darknessValue) darknessValue.textContent = emojiRepeat("💀", darknessLevel);
  if (spiceValue) spiceValue.textContent = emojiRepeat("🌶", spiceLevel);
  if (tensionValue) tensionValue.textContent = emojiRepeat("🔥", tensionLevel);
  if (damageValue) damageValue.textContent = emojiRepeat("💔", damageLevel);

  withStableArchiveViewport(function(){
getArchiveBooks().forEach(function(book){

    if (!hasInteracted){
      book.style.display = '';
      return;
    }

    var bookSpice = parseInt(book.dataset.spice) || 0;
    var bookTension = parseInt(book.dataset.tension) || 0;
    var bookDamage = parseInt(book.dataset.damage) || 0;
    var bookYearning = parseInt(book.dataset.yearning) || 0;
var bookDarkness = parseInt(book.dataset.darkness || 0);
var matches =
  bookSpice >= spiceLevel &&
  bookTension >= tensionLevel &&
  bookDamage >= damageLevel &&
    bookDarkness >= darknessLevel &&
  (yearningLevel === 0 || bookYearning === yearningLevel);

if (kuFilter && kuFilter === "true"){
  var bookKU = book.dataset.ku === "true";
  matches = matches && bookKU;
}

if (tropeFilter){
  var bookTropes = (book.dataset.tropes || '').toLowerCase();
  matches = matches && bookTropes.includes(tropeFilter);
}

if (searchQuery){
  var combined =
    (book.dataset.title || '') + ' ' +
    (book.dataset.tropes || '') + ' ' +
    (book.dataset.why || '') + ' ' +
    (book.dataset.boyfriend || '');

  matches = matches && combined.toLowerCase().includes(searchQuery);
}

    book.style.display = matches ? '' : 'none';

  });
  refreshPaginatedGridVisibility();
  });

}

function bindArchiveRankInputs(){
  rankInputs.forEach(function(input){
    if (input.__sssRankBound) return;
    input.__sssRankBound = true;
    input.addEventListener('input', function(){
      hasInteracted = true;
      updateRanking();
    });
  });
}

bindArchiveRankInputs();
/* ======================
   YEARNING TOGGLE
====================== */

var yearningButtons = document.querySelectorAll('.sss-lib__yearningToggle [data-yearning]');

function bindArchiveYearningButtons(){
  yearningButtons.forEach(function(btn){
    if (btn.__sssYearningBound) return;
    btn.__sssYearningBound = true;
    btn.addEventListener('click', function(){

    // remove active from all
    yearningButtons.forEach(function(b){
      b.classList.remove('active');
    });

    // add active to clicked
    btn.classList.add('active');

    hasInteracted = true;

    updateRanking(); // trigger filter
  });

  });
}

bindArchiveYearningButtons();

var kuButtons = document.querySelectorAll('[data-ku-filter]');

function bindArchiveKuButtons(){
  kuButtons.forEach(function(btn){
    if (btn.__sssKuBound) return;
    btn.__sssKuBound = true;
    btn.addEventListener('click', function(){

    kuButtons.forEach(function(b){
      b.classList.remove('active');
    });

    btn.classList.add('active');

    hasInteracted = true;
    updateRanking();
  });

  });
}

bindArchiveKuButtons();
/* ======================
   FLOATING SHARE BUTTON
====================== */

const shareBtn = document.getElementById("sssShareLibrary");

if (shareBtn){

  shareBtn.addEventListener("click", function(){

    trackSiteEvent("library_shared", {
      uiLocation: "library_page",
      metadata: {
        method: navigator.share ? "native_share" : "clipboard"
      }
    });

    if (navigator.share){

      navigator.share({
        title: "The Smut & Sentiment Society Library",
        text: "You need to see this romance library 👀",
        url: window.location.href
      });

    } else {

      navigator.clipboard.writeText(window.location.href);

      shareBtn.innerHTML = "✓";

      setTimeout(()=>{
        shareBtn.innerHTML = "📲";
      },2000);

    }

  });

}
/* ======================
   TROPE DISCOVERY POPUP
====================== */

document.addEventListener("DOMContentLoaded", function(){

const popup = document.getElementById("sssTropePopup");
const list = document.getElementById("sssTropePopupList");
const close = document.getElementById("sssTropePopupClose");

if(!popup || !list) return;

let shown = false;
let popupTimer = null;

const tropePillColors = {
  "enemies-to-lovers": { bg: "#f2a7ad", text: "#6e1422" },
  "friends-to-lovers": { bg: "#bfe3cb", text: "#144a31" },
  "slow-burn": { bg: "#f2c179", text: "#6a3700" },
  "billionaire-romance": { bg: "#bfdca0", text: "#365316" },
  "billionaire": { bg: "#bfdca0", text: "#365316" },
  "second-chance": { bg: "#cfbef5", text: "#4b2280" },
  "forced-proximity": { bg: "#a9cdf6", text: "#163f72" },
  "grumpy-sunshine": { bg: "#f2d35f", text: "#5f4700" },
  "workplace-romance": { bg: "#bfd0ef", text: "#274469" },
  "fake-dating": { bg: "#efb6d3", text: "#6e2147" },
  "marriage-of-convenience": { bg: "#dbc2a7", text: "#6c4221" },
  "sports-romance": { bg: "#9fd8e5", text: "#0f5064" },
  "small-town": { bg: "#c7d89b", text: "#405719" },
  "brothers-best-friend": { bg: "#ebb99c", text: "#71351a" },
  "dark-romance": { bg: "#b8a0d8", text: "#2f1646" },
  "stalker-romance": { bg: "#b8a0d8", text: "#2f1646" },
  "stalker": { bg: "#b8a0d8", text: "#2f1646" },
  "morally-gray-hero": { bg: "#b9c1cb", text: "#26303b" },
  "morally-gray-men": { bg: "#b9c1cb", text: "#26303b" },
  "morally-gray": { bg: "#b9c1cb", text: "#26303b" },
  "touch-her-and-die": { bg: "#e596a8", text: "#641223" },
  "one-bed": { bg: "#d8b9ea", text: "#55276f" },
  "fated-mates": { bg: "#e7acd1", text: "#74204f" },
  "age-gap": { bg: "#c4d4ec", text: "#31486e" },
  "single-dad": { bg: "#b7dbc9", text: "#1f543b" },
  "reverse-harem": { bg: "#d7a8d7", text: "#651c58" }
};

function normalizeTropeHandle(value){
  return String(value || "")
    .toLowerCase()
    .trim()
    .replace(/&/g, " and ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function getTropePillColors(name){
  const handle = normalizeTropeHandle(name);
  return tropePillColors[handle] || { bg: "#f3bfd5", text: "#4b112d" };
}

function escapeTropePopupText(value){
  return String(value || "").replace(/[&<>"']/g, function(char){
    return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" }[char];
  });
}

function getTropePopupCustomKey(value){
  const label = String(value || "").toLowerCase();
  const handle = normalizeTropeHandle(value);
  const haystack = handle + " " + label;
  const aliases = [
    ["found-family", ["found family", "found-family"]],
    ["friends-to-lovers", ["friends to lovers", "friends-to-lovers"]],
    ["step-siblings", ["step siblings", "step-siblings", "stepsiblings"]],
    ["mafia-romance", ["mafia"]],
    ["slow-burn", ["slow burn", "slow-burn"]],
    ["enemies-to-lovers", ["enemies to lovers", "enemies-to-lovers"]],
    ["fated-mates", ["fated mates", "fated-mates"]],
    ["why-choose", ["why choose", "why-choose"]],
    ["touch-her-and-die", ["touch her and die", "touch-her-and-die"]],
    ["who-did-this-to-you", ["who did this to you", "who-did-this-to-you"]],
    ["dark-academia", ["dark academia", "dark-academia"]],
    ["dark-romance", ["dark romance", "dark-romance"]],
    ["romantasy", ["romantasy", "fantasy romance"]],
    ["paranormal-romance", ["paranormal"]],
    ["fake-dating", ["fake dating", "fake-dating"]],
    ["captor-x-captive", ["captor x captive", "captor-x-captive", "captor captive", "captor", "captive"]],
    ["boss-x-employee", ["boss x employee", "boss-x-employee", "boss employee"]],
    ["age-gap", ["age gap", "age-gap"]],
    ["trauma-bonding", ["trauma bonding", "trauma-bonding"]],
    ["baseball-romance", ["baseball romance", "baseball-romance", "baseball"]],
    ["hockey-romance", ["hockey romance", "hockey-romance", "hockey"]],
    ["one-bed", ["one bed", "one-bed"]],
    ["brothers-best-friend", ["brother best friend", "brothers best friend", "brother's best friend", "brothers-best-friend", "brother-s-best-friend"]],
    ["second-chance", ["second chance", "second-chance"]],
    ["contemporary-romance", ["contemporary romance", "contemporary-romance"]],
    ["forbidden-love", ["forbidden love", "forbidden-love", "forbidden romance"]],
    ["nanny", ["nanny"]],
    ["single-dad", ["single dad", "single-dad"]],
    ["small-town", ["small town", "small-town"]],
    ["grumpy-x-sunshine", ["grumpy x sunshine", "grumpy-x-sunshine", "grumpy sunshine"]],
    ["billionaire-romance", ["billionaire"]],
    ["stalker-romance", ["stalker"]],
    ["dystopian-romance", ["dystopian romance", "dystopian-romance"]],
    ["sports-romance", ["sports romance", "sports-romance", "sports"]],
    ["bully-romance", ["bully romance", "bully-romance", "bully"]],
    ["forced-proximity", ["forced proximity", "forced-proximity"]],
    ["villain-gets-the-girl", ["villain gets the girl", "villain-gets-the-girl", "villain romance"]],
    ["historical-romance", ["historical romance", "historical-romance"]],
    ["bodyguard-romance", ["bodyguard romance", "bodyguard"]],
    ["opposites-attract", ["opposites attract", "opposites-attract"]],
    ["marriage-of-convenience", ["marriage of convenience", "marriage-of-convenience"]]
  ];
  for (let i = 0; i < aliases.length; i += 1) {
    for (let j = 0; j < aliases[i][1].length; j += 1) {
      if (haystack.indexOf(aliases[i][1][j]) !== -1) return aliases[i][0];
    }
  }
  return "";
}

function getTropePopupHtml(name, label){
  const displayLabel = label || name;
  const key = getTropePopupCustomKey(name + " " + displayLabel);
  if (!key) return escapeTropePopupText(displayLabel);
  const emojiMap = window.BBBSiteData && window.BBBSiteData.customTropeEmojis ? window.BBBSiteData.customTropeEmojis : {};
  const src = emojiMap[key] || ("/wp-content/themes/wordpress-theme/assets/images/custom-emojis/" + key + ".png");
  return '<img class="bbb-custom-emoji" src="' + escapeTropePopupText(src) + '" alt="" aria-hidden="true" loading="lazy" decoding="async"> <span>' + escapeTropePopupText(displayLabel) + '</span>';
}

/* ----------------------
CHECK IF PAGE EXISTS
---------------------- */

async function pageExists(url){

try{

const res = await fetch(url,{ method:"HEAD" });

return res.ok;

}catch(e){

return false;

}

}

/* ----------------------
BUILD TROPE LINKS
---------------------- */

async function renderTropes(names, urls, labels){

list.innerHTML = "";

let added = 0;

for(let i=0;i<names.length;i++){

if(added >= 3) break;

const name = names[i];
const url = urls[i];
const labelText = labels[i] || name;

if(!url) continue;

const exists = await pageExists(url);

if(!exists) continue;

const a = document.createElement("a");
const colors = getTropePillColors(name);

a.href = url;
a.className = "sss-tropePopup__pill";
a.innerHTML = getTropePopupHtml(name, labelText);
a.style.setProperty("--trope-bg", colors.bg);
a.style.setProperty("--trope-text", colors.text);

list.appendChild(a);

added++;

}

}

/* ----------------------
SHOW POPUP
---------------------- */

async function showPopup(names, urls, labels){

if(shown) return;

shown = true;

await renderTropes(names, urls, labels);

if(list.children.length > 0){

popup.hidden = false;
  if(popupTimer){
    clearTimeout(popupTimer);
  }
  popupTimer = window.setTimeout(function(){
    popup.hidden = true;
  }, 8000);

}

}

/* ----------------------
BOOK INTERACTION
---------------------- */

document.querySelectorAll(".sss-lib__book").forEach(function(card){

card.addEventListener("mouseenter", function(){

if(shown) return;

const tropeNames = (card.dataset.tropes || "")
.split(",")
.map(t => t.trim())
.filter(Boolean);

const tropeUrls = (card.dataset.tropeUrls || "")
.split(",")
.map(t => t.trim())
.filter(Boolean);

const tropeLabels = (card.dataset.tropesDisplay || "")
.split(",")
.map(t => t.trim());

if(!tropeNames.length) return;

showPopup(tropeNames, tropeUrls, tropeLabels);

});

});

/* ----------------------
CLOSE POPUP
---------------------- */

if(close){

close.addEventListener("click", function(){

popup.hidden = true;
if(popupTimer){
  clearTimeout(popupTimer);
}

});

}

});

/* ======================
   SEARCH FILTER
====================== */

function bindArchiveSearchFilters(){
  if (searchInput && !searchInput.__sssArchiveSearchBound){
    searchInput.__sssArchiveSearchBound = true;
    searchInput.addEventListener('input', function(){
      hasInteracted = true;
      updateRanking();
    });
  }

  if (archiveTropeSelect && !archiveTropeSelect.__sssArchiveTropeBound){
    archiveTropeSelect.__sssArchiveTropeBound = true;
    archiveTropeSelect.addEventListener('change', function(){
      hasInteracted = true;
      updateRanking();
    });
  }
}

bindArchiveSearchFilters();

function initArchiveFilters(){
  rankInputs = document.querySelectorAll('[data-rank]');
  archiveTropeSelect = document.getElementById('sssArchiveTropeFilter');
  searchInput = document.getElementById('sssSearchInput');
  yearningButtons = document.querySelectorAll('.sss-lib__yearningToggle [data-yearning]');
  kuButtons = document.querySelectorAll('[data-ku-filter]');

  bindArchiveSliderTouchLock();
  bindArchiveRankInputs();
  bindArchiveYearningButtons();
  bindArchiveKuButtons();
  bindArchiveSearchFilters();
  updateRanking();
}

/* ======================
   MADE FOR YOU
====================== */

function initMadeForYou(){
  var dataEl = document.getElementById('sssMadeForYouData');
  var root = document.getElementById('sssMadeForYou');
  if (!dataEl || !root) return;
  if (root.__bbbMadeForYouInitialized) return;
  root.__bbbMadeForYouInitialized = true;

  var profileVersion = getMfyProfileVersion();
  var accountScopedKey = getMfyAccountKey();
  var storageKey = scopedMfyStorageKey('sssMadeForYouProfile');
  var questionsOrder = ['name', 'heat_lane', 'group_chat_text', 'love_interest', 'wall_line'];
  var row = document.getElementById('sssMadeForYouRow');
  var nextOpinionEl = document.getElementById('sssMfyNextOpinion');
  var matchBookEl = document.getElementById('sssMfyMatchBook');
  var boyfriendKicker = document.getElementById('sssMfyBoyfriendKicker');
  var backBtn = document.getElementById('sssMadeForYouBack');
  var finishBtn = document.getElementById('sssMadeForYouFinish');
  var resetBtn = document.getElementById('sssMadeForYouReset');
  var nameInput = document.getElementById('sssMfyNameInput');
  var nameContinueBtn = document.getElementById('sssMfyNameContinue');
  var stepCount = document.getElementById('sssMfyStepCount');
  var progressFill = document.getElementById('sssMfyProgressFill');
  var continueNote = root.querySelector('.sss-mfy__continueNote');
  var resultsEl = document.getElementById('sssMadeForYouResults');
  var resultPanels = Array.prototype.slice.call(root.querySelectorAll('[data-mfy-panel]'));
  var resultsRail = root.querySelector('.sss-mfy__resultsRail');
  var quoteDataEl = document.getElementById('sssMadeForYouQuotes');
  var nextResultBtn = document.getElementById('sssMfyNextResult');
  var resultsMeta = document.getElementById('sssMfyResultsMeta');
  var dashboardTitle = document.getElementById('sssMfyDashboardTitle');
  var dashboardKicker = document.getElementById('sssMfyDashboardKicker');
  var newsletterDataEl = document.getElementById('sssMadeForYouNewsletters');
  var blogPostsDataEl = document.getElementById('sssMadeForYouBlogPosts');
  var boyfriendDataEl = document.getElementById('sssMadeForYouBoyfriends');
  var readerTypesDataEl = document.getElementById('sssReaderTypesData');
  var resetResultsBtn = document.getElementById('sssMadeForYouResetResults');
  var customizeEl = document.getElementById('sssMfyCustomize');
  var seeFullBreakdownBtn = document.getElementById('sssMfySeeFullBreakdown');
  var quoteSpotlightEl = document.getElementById('sssMfyQuoteSpotlight');
  var savedQuotesEl = document.getElementById('sssMfySavedQuotes');
  var readShelfEl = document.getElementById('sssMfyReadShelf');
  var addonButtons = Array.prototype.slice.call(root.querySelectorAll('[data-mfy-addon]'));
  var addonModules = Array.prototype.slice.call(root.querySelectorAll('[data-mfy-module]'));
  var addonCloseButtons = Array.prototype.slice.call(root.querySelectorAll('[data-mfy-close]'));
  var trackEl = document.getElementById('sssMfyTrack');
  var heroKicker = document.getElementById('sssMfyHeroKicker');
  var coreEmojiBadge = document.getElementById('sssMfyCoreEmojiBadge');
  var coreTitle = document.getElementById('sssMfyCoreTitle');
  var coreEmotion = document.getElementById('sssMfyCoreEmotion');
  var coreBody = document.getElementById('sssMfyCoreBody');
  var heroRain = document.getElementById('sssMfyHeroRain');
  var themeTokens = document.getElementById('sssMfyThemeTokens');
  var dashboardSpice = document.getElementById('sssMfyDashboardSpice');
  var dashboardSpiceLabel = document.getElementById('sssMfyDashboardSpiceLabel');
  var dashboardReaderType = document.getElementById('sssMfyDashboardReaderType');
  var dashboardReaderSignal = document.getElementById('sssMfyDashboardReaderSignal');
  var dashboardTrope = document.getElementById('sssMfyDashboardTrope');
  var dashboardTheme = document.getElementById('sssMfyDashboardTheme');
  var dashboardThemeSignal = document.getElementById('sssMfyDashboardThemeSignal');
  var visibleReaderType = document.getElementById('sssMfyVisibleReaderType');
  var visibleReaderSignal = document.getElementById('sssMfyVisibleReaderSignal');
  var visibleSpice = document.getElementById('sssMfyVisibleSpice');
  var visibleSpiceLabel = document.getElementById('sssMfyVisibleSpiceLabel');
  var visibleTrope = document.getElementById('sssMfyVisibleTrope');
  var visibleTheme = document.getElementById('sssMfyVisibleTheme');
  var personaBadge = document.getElementById('sssMfyPersonaBadge');
  var refreshRecsBtn = document.getElementById('sssMfyRefreshRecs');
  var dashboardBookshelf = document.getElementById('sssMfyDashboardBookshelf');
  var bookshelfTabButtons = Array.prototype.slice.call(root.querySelectorAll('[data-mfy-bookshelf-tab]'));
  var fictionalBfCard = document.getElementById('sssMfyFictionalBfCard');
  var fictionalBfLabel = document.getElementById('sssMfyFictionalBfLabel');
  var quickLinksGrid = document.getElementById('sssMfyQuickLinks');
  var featureLinksGrid = document.getElementById('sssMfyFeatureLinks');
  var societyDashboard = root.querySelector('[data-society-dashboard]');
  var societyReaderBadge = root.querySelector('[data-society-reader-badge]');
  var societyReaderBio = root.querySelector('[data-society-reader-bio]');
  var societyReaderFootnote = root.querySelector('[data-society-reader-footnote]');
  var societyHeatBar = root.querySelector('[data-society-heat-bar]');
  var societyShelfPreview = root.querySelector('[data-society-shelf-preview]');
  var societySaveCount = root.querySelector('[data-society-save-count]');
  var societyReadCount = root.querySelector('[data-society-read-count]');
  var societyTopTrope = root.querySelector('[data-society-top-trope]');
  var societyBfName = root.querySelector('[data-society-bf-name]');
  var societyBfCard = root.querySelector('[data-society-bf-card]');
  var societyTropeDna = root.querySelector('[data-society-trope-dna]');
  var societyPerks = root.querySelector('[data-society-perks]');
  var societyDashboardTitle = root.querySelector('[data-society-dashboard-title]');
  var societyThemeButton = root.querySelector('[data-society-theme-button]');
  var noteInput = document.getElementById('sssMfyNoteInput');
  var saveNoteBtn = document.getElementById('sssMfySaveNote');
  var notesList = document.getElementById('sssMfyNotesList');
  var typeTitle = document.getElementById('sssMfyTypeTitle');
  var typeBody = document.getElementById('sssMfyTypeBody');
  var boyfriendEmojiBadge = document.getElementById('sssMfyBoyfriendEmojiBadge');
  var boyfriendRain = document.getElementById('sssMfyBoyfriendRain');
  var shelfKicker = document.getElementById('sssMfyShelfKicker');
  var shelfEmojiBadge = document.getElementById('sssMfyShelfEmojiBadge');
  var shelfRain = document.getElementById('sssMfyShelfRain');
  var shelfTitle = document.getElementById('sssMfyShelfTitle');
  var shelfBody = document.getElementById('sssMfyShelfBody');
  var readsKicker = document.getElementById('sssMfyReadsKicker');
  var readsEmojiBadge = document.getElementById('sssMfyReadsEmojiBadge');
  var readsRain = document.getElementById('sssMfyReadsRain');
  var quoteRain = document.getElementById('sssMfyQuoteRain');
  var quoteEyebrow = document.getElementById('sssMfyQuoteEyebrow');
  var readShelfEyebrow = document.getElementById('sssMfyReadShelfEyebrow');
  var recTitle = document.getElementById('sssMfyRecTitle');
  var readShelfMeta = document.getElementById('sssMfyReadShelfMeta');
  var readShelfRow = document.getElementById('sssMfyReadShelfRow');
  var readTropesEl = document.getElementById('sssMfyReadTropes');
  var readShelfInsight = document.getElementById('sssMfyReadShelfInsight');
  var readNextTitle = document.getElementById('sssMfyReadNextTitle');
  var readNextRow = document.getElementById('sssMfyReadNextRow');
  var hardNoButtons = Array.prototype.slice.call(root.querySelectorAll('[data-mfy-hard-no]'));
  var saveHardNosBtn = document.getElementById('sssMfySaveHardNos');
  var hardNoSummary = document.getElementById('sssMfyHardNoSummary');
  var manDialInput = document.getElementById('sssMfyManDialInput');
  var manDialOrb = document.getElementById('sssMfyManDialOrb');
  var manDialValue = document.getElementById('sssMfyManDialValue');
  var manDialChoices = Array.prototype.slice.call(root.querySelectorAll('[data-mfy-dial-choice]'));
  var saveManDialBtn = document.getElementById('sssMfySaveManDial');
  var manDialSummary = document.getElementById('sssMfyManDialSummary');
  var favoriteTropeButtons = Array.prototype.slice.call(root.querySelectorAll('[data-mfy-favorite-trope]'));
  var saveFavoriteTropeBtn = document.getElementById('sssMfySaveFavoriteTrope');
  var favoriteTropeSummary = document.getElementById('sssMfyFavoriteTropeSummary');
  var favoriteBookSearchInput = document.getElementById('sssMfyFavoriteBookSearch');
  var favoriteBookResults = document.getElementById('sssMfyFavoriteBookResults');
  var saveFavoriteBookBtn = document.getElementById('sssMfySaveFavoriteBook');
  var favoriteBookEcho = document.getElementById('sssMfyFavoriteBookEcho');
  var favoriteBookPreview = document.getElementById('sssMfyFavoriteBookPreview');
  var favoriteSummary = document.getElementById('sssMfyFavoriteSummary');
  var quoteCard = document.getElementById('sssMfyQuoteCard');
  var quoteText = document.getElementById('sssMfyQuoteText');
  var quoteSource = document.getElementById('sssMfyQuoteSource');
  var savedQuotesMeta = document.getElementById('sssMfySavedQuotesMeta');
  var savedQuotesRow = document.getElementById('sssMfySavedQuotesRow');
  var manDialNote = document.getElementById('sssMfyManDialNote');
  var answerButtons = root.querySelectorAll('[data-mfy-answer]');
  var questionEls = Array.prototype.slice.call(root.querySelectorAll('.sss-mfy__slide'));
  var books = [];
  var currentStep = 0;
  var currentResultStep = 0;
  var isDashboardView = false;
  var isPersonalLayerView = false;
  var draftHardNos = [];
  var draftManDial = '';
  var draftFavoriteTrope = '';
  var draftFavoriteBook = '';
  var dashboardBookshelfTab = 'reading';
  var fallbackStorage = {};

  function storageGet(key){
    try {
      if (window.localStorage){
        var stored = window.localStorage.getItem(key);
        if (stored !== null){
          fallbackStorage[key] = stored;
        }
        return stored;
      }
    } catch(e) {}
    return Object.prototype.hasOwnProperty.call(fallbackStorage, key) ? fallbackStorage[key] : null;
  }

  function storageSet(key, value){
    fallbackStorage[key] = String(value);
    try {
      if (window.localStorage){
        window.localStorage.setItem(key, String(value));
      }
    } catch(e) {}
  }

  function storageRemove(key){
    delete fallbackStorage[key];
    try {
      if (window.localStorage){
        window.localStorage.removeItem(key);
      }
    } catch(e) {}
  }

  function scopedMfyStorageKey(key){
    return accountScopedKey ? key + '::' + accountScopedKey : key;
  }

  function cleanupLegacyMfyStorage(key){
    if (!accountScopedKey) return;
    if (key === 'bbbReaderSpiceProfile') return;
    storageRemove(key);
  }

  try {
    books = JSON.parse(dataEl.textContent) || [];
  } catch(e) {
    books = [];
  }

  var quoteLibrary = [];
  try {
    quoteLibrary = quoteDataEl ? (JSON.parse(quoteDataEl.textContent) || []) : [];
  } catch(e) {
    quoteLibrary = [];
  }

  var newsletterLibrary = [];
  try {
    newsletterLibrary = newsletterDataEl ? (JSON.parse(newsletterDataEl.textContent) || []) : [];
  } catch(e) {
    newsletterLibrary = [];
  }

  var blogPostLibrary = [];
  try {
    blogPostLibrary = blogPostsDataEl ? (JSON.parse(blogPostsDataEl.textContent) || []) : [];
  } catch(e) {
    blogPostLibrary = [];
  }

  var boyfriendLibrary = [];
  try {
    boyfriendLibrary = boyfriendDataEl ? (JSON.parse(boyfriendDataEl.textContent) || []) : [];
  } catch(e) {
    boyfriendLibrary = [];
  }

  var readerTypeRegistry = [];
  try {
    readerTypeRegistry = readerTypesDataEl ? (JSON.parse(readerTypesDataEl.textContent) || []) : [];
  } catch(e) {
    readerTypeRegistry = [];
  }

  var answerGroups = {
    heat_lane: {},
    group_chat_text: {},
    love_interest: {},
    wall_line: {},
    craving: {},
    favorite_trope: {},
    reader_type_prior: {},
    spice_dial: {},
    theme: {}
  };

  var boyfriendTypeAliases = {
    academic_rival: ['academic rival', 'academic rivals', 'rival', 'rivals'],
    arrogant_asshole: ['arrogant asshole', 'arrogant arsehole', 'arrogant menace', 'rich man with issues', 'billionaire'],
    cold_grump: ['cold grump', 'quiet brooder', 'brooding hero', 'grump', 'brooder'],
    bully: ['bully', 'bully romance'],
    emotionally_unavailable_man: ['emotionally unavailable man', 'emotionally unavailable', 'commitment issues'],
    obsessive_protector: ['obsessive protector', 'possessive protector', 'protective hero', 'protector'],
    athlete_with_heart: ['athlete with heart', 'golden retriever with bite', 'golden retriever', 'athlete'],
    morally_gray_villain: ['morally gray villain', 'morally gray', 'villain', 'villain gets the girl'],
    mafia_boss: ['mafia boss', 'mafia'],
    tortured_prince: ['tortured prince', 'prince', 'fallen prince'],
    stalker: ['stalker', 'obsession', 'obsessive stalker'],
    sweetheart: ['sweetheart', 'soft boy', 'cinnamon roll']
  };

  var legacyThemeMap = {
    blush_pink: 'rose_ribbon',
    deep_red: 'obsession_red',
    forest_green: 'pearl_white',
    midnight_blue: 'stormy_blue',
    buttercream: 'pearl_white'
  };

  var themeProfiles = {
    dark_hearts: { season: 'autumn', emojiGroup: 'dangerous_pretty' },
    obsession_red: { season: 'summer', emojiGroup: 'spicy_glam' },
    rose_ribbon: { season: 'spring', emojiGroup: 'soft_romantic' },
    stormy_blue: { season: 'winter', emojiGroup: 'stormy_broody' },
    pearl_white: { season: 'spring', emojiGroup: 'cozy_reader' },
    royal_violet: { season: 'winter', emojiGroup: 'dangerous_pretty' }
  };

  var legacyBoyfriendMap = {
    possessive_protector: 'obsessive_protector',
    quiet_brooder: 'cold_grump',
    arrogant_menace: 'arrogant_asshole',
    golden_retriever_bite: 'athlete_with_heart',
    rich_man_issues: 'emotionally_unavailable_man'
  };

  var legacyBoyfriendQuizMap = {
    academic_rival: {
      boyfriend_hook: 'brain_and_banter',
      boyfriend_dynamic: 'rivals_with_tension'
    },
    arrogant_asshole: {
      boyfriend_hook: 'brain_and_banter',
      boyfriend_dynamic: 'mean_and_magnetic'
    },
    cold_grump: {
      boyfriend_hook: 'cold_and_unreadable',
      boyfriend_dynamic: 'grump_softening'
    },
    bully: {
      boyfriend_hook: 'dangerous_and_powerful',
      boyfriend_dynamic: 'mean_and_magnetic'
    },
    emotionally_unavailable_man: {
      boyfriend_hook: 'cold_and_unreadable',
      boyfriend_dynamic: 'grump_softening'
    },
    obsessive_protector: {
      boyfriend_hook: 'protective_and_all_in',
      boyfriend_dynamic: 'touch_her_and_die'
    },
    athlete_with_heart: {
      boyfriend_hook: 'charming_and_soft',
      boyfriend_dynamic: 'falls_first_hard'
    },
    morally_gray_villain: {
      boyfriend_hook: 'dangerous_and_powerful',
      boyfriend_dynamic: 'villainous_obsession'
    },
    mafia_boss: {
      boyfriend_hook: 'dangerous_and_powerful',
      boyfriend_dynamic: 'touch_her_and_die'
    },
    tortured_prince: {
      boyfriend_hook: 'cold_and_unreadable',
      boyfriend_dynamic: 'grump_softening'
    },
    stalker: {
      boyfriend_hook: 'protective_and_all_in',
      boyfriend_dynamic: 'villainous_obsession'
    },
    sweetheart: {
      boyfriend_hook: 'charming_and_soft',
      boyfriend_dynamic: 'falls_first_hard'
    }
  };

  var boyfriendQuestionWeights = {
    boyfriend_hook: {
      brain_and_banter: { academic_rival: 4, arrogant_asshole: 3, emotionally_unavailable_man: 1 },
      cold_and_unreadable: { cold_grump: 4, emotionally_unavailable_man: 4, tortured_prince: 3 },
      dangerous_and_powerful: { morally_gray_villain: 4, mafia_boss: 4, arrogant_asshole: 2, bully: 1 },
      protective_and_all_in: { obsessive_protector: 5, stalker: 4, mafia_boss: 1 },
      charming_and_soft: { athlete_with_heart: 4, sweetheart: 5, academic_rival: 1 }
    },
    boyfriend_dynamic: {
      rivals_with_tension: { academic_rival: 5, arrogant_asshole: 3, cold_grump: 1 },
      grump_softening: { cold_grump: 5, emotionally_unavailable_man: 4, tortured_prince: 3 },
      mean_and_magnetic: { arrogant_asshole: 4, bully: 4, morally_gray_villain: 2 },
      touch_her_and_die: { obsessive_protector: 5, mafia_boss: 4, stalker: 3 },
      falls_first_hard: { sweetheart: 5, athlete_with_heart: 4, obsessive_protector: 1 },
      villainous_obsession: { morally_gray_villain: 5, stalker: 4, mafia_boss: 3 }
    }
  };

  var spiceDialValues = ['soft_open_door', 'some_heat', 'balanced', 'high_spice', 'wreck_me'];
  var storedSpiceProfileMap = {
    1: 'soft_open_door',
    2: 'some_heat',
    3: 'balanced',
    4: 'high_spice',
    5: 'wreck_me'
  };
  var legacySpiceDialMap = {
    safer: 'soft_open_door',
    broodier: 'balanced',
    meaner: 'high_spice',
    richer: 'balanced',
    'more obsessed': 'wreck_me'
  };

  var profile = loadProfile();
  var accountSnapshot = getAccountSnapshot();
  var sharedTasteProfile = loadSharedTasteProfile();
  var shouldPersistProfileMigration = false;
  if (sharedTasteProfile.favorite_trope && !profile.favorite_trope){
    profile.favorite_trope = sharedTasteProfile.favorite_trope;
    shouldPersistProfileMigration = true;
  }
  if (sharedTasteProfile.dashboard_theme && !profile.theme){
    profile.theme = sharedTasteProfile.dashboard_theme;
    shouldPersistProfileMigration = true;
  }
  var storedSpiceProfileLevel = getStoredSpiceProfileLevel();
  if (storedSpiceProfileLevel && profile.spice_profile !== storedSpiceProfileLevel){
    profile.spice_profile = storedSpiceProfileLevel;
    profile.spice_dial = storedSpiceProfileMap[storedSpiceProfileLevel] || profile.spice_dial || 'balanced';
    shouldPersistProfileMigration = true;
  }
  if (legacyThemeMap[profile.color] && !profile.theme){
    profile.theme = legacyThemeMap[profile.color];
    shouldPersistProfileMigration = true;
  }
  if (!profile.theme && themeProfiles[profile.color]){
    profile.theme = profile.color;
    shouldPersistProfileMigration = true;
  }
  if (profile.fictional_man && legacyBoyfriendMap[profile.fictional_man]){
    profile.fictional_man = legacyBoyfriendMap[profile.fictional_man];
    shouldPersistProfileMigration = true;
  }
  if (profile.fictional_man){
    var normalizedFictionalMan = canonicalBoyfriendType(profile.fictional_man);
    if (normalizedFictionalMan !== profile.fictional_man){
      profile.fictional_man = normalizedFictionalMan;
      shouldPersistProfileMigration = true;
    }
  }
  if ((!profile.boyfriend_hook || !profile.boyfriend_dynamic) && profile.fictional_man && legacyBoyfriendQuizMap[profile.fictional_man]){
    var seededQuiz = legacyBoyfriendQuizMap[profile.fictional_man];
    profile.boyfriend_hook = profile.boyfriend_hook || seededQuiz.boyfriend_hook;
    profile.boyfriend_dynamic = profile.boyfriend_dynamic || seededQuiz.boyfriend_dynamic;
    shouldPersistProfileMigration = true;
  }
  if (profile.boyfriend_hook && profile.boyfriend_dynamic){
    var derivedBoyfriendType = deriveBoyfriendTypeFromQuiz(profile);
    if (derivedBoyfriendType && derivedBoyfriendType !== profile.fictional_man){
      profile.fictional_man = derivedBoyfriendType;
      shouldPersistProfileMigration = true;
    }
  }
  if (!profile.fictional_boyfriend){
    var savedQuizBoyfriend = getSavedFictionalBoyfriend(profile);
    if (savedQuizBoyfriend){
      profile.fictional_boyfriend = savedQuizBoyfriend;
      if (savedQuizBoyfriend.result_type && !profile.fictional_man){
        profile.fictional_man = savedQuizBoyfriend.result_type;
      }
      shouldPersistProfileMigration = true;
    }
  } else if (profile.fictional_boyfriend && profile.fictional_boyfriend.result_type && !profile.fictional_man){
    profile.fictional_man = profile.fictional_boyfriend.result_type;
    shouldPersistProfileMigration = true;
  }
  if (profile.dashboard_built && profile.heat_lane && profile.group_chat_text && profile.love_interest && profile.wall_line){
    var repairedReaderType = getQuizResolvedReaderTypeKey();
    if (repairedReaderType && repairedReaderType !== profile.reader_type_prior){
      profile.reader_type_prior = repairedReaderType;
      if (readerTypePrimaryTrope[repairedReaderType]){
        profile.favorite_trope = readerTypePrimaryTrope[repairedReaderType];
      }
      shouldPersistProfileMigration = true;
    }
  }
  if (!profile.spice_dial && profile.man_dial && legacySpiceDialMap[profile.man_dial]){
    profile.spice_dial = legacySpiceDialMap[profile.man_dial];
    delete profile.man_dial;
    shouldPersistProfileMigration = true;
  }
  if (profile.spice_dial && spiceDialValues.indexOf(profile.spice_dial) === -1){
    profile.spice_dial = 'balanced';
    shouldPersistProfileMigration = true;
  }
  if (Array.isArray(profile.favorite_tropes) && profile.favorite_tropes.length && !profile.favorite_trope){
    profile.favorite_trope = String(profile.favorite_tropes[0] || '').trim();
    shouldPersistProfileMigration = true;
  }
  if (profile.favorite_trope){
    profile.favorite_trope = normalize(profile.favorite_trope);
    shouldPersistProfileMigration = true;
  }
  if (profile.theme){
    delete profile.color;
    delete profile.season;
    delete profile.emoji_group;
    shouldPersistProfileMigration = true;
  }
  if (Array.isArray(profile.panel_order)){
    delete profile.panel_order;
    shouldPersistProfileMigration = true;
  }
  if (Array.isArray(profile.open_addons) && profile.open_addons.indexOf('quote_spotlight') > -1){
    profile.open_addons = profile.open_addons.filter(function(item){
      return item !== 'quote_spotlight';
    });
    shouldPersistProfileMigration = true;
  }
	  if (shouldPersistProfileMigration){
	    saveProfile(profile);
	  } else if (
	    profile && Object.keys(profile).length &&
	    (!accountSnapshot.madeForYouProfile || !Object.keys(accountSnapshot.madeForYouProfile).length || isNewerProfile(profile, accountSnapshot.madeForYouProfile))
	  ){
	    saveProfile(profile);
	  }
  syncAddonDrafts();
  currentStep = getInitialStep();

  if (nameInput){
    nameInput.value = profile.name || '';
    nameInput.addEventListener('input', function(){
      profile.name = String(nameInput.value || '').trim();
      saveProfile(profile);
      syncStepUI();
      renderMadeForYou();
    });
    nameInput.addEventListener('keydown', function(event){
      if (event.key !== 'Enter' || !nameContinueBtn || nameContinueBtn.disabled){
        return;
      }
      event.preventDefault();
      nameContinueBtn.click();
    });
  }

  if (nameContinueBtn){
    nameContinueBtn.addEventListener('click', function(){
      var nextName = String(nameInput && nameInput.value || '').trim();
      if (!nextName){
        if (nameInput) nameInput.focus();
        return;
      }
      profile.name = nextName;
      saveProfile(profile);
      currentStep = 1;
      syncStepUI();
      renderMadeForYou();
    });
  }

  var cravingProfiles = {
    cozy: {
      title: 'you are in your soft landing era',
      body: 'you want comfort, sweetness, and romance that feels warm without going flat.',
      tropeBoosts: ['friends to lovers', 'small town romance', 'grumpy sunshine', 'healing', 'comfort'],
      shelfBoosts: ['contemporary romance', 'small town romance'],
      boyfriendBoosts: ['sweetheart', 'athlete with heart', 'cold grump'],
      stats: { yearning: 1, damage: 1 }
    },
    spicy: {
      title: 'you want chaos with heat on it',
      body: 'you are here for banter, bad decisions, sharp chemistry, and the kind of page-turning that feels a little reckless.',
      tropeBoosts: ['enemies to lovers', 'forced proximity', 'forbidden romance', 'workplace romance'],
      shelfBoosts: ['sports romance', 'dark romance', 'contemporary romance'],
      boyfriendBoosts: ['arrogant asshole', 'bully', 'athlete with heart', 'mafia boss'],
      stats: { tension: 2, spice: 2 }
    },
    dark: {
      title: 'you are choosing the pretty bad idea',
      body: 'you want intensity, obsession, and romance with enough danger to make the devotion feel impossible to ignore.',
      tropeBoosts: ['dark romance', 'morally gray', 'touch her and die', 'obsession', 'forbidden romance'],
      shelfBoosts: ['dark romance', 'gothic romance', 'romantasy'],
      boyfriendBoosts: ['morally gray villain', 'mafia boss', 'stalker', 'obsessive protector'],
      stats: { darkness: 2, tension: 2 }
    },
    slowburn: {
      title: 'you want the almost-touch to do damage',
      body: 'you like glances, restraint, tension that stretches, and payoff that earns every single page.',
      tropeBoosts: ['slow burn', 'yearning', 'forced proximity', 'grumpy sunshine', 'second chance'],
      shelfBoosts: ['sports romance', 'contemporary romance', 'romantasy'],
      boyfriendBoosts: ['cold grump', 'emotionally unavailable man', 'academic rival', 'tortured prince'],
      stats: { tension: 2, yearning: 2 }
    },
    surprise: {
      title: 'you are letting the shelf choose violence',
      body: 'you want the dashboard to read the room and hand you whatever feels most likely to hook you next.',
      tropeBoosts: ['enemies to lovers', 'slow burn', 'forced proximity', 'second chance', 'forbidden romance'],
      shelfBoosts: ['sports romance', 'contemporary romance', 'romantasy', 'dark romance'],
      boyfriendBoosts: ['academic rival', 'athlete with heart', 'cold grump', 'morally gray villain'],
      stats: { tension: 1, yearning: 1, spice: 1 }
    },
    slow_ache: {
      title: 'you are here for yearning that ruins your peace',
      body: 'you like tension that stretches, glances that linger, and romance that takes its sweet time before it wrecks you.',
      tropeBoosts: ['slow burn', 'yearning', 'friends to lovers', 'sports romance'],
      shelfBoosts: ['sports romance', 'contemporary romance'],
      boyfriendBoosts: ['cold grump', 'emotionally unavailable man', 'tortured prince', 'academic rival'],
      stats: { tension: 2, damage: 1, yearning: 1 }
    },
    messy_obsession: {
      title: 'you like devotion with surveillance tendencies',
      body: 'you want romance that feels consuming, possessive, and a little impossible to explain to polite company.',
      tropeBoosts: ['stalker', 'morally gray', 'obsession', 'possessive', 'dark romance'],
      shelfBoosts: ['dark romance', 'romantasy'],
      boyfriendBoosts: ['stalker', 'obsessive protector', 'morally gray villain', 'mafia boss'],
      stats: { darkness: 2, tension: 1, spice: 1 }
    },
    comfort_devotion: {
      title: 'you want softness, but not boredom',
      body: 'you like loyalty, tenderness, and emotional safety, but you still need enough ache to keep things memorable.',
      tropeBoosts: ['friends to lovers', 'protective hero', 'caretaking', 'marriage of convenience'],
      shelfBoosts: ['contemporary romance', 'small town romance'],
      boyfriendBoosts: ['sweetheart', 'athlete with heart', 'obsessive protector'],
      stats: { yearning: 1, damage: 1 }
    },
    chaos_chemistry: {
      title: 'you like your romance fast, sharp, and a little unhinged',
      body: 'you want chemistry on impact, banter that bites, and books that feel addictive from page one.',
      tropeBoosts: ['enemies to lovers', 'forced proximity', 'banter', 'workplace romance'],
      shelfBoosts: ['sports romance', 'contemporary romance', 'rom-com'],
      boyfriendBoosts: ['academic rival', 'arrogant asshole', 'athlete with heart', 'bully'],
      stats: { tension: 2, spice: 1 }
    },
    dark_dangerous: {
      title: 'you are romantically aligned with danger and bad decisions',
      body: 'you do not want the safest option in the room. you want mystery, menace, and a man who could absolutely make things worse first.',
      tropeBoosts: ['dark romance', 'villain gets the girl', 'touch her and die', 'morally gray'],
      shelfBoosts: ['dark romance', 'gothic romance', 'romantasy'],
      boyfriendBoosts: ['morally gray villain', 'mafia boss', 'stalker', 'tortured prince'],
      stats: { darkness: 3, tension: 1 }
    }
  };

  var payoffProfiles = {
    long_tension: { tropeBoosts: ['slow burn', 'yearning'], stats: { tension: 2 } },
    emotional_devastation: { tropeBoosts: ['angst', 'second chance'], stats: { damage: 2 } },
    soft_after_storm: { tropeBoosts: ['healing', 'comfort'], stats: { damage: 1, yearning: 1 } },
    plot_addiction: { shelfBoosts: ['romantasy', 'sports romance'], stats: { tension: 1, darkness: 1 } },
    illegal_chemistry: { tropeBoosts: ['forbidden romance', 'enemies to lovers'], stats: { spice: 2 } }
  };

	  var favoriteTropeProfiles = {
	    'enemies to lovers': { tropeBoosts: ['enemies to lovers', 'rivals to lovers', 'banter'], boyfriendBoosts: ['academic rival', 'arrogant asshole'], stats: { tension: 1 } },
	    'friends to lovers': { tropeBoosts: ['friends to lovers', 'childhood friends', 'best friends to lovers', 'teammates', 'small town romance', 'found family', 'healing'], shelfBoosts: ['contemporary romance', 'small town romance', 'sports romance'], boyfriendBoosts: ['sweetheart', 'athlete with heart'], stats: { yearning: 1, damage: 1 } },
	    'second chance': { tropeBoosts: ['second chance', 'angst', 'yearning'], boyfriendBoosts: ['emotionally unavailable man', 'tortured prince'], stats: { damage: 1, yearning: 1 } },
    'forced proximity': { tropeBoosts: ['forced proximity', 'one bed', 'only one bed'], boyfriendBoosts: ['cold grump', 'academic rival'], stats: { tension: 1 } },
    'fake dating': { tropeBoosts: ['fake dating', 'fake relationship', 'marriage of convenience'], boyfriendBoosts: ['sweetheart', 'arrogant asshole'], stats: { yearning: 1 } },
    'grumpy sunshine': { tropeBoosts: ['grumpy sunshine', 'slow burn', 'forced proximity'], boyfriendBoosts: ['cold grump', 'sweetheart'], stats: { yearning: 1 } },
    'sports romance': { tropeBoosts: ['sports romance', 'teammates', 'friends to lovers'], shelfBoosts: ['sports romance'], boyfriendBoosts: ['athlete with heart'], stats: { tension: 1 } },
    'dark romance': { tropeBoosts: ['dark romance', 'morally gray', 'touch her and die', 'obsession'], shelfBoosts: ['dark romance'], boyfriendBoosts: ['morally gray villain', 'mafia boss', 'stalker'], stats: { darkness: 2 } },
    romantasy: { tropeBoosts: ['fantasy romance', 'romantasy', 'magic', 'fated mates'], shelfBoosts: ['romantasy', 'fantasy romance'], boyfriendBoosts: ['tortured prince', 'morally gray villain'], stats: { yearning: 1 } },
    'age gap': { tropeBoosts: ['age gap', 'forbidden romance'], boyfriendBoosts: ['emotionally unavailable man', 'mafia boss'], stats: { tension: 1 } },
    'forbidden romance': { tropeBoosts: ['forbidden romance', 'forbidden love', 'angst'], boyfriendBoosts: ['morally gray villain', 'mafia boss', 'arrogant asshole'], stats: { tension: 1, spice: 1 } },
    'workplace romance': { tropeBoosts: ['workplace romance', 'office romance', 'enemies to lovers'], boyfriendBoosts: ['arrogant asshole', 'academic rival'], stats: { tension: 1 } },
	    'small town romance': { tropeBoosts: ['small town romance', 'friends to lovers', 'healing'], shelfBoosts: ['small town romance'], boyfriendBoosts: ['sweetheart', 'athlete with heart'], stats: { yearning: 1 } }
	  };

	  var readerTypePrimaryTrope = {
	    slow_burn_girlie: 'slow burn',
	    tension_addict: 'enemies to lovers',
	    fake_dating_fanatic: 'fake dating',
	    dark_romance_girlie: 'dark romance',
	    fantasy_girlie: 'romantasy',
	    jersey_chaser: 'sports romance',
	    sweet_romance_devotee: 'friends to lovers',
	    chaos_reader: 'why choose',
	    romance_reader: 'found family'
	  };

	  var tropeLaneAliases = {
	    'friends to lovers': ['friends to lovers', 'best friends to lovers', 'childhood friends', 'friends-to-lovers', 'teammates', 'small town romance', 'found family', 'healing', 'comfort', 'single dad romance'],
	    'enemies to lovers': ['enemies to lovers', 'rivals to lovers', 'hate to love', 'banter'],
	    'slow burn': ['slow burn', 'yearning', 'he falls first'],
	    'fake dating': ['fake dating', 'fake relationship', 'marriage of convenience'],
	    'dark romance': ['dark romance', 'morally gray', 'touch her and die', 'obsession', 'stalker', 'villain gets the girl'],
	    romantasy: ['romantasy', 'fantasy romance', 'fantasy', 'fated mates', 'paranormal romance'],
	    'sports romance': ['sports romance', 'hockey romance', 'baseball romance', 'football romance', 'teammates'],
	    'why choose': ['why choose', 'reverse harem', 'poly romance'],
	    'found family': ['found family', 'healing', 'comfort', 'small town romance']
	  };

	  var tropeLaneConflicts = {
	    'friends to lovers': ['dark romance', 'mafia romance', 'touch her and die', 'stalker', 'obsession', 'bully romance', 'morally gray', 'villain gets the girl', 'captor x captive']
	  };

  var fictionalManProfiles = {
    academic_rival: {
      body: 'you want sharp banter, matched intelligence, and chemistry that feels one insult away from making out in a library aisle.',
      boyfriendBoosts: ['academic rival'],
      tropeBoosts: ['enemies to lovers', 'academic rivals', 'banter', 'rivals to lovers']
    },
    arrogant_asshole: {
      body: 'you clearly like confidence with a superiority complex and the kind of man who needs to be dragged into emotional competence.',
      boyfriendBoosts: ['arrogant asshole'],
      tropeBoosts: ['enemies to lovers', 'workplace romance', 'billionaire', 'marriage of convenience']
    },
    cold_grump: {
      body: 'you fall for emotional frostbite, one softened look, and men who act impossible until they are suddenly devoted.',
      boyfriendBoosts: ['cold grump'],
      tropeBoosts: ['grumpy sunshine', 'slow burn', 'yearning', 'forced proximity']
    },
    bully: {
      body: 'you like tension with teeth, a little humiliation, and a love story that has to claw its way into tenderness.',
      boyfriendBoosts: ['bully'],
      tropeBoosts: ['bully romance', 'enemies to lovers', 'dark romance']
    },
    emotionally_unavailable_man: {
      body: 'you are weak for emotional repression, damaged eye contact, and men who act fine right up until they absolutely are not.',
      boyfriendBoosts: ['emotionally unavailable man'],
      tropeBoosts: ['angst', 'yearning', 'second chance', 'slow burn']
    },
    obsessive_protector: {
      body: 'you like loyalty with a dangerous edge and protection that crosses the line into possessive before anyone can stop it.',
      boyfriendBoosts: ['obsessive protector'],
      tropeBoosts: ['touch her and die', 'protective hero', 'possessive', 'obsession']
    },
    athlete_with_heart: {
      body: 'you want charm, devotion, and a man who feels easy to love until he blindsides you by being sincerely gone for you.',
      boyfriendBoosts: ['athlete with heart'],
      tropeBoosts: ['sports romance', 'friends to lovers', 'teammates', 'golden retriever']
    },
    morally_gray_villain: {
      body: 'you like menace, charisma, and men who are one bad choice away from disaster but still somehow feel inevitable.',
      boyfriendBoosts: ['morally gray villain'],
      tropeBoosts: ['morally gray', 'villain gets the girl', 'dark romance', 'enemies to lovers']
    },
    mafia_boss: {
      body: 'you want power, danger, and a man whose love language is making a problem disappear before breakfast.',
      boyfriendBoosts: ['mafia boss'],
      tropeBoosts: ['mafia romance', 'forbidden romance', 'possessive', 'dark romance']
    },
    tortured_prince: {
      body: 'you like royalty with trauma, impossible choices, and the kind of devotion that feels doomed before it feels safe.',
      boyfriendBoosts: ['tortured prince'],
      tropeBoosts: ['prince', 'fantasy romance', 'yearning', 'forbidden romance']
    },
    stalker: {
      body: 'you are not here for normal. you want fixation, danger, and a man who would absolutely cross every line to keep you.',
      boyfriendBoosts: ['stalker'],
      tropeBoosts: ['stalker', 'obsession', 'dark romance', 'touch her and die']
    },
    sweetheart: {
      body: 'you want tenderness first, devotion second, and a love story that still hurts a little even while it feels safe.',
      boyfriendBoosts: ['sweetheart'],
      tropeBoosts: ['friends to lovers', 'caretaking', 'healing', 'small town romance']
    }
  };

  var emojiMap = {
    soft_romantic: '🎀',
    dangerous_pretty: '🥀',
    cozy_reader: '☕️',
    spicy_glam: '🍒',
    stormy_broody: '🌙'
  };

  var moduleEmojiMap = {
    soft_romantic: ['🎀', '☁️', '📚', '💌'],
    dangerous_pretty: ['🥀', '🗡️', '🔥', '🖤'],
    cozy_reader: ['☕️', '🧣', '📚', '🍂'],
    spicy_glam: ['🍒', '💋', '🌶️', '✨'],
    stormy_broody: ['🌙', '🌊', '📖', '🖤']
  };

  var shelfCopy = {
    dark_hearts: 'your taste wants black hearts, sharp edges, and devotion that feels a little dangerous.',
    obsession_red: 'you keep choosing books with heat, danger, and chemistry intense enough to leave a mark.',
    rose_ribbon: 'your taste wants pretty details, soft yearning, and a romance that blushes before it confesses.',
    stormy_blue: 'you lean toward broody tension, midnight longing, and books that feel like weather.',
    pearl_white: 'you like tenderness, comfort, and stories that still know how to quietly undo you.',
    royal_violet: 'you want something lush, dramatic, and just a little enchanted around the edges.'
  };

  var fallingEmotions = {
    cozy: 'reader breakdown: cozy, curated, and only a little emotionally unsafe.',
    spicy: 'reader breakdown: chemistry first, consequences eventually.',
    dark: 'reader breakdown: danger, devotion, and one questionable choice.',
    slowburn: 'reader breakdown: yearning at a simmer until it finally ruins you.',
    surprise: 'reader breakdown: letting the shelf choose the plot today.',
    slow_ache: 'reader breakdown: stomach-drop longing and slow-motion devastation.',
    messy_obsession: 'reader breakdown: pulse-up obsession and one very bad decision.',
    comfort_devotion: 'reader breakdown: safety first, then the ache sneaks in.',
    chaos_chemistry: 'reader breakdown: adrenaline, tension, and one perfect argument.',
    dark_dangerous: 'reader breakdown: dangerous attraction with zero self-preservation.'
  };

  var tokenLabels = {
    theme: {
      dark_hearts: 'annotated in black tabs',
      obsession_red: 'golden hour',
      rose_ribbon: 'rose garden',
      stormy_blue: 'midnight library',
      pearl_white: 'sage & honey',
      royal_violet: 'underlined in velvet ink'
    },
    craving: {
      cozy: 'cozy & sweet',
      spicy: 'spicy chaos',
      dark: 'dark & intense',
      slowburn: 'slow burn only',
      surprise: 'surprise me',
      slow_ache: 'slow burn',
      messy_obsession: 'obsession / stalker',
      comfort_devotion: 'friends to lovers',
      chaos_chemistry: 'enemies to lovers',
      dark_dangerous: 'touch her and die'
    },
    payoff: {
      long_tension: 'long tension',
      emotional_devastation: 'emotional devastation',
      soft_after_storm: 'softness after the storm',
      plot_addiction: 'plot that eats your brain',
      illegal_chemistry: 'chemistry so sharp it hurts'
    },
    favorite_trope: {
      'enemies to lovers': 'enemies to lovers',
      'second chance': 'second chance',
      'forced proximity': 'forced proximity',
      'fake dating': 'fake dating',
      'grumpy sunshine': 'grumpy x sunshine',
      'sports romance': 'sports romance',
      'dark romance': 'dark romance',
      romantasy: 'fantasy romance',
      'age gap': 'age gap',
      'forbidden romance': 'forbidden love',
      'workplace romance': 'workplace romance',
      'small town romance': 'small town romance'
    },
    spice_dial: {
      soft_open_door: '🌶 • barely sweet',
      some_heat: '🌶🌶 • warm',
      balanced: '🌶🌶🌶 • medium',
      high_spice: '🌶🌶🌶🌶 • hot',
      wreck_me: '🌶🌶🌶🌶🌶 • burn it'
    },
    fictional_man: {
      academic_rival: 'academic rival',
      arrogant_asshole: 'arrogant asshole',
      cold_grump: 'cold grump',
      bully: 'bully',
      emotionally_unavailable_man: 'emotionally unavailable man',
      obsessive_protector: 'obsessive protector',
      athlete_with_heart: 'athlete with heart',
      morally_gray_villain: 'morally gray villain',
      mafia_boss: 'mafia boss',
      tortured_prince: 'tortured prince',
      stalker: 'stalker',
      sweetheart: 'sweetheart'
    }
  };

  var favoriteBookMap = {};
  var favoriteBookQuotes = {};
  var quoteLibraryHandles = {};
  var quoteLibraryKeys = {};

  quoteLibrary.forEach(function(entry){
    entry = normalizeQuoteData(entry);
    var handle = String(entry && entry.handle || '').trim();
    if (handle){
      quoteLibraryHandles[handle] = true;
    }
    quoteLibraryKeys[getSavedQuoteKey(entry)] = true;
    if (!handle) return;
    if (!favoriteBookQuotes[handle]){
      favoriteBookQuotes[handle] = [];
    }
    favoriteBookQuotes[handle].push(entry);
  });

  answerButtons.forEach(function(button){
    var question = button.getAttribute('data-mfy-answer');
    var value = button.getAttribute('data-value');
    if (!answerGroups[question]){
      answerGroups[question] = {};
    }
    answerGroups[question][value] = button;

    button.addEventListener('click', function(){
      var nextStep = Math.min(findNextStepIndex(question) + 1, questionsOrder.length - 1);
      profile[question] = question === 'favorite_trope' ? normalize(value) : value;
      if (question === 'spice_dial'){
        saveSharedSpiceProfile(Math.max(spiceDialValues.indexOf(value) + 1, 1), 'made-for-you-answer');
      }
      syncQuizDerivedProfile();
      syncDerivedBoyfriendType();
      saveProfile(profile);
      syncAddonDrafts();
      syncAnswerUI();
      if (isProfileComplete()){
        finishQuiz();
        return;
      }

      currentStep = nextStep;
      syncStepUI();
      renderMadeForYou();
    });
  });

  if (backBtn){
    backBtn.addEventListener('click', function(){
      if (currentStep <= 0){
        return;
      }
      currentStep -= 1;
      syncStepUI();
    });
  }

  if (finishBtn){
    finishBtn.addEventListener('click', finishQuiz);
  }

  document.addEventListener('click', function(event){
    var target = event.target && event.target.closest ? event.target.closest('#sssMadeForYouFinish') : null;
    if (!target || !root.contains(target)){
      return;
    }

    event.preventDefault();
    finishQuiz();
  }, true);

  root.addEventListener('click', function(event){
    var target = event.target && event.target.closest ? event.target.closest('#sssMadeForYouFinish') : null;
    if (!target || !root.contains(target)){
      return;
    }

    event.preventDefault();
    finishQuiz();
  });

  if (resetBtn){
    resetBtn.addEventListener('click', function(){
      resetMadeForYou();
    });
  }

  if (resetResultsBtn){
    resetResultsBtn.addEventListener('click', function(){
      resetMadeForYou();
    });
  }

  if (nextResultBtn){
    nextResultBtn.addEventListener('click', function(){
      if (isDashboardView){
        return;
      }
      if (currentResultStep >= resultPanels.length - 1){
        isDashboardView = true;
        profile.dashboard_built = true;
        saveProfile(profile);
        syncResultStepUI();
        return;
      }
      currentResultStep += 1;
      syncResultStepUI();
    });
  }

  if (refreshRecsBtn){
    refreshRecsBtn.addEventListener('click', function(){
      renderRecommendations(Object.keys(profile).filter(function(key){ return !!profile[key]; }).length);
      renderFictionalBfCard();
    });
  }

  bookshelfTabButtons.forEach(function(button){
    button.addEventListener('click', function(){
      dashboardBookshelfTab = button.getAttribute('data-mfy-bookshelf-tab') || 'reading';
      renderDashboardBookshelf();
    });
  });

  if (saveNoteBtn && noteInput && notesList){
    saveNoteBtn.addEventListener('click', function(){
      var note = String(noteInput.value || '').trim();
      if (!note) return;
      var item = document.createElement('div');
      item.className = 'sss-mfy__noteItem';
      item.innerHTML = '<p></p><span>general · just now</span>';
      item.querySelector('p').textContent = note;
      notesList.prepend(item);
      noteInput.value = '';
    });
  }

  if (seeFullBreakdownBtn){
    seeFullBreakdownBtn.addEventListener('click', function(){
      syncProfileFromQuizUI();
      syncDerivedBoyfriendType();
      if (!isProfileComplete() || !hasRequiredPersonalLayers()){
        syncStepUI();
        syncAddonUI();
        return;
      }
      renderMadeForYou();
      showFullBreakdown();
    });
  }

  addonButtons.forEach(function(button){
    button.addEventListener('click', function(){
      var key = button.getAttribute('data-mfy-addon');
      toggleAddon(key);
    });
  });

  addonCloseButtons.forEach(function(button){
    button.addEventListener('click', function(){
      var key = button.getAttribute('data-mfy-close');
      closeAddon(key);
    });
  });

  hardNoButtons.forEach(function(button){
    button.addEventListener('click', function(){
      var value = button.getAttribute('data-mfy-hard-no');
      if (!Array.isArray(draftHardNos)){
        draftHardNos = [];
      }
      if (draftHardNos.indexOf(value) > -1){
        draftHardNos = draftHardNos.filter(function(item){ return item !== value; });
      } else {
        draftHardNos.push(value);
      }
      profile.hard_nos = draftHardNos.slice();
      saveProfile(profile);
      syncAddonUI();
      renderMadeForYou();
    });
  });

  if (saveHardNosBtn){
    saveHardNosBtn.addEventListener('click', function(){
      profile.hard_nos = draftHardNos.slice();
      saveProfile(profile);
      syncAddonUI();
      renderMadeForYou();
      closeAddon('hard_nos');
    });
  }

  if (manDialInput){
    manDialInput.addEventListener('input', function(){
      draftManDial = spiceDialValues[Number(manDialInput.value || 0)] || 'soft_open_door';
      syncAddonUI();
    });
    manDialInput.addEventListener('change', function(){
      saveManDialLayer(true);
    });
  }

  manDialChoices.forEach(function(button){
    button.addEventListener('click', function(){
      draftManDial = button.getAttribute('data-mfy-dial-choice') || 'soft_open_door';
      saveManDialLayer(true);
    });
  });

  favoriteTropeButtons.forEach(function(button){
    button.addEventListener('click', function(){
      draftFavoriteTrope = normalize(button.getAttribute('data-mfy-favorite-trope') || '');
      saveFavoriteTropeLayer(true);
    });
  });

  if (saveManDialBtn){
    saveManDialBtn.addEventListener('click', function(){
      saveManDialLayer(true);
    });
  }

  if (saveFavoriteTropeBtn){
    saveFavoriteTropeBtn.addEventListener('click', function(){
      saveFavoriteTropeLayer(true);
    });
  }

  if (favoriteBookSearchInput){
    favoriteBookSearchInput.addEventListener('input', function(){
      renderFavoriteBookResults(favoriteBookSearchInput.value || '');
      syncAddonUI();
    });
  }

  if (saveFavoriteBookBtn){
    saveFavoriteBookBtn.addEventListener('click', function(){
      saveFavoriteBookLayer(true);
    });
  }

  root.__refreshMadeForYou = function(){
    syncAddonUI();
    renderMadeForYou();
  };

  function findMadeForYouBookFromCard(card){
    if (!card) return null;
    var handle = normalize(card.dataset.handle || '');
    var title = normalize(card.dataset.title || '');

    return books.find(function(book){
      return (handle && normalize(book.handle || book.book_handle) === handle) ||
        (title && normalize(book.title || book.book_title) === title);
    }) || null;
  }

  function openMadeForYouDashboardBook(card){
    var book = findMadeForYouBookFromCard(card);
    if (!book) return false;

    if (typeof window.sssOpenBookModal === 'function' && window.sssOpenBookModal(book, card)){
      return true;
    }

    var source = Array.from(root.querySelectorAll('.sss-mfy__sourceGrid .sss-lib__book[data-title]')).find(function(sourceCard){
      return getBookStatusKey(sourceCard.dataset || {}) === getBookStatusKey(book);
    }) || null;

    if (source){
      source.click();
      return true;
    }

    return false;
  }

  root.addEventListener('click', function(event){
    var card = closestFromTarget(event.target, '[data-mfy-dashboard-book]');
    if (!card || !root.contains(card)) return;
    if (openMadeForYouDashboardBook(card)){
      event.preventDefault();
      event.stopPropagation();
    }
  });

  root.addEventListener('keydown', function(event){
    if (event.key !== 'Enter' && event.key !== ' ') return;
    var card = closestFromTarget(event.target, '[data-mfy-dashboard-book]');
    if (!card || !root.contains(card)) return;
    if (openMadeForYouDashboardBook(card)){
      event.preventDefault();
      event.stopPropagation();
    }
  });

  document.addEventListener('sss:quote-saves-updated', function(){
    if (!root || !root.isConnected) return;
    renderSavedQuotes();
    syncResultStepUI();
  });

  syncAnswerUI();
  syncStepUI();
	  populateFavoriteBookSelect();
	  syncAddonUI();
	  renderMadeForYou();
	  refreshRemoteProfile();

	  var shouldOpenResultsFromUrl = window.location.search.indexOf('mfy_results=1') > -1;
	  if (isProfileComplete()){
	    showResults(!!profile.dashboard_built && !shouldOpenResultsFromUrl);
	    saveProfile(profile);
	  }

	  function loadProfile(){
	    var accountProfile = getSnapshotProfile();
	    var localProfile = {};
	    try {
	      localProfile = JSON.parse(storageGet(storageKey)) || {};
	    } catch(e) {
	      localProfile = {};
	    }
	    if (!isCurrentMfyProfile(localProfile)){
	      localProfile = {};
	      storageRemove(storageKey);
	    }
	    cleanupLegacyMfyStorage('sssMadeForYouProfile');
	    if (!isCurrentMfyProfile(accountProfile)){
	      accountProfile = {};
	    }

	    if (isNewerProfile(accountProfile, localProfile)){
	      storageSet(storageKey, JSON.stringify(accountProfile));
	      return accountProfile;
	    }

	    return localProfile;
	  }

	  function saveProfile(nextProfile){
	    var savedProfile = nextProfile || {};
	    if (Object.keys(savedProfile).length){
	      savedProfile.mfy_profile_version = profileVersion;
	    }
	    savedProfile.updatedAt = new Date().toISOString();
	    storageSet(storageKey, JSON.stringify(savedProfile));
	    cleanupLegacyMfyStorage('sssMadeForYouProfile');
	    saveSharedTasteProfile(savedProfile);
	    queueRemoteProfileSave(savedProfile);
	  }

	  function getReaderAccountApi(){
	    var directApi = typeof BBBReaderAccountApi !== 'undefined' ? BBBReaderAccountApi : window.BBBReaderAccountApi;
	    var siteData = typeof BBBSiteData !== 'undefined' ? BBBSiteData : window.BBBSiteData;
	    var api = directApi || (siteData && siteData.readerAccount) || {};
	    return api && api.profileEndpoint && api.nonce ? api : null;
	  }

	  function getMfyProfileVersion(){
	    var directApi = typeof BBBReaderAccountApi !== 'undefined' ? BBBReaderAccountApi : window.BBBReaderAccountApi;
	    var siteData = typeof BBBSiteData !== 'undefined' ? BBBSiteData : window.BBBSiteData;
	    var api = directApi || (siteData && siteData.readerAccount) || {};
	    return String((api && api.profileVersion) || 'mfy-2026-06-11-reader-types');
	  }

	  function getMfyAccountKey(){
	    var directApi = typeof BBBReaderAccountApi !== 'undefined' ? BBBReaderAccountApi : window.BBBReaderAccountApi;
	    var siteData = typeof BBBSiteData !== 'undefined' ? BBBSiteData : window.BBBSiteData;
	    var api = directApi || (siteData && siteData.readerAccount) || {};
	    var snapshot = getAccountSnapshot();
	    var rootKey = root && root.dataset ? root.dataset.mfyAccountKey : '';
	    return String(rootKey || api.accountKey || snapshot.accountKey || '').trim();
	  }

	  function isCurrentMfyProfile(candidate){
	    if (!candidate || typeof candidate !== 'object' || !Object.keys(candidate).length) return false;
	    return String(candidate.mfy_profile_version || candidate.profile_version || '') === profileVersion;
	  }

	  function getSnapshotProfile(){
	    var snapshot = getAccountSnapshot();
	    var snapshotProfile = snapshot && snapshot.madeForYouProfile && typeof snapshot.madeForYouProfile === 'object'
	      ? snapshot.madeForYouProfile
	      : {};
	    return isCurrentMfyProfile(snapshotProfile) ? snapshotProfile : {};
	  }

	  function profileTime(profile){
	    var raw = profile && (profile.updatedAt || profile.updated_at || '');
	    var time = raw ? Date.parse(String(raw)) : 0;
	    return Number.isFinite(time) ? time : 0;
	  }

	  function isNewerProfile(candidate, current){
	    if (!candidate || typeof candidate !== 'object' || !Object.keys(candidate).length) return false;
	    if (!current || typeof current !== 'object' || !Object.keys(current).length) return true;
	    return profileTime(candidate) > profileTime(current);
	  }

	  var remoteProfileSaveTimer = null;
	  function queueRemoteProfileSave(nextProfile){
	    var api = getReaderAccountApi();
	    if (!api) return;
	    window.clearTimeout(remoteProfileSaveTimer);
	    remoteProfileSaveTimer = window.setTimeout(function(){
	      window.fetch(api.profileEndpoint, {
	        method: 'POST',
	        credentials: 'same-origin',
	        headers: {
	          'Content-Type': 'application/json',
	          'X-WP-Nonce': api.nonce
	        },
	        body: JSON.stringify({ profile: nextProfile || {} })
	      }).catch(function(error){
	        console.log('Made For You profile sync failed', error);
	      });
	    }, 450);
	  }

	  function refreshRemoteProfile(){
	    var api = getReaderAccountApi();
	    if (!api) return;
	    window.fetch(api.profileEndpoint, {
	      method: 'GET',
	      credentials: 'same-origin',
	      headers: { 'X-WP-Nonce': api.nonce }
	    }).then(function(response){
	      if (!response.ok) throw new Error('profile fetch failed');
	      return response.json();
	    }).then(function(payload){
	      var remoteProfile = payload && payload.profile && typeof payload.profile === 'object' ? payload.profile : {};
	      if (!isCurrentMfyProfile(remoteProfile)) return;
	      if (!isNewerProfile(remoteProfile, profile)) return;
	      profile = remoteProfile;
	      storageSet(storageKey, JSON.stringify(profile));
	      syncAnswerUI();
	      syncStepUI();
	      syncAddonDrafts();
	      syncAddonUI();
	      renderMadeForYou();
	      if (isProfileComplete()){
	        showResults(!!profile.dashboard_built);
	        saveProfile(profile);
	      }
	    }).catch(function(error){
	      console.log('Made For You profile fetch failed', error);
	    });
	  }

	  function loadSharedTasteProfile(){
    try {
      return JSON.parse(storageGet(scopedMfyStorageKey('bbbReaderTasteProfile'))) || {};
    } catch(e) {
      return {};
    }
  }

  function saveSharedTasteProfile(nextProfile){
    try {
      var existing = loadSharedTasteProfile();
      var persona = getPersonaProfile();
      var sharedProfile = Object.assign({}, existing, {
        favorite_trope: normalize(nextProfile.favorite_trope || existing.favorite_trope || ''),
        dashboard_theme: persona && persona.theme ? persona.theme.name : existing.dashboard_theme || '',
        reader_type: persona && persona.key ? persona.key : existing.reader_type || '',
        reader_type_prior: nextProfile.reader_type_prior || existing.reader_type_prior || '',
        spice_profile: Number(nextProfile.spice_profile || existing.spice_profile || 0),
        spice_dial: nextProfile.spice_dial || existing.spice_dial || '',
        fictional_boyfriend: nextProfile.fictional_boyfriend || existing.fictional_boyfriend || null
      });
      storageSet(scopedMfyStorageKey('bbbReaderTasteProfile'), JSON.stringify(sharedProfile));
      cleanupLegacyMfyStorage('bbbReaderTasteProfile');
    } catch(e) {}
  }

  function getStoredSpiceProfileLevel(){
    var level = Number(storageGet(scopedMfyStorageKey('bbbReaderSpiceProfile')) || (!accountScopedKey ? storageGet('bbbReaderSpiceProfile') : '') || 0);
    return level >= 1 && level <= 5 ? level : 0;
  }

  function saveSharedSpiceProfile(level, source){
    level = Number(level || 0);
    if (!(level >= 1 && level <= 5)) return;

    storageSet(scopedMfyStorageKey('bbbReaderSpiceProfile'), String(level));
    storageSet('bbbReaderSpiceProfile', String(level));
    profile.spice_profile = level;
    profile.spice_dial = storedSpiceProfileMap[level] || profile.spice_dial || 'balanced';

    try {
      var existing = loadSharedTasteProfile();
      existing.spice_profile = level;
      existing.spice_dial = profile.spice_dial;
      storageSet(scopedMfyStorageKey('bbbReaderTasteProfile'), JSON.stringify(existing));
      storageSet('bbbReaderTasteProfile', JSON.stringify(existing));
    } catch(e) {}

    window.dispatchEvent(new CustomEvent('bbb:spice-profile-changed', {
      detail: { level: level, source: source || 'made-for-you' }
    }));

    var api = window.BBBReaderAccountApi || (window.BBBSiteData && window.BBBSiteData.readerAccount) || {};
    if (api && api.spiceEndpoint && api.nonce) {
      window.fetch(api.spiceEndpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': api.nonce
        },
        body: JSON.stringify({ level: level })
      }).catch(function(error){
        console.log('Reader spice profile sync failed', error);
      });
    }
  }

  function syncStoredSpiceProfile(){
    var level = getStoredSpiceProfileLevel();
    if (!level || profile.spice_profile === level) return false;
    if (!profile.dashboard_built && !profile.spice_dial) return false;

    profile.spice_profile = level;
    profile.spice_dial = storedSpiceProfileMap[level] || profile.spice_dial || 'balanced';
    saveProfile(profile);
    return true;
  }

  window.addEventListener('bbb:spice-profile-changed', function(event){
    var level = Number(event.detail && event.detail.level || 0);
    if (!(level >= 1 && level <= 5) || profile.spice_profile === level) return;
    profile.spice_profile = level;
    profile.spice_dial = storedSpiceProfileMap[level] || profile.spice_dial || 'balanced';
    saveProfile(profile);
    syncAnswerUI();
    syncAddonDrafts();
    syncAddonUI();
    renderMadeForYou();
  });

  function syncDerivedBoyfriendType(){
    var nextType = deriveBoyfriendTypeFromQuiz(profile);
    if (nextType){
      profile.fictional_man = nextType;
    }
  }

  function syncAnswerUI(){
    Object.keys(answerGroups).forEach(function(question){
      Object.keys(answerGroups[question]).forEach(function(value){
        answerGroups[question][value].classList.toggle('is-active', profile[question] === value);
      });
    });
  }

  function getInitialStep(){
    var firstUnanswered = questionsOrder.findIndex(function(question){
      return !profile[question];
    });

    return firstUnanswered === -1 ? questionsOrder.length - 1 : firstUnanswered;
  }

  function isProfileComplete(){
    syncProfileFromQuizUI();
    return questionsOrder.every(function(question){
      if (question === 'name' && nameInput && String(nameInput.value || '').trim()){
        return true;
      }
      if (root.querySelector('[data-mfy-answer="' + question + '"].is-active')){
        return true;
      }
      return !!String(profile[question] || '').trim();
    });
  }

  function syncProfileFromQuizUI(){
    if (nameInput && String(nameInput.value || '').trim()){
      profile.name = String(nameInput.value || '').trim();
    }

    root.querySelectorAll('[data-mfy-answer].is-active').forEach(function(button){
      var question = button.getAttribute('data-mfy-answer');
      var value = button.getAttribute('data-value');
      if (question && value){
        profile[question] = question === 'favorite_trope' ? normalize(value) : value;
      }
    });
    syncQuizDerivedProfile();
  }

  function finishQuiz(){
    syncProfileFromQuizUI();
    syncQuizDerivedProfile();
    syncDerivedBoyfriendType();
    if (!isProfileComplete()){
      syncStepUI();
      return;
    }

    delete profile.dashboard_built;
    saveProfile(profile);
    currentStep = questionsOrder.length - 1;
    syncStepUI();
    showFullBreakdown();
    renderMadeForYou();
  }

  function findNextStepIndex(question){
    return questionsOrder.indexOf(question);
  }

  function syncStepUI(){
    root.classList.toggle('is-first-step', currentStep === 0);

    questionEls.forEach(function(questionEl, index){
      var questionKey = questionEl.getAttribute('data-mfy-question');
      questionEl.classList.toggle('is-answered', !!profile[questionKey]);
      questionEl.classList.toggle('is-active', index === currentStep);
    });

    if (stepCount){
      stepCount.textContent = 'question ' + (currentStep + 1) + ' of ' + questionsOrder.length;
    }

    if (progressFill){
      progressFill.style.width = (((currentStep + 1) / questionsOrder.length) * 100) + '%';
    }

    if (backBtn){
      backBtn.disabled = currentStep === 0;
      backBtn.hidden = currentStep === 0;
    }

    if (finishBtn){
      finishBtn.hidden = true;
    }

    if (nameContinueBtn){
      nameContinueBtn.disabled = !String(profile.name || '').trim();
    }

    if (continueNote){
      continueNote.textContent = questionsOrder[currentStep] === 'name' ? 'enter your name to keep going' : 'tap an answer to keep going';
    }

    if (trackEl){
      trackEl.style.transform = 'translateX(-' + (currentStep * 100) + '%)';
    }

    if (isProfileComplete() && currentStep === questionsOrder.length - 1 && !root.classList.contains('is-complete') && !isPersonalLayerView){
      window.setTimeout(function(){
        if (isProfileComplete() && currentStep === questionsOrder.length - 1 && !root.classList.contains('is-complete') && !isPersonalLayerView){
          finishQuiz();
        }
      }, 0);
    } else if (!isProfileComplete()){
      hideResults();
    }
  }

  function showPersonalLayers(){
    isPersonalLayerView = true;
    isDashboardView = false;
    root.classList.add('is-complete', 'is-layering');
    if (resultsEl){
      resultsEl.hidden = false;
      resultsEl.classList.remove('is-visible', 'is-dashboard');
    }
    if (customizeEl){
      customizeEl.hidden = false;
      window.requestAnimationFrame(function(){
        customizeEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }
    syncAddonUI();
    syncResultStepUI();
  }

  function showFullBreakdown(){
    isPersonalLayerView = false;
    root.classList.remove('is-layering');
    profile.dashboard_built = true;
    saveProfile(profile);
    showResults(true);
  }

  function showResults(preserveDashboard){
    isPersonalLayerView = false;
    root.classList.remove('is-layering');
    root.classList.add('is-complete');
    if (resultsEl){
      currentResultStep = preserveDashboard ? (resultPanels.length - 1) : 0;
      isDashboardView = !!preserveDashboard;
      resultsEl.hidden = false;
      resultsEl.classList.remove('is-visible');
      window.requestAnimationFrame(function(){
        window.requestAnimationFrame(function(){
          resultsEl.classList.add('is-visible');
          resultsEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
          syncResultStepUI();
        });
      });
    }
  }

  function hideResults(){
    isPersonalLayerView = false;
    root.classList.remove('is-complete', 'is-layering');
    if (resultsEl){
      resultsEl.hidden = true;
      resultsEl.classList.remove('is-visible');
      resultsEl.classList.remove('is-dashboard');
    }
  }

  function syncResultStepUI(){
    var canShowDashboardExtras = isDashboardView && !!profile.dashboard_built;
    var canShowPersonalLayers = isPersonalLayerView;
    var canShowReadShelf = canShowDashboardExtras;

    if (!canShowPersonalLayers && Array.isArray(profile.open_addons) && profile.open_addons.length){
      profile.open_addons = [];
      saveProfile(profile);
    }

    if (resultsEl){
      resultsEl.classList.toggle('is-dashboard', isDashboardView);
    }
    if (customizeEl){
      customizeEl.hidden = !canShowPersonalLayers;
    }
    if (quoteSpotlightEl){
      quoteSpotlightEl.hidden = true;
    }
    if (savedQuotesEl){
      setSlowRevealState(savedQuotesEl, canShowDashboardExtras && getVisibleSavedQuotes().length > 0);
    }
    if (readShelfEl){
      setSlowRevealState(readShelfEl, canShowReadShelf);
    }

    resultPanels.forEach(function(panel, index){
      var active = isDashboardView ? true : index === currentResultStep;
      panel.classList.toggle('is-active', active);
      panel.classList.toggle('is-complete', isDashboardView ? true : index < currentResultStep);
    });

    if (resultsMeta){
      resultsMeta.textContent = isDashboardView
        ? ''
        : ('step ' + (currentResultStep + 1) + ' of ' + resultPanels.length);
    }

    if (nextResultBtn){
      nextResultBtn.textContent = currentResultStep >= resultPanels.length - 1 ? 'create my dashboard' : 'next';
      nextResultBtn.hidden = isDashboardView;
      nextResultBtn.disabled = isDashboardView;
    }
  }

  function resetMadeForYou(){
    profile = {};
    storageRemove(storageKey);
    storageRemove(scopedMfyStorageKey('bbbReaderTasteProfile'));
    storageRemove(scopedMfyStorageKey('bbbReaderTypeState'));
    cleanupLegacyMfyStorage('bbbReaderTasteProfile');
    cleanupLegacyMfyStorage('bbbReaderTypeState');
    if (nameInput){
      nameInput.value = '';
    }
    currentStep = 0;
    currentResultStep = 0;
    isDashboardView = false;
    isPersonalLayerView = false;
    root.classList.remove('is-complete', 'is-layering');
    if (resultsEl){
      resultsEl.hidden = true;
      resultsEl.classList.remove('is-visible', 'is-dashboard');
    }
    if (customizeEl){
      customizeEl.hidden = true;
    }
    syncAnswerUI();
    syncStepUI();
    syncResultStepUI();
    syncAddonUI();
    renderMadeForYou();
    if (trackEl){
      trackEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function manualVibeScore(book, sourceHandle){
    if (!book || !book.handle || !sourceHandle) return 0;

    var targetHandle = normalize(book.handle);
    var sourceKey = normalize(sourceHandle);
    var related = (book.most_like || book.mostLike || []).map(normalize);
    var sourceBook = books.find(function(item){
      return normalize(item && item.handle) === sourceKey;
    });
    var sourceRelated = sourceBook ? (sourceBook.most_like || sourceBook.mostLike || []).map(normalize) : [];

    if (sourceRelated.indexOf(targetHandle) > -1) return 18;
    if (related.indexOf(sourceKey) > -1) return 15;

    return 0;
  }

  function scoreBook(book){
    var score = 0;
    var theme = getThemeProfile();
    var craving = cravingProfiles[profile.craving];
    var payoff = payoffProfiles[profile.payoff];
    var favoriteTrope = favoriteTropeProfiles[profile.favorite_trope];
    var man = fictionalManProfiles[profile.fictional_man];
    var bookTropes = (book.tropes || []).map(normalize);
    var shelfName = normalize(book.shelf);
    var boyfriendType = canonicalBoyfriendType(book.boyfriend_type);
    var status = getBookStatus({ handle: book.handle, title: book.title });
    var reactions = getBookReactions();
    var saved = getShelf().find(function(item){
      return getBookStatusKey(item) === getBookStatusKey(book);
    });

    if (status === 'read' || status === 'dnf') return -999;
    if (isSoftProfileIncompatibleBook(book)) return -999;

    if (craving){
      craving.tropeBoosts.forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 3;
      });
      craving.shelfBoosts.forEach(function(shelf){
        if (shelfName === normalize(shelf)) score += 3;
      });
      craving.boyfriendBoosts.forEach(function(type){
        if (boyfriendType === canonicalBoyfriendType(type)) score += 2;
      });
      score += statScore(book, craving.stats);
    }

    if (payoff){
      (payoff.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 2;
      });
      (payoff.shelfBoosts || []).forEach(function(shelf){
        if (shelfName === normalize(shelf)) score += 2;
      });
      score += statScore(book, payoff.stats);
    }

    if (favoriteTrope){
      (favoriteTrope.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 4;
      });
      (favoriteTrope.shelfBoosts || []).forEach(function(shelf){
        if (shelfName === normalize(shelf)) score += 3;
      });
      (favoriteTrope.boyfriendBoosts || []).forEach(function(type){
        if (boyfriendType === canonicalBoyfriendType(type)) score += 2;
      });
      score += statScore(book, favoriteTrope.stats);
    }

    if (man){
      (man.boyfriendBoosts || []).forEach(function(type){
        if (boyfriendType === canonicalBoyfriendType(type)) score += 3;
      });
      (man.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 2;
      });
    }

    if (theme.season === 'autumn' && (book.darkness || 0) >= 2) score += 1;
    if (theme.season === 'winter' && (book.yearning || 0) >= 2) score += 1;
    if (theme.season === 'summer' && (book.spice || 0) >= 3) score += 1;
    if (theme.season === 'spring' && (book.damage || 0) <= 2) score += 1;

    if (theme.key === 'dark_hearts' && ((book.darkness || 0) >= 2 || bookTropes.indexOf('morally gray') > -1 || bookTropes.indexOf('obsession') > -1)) score += 1;
    if (theme.key === 'obsession_red' && (((book.spice || 0) >= 2 && (book.tension || 0) >= 2) || bookTropes.indexOf('forbidden romance') > -1 || bookTropes.indexOf('obsession') > -1)) score += 1;
    if (theme.key === 'rose_ribbon' && (bookTropes.indexOf('slow burn') > -1 || bookTropes.indexOf('friends to lovers') > -1 || (book.yearning || 0) >= 2)) score += 1;
    if (theme.key === 'stormy_blue' && (boyfriendType === 'cold_grump' || boyfriendType === 'emotionally_unavailable_man' || boyfriendType === 'tortured_prince' || (book.yearning || 0) >= 2 || (book.tension || 0) >= 2)) score += 1;
    if (theme.key === 'pearl_white' && (bookTropes.indexOf('friends to lovers') > -1 || bookTropes.indexOf('healing') > -1 || bookTropes.indexOf('protective hero') > -1)) score += 1;
    if (theme.key === 'royal_violet' && (shelfName.indexOf('romantasy') > -1 || shelfName.indexOf('fantasy') > -1 || bookTropes.indexOf('villain gets the girl') > -1 || bookTropes.indexOf('magic') > -1)) score += 1;

    if (theme.emojiGroup === 'dangerous_pretty' && (book.darkness || 0) >= 2) score += 1;
    if (theme.emojiGroup === 'cozy_reader' && (book.yearning || 0) >= 2) score += 1;
    if (theme.emojiGroup === 'spicy_glam' && (book.spice || 0) >= 3) score += 1;
    if (theme.emojiGroup === 'stormy_broody' && (boyfriendType === 'cold_grump' || boyfriendType === 'emotionally_unavailable_man' || boyfriendType === 'tortured_prince')) score += 1;
    if (theme.emojiGroup === 'soft_romantic' && bookTropes.indexOf('slow burn') > -1) score += 1;

    if (profile.spice_dial){
      if (getSpiceDialReason(book)){
        score += 7;
      } else {
        var preferredSpice = spiceDialValues.indexOf(profile.spice_dial) + 1;
        var spiceDistance = Math.abs(Number(book.spice || 0) - preferredSpice);
        if (spiceDistance === 1) score += 2;
        if (spiceDistance >= 3) score -= 4;
      }
    }

    if (profile.favorite_trope && bookTropes.indexOf(normalize(profile.favorite_trope)) > -1){
      score += 8;
    }

    if (saved) score += 1;
    if (status === 'reading') score += 4;
    if (status === 'tbr') score += 2;
    if (Array.isArray(profile.hard_nos)){
      if (profile.hard_nos.indexOf('love triangle') > -1 && bookTropes.indexOf('love triangle') > -1) score -= 20;
      if (profile.hard_nos.indexOf('accidental pregnancy') > -1 && bookTropes.indexOf('accidental pregnancy') > -1) score -= 20;
      if (profile.hard_nos.indexOf('cheating') > -1 && bookTropes.indexOf('cheating') > -1) score -= 20;
      if (profile.hard_nos.indexOf('bully romance') > -1 && bookTropes.indexOf('bully romance') > -1) score -= 20;
      if (profile.hard_nos.indexOf('second chance') > -1 && bookTropes.indexOf('second chance') > -1) score -= 20;
      if (profile.hard_nos.indexOf('secret baby') > -1 && bookTropes.indexOf('secret baby') > -1) score -= 20;
      if (profile.hard_nos.indexOf('why choose') > -1 && bookTropes.indexOf('why choose') > -1) score -= 20;
      if (profile.hard_nos.indexOf('friends with benefits') > -1 && bookTropes.indexOf('friends with benefits') > -1) score -= 20;
      if (profile.hard_nos.indexOf('cliffhanger') > -1 && bookTropes.indexOf('cliffhanger') > -1) score -= 20;
      if (profile.hard_nos.indexOf('dark romance') > -1 && (bookTropes.indexOf('dark romance') > -1 || shelfName === 'dark romance')) score -= 20;
      if (profile.hard_nos.indexOf('instalove') > -1 && (bookTropes.indexOf('instalove') > -1 || bookTropes.indexOf('insta love') > -1)) score -= 20;
      if (profile.hard_nos.indexOf('long series') > -1 && bookTropes.indexOf('long series') > -1) score -= 20;
    }

    if (saved && status !== 'tbr' && status !== 'reading') score -= 1;

    Object.keys(reactions).forEach(function(key){
      var reaction = reactions[key];
      if (!reaction) return;

      var reactedBook = books.find(function(item){
        return getBookStatusKey(item) === key;
      });
      if (!reactedBook || reactedBook.handle === book.handle) return;

      var reactedTropes = (reactedBook.tropes || []).map(normalize);
      var sharedTropeCount = reactedTropes.filter(function(trope){
        return trope && bookTropes.indexOf(trope) > -1;
      }).length;
      var sameShelf = normalize(reactedBook.shelf) === shelfName;
      var sameBoyfriend = canonicalBoyfriendType(reactedBook.boyfriend_type) === boyfriendType;

      if (reaction === 'obsessed'){
        score += sharedTropeCount * 3;
        if (sameShelf) score += 3;
        if (sameBoyfriend) score += 2;
        score += manualVibeScore(book, reactedBook.handle);
      }

      if (reaction === 'liked_it'){
        score += sharedTropeCount * 1.5;
        if (sameShelf) score += 1;
        score += manualVibeScore(book, reactedBook.handle) / 2;
      }

      if (reaction === 'not_for_me'){
        score -= sharedTropeCount * 3;
        if (sameShelf) score -= 4;
        if (sameBoyfriend) score -= 2;
        score -= manualVibeScore(book, reactedBook.handle) / 2;
      }
    });

    score += getBookReaderTypeScore(book, getPersonaProfile());

    return score;
  }

  function scoreQuizOnlyBook(book){
    var score = 0;
    var theme = getThemeProfile();
    var craving = cravingProfiles[profile.craving];
    var payoff = payoffProfiles[profile.payoff];
    var favoriteTrope = favoriteTropeProfiles[profile.favorite_trope];
    var man = fictionalManProfiles[profile.fictional_man];
    var bookTropes = (book.tropes || []).map(normalize);
    var shelfName = normalize(book.shelf);
    var boyfriendType = canonicalBoyfriendType(book.boyfriend_type);
    var status = getBookStatus({ handle: book.handle, title: book.title });

    if (status === 'read' || status === 'dnf') return -999;
    if (isSoftProfileIncompatibleBook(book)) return -999;

    if (craving){
      craving.tropeBoosts.forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 3;
      });
      craving.shelfBoosts.forEach(function(shelf){
        if (shelfName === normalize(shelf)) score += 3;
      });
      craving.boyfriendBoosts.forEach(function(type){
        if (boyfriendType === canonicalBoyfriendType(type)) score += 2;
      });
      score += statScore(book, craving.stats);
    }

    if (payoff){
      (payoff.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 2;
      });
      (payoff.shelfBoosts || []).forEach(function(shelf){
        if (shelfName === normalize(shelf)) score += 2;
      });
      score += statScore(book, payoff.stats);
    }

    if (favoriteTrope){
      (favoriteTrope.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 4;
      });
      (favoriteTrope.shelfBoosts || []).forEach(function(shelf){
        if (shelfName === normalize(shelf)) score += 3;
      });
      (favoriteTrope.boyfriendBoosts || []).forEach(function(type){
        if (boyfriendType === canonicalBoyfriendType(type)) score += 2;
      });
      score += statScore(book, favoriteTrope.stats);
    }

    if (man){
      (man.boyfriendBoosts || []).forEach(function(type){
        if (boyfriendType === canonicalBoyfriendType(type)) score += 4;
      });
      (man.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 2;
      });
    }

    if (theme.season === 'autumn' && (book.darkness || 0) >= 2) score += 1;
    if (theme.season === 'winter' && (book.yearning || 0) >= 2) score += 1;
    if (theme.season === 'summer' && (book.spice || 0) >= 3) score += 1;
    if (theme.season === 'spring' && (book.damage || 0) <= 2) score += 1;

    if (theme.key === 'dark_hearts' && ((book.darkness || 0) >= 2 || bookTropes.indexOf('morally gray') > -1 || bookTropes.indexOf('obsession') > -1)) score += 1;
    if (theme.key === 'obsession_red' && (((book.spice || 0) >= 2 && (book.tension || 0) >= 2) || bookTropes.indexOf('forbidden romance') > -1 || bookTropes.indexOf('obsession') > -1)) score += 1;
    if (theme.key === 'rose_ribbon' && (bookTropes.indexOf('slow burn') > -1 || bookTropes.indexOf('friends to lovers') > -1 || (book.yearning || 0) >= 2)) score += 1;
    if (theme.key === 'stormy_blue' && (boyfriendType === 'cold_grump' || boyfriendType === 'emotionally_unavailable_man' || boyfriendType === 'tortured_prince' || (book.yearning || 0) >= 2 || (book.tension || 0) >= 2)) score += 1;
    if (theme.key === 'pearl_white' && (bookTropes.indexOf('friends to lovers') > -1 || bookTropes.indexOf('healing') > -1 || bookTropes.indexOf('protective hero') > -1)) score += 1;
    if (theme.key === 'royal_violet' && (shelfName.indexOf('romantasy') > -1 || shelfName.indexOf('fantasy') > -1 || bookTropes.indexOf('villain gets the girl') > -1 || bookTropes.indexOf('magic') > -1)) score += 1;

    if (profile.spice_dial){
      if (getSpiceDialReason(book)){
        score += 6;
      } else {
        var preferredSpice = spiceDialValues.indexOf(profile.spice_dial) + 1;
        var spiceDistance = Math.abs(Number(book.spice || 0) - preferredSpice);
        if (spiceDistance === 1) score += 1;
        if (spiceDistance >= 3) score -= 3;
      }
    }

    if (profile.favorite_trope && bookTropes.indexOf(normalize(profile.favorite_trope)) > -1){
      score += 7;
    }

    score += getBookReaderTypeScore(book, getPersonaProfile());

    return score;
  }

	  function statScore(book, targets){
	    if (!targets) return 0;

    var score = 0;
    if (targets.spice && (book.spice || 0) >= targets.spice) score += 1;
    if (targets.tension && (book.tension || 0) >= targets.tension) score += 1;
    if (targets.damage && (book.damage || 0) >= targets.damage) score += 1;
    if (targets.darkness && (book.darkness || 0) >= targets.darkness) score += 1;
    if (targets.yearning && (book.yearning || 0) >= targets.yearning) score += 1;
    return score;
  }

	  function normalize(value){
	    return String(value || '').trim().toLowerCase();
	  }

	  function getActiveTropeLane(){
	    var explicitTrope = normalize(profile.favorite_trope || '');
	    if (explicitTrope) return explicitTrope;

	    var readerTypeKey = profile.reader_type_prior || getQuizResolvedReaderTypeKey() || '';
	    return normalize(readerTypePrimaryTrope[readerTypeKey] || '');
	  }

	  function getTropeLaneAliases(lane){
	    var normalizedLane = normalize(lane);
	    return [normalizedLane].concat(tropeLaneAliases[normalizedLane] || []).map(normalize).filter(Boolean);
	  }

	  function bookMatchesTropeLane(book, lane){
	    if (!book || !lane) return false;
	    var aliases = getTropeLaneAliases(lane);
	    var values = (book.tropes || []).concat([book.shelf || '']).map(normalize).filter(Boolean);

	    return values.some(function(value){
	      return aliases.some(function(alias){
	        return value === alias || value.indexOf(alias) > -1 || alias.indexOf(value) > -1;
	      });
	    });
	  }

	  function bookConflictsWithTropeLane(book, lane){
	    var conflicts = (tropeLaneConflicts[normalize(lane)] || []).map(normalize);
	    if (!book || !conflicts.length) return false;
	    var values = (book.tropes || []).concat([book.shelf || '']).map(normalize).filter(Boolean);

	    return values.some(function(value){
	      return conflicts.some(function(conflict){
	        return value === conflict || value.indexOf(conflict) > -1 || conflict.indexOf(value) > -1;
	      });
	    });
	  }

	  function filterRankedByTropeLane(entries, lane){
	    if (!lane) return entries;

	    return entries.filter(function(entry){
	      var book = entry && entry.book;
	      return bookMatchesTropeLane(book, lane) && !bookConflictsWithTropeLane(book, lane);
	    });
	  }

	  function canonicalBoyfriendType(value){
    var raw = normalize(value);
    if (!raw) return '';

    if (legacyBoyfriendMap[raw]){
      return legacyBoyfriendMap[raw];
    }

    var matchedKey = Object.keys(boyfriendTypeAliases).find(function(key){
      if (key === raw) return true;

      return (boyfriendTypeAliases[key] || []).some(function(alias){
        var normalizedAlias = normalize(alias);
        return raw === normalizedAlias || raw.indexOf(normalizedAlias) > -1 || normalizedAlias.indexOf(raw) > -1;
      });
    });

    return matchedKey || raw;
  }

  function applyTypeWeights(target, weightMap, multiplier){
    if (!weightMap) return;

    Object.keys(weightMap).forEach(function(type){
      target[type] = (target[type] || 0) + ((weightMap[type] || 0) * (multiplier || 1));
    });
  }

  function deriveBoyfriendTypeFromQuiz(sourceProfile){
    var nextProfile = sourceProfile || profile || {};
    var hook = nextProfile.boyfriend_hook;
    var dynamic = nextProfile.boyfriend_dynamic;
    var scores = {};

    if (hook && boyfriendQuestionWeights.boyfriend_hook[hook]){
      applyTypeWeights(scores, boyfriendQuestionWeights.boyfriend_hook[hook], 1.2);
    }
    if (dynamic && boyfriendQuestionWeights.boyfriend_dynamic[dynamic]){
      applyTypeWeights(scores, boyfriendQuestionWeights.boyfriend_dynamic[dynamic], 1.1);
    }

    applyTypeWeights(scores, {
      cozy: { sweetheart: 3, athlete_with_heart: 2, cold_grump: 1 },
      spicy: { arrogant_asshole: 3, bully: 2, athlete_with_heart: 2, mafia_boss: 1 },
      dark: { morally_gray_villain: 3, mafia_boss: 3, stalker: 2, obsessive_protector: 2 },
      slowburn: { cold_grump: 3, emotionally_unavailable_man: 2, academic_rival: 2, tortured_prince: 2 },
      surprise: { academic_rival: 1, athlete_with_heart: 1, cold_grump: 1, morally_gray_villain: 1 },
      slow_ache: { cold_grump: 2, emotionally_unavailable_man: 2, tortured_prince: 2, academic_rival: 1 },
      messy_obsession: { stalker: 3, obsessive_protector: 2, morally_gray_villain: 2, mafia_boss: 2 },
      comfort_devotion: { sweetheart: 3, athlete_with_heart: 2, obsessive_protector: 1 },
      chaos_chemistry: { academic_rival: 2, arrogant_asshole: 2, bully: 2, athlete_with_heart: 1 },
      dark_dangerous: { morally_gray_villain: 3, mafia_boss: 3, stalker: 2, tortured_prince: 1 }
    }[nextProfile.craving], 1);

    applyTypeWeights(scores, {
      long_tension: { academic_rival: 2, cold_grump: 2, emotionally_unavailable_man: 1 },
      emotional_devastation: { emotionally_unavailable_man: 2, tortured_prince: 2, morally_gray_villain: 1 },
      soft_after_storm: { sweetheart: 2, athlete_with_heart: 2, obsessive_protector: 1 },
      plot_addiction: { academic_rival: 1, mafia_boss: 2, tortured_prince: 1, morally_gray_villain: 1 },
      illegal_chemistry: { arrogant_asshole: 2, morally_gray_villain: 2, mafia_boss: 2, stalker: 1 }
    }[nextProfile.payoff], 1);

    applyTypeWeights(scores, {
      'enemies to lovers': { academic_rival: 3, arrogant_asshole: 2, bully: 1 },
      'second chance': { emotionally_unavailable_man: 3, tortured_prince: 2, sweetheart: 1 },
      'forced proximity': { cold_grump: 3, academic_rival: 2, obsessive_protector: 1 },
      'fake dating': { sweetheart: 2, arrogant_asshole: 2, athlete_with_heart: 1 },
      'grumpy sunshine': { cold_grump: 3, sweetheart: 1 },
      'sports romance': { athlete_with_heart: 4 },
      'dark romance': { morally_gray_villain: 3, mafia_boss: 3, stalker: 2 },
      romantasy: { tortured_prince: 3, morally_gray_villain: 2 },
      'age gap': { emotionally_unavailable_man: 2, mafia_boss: 2 },
      'forbidden romance': { morally_gray_villain: 3, mafia_boss: 2, arrogant_asshole: 1 },
      'workplace romance': { arrogant_asshole: 3, academic_rival: 2 },
      'small town romance': { sweetheart: 3, athlete_with_heart: 2 }
    }[nextProfile.favorite_trope], 1.2);

    var sortedTypes = Object.keys(boyfriendTypeAliases).sort(function(a, b){
      return (scores[b] || 0) - (scores[a] || 0);
    });
    return scores[sortedTypes[0]] ? sortedTypes[0] : canonicalBoyfriendType(nextProfile.fictional_man);
  }

  function getBookByHandle(handle){
    if (!handle) return null;
    return books.find(function(book){
      return book.handle === handle;
    }) || null;
  }

  function buildReaderCore(){
    var craving = cravingProfiles[profile.craving];
    var man = fictionalManProfiles[profile.fictional_man];
    var theme = getThemeProfile();
    var titleParts = [];
    var favoriteTropeLabel = tokenLabels.favorite_trope[profile.favorite_trope] || profile.favorite_trope || '';
    var spiceLabel = tokenLabels.spice_dial[profile.spice_dial] || '';

    if (profile.craving === 'cozy' || profile.craving === 'comfort_devotion') titleParts.push('soft devotion');
    if (profile.craving === 'spicy' || profile.craving === 'chaos_chemistry') titleParts.push('sharp chemistry');
    if (profile.craving === 'dark' || profile.craving === 'dark_dangerous') titleParts.push('dangerous devotion');
    if (profile.craving === 'slowburn' || profile.craving === 'slow_ache') titleParts.push('slow ache');
    if (profile.craving === 'surprise') titleParts.push('curated chaos');
    if (profile.craving === 'messy_obsession') titleParts.push('messy obsession');
    if (favoriteTropeLabel) titleParts.push(favoriteTropeLabel);

    return {
      title: titleParts.length ? ('you are built for ' + titleParts.join(' + ')) : 'waiting on your answers',
      emotion: fallingEmotions[profile.craving] || 'reader breakdown: loading',
      body: [
        craving ? craving.body : '',
        favoriteTropeLabel ? ('your top trope lane is ' + favoriteTropeLabel + '.') : '',
        spiceLabel ? ('your spice setting is ' + spiceLabel + '.') : '',
        man ? ('your weakness is still ' + tokenLabels.fictional_man[profile.fictional_man] + '.') : '',
        theme.key && tokenLabels.theme[theme.key] ? ('the whole thing is wrapped in ' + tokenLabels.theme[theme.key] + ' energy.') : ''
      ].filter(Boolean).slice(0, 2).join(' '),
      tokens: []
    };
  }

  function scoreBoyfriendMatch(book){
    var score = 0;
    var craving = cravingProfiles[profile.craving];
    var payoff = payoffProfiles[profile.payoff];
    var favoriteTrope = favoriteTropeProfiles[profile.favorite_trope];
    var man = fictionalManProfiles[profile.fictional_man];
    var boyfriendType = canonicalBoyfriendType(book && book.boyfriend_type);
    var bookTropes = (book && book.tropes || []).map(normalize);
    var shelfName = normalize(book && book.shelf);
    var persona = getPersonaProfile();
    var personaKey = persona && persona.key ? persona.key : '';
    var readerTypeBoyfriendBoosts = {
      chaos_reader: ['stalker', 'morally_gray_villain', 'mafia_boss', 'bully'],
      dark_romance_girlie: ['morally_gray_villain', 'stalker', 'mafia_boss', 'obsessive_protector'],
      tension_addict: ['academic_rival', 'cold_grump', 'arrogant_asshole', 'obsessive_protector'],
      fantasy_girlie: ['tortured_prince', 'morally_gray_villain', 'cold_grump'],
      jersey_chaser: ['athlete_with_heart'],
      fake_dating_fanatic: ['sweetheart', 'arrogant_asshole', 'athlete_with_heart'],
      slow_burn_girlie: ['cold_grump', 'emotionally_unavailable_man', 'academic_rival', 'tortured_prince'],
      sweet_romance_devotee: ['sweetheart', 'athlete_with_heart', 'obsessive_protector'],
      romance_reader: []
    };
    var readerTypeTropeBoosts = {
      chaos_reader: ['why choose', 'dark romance', 'touch her and die', 'stalker romance', 'obsession', 'bully romance'],
      dark_romance_girlie: ['dark romance', 'touch her and die', 'stalker romance', 'morally gray', 'forbidden romance'],
      tension_addict: ['enemies to lovers', 'forced proximity', 'slow burn', 'banter'],
      fantasy_girlie: ['romantasy', 'fantasy romance', 'fated mates', 'villain gets the girl'],
      jersey_chaser: ['sports romance', 'hockey romance', 'baseball romance'],
      fake_dating_fanatic: ['fake dating', 'marriage of convenience', 'only one bed', 'one bed'],
      slow_burn_girlie: ['slow burn', 'yearning', 'he falls first', 'second chance'],
      sweet_romance_devotee: ['friends to lovers', 'small town romance', 'found family', 'grumpy sunshine']
    };
    var personaTypes = readerTypeBoyfriendBoosts[personaKey] || [];
    var personaTropes = readerTypeTropeBoosts[personaKey] || [];
    var allowedTypes = man ? (man.boyfriendBoosts || []).map(canonicalBoyfriendType) : [];
    var isAllowedType = !allowedTypes.length || allowedTypes.some(function(type){
      return boyfriendType === type;
    });

    if (!isAllowedType) return -999;

    personaTypes.forEach(function(type, index){
      if (boyfriendType === canonicalBoyfriendType(type)){
        score += Math.max(18 - (index * 3), 8);
      }
    });

    personaTropes.forEach(function(trope){
      var normalizedTrope = normalize(trope);
      if (bookTropes.indexOf(normalizedTrope) > -1) score += 5;
      if (shelfName && (shelfName === normalizedTrope || shelfName.indexOf(normalizedTrope) > -1 || normalizedTrope.indexOf(shelfName) > -1)) score += 3;
    });

    if (personaKey === 'dark_romance_girlie' && (Number(book.darkness || 0) >= 4 || shelfName.indexOf('dark') > -1)) score += 8;
    if (personaKey === 'chaos_reader' && Number(book.spice || 0) >= 4) score += 7;
    if (personaKey === 'sweet_romance_devotee' && Number(book.spice || 0) <= 2) score += 5;
    if (personaKey === 'jersey_chaser' && shelfName.indexOf('sports') > -1) score += 7;

    if (man){
      (man.boyfriendBoosts || []).forEach(function(type){
        if (boyfriendType === canonicalBoyfriendType(type)) score += 20;
      });
      (man.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 6;
      });
    }

    if (craving){
      (craving.boyfriendBoosts || []).forEach(function(type){
        if (boyfriendType === canonicalBoyfriendType(type)) score += 7;
      });
      (craving.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 2;
      });
    }

    if (payoff){
      (payoff.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 2;
      });
    }

    if (favoriteTrope){
      (favoriteTrope.boyfriendBoosts || []).forEach(function(type){
        if (boyfriendType === canonicalBoyfriendType(type)) score += 7;
      });
      (favoriteTrope.tropeBoosts || []).forEach(function(trope){
        if (bookTropes.indexOf(normalize(trope)) > -1) score += 2;
      });
    }

    return score;
  }

  function renderMadeForYou(){
    if (syncStoredSpiceProfile()){
      syncAddonDrafts();
    }
    var answeredCount = Object.keys(profile).filter(function(key){ return !!profile[key]; }).length;
    var readerCore = buildReaderCore();
    var man = fictionalManProfiles[profile.fictional_man];
    var firstName = String(profile.name || '').trim();

    applyThemeProfile();

    if (dashboardTitle){
      dashboardTitle.textContent = firstName ? (firstName + "'s dashboard") : 'made for you';
    }
    if (dashboardKicker){
      dashboardKicker.textContent = firstName ? ('curated for ' + firstName) : 'curated for you';
    }
    renderHeroReaderTypeSymbol();
    coreTitle.textContent = readerCore.title;
    if (coreEmotion){
      coreEmotion.textContent = readerCore.emotion;
    }
    coreBody.textContent = readerCore.body
      ? ((firstName ? (firstName + ', ') : '') + readerCore.body)
      : 'pick a few answers and i’ll tell you what kind of romance damage you’re actually here for.';

    if (themeTokens){
      themeTokens.innerHTML = '';
      [
        profile.craving && tokenLabels.craving[profile.craving],
        profile.payoff && tokenLabels.payoff[profile.payoff],
        profile.favorite_trope && ('trope: ' + profile.favorite_trope),
        profile.spice_dial && ('spice ' + getSpiceLevel(profile.spice_dial) + '/5')
      ].filter(Boolean).forEach(function(label){
        var token = document.createElement('span');
        token.textContent = label;
        themeTokens.appendChild(token);
      });
    }

    renderDashboardProfile(readerCore, man);

    applyModuleCopy();

    typeTitle.textContent = man ? tokenLabels.fictional_man[profile.fictional_man] : 'currently unreadable';
    typeBody.textContent = man ? man.body : 'this is where i’ll lovingly explain what your taste in fictional men says about you.';

    renderShelfInsight();
    renderNextOpinion();
    renderDashboardBookshelf();
    renderRecommendations(answeredCount);
    renderFictionalBfCard();
    renderQuickLinks();
    renderFeatureLinks();
    renderReadShelf();
    renderFavoriteBookEcho();
    renderQuote();
    renderSavedQuotes();
    renderManDialNote();
    renderEmojiRain();
  }

  function renderDashboardProfile(readerCore, man){
    var theme = getThemeProfile();
    var persona = getPersonaProfile();
    var leadTrope = getLeadTropeName();
    syncDisplaySpiceDial(persona.key);
    var displaySpiceDial = getDisplaySpiceDial(persona.key);
    var spiceLabel = getDialDisplayText(displaySpiceDial);
    var spiceLevel = getSpiceLevel(displaySpiceDial);
    var readerType = readerCore && readerCore.title
      ? readerCore.title.replace(/^you are built for\s+/i, '')
      : 'unread';

    if (dashboardSpice){
      dashboardSpice.textContent = spiceLabel;
    }
    if (dashboardSpiceLabel){
      dashboardSpiceLabel.textContent = 'level ' + spiceLevel + ' of 5';
    }
    if (dashboardReaderType){
      dashboardReaderType.textContent = readerType || 'unread';
    }
    if (dashboardReaderSignal){
      dashboardReaderSignal.textContent = getReaderSignalText();
    }
    if (dashboardTrope){
      dashboardTrope.textContent = profile.favorite_trope || 'waiting';
    }
    if (dashboardTheme){
      dashboardTheme.textContent = getThemeDisplayText(theme.key);
    }
    if (dashboardThemeSignal){
      dashboardThemeSignal.textContent = man && profile.fictional_man
        ? ('paired with ' + tokenLabels.fictional_man[profile.fictional_man])
        : 'pick your colors';
    }
    if (visibleReaderType){
      visibleReaderType.innerHTML = getPersonaBadgeMarkup();
    }
    if (visibleReaderSignal){
      visibleReaderSignal.textContent = getReaderTypeVsTropeText(persona, leadTrope);
    }
    if (visibleSpice){
      visibleSpice.innerHTML = [1,2,3,4,5].map(function(n){
        return '<i class="' + (n <= spiceLevel ? 'is-on' : '') + '" aria-hidden="true"></i>';
      }).join('');
      visibleSpice.setAttribute('aria-label', spiceLevel + ' out of 5 spice');
    }
    if (visibleSpiceLabel){
      visibleSpiceLabel.textContent = spiceLabel.replace(/\s*•\s*/, ' · ');
    }
    if (visibleTrope){
      visibleTrope.textContent = leadTrope || profile.favorite_trope || 'pick a trope';
    }
    if (visibleTheme){
      visibleTheme.textContent = (persona.theme && persona.theme.name) || getThemeDisplayText(theme.key);
    }
    applyReaderTheme(persona);
    renderSocietyDashboard();
  }

  function applyModuleCopy(){
    var emojis = moduleEmojiMap[getThemeProfile().emojiGroup] || ['🖤', '✨', '📚', '💌'];

    if (coreEmojiBadge){
      coreEmojiBadge.textContent = emojis[0];
    }
    renderHeroReaderTypeSymbol();
    if (boyfriendEmojiBadge){
      boyfriendEmojiBadge.textContent = emojis[1];
    }
    if (boyfriendKicker){
      boyfriendKicker.textContent = emojis[1] + ' your fictional boyfriend';
    }
    if (shelfEmojiBadge){
      shelfEmojiBadge.textContent = emojis[2];
    }
    if (shelfKicker){
      shelfKicker.textContent = emojis[2] + ' what your bookshelf says about you...';
    }
    if (readsEmojiBadge){
      readsEmojiBadge.textContent = emojis[3];
    }
    if (readsKicker){
      readsKicker.textContent = emojis[3] + ' your next read';
    }
  }

  function renderShelfInsight(){
    var shelf = getShelf();
    var statuses = getBookStatuses();
    var statusEntries = Object.keys(statuses);
    var savedCount = shelf.length;
    var insightBooks = getShelfInsightBooks();

    if (!savedCount && !statusEntries.length){
      shelfTitle.textContent = 'not enough evidence yet';
      shelfBody.textContent = 'save a few books or tag them as tbr / reading and this gets smarter.';
      return;
    }

    var readCount = statusEntries.filter(function(key){ return statuses[key] === 'read'; }).length;
    var readingCount = statusEntries.filter(function(key){ return statuses[key] === 'reading'; }).length;
    var tbrCount = statusEntries.filter(function(key){ return statuses[key] === 'tbr'; }).length;
    var bookshelfPattern = getBookshelfPattern(insightBooks);

    shelfTitle.textContent = bookshelfPattern.title || 'your bookshelf has a type';

    shelfBody.textContent =
      (bookshelfPattern.body ? (bookshelfPattern.body + ' ') : '') +
      'right now this is reading your ' + readCount + ' finished books first, with ' + readingCount + ' marked reading and ' + tbrCount + ' sitting in your tbr pile as lighter context.';
  }

  function getShelfInsightBooks(){
    var statusPriority = { read: 0, reading: 1, tbr: 2 };
    var statuses = getBookStatuses();
    var keyedBooks = {};

    books.forEach(function(book){
      keyedBooks[getBookStatusKey(book)] = book;
    });

    var prioritized = Object.keys(statuses).map(function(key){
      return {
        book: keyedBooks[key],
        status: statuses[key]
      };
    }).filter(function(entry){
      return !!entry.book;
    }).sort(function(a, b){
      return (statusPriority[a.status] ?? 99) - (statusPriority[b.status] ?? 99);
    }).map(function(entry){
      return entry.book;
    });

    var seen = {};
    var ordered = [];

    prioritized.forEach(function(book){
      var key = getBookStatusKey(book);
      if (seen[key]) return;
      seen[key] = true;
      ordered.push(book);
    });

    getShelf().forEach(function(item){
      var key = getBookStatusKey(item);
      var match = keyedBooks[key];
      if (!match || seen[key]) return;
      seen[key] = true;
      ordered.push(match);
    });

    return ordered;
  }

  var readerTypeRules = {
    'slow burn': {
      title: 'the patient one',
      body: 'patient in fiction. chaotic in real life. lives for the almost-moment. deep down just wants to be perceived that carefully by someone.',
      flag: 'has cried at a forehead touch before. no notes.',
      aliases: ['slow burn', 'yearning']
    },
    'forced proximity': {
      title: 'the one-bed truther',
      body: 'loves the idea that love finds you, not the other way around. probably romanticises being stuck somewhere with someone.',
      flag: 'has thought "if only we were snowed in" about someone who did not deserve it.',
      aliases: ['forced proximity', 'one bed', 'only one bed']
    },
    'fake dating': {
      title: 'the plausible deniability fan',
      body: 'comes for the fake intimacy that accidentally becomes real. that is the only kind of love story that makes sense to them and they know it.',
      flag: 'has entertained a fake-dating plan. has not acted on it. yet.',
      aliases: ['fake dating', 'fake relationship', 'marriage of convenience']
    },
    'second chance': {
      title: 'the door-holder',
      body: 'believes in people maybe a little too much. reads this trope for permission to try again, or to feel okay about not.',
      flag: 'definitely has an unsent message somewhere.',
      aliases: ['second chance', 'exes to lovers']
    },
    'sports romance': {
      title: 'here for the man, not the sport',
      body: 'cannot name a single real player. is here because athletes are written with a very specific kind of devotion: the dedication, the discipline, the biceps.',
      flag: 'has very strong opinions about ryan shay.',
      aliases: ['sports romance', 'sports', 'hockey romance', 'football romance', 'baseball romance']
    },
    'grumpy sunshine': {
      title: 'secretly both',
      body: 'either brings light into difficult spaces and gets called too much, or is the grumpy one who needs someone to soften them and will not admit it.',
      flag: 'they are grumpy about being assigned sunshine.',
      aliases: ['grumpy sunshine', 'grumpy / sunshine', 'grumpy/sunshine', 'grumpy x sunshine']
    },
    'enemies to lovers': {
      title: 'loves the problem',
      body: 'does not want easy. wants someone who challenges them and then loves them so hard it breaks the whole thing open. the argument is the intimacy.',
      flag: 'has confused conflict with chemistry at least once. this trope did not help.',
      aliases: ['enemies to lovers', 'rivals to lovers', 'hate to love']
    },
    'forbidden romance': {
      title: 'the rule is the problem, not them',
      body: 'not here for easy love. wants the kind that costs something. boss/employee, rival families, that is the only love that feels real to them.',
      flag: 'deeply committed to the idea that circumstance is the only obstacle here.',
      aliases: ['forbidden romance', 'forbidden love', 'forbidden']
    },
    'dark romance': {
      title: 'the villain is the love interest',
      body: 'wants to feel something, not be told what they are allowed to feel. they are not advocating. they are escaping. and they do not need to explain that to anyone.',
      flag: 'has a type in fiction they would absolutely never date in real life.',
      aliases: ['dark romance', 'dark', 'stalker romance', 'stalker', 'touch her and die', 'villain gets the girl']
    },
    'mafia romance': {
      title: 'the suit is doing something to them',
      body: 'not about crime. it is about devotion without limit. someone who would destroy the world for this one person. the protection. the contradiction.',
      flag: 'has a ranked list of fictional dons. it is detailed.',
      aliases: ['mafia romance', 'mafia', 'bratva', 'cartel romance']
    },
    'bully romance': {
      title: 'obsession decoder',
      body: 'does not want to be bullied. wants to be the person someone hates so specifically it reads as obsession. that is not cruelty. that is being perceived. completely.',
      flag: 'would describe their ideal relationship as "he hates me in a way that feels personal."',
      aliases: ['bully romance', 'bully', 'bully romance books']
    },
    'morally gray mmc': {
      title: 'the problem is the point',
      body: 'tired of being told who to root for. wants a man who does the wrong thing for the right reason, or the wrong reason with extremely good hair.',
      flag: 'their book boyfriend list reads like a restraining order waiting to happen. they are fine.',
      aliases: ['morally gray mmc', 'morally gray', 'morally grey', 'morally grey mmc', 'antihero']
    },
    'age gap': {
      title: 'experience is a vibe',
      body: 'here for the dynamic. the knowing. someone who has done something with their life loving someone still becoming. experience reading as devotion.',
      flag: 'googles age gaps in fictional couples. then googles it again.',
      aliases: ['age gap', 'age-gap']
    },
    'reverse harem': {
      title: 'why choose is not a question',
      body: 'less about the fantasy of many, more about the fantasy of being completely loved by multiple people who just accept all of you. the logistics are not the point.',
      flag: 'finds monogamy limitations in fiction genuinely boring at this point.',
      aliases: ['reverse harem', 'why choose', 'poly romance']
    },
    'instalove': {
      title: 'vibes over evidence',
      body: 'when it is right it is right. that is the whole philosophy. refuses to feel bad about wanting to skip to the good part.',
      flag: 'has said "i just knew" about someone they knew for four days.',
      aliases: ['instalove', 'insta love', 'love at first sight']
    },
    'paranormal monster romance': {
      title: 'not here to explain herself',
      body: 'has simply decided that fictional men available in reality are not meeting the brief. the spec: dangerous, ancient, non-human, completely devoted to one woman specifically.',
      flag: 'claws are negotiable. the obsession is not.',
      aliases: ['paranormal / monster romance', 'paranormal romance', 'monster romance', 'monster', 'vampire romance', 'shifter romance']
    },
    'bodyguard protector': {
      title: 'safety is the love language',
      body: 'wants to feel safe in a specific, consuming, slightly-overwhelming way. someone who would rearrange the world so nothing bad reaches her. that is it.',
      flag: 'has asked "but would he keep me safe" as a genuine compatibility metric.',
      aliases: ['bodyguard / protector', 'bodyguard', 'protector', 'protector romance', 'bodyguard romance']
    },
    'he falls first': {
      title: 'watching him spiral is the plot',
      body: 'needs the man to be completely undone before she knows it. wants to watch the exact moment it shifts, when she becomes the whole thing for him and she is still just getting there.',
      flag: 'has a list of scenes where the man realises first. it is long.',
      aliases: ['he falls first', 'he falls first romance', 'hero falls first', 'he falls first/her falls harder']
    }
  };

  function normalizeReaderTypeTrope(value){
    return normalize(String(value || '').replace(/[\/_-]+/g, ' ').replace(/\s+x\s+/g, ' '));
  }

  function readerTypeRuleKeyForTrope(trope){
    var normalized = normalizeReaderTypeTrope(trope);
    var match = '';
    if (!normalized) return '';

    Object.keys(readerTypeRules).some(function(ruleKey){
      var aliases = [ruleKey].concat(readerTypeRules[ruleKey].aliases || []);
      return aliases.some(function(alias){
        var aliasKey = normalizeReaderTypeTrope(alias);
        if (!aliasKey) return false;
        if (normalized === aliasKey || normalized.indexOf(aliasKey) > -1 || aliasKey.indexOf(normalized) > -1){
          match = ruleKey;
          return true;
        }
        return false;
      });
    });

    return match || normalized;
  }

  function getBookshelfPattern(sourceBooks){
    var insightBooks = Array.isArray(sourceBooks) ? sourceBooks : getShelfInsightBooks();
    var tropeCounts = {};

    insightBooks.forEach(function(book){
      var status = getBookStatus({ handle: book.handle, title: book.title });
      var weight = status === 'read' ? 5 : status === 'reading' ? 4 : status === 'tbr' ? 3 : status === 'dnf' ? 1 : 2;

      (book.tropes || []).forEach(function(trope){
        var key = readerTypeRuleKeyForTrope(trope);
        if (!key) return;
        tropeCounts[key] = (tropeCounts[key] || 0) + weight;
      });
    });

    var topTropes = Object.keys(tropeCounts).sort(function(a, b){
      if (tropeCounts[b] === tropeCounts[a]){
        return a.localeCompare(b);
      }
      return tropeCounts[b] - tropeCounts[a];
    });
    var topRule = readerTypeRules[topTropes[0]] || null;
    var title = topRule ? topRule.title : 'mood-led romance reader';
    var body = topRule ? topRule.body : 'your shelf is giving a little bit of everything, with mood doing more steering than one fixed trope.';

    if (topRule && topRule.flag){
      body += ' red flag: ' + topRule.flag;
    }

    return {
      title: title,
      body: body
    };
  }

  function findDominantTrait(field, sourceBooks){
    var counts = {};
    var insightBooks = Array.isArray(sourceBooks) ? sourceBooks : getShelfInsightBooks();

    insightBooks.forEach(function(match){
      if (field === 'tropes'){
        (match.tropes || []).forEach(function(trope){
          var key = normalize(trope);
          if (!key) return;
          counts[key] = (counts[key] || 0) + 1;
        });
      } else {
        var key = normalize(match[field]);
        if (!key) return;
        counts[key] = (counts[key] || 0) + 1;
      }
    });

    var sorted = Object.keys(counts).sort(function(a, b){
      return counts[b] - counts[a];
    });

    return sorted[0] || '';
  }

  function featuredMatchScore(book, typeHint){
    var man = fictionalManProfiles[profile.fictional_man];
    var score = 0;
    var boyfriendType = canonicalBoyfriendType(book.boyfriend_type);
    var bookTropes = (book.tropes || []).map(normalize);

    if (typeHint && boyfriendType === canonicalBoyfriendType(typeHint)) score += 10;
    if (!man) return score;

    (man.boyfriendBoosts || []).forEach(function(type){
      if (boyfriendType === canonicalBoyfriendType(type)) score += 5;
    });

    (man.tropeBoosts || []).forEach(function(trope){
      if (bookTropes.indexOf(normalize(trope)) > -1) score += 3;
    });

    return score;
  }

  function mfyEscape(value){
    return String(value || '').replace(/[&<>"']/g, function(char){
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function mfyDecodeEntities(value){
    var textarea = document.createElement('textarea');
    textarea.innerHTML = String(value || '');
    return textarea.value;
  }

  function getPersonaLabel(){
    return getPersonaProfile().label;
  }

  function getFallbackReaderTypes(){
    return [
      { key: 'chaos_reader', label: 'the chaos reader', emoji: 'why-choose', bio: 'maximum spice, maximum plot complications, very little concern for emotional safety.', triggers: ['why-choose', 'forbidden-love', 'mafia-romance', 'touch-her-and-die', 'stalker-romance'], theme: { name: 'scarlet riot', surface: '#1C0A0C', border: '#3D1216', deep: '#8A1622', accent: '#FF4040', accent2: '#FFB3B8', onAccent: '#4A0408', textHeading: '#FFF2F2', textBody: '#F4E2E4', textMuted: '#C49AA0', glow: 'rgba(255,64,64,.14)' } },
      { key: 'dark_romance_girlie', label: 'the dark romance girlie', emoji: 'touch-her-and-die', bio: 'danger, obsession, morally gray choices, and devotion with teeth.', triggers: ['dark-romance', 'stalker-romance', 'mafia-romance', 'captor-x-captive', 'villain-gets-the-girl', 'bully-romance'], theme: { name: 'oxblood velvet', surface: '#170609', border: '#38101C', deep: '#6E1233', accent: '#E0245E', accent2: '#F77FAB', onAccent: '#3D0218', textHeading: '#FBEFF4', textBody: '#EFDCE4', textMuted: '#B58FA0', glow: 'rgba(224,36,94,.13)' } },
      { key: 'fantasy_girlie', label: 'the fantasy girlie', emoji: 'fated-mates', bio: 'magic systems, fated stakes, crowns, monsters, and dramatic yearning.', triggers: ['romantasy', 'paranormal-romance', 'fated-mates', 'dystopian-romance'], theme: { name: 'amethyst hour', surface: '#110A1D', border: '#271746', deep: '#4E2E96', accent: '#A875FF', accent2: '#D4BCFF', onAccent: '#25104F', textHeading: '#F7F2FF', textBody: '#E8DEF7', textMuted: '#A996C9', glow: 'rgba(168,117,255,.14)' } },
      { key: 'jersey_chaser', label: 'the jersey chaser', emoji: 'hockey-romance', bio: 'athletes, rivalry, teammates, locker-room confidence, and softness after the game.', triggers: ['sports-romance', 'hockey-romance', 'baseball-romance'], theme: { name: 'rink lights', surface: '#07101C', border: '#122B4A', deep: '#1A4E8F', accent: '#4FA8FF', accent2: '#A8D2FF', onAccent: '#062646', textHeading: '#F0F7FF', textBody: '#DCE8F4', textMuted: '#8FA9C7', glow: 'rgba(79,168,255,.13)' } },
      { key: 'slow_burn_girlie', label: 'the slow burn girlie', emoji: 'slow-burn', bio: 'glances, restraint, almost-confessions, and payoff that takes its sweet time.', triggers: ['slow-burn', 'he-falls-first', 'forbidden-love', 'second-chance'], theme: { name: 'candlelit amber', surface: '#181004', border: '#3B2A0C', deep: '#7D5A14', accent: '#F2B13D', accent2: '#FFE0A3', onAccent: '#3F2A04', textHeading: '#FFF8EC', textBody: '#F2E8D4', textMuted: '#C2B08C', glow: 'rgba(242,177,61,.12)' } },
      { key: 'tension_addict', label: 'the tension addict', emoji: 'enemies-to-lovers', bio: 'banter, friction, rivalry, and the very specific joy of two people losing the argument.', triggers: ['enemies-to-lovers', 'forced-proximity', 'grumpy-sunshine', 'opposites-attract', 'boss-x-employee'], theme: { name: 'live wire', surface: '#1A0C06', border: '#3D1C0E', deep: '#8A3A14', accent: '#FF7438', accent2: '#FFC09A', onAccent: '#471A04', textHeading: '#FFF4ED', textBody: '#F4E4DA', textMuted: '#C7A48F', glow: 'rgba(255,116,56,.13)' } },
      { key: 'fake_dating_fanatic', label: 'the fake dating fanatic', emoji: 'fake-dating', bio: 'contracts, public pretending, private feelings, and the moment the lie starts telling the truth.', triggers: ['fake-dating', 'one-bed', 'marriage-of-convenience'], theme: { name: 'bubblegum noir', surface: '#1B0814', border: '#3F1230', deep: '#87265F', accent: '#FF6FC2', accent2: '#FFC2E4', onAccent: '#470A30', textHeading: '#FFF1F8', textBody: '#F4DEEA', textMuted: '#C397B0', glow: 'rgba(255,111,194,.14)' } },
      { key: 'sweet_romance_devotee', label: 'the sweet romance devotee', emoji: 'friends-to-lovers', bio: 'comfort, tenderness, caretaking, and low-spice softness that still knows how to ache.', triggers: ['friends-to-lovers', 'small-town', 'found-family', 'single-dad', 'grumpy-sunshine', 'contemporary-romance'], theme: { name: 'blush hour', surface: '#170D11', border: '#38202A', deep: '#74404F', accent: '#FFB3C6', accent2: '#FFDDE7', onAccent: '#4A1626', textHeading: '#FFF4F7', textBody: '#F2E3E8', textMuted: '#BFA0AB', glow: 'rgba(255,179,198,.10)' } },
      { key: 'romance_reader', label: 'the romance reader', emoji: 'found-family', bio: 'balanced taste, flexible moods, and a dashboard still learning the exact flavor of book chaos.', triggers: ['found-family', 'opposites-attract', 'second-chance', 'he-falls-first', 'contemporary-romance'], theme: { name: 'unsorted silver', surface: '#131013', border: '#2E282C', deep: '#5E5258', accent: '#D4C2CE', accent2: '#EFE4EA', onAccent: '#2E242A', textHeading: '#FAF6F8', textBody: '#EAE2E6', textMuted: '#A89AA1', glow: 'rgba(212,194,206,.08)' } }
    ];
  }

  function getReaderTypes(){
    var types = readerTypeRegistry && readerTypeRegistry.length ? readerTypeRegistry : getFallbackReaderTypes();
    return types.map(function(type){
      type.triggers = Array.isArray(type.triggers) && type.triggers.length ? type.triggers : (type.supporting || []);
      type.theme = type.theme || {};
      return type;
    });
  }

  function slugifyTaste(value){
    return normalize(value).replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  }

  function getReaderTypeByKey(key){
    return getReaderTypes().find(function(type){ return type.key === key; }) || getReaderTypes().slice(-1)[0] || getFallbackReaderTypes().slice(-1)[0];
  }

  function hasReaderTypeKey(key){
    key = String(key || '');
    return getReaderTypes().some(function(type){ return type.key === key; });
  }

  function addReaderTypeScore(scores, key, amount){
    scores[key] = (scores[key] || 0) + amount;
  }

  function getQuizResolvedReaderTypeKey(){
    var picks = [profile.group_chat_text, profile.love_interest, profile.wall_line].filter(Boolean);
    if (picks.length < 3) return '';

    var counts = {};
    picks.forEach(function(key){
      counts[key] = (counts[key] || 0) + 1;
    });

    var matched = Object.keys(counts).find(function(key){
      return counts[key] >= 2;
    });
    if (matched) return matched;

    var feralOrder = [
      'sweet_romance_devotee',
      'slow_burn_girlie',
      'fake_dating_fanatic',
      'jersey_chaser',
      'fantasy_girlie',
      'tension_addict',
      'dark_romance_girlie',
      'chaos_reader'
    ];
    var sorted = picks.slice().sort(function(a, b){
      return feralOrder.indexOf(a) - feralOrder.indexOf(b);
    });
    var lane = profile.heat_lane || 'cracked';

    if (lane === 'unhinged' && picks.indexOf('dark_romance_girlie') > -1 && picks.indexOf('chaos_reader') > -1){
      return 'chaos_reader';
    }
    if (lane === 'closed') return sorted[0] || '';
    if (lane === 'open' || lane === 'unhinged') return sorted[sorted.length - 1] || '';
    return sorted[1] || sorted[0] || '';
  }

  function getHeatLaneSpiceDial(lane){
    return {
      closed: 'soft_open_door',
      cracked: 'balanced',
      open: 'high_spice',
      unhinged: 'wreck_me'
    }[lane || ''] || '';
  }

  function getReaderTypeSpiceDial(readerTypeKey){
    return {
      chaos_reader: 'wreck_me',
      dark_romance_girlie: 'high_spice',
      tension_addict: 'balanced',
      fantasy_girlie: 'balanced',
      jersey_chaser: 'balanced',
      fake_dating_fanatic: 'balanced',
      slow_burn_girlie: 'some_heat',
      sweet_romance_devotee: 'soft_open_door',
      romance_reader: 'balanced'
    }[readerTypeKey || ''] || '';
  }

  function getDisplaySpiceDial(readerTypeKey){
    var heatDial = getHeatLaneSpiceDial(profile.heat_lane);
    if (heatDial) return heatDial;

    var currentDial = spiceDialValues.indexOf(profile.spice_dial) > -1 ? profile.spice_dial : '';
    var typeDial = getReaderTypeSpiceDial(readerTypeKey || profile.reader_type_prior || getPreviewReaderTypeKey());

    if ((!currentDial || currentDial === 'soft_open_door') && typeDial && typeDial !== 'soft_open_door'){
      return typeDial;
    }

    return currentDial || typeDial || 'balanced';
  }

  function syncDisplaySpiceDial(readerTypeKey){
    var nextDial = getDisplaySpiceDial(readerTypeKey);
    if (!nextDial || nextDial === profile.spice_dial) return false;
    if (profile.heat_lane && getHeatLaneSpiceDial(profile.heat_lane) !== nextDial) return false;
    if (profile.spice_dial && profile.spice_dial !== 'soft_open_door' && !profile.heat_lane) return false;

    profile.spice_dial = nextDial;
    saveSharedSpiceProfile(Math.max(spiceDialValues.indexOf(nextDial) + 1, 1), 'made-for-you-display');
    saveProfile(profile);
    return true;
  }

  function syncQuizDerivedProfile(){
    var lane = profile.heat_lane || '';
    var laneDial = getHeatLaneSpiceDial(lane);
    if (laneDial){
      profile.spice_dial = laneDial;
      saveSharedSpiceProfile(Math.max(spiceDialValues.indexOf(profile.spice_dial) + 1, 1), 'made-for-you-heat-lane');
    }

    var resolved = getQuizResolvedReaderTypeKey();
    if (resolved){
      profile.reader_type_prior = resolved;
    }

	    if (resolved && readerTypePrimaryTrope[resolved] && !profile.favorite_trope){
	      profile.favorite_trope = readerTypePrimaryTrope[resolved];
	    }
	  }

  function getBookReaderTypeScore(book, persona){
    if (!book || !persona) return 0;
    var score = 0;
    var bookTropes = (book.tropes || []).map(slugifyTaste);
    var shelfName = slugifyTaste(book.shelf || '');
    var triggers = (persona.triggers || []).map(slugifyTaste);

    triggers.forEach(function(trigger){
      if (!trigger) return;
      if (bookTropes.indexOf(trigger) > -1) score += 7;
      if (shelfName && (shelfName === trigger || shelfName.indexOf(trigger) > -1 || trigger.indexOf(shelfName) > -1)) score += 5;
    });

    if (persona.key === 'chaos_reader' && Number(book.spice || 0) >= 4) score += 8;
    if (persona.key === 'dark_romance_girlie' && (Number(book.darkness || 0) >= 3 || shelfName.indexOf('dark') > -1)) score += 7;
    if (persona.key === 'fantasy_girlie' && (shelfName.indexOf('fantasy') > -1 || shelfName.indexOf('romantasy') > -1 || bookTropes.indexOf('fated-mates') > -1)) score += 8;
    if (persona.key === 'jersey_chaser' && (shelfName.indexOf('sports') > -1 || bookTropes.indexOf('hockey-romance') > -1 || bookTropes.indexOf('sports-romance') > -1)) score += 8;
    if (persona.key === 'slow_burn_girlie' && (Number(book.yearning || 0) >= 3 || bookTropes.indexOf('slow-burn') > -1)) score += 7;
    if (persona.key === 'tension_addict' && (Number(book.tension || 0) >= 3 || bookTropes.indexOf('enemies-to-lovers') > -1 || bookTropes.indexOf('forced-proximity') > -1)) score += 7;
    if (persona.key === 'fake_dating_fanatic' && (bookTropes.indexOf('fake-dating') > -1 || bookTropes.indexOf('marriage-of-convenience') > -1)) score += 9;
    if (persona.key === 'sweet_romance_devotee' && (Number(book.spice || 0) <= 2 || bookTropes.indexOf('friends-to-lovers') > -1 || bookTropes.indexOf('found-family') > -1)) score += 6;
    if (persona.key === 'romance_reader' && score === 0) score += 1;

    return score;
  }

  function softProfileGuardrailActive(){
    var personaKey = (profile.reader_type_prior || getQuizResolvedReaderTypeKey() || getPreviewReaderTypeKey() || '');
    var displayDial = getDisplaySpiceDial(personaKey);
    return personaKey === 'sweet_romance_devotee' || displayDial === 'soft_open_door' || profile.heat_lane === 'closed';
  }

  function isSoftProfileIncompatibleBook(book){
    if (!book || !softProfileGuardrailActive()) return false;

    var bookTropes = (book.tropes || []).map(normalize);
    var shelfName = normalize(book.shelf || '');
    var combined = [shelfName].concat(bookTropes).join(' ');
    var blockedSignals = [
      'dark romance',
      'dark academia',
      'mafia',
      'bully romance',
      'touch her and die',
      'stalker',
      'obsession',
      'morally gray'
    ];

    if (Number(book.spice || 0) > 2) return true;
    if (Number(book.darkness || 0) >= 2) return true;

    return blockedSignals.some(function(signal){
      return combined.indexOf(signal) > -1;
    });
  }

  function getReaderTypeRankedBooks(limit){
    var persona = getPersonaProfile();
    return books
      .map(function(book){
        return { book: book, score: getBookReaderTypeScore(book, persona) + Math.max(0, scoreBook(book)) };
      })
      .filter(function(entry){
        var status = getBookStatus({ handle: entry.book.handle, title: entry.book.title });
        return entry.score > 0 && status !== 'read' && status !== 'dnf' && !isSoftProfileIncompatibleBook(entry.book);
      })
      .sort(function(a, b){ return b.score - a.score; })
      .map(function(entry){ return entry.book; })
      .slice(0, limit || 5);
  }

  function getPreviewReaderTypeKey(){
    try {
      var params = new URLSearchParams(window.location.search || '');
      var key = params.get('mfy_type') || '';
      var isLocal = window.location.hostname === 'localhost' || window.location.hostname.indexOf('.local') > -1;
      return isLocal && key && getReaderTypeByKey(key) ? key : '';
    } catch(e) {
      return '';
    }
  }

  function scoreReaderTypes(){
    var types = getReaderTypes();
    var scores = {};
    var shelf = getShelf();
    var spiceLevel = getSpiceLevel(getDisplaySpiceDial(profile.reader_type_prior) || 'balanced');
    var previewKey = getPreviewReaderTypeKey();

    if (previewKey){
      var previewAssigned = getReaderTypeByKey(previewKey);
      try {
        storageSet(scopedMfyStorageKey('bbbReaderTypeState'), JSON.stringify({ key: previewAssigned.key, score: 999, assignedAt: new Date().toISOString(), scores: {} }));
        cleanupLegacyMfyStorage('bbbReaderTypeState');
      } catch(e) {}
      return { assigned: previewAssigned, scores: {}, ranked: [{ key: previewAssigned.key, score: 999 }] };
    }

    var quizPrior = String(profile.reader_type_prior || '');
    if (!quizPrior){
      quizPrior = getQuizResolvedReaderTypeKey();
      if (quizPrior){
        profile.reader_type_prior = quizPrior;
      }
    }
    var hasCompleteQuizPrior = quizPrior && hasReaderTypeKey(quizPrior) && questionsOrder.every(function(question){
      return !!String(profile[question] || '').trim();
    });
    if (hasCompleteQuizPrior){
      var quizAssigned = getReaderTypeByKey(quizPrior);
      var quizScores = {};
      quizScores[quizAssigned.key] = 999;
      try {
        storageSet(scopedMfyStorageKey('bbbReaderTypeState'), JSON.stringify({ key: quizAssigned.key, score: 999, assignedAt: new Date().toISOString(), scores: quizScores }));
        cleanupLegacyMfyStorage('bbbReaderTypeState');
      } catch(e) {}
      return { assigned: quizAssigned, scores: quizScores, ranked: [{ key: quizAssigned.key, score: 999 }] };
    }

    types.forEach(function(type){ scores[type.key] = type.key === 'romance_reader' ? 1 : 0; });

    shelf.forEach(function(item){
      var status = getBookStatus(item);
      var weight = status === 'read' ? 3 : status === 'reading' ? 2 : status === 'dnf' ? -2 : 1;
      var matchedBook = books.find(function(book){
        return getBookStatusKey(book) === getBookStatusKey(item);
      }) || item;
      var tropeSignals = (matchedBook.tropes || item.tropes || []).concat([matchedBook.shelf || item.shelf || '']).map(slugifyTaste);

      types.forEach(function(type){
        var triggers = (type.triggers || []).map(slugifyTaste);
        if (tropeSignals.some(function(signal){ return triggers.indexOf(signal) > -1; })){
          addReaderTypeScore(scores, type.key, weight);
        }
      });
    });

    var favoriteTrope = slugifyTaste(profile.favorite_trope || '');
    if (favoriteTrope){
      types.forEach(function(type){
        if ((type.triggers || []).map(slugifyTaste).indexOf(favoriteTrope) > -1){
          addReaderTypeScore(scores, type.key, 2);
        }
      });
    }

    if (profile.reader_type_prior && getReaderTypeByKey(profile.reader_type_prior)){
      var priorWeight = shelf.length ? 2 : 5;
      if (profile.reader_type_prior === 'chaos_reader' && spiceLevel <= 2){
        priorWeight = 1;
      }
      addReaderTypeScore(scores, profile.reader_type_prior, priorWeight);
    }

    if (spiceLevel >= 5) addReaderTypeScore(scores, 'chaos_reader', 3);
    if (spiceLevel <= 2) addReaderTypeScore(scores, 'chaos_reader', -8);
    if (spiceLevel <= 2) addReaderTypeScore(scores, 'sweet_romance_devotee', 2);
    if (profile.craving === 'dark') addReaderTypeScore(scores, 'dark_romance_girlie', 2);
    if (profile.craving === 'slowburn') addReaderTypeScore(scores, 'slow_burn_girlie', 2);
    if (profile.craving === 'cozy') addReaderTypeScore(scores, 'sweet_romance_devotee', 2);

    var ranked = Object.keys(scores).map(function(key){
      return { key: key, score: scores[key] || 0 };
    }).sort(function(a, b){ return b.score - a.score; });
    var leader = ranked[0] || { key: 'romance_reader', score: 0 };
    var runnerUp = ranked[1] || { score: 0 };
    var assignedKey = leader.score >= 3 && leader.score >= Math.max(1, runnerUp.score * 1.5) ? leader.key : 'romance_reader';
    if (!shelf.length && profile.dashboard_built && profile.reader_type_prior){
      assignedKey = profile.reader_type_prior;
    } else if (!shelf.length && assignedKey === 'romance_reader' && leader.score >= 2){
      assignedKey = leader.key;
    }
    if (assignedKey === 'chaos_reader' && spiceLevel <= 2){
      assignedKey = profile.craving === 'dark' ? 'dark_romance_girlie' : 'tension_addict';
    }
    var assigned = getReaderTypeByKey(assignedKey);

    try {
      storageSet(scopedMfyStorageKey('bbbReaderTypeState'), JSON.stringify({ key: assigned.key, score: leader.score, assignedAt: new Date().toISOString(), scores: scores }));
      cleanupLegacyMfyStorage('bbbReaderTypeState');
    } catch(e) {}

    return { assigned: assigned, scores: scores, ranked: ranked };
  }

  function getPersonaProfile(){
    return scoreReaderTypes().assigned;
  }

  function getPersonaBadgeMarkup(){
    var persona = getPersonaProfile();
    var emoji = persona.emoji || 'found-family';
    return '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + mfyEscape(emoji) + '.png" alt="" aria-hidden="true" loading="lazy" decoding="async"><span>' + mfyEscape(persona.label || 'the romance reader') + '</span>';
  }

  function renderHeroReaderTypeSymbol(){
    if (!heroKicker) return;
    var persona = getPersonaProfile();
    var emoji = persona.emoji || 'found-family';
    heroKicker.classList.add('sss-mfy__readerTypeSymbol');
    heroKicker.innerHTML =
      '<img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + mfyEscape(emoji) + '.png" alt="" aria-hidden="true" loading="lazy" decoding="async">' +
      '<span>' + mfyEscape(persona.label || 'the romance reader') + '</span>';
  }

  function applyReaderTheme(persona){
    if (!persona || !persona.theme) return;
    var target = root;
    var theme = persona.theme || {};
    var surface = theme.surface || '#131013';
    var border = theme.border || theme.soft || '#2E282C';
    var deep = theme.deep || '#5E5258';
    var accent = theme.accent || '#D4C2CE';
    var accent2 = theme.accent2 || theme.accent_2 || theme.soft || '#EFE4EA';
    var onAccent = theme.onAccent || theme.on_accent || theme.bg || '#2E242A';
    var textHeading = theme.textHeading || theme.text_heading || theme.soft || '#FAF6F8';
    var textBody = theme.textBody || theme.text_body || theme.soft || '#EAE2E6';
    var textMuted = theme.textMuted || theme.text_muted || theme.deep || '#A89AA1';
    var glow = theme.glow || 'rgba(212,194,206,.08)';
    target.setAttribute('data-reader-theme', persona.key || 'romance_reader');
    target.style.setProperty('--reader-bg', '#0b090b');
    target.style.setProperty('--reader-surface', surface);
    target.style.setProperty('--reader-border', border);
    target.style.setProperty('--reader-deep', deep);
    target.style.setProperty('--reader-accent', accent);
    target.style.setProperty('--reader-accent-2', accent2);
    target.style.setProperty('--reader-on-accent', onAccent);
    target.style.setProperty('--reader-text-heading', textHeading);
    target.style.setProperty('--reader-text-body', textBody);
    target.style.setProperty('--reader-text-muted', textMuted);
    target.style.setProperty('--reader-glow', glow);
    target.style.setProperty('--reader-soft', accent2);
    target.style.setProperty('--mfy-accent', accent);
    target.style.setProperty('--mfy-accent-two', accent2);
    target.style.setProperty('--mfy-accent-three', deep);
    target.style.setProperty('--mfy-accent-four', surface);
    target.style.setProperty('--mfy-accent-soft', 'color-mix(in srgb, var(--reader-accent) 24%, transparent)');
    target.style.setProperty('--mfy-accent-two-soft', 'color-mix(in srgb, var(--reader-accent-2) 18%, transparent)');
    target.style.setProperty('--mfy-accent-three-soft', 'color-mix(in srgb, var(--reader-deep) 28%, transparent)');
    target.style.setProperty('--mfy-accent-four-soft', 'color-mix(in srgb, var(--reader-surface) 42%, transparent)');
    target.style.setProperty('--mfy-accent-ink', onAccent);
    target.style.setProperty('--mfy-accent-two-ink', onAccent);
    target.style.setProperty('--mfy-accent-three-ink', textBody);
    target.style.setProperty('--mfy-accent-four-ink', textBody);
    target.style.setProperty('--mfy-primary', deep);
    target.style.setProperty('--mfy-cream', textBody);
  }

	  function renderSocietyShelfPreview(){
	    if (!societyShelfPreview) return;
	    var previewLimit = 5;
    var shelf = getShelf();
    var previewBooks = shelf.map(function(item){
      return books.find(function(book){ return getBookStatusKey(book) === getBookStatusKey(item); }) || item;
    }).slice(0, previewLimit);
    if (!previewBooks.length){
      previewBooks = getReaderTypeRankedBooks(previewLimit);
    }
    societyShelfPreview.innerHTML = previewBooks.length ? previewBooks.map(function(book, index){
      return '<span class="sss-mfy__shelfPreviewCover">' + getDashboardCoverMarkup(book, index, true) + '</span>';
    }).join('') : '<span class="sss-mfy__empty">save a book to start the preview.</span>';
  }

	  function renderSocietyTropeDna(readBooks){
	    if (!societyTropeDna) return;
	    var top = getTopReadTropes(readBooks, 5);
	    var activeTropeLane = getActiveTropeLane();
	    if (activeTropeLane){
	      top = top.filter(function(entry){
	        return normalize(entry.name) !== activeTropeLane;
	      });
	      top.unshift({ name: activeTropeLane, count: Math.max(1, (top[0] && top[0].count) || 1) });
	    } else if (!top.length && profile.favorite_trope){
	      top = [{ name: profile.favorite_trope, count: 1 }];
	    }
    [
      { name: 'found family', count: 1 },
      { name: 'slow burn', count: 1 },
      { name: 'forced proximity', count: 1 }
    ].forEach(function(fallback){
      if (top.length >= 3) return;
      if (top.some(function(item){ return normalize(item.name) === normalize(fallback.name); })) return;
      top.push(fallback);
    });
    var max = Math.max.apply(null, top.map(function(item){ return item.count || 1; }));
    societyTropeDna.innerHTML = top.map(function(item){
      var percent = Math.max(18, Math.round(((item.count || 1) / max) * 100));
      return '<div class="sss-mfy__tropeDnaRow">' +
        '<span>' + mfyEscape(item.name) + '</span>' +
        '<i><b style="width:' + percent + '%"></b></i>' +
        '<em>' + mfyEscape(String(item.count || 1)) + '</em>' +
      '</div>';
    }).join('');
  }

  function renderSocietyPerks(){
    if (!societyPerks) return;
    var weeklyPicks = getWeeklyReadNextPicks();
    var perks = [
      {
        label: 'weekly read-next logic',
        value: 'live from your profile',
        preview: renderSocietyReadNextPreview(weeklyPicks)
      },
      {
        label: 'private notes layer',
        value: 'synced from book notes',
        preview: renderSocietyNotesPreview(weeklyPicks)
      }
    ];
    societyPerks.innerHTML = perks.map(function(perk){
      var examples = Array.isArray(perk.examples) && perk.examples.length ? '<div class="sss-mfy__societyPerkExamples">' + perk.examples.map(function(example){
        return '<em>' + mfyEscape(example) + '</em>';
      }).join('') + '</div>' : '';
      return '<article class="sss-mfy__societyPerk"><span>' + mfyEscape(perk.label) + '</span><strong>' + mfyEscape(perk.value) + '</strong>' + (perk.preview || examples) + '</article>';
    }).join('');
  }

  function renderSocietyBoyfriendMatch(){
    if (!societyBfCard) return;
    var match = getPaidFictionalBfMatch();
    var book = match.book || {};
    var profileEntry = match.profileEntry || getFictionalBfProfile(book, book.boyfriend_name || '', book.title || '');
    var title = mfyDecodeEntities(profileEntry && profileEntry.name ? profileEntry.name : (book.boyfriend_name || match.typeLabel || 'fictional boyfriend'));
    var url = profileEntry && profileEntry.url ? profileEntry.url : '/fictional-boyfriends/';
    var portrait = renderFictionalBfPortrait(profileEntry, title);
    var profileBookTitle = profileEntry && profileEntry.bookTitle ? mfyDecodeEntities(profileEntry.bookTitle) : '';
    var scoringBookTitle = book && book.title ? mfyDecodeEntities(book.title) : '';
    var sameProfileBook = profileBookTitle && scoringBookTitle && mfyMatchKey(profileBookTitle) === mfyMatchKey(scoringBookTitle);
    var contextLabel = profileBookTitle;
    if (!contextLabel && match.source === 'reader_type'){
      contextLabel = (profileEntry && profileEntry.bookTitle) ? profileEntry.bookTitle : 'reader type match';
    }
    if (!contextLabel && match.source === 'quiz'){
      contextLabel = (match.savedBoyfriend && match.savedBoyfriend.matchedAt) ? 'saved from your quiz' : 'fictional boyfriend quiz';
    }
    if (!contextLabel){
      contextLabel = book.title ? ('shelf signal: ' + book.title) : (((match.persona && match.persona.label) || 'reader type') + ' match');
    }
    var descriptorLabel = match.source === 'reader_type' && profileEntry
      ? (profileEntry.descriptor || profileEntry.bookTitle || match.typeLabel || 'fictional boyfriend')
      : (match.typeLabel || 'fictional boyfriend');
    if (match.source === 'quiz' && profileEntry){
      descriptorLabel = profileEntry.descriptor || match.typeLabel || descriptorLabel;
    }
    if (profileEntry && !sameProfileBook){
      descriptorLabel = profileEntry.descriptor || match.typeLabel || descriptorLabel;
    }
    contextLabel = mfyDecodeEntities(contextLabel);
    descriptorLabel = mfyDecodeEntities(descriptorLabel);
    if (societyBfName) societyBfName.textContent = title;
    societyBfCard.innerHTML =
      '<a class="sss-mfy__societyBfLink" href="' + mfyEscape(url) + '">' +
        portrait +
        '<span><strong>' + mfyEscape(descriptorLabel) + '</strong>' +
        '<small>' + mfyEscape(contextLabel) + '</small></span>' +
      '</a>';
  }

  function renderSocietyDashboard(){
    if (!societyDashboard) return;
    var persona = getPersonaProfile();
    var readBooks = getReadBooks();
    var topTropes = getTopReadTropes(readBooks, 1);
    var leadTrope = getLeadTropeName(topTropes);
    var spiceLevel = getSpiceLevel(profile.spice_dial || 'balanced');
    applyReaderTheme(persona);

    if (societyDashboardTitle) societyDashboardTitle.textContent = persona.label || 'the romance reader';
    if (societyReaderBadge) societyReaderBadge.innerHTML = getPersonaBadgeMarkup();
    if (societyReaderBio) societyReaderBio.textContent = getReaderTypeVsTropeText(persona, leadTrope);
    if (societyReaderFootnote) societyReaderFootnote.textContent = getReaderTypeWhyText(persona, leadTrope) + ' Theme: ' + ((persona.theme && persona.theme.name) || 'unsorted silver') + '.';
    if (societySaveCount) societySaveCount.textContent = getShelf().length ? (getShelf().length + ' saved') : 'suggested';
    if (societyReadCount) societyReadCount.textContent = String(readBooks.length);
    if (societyTopTrope) societyTopTrope.textContent = leadTrope || 'still learning';
    if (societyThemeButton) societyThemeButton.textContent = 'reader theme';
    if (societyHeatBar){
      Array.prototype.forEach.call(societyHeatBar.children, function(segment, index){
        segment.classList.toggle('is-on', index < spiceLevel);
      });
    }

    renderSocietyShelfPreview();
    renderSocietyBoyfriendMatch();
    renderSocietyTropeDna(readBooks);
    renderSocietyPerks();
  }

  function getBookThemeColor(book, index){
    var themePalette = {
      rose_ribbon: [['#F4C0D1', '#993556'], ['#FBEAF0', '#72243E'], ['#ED93B1', '#72243E']],
      stormy_blue: [['#3C3489', '#CECBF6'], ['#1E2445', '#AEBBFF'], ['#5E6BC4', '#F6F4FF']],
      pearl_white: [['#9FE1CB', '#085041'], ['#F2D982', '#64470A'], ['#DDECC8', '#365316']],
      obsession_red: [['#FAC775', '#854F0B'], ['#F5C4B3', '#993C1D'], ['#FFE6A7', '#5D3303']],
      dark_hearts: [['#27202F', '#D6D0E3'], ['#3A2532', '#F4C0D1'], ['#1B1A22', '#CECBF6']],
      royal_violet: [['#CEB3F2', '#3C3489'], ['#BFA1FF', '#28175A'], ['#E1D4FF', '#4B2280']]
    };
    var palette = themePalette[getThemeProfile().key] || themePalette.rose_ribbon;
    return palette[index % palette.length];
  }

  function getDashboardCoverMarkup(book, index, compact){
    var colors = getBookThemeColor(book, index);
    var handle = book && book.handle ? String(book.handle) : '';
    var source = handle ? (
      root.querySelector('.sss-mfy__sourceGrid .sss-lib__book[data-handle="' + handle + '"] .sss-lib__coverWrap') ||
      document.querySelector('.sss-lib__book[data-handle="' + handle + '"] .sss-lib__coverWrap')
    ) : null;

    if (source){
      var clone = source.cloneNode(true);
      clone.querySelectorAll('[data-heart], button').forEach(function(item){
        item.remove();
      });
      clone.setAttribute('data-book-rating-cover', '');
      clone.dataset.handle = book && book.handle ? String(book.handle) : (clone.dataset.handle || '');
      clone.dataset.title = book && book.title ? String(book.title) : (clone.dataset.title || '');
      applyRatingStamp(clone, getBookRating(book));
      return clone.outerHTML;
    }

    var rating = getBookRating(book);
    var ratingStamp = rating ? '<div class="sss-lib__ratingStamp" data-book-rating-stamp aria-label="' + rating + ' out of 5 stars">' + mfyEscape(ratingStampText(rating)) + '</div>' : '';

    return '<div class="' + (compact ? 'sss-mfy__fallbackCover sss-mfy__fallbackCover--mini' : 'sss-mfy__fallbackCover') + '" data-book-rating-cover data-handle="' + mfyEscape(book && book.handle || '') + '" data-title="' + mfyEscape(book && book.title || '') + '" style="--mfy-rec-bg:' + colors[0] + ';--mfy-rec-ink:' + colors[1] + '">' + ratingStamp + '<span>' + mfyEscape(book && book.title || 'book') + '</span></div>';
  }

  function getSocietyPreviewTropes(book, limit){
    return (Array.isArray(book && book.tropes) ? book.tropes : []).filter(Boolean).slice(0, limit || 2);
  }

  function renderSocietyReadNextPreview(entries){
    var picks = (entries || []).filter(function(entry){
      return entry && entry.book;
    }).slice(0, 3);

    if (!picks.length){
      return '<div class="sss-mfy__societyPerkExamples"><em>profile picks are loading.</em></div>';
    }

    return '<div class="sss-mfy__societyReadPreview">' + picks.map(function(entry, index){
      var book = entry.book || {};
      var tropes = getSocietyPreviewTropes(book, 2);
      return '<article class="sss-mfy__societyReadItem">' +
        '<div class="sss-mfy__societyReadCover">' + getDashboardCoverMarkup(book, index, true) + '</div>' +
        '<div class="sss-mfy__societyReadMeta">' +
          '<b>' + mfyEscape(mfyDecodeEntities(book.title || 'your next read')) + '</b>' +
          '<div>' + tropes.map(function(trope){ return '<i>' + mfyEscape(trope) + '</i>'; }).join('') + '</div>' +
        '</div>' +
      '</article>';
    }).join('') + '</div>';
  }

  function renderSocietyNotesPreview(entries){
    var pick = (entries || []).filter(function(entry){
      return entry && entry.book;
    })[0];
    var book = pick && pick.book ? pick.book : {};
    var title = mfyDecodeEntities(book.title || 'your next read');
    var trope = getSocietyPreviewTropes(book, 1)[0] || profile.favorite_trope || 'reader signal';

    return '<div class="sss-mfy__societyNotesPreview">' +
      '<div class="sss-mfy__societyNotesCover">' + getDashboardCoverMarkup(book, 0, true) + '</div>' +
      '<div class="sss-mfy__societyNotePaper">' +
        '<b>' + mfyEscape(title) + '</b>' +
        '<p>"the exact kind of ' + mfyEscape(String(trope).toLowerCase()) + ' chaos i want saved for later."</p>' +
        '<small>private note mockup</small>' +
      '</div>' +
    '</div>';
  }

  function getDashboardWhy(book, index){
    var bookTropes = (book && book.tropes || []).map(normalize);
    if (profile.favorite_trope && bookTropes.indexOf(normalize(profile.favorite_trope)) > -1){
      return 'matches your tropes + heat exactly';
    }
    if (getSpiceDialReason(book)){
      return 'hits your saved spice lane';
    }
    if (profile.craving === 'slowburn') return 'slow burn readers always come here';
    if (index === 2) return 'wildcard pick for your current vibe';
    return 'based on your profile';
  }

  function renderDashboardRecCard(entry, index){
    var book = entry && entry.book ? entry.book : {};
    var colors = getBookThemeColor(book, index);
    var labels = ['closest match', 'trope twin', 'wildcard pick'];
    var spice = Math.max(0, Math.min(5, Number(book.spice || 0)));
    var tropes = (book.tropes || []).slice(0, 2);
    return '<article class="sss-mfy__dashRec" role="button" tabindex="0" data-mfy-dashboard-book data-handle="' + mfyEscape(book.handle || '') + '" data-title="' + mfyEscape(book.title || '') + '">' +
      '<div class="sss-mfy__dashRecCover" style="--mfy-rec-bg:' + colors[0] + ';--mfy-rec-ink:' + colors[1] + '">' + getDashboardCoverMarkup(book, index, false) + '</div>' +
      '<div class="sss-mfy__dashRecBody">' +
        '<div class="sss-mfy__dashRecLabel">' + mfyEscape(labels[index] || 'made for you') + '</div>' +
        '<div class="sss-mfy__dashRecTitle">' + mfyEscape(book.title || 'your next read') + '</div>' +
        '<div class="sss-mfy__dashRecAuthor">' + mfyEscape(book.author || '') + '</div>' +
        '<div class="sss-mfy__dashSpice" aria-label="' + spice + ' out of 5 spice">' +
          [1,2,3,4,5].map(function(n){ return '<span class="' + (n <= spice ? 'is-on' : '') + '"></span>'; }).join('') +
        '</div>' +
        '<div class="sss-mfy__dashTags">' + tropes.map(function(trope){ return '<span>' + mfyEscape(trope) + '</span>'; }).join('') + '</div>' +
        '<div class="sss-mfy__dashWhy">' + mfyEscape(getDashboardWhy(book, index)) + '</div>' +
      '</div>' +
    '</article>';
  }

  function getReadNextReasons(book){
    var reasons = [];
    var bookTropes = (book && book.tropes || []).map(normalize);
    var status = getBookStatus({ handle: book && book.handle, title: book && book.title });
    var saved = getShelf().find(function(item){
      return getBookStatusKey(item) === getBookStatusKey(book);
    });

    if (profile.favorite_trope && bookTropes.indexOf(normalize(profile.favorite_trope)) > -1){
      reasons.push('favorite trope');
    }
    if (getSpiceDialReason(book)){
      reasons.push('spice match');
    }
    if (status === 'reading'){
      reasons.push('already reading');
    } else if (status === 'tbr' || saved){
      reasons.push('saved shelf');
    }
    if (profile.craving && tokenLabels.craving && tokenLabels.craving[profile.craving]){
      reasons.push(tokenLabels.craving[profile.craving]);
    }

    return reasons.filter(Boolean).slice(0, 4);
  }

  function getWeeklyReadNextPicks(entries){
    var picks = (entries || []).filter(function(entry){
      return entry && entry.book && entry.score > -999 && !isSoftProfileIncompatibleBook(entry.book);
    }).slice(0, 3);
    var seen = {};

    picks.forEach(function(entry){
      var key = getBookStatusKey(entry.book || {});
      if (key) seen[key] = true;
    });

    if (picks.length < 3){
      books
        .map(function(book){
          return { book: book, score: scoreQuizOnlyBook(book) };
        })
        .filter(function(entry){
          var key = getBookStatusKey(entry.book || {});
          return entry && entry.book && entry.score > -999 && key && !seen[key] && !isSoftProfileIncompatibleBook(entry.book);
        })
        .sort(function(a, b){
          return b.score - a.score;
        })
        .slice(0, 3 - picks.length)
        .forEach(function(entry){
          var key = getBookStatusKey(entry.book || {});
          if (key) seen[key] = true;
          picks.push(entry);
        });
    }

    return picks;
  }

  function renderNextOpinion(entries){
    if (!nextOpinionEl) return;

    var picks = getWeeklyReadNextPicks(entries);

    if (!picks.length){
      if (nextOpinionEl.children.length) return;
      nextOpinionEl.innerHTML = '<div class="sss-mfy__empty">answer the quiz and your first three profile picks will land here.</div>';
      return;
    }

    nextOpinionEl.innerHTML = picks.map(function(entry, index){
      var book = entry.book || {};
      var colors = getBookThemeColor(book, index);
      var reasons = getReadNextReasons(book);
      var label = index === 0 ? 'our pick' : (index === 1 ? 'backup plan' : 'wildcard');
      var spice = Math.max(0, Math.min(5, Number(book.spice || 0)));
      return '<article class="sss-mfy__nextOpinionCard' + (index === 0 ? ' sss-mfy__nextOpinionCard--primary' : '') + '">' +
        '<div class="sss-mfy__nextOpinionCover" style="--mfy-rec-bg:' + colors[0] + ';--mfy-rec-ink:' + colors[1] + '">' + getDashboardCoverMarkup(book, index, index !== 0) + '</div>' +
        '<div class="sss-mfy__nextOpinionBody">' +
          '<span class="sss-mfy__nextOpinionLabel">' + mfyEscape(label) + '</span>' +
          '<strong>' + mfyEscape(book.title || 'your next read') + '</strong>' +
          '<small>' + mfyEscape(book.author || '') + '</small>' +
          '<div class="sss-mfy__dashSpice" aria-label="' + spice + ' out of 5 spice">' +
            [1,2,3,4,5].map(function(n){ return '<span class="' + (n <= spice ? 'is-on' : '') + '"></span>'; }).join('') +
          '</div>' +
          '<p>' + mfyEscape(index === 0 ? getDashboardWhy(book, index) : 'kept close because it still fits your reader pattern.') + '</p>' +
          '<div class="sss-mfy__nextOpinionReasons">' + reasons.map(function(reason){ return '<span>' + mfyEscape(reason) + '</span>'; }).join('') + '</div>' +
        '</div>' +
      '</article>';
    }).join('');
  }

  function renderDashboardBookshelf(){
    if (!dashboardBookshelf) return;
    bookshelfTabButtons.forEach(function(button){
      button.classList.toggle('is-active', (button.getAttribute('data-mfy-bookshelf-tab') || 'reading') === dashboardBookshelfTab);
    });
    var statusMap = { reading: 'reading', tbr: 'tbr', read: 'read' };
    var targetStatus = statusMap[dashboardBookshelfTab] || 'reading';
    var entries = books.filter(function(book){
      return getBookStatus({ handle: book.handle, title: book.title }) === targetStatus;
    }).slice(0, 3);
    if (!entries.length){
      entries = getReaderTypeRankedBooks(3);
    }
    if (entries.length < 3){
      var seen = {};
      entries.forEach(function(book){
        var key = getBookStatusKey(book || {});
        if (key) seen[key] = true;
      });
      getWeeklyReadNextPicks().forEach(function(entry){
        var book = entry && entry.book ? entry.book : null;
        var key = getBookStatusKey(book || {});
        if (book && key && !seen[key] && entries.length < 3){
          seen[key] = true;
          entries.push(book);
        }
      });
    }
    if (entries.length < 3){
      books.forEach(function(book){
        var key = getBookStatusKey(book || {});
        if (book && key && !seen[key] && entries.length < 3){
          seen[key] = true;
          entries.push(book);
        }
      });
    }
    dashboardBookshelf.innerHTML = entries.length ? entries.map(function(book, index){
      var colors = getBookThemeColor(book, index);
      var tropes = (book.tropes || []).slice(0, 2).map(function(trope){ return '<span>' + mfyEscape(trope) + '</span>'; }).join('');
      return '<div class="sss-mfy__bookRow">' +
        '<div class="sss-mfy__miniCover" style="--mfy-rec-bg:' + colors[0] + ';--mfy-rec-ink:' + colors[1] + '">' + getDashboardCoverMarkup(book, index, true) + '</div>' +
        '<div><strong>' + mfyEscape(book.title || 'untitled') + '</strong><small>' + mfyEscape(book.author || '') + '</small><div>' + tropes + '</div></div>' +
      '</div>';
    }).join('') : '<div class="sss-mfy__empty">mark books in the library and they’ll show up here.</div>';
  }

  function getBestBoyfriendBook(){
    return books
      .map(function(book){
        var boyfriendScore = scoreBoyfriendMatch(book);
        var bookScore = scoreBook(book);
        return { book: book, score: boyfriendScore + Math.max(0, bookScore / 4) };
      })
      .filter(function(entry){
        var book = entry && entry.book ? entry.book : {};
        return entry.score > -999 && (!!String(book.boyfriend_name || '').trim() || !!String(book.boyfriend_type || '').trim());
      })
      .sort(function(a, b){ return b.score - a.score; })[0];
  }

  function getFictionalBfTypeLabel(book){
    var type = canonicalBoyfriendType(book && book.boyfriend_type);
    return tokenLabels.fictional_man[type] || String(book && book.boyfriend_type || '').trim() || 'fictional boyfriend';
  }

  function mfyMatchKey(value){
    return normalize(mfyDecodeEntities(value)).replace(/[^a-z0-9]+/g, ' ').trim();
  }

  function mfyLooseKeyMatch(candidate, target){
    var candidateKey = mfyMatchKey(candidate);
    var targetKey = mfyMatchKey(target);
    if (!candidateKey || !targetKey) return false;
    if (candidateKey === targetKey) return true;
    if (targetKey.length >= 4 && candidateKey.indexOf(targetKey) > -1) return true;
    if (candidateKey.length >= 4 && targetKey.indexOf(candidateKey) > -1) return true;
    return false;
  }

  function mfyProfileSearchText(profileEntry){
    if (!profileEntry) return '';
    return [
      profileEntry.name,
      profileEntry.bookTitle,
      profileEntry.descriptor,
      profileEntry.hook,
      profileEntry.shelf,
      (profileEntry.traits || []).join(' '),
      (profileEntry.traitLabels || []).join(' '),
      (profileEntry.tropes || []).join(' ')
    ].map(mfyMatchKey).join(' ');
  }

  function getFictionalBfProfile(book, boyfriendName, sourceTitle){
    var nameKey = mfyMatchKey(boyfriendName);
    var bookKey = mfyMatchKey(sourceTitle || (book && book.title));
    if (!boyfriendLibrary.length) return null;

    if (nameKey){
      var byName = boyfriendLibrary.find(function(profileEntry){
        return mfyMatchKey(profileEntry && profileEntry.name) === nameKey;
      });
      if (byName) return byName;

      var byLooseName = boyfriendLibrary.find(function(profileEntry){
        return mfyLooseKeyMatch(profileEntry && profileEntry.name, nameKey);
      });
      if (byLooseName) return byLooseName;
    }

    if (bookKey){
      var byBook = boyfriendLibrary.find(function(profileEntry){
        return mfyMatchKey(profileEntry && profileEntry.bookTitle) === bookKey;
      });
      if (byBook) return byBook;

      var byLooseBook = boyfriendLibrary.find(function(profileEntry){
        return mfyLooseKeyMatch(profileEntry && profileEntry.bookTitle, bookKey);
      });
      if (byLooseBook) return byLooseBook;
    }

    var typeLabel = getFictionalBfTypeLabel(book);
    var typeWords = mfyMatchKey(typeLabel).split(' ').filter(function(word){ return word.length >= 5; });
    if (!nameKey && !bookKey && typeWords.length){
      var byType = boyfriendLibrary.find(function(profileEntry){
        var searchText = mfyProfileSearchText(profileEntry);
        return typeWords.some(function(word){ return searchText.indexOf(word) > -1; });
      });
      if (byType) return byType;
    }

    return (!nameKey && !bookKey) ? (boyfriendLibrary[0] || null) : null;
  }

  function renderFictionalBfPortrait(profileEntry, title){
    var image = profileEntry && (profileEntry.imageFull || profileEntry.image) ? (profileEntry.imageFull || profileEntry.image) : '';
    var name = profileEntry && profileEntry.name ? profileEntry.name : title;
    if (image){
      return '<img src="' + mfyEscape(image) + '" alt="' + mfyEscape(name || 'fictional boyfriend') + '" loading="lazy" decoding="async">';
    }
    return '<div class="sss-mfy__fictionalBfFallback"><span>' + mfyEscape((name || 'quiz').split(' ').slice(0, 2).join(' ')) + '</span></div>';
  }

  function getReaderTypeBoyfriendTypes(personaKey){
    return {
      chaos_reader: ['stalker', 'morally_gray_villain', 'mafia_boss', 'bully'],
      dark_romance_girlie: ['morally_gray_villain', 'stalker', 'mafia_boss', 'obsessive_protector'],
      tension_addict: ['academic_rival', 'cold_grump', 'arrogant_asshole', 'obsessive_protector'],
      fantasy_girlie: ['tortured_prince', 'morally_gray_villain', 'cold_grump'],
      jersey_chaser: ['athlete_with_heart'],
      fake_dating_fanatic: ['sweetheart', 'arrogant_asshole', 'athlete_with_heart'],
      slow_burn_girlie: ['cold_grump', 'emotionally_unavailable_man', 'academic_rival', 'tortured_prince'],
      sweet_romance_devotee: ['sweetheart', 'athlete_with_heart', 'obsessive_protector'],
      romance_reader: ['sweetheart', 'cold_grump', 'obsessive_protector']
    }[personaKey || ''] || ['sweetheart'];
  }

  function getReaderTypeBoyfriendNames(personaKey){
    return {
      chaos_reader: ['tiernan callaghan', 'zade meadows', 'aaron warner'],
      dark_romance_girlie: ['thiago el diablo da silva', 'tiernan callaghan', 'zade meadows'],
      fantasy_girlie: ['aaron warner', 'kai rhodes', 'zade meadows']
    }[personaKey || ''] || [];
  }

  function findBoyfriendProfileByNames(names){
    if (!boyfriendLibrary.length || !names || !names.length) return null;
    for (var i = 0; i < names.length; i += 1){
      var target = mfyMatchKey(names[i]);
      var looseTarget = target.replace(/\s+/g, '');
      var matched = boyfriendLibrary.find(function(profileEntry){
        var name = mfyMatchKey(profileEntry && profileEntry.name);
        return name === target || name.replace(/\s+/g, '') === looseTarget || mfyLooseKeyMatch(profileEntry && profileEntry.name, target);
      });
      if (matched) return matched;
    }
    return null;
  }

  function normalizeSavedFictionalBoyfriend(value){
    if (!value || typeof value !== 'object') return null;
    var name = String(value.name || '').trim();
    var profileId = String(value.profile_id || value.profileid || value.id || '').trim();
    var bookTitle = String(value.book_title || value.booktitle || value.bookTitle || '').trim();
    var resultType = canonicalBoyfriendType(value.result_type || value.resulttype || value.type || value.fictional_man || '');

    if (!name && !profileId && !bookTitle) return null;

    return {
      source: String(value.source || 'fictional_boyfriend_quiz'),
      matchedAt: String(value.matched_at || value.matchedat || value.matchedAt || value.completed_at || ''),
      profileId: profileId,
      name: name,
      url: String(value.url || ''),
      image: String(value.image || value.image_full || value.imagefull || ''),
      imageFull: String(value.image_full || value.imagefull || value.image || ''),
      bookId: String(value.book_id || value.bookid || ''),
      bookTitle: bookTitle,
      bookUrl: String(value.book_url || value.bookurl || value.bookUrl || ''),
      bookCover: String(value.book_cover || value.bookcover || value.bookCover || ''),
      author: String(value.author || ''),
      shelf: String(value.shelf || ''),
      descriptor: String(value.descriptor || ''),
      resultType: resultType,
      resultTitle: String(value.result_title || value.resulttitle || ''),
      resultKicker: String(value.result_kicker || value.resultkicker || ''),
      resultCopy: String(value.result_copy || value.resultcopy || ''),
      tropes: Array.isArray(value.tropes) ? value.tropes : [],
      traits: Array.isArray(value.traits) ? value.traits : [],
      traitLabels: Array.isArray(value.trait_labels || value.traitlabels || value.traitLabels) ? (value.trait_labels || value.traitlabels || value.traitLabels) : [],
      scores: value.scores && typeof value.scores === 'object' ? value.scores : {}
    };
  }

  function getSavedFictionalBoyfriend(sourceProfile){
    var nextProfile = sourceProfile || profile || {};
    var direct = normalizeSavedFictionalBoyfriend(nextProfile.fictional_boyfriend);
    if (direct) return direct;

    var latest = nextProfile.quiz_evidence && nextProfile.quiz_evidence.latest ? nextProfile.quiz_evidence.latest : null;
    var byType = nextProfile.quiz_evidence && nextProfile.quiz_evidence.by_type ? nextProfile.quiz_evidence.by_type : {};
    var boyfriendEvidence = (latest && latest.quiz_type === 'boyfriend') ? latest : (byType && byType.boyfriend ? byType.boyfriend : null);
    if (!boyfriendEvidence || typeof boyfriendEvidence !== 'object') return null;

    return normalizeSavedFictionalBoyfriend(boyfriendEvidence.fictional_boyfriend || boyfriendEvidence.boyfriend_match || null);
  }

  function getSavedFictionalBoyfriendProfile(saved){
    if (!saved) return null;
    if (saved.profileId && boyfriendLibrary.length){
      var byId = boyfriendLibrary.find(function(profileEntry){
        return String(profileEntry && profileEntry.id || '') === saved.profileId;
      });
      if (byId) return byId;
    }

    return getFictionalBfProfile(
      {
        title: saved.bookTitle,
        boyfriend_name: saved.name,
        boyfriend_type: saved.resultType,
        cover: saved.bookCover
      },
      saved.name,
      saved.bookTitle
    );
  }

  function getProfileTypeMatch(profileEntry, typeKey){
    var searchText = mfyProfileSearchText(profileEntry);
    var typeLabel = tokenLabels.fictional_man[typeKey] || String(typeKey || '').replace(/_/g, ' ');
    var words = mfyMatchKey(typeLabel).split(' ').filter(function(word){ return word.length >= 4; });
    var boosts = fictionalManProfiles[typeKey] || {};
    var boostWords = (boosts.boyfriendBoosts || []).concat(boosts.tropeBoosts || []).join(' ');
    var boostTokens = mfyMatchKey(boostWords).split(' ').filter(function(word){ return word.length >= 4; });
    return words.concat(boostTokens).some(function(word){ return searchText.indexOf(word) > -1; });
  }

  function getPaidFictionalBfMatch(){
    var persona = getPersonaProfile();
    var personaKey = persona && persona.key ? persona.key : 'romance_reader';
    var savedBoyfriend = getSavedFictionalBoyfriend(profile);
    if (savedBoyfriend){
      var savedProfile = getSavedFictionalBoyfriendProfile(savedBoyfriend);
      var savedType = canonicalBoyfriendType(savedBoyfriend.resultType || profile.fictional_man || '');
      var savedBook = books.find(function(book){
        return mfyMatchKey(book && book.title) === mfyMatchKey(savedBoyfriend.bookTitle) ||
          mfyMatchKey(book && book.boyfriend_name) === mfyMatchKey(savedBoyfriend.name);
      }) || {
        title: savedBoyfriend.bookTitle,
        author: savedBoyfriend.author,
        cover: savedBoyfriend.bookCover,
        boyfriend_name: savedBoyfriend.name,
        boyfriend_type: savedType,
        shelf: savedBoyfriend.shelf,
        tropes: savedBoyfriend.tropes || []
      };

      return {
        persona: persona,
        typeKey: savedType || 'obsessive_protector',
        typeLabel: tokenLabels.fictional_man[savedType] || savedBoyfriend.descriptor || 'fictional boyfriend',
        profileEntry: savedProfile || {
          id: savedBoyfriend.profileId,
          name: savedBoyfriend.name,
          url: savedBoyfriend.url,
          image: savedBoyfriend.image,
          imageFull: savedBoyfriend.imageFull,
          bookTitle: savedBoyfriend.bookTitle,
          bookUrl: savedBoyfriend.bookUrl,
          bookCover: savedBoyfriend.bookCover,
          author: savedBoyfriend.author,
          shelf: savedBoyfriend.shelf,
          descriptor: savedBoyfriend.descriptor,
          tropes: savedBoyfriend.tropes,
          traits: savedBoyfriend.traits,
          traitLabels: savedBoyfriend.traitLabels,
          scores: savedBoyfriend.scores
        },
        book: savedBook,
        body: fictionalManProfiles[savedType] || null,
        source: 'quiz',
        savedBoyfriend: savedBoyfriend
      };
    }
    var personaNames = getReaderTypeBoyfriendNames(personaKey);
    var explicitProfile = findBoyfriendProfileByNames(personaNames);
    if (explicitProfile){
      var explicitNameKey = mfyMatchKey(explicitProfile.name);
      var explicitBook = books.find(function(book){
        return mfyMatchKey(book && book.boyfriend_name) === explicitNameKey || mfyLooseKeyMatch(book && book.boyfriend_name, explicitNameKey);
      }) || null;
      var explicitType = canonicalBoyfriendType(explicitBook && explicitBook.boyfriend_type) || 'morally_gray_villain';
      return {
        persona: persona,
        typeKey: explicitType,
        typeLabel: tokenLabels.fictional_man[explicitType] || 'fictional boyfriend',
        profileEntry: explicitProfile,
        book: explicitBook,
        body: fictionalManProfiles[explicitType] || null,
        source: 'reader_type'
      };
    }

    var typeOrder = getReaderTypeBoyfriendTypes(personaKey);
    var bestBookEntry = getBestBoyfriendBook();
    var bestBook = bestBookEntry && bestBookEntry.book ? bestBookEntry.book : null;

    for (var i = 0; i < typeOrder.length; i += 1){
      var typeKey = canonicalBoyfriendType(typeOrder[i]);
      var profileBody = fictionalManProfiles[typeKey] || null;
      var matchingBook = bestBook && canonicalBoyfriendType(bestBook.boyfriend_type) === typeKey ? bestBook : null;
      if (!matchingBook){
        matchingBook = books.find(function(book){
          return canonicalBoyfriendType(book && book.boyfriend_type) === typeKey;
        }) || null;
      }

      var matchingProfile = null;
      if (boyfriendLibrary.length){
        matchingProfile = boyfriendLibrary.find(function(profileEntry){
          return getProfileTypeMatch(profileEntry, typeKey);
        }) || null;
      }

      if (matchingProfile || matchingBook || profileBody){
        return {
          persona: persona,
          typeKey: typeKey,
          typeLabel: tokenLabels.fictional_man[typeKey] || 'fictional boyfriend',
          profileEntry: matchingProfile,
          book: matchingBook,
          body: profileBody
        };
      }
    }

    return {
      persona: persona,
      typeKey: 'sweetheart',
      typeLabel: tokenLabels.fictional_man.sweetheart || 'fictional boyfriend',
      profileEntry: boyfriendLibrary[0] || null,
      book: bestBook,
      body: fictionalManProfiles.sweetheart || null
    };
  }

  function renderFictionalBfCard(){
    if (!fictionalBfCard) return;

    if (profile.dashboard_built){
      var paidMatch = getPaidFictionalBfMatch();
      var paidBook = paidMatch.book || {};
      var paidProfile = paidMatch.profileEntry || getFictionalBfProfile(paidBook, paidBook.boyfriend_name || '', paidBook.title || '');
      var paidTitle = paidProfile && paidProfile.name ? paidProfile.name : (paidBook.boyfriend_name || paidMatch.typeLabel || 'fictional boyfriend match');
      var paidUrl = paidProfile && paidProfile.url ? paidProfile.url : '/fictional-boyfriends/';
      var personaLabel = (paidMatch.persona && paidMatch.persona.label) || 'reader type';
      var paidCopy = personaLabel + ' points your profile toward ' + paidMatch.typeLabel + ' energy.';
      if (paidMatch.source === 'quiz'){
        paidCopy = 'your fictional boyfriend quiz locked this match onto your reader profile.';
        if (paidProfile && paidProfile.bookTitle){
          paidCopy += ' he is tied to ' + paidProfile.bookTitle + '.';
        } else if (paidBook.title){
          paidCopy += ' the closest book signal is ' + paidBook.title + '.';
        }
      } else if (paidMatch.source === 'reader_type' && paidProfile){
        paidCopy = personaLabel + ' points your profile toward ' + paidTitle + (paidProfile.bookTitle ? (' from ' + paidProfile.bookTitle) : '') + '.';
      } else if (paidBook.title){
        paidCopy += ' the closest shelf signal right now is ' + paidBook.title + '.';
      }

      if (fictionalBfLabel){
        fictionalBfLabel.textContent = paidMatch.source === 'quiz' ? 'saved from your quiz' : 'match from your reader type';
      }

      fictionalBfCard.innerHTML =
        '<div class="sss-mfy__fictionalBfCopy">' +
          '<span>💘 fictional boyfriend match</span>' +
          '<strong>' + mfyEscape(paidTitle) + '</strong>' +
          '<p>' + mfyEscape(paidCopy) + '</p>' +
          (paidMatch.body && paidMatch.body.body ? '<small>' + mfyEscape(paidMatch.body.body) + '</small>' : '') +
          '<div class="sss-mfy__fictionalBfActions">' +
            '<a href="/fictional-boyfriend-quiz/">take quiz →</a>' +
            '<a href="' + mfyEscape(paidUrl) + '">' + mfyEscape(paidProfile ? 'open profile →' : 'meet the lineup →') + '</a>' +
          '</div>' +
        '</div>' +
        '<a class="sss-mfy__fictionalBfBook" href="' + mfyEscape(paidUrl) + '">' +
          renderFictionalBfPortrait(paidProfile, paidTitle) +
          '<span>' + mfyEscape(paidProfile && paidProfile.name ? paidProfile.name : paidMatch.typeLabel) + '</span>' +
        '</a>';
      return;
    }

    var favoriteHandle = String(profile.favorite_book || '').trim();
    var favoriteBook = favoriteHandle && favoriteBookMap[favoriteHandle] ? favoriteBookMap[favoriteHandle] : null;
    var suggestedEntry = getBestBoyfriendBook();
    var suggestedBook = suggestedEntry && suggestedEntry.book ? suggestedEntry.book : null;
    var isCustomFavorite = favoriteHandle.indexOf('custom:') === 0 && !favoriteBook;
    var customTitle = isCustomFavorite ? favoriteHandle.replace(/^custom:/, '') : '';
    var hasFavorite = !!favoriteBook || !!customTitle;
    var book = favoriteBook || (!hasFavorite ? suggestedBook : null);
    var boyfriendName = book ? String(book.boyfriend_name || '').trim() : '';
    var boyfriendType = book ? getFictionalBfTypeLabel(book) : (profile.fictional_man ? tokenLabels.fictional_man[profile.fictional_man] : '');
    var profileBody = fictionalManProfiles[canonicalBoyfriendType(book && book.boyfriend_type)] || fictionalManProfiles[profile.fictional_man];
    var title = boyfriendName || boyfriendType || 'find your fictional boyfriend';
    var sourceTitle = book && book.title ? book.title : customTitle;
    var boyfriendProfile = getFictionalBfProfile(book, boyfriendName, sourceTitle);
    var boyfriendUrl = boyfriendProfile && boyfriendProfile.url ? boyfriendProfile.url : '/fictional-boyfriends/';
    var portraitLabel = boyfriendProfile && boyfriendProfile.name ? boyfriendProfile.name : title;
    var copy = hasFavorite && favoriteBook && sourceTitle
      ? ('because your favorite book is ' + sourceTitle + ', your dashboard is clocking ' + (boyfriendType || 'that exact boyfriend energy') + '.')
      : hasFavorite && sourceTitle
        ? ('because your favorite book is ' + sourceTitle + ', take the quiz to lock in the fictional boyfriend energy behind it.')
      : sourceTitle
        ? ('no favorite book saved yet, so i pulled a likely match from ' + sourceTitle + '. take the quiz to make it official.')
        : 'save a favorite book or take the quiz and this card will lock onto your exact fictional boyfriend problem.';

    if (fictionalBfLabel){
      fictionalBfLabel.textContent = hasFavorite ? 'from your favorite book' : 'suggested from your matches';
    }

    fictionalBfCard.innerHTML =
      '<div class="sss-mfy__fictionalBfCopy">' +
        '<span>💘 ' + mfyEscape(hasFavorite ? 'favorite book signal' : 'suggested match') + '</span>' +
        '<strong>' + mfyEscape(title) + '</strong>' +
        '<p>' + mfyEscape(copy) + '</p>' +
        (profileBody && profileBody.body ? '<small>' + mfyEscape(profileBody.body) + '</small>' : '') +
        '<div class="sss-mfy__fictionalBfActions">' +
          '<a href="/fictional-boyfriend-quiz/">take quiz →</a>' +
          '<a href="' + mfyEscape(boyfriendUrl) + '">' + mfyEscape(boyfriendProfile ? 'open profile →' : 'meet the lineup →') + '</a>' +
        '</div>' +
      '</div>' +
      '<a class="sss-mfy__fictionalBfBook" href="' + mfyEscape(boyfriendUrl) + '">' +
        renderFictionalBfPortrait(boyfriendProfile, title) +
        '<span>' + mfyEscape(portraitLabel || 'fictional boyfriend quiz') + '</span>' +
      '</a>';
  }

  function renderQuickLinks(){
    quickLinksGrid = quickLinksGrid || document.getElementById('sssMfyQuickLinks') || root.querySelector('#sssMfyQuickLinks');
    if (!quickLinksGrid) return;
    var spiceLevel = getSpiceLevel(profile.spice_dial || 'balanced');
    var links = [
      { emoji: '🏆', name: 'reader quiz', sub: 'refresh your type', url: '/reader-quizzes/' },
      { emoji: '💘', name: 'boyfriend quiz', sub: 'find your fictional man', url: '/fictional-boyfriend-quiz/' },
      { emoji: '📚', name: 'the library', sub: 'save books + notes', url: '/library/' },
      { emoji: '🌶', name: 'spice picks', sub: 'level ' + spiceLevel + ' recs', url: '/romance-books-by-spice-level/' }
    ];
    quickLinksGrid.innerHTML = links.map(function(link){
      return '<a href="' + mfyEscape(link.url) + '"><b aria-hidden="true">' + mfyEscape(link.emoji) + '</b><span><strong>' + mfyEscape(link.name) + '</strong><small>' + mfyEscape(link.sub) + '</small></span></a>';
    }).join('');
  }

	  function getDashboardTopBook(){
	    var activeTropeLane = getActiveTropeLane();
	    var ranked = books
	      .map(function(book){ return { book: book, score: scoreBook(book) }; })
	      .filter(function(entry){ return entry.score > -999; })
	      .sort(function(a, b){ return b.score - a.score; });
	    var laneRanked = filterRankedByTropeLane(ranked, activeTropeLane);
	    return (laneRanked[0] || ranked[0]);
	  }

  function readerTypeReadFallbacks(persona, topBook){
    var key = persona && persona.key ? persona.key : 'romance_reader';
    var favoriteTrope = profile.favorite_trope || (topBook && topBook.tropes && topBook.tropes[0]) || 'romance';
    var map = {
      chaos_reader: [
        ['why choose romance guide', 'maximum plot complications', '/romance-trope-dictionary/#why-choose', 'why choose'],
        ['forbidden romance picks', 'because restraint is optional', '/romance-trope-dictionary/#forbidden-love', 'forbidden romance'],
        ['books like your current chaos', 'same-energy rec lists', '/if-you-liked-pages/', 'chaos romance']
      ],
      dark_romance_girlie: [
        ['dark romance guide', 'danger, obsession, devotion', '/romance-trope-dictionary/#dark-romance', 'dark romance'],
        ['mafia romance picks', 'morally gray and possessive', '/romance-trope-dictionary/#mafia-romance', 'mafia romance'],
        ['touch her and die books', 'protective menace energy', '/romance-trope-dictionary/#touch-her-and-die', 'touch her and die']
      ],
      fantasy_girlie: [
        ['romantasy reading guide', 'magic, stakes, yearning', '/romance-trope-dictionary/#romantasy', 'romantasy'],
        ['fated mates picks', 'destiny with teeth', '/romance-trope-dictionary/#fated-mates', 'fated mates'],
        ['fantasy romance moodboards', 'visuals for your next spiral', '/romance-book-moodboards/', 'fantasy romance']
      ],
      jersey_chaser: [
        ['sports romance guide', 'athletes with feelings', '/romance-trope-dictionary/#sports-romance', 'sports romance'],
        ['hockey romance picks', 'rink lights and soft landings', '/romance-trope-dictionary/#hockey-romance', 'hockey romance'],
        ['baseball romance picks', 'dugout longing, obviously', '/romance-trope-dictionary/#baseball-romance', 'baseball romance']
      ],
      slow_burn_girlie: [
        ['slow burn romance guide', 'the almost-touch economy', '/romance-trope-dictionary/#slow-burn', 'slow burn'],
        ['he falls first picks', 'yearning with receipts', '/romance-trope-dictionary/#he-falls-first', 'he falls first'],
        ['second chance romance', 'old feelings, new damage', '/romance-trope-dictionary/#second-chance', 'second chance romance']
      ],
      tension_addict: [
        ['enemies to lovers guide', 'banter, friction, payoff', '/romance-trope-dictionary/#enemies-to-lovers', 'enemies to lovers'],
        ['forced proximity picks', 'one room, too much tension', '/romance-trope-dictionary/#forced-proximity', 'forced proximity'],
        ['grumpy sunshine books', 'the argument becomes affection', '/romance-trope-dictionary/#grumpy-sunshine', 'grumpy sunshine']
      ],
      fake_dating_fanatic: [
        ['fake dating romance guide', 'public lie, private feelings', '/romance-trope-dictionary/#fake-dating', 'fake dating'],
        ['one bed picks', 'logistical romance problems', '/romance-trope-dictionary/#one-bed', 'one bed'],
        ['marriage of convenience', 'contract first, feelings later', '/romance-trope-dictionary/#marriage-of-convenience', 'marriage of convenience']
      ],
      sweet_romance_devotee: [
        ['friends to lovers guide', 'softness that still aches', '/romance-trope-dictionary/#friends-to-lovers', 'friends to lovers'],
        ['small town romance picks', 'comfort, gossip, porch lights', '/romance-trope-dictionary/#small-town', 'small town romance'],
        ['found family romance', 'tender chaos, chosen people', '/romance-trope-dictionary/#found-family', 'found family']
      ],
      romance_reader: [
        ['what to read next', 'fresh picks from your profile', '/what-to-read-next/', favoriteTrope],
        ['romance trope dictionary', 'browse your strongest signal', '/romance-trope-dictionary/', favoriteTrope],
        ['book moodboards', 'find the visual lane', '/romance-book-moodboards/', favoriteTrope]
      ]
    };

    return (map[key] || map.romance_reader).map(function(item){
      var trope = item[3] || favoriteTrope;
      var isTropePage = String(item[2] || '').indexOf('/romance-trope-dictionary/') > -1;
      var url = item[2];
      if (isTropePage && String(url).indexOf('#') === -1 && trope){
        url = '/romance-trope-dictionary/#' + getTropeEmojiKey(trope);
      }
      return {
        badge: isTropePage ? 'trope page' : 'blog pick',
        emoji: isTropePage ? getTropeEmojiKey(trope) : '📝',
        name: item[0],
        sub: item[1],
        url: url,
        trope: isTropePage ? trope : ''
      };
    });
  }

	  function getPostMatchScore(post, persona, topBook){
    var haystack = [
      post && post.title,
      post && post.summary,
      Array.isArray(post && post.terms) ? post.terms.join(' ') : ''
    ].join(' ').toLowerCase();
	    var score = 0;
	    var favoriteTrope = getActiveTropeLane();
	    var activeTropeAliases = getTropeLaneAliases(favoriteTrope);
	    var bookTropes = (topBook && topBook.tropes || []).map(normalize);
	    var personaTriggers = (persona && persona.triggers || []).map(normalize);

	    activeTropeAliases.forEach(function(alias){
	      if (alias && haystack.indexOf(alias.replace(/-/g, ' ')) > -1) score += 6;
	    });
	    personaTriggers.forEach(function(trigger){
	      if (trigger && haystack.indexOf(trigger.replace(/-/g, ' ')) > -1) score += 4;
	    });
    bookTropes.forEach(function(trope){
      if (trope && haystack.indexOf(trope.replace(/-/g, ' ')) > -1) score += 3;
	    });
	    if (persona && persona.label && haystack.indexOf(String(persona.label).replace(/^the\s+/i, '').toLowerCase()) > -1) score += 2;
	    (tropeLaneConflicts[favoriteTrope] || []).forEach(function(conflict){
	      if (conflict && haystack.indexOf(conflict.replace(/-/g, ' ')) > -1) score -= 20;
	    });
	    return score;
	  }

  function getReaderTypeBlogLinks(topEntry){
    var topBook = topEntry && topEntry.book ? topEntry.book : {};
    var persona = getPersonaProfile();
    var seen = {};
    var posts = (blogPostLibrary || []).map(function(post){
      return { post: post, score: getPostMatchScore(post, persona, topBook) };
	    }).sort(function(a, b){
	      return b.score - a.score;
	    }).filter(function(entry){
	      return entry && entry.score > 0 && entry.post && entry.post.title && entry.post.url;
	    }).slice(0, 3).map(function(entry){
      seen[normalizeMfyUrl(entry.post.url)] = true;
      return {
        badge: entry.score > 0 ? 'matched post' : 'blog pick',
        emoji: '📝',
        name: entry.post.title,
        sub: entry.post.summary || ((persona && persona.label ? persona.label : 'your reader type') + ' reading route'),
        url: entry.post.url,
        image: entry.post.image || '',
        alt: entry.post.alt || ''
      };
    });

    readerTypeReadFallbacks(persona, topBook).forEach(function(link){
      if (posts.length >= 3) return;
      var key = normalizeMfyUrl(link.url);
      if (seen[key]) return;
      seen[key] = true;
      posts.push(link);
    });

    return posts.slice(0, 3);
  }

  function normalizeMfyUrl(value){
    return String(value || '').trim().replace(/\/$/, '').toLowerCase();
  }

  function getMatchingNewsletter(book){
    var bookUrl = normalizeMfyUrl(book && book.newsletter);
    if (bookUrl){
      var matched = newsletterLibrary.find(function(issue){
        return normalizeMfyUrl(issue && issue.url) === bookUrl;
      });
      if (matched) return matched;
    }
    var persona = getPersonaProfile();
    var scored = (newsletterLibrary || []).map(function(issue){
      return { issue: issue, score: getPostMatchScore(issue || {}, persona, book || {}) };
    }).sort(function(a, b){
      return b.score - a.score;
    });
    if (scored.length && scored[0].score > 0){
      return scored[0].issue;
    }
    return newsletterLibrary[0] || null;
  }

  function getTropeEmojiKey(trope){
    var key = normalize(trope).replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    if (key === 'age-gap' || normalize(trope).indexOf('age gap') > -1) return 'age-gap';
    if (key === 'forbidden-romance') return 'forbidden-love';
    if (key === 'small-town-romance') return 'small-town';
    if (key === 'grumpy-sunshine') return 'grumpy-x-sunshine';
    return key || 'romance';
  }

  function renderFeatureMedia(link){
    if (link.image){
      return '<span class="sss-mfy__featureMedia sss-mfy__featureMedia--image"><b aria-hidden="true">📚</b><img src="' + mfyEscape(link.image) + '" alt="' + mfyEscape(link.alt || '') + '" loading="lazy" decoding="async" onerror="this.parentNode.classList.add(&quot;is-missing-image&quot;);this.remove();"></span>';
    }
    if (link.trope){
      var emojiKey = link.emoji || getTropeEmojiKey(link.trope);
      return '<span class="sss-mfy__featureMedia sss-mfy__featureMedia--emoji"><img class="bbb-custom-emoji" src="/wp-content/themes/wordpress-theme/assets/images/custom-emojis/' + mfyEscape(emojiKey) + '.png" alt="" aria-hidden="true" loading="lazy" decoding="async" onerror="this.parentNode.classList.add(&quot;is-missing-image&quot;);this.remove();"><b aria-hidden="true">📚</b></span>';
    }
    return '<span class="sss-mfy__featureMedia"><b aria-hidden="true">' + mfyEscape(link.emoji || '✨') + '</b></span>';
  }

  function hydrateFeatureImageFallbacks(){
    if (!featureLinksGrid) return;
    Array.prototype.forEach.call(featureLinksGrid.querySelectorAll('.sss-mfy__featureMedia img'), function(img){
      function markMissing(){
        var wrap = img.parentNode;
        if (!wrap) return;
        wrap.classList.add('is-missing-image');
        img.remove();
      }

      if (img.complete && !img.naturalWidth){
        markMissing();
        return;
      }

      img.addEventListener('error', markMissing, { once: true });
    });
  }

  function renderFeatureLinks(){
    if (!featureLinksGrid) return;
    var featureHead = root.querySelector('[data-mfy-feature-head]');
    var topEntry = getDashboardTopBook();
    var topBook = topEntry && topEntry.book ? topEntry.book : {};
    var persona = getPersonaProfile();
    var newsletter = getMatchingNewsletter(topBook);
    var newsletterSub = newsletter && newsletter.summary
      ? newsletter.summary
      : topBook.title ? ('newsletter pick near ' + topBook.title) : ((persona && persona.label ? persona.label : 'reader type') + ' dispatch');
    var links = getReaderTypeBlogLinks(topEntry);
    links.push({
      badge: 'newsletter match',
      emoji: '💌',
      name: newsletter && newsletter.title ? newsletter.title : 'latest society newsletter',
      sub: newsletterSub,
      url: newsletter && newsletter.url ? newsletter.url : '/society-newsletter-recent/',
      image: newsletter && newsletter.image ? newsletter.image : '',
      alt: newsletter && newsletter.alt ? newsletter.alt : ''
    });
    links = links.filter(function(link){
      return !!(link && link.name && link.url);
    });
    if (!links.length){
      if (featureLinksGrid.children.length){
        featureLinksGrid.hidden = false;
        if (featureHead) featureHead.hidden = false;
      }
      return;
    }
    var html = links.map(function(link){
      return '<a href="' + mfyEscape(link.url) + '">' + renderFeatureMedia(link) + '<span><em>' + mfyEscape(link.badge || 'update') + '</em><strong>' + mfyEscape(mfyDecodeEntities(link.name)) + '</strong><small>' + mfyEscape(mfyDecodeEntities(link.sub)) + '</small></span></a>';
    }).join('');
    if (!html) return;
    featureLinksGrid.innerHTML = html;
    hydrateFeatureImageFallbacks();
    featureLinksGrid.hidden = false;
    if (featureHead) featureHead.hidden = false;
  }

	  function renderRecommendations(answeredCount){
	    if (!row) return;
	    var boyfriendProfile = fictionalManProfiles[profile.fictional_man];
	    var activeTropeLane = getActiveTropeLane();
	    var quizRecommendationEntries = Array.isArray(profile.quiz_recommendations)
	      ? profile.quiz_recommendations.map(function(rec, index){
	          var handle = normalize(rec && rec.handle || '');
	          var title = normalize(rec && rec.title || '');
	          var matched = books.find(function(book){
	            return (handle && normalize(book.handle || book.book_handle) === handle) ||
	              (title && normalize(book.title || book.book_title) === title);
	          });
	          return matched ? { book: matched, score: 1000 - index } : null;
	        }).filter(Boolean)
	      : [];
	    var allRanked = books
	      .map(function(book){
	        return { book: book, score: scoreBook(book) };
      })
      .filter(function(entry){
        return entry.score > -999;
      })
	      .sort(function(a, b){
	        return b.score - a.score;
	      });
	    var laneRanked = filterRankedByTropeLane(allRanked, activeTropeLane);

	    var quizRanked = books
	      .map(function(book){
        return { book: book, score: scoreQuizOnlyBook(book) };
      })
      .filter(function(entry){
        return entry.score > -999;
      })
	      .sort(function(a, b){
	        return b.score - a.score;
	      });
	    var quizLaneRanked = filterRankedByTropeLane(quizRanked, activeTropeLane);
	    var rankedSource = quizRecommendationEntries.length >= 3 ? quizRecommendationEntries : (quizLaneRanked.length >= 3 ? quizLaneRanked : (laneRanked.length >= 3 ? laneRanked : quizRanked));
	    var ranked = rankedSource.slice(0, 3);
	    var paidOpinionRanked = laneRanked.length ? laneRanked : (allRanked.length ? allRanked : quizRanked);

	    var dominantType = getDominantBoyfriendType(ranked);
    var boyfriendCandidates = books
      .map(function(book){
        return { book: book, score: scoreBoyfriendMatch(book) };
      })
	      .filter(function(entry){
	        return entry.score > -999;
	      });
	    var laneBoyfriendCandidates = filterRankedByTropeLane(boyfriendCandidates, activeTropeLane);
	    var fallbackBoyfriendCandidates = books
	      .map(function(book){
	        return { book: book, score: scoreBook(book) };
      })
      .filter(function(entry){
        if (entry.score <= -999 || !boyfriendProfile) return false;
        var boyfriendType = canonicalBoyfriendType(entry.book && entry.book.boyfriend_type);
        return (boyfriendProfile.boyfriendBoosts || []).some(function(type){
          return boyfriendType === canonicalBoyfriendType(type);
	        });
	      });
	    var laneFallbackBoyfriendCandidates = filterRankedByTropeLane(fallbackBoyfriendCandidates, activeTropeLane);
	    var namedBoyfriendCandidates = (laneBoyfriendCandidates.length ? laneBoyfriendCandidates : boyfriendCandidates).filter(function(entry){
	      return !!String(entry && entry.book && entry.book.boyfriend_name || '').trim();
	    });
	    var namedFallbackBoyfriendCandidates = (laneFallbackBoyfriendCandidates.length ? laneFallbackBoyfriendCandidates : fallbackBoyfriendCandidates).filter(function(entry){
	      return !!String(entry && entry.book && entry.book.boyfriend_name || '').trim();
	    });
	    var namedGlobalCandidates = (laneRanked.length ? laneRanked : allRanked).filter(function(entry){
	      return !!String(entry && entry.book && entry.book.boyfriend_name || '').trim();
	    });

    function sortFeaturedCandidates(entries){
      return entries.slice().sort(function(a, b){
        var scoreDiff = (b.score || 0) - (a.score || 0);
        if (scoreDiff) return scoreDiff;
        return featuredMatchScore(b.book, dominantType) - featuredMatchScore(a.book, dominantType);
      });
    }

	    var featuredEntry = sortFeaturedCandidates(
	      namedBoyfriendCandidates.length ? namedBoyfriendCandidates :
	      laneBoyfriendCandidates.length ? laneBoyfriendCandidates :
	      boyfriendCandidates.length ? boyfriendCandidates :
	      namedFallbackBoyfriendCandidates.length ? namedFallbackBoyfriendCandidates :
	      laneFallbackBoyfriendCandidates.length ? laneFallbackBoyfriendCandidates :
	      fallbackBoyfriendCandidates.length ? fallbackBoyfriendCandidates :
	      namedGlobalCandidates.length ? namedGlobalCandidates :
	      laneRanked.length ? laneRanked :
	      allRanked
	    )[0] || ranked[0] || null;
    if (recTitle){
      recTitle.textContent = answeredCount >= 3
        ? 'the book most likely to ruin your week, beautifully'
        : 'your next read will land here';
    }
    if (personaBadge){
      personaBadge.innerHTML = getPersonaBadgeMarkup();
    }

    if (boyfriendKicker){
      boyfriendKicker.textContent = (moduleEmojiMap[getThemeProfile().emojiGroup] || ['🖤', '✨', '📚', '💌'])[1] + ' your fictional boyfriend';
    }

    row.innerHTML = '';
    if (matchBookEl){
      matchBookEl.innerHTML = '';
    }

    if (featuredEntry && featuredEntry.book && matchBookEl){
      var featuredSource = root.querySelector('.sss-mfy__sourceGrid .sss-lib__book[data-handle="' + featuredEntry.book.handle + '"]') ||
        document.querySelector('.sss-lib__book[data-handle="' + featuredEntry.book.handle + '"]');
      if (featuredSource){
        matchBookEl.appendChild(featuredSource.cloneNode(true));
      }
    }

    row.innerHTML = ranked.length
      ? ranked.map(renderDashboardRecCard).join('')
      : '<div class="sss-mfy__empty">your recommendations are loading from the full library. try refresh if this stays empty.</div>';
    renderNextOpinion(paidOpinionRanked);

    var featuredBookBtn = matchBookEl ? matchBookEl.querySelector('.sss-lib__book') : null;
    var featuredBoyfriendName = featuredBookBtn ? String(featuredBookBtn.dataset.boyfriendName || '').trim() : '';
    if (!featuredBoyfriendName && featuredEntry && featuredEntry.book){
      featuredBoyfriendName = String(featuredEntry.book.boyfriend_name || '').trim();
    }
    if (!featuredBoyfriendName){
      var fallbackNamedEntry = sortFeaturedCandidates(
        namedBoyfriendCandidates.length ? namedBoyfriendCandidates :
        namedFallbackBoyfriendCandidates.length ? namedFallbackBoyfriendCandidates :
        namedGlobalCandidates
      )[0];
      if (fallbackNamedEntry && fallbackNamedEntry.book){
        featuredBoyfriendName = String(fallbackNamedEntry.book.boyfriend_name || '').trim();
      }
    }

    typeTitle.textContent = featuredBoyfriendName || (fictionalManProfiles[profile.fictional_man] ? tokenLabels.fictional_man[profile.fictional_man] : 'currently unreadable');
    typeBody.textContent = fictionalManProfiles[profile.fictional_man]
      ? fictionalManProfiles[profile.fictional_man].body
      : 'this is where i’ll lovingly explain what your taste in fictional men says about you.';

    if (matchBookEl && !matchBookEl.children.length){
      matchBookEl.innerHTML = '<div class="sss-mfy__empty">your featured match will appear here.</div>';
    }

    Array.prototype.forEach.call(row.children, function(child, index){
      child.style.setProperty('--mfy-delay', (index * 120) + 'ms');
    });
    if (matchBookEl && matchBookEl.firstElementChild){
      matchBookEl.firstElementChild.style.setProperty('--mfy-delay', '360ms');
    }

    renderDashboardBookshelf();
    renderQuickLinks();

    root.querySelectorAll('#sssMfyMatchBook [data-heart]').forEach(function(heart){
      var bookBtn = heart.closest('.sss-lib__book');
      if (!bookBtn) return;

      var saved = getShelf().find(function(item){
        return item.title === bookBtn.dataset.title;
      });

      heart.classList.toggle('is-saved', !!saved);
      applyHeartSavedState(heart, !!saved);

      heart.addEventListener('click', function(e){
        e.stopPropagation();
        var original = root.querySelector('.sss-mfy__sourceGrid .sss-lib__book[data-handle="' + bookBtn.dataset.handle + '"] [data-heart]') ||
          document.querySelector('.sss-lib__grid .sss-lib__book[data-handle="' + bookBtn.dataset.handle + '"] [data-heart]');
        if (original) original.click();
      });
    });

    syncBookStatusUI();
  }

  function populateFavoriteBookSelect(){
    books.slice().sort(function(a, b){
      return String(a.title || '').localeCompare(String(b.title || ''));
    }).forEach(function(book){
      if (!book || !book.handle || favoriteBookMap[book.handle]) return;
      favoriteBookMap[book.handle] = book;
    });
    renderFavoriteBookResults('');
  }

  function syncAddonDrafts(){
    draftHardNos = Array.isArray(profile.hard_nos) ? profile.hard_nos.slice() : [];
    draftManDial = profile.spice_dial || 'soft_open_door';
    draftFavoriteTrope = profile.favorite_trope || '';
    draftFavoriteBook = profile.favorite_book || '';
  }

  function setDraftFavoriteBook(handle){
    draftFavoriteBook = handle || '';
    if (favoriteBookSearchInput){
      var book = favoriteBookMap[draftFavoriteBook];
      favoriteBookSearchInput.value = book
        ? (book.title + (book.author ? ' — ' + book.author : ''))
        : (draftFavoriteBook.indexOf('custom:') === 0 ? draftFavoriteBook.replace(/^custom:/, '') : '');
    }
    renderFavoriteBookResults(favoriteBookSearchInput ? favoriteBookSearchInput.value : '');
    syncAddonUI();
    renderFavoriteBookEcho();
  }

  function renderFavoriteBookResults(query){
    if (!favoriteBookResults) return;

    var normalizedQuery = normalize(query).replace(/[^\w\s]/g, ' ');
    var queryTokens = normalizedQuery.split(/\s+/).filter(Boolean);
    var allBooks = Object.keys(favoriteBookMap).map(function(handle){
      return favoriteBookMap[handle];
    });
    var matches = allBooks.filter(function(book){
      if (!queryTokens.length) return true;
      var haystack = [book.title || '', book.author || ''].join(' ');
      var normalizedHaystack = normalize(haystack).replace(/[^\w\s]/g, ' ');
      return queryTokens.every(function(token){
        return normalizedHaystack.indexOf(token) > -1;
      });
    }).slice(0, 8);

    favoriteBookResults.innerHTML = '';

    matches.forEach(function(book){
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'sss-mfy__searchResult';
      button.classList.toggle('is-active', draftFavoriteBook === book.handle);
      button.innerHTML = '<strong>' + book.title + '</strong><span>' + (book.author || 'library book') + '</span>';
      button.addEventListener('click', function(){
        setDraftFavoriteBook(book.handle);
        saveFavoriteBookLayer(true);
      });
      favoriteBookResults.appendChild(button);
    });

    if (queryTokens.length){
      var customKey = 'custom:' + String(query || '').trim();
      var customButton = document.createElement('button');
      customButton.type = 'button';
      customButton.className = 'sss-mfy__searchResult';
      customButton.classList.toggle('is-active', draftFavoriteBook === customKey);
      customButton.innerHTML = '<strong>use "' + String(query || '').trim() + '"</strong><span>save as your favorite book anyway</span>';
      customButton.addEventListener('click', function(){
        setDraftFavoriteBook(customKey);
        saveFavoriteBookLayer(true);
      });
      favoriteBookResults.appendChild(customButton);
    }

    if (!matches.length && !queryTokens.length){
      favoriteBookResults.innerHTML = '<div class="sss-mfy__searchEmpty">start typing a title or author.</div>';
    }
  }

  function getReadBooks(){
    return books.filter(function(book){
      return getBookStatus({ handle: book.handle, title: book.title }) === 'read';
    });
  }

  function getTopReadTropes(readBooks, limit){
    var counts = {};

    readBooks.forEach(function(book){
      (book.tropes || []).forEach(function(trope){
        var key = String(trope || '').trim();
        if (!key) return;
        counts[key] = (counts[key] || 0) + 1;
      });
    });

    return Object.keys(counts).sort(function(a, b){
      if (counts[b] === counts[a]){
        return a.localeCompare(b);
      }
      return counts[b] - counts[a];
    }).slice(0, limit || 3).map(function(name){
      return {
        name: name,
        count: counts[name]
      };
    });
  }

  function getTopReadShelves(readBooks, limit){
    var counts = {};

    readBooks.forEach(function(book){
      var shelf = String(book && book.shelf || '').trim();
      if (!shelf) return;
      counts[shelf] = (counts[shelf] || 0) + 1;
    });

    return Object.keys(counts).sort(function(a, b){
      if (counts[b] === counts[a]){
        return a.localeCompare(b);
      }
      return counts[b] - counts[a];
    }).slice(0, limit || 3).map(function(name){
      return {
        name: name,
        count: counts[name]
      };
    });
  }

  function getTropeEmoji(tropeName){
    var trope = normalize(tropeName);
    if (trope.indexOf('slow burn') > -1 || trope.indexOf('yearning') > -1) return '🕯️';
    if (trope.indexOf('enemies to lovers') > -1 || trope.indexOf('banter') > -1) return '⚔️';
    if (trope.indexOf('friends to lovers') > -1 || trope.indexOf('comfort') > -1 || trope.indexOf('healing') > -1) return '🤍';
    if (trope.indexOf('dark') > -1 || trope.indexOf('morally gray') > -1 || trope.indexOf('villain') > -1) return '🥀';
    if (trope.indexOf('obsession') > -1 || trope.indexOf('stalker') > -1 || trope.indexOf('possessive') > -1) return '🖤';
    if (trope.indexOf('sports') > -1) return '🏒';
    if (trope.indexOf('forbidden') > -1) return '🍒';
    if (trope.indexOf('grumpy') > -1) return '☕';
    if (trope.indexOf('small town') > -1) return '🍂';
    if (trope.indexOf('romantasy') > -1 || trope.indexOf('fantasy') > -1) return '🌙';
    if (trope.indexOf('workplace') > -1 || trope.indexOf('billionaire') > -1 || trope.indexOf('marriage of convenience') > -1) return '💋';
    return '📚';
  }

  function scoreReadShelfRecommendation(book, topTropes){
    var score = scoreBook(book);
    if (score <= -999) return score;

    var bookTropes = (book.tropes || []).map(normalize);
    topTropes.forEach(function(entry, index){
      if (bookTropes.indexOf(normalize(entry.name)) > -1){
        score += Math.max(4 - index, 2) + entry.count;
      }
    });

    if (profile.favorite_trope && bookTropes.indexOf(normalize(profile.favorite_trope)) > -1){
      score += 6;
    }

    if (profile.spice_dial === 'soft_open_door'){
      if ((book.spice || 0) <= 1) score += 5;
      if ((book.spice || 0) >= 4) score -= 4;
    }
    if (profile.spice_dial === 'some_heat'){
      if ((book.spice || 0) === 2) score += 5;
      if ((book.spice || 0) === 3) score += 1;
    }
    if (profile.spice_dial === 'balanced'){
      if ((book.spice || 0) === 3) score += 5;
      if ((book.spice || 0) === 2 || (book.spice || 0) === 4) score += 1;
    }
    if (profile.spice_dial === 'high_spice'){
      if ((book.spice || 0) === 4) score += 6;
      if ((book.spice || 0) === 5) score += 2;
      if ((book.spice || 0) <= 2) score -= 3;
    }
    if (profile.spice_dial === 'wreck_me'){
      if ((book.spice || 0) >= 5) score += 8;
      if ((book.spice || 0) === 4) score += 2;
      if ((book.spice || 0) <= 3) score -= 4;
    }

    return score;
  }

  function getSpiceDialReason(book){
    if (!profile.spice_dial) return false;

    var spice = Number(book && book.spice || 0);

    if (profile.spice_dial === 'soft_open_door' && spice <= 1) return true;
    if (profile.spice_dial === 'some_heat' && spice === 2) return true;
    if (profile.spice_dial === 'balanced' && spice === 3) return true;
    if (profile.spice_dial === 'high_spice' && spice === 4) return true;
    if (profile.spice_dial === 'wreck_me' && spice >= 5) return true;

    return false;
  }

  function getDialDisplayText(dialKey){
    if (dialKey === 'some_heat') return '2/5 🌶️🌶️ • some heat';
    if (dialKey === 'balanced') return '3/5 🌶️🌶️🌶️ • balanced';
    if (dialKey === 'high_spice') return '4/5 🌶️🌶️🌶️🌶️ • high spice';
    if (dialKey === 'wreck_me') return '5/5 🌶️🌶️🌶️🌶️🌶️ • wreck me';
    return '1/5 🌶️ • soft open door';
  }

  function getSpiceLevel(dialKey){
    var index = spiceDialValues.indexOf(dialKey);
    return index > -1 ? index + 1 : 1;
  }

  function getThemeDisplayText(themeKey){
    return tokenLabels.theme[themeKey] || 'unthemed';
  }

  function getReaderSignalText(){
    if (profile.craving && tokenLabels.craving[profile.craving]){
      return tokenLabels.craving[profile.craving];
    }
    return 'taste profile pending';
  }

	  function getLeadTropeName(topTropes){
	    var activeTropeLane = getActiveTropeLane();
	    if (activeTropeLane) {
	      return activeTropeLane;
	    }

	    if (Array.isArray(topTropes) && topTropes[0] && topTropes[0].name){
	      return String(topTropes[0].name || '').trim();
	    }

    var readTop = getTopReadTropes(getReadBooks(), 1);
    if (readTop[0] && readTop[0].name){
      return String(readTop[0].name || '').trim();
    }

    return String(profile.favorite_trope || '').trim();
  }

  function getReaderTypeVsTropeText(persona, leadTrope){
    return (persona && (persona.signal || persona.bio)) || getReaderSignalText();
  }

  function getReaderTypeWhyText(persona, leadTrope){
    var personaLabel = persona && persona.label ? String(persona.label) : 'your reader type';
    var trope = String(leadTrope || '').trim();
    if (!trope){
      return 'Reader type is calculated from quiz answers, shelf patterns, tropes, and spice signals.';
    }

    return personaLabel + ' is the pattern your dashboard sees across quiz answers, shelf behavior, and spice. ' + trope + ' is the trope showing up most clearly.';
  }

  function getReadNextGroupKey(book, prompt, topTropes){
    var bookTropes = (book && book.tropes || []).map(normalize);
    var matchingTrope = (topTropes || []).find(function(entry){
      return bookTropes.indexOf(normalize(entry.name)) > -1;
    });

    if (matchingTrope){
      return 'trope:' + normalize(matchingTrope.name);
    }

    return 'prompt:' + normalize((prompt && prompt.text) || (prompt && prompt.label) || (book && book.shelf) || (book && book.title) || '');
  }

  function getSpiceDialLine(){
    if (profile.spice_dial === 'wreck_me') return 'for a night when you want five-chili wreck me spice';
    if (profile.spice_dial === 'high_spice') return 'for a night when you want four-chili high spice';
    if (profile.spice_dial === 'balanced') return 'for a night when you want a balanced three-chili read';
    if (profile.spice_dial === 'some_heat') return 'for a night when you want two-chili some heat';
    return 'for a night when you want the softer side of the tension';
  }

  function getSpiceContextLine(book, topTrope, topShelf){
    var bookTropes = (book && book.tropes || []).map(normalize);

    if (topTrope && bookTropes.indexOf(normalize(topTrope.name)) > -1){
      return getSpiceDialLine() + ' with more ' + topTrope.name + '.';
    }

    if (topShelf && normalize(book && book.shelf) === normalize(topShelf.name)){
      return getSpiceDialLine() + ' inside more ' + topShelf.name + '.';
    }

    return getSpiceDialLine() + '.';
  }

  function matchesTopTrope(book, topTrope){
    if (!book || !topTrope) return false;
    return (book.tropes || []).map(normalize).indexOf(normalize(topTrope.name)) > -1;
  }

  function matchesFavoriteTrope(book){
    if (!book || !profile.favorite_trope) return false;
    return (book.tropes || []).map(normalize).indexOf(normalize(profile.favorite_trope)) > -1;
  }

  function matchesTopShelf(book, topShelf){
    if (!book || !topShelf) return false;
    return normalize(book.shelf) === normalize(topShelf.name);
  }

  function getReadShelfRecommendationPrompt(book, topTropes){
    var quizScore = scoreQuizOnlyBook(book);
    var bookTropes = (book && book.tropes || []).map(normalize);
    var matchingTopTropes = (topTropes || []).filter(function(entry){
      return bookTropes.indexOf(normalize(entry.name)) > -1;
    });
    var spiceMatched = getSpiceDialReason(book);
    var topTropeNames = matchingTopTropes.slice(0, 2).map(function(entry){
      return entry.name;
    });
    var leadTrope = topTropeNames[0] || '';
    var spiceLine = profile.spice_dial === 'wreck_me'
      ? 'for a night when you want a lot more spice.'
      : profile.spice_dial === 'high_spice'
        ? 'for a night when you want high spice.'
        : profile.spice_dial === 'some_heat'
          ? 'for a night when you want a little more spice.'
          : profile.spice_dial === 'soft_open_door'
            ? 'for a night when you want the softer side of the tension.'
            : 'for a night when you want a balanced level of spice.';
    var tropeLine = leadTrope
      ? 'for when you are in the mood for ' + leadTrope + '.'
      : '';
    var quizLine = profile.craving === 'slow_ache'
      ? 'for when you want slow burn and the payoff to take its time.'
      : profile.craving === 'messy_obsession'
        ? 'for when you want obsession and a book that feels a little dangerous.'
        : profile.craving === 'comfort_devotion'
          ? 'for when you want something tender with enough ache to matter.'
          : profile.craving === 'chaos_chemistry'
            ? 'for when you want sharp chemistry right away.'
            : profile.craving === 'dark_dangerous'
              ? 'for when you want danger, devotion, and bad decisions.'
              : 'for when you want the mood you picked in the quiz.';

    if (quizScore > 8 && matchingTopTropes.length && spiceMatched){
      return {
        label: 'mood + shelf + spice',
        text: tropeLine || spiceLine || quizLine
      };
    }

    if (quizScore > 8 && matchingTopTropes.length){
      return {
        label: 'from your shelf mood',
        text: tropeLine || quizLine
      };
    }

    if (quizScore > 8 && spiceMatched){
      return {
        label: 'spice dial pick',
        text: spiceLine || quizLine
      };
    }

    if (matchingTopTropes.length && spiceMatched){
      return {
        label: 'shelf + spice pick',
        text: spiceLine || tropeLine
      };
    }

    if (matchingTopTropes.length){
      return {
        label: 'from your finished shelf',
        text: tropeLine
      };
    }

    if (quizScore > 8){
      return {
        label: 'from your quiz mood',
        text: quizLine
      };
    }

    if (spiceMatched){
      return {
        label: 'spice dial pick',
        text: spiceLine
      };
    }

    return {
      label: 'library match',
      text: 'for a night when you want a close library match to what has been landing for you lately.'
    };
  }

  function cloneBookCardByHandle(handle){
    if (!handle) return null;
    var source = root.querySelector('.sss-mfy__sourceGrid .sss-lib__book[data-handle="' + handle + '"]') ||
      document.querySelector('.sss-lib__book[data-handle="' + handle + '"]');
    return source ? source.cloneNode(true) : null;
  }

  function enhancePrimaryNextReadCard(card, book){
    if (!card) return;
    var under = card.querySelector('.sss-lib__under');
    var shelfName = String((book && book.shelf) || card.dataset.shelf || '').trim();
    var tropes = Array.isArray(book && book.tropes) ? book.tropes.filter(Boolean).slice(0, 4) : [];
    card.classList.add('sss-mfy__leadReadCard');

    if (!under || !shelfName || under.querySelector('.sss-mfy__genreRow')) return;

    var genreRow = document.createElement('div');
    genreRow.className = 'sss-mfy__genreRow';
    genreRow.innerHTML = '<span class="sss-mfy__genreLine" aria-hidden="true"></span><span class="sss-mfy__genreLabel">' + shelfName + '</span>';
    under.insertBefore(genreRow, under.firstChild);

    if (tropes.length && !card.querySelector('.sss-mfy__nextReadTropes')){
      var tropeColumn = document.createElement('div');
      tropeColumn.className = 'sss-mfy__nextReadTropes';
      tropes.forEach(function(trope){
        var item = document.createElement('span');
        item.className = 'sss-mfy__nextReadTrope';
        item.textContent = trope;
        tropeColumn.appendChild(item);
      });
      card.appendChild(tropeColumn);
    }
  }

  function enhanceReadNextCard(card, book){
    if (!card) return;
    var under = card.querySelector('.sss-lib__under');
    var shelfName = String((book && book.shelf) || card.dataset.shelf || '').trim();

    if (!under || !shelfName || under.querySelector('.sss-mfy__genreRow')) return;

    var genreRow = document.createElement('div');
    genreRow.className = 'sss-mfy__genreRow';
    genreRow.innerHTML = '<span class="sss-mfy__genreLine" aria-hidden="true"></span><span class="sss-mfy__genreLabel">' + shelfName + '</span>';
    under.insertBefore(genreRow, under.firstChild);
  }

  function createReadNextPick(cards, prompt){
    if (!cards || !cards.length) return null;

    var wrap = document.createElement('div');
    wrap.className = 'sss-mfy__guidedPick';

    if (prompt && prompt.text){
      var row = document.createElement('div');
      row.className = 'sss-mfy__reasonRow';
      row.textContent = prompt.text;
      wrap.appendChild(row);
    }

    var shelfRow = document.createElement('div');
    shelfRow.className = 'sss-mfy__guidedRow';

    cards.forEach(function(card){
      if (card){
        shelfRow.appendChild(card);
      }
    });

    wrap.appendChild(shelfRow);
    return wrap;
  }

  function enhanceReadShelfCard(card, book){
    if (!card || !book) return;
    var under = card.querySelector('.sss-lib__under');
    if (!under) return;

    var reaction = getBookReaction({ handle: book.handle, title: book.title });
    if (!reaction || under.querySelector('.sss-mfy__reactionTag')) return;

    var label = reaction === 'obsessed'
      ? 'obsessed'
      : reaction === 'liked_it'
        ? 'liked it'
        : 'not for me';

    var tag = document.createElement('div');
    tag.className = 'sss-mfy__reactionTag is-' + reaction;
    tag.textContent = label;
    under.appendChild(tag);
  }

  function renderReadShelf(){
    if (!readShelfRow || !readNextRow || !readTropesEl) return;

    var readBooks = getReadBooks();
    var topTropes = getTopReadTropes(readBooks, 3);
    var topShelves = getTopReadShelves(readBooks, 3);
    var leadTrope = topTropes[0] || null;
    var leadShelf = topShelves[0] || null;

    if (readShelfEyebrow){
      readShelfEyebrow.textContent = 'books you\'ve read in the library';
    }

    readShelfRow.innerHTML = '';
    readNextRow.innerHTML = '';
    readTropesEl.innerHTML = '';

    if (!readBooks.length){
      if (readShelfMeta){
        readShelfMeta.textContent = 'mark a book as read anywhere on bybookishbabe and it will land here.';
      }
      if (readShelfInsight){
        readShelfInsight.textContent = 'once you’ve marked a few finished books, i’ll pull the patterns and aim your next recs harder.';
      }
      if (readNextTitle){
        readNextTitle.textContent = 'your next reads will land here once your read shelf has a pattern.';
      }
      readShelfRow.innerHTML = '<div class="sss-mfy__empty">nothing marked read yet. tag a finished book in the modal and this shelf will wake up.</div>';
      readNextRow.innerHTML = '<div class="sss-mfy__empty">your next recommendations will build from your finished books.</div>';
      return;
    }

    if (readShelfMeta){
      readShelfMeta.textContent = readBooks.length === 1
        ? '1 finished book is shaping this section already.'
        : (readBooks.length + ' finished books are shaping this section already.');
    }

    readBooks.slice(0, 8).forEach(function(book, index){
      var clone = cloneBookCardByHandle(book.handle);
      if (!clone) return;
      enhanceReadShelfCard(clone, book);
      clone.style.setProperty('--mfy-delay', (index * 90) + 'ms');
      readShelfRow.appendChild(clone);
    });

    if (!readShelfRow.children.length){
      readShelfRow.innerHTML = '<div class="sss-mfy__empty">your finished books will appear here once they can be matched to the library.</div>';
    }

    if (topTropes.length){
      topTropes.forEach(function(entry){
        var token = document.createElement('span');
        token.className = 'sss-mfy__tropeToken';
        token.innerHTML = '<span class="sss-mfy__tropeEmoji" aria-hidden="true">' + getTropeEmoji(entry.name) + '</span><span>' + entry.name + '</span>';
        readTropesEl.appendChild(token);
      });
      if (readShelfInsight){
        readShelfInsight.textContent = 'you keep coming back to ' + topTropes.map(function(entry){
          return entry.name;
        }).join(', ') + '. so the next recs are leaning in that direction.';
      }
    } else if (readShelfInsight){
      readShelfInsight.textContent = 'you have finished books logged, but i need a little more trope overlap before i can call your pattern.';
    }

    var rankedNext = books
      .map(function(book){
        return { book: book, score: scoreReadShelfRecommendation(book, topTropes) };
      })
      .filter(function(entry){
        var status = getBookStatus({ handle: entry.book.handle, title: entry.book.title });
        return entry.score > -999 && status !== 'read' && status !== 'reading';
      })
      .sort(function(a, b){
        return b.score - a.score;
      });

    if (readNextTitle){
      readNextTitle.textContent = topTropes.length
        ? 'based on your finished shelf and your quiz, these are the books most likely to hit.'
        : 'with a few more read books, this quiz-shaped next-read stack will get sharper.';
    }

    var usedHandles = {};
    var readNextSections = [];

    function collectSectionEntries(predicate, limit){
      var items = [];
      rankedNext.forEach(function(entry){
        if (items.length >= (limit || 1)) return;
        if (!entry || !entry.book || usedHandles[entry.book.handle]) return;
        if (!predicate(entry)) return;
        items.push(entry);
      });
      return items;
    }

    function addSection(key, text, entries){
      if (!entries || !entries.length) return;
      readNextSections.push({
        key: key,
        prompt: {
          label: key,
          text: text
        },
        entries: entries
      });
      entries.forEach(function(entry){
        if (entry && entry.book && entry.book.handle){
          usedHandles[entry.book.handle] = true;
        }
      });
    }

    var tropeEntries = collectSectionEntries(function(entry){
      return matchesTopTrope(entry.book, leadTrope);
    }, 2);

    if (leadTrope && tropeEntries.length){
      addSection(
        'trope-match',
        'because your finished shelf keeps pulling toward ' + leadTrope.name + ', here are more books in that lane.',
        tropeEntries
      );
    }

    var shelfEntries = collectSectionEntries(function(entry){
      return matchesTopShelf(entry.book, leadShelf);
    }, 2);

    if (leadShelf && shelfEntries.length){
      addSection(
        'genre-match',
        'because you keep landing on ' + leadShelf.name + ', here are more books in that genre lane.',
        shelfEntries
      );
    }

    var spiceEntries = collectSectionEntries(function(entry){
      if (!getSpiceDialReason(entry.book)) return false;
      return matchesTopTrope(entry.book, leadTrope) || matchesTopShelf(entry.book, leadShelf);
    }, 1);

    if (!spiceEntries.length && profile.spice_dial){
      spiceEntries = collectSectionEntries(function(entry){
        return getSpiceDialReason(entry.book);
      }, 1);
    }

    if (profile.spice_dial && spiceEntries.length){
      addSection(
        'spice-match',
        getSpiceContextLine(spiceEntries[0].book, leadTrope, leadShelf),
        spiceEntries
      );
    }

    var favoriteTropeEntries = collectSectionEntries(function(entry){
      return matchesFavoriteTrope(entry.book);
    }, 2);

    if (profile.favorite_trope && favoriteTropeEntries.length){
      addSection(
        'favorite-trope',
        'because you saved ' + profile.favorite_trope + ' as your favorite trope lane, these get pushed higher.',
        favoriteTropeEntries
      );
    }

    if (!readNextSections.length){
      addSection(
        'library-match',
        'these are the closest next matches to what has been landing for you lately.',
        rankedNext.slice(0, 2)
      );
    }

    readNextSections.forEach(function(group, index){
      var cards = [];

      group.entries.forEach(function(entry, cardIndex){
        var clone = cloneBookCardByHandle(entry.book.handle);
        if (!clone) return;
        enhanceReadNextCard(clone, entry.book);
        clone.style.setProperty('--mfy-delay', ((index * 140) + (cardIndex * 80)) + 'ms');
        cards.push(clone);
      });

      var pick = createReadNextPick(cards, group.prompt);
      if (!pick || !cards.length) return;
      pick.style.setProperty('--mfy-delay', (index * 110) + 'ms');
      readNextRow.appendChild(pick);
    });

    if (!readNextRow.children.length){
      readNextRow.innerHTML = '<div class="sss-mfy__empty">mark more books read and i’ll build a cleaner next-read lane from your patterns.</div>';
    }

    syncBookStatusUI();
  }

  function getOpenAddons(){
    return (Array.isArray(profile.open_addons) ? profile.open_addons : []).filter(function(item){
      return item === 'spice_dial' || item === 'favorite_trope';
    });
  }

  function openAddon(key){
    var openAddons = getOpenAddons();
    if (openAddons.indexOf(key) === -1){
      openAddons.push(key);
    }
    profile.open_addons = openAddons;
    syncAddonDrafts();
  }

  function closeAddon(key){
    var openAddons = getOpenAddons().filter(function(item){
      return item !== key;
    });
    profile.open_addons = openAddons;
    saveProfile(profile);
    syncAddonUI();
  }

  function toggleAddon(key){
    var openAddons = getOpenAddons();
    if (openAddons.indexOf(key) > -1){
      closeAddon(key);
      return;
    }
    openAddon(key);
    saveProfile(profile);
    syncAddonUI();
  }

  function saveManDialLayer(shouldClose){
    profile.spice_dial = draftManDial || 'soft_open_door';
    saveProfile(profile);
    syncAddonUI();
    renderMadeForYou();
    if (shouldClose){
      closeAddon('spice_dial');
    }
  }

  function saveFavoriteTropeLayer(shouldClose){
    profile.favorite_trope = draftFavoriteTrope || '';
    saveProfile(profile);
    syncAddonUI();
    renderMadeForYou();
    syncResultStepUI();
    if (shouldClose){
      closeAddon('favorite_trope');
    }
  }

  function saveFavoriteBookLayer(shouldClose){
    profile.favorite_book = draftFavoriteBook || '';
    saveProfile(profile);
    syncAddonUI();
    renderMadeForYou();
    syncResultStepUI();
    if (shouldClose){
      closeAddon('favorite_book');
    }
  }

  function hasRequiredPersonalLayers(){
    return !!profile.spice_dial && !!profile.favorite_trope;
  }

  function getAddonSummary(key){
    if (key === 'hard_nos'){
      var count = Array.isArray(profile.hard_nos) ? profile.hard_nos.length : 0;
      return count ? (count + ' saved') : 'shape your recs';
    }
    if (key === 'spice_dial'){
      if (profile.spice_dial === 'soft_open_door') return 'saved: 🌶️ • soft open door';
      if (profile.spice_dial === 'some_heat') return 'saved: 🌶️🌶️ • some heat';
      if (profile.spice_dial === 'balanced') return 'saved: 🌶️🌶️🌶️ • balanced';
      if (profile.spice_dial === 'high_spice') return 'saved: 🌶️🌶️🌶️🌶️ • high spice';
      if (profile.spice_dial === 'wreck_me') return 'saved: 🌶️🌶️🌶️🌶️🌶️ • wreck me';
      return 'set the heat';
    }
    if (key === 'favorite_book'){
      var book = favoriteBookMap[profile.favorite_book];
      return book ? ('saved: ' + book.title) : 'the one that changed you';
    }
    if (key === 'favorite_trope'){
      return profile.favorite_trope ? ('saved: ' + profile.favorite_trope) : 'your default lane';
    }
    return '';
  }

  function getVisibleFavoriteBookHandle(){
    return getOpenAddons().indexOf('favorite_book') > -1 ? draftFavoriteBook : profile.favorite_book;
  }

  function getSavedPersonalLayerCount(){
    var count = 0;

    if (Array.isArray(profile.hard_nos) && profile.hard_nos.length){
      count += 1;
    }
    if (profile.spice_dial){
      count += 1;
    }
    if (profile.favorite_trope){
      count += 1;
    }
    return count;
  }

  function setSlowRevealState(element, shouldShow){
    if (!element) return;

    if (element.__revealTimer){
      window.clearTimeout(element.__revealTimer);
      element.__revealTimer = null;
    }

    if (shouldShow){
      if (element.hidden){
        element.hidden = false;
      }
      window.requestAnimationFrame(function(){
        element.classList.add('is-visible');
      });
      return;
    }

    element.classList.remove('is-visible');
    element.__revealTimer = window.setTimeout(function(){
      if (!element.classList.contains('is-visible')){
        element.hidden = true;
      }
      element.__revealTimer = null;
    }, 760);
  }

  function createSavedQuoteCard(item, index){
    if (!item || !item.text) return null;

    var card = document.createElement('article');
    card.className = 'sss-mfy__savedQuoteCard';
    card.style.setProperty('--mfy-delay', (index * 90) + 'ms');

    var text = document.createElement('p');
    text.className = 'sss-mfy__savedQuoteText';
    text.textContent = '“' + item.text + '”';
    card.appendChild(text);

    var meta = document.createElement('div');
    meta.className = 'sss-mfy__savedQuoteMeta';
    meta.textContent = [item.title, item.author].filter(Boolean).join(' by ');
    card.appendChild(meta);

    return card;
  }

  function getVisibleSavedQuotes(){
    return getSavedQuotes()
      .map(normalizeQuoteData)
      .filter(function(item){
        if (!item.text) return false;
        if (item.handle && quoteLibraryHandles[item.handle]) return true;
        return !!quoteLibraryKeys[getSavedQuoteKey(item)];
      });
  }

  function renderSavedQuotes(){
    if (!savedQuotesRow) return;

    var savedQuotes = getVisibleSavedQuotes();
    savedQuotesRow.innerHTML = '';

    if (!savedQuotes.length){
      if (savedQuotesMeta){
        savedQuotesMeta.textContent = 'save quotes in the wall and they’ll land here.';
      }
      return;
    }

    if (savedQuotesMeta){
      savedQuotesMeta.textContent = savedQuotes.length === 1
        ? '1 quote is saved into your reader file.'
        : (savedQuotes.length + ' quotes are saved into your reader file.');
    }

    savedQuotes.slice(0, 4).forEach(function(item, index){
      var card = createSavedQuoteCard(item, index);
      if (card){
        savedQuotesRow.appendChild(card);
      }
    });
  }

  function syncAddonUI(){
    var openAddons = getOpenAddons();
    var canShowDashboardExtras = isDashboardView && !!profile.dashboard_built;
    var canShowPersonalLayers = isPersonalLayerView;

    addonButtons.forEach(function(button){
      var key = button.getAttribute('data-mfy-addon');
      var isOpen = openAddons.indexOf(key) > -1;
      var isSaved = key === 'hard_nos'
        ? Array.isArray(profile.hard_nos) && profile.hard_nos.length > 0
        : key === 'spice_dial'
          ? !!profile.spice_dial
          : key === 'favorite_trope'
            ? !!profile.favorite_trope
            : !!(profile.favorite_book && favoriteBookMap[profile.favorite_book]);
      button.classList.toggle('is-active', isOpen);
      button.classList.toggle('is-saved', isSaved);
    });

    addonModules.forEach(function(module){
      var key = module.getAttribute('data-mfy-module');
      module.hidden = openAddons.indexOf(key) === -1 || !canShowPersonalLayers;
    });

    hardNoButtons.forEach(function(button){
      var value = button.getAttribute('data-mfy-hard-no');
      var active = Array.isArray(draftHardNos) && draftHardNos.indexOf(value) > -1;
      button.classList.toggle('is-active', active);
    });

    if (manDialInput){
      var dialIndex = Math.max(spiceDialValues.indexOf(draftManDial), 0);
      manDialInput.value = String(dialIndex);
    }

    if (manDialValue){
      manDialValue.textContent = getDialDisplayText(draftManDial);
    }

    if (favoriteBookSearchInput){
      if (!getOpenAddons().length || getOpenAddons().indexOf('favorite_book') === -1){
        var book = favoriteBookMap[profile.favorite_book];
        favoriteBookSearchInput.value = book ? (book.title + (book.author ? ' — ' + book.author : '')) : '';
      }
    }

    if (manDialOrb){
      var dialIndex = Math.max(spiceDialValues.indexOf(draftManDial), 0);
      manDialOrb.style.setProperty('--mfy-dial-progress', String((dialIndex / 4) * 360) + 'deg');
    }

    manDialChoices.forEach(function(button){
      var value = button.getAttribute('data-mfy-dial-choice');
      button.classList.toggle('is-active', value === draftManDial);
    });

    favoriteTropeButtons.forEach(function(button){
      var value = normalize(button.getAttribute('data-mfy-favorite-trope') || '');
      button.classList.toggle('is-active', value === draftFavoriteTrope);
    });

    if (saveHardNosBtn){
      var currentHardNos = Array.isArray(profile.hard_nos) ? profile.hard_nos : [];
      saveHardNosBtn.disabled = JSON.stringify(currentHardNos) === JSON.stringify(draftHardNos);
    }
    if (saveManDialBtn){
      saveManDialBtn.disabled = (profile.spice_dial || 'soft_open_door') === (draftManDial || 'soft_open_door');
    }
    if (saveFavoriteBookBtn){
      saveFavoriteBookBtn.disabled = (profile.favorite_book || '') === (draftFavoriteBook || '');
    }
    if (saveFavoriteTropeBtn){
      saveFavoriteTropeBtn.disabled = (profile.favorite_trope || '') === (draftFavoriteTrope || '');
    }

    if (hardNoSummary){
      hardNoSummary.textContent = getAddonSummary('hard_nos');
    }
    if (manDialSummary){
      manDialSummary.textContent = getAddonSummary('spice_dial');
    }
    if (favoriteSummary){
      favoriteSummary.textContent = getAddonSummary('favorite_book');
    }
    if (favoriteTropeSummary){
      favoriteTropeSummary.textContent = getAddonSummary('favorite_trope');
    }
    if (seeFullBreakdownBtn){
      seeFullBreakdownBtn.hidden = isDashboardView || !hasRequiredPersonalLayers();
      seeFullBreakdownBtn.disabled = !hasRequiredPersonalLayers();
    }
  }

  function renderFavoriteBookEcho(){
    if (!favoriteBookEcho) return;

    var favoriteHandle = getVisibleFavoriteBookHandle();
    if (!favoriteHandle || !favoriteBookMap[favoriteHandle]){
      favoriteBookEcho.textContent = 'pick the book that changed everything and i’ll weave that into your recs.';
      if (favoriteBookPreview){
        favoriteBookPreview.innerHTML = '<div class="sss-mfy__favoriteEmpty">your chosen book will appear here.</div>';
      }
      return;
    }

    var book = favoriteBookMap[favoriteHandle];
    favoriteBookEcho.textContent = 'because you loved ' + book.title + ', your page is now quietly favoring ' + ((book.tropes || []).slice(0, 2).join(' and ') || 'that same emotional damage') + '.';
    if (favoriteBookPreview){
      favoriteBookPreview.innerHTML = '';
      var source = root.querySelector('.sss-mfy__sourceGrid .sss-lib__book[data-handle="' + book.handle + '"]') ||
        document.querySelector('.sss-lib__book[data-handle="' + book.handle + '"]');
      if (source){
        favoriteBookPreview.appendChild(source.cloneNode(true));
      }
    }
  }

  function renderManDialNote(){
    if (!manDialNote) return;
    if (!profile.spice_dial){
      manDialNote.textContent = 'this will only tune the reads suggested below your dashboard.';
      return;
    }
    if (profile.spice_dial === 'soft_open_door'){
      manDialNote.textContent = 'currently keeping the lower recs on the softer side.';
    } else if (profile.spice_dial === 'some_heat'){
      manDialNote.textContent = 'currently nudging the lower recs toward some heat.';
    } else if (profile.spice_dial === 'balanced'){
      manDialNote.textContent = 'currently keeping the lower recs balanced.';
    } else if (profile.spice_dial === 'high_spice'){
      manDialNote.textContent = 'currently pushing the lower recs toward high spice.';
    } else {
      manDialNote.textContent = 'currently letting the lower recs go full wreck me.';
    }
    if (manDialValue){
      manDialValue.textContent = getDialDisplayText(profile.spice_dial);
    }
  }

  function getFavoriteBookQuote(){
    if (!profile.favorite_book) return null;
    var list = favoriteBookQuotes[profile.favorite_book] || [];
    if (list.length){
      var picked = list[0];
      if (!picked) return null;
      return {
        text: picked.text || picked.quote,
        eyebrow: 'favorite book spotlight',
        source: [picked.title, picked.author].filter(Boolean).join(' by ')
      };
    }

    if (quoteLibrary.length){
      var fallbackQuote = quoteLibrary[Math.floor(Math.random() * quoteLibrary.length)];
      if (fallbackQuote && (fallbackQuote.text || fallbackQuote.quote)){
        return {
          text: fallbackQuote.text || fallbackQuote.quote,
          eyebrow: 'quote spotlight',
          source: [fallbackQuote.title, fallbackQuote.author].filter(Boolean).join(' by ')
        };
      }
    }

    return null;
  }

  function renderQuote(){
    if (!quoteText) return;
    var quote = getFavoriteBookQuote();

    if (!quote){
      if (quoteSpotlightEl){
        quoteSpotlightEl.hidden = true;
      }
      return;
    }

    if (quoteSpotlightEl && isDashboardView){
      quoteSpotlightEl.hidden = false;
    }

    if (quoteEyebrow){
      quoteEyebrow.textContent = quote.eyebrow || 'quote spotlight';
    }
    quoteText.textContent = quote.text;
    if (quoteSource){
      quoteSource.textContent = quote.source || '';
    }
  }

  function getDominantBoyfriendType(ranked){
    var counts = {};

    ranked.slice(0, 6).forEach(function(entry){
      var key = canonicalBoyfriendType(entry.book && entry.book.boyfriend_type);
      if (!key) return;
      counts[key] = (counts[key] || 0) + Math.max(entry.score, 1);
    });

    return Object.keys(counts).sort(function(a, b){
      return counts[b] - counts[a];
    })[0] || '';
  }

  function renderEmojiRain(){
    var emojis = moduleEmojiMap[getThemeProfile().emojiGroup] || ['🖤', '✨', '📚', '💌'];

    startEmojiRain(heroRain, emojis[0], 10);
    startEmojiRain(boyfriendRain, emojis[1], 8);
    startEmojiRain(shelfRain, emojis[2], 8);
    startEmojiRain(readsRain, emojis[3], 8);
    startEmojiRain(quoteRain, emojis[0], 8);
  }

  function startEmojiRain(container, emoji, count){
    if (!container) return;

    container.innerHTML = '';

    for (var i = 0; i < count; i += 1){
      var span = document.createElement('span');
      span.textContent = emoji;
      span.style.left = (Math.random() * 100) + '%';
      span.style.animationDuration = (4 + Math.random() * 4) + 's';
      span.style.animationDelay = (Math.random() * 4) + 's';
      container.appendChild(span);
    }
  }

  function getThemeProfile(){
    var persona = getPersonaProfile();
    var readerThemeMap = {
      chaos_reader: 'obsession_red',
      dark_romance_girlie: 'dark_hearts',
      fantasy_girlie: 'royal_violet',
      jersey_chaser: 'stormy_blue',
      slow_burn_girlie: 'obsession_red',
      tension_addict: 'obsession_red',
      fake_dating_fanatic: 'rose_ribbon',
      sweet_romance_devotee: 'pearl_white',
      romance_reader: 'rose_ribbon'
    };
    var key = readerThemeMap[persona && persona.key] || legacyThemeMap[profile.color] || 'rose_ribbon';
    var themeProfile = themeProfiles[key] || themeProfiles.rose_ribbon;

    return {
      key: key,
      season: themeProfile.season,
      emojiGroup: themeProfile.emojiGroup
    };
  }

  function applyThemeProfile(){
    var theme = getThemeProfile();
    var craving = profile.craving || 'slow_ache';

    root.setAttribute('data-mfy-color', theme.key);
    root.setAttribute('data-mfy-season', theme.season);
    root.setAttribute('data-mfy-craving', craving);
    root.setAttribute('data-mfy-emoji', theme.emojiGroup);
    root.setAttribute('data-mfy-name', profile.name ? 'set' : 'empty');
  }
}

/* ======================
   NEXT READ FINDER
====================== */

function initReadFinder(){
  var dataEl = document.getElementById('sssFinderData');
  var root = document.getElementById('sssReadFinder');

  if (!dataEl || !root) return;
  if (root.dataset.finderReady === 'true') return;

  var shelfSelect = document.getElementById('sssFinderShelf');
  var tropeOneSelect = document.getElementById('sssFinderTropeOne');
  var selectedTropesEl = document.getElementById('sssFinderSelectedTropes');
  var submitBtn = document.getElementById('sssFinderSubmit');
  var stepOneField = root.querySelector('[data-finder-step="1"]');
  var stepTwoField = root.querySelector('[data-finder-step="2"]');
  var result = document.getElementById('sssFinderResult');
  var resultCover = document.getElementById('sssFinderCover');
  var resultTitle = document.getElementById('sssFinderResultTitle');
  var resultAuthor = document.getElementById('sssFinderResultAuthor');
  var resultMeta = document.getElementById('sssFinderResultMeta');
  var resultWhy = document.getElementById('sssFinderResultWhy');
  var resultNote = document.getElementById('sssFinderResultNote');
  var openBtn = document.getElementById('sssFinderOpen');
  var readBtn = document.getElementById('sssFinderRead');
  var retryBtn = document.getElementById('sssFinderRetry');
  var finderHeart = document.getElementById('sssFinderHeart');
  var finderSpice = document.getElementById('sssFinderSpice');
  var finderSeriesBadge = document.getElementById('sssFinderSeriesBadge');

  if (
    !shelfSelect ||
    !tropeOneSelect ||
    !submitBtn ||
    !result ||
    !resultCover ||
    !resultTitle ||
    !resultAuthor ||
    !resultMeta ||
    !resultWhy ||
    !resultNote ||
    !openBtn ||
    !readBtn ||
    !retryBtn
  ) {
    return;
  }

  var books;

  try {
    books = JSON.parse(dataEl.textContent);
  } catch (error) {
    console.error('Finder data failed to parse', error);
    return;
  }

  if (!Array.isArray(books) || !books.length) return;

  root.dataset.finderReady = 'true';

  var seenHandles = [];
  var currentBook = null;
  var currentKey = '';
  var selectedTropes = [];

  function normalize(value){
    return String(value || '').trim().toLowerCase();
  }

  function cleanFilterLabel(value){
    return String(value || '')
      .replace(/\s*\(\s*\d+\s*(?:books?)?\s*\)\s*$/i, '')
      .replace(/\s*[·-]\s*\d+\s*(?:books?)?\s*$/i, '')
      .trim();
  }

  function dedupe(values){
    var seen = {};

    return values.filter(function(value){
      var key = normalize(cleanFilterLabel(value));
      if (!key || seen[key]) return false;
      seen[key] = true;
      return true;
    }).map(cleanFilterLabel);
  }

  function bookMatchesShelf(book, shelfValue){
    var shelf = normalize(shelfValue);
    return !shelf || shelf === 'all romance' || normalize(book.shelf) === shelf;
  }

  function bookMatchesTropeKeys(book, tropeKeys){
    if (!tropeKeys.length) return true;

    return tropeKeys.every(function(trope){
      return book._tropes.indexOf(trope) > -1;
    });
  }

  function tropeValues(book){
    return dedupe(Array.isArray(book && book.tropes) ? book.tropes.map(function(trope){
      if (trope && typeof trope === 'object') {
        return trope.name || trope.label || trope.title || '';
      }

      return trope;
    }) : []);
  }

  function buildCounts(list, mapper){
    var counts = {};

    list.forEach(function(item){
      mapper(item).forEach(function(value){
        var label = cleanFilterLabel(value);
        var key = normalize(label);
        if (!key) return;
        counts[key] = {
          label: label,
          count: (counts[key] ? counts[key].count : 0) + 1
        };
      });
    });

    return Object.keys(counts)
      .map(function(key){
        return counts[key];
      })
      .sort(function(a, b){
        if (b.count !== a.count) return b.count - a.count;
        return a.label.localeCompare(b.label);
      });
  }

  function fillSelect(select, items, emptyLabel){
    var current = select.value;

    select.innerHTML = '';

    var empty = document.createElement('option');
    empty.value = '';
    empty.textContent = emptyLabel;
    select.appendChild(empty);

    items.forEach(function(item){
      var option = document.createElement('option');
      var label = cleanFilterLabel(item.label);
      option.value = label;
      option.textContent = label;
      select.appendChild(option);
    });

    if (current && Array.from(select.options).some(function(option){ return option.value === current; })) {
      select.value = current;
    }
  }

  function selectedTropeKeys(){
    return selectedTropes.map(normalize).filter(Boolean);
  }

  function bookMatchesSelectedTropes(book){
    return bookMatchesTropeKeys(book, selectedTropeKeys());
  }

  function getKey(){
    return [
      normalize(shelfSelect.value),
      selectedTropeKeys().join('+')
    ].join('|');
  }

  function getPools(){
    var shelf = normalize(shelfSelect.value);

    return [
      function(book){
        return bookMatchesShelf(book, shelf)
          && bookMatchesSelectedTropes(book);
      }
    ];
  }

  books.forEach(function(book){
    book.tropes = tropeValues(book);
    book._tropes = book.tropes.map(normalize);
  });

  var allShelves = buildCounts(books, function(book){
    return book.shelf ? [book.shelf] : [];
  }).filter(function(item){
    return normalize(item.label) !== 'private shelf';
  });

  if (!allShelves.length) {
    allShelves = [{ label: 'all romance', count: books.length }];
  }

  function booksForShelf(){
    return books.filter(function(book){
      return bookMatchesShelf(book, shelfSelect.value);
    });
  }

  function booksForShelfAndTrope(){
    return booksForShelf().filter(function(book){
      return bookMatchesSelectedTropes(book);
    });
  }

  function renderSelectedTropes(){
    if (!selectedTropesEl) return;

    selectedTropesEl.innerHTML = '';
    selectedTropesEl.hidden = !selectedTropes.length;

    selectedTropes.forEach(function(trope){
      var chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'sss-lib__finderChip';
      chip.setAttribute('data-finder-remove-trope', trope);
      chip.setAttribute('aria-label', 'remove ' + trope);
      chip.textContent = trope + ' ×';
      selectedTropesEl.appendChild(chip);
    });
  }

  function refreshFinderOptions(){
    var activeTropeKeys = selectedTropeKeys();
    var shelfOptions = allShelves.filter(function(item){
      return books.some(function(book){
        return bookMatchesShelf(book, item.label) && bookMatchesTropeKeys(book, activeTropeKeys);
      });
    });

    if (!activeTropeKeys.length) {
      shelfOptions = allShelves;
    }

    fillSelect(shelfSelect, shelfOptions, 'choose a shelf');

    var tropeOneOptions = buildCounts(booksForShelf(), function(book){
      return tropeValues(book);
    }).filter(function(item){
      return selectedTropeKeys().indexOf(normalize(item.label)) === -1;
    });

    if (selectedTropes.length) {
      tropeOneOptions = buildCounts(booksForShelfAndTrope(), function(book){
        return tropeValues(book);
      }).filter(function(item){
        return selectedTropeKeys().indexOf(normalize(item.label)) === -1;
      });
    }

    fillSelect(tropeOneSelect, tropeOneOptions, selectedTropes.length ? 'add another trope' : 'choose a trope');
    renderSelectedTropes();
  }

  function addSelectedTrope(value){
    var label = cleanFilterLabel(value);
    var key = normalize(label);

    if (!key || selectedTropeKeys().indexOf(key) > -1) {
      tropeOneSelect.value = '';
      return;
    }

    selectedTropes.push(label);
    tropeOneSelect.value = '';
  }

  function removeSelectedTrope(value){
    var key = normalize(value);

    selectedTropes = selectedTropes.filter(function(trope){
      return normalize(trope) !== key;
    });
  }

  function updateFinderProgress(){
    var hasShelf = !!shelfSelect.value;
    var hasTropeOne = !!selectedTropes.length;

    if (stepTwoField) {
      stepTwoField.classList.remove('is-locked');
    }

    if (submitBtn) {
      var ready = hasShelf || hasTropeOne;
      submitBtn.disabled = !ready;
      submitBtn.classList.toggle('is-ready', ready);
    }
  }

  refreshFinderOptions();
  updateFinderProgress();

  function pickBook(){
    var pools = getPools();
    var unused = books.filter(function(book){
      return seenHandles.indexOf(book.handle) === -1;
    });

    var selected = null;

    pools.some(function(test){
      var matches = unused.filter(test);
      if (!matches.length) return false;
      selected = matches[Math.floor(Math.random() * matches.length)];
      return true;
    });

    return selected;
  }

  function setFinderBookAttrs(book){
    if (!openBtn || !book) return;
    var tropes = Array.isArray(book.tropes) ? dedupe(book.tropes) : [];
    var attrs = {
      handle: book.handle || '',
      title: book.title || '',
      author: book.author || '',
      cover: book.cover || '',
      amazon: book.amazon || '',
      bookshop: book.bookshop || '',
      shelf: book.shelf || '',
      'private-shelf': 'false',
      spice: book.spice || '',
      tropes: tropes.join(', '),
      'tropes-display': tropes.join(', '),
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
      standalone: book.standalone || 'false',
      ku: book.ku || '',
      darkness: book.darkness || ''
    };

    Object.keys(attrs).forEach(function(key){
      openBtn.setAttribute('data-' + key, attrs[key]);
    });
  }

  function syncFinderHeart(book){
    if (!finderHeart || !book) return;
    var saved = getShelf().some(function(item){
      return item && (item.handle === book.handle || item.title === book.title);
    });
    applyHeartSavedState(finderHeart, saved);
  }

  function updateFinderCoverMeta(book){
    if (finderSpice){
      var spice = parseInt(book && book.spice, 10) || 0;
      finderSpice.hidden = spice <= 0;
      finderSpice.textContent = spice > 0 ? '🌶'.repeat(Math.min(spice, 5)) : '';
    }

    if (finderSeriesBadge){
      var seriesNumber = String(book && book.seriesNumber || '').trim();
      finderSeriesBadge.hidden = !seriesNumber;
      finderSeriesBadge.textContent = seriesNumber ? String(parseInt(seriesNumber, 10) || seriesNumber) : '';
      finderSeriesBadge.classList.toggle('sss-lib__seriesBadge--standalone', String(book && book.standalone) === 'true');
      if (book && book.series){
        finderSeriesBadge.setAttribute('data-series-url', '/series/' + encodeURIComponent(book.series) + '/');
      } else {
        finderSeriesBadge.removeAttribute('data-series-url');
      }
    }
  }

  function renderRecommendation(book, note){
    currentBook = book;
    result.hidden = false;
    openBtn.hidden = false;
    readBtn.hidden = false;
    retryBtn.hidden = false;
    setFinderBookAttrs(book);
    resultCover.src = book.cover || '';
    resultCover.alt = bookCoverAlt(book.title, book.author, book.shelf);
    resultTitle.textContent = book.title || '';
    resultAuthor.textContent = book.author ? ('by ' + book.author) : '';

    var meta = [];
    if (book.shelf) meta.push(book.shelf);
    if (book.tropes && book.tropes.length) meta.push(book.tropes.slice(0, 3).join(' • '));
    resultMeta.textContent = meta.join('  //  ');
    resultWhy.textContent = book.mini || book.why || 'this one fits the mood, and i have a feeling you’re going to get attached.';
    resultNote.textContent = note || 'want another option? i can keep going.';
    updateFinderCoverMeta(book);
    syncFinderHeart(book);
  }

  function showEmptyState(message){
    currentBook = null;
    result.hidden = false;
    openBtn.hidden = true;
    readBtn.hidden = true;
    retryBtn.hidden = false;
    if (finderSpice) finderSpice.hidden = true;
    if (finderSeriesBadge) finderSeriesBadge.hidden = true;
    resultCover.removeAttribute('src');
    resultCover.alt = '';
    resultTitle.textContent = 'i need a slightly broader brief';
    resultAuthor.textContent = '';
    resultMeta.textContent = '';
    resultWhy.textContent = 'try loosening one of the trope picks and i’ll pull from a wider corner of the library.';
    resultNote.textContent = message;
  }

  function recommend(note){
    if (!shelfSelect.value && !selectedTropes.length) {
      showEmptyState('pick a genre or a trope so i know where to start.');
      return;
    }

    var next = pickBook();

    if (!next) {
      showEmptyState('you’ve burned through this exact combo. switch one answer and i’ll find a new obsession.');
      return;
    }

    renderRecommendation(next, note);
  }

  submitBtn.addEventListener('click', function(){
    var nextKey = getKey();

    if (nextKey !== currentKey) {
      seenHandles = [];
      currentKey = nextKey;
    }

    recommend('fresh from the shelves.');
  });

  shelfSelect.addEventListener('change', function(){
    currentKey = '';
    currentBook = null;
    result.hidden = true;
    refreshFinderOptions();
    updateFinderProgress();
  });

  tropeOneSelect.addEventListener('change', function(){
    addSelectedTrope(tropeOneSelect.value);
    currentKey = '';
    currentBook = null;
    result.hidden = true;
    refreshFinderOptions();
    updateFinderProgress();
  });

  if (selectedTropesEl) {
    selectedTropesEl.addEventListener('click', function(event){
      var button = event.target.closest('[data-finder-remove-trope]');
      if (!button) return;

      removeSelectedTrope(button.getAttribute('data-finder-remove-trope'));
      currentKey = '';
      currentBook = null;
      result.hidden = true;
      refreshFinderOptions();
      updateFinderProgress();
    });
  }

  retryBtn.addEventListener('click', function(){
    if (currentBook) seenHandles.push(currentBook.handle);
    recommend('okay, trying another.');
  });

  readBtn.addEventListener('click', function(){
    if (currentBook) seenHandles.push(currentBook.handle);
    recommend('love that. let’s queue up the next one.');
  });

  openBtn.addEventListener('click', function(){
    if (!currentBook) return;

    var card = document.querySelector('.sss-lib__book[data-handle="' + currentBook.handle + '"]');
    if (!card) return;

    card.click();
  });

  if (finderHeart) {
    finderHeart.addEventListener('click', function(event){
      event.preventDefault();
      event.stopPropagation();
      if (!currentBook) return;
      toggleSave(finderHeart, openBtn);
      syncFinderHeart(currentBook);
    });
  }
}

function groupSeriesShelves(){
  var grids = document.querySelectorAll('.sss-lib__grid, .sss-lib__shelfRow');
  if (!grids.length) return;

  grids.forEach(function(grid){
    if (!grid) return;

    var directBooks = Array.prototype.slice.call(grid.children).filter(function(child){
      return child && child.classList && child.classList.contains('sss-lib__book');
    });

    if (!directBooks.length) return;

    grid.classList.remove('has-series-groups');

    directBooks.forEach(function(card){
      card.classList.remove(
        'sss-lib__book--seriesCluster',
        'sss-lib__book--seriesStart',
        'sss-lib__book--seriesMiddle',
        'sss-lib__book--seriesEnd',
        'sss-lib__book--seriesSolo'
      );
      card.removeAttribute('data-series-cluster');
    });

    var counts = {};
    directBooks.forEach(function(card){
      var handle = (card.getAttribute('data-series') || '').trim();
      var name = (card.getAttribute('data-series-name') || '').trim();
      if (!handle || !name) return;
      if (!counts[handle]){
        counts[handle] = { name: name, cards: [] };
      }
      counts[handle].cards.push(card);
    });

    Object.keys(counts).forEach(function(handle){
      var entry = counts[handle];
      if (!entry || !entry.cards || entry.cards.length < 2) return;
      grid.classList.add('has-series-groups');

      entry.cards.sort(function(a, b){
        var aNum = parseFloat(a.getAttribute('data-series-number') || '999');
        var bNum = parseFloat(b.getAttribute('data-series-number') || '999');
        if (isNaN(aNum)) aNum = 999;
        if (isNaN(bNum)) bNum = 999;
        return aNum - bNum;
      });

      var anchor = entry.cards[0];
      entry.cards.forEach(function(card, index){
        if (index > 0){
          grid.insertBefore(card, anchor.nextSibling);
          anchor = card;
        }

        card.classList.add('sss-lib__book--seriesCluster');
        card.setAttribute('data-series-cluster', entry.name);
        card.setAttribute('data-series-label', 'series — ' + entry.name);

        if (entry.cards.length === 2){
          card.classList.add(index === 0 ? 'sss-lib__book--seriesStart' : 'sss-lib__book--seriesEnd');
          return;
        }

        if (index === 0){
          card.classList.add('sss-lib__book--seriesStart');
        } else if (index === entry.cards.length - 1){
          card.classList.add('sss-lib__book--seriesEnd');
        } else {
          card.classList.add('sss-lib__book--seriesMiddle');
        }
      });
    });
  });
}

var groupSeriesShelvesQueued = null;
function queueSeriesGrouping(){
  if (groupSeriesShelvesQueued) window.cancelAnimationFrame(groupSeriesShelvesQueued);
  groupSeriesShelvesQueued = window.requestAnimationFrame(function(){
    groupSeriesShelvesQueued = null;
    groupSeriesShelves();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initReadFinder);
} else {
  initReadFinder();
}
initMobileGridPagination();
queueSeriesGrouping();

var seriesGroupObserver = new MutationObserver(function(){
  queueSeriesGrouping();
});

seriesGroupObserver.observe(document.body, {
  childList: true,
  subtree: true
});
/* ======================
   RELATED TROPES
====================== */

function buildRelatedTropes(){

  const container = document.getElementById("sssRelatedTropes")
  if(!container) return

  const cards = document.querySelectorAll(".sss-lib__book")

  const counts = {}

  cards.forEach(card=>{

    const tropes = (card.dataset.tropes || "").split(",")

    tropes.forEach(t=>{
      const name = t.trim().toLowerCase()
      if(!name) return

      counts[name] = (counts[name] || 0) + 1
    })

  })

  const sorted = Object.entries(counts)
    .sort((a,b)=>b[1]-a[1])
    .slice(0,6)

  container.innerHTML = sorted.map(([name])=>{

    const slug = name.replace(/\s+/g,"-")

    return `
      <a href="/pages/${slug}" class="sss-trope__relatedItem">
        ${name}
      </a>
    `
  }).join("")
}

buildRelatedTropes()

/* ======================
   PRIVATE READER NOTES MOCK
====================== */

function initReaderNotesMock(){
  if (window.__bbbReaderNotesMockInitialized) return;

  var storageKey = 'bbbReaderPrivateNotes:v1';
  var migrationKey = 'bbbReaderPrivateNotesMigrated:v1';
  var saveTimers = {};
  var activeCard = null;
  var activePanel = null;
  var activeOverlay = null;
  var swipeStartY = null;
  var remoteNotesReady = false;
  var remoteNotesSyncing = false;
  var remoteNotesPending = false;
  var notesChangedLocally = false;
  var params = new URLSearchParams(window.location.search);
  var isJournalPage = !!document.querySelector('[data-reader-journal]');
  var hasNoteControls = !!document.querySelector('[data-reader-note-toggle]');
  var hasMadeForYouNotesPreview = !!document.querySelector('[data-mfy-book-notes-preview]');
  var root = document.querySelector('[data-sss-lib]');
  var noteRoot = document;
  var forcedState = String(params.get('reader_notes') || params.get('notes') || '').toLowerCase();
  if (forcedState !== 'paid' && forcedState !== 'free' && !isJournalPage && !hasNoteControls && !hasMadeForYouNotesPreview) return;
  if (forcedState !== 'paid' && forcedState !== 'free') {
    forcedState = root && root.getAttribute('data-sss-lib') === 'society' ? 'paid' : 'free';
  }
  window.__bbbReaderNotesMockInitialized = true;

	  var siteData = window.BBBSiteData || {};
	  var readerAccount = siteData.BBBReaderAccount || {};
	  var readerApi = window.BBBReaderAccountApi || siteData.readerAccount || {};
	  var hasNotesAccess = forcedState === 'paid' || !!(readerAccount.hasNotesAccess || readerApi.hasNotesAccess || (window.BBBLibraryData && window.BBBLibraryData.hasNotesAccess));
	  var notesPageUrl = readerApi.notesUrl || readerAccount.notesUrl || '/my-notes/';

	  function getAccountApi(){
	    var api = window.BBBReaderAccountApi || siteData.readerAccount || {};
	    return api && api.notesEndpoint && api.nonce ? api : null;
	  }

  function accountNotesRequest(method, body){
    var api = getAccountApi();
    if (!api) return Promise.reject(new Error('Reader notes endpoint unavailable'));

    return window.fetch(api.notesEndpoint, {
      method: method || 'GET',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': api.nonce
      },
      body: body ? JSON.stringify(body) : undefined
    }).then(function(response){
      return response.json().then(function(payload){
        if (!response.ok) throw payload || new Error('Reader notes request failed');
        return payload || {};
      });
    });
  }

  function readNotes(){
    try {
      return JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
    } catch(err) {
      return {};
    }
  }

  function writeNotes(notes){
    try {
      localStorage.setItem(storageKey, JSON.stringify(notes || {}));
      return true;
    } catch(err) {
      if (window.console && console.warn) console.warn('Reader notes local save failed', err);
      return false;
    }
  }

  function noteTimestamp(note){
    var time = note && note.updatedAt ? new Date(note.updatedAt).getTime() : 0;
    return isNaN(time) ? 0 : time;
  }

  function mergeNotes(localNotes, remoteNotes){
    var merged = {};
    var keys = {};
    localNotes = localNotes && typeof localNotes === 'object' ? localNotes : {};
    remoteNotes = remoteNotes && typeof remoteNotes === 'object' ? remoteNotes : {};

    Object.keys(localNotes).forEach(function(key){ keys[key] = true; });
    Object.keys(remoteNotes).forEach(function(key){ keys[key] = true; });

    Object.keys(keys).forEach(function(key){
      var localNote = localNotes[key];
      var remoteNote = remoteNotes[key];
      var localText = localNote && String(localNote.text || '').trim();
      var remoteText = remoteNote && String(remoteNote.text || '').trim();

      if (!localText && !remoteText) return;
      if (!remoteText) {
        merged[key] = localNote;
        return;
      }
      if (!localText) {
        merged[key] = remoteNote;
        return;
      }

      merged[key] = noteTimestamp(localNote) >= noteTimestamp(remoteNote) ? localNote : remoteNote;
    });

    return merged;
  }

	  function syncNotesToAccount(notes){
	    if (!hasNotesAccess || !getAccountApi()) return;
    if (remoteNotesSyncing) {
      remoteNotesPending = true;
      return;
    }

    remoteNotesSyncing = true;
    accountNotesRequest('POST', { notes: notes || readNotes() }).then(function(payload){
      if (payload && payload.notes) {
        writeNotes(payload.notes);
        refreshCards();
        renderJournal();
      }
    }).catch(function(err){
      if (window.console && console.warn) console.warn('Reader account notes sync failed', err);
    }).finally(function(){
      remoteNotesSyncing = false;
      if (remoteNotesPending) {
        remoteNotesPending = false;
        syncNotesToAccount(readNotes());
      }
    });
  }

	  function loadAccountNotes(){
	    if (!hasNotesAccess || !getAccountApi()) {
      remoteNotesReady = true;
      return Promise.resolve(readNotes());
    }

    return accountNotesRequest('GET').then(function(payload){
      var remoteNotes = payload && payload.notes && typeof payload.notes === 'object' ? payload.notes : {};
      var localNotes = readNotes();

      if (notesChangedLocally) {
        remoteNotesReady = true;
        syncNotesToAccount(localNotes);
        return localNotes;
      }

      var hasLocalNotes = Object.keys(localNotes).length > 0;
      var merged = mergeNotes(localNotes, remoteNotes);
      var mergedJson = '';
      var remoteJson = '';

      try {
        mergedJson = JSON.stringify(merged);
        remoteJson = JSON.stringify(remoteNotes);
      } catch(err) {}

      if (hasLocalNotes && mergedJson !== remoteJson) syncNotesToAccount(merged);

      writeNotes(merged);
      try {
        localStorage.setItem(migrationKey, '1');
      } catch(err) {}
      remoteNotesReady = true;
      return merged;
    }).catch(function(err){
      remoteNotesReady = true;
      if (window.console && console.warn) console.warn('Reader account notes load failed', err);
      return readNotes();
    });
  }

  function noteKey(card){
    return String((card && (card.dataset.handle || card.dataset.title)) || '').trim().toLowerCase();
  }

  function setNoteBookDataset(target, book){
    if (!target || !target.dataset || !book) return target;

    [
      'handle',
      'title',
      'author',
      'cover',
      'url',
      'amazon',
      'bookshop',
      'spice',
      'tropes',
      'mini',
      'series',
      'seriesName',
      'seriesNumber',
      'ku'
    ].forEach(function(key){
      if (book[key] !== undefined && book[key] !== null && String(book[key]).trim() !== ''){
        target.dataset[key] = String(book[key]);
      }
    });

    return target;
  }

  function resolveNoteCard(toggle){
    if (!toggle) return null;

    var card = toggle.closest && toggle.closest('.sss-lib__book[data-title]');
    if (card) return card;

    if (toggle.dataset && (toggle.dataset.title || toggle.dataset.handle)) return toggle;

    var modal = toggle.closest && toggle.closest('.sss-lib__modal');
    if (modal && modal.__currentBook){
      return setNoteBookDataset(toggle, modal.__currentBook);
    }

    var bookPage = toggle.closest && toggle.closest('.sss-book-page');
    if (bookPage){
      card = bookPage.querySelector('.sss-lib__book[data-title]');
      if (card) return card;
    }

    return null;
  }

  function escapeHtml(value){
    return String(value || '').replace(/[&<>"']/g, function(char){
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
  }

  function firstLine(value){
    return String(value || '').split(/\r?\n/).map(function(line){
      return line.trim();
    }).filter(Boolean)[0] || '';
  }

  function snippet(value){
    var line = firstLine(value);
    return line.length > 64 ? line.slice(0, 61).trim() + '...' : line;
  }

  function dateLabel(timestamp){
    if (!timestamp) return '';
    try {
      return new Intl.DateTimeFormat('en-US', { month:'short', day:'numeric' }).format(new Date(timestamp));
    } catch(err) {
      return '';
    }
  }

  function getCardData(card){
    if (!card || !card.dataset){
      return {
        key: '',
        handle: '',
        title: '',
        author: '',
        cover: ''
      };
    }

    return {
      key: noteKey(card),
      handle: card.dataset.handle || '',
      title: card.dataset.title || '',
      author: card.dataset.author || '',
      cover: card.dataset.cover || ''
    };
  }

  function updateCard(card){
    if (!card) return;
    var notes = readNotes();
    var note = notes[noteKey(card)];
    var wrap = card.closest('[data-reader-note-wrap]');
    var preview = wrap ? wrap.querySelector('[data-reader-note-preview]') : card.querySelector('[data-reader-note-preview]');
    var toggle = card.matches && card.matches('[data-reader-note-toggle]')
      ? card
      : (wrap ? wrap.querySelector('[data-reader-note-toggle]') : card.querySelector('[data-reader-note-toggle]'));
    var hasNote = !!(note && String(note.text || '').trim());

    card.classList.toggle('has-reader-note', hasNote);
    if (toggle){
      toggle.classList.toggle('has-reader-note', hasNote);
      toggle.setAttribute('aria-label', hasNote ? 'open your private note' : 'add your private note');
      if (toggle.matches && toggle.matches('.sss-lib__mnoteBtn')){
        toggle.textContent = hasNote ? 'edit note' : 'add note';
      } else if (toggle.matches && toggle.matches('.sss-book-page__noteText')){
        toggle.textContent = hasNote ? 'edit note' : 'add note';
      }
    }

    if (preview){
      if (hasNote){
        preview.textContent = '"' + snippet(note.text) + '"';
        preview.hidden = false;
      } else {
        preview.textContent = '';
        preview.hidden = true;
      }
    }
  }

  function refreshCards(){
    document.querySelectorAll('.sss-lib__book[data-title]').forEach(updateCard);
    document.querySelectorAll('.sss-lib__modal [data-reader-note-toggle][data-title]').forEach(updateCard);
  }

  function closePanel(){
    flushActivePanel();
    if (activePanel){
      activePanel.remove();
      activePanel = null;
    }
    if (activeOverlay){
      activeOverlay.remove();
      activeOverlay = null;
    }
    document.body.classList.remove('bbb-reader-note-open');
    activeCard = null;
  }

  function positionDesktopPanel(card){
    if (!activePanel || !card) return;

    var rect = card.getBoundingClientRect();
    var gutter = 10;
    var width = Math.min(560, window.innerWidth - (gutter * 2));
    var left = rect.left + ((rect.width - width) / 2);
    left = Math.max(gutter, Math.min(left, window.innerWidth - width - gutter));

    var belowTop = rect.bottom + gutter;
    var maxHeight = Math.min(440, window.innerHeight - (gutter * 2));
    var top = belowTop;

    if (belowTop + maxHeight > window.innerHeight - gutter) {
      top = Math.max(gutter, rect.top - maxHeight - gutter);
    }

    activePanel.style.setProperty('--reader-note-left', left + 'px');
    activePanel.style.setProperty('--reader-note-top', top + 'px');
    activePanel.style.setProperty('--reader-note-width', width + 'px');
    activePanel.style.setProperty('--reader-note-max-height', maxHeight + 'px');
  }

  function repositionActivePanel(){
    if (!activePanel || !activeCard || !activePanel.classList.contains('bbb-reader-note--desktop')) return;
    positionDesktopPanel(activeCard);
  }

	  function flushActivePanel(){
	    if (!activeCard || !activePanel || !hasNotesAccess) return;
    var textarea = activePanel.querySelector('[data-reader-note-text]');
    if (!textarea) return;

    window.clearTimeout(saveTimers[noteKey(activeCard)]);
    saveNote(activeCard, textarea.value, activePanel.querySelector('[data-reader-note-status]'));
  }

  function queueNoteSave(card, textarea, statusEl){
    var key = noteKey(card);
    if (!key || !textarea) return;

    window.clearTimeout(saveTimers[key]);
    if (statusEl) statusEl.textContent = 'saving...';
    saveTimers[key] = window.setTimeout(function(){
      saveNote(card, textarea.value, statusEl);
    }, 450);
  }

  function saveNote(card, value, statusEl){
    try {
      var data = getCardData(card);
      if (!data.key) return false;
      var notes = readNotes();
      var text = String(value || '').trim();
      notesChangedLocally = true;

      if (text){
        notes[data.key] = {
          key: data.key,
          handle: data.handle,
          title: data.title,
          author: data.author,
          cover: data.cover,
          text: value,
          updatedAt: new Date().toISOString()
        };
      } else {
        delete notes[data.key];
      }

      if (!writeNotes(notes)) {
        if (statusEl) statusEl.textContent = 'could not save on this device';
        return false;
      }
      syncNotesToAccount(notes);
      updateCard(card);
      renderJournal();

      if (statusEl){
        statusEl.textContent = text ? 'saved' : 'note cleared';
        window.setTimeout(function(){
          if (statusEl) statusEl.textContent = text ? 'saved quietly' : '';
        }, 1200);
      }
    } catch(err) {
      if (statusEl){
        statusEl.textContent = 'could not save locally';
      }
      if (window.console && console.warn){
        console.warn('Reader note save failed', err);
      }
      return false;
    }

    return true;
  }

  function panelHtml(card){
    var notes = readNotes();
    var data = getCardData(card);
    var note = notes[data.key] || {};
    var updated = note.updatedAt ? 'last updated ' + dateLabel(note.updatedAt) : 'not saved yet';

    return '' +
      '<div class="bbb-reader-note__sheet" role="dialog" aria-modal="true" aria-label="private reading note">' +
        '<div class="bbb-reader-note__pull" aria-hidden="true"></div>' +
        '<div class="bbb-reader-note__privacy">your notes are completely private — only you can see them.</div>' +
        '<div class="bbb-reader-note__book">' +
          (data.cover ? '<img src="' + escapeHtml(data.cover) + '" alt="" loading="lazy">' : '') +
          '<div><strong>' + escapeHtml(data.title) + '</strong>' +
          (data.author ? '<span>by ' + escapeHtml(data.author) + '</span>' : '') + '</div>' +
        '</div>' +
        '<textarea class="bbb-reader-note__textarea" data-reader-note-text placeholder="your thoughts, feelings, spoilers, reasons to reread...">' + escapeHtml(note.text || '') + '</textarea>' +
        '<div class="bbb-reader-note__meta">' +
          '<span data-reader-note-status>' + escapeHtml(updated) + '</span>' +
          '<div class="bbb-reader-note__actions">' +
            '<a class="bbb-reader-note__journal" href="' + escapeHtml(notesPageUrl) + '">open my notes</a>' +
            '<button class="bbb-reader-note__delete" type="button" data-reader-note-delete>delete</button>' +
            '<button class="bbb-reader-note__save" type="button" data-reader-note-save>save</button>' +
          '</div>' +
        '</div>' +
      '</div>';
  }

  function freePromptHtml(card){
    var title = card && card.dataset.title ? card.dataset.title : 'this book';
    return '' +
      '<div class="bbb-reader-note__sheet bbb-reader-note__sheet--locked" role="dialog" aria-modal="true" aria-label="notes are a member feature">' +
        '<button class="bbb-reader-note__close" type="button" data-reader-note-close aria-label="close">×</button>' +
        '<div class="bbb-reader-note__privacy">your notes are completely private — only you can see them.</div>' +
	        '<p>notes are a member feature — join the society to keep your own reading journal.</p>' +
        '<a href="/smut-sentiment-society/">join the society</a>' +
        '<small>' + escapeHtml(title) + ' will be waiting.</small>' +
      '</div>';
  }

  function openPanel(card){
    if (!card) return;
    closePanel();
    activeCard = card;

    var isMobile = window.matchMedia('(max-width: 749px)').matches;
    activePanel = document.createElement('div');
    activePanel.className = 'bbb-reader-note' + (isMobile ? ' bbb-reader-note--mobile' : ' bbb-reader-note--desktop');
	    activePanel.innerHTML = hasNotesAccess ? panelHtml(card) : freePromptHtml(card);

    activeOverlay = document.createElement('div');
    activeOverlay.className = 'bbb-reader-note__overlay';
    activeOverlay.addEventListener('click', closePanel);

    if (isMobile){
      document.body.appendChild(activeOverlay);
      document.body.appendChild(activePanel);
      document.body.classList.add('bbb-reader-note-open');
    } else {
      document.body.appendChild(activePanel);
      positionDesktopPanel(card);
    }

    var sheet = activePanel.querySelector('.bbb-reader-note__sheet');
    if (sheet){
      sheet.addEventListener('touchstart', function(e){
        swipeStartY = e.touches && e.touches[0] ? e.touches[0].clientY : null;
      }, { passive:true });
      sheet.addEventListener('touchend', function(e){
        if (swipeStartY === null || !e.changedTouches || !e.changedTouches[0]) return;
        if (e.changedTouches[0].clientY - swipeStartY > 70) closePanel();
        swipeStartY = null;
      }, { passive:true });
    }

    var close = activePanel.querySelector('[data-reader-note-close]');
    if (close) close.addEventListener('click', closePanel);

	    if (!hasNotesAccess) return;

    var textarea = activePanel.querySelector('[data-reader-note-text]');
    var status = activePanel.querySelector('[data-reader-note-status]');
    var deleteBtn = activePanel.querySelector('[data-reader-note-delete]');
    var saveBtn = activePanel.querySelector('[data-reader-note-save]');

    if (textarea){
      textarea.focus({ preventScroll:true });
      textarea.addEventListener('input', function(){
        queueNoteSave(card, textarea, status);
      });
      textarea.addEventListener('change', function(){
        saveNote(card, textarea.value, status);
      });
      textarea.addEventListener('blur', function(){
        saveNote(card, textarea.value, status);
      });
    }

    if (saveBtn){
      function commitSave(e){
        e.preventDefault();
        e.stopPropagation();
        if (saveBtn.__bbbReaderNoteCommitted) return;
        saveBtn.__bbbReaderNoteCommitted = true;
        window.clearTimeout(saveTimers[noteKey(card)]);
        if (saveNote(card, textarea ? textarea.value : '', status)) closePanel();
        window.setTimeout(function(){
          saveBtn.__bbbReaderNoteCommitted = false;
        }, 500);
      }

      saveBtn.addEventListener('pointerdown', function(e){
        if (e.pointerType === 'mouse') return;
        commitSave(e);
      });
      saveBtn.addEventListener('click', commitSave);
    }

    if (deleteBtn){
      function commitDelete(e){
        e.preventDefault();
        e.stopPropagation();
        if (deleteBtn.__bbbReaderNoteCommitted) return;
        deleteBtn.__bbbReaderNoteCommitted = true;
        if (textarea) textarea.value = '';
        if (saveNote(card, '', status)) closePanel();
        window.setTimeout(function(){
          deleteBtn.__bbbReaderNoteCommitted = false;
        }, 500);
      }

      deleteBtn.addEventListener('pointerdown', function(e){
        if (e.pointerType === 'mouse') return;
        commitDelete(e);
      });
      deleteBtn.addEventListener('click', commitDelete);
    }
  }

  function handleToggleEvent(e, shouldOpen){
    var toggle = e.target && e.target.closest ? e.target.closest('[data-reader-note-toggle]') : null;
    if (!toggle) return false;

    var card = resolveNoteCard(toggle);
    if (!card) return false;

    e.preventDefault();
    e.stopPropagation();
    if (e.stopImmediatePropagation) e.stopImmediatePropagation();

    if (shouldOpen) openPanel(card);
    return true;
  }

  function bindCards(){
    document.querySelectorAll('.sss-lib__book[data-title]').forEach(function(card){
      if (card.__bbbReaderNotesBound) return;
      card.__bbbReaderNotesBound = true;
      var toggle = card.querySelector('[data-reader-note-toggle]');
      if (!toggle) return;
    });
    refreshCards();
  }

  function renderJournal(){
    var list = document.querySelector('[data-reader-journal-list]');
    if (!list) return;

	    if (!hasNotesAccess){
	      list.innerHTML = '<div class="bbb-reader-journal__empty"><p>notes are a member feature — join the society to keep your own reading journal.</p><a href="/smut-sentiment-society/">join the society</a></div>';
      return;
    }

    var term = String((document.querySelector('[data-reader-journal-search]') || {}).value || '').toLowerCase().trim();
    var storedNotes = readNotes();
    var notes = Object.keys(storedNotes).map(function(key){
      return storedNotes[key];
    }).filter(function(note){
      return note && String(note.text || '').trim();
    }).sort(function(a, b){
      return new Date(b.updatedAt || 0) - new Date(a.updatedAt || 0);
    }).filter(function(note){
      if (!term) return true;
      return [note.title, note.author, note.text].join(' ').toLowerCase().indexOf(term) !== -1;
    });

    if (!notes.length){
      list.innerHTML = '<div class="bbb-reader-journal__empty">no private notes here yet. tap the little note on a book card when one has something to say back.</div>';
      return;
    }

    list.innerHTML = notes.map(function(note){
      return '' +
        '<article class="bbb-reader-journal__entry">' +
          (note.cover ? '<img src="' + escapeHtml(note.cover) + '" alt="" loading="lazy">' : '') +
          '<div class="bbb-reader-journal__entryBody">' +
            '<div class="bbb-reader-journal__entryMeta">last updated ' + escapeHtml(dateLabel(note.updatedAt)) + '</div>' +
            '<h2>' + escapeHtml(note.title || 'untitled book') + '</h2>' +
            (note.author ? '<p class="bbb-reader-journal__author">by ' + escapeHtml(note.author) + '</p>' : '') +
            '<p class="bbb-reader-journal__note">' + escapeHtml(note.text).replace(/\n/g, '<br>') + '</p>' +
          '</div>' +
        '</article>';
    }).join('');
  }

  function renderMadeForYouNotesPreview(){
    var list = document.querySelector('[data-mfy-book-notes-preview]');
    if (!list) return;

	    if (!hasNotesAccess){
	      list.innerHTML = '<div class="sss-mfy__noteItem"><p>book notes are a member feature.</p><span>join the society to keep a private reading journal</span></div>';
      return;
    }

    var notesUrl = list.getAttribute('data-notes-url') || '/my-notes/';
    var libraryUrl = list.getAttribute('data-library-url') || '/library/';
    var storedNotes = readNotes();
    var notes = Object.keys(storedNotes).map(function(key){
      return storedNotes[key];
    }).filter(function(note){
      return note && String(note.text || '').trim();
    }).sort(function(a, b){
      return new Date(b.updatedAt || 0) - new Date(a.updatedAt || 0);
    }).slice(0, 3);

    if (!notes.length){
      list.innerHTML = '' +
        '<div class="sss-mfy__noteItem sss-mfy__noteItem--empty">' +
          '<p>"the betrayal works because he notices what everyone else missed."</p>' +
          '<span>example private note</span>' +
        '</div>' +
        '<div class="sss-mfy__noteItem sss-mfy__noteItem--empty">' +
          '<p>"save this one for when i want obsessive, messy, touch-her-and-die energy."</p>' +
          '<span>example private note · <a href="' + escapeHtml(libraryUrl) + '">add yours in the library</a></span>' +
        '</div>';
      return;
    }

    list.innerHTML = notes.map(function(note){
      return '' +
        '<article class="sss-mfy__noteItem sss-mfy__bookNote">' +
          (note.cover ? '<img src="' + escapeHtml(note.cover) + '" alt="" loading="lazy">' : '') +
          '<div>' +
            '<strong>' + escapeHtml(note.title || 'untitled book') + '</strong>' +
            (note.author ? '<small>by ' + escapeHtml(note.author) + '</small>' : '') +
            '<p>"' + escapeHtml(snippet(note.text)) + '"</p>' +
            '<span>updated ' + escapeHtml(dateLabel(note.updatedAt)) + '</span>' +
          '</div>' +
        '</article>';
    }).join('') + '<a class="sss-mfy__notesJournalLink" href="' + escapeHtml(notesUrl) + '">view all book notes</a>';
  }

  window.bbbReaderNotesRefresh = function(){
    bindCards();
    refreshCards();
    renderJournal();
    renderMadeForYouNotesPreview();
  };

  bindCards();
  renderJournal();
  renderMadeForYouNotesPreview();
  loadAccountNotes().then(function(){
    refreshCards();
    renderJournal();
    renderMadeForYouNotesPreview();
  });

  var search = document.querySelector('[data-reader-journal-search]');
  if (search) search.addEventListener('input', renderJournal);

  if (noteRoot){
    noteRoot.addEventListener('click', function(e){
      handleToggleEvent(e, true);
    }, true);

    noteRoot.addEventListener('keydown', function(e){
      if (e.key !== 'Enter' && e.key !== ' ') return;
      handleToggleEvent(e, true);
    }, true);
  }

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closePanel();
  });

  window.addEventListener('resize', repositionActivePanel, { passive:true });
  window.addEventListener('scroll', repositionActivePanel, { passive:true });
  window.addEventListener('pagehide', flushActivePanel);
  document.addEventListener('visibilitychange', function(){
    if (document.visibilityState === 'hidden') flushActivePanel();
  });
}

if (document.readyState === 'loading'){
  document.addEventListener('DOMContentLoaded', initReaderNotesMock);
} else {
  initReaderNotesMock();
}

})();
