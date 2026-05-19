document.addEventListener('DOMContentLoaded', function(){
  var rotators = document.querySelectorAll('[data-trope-rotator]');

  rotators.forEach(function(stage){
    var sets = Array.from(stage.querySelectorAll('[data-trope-set]'));
    if (sets.length <= 1) return;

    var allCards = [];
    sets.forEach(function(set){
      Array.from(set.querySelectorAll('.blog-discovery__card--trope')).forEach(function(card){
        allCards.push(card.cloneNode(true));
      });
    });

    if (allCards.length <= 6) {
      sets.forEach(function(set, index){
        set.hidden = index !== 0;
        set.classList.toggle('is-active', index === 0);
      });
      return;
    }

    var pages = [];
    for (var i = 0; i < allCards.length; i += 6) {
      pages.push(allCards.slice(i, i + 6));
    }

    var lastPage = pages[pages.length - 1];
    if (lastPage && lastPage.length < 6) {
      for (var fillIndex = 0; lastPage.length < 6; fillIndex += 1) {
        lastPage.push(allCards[fillIndex % allCards.length].cloneNode(true));
      }
    }

    var activeSet = sets[0];
    sets.slice(1).forEach(function(set){
      set.remove();
    });

    function renderPage(pageIndex){
      activeSet.innerHTML = '';
      pages[pageIndex].forEach(function(card){
        activeSet.appendChild(card.cloneNode(true));
      });
    }

    var activeIndex = 0;
    activeSet.hidden = false;
    activeSet.classList.add('is-active');
    renderPage(activeIndex);

    window.setInterval(function(){
      activeSet.classList.remove('is-active');
      activeSet.classList.add('is-leaving');

      var nextIndex = (activeIndex + 1) % pages.length;

      window.setTimeout(function(){
        renderPage(nextIndex);
        activeSet.classList.remove('is-leaving');
        activeSet.classList.add('is-entering');

        window.requestAnimationFrame(function(){
          activeSet.classList.add('is-active');
          activeSet.classList.remove('is-entering');
        });
      }, 460);

      activeIndex = nextIndex;
    }, 8000);
  });
});
