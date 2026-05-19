document.addEventListener('DOMContentLoaded', function() {
  const params = new URLSearchParams(window.location.search);
  const initial = parseInt(params.get('spice')) || 3;
  applySpice(initial);

  document.querySelectorAll('[data-spice-filter]').forEach(btn => {
    btn.addEventListener('click', function() {
      applySpice(parseInt(this.dataset.spiceFilter));
    });
  });

  function applySpice(level) {
    const cards = document.querySelectorAll('#sssSpiceGrid .sss-lib__card');
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
