(function () {
  'use strict';

  var root = document.querySelector('[data-bbb-shop-filters]');
  if (!root) return;

  var cards = [];
  var sections = [];
  var controls = Array.prototype.slice.call(root.querySelectorAll('[data-bbb-shop-filter]'));
  var search = root.querySelector('[data-bbb-shop-search]');
  var reset = root.querySelector('[data-bbb-shop-filter-reset]');
  var count = document.querySelector('[data-bbb-shop-filter-count]');
  var noResults = document.querySelector('[data-bbb-shop-no-results]');
  var loadingMore = false;

  function refreshCatalog() {
    cards = Array.prototype.slice.call(document.querySelectorAll('[data-bbb-shop-card]'));
    sections = Array.prototype.slice.call(document.querySelectorAll('.bbb-shop__section'));
  }

  function normalize(value) {
    return String(value || '').trim().toLowerCase();
  }

  function activeFilters() {
    var filters = {};
    controls.forEach(function (control) {
      filters[control.getAttribute('data-bbb-shop-filter')] = normalize(control.value);
    });
    filters.search = normalize(search ? search.value : '');
    return filters;
  }

  function hasToken(value, token) {
    return normalize(value).split(/\s+/).indexOf(token) !== -1;
  }

  function cardMatches(card, filters) {
    return (!filters.kind || card.getAttribute('data-filter-kind') === filters.kind)
      && (!filters.color || hasToken(card.getAttribute('data-filter-color'), filters.color))
      && (!filters.theme || card.getAttribute('data-filter-theme') === filters.theme)
      && (!filters.search || normalize(card.getAttribute('data-filter-search')).indexOf(filters.search) !== -1);
  }

  function updateSections() {
    sections.forEach(function (section) {
      var visibleCards = section.querySelectorAll('[data-bbb-shop-card]:not([hidden])');
      section.hidden = visibleCards.length === 0;
    });
  }

  function render() {
    var filters = activeFilters();
    var visible = 0;

    cards.forEach(function (card) {
      var matches = cardMatches(card, filters);
      card.hidden = !matches;
      if (matches) visible += 1;
    });

    updateSections();

    if (count) {
      count.textContent = visible + (visible === 1 ? ' design' : ' designs');
    }

    if (noResults) {
      noResults.hidden = visible !== 0;
    }
  }

  controls.forEach(function (control) {
    control.addEventListener('change', render);
  });

  if (search) {
    search.addEventListener('input', render);
  }

  if (reset) {
    reset.addEventListener('click', function () {
      controls.forEach(function (control) {
        control.value = '';
      });
      if (search) search.value = '';
      render();
    });
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('.bbb-shop__loadMoreButton');
    if (!button || loadingMore || !window.DOMParser || !window.fetch) return;

    event.preventDefault();
    loadingMore = true;
    button.setAttribute('aria-busy', 'true');

    fetch(button.href, { credentials: 'same-origin' })
      .then(function (response) {
        if (!response.ok) throw new Error('Shop products could not be loaded.');
        return response.text();
      })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var currentSection = document.getElementById('kindle-inserts');
        var nextSection = doc.getElementById('kindle-inserts');
        var currentGrid = currentSection ? currentSection.querySelector('.bbb-shop__grid') : null;
        var nextGrid = nextSection ? nextSection.querySelector('.bbb-shop__grid') : null;
        var currentLoadMore = currentSection ? currentSection.querySelector('.bbb-shop__loadMore') : null;
        var nextLoadMore = nextSection ? nextSection.querySelector('.bbb-shop__loadMore') : null;

        if (!currentGrid || !nextGrid || !currentLoadMore) {
          window.location.href = button.href;
          return;
        }

        currentGrid.innerHTML = nextGrid.innerHTML;

        if (nextLoadMore) {
          currentLoadMore.replaceWith(nextLoadMore);
        } else {
          currentLoadMore.remove();
        }

        refreshCatalog();
        render();
      })
      .catch(function () {
        window.location.href = button.href;
      })
      .finally(function () {
        loadingMore = false;
        button.removeAttribute('aria-busy');
      });
  });

  refreshCatalog();
  render();
})();
