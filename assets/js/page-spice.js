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

  function spiceUrl(level) {
    return '/romance-books-by-spice-level/spice-' + level + '/';
  }

  function setText(selector, value) {
    document.querySelectorAll(selector).forEach(node => {
      node.textContent = value;
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
      const show = parseInt(card.dataset.spice) === level;
      card.hidden = !show;
      if (show) count++;
    });
    const countNode = document.getElementById('sssSpiceCount');
    if (countNode) countNode.textContent = count;
    document.querySelectorAll('[data-spice-filter]').forEach(b =>
      b.classList.toggle('is-active', parseInt(b.dataset.spiceFilter) === level)
    );
    updateDial(level, count);
    if (!preserveCurrentUrl || params.has('spice')) {
      history.replaceState({ spice: level }, '', spiceUrl(level));
    }
  }
});
