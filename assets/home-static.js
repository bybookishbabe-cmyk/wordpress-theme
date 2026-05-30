(function () {
  var targets = document.querySelectorAll('.bbb-romance-lists, .sss-lib--preview, #bbb-newsletter-cta-society-hero');
  var dashboardScroller = document.querySelector('.bbb-home-shelf-week--dashboard .bbb-home-shelf-week__inner');
  var monthlyCountdowns = document.querySelectorAll('[data-monthly-release]');

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
