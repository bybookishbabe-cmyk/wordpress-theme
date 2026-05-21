(function(){
  var root = document.querySelector('[data-popular-pages]');
  if (!root || !window.BBBPopularPages) return;

  var candidates = Array.isArray(window.BBBPopularPages.candidates) ? window.BBBPopularPages.candidates : [];
  var list = root.querySelector('[data-popular-list]');
  var feature = root.querySelector('[data-popular-feature]');
  var status = root.querySelector('[data-popular-status]');
  var windowLabel = root.querySelector('[data-popular-window]');

  function normalizePath(path){
    if (!path) return '/';
    try {
      path = new URL(path, window.location.origin).pathname;
    } catch(error) {}
    path = '/' + String(path).replace(/^\/+|\/+$/g, '') + '/';
    return path === '//' ? '/' : path;
  }

  function candidateMap(){
    var map = {};
    candidates.forEach(function(item){
      var key = normalizePath(item.path || item.url);
      if (!map[key]) map[key] = item;
    });
    return map;
  }

  function escapeHtml(value){
    return String(value || '').replace(/[&<>"']/g, function(char){
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char];
    });
  }

  function itemMarkup(item, index){
    var rank = String(index + 1).padStart(2, '0');
    return [
      '<a class="bbb-popular__card" href="' + escapeHtml(item.url) + '">',
        '<span class="bbb-popular__cardRank">' + rank + '</span>',
        '<span class="bbb-popular__cardBody">',
          '<span class="bbb-popular__type">' + escapeHtml(item.type || 'reader favorite') + '</span>',
          '<strong>' + escapeHtml(item.title || 'reader favorite') + '</strong>',
          '<span>' + escapeHtml(item.description || 'one of the pages readers keep coming back to.') + '</span>',
        '</span>',
        '<span class="bbb-popular__open">' + escapeHtml(item.visits ? item.visits + ' visits' : 'open') + '</span>',
      '</a>'
    ].join('');
  }

  function featureMarkup(item){
    if (!item) return '';
    return [
      '<a class="bbb-popular__featureLink" href="' + escapeHtml(item.url) + '">',
        '<span class="bbb-popular__rank">01</span>',
        '<span class="bbb-popular__featureCopy">',
          '<span class="bbb-popular__type">' + escapeHtml(item.type || 'reader favorite') + '</span>',
          '<strong>' + escapeHtml(item.title || 'reader favorite') + '</strong>',
          '<span>' + escapeHtml(item.description || 'one of the pages readers keep coming back to.') + '</span>',
        '</span>',
      '</a>'
    ].join('');
  }

  function render(items, sourceLabel){
    var top = items.slice(0, 10);
    if (!top.length) return;
    if (feature) feature.innerHTML = featureMarkup(top[0]);
    if (list) list.innerHTML = top.map(itemMarkup).join('');
    if (status) status.textContent = sourceLabel;
  }

  function fallbackItems(){
    return candidates.slice(0, 10);
  }

  function ignoredPath(path){
    return /\/(?:wp-admin|wp-json|cart|checkout|account|my-account)\//.test(path);
  }

  async function loadLiveRanking(){
    if (!window.supabase || !window.supabase.createClient) {
      render(fallbackItems(), 'curated fallback');
      return;
    }

    var client = window.supabase.createClient(window.BBBPopularPages.supabaseUrl, window.BBBPopularPages.supabaseKey);
    var since = new Date(Date.now() - 1000 * 60 * 60 * 24 * 30).toISOString();
    var response = await client
      .from('site_events')
      .select('page_path,page_title,created_at')
      .eq('event_type', 'daily_visit')
      .gte('created_at', since)
      .order('created_at', { ascending: false })
      .limit(1500);

    if (response.error || !Array.isArray(response.data)) {
      render(fallbackItems(), 'curated fallback');
      return;
    }

    var map = candidateMap();
    var counts = {};
    response.data.forEach(function(row){
      var path = normalizePath(row.page_path || '');
      if (ignoredPath(path)) return;
      if (!counts[path]) {
        var known = map[path] || {};
        counts[path] = {
          title: known.title || row.page_title || path.replace(/^\/|\/$/g, '').replace(/-/g, ' '),
          url: known.url || path,
          path: path,
          type: known.type || 'popular page',
          description: known.description || 'a page readers visited often in the last 30 days.',
          visits: 0
        };
      }
      counts[path].visits += 1;
    });

    var ranked = Object.keys(counts)
      .map(function(path){ return counts[path]; })
      .sort(function(a, b){
        if (b.visits !== a.visits) return b.visits - a.visits;
        return String(a.title).localeCompare(String(b.title));
      });

    var used = {};
    ranked.forEach(function(item){ used[item.path] = true; });
    candidates.forEach(function(item){
      var path = normalizePath(item.path || item.url);
      if (!used[path]) {
        ranked.push(Object.assign({}, item, { visits: 0 }));
        used[path] = true;
      }
    });

    if (windowLabel) windowLabel.textContent = 'last 30 days';
    render(ranked, 'ranked by visits');
  }

  loadLiveRanking().catch(function(){
    render(fallbackItems(), 'curated fallback');
  });
})();
