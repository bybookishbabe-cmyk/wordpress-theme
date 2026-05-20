(function () {
  function parseScores(value) {
    var scores = {};
    String(value || '').split(',').forEach(function (part) {
      var pair = part.split(':');
      var key = (pair[0] || '').trim();
      var amount = parseFloat(pair[1] || '0');
      if (key) scores[key] = amount || 0;
    });
    return scores;
  }

  function text(value) {
    return String(value || '').toLowerCase();
  }

  function includesAny(haystack, needles) {
    var value = text(haystack);
    return needles.some(function (needle) { return value.indexOf(needle) !== -1; });
  }

  function bookText(book) {
    return [
      book.title,
      book.author,
      book.shelf,
      book.shelfSlug,
      book.boyfriend,
      book.boyfriendName,
      book.yearning,
      book.reread,
      (book.tropes || []).join(' ')
    ].join(' ');
  }

  function topKey(scores, keys) {
    return keys.slice().sort(function (a, b) {
      return (scores[b] || 0) - (scores[a] || 0);
    })[0];
  }

  function profileFor(type, scores) {
    if (type === 'boyfriend') {
      var boyfriend = topKey(scores, ['gray', 'golden', 'rivals', 'broody', 'fantasy']);
      var map = {
        gray: {
          title: 'your fictional boyfriend is the morally gray protector',
          kicker: 'dangerous devotion',
          copy: 'He has plans, secrets, and exactly one person he would burn the plot down for.',
          tags: ['dark', 'protective', 'touch her and die']
        },
        golden: {
          title: 'your fictional boyfriend is the golden retriever menace',
          kicker: 'soft chaos',
          copy: 'He falls first, tries hard, and makes emotional availability look unfairly attractive.',
          tags: ['he falls first', 'sports', 'friends to lovers']
        },
        rivals: {
          title: 'your fictional boyfriend is the rival with banter privileges',
          kicker: 'chemistry problem',
          copy: 'He is annoying on purpose, obsessed by accident, and allergic to admitting feelings early.',
          tags: ['enemies to lovers', 'forced proximity', 'slow burn']
        },
        broody: {
          title: 'your fictional boyfriend is the wounded softie',
          kicker: 'quiet ache',
          copy: 'He has emotional damage, excellent restraint, and one devastating moment where the wall finally cracks.',
          tags: ['slow burn', 'second chance', 'emotional damage']
        },
        fantasy: {
          title: 'your fictional boyfriend is the cursed romantic lead',
          kicker: 'mythic attachment',
          copy: 'He probably has a kingdom, a curse, or wings. Maybe all three, which feels correct.',
          tags: ['romantasy', 'fated mates', 'villain gets the girl']
        }
      };
      return map[boyfriend] || map.gray;
    }

    var mood = topKey(scores, ['comfort', 'chaos', 'escape', 'cry', 'tension']);
    var moods = {
      comfort: {
        title: 'you need a soft landing romance',
        kicker: 'comfort read',
        copy: 'Low panic, high feelings, and a romance that knows tenderness can still ruin you.',
        tags: ['contemporary romance', 'friends to lovers', 'he falls first']
      },
      chaos: {
        title: 'you need an unhinged little spiral',
        kicker: 'chaos read',
        copy: 'Darker stakes, hotter choices, and a book that does not know how to behave.',
        tags: ['dark romance', 'touch her and die', 'mafia romance']
      },
      escape: {
        title: 'you need a full escape hatch',
        kicker: 'otherworldly read',
        copy: 'Magic, monsters, curses, immortals, or at minimum a man with impossible energy.',
        tags: ['romantasy', 'paranormal romance', 'fated mates']
      },
      cry: {
        title: 'you need emotional damage with a payoff',
        kicker: 'ache read',
        copy: 'Something tender, intense, and dramatic enough to make staring at the ceiling feel productive.',
        tags: ['second chance romance', 'trauma bonding', 'slow burn']
      },
      tension: {
        title: 'you need a slow-burn tension read',
        kicker: 'payoff read',
        copy: 'Banter, longing, almost-moments, and the sort of restraint that becomes everyone else’s problem.',
        tags: ['slow burn', 'enemies to lovers', 'forced proximity']
      }
    };
    return moods[mood] || moods.comfort;
  }

  function scoreBook(book, profile, scores, index) {
    var value = 0;
    var content = bookText(book);
    var spice = Number(book.spice || 0);
    var darkness = Number(book.darkness || 0);
    var damage = Number(book.damage || 0);
    var tension = Number(book.tension || 0);

    profile.tags.forEach(function (tag) {
      if (includesAny(content, [tag])) value += 22;
    });

    if ((scores.dark || scores.chaos || 0) > 0 && (includesAny(content, ['dark romance', 'mafia', 'stalker', 'touch her']) || darkness >= 3)) value += 24;
    if ((scores.golden || scores.comfort || scores.soft || 0) > 0 && (includesAny(content, ['sports romance', 'friends to lovers', 'he falls first', 'contemporary']) || spice <= 2)) value += 20;
    if ((scores.rivals || scores.enemies || scores.tension || 0) > 0 && (includesAny(content, ['enemies to lovers', 'forced proximity', 'slow burn']) || tension >= 3)) value += 20;
    if ((scores.fantasy || scores.escape || 0) > 0 && includesAny(content, ['romantasy', 'paranormal', 'fated mates', 'villain'])) value += 24;
    if ((scores.cry || scores.damage || scores.broody || 0) > 0 && (damage >= 3 || includesAny(content, ['second chance', 'trauma', 'historical', 'dystopian']))) value += 20;
    if ((scores.spicy || 0) > 0 && spice >= 4) value += 12;
    if ((scores.sweet || 0) > 0 && spice > 0 && spice <= 2) value += 12;
    if ((scores.medium || scores.slow || 0) > 0 && spice >= 2 && spice <= 3) value += 10;

    return value + Math.max(0, 8 - index * 0.02);
  }

  function esc(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function dataAttrs(book) {
    var tropes = (book.tropes || []).join(', ');
    return [
      ['data-book-preview', ''],
      ['data-handle', book.handle],
      ['data-title', book.title],
      ['data-author', book.author],
      ['data-cover', book.cover],
      ['data-amazon', book.amazon],
      ['data-bookshop', book.bookshop],
      ['data-shelf', book.shelf],
      ['data-private-shelf', 'false'],
      ['data-spice', book.spice || ''],
      ['data-tropes', tropes],
      ['data-tropes-display', tropes],
      ['data-trope-urls', ''],
      ['data-why', book.why || ''],
      ['data-newsletter', book.newsletter || ''],
      ['data-mini', book.mini || ''],
      ['data-series', book.series || ''],
      ['data-series-name', book.seriesName || ''],
      ['data-series-number', book.seriesNumber || ''],
      ['data-tension', book.tension || ''],
      ['data-damage', book.damage || ''],
      ['data-yearning', book.yearning || ''],
      ['data-boyfriend', book.boyfriend || ''],
      ['data-boyfriend-name', book.boyfriendName || ''],
      ['data-reread', book.reread || ''],
      ['data-standalone', book.standalone || 'false'],
      ['data-ku', book.ku || ''],
      ['data-darkness', book.darkness || '']
    ].map(function (pair) {
      return pair[1] === '' && pair[0] === 'data-book-preview' ? pair[0] : pair[0] + '="' + esc(pair[1]) + '"';
    }).join(' ');
  }

  function card(book, label) {
    var spice = Number(book.spice || 0);
    var spiceHtml = spice > 0 ? '<div class="sss-lib__floatSpice">' + '🌶'.repeat(Math.min(spice, 5)) + '</div>' : '';
    var cover = book.cover ? '<img class="sss-lib__cover" src="' + esc(book.cover) + '" alt="' + esc(book.title) + '" loading="lazy">' : '';
    var tropes = (book.tropes || []).slice(0, 3).map(function (trope) {
      return '<span>' + esc(trope) + '</span>';
    }).join('');

    return '<article class="bbb-livequiz__book">' +
      '<p class="bbb-livequiz__bookLabel">' + esc(label) + '</p>' +
      '<button type="button" class="sss-lib__book sss-lib__book--mini" ' + dataAttrs(book) + '>' +
        '<div class="sss-lib__coverWrap">' + spiceHtml + cover + '</div>' +
        '<div class="sss-lib__under"><div class="sss-lib__name">' + esc(book.title) + '</div><div class="sss-lib__author">' + esc(book.author) + '</div></div>' +
      '</button>' +
      '<div class="bbb-livequiz__bookTropes">' + tropes + '</div>' +
      '<p class="bbb-livequiz__bookWhy">' + esc(book.mini || book.why || 'this one fits the mood you just picked.') + '</p>' +
    '</article>';
  }

  function renderResult(root, scores) {
    var type = root.dataset.quizType || 'mood';
    var data = root.querySelector('[data-quiz-books]');
    var books = [];
    try { books = JSON.parse(data ? data.textContent : '[]') || []; } catch (error) { books = []; }

    var profile = profileFor(type, scores);
    var picks = books.map(function (book, index) {
      return { book: book, score: scoreBook(book, profile, scores, index) };
    }).filter(function (item) {
      return item.book && item.book.title && item.book.cover;
    }).sort(function (a, b) {
      return b.score - a.score;
    }).slice(0, 3).map(function (item) {
      return item.book;
    });

    var result = root.querySelector('[data-quiz-result]');
    result.hidden = false;
    result.innerHTML =
      '<p class="bbb-livequiz__resultKicker">' + esc(profile.kicker) + '</p>' +
      '<h2>' + esc(profile.title) + '</h2>' +
      '<p class="bbb-livequiz__resultCopy">' + esc(profile.copy) + '</p>' +
      '<div class="bbb-livequiz__tags">' + profile.tags.map(function (tag) { return '<span>' + esc(tag) + '</span>'; }).join('') + '</div>' +
      '<div class="bbb-livequiz__books">' + picks.map(function (book, index) {
        return card(book, index === 0 ? 'best match' : (index === 1 ? 'second mood' : 'wildcard'));
      }).join('') + '</div>' +
      '<div class="bbb-livequiz__links">' +
        '<a href="/what-to-read-next/">open the rec engine</a>' +
        '<a href="/library/">browse the library</a>' +
      '</div>';
    root.classList.add('is-showing-result');
    result.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function init(root) {
    var begin = root.querySelector('[data-quiz-begin]');
    var again = root.querySelector('[data-quiz-again]');
    var track = root.querySelector('[data-quiz-track]');
    var slides = Array.prototype.slice.call(root.querySelectorAll('[data-quiz-slide]'));
    var scores = {};
    var index = 0;

    function showSlide(nextIndex) {
      index = nextIndex;
      slides.forEach(function (slide, slideIndex) {
        slide.classList.toggle('is-active', slideIndex === index);
      });
    }

    function reset() {
      scores = {};
      root.classList.remove('is-started', 'is-showing-result');
      root.querySelector('[data-quiz-result]').hidden = true;
      root.querySelector('[data-quiz-result]').innerHTML = '';
      track.hidden = true;
      showSlide(0);
      root.querySelector('.bbb-livequiz__hero').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    begin && begin.addEventListener('click', function () {
      root.classList.add('is-started');
      track.hidden = false;
      showSlide(0);
      track.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    again && again.addEventListener('click', reset);

    root.addEventListener('click', function (event) {
      var answer = event.target.closest('[data-quiz-answer]');
      if (!answer || !root.contains(answer)) return;

      var answerScores = parseScores(answer.dataset.score);
      Object.keys(answerScores).forEach(function (key) {
        scores[key] = (scores[key] || 0) + answerScores[key];
      });

      if (index < slides.length - 1) {
        showSlide(index + 1);
      } else {
        track.hidden = true;
        renderResult(root, scores);
      }
    });
  }

  document.querySelectorAll('[data-reader-quiz]').forEach(init);
})();
