document.addEventListener('DOMContentLoaded', function() {
  const params = new URLSearchParams(window.location.search);
  const pathMatch = window.location.pathname.match(/\/romance-books-by-spice-level\/(?:spice-)?([1-5])\/?$/);
  const available = Array.from(document.querySelectorAll('#sssSpiceGrid .sss-lib__book'))
    .map(card => parseInt(card.dataset.spice, 10))
    .filter(level => level >= 1 && level <= 5);
  const requested = parseInt(params.get('spice'), 10);
  const pathLevel = pathMatch ? parseInt(pathMatch[1], 10) : 0;
  const initial = pathLevel >= 1 && pathLevel <= 5 ? pathLevel : (requested >= 1 && requested <= 5 ? requested : (available[0] || 3));
  applySpice(initial, true);

  document.querySelectorAll('[data-spice-filter]').forEach(btn => {
    btn.addEventListener('click', function() {
      applySpice(parseInt(this.dataset.spiceFilter), false);
    });
  });

  function spiceUrl(level) {
    return '/romance-books-by-spice-level/spice-' + level + '/';
  }

  function applySpice(level, preserveCurrentUrl) {
    const cards = document.querySelectorAll('#sssSpiceGrid .sss-lib__book');
    let count = 0;
    cards.forEach(card => {
      const show = parseInt(card.dataset.spice) === level;
      card.hidden = !show;
      if (show) count++;
    });
    if (typeof window.refreshPaginatedGridVisibility === 'function') {
      window.refreshPaginatedGridVisibility();
    }
    const countNode = document.getElementById('sssSpiceCount');
    if (countNode) countNode.textContent = count;
    document.querySelectorAll('[data-spice-filter]').forEach(b =>
      b.classList.toggle('is-active', parseInt(b.dataset.spiceFilter) === level)
    );
    if (!preserveCurrentUrl || params.has('spice')) {
      history.replaceState({ spice: level }, '', spiceUrl(level));
    }
  }
});
