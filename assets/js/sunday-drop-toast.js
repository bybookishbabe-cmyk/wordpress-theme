(function () {
  var toast = document.querySelector('[data-bbb-sunday-drop]');
  if (!toast) return;

  var account = window.BBBReaderAccount || {};
  if (account.hasEmailAccess || account.isSociety) return;

  var toastId = toast.getAttribute('data-toast-id') || 'sunday-drop';
  var sessionKey = 'bbbSundayDropShown:' + toastId;

  try {
    if (window.sessionStorage && window.sessionStorage.getItem(sessionKey) === '1') return;
  } catch (error) {}

  function pacificNowParts() {
    var formatter = new Intl.DateTimeFormat('en-US', {
      timeZone: 'America/Los_Angeles',
      weekday: 'short',
      hour: 'numeric',
      hour12: false
    });
    var parts = formatter.formatToParts(new Date());
    var values = {};
    parts.forEach(function (part) {
      values[part.type] = part.value;
    });
    return {
      weekday: values.weekday || '',
      hour: parseInt(values.hour || '0', 10)
    };
  }

  function isDropWindow() {
    var now = pacificNowParts();
    var isSundayOrMonday = now.weekday === 'Sun' || now.weekday === 'Mon';
    return isSundayOrMonday && now.hour >= 8 && now.hour < 20;
  }

  if (!isDropWindow()) return;

  function hide() {
    toast.classList.remove('is-visible');
    window.setTimeout(function () {
      toast.hidden = true;
    }, 260);
  }

  var close = toast.querySelector('[data-bbb-sunday-drop-close]');
  if (close) {
    close.addEventListener('click', function (event) {
      event.preventDefault();
      hide();
    });
  }

  try {
    if (window.sessionStorage) window.sessionStorage.setItem(sessionKey, '1');
  } catch (error) {}

  window.setTimeout(function () {
    toast.hidden = false;
    window.requestAnimationFrame(function () {
      toast.classList.add('is-visible');
    });
  }, 900);

  window.setTimeout(hide, 10900);
})();
