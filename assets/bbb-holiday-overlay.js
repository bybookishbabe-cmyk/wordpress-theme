(function () {
  const overlay = document.getElementById('bbb-holiday-overlay');
  if (!overlay) return;

  const today = new Date();
  const month = today.getMonth() + 1;
  const day = today.getDate();

  let emojis = [];

  if (month === 2 && day <= 14) {
    emojis = ['❤️'];
  }

  if (month === 10) {
    emojis = ['🖤'];
  }

  if (month === 12) {
    emojis = ['❄️', '🎄', '✨'];
  }

  if (!emojis.length) return;

  const count = 10;

  for (let i = 0; i < count; i++) {
    const el = document.createElement('span');
    el.className = 'bbb-holiday-item';
    el.textContent = emojis[Math.floor(Math.random() * emojis.length)];

    const left = Math.random() * 100;
    const size = 18 + Math.random() * 24;
    const duration = 18 + Math.random() * 28;
    const delay = Math.random() * -duration;
    const drift = Math.random() * 140 - 70;
    const rotate = Math.random() * 60 - 30 + 'deg';

    el.style.left = left + '%';
    el.style.fontSize = size + 'px';
    el.style.animationDuration = duration + 's';
    el.style.animationDelay = delay + 's';
    el.style.setProperty('--drift', drift + 'px');
    el.style.setProperty('--rotate', rotate);

    overlay.appendChild(el);
  }
})();
