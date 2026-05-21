(function () {
  function apiConfig() {
    return window.BBBReaderAccountApi || {};
  }

  function message(node, text, tone) {
    if (!node) return;
    node.textContent = text || '';
    node.dataset.tone = tone || '';
    node.hidden = !text;
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
      message(status, 'email found. opening your account...', 'success');
      window.setTimeout(function () {
        window.location.reload();
      }, 450);
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
