(function () {
  var popup = document.querySelector('[data-bbb-shop-drop]');
  if (!popup) return;

  var id = popup.getAttribute('data-drop-id') || 'shop-drop';
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
    }
  });

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
