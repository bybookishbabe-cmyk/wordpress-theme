function dropRosePetals() {
  const overlay = document.createElement('div');
  overlay.className = 'bbb-rose-overlay';
  document.body.appendChild(overlay);

  const count = 24;

  for (let i = 0; i < count; i++) {
    const petal = document.createElement('div');
    petal.className = 'bbb-rose';

    const left = Math.random() * 100;
    const size = 18 + Math.random() * 14;
    const duration = 6 + Math.random() * 6;
    const delay = Math.random() * 0.8;
    const drift = Math.random() * 120 - 60 + 'px';
    const spin = Math.random() * 360 + 'deg';

    petal.style.left = left + '%';
    petal.style.width = size + 'px';
    petal.style.height = size + 'px';
    petal.style.animationDuration = duration + 's';
    petal.style.animationDelay = delay + 's';
    petal.style.setProperty('--drift', drift);
    petal.style.setProperty('--spin', spin);

    overlay.appendChild(petal);
    window.setTimeout(() => petal.remove(), (duration + 1) * 1000);
  }

  window.setTimeout(() => overlay.remove(), 9000);
}
