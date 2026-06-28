(function(){
  function readJSON(key, fallback){
    try {
      var parsed = JSON.parse(localStorage.getItem(key));
      return parsed || fallback;
    } catch(err){
      return fallback;
    }
  }

  function writeJSON(key, value){
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch(err){}
  }

  function bookDataFromElement(source){
    if (!source || !source.dataset) return null;

    return {
      handle: source.dataset.handle || '',
      url: source.dataset.url || window.location.pathname || '',
      title: source.dataset.title || '',
      author: source.dataset.author || '',
      cover: source.dataset.cover || '',
      amazon: source.dataset.amazon || '',
      bookshop: source.dataset.bookshop || '',
      spice: source.dataset.spice || '',
      darkness: source.dataset.darkness || '',
      tropes: source.dataset.tropes || '',
      tropesDisplay: source.dataset.tropesDisplay || source.dataset.tropes || '',
      why: source.dataset.why || '',
      newsletter: source.dataset.newsletter || '',
      tension: source.dataset.tension || '',
      damage: source.dataset.damage || '',
      yearning: source.dataset.yearning || '',
      boyfriend: source.dataset.boyfriend || '',
      boyfriendName: source.dataset.boyfriendName || '',
      reread: source.dataset.reread || '',
      ku: source.dataset.ku || '',
      mini: source.dataset.mini || '',
      series: source.dataset.series || '',
      seriesName: source.dataset.seriesName || '',
      seriesNumber: source.dataset.seriesNumber || '',
      standalone: source.dataset.standalone || '',
      privateShelf: source.dataset.privateShelf || 'false'
    };
  }

  function bookKey(bookData){
    if (!bookData) return '';
    return String(bookData.handle || bookData.title || '').trim().toLowerCase();
  }

  function bookKeys(bookData){
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

  function readByBookKeys(bookData, map){
    var keys = bookKeys(bookData);
    for (var i = 0; i < keys.length; i += 1){
      if (Object.prototype.hasOwnProperty.call(map || {}, keys[i])){
        return map[keys[i]];
      }
    }
    return undefined;
  }

  function findSource(root){
    var page = root ? root.closest('.sss-book-page') : document.querySelector('.sss-book-page');
    if (!page) return null;
    return page.querySelector('.sss-book-page__coverWrap.sss-lib__book')
      || page.querySelector('.sss-book-page__titleRow.sss-lib__book');
  }

  function getRating(bookData){
    var key = bookKey(bookData);
    if (!key) return 0;
    var ratings = readJSON('sssBookRatings', {}) || {};
    var rating = parseInt(readByBookKeys(bookData, ratings) || 0, 10);
    return rating >= 1 && rating <= 5 ? rating : 0;
  }

  function setRating(bookData, rating){
    var keys = bookKeys(bookData);
    var key = keys[0] || '';
    if (!key) return;
    var ratings = readJSON('sssBookRatings', {}) || {};
    keys.forEach(function(ratingKey){
      ratings[ratingKey] = rating;
    });
    writeJSON('sssBookRatings', ratings);
    document.dispatchEvent(new CustomEvent('bbb:book-ratings-updated', { detail: { ratings: ratings } }));
    document.dispatchEvent(new CustomEvent('bbb:book-rating-changed', { detail: { key: key, rating: rating, status: 'read', book: bookData, source: 'book-page' } }));
  }

  function setRead(bookData){
    var keys = bookKeys(bookData);
    var key = keys[0] || '';
    if (!key) return;
    var statuses = readJSON('sssBookStatuses', {}) || {};
    keys.forEach(function(statusKey){
      statuses[statusKey] = 'read';
    });
    writeJSON('sssBookStatuses', statuses);
    document.dispatchEvent(new CustomEvent('bbb:book-statuses-updated', { detail: { statuses: statuses } }));
    document.dispatchEvent(new CustomEvent('bbb:book-status-changed', { detail: { key: key, status: 'read', book: bookData, source: 'book-page' } }));
  }

  function ensureShelf(bookData, rating){
    var keys = bookKeys(bookData);
    var key = keys[0] || '';
    if (!key || !bookData.title) return;

    var shelf = readJSON('sssMyShelf', []);
    if (!Array.isArray(shelf)) shelf = [];

    var found = false;
    shelf = shelf.map(function(item){
      var itemKeys = bookKeys(item);
      var matches = itemKeys.some(function(itemKey){
        return keys.indexOf(itemKey) > -1;
      });
      if (!matches) return item;
      found = true;
      return Object.assign({}, item, { rating: rating });
    });

    if (!found){
      shelf.push(Object.assign({}, bookData, { rating: rating, saved_at: Date.now() }));
    }

    writeJSON('sssMyShelf', shelf);
    document.dispatchEvent(new CustomEvent('sss:bookshelf-updated', { detail: { count: shelf.length } }));
  }

  function ratingText(rating){
    rating = parseInt(rating || 0, 10);
    if (!(rating >= 1 && rating <= 5)) return '';
    return Array(rating + 1).join('★');
  }

  function renderCover(coverWrap, rating){
    if (!coverWrap) return;
    var stamp = coverWrap.querySelector('[data-book-rating-stamp]');
    var text = ratingText(rating);

    if (!text){
      if (stamp) stamp.remove();
      return;
    }

    if (!stamp){
      stamp = document.createElement('div');
      stamp.className = 'sss-lib__ratingStamp';
      stamp.setAttribute('data-book-rating-stamp', '');
      coverWrap.appendChild(stamp);
    }

    stamp.textContent = text;
    stamp.setAttribute('aria-label', rating + ' out of 5 stars');

    var ribbon = coverWrap.querySelector('[data-book-status-ribbon]');
    if (!ribbon){
      ribbon = document.createElement('div');
      ribbon.className = 'sss-lib__statusRibbon is-read';
      ribbon.setAttribute('data-book-status-ribbon', '');
      coverWrap.appendChild(ribbon);
    }
    ribbon.className = 'sss-lib__statusRibbon is-read';
    ribbon.textContent = 'read';
  }

  function renderHearts(bookData){
    var key = bookKey(bookData);
    if (!key) return;

    document.querySelectorAll('.sss-book-page .sss-lib__book').forEach(function(card){
      if (bookKey({ handle: card.dataset.handle, title: card.dataset.title }) !== key) return;
      card.querySelectorAll('[data-heart]').forEach(function(heart){
        heart.classList.add('is-saved');
        heart.setAttribute('aria-label', heart.classList.contains('sss-book-page__addTbr') ? 'remove from tbr' : 'remove from your bookshelf');
        var icon = heart.querySelector('[data-heart-icon]');
        var label = heart.querySelector('[data-heart-label]');
        if (icon) icon.textContent = '♥';
        if (label) label.textContent = heart.classList.contains('sss-book-page__addTbr') ? 'in tbr' : 'saved';
      });
    });
  }

  function render(root){
    var source = findSource(root);
    var bookData = bookDataFromElement(source);
    if (!bookData || !bookData.title) return;

    var rating = getRating(bookData);
    root.querySelectorAll('[data-book-page-rating-option]').forEach(function(button){
      var buttonRating = parseInt(button.getAttribute('data-book-page-rating-option') || '0', 10);
      button.classList.toggle('is-active', rating >= buttonRating);
      button.setAttribute('aria-checked', rating === buttonRating ? 'true' : 'false');
    });

    var summary = root.querySelector('[data-book-page-rating-summary]');
    if (summary){
      summary.textContent = rating
        ? rating + '/5 saved. this book is marked read.'
        : 'rating marks it read and saves it to your bookshelf.';
    }

    renderCover(source && source.classList.contains('sss-book-page__coverWrap') ? source : null, rating);
    if (rating) renderHearts(bookData);
  }

  function bind(){
    document.querySelectorAll('[data-book-page-rating-controls]').forEach(function(root){
      if (root.__bbbBookPageRatingBound) return;
      root.__bbbBookPageRatingBound = true;

      root.querySelectorAll('[data-book-page-rating-option]').forEach(function(button){
        button.addEventListener('click', function(){
          var source = findSource(root);
          var bookData = bookDataFromElement(source);
          var rating = parseInt(button.getAttribute('data-book-page-rating-option') || '0', 10);
          if (!bookData || !bookData.title || !(rating >= 1 && rating <= 5)) return;

          setRead(bookData);
          setRating(bookData, rating);
          ensureShelf(bookData, rating);
          render(root);
        });
      });

      render(root);
    });
  }

  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})();
