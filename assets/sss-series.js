(function(){
  function routeSeries(){
    var seriesRoot = document.querySelector('.series-container');
    if (!seriesRoot) return;

    var params = new URLSearchParams(window.location.search);
    var slug = params.get('series');
    var archive = document.querySelector('[data-series-archive]');
    var sections = document.querySelectorAll('[data-series]');
    var shown = false;

    sections.forEach(function(section){
      section.style.display = 'none';

      if (slug && section.dataset.series === slug) {
        section.style.display = 'block';
        shown = true;
      }
    });

    if (slug && shown) {
      if (archive) archive.style.display = 'none';
      return;
    }

    window.location.replace('/series-reading-orders/');
  }

  function updateSeriesReadingNote(){
    var visibleSeries = Array.prototype.slice.call(document.querySelectorAll('.series-page')).find(function(section){
      return section.style.display === 'block';
    });

    if (!visibleSeries) return;

    var note = visibleSeries.querySelector('[data-series-reading]');
    if (!note) return;

    var books = visibleSeries.querySelectorAll('.sss-lib__book');
    var hasStandalone = false;

    books.forEach(function(book){
      if (book.dataset.standalone === 'true') {
        hasStandalone = true;
      }
    });

    note.classList.remove('note-series', 'note-standalone');

    if (hasStandalone) {
      note.textContent = 'can be read as standalone';
      note.classList.add('note-standalone');
    } else {
      note.textContent = 'highly recommend starting from book one';
      note.classList.add('note-series');
    }
  }

  function setupBackButton(){
    var backButtons = document.querySelectorAll('[data-series-back]');
    if (!backButtons.length) return;

    var referrer = document.referrer || '';

    backButtons.forEach(function(backBtn){
      var defaultHref = backBtn.getAttribute('data-default-href') || '/series-reading-orders/';
      var defaultLabel = backBtn.getAttribute('data-default-label') || '← back to series reading orders';

      backBtn.href = defaultHref;
      backBtn.textContent = defaultLabel;

      if (referrer.includes('/library')) {
        backBtn.href = referrer;
        backBtn.textContent = '← back to library';
      }
    });
  }

  function setupReadingOrderFilters(){
    var sections = document.querySelectorAll('[data-series-filters]');

    sections.forEach(function(filters){
      var root = filters.closest('.bbb-seriesOrders') || document;
      var buttons = Array.prototype.slice.call(filters.querySelectorAll('[data-filter]'));
      var cards = Array.prototype.slice.call(root.querySelectorAll('[data-series-card]'));

      if (!buttons.length || !cards.length) return;

      buttons.forEach(function(button){
        button.addEventListener('click', function(){
          var activeFilter = button.getAttribute('data-filter') || 'all';

          buttons.forEach(function(btn){
            btn.classList.toggle('is-active', btn === button);
          });

          cards.forEach(function(card){
            var genre = card.getAttribute('data-series-genre') || '';
            var shouldShow = activeFilter === 'all' || genre === activeFilter;
            card.hidden = !shouldShow;
          });
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    setupBackButton();
    routeSeries();
    updateSeriesReadingNote();
    setupReadingOrderFilters();
  });
})();
