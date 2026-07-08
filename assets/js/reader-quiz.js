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

  function localAssetUrl(value) {
    var url = String(value || '').trim();
    if (!url || !window.location || window.location.protocol !== 'http:') return url;

    try {
      var parsed = new URL(url, window.location.href);
      if (parsed.protocol === 'https:' && parsed.host === window.location.host) {
        parsed.protocol = 'http:';
        return parsed.toString();
      }
    } catch (error) {}

    return url;
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

  function bookCoverAlt(book) {
    var title = String((book && book.title) || '').trim();
    var author = String((book && book.author) || '').trim();
    var shelf = String((book && book.shelf) || '').trim();

    if (!title) return 'book cover';
    if (author) title += ' by ' + author;
    if (shelf) title += ' – ' + shelf;
    return title + ' book cover';
  }

  function topKey(scores, keys) {
    return keys.slice().sort(function (a, b) {
      return (scores[b] || 0) - (scores[a] || 0);
    })[0];
  }

  function profileFor(type, scores) {
    if (type === 'reader-type') {
      var readerType = topKey(scores, ['chaos', 'dark', 'fantasy', 'sports', 'slow', 'tension', 'fake', 'sweet', 'comfort']);
      var readerTypes = {
        chaos: {
          title: 'you are the chaos reader',
          kicker: 'reader type unlocked',
          copy: 'you want intensity, momentum, and romance that makes one tiny normal choice then immediately swerves off-road.',
          tags: ['dark romance', 'high stakes', 'touch her and die'],
          slug: 'chaos-reader',
          match: {
            boost: ['dark romance', 'mafia', 'stalker', 'touch her', 'villain', 'morally gray', 'high stakes'],
            minDarknessBoost: 3
          }
        },
        dark: {
          title: 'you are the dark romance girlie',
          kicker: 'reader type unlocked',
          copy: 'morally gray devotion, dangerous promises, and a plot with consequences. you are here for obsession with a paper trail.',
          tags: ['dark romance', 'morally gray', 'possessive'],
          slug: 'dark-romance-girlie',
          match: {
            boost: ['dark romance', 'mafia', 'stalker', 'touch her', 'villain', 'morally gray', 'possessive'],
            minDarknessBoost: 3
          }
        },
        fantasy: {
          title: 'you are the fantasy girlie',
          kicker: 'reader type unlocked',
          copy: 'you prefer your romance with magic, curses, creatures, impossible stakes, and a man who may or may not be ancient.',
          tags: ['romantasy', 'paranormal romance', 'fated mates'],
          slug: 'fantasy-girlie',
          match: {
            boost: ['romantasy', 'fantasy', 'paranormal', 'fated mates', 'dragon', 'fae']
          }
        },
        sports: {
          title: 'you are the jersey chaser',
          kicker: 'reader type unlocked',
          copy: 'you like competition, locker-room confidence, public wins, private softness, and men who have absolutely practiced the apology.',
          tags: ['sports romance', 'he falls first', 'protective'],
          slug: 'jersey-chaser',
          match: {
            boost: ['sports romance', 'hockey', 'football', 'baseball romance', 'athlete', 'he falls first'],
            avoid: ['stalker', 'mafia', 'captor', 'captive'],
            maxDarkness: 3
          }
        },
        slow: {
          title: 'you are the slow burn girlie',
          kicker: 'reader type unlocked',
          copy: 'you want restraint, yearning, almost-moments, and the kind of payoff that requires patience and a dramatic little stare.',
          tags: ['slow burn', 'second chance', 'yearning'],
          slug: 'slow-burn-girlie',
          match: {
            boost: ['slow burn', 'yearning', 'second chance', 'friends to lovers'],
            avoid: ['bully romance', 'stalker', 'mafia'],
            maxSpice: 4,
            maxDarkness: 3,
            spiceMin: 2,
            spiceMax: 3
          }
        },
        tension: {
          title: 'you are the tension addict',
          kicker: 'reader type unlocked',
          copy: 'you read for charged silence, sharp banter, inconvenient proximity, and the exact second the denial stops working.',
          tags: ['enemies to lovers', 'forced proximity', 'banter'],
          slug: 'tension-addict',
          match: {
            boost: ['enemies to lovers', 'forced proximity', 'banter', 'rivals'],
            minTensionBoost: 3
          }
        },
        fake: {
          title: 'you are the fake dating fanatic',
          kicker: 'reader type unlocked',
          copy: 'you believe rules are foreplay, pretend affection is evidence, and the fake kiss should absolutely ruin everyone.',
          tags: ['fake dating', 'jealousy', 'forced proximity'],
          slug: 'fake-dating-fanatic',
          match: {
            boost: ['fake dating', 'marriage of convenience', 'forced proximity', 'jealousy'],
            avoid: ['stalker', 'captor', 'captive'],
            maxDarkness: 3
          }
        },
        sweet: {
          title: 'you are the sweet romance devotee',
          kicker: 'reader type unlocked',
          copy: 'you want tenderness, safety, mutual care, and proof that soft romance can still hit hard.',
          tags: ['friends to lovers', 'comfort read', 'he falls first'],
          slug: 'sweet-romance-devotee',
          match: {
            boost: ['friends to lovers', 'he falls first', 'comfort', 'contemporary', 'small town', 'single dad', 'grumpy sunshine'],
            avoid: ['bully', 'dark romance', 'mafia', 'stalker', 'touch her', 'villain', 'captor', 'captive', 'morally gray', 'obsession', 'possessive', 'forbidden love'],
            penalty: ['enemies to lovers', 'rivals'],
            maxSpice: 3,
            maxDarkness: 1,
            maxDamage: 3,
            spiceMin: 1,
            spiceMax: 2,
            preferLowDarkness: true,
            preferLowDamage: true,
            maxTensionPenalty: 4
          }
        },
        comfort: {
          title: 'you are the sweet romance devotee',
          kicker: 'reader type unlocked',
          copy: 'you want tenderness, safety, mutual care, and proof that soft romance can still hit hard.',
          tags: ['friends to lovers', 'comfort read', 'he falls first'],
          slug: 'sweet-romance-devotee',
          match: {
            boost: ['friends to lovers', 'he falls first', 'comfort', 'contemporary', 'small town', 'single dad', 'grumpy sunshine'],
            avoid: ['bully', 'dark romance', 'mafia', 'stalker', 'touch her', 'villain', 'captor', 'captive', 'morally gray', 'obsession', 'possessive', 'forbidden love'],
            penalty: ['enemies to lovers', 'rivals'],
            maxSpice: 3,
            maxDarkness: 1,
            maxDamage: 3,
            spiceMin: 1,
            spiceMax: 2,
            preferLowDarkness: true,
            preferLowDamage: true,
            maxTensionPenalty: 4
          }
        }
      };
      return readerTypes[readerType] || readerTypes.tension;
    }

    if (type === 'trope') {
      var trope = topKey(scores, ['enemies', 'friends', 'proximity', 'fake', 'second']);
      var tropeMap = {
        enemies: {
          title: 'you are enemies to lovers',
          kicker: 'trope diagnosis',
          copy: 'you want the argument, the eye contact, the rivalry, and the exact second irritation becomes devotion.',
          tags: ['enemies to lovers', 'rivals', 'slow burn'],
          slug: 'enemies-to-lovers'
        },
        friends: {
          title: 'you are friends to lovers',
          kicker: 'trope diagnosis',
          copy: 'you like comfort with consequences: inside jokes, shared history, and the terrifying discovery that home has feelings.',
          tags: ['friends to lovers', 'he falls first', 'found family'],
          slug: 'friends-to-lovers'
        },
        proximity: {
          title: 'you are forced proximity',
          kicker: 'trope diagnosis',
          copy: 'you believe the best denial happens in cabins, hotel rooms, road trips, and situations no one can politely escape.',
          tags: ['forced proximity', 'one bed', 'slow burn'],
          slug: 'forced-proximity'
        },
        fake: {
          title: 'you are fake dating',
          kicker: 'trope diagnosis',
          copy: 'you enjoy a plan with rules, witnesses, fake affection, and absolutely no chance of staying fake.',
          tags: ['fake dating', 'contemporary romance', 'jealousy'],
          slug: 'fake-dating'
        },
        second: {
          title: 'you are second chance romance',
          kicker: 'trope diagnosis',
          copy: 'you want longing with receipts: history, ache, accountability, and a love that comes back changed.',
          tags: ['second chance', 'emotional damage', 'angst'],
          slug: 'second-chance'
        }
      };
      return tropeMap[trope] || tropeMap.enemies;
    }

    if (type === 'boyfriend') {
      var boyfriend = topKey(scores, ['gray', 'golden', 'rivals', 'broody', 'fantasy']);
      var map = {
        gray: {
          title: 'your fictional boyfriend is the morally gray protector',
          kicker: 'dangerous devotion',
          copy: 'he has plans, secrets, and exactly one person he would burn the plot down for.',
          tags: ['dark', 'protective', 'touch her and die']
        },
        golden: {
          title: 'your fictional boyfriend is the golden retriever menace',
          kicker: 'soft chaos',
          copy: 'he falls first, tries hard, and makes emotional availability look unfairly attractive.',
          tags: ['he falls first', 'sports', 'friends to lovers']
        },
        rivals: {
          title: 'your fictional boyfriend is the rival with banter privileges',
          kicker: 'chemistry problem',
          copy: 'he is annoying on purpose, obsessed by accident, and allergic to admitting feelings early.',
          tags: ['enemies to lovers', 'forced proximity', 'slow burn']
        },
        broody: {
          title: 'your fictional boyfriend is the wounded softie',
          kicker: 'quiet ache',
          copy: 'he has emotional damage, excellent restraint, and one devastating moment where the wall finally cracks.',
          tags: ['slow burn', 'second chance', 'emotional damage']
        },
        fantasy: {
          title: 'your fictional boyfriend is the cursed romantic lead',
          kicker: 'mythic attachment',
          copy: 'he probably has a kingdom, a curse, or wings. maybe all three, which feels correct.',
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
        copy: 'low panic, high feelings, and a romance that knows tenderness can still ruin you.',
        tags: ['contemporary romance', 'friends to lovers', 'he falls first']
      },
      chaos: {
        title: 'you need an unhinged little spiral',
        kicker: 'chaos read',
        copy: 'darker stakes, hotter choices, and a book that does not know how to behave.',
        tags: ['dark romance', 'touch her and die', 'mafia romance']
      },
      escape: {
        title: 'you need a full escape hatch',
        kicker: 'otherworldly read',
        copy: 'magic, monsters, curses, immortals, or at minimum a man with impossible energy.',
        tags: ['romantasy', 'paranormal romance', 'fated mates']
      },
      cry: {
        title: 'you need emotional damage with a payoff',
        kicker: 'ache read',
        copy: 'something tender, intense, and dramatic enough to make staring at the ceiling feel productive.',
        tags: ['second chance romance', 'trauma bonding', 'slow burn']
      },
      tension: {
        title: 'you need a slow-burn tension read',
        kicker: 'payoff read',
        copy: 'banter, longing, almost-moments, and the sort of restraint that becomes everyone else’s problem.',
        tags: ['slow burn', 'enemies to lovers', 'forced proximity']
      }
    };
    return moods[mood] || moods.comfort;
  }

  function readJson(root, selector) {
    var node = root.querySelector(selector);
    try { return JSON.parse(node ? node.textContent : '[]') || []; } catch (error) { return []; }
  }

  function accountApi() {
    var direct = typeof BBBReaderAccountApi !== 'undefined' ? BBBReaderAccountApi : window.BBBReaderAccountApi;
    var site = typeof BBBSiteData !== 'undefined' ? BBBSiteData : window.BBBSiteData;
    return direct || (site && site.readerAccount) || {};
  }

  function mfyProfileVersion() {
    var api = accountApi();
    return String((api && api.profileVersion) || 'mfy-2026-06-11-reader-types');
  }

  function mfyAccountKey() {
    var api = accountApi();
    return String((api && api.accountKey) || '').trim();
  }

  function scopedStorageKey(key) {
    var accountKey = mfyAccountKey();
    return accountKey ? key + '::' + accountKey : key;
  }

  function getStoredProfile() {
    try {
      return JSON.parse(window.localStorage.getItem(scopedStorageKey('sssMadeForYouProfile')) || '{}') || {};
    } catch (error) {
      return {};
    }
  }

  function setStoredProfile(profile) {
    try {
      window.localStorage.setItem(scopedStorageKey('sssMadeForYouProfile'), JSON.stringify(profile || {}));
      if (mfyAccountKey()) {
        window.localStorage.removeItem('sssMadeForYouProfile');
      }
    } catch (error) {}
  }

  function isCurrentMfyProfile(profile) {
    return !!(profile && typeof profile === 'object' && String(profile.mfy_profile_version || profile.profile_version || '') === mfyProfileVersion());
  }

  function topScoreKey(scores, keys) {
    return keys.slice().sort(function (a, b) {
      return Number(scores[b] || 0) - Number(scores[a] || 0);
    })[0] || '';
  }

  function readerTypeFromQuiz(type, scores, profile, boyfriendMatch) {
    if (type === 'reader-type') {
      var readerType = topScoreKey(scores, ['chaos', 'dark', 'fantasy', 'sports', 'slow', 'tension', 'fake', 'sweet', 'comfort']);
      var readerTypeMap = {
        chaos: 'chaos_reader',
        dark: 'dark_romance_girlie',
        fantasy: 'fantasy_girlie',
        sports: 'jersey_chaser',
        slow: 'slow_burn_girlie',
        tension: 'tension_addict',
        fake: 'fake_dating_fanatic',
        sweet: 'sweet_romance_devotee',
        comfort: 'sweet_romance_devotee'
      };
      return readerTypeMap[readerType] || 'romance_reader';
    }

    if (type === 'boyfriend') {
      var dark = Number(scores.dark || 0) + Number(scores.gray || 0) + Number(scores.stalker || 0) + Number(scores.mafia || 0) + Number(scores.possessive || 0);
      var chaos = dark + Number(scores.spicy || 0);
      var sweet = Number(scores.golden || 0) + Number(scores.soft || 0) + Number(scores.singleDad || 0) + Number(scores.hefalls || 0);
      var tension = Number(scores.rivals || 0) + Number(scores.enemies || 0) + Number(scores.forcedProx || 0) + Number(scores.fakeDating || 0);
      var slow = Number(scores.broody || 0) + Number(scores.slow || 0) + Number(scores.damage || 0);
      var fantasy = Number(scores.fantasy || 0) + Number(scores.paranormal || 0);
      var sports = Number(scores.sports || 0);

      if (chaos >= 18 && Number(scores.spicy || 0) >= 3) return 'chaos_reader';
      if (dark >= Math.max(sweet, tension, slow, fantasy, sports, 8)) return 'dark_romance_girlie';
      if (fantasy >= Math.max(sweet, tension, slow, sports, 5)) return 'fantasy_girlie';
      if (sports >= Math.max(sweet, tension, slow, 4)) return 'jersey_chaser';
      if (slow >= Math.max(sweet, tension, 6)) return 'slow_burn_girlie';
      if (tension >= Math.max(sweet, 6)) return Number(scores.fakeDating || 0) >= 3 ? 'fake_dating_fanatic' : 'tension_addict';
      if (sweet >= 6) return 'sweet_romance_devotee';

      var matchText = [
        boyfriendMatch && boyfriendMatch.shelf,
        boyfriendMatch && boyfriendMatch.descriptor,
        boyfriendMatch && (boyfriendMatch.tropes || []).join(' '),
        profile && (profile.tags || []).join(' ')
      ].join(' ').toLowerCase();
      if (matchText.indexOf('dark') > -1 || matchText.indexOf('mafia') > -1 || matchText.indexOf('stalker') > -1) return 'dark_romance_girlie';
      if (matchText.indexOf('romantasy') > -1 || matchText.indexOf('fantasy') > -1 || matchText.indexOf('paranormal') > -1) return 'fantasy_girlie';
      if (matchText.indexOf('sports') > -1 || matchText.indexOf('hockey') > -1) return 'jersey_chaser';
      return 'romance_reader';
    }

    if (type === 'trope') {
      var trope = topScoreKey(scores, ['enemies', 'friends', 'proximity', 'fake', 'second']);
      return trope === 'fake' ? 'fake_dating_fanatic' : trope === 'friends' ? 'sweet_romance_devotee' : trope === 'second' ? 'slow_burn_girlie' : 'tension_addict';
    }

    var mood = topScoreKey(scores, ['comfort', 'chaos', 'escape', 'cry', 'tension']);
    return mood === 'chaos' ? 'dark_romance_girlie' : mood === 'escape' ? 'fantasy_girlie' : mood === 'comfort' ? 'sweet_romance_devotee' : mood === 'cry' ? 'slow_burn_girlie' : 'tension_addict';
  }

  function compactBook(book) {
    return {
      handle: String(book && (book.handle || book.book_handle) || ''),
      title: String(book && (book.title || book.book_title) || ''),
      author: String(book && book.author || ''),
      cover: String(book && book.cover || ''),
      shelf: String(book && book.shelf || ''),
      tropes: Array.isArray(book && book.tropes) ? book.tropes.slice(0, 8) : [],
      spice: Number(book && book.spice || 0),
      darkness: Number(book && book.darkness || 0),
      tension: Number(book && book.tension || 0),
      damage: Number(book && book.damage || 0),
      boyfriend: String(book && (book.boyfriend || book.boyfriendName) || ''),
      url: String(book && book.url || '')
    };
  }

  function fictionalManKeyFromBoyfriendResult(match, scores, archetype) {
    var tags = [
      match && match.descriptor,
      match && match.shelf,
      match && (match.tropes || []).join(' '),
      match && (match.traits || []).join(' '),
      archetype && (archetype.tags || []).join(' ')
    ].join(' ').toLowerCase();

    if (tags.indexOf('stalker') > -1) return 'stalker';
    if (tags.indexOf('mafia') > -1) return 'mafia_boss';
    if (tags.indexOf('morally gray') > -1 || tags.indexOf('villain') > -1 || Number(scores.gray || 0) >= 6) return 'morally_gray_villain';
    if (tags.indexOf('sports') > -1 || Number(scores.sports || 0) >= 3) return 'athlete_with_heart';
    if (tags.indexOf('rival') > -1 || tags.indexOf('academic') > -1 || Number(scores.rivals || 0) >= 5) return 'academic_rival';
    if (tags.indexOf('broody') > -1 || tags.indexOf('guarded') > -1 || Number(scores.broody || 0) >= 5) return 'cold_grump';
    if (tags.indexOf('fantasy') > -1 || tags.indexOf('romantasy') > -1 || tags.indexOf('cursed') > -1 || Number(scores.fantasy || 0) >= 5) return 'tortured_prince';
    if (Number(scores.golden || 0) + Number(scores.soft || 0) >= 6) return 'sweetheart';

    return 'obsessive_protector';
  }

  function canonicalFictionalBoyfriend(match, scores, archetype) {
    if (!match) return null;

    return {
      source: 'fictional_boyfriend_quiz',
      matched_at: new Date().toISOString(),
      profile_id: match.id || '',
      name: match.name || '',
      url: match.url || '',
      image: match.image || '',
      image_full: match.imageFull || match.image || '',
      book_id: match.bookId || '',
      book_title: match.bookTitle || '',
      book_url: match.bookUrl || '',
      book_cover: match.bookCover || '',
      author: match.author || '',
      shelf: match.shelf || '',
      descriptor: match.descriptor || '',
      result_type: fictionalManKeyFromBoyfriendResult(match, scores || {}, archetype || {}),
      result_title: match.name ? 'your fictional boyfriend is ' + match.name : '',
      result_kicker: match.shelf || (archetype && archetype.kicker) || '',
      result_copy: boyfriendResultCopy(match, archetype || {}),
      tropes: Array.isArray(match.tropes) ? match.tropes.slice(0, 12) : [],
      traits: Array.isArray(match.traits) ? match.traits.slice(0, 12) : [],
      trait_labels: Array.isArray(match.traitLabels) ? match.traitLabels.slice(0, 12) : [],
      scores: match.scores || {}
    };
  }

  function mergeQuizEvidence(existing, evidence) {
    var now = new Date().toISOString();
    var profile = existing && typeof existing === 'object' ? Object.assign({}, existing) : {};
    var version = mfyProfileVersion();
    var seed = evidence && evidence.dashboard_seed && typeof evidence.dashboard_seed === 'object' ? evidence.dashboard_seed : null;
    profile.mfy_profile_version = version;
    profile.profile_version = version;
    profile.updatedAt = now;
    if (evidence.quiz_type !== 'boyfriend') {
      profile.reader_type_prior = evidence.reader_type_prior || profile.reader_type_prior || profile.theme || 'romance_reader';
      profile.favorite_trope = evidence.favorite_trope || profile.favorite_trope || '';
      profile.spice_profile = evidence.spice_profile || profile.spice_profile || 0;
      profile.spice_dial = evidence.spice_dial || profile.spice_dial || '';
      if (seed) {
        profile.name = profile.name || seed.name || 'reader';
        profile.heat_lane = seed.heat_lane || profile.heat_lane || '';
        profile.group_chat_text = seed.group_chat_text || profile.group_chat_text || '';
        profile.love_interest = seed.love_interest || profile.love_interest || '';
        profile.wall_line = seed.wall_line || profile.wall_line || '';
        profile.dashboard_built = true;
        profile.profile_source = 'reader_type_quiz';
      }
    } else {
      profile.reader_type_prior = profile.reader_type_prior || profile.theme || '';
      profile.favorite_trope = profile.favorite_trope || '';
      profile.spice_profile = profile.spice_profile || 0;
      profile.spice_dial = profile.spice_dial || '';
    }
    profile.quiz_evidence = Object.assign({}, profile.quiz_evidence || {}, {
      latest: evidence,
      by_type: Object.assign({}, (profile.quiz_evidence && profile.quiz_evidence.by_type) || {}, {
        [evidence.quiz_type]: evidence
      })
    });
    if (evidence.quiz_type === 'boyfriend' && evidence.fictional_boyfriend) {
      profile.fictional_boyfriend = evidence.fictional_boyfriend;
      profile.fictional_man = evidence.fictional_boyfriend.result_type || profile.fictional_man || '';
      profile.boyfriend_quiz_completed_at = evidence.completed_at;
    }
    profile.quiz_recommendations = evidence.recommendations || profile.quiz_recommendations || [];
    profile.quiz_answer_history = [evidence].concat(Array.isArray(profile.quiz_answer_history) ? profile.quiz_answer_history : []).slice(0, 8);
    return profile;
  }

  function dashboardSeedForReaderType(readerType) {
    var seeds = {
      chaos_reader: {
        heat_lane: 'unhinged',
        group_chat_text: 'tension_addict',
        love_interest: 'dark_romance_girlie',
        wall_line: 'chaos_reader'
      },
      dark_romance_girlie: {
        heat_lane: 'open',
        group_chat_text: 'dark_romance_girlie',
        love_interest: 'dark_romance_girlie',
        wall_line: 'fake_dating_fanatic'
      },
      fantasy_girlie: {
        heat_lane: 'cracked',
        group_chat_text: 'fake_dating_fanatic',
        love_interest: 'fantasy_girlie',
        wall_line: 'chaos_reader'
      },
      jersey_chaser: {
        heat_lane: 'cracked',
        group_chat_text: 'tension_addict',
        love_interest: 'jersey_chaser',
        wall_line: 'fake_dating_fanatic'
      },
      slow_burn_girlie: {
        heat_lane: 'cracked',
        group_chat_text: 'slow_burn_girlie',
        love_interest: 'sweet_romance_devotee',
        wall_line: 'slow_burn_girlie'
      },
      tension_addict: {
        heat_lane: 'open',
        group_chat_text: 'tension_addict',
        love_interest: 'jersey_chaser',
        wall_line: 'fake_dating_fanatic'
      },
      fake_dating_fanatic: {
        heat_lane: 'cracked',
        group_chat_text: 'fake_dating_fanatic',
        love_interest: 'jersey_chaser',
        wall_line: 'fake_dating_fanatic'
      },
      sweet_romance_devotee: {
        heat_lane: 'closed',
        group_chat_text: 'slow_burn_girlie',
        love_interest: 'sweet_romance_devotee',
        wall_line: 'fake_dating_fanatic'
      },
      romance_reader: {
        heat_lane: 'cracked',
        group_chat_text: 'slow_burn_girlie',
        love_interest: 'sweet_romance_devotee',
        wall_line: 'fake_dating_fanatic'
      }
    };

    return Object.assign({ name: 'reader' }, seeds[readerType] || seeds.romance_reader);
  }

  function syncQuizEvidence(root, type, scores, answers, profile, boyfriendMatch, picks) {
    var readerType = readerTypeFromQuiz(type, scores, profile, boyfriendMatch);
    var fictionalBoyfriend = type === 'boyfriend' ? canonicalFictionalBoyfriend(boyfriendMatch, scores, profile) : null;
    var tags = profile && Array.isArray(profile.tags) ? profile.tags : [];
    var evidence = {
      quiz_type: type,
      source: 'reader_quiz',
      completed_at: new Date().toISOString(),
      reader_type_prior: readerType,
      favorite_trope: String(tags[0] || ''),
      spice_profile: Math.max(0, Math.min(5, Number(scores.spicy || 0) >= 3 ? 4 : (Number(scores.dark || 0) >= 5 ? 4 : 0))),
      spice_dial: Number(scores.spicy || 0) >= 3 || Number(scores.dark || 0) >= 5 ? 'high_spice' : '',
      result_title: boyfriendMatch ? 'your fictional boyfriend is ' + boyfriendMatch.name : String(profile && profile.title || ''),
      result_kicker: boyfriendMatch && boyfriendMatch.shelf ? boyfriendMatch.shelf : String(profile && profile.kicker || ''),
      scores: Object.assign({}, scores),
      answers: answers.slice(),
      recommendations: (picks || []).slice(0, 5).map(compactBook),
      dashboard_seed: type === 'reader-type' ? dashboardSeedForReaderType(readerType) : null,
      fictional_boyfriend: fictionalBoyfriend,
      boyfriend_match: boyfriendMatch ? {
        id: boyfriendMatch.id || '',
        name: boyfriendMatch.name || '',
        url: boyfriendMatch.url || '',
        bookTitle: boyfriendMatch.bookTitle || '',
        author: boyfriendMatch.author || '',
        descriptor: boyfriendMatch.descriptor || '',
        tropes: boyfriendMatch.tropes || [],
        traits: boyfriendMatch.traits || [],
        scores: boyfriendMatch.scores || {}
      } : null
    };

    var stored = getStoredProfile();
    var existing = isCurrentMfyProfile(stored) ? stored : {};
    var merged = mergeQuizEvidence(existing, evidence);
    setStoredProfile(merged);

    var api = accountApi();
    if (!api || !api.profileEndpoint || !api.nonce) return;

    window.fetch(api.profileEndpoint, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-WP-Nonce': api.nonce }
    }).then(function (response) {
      if (!response.ok) return {};
      return response.json();
    }).then(function (payload) {
      var remote = payload && payload.profile && typeof payload.profile === 'object' && isCurrentMfyProfile(payload.profile) ? payload.profile : {};
      var next = mergeQuizEvidence(Object.keys(remote).length ? remote : merged, evidence);
      setStoredProfile(next);
      return window.fetch(api.profileEndpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': api.nonce
        },
        body: JSON.stringify({ profile: next })
      });
    }).catch(function (error) {
      console.log('Reader quiz evidence sync failed', error);
    });
  }

  function profileText(profile) {
    return [
      profile.name,
      profile.bookTitle,
      profile.author,
      profile.shelf,
      profile.descriptor,
      profile.hook,
      profile.loveLanguage,
      profile.wouldTextBack,
      (profile.tropes || []).join(' '),
      (profile.traits || []).join(' '),
      (profile.traitLabels || []).join(' ')
    ].join(' ');
  }

  function boyfriendAffinity(profile) {
    var content = profileText(profile);
    var traits = (profile && profile.traits ? profile.traits : []).map(text);
    var tropes = (profile && profile.tropes ? profile.tropes : []).map(text);
    var stat = (profile && profile.scores) || {};
    var spice = Number((profile && profile.spice) || 0);
    var affinity = {};

    function bump(key, amount) {
      affinity[key] = Math.max(affinity[key] || 0, amount);
    }

    function has(values) {
      return includesAny(content, values);
    }

    function hasTrope(values) {
      return tropes.some(function (trope) { return includesAny(trope, values); }) || has(values);
    }

    var possessive = Number(stat.possessive || 0);
    var protective = Number(stat.protective || 0);
    var available = Number(stat['emotionally-available'] || 0);
    var apologizes = Number(stat['will-apologize'] || 0);
    var goodForYou = Number(stat['actually-good-for-you'] || 0);

    if (has(['sports romance', 'athlete', 'baseball', 'hockey', 'football']) || traits.indexOf('athletic') !== -1) bump('sports', 10);
    if (hasTrope(['mafia'])) bump('mafia', 10);
    if (hasTrope(['stalker'])) bump('stalker', 10);
    if (hasTrope(['dark romance']) || has(['dark romance'])) bump('dark', 8);
    if (has(['morally gray', 'morally-gray', 'dangerous', 'villain']) || traits.indexOf('morally-gray') !== -1 || traits.indexOf('dangerous') !== -1) bump('gray', 8);
    if (hasTrope(['billionaire'])) bump('billionaire', 9);
    if (hasTrope(['workplace', 'boss x employee'])) bump('workplace', 9);
    if (hasTrope(['small town'])) bump('smallTown', 9);
    if (hasTrope(['single dad', 'nanny'])) bump('singleDad', 9);
    if (hasTrope(['marriage of convenience', 'arranged marriage'])) bump('marriage', 9);
    if (hasTrope(['fake dating'])) bump('fakeDating', 9);
    if (hasTrope(['forced proximity', 'one bed'])) bump('forcedProx', 9);
    if (hasTrope(['enemies to lovers', 'rivals'])) {
      bump('enemies', 10);
      bump('rivals', 8);
    }
    if (has(['banter', 'teasing']) || traits.indexOf('banter-heavy') !== -1 || traits.indexOf('competitive') !== -1) bump('rivals', 7);
    if (hasTrope(['slow burn']) || traits.indexOf('emotionally-guarded') !== -1 || has(['guarded', 'reserved', 'tortured'])) bump('slow', 9);
    if (has(['broody']) || traits.indexOf('tortured') !== -1 || available <= 2) bump('broody', 7);
    if (has(['damage', 'wounded']) || apologizes <= 3) bump('damage', 6);
    if (has(['romantasy', 'fantasy', 'cursed', 'fated mates', 'dystopian', 'paranormal'])) {
      bump('fantasy', 9);
      if (has(['paranormal'])) bump('paranormal', 8);
    }
    if (hasTrope(['found family']) || traits.indexOf('devoted') !== -1) bump('foundFamily', 7);

    if (traits.indexOf('soft-for-her') !== -1 || traits.indexOf('gentle') !== -1 || traits.indexOf('emotionally-intelligent') !== -1 || has(['soft for her', 'gentle'])) bump('soft', 8);
    if (goodForYou >= 7 || apologizes >= 6 || traits.indexOf('steady') !== -1 || traits.indexOf('grounded') !== -1 || has(['athlete with heart', 'golden retriever'])) bump('golden', 8);
    if (has(['he falls first', 'falls first']) || traits.indexOf('devoted') !== -1 || has(['acts of service', 'words of affirmation', 'quality time'])) bump('hefalls', 7);
    if (protective >= 7 || traits.indexOf('protective') !== -1 || hasTrope(['touch her and die', 'who did this to you', 'bodyguard'])) bump('protective', 8);
    if (possessive >= 8 || traits.indexOf('possessive') !== -1 || traits.indexOf('obsessive') !== -1 || has(['possessive', 'obsessive'])) bump('possessive', 8);
    if (spice >= 4) bump('spicy', 9);
    else if (spice >= 3) bump('spicy', 6);

    if ((affinity.rivals || 0) || (affinity.enemies || 0) || (affinity.fakeDating || 0) || (affinity.forcedProx || 0) || (affinity.possessive || 0)) bump('tension', 6);
    if ((affinity.dark || 0) && !(affinity.soft || 0)) bump('tension', 7);

    return affinity;
  }

  function scoreBoyfriendProfile(profile, scores, archetype, index) {
    var value = 0;
    var content = profileText(profile);
    var affinity = boyfriendAffinity(profile);

    function want(key) {
      return Number(scores[key] || 0);
    }

    (archetype.tags || []).forEach(function (tag) {
      if (includesAny(content, [tag])) value += 8;
    });

    Object.keys(scores).forEach(function (key) {
      value += want(key) * Number(affinity[key] || 0);
    });

    if (want('sports') >= 5 && Number(affinity.sports || 0) < 5) value -= 20;
    if (want('mafia') >= 3 && Number(affinity.mafia || 0) < 5) value -= 18;
    if (want('stalker') >= 3 && Number(affinity.stalker || 0) < 5) value -= 18;
    if (want('dark') >= 5 && Number(affinity.dark || affinity.mafia || affinity.stalker || 0) < 5) value -= 18;
    if (want('golden') + want('soft') >= 6 && Number(affinity.dark || affinity.stalker || 0) >= 8) value -= 18;
    if (want('spicy') > 0 && Number(affinity.spicy || 0) < 5) value -= 8;
    if (want('singleDad') >= 3 && Number(affinity.singleDad || 0) < 5) value -= 16;
    if (want('marriage') >= 3 && Number(affinity.marriage || 0) < 5) value -= 16;

    return value + Math.max(0, 2 - index * 0.08);
  }

  function selectBoyfriendProfile(root, scores, archetype) {
    var profiles = readJson(root, '[data-quiz-boyfriends]').filter(function (profile) {
      return profile && profile.name && profile.image;
    });
    if (!profiles.length) return null;

    return profiles.map(function (profile, index) {
      return { profile: profile, score: scoreBoyfriendProfile(profile, scores, archetype, index) };
    }).sort(function (a, b) {
      return b.score - a.score;
    })[0].profile;
  }

  function rankedBoyfriendProfiles(root, scores, archetype, excludeId, limit) {
    var profiles = readJson(root, '[data-quiz-boyfriends]').filter(function (profile) {
      return profile && profile.name && profile.image && String(profile.id || '') !== String(excludeId || '');
    });

    return profiles.map(function (profile, index) {
      return { profile: profile, score: scoreBoyfriendProfile(profile, scores, archetype, index) };
    }).sort(function (a, b) {
      return b.score - a.score;
    }).slice(0, limit || 3).map(function (item) {
      return item.profile;
    });
  }

  function boyfriendResultCopy(match, archetype) {
    if (!match) return archetype.copy || '';

    function sentence(value) {
      var clean = String(value || '').trim().replace(/[.!?]+$/g, '');
      return clean ? clean + '.' : '';
    }

    var pieces = [];
    if (match.hook) pieces.push(match.hook);
    if (match.wouldTextBack) pieces.push(sentence('text-back probability: ' + match.wouldTextBack));
    if (match.loveLanguage) pieces.push(sentence('love language: ' + match.loveLanguage));
    if (match.readNextNote) pieces.push(match.readNextNote);
    if (!pieces.length) pieces.push(archetype.copy || 'he matches the chaos, comfort, and standards you just selected.');

    return pieces.join(' ');
  }

  function statPill(label, value) {
    if (value === undefined || value === null || value === '') return '';
    var className = 'bbb-livequiz__bfStat' + (String(label || '').toLowerCase() === 'red flag' ? ' bbb-livequiz__bfStat--redFlag' : '');
    return '<span class="' + className + '"><em>' + esc(label) + '</em><strong>' + esc(value) + '</strong></span>';
  }

  function boyfriendTraitStats(match, archetype) {
    if (!match) return [];

    var scores = match.scores || {};
    var type = match.descriptor || archetype.kicker || '';
    var devotion = match.loveLanguage || '';
    var redFlag = '';
    var textBack = match.wouldTextBack || '';

    if (!devotion) {
      if (Number(scores.protective || 0) >= 8) devotion = 'protective first';
      else if (Number(scores.possessive || 0) >= 8) devotion = 'intense devotion';
      else if (Number(scores['emotionally-available'] || 0) >= 6) devotion = 'emotionally present';
      else devotion = includesAny(profileText(match), ['golden', 'warm', 'charming', 'he falls first']) ? 'falls first energy' : 'quietly obsessed';
    }

    if (Number(scores.possessive || 0) >= 8 || includesAny(profileText(match), ['obsessive', 'possessive', 'stalker'])) redFlag = 'possessive streak';
    else if (Number(scores['will-apologize'] || 0) <= 3 && Object.keys(scores).length) redFlag = 'apology problem';
    else if (includesAny(profileText(match), ['broody', 'guarded', 'reserved'])) redFlag = 'emotionally guarded';
    else redFlag = 'too charming';

    if (!textBack) {
      if (Number(scores['emotionally-available'] || 0) >= 7 || Number(scores['actually-good-for-you'] || 0) >= 7) textBack = 'yes, quickly';
      else if (Number(scores.possessive || 0) >= 8 || Number(scores.protective || 0) >= 8) textBack = 'already watching';
      else textBack = 'after brooding';
    }

    return [
      ['type', type],
      ['devotion', devotion],
      ['red flag', redFlag],
      ['texts back', textBack]
    ];
  }

  function boyfriendMeters(match) {
    var scores = match && match.scores ? match.scores : {};
    var items = [
      ['protective', scores.protective],
      ['possessive', scores.possessive],
      ['apologizes', scores['will-apologize']],
      ['good for you', scores['actually-good-for-you']]
    ];

    return items.filter(function (item) {
      return item[1] !== undefined && item[1] !== null && item[1] !== '';
    }).map(function (item) {
      var value = Math.max(0, Math.min(10, Number(item[1] || 0)));
      return '<span class="bbb-livequiz__bfMeter"><em>' + esc(item[0]) + '</em><i><b style="width:' + esc(String(value * 10)) + '%"></b></i><strong>' + esc(String(value)) + '/10</strong></span>';
    }).join('');
  }

  function pinterestIcon() {
    return '<svg aria-hidden="true" viewBox="0 0 20 20" focusable="false"><path d="M10 2.01a8.1 8.1 0 0 1 5.666 2.353 8.09 8.09 0 0 1 1.277 9.68A7.95 7.95 0 0 1 10 18.04a8.2 8.2 0 0 1-2.276-.307c.403-.653.672-1.24.816-1.729l.567-2.2c.134.27.393.5.768.702.384.192.768.297 1.19.297q1.254 0 2.248-.72a4.7 4.7 0 0 0 1.537-1.969c.37-.89.554-1.848.537-2.813 0-1.249-.48-2.315-1.43-3.227a5.06 5.06 0 0 0-3.65-1.374c-.893 0-1.729.154-2.478.461a5.02 5.02 0 0 0-3.236 4.552c0 .72.134 1.355.413 1.902.269.538.672.922 1.22 1.152.096.039.182.039.25 0 .066-.028.114-.096.143-.192l.173-.653c.048-.144.02-.288-.105-.432a2.26 2.26 0 0 1-.548-1.565 3.803 3.803 0 0 1 3.976-3.861c1.047 0 1.863.288 2.44.855.585.576.883 1.315.883 2.228a6.8 6.8 0 0 1-.317 2.122 3.8 3.8 0 0 1-.893 1.556c-.384.384-.836.576-1.345.576-.413 0-.749-.144-1.018-.451-.259-.307-.345-.672-.25-1.085q.22-.77.452-1.537l.173-.701c.057-.25.086-.451.086-.624 0-.346-.096-.634-.269-.855-.192-.22-.451-.336-.797-.336-.432 0-.797.192-1.085.595-.288.394-.442.893-.442 1.499.005.374.063.746.173 1.104l.058.144c-.576 2.478-.913 3.938-1.037 4.36-.116.528-.154 1.153-.125 1.863A8.07 8.07 0 0 1 2 10.03c0-2.208.778-4.11 2.343-5.666A7.72 7.72 0 0 1 10 2.001z" /></svg>';
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

  function readerTypeBookBlocked(book, profile) {
    if (!book || !profile || !profile.match) return false;

    var rules = profile.match || {};
    var content = bookText(book);
    var spice = Number(book.spice || 0);
    var darkness = Number(book.darkness || 0);
    var damage = Number(book.damage || 0);

    return (typeof rules.maxSpice === 'number' && spice > rules.maxSpice)
      || (typeof rules.maxDarkness === 'number' && darkness > rules.maxDarkness)
      || (typeof rules.maxDamage === 'number' && damage > rules.maxDamage)
      || (Array.isArray(rules.avoid) && includesAny(content, rules.avoid));
  }

  function scoreReaderTypeBook(book, profile, scores, index) {
    if (readerTypeBookBlocked(book, profile)) return -100000;

    var value = scoreBook(book, profile, scores, index);
    var rules = (profile && profile.match) || {};
    var content = bookText(book);
    var spice = Number(book.spice || 0);
    var darkness = Number(book.darkness || 0);
    var damage = Number(book.damage || 0);
    var tension = Number(book.tension || 0);

    if (Array.isArray(rules.boost) && includesAny(content, rules.boost)) value += 40;
    if (Array.isArray(rules.penalty) && includesAny(content, rules.penalty)) value -= 24;
    if (typeof rules.spiceMin === 'number' && typeof rules.spiceMax === 'number' && spice >= rules.spiceMin && spice <= rules.spiceMax) value += 16;
    if (rules.preferLowDarkness && darkness === 0) value += 18;
    if (rules.preferLowDamage && damage <= 2) value += 10;
    if (typeof rules.maxTensionPenalty === 'number' && tension >= rules.maxTensionPenalty) value -= 10;
    if (typeof rules.minTensionBoost === 'number' && tension >= rules.minTensionBoost) value += 28;
    if (typeof rules.minDarknessBoost === 'number' && darkness >= rules.minDarknessBoost) value += 34;

    return value;
  }

  function cleanText(value) {
    return String(value || '').replace(/&#(\d+);/g, function (match, code) {
      return String.fromCharCode(Number(code));
    }).replace(/&#x([0-9a-f]+);/gi, function (match, code) {
      return String.fromCharCode(parseInt(code, 16));
    }).replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>');
  }

  function esc(value) {
    return cleanText(value).replace(/[&<>"']/g, function (char) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char];
    });
  }

  function loadCanvasImage(src) {
    return new Promise(function (resolve) {
      if (!src) {
        resolve(null);
        return;
      }

      var image = new Image();
      image.crossOrigin = 'anonymous';
      image.onload = function () { resolve(image); };
      image.onerror = function () { resolve(null); };
      image.src = localAssetUrl(src);
    });
  }

  function roundRect(ctx, x, y, width, height, radius) {
    var r = Math.min(radius, width / 2, height / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + width, y, x + width, y + height, r);
    ctx.arcTo(x + width, y + height, x, y + height, r);
    ctx.arcTo(x, y + height, x, y, r);
    ctx.arcTo(x, y, x + width, y, r);
    ctx.closePath();
  }

  function drawContainedCanvasImage(ctx, image, x, y, width, height) {
    if (!image) return;

    var imageRatio = image.width / image.height;
    var boxRatio = width / height;
    var drawWidth = width;
    var drawHeight = height;
    var drawX = x;
    var drawY = y;

    if (imageRatio > boxRatio) {
      drawHeight = width / imageRatio;
      drawY = y + (height - drawHeight) / 2;
    } else {
      drawWidth = height * imageRatio;
      drawX = x + (width - drawWidth) / 2;
    }

    ctx.drawImage(image, drawX, drawY, drawWidth, drawHeight);
  }

  function wrapCanvasText(ctx, value, x, y, maxWidth, lineHeight, maxLines) {
    var words = cleanText(value).split(/\s+/).filter(Boolean);
    var lines = [];
    var line = '';

    words.forEach(function (word) {
      var test = line ? line + ' ' + word : word;
      if (ctx.measureText(test).width <= maxWidth || !line) {
        line = test;
        return;
      }
      lines.push(line);
      line = word;
    });

    if (line) lines.push(line);
    if (maxLines && lines.length > maxLines) {
      lines = lines.slice(0, maxLines);
      while (lines[lines.length - 1] && ctx.measureText(lines[lines.length - 1] + '...').width > maxWidth) {
        lines[lines.length - 1] = lines[lines.length - 1].split(' ').slice(0, -1).join(' ');
      }
      lines[lines.length - 1] = lines[lines.length - 1] + '...';
    }

    lines.forEach(function (textLine, index) {
      ctx.fillText(textLine, x, y + index * lineHeight);
    });

    return y + lines.length * lineHeight;
  }

  function canvasToBlob(canvas) {
    return new Promise(function (resolve, reject) {
      try {
        canvas.toBlob(function (blob) {
          if (blob) resolve(blob);
          else reject(new Error('image export failed'));
        }, 'image/png', 0.96);
      } catch (error) {
        reject(error);
      }
    });
  }

  function drawSharePill(ctx, label, x, y) {
    ctx.save();
    ctx.font = 'italic 30px Georgia, serif';
    var width = Math.min(360, Math.max(190, ctx.measureText(label).width + 64));
    roundRect(ctx, x, y, width, 58, 29);
    ctx.fillStyle = 'rgba(255,255,255,.07)';
    ctx.fill();
    ctx.strokeStyle = 'rgba(255,138,199,.44)';
    ctx.lineWidth = 2;
    ctx.stroke();
    ctx.fillStyle = '#f8edf3';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(label, x + width / 2, y + 31);
    ctx.restore();
    return width;
  }

  function drawShareStat(ctx, label, value, x, y, width, height) {
    var isRedFlag = cleanText(label).toLowerCase() === 'red flag';
    ctx.save();
    roundRect(ctx, x, y, width, height, 22);
    ctx.fillStyle = isRedFlag ? 'rgba(103,14,24,.26)' : 'rgba(255,255,255,.055)';
    ctx.fill();
    ctx.strokeStyle = isRedFlag ? 'rgba(148,28,39,.88)' : 'rgba(255,138,199,.38)';
    ctx.lineWidth = 2;
    ctx.stroke();
    ctx.textAlign = 'left';
    ctx.fillStyle = isRedFlag ? '#b32635' : '#ff8ac7';
    ctx.font = '800 22px Georgia, serif';
    ctx.fillText(label, x + 26, y + 38);
    ctx.fillStyle = isRedFlag ? '#d54755' : '#f8edf3';
    ctx.font = '31px Georgia, serif';
    wrapCanvasText(ctx, value, x + 26, y + 82, width - 52, 36, 2);
    ctx.restore();
  }

  async function buildShareImage(root, includeCover) {
    var canvas = document.createElement('canvas');
    var width = 1080;
    var height = 1350;
    canvas.width = width;
    canvas.height = height;
    var ctx = canvas.getContext('2d');
    var title = cleanText(root.dataset.quizShareTitle || 'my reader quiz result');
    var kicker = cleanText(root.dataset.quizShareKicker || 'reader quiz result').toUpperCase();
    var copy = cleanText(root.dataset.quizShareCopy || '');
    var tags = (root.dataset.quizShareTags || '').split('|').map(cleanText).filter(Boolean).slice(0, 3);
    var bookTitle = cleanText(root.dataset.quizShareBookTitle || '');
    var bookAuthor = cleanText(root.dataset.quizShareBookAuthor || '');
    var isBoyfriendShare = root.dataset.quizShareType === 'boyfriend';
    var profileImage = includeCover === false ? null : await loadCanvasImage(root.dataset.quizShareProfileImage || '');
    var cover = includeCover === false ? null : await loadCanvasImage(root.dataset.quizShareBookCover || '');

    ctx.fillStyle = '#080808';
    ctx.fillRect(0, 0, width, height);

    var gradient = ctx.createRadialGradient(250, 170, 30, 250, 170, 520);
    gradient.addColorStop(0, 'rgba(255,138,199,.34)');
    gradient.addColorStop(1, 'rgba(255,138,199,0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, width, height);

    var gradientTwo = ctx.createRadialGradient(880, 1120, 20, 880, 1120, 440);
    gradientTwo.addColorStop(0, 'rgba(255,255,255,.13)');
    gradientTwo.addColorStop(1, 'rgba(255,255,255,0)');
    ctx.fillStyle = gradientTwo;
    ctx.fillRect(0, 0, width, height);

    roundRect(ctx, 74, 74, width - 148, height - 148, 38);
    ctx.fillStyle = 'rgba(18,18,18,.92)';
    ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,.18)';
    ctx.lineWidth = 2;
    ctx.stroke();

    if (isBoyfriendShare) {
      ctx.textAlign = 'center';
      ctx.fillStyle = '#ff8ac7';
      ctx.font = '800 23px Georgia, serif';
      ctx.letterSpacing = '4px';
      ctx.fillText(kicker, width / 2, 156);

      ctx.letterSpacing = '0px';
      ctx.fillStyle = '#fff';
      ctx.font = '66px Georgia, serif';
      var boyfriendY = wrapCanvasText(ctx, title, width / 2, 228, 780, 74, 2);

      ctx.fillStyle = 'rgba(255,255,255,.78)';
      ctx.font = '28px Georgia, serif';
      boyfriendY = wrapCanvasText(ctx, copy, width / 2, boyfriendY + 34, 760, 38, 4);

      var imageX = 122;
      var imageY = Math.max(430, boyfriendY + 42);
      var imageW = profileImage ? 390 : 0;
      var imageH = profileImage ? 560 : 0;
      var copyX = profileImage ? 552 : 170;
      var copyW = profileImage ? 408 : 740;

      if (profileImage) {
        roundRect(ctx, imageX, imageY, imageW, imageH, 26);
        ctx.fillStyle = '#111';
        ctx.fill();
        ctx.save();
        ctx.clip();
        drawContainedCanvasImage(ctx, profileImage, imageX, imageY, imageW, imageH);
        ctx.restore();
        ctx.strokeStyle = 'rgba(255,138,199,.48)';
        ctx.lineWidth = 3;
        ctx.stroke();
      }

      var statY = imageY + 10;
      var statHeight = 118;
      tags.forEach(function (tag, index) {
        var parts = tag.split(':');
        var label = cleanText(parts.shift() || 'stat');
        var value = cleanText(parts.join(':') || tag);
        drawShareStat(ctx, label, value, copyX, statY + index * (statHeight + 20), copyW, statHeight);
      });

      var sourceTop = imageY + imageH - 140;
      roundRect(ctx, copyX, sourceTop, copyW, 136, 24);
      ctx.fillStyle = 'rgba(255,255,255,.045)';
      ctx.fill();
      ctx.strokeStyle = 'rgba(255,255,255,.15)';
      ctx.stroke();

      var coverW = 68;
      var coverH = 102;
      var coverX = copyX + copyW - coverW - 24;
      var coverY = sourceTop + 17;
      var sourceTextX = copyX + 28;
      var sourceTextW = cover ? (coverX - sourceTextX - 18) : (copyW - 56);

      if (cover) {
        roundRect(ctx, coverX, coverY, coverW, coverH, 10);
        ctx.fillStyle = '#111';
        ctx.fill();
        ctx.save();
        ctx.clip();
        drawContainedCanvasImage(ctx, cover, coverX, coverY, coverW, coverH);
        ctx.restore();
      }

      ctx.fillStyle = '#ff8ac7';
      ctx.font = '800 22px Georgia, serif';
      ctx.textAlign = 'left';
      ctx.fillText('profile card', sourceTextX, sourceTop + 44);
      ctx.fillStyle = '#fff';
      ctx.font = '28px Georgia, serif';
      wrapCanvasText(ctx, bookTitle ? 'from ' + bookTitle : 'open his profile', sourceTextX, sourceTop + 84, sourceTextW, 32, 2);

      ctx.fillStyle = '#ff8ac7';
      ctx.font = '36px Georgia, serif';
      ctx.textAlign = 'center';
      ctx.fillText('bybookishbabe', width / 2, height - 145);

      return canvas;
    }

    ctx.textAlign = 'center';
    ctx.fillStyle = '#ff8ac7';
    ctx.font = '800 23px Georgia, serif';
    ctx.letterSpacing = '4px';
    ctx.fillText(kicker, width / 2, 160);

    ctx.letterSpacing = '0px';
    ctx.fillStyle = '#fff';
    ctx.font = '72px Georgia, serif';
    var nextY = wrapCanvasText(ctx, title, width / 2, 235, 800, 82, 3);

    ctx.fillStyle = 'rgba(255,255,255,.78)';
    ctx.font = '34px Georgia, serif';
    ctx.textAlign = 'center';
    nextY = wrapCanvasText(ctx, copy, width / 2, nextY + 34, 720, 46, 3);

    var portraitTop = nextY + 36;
    if (profileImage) {
      var portraitSize = 290;
      var portraitX = (width - portraitSize) / 2;
      roundRect(ctx, portraitX, portraitTop, portraitSize, portraitSize, 28);
      ctx.save();
      ctx.clip();
      ctx.drawImage(profileImage, portraitX, portraitTop, portraitSize, portraitSize);
      ctx.restore();
      ctx.strokeStyle = 'rgba(255,138,199,.45)';
      ctx.lineWidth = 3;
      ctx.stroke();
      nextY = portraitTop + portraitSize;
    }

    var pillY = nextY + 54;
    var totalWidth = tags.reduce(function (sum, tag) {
      ctx.font = 'italic 30px Georgia, serif';
      return sum + Math.min(360, Math.max(190, ctx.measureText(tag).width + 64)) + 16;
    }, -16);
    var pillX = (width - totalWidth) / 2;
    tags.forEach(function (tag) {
      var pillWidth = drawSharePill(ctx, tag, pillX, pillY);
      pillX += pillWidth + 16;
    });

    var bookTop = pillY + (profileImage ? 88 : 118);
    var compactBook = profileImage || bookTop > 930;
    roundRect(ctx, compactBook ? 180 : 254, bookTop, compactBook ? 720 : 572, compactBook ? 128 : 438, 32);
    ctx.fillStyle = 'rgba(255,255,255,.045)';
    ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,.15)';
    ctx.stroke();

    if (cover && !compactBook) {
      var coverW = 210;
      var coverH = 315;
      var coverX = 312;
      var coverY = bookTop + 62;
      roundRect(ctx, coverX, coverY, coverW, coverH, 18);
      ctx.save();
      ctx.clip();
      drawContainedCanvasImage(ctx, cover, coverX, coverY, coverW, coverH);
      ctx.restore();
    }

    ctx.textAlign = compactBook || !cover ? 'center' : 'left';
    ctx.fillStyle = '#ff8ac7';
    ctx.font = '800 22px Georgia, serif';
    ctx.fillText(isBoyfriendShare ? 'profile card' : (compactBook ? 'source book' : 'best match'), compactBook ? width / 2 : (cover ? 558 : width / 2), bookTop + (compactBook ? 42 : 94));
    ctx.fillStyle = '#fff';
    ctx.font = compactBook ? '34px Georgia, serif' : '40px Georgia, serif';
    wrapCanvasText(ctx, isBoyfriendShare ? (bookTitle ? 'from ' + bookTitle : 'open his profile') : (bookTitle || 'your next read is waiting'), compactBook ? width / 2 : (cover ? 558 : width / 2), bookTop + (compactBook ? 86 : 154), compactBook ? 610 : (cover ? 220 : 420), compactBook ? 40 : 48, compactBook ? 1 : 3);
    if (!compactBook) {
      ctx.fillStyle = 'rgba(255,255,255,.68)';
      ctx.font = '26px Arial, sans-serif';
      wrapCanvasText(ctx, bookAuthor, cover ? 558 : width / 2, bookTop + 310, cover ? 220 : 420, 34, 2);
    }

    ctx.textAlign = 'center';
    ctx.fillStyle = '#ff8ac7';
    ctx.font = '34px Georgia, serif';
    ctx.fillText('by bookish babe', width / 2, height - 120);

    return canvas;
  }

  async function generateShareImage(root) {
    try {
      return await canvasToBlob(await buildShareImage(root, true));
    } catch (error) {
      return canvasToBlob(await buildShareImage(root, false));
    }
  }

  function setShareButtonFeedback(button, label) {
    if (!button) return;
    var textEl = button.querySelector('.bbb-livequiz__shareText');
    if (textEl) textEl.textContent = label;
    else if (!button.classList.contains('bbb-livequiz__bfPinButton')) button.textContent = label;
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
  }

  async function shareResult(root, button) {
    var url = root.dataset.quizShareUrl || window.location.href;
    var title = root.dataset.quizShareTitle || 'my reader quiz result';
    var originalLabel = button ? (button.getAttribute('aria-label') || button.textContent || 'share your result') : 'share your result';
    var blob;

    if (button) {
      button.classList.add('is-making-image');
      setShareButtonFeedback(button, 'making image');
    }

    try {
      blob = await generateShareImage(root);
      var file = typeof File !== 'undefined' ? new File([blob], 'my fictional bf.png', { type: 'image/png' }) : null;

      if (file && navigator.canShare && navigator.canShare({ files: [file] }) && navigator.share) {
        await navigator.share({
          title: title,
          text: title,
          url: url,
          files: [file]
        });
      } else {
        var downloadUrl = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = downloadUrl;
        link.download = 'my fictional bf.png';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 1000);
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(title + '\n' + url).catch(function () {});
        }
        setShareButtonFeedback(button, 'image saved');
      }
    } catch (error) {
      if (navigator.share) {
        await navigator.share({ title: title, text: title, url: url }).catch(function () {});
      } else if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(title + '\n' + url);
        setShareButtonFeedback(button, 'link copied');
      }
    } finally {
      if (button) {
        button.classList.remove('is-making-image');
        window.setTimeout(function () {
          setShareButtonFeedback(button, originalLabel);
        }, 1800);
      }
    }
  }

  async function uploadPinCard(blob) {
    var settings = window.BBBReaderQuizPins || {};
    if ((!settings.restUrl || !settings.restNonce) && (!settings.ajaxUrl || !settings.nonce)) throw new Error('pin upload unavailable');

    var form = new FormData();
    form.append('card', blob, 'my fictional bf.png');

    var response;
    if (settings.restUrl && settings.restNonce) {
      response = await fetch(settings.restUrl, {
        method: 'POST',
        body: form,
        credentials: 'same-origin',
        headers: {
          'X-WP-Nonce': settings.restNonce
        }
      });
    } else {
      form.append('action', 'bbb_reader_quiz_pin_card');
      form.append('nonce', settings.nonce);
      response = await fetch(settings.ajaxUrl, {
        method: 'POST',
        body: form,
        credentials: 'same-origin'
      });
    }
    var data = await response.json();
    var url = data && data.url ? data.url : (data && data.data && data.data.url ? data.data.url : '');
    if (!response.ok || !url) {
      throw new Error((data && data.message) || (data && data.data && data.data.message) || 'pin upload failed');
    }

    return url;
  }

  async function pinResult(root, button) {
    var title = root.dataset.quizShareTitle || 'my fictional boyfriend';
    var url = root.dataset.quizShareUrl || window.location.href;
    var originalLabel = button ? (button.getAttribute('aria-label') || 'pin the result card') : 'pin the result card';
    var popup = window.open('about:blank', '_blank');

    if (button) {
      button.classList.add('is-making-image');
      setShareButtonFeedback(button, 'making pin');
    }

    try {
      var blob = await generateShareImage(root);
      var mediaUrl = await uploadPinCard(blob);
      var pinUrl = 'https://www.pinterest.com/pin/create/button/?url=' + encodeURIComponent(url) +
        '&media=' + encodeURIComponent(mediaUrl) +
        '&description=' + encodeURIComponent(title + ' | bybookishbabe');

      if (popup) popup.location.href = pinUrl;
      else window.open(pinUrl, '_blank', 'noopener,noreferrer');
      setShareButtonFeedback(button, 'pin opened');
    } catch (error) {
      if (popup) popup.close();
      shareResult(root, button).catch(function () {});
    } finally {
      if (button) {
        button.classList.remove('is-making-image');
        window.setTimeout(function () {
          setShareButtonFeedback(button, originalLabel);
        }, 1800);
      }
    }
  }

  function tropeEmoji(trope) {
    var value = text(trope);
    if (includesAny(value, ['slow burn', 'yearning'])) return '🕯️';
    if (includesAny(value, ['enemies to lovers', 'rivals', 'banter'])) return '⚔️';
    if (includesAny(value, ['friends to lovers', 'comfort', 'healing', 'found family'])) return '🤍';
    if (includesAny(value, ['forced proximity', 'one bed'])) return '🛏️';
    if (includesAny(value, ['fake dating', 'marriage of convenience'])) return '💍';
    if (includesAny(value, ['second chance', 'emotional damage', 'angst'])) return '💔';
    if (includesAny(value, ['dark', 'morally gray', 'villain'])) return '🥀';
    if (includesAny(value, ['obsession', 'stalker', 'possessive', 'touch her'])) return '🖤';
    if (includesAny(value, ['sports', 'hockey'])) return '🏒';
    if (includesAny(value, ['forbidden'])) return '🍒';
    if (includesAny(value, ['grumpy'])) return '☕';
    if (includesAny(value, ['small town'])) return '🍂';
    if (includesAny(value, ['romantasy', 'fantasy', 'fated mates', 'paranormal'])) return '🌙';
    if (includesAny(value, ['workplace', 'billionaire'])) return '💋';
    return '📚';
  }

  function customTropeKey(trope) {
    var value = text(trope).replace(/^[^\w\s]+/i, '').replace(/\s+/g, ' ').trim();
    var key = value.replace(/&/g, ' and ').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

    if (key === 'billionaire') return 'billionaire-romance';
    if (key === 'bodyguard') return 'bodyguard-romance';
    if (key === 'bully') return 'bully-romance';
    if (key === 'brother-s-best-friend' || key === 'brother-best-friend' || value.indexOf("brother's best friend") !== -1) return 'brothers-best-friend';
    if (key === 'captor-captive-romance' || value.indexOf('captor') !== -1 || value.indexOf('captive') !== -1) return 'captor-x-captive';
    if (key === 'fake-dating-romance') return 'fake-dating';
    if (key === 'forbidden-romance') return 'forbidden-love';
    if (key === 'grumpy-sunshine') return 'grumpy-x-sunshine';
    if (key === 'nanny-romance') return 'nanny';
    if (key === 'paranormal') return 'paranormal-romance';
    if (key === 'single-dad-romance') return 'single-dad';
    if (key === 'small-town-romance') return 'small-town';
    if (key === 'sports') return 'sports-romance';
    if (key === 'stalker') return 'stalker-romance';
    if (key === 'stepsiblings') return 'step-siblings';
    if (key === 'villain-romance') return 'villain-gets-the-girl';
    if (key === 'workplace') return 'boss-x-employee';

    return key;
  }

  function customTropeEmojiHtml(trope) {
    var map = window.BBBSiteData && window.BBBSiteData.customTropeEmojis ? window.BBBSiteData.customTropeEmojis : {};
    var src = map[customTropeKey(trope)];
    if (!src) return '';

    return '<img class="bbb-custom-emoji" src="' + esc(src) + '" alt="" aria-hidden="true" loading="lazy" decoding="async">';
  }

  function tropeLabel(trope) {
    var value = String(trope || '').trim();
    if (!value) return '';
    return /^[^a-z0-9]+ /i.test(value) ? value : tropeEmoji(value) + ' ' + value;
  }

  function tropeLabelHtml(trope) {
    var value = String(trope || '').trim().replace(/^[^\w\s]+\s*/i, '').trim();
    if (!value) return '';
    var custom = customTropeEmojiHtml(value);
    if (custom) return custom + ' <span class="bbb-custom-emoji-label">' + esc(value) + '</span>';
    return esc(tropeLabel(value));
  }

  function tropePill(trope) {
    var label = tropeLabelHtml(trope);
    return label ? '<span><em>' + label + '</em></span>' : '';
  }

  function answerEmoji(answer) {
    var value = text((answer && answer.textContent) + ' ' + (answer && answer.dataset.score));
    if (includesAny(value, ['spicy', 'spice', 'locked door', 'problem'])) return '🌶';
    if (includesAny(value, ['touch her', 'dangerous', 'dark', 'obsession', 'secrets'])) return '🖤';
    if (includesAny(value, ['sweet', 'soft', 'comfort', 'healing', 'smile'])) return '💌';
    if (includesAny(value, ['rivals', 'argues', 'banter', 'enemies'])) return '⚔️';
    if (includesAny(value, ['impossible', 'cursed', 'winged', 'fantasy', 'immortal'])) return '✨';
    if (includesAny(value, ['rink', 'court', 'field', 'team'])) return '🏒';
    if (includesAny(value, ['cry', 'damage', 'ache', 'devastation', 'suffer'])) return '💔';
    if (includesAny(value, ['slow', 'tension', 'yearning'])) return '🕯️';
    return '💘';
  }

  function rainAnswerEmojis(root, answer) {
    var layer = document.createElement('div');
    var emoji = answerEmoji(answer);
    layer.className = 'bbb-livequiz__answerEmojiRain';
    layer.setAttribute('aria-hidden', 'true');

    for (var i = 0; i < 18; i += 1) {
      var drop = document.createElement('span');
      drop.textContent = emoji;
      drop.style.setProperty('--x', (6 + Math.random() * 88).toFixed(2) + '%');
      drop.style.setProperty('--size', (21 + Math.random() * 17).toFixed(0) + 'px');
      drop.style.setProperty('--delay', (Math.random() * 220).toFixed(0) + 'ms');
      drop.style.setProperty('--dur', (900 + Math.random() * 560).toFixed(0) + 'ms');
      drop.style.setProperty('--drift', ((Math.random() * 70) - 35).toFixed(0) + 'px');
      drop.style.setProperty('--spin', ((Math.random() * 420) - 210).toFixed(0) + 'deg');
      layer.appendChild(drop);
    }

    document.body.appendChild(layer);
    window.setTimeout(function () {
      if (layer.parentNode) layer.parentNode.removeChild(layer);
    }, 1800);
  }

  function shelfKey(book) {
    return text(book && (book.handle || book.title));
  }

  function getShelf() {
    try {
      var primary = JSON.parse(localStorage.getItem('sssMyShelf') || 'null');
      if (Array.isArray(primary)) return primary;
    } catch (error) {}

    try {
      var legacy = JSON.parse(localStorage.getItem('sssShelf') || '[]');
      return Array.isArray(legacy) ? legacy : [];
    } catch (error) {
      return [];
    }
  }

  function setShelf(items) {
    try {
      localStorage.setItem('sssMyShelf', JSON.stringify(items));
      localStorage.setItem('sssShelf', JSON.stringify(items));
    } catch (error) {}

    document.dispatchEvent(new CustomEvent('sss:bookshelf-updated', {
      detail: { count: Array.isArray(items) ? items.length : 0 }
    }));
  }

  function shelfBook(book) {
    var tropes = (book.tropes || []).join(', ');
    var tropesDisplay = (book.tropesDisplay || []).join(', ') || (book.tropes || []).map(tropeLabel).join(', ');

    return {
      handle: book.handle || '',
      title: book.title || '',
      author: book.author || '',
      cover: book.cover || '',
      amazon: book.amazon || '',
      bookshop: book.bookshop || '',
      spice: book.spice || '',
      darkness: book.darkness || '',
      tropes: tropes,
      tropesDisplay: tropesDisplay,
      why: book.why || '',
      newsletter: book.newsletter || '',
      mini: book.mini || '',
      series: book.series || '',
      seriesName: book.seriesName || '',
      seriesNumber: book.seriesNumber || '',
      tension: book.tension || '',
      damage: book.damage || '',
      yearning: book.yearning || '',
      boyfriend: book.boyfriend || '',
      boyfriendName: book.boyfriendName || '',
      reread: book.reread || '',
      ku: book.ku || '',
      standalone: book.standalone || 'false',
      privateShelf: 'false',
      saved_at: Date.now()
    };
  }

  function isSaved(book) {
    var key = shelfKey(book);
    return getShelf().some(function (item) {
      return shelfKey(item) === key || text(item.title) === text(book && book.title);
    });
  }

  function updateHeart(heart, saved) {
    if (!heart) return;
    var icon = heart.querySelector('[data-heart-icon]');
    var label = heart.querySelector('[data-heart-label]');
    heart.classList.toggle('is-saved', !!saved);
    heart.setAttribute('aria-label', saved ? 'remove from your bookshelf' : 'save to your bookshelf');
    if (icon) icon.textContent = saved ? '♥' : '♡';
    if (label) label.textContent = saved ? 'saved' : 'save';
  }

  function toggleSave(book, heart) {
    if (!book || !book.title) return;
    var key = shelfKey(book);
    var removed = false;
    var shelf = getShelf().filter(function (item) {
      var same = shelfKey(item) === key || text(item.title) === text(book.title);
      if (same) removed = true;
      return !same;
    });

    if (!removed) shelf.unshift(shelfBook(book));
    setShelf(shelf);
    updateHeart(heart, !removed);
  }

  function dataAttrs(book) {
    var tropes = (book.tropes || []).join(', ');
    var tropesDisplay = (book.tropesDisplay || []).join(', ') || (book.tropes || []).map(tropeLabel).join(', ');
    var tropeUrls = (book.tropeUrls || []).join(', ');
    return [
      ['data-book-preview', ''],
      ['data-handle', book.handle],
      ['data-title', book.title],
      ['data-author', book.author],
      ['data-cover', localAssetUrl(book.cover)],
      ['data-amazon', book.amazon],
      ['data-bookshop', book.bookshop],
      ['data-shelf', book.shelf],
      ['data-private-shelf', 'false'],
      ['data-spice', book.spice || ''],
      ['data-tropes', tropes],
      ['data-tropes-display', tropesDisplay],
      ['data-trope-urls', tropeUrls],
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
    var cover = book.cover ? '<img class="sss-lib__cover" src="' + esc(localAssetUrl(book.cover)) + '" alt="' + esc(bookCoverAlt(book)) + '" loading="lazy">' : '';
    var tropes = (book.tropesDisplay && book.tropesDisplay.length ? book.tropesDisplay : (book.tropes || []).map(tropeLabel)).slice(0, 3).map(tropePill).join('');

    return '<article class="bbb-livequiz__book">' +
      '<p class="bbb-livequiz__bookLabel">' + esc(label) + '</p>' +
      '<button type="button" class="sss-lib__book sss-lib__book--mini" ' + dataAttrs(book) + '>' +
        '<div class="sss-lib__coverWrap">' +
          '<span class="sss-lib__heart' + (isSaved(book) ? ' is-saved' : '') + '" data-heart data-quiz-save role="button" aria-label="' + (isSaved(book) ? 'remove from your bookshelf' : 'save to your bookshelf') + '">' +
            '<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">' + (isSaved(book) ? '♥' : '♡') + '</span>' +
            '<span class="sss-lib__heartLabel" data-heart-label>' + (isSaved(book) ? 'saved' : 'save') + '</span>' +
          '</span>' +
          spiceHtml + cover +
        '</div>' +
        '<div class="sss-lib__under"><div class="sss-lib__name">' + esc(book.title) + '</div><div class="sss-lib__author">' + esc(book.author) + '</div></div>' +
      '</button>' +
      '<div class="bbb-livequiz__bookTropes">' + tropes + '</div>' +
      '<p class="bbb-livequiz__bookWhy">' + esc(book.mini || book.why || 'this one fits the mood you just picked.') + '</p>' +
    '</article>';
  }

  function shareUrl(profile) {
    var path = profile && profile.slug ? window.location.pathname + '#' + profile.slug : window.location.pathname;
    return window.location.origin + path;
  }

  function shareButton(label) {
    var text = label || 'share your result';
    return '<button type="button" class="bbb-livequiz__shareBtn bbb-livequiz__shareBtn--result" data-quiz-share aria-label="' + esc(text) + '" title="' + esc(text) + '">' +
      '<span class="bbb-livequiz__shareText">' + esc(text) + '</span>' +
      '<span class="bbb-livequiz__shareIcon" aria-hidden="true">📲</span>' +
    '</button>';
  }

  function nextQuiz(type) {
    var quizzes = {
      'reader-type': {
        href: '/fictional-boyfriend-quiz/',
        label: 'find your fictional boyfriend'
      },
      boyfriend: {
        href: '/romance-trope-quiz/',
        label: 'take the trope quiz'
      },
      trope: {
        href: '/reader-mood-quiz/',
        label: 'take the mood quiz'
      },
      mood: {
        href: '/fictional-boyfriend-quiz/',
        label: 'find your fictional boyfriend'
      }
    };

    return quizzes[type] || quizzes.mood;
  }

  function safeBoyfriendName(book) {
    if (!book) return '';

    var name = String(book.boyfriendName || '').trim();
    var title = String(book.title || '').trim().toLowerCase();
    var author = String(book.author || '').trim().toLowerCase();
    var normalizedName = name.toLowerCase();

    if (name && normalizedName !== title && normalizedName !== author && name.length <= 42) {
      return normalizedName;
    }

    var type = String(book.boyfriend || '').trim().toLowerCase();
    if (type && type !== title && type !== author && type.length <= 42) {
      return 'the ' + type;
    }

    return '';
  }

  function resultTitle(type, profile, picks) {
    if (type === 'boyfriend') {
      var name = safeBoyfriendName(picks[0]);
      if (name) return 'your fictional boyfriend is ' + name;
    }

    return String(profile.title || '').toLowerCase();
  }

  function boyfriendMiniCard(profile, label) {
    var descriptor = profile && profile.descriptor ? profile.descriptor : '';
    return '<a class="bbb-livequiz__bfMini" href="' + esc(profile.url || '/fictional-boyfriends/') + '">' +
      '<span class="bbb-livequiz__bfMiniImage">' +
        '<img src="' + esc(localAssetUrl(profile.image)) + '" alt="' + esc(profile.name + ' book boyfriend profile') + '" loading="lazy" decoding="async">' +
      '</span>' +
      '<span class="bbb-livequiz__bfMiniCopy">' +
        '<span class="bbb-livequiz__bookLabel">' + esc(label) + '</span>' +
        '<span class="bbb-livequiz__bfMiniName">' + esc(profile.name) + '</span>' +
        (descriptor ? '<span class="bbb-livequiz__bfMiniType">' + esc(descriptor) + '</span>' : '') +
      '</span>' +
    '</a>';
  }

  function boyfriendResultFaq(match, archetype) {
    var name = match && match.name ? match.name : 'him';
    var firstName = name.split(/\s+/)[0] || name;
    var shelf = match && match.shelf ? match.shelf : 'romance';
    var descriptor = match && match.descriptor ? match.descriptor : archetype.kicker;
    var descriptorArticle = /^[aeiou]/i.test(String(descriptor || '').trim()) ? 'an' : 'a';
    var textBack = match && match.wouldTextBack ? match.wouldTextBack : 'eventually, after making it weird first';
    var loveLanguage = match && match.loveLanguage ? match.loveLanguage : 'acts of suspiciously specific devotion';
    var bookTitle = match && match.bookTitle ? match.bookTitle : '';
    var bookUrl = match && match.bookUrl ? match.bookUrl : '';
    var author = match && match.author ? match.author : '';
    var source = bookTitle
      ? esc(firstName) + ' is from ' + (bookUrl ? '<a href="' + esc(bookUrl) + '">' + esc(bookTitle) + '</a>' : esc(bookTitle)) + (author ? ' by ' + esc(author) : '') + '.'
      : 'open his profile for the book trail. suspiciously useful, i know.';

    return '<section class="bbb-livequiz__resultFaq" aria-labelledby="fictional-boyfriend-result-faq">' +
      '<h3 id="fictional-boyfriend-result-faq">fictional boyfriend quiz faq</h3>' +
      '<details open>' +
        '<summary>what book is ' + esc(firstName) + ' from?</summary>' +
        '<p>' + source + '</p>' +
      '</details>' +
      '<details>' +
        '<summary>what does this say about me?</summary>' +
        '<p>you saw ' + esc(descriptorArticle) + ' ' + esc(descriptor) + ' from the ' + esc(shelf) + ' shelf and said, respectfully, yes, that problem can sit beside me. this is not a diagnosis. it is evidence.</p>' +
      '</details>' +
      '<details>' +
        '<summary>would he text back?</summary>' +
        '<p>' + esc(textBack) + '. his love language is ' + esc(loveLanguage) + ', so please adjust your expectations and your notification settings accordingly.</p>' +
      '</details>' +
    '</section>';
  }

  function boyfriendCard(match, archetype, relatedBoyfriends) {
    var profileUrl = match && match.url ? match.url : '';
    var traitStats = boyfriendTraitStats(match, archetype);
    var meters = boyfriendMeters(match);

    return '<article class="bbb-livequiz__resultCard bbb-livequiz__resultCard--boyfriend" data-quiz-result-card>' +
      '<div class="bbb-livequiz__bfPinCard">' +
        '<div class="bbb-livequiz__bfPortrait">' +
          (match && match.image ? '<img src="' + esc(localAssetUrl(match.image)) + '" alt="' + esc(match.name + ' fictional boyfriend result') + '" loading="lazy" decoding="async">' : '') +
          '<button type="button" class="bbb-livequiz__bfPinButton" data-quiz-pin-card aria-label="pin the result card" title="pin the result card">' + pinterestIcon() + '</button>' +
        '</div>' +
        '<div class="bbb-livequiz__bfCopy">' +
          '<p class="bbb-livequiz__resultKicker">' + esc(match && match.shelf ? match.shelf : archetype.kicker) + '</p>' +
          '<h2>your fictional boyfriend is ' + esc(match ? match.name : archetype.title.replace(/^your fictional boyfriend is\s+/i, '')) + '</h2>' +
          '<p class="bbb-livequiz__resultCopy">' + esc(boyfriendResultCopy(match, archetype)) + '</p>' +
          '<div class="bbb-livequiz__bfStats">' +
            traitStats.map(function (stat) { return statPill(stat[0], stat[1]); }).join('') +
          '</div>' +
          (meters ? '<div class="bbb-livequiz__bfMeters">' + meters + '</div>' : '') +
          '<div class="bbb-livequiz__resultActions">' +
            (profileUrl ? '<a class="bbb-livequiz__link bbb-livequiz__link--library" href="' + esc(profileUrl) + '">open profile</a>' : '<a class="bbb-livequiz__link bbb-livequiz__link--library" href="/fictional-boyfriends/">meet the lineup</a>') +
            shareButton('save the card') +
          '</div>' +
        '</div>' +
      '</div>' +
      boyfriendResultFaq(match, archetype) +
      '<div class="bbb-livequiz__booksHead bbb-livequiz__booksHead--boyfriends"><p>book boyfriends you should check out</p></div>' +
      '<div class="bbb-livequiz__boyfriends">' + relatedBoyfriends.map(function (profile, index) {
        return boyfriendMiniCard(profile, index === 0 ? 'same energy' : (index === 1 ? 'next obsession' : 'wildcard'));
      }).join('') + '</div>' +
    '</article>';
  }

  function resultAccountCta(root) {
    if (!root || !root.dataset.quizResultCta) return '';

    var subscribeUrl = root.dataset.quizSubscribeUrl || '/smut-sentiment-society/';
    var accountUrl = root.dataset.quizAccountUrl || '/account/';
    var recsUrl = root.dataset.quizRecsUrl || '/made-for-you/';

    return '<aside class="bbb-livequiz__conversion" aria-label="save your reader type">' +
      '<div class="bbb-livequiz__conversionCopy">' +
        '<p class="bbb-livequiz__resultKicker">keep the result</p>' +
        '<h3>turn this into your personal rec engine.</h3>' +
      '</div>' +
      '<div class="bbb-livequiz__conversionActions">' +
        '<a class="bbb-livequiz__conversionStep" href="' + esc(subscribeUrl) + '">' +
          '<span class="bbb-livequiz__conversionStepTop" aria-hidden="true"><span>1</span></span>' +
          '<strong>subscribe free</strong>' +
          '<p>get the weekly reader dispatch and keep the quiz world close.</p>' +
        '</a>' +
        '<a class="bbb-livequiz__conversionStep" href="' + esc(accountUrl) + '">' +
          '<span class="bbb-livequiz__conversionStepTop" aria-hidden="true"><span>2</span></span>' +
          '<strong>create account</strong>' +
          '<p>save your reader type so it can follow your shelf and quiz history.</p>' +
        '</a>' +
        '<a class="bbb-livequiz__conversionStep" href="' + esc(recsUrl) + '">' +
          '<span class="bbb-livequiz__conversionStepTop" aria-hidden="true"><span>3</span></span>' +
          '<strong>get recs</strong>' +
          '<p>open Made For You for smarter picks based on this result.</p>' +
        '</a>' +
      '</div>' +
    '</aside>';
  }

  function renderResult(root, scores, answers) {
    var type = root.dataset.quizType || 'mood';
    var standard = root.hasAttribute('data-quiz-standard');
    var books = readJson(root, '[data-quiz-books]');

    var profile = profileFor(type, scores);
    var boyfriendMatch = type === 'boyfriend' ? selectBoyfriendProfile(root, scores, profile) : null;
    var relatedBoyfriends = boyfriendMatch ? rankedBoyfriendProfiles(root, scores, profile, boyfriendMatch.id, 3) : [];
    var suggestedQuiz = nextQuiz(type);
    var picks = books.map(function (book, index) {
      return { book: book, score: type === 'reader-type' ? scoreReaderTypeBook(book, profile, scores, index) : scoreBook(book, profile, scores, index) };
    }).filter(function (item) {
      return item.score > -100000 && item.book && item.book.title && item.book.cover;
    }).sort(function (a, b) {
      return b.score - a.score;
    }).slice(0, 3).map(function (item) {
      return item.book;
    });
    syncQuizEvidence(root, type, scores, answers || [], profile, boyfriendMatch, picks);
    var result = root.querySelector('[data-quiz-result]');
    result.hidden = false;
    if (standard && type === 'boyfriend' && boyfriendMatch) {
      result.innerHTML = boyfriendCard(boyfriendMatch, profile, relatedBoyfriends) +
        '<div class="bbb-livequiz__links">' +
          '<a class="bbb-livequiz__link bbb-livequiz__link--quiz" href="' + esc(suggestedQuiz.href) + '">next quiz <span aria-hidden="true">→</span></a>' +
        '</div>';
    } else if (standard) {
      result.innerHTML =
        '<article class="bbb-livequiz__resultCard" data-quiz-result-card>' +
          '<p class="bbb-livequiz__resultKicker">' + esc(profile.kicker) + '</p>' +
          '<h2>' + esc(resultTitle(type, profile, picks)) + '</h2>' +
          '<p class="bbb-livequiz__resultCopy">' + esc(profile.copy) + '</p>' +
          '<div class="bbb-livequiz__tags">' + profile.tags.map(tropePill).join('') + '</div>' +
          '<div class="bbb-livequiz__booksHead"><p>your result stack</p></div>' +
          '<div class="bbb-livequiz__books">' + picks.map(function (book, index) {
            return card(book, index === 0 ? 'best match' : (index === 1 ? 'same energy' : 'wildcard'));
          }).join('') + '</div>' +
          resultAccountCta(root) +
          '<div class="bbb-livequiz__resultActions">' +
            '<a class="bbb-livequiz__link bbb-livequiz__link--library" href="/library/">browse the library</a>' +
            shareButton('share your result') +
          '</div>' +
        '</article>' +
        '<div class="bbb-livequiz__links">' +
          '<a class="bbb-livequiz__link bbb-livequiz__link--quiz" href="' + esc(suggestedQuiz.href) + '">next quiz <span aria-hidden="true">→</span></a>' +
        '</div>';
    } else {
      result.innerHTML =
        '<p class="bbb-livequiz__resultKicker">' + esc(profile.kicker) + '</p>' +
        '<h2>' + esc(resultTitle(type, profile, picks)) + '</h2>' +
        '<p class="bbb-livequiz__resultCopy">' + esc(profile.copy) + '</p>' +
        '<div class="bbb-livequiz__shareTop">' + shareButton('share result') + '</div>' +
        '<div class="bbb-livequiz__tags">' + profile.tags.map(tropePill).join('') + '</div>' +
        '<div class="bbb-livequiz__booksHead"><p>what you need to add to your tbr</p></div>' +
        '<div class="bbb-livequiz__books">' + picks.map(function (book, index) {
          return card(book, index === 0 ? 'best match' : (index === 1 ? 'second mood' : 'wildcard'));
        }).join('') + '</div>' +
        '<div class="bbb-livequiz__shareCard">' +
          '<p>send this to the group chat and let them diagnose you too.</p>' +
          shareButton('share result') +
        '</div>' +
        '<div class="bbb-livequiz__links">' +
          '<a class="bbb-livequiz__link bbb-livequiz__link--quiz" href="' + esc(suggestedQuiz.href) + '">suggested next: ' + esc(suggestedQuiz.label) + '</a>' +
          '<a class="bbb-livequiz__link bbb-livequiz__link--library" href="/library/"><span aria-hidden="true">📚</span> browse full library</a>' +
        '</div>';
    }
    root.dataset.quizShareUrl = boyfriendMatch && boyfriendMatch.url ? boyfriendMatch.url : shareUrl(profile);
    root.dataset.quizShareType = boyfriendMatch ? 'boyfriend' : type;
    root.dataset.quizShareTitle = boyfriendMatch ? 'your fictional boyfriend is ' + boyfriendMatch.name : resultTitle(type, profile, picks);
    root.dataset.quizShareKicker = boyfriendMatch && boyfriendMatch.shelf ? boyfriendMatch.shelf : (profile.kicker || '');
    root.dataset.quizShareCopy = boyfriendMatch ? boyfriendResultCopy(boyfriendMatch, profile) : (profile.copy || '');
    root.dataset.quizShareTags = (boyfriendMatch ? boyfriendTraitStats(boyfriendMatch, profile).slice(0, 3).map(function (stat) {
      return stat[0] + ': ' + stat[1];
    }) : profile.tags).slice(0, 3).join('|');
    root.dataset.quizShareBookTitle = boyfriendMatch && boyfriendMatch.bookTitle ? boyfriendMatch.bookTitle : (picks[0] && picks[0].title ? picks[0].title : '');
    root.dataset.quizShareBookAuthor = boyfriendMatch && boyfriendMatch.author ? boyfriendMatch.author : (picks[0] && picks[0].author ? picks[0].author : '');
    root.dataset.quizShareBookCover = localAssetUrl(boyfriendMatch && boyfriendMatch.bookCover ? boyfriendMatch.bookCover : (picks[0] && picks[0].cover ? picks[0].cover : ''));
    root.dataset.quizShareProfileImage = localAssetUrl(boyfriendMatch && (boyfriendMatch.imageFull || boyfriendMatch.image) ? (boyfriendMatch.imageFull || boyfriendMatch.image) : '');
    root.classList.remove('is-started');
    root.classList.add('is-showing-result');
    window.requestAnimationFrame(function () {
      root.classList.add('is-result-ready');
    });
    result.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function init(root) {
    var begin = root.querySelector('[data-quiz-begin]');
    var again = root.querySelector('[data-quiz-again]');
    var track = root.querySelector('[data-quiz-track]');
    var progressText = root.querySelector('[data-quiz-progress-text]');
    var progressBar = root.querySelector('[data-quiz-progress-bar]');
    var slides = Array.prototype.slice.call(root.querySelectorAll('[data-quiz-slide]'));
    var scores = {};
    var answers = [];
    var index = 0;
    var locked = false;

    function showSlide(nextIndex) {
      index = nextIndex;
      if (progressText) progressText.textContent = 'question ' + (index + 1) + ' of ' + slides.length;
      if (progressBar) progressBar.style.width = (((index + 1) / slides.length) * 100).toFixed(2) + '%';
      slides.forEach(function (slide, slideIndex) {
        slide.classList.toggle('is-active', slideIndex === index);
        var answers = slide.querySelector('.bbb-livequiz__answers');
        if (answers) answers.classList.remove('is-locked');
        if (slideIndex === index) {
          Array.prototype.slice.call(slide.querySelectorAll('[data-quiz-answer]')).forEach(function (button, buttonIndex) {
            button.style.setProperty('--quiz-answer-index', String(buttonIndex));
            button.classList.remove('is-selected', 'selected');
          });
        }
      });
    }

    function reset() {
      scores = {};
      answers = [];
      locked = false;
      root.classList.remove('is-started', 'is-showing-result', 'is-result-ready');
      root.querySelector('[data-quiz-result]').hidden = true;
      root.querySelector('[data-quiz-result]').innerHTML = '';
      track.hidden = true;
      showSlide(0);
      root.querySelector('.bbb-livequiz__hero').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    begin && begin.addEventListener('click', function () {
      root.classList.add('is-started');
      locked = false;
      track.hidden = false;
      showSlide(0);
      window.setTimeout(function () {
        (root.querySelector('[data-quiz-progress]') || track).scrollIntoView({ behavior: 'smooth', block: 'start' });
      }, 80);
    });

    again && again.addEventListener('click', reset);

    root.addEventListener('click', function (event) {
      var save = event.target.closest('[data-quiz-save]');
      if (save && root.contains(save)) {
        var bookBtn = save.closest('.sss-lib__book');
        var handle = bookBtn ? bookBtn.dataset.handle : '';
        var data = root.querySelector('[data-quiz-books]');
        var books = [];
        try { books = JSON.parse(data ? data.textContent : '[]') || []; } catch (error) { books = []; }
        var book = books.find(function (item) { return item.handle === handle; });
        event.preventDefault();
        event.stopPropagation();
        toggleSave(book, save);
        return;
      }

      var share = event.target.closest('[data-quiz-share]');
      if (share && root.contains(share)) {
        event.preventDefault();
        event.stopPropagation();
        shareResult(root, share).catch(function () {});
        return;
      }

      var pin = event.target.closest('[data-quiz-pin-card]');
      if (pin && root.contains(pin)) {
        event.preventDefault();
        event.stopPropagation();
        pinResult(root, pin).catch(function () {});
        return;
      }

      var answer = event.target.closest('[data-quiz-answer]');
      if (!answer || !root.contains(answer)) return;
      if (locked) return;

      locked = true;
      answer.classList.add('is-selected', 'selected');
      var answerGroup = answer.closest('.bbb-livequiz__answers');
      if (answerGroup) answerGroup.classList.add('is-locked');
      if (!root.hasAttribute('data-quiz-standard')) rainAnswerEmojis(root, answer);

      var answerScores = parseScores(answer.dataset.score);
      var slide = answer.closest('[data-quiz-slide]');
      var questionHeading = slide ? slide.querySelector('h2') : null;
      answers.push({
        index: index + 1,
        question: String(questionHeading ? questionHeading.textContent : '').trim(),
        answer: String(answer.textContent || '').trim(),
        score: Object.assign({}, answerScores)
      });
      Object.keys(answerScores).forEach(function (key) {
        scores[key] = (scores[key] || 0) + answerScores[key];
      });

      window.setTimeout(function () {
        if (index < slides.length - 1) {
          showSlide(index + 1);
          locked = false;
        } else {
          track.hidden = true;
          renderResult(root, scores, answers);
          locked = false;
        }
      }, 430);
    });
  }

  window.BBBReaderQuizCreateShareImage = async function (root) {
    var target = root || document.querySelector('[data-reader-quiz].is-showing-result');
    if (!target) return '';
    var blob = await generateShareImage(target);
    return new Promise(function (resolve) {
      var reader = new FileReader();
      reader.onload = function () { resolve(reader.result); };
      reader.readAsDataURL(blob);
    });
  };

  document.querySelectorAll('[data-reader-quiz]').forEach(init);
})();
