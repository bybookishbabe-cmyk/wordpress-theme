document.querySelectorAll('.bbb-trope-card').forEach(card => {
  const emoji = card.dataset.emoji || "✨";
  const container = card.querySelector('.bbb-emoji-rain');

  if (!container) {
    return;
  }

  for (let i = 0; i < 10; i++) {
    const span = document.createElement('span');

    span.innerText = emoji;

    span.style.left = Math.random() * 100 + "%";
    span.style.animationDuration = (4 + Math.random() * 4) + "s";
    span.style.animationDelay = (Math.random() * 4) + "s";

    container.appendChild(span);
  }
});
