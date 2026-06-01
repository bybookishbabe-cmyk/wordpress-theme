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

    if (window.Shopify && Shopify.designMode) return true;

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
    var desktopInitialCount = 36;
    var desktopIncrement = 20;
    var mobileInitialCount = isBrowsePageGrid ? 12 : 10;
    var mobileIncrement = isBrowsePageGrid ? 12 : 10;
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
        return card.style.display !== 'none';
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

function getShelf(){
  try {
    return JSON.parse(localStorage.getItem('sssMyShelf')) || [];
  } catch(e){
    return [];
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

function setSavedQuotes(data){
  localStorage.setItem('sssSavedQuotes', JSON.stringify(data));
  document.dispatchEvent(new CustomEvent('sss:quote-saves-updated', {
    detail: { count: Array.isArray(data) ? data.length : 0 }
  }));
}

function getSavedQuoteKey(quoteData){
  if (!quoteData) return '';
  var title = quoteData.title || quoteData.bookTitle || '';
  var text = quoteData.text || quoteData.quote || '';
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
    next.unshift({
      handle: quoteData.handle || '',
      title: quoteData.title || quoteData.bookTitle || '',
      author: quoteData.author || '',
      text: quoteData.text || quoteData.quote || '',
      shelf: quoteData.shelf || '',
      tropes: Array.isArray(quoteData.tropes)
        ? quoteData.tropes
        : String(quoteData.tropes || '').split(',').map(function(item){ return item.trim(); }).filter(Boolean),
      saved_at: Date.now()
    });
  }

  setSavedQuotes(next);
  return !exists;
}

function addQuoteNote(quoteData){
  if (!quoteData) return '';

  var formatted = [
    '"' + String(quoteData.text || quoteData.quote || '').trim() + '"',
    [quoteData.title || quoteData.bookTitle || '', quoteData.author || ''].filter(Boolean).join(' by ')
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

function getBookStatuses(){
  try {
    return JSON.parse(localStorage.getItem('sssBookStatuses')) || {};
  } catch(e){
    return {};
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
  return statuses[key] || '';
}

function setBookStatus(bookData, status){
  var key = getBookStatusKey(bookData);
  if (!key) return;

  var statuses = getBookStatuses();

  if (status){
    statuses[key] = status;
  } else {
    delete statuses[key];
  }

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
    syncAllLibraryHearts();
    return;
  }

  shelf.push({
    handle: bookData.handle || '',
    title: bookData.title || '',
    author: bookData.author || '',
    cover: bookData.cover || '',
    amazon: bookData.amazon || '',
    bookshop: bookData.bookshop || ''
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

function applyBookStatusToCard(card){
  if (!card || card.classList.contains('sss-lib__book--placeholder')) return;

  var coverWrap = card.querySelector('.sss-lib__coverWrap');
  if (!coverWrap) return;

  var status = getBookStatus({
    handle: card.dataset.handle,
    title: card.dataset.title
  });

  var ribbon = coverWrap.querySelector('[data-book-status-ribbon]');

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
}

function syncBookStatusUI(){
  document.querySelectorAll('.sss-lib__book').forEach(applyBookStatusToCard);

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

var bookData = {
  handle: bookBtn.dataset.handle || '',
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
  reread: bookBtn.dataset.reread || '',
  ku: bookBtn.dataset.ku || '',
  mini: bookBtn.dataset.mini || '',
  series: bookBtn.dataset.series || '',
  seriesName: bookBtn.dataset.seriesName || '',
  seriesNumber: bookBtn.dataset.seriesNumber || '',
  privateShelf: bookBtn.dataset.privateShelf || 'false'
};

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
    label.textContent = isSaved ? 'saved' : 'save';
  }

  heartEl.setAttribute('aria-label', isSaved ? 'remove from your bookshelf' : 'save to your bookshelf');
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

function stringifyBookDatasetValue(value){
  if (Array.isArray(value)) return value.join(',');
  if (value === null || typeof value === 'undefined') return '';
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
            alt="${stringifyBookDatasetValue(hydratedBook.title || book.title)}"
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
        reread: bookBtn.dataset.reread || '',
        ku: bookBtn.dataset.ku || '',
        mini: bookBtn.dataset.mini || '',
        series: bookBtn.dataset.series || '',
        seriesName: bookBtn.dataset.seriesName || '',
        seriesNumber: bookBtn.dataset.seriesNumber || '',
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

	    function getModalBookData(btn){
	      return {
        handle: btn.dataset.handle || '',
        url: btn.dataset.url || '',
        title: btn.dataset.title || '',
        author: btn.dataset.author || '',
        cover: btn.dataset.cover || '',
        amazon: btn.dataset.amazon || '',
        bookshop: btn.dataset.bookshop || '',
        spice: btn.dataset.spice || '',
        tropes: btn.dataset.tropes || '',
        tropesDisplay: btn.dataset.tropesDisplay || '',
        why: btn.dataset.why || '',
        newsletter: btn.dataset.newsletter || '',
        tension: btn.dataset.tension || '',
        damage: btn.dataset.damage || '',
        darkness: btn.dataset.darkness || '',
        yearning: btn.dataset.yearning || '',
        boyfriend: btn.dataset.boyfriend || '',
        reread: btn.dataset.reread || '',
        ku: btn.dataset.ku || '',
        mini: btn.dataset.mini || '',
        series: btn.dataset.series || '',
        seriesName: btn.dataset.seriesName || '',
        seriesNumber: btn.dataset.seriesNumber || '',
	        privateShelf: btn.dataset.privateShelf || 'false'
	      };
	    }

	    function modalEscape(value){
	      return String(value || '')
	        .replace(/&/g, '&amp;')
	        .replace(/</g, '&lt;')
	        .replace(/>/g, '&gt;')
	        .replace(/"/g, '&quot;')
	        .replace(/'/g, '&#039;');
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

	    function modalTropesHtml(value){
	      var tropes = String(value || '').split(',').map(function(trope){
	        return String(trope || '').trim();
	      }).filter(Boolean);

	      if (!tropes.length) return '';

	      return 'tropes: ' + tropes.map(function(trope){
	        var name = modalTropeNameWithoutEmoji(trope);
	        var key = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
	        var customKey = modalTropeCustomKey(trope);
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

	        return modalEscape(trope);
	      }).join(', ');
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
            title:data.title,
            author:data.author,
            cover:data.cover,
            amazon:data.amazon,
            bookshop:data.bookshop,
            series:data.series
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
        coverEl.alt = data.title || '';
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

      if (darknessEl){
        darknessEl.textContent = data.darkness
          ? "💀 darkness: " + data.darkness + "/5"
          : '';
      }

var seriesEl = modal.querySelector('[data-mseries]');

if (seriesEl){

  if (data.seriesName || data.seriesNumber){

var slug = (data.series || '').toLowerCase().trim();
    var url = slug ? "/series/" + encodeURIComponent(slug) + "/" : "#";

    var name = data.seriesName ? data.seriesName + " series →" : "";

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

}

      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      document.documentElement.style.overflow = 'hidden';
    }

    function closeModal(){
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.documentElement.style.overflow = '';
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

  const btn = e.target.closest('[data-title]');
  if(!btn) return;

  if (e.target.closest('[data-heart]')) return;
  if (e.target.closest('.sss-lib__seriesBadge')) return;
  if (window.getSelection && String(window.getSelection()).trim()) return;

  e.preventDefault();

  if (btn.hasAttribute('disabled')) return;

  openModal(getModalBookData(btn));

});

    root.querySelectorAll('.sss-lib__book[data-title]').forEach(function(btn){
      if (btn.__sssModalBound) return;
      btn.__sssModalBound = true;

      function handleOpen(e){
        if (e.target.closest('[data-heart]')) return;
        if (e.target.closest('.sss-lib__seriesBadge')) return;
        if (btn.hasAttribute('disabled')) return;
        if (window.getSelection && String(window.getSelection()).trim()) return;

        e.preventDefault();
        e.stopPropagation();
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

  if(e.target.closest('[data-heart]')) return;

  card.click();

});

    });

syncBookStatusUI();

    window.requestAnimationFrame(function(){
      row.scrollLeft = 0;
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
  initMadeForYou();
  syncBookStatusUI();
  loadTrending();
  openSharedBookFromUrl(0);

});

document.addEventListener('shopify:section:load', function(){
  init();
  initMadeForYou();
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

rankInputs.forEach(function(input){
  input.addEventListener('input', function(){
    hasInteracted = true;
    updateRanking();
  });
});
/* ======================
   YEARNING TOGGLE
====================== */

var yearningButtons = document.querySelectorAll('.sss-lib__yearningToggle [data-yearning]');

yearningButtons.forEach(function(btn){

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

var kuButtons = document.querySelectorAll('[data-ku-filter]');

kuButtons.forEach(function(btn){

  btn.addEventListener('click', function(){

    kuButtons.forEach(function(b){
      b.classList.remove('active');
    });

    btn.classList.add('active');

    hasInteracted = true;
    updateRanking();
  });

});
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

var searchInput = document.getElementById('sssSearchInput');

if (searchInput){
  searchInput.addEventListener('input', function(){
    hasInteracted = true;
    updateRanking();
  });
}

if (archiveTropeSelect){
  archiveTropeSelect.addEventListener('change', function(){
    hasInteracted = true;
    updateRanking();
  });
}

/* ======================
   MADE FOR YOU
====================== */

function initMadeForYou(){
  var dataEl = document.getElementById('sssMadeForYouData');
  var root = document.getElementById('sssMadeForYou');
  if (!dataEl || !root) return;

  var storageKey = 'sssMadeForYouProfile';
  var questionsOrder = ['name', 'craving', 'payoff', 'boyfriend_hook', 'boyfriend_dynamic', 'theme'];
  var row = document.getElementById('sssMadeForYouRow');
  var matchBookEl = document.getElementById('sssMfyMatchBook');
  var boyfriendKicker = document.getElementById('sssMfyBoyfriendKicker');
  var backBtn = document.getElementById('sssMadeForYouBack');
  var resetBtn = document.getElementById('sssMadeForYouReset');
  var nameInput = document.getElementById('sssMfyNameInput');
  var nameContinueBtn = document.getElementById('sssMfyNameContinue');
  var stepCount = document.getElementById('sssMfyStepCount');
  var progressFill = document.getElementById('sssMfyProgressFill');
  var resultsEl = document.getElementById('sssMadeForYouResults');
  var resultPanels = Array.prototype.slice.call(root.querySelectorAll('[data-mfy-panel]'));
  var resultsRail = root.querySelector('.sss-mfy__resultsRail');
  var quoteDataEl = document.getElementById('sssMadeForYouQuotes');
  var nextResultBtn = document.getElementById('sssMfyNextResult');
  var resultsMeta = document.getElementById('sssMfyResultsMeta');
  var dashboardTitle = document.getElementById('sssMfyDashboardTitle');
  var dashboardKicker = document.getElementById('sssMfyDashboardKicker');
  var resetResultsBtn = document.getElementById('sssMadeForYouResetResults');
  var customizeEl = document.getElementById('sssMfyCustomize');
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
  var draftHardNos = [];
  var draftManDial = '';
  var draftFavoriteBook = '';

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

  var answerGroups = {
    craving: {},
    payoff: {},
    boyfriend_hook: {},
    boyfriend_dynamic: {},
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
  var legacySpiceDialMap = {
    safer: 'soft_open_door',
    broodier: 'balanced',
    meaner: 'high_spice',
    richer: 'balanced',
    'more obsessed': 'wreck_me'
  };

  var profile = loadProfile();
  var shouldPersistProfileMigration = false;
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
  if (!profile.spice_dial && profile.man_dial && legacySpiceDialMap[profile.man_dial]){
    profile.spice_dial = legacySpiceDialMap[profile.man_dial];
    delete profile.man_dial;
    shouldPersistProfileMigration = true;
  }
  if (profile.spice_dial && spiceDialValues.indexOf(profile.spice_dial) === -1){
    profile.spice_dial = 'balanced';
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
    slow_ache: 'reader breakdown: stomach-drop longing and slow-motion devastation.',
    messy_obsession: 'reader breakdown: pulse-up obsession and one very bad decision.',
    comfort_devotion: 'reader breakdown: safety first, then the ache sneaks in.',
    chaos_chemistry: 'reader breakdown: adrenaline, tension, and one perfect argument.',
    dark_dangerous: 'reader breakdown: dangerous attraction with zero self-preservation.'
  };

  var tokenLabels = {
    theme: {
      dark_hearts: 'annotated in black tabs',
      obsession_red: 'dog-eared after midnight',
      rose_ribbon: 'pressed petals in chapter ten',
      stormy_blue: 'margin notes in the rain',
      pearl_white: 'cream dust jacket energy',
      royal_violet: 'underlined in velvet ink'
    },
    craving: {
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

  quoteLibrary.forEach(function(entry){
    var handle = String(entry && entry.handle || '').trim();
    if (!handle) return;
    if (!favoriteBookQuotes[handle]){
      favoriteBookQuotes[handle] = [];
    }
    favoriteBookQuotes[handle].push(entry);
  });

  answerButtons.forEach(function(button){
    var question = button.getAttribute('data-mfy-answer');
    var value = button.getAttribute('data-value');
    answerGroups[question][value] = button;

    button.addEventListener('click', function(){
      var isChangingAnswer = profile[question] === value;
      profile[question] = value;
      syncDerivedBoyfriendType();
      saveProfile(profile);
      syncAnswerUI();
      renderMadeForYou();

      if (isChangingAnswer && currentStep !== questionsOrder.length - 1){
        currentStep = Math.min(findNextStepIndex(question) + 1, questionsOrder.length - 1);
        syncStepUI();
        return;
      }

      if (isProfileComplete()){
        delete profile.dashboard_built;
        saveProfile(profile);
        currentStep = questionsOrder.length - 1;
        syncStepUI();
        showResults();
        return;
      }

      currentStep = Math.min(findNextStepIndex(question) + 1, questionsOrder.length - 1);
      syncStepUI();
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
      syncAddonUI();
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
  }

  manDialChoices.forEach(function(button){
    button.addEventListener('click', function(){
      draftManDial = button.getAttribute('data-mfy-dial-choice') || 'soft_open_door';
      syncAddonUI();
    });
  });

  if (saveManDialBtn){
    saveManDialBtn.addEventListener('click', function(){
      profile.spice_dial = draftManDial || 'soft_open_door';
      saveProfile(profile);
      syncAddonUI();
      renderMadeForYou();
      closeAddon('spice_dial');
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
      profile.favorite_book = draftFavoriteBook || '';
      saveProfile(profile);
      syncAddonUI();
      renderMadeForYou();
      syncResultStepUI();
      closeAddon('favorite_book');
    });
  }

  root.__refreshMadeForYou = function(){
    syncAddonUI();
    renderMadeForYou();
  };

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

  if (isProfileComplete() && profile.dashboard_built){
    showResults(true);
  }

  function loadProfile(){
    try {
      return JSON.parse(localStorage.getItem(storageKey)) || {};
    } catch(e) {
      return {};
    }
  }

  function saveProfile(nextProfile){
    localStorage.setItem(storageKey, JSON.stringify(nextProfile || {}));
  }

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
    return questionsOrder.every(function(question){
      return !!String(profile[question] || '').trim();
    });
  }

  function findNextStepIndex(question){
    return questionsOrder.indexOf(question);
  }

  function syncStepUI(){
    questionEls.forEach(function(questionEl, index){
      var questionKey = questionEl.getAttribute('data-mfy-question');
      questionEl.classList.toggle('is-answered', !!profile[questionKey]);
    });

    if (stepCount){
      stepCount.textContent = 'question ' + (currentStep + 1) + ' of ' + questionsOrder.length;
    }

    if (progressFill){
      progressFill.style.width = (((currentStep + 1) / questionsOrder.length) * 100) + '%';
    }

    if (backBtn){
      backBtn.disabled = currentStep === 0;
    }

    if (nameContinueBtn){
      nameContinueBtn.disabled = !String(profile.name || '').trim();
    }

    if (trackEl){
      trackEl.style.transform = 'translateX(-' + (currentStep * 100) + '%)';
    }

    if (!isProfileComplete()){
      hideResults();
    }
  }

  function showResults(preserveDashboard){
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
    root.classList.remove('is-complete');
    if (resultsEl){
      resultsEl.hidden = true;
      resultsEl.classList.remove('is-visible');
      resultsEl.classList.remove('is-dashboard');
    }
  }

  function syncResultStepUI(){
    var canShowDashboardExtras = isDashboardView && !!profile.dashboard_built;
    var canShowReadShelf = canShowDashboardExtras && !!profile.favorite_book;

    if (!canShowDashboardExtras && Array.isArray(profile.open_addons) && profile.open_addons.length){
      profile.open_addons = [];
      saveProfile(profile);
    }

    if (resultsEl){
      resultsEl.classList.toggle('is-dashboard', isDashboardView);
    }
    if (customizeEl){
      customizeEl.hidden = !canShowDashboardExtras;
    }
    if (quoteSpotlightEl){
      quoteSpotlightEl.hidden = !canShowDashboardExtras || !Boolean(getFavoriteBookQuote());
    }
    if (savedQuotesEl){
      setSlowRevealState(savedQuotesEl, canShowDashboardExtras && getSavedQuotes().length > 0);
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
    localStorage.removeItem(storageKey);
    if (nameInput){
      nameInput.value = '';
    }
    currentStep = 0;
    currentResultStep = 0;
    isDashboardView = false;
    syncAnswerUI();
    syncStepUI();
    syncResultStepUI();
    syncAddonUI();
    renderMadeForYou();
    if (trackEl){
      trackEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function scoreBook(book){
    var score = 0;
    var theme = getThemeProfile();
    var craving = cravingProfiles[profile.craving];
    var payoff = payoffProfiles[profile.payoff];
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
    }

    if (profile.favorite_book && profile.favorite_book === book.handle) score += 8;

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
      }

      if (reaction === 'liked_it'){
        score += sharedTropeCount * 1.5;
        if (sameShelf) score += 1;
      }

      if (reaction === 'not_for_me'){
        score -= sharedTropeCount * 3;
        if (sameShelf) score -= 4;
        if (sameBoyfriend) score -= 2;
      }
    });

    return score;
  }

  function scoreQuizOnlyBook(book){
    var score = 0;
    var theme = getThemeProfile();
    var craving = cravingProfiles[profile.craving];
    var payoff = payoffProfiles[profile.payoff];
    var man = fictionalManProfiles[profile.fictional_man];
    var bookTropes = (book.tropes || []).map(normalize);
    var shelfName = normalize(book.shelf);
    var boyfriendType = canonicalBoyfriendType(book.boyfriend_type);
    var status = getBookStatus({ handle: book.handle, title: book.title });

    if (status === 'read' || status === 'dnf') return -999;

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

    if (!hook || !dynamic){
      return canonicalBoyfriendType(nextProfile.fictional_man);
    }

    applyTypeWeights(scores, boyfriendQuestionWeights.boyfriend_hook[hook], 1.2);
    applyTypeWeights(scores, boyfriendQuestionWeights.boyfriend_dynamic[dynamic], 1.1);

    applyTypeWeights(scores, {
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

    return Object.keys(boyfriendTypeAliases).sort(function(a, b){
      return (scores[b] || 0) - (scores[a] || 0);
    })[0] || '';
  }

  function getBookByHandle(handle){
    if (!handle) return null;
    return books.find(function(book){
      return book.handle === handle;
    }) || null;
  }

  function buildReaderCore(){
    var craving = cravingProfiles[profile.craving];
    var payoff = payoffProfiles[profile.payoff];
    var man = fictionalManProfiles[profile.fictional_man];
    var theme = getThemeProfile();
    var titleParts = [];
    var payoffLine = '';

    if (profile.craving === 'slow_ache') titleParts.push('slow ache');
    if (profile.craving === 'messy_obsession') titleParts.push('messy obsession');
    if (profile.craving === 'comfort_devotion') titleParts.push('soft devotion');
    if (profile.craving === 'chaos_chemistry') titleParts.push('sharp chemistry');
    if (profile.craving === 'dark_dangerous') titleParts.push('danger');

    if (profile.payoff === 'long_tension') payoffLine = 'you want the tension stretched out.';
    if (profile.payoff === 'emotional_devastation') payoffLine = 'you want it to hurt before it heals.';
    if (profile.payoff === 'soft_after_storm') payoffLine = 'you want the softness after the wreckage.';
    if (profile.payoff === 'plot_addiction') payoffLine = 'you need plot and chemistry pulling at the same time.';
    if (profile.payoff === 'illegal_chemistry') payoffLine = 'you want chemistry with bite.';

    return {
      title: titleParts.length ? ('you are built for ' + titleParts.join(' + ')) : 'waiting on your answers',
      emotion: fallingEmotions[profile.craving] || 'reader breakdown: loading',
      body: [
        craving ? craving.body : '',
        payoffLine,
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
    var man = fictionalManProfiles[profile.fictional_man];
    var boyfriendType = canonicalBoyfriendType(book && book.boyfriend_type);
    var bookTropes = (book && book.tropes || []).map(normalize);
    var allowedTypes = man ? (man.boyfriendBoosts || []).map(canonicalBoyfriendType) : [];
    var isAllowedType = !allowedTypes.length || allowedTypes.some(function(type){
      return boyfriendType === type;
    });

    if (!isAllowedType) return -999;

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

    return score;
  }

  function renderMadeForYou(){
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
    if (heroKicker){
      heroKicker.textContent = (emojiMap[getThemeProfile().emojiGroup] || '🖤') + ' your reader core';
    }
    coreTitle.textContent = readerCore.title;
    if (coreEmotion){
      coreEmotion.textContent = readerCore.emotion;
    }
    coreBody.textContent = readerCore.body
      ? ((firstName ? (firstName + ', ') : '') + readerCore.body)
      : 'pick a few answers and i’ll tell you what kind of romance damage you’re actually here for.';

    if (themeTokens){
      themeTokens.innerHTML = '';
    }

    applyModuleCopy();

    typeTitle.textContent = man ? tokenLabels.fictional_man[profile.fictional_man] : 'currently unreadable';
    typeBody.textContent = man ? man.body : 'this is where i’ll lovingly explain what your taste in fictional men says about you.';

    renderShelfInsight();
    renderRecommendations(answeredCount);
    renderReadShelf();
    renderFavoriteBookEcho();
    renderQuote();
    renderSavedQuotes();
    renderManDialNote();
    renderEmojiRain();
  }

  function applyModuleCopy(){
    var emojis = moduleEmojiMap[getThemeProfile().emojiGroup] || ['🖤', '✨', '📚', '💌'];

    if (coreEmojiBadge){
      coreEmojiBadge.textContent = emojis[0];
    }
    if (heroKicker){
      heroKicker.textContent = emojis[0] + ' your reader core';
    }
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

  function getBookshelfPattern(sourceBooks){
    var insightBooks = Array.isArray(sourceBooks) ? sourceBooks : getShelfInsightBooks();
    var tropeCounts = {};
    var shelfCounts = {};
    var stats = { spice: 0, tension: 0, damage: 0, darkness: 0, yearning: 0 };

    insightBooks.forEach(function(book){
      (book.tropes || []).forEach(function(trope){
        var key = normalize(trope);
        if (!key) return;
        tropeCounts[key] = (tropeCounts[key] || 0) + 1;
      });

      var shelfKey = normalize(book.shelf);
      if (shelfKey){
        shelfCounts[shelfKey] = (shelfCounts[shelfKey] || 0) + 1;
      }

      stats.spice += Number(book.spice || 0);
      stats.tension += Number(book.tension || 0);
      stats.damage += Number(book.damage || 0);
      stats.darkness += Number(book.darkness || 0);
      stats.yearning += Number(book.yearning || 0);
    });

    var topTropes = Object.keys(tropeCounts).sort(function(a, b){
      if (tropeCounts[b] === tropeCounts[a]){
        return a.localeCompare(b);
      }
      return tropeCounts[b] - tropeCounts[a];
    });
    var topShelves = Object.keys(shelfCounts).sort(function(a, b){
      return shelfCounts[b] - shelfCounts[a];
    });
    var avg = insightBooks.length ? {
      spice: stats.spice / insightBooks.length,
      tension: stats.tension / insightBooks.length,
      damage: stats.damage / insightBooks.length,
      darkness: stats.darkness / insightBooks.length,
      yearning: stats.yearning / insightBooks.length
    } : stats;

    var hasTrope = function(names){
      return names.some(function(name){
        return tropeCounts[normalize(name)] > 0;
      });
    };

    var title = 'your bookshelf has a type';
    var body = (getThemeProfile().key && shelfCopy[getThemeProfile().key]) ? shelfCopy[getThemeProfile().key] : '';

    if ((avg.tension >= 2 && avg.yearning >= 1.4) || hasTrope(['slow burn', 'yearning', 'grumpy sunshine'])){
      title = 'you keep choosing tension-first romances';
      body = 'slow build, withheld feelings, and payoff that takes its time are showing up more than any single trope label.';
    } else if ((avg.darkness >= 2 && avg.spice >= 2) || hasTrope(['dark romance', 'morally gray', 'obsession', 'stalker', 'villain gets the girl'])){
      title = 'you lean toward dark devotion and dangerous chemistry';
      body = 'your finished shelf keeps pulling toward obsession, risk, and romance that feels a little unsafe in the best way.';
    } else if ((avg.damage >= 1.8 && avg.yearning >= 1.4) || hasTrope(['angst', 'second chance', 'emotional devastation'])){
      title = 'you like the ache before the payoff';
      body = 'you are repeatedly choosing books that make the emotional damage part of the appeal, not a side effect.';
    } else if ((avg.spice >= 2.2 && avg.tension >= 1.8) || hasTrope(['enemies to lovers', 'forbidden romance', 'banter'])){
      title = 'you keep chasing sharp chemistry';
      body = 'banter, friction, and immediate pull are doing more work on your shelf than soft comfort reads.';
    } else if (hasTrope(['friends to lovers', 'healing', 'comfort', 'caretaking']) || topShelves[0] === 'contemporary romance' || topShelves[0] === 'small town romance'){
      title = 'you come back to devotion with heart';
      body = 'you keep picking books where tenderness, loyalty, and emotional safety still have enough ache to matter.';
    } else if (topShelves[0] && (topShelves[0].indexOf('romantasy') > -1 || topShelves[0].indexOf('fantasy') > -1 || hasTrope(['magic', 'fated mates', 'prince']))){
      title = 'your shelf wants drama with a bigger world';
      body = 'fantasy stakes, larger-than-life longing, and romantic chaos are shaping your pattern more than plain realism.';
    } else if (topTropes.length){
      title = 'your reading pattern is getting clearer';
      body = 'the strongest repeats right now are ' + topTropes.slice(0, 3).join(', ') + ', which is a much better clue than any one label alone.';
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

  function renderRecommendations(answeredCount){
    if (!row) return;
    var boyfriendProfile = fictionalManProfiles[profile.fictional_man];
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

    var ranked = books
      .map(function(book){
        return { book: book, score: scoreQuizOnlyBook(book) };
      })
      .filter(function(entry){
        return entry.score > -999;
      })
      .sort(function(a, b){
        return b.score - a.score;
      })
      .slice(0, 1);

    var dominantType = getDominantBoyfriendType(ranked);
    var boyfriendCandidates = books
      .map(function(book){
        return { book: book, score: scoreBoyfriendMatch(book) };
      })
      .filter(function(entry){
        return entry.score > -999;
      });
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
    var namedBoyfriendCandidates = boyfriendCandidates.filter(function(entry){
      return !!String(entry && entry.book && entry.book.boyfriend_name || '').trim();
    });
    var namedFallbackBoyfriendCandidates = fallbackBoyfriendCandidates.filter(function(entry){
      return !!String(entry && entry.book && entry.book.boyfriend_name || '').trim();
    });
    var namedGlobalCandidates = allRanked.filter(function(entry){
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
      boyfriendCandidates.length ? boyfriendCandidates :
      namedFallbackBoyfriendCandidates.length ? namedFallbackBoyfriendCandidates :
      fallbackBoyfriendCandidates.length ? fallbackBoyfriendCandidates :
      namedGlobalCandidates.length ? namedGlobalCandidates :
      allRanked
    )[0] || ranked[0] || null;
    recTitle.textContent = answeredCount >= 3
      ? 'the book most likely to ruin your week, beautifully'
      : 'your next read will land here';

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

    ranked.forEach(function(entry){
      var source = root.querySelector('.sss-mfy__sourceGrid .sss-lib__book[data-handle="' + entry.book.handle + '"]') ||
        document.querySelector('.sss-lib__book[data-handle="' + entry.book.handle + '"]');
      if (!source) return;

      var clone = source.cloneNode(true);
      enhancePrimaryNextReadCard(clone, entry.book);
      row.appendChild(clone);
    });

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

    if (!row.children.length){
      row.innerHTML = '<div class="sss-mfy__empty">save a few books or answer a few more questions and i’ll start matching your chaos.</div>';
    }
    if (matchBookEl && !matchBookEl.children.length){
      matchBookEl.innerHTML = '<div class="sss-mfy__empty">your featured match will appear here.</div>';
    }

    Array.prototype.forEach.call(row.children, function(child, index){
      child.style.setProperty('--mfy-delay', (index * 120) + 'ms');
    });
    if (matchBookEl && matchBookEl.firstElementChild){
      matchBookEl.firstElementChild.style.setProperty('--mfy-delay', '360ms');
    }

    root.querySelectorAll('#sssMadeForYouRow [data-heart], #sssMfyMatchBook [data-heart]').forEach(function(heart){
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
    if (dialKey === 'some_heat') return '🌶️🌶️ • some heat';
    if (dialKey === 'balanced') return '🌶️🌶️🌶️ • balanced';
    if (dialKey === 'high_spice') return '🌶️🌶️🌶️🌶️ • high spice';
    if (dialKey === 'wreck_me') return '🌶️🌶️🌶️🌶️🌶️ • wreck me';
    return '🌶️ • soft open door';
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
      return item === 'hard_nos' || item === 'spice_dial' || item === 'favorite_book';
    });
  }

  function openAddon(key){
    var openAddons = getOpenAddons();
    if (openAddons.indexOf(key) === -1){
      openAddons.push(key);
    }
    profile.open_addons = openAddons;
    syncAddonDrafts();
    if (key === 'favorite_book'){
      var savedBook = favoriteBookMap[draftFavoriteBook];
      if (favoriteBookSearchInput){
        favoriteBookSearchInput.value = savedBook ? (savedBook.title + (savedBook.author ? ' — ' + savedBook.author : '')) : '';
      }
      renderFavoriteBookResults(favoriteBookSearchInput ? favoriteBookSearchInput.value : '');
      renderFavoriteBookEcho();
    }
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
    if (profile.favorite_book && favoriteBookMap[profile.favorite_book]){
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

  function renderSavedQuotes(){
    if (!savedQuotesRow) return;

    var savedQuotes = getSavedQuotes();
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

    addonButtons.forEach(function(button){
      var key = button.getAttribute('data-mfy-addon');
      var isOpen = openAddons.indexOf(key) > -1;
      var isSaved = key === 'hard_nos'
        ? Array.isArray(profile.hard_nos) && profile.hard_nos.length > 0
        : key === 'spice_dial'
          ? !!profile.spice_dial
          : !!(profile.favorite_book && favoriteBookMap[profile.favorite_book]);
      button.classList.toggle('is-active', isOpen);
      button.classList.toggle('is-saved', isSaved);
    });

    addonModules.forEach(function(module){
      var key = module.getAttribute('data-mfy-module');
      module.hidden = openAddons.indexOf(key) === -1 || !canShowDashboardExtras;
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

    if (hardNoSummary){
      hardNoSummary.textContent = getAddonSummary('hard_nos');
    }
    if (manDialSummary){
      manDialSummary.textContent = getAddonSummary('spice_dial');
    }
    if (favoriteSummary){
      favoriteSummary.textContent = getAddonSummary('favorite_book');
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
        text: picked.quote,
        eyebrow: 'favorite book spotlight',
        source: [picked.title, picked.author].filter(Boolean).join(' by ')
      };
    }

    if (quoteLibrary.length){
      var fallbackQuote = quoteLibrary[Math.floor(Math.random() * quoteLibrary.length)];
      if (fallbackQuote && fallbackQuote.quote){
        return {
          text: fallbackQuote.quote,
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
    var key = profile.theme || legacyThemeMap[profile.color] || 'rose_ribbon';
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
  var tropeTwoSelect = document.getElementById('sssFinderTropeTwo');
  var submitBtn = document.getElementById('sssFinderSubmit');
  var stepOneField = root.querySelector('[data-finder-step="1"]');
  var stepTwoField = root.querySelector('[data-finder-step="2"]');
  var stepThreeField = root.querySelector('[data-finder-step="3"]');
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
    !tropeTwoSelect ||
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

  function normalize(value){
    return String(value || '').trim().toLowerCase();
  }

  function dedupe(values){
    var seen = {};

    return values.filter(function(value){
      var key = normalize(value);
      if (!key || seen[key]) return false;
      seen[key] = true;
      return true;
    });
  }

  function buildCounts(list, mapper){
    var counts = {};

    list.forEach(function(item){
      mapper(item).forEach(function(value){
        var key = normalize(value);
        if (!key) return;
        counts[key] = {
          label: value,
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
      option.value = item.label;
      option.textContent = item.label;
      select.appendChild(option);
    });

    if (current && Array.from(select.options).some(function(option){ return option.value === current; })) {
      select.value = current;
    }
  }

  function getKey(){
    return [
      normalize(shelfSelect.value),
      normalize(tropeOneSelect.value),
      normalize(tropeTwoSelect.value)
    ].join('|');
  }

  function getPools(){
    var shelf = normalize(shelfSelect.value);
    var tropeOne = normalize(tropeOneSelect.value);
    var tropeTwo = normalize(tropeTwoSelect.value);

    return [
      function(book){
        return (!shelf || normalize(book.shelf) === shelf)
          && (!tropeOne || book._tropes.indexOf(tropeOne) > -1)
          && (!tropeTwo || book._tropes.indexOf(tropeTwo) > -1);
      },
      function(book){
        return (!shelf || normalize(book.shelf) === shelf)
          && (!tropeOne || book._tropes.indexOf(tropeOne) > -1);
      },
      function(book){
        return (!tropeOne || book._tropes.indexOf(tropeOne) > -1)
          && (!tropeTwo || book._tropes.indexOf(tropeTwo) > -1);
      },
      function(book){
        return !tropeOne || book._tropes.indexOf(tropeOne) > -1;
      },
      function(book){
        return !shelf || normalize(book.shelf) === shelf;
      },
      function(){
        return true;
      }
    ];
  }

  books.forEach(function(book){
    book._tropes = dedupe(Array.isArray(book.tropes) ? book.tropes : []).map(normalize);
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
    var shelf = normalize(shelfSelect.value);

    return books.filter(function(book){
      return !shelf || shelf === 'all romance' || normalize(book.shelf) === shelf;
    });
  }

  function booksForShelfAndTrope(){
    var tropeOne = normalize(tropeOneSelect.value);

    return booksForShelf().filter(function(book){
      return !tropeOne || book._tropes.indexOf(tropeOne) > -1;
    });
  }

  function refreshFinderOptions(){
    fillSelect(shelfSelect, allShelves, 'choose a genre');

    var tropeOneOptions = buildCounts(booksForShelf(), function(book){
      return Array.isArray(book.tropes) ? dedupe(book.tropes) : [];
    });

    fillSelect(tropeOneSelect, tropeOneOptions, 'choose a trope');

    var tropeTwoOptions = buildCounts(booksForShelfAndTrope(), function(book){
      var values = Array.isArray(book.tropes) ? dedupe(book.tropes) : [];
      var selectedTrope = normalize(tropeOneSelect.value);

      return values.filter(function(value){
        return normalize(value) !== selectedTrope;
      });
    });

    fillSelect(tropeTwoSelect, tropeTwoOptions, 'surprise me');

    if (!booksForShelfAndTrope().length) {
      tropeTwoSelect.value = '';
    }
  }

  function updateFinderProgress(){
    var hasShelf = !!shelfSelect.value;
    var hasTropeOne = !!tropeOneSelect.value;

    if (stepTwoField) {
      stepTwoField.classList.remove('is-locked');
    }

    if (stepThreeField) {
      stepThreeField.classList.toggle('is-locked', !hasTropeOne);
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
    resultCover.alt = book.title ? (book.title + ' cover') : '';
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
    if (!shelfSelect.value && !tropeOneSelect.value) {
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

  [shelfSelect, tropeOneSelect, tropeTwoSelect].forEach(function(select){
    select.addEventListener('change', function(){
      currentKey = '';
      currentBook = null;
      result.hidden = true;
      refreshFinderOptions();
      updateFinderProgress();
    });
  });

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

})();
