(function () {
  function apiConfig() {
    var siteData = window.siteData || {};
    var api = window.BBBReaderAccountApi || siteData.readerAccount || {};

    if (!api.emailEndpoint) {
      api.emailEndpoint = window.location.origin + '/wp-json/bbb/v1/reader-account/email-session';
    }

    return api;
  }

  function message(node, text, tone) {
    if (!node) return;
    node.textContent = text || '';
    node.dataset.tone = tone || '';
    node.hidden = !text;
  }

  function isStandaloneApp() {
    return window.matchMedia && window.matchMedia('(display-mode: standalone)').matches
      || window.navigator.standalone === true
      || document.documentElement.classList.contains('bbb-is-pwa-app');
  }

  function syncHeaderAccountState() {
    var indicator = document.querySelector('.header__account-indicator');
    if (!indicator) return;

    indicator.classList.remove('header__account-indicator--visitor', 'header__account-indicator--paid');
    indicator.classList.add('header__account-indicator--free');
    indicator.setAttribute('aria-label', 'free reader account');
    indicator.setAttribute('title', 'free reader account');
  }

  function submitEmail(form) {
    var api = apiConfig();
    var status = form.querySelector('[data-reader-email-access-status]');
    var button = form.querySelector('button[type="submit"]');
    var input = form.querySelector('input[type="email"]');
    var email = input ? input.value.trim() : '';

    if (!api.emailEndpoint || !email) {
      message(status, 'enter the email you use for the society.', 'error');
      return;
    }

    if (button) button.disabled = true;
    message(status, 'checking your reader email...', '');

    window.fetch(api.emailEndpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': api.nonce || ''
      },
      body: JSON.stringify({ email: email })
    }).then(function (response) {
      return response.json().then(function (payload) {
        if (!response.ok) throw payload || new Error('Email access failed');
        return payload;
      });
    }).then(function () {
      var target = isStandaloneApp()
        ? '/?reader_opened=' + Date.now()
        : '/account/?reader_opened=' + Date.now();

      syncHeaderAccountState();
      message(status, isStandaloneApp() ? 'email found. opening your dashboard...' : 'email found. opening your account...', 'success');
      window.setTimeout(function () {
        window.location.replace(target);
      }, 100);
    }).catch(function (error) {
      var text = error && (error.message || (error.data && error.data.message)) || 'that email was not found yet.';
      message(status, text, 'error');
    }).finally(function () {
      if (button) button.disabled = false;
    });
  }

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-reader-email-access-form]');
    if (!form) return;

    event.preventDefault();
    submitEmail(form);
  });
})();
