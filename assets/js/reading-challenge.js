(function () {
  var root = document.querySelector('[data-bbb-reading-challenge]');
  if (!root) return;

  var storageKey = 'bbbReadingChallengeRuinMe';
  var cards = Array.prototype.slice.call(root.querySelectorAll('[data-bbb-rc-prompt]'));
  var filters = Array.prototype.slice.call(root.querySelectorAll('[data-bbb-rc-filter]'));
  var count = root.querySelector('[data-bbb-rc-count]');
  var fill = root.querySelector('[data-bbb-rc-fill]');
  var complete = root.querySelector('[data-bbb-rc-complete]');
  var promptModal = root.querySelector('[data-bbb-rc-prompt-modal]');
  var trackerModal = root.querySelector('[data-bbb-rc-tracker-modal]');
  var modalCat = root.querySelector('[data-bbb-rc-modal-cat]');
  var modalTitle = root.querySelector('[data-bbb-rc-modal-title]');
  var modalDetail = root.querySelector('[data-bbb-rc-modal-detail]');
  var modalLink = root.querySelector('[data-bbb-rc-modal-link]');
  var activeFilter = 'all';
  var state = {};

  function readState() {
    try {
      state = JSON.parse(window.localStorage.getItem(storageKey) || '{}') || {};
    } catch (error) {
      state = {};
    }
  }

  function writeState() {
    try {
      window.localStorage.setItem(storageKey, JSON.stringify(state));
    } catch (error) {}
  }

  function completedTotal() {
    return cards.filter(function (card) {
      var number = card.getAttribute('data-bbb-rc-number');
      return state[number] && state[number].done;
    }).length;
  }

  function updateCard(card) {
    var number = card.getAttribute('data-bbb-rc-number');
    var note = card.querySelector('[data-bbb-rc-note]');
    var done = Boolean(state[number] && state[number].done);
    card.classList.toggle('is-complete', done);
    if (note && document.activeElement !== note) {
      note.value = (state[number] && state[number].note) || '';
    }
  }

  function updateProgress() {
    var done = completedTotal();
    var percent = cards.length ? (done / cards.length) * 100 : 0;
    if (count) count.textContent = done + ' of ' + cards.length;
    if (fill) fill.style.width = percent + '%';
    if (complete) {
      complete.hidden = done !== cards.length;
      complete.classList.toggle('is-visible', done === cards.length);
    }
  }

  function applyFilter() {
    cards.forEach(function (card) {
      var category = card.getAttribute('data-bbb-rc-category') || '';
      var number = card.getAttribute('data-bbb-rc-number');
      var done = Boolean(state[number] && state[number].done);
      var show = activeFilter === 'all' || category === activeFilter || (activeFilter === 'completed' && done);
      card.hidden = !show;
    });
  }

  function render() {
    cards.forEach(updateCard);
    updateProgress();
    applyFilter();
    writeState();
  }

  function openModal(modal) {
    if (!modal) return;
    modal.hidden = false;
    document.documentElement.classList.add('bbb-rc-modal-open');
    document.body.classList.add('bbb-rc-modal-open');
  }

  function closeModals() {
    [promptModal, trackerModal].forEach(function (modal) {
      if (modal) modal.hidden = true;
    });
    document.documentElement.classList.remove('bbb-rc-modal-open');
    document.body.classList.remove('bbb-rc-modal-open');
  }

  function openPrompt(card) {
    if (!card || !promptModal) return;
    var number = card.getAttribute('data-bbb-rc-number') || '';
    var category = card.getAttribute('data-bbb-rc-category') || 'prompt';
    var title = card.querySelector('h3') ? card.querySelector('h3').textContent : '';
    var detail = card.getAttribute('data-bbb-rc-detail') || '';
    var link = card.getAttribute('data-bbb-rc-link') || '#';
    var label = card.getAttribute('data-bbb-rc-link-label') || 'open the shelf';
    if (modalCat) modalCat.textContent = 'prompt ' + String(number).padStart(2, '0') + ' · ' + category;
    if (modalTitle) modalTitle.textContent = title;
    if (modalDetail) modalDetail.textContent = detail;
    if (modalLink) {
      modalLink.href = link;
      modalLink.textContent = label;
    }
    openModal(promptModal);
  }

  readState();

  cards.forEach(function (card) {
    var number = card.getAttribute('data-bbb-rc-number');
    var toggle = card.querySelector('[data-bbb-rc-toggle]');
    var note = card.querySelector('[data-bbb-rc-note]');

    if (toggle) {
      toggle.addEventListener('click', function () {
        state[number] = state[number] || {};
        state[number].done = !state[number].done;
        render();
      });
    }

    if (note) {
      note.addEventListener('input', function () {
        state[number] = state[number] || {};
        state[number].note = note.value;
        if (note.value.trim()) state[number].done = true;
        render();
      });
    }

    var promptButton = card.querySelector('[data-bbb-rc-open-prompt]');
    if (promptButton) {
      promptButton.addEventListener('click', function () {
        openPrompt(card);
      });
    }
  });

  filters.forEach(function (button) {
    button.addEventListener('click', function () {
      activeFilter = button.getAttribute('data-bbb-rc-filter') || 'all';
      filters.forEach(function (item) {
        item.classList.toggle('is-active', item === button);
      });
      applyFilter();
    });
  });

  Array.prototype.slice.call(root.querySelectorAll('[data-bbb-rc-open-tracker]')).forEach(function (button) {
    button.addEventListener('click', function () {
      openModal(trackerModal);
    });
  });

  Array.prototype.slice.call(root.querySelectorAll('[data-bbb-rc-close]')).forEach(function (button) {
    button.addEventListener('click', closeModals);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') closeModals();
  });

  if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.12 });

    Array.prototype.slice.call(root.querySelectorAll('.bbb-rc__prompt, .bbb-rc__afterGrid a')).forEach(function (item) {
      item.classList.add('is-reveal');
      observer.observe(item);
    });
  }

  render();
})();
