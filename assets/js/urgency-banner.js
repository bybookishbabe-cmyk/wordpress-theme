(function () {
  var banner = document.querySelector('[data-bbb-urgency-banner]');
  if (!banner) return;

  var id = banner.getAttribute('data-banner-id') || 'bbb-urgency-banner';
  var storageKey = 'bbbUrgencyDismissed:' + id;
  var targetValue = banner.getAttribute('data-target-time') || '';
  var targetTime = Date.parse(targetValue);
  var dismiss = banner.querySelector('[data-bbb-urgency-dismiss]');
  var daysNode = banner.querySelector('[data-bbb-days]');
  var hoursNode = banner.querySelector('[data-bbb-hours]');
  var minutesNode = banner.querySelector('[data-bbb-minutes]');
  var secondsNode = banner.querySelector('[data-bbb-seconds]');

  if (!targetTime || Number.isNaN(targetTime)) return;

  try {
    if (window.localStorage && window.localStorage.getItem(storageKey) === '1') return;
  } catch (error) {}

  // Swap CTA for any logged-in member (free or paid), regardless of page cache state.
  // BBBSiteData is injected server-side but may be stale in cached pages; this runs
  // fresh on every page load and corrects the CTA client-side.
  var account = window.BBBSiteData && window.BBBSiteData.BBBReaderAccount;
  if (banner.getAttribute('data-banner-mode') !== 'live' && account && (account.loggedIn || account.hasEmailAccess)) {
    var societyUrl = banner.getAttribute('data-society-url') || '/smut-sentiment-society/';
    var cta = banner.querySelector('.bbb-urgency-banner__cta');
    if (cta) {
      // Non-paid member: CTA exists, update it in place.
      cta.href = societyUrl;
      cta.removeAttribute('target');
      cta.removeAttribute('rel');
      cta.textContent = 'the society';
    } else {
      // Paid member: PHP omits the CTA — create one.
      var inner = banner.querySelector('.bbb-urgency-banner__inner');
      var dismissBtn = banner.querySelector('[data-bbb-urgency-dismiss]');
      if (inner && dismissBtn) {
        var newCta = document.createElement('a');
        newCta.className = 'bbb-urgency-banner__cta';
        newCta.href = societyUrl;
        newCta.textContent = 'the society';
        inner.insertBefore(newCta, dismissBtn);
        banner.classList.remove('bbb-urgency-banner--no-cta');
      }
    }
  }

  function pad(value) {
    return String(value).padStart(2, '0');
  }

  function update() {
    var remaining = targetTime - Date.now();
    if (remaining <= 0) {
      banner.hidden = true;
      window.clearInterval(interval);
      return;
    }

    var totalSeconds = Math.floor(remaining / 1000);
    var days = Math.floor(totalSeconds / 86400);
    var hours = Math.floor((totalSeconds % 86400) / 3600);
    var minutes = Math.floor((totalSeconds % 3600) / 60);
    var seconds = totalSeconds % 60;

    if (daysNode) daysNode.textContent = pad(days);
    if (hoursNode) hoursNode.textContent = pad(hours);
    if (minutesNode) minutesNode.textContent = pad(minutes);
    if (secondsNode) secondsNode.textContent = pad(seconds);
    banner.hidden = false;
  }

  if (dismiss) {
    dismiss.addEventListener('click', function () {
      banner.hidden = true;
      try {
        if (window.localStorage) window.localStorage.setItem(storageKey, '1');
      } catch (error) {}
    });
  }

  update();
  var interval = window.setInterval(update, 1000);
})();
