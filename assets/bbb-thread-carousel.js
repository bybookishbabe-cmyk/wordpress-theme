document.addEventListener('DOMContentLoaded', function () {
  const cards = document.querySelectorAll('.bbb-thread-card');
  const dots = document.querySelectorAll('.bbb-threads__dot');
  if (!cards.length) return;

  let index = 0;
  const delay = 5000;

  function showSlide(i) {
    cards.forEach((card) => card.classList.remove('is-active'));
    dots.forEach((dot) => dot.classList.remove('is-active'));

    cards[i].classList.add('is-active');
    if (dots[i]) dots[i].classList.add('is-active');

    index = i;
  }

  window.setInterval(function () {
    const next = (index + 1) % cards.length;
    showSlide(next);
  }, delay);

  dots.forEach((dot) => {
    dot.addEventListener('click', function () {
      const target = Number(this.dataset.target || 0);
      showSlide(target);
    });
  });
});
