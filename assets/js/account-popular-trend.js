(function () {
  var card = document.querySelector('[data-account-popular-trend]');
  if (!card || !window.BBBAccountPopularTrend) return;

  var config = window.BBBAccountPopularTrend;
  var candidates = Array.isArray(config.candidates) ? config.candidates : [];

  function normalizePath(path) {
    if (!path) return '/';
    try {
      path = new URL(path, window.location.origin).pathname;
    } catch (error) {}
    path = '/' + String(path).replace(/^\/+|\/+$/g, '') + '/';
    return path === '//' ? '/' : path;
  }

  function ignoredPath(path) {
    path = normalizePath(path);
    if (path === '/' || path === '/library/') return true;
    return /\/(?:wp-admin|wp-json|cart|checkout|account|my-account)\//.test(path);
  }

  function candidateMap() {
    var map = {};
    candidates.forEach(function (item) {
      var key = normalizePath(item.path || item.url);
      if (!map[key]) map[key] = item;
    });
    return map;
  }

  function labelFromPath(path) {
    return String(path || '')
      .replace(/^\/|\/$/g, '')
      .replace(/-/g, ' ')
      .replace(/\b\w/g, function (char) {
        return char.toUpperCase();
      });
  }

  function applyTrend(item) {
    if (!item || !item.url) return;
    var small = card.querySelector('small');
    var title = card.querySelector('strong');

    card.href = item.url;
    if (small) {
      small.textContent = item.visits ? 'no. 1 page - ' + item.visits + ' visits' : (item.type || 'no. 1 page');
    }
    if (title) {
      title.textContent = item.title || 'reader favorite';
    }
  }

  async function loadTrend() {
    if (!window.supabase || !window.supabase.createClient || !config.supabaseUrl || !config.supabaseKey) {
      return;
    }

    var client = window.supabase.createClient(config.supabaseUrl, config.supabaseKey);
    var since = new Date(Date.now() - 1000 * 60 * 60 * 24 * 30).toISOString();
    var response = await client
      .from('site_events')
      .select('page_path,page_title,created_at')
      .eq('event_type', 'daily_visit')
      .gte('created_at', since)
      .order('created_at', { ascending: false })
      .limit(1500);

    if (response.error || !Array.isArray(response.data)) return;

    var knownByPath = candidateMap();
    var counts = {};
    response.data.forEach(function (row) {
      var path = normalizePath(row.page_path || '');
      if (ignoredPath(path)) return;

      if (!counts[path]) {
        var known = knownByPath[path] || {};
        counts[path] = {
          title: known.title || row.page_title || labelFromPath(path),
          url: known.url || path,
          path: path,
          type: known.type || 'popular page',
          visits: 0
        };
      }
      counts[path].visits += 1;
    });

    var ranked = Object.keys(counts)
      .map(function (path) {
        return counts[path];
      })
      .sort(function (a, b) {
        if (b.visits !== a.visits) return b.visits - a.visits;
        return String(a.title || '').localeCompare(String(b.title || ''));
      });

    if (ranked[0]) {
      applyTrend(ranked[0]);
    }
  }

  loadTrend().catch(function () {});
})();
