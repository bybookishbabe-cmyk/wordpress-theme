(function () {
  'use strict';

  var root = document.querySelector('[data-bbb-shop-filters]');
  if (!root) return;

  var cards = Array.prototype.slice.call(document.querySelectorAll('[data-bbb-shop-card]'));
  var sections = Array.prototype.slice.call(document.querySelectorAll('.bbb-shop__section'));
  var controls = Array.prototype.slice.call(root.querySelectorAll('[data-bbb-shop-filter]'));
  var search = root.querySelector('[data-bbb-shop-search]');
  var reset = root.querySelector('[data-bbb-shop-filter-reset]');
  var count = document.querySelector('[data-bbb-shop-filter-count]');
  var noResults = document.querySelector('[data-bbb-shop-no-results]');

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

  render();
})();
