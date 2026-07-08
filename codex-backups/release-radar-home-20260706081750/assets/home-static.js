(function () {
  var targets = document.querySelectorAll('.bbb-romance-lists, .sss-lib--preview, #bbb-newsletter-cta-society-hero');
  var dashboardScroller = document.querySelector('.bbb-home-shelf-week--dashboard .bbb-home-shelf-week__inner');
  var madeForYouDashboard = document.querySelector('[data-home-mfy-dashboard]');
  var monthlyCountdowns = document.querySelectorAll('[data-monthly-release]');

  function getStoredTasteProfile() {
    try {
      return JSON.parse(window.localStorage.getItem('bbbReaderTasteProfile') || '{}') || {};
    } catch (error) {
      return {};
    }
  }

  function getStoredMadeForYouProfile() {
    try {
      var profile = JSON.parse(window.localStorage.getItem('sssMadeForYouProfile') || '{}') || {};
      var readerState = JSON.parse(window.localStorage.getItem('bbbReaderTypeState') || '{}') || {};
      var tasteProfile = getStoredTasteProfile();
      if (readerState.key && !profile.reader_type_prior) {
        profile.reader_type_prior = readerState.key;
      }
      if (tasteProfile.reader_type && !profile.reader_type_prior) {
        profile.reader_type_prior = tasteProfile.reader_type;
      }
      if ((profile.reader_type_prior || readerState.key) && !profile.dashboard_built) {
        profile.dashboard_built = true;
      }
      return profile;
    } catch (error) {
      return {};
    }
  }

  function isMadeForYouReady(profile) {
    return !!(profile && profile.dashboard_built && (profile.reader_type_prior || profile.theme));
  }

  function syncHomeMadeForYouDashboard() {
    if (!madeForYouDashboard) return;

    var tasteProfile = getStoredTasteProfile();
    var madeForYouProfile = getStoredMadeForYouProfile();
    var serverReady = madeForYouDashboard.getAttribute('data-home-mfy-server-ready') === 'true';
    var serverReaderType = madeForYouDashboard.getAttribute('data-server-reader-type') || '';
    var isReady = isMadeForYouReady(madeForYouProfile) || serverReady;
    var theme = isReady ? (tasteProfile.dashboard_theme || madeForYouProfile.theme || '') : '';
    var readerType = isReady ? (madeForYouProfile.reader_type_prior || tasteProfile.reader_type || serverReaderType || '') : '';

    madeForYouDashboard.classList.toggle('is-home-mfy-locked', !isReady);
    madeForYouDashboard.classList.toggle('is-home-mfy-ready', isReady);
    madeForYouDashboard.setAttribute('data-home-mfy-locked', isReady ? 'false' : 'true');

    if (!isReady) {
      madeForYouDashboard.removeAttribute('data-home-mfy-theme');
      madeForYouDashboard.removeAttribute('data-mfy-theme');
      madeForYouDashboard.removeAttribute('data-reader-theme');
      return;
    }

    if (readerType) {
      madeForYouDashboard.setAttribute('data-reader-theme', readerType);
    }

    if (theme) {
      madeForYouDashboard.setAttribute('data-home-mfy-theme', theme);
      madeForYouDashboard.setAttribute('data-mfy-theme', theme);
    }
  }

  function openHomeDashboard(url) {
    if (!url) return;
    window.location.href = url;
  }

  function initHomeMadeForYouDashboard() {
    if (!madeForYouDashboard) return;

    var dashboardUrl = madeForYouDashboard.getAttribute('data-dashboard-url') || '/made-for-you/';
    syncHomeMadeForYouDashboard();

    madeForYouDashboard.addEventListener('click', function (event) {
      var book = event.target.closest('.bbb-home-shelf-week__book[data-url]');
      var heart = event.target.closest('[data-heart]');
      var link = event.target.closest('a');

      if (heart) {
        return;
      }

      if (book) {
        var bookUrl = book.getAttribute('data-url');
        if (bookUrl) {
          event.preventDefault();
          event.stopPropagation();
          if (event.stopImmediatePropagation) {
            event.stopImmediatePropagation();
          }
          openHomeDashboard(bookUrl);
        }
        return;
      }

      if (link) {
        return;
      }

      openHomeDashboard(dashboardUrl);
    }, true);

    madeForYouDashboard.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      if (event.target.closest('a, button, [data-heart]')) return;

      event.preventDefault();
      openHomeDashboard(dashboardUrl);
    });
  }

  function padCountdown(value) {
    return String(value).padStart(2, '0');
  }

  monthlyCountdowns.forEach(function (root) {
    var label = root.querySelector('.bbb-monthly-teaser__countdown-label');
    var daysNode = root.querySelector('[data-monthly-days]');
    var hoursNode = root.querySelector('[data-monthly-hours]');
    var minutesNode = root.querySelector('[data-monthly-minutes]');
    var secondsNode = root.querySelector('[data-monthly-seconds]');
    var releaseAt = new Date(root.getAttribute('data-monthly-release') || '');

    if (!daysNode || !hoursNode || !minutesNode || !secondsNode || Number.isNaN(releaseAt.getTime())) {
      return;
    }

    function updateCountdown() {
      var diff = releaseAt.getTime() - Date.now();
      if (diff <= 0) {
        if (label) {
          label.textContent = 'released';
        }
        daysNode.textContent = '00';
        hoursNode.textContent = '00';
        minutesNode.textContent = '00';
        secondsNode.textContent = '00';
        return;
      }

      var totalSeconds = Math.floor(diff / 1000);
      var days = Math.floor(totalSeconds / 86400);
      var hours = Math.floor((totalSeconds % 86400) / 3600);
      var minutes = Math.floor((totalSeconds % 3600) / 60);
      var seconds = totalSeconds % 60;

      daysNode.textContent = padCountdown(days);
      hoursNode.textContent = padCountdown(hours);
      minutesNode.textContent = padCountdown(minutes);
      secondsNode.textContent = padCountdown(seconds);
    }

    updateCountdown();
    window.setInterval(updateCountdown, 1000);
  });

  initHomeMadeForYouDashboard();

  if (dashboardScroller && window.matchMedia && window.matchMedia('(max-width: 640px)').matches) {
    window.setTimeout(function () {
      if (dashboardScroller.scrollWidth <= dashboardScroller.clientWidth || dashboardScroller.scrollLeft > 4) {
        return;
      }

      var nudgeLeft = Math.min(54, dashboardScroller.scrollWidth - dashboardScroller.clientWidth);
      dashboardScroller.scrollLeft = Math.min(24, nudgeLeft);
      window.setTimeout(function () {
        dashboardScroller.scrollTo({
          left: nudgeLeft,
          behavior: 'smooth'
        });
      }, 80);
    }, 850);
  }

  if (!targets.length) {
    return;
  }

  document.documentElement.classList.add('bbb-home-animate-ready');

  if (!('IntersectionObserver' in window)) {
    targets.forEach(function (target) {
      target.classList.add('is-bbb-revealed');
    });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) {
        return;
      }

      entry.target.classList.add('is-bbb-revealed');
      observer.unobserve(entry.target);
    });
  }, {
    rootMargin: '0px 0px -16% 0px',
    threshold: 0.18
  });

  targets.forEach(function (target) {
    observer.observe(target);
  });
})();
