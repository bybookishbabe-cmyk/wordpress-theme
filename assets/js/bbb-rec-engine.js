(function(window) {
  'use strict';

  function normalize(value) {
    return String(value || '').toLowerCase().trim();
  }

  function escapeRegex(value) {
    return String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function containsPhrase(value, phrase) {
    var haystack = normalize(value);
    var needle = normalize(phrase);
    if (!haystack || !needle) return false;
    return new RegExp('(^|[^a-z0-9])' + escapeRegex(needle) + '($|[^a-z0-9])').test(haystack);
  }

  function includesAny(value, needles) {
    return (needles || []).some(function(needle) {
      return containsPhrase(value, needle);
    });
  }

  function uniq(items) {
    var seen = {};
    return (items || []).filter(function(item) {
      var key = normalize(item);
      if (!key || seen[key]) return false;
      seen[key] = true;
      return true;
    });
  }

  var tropeAliases = {
    mafia: ['mafia', 'mafia romance', 'bratva', 'cartel', 'organized crime', 'mob romance'],
    dark: ['dark romance', 'extra dark', 'morally gray', 'morally grey', 'villain gets the girl', 'touch her and die', 'stalker romance', 'captor', 'captive'],
    obsession: ['obsession', 'obsessive', 'possessive', 'stalker', 'touch her and die'],
    romantasy: ['romantasy', 'fantasy romance', 'fated mates', 'fae', 'dragon', 'magic'],
    banter: ['banter', 'enemies to lovers', 'rivals', 'forced proximity', 'opposites attract'],
    emotional: ['second chance', 'slow burn', 'friends to lovers', 'trauma bonding', 'angst', 'yearning']
  };

  var relatedTropeMap = {
    'touch her and die': ['trauma bonding', 'forced proximity', 'who did this to you', 'enemies to lovers', 'protective hero', 'mafia romance'],
    'mafia romance': ['bratva', 'cartel', 'touch her and die', 'possessive', 'dark romance', 'forbidden romance'],
    mafia: ['bratva', 'cartel', 'touch her and die', 'possessive', 'dark romance', 'forbidden romance'],
    bratva: ['mafia romance', 'touch her and die', 'possessive', 'dark romance'],
    cartel: ['mafia romance', 'touch her and die', 'possessive', 'dark romance'],
    'trauma bonding': ['touch her and die', 'forced proximity', 'who did this to you', 'enemies to lovers'],
    'forced proximity': ['touch her and die', 'trauma bonding', 'marriage of convenience', 'enemies to lovers'],
    'enemies to lovers': ['forced proximity', 'touch her and die', 'hate to love', 'rivals to lovers'],
    'slow burn': ['yearning', 'forced proximity', 'enemies to lovers'],
    'who did this to you': ['touch her and die', 'protective hero', 'trauma bonding']
  };

  function textForBook(book) {
    return [
      book && book.title,
      book && book.author,
      book && book.shelf,
      book && book.shelfSlug,
      book && book.boyfriend,
      book && book.boyfriendType,
      book && book.boyfriendName,
      book && book.yearning,
      (book && book.tropes || []).join(' ')
    ].join(' ');
  }

  function bookTropes(book) {
    return (book && book.tropes || []).map(normalize);
  }

  function bookHasTropeAlias(book, aliases) {
    var content = textForBook(book);
    return (aliases || []).some(function(alias) {
      var key = normalize(alias);
      if (!key) return false;
      return bookTropes(book).some(function(trope) {
        return trope === key || trope.indexOf(key) !== -1 || key.indexOf(trope) !== -1;
      }) || includesAny(content, [key]);
    });
  }

  function sharedTropes(baseBook, candidate) {
    var selected = bookTropes(baseBook);
    return uniq((candidate && candidate.tropes || []).filter(function(trope) {
      return selected.indexOf(normalize(trope)) !== -1;
    }));
  }

  function relatedTropeScore(baseBook, candidate) {
    var selected = bookTropes(baseBook);
    var candidateTropes = bookTropes(candidate);
    var score = 0;

    selected.forEach(function(trope) {
      (relatedTropeMap[trope] || []).forEach(function(related) {
        if (candidateTropes.indexOf(normalize(related)) !== -1) score += 1;
      });
    });

    return score;
  }

  function requestFromSeed(seed) {
    var preferences = seed && seed.preferences || {};
    var preferredTropes = uniq((preferences.tropes || []).concat(seed && seed.tropes || []));
    var request = {
      preferredTropes: preferredTropes,
      preferredShelf: preferences.shelf || seed && seed.shelf || '',
      minSpice: Number(preferences.minSpice || 0),
      maxSpice: Number(preferences.maxSpice || 0),
      minDarkness: Number(preferences.minDarkness || 0),
      maxDarkness: Number(preferences.maxDarkness || 0),
      kuOnly: preferences.access === 'ku',
      requiredAnyTropeAliases: [],
      excludedHandles: seed && seed.handle ? [seed.handle] : [],
      excludedTitles: seed && seed.title ? [seed.title] : []
    };

    if (preferences.vibe === 'mafia') {
      request.requiredAnyTropeAliases = tropeAliases.mafia.slice();
    } else if (preferences.vibe === 'danger') {
      request.requiredAnyTropeAliases = tropeAliases.dark.slice();
    }

    return request;
  }

  function exclusionReason(book, request) {
    var handle = normalize(book && book.handle);
    if (handle && (request.excludedHandles || []).map(normalize).indexOf(handle) !== -1) return 'same-source';
    var title = normalize(book && book.title);
    if (title && (request.excludedTitles || []).map(normalize).indexOf(title) !== -1) return 'same-source';
    if (request.kuOnly && String(book && book.ku) !== 'true') return 'ku-required';
    if (request.minSpice && Number(book && book.spice || 0) < request.minSpice) return 'spice-too-low';
    if (request.maxSpice && Number(book && book.spice || 0) > request.maxSpice) return 'spice-too-high';
    if (request.minDarkness && Number(book && book.darkness || 0) < request.minDarkness) return 'darkness-too-low';
    if (request.maxDarkness && Number(book && book.darkness || 0) > request.maxDarkness) return 'darkness-too-high';
    if ((request.requiredAnyTropeAliases || []).length && !bookHasTropeAlias(book, request.requiredAnyTropeAliases)) return 'required-trope-missing';
    return '';
  }

  function scoreCandidate(seed, candidate, request) {
    var shared = sharedTropes(seed, candidate);
    var sameShelf = seed && seed.shelf && candidate && candidate.shelf && normalize(seed.shelf) === normalize(candidate.shelf);
    var spiceDiff = Math.abs(Number(seed && seed.spice || 0) - Number(candidate && candidate.spice || 0));
    var darknessDiff = Math.abs(Number(seed && seed.darkness || 0) - Number(candidate && candidate.darkness || 0));
    var relatedScore = relatedTropeScore(seed, candidate);
    var seedMostLike = (seed && seed.mostLike || []).map(normalize);
    var candidateMostLike = (candidate && candidate.mostLike || []).map(normalize);
    var manualMatchScore = 0;
    var preferredTropes = (request.preferredTropes || []).map(normalize);
    var candidateTropes = bookTropes(candidate);
    var preferredOverlap = preferredTropes.filter(function(trope) {
      return candidateTropes.some(function(candidateTrope) {
        return candidateTrope === trope || candidateTrope.indexOf(trope) !== -1 || trope.indexOf(candidateTrope) !== -1;
      });
    }).length;
    var preferredShelf = request.preferredShelf && candidate && candidate.shelf && normalize(request.preferredShelf) === normalize(candidate.shelf);
    var boyfriendScore = seed && seed.boyfriend && normalize(seed.boyfriend) === normalize(candidate && candidate.boyfriend) ? 24 : 0;

    if (candidate && candidate.handle && seedMostLike.indexOf(normalize(candidate.handle)) !== -1) {
      manualMatchScore = 260;
    } else if (seed && seed.handle && candidateMostLike.indexOf(normalize(seed.handle)) !== -1) {
      manualMatchScore = 230;
    }

    var matchScore = manualMatchScore +
      (shared.length * 100) +
      (preferredOverlap * 52) +
      (relatedScore * 28) +
      (sameShelf ? 150 : 0) +
      (preferredShelf ? 80 : 0) +
      boyfriendScore -
      (spiceDiff * 10) -
      (darknessDiff * 8);

    if ((request.requiredAnyTropeAliases || []).length) matchScore += 120;
    if (request.minSpice && Number(candidate && candidate.spice || 0) >= request.minSpice) matchScore += 55;
    if (request.minDarkness && Number(candidate && candidate.darkness || 0) >= request.minDarkness) matchScore += 35;

    return {
      book: candidate,
      shared: shared,
      sameShelf: sameShelf,
      spiceDiff: spiceDiff,
      darknessDiff: darknessDiff,
      relatedScore: relatedScore,
      manualMatchScore: manualMatchScore,
      preferredOverlap: preferredOverlap,
      matchScore: matchScore,
      reasons: buildReasons(candidate, shared, request, sameShelf, preferredOverlap)
    };
  }

  function buildReasons(book, shared, request, sameShelf, preferredOverlap) {
    var reasons = [];
    if ((request.requiredAnyTropeAliases || []).length) {
      if (bookHasTropeAlias(book, tropeAliases.mafia)) reasons.push('mafia romance');
      else reasons.push('required trope match');
    }
    if (request.minSpice) reasons.push(Number(book && book.spice || 0) + '/5 spice');
    if (sameShelf && book && book.shelf) reasons.push('same ' + book.shelf + ' shelf');
    if (shared && shared.length) reasons.push('shares ' + shared.slice(0, 2).join(' + '));
    if (preferredOverlap && !shared.length) reasons.push('matches your requested trope lane');
    return uniq(reasons);
  }

  function sortByStrength(a, b) {
    if (b.matchScore !== a.matchScore) return b.matchScore - a.matchScore;
    if (b.manualMatchScore !== a.manualMatchScore) return b.manualMatchScore - a.manualMatchScore;
    if (b.sameShelf !== a.sameShelf) return b.sameShelf ? 1 : -1;
    if (b.shared.length !== a.shared.length) return b.shared.length - a.shared.length;
    if (b.relatedScore !== a.relatedScore) return b.relatedScore - a.relatedScore;
    if (a.darknessDiff !== b.darknessDiff) return a.darknessDiff - b.darknessDiff;
    if (a.spiceDiff !== b.spiceDiff) return a.spiceDiff - b.spiceDiff;
    return String(a.book && a.book.title || '').localeCompare(String(b.book && b.book.title || ''));
  }

  function rank(books, seed) {
    var request = requestFromSeed(seed || {});
    return (books || []).map(function(book) {
      var excluded = exclusionReason(book, request);
      if (excluded) return { book: book, excluded: excluded, matchScore: -Infinity, shared: [], reasons: [] };
      return scoreCandidate(seed || {}, book, request);
    }).filter(function(match) {
      return !match.excluded;
    }).sort(sortByStrength);
  }

  function pick(pool, used, offset) {
    if (!pool.length) return null;
    var start = offset % pool.length;
    for (var i = 0; i < pool.length; i += 1) {
      var candidate = pool[(start + i) % pool.length];
      if (used.indexOf(candidate.book.handle) === -1) return candidate;
    }
    return null;
  }

  function matches(books, seed, rotationStep) {
    var ranked = rank(books, seed);
    var used = [];
    var first = pick(ranked, used, rotationStep || 0);
    if (first) used.push(first.book.handle);

    var tropePool = ranked.filter(function(candidate) {
      return candidate.shared.length >= 1 || candidate.preferredOverlap > 0;
    });
    var second = pick(tropePool.length ? tropePool : ranked, used, (rotationStep || 0) + 1);
    if (second) used.push(second.book.handle);

    var spicePool = ranked.filter(function(candidate) {
      return candidate.spiceDiff <= 1;
    });
    var third = pick(spicePool.length ? spicePool : ranked, used, (rotationStep || 0) + 2);

    return [first, second, third].filter(Boolean);
  }

  window.BBBRecEngine = {
    matches: matches,
    rank: rank,
    requestFromSeed: requestFromSeed,
    tropeAliases: tropeAliases
  };
})(window);
