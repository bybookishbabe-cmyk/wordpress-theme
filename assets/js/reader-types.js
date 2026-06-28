(function () {
  const mobileQuery = window.matchMedia('(max-width: 640px)');
  const cards = Array.from(document.querySelectorAll('[data-reader-type-card]'));

  if (!cards.length) {
    return;
  }

  const closeCard = (card) => {
    card.classList.remove('is-expanded');
    card.setAttribute('aria-expanded', 'false');
  };

  const closeAll = () => {
    cards.forEach(closeCard);
  };

  const openCard = (card) => {
    if (!mobileQuery.matches) {
      return;
    }

    cards.forEach((otherCard) => {
      if (otherCard !== card) {
        closeCard(otherCard);
      }
    });

    card.classList.add('is-expanded');
    card.setAttribute('aria-expanded', 'true');
  };

  cards.forEach((card) => {
    card.addEventListener('click', (event) => {
      const closeButton = event.target.closest('[data-reader-type-close]');
      if (closeButton) {
        event.stopPropagation();
        closeCard(card);
        card.focus({ preventScroll: true });
        return;
      }

      openCard(card);
    });

    card.addEventListener('keydown', (event) => {
      if ('Enter' === event.key || ' ' === event.key) {
        event.preventDefault();
        openCard(card);
      }

      if ('Escape' === event.key) {
        closeCard(card);
      }
    });
  });

  mobileQuery.addEventListener('change', () => {
    if (!mobileQuery.matches) {
      closeAll();
    }
  });
})();
