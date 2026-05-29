(function () {
  var root = document.getElementById('bbb-sponsor-header');
  var modal = root ? root.querySelector('[data-bbb-sponsor-modal]') : document.querySelector('[data-bbb-sponsor-modal]');
  if (!modal) return;

  var closeButtons = modal.querySelectorAll('[data-bbb-sponsor-close]');
  var firstField = modal.querySelector('input, textarea, button');
  var lastTrigger = null;

  function openModal() {
    lastTrigger = document.activeElement;
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('bbb-sponsor-modal-open');
    if (firstField) firstField.focus();
  }

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('bbb-sponsor-modal-open');
    if (lastTrigger && lastTrigger.focus) lastTrigger.focus();
  }

  document.querySelectorAll('[data-bbb-sponsor-open]').forEach(function (openButton) {
    openButton.addEventListener('click', openModal);
  });

  closeButtons.forEach(function (button) {
    button.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal();
    }
  });

  if (window.location.hash === '#bbb-sponsor-header' && /[?&]bbb_sponsor=/.test(window.location.search)) {
    openModal();
  }
})();
