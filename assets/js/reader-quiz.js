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
      image.src = src;
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

    var bookTop = pillY + 118;
    roundRect(ctx, 254, bookTop, 572, 438, 32);
    ctx.fillStyle = 'rgba(255,255,255,.045)';
    ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,.15)';
    ctx.stroke();

    if (cover) {
      var coverW = 210;
      var coverH = 315;
      var coverX = 312;
      var coverY = bookTop + 62;
      roundRect(ctx, coverX, coverY, coverW, coverH, 18);
      ctx.save();
      ctx.clip();
      ctx.drawImage(cover, coverX, coverY, coverW, coverH);
      ctx.restore();
    }

    ctx.textAlign = cover ? 'left' : 'center';
    ctx.fillStyle = '#ff8ac7';
    ctx.font = '800 22px Georgia, serif';
    ctx.fillText('best match', cover ? 558 : width / 2, bookTop + 94);
    ctx.fillStyle = '#fff';
    ctx.font = '40px Georgia, serif';
    wrapCanvasText(ctx, bookTitle || 'your next read is waiting', cover ? 558 : width / 2, bookTop + 154, cover ? 220 : 420, 48, 3);
    ctx.fillStyle = 'rgba(255,255,255,.68)';
    ctx.font = '26px Arial, sans-serif';
    wrapCanvasText(ctx, bookAuthor, cover ? 558 : width / 2, bookTop + 310, cover ? 220 : 420, 34, 2);

    ctx.textAlign = 'center';
    ctx.fillStyle = '#ff8ac7';
    ctx.font = '34px Georgia, serif';
    ctx.fillText('by bookish babe', width / 2, height - 150);
    ctx.fillStyle = 'rgba(255,255,255,.64)';
    ctx.font = '24px Arial, sans-serif';
    ctx.fillText(cleanText(root.dataset.quizShareUrl || window.location.href), width / 2, height - 110);

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
    else button.textContent = label;
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
      var file = typeof File !== 'undefined' ? new File([blob], 'bookish-babe-quiz-result.png', { type: 'image/png' }) : null;

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
        link.download = 'bookish-babe-quiz-result.png';
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
      ['data-cover', book.cover],
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
    var cover = book.cover ? '<img class="sss-lib__cover" src="' + esc(book.cover) + '" alt="' + esc(book.title) + '" loading="lazy">' : '';
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
    var path = profile && profile.slug ? '/romance-trope-quiz/#' + profile.slug : window.location.pathname;
    return window.location.origin + path;
  }

  function shareButton(label) {
    var text = label || 'share your result';
    return '<button type="button" class="bbb-livequiz__shareBtn bbb-livequiz__shareBtn--result" data-quiz-share aria-label="' + esc(text) + '" title="' + esc(text) + '">' +
      '<span class="bbb-livequiz__shareText">' + esc(text) + '</span>' +
      '<span class="bbb-livequiz__shareIcon" aria-hidden="true">📱</span>' +
    '</button>';
  }

  function nextQuiz(type) {
    var quizzes = {
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

  function renderResult(root, scores) {
    var type = root.dataset.quizType || 'mood';
    var standard = root.hasAttribute('data-quiz-standard');
    var data = root.querySelector('[data-quiz-books]');
    var books = [];
    try { books = JSON.parse(data ? data.textContent : '[]') || []; } catch (error) { books = []; }

    var profile = profileFor(type, scores);
    var suggestedQuiz = nextQuiz(type);
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
    if (standard) {
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
    root.dataset.quizShareUrl = shareUrl(profile);
    root.dataset.quizShareTitle = resultTitle(type, profile, picks);
    root.dataset.quizShareKicker = profile.kicker || '';
    root.dataset.quizShareCopy = profile.copy || '';
    root.dataset.quizShareTags = profile.tags.join('|');
    root.dataset.quizShareBookTitle = picks[0] && picks[0].title ? picks[0].title : '';
    root.dataset.quizShareBookAuthor = picks[0] && picks[0].author ? picks[0].author : '';
    root.dataset.quizShareBookCover = picks[0] && picks[0].cover ? picks[0].cover : '';
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

      var answer = event.target.closest('[data-quiz-answer]');
      if (!answer || !root.contains(answer)) return;
      if (locked) return;

      locked = true;
      answer.classList.add('is-selected', 'selected');
      var answerGroup = answer.closest('.bbb-livequiz__answers');
      if (answerGroup) answerGroup.classList.add('is-locked');
      if (!root.hasAttribute('data-quiz-standard')) rainAnswerEmojis(root, answer);

      var answerScores = parseScores(answer.dataset.score);
      Object.keys(answerScores).forEach(function (key) {
        scores[key] = (scores[key] || 0) + answerScores[key];
      });

      window.setTimeout(function () {
        if (index < slides.length - 1) {
          showSlide(index + 1);
          locked = false;
        } else {
          track.hidden = true;
          renderResult(root, scores);
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
