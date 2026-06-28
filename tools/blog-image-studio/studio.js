const canvas = document.querySelector('#pinCanvas');
const ctx = canvas.getContext('2d');
ctx.imageSmoothingEnabled = true;
ctx.imageSmoothingQuality = 'high';
const statusEl = document.querySelector('#status');
const templateName = document.querySelector('#templateName');

const state = {
  title: 'Best Hockey Romance Books',
  subtitle: 'spicy reads for your romance shelf',
  template: 'library',
  cta: 'which one are you reading first?',
  profileName: 'the hockey boyfriend',
  profileLine: 'protective, competitive, emotionally inconvenient',
  shelf: 'fictional boyfriend',
  bookLine: '',
  series: '',
  trope: '',
  loveLanguage: '',
  wouldTextBack: '',
  profileSpice: 0,
  bookTitle: '',
  author: '',
  bookQuote: '',
  quotes: [],
  selectedQuoteIndex: 0,
  boyfriendName: '',
  boyfriendImage: null,
  bookTropes: [],
  bookSeries: '',
  seriesBooks: [],
  reviewLine: '',
  reviewStats: [],
  termName: '',
  termDescription: '',
  covers: [],
  selected: [],
};

const templateDefaultCtas = {
  library: 'which one are you reading first?',
  rink: 'which one are you reading first?',
  stack: 'which one are you reading first?',
  boyfriend: 'would he ruin your standards?',
  book: 'have you read it?',
  quoteCard: 'save this quote?',
  reviewPin: 'would you read it?',
  seriesReviewPin: 'read the duet?',
  seriesReadingOrder: 'read full breakdown',
  tropeMood: 'which one are you reading first?',
};

const fallbackCovers = [
  { title: 'Hockey Romance', color: '#f0b7c4' },
  { title: 'Ice Cold Crush', color: '#d7e7ef' },
  { title: 'Penalty Box', color: '#c64c68' },
  { title: 'Power Play', color: '#f8ded2' },
];

function setStatus(message, isError = false) {
  statusEl.textContent = message;
  statusEl.style.color = isError ? '#9d2235' : '';
}

function decodeHtmlEntities(value = '') {
  const textarea = document.createElement('textarea');
  let text = String(value || '');

  for (let pass = 0; pass < 4; pass += 1) {
    textarea.innerHTML = text;
    const decoded = textarea.value;
    if (decoded === text) return decoded;
    text = decoded;
  }

  return text;
}

function wrapText(text, maxWidth, font, maxLines = 5) {
  ctx.font = font;
  const words = decodeHtmlEntities(text).split(/\s+/).filter(Boolean);
  const lines = [];
  let line = '';

  for (const word of words) {
    const test = line ? `${line} ${word}` : word;
    if (ctx.measureText(test).width <= maxWidth) {
      line = test;
      continue;
    }
    if (line) lines.push(line);
    line = word;
    if (lines.length === maxLines - 1) break;
  }
  if (line && lines.length < maxLines) lines.push(line);
  return lines;
}

function fitLines(text, maxWidth, font, maxLines = 4) {
  const lines = wrapText(text, maxWidth, font, maxLines);
  const words = String(text || '').split(/\s+/).filter(Boolean);
  const lineWords = lines.join(' ').split(/\s+/).filter(Boolean);

  if (words.length > lineWords.length && lines.length) {
    let last = lines[lines.length - 1];
    while (last.length > 1 && ctx.measureText(`${last}...`).width > maxWidth) {
      last = last.split(/\s+/).slice(0, -1).join(' ') || last.slice(0, -1);
    }
    lines[lines.length - 1] = `${last.replace(/[.,;:!?-]+$/, '')}...`;
  }

  return lines;
}

function drawTextBlock(text, x, y, maxWidth, font, lineHeight, maxLines, options = {}) {
  ctx.textAlign = options.align || 'left';
  ctx.textBaseline = options.baseline || 'alphabetic';
  ctx.fillStyle = options.color || '#fff';
  const lines = fitLines(text, maxWidth, font, maxLines);
  ctx.font = font;
  lines.forEach((line, index) => {
    ctx.fillText(line, x, y + index * lineHeight);
  });
  return lines.length * lineHeight;
}

function linesIncludeFullText(text, lines) {
  const wanted = String(text || '').split(/\s+/).filter(Boolean).length;
  const rendered = lines.join(' ').split(/\s+/).filter(Boolean).length;
  return rendered >= wanted;
}

function drawFittedTextBlock(text, x, y, maxWidth, fontForSize, sizes, lineHeightRatio, maxLines, maxHeight, options = {}) {
  const value = decodeHtmlEntities(text);
  ctx.textAlign = options.align || 'left';
  ctx.textBaseline = options.baseline || 'alphabetic';
  ctx.fillStyle = options.color || '#fff';

  for (const size of sizes) {
    const font = fontForSize(size);
    const lineHeight = Math.round(size * lineHeightRatio);
    const lines = wrapText(value, maxWidth, font, maxLines);
    if (linesIncludeFullText(value, lines) && lines.length * lineHeight <= maxHeight) {
      ctx.font = font;
      lines.forEach((line, index) => {
        ctx.fillText(line, x, y + index * lineHeight);
      });
      return lines.length * lineHeight;
    }
  }

  return drawTextBlock(
    value,
    x,
    y,
    maxWidth,
    fontForSize(sizes[sizes.length - 1]),
    Math.round(sizes[sizes.length - 1] * lineHeightRatio),
    maxLines,
    options
  );
}

function roundRect(x, y, w, h, r) {
  ctx.beginPath();
  ctx.moveTo(x + r, y);
  ctx.arcTo(x + w, y, x + w, y + h, r);
  ctx.arcTo(x + w, y + h, x, y + h, r);
  ctx.arcTo(x, y + h, x, y, r);
  ctx.arcTo(x, y, x + w, y, r);
  ctx.closePath();
}

function fillRoundRect(x, y, w, h, r, color) {
  ctx.fillStyle = color;
  roundRect(x, y, w, h, r);
  ctx.fill();
}

function strokeRoundRect(x, y, w, h, r, color, width = 1) {
  ctx.strokeStyle = color;
  ctx.lineWidth = width;
  roundRect(x, y, w, h, r);
  ctx.stroke();
}

function drawCoverPlaceholder(x, y, w, h, title, color) {
  fillRoundRect(x, y, w, h, 18, color);
  ctx.fillStyle = 'rgba(255,255,255,.65)';
  ctx.fillRect(x + 24, y + 28, w - 48, 10);
  ctx.fillRect(x + 24, y + h - 56, w - 48, 8);
  ctx.fillStyle = '#130b10';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  const font = '700 34px Cormorant Garamond, Georgia';
  const lines = wrapText(title || 'Book Cover', w - 46, font, 3);
  ctx.font = font;
  lines.forEach((line, index) => {
    ctx.fillText(line, x + w / 2, y + h / 2 - (lines.length - 1) * 22 + index * 44);
  });
}

async function drawCover(cover, x, y, w, h, index) {
  ctx.save();
  roundRect(x, y, w, h, 6);
  ctx.clip();
  ctx.fillStyle = '#f2dedf';
  ctx.fillRect(x, y, w, h);

  if (cover?.proxySrc) {
    try {
      const img = await loadImage(cover.proxySrc);
      const ratio = Math.max(w / img.width, h / img.height);
      const sw = img.width * ratio;
      const sh = img.height * ratio;
      ctx.drawImage(img, x + (w - sw) / 2, y + (h - sh) / 2, sw, sh);
      ctx.restore();
      return;
    } catch {
      // Fall through to generated placeholder.
    }
  }

  const fallback = fallbackCovers[index % fallbackCovers.length];
  drawCoverPlaceholder(x, y, w, h, cover?.title || fallback.title, fallback.color);
  ctx.restore();
}

async function drawSiteCoverCard(cover, x, y, w, h, index, options = {}) {
  const rotation = options.rotation || 0;
  const spiceCount = options.spiceCount ?? coverSpice(cover);

  ctx.save();
  const cx = x + w / 2;
  const cy = y + h / 2;
  ctx.translate(cx, cy);
  ctx.rotate(rotation);
  ctx.translate(-cx, -cy);

  ctx.shadowColor = 'rgba(0,0,0,.38)';
  ctx.shadowBlur = 30;
  ctx.shadowOffsetY = 18;
  fillRoundRect(x - 10, y - 10, w + 20, h + 42, 10, 'rgba(255,255,255,.07)');
  ctx.shadowColor = 'transparent';
  strokeRoundRect(x - 10, y - 10, w + 20, h + 42, 10, 'rgba(255,255,255,.14)', 1);

  await drawCover(cover, x, y, w, h, index);

  if (spiceCount > 0) {
    const pillText = '🌶'.repeat(spiceCount);
    ctx.font = `${Math.max(17, Math.round(w * .075))}px "Apple Color Emoji", "Segoe UI Emoji", sans-serif`;
    const pillW = Math.min(w - 20, Math.max(58, ctx.measureText(pillText).width + 24));
    const pillH = Math.max(34, Math.round(w * .16));
    fillRoundRect(x + w - pillW - 10, y + 10, pillW, pillH, pillH / 2, 'rgba(10,10,10,.78)');
    strokeRoundRect(x + w - pillW - 10, y + 10, pillW, pillH, pillH / 2, 'rgba(255,255,255,.14)', 1);
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#fff7fb';
    ctx.fillText(pillText, x + w - pillW / 2 - 10, y + 10 + pillH / 2 + 1);
  }

  const heartSize = Math.max(32, Math.round(w * .17));
  fillRoundRect(x + 10, y + h - heartSize - 10, heartSize, heartSize, heartSize / 2, 'rgba(10,10,10,.82)');
  strokeRoundRect(x + 10, y + h - heartSize - 10, heartSize, heartSize, heartSize / 2, 'rgba(255,255,255,.14)', 1);
  ctx.fillStyle = '#f5f0f3';
  ctx.font = `${Math.round(heartSize * .58)}px Georgia, serif`;
  ctx.fillText('♡', x + 10 + heartSize / 2, y + h - 10 - heartSize / 2 + 1);

  ctx.textBaseline = 'alphabetic';
  ctx.restore();
}

async function drawPortraitCard(image, x, y, w, h) {
  ctx.save();
  ctx.shadowColor = 'rgba(0,0,0,.42)';
  ctx.shadowBlur = 46;
  ctx.shadowOffsetY = 18;
  fillRoundRect(x, y, w, h, 8, '#0e0e0e');
  ctx.shadowColor = 'transparent';
  strokeRoundRect(x, y, w, h, 8, 'rgba(255,255,255,.12)', 1);
  roundRect(x, y, w, h, 8);
  ctx.clip();

  if (image?.proxySrc) {
    try {
      const img = await loadImage(image.proxySrc);
      const ratio = Math.max(w / img.width, h / img.height);
      const sw = img.width * ratio;
      const sh = img.height * ratio;
      ctx.drawImage(img, x + (w - sw) / 2, y + (h - sh) / 2, sw, sh);
    } catch {
      drawCoverPlaceholder(x, y, w, h, image?.title || 'profile image', '#171317');
    }
  }

  const shade = ctx.createLinearGradient(0, y, 0, y + h);
  shade.addColorStop(0, 'rgba(0,0,0,.08)');
  shade.addColorStop(.72, 'rgba(0,0,0,.22)');
  shade.addColorStop(1, 'rgba(0,0,0,.5)');
  ctx.fillStyle = shade;
  ctx.fillRect(x, y, w, h);
  ctx.restore();
}

async function drawBlurredImageBackground(image) {
  drawBackground('boyfriend');
  if (!image?.proxySrc) return;

  try {
    const img = await loadImage(image.proxySrc);
    ctx.save();
    ctx.filter = 'blur(34px) brightness(.42) saturate(1.15)';
    const ratio = Math.max(1200 / img.width, 1700 / img.height);
    const sw = img.width * ratio;
    const sh = img.height * ratio;
    ctx.drawImage(img, (1000 - sw) / 2, (1500 - sh) / 2, sw, sh);
    ctx.restore();
    ctx.filter = 'none';
    ctx.fillStyle = 'rgba(0,0,0,.62)';
    ctx.fillRect(0, 0, 1000, 1500);
  } catch {
    ctx.filter = 'none';
  }
}

function loadImage(src) {
  return new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => resolve(image);
    image.onerror = reject;
    image.src = src;
  });
}

function drawBackground(template) {
  const gradient = ctx.createLinearGradient(0, 0, 1000, 1500);
  if (template === 'rink') {
    gradient.addColorStop(0, '#081116');
    gradient.addColorStop(.52, '#130d12');
    gradient.addColorStop(1, '#2d1220');
  } else if (template === 'boyfriend') {
    gradient.addColorStop(0, '#070707');
    gradient.addColorStop(.55, '#090909');
    gradient.addColorStop(1, '#0b0b0b');
  } else if (template === 'stack') {
    gradient.addColorStop(0, '#0a0709');
    gradient.addColorStop(.55, '#1b1018');
    gradient.addColorStop(1, '#4b1630');
  } else {
    gradient.addColorStop(0, '#050405');
    gradient.addColorStop(.58, '#130d12');
    gradient.addColorStop(1, '#2a111d');
  }
  ctx.fillStyle = gradient;
  ctx.fillRect(0, 0, 1000, 1500);

  if (template === 'boyfriend') {
    ctx.strokeStyle = 'rgba(255,255,255,.035)';
    ctx.lineWidth = 1;
    for (let x = 0; x <= 1000; x += 42) {
      ctx.beginPath();
      ctx.moveTo(x, 0);
      ctx.lineTo(x, 1500);
      ctx.stroke();
    }
    for (let y = 0; y <= 1500; y += 42) {
      ctx.beginPath();
      ctx.moveTo(0, y);
      ctx.lineTo(1000, y);
      ctx.stroke();
    }
    return;
  }

  const glow = ctx.createRadialGradient(80, 80, 0, 80, 80, 650);
  glow.addColorStop(0, 'rgba(255, 138, 199, .22)');
  glow.addColorStop(1, 'rgba(255, 138, 199, 0)');
  ctx.fillStyle = glow;
  ctx.fillRect(0, 0, 1000, 1500);

  const soft = ctx.createRadialGradient(890, 170, 0, 890, 170, 540);
  soft.addColorStop(0, 'rgba(255, 212, 233, .12)');
  soft.addColorStop(1, 'rgba(255, 212, 233, 0)');
  ctx.fillStyle = soft;
  ctx.fillRect(0, 0, 1000, 1500);
}

function drawBrand() {
  ctx.textAlign = 'center';
  ctx.fillStyle = '#ff8ac7';
  ctx.font = '54px Kaushan Script, cursive';
  ctx.fillText('by bookish babe', 500, 106);
}

function drawTitle() {
  ctx.textAlign = 'center';
  ctx.fillStyle = '#fff7fb';
  const titleFont = state.title.length > 62 ? '600 68px Cormorant Garamond, Cormorant, Georgia' : '600 82px Cormorant Garamond, Cormorant, Georgia';
  const titleStep = state.title.length > 62 ? 70 : 82;
  const lines = wrapText(state.title, 820, titleFont, 5);
  ctx.font = titleFont;
  lines.forEach((line, index) => {
    ctx.fillText(line, 500, 220 + index * titleStep);
  });

  ctx.fillStyle = '#de86bb';
  const subtitleFont = '700 24px Libre Baskerville, Georgia';
  const subtitleLines = wrapText(String(state.subtitle || '').toUpperCase(), 780, subtitleFont, 2);
  ctx.font = subtitleFont;
  subtitleLines.forEach((line, index) => {
    ctx.fillText(line, 500, 220 + lines.length * titleStep + 14 + index * 36);
  });
}

function drawSpiceMeter(x, y, cover) {
  const spiceCount = coverSpice(cover);
  ctx.textAlign = 'left';
  ctx.font = '800 24px DM Sans, Arial';
  ctx.fillStyle = '#ffd4e9';
  ctx.fillText('spice', x, y + 31);

  for (let i = 0; i < 4; i += 1) {
    ctx.fillStyle = i < spiceCount ? '#de86bb' : 'rgba(255,255,255,.16)';
    ctx.beginPath();
    ctx.arc(x + 126 + i * 40, y + 22, 15, 0, Math.PI * 2);
    ctx.fill();
  }
}

function coverSpice(cover) {
  const spice = Number(cover?.spice || 0);
  return Number.isFinite(spice) ? Math.max(0, Math.min(5, spice)) : 0;
}

function drawCtaQuestion(y = 1328, mode = 'light') {
  const cta = String(state.cta || 'which one are you reading first?').toLowerCase();
  const dark = mode === 'dark';
  fillRoundRect(104, y, 792, 92, 46, dark ? 'rgba(255,138,199,.08)' : 'rgba(255,255,255,.94)');
  strokeRoundRect(104, y, 792, 92, 46, dark ? 'rgba(255,138,199,.42)' : 'rgba(255,138,199,.42)', 2);

  ctx.textAlign = 'left';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = dark ? '#ff8ac7' : '#130b10';
  ctx.font = '800 31px DM Sans, Arial';
  const lines = wrapText(cta, 610, '800 31px DM Sans, Arial', 2);
  lines.forEach((line, index) => {
    ctx.fillText(line, 152, y + 46 - (lines.length - 1) * 18 + index * 36);
  });

  ctx.strokeStyle = dark ? '#ff8ac7' : '#de86bb';
  ctx.lineWidth = 8;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.beginPath();
  ctx.moveTo(760, y + 46);
  ctx.lineTo(835, y + 46);
  ctx.moveTo(810, y + 20);
  ctx.lineTo(838, y + 46);
  ctx.lineTo(810, y + 72);
  ctx.stroke();
  ctx.textBaseline = 'alphabetic';
}

function drawReviewFooterCta(y = 1392) {
  const cta = String(state.cta || 'would you read it?').toLowerCase();
  ctx.font = '800 22px DM Sans, Arial';
  ctx.textAlign = 'right';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = '#ff8ac7';
  const textX = 824;
  ctx.fillText(cta, textX, y);

  ctx.strokeStyle = '#ff8ac7';
  ctx.lineWidth = 4;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.beginPath();
  ctx.moveTo(844, y);
  ctx.lineTo(888, y);
  ctx.moveTo(872, y - 14);
  ctx.lineTo(890, y);
  ctx.lineTo(872, y + 14);
  ctx.stroke();
  ctx.textBaseline = 'alphabetic';
}

function drawPill(text, x, y, w, h, options = {}) {
  fillRoundRect(x, y, w, h, h / 2, options.fill || 'rgba(255,138,199,.1)');
  strokeRoundRect(x, y, w, h, h / 2, options.stroke || 'rgba(255,138,199,.36)', 1);
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = options.color || '#ff8ac7';
  ctx.font = options.font || '800 20px DM Sans, Arial';
  ctx.fillText(String(text).toLowerCase(), x + w / 2, y + h / 2 + 1);
  ctx.textBaseline = 'alphabetic';
}

function drawCoverSpiceBadge(cover, x, y, w) {
  const spiceCount = coverSpice(cover);
  if (spiceCount <= 0) return;

  const text = '🌶'.repeat(spiceCount);
  ctx.font = `${Math.max(15, Math.round(w * .09))}px "Apple Color Emoji", "Segoe UI Emoji", sans-serif`;
  const pillW = Math.min(w - 14, Math.max(54, ctx.measureText(text).width + 22));
  const pillH = Math.max(30, Math.round(w * .18));
  const px = x + w - pillW - 8;
  const py = y + 8;

  fillRoundRect(px, py, pillW, pillH, pillH / 2, 'rgba(10,10,10,.78)');
  strokeRoundRect(px, py, pillW, pillH, pillH / 2, 'rgba(255,255,255,.18)', 1);
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = '#fff7fb';
  ctx.fillText(text, px + pillW / 2, py + pillH / 2 + 1);
  ctx.textBaseline = 'alphabetic';
}

async function drawTinyTropeRows(cover, x, y, w, options = {}) {
  const raw = Array.isArray(cover?.tropes) && cover.tropes.length
    ? cover.tropes
    : String(cover?.label || '')
      .split(/\s*\+\s*|\s*,\s*/)
      .filter(Boolean)
      .map((name) => ({ name }));
  const rows = raw.slice(0, 4).map(normalizeTrope);
  const color = options.color || '#050405';
  const back = options.back || '';

  let cursorY = y;
  for (let index = 0; index < rows.length; index += 1) {
    const row = rows[index];
    const font = '800 16px DM Sans, Arial';
    const lines = wrapText(row.name.toLowerCase(), w - 30, font, 2);
    const rowH = Math.max(28, lines.length * 18 + 7);
    if (back) fillRoundRect(x - 6, cursorY - 21, w + 12, rowH, 4, back);
    if (row.emojiSrc) {
      try {
        const img = await loadImage(row.emojiSrc);
        const box = 20;
        const ratio = Math.min(box / img.width, box / img.height);
        const iw = img.width * ratio;
        const ih = img.height * ratio;
        ctx.drawImage(img, x, cursorY - 16 + (box - ih) / 2, iw, ih);
      } catch {
        ctx.fillStyle = '#cf6d9d';
        ctx.font = '16px Georgia, serif';
        ctx.fillText('♡', x + 2, cursorY);
      }
    }
    ctx.textAlign = 'left';
    ctx.fillStyle = color;
    ctx.font = font;
    lines.forEach((line, lineIndex) => {
      ctx.fillText(line, x + 28, cursorY + lineIndex * 18);
    });
    cursorY += rowH + 6;
  }
}

function normalizeTrope(trope) {
  const name = typeof trope === 'string' ? trope : trope?.name || '';
  const slug = String(name)
    .toLowerCase()
    .replace(/&/g, ' and ')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');
  const emojiSrc = typeof trope === 'object' && (trope.emojiProxySrc || trope.emojiSrc)
    ? trope.emojiProxySrc || trope.emojiSrc
    : (slug ? `/api/image?url=${encodeURIComponent(`https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/images/custom-emojis/${slug}.png`)}` : '');
  return { name, slug, emojiSrc };
}

async function drawTropeSticker(trope, x, y, w, rotation = 0) {
  const item = normalizeTrope(trope);
  const h = 86;
  ctx.save();
  ctx.translate(x + w / 2, y + h / 2);
  ctx.rotate(rotation);
  ctx.translate(-x - w / 2, -y - h / 2);

  ctx.shadowColor = 'rgba(0,0,0,.34)';
  ctx.shadowBlur = 20;
  ctx.shadowOffsetY = 10;
  fillRoundRect(x, y, w, h, 14, 'rgba(255,247,251,.96)');
  ctx.shadowColor = 'transparent';
  strokeRoundRect(x, y, w, h, 14, 'rgba(255,138,199,.44)', 2);

  if (item.emojiSrc) {
    try {
      const img = await loadImage(item.emojiSrc);
      const box = 58;
      const ratio = Math.min(box / img.width, box / img.height);
      const iw = img.width * ratio;
      const ih = img.height * ratio;
      ctx.drawImage(img, x + 18 + (box - iw) / 2, y + 14 + (box - ih) / 2, iw, ih);
    } catch {
      ctx.fillStyle = '#de86bb';
      ctx.font = '28px "Apple Color Emoji", "Segoe UI Emoji", sans-serif';
      ctx.fillText('♡', x + 34, y + 54);
    }
  }

  drawTextBlock(item.name, x + 92, y + 38, w - 110, '800 20px DM Sans, Arial', 24, 2, { color: '#130b10' });
  ctx.restore();
}

function drawHighlightedQuote(text, source, x, y, w, h) {
  fillRoundRect(x, y, w, h, 18, '#f4f3f0');
  strokeRoundRect(x, y, w, h, 18, 'rgba(0,0,0,.12)', 1);

  ctx.textAlign = 'left';
  ctx.fillStyle = '#e6c953';
  ctx.font = '800 18px DM Sans, Arial';
  ctx.fillText('‹ all quotes', x + 28, y + 48);

  fillRoundRect(x + w - 82, y + 22, 54, 34, 17, 'rgba(255,255,255,.58)');
  strokeRoundRect(x + w - 82, y + 22, 54, 34, 17, 'rgba(230,201,83,.5)', 1);
  ctx.fillStyle = '#b69930';
  ctx.font = '800 15px DM Sans, Arial';
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillText('copy', x + w - 55, y + 39);
  ctx.textBaseline = 'alphabetic';

  const cleanQuote = String(text || '').replace(/^["“]+|["”]+$/g, '').trim();
  const quoteTop = y + 112;
  const sourceTop = source ? y + h - 88 : y + h - 54;
  const quote = quoteFontFor(cleanQuote, w - 190, 4, sourceTop - quoteTop);
  ctx.font = quote.font;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'alphabetic';
  const totalHeight = quote.lines.length * quote.lineHeight;
  const startY = quoteTop + Math.max(0, (sourceTop - quoteTop - totalHeight) / 2) + quote.lineHeight * .72;
  quote.lines.forEach((line, index) => {
    const textWidth = ctx.measureText(line).width;
    const lineX = x + w / 2;
    const lineY = startY + index * quote.lineHeight;
    fillRoundRect(lineX - textWidth / 2 - 8, lineY - quote.lineHeight * .72, textWidth + 16, quote.lineHeight * .68, 0, 'rgba(255,138,199,.34)');
    ctx.fillStyle = '#271d22';
    ctx.fillText(line, lineX, lineY);
  });

  if (source) {
    drawTextBlock(`— ${source}`, x + w / 2, y + h - 72, w - 170, '800 21px DM Sans, Arial', 25, 2, {
      align: 'center',
      color: '#151515',
    });
  }
}

function quoteFontFor(text, width, maxLines = 4, maxHeight = Infinity) {
  const cleanQuote = String(text || '').replace(/^["“]+|["”]+$/g, '').trim();
  const sizes = [78, 70, 62, 56, 50, 44, 39, 34, 30, 27];
  for (const size of sizes) {
    const font = `italic ${size}px Cormorant Garamond, Cormorant, Georgia`;
    const lineHeight = Math.round(size * 1.05);
    const lines = wrapText(`“${cleanQuote}”`, width, font, maxLines);
    const fullQuoteFits = lines.join(' ').replace(/[“”]/g, '').length >= cleanQuote.length - 2;
    const verticalFits = lines.length * lineHeight <= maxHeight;
    if (fullQuoteFits && verticalFits) {
      return { font, lineHeight, lines };
    }
  }
  const font = 'italic 27px Cormorant Garamond, Cormorant, Georgia';
  return { font, lineHeight: 30, lines: fitLines(`“${cleanQuote}”`, width, font, maxLines) };
}

function drawBookPageQuoteCard(text, source, x, y, w, h, options = {}) {
  fillRoundRect(x, y, w, h, 20, '#f4f3f0');
  strokeRoundRect(x, y, w, h, 20, 'rgba(255,255,255,.18)', 1);

  if (options.chrome !== false) {
    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = '#e6c953';
    ctx.font = '800 20px DM Sans, Arial';
    ctx.fillText('‹ all quotes', x + 34, y + 56);

    fillRoundRect(x + w - 98, y + 26, 64, 38, 19, 'rgba(255,255,255,.58)');
    strokeRoundRect(x + w - 98, y + 26, 64, 38, 19, 'rgba(230,201,83,.5)', 1);
    ctx.fillStyle = '#b69930';
    ctx.font = '800 16px DM Sans, Arial';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText('copy', x + w - 66, y + 45);
  }

  const cleanQuote = String(text || 'linked quote not found yet').replace(/^["“]+|["”]+$/g, '').trim();
  const quoteTop = options.chrome === false ? y + 62 : y + 126;
  const sourceTop = options.source === false ? y + h - 62 : y + h - 104;
  const quote = quoteFontFor(cleanQuote, w - 190, 4, sourceTop - quoteTop);
  ctx.font = quote.font;
  ctx.textAlign = 'center';
  ctx.textBaseline = 'alphabetic';
  const totalHeight = quote.lines.length * quote.lineHeight;
  const startY = quoteTop + Math.max(0, (sourceTop - quoteTop - totalHeight) / 2) + quote.lineHeight * .72;
  quote.lines.forEach((line, index) => {
    const textWidth = ctx.measureText(line).width;
    const lineX = x + w / 2;
    const lineY = startY + index * quote.lineHeight;
    fillRoundRect(lineX - textWidth / 2 - 10, lineY - quote.lineHeight * .72, textWidth + 20, quote.lineHeight * .68, 0, 'rgba(255,138,199,.34)');
    ctx.fillStyle = '#271d22';
    ctx.fillText(line, lineX, lineY);
  });

  if (source && options.source !== false) {
    drawTextBlock(`— ${source}`, x + w / 2, y + h - 74, w - 170, '800 21px DM Sans, Arial', 25, 2, {
      align: 'center',
      color: '#151515',
    });
  }
}

function drawPaperScrap(x, y, w, h, rotation = 0, fill = 'rgba(255,247,251,.95)') {
  ctx.save();
  ctx.translate(x + w / 2, y + h / 2);
  ctx.rotate(rotation);
  ctx.translate(-x - w / 2, -y - h / 2);
  ctx.shadowColor = 'rgba(0,0,0,.36)';
  ctx.shadowBlur = 28;
  ctx.shadowOffsetY = 18;
  fillRoundRect(x, y, w, h, 6, fill);
  ctx.shadowColor = 'transparent';
  strokeRoundRect(x, y, w, h, 6, 'rgba(255,138,199,.34)', 2);
  ctx.restore();
}

function drawScore(label, value, x, y, w) {
  ctx.textAlign = 'left';
  ctx.fillStyle = 'rgba(247,243,238,.68)';
  ctx.font = '800 17px DM Sans, Arial';
  ctx.fillText(label.toLowerCase(), x, y);
  fillRoundRect(x, y + 14, w, 10, 5, 'rgba(255,255,255,.14)');
  fillRoundRect(x, y + 14, w * value, 10, 5, '#de86bb');
}

function scoreValue(value) {
  const match = String(value || '').match(/(\d+(?:\.\d+)?)\s*\/\s*5/);
  if (match) return Math.max(0, Math.min(1, Number(match[1]) / 5));
  const number = Number(String(value || '').match(/\d+(?:\.\d+)?/)?.[0] || 0);
  return number ? Math.max(0, Math.min(1, number / 5)) : 0;
}

function drawFbStat(label, value, x, y, w, h) {
  fillRoundRect(x, y, w, h, 8, 'rgba(255,255,255,.045)');
  strokeRoundRect(x, y, w, h, 8, 'rgba(255,255,255,.12)', 1);
  drawTextBlock(label.toLowerCase(), x + 18, y + 30, w - 36, '800 16px DM Sans, Arial', 18, 1, { color: '#f3bfd5' });
  const valueFont = String(value).length > 42 ? '800 20px DM Sans, Arial' : '800 24px DM Sans, Arial';
  drawTextBlock(String(value || '').toLowerCase(), x + 18, y + 66, w - 36, valueFont, 28, Math.max(1, Math.floor((h - 54) / 28)), { color: '#fff' });
}

function drawReviewStat(stat, x, y, w, h) {
  fillRoundRect(x, y, w, h, 8, 'rgba(255,247,251,.94)');
  strokeRoundRect(x, y, w, h, 8, 'rgba(255,138,199,.34)', 2);
  drawTextBlock(String(stat.label || '').toLowerCase(), x + 18, y + 32, w - 36, '800 15px DM Sans, Arial', 17, 1, { color: '#a64f82' });
  drawTextBlock(String(stat.value || '').toLowerCase(), x + 18, y + 66, w - 36, '800 22px DM Sans, Arial', 25, 1, { color: '#130b10' });
  const value = scoreValue(stat.value);
  if (value) {
    fillRoundRect(x + 18, y + h - 24, w - 36, 8, 4, 'rgba(19,11,16,.14)');
    fillRoundRect(x + 18, y + h - 24, (w - 36) * value, 8, 4, '#de86bb');
  }
}

function statNumber(stat) {
  return Number(String(stat?.value || '').match(/\d+/)?.[0] || 0);
}

function statByLabel(stats, label) {
  return stats.find((stat) => String(stat.label || '').toLowerCase().includes(label));
}

function drawBlogStatRow(label, value, x, y, w, h, options = {}) {
  fillRoundRect(x, y, w, h, 16, 'rgba(255,255,255,.035)');
  strokeRoundRect(x, y, w, h, 16, 'rgba(255,255,255,.12)', 1);
  drawTextBlock(label, x + 18, y + 22, w - 36, '700 14px Cormorant Garamond, Georgia', 16, 1, {
    color: 'rgba(246,246,246,.58)',
  });
  drawTextBlock(String(value || '').toLowerCase(), x + 18, y + h - 22, w - 36, options.font || '800 20px DM Sans, Arial', options.lineHeight || 24, options.maxLines || 1, {
    color: '#f7f1f4',
  });
}

function drawBlogEmojiStatRow(label, emoji, count, x, y, w, h) {
  fillRoundRect(x, y, w, h, 16, 'rgba(255,255,255,.035)');
  strokeRoundRect(x, y, w, h, 16, 'rgba(255,255,255,.12)', 1);
  drawTextBlock(label, x + 18, y + 22, w - 36, '700 14px Cormorant Garamond, Georgia', 16, 1, {
    color: 'rgba(246,246,246,.58)',
  });
  ctx.textAlign = 'left';
  ctx.textBaseline = 'alphabetic';
  ctx.font = '20px "Apple Color Emoji", "Segoe UI Emoji", sans-serif';
  ctx.fillStyle = '#f7f1f4';
  const icons = emoji.repeat(Math.max(0, Math.min(5, count)));
  ctx.fillText(icons, x + 18, y + 53);
  ctx.font = '800 18px DM Sans, Arial';
  ctx.fillStyle = 'rgba(246,246,246,.74)';
  ctx.fillText('/ 5', x + 22 + ctx.measureText(icons).width, y + 53);
}

function drawBlogQuickStatsPanel(title, stats, tropes, series, x, y, w, h) {
  const panelGradient = ctx.createLinearGradient(0, y, 0, y + h);
  panelGradient.addColorStop(0, 'rgba(232,90,155,.16)');
  panelGradient.addColorStop(1, 'rgba(255,255,255,.025)');
  fillRoundRect(x, y, w, h, 24, '#171315');
  ctx.fillStyle = panelGradient;
  roundRect(x, y, w, h, 24);
  ctx.fill();
  strokeRoundRect(x, y, w, h, 24, 'rgba(232,90,155,.42)', 2);

  drawTextBlock(`${title.toLowerCase()} book stats`, x + 28, y + 70, w - 56, '500 46px Cormorant Garamond, Georgia', 48, 1, {
    color: '#ff9ccb',
  });
  ctx.strokeStyle = 'rgba(255,255,255,.12)';
  ctx.lineWidth = 2;
  ctx.beginPath();
  ctx.moveTo(x + 28, y + 90);
  ctx.lineTo(x + w - 28, y + 90);
  ctx.stroke();

  const spice = statNumber(statByLabel(stats, 'spice'));
  const darkness = statNumber(statByLabel(stats, 'darkness'));
  const tension = statByLabel(stats, 'tension')?.value || '';
  const damage = statByLabel(stats, 'emotional')?.value || '';
  const tropeText = tropes.length ? tropes.map((trope) => trope.name || trope).join(' · ') : 'dark romance · obsessive tension';

  drawBlogEmojiStatRow('spice', '🌶', spice || 4, x + 28, y + 112, 250, 70);
  drawBlogEmojiStatRow('darkness', '💀', darkness || 4, x + 294, y + 112, 250, 70);
  drawBlogStatRow('tension', tension || '3/5', x + 560, y + 112, 226, 70);
  drawBlogStatRow('tropes', tropeText, x + 28, y + 196, w - 56, 78, {
    font: '800 17px Libre Baskerville, Georgia',
    lineHeight: 21,
    maxLines: 2,
  });
  drawBlogStatRow('series', series || 'series', x + 28, y + 288, 370, 70);
  drawBlogStatRow('emotional damage', damage || '3/5', x + 414, y + 288, 372, 70);
}

function drawReviewPullQuote(quote, source, x, y, w) {
  const cleanQuote = String(quote || '').replace(/^["“]+|["”]+$/g, '').trim();
  const quoted = /[.!?]$/.test(cleanQuote) ? `“${cleanQuote}”` : `“${cleanQuote}.”`;
  ctx.strokeStyle = '#ff4fa3';
  ctx.lineWidth = 5;
  ctx.beginPath();
  ctx.moveTo(x, y);
  ctx.lineTo(x, y + 156);
  ctx.stroke();
  drawFittedTextBlock(
    quoted,
    x + 26,
    y + 46,
    w - 26,
    (size) => `italic ${size}px Libre Baskerville, Georgia`,
    [30, 27, 24, 21],
    1.5,
    3,
    118,
    { color: '#fff' }
  );
  drawTextBlock(source, x + 26, y + 142, w - 26, '700 19px Cormorant Garamond, Georgia', 22, 1, {
    color: 'rgba(246,246,246,.72)',
  });
}

async function drawOrderedSeriesBook(book, index, x, y, w, h, options = {}) {
  await drawSiteCoverCard(book, x, y, w, h, index, { rotation: index % 2 ? .035 : -.025 });
  if (options.showNumber !== false) {
    fillRoundRect(x - 18, y + 18, 50, 50, 25, 'rgba(255,138,199,.94)');
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#130b10';
    ctx.font = '800 24px DM Sans, Arial';
    ctx.fillText(String(index + 1), x + 7, y + 43);
    ctx.textBaseline = 'alphabetic';
  }
  drawTextBlock(options.caption || book?.bookTitle || book?.title || '', x - 26, y + h + 34, w + 52, '800 18px DM Sans, Arial', 21, 1, {
    align: 'center',
    color: '#fff7fb',
  });
}

async function drawSeriesTropeCard(book, x, y, w, h, number) {
  const bookTitle = (book?.bookTitle || book?.title || `book ${number}`).toLowerCase();
  const tropeSource = {
    ...book,
    label: book?.label || (Array.isArray(state.bookTropes) && state.bookTropes.length ? state.bookTropes.slice(0, 2).map((item) => item.name || item).join(' + ') : 'series romance'),
  };

  fillRoundRect(x, y, w, h, 16, 'rgba(15,15,15,.84)');
  strokeRoundRect(x, y, w, h, 16, 'rgba(232,90,155,.36)', 2);
  fillRoundRect(x + 20, y + 20, 42, 42, 21, 'rgba(255,138,199,.92)');
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = '#140b11';
  ctx.font = '800 20px DM Sans, Arial';
  ctx.fillText(String(number), x + 41, y + 42);
  ctx.textBaseline = 'alphabetic';
  drawTextBlock(bookTitle, x + 78, y + 30, w - 98, '800 21px DM Sans, Arial', 23, 2, { color: '#fff7fb' });
  drawTextBlock('tropes', x + 24, y + 92, w - 48, '700 18px Cormorant Garamond, Georgia', 20, 1, { color: '#ff9ccb' });
  await drawTinyTropeRows(tropeSource, x + 28, y + 130, w - 60, {
    color: '#fff7fb',
    back: 'rgba(255,247,251,.045)',
  });
}

async function drawSeriesBoyfriendCard(x, y, w, h, fallbackImage) {
  const hasImage = Boolean(state.boyfriendImage?.proxySrc);
  const textX = hasImage ? x + 198 : x + w / 2;
  const textW = hasImage ? w - 230 : w - 64;

  fillRoundRect(x, y, w, h, 18, 'rgba(15,15,15,.86)');
  strokeRoundRect(x, y, w, h, 18, 'rgba(232,90,155,.38)', 2);
  if (hasImage) {
    await drawPortraitCard(state.boyfriendImage, x + 22, y + 22, 150, h - 44);
  }

  drawTextBlock('fictional boyfriend', textX, y + (hasImage ? 48 : 62), textW, '700 20px Cormorant Garamond, Georgia', 22, 1, {
    align: hasImage ? 'left' : 'center',
    color: '#ff9ccb',
  });
  drawFittedTextBlock(
    state.boyfriendName || state.profileName || 'not listed',
    textX,
    y + (hasImage ? 88 : 112),
    textW,
    (size) => `500 ${size}px Cormorant Garamond, Georgia`,
    hasImage ? [42, 37, 32, 28] : [52, 46, 40, 34],
    1.05,
    2,
    hasImage ? 82 : 92,
    { align: hasImage ? 'left' : 'center', color: '#fff7fb' }
  );
  if (hasImage && state.profileLine) {
    drawFittedTextBlock(
      state.profileLine,
      x + 198,
      y + 170,
      w - 230,
      (size) => `800 ${size}px DM Sans, Arial`,
      [20, 18, 16],
      1.22,
      2,
      48,
      { color: 'rgba(247,243,238,.74)' }
    );
  }
}

function drawSmallArrowCta(text, x, y) {
  ctx.font = '800 22px DM Sans, Arial';
  ctx.textAlign = 'right';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = '#ff8ac7';
  ctx.fillText(String(text || 'read full breakdown').toLowerCase(), x, y);

  ctx.strokeStyle = '#ff8ac7';
  ctx.lineWidth = 4;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.beginPath();
  ctx.moveTo(x + 22, y);
  ctx.lineTo(x + 66, y);
  ctx.moveTo(x + 50, y - 14);
  ctx.lineTo(x + 68, y);
  ctx.lineTo(x + 50, y + 14);
  ctx.stroke();
  ctx.textBaseline = 'alphabetic';
}

function drawReadingOrderRow(book, index, x, y, w, h = 98) {
  const title = String(book?.bookTitle || book?.title || `Book ${index + 1}`);
  const author = String(book?.author || state.author || '');
  const compact = h < 90;

  fillRoundRect(x, y, w, h, 14, 'rgba(15,15,15,.82)');
  strokeRoundRect(x, y, w, h, 14, 'rgba(232,90,155,.34)', 2);
  fillRoundRect(x + 22, y + (h - 54) / 2, 54, 54, 27, 'rgba(255,138,199,.94)');
  ctx.textAlign = 'center';
  ctx.textBaseline = 'middle';
  ctx.fillStyle = '#140b11';
  ctx.font = '800 25px DM Sans, Arial';
  ctx.fillText(String(index + 1), x + 49, y + h / 2);
  ctx.textBaseline = 'alphabetic';

  drawTextBlock(title, x + 98, y + (compact ? 30 : 38), w - 128, compact ? '800 22px DM Sans, Arial' : '800 26px DM Sans, Arial', compact ? 25 : 29, 1, { color: '#fff7fb' });
  drawTextBlock(author ? `by ${author}` : `book ${index + 1}`, x + 98, y + (compact ? 58 : 70), w - 128, '700 18px Cormorant Garamond, Georgia', 21, 1, {
    color: 'rgba(247,243,238,.72)',
  });
}

async function drawSeriesReadingOrder() {
  const books = (state.seriesBooks.length ? state.seriesBooks : selectedCovers()).slice(0, 4);
  const title = state.bookSeries || state.title.replace(/\s+review\b.*$/i, '').replace(/\s+—.*$/i, '') || 'series reading order';
  const author = state.author || books[0]?.author || '';
  const visualBooks = books.slice(0, Math.min(3, books.length || 2));

  drawBackground('boyfriend');
  ctx.fillStyle = 'rgba(255,247,251,.03)';
  for (let y = 90; y < 1410; y += 58) ctx.fillRect(70, y, 860, 1);

  ctx.textAlign = 'center';
  ctx.fillStyle = '#f3bfd5';
  ctx.font = '800 20px DM Sans, Arial';
  ctx.fillText('series reading order', 500, 104);

  const titleFont = title.length > 22 ? '500 70px Cormorant, Cormorant Garamond, Georgia' : '500 88px Cormorant, Cormorant Garamond, Georgia';
  const titleHeight = drawTextBlock(title, 500, 178, 820, titleFont, title.length > 22 ? 68 : 82, 2, {
    align: 'center',
    color: '#fff7fb',
  });
  if (author) {
    drawTextBlock(`by ${author}`, 500, 190 + titleHeight, 500, '800 24px DM Sans, Arial', 30, 1, {
      align: 'center',
      color: 'rgba(247,243,238,.7)',
    });
  }

  fillRoundRect(84, 330, 832, 430, 24, 'rgba(15,15,15,.76)');
  strokeRoundRect(84, 330, 832, 430, 24, 'rgba(232,90,155,.42)', 2);
  drawTextBlock('start here', 118, 388, 270, '500 42px Cormorant Garamond, Georgia', 44, 1, { color: '#ff9ccb' });

  const coverW = visualBooks.length > 2 ? 156 : 188;
  const coverH = visualBooks.length > 2 ? 236 : 282;
  const gap = visualBooks.length > 2 ? 76 : 132;
  const totalW = visualBooks.length * coverW + (visualBooks.length - 1) * gap;
  const startX = 500 - totalW / 2;
  for (let index = 0; index < visualBooks.length; index += 1) {
    const x = startX + index * (coverW + gap);
    await drawOrderedSeriesBook(visualBooks[index], index, x, 412, coverW, coverH, {
      showNumber: false,
      caption: `book #${index + 1}`,
    });
    if (index < visualBooks.length - 1) {
      ctx.strokeStyle = '#ff8ac7';
      ctx.lineWidth = 5;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      const ax = x + coverW + gap / 2 - 30;
      const ay = 530;
      ctx.beginPath();
      ctx.moveTo(ax, ay);
      ctx.lineTo(ax + 60, ay);
      ctx.moveTo(ax + 42, ay - 22);
      ctx.lineTo(ax + 64, ay);
      ctx.lineTo(ax + 42, ay + 22);
      ctx.stroke();
    }
  }

  const rowH = books.length > 3 ? 78 : 98;
  const rowGap = rowH + 14;
  const listTop = 835;
  drawTextBlock('reading order', 92, 803, 360, '500 42px Cormorant Garamond, Georgia', 44, 1, { color: '#ff9ccb' });
  books.forEach((book, index) => drawReadingOrderRow(book, index, 92, listTop + index * rowGap, 816, rowH));

  const boyfriendY = listTop + books.length * rowGap + 24;
  await drawSeriesBoyfriendCard(92, boyfriendY, 816, books.length > 3 ? 150 : 178, books[0]);
  drawSmallArrowCta('read full breakdown', 776, 1390);
}

async function drawSeriesReviewPin() {
  const books = (state.seriesBooks.length ? state.seriesBooks : selectedCovers()).slice(0, 2);
  const title = state.bookSeries || state.title.replace(/\s+review\b.*$/i, '').replace(/\s+—.*$/i, '') || 'series review';
  const author = state.author || books[0]?.author || '';
  const line = state.reviewLine || state.subtitle || 'a quick review and reading order for the series.';
  const hasBoyfriendFeature = Boolean(state.boyfriendImage?.proxySrc || state.boyfriendName || state.profileName);
  const quickReviewY = hasBoyfriendFeature ? 1230 : 1020;
  const quickReviewH = hasBoyfriendFeature ? 116 : 170;

  drawBackground('boyfriend');
  ctx.fillStyle = 'rgba(255,247,251,.03)';
  for (let y = 84; y < 1420; y += 62) {
    ctx.fillRect(70, y, 860, 1);
  }

  ctx.textAlign = 'left';
  ctx.fillStyle = '#f3bfd5';
  ctx.font = '800 20px DM Sans, Arial';
  ctx.fillText('series review', 78, 104);

  const titleFont = title.length > 22 ? '500 70px Cormorant, Cormorant Garamond, Georgia' : '500 88px Cormorant, Cormorant Garamond, Georgia';
  const titleHeight = drawTextBlock(title, 76, 178, 760, titleFont, title.length > 22 ? 68 : 82, 2, { color: '#fff7fb' });
  if (author) {
    drawTextBlock(`by ${author}`, 82, 186 + titleHeight, 500, '800 24px DM Sans, Arial', 30, 1, { color: 'rgba(247,243,238,.7)' });
  }

  ctx.fillStyle = 'rgba(255,138,199,.12)';
  fillRoundRect(86, 330, 828, 356, 22, 'rgba(15,15,15,.76)');
  strokeRoundRect(86, 330, 828, 356, 22, 'rgba(232,90,155,.42)', 2);
  drawTextBlock('read in order', 120, 384, 300, '500 42px Cormorant Garamond, Georgia', 44, 1, { color: '#ff9ccb' });

  await drawOrderedSeriesBook(books[0], 0, 230, 406, 190, 285);
  await drawOrderedSeriesBook(books[1] || books[0], 1, 582, 406, 190, 285);

  ctx.strokeStyle = '#ff8ac7';
  ctx.lineWidth = 5;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.beginPath();
  ctx.moveTo(466, 542);
  ctx.lineTo(540, 542);
  ctx.moveTo(520, 516);
  ctx.lineTo(544, 542);
  ctx.lineTo(520, 568);
  ctx.stroke();

  await drawSeriesTropeCard(books[0], 74, 744, 410, 220, 1);
  await drawSeriesTropeCard(books[1] || books[0], 516, 744, 410, 220, 2);
  if (hasBoyfriendFeature) await drawSeriesBoyfriendCard(74, 1002, 852, 194, books[0]);

  fillRoundRect(74, quickReviewY, 852, quickReviewH, 18, 'rgba(15,15,15,.82)');
  strokeRoundRect(74, quickReviewY, 852, quickReviewH, 18, 'rgba(232,90,155,.42)', 2);
  drawTextBlock('quick review', 106, quickReviewY + 34, 260, '700 18px Cormorant Garamond, Georgia', 22, 1, { color: '#ff9ccb' });
  drawFittedTextBlock(
    line,
    106,
    quickReviewY + 72,
    780,
    (size) => `700 ${size}px Libre Baskerville, Georgia`,
    [24, 22, 20, 18],
    1.42,
    hasBoyfriendFeature ? 2 : 3,
    quickReviewH - 88,
    { color: '#f7f1f4' }
  );

  drawReviewFooterCta(1398);
}

async function drawLibrary() {
  drawBackground('library');
  drawBrand();
  drawTitle();
  fillRoundRect(92, 594, 816, 640, 24, 'rgba(255,255,255,.075)');
  ctx.strokeStyle = 'rgba(255, 138, 199, .26)';
  ctx.lineWidth = 2;
  roundRect(92, 594, 816, 640, 24);
  ctx.stroke();
  ctx.fillStyle = '#de86bb';
  ctx.fillRect(132, 985, 736, 16);
  ctx.fillRect(132, 1190, 736, 16);

  const covers = selectedCovers();
  await drawSiteCoverCard(covers[0], 162, 662, 205, 306, 0);
  await drawSiteCoverCard(covers[1], 398, 626, 205, 342, 1);
  await drawSiteCoverCard(covers[2], 633, 670, 205, 298, 2);
  await drawSiteCoverCard(covers[3], 398, 862, 205, 310, 3);
  drawCtaQuestion(1288);
  drawFooter('save this shelf');
}

async function drawRink() {
  drawBackground('rink');
  drawBrand();
  drawTitle();
  ctx.strokeStyle = 'rgba(100, 155, 170, .32)';
  ctx.lineWidth = 10;
  ctx.beginPath();
  ctx.arc(500, 980, 390, 0, Math.PI * 2);
  ctx.stroke();
  ctx.strokeStyle = 'rgba(185, 40, 70, .34)';
  ctx.beginPath();
  ctx.moveTo(112, 980);
  ctx.lineTo(888, 980);
  ctx.stroke();

  const covers = selectedCovers();
  await drawSiteCoverCard(covers[0], 130, 650, 230, 345, 0, { rotation: -.035 });
  await drawSiteCoverCard(covers[1], 385, 710, 230, 345, 1, { rotation: .025 });
  await drawSiteCoverCard(covers[2], 640, 650, 230, 345, 2, { rotation: .04 });

  drawCtaQuestion(1168);
  drawFooter('hockey romance picks');
}

async function drawStack() {
  drawBackground('stack');
  drawBrand();
  drawTitle();
  const covers = selectedCovers();

  ctx.save();
  ctx.translate(505, 950);
  ctx.rotate(-0.1);
  await drawSiteCoverCard(covers[0], -310, -320, 235, 355, 0);
  ctx.restore();

  ctx.save();
  ctx.translate(505, 930);
  ctx.rotate(0.06);
  await drawSiteCoverCard(covers[1], -115, -385, 245, 380, 1);
  ctx.restore();

  ctx.save();
  ctx.translate(505, 960);
  ctx.rotate(0.14);
  await drawSiteCoverCard(covers[2], 100, -320, 235, 355, 2);
  ctx.restore();

  drawCtaQuestion(1162);
  drawFooter('save this reading list');
}

async function drawBoyfriendProfile() {
  drawBackground('boyfriend');

  const portrait = selectedCovers()[0];
  const book = selectedCovers()[1] || selectedCovers()[0];

  ctx.textAlign = 'left';
  ctx.fillStyle = '#f3bfd5';
  ctx.font = '800 18px DM Sans, Arial';
  ctx.fillText((state.shelf || 'fictional boyfriend').toLowerCase(), 78, 112);

  ctx.fillStyle = '#fff';
  const profileTitle = state.profileName || 'the fictional boyfriend';
  const titleFont = profileTitle.length > 28 ? '500 78px Cormorant, Cormorant Garamond, Georgia' : '500 94px Cormorant, Cormorant Garamond, Georgia';
  const titleLines = wrapText(profileTitle, 840, titleFont, 3);
  ctx.font = titleFont;
  titleLines.forEach((line, index) => {
    ctx.fillText(line, 78, 196 + index * 86);
  });

  const heroY = 345;
  fillRoundRect(70, heroY, 860, 625, 8, 'rgba(15,15,15,.88)');
  strokeRoundRect(70, heroY, 860, 625, 8, 'rgba(255,255,255,.12)', 1);

  await drawPortraitCard(portrait, 106, heroY + 42, 336, 510);

  ctx.textAlign = 'left';
  ctx.fillStyle = '#f7f3ee';
  ctx.font = '400 48px Cormorant, Cormorant Garamond, Georgia';
  const bookTitle = state.bookLine || book?.bookTitle || book?.title || '';
  drawTextBlock(bookTitle, 498, heroY + 78, 380, '800 27px DM Sans, Arial', 33, 2, { color: '#fff' });

  if (state.series) {
    drawTextBlock(state.series, 498, heroY + 152, 380, '800 22px DM Sans, Arial', 28, 2, { color: '#f3bfd5' });
  }

  drawFittedTextBlock(
    state.profileLine,
    498,
    heroY + 218,
    380,
    (size) => `italic ${size}px Libre Baskerville, Georgia`,
    [24, 22, 20, 18, 16],
    1.36,
    6,
    184,
    { color: '#fff' }
  );

  const spiceText = state.profileSpice > 0 ? '🌶'.repeat(state.profileSpice) : (coverSpice(book) > 0 ? '🌶'.repeat(coverSpice(book)) : 'not listed');

  drawScore('obsession level', .92, 498, heroY + 430, 330);
  drawScore('protective streak', .86, 498, heroY + 505, 330);
  drawScore('emotional damage', .78, 498, heroY + 580, 330);

  drawFbStat('trope', state.trope || 'needs linked trope', 70, 1000, 410, 110);
  drawFbStat('book', (book?.bookTitle || state.profileBook || 'linked book'), 500, 1000, 210, 110);
  drawFbStat('spice level', spiceText, 730, 1000, 200, 110);
  drawFbStat('love language', state.loveLanguage || 'being decoded', 70, 1128, 300, 110);
  drawFbStat('would text back?', state.wouldTextBack || 'pending evidence', 390, 1128, 540, 110);

  drawCtaQuestion(1308, 'dark');
}

async function drawBookCollage() {
  const covers = selectedCovers();
  const cover = covers[0];
  const moodImage = covers.find((item, index) => index > 0 && item?.proxySrc) || cover;
  const boyfriendImage = state.boyfriendImage || covers.find((item, index) => index > 0 && /profile|boyfriend|dane/i.test(`${item?.alt || ''} ${item?.title || ''}`));
  const spiceText = state.profileSpice > 0 ? '🌶'.repeat(state.profileSpice) : (coverSpice(cover) > 0 ? '🌶'.repeat(coverSpice(cover)) : '');
  const title = state.bookTitle || cover?.bookTitle || state.title.replace(/\s+by\s+.+$/i, '').replace(/\s+—.*$/i, '');
  const author = state.author || cover?.author || '';
  const quote = state.bookQuote || 'linked quote not found yet';
  const tropes = Array.isArray(state.bookTropes) ? state.bookTropes : [];

  drawBackground('boyfriend');

  ctx.fillStyle = 'rgba(255,247,251,.03)';
  for (let y = 92; y < 1320; y += 64) {
    ctx.fillRect(60, y, 880, 1);
  }

  if (moodImage?.proxySrc) {
    ctx.save();
    ctx.globalAlpha = .72;
    await drawCover(moodImage, 52, 372, 292, 220, 1);
    ctx.restore();
    strokeRoundRect(52, 372, 292, 220, 8, 'rgba(255,255,255,.18)', 1);
  }

  ctx.fillStyle = '#fff';
  const titleFont = title.length > 24 ? '500 70px Cormorant, Cormorant Garamond, Georgia' : '500 86px Cormorant, Cormorant Garamond, Georgia';
  const titleStep = title.length > 24 ? 67 : 80;
  const titleHeight = drawTextBlock(title, 78, 190, 540, titleFont, titleStep, 3, { color: '#fff' });
  if (author) {
    drawTextBlock(`by ${author}`, 82, 190 + titleHeight + 20, 520, '800 24px DM Sans, Arial', 30, 1, { color: 'rgba(247,243,238,.72)' });
  }

  await drawSiteCoverCard(cover, 642, 128, 252, 378, 0, { rotation: .04 });

  if (boyfriendImage?.proxySrc) {
    await drawPortraitCard(boyfriendImage, 374, 320, 214, 292);
    fillRoundRect(388, 548, 186, 44, 22, 'rgba(0,0,0,.72)');
    drawTextBlock((state.boyfriendName || 'fictional boyfriend').toLowerCase(), 404, 576, 154, '800 17px DM Sans, Arial', 19, 1, { color: '#fff' });
  }

  drawHighlightedQuote(
    quote,
    `${title}${author ? ` by ${author}` : ''}`,
    88,
    602,
    824,
    352
  );

  const tropeItems = tropes.length ? tropes.slice(0, 3) : ['romance chaos'];
  await drawTropeSticker(tropeItems[0], 96, 1010, 382, 0);
  if (tropeItems[1]) await drawTropeSticker(tropeItems[1], 522, 1010, 382, 0);
  if (tropeItems[2]) await drawTropeSticker(tropeItems[2], 238, 1112, 524, 0);

  drawCtaQuestion(1308, 'dark');
}

async function drawBookQuoteCard() {
  const cover = selectedCovers()[0];
  const title = state.bookTitle || cover?.bookTitle || state.title.replace(/\s+by\s+.+$/i, '').replace(/\s+—.*$/i, '');
  const author = state.author || cover?.author || '';
  const quote = state.bookQuote || state.quotes[state.selectedQuoteIndex] || 'linked quote not found yet';
  const source = `${title}${author ? ` by ${author}` : ''}`;

  drawBackground('boyfriend');

  ctx.fillStyle = 'rgba(255,247,251,.025)';
  for (let y = 78; y < 1410; y += 62) {
    ctx.fillRect(70, y, 860, 1);
  }

  await drawBookPageQuoteCard(
    quote,
    source,
    92,
    520,
    816,
    390,
    { chrome: false }
  );
}

async function drawReviewPin() {
  const cover = selectedCovers()[0];
  const title = state.bookTitle || cover?.bookTitle || state.title.replace(/\s+review\b.*$/i, '').replace(/\s+by\s+.+$/i, '').replace(/\s+—.*$/i, '');
  const author = state.author || cover?.author || '';
  const quote = state.bookQuote || state.quotes[state.selectedQuoteIndex] || '';
  const line = state.reviewLine || state.subtitle || 'dark, messy, and worth checking the trigger warnings first.';
  const stats = state.reviewStats?.length
    ? state.reviewStats.slice(0, 4)
    : [
        { label: 'spice', value: state.profileSpice ? `${state.profileSpice}/5` : '4/5' },
        { label: 'tension', value: '3/5' },
        { label: 'darkness', value: '4/5' },
      ];

  drawBackground('boyfriend');

  ctx.fillStyle = 'rgba(255,247,251,.03)';
  for (let y = 84; y < 1420; y += 62) {
    ctx.fillRect(70, y, 860, 1);
  }

  ctx.textAlign = 'left';
  ctx.fillStyle = '#f3bfd5';
  ctx.font = '800 20px DM Sans, Arial';
  ctx.fillText('book review', 78, 104);

  ctx.fillStyle = '#fff7fb';
  const titleFont = title.length > 22 ? '500 74px Cormorant, Cormorant Garamond, Georgia' : '500 90px Cormorant, Cormorant Garamond, Georgia';
  const titleStep = title.length > 22 ? 70 : 82;
  const titleHeight = drawTextBlock(title, 76, 180, 600, titleFont, titleStep, 2, { color: '#fff7fb' });
  if (author) {
    drawTextBlock(`by ${author}`, 82, 188 + titleHeight, 500, '800 24px DM Sans, Arial', 30, 1, { color: 'rgba(247,243,238,.7)' });
  }

  await drawSiteCoverCard(cover, 690, 96, 214, 321, 0, { rotation: .035 });

  drawBlogQuickStatsPanel(title, stats, state.bookTropes, state.bookSeries, 74, 466, 852, 388);

  if (quote) {
    drawReviewPullQuote(
      quote,
      `${title}${author ? ` by ${author}` : ''}`,
      100,
      914,
      800
    );
  }

  const vibeY = quote ? 1110 : 904;
  fillRoundRect(74, vibeY, 852, 188, 18, 'rgba(15,15,15,.82)');
  strokeRoundRect(74, vibeY, 852, 188, 18, 'rgba(232,90,155,.42)', 2);
  const boyfriendImage = state.boyfriendImage || covers.find((item, index) => index > 0 && /profile|boyfriend|fictional|portrait/i.test(`${item?.alt || ''} ${item?.title || ''}`));
  const boyfriendName = state.boyfriendName || state.profileName || 'linked profile coming soon';
  if (boyfriendImage?.proxySrc) {
    await drawPortraitCard(boyfriendImage, 106, vibeY + 24, 118, 140);
  } else {
    fillRoundRect(106, vibeY + 24, 118, 140, 8, 'rgba(255,255,255,.06)');
    strokeRoundRect(106, vibeY + 24, 118, 140, 8, 'rgba(255,255,255,.16)', 1);
    drawTextBlock('♡', 144, vibeY + 106, 60, '500 54px Cormorant Garamond, Georgia', 56, 1, { color: 'rgba(255,255,255,.72)' });
  }
  drawTextBlock('fictional boyfriend', 252, vibeY + 68, 560, '800 18px DM Sans, Arial', 22, 1, { color: '#ff9ccb' });
  drawFittedTextBlock(
    boyfriendName,
    252,
    vibeY + 118,
    600,
    (size) => `500 ${size}px Cormorant, Cormorant Garamond, Georgia`,
    [56, 50, 44, 38],
    1.03,
    2,
    96,
    { color: '#fff7fb' }
  );

  drawReviewFooterCta(1398);
}

async function drawTropeMood() {
  const covers = state.covers.length ? state.covers : selectedCovers();
  const term = (state.termName || state.title || 'sports romance').replace(/\s+books\b/i, '').toLowerCase();
  const picks = covers.slice(0, 3);

  const paper = ctx.createLinearGradient(0, 0, 1000, 1500);
  paper.addColorStop(0, '#070607');
  paper.addColorStop(.58, '#0d0b0d');
  paper.addColorStop(1, '#050405');
  ctx.fillStyle = paper;
  ctx.fillRect(0, 0, 1000, 1500);

  ctx.strokeStyle = 'rgba(255,255,255,.045)';
  ctx.lineWidth = 1;
  for (let y = 80; y < 1420; y += 38) {
    ctx.beginPath();
    ctx.moveTo(58, y);
    ctx.lineTo(942, y);
    ctx.stroke();
  }

  ctx.strokeStyle = 'rgba(255,138,199,.08)';
  for (let x = 84; x < 942; x += 86) {
    ctx.beginPath();
    ctx.moveTo(x, 70);
    ctx.lineTo(x, 1426);
    ctx.stroke();
  }

  ctx.fillStyle = 'rgba(255,247,251,.025)';
  for (let i = 0; i < 80; i += 1) {
    const x = (i * 137) % 1000;
    const y = (i * 211) % 1500;
    ctx.fillRect(x, y, 1.5, 1.5);
  }

  ctx.textAlign = 'left';
  ctx.fillStyle = '#fff7fb';
  ctx.font = '112px Kaushan Script, cursive';
  ctx.fillText('books', 100, 228);

  ctx.textAlign = 'center';
  ctx.fillStyle = '#de86bb';
  ctx.font = '700 68px Cormorant Garamond, Cormorant, Georgia';
  const moodLines = wrapText("for when you're in\nthe mood for...", 780, '700 68px Cormorant Garamond, Cormorant, Georgia', 3);
  moodLines.forEach((line, index) => {
    ctx.fillText(line, 560, 328 + index * 78);
  });

  ctx.fillStyle = '#fff7fb';
  ctx.font = '800 48px DM Sans, Arial';
  ctx.fillText(term, 500, 604);

  ctx.strokeStyle = '#de86bb';
  ctx.lineWidth = 5;
  ctx.lineCap = 'round';
  ctx.beginPath();
  for (let i = 0; i < 80; i += 1) {
    const x = 514 + i * 4.4;
    const y = 646 + Math.sin(i / 5) * 9;
    if (i === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  }
  ctx.stroke();

  const count = Math.min(3, Math.max(3, picks.length || 3));
  const bookW = 218;
  const bookH = Math.round(bookW * 1.5);
  const gap = 62;
  const totalW = count * bookW + (count - 1) * gap;
  const startX = (1000 - totalW) / 2;
  const y = 750;

  for (let index = 0; index < count; index += 1) {
    const cover = picks[index] || fallbackCovers[index % fallbackCovers.length];
    const x = startX + index * (bookW + gap);
    ctx.shadowColor = 'rgba(0,0,0,.62)';
    ctx.shadowBlur = 38;
    ctx.shadowOffsetX = 18;
    ctx.shadowOffsetY = 22;
    await drawCover(cover, x, y, bookW, bookH, index);
    ctx.shadowColor = 'transparent';
    drawCoverSpiceBadge(cover, x, y, bookW);

    await drawTinyTropeRows(cover, x, y + bookH + 42, bookW + 4, {
      color: '#fff7fb',
      back: 'rgba(0,0,0,.34)',
    });
  }

  ctx.textAlign = 'center';
  ctx.fillStyle = 'rgba(255,247,251,.56)';
  ctx.font = '800 18px DM Sans, Arial';
  ctx.fillText('more tropes at bybookishbabe.com', 500, 1418);
}

function drawFooter(text) {
  ctx.textAlign = 'center';
  ctx.fillStyle = '#ffd4e9';
  ctx.font = '700 22px Libre Baskerville, Georgia';
  ctx.fillText(text.toLowerCase(), 500, 1420);
}

function selectedCovers() {
  const selected = state.selected.map((index) => state.covers[index]).filter(Boolean);
  return selected.length ? selected : fallbackCovers;
}

async function render() {
  ctx.clearRect(0, 0, 1000, 1500);
  templateName.textContent = document.querySelector('#templateInput').selectedOptions[0].textContent;
  if (state.template === 'rink') await drawRink();
  else if (state.template === 'stack') await drawStack();
  else if (state.template === 'boyfriend') await drawBoyfriendProfile();
  else if (state.template === 'book') await drawBookCollage();
  else if (state.template === 'quoteCard') await drawBookQuoteCard();
  else if (state.template === 'reviewPin') await drawReviewPin();
  else if (state.template === 'seriesReviewPin') await drawSeriesReviewPin();
  else if (state.template === 'seriesReadingOrder') await drawSeriesReadingOrder();
  else if (state.template === 'tropeMood') await drawTropeMood();
  else await drawLibrary();
}

function renderQuoteOptions() {
  const quoteField = document.querySelector('#quoteField');
  const quoteInput = document.querySelector('#quoteInput');
  if (!quoteField || !quoteInput) return;

  quoteInput.innerHTML = '';
  const quotes = state.quotes.length ? state.quotes : (state.bookQuote ? [state.bookQuote] : []);
  quoteField.classList.toggle('is-hidden', !quotes.length);

  quotes.forEach((quote, index) => {
    const option = document.createElement('option');
    option.value = String(index);
    const label = quote.length > 78 ? `${quote.slice(0, 75).trim()}...` : quote;
    option.textContent = label || `Quote ${index + 1}`;
    quoteInput.append(option);
  });

  if (state.selectedQuoteIndex >= quotes.length) state.selectedQuoteIndex = 0;
  quoteInput.value = String(state.selectedQuoteIndex);
}

function renderCoverList() {
  const coverList = document.querySelector('#coverList');
  if (!coverList) return;
  coverList.innerHTML = '';
  const covers = state.covers.length ? state.covers : fallbackCovers;
  covers.forEach((cover, index) => {
    const item = document.createElement('div');
    item.className = 'cover-item';

    const img = document.createElement('img');
    img.alt = cover.alt || cover.title || 'Cover';
    img.src = cover.proxySrc || '';
    if (!img.src) img.style.display = 'none';

    const input = document.createElement('input');
    input.value = cover.title || cover.alt || `Book ${index + 1}`;
    input.addEventListener('input', () => {
      cover.title = input.value;
      render();
    });

    const copy = document.createElement('div');
    copy.className = 'cover-copy';
    const spice = document.createElement('div');
    spice.className = 'cover-spice';
    spice.textContent = coverSpice(cover) > 0 ? `${'🌶'.repeat(coverSpice(cover))} from site` : 'spice not listed';
    copy.append(input, spice);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = state.selected.includes(index) ? '' : 'secondary';
    button.textContent = state.selected.includes(index) ? 'Using' : 'Use';
    button.addEventListener('click', () => {
      if (state.selected.includes(index)) {
        state.selected = state.selected.filter((value) => value !== index);
      } else if (state.selected.length < 4) {
        state.selected.push(index);
      } else {
        state.selected = [...state.selected.slice(1), index];
      }
      renderCoverList();
      render();
    });

    item.append(img, copy, button);
    coverList.append(item);
  });
}

function shuffleBooks() {
  const count = Math.min(4, state.covers.length || fallbackCovers.length);
  const pool = Array.from({ length: state.covers.length || fallbackCovers.length }, (_, index) => index);
  state.selected = pool.sort(() => Math.random() - 0.5).slice(0, count);
  renderCoverList();
  render();
}

async function scrape(url) {
  setStatus('Pulling blog title and covers...');
  const response = await fetch(`/api/scrape?url=${encodeURIComponent(url)}`);
  const payload = await response.json();
  if (!response.ok || payload.error) throw new Error(payload.error || 'Could not pull the blog.');

  state.title = payload.termName || payload.title || state.title;
  state.subtitle = payload.termDescription || (payload.description ? payload.description.split('.').shift() : state.subtitle);
  state.template = payload.suggestedTemplate || state.template;
  state.cta = payload.cta || templateDefaultCtas[state.template] || state.cta;
  state.profileName = payload.profileName || payload.boyfriendName || '';
  state.profileLine = payload.profileLine || '';
  state.shelf = payload.shelf || state.shelf;
  state.bookLine = payload.bookLine || payload.profileBook || '';
  state.series = payload.series || '';
  state.trope = payload.trope || '';
  state.loveLanguage = payload.loveLanguage || '';
  state.wouldTextBack = payload.wouldTextBack || '';
  state.profileSpice = Number(payload.spice || 0);
  state.bookTitle = payload.bookTitle || '';
  state.author = payload.author || '';
  state.quotes = Array.isArray(payload.quotes) ? payload.quotes : (payload.quote ? [payload.quote] : []);
  state.selectedQuoteIndex = 0;
  state.bookQuote = state.quotes[0] || payload.quote || '';
  state.boyfriendName = payload.boyfriendName || '';
  state.boyfriendImage = payload.boyfriendImage || null;
  state.bookTropes = Array.isArray(payload.tropes) ? payload.tropes : [];
  state.bookSeries = payload.bookSeries || '';
  state.seriesBooks = Array.isArray(payload.seriesBooks) ? payload.seriesBooks : [];
  state.reviewLine = payload.reviewLine || '';
  state.reviewStats = Array.isArray(payload.reviewStats) ? payload.reviewStats : [];
  state.termName = payload.termName || '';
  state.termDescription = payload.termDescription || '';
  state.covers = (payload.images || []).map((image, index) => ({
    ...image,
    title: image.bookTitle || image.alt || `Book ${index + 1}`,
  }));
  state.selected = state.covers.slice(0, 4).map((_, index) => index);

  document.querySelector('#titleInput').value = state.title;
  document.querySelector('#subtitleInput').value = state.subtitle;
  document.querySelector('#ctaInput').value = state.cta;
  document.querySelector('#profileNameInput').value = state.profileName;
  document.querySelector('#profileLineInput').value = state.profileLine;
  document.querySelector('#templateInput').value = state.template;
  renderQuoteOptions();
  renderCoverList();
  await render();
  setStatus(`Detected ${payload.pageType || 'blog'} page. Pulled ${state.covers.length} likely image option${state.covers.length === 1 ? '' : 's'}.`);
}

document.querySelector('#scrapeForm').addEventListener('submit', async (event) => {
  event.preventDefault();
  try {
    await scrape(document.querySelector('#blogUrl').value);
  } catch (error) {
    setStatus(error.message, true);
  }
});

document.querySelector('#titleInput').addEventListener('input', (event) => {
  state.title = event.target.value;
  render();
});

document.querySelector('#subtitleInput').addEventListener('input', (event) => {
  state.subtitle = event.target.value;
  render();
});

document.querySelector('#ctaInput').addEventListener('input', (event) => {
  state.cta = event.target.value;
  render();
});

document.querySelector('#profileNameInput').addEventListener('input', (event) => {
  state.profileName = event.target.value;
  render();
});

document.querySelector('#profileLineInput').addEventListener('input', (event) => {
  state.profileLine = event.target.value;
  render();
});

document.querySelector('#templateInput').addEventListener('change', (event) => {
  const previousDefault = templateDefaultCtas[state.template];
  const previousCta = state.cta;
  state.template = event.target.value;
  const reviewCtas = ['would you read it?', 'read the duet?'];
  if (!state.cta || state.cta === previousDefault || (state.template === 'seriesReadingOrder' && reviewCtas.includes(String(previousCta || '').toLowerCase()))) {
    state.cta = templateDefaultCtas[state.template] || templateDefaultCtas.library;
    document.querySelector('#ctaInput').value = state.cta;
  }
  render();
});

document.querySelector('#quoteInput')?.addEventListener('change', (event) => {
  state.selectedQuoteIndex = Number(event.target.value || 0);
  state.bookQuote = state.quotes[state.selectedQuoteIndex] || state.bookQuote;
  render();
});

document.querySelector('#shuffleBtn')?.addEventListener('click', shuffleBooks);

document.querySelector('#downloadBtn').addEventListener('click', () => {
  const link = document.createElement('a');
  const slug = state.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'blog-image';
  link.download = `${slug}-${state.template}-1000x1500.png`;
  link.href = canvas.toDataURL('image/png');
  link.click();
});

renderCoverList();
renderQuoteOptions();
render();

const initialUrl = new URLSearchParams(window.location.search).get('url');
if (initialUrl) {
  const blogUrlInput = document.querySelector('#blogUrl');
  blogUrlInput.value = initialUrl;
  scrape(initialUrl).catch((error) => setStatus(error.message, true));
}
