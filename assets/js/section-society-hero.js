(function () {
  const section = document.getElementById('bbb-newsletter-cta-society-hero');
  if (!section) return;

  const rain = section.querySelector('.bbb-newsletter-cta__rain');
  if (!rain) return;

  const EMOJIS = ['📚', '🖤', '🤍', '📖', '✨'];
  const COUNT  = 26;

  rain.innerHTML = ''; // prevent duplicates on re-render

  for (let i = 0; i < COUNT; i++) {
    const el    = document.createElement('span');
    el.className = 'bbb-rain-emoji';
    el.textContent = EMOJIS[Math.floor(Math.random() * EMOJIS.length)];

    const left  = Math.random() * 100;
    const size  = 14 + Math.random() * 18;          // 14–32px
    const dur   = 18 + Math.random() * 22;          // 18–40s (slow)
    const delay = Math.random() * dur * -1;          // pre-stagger
    const drift = Math.random() * 120 - 60;         // -60..60px
    const rot   = (Math.random() * 140 - 70) + 'deg'; // -70..70deg

    el.style.left = left + '%';
    el.style.setProperty('--size',  size  + 'px');
    el.style.setProperty('--dur',   dur   + 's');
    el.style.setProperty('--drift', drift + 'px');
    el.style.setProperty('--rot',   rot);
    el.style.animationDelay = delay + 's';

    rain.appendChild(el);
  }
})();
