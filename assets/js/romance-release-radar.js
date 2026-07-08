(function () {
  var radar = document.querySelector('[data-radar-page]');
  if (!radar) {
    return;
  }

  function setPanelHeight(item) {
    var panel = item.querySelector('.rrr-archive__panel');
    if (!panel) {
      return;
    }

    panel.style.maxHeight = item.classList.contains('is-open') ? panel.scrollHeight + 'px' : null;
  }

  radar.querySelectorAll('.rrr-archive__item').forEach(setPanelHeight);

  radar.querySelectorAll('.rrr-archive__toggle').forEach(function (button) {
    button.addEventListener('click', function () {
      var item = button.closest('.rrr-archive__item');
      if (!item) {
        return;
      }

      var isOpen = item.classList.contains('is-open');
      radar.querySelectorAll('.rrr-archive__item').forEach(function (archiveItem) {
        archiveItem.classList.remove('is-open');
        setPanelHeight(archiveItem);
      });

      if (!isOpen) {
        item.classList.add('is-open');
        setPanelHeight(item);
      }
    });
  });

  radar.querySelectorAll('[data-radar-alert-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var button = form.querySelector('[data-radar-alert-toggle]');
      var status = form.querySelector('[data-radar-alert-status]');
      var config = window.bbbRomanceReleaseRadar || {};
      if (!button) {
        return;
      }

      if (!config.alertsEndpoint) {
        if (status) {
          status.textContent = 'radar alerts are not connected yet.';
        }
        return;
      }

      var nextEnabled = button.getAttribute('data-enabled') !== '1';
      button.disabled = true;
      if (status) {
        status.textContent = 'saving...';
      }

      fetch(config.alertsEndpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': config.nonce || ''
        },
        body: JSON.stringify({ radar_alerts: nextEnabled })
      })
        .then(function (response) {
          return response.json().then(function (body) {
            if (!response.ok) {
              throw new Error(body && body.message ? body.message : 'could not save radar alerts.');
            }
            return body;
          });
        })
        .then(function (body) {
          var enabled = !!body.radarAlerts;
          button.setAttribute('data-enabled', enabled ? '1' : '0');
          button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
          button.textContent = enabled ? 'radar alerts on' : 'turn on radar alerts';
          if (status) {
            status.textContent = enabled ? 'you are set for radar alerts.' : 'radar alerts are off.';
          }
        })
        .catch(function (error) {
          if (status) {
            status.textContent = error.message || 'could not save radar alerts.';
          }
        })
        .finally(function () {
          button.disabled = false;
        });
    });
  });
}());
