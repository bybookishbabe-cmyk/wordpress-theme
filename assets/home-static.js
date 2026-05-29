(function () {
  var targets = document.querySelectorAll('.bbb-romance-lists, .sss-lib--preview, #bbb-newsletter-cta-society-hero');
  var dashboardScroller = document.querySelector('.bbb-home-shelf-week--dashboard .bbb-home-shelf-week__inner');

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
