document.addEventListener('DOMContentLoaded', function() {
  const params = new URLSearchParams(window.location.search);
  const available = Array.from(document.querySelectorAll('#sssSpiceGrid .sss-lib__book'))
    .map(card => parseInt(card.dataset.spice, 10))
    .filter(level => level >= 1 && level <= 5);
  const requested = parseInt(params.get('spice'), 10);
  const initial = requested >= 1 && requested <= 5 ? requested : (available[0] || 3);
  applySpice(initial);

  document.querySelectorAll('[data-spice-filter]').forEach(btn => {
    btn.addEventListener('click', function() {
      applySpice(parseInt(this.dataset.spiceFilter));
    });
  });

  function applySpice(level) {
    const cards = document.querySelectorAll('#sssSpiceGrid .sss-lib__book');
    let count = 0;
    cards.forEach(card => {
      const show = parseInt(card.dataset.spice) === level;
      card.hidden = !show;
      if (show) count++;
    });
    const countNode = document.getElementById('sssSpiceCount');
    if (countNode) countNode.textContent = count;
    document.querySelectorAll('[data-spice-filter]').forEach(b =>
      b.classList.toggle('is-active', parseInt(b.dataset.spiceFilter) === level)
    );
    history.replaceState({ spice: level }, '', '?spice=' + level);
  }
});
