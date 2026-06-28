(function () {
  var popup = document.querySelector('[data-bbb-shop-drop]');
  if (!popup) return;

  var id = popup.getAttribute('data-drop-id') || 'shop-drop';
  var dialog = popup.querySelector('[data-bbb-shop-drop-link]');
  var sessionKey = 'bbbShopDropShown:' + id;
  var shown = false;
  var timer = null;

  try {
    if (window.sessionStorage && window.sessionStorage.getItem(sessionKey) === '1') return;
  } catch (error) {}

  function markShown() {
    try {
      if (window.sessionStorage) window.sessionStorage.setItem(sessionKey, '1');
    } catch (error) {}
  }

  function show() {
    if (shown) return;
    shown = true;
    markShown();
    popup.hidden = false;
    window.requestAnimationFrame(function () {
      popup.classList.add('is-visible');
    });
  }

  function hide() {
    popup.classList.remove('is-visible');
    window.setTimeout(function () {
      popup.hidden = true;
    }, 260);
  }

  popup.addEventListener('click', function (event) {
    if (event.target.closest('[data-bbb-shop-drop-close]')) {
      event.preventDefault();
      hide();
      return;
    }

    if (event.target.closest('a, button, input, select, textarea')) {
      return;
    }

    var linkTarget = event.target.closest('[data-bbb-shop-drop-link]');
    if (linkTarget) {
      var url = linkTarget.getAttribute('data-bbb-shop-drop-link');
      if (url) window.location.href = url;
    }
  });

  if (dialog) {
    dialog.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      if (event.target.closest('a, button, input, select, textarea')) return;

      var url = dialog.getAttribute('data-bbb-shop-drop-link');
      if (!url) return;

      event.preventDefault();
      window.location.href = url;
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !popup.hidden) hide();
  });

  document.addEventListener('mouseout', function (event) {
    if (shown || event.relatedTarget || event.clientY > 8) return;
    if (timer) window.clearTimeout(timer);
    show();
  });

  timer = window.setTimeout(show, 30000);
})();
