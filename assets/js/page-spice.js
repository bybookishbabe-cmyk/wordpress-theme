document.addEventListener('DOMContentLoaded', function() {
  const spiceMeta = {
    1: {
      peppers: '🌶',
      title: 'soft spice',
      copy: 'low heat, soft tension, mostly fade to black'
    },
    2: {
      peppers: '🌶🌶',
      title: 'some heat',
      copy: 'a little steam, a lot of yearning, still easy to breathe'
    },
    3: {
      peppers: '🌶🌶🌶',
      title: 'balanced spice',
      copy: 'clear heat, emotional payoff, romance-forward pacing'
    },
    4: {
      peppers: '🌶🌶🌶🌶',
      title: 'high spice',
      copy: 'explicit scenes, dominant energy, tension that pays off'
    },
    5: {
      peppers: '🌶🌶🌶🌶🌶',
      title: 'wreck me spice',
      copy: 'maximum heat, high intensity, no delicate little fade out'
    }
  };
  const params = new URLSearchParams(window.location.search);
  const pathMatch = window.location.pathname.match(/\/romance-books-by-spice-level\/(?:spice-)?([1-5])\/?$/);
  const range = document.querySelector('[data-spice-range]');
  const discoverySelect = document.querySelector('[data-spice-discovery-select]');
  const discoverySelected = document.querySelector('[data-spice-discovery-selected]');
  const discoveryClear = document.querySelector('[data-spice-discovery-clear]');
  const emptyState = document.querySelector('[data-spice-empty]');
  const selectedDiscoveryFilters = [];
  const available = Array.from(document.querySelectorAll('#sssSpiceGrid .sss-lib__book'))
    .map(card => parseInt(card.dataset.spice, 10))
    .filter(level => level >= 1 && level <= 5);
  const requested = parseInt(params.get('spice'), 10);
  const pathLevel = pathMatch ? parseInt(pathMatch[1], 10) : 0;
  const initial = pathLevel >= 1 && pathLevel <= 5 ? pathLevel : (requested >= 1 && requested <= 5 ? requested : (available.includes(4) ? 4 : (available[0] || 3)));
  applySpice(initial, true);

  document.querySelectorAll('[data-spice-filter]').forEach(btn => {
    btn.addEventListener('click', function() {
      applySpice(parseInt(this.dataset.spiceFilter), false);
    });
  });

  if (range) {
    range.addEventListener('input', function() {
      applySpice(parseInt(this.value, 10), false);
    });
  }

  if (discoverySelect) {
    discoverySelect.addEventListener('change', function() {
      addDiscoveryFilter(discoverySelect.value);
      discoverySelect.value = '';
      applySpice(currentSpice(), false);
    });
  }

  if (discoveryClear) {
    discoveryClear.addEventListener('click', function() {
      selectedDiscoveryFilters.splice(0, selectedDiscoveryFilters.length);
      renderDiscoveryFilters();
      applySpice(currentSpice(), false);
    });
  }

  if (discoverySelected) {
    discoverySelected.addEventListener('click', function(event) {
      const button = event.target.closest('[data-spice-discovery-remove]');
      if (!button) return;

      removeDiscoveryFilter(button.dataset.spiceDiscoveryRemove);
      applySpice(currentSpice(), false);
    });
  }

  function spiceUrl(level) {
    return '/romance-books-by-spice-level/spice-' + level + '/';
  }

  function setText(selector, value) {
    document.querySelectorAll(selector).forEach(node => {
      node.textContent = value;
    });
  }

  function normalize(value) {
    return String(value || '')
      .toLowerCase()
      .trim()
      .replace(/&/g, ' and ')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function parseDiscoveryValue(value) {
    const parts = String(value || '').split(':');
    const kind = parts[0] || '';
    const key = parts.slice(1).join(':');

    if (!kind || !key) return null;

    return {
      value: kind + ':' + key,
      kind,
      key,
      label: ''
    };
  }

  function getDiscoveryOption(value) {
    if (!discoverySelect || !value) return null;

    return Array.from(discoverySelect.options).find(option => option.value === value) || null;
  }

  function discoveryLabel(value) {
    const option = getDiscoveryOption(value);
    return option ? option.textContent.trim() : String(value || '').replace(/^[^:]+:/, '');
  }

  function addDiscoveryFilter(value) {
    const parsed = parseDiscoveryValue(value);
    if (!parsed) return;

    if (selectedDiscoveryFilters.some(filter => filter.value === parsed.value)) return;

    parsed.label = discoveryLabel(parsed.value);
    selectedDiscoveryFilters.push(parsed);
    renderDiscoveryFilters();
  }

  function removeDiscoveryFilter(value) {
    const index = selectedDiscoveryFilters.findIndex(filter => filter.value === value);
    if (index === -1) return;

    selectedDiscoveryFilters.splice(index, 1);
    renderDiscoveryFilters();
  }

  function renderDiscoveryFilters() {
    if (!discoverySelected) return;

    discoverySelected.innerHTML = '';
    discoverySelected.hidden = selectedDiscoveryFilters.length === 0;

    selectedDiscoveryFilters.forEach(filter => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'sss-spicePicker__chip';
      chip.dataset.spiceDiscoveryRemove = filter.value;
      chip.setAttribute('aria-label', 'remove ' + filter.label);
      chip.textContent = filter.label + ' ×';
      discoverySelected.appendChild(chip);
    });
  }

  function currentSpice() {
    if (range) {
      const value = parseInt(range.value, 10);
      if (value >= 1 && value <= 5) return value;
    }

    const active = document.querySelector('[data-spice-filter].is-active');
    const activeLevel = active ? parseInt(active.dataset.spiceFilter, 10) : 0;
    return activeLevel >= 1 && activeLevel <= 5 ? activeLevel : initial;
  }

  function cardMatchesFilter(card, filter) {
    if (!filter || !filter.key) return true;

    if (filter.kind === 'genre') {
      return normalize(card.dataset.shelf) === filter.key;
    }

    if (filter.kind === 'trope') {
      return String(card.dataset.tropes || '')
        .split(',')
        .map(normalize)
        .filter(Boolean)
        .includes(filter.key);
    }

    return true;
  }

  function matchesDiscovery(card, extraFilter) {
    const filters = extraFilter ? selectedDiscoveryFilters.concat([extraFilter]) : selectedDiscoveryFilters;
    if (!filters.length) return true;

    return filters.every(filter => cardMatchesFilter(card, filter));
  }

  function filterHasMatches(level, extraFilter) {
    return Array.from(document.querySelectorAll('#sssSpiceGrid .sss-lib__book')).some(card => {
      return parseInt(card.dataset.spice, 10) === level && matchesDiscovery(card, extraFilter);
    });
  }

  function updateDiscoveryOptions(level) {
    if (!discoverySelect) return;

    Array.from(discoverySelect.options).forEach(option => {
      if (!option.value) {
        option.disabled = false;
        option.textContent = selectedDiscoveryFilters.length ? 'add another genre / trope' : 'all genres + tropes';
        return;
      }

      const parsed = parseDiscoveryValue(option.value);
      const alreadySelected = selectedDiscoveryFilters.some(filter => filter.value === option.value);
      option.disabled = alreadySelected || !filterHasMatches(level, parsed);
    });

    Array.from(discoverySelect.querySelectorAll('optgroup')).forEach(group => {
      group.disabled = Array.from(group.querySelectorAll('option')).every(option => option.disabled);
    });
  }

  function updateDial(level, count) {
    const meta = spiceMeta[level] || spiceMeta[3];
    setText('[data-spice-peppers], [data-spice-card-peppers]', meta.peppers);
    setText('[data-spice-title], [data-spice-card-title]', meta.title);
    setText('[data-spice-card-copy]', meta.copy);
    setText('[data-spice-card-count]', count);
    if (range) {
      range.value = String(level);
      range.style.setProperty('--spice-fill', ((level - 1) / 4 * 100) + '%');
    }
  }

  function applySpice(level, preserveCurrentUrl) {
    const cards = document.querySelectorAll('#sssSpiceGrid .sss-lib__book');
    let count = 0;
    cards.forEach(card => {
      const show = parseInt(card.dataset.spice) === level && matchesDiscovery(card);
      card.hidden = !show;
      if (show) count++;
    });
    const countNode = document.getElementById('sssSpiceCount');
    if (countNode) countNode.textContent = count;
    document.querySelectorAll('[data-spice-filter]').forEach(b =>
      b.classList.toggle('is-active', parseInt(b.dataset.spiceFilter) === level)
    );
    updateDial(level, count);
    if (discoveryClear) {
      discoveryClear.hidden = selectedDiscoveryFilters.length === 0;
    }
    updateDiscoveryOptions(level);
    if (emptyState) {
      emptyState.hidden = count > 0;
    }
    if (typeof window.refreshPaginatedGridVisibility === 'function') {
      window.refreshPaginatedGridVisibility();
    }
    if (!preserveCurrentUrl || params.has('spice')) {
      history.replaceState({ spice: level }, '', spiceUrl(level));
    }
  }
});
