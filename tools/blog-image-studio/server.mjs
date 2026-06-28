import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = fileURLToPath(new URL('.', import.meta.url));
const PORT = Number(process.env.PORT || 4177);
const ALLOWED_HOSTS = new Set(['bybookishbabe.com', 'www.bybookishbabe.com']);

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.svg': 'image/svg+xml',
};

function sendJson(res, status, payload) {
  res.writeHead(status, {
    'content-type': 'application/json; charset=utf-8',
    'cache-control': 'no-store',
  });
  res.end(JSON.stringify(payload));
}

function cleanText(value = '') {
  return decodeHtml(value)
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function decodeHtml(value = '') {
  let text = String(value || '');

  for (let pass = 0; pass < 4; pass += 1) {
    const decoded = text
      .replace(/&#x([0-9a-f]+);/gi, (_, hex) => String.fromCodePoint(parseInt(hex, 16)))
      .replace(/&#(\d+);/g, (_, number) => String.fromCodePoint(parseInt(number, 10)))
      .replace(/&amp;/g, '&')
      .replace(/&quot;/g, '"')
      .replace(/&ldquo;|&rdquo;/g, '"')
      .replace(/&lsquo;|&rsquo;/g, "'")
      .replace(/&#039;/g, "'")
      .replace(/&apos;/g, "'")
      .replace(/&nbsp;/g, ' ')
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>');

    if (decoded === text) return decoded;
    text = decoded;
  }

  return text;
}

function absoluteUrl(value, baseUrl) {
  try {
    return new URL(value, baseUrl).href;
  } catch {
    return '';
  }
}

function fullSizeUploadUrl(value) {
  return String(value || '').replace(/-\d+x\d+(?=\.(?:png|jpe?g|webp)(?:\?|$))/i, '');
}

function proxiedImageUrl(src) {
  return `/api/image?url=${encodeURIComponent(src)}`;
}

function slugify(value = '') {
  return cleanText(value)
    .toLowerCase()
    .replace(/&/g, ' and ')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '');
}

function tropeEmojiUrl(trope) {
  const slug = slugify(trope);
  return slug ? `https://bybookishbabe.com/wp-content/themes/wordpress-theme/assets/images/custom-emojis/${slug}.png` : '';
}

function normalizeTrope(trope) {
  const name = cleanText(typeof trope === 'string' ? trope : trope?.name || '');
  const emojiSrc = trope?.emojiSrc || tropeEmojiUrl(name);
  return {
    name,
    slug: slugify(name),
    emojiSrc,
    emojiProxySrc: emojiSrc ? proxiedImageUrl(emojiSrc) : '',
  };
}

function bookTropeData(book) {
  return Array.isArray(book?.tropes) ? book.tropes.filter(Boolean).slice(0, 4).map(normalizeTrope) : [];
}

function bookLabel(book, fallback = '') {
  if (Array.isArray(book?.tropes) && book.tropes.length) return book.tropes.slice(0, 2).join(' + ');
  return cleanText(book?.shelf_name || book?.shelf || fallback);
}

function detectPageType(parsed, html) {
  const path = parsed.pathname.replace(/\/+$/, '/');
  if (/-review\/$/i.test(path) || /"@type"\s*:\s*"Review"/i.test(html)) {
    return 'review';
  }
  if (/^\/series\/[^/]+\/$/i.test(path) || /bbb-seriesOrderPage/i.test(html)) {
    return 'series';
  }
  if (/^\/fictional-boyfriends\/[^/]+\/$/i.test(path)) {
    return 'boyfriend';
  }
  if (/^\/books\/[^/]+\/$/i.test(path)) {
    return 'book';
  }
  if (/bbb-fb-single/i.test(html)) {
    return 'boyfriend';
  }
  if (/sss-book-page/i.test(html)) {
    return 'book';
  }
  if (/sss-tropeTop|the trope archive|the society shelves/i.test(html)) {
    return 'trope';
  }
  return 'blog';
}

function extractBoyfriendName(title) {
  return cleanText(title)
    .replace(/\s+fictional boyfriend profile.*$/i, '')
    .replace(/\s+\|\s+.*$/i, '')
    .trim();
}

function extractBoyfriendBook(description) {
  const match = cleanText(description).match(/\bfrom\s+(.+?)(?::|,|\s+-|\.)/i);
  return match ? match[1].trim() : '';
}

function boyfriendProfileLine(description) {
  const text = cleanText(description);
  if (!text) return 'morally gray, obsessive, standards-ruining';
  return text
    .replace(/^meet\s+[^:]+:\s*/i, '')
    .replace(/\s*his book boyfriend tropes.*$/i, '')
    .replace(/\s*with his book boyfriend tropes.*$/i, '')
    .trim() || 'morally gray, obsessive, standards-ruining';
}

function firstMatch(value, regex) {
  return (value.match(regex) || [])[1] || '';
}

function attrValue(html = '', name = '') {
  const pattern = new RegExp(`\\b${name}=["']([^"']*)["']`, 'i');
  return cleanText(firstMatch(html, pattern));
}

function extractClassBlock(html, className, tag = '[a-z0-9]+') {
  const re = new RegExp(`<(${tag})\\b[^>]*class=["'][^"']*${className}[^"']*["'][^>]*>([\\s\\S]*?)<\\/\\1>`, 'i');
  return (html.match(re) || [])[2] || '';
}

function extractSectionBlock(html, className) {
  return extractClassBlock(html, className, 'section');
}

function extractFirstImage(block, baseUrl) {
  const src =
    firstMatch(block, /\bdata-pin-media=["']([^"']+)["']/i) ||
    firstMatch(block, /<img\b[^>]*(?:src|data-src|data-lazy-src)=["']([^"']+)["']/i);
  return absoluteUrl(src, baseUrl);
}

function extractStats(html) {
  const statsBlock = extractSectionBlock(html, 'bbb-fb-stats');
  const stats = {};
  const statRe = /<span[^>]*>([\s\S]*?)<\/span>[\s\S]*?<strong[^>]*>([\s\S]*?)<\/strong>/gi;
  let match;

  while ((match = statRe.exec(statsBlock))) {
    const label = cleanText(match[1]).toLowerCase();
    const value = cleanText(match[2]);
    if (label) stats[label] = value;
  }

  return stats;
}

function extractJsonLdItems(html) {
  const scripts = Array.from(html.matchAll(/<script[^>]+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi));
  const items = [];
  for (const script of scripts) {
    try {
      const data = JSON.parse(script[1]);
      items.push(...(Array.isArray(data?.['@graph']) ? data['@graph'] : [data]));
    } catch {
      // Ignore malformed/escaped schema blocks.
    }
  }
  return items;
}

function typeIncludes(item, type) {
  const value = item?.['@type'];
  return Array.isArray(value) ? value.includes(type) : value === type;
}

function extractSchemaBook(html) {
  const graph = extractJsonLdItems(html);
  const directBook = graph.find((item) => typeIncludes(item, 'Book'));
  if (directBook) return directBook;
  const review = graph.find((item) => typeIncludes(item, 'Review') && item?.itemReviewed);
  if (review?.itemReviewed && typeIncludes(review.itemReviewed, 'Book')) return review.itemReviewed;
  return null;
}

function extractSchemaReview(html) {
  return extractJsonLdItems(html).find((item) => typeIncludes(item, 'Review')) || null;
}

function extractSchemaItemListBooks(html) {
  const scripts = Array.from(html.matchAll(/<script[^>]+type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi));
  const books = [];

  for (const script of scripts) {
    try {
      const data = JSON.parse(script[1]);
      const graph = Array.isArray(data?.['@graph']) ? data['@graph'] : [data];
      const lists = graph.filter((item) => item?.['@type'] === 'ItemList' && Array.isArray(item?.itemListElement));
      for (const list of lists) {
        for (const entry of list.itemListElement) {
          const item = entry?.item;
          if (!item || item?.['@type'] !== 'Book') continue;
          const url = item.url || item['@id'] || '';
          const image = Array.isArray(item.image) ? item.image[0] : item.image;
          const properties = Array.isArray(item.additionalProperty) ? item.additionalProperty : [];
          const spiceProperty = properties.find((property) => /spice level/i.test(property?.name || ''));
          const spice = Number(String(spiceProperty?.value || '').match(/\d+/)?.[0] || 0);

          books.push({
            title: cleanText(item.name || ''),
            slug: String(url).replace(/\/+$/, '').split('/').pop() || slugify(item.name || ''),
            author: cleanText(item.author?.name || ''),
            cover_url: fullSizeUploadUrl(image || ''),
            tropes: Array.isArray(item.genre) ? item.genre.map(cleanText).filter(Boolean) : [],
            spice_level: spice,
            series_name: cleanText(item.isPartOf?.name || ''),
            series_url: item.isPartOf?.url || item.isPartOf?.['@id'] || '',
            position: Number(entry.position || 0),
          });
        }
      }
    } catch {
      // Ignore malformed/escaped schema blocks.
    }
  }

  return books;
}

function seriesComparableTitle(value = '') {
  return normalizeTitleKey(
    cleanText(value)
      .replace(/\b(?:series|duet|trilogy|saga)\b/gi, '')
      .trim()
  );
}

function collapseConnectedSeries(books) {
  const output = [];
  const groups = new Map();

  books.forEach((book, index) => {
    const key = slugify(book?.series_url || book?.series_handle || book?.series_name || '');
    if (!key) {
      output.push({ ...book, __originalIndex: index });
      return;
    }
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key).push({ ...book, __originalIndex: index });
  });

  for (const group of groups.values()) {
    const sorted = [...group].sort((a, b) => (a.position || 9999) - (b.position || 9999));
    const seriesTitle = seriesComparableTitle(sorted[0]?.series_name || '');
    const exactTitle = sorted.find((book) => seriesTitle && normalizeTitleKey(book.title) === seriesTitle);
    const bookOne = sorted.find((book) => {
      const seriesNumber = Number.parseFloat(String(book?.series_number || '').replace(/[^0-9.]/g, ''));
      return Number.isFinite(seriesNumber) && seriesNumber <= 1;
    });
    output.push(exactTitle || bookOne || sorted[0]);
  }

  return output.sort((a, b) => (a.__originalIndex || 0) - (b.__originalIndex || 0));
}

function bookFromUrl(parsed, books) {
  const slug = parsed.pathname.replace(/\/+$/, '').split('/').pop();
  return books.find((book) => book?.slug === slug) || null;
}

function taxonomySlugFromPath(parsed) {
  const slug = parsed.pathname.replace(/\/+$/, '').split('/').filter(Boolean).pop() || '';
  return slugify(slug.replace(/-books$|-book$/i, ''));
}

function extractTaxonomyProfile(html, parsed, title, description, books, images) {
  const header = extractClassBlock(html, 'sss-trope__header', 'div');
  const rawName =
    cleanText(firstMatch(header, /<h1[^>]*>([\s\S]*?)<\/h1>/i)) ||
    cleanText(title).replace(/\s+[-|]\s+.*$/i, '');
  const termName = cleanText(rawName)
    .replace(/\s+books\b.*$/i, '')
    .replace(/[^\p{L}\p{N}\s&/+-]+$/u, '')
    .trim() || taxonomySlugFromPath(parsed).replace(/-/g, ' ');
  const slug = taxonomySlugFromPath(parsed) || slugify(termName);
  const desc = cleanText(extractClassBlock(header, 'sss-trope__desc', 'p')) || description;
  const schemaBooks = extractSchemaItemListBooks(html);
  const sourceBooks = schemaBooks.length ? schemaBooks : books;
  const matches = sourceBooks.filter((book) => {
    const shelfValues = [book?.shelf, book?.shelf_name].map(slugify);
    const tropeValues = Array.isArray(book?.tropes) ? book.tropes.map(slugify) : [];
    const values = [...shelfValues, ...tropeValues].filter(Boolean);
    const exact = values.includes(slug);
    const broadSports = 'sports-romance' === slug && values.some((value) => /sports-romance|hockey-romance|baseball-romance|football-romance/.test(value));
    const broadGenre = slug.endsWith('-romance') && values.some((value) => value.includes(slug.replace(/-romance$/, '')));
    return exact || broadSports || broadGenre;
  });
  const seriesCollapsedBooks = collapseConnectedSeries(matches.length ? matches : sourceBooks);
  const seriesSafeBooks = seriesCollapsedBooks.filter((book) => {
    const seriesNumber = Number.parseFloat(String(book?.series_number || '').replace(/[^0-9.]/g, ''));
    return !Number.isFinite(seriesNumber) || seriesNumber <= 1 || Boolean(book?.standalone);
  });
  const selectedBooks = seriesSafeBooks.slice(0, 12);
  const bookImages = selectedBooks
    .filter((book) => book?.cover_url)
    .map((book) => ({
      src: fullSizeUploadUrl(book.cover_url),
      proxySrc: proxiedImageUrl(fullSizeUploadUrl(book.cover_url)),
      alt: `${book.title} book cover`,
      width: 0,
      height: 0,
      score: 32,
      bookTitle: book.title,
      author: book.author || '',
      spice: Number(book.spice_level || 0),
      tropes: Array.isArray(book.tropes) ? book.tropes.slice(0, 4).map(normalizeTrope) : [],
      label: Array.isArray(book.tropes) && book.tropes.length
        ? book.tropes.slice(0, 2).join(' + ')
        : (book.shelf_name || book.shelf || termName),
    }));

  return {
    termName,
    termSlug: slug,
    termDescription: desc,
    images: bookImages.length ? bookImages : images,
  };
}

async function extractSeriesProfile(html, parsed, title) {
  const header = extractClassBlock(html, 'bbb-seriesOrderPage__header', 'header');
  const rawTitle =
    cleanText(firstMatch(header, /<h1[^>]*>([\s\S]*?)<\/h1>/i)) ||
    cleanText(title);
  let seriesName = cleanText(rawTitle)
    .replace(/\s+series\s+reading\s+order\b.*$/i, '')
    .replace(/\s+reading\s+order\b.*$/i, '')
    .replace(/\s+—.*$/i, '')
    .trim();
  const author = cleanText(extractClassBlock(header, 'bbb-seriesOrderPage__author', 'p'))
    .replace(/^by\s+/i, '') ||
    cleanText(title.match(/\bby\s+(.+?)(?:\s+—|\||$)/i)?.[1] || '');
  const blurb = cleanText(extractClassBlock(html, 'bbb-seriesOrderPage__blurb', 'p'));
  const rows = [...html.matchAll(/<article\b[^>]*class=["'][^"']*bbb-seriesOrderPage__bookRow[^"']*["'][\s\S]*?<\/article>/gi)]
    .map((match) => match[0]);
  seriesName = attrValue(rows[0] || '', 'data-series-name') || seriesName;

  const seriesBooks = rows.map((row, index) => {
    const cover = fullSizeUploadUrl(attrValue(row, 'data-cover') || extractFirstImage(row, parsed.href));
    const rawTropes = attrValue(row, 'data-tropes-display') || attrValue(row, 'data-tropes');
    const tropes = rawTropes
      .split(/\s*,\s*|\s*·\s*|\s*\+\s*/)
      .map(cleanText)
      .filter(Boolean)
      .slice(0, 4)
      .map(normalizeTrope);
    const shelf = attrValue(row, 'data-shelf');
    const spice = Number(attrValue(row, 'data-spice') || 0);
    const bookTitle = attrValue(row, 'data-title') || cleanText(firstMatch(row, /<h3[^>]*>[\s\S]*?<a[^>]*>([\s\S]*?)<\/a>/i));
    const bookAuthor = attrValue(row, 'data-author') || author;
    const slug = attrValue(row, 'data-handle') || slugify(bookTitle);

    return {
      src: cover,
      proxySrc: cover ? proxiedImageUrl(cover) : '',
      alt: `${bookTitle} book cover`,
      width: 0,
      height: 0,
      score: 40,
      bookTitle,
      title: bookTitle,
      author: bookAuthor,
      slug,
      spice: Number.isFinite(spice) ? Math.max(0, Math.min(5, spice)) : 0,
      tropes,
      label: tropes.length ? tropes.slice(0, 2).map((trope) => trope.name).join(' + ') : shelf,
      seriesName,
      seriesNumber: Number(attrValue(row, 'data-series-number') || index + 1),
      mini: attrValue(row, 'data-mini'),
    };
  }).filter((book) => book.bookTitle && book.src);

  const details = await Promise.all(seriesBooks.map((item) => fetchBookPageDetails(parsed.origin, item)));
  const enrichedBooks = seriesBooks
    .map((book, index) => ({ ...book, boyfriendName: details[index]?.boyfriendName || '' }))
    .sort((a, b) => (a.seriesNumber || 999) - (b.seriesNumber || 999));

  return {
    seriesName,
    author,
    blurb,
    seriesBooks: enrichedBooks,
    boyfriendName: enrichedBooks.find((book) => book.boyfriendName)?.boyfriendName || '',
    images: enrichedBooks,
  };
}

function uniqueQuotes(quotes) {
  const seen = new Set();
  return quotes
    .map((quote) => cleanText(quote).replace(/^["“]+|["”]+$/g, '').trim())
    .filter((quote) => {
      const key = quote.toLowerCase();
      if (!quote || seen.has(key)) return false;
      seen.add(key);
      return true;
    });
}

function extractRenderedBookQuotes(html) {
  const quotes = [];
  const copyQuote = cleanText(firstMatch(html, /data-bbb-copy-quote=["']([^"']+)["']/i));
  if (copyQuote) quotes.push(copyQuote);

  const quoteBlocks = Array.from(html.matchAll(/<blockquote\b[^>]*class=["'][^"']*sss-book-page__bookQuote[^"']*["'][^>]*>([\s\S]*?)<\/blockquote>/gi));
  for (const block of quoteBlocks) {
    const quoteText = cleanText(firstMatch(block[1], /<p[^>]*>([\s\S]*?)<\/p>/i));
    if (quoteText) quotes.push(quoteText);
  }

  return uniqueQuotes(quotes);
}

function extractRenderedBookQuote(html) {
  return extractRenderedBookQuotes(html)[0] || '';
}

function firstParagraphAfterHeading(html, labels) {
  const labelPattern = labels.map((label) => label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
  const re = new RegExp(`<h[2-4][^>]*>\\s*(?:[^<]*\\b)?(?:${labelPattern})(?:\\b[^<]*)?<\\/h[2-4]>([\\s\\S]{0,2200})`, 'i');
  const block = (html.match(re) || [])[1] || '';
  return cleanText(
    firstMatch(block, /<p[^>]*>([\s\S]*?)<\/p>/i) ||
    firstMatch(block, /<li[^>]*>([\s\S]*?)<\/li>/i)
  );
}

function extractReviewQuote(html) {
  const blockquote = cleanText(firstMatch(html, /<blockquote\b[^>]*>([\s\S]*?)<\/blockquote>/i));
  if (blockquote) return blockquote.replace(/^["“]+|["”]+$/g, '').trim();

  const quotePatterns = [
    /(?:favorite|best|quote)[^<]{0,80}<[^>]*>\s*["“]([^"”]{24,260})["”]/i,
    /data-bbb-copy-quote=["']([^"']+)["']/i,
  ];
  for (const pattern of quotePatterns) {
    const quote = cleanText(firstMatch(html, pattern)).replace(/^["“]+|["”]+$/g, '').trim();
    if (quote) return quote;
  }

  return '';
}

function reviewStatsFromProperties(properties = []) {
  return properties
    .map((property) => ({
      label: cleanText(property?.name || '').replace(/\s+level|\s+score/gi, ''),
      value: cleanText(property?.value || ''),
    }))
    .filter((item) => item.label && item.value)
    .slice(0, 5);
}

function pagePlainText(html = '') {
  return cleanText(
    String(html)
      .replace(/<script[\s\S]*?<\/script>/gi, ' ')
      .replace(/<style[\s\S]*?<\/style>/gi, ' ')
  );
}

function extractBookBoyfriendText(html = '') {
  const text = pagePlainText(html);
  return cleanText(
    text.match(/book boyfriend:\s*([\s\S]*?)(?:\s+read free|\s+buy on amazon|\s+verdict|\s+vibe|$)/i)?.[1] || ''
  ).replace(/^[🖤\s]+/u, '');
}

function extractReviewSeriesTropeGroups(html = '') {
  const text = pagePlainText(html);
  const marker = text.match(/tropes in (?:the )?[\s\S]{0,80}? duet\s+([\s\S]*?)(?:similar vibes|reader fit|content warnings|is .+ worth it\?|$)/i)?.[1] || '';
  if (!marker) return [];

  const bookOne = [];
  const bookTwo = [];
  const shared = [];
  const first = marker.match(/([a-z][a-z\s'’&-]+?)\s*\(book\s*1\)/i)?.[1];
  const second = marker.match(/([a-z][a-z\s'’&-]+?)\s*\(book\s*2\)/i)?.[1];
  if (first) bookOne.push(first);
  if (second) bookTwo.push(second);

  [
    'paranormal romance',
    'morally gray characters',
    'high tension dynamic',
    "shouldn't but do anyway",
    'shouldn’t but do anyway',
  ].forEach((trope) => {
    if (new RegExp(trope.replace(/[’']/g, "['’]"), 'i').test(marker)) shared.push(trope.replace('shouldn’t', "shouldn't"));
  });

  return [
    [...bookOne, ...shared].filter(Boolean).slice(0, 4).map(normalizeTrope),
    [...bookTwo, ...shared].filter(Boolean).slice(0, 4).map(normalizeTrope),
  ];
}

async function fetchBookPageDetails(origin, book) {
  const slug = book?.slug || slugify(book?.bookTitle || book?.title || '');
  if (!slug) return {};

  try {
    const response = await fetch(new URL(`/books/${slug}/`, origin).href, {
      headers: {
        'user-agent': 'BBB Image Studio Local Prototype',
        accept: 'text/html,application/xhtml+xml',
      },
    });
    if (!response.ok) return {};
    const html = await response.text();
    return {
      boyfriendName: extractBookBoyfriendText(html),
    };
  } catch {
    return {};
  }
}

function condenseReviewLine(value = '') {
  const text = cleanText(value);
  if (text.length <= 150) return text;
  const sentences = text.match(/[^.!?]+[.!?]+/g) || [];
  const firstTwo = sentences.slice(0, 2).join(' ').trim();
  if (firstTwo && firstTwo.length <= 170) return firstTwo;
  const first = sentences[0]?.trim();
  if (first) return first;
  return `${text.slice(0, 145).replace(/\s+\S*$/, '')}.`;
}

function reviewSeriesBooksFor(bookTitle, author, coverImage, images) {
  const seen = new Set();
  const normalizedAuthor = normalizeTitleKey(author);
  return [coverImage, ...images]
    .filter(Boolean)
    .filter((image) => {
      const src = image?.src || '';
      if (!src || seen.has(src)) return false;
      seen.add(src);
      if (normalizeTitleKey(image.bookTitle || image.alt) === normalizeTitleKey(bookTitle)) return true;
      return normalizedAuthor && normalizeTitleKey(image.author || image.alt).includes(normalizedAuthor);
    })
    .slice(0, 4);
}

async function extractReviewProfile(html, parsed, title, description, books, images) {
  const review = extractSchemaReview(html);
  const book = review?.itemReviewed && typeIncludes(review.itemReviewed, 'Book')
    ? review.itemReviewed
    : extractSchemaBook(html);
  const bookUrl = book?.url || book?.['@id'] || '';
  const slug = bookUrl ? new URL(bookUrl, parsed.href).pathname.replace(/\/+$/, '').split('/').pop() : '';
  const libraryBook = books.find((item) => item?.slug === slug) || null;
  const image = fullSizeUploadUrl(libraryBook?.cover_url || (Array.isArray(book?.image) ? book.image[0] : book?.image) || extractMeta(html, 'og:image'));
  const bookTitle = libraryBook?.title || cleanText(book?.name || title).replace(/\s+review\b.*$/i, '').replace(/\s+by\s+.+$/i, '').replace(/\s+—.*$/i, '');
  const author = libraryBook?.author || cleanText(book?.author?.name || title.match(/\bby\s+(.+?)(?:\s+—|\(|$)/i)?.[1] || '');
  const properties = Array.isArray(book?.additionalProperty) ? book.additionalProperty : [];
  const stats = reviewStatsFromProperties(properties);
  const tropes = Array.isArray(libraryBook?.tropes)
    ? libraryBook.tropes
    : (Array.isArray(book?.genre) ? book.genre : []);
  const vibeLine = condenseReviewLine(
    firstParagraphAfterHeading(html, ['vibe', 'verdict', 'worth it', 'final thoughts']) ||
    cleanText(review?.description || review?.reviewBody || description)
  );
  let quotes = [];
  let quote = '';

  quotes = uniqueQuotes([
    ...(await fetchLinkedBookQuotes(parsed.origin, libraryBook?.id || 0)),
  ]);
  quote = quotes[0] || '';

  if (bookUrl) {
    try {
      const bookResponse = await fetch(new URL(bookUrl, parsed.href).href, {
        headers: {
          'user-agent': 'BBB Image Studio Local Prototype',
          accept: 'text/html,application/xhtml+xml',
        },
      });
      if (bookResponse.ok) {
        const bookHtml = await bookResponse.text();
        quotes = uniqueQuotes([...quotes, ...extractRenderedBookQuotes(bookHtml)]);
        quote = quotes[0] || '';
      }
    } catch {
      // Quote is optional for review pins.
    }
  }

  if (!quotes.length) {
    quote = extractReviewQuote(html);
    quotes = quote ? [quote] : [];
  }

  const coverImage = image
    ? {
        src: image,
        proxySrc: proxiedImageUrl(image),
        alt: `${bookTitle} book cover`,
        width: 0,
        height: 0,
        score: 36,
        bookTitle,
        author,
        spice: Number(String(stats.find((item) => /spice/i.test(item.label))?.value || '').match(/\d+/)?.[0] || libraryBook?.spice_level || 0),
        tropes: bookTropeData(libraryBook),
        label: bookLabel(libraryBook, Array.isArray(book?.genre) ? book.genre[0] : ''),
      }
    : images[0];
  const seriesTropeGroups = extractReviewSeriesTropeGroups(html);
  const seriesBooks = reviewSeriesBooksFor(bookTitle, author, coverImage, images);
  const seriesBookDetails = await Promise.all(seriesBooks.map((item) => fetchBookPageDetails(parsed.origin, item)));
  const enrichedSeriesBooks = seriesBooks.map((item, index) => {
    const detail = seriesBookDetails[index] || {};
    const parsedTropes = seriesTropeGroups[index] || [];
    const tropesForBook = Array.isArray(item?.tropes) && item.tropes.length ? item.tropes : parsedTropes;
    return {
      ...item,
      tropes: tropesForBook,
      label: tropesForBook.length ? tropesForBook.slice(0, 2).map((trope) => trope.name).join(' + ') : item.label,
      boyfriendName: detail.boyfriendName || '',
    };
  });
  const boyfriendCandidates = [bookTitle, ...enrichedSeriesBooks.map((item) => item?.bookTitle || item?.title || '')].filter(Boolean);
  let linkedBoyfriend = null;
  for (const candidate of boyfriendCandidates) {
    linkedBoyfriend = await fetchBookBoyfriendProfile(parsed.origin, candidate, books);
    if (linkedBoyfriend?.profileImage) break;
  }
  const boyfriendName = linkedBoyfriend?.profileName || enrichedSeriesBooks.find((item) => item.boyfriendName)?.boyfriendName || '';

  return {
    bookTitle,
    author,
    series: cleanText(book?.isPartOf?.name || ''),
    tropes: tropes.filter(Boolean).slice(0, 4).map(normalizeTrope),
    quote,
    quotes,
    vibeLine,
    reviewStats: stats,
    coverImage,
    seriesBooks: enrichedSeriesBooks,
    boyfriendName,
    boyfriendImage: linkedBoyfriend?.profileImage || null,
  };
}

function quoteTextFromRestItem(item) {
  return cleanText(item?.content?.rendered || item?.title?.rendered || '')
    .replace(/^["“]+|["”]+$/g, '')
    .trim();
}

async function fetchLinkedBookQuotes(origin, bookId) {
  if (!bookId) return [];

  const linkedQuotes = [];

  try {
    const response = await fetch(new URL('/wp-json/wp/v2/bbb_quote?per_page=80', origin).href, {
      headers: {
        'user-agent': 'BBB Image Studio Local Prototype',
        accept: 'application/json',
      },
    });
    if (!response.ok) return [];
    const quotes = await response.json();
    if (!Array.isArray(quotes)) return [];

    for (const quote of quotes) {
      if (!quote?.link) continue;
      try {
        const redirect = await fetch(quote.link, {
          method: 'HEAD',
          redirect: 'manual',
          headers: { 'user-agent': 'BBB Image Studio Local Prototype' },
        });
        const location = redirect.headers.get('location') || '';
        if (new RegExp(`[?&]p=${bookId}(?:&|$)`).test(location)) {
          const text = quoteTextFromRestItem(quote);
          if (text) linkedQuotes.push(text);
        }
      } catch {
        // Keep looking through public quote posts.
      }
    }
  } catch {
    return [];
  }

  return uniqueQuotes(linkedQuotes);
}

async function fetchLinkedBookQuote(origin, bookId) {
  return (await fetchLinkedBookQuotes(origin, bookId))[0] || '';
}

async function fetchBookBoyfriendProfile(origin, bookTitle, books) {
  if (!bookTitle) return null;

  try {
    const searchUrl = new URL('/wp-json/wp/v2/search', origin);
    searchUrl.searchParams.set('search', bookTitle);
    searchUrl.searchParams.set('per_page', '20');
    const response = await fetch(searchUrl.href, {
      headers: {
        'user-agent': 'BBB Image Studio Local Prototype',
        accept: 'application/json',
      },
    });
    if (!response.ok) return null;
    const results = await response.json();
    const match = Array.isArray(results)
      ? results.find((item) => item?.subtype === 'bbb_boyfriend' && item?.url)
      : null;
    if (!match) return null;

    const profileUrl = new URL(match.url);
    const profileResponse = await fetch(profileUrl.href, {
      headers: {
        'user-agent': 'BBB Image Studio Local Prototype',
        accept: 'text/html,application/xhtml+xml',
      },
    });
    if (!profileResponse.ok) return null;

    const profileHtml = await profileResponse.text();
    const profileImages = attachBookData(extractImages(profileHtml, profileUrl.href), books);
    return extractBoyfriendProfile(
      profileHtml,
      profileUrl,
      extractTitle(profileHtml),
      extractMeta(profileHtml, 'og:description'),
      profileImages
    );
  } catch {
    return null;
  }
}

async function extractBookProfile(html, parsed, title, description, books, images) {
  const schema = extractSchemaBook(html);
  const libraryBook = bookFromUrl(parsed, books);
  const schemaImage = Array.isArray(schema?.image) ? schema.image[0] : schema?.image;
  const cover = fullSizeUploadUrl(libraryBook?.cover_url || schemaImage || extractMeta(html, 'og:image'));
  const spice = Number(libraryBook?.spice_level || String(description).match(/spice\s+(\d)\/5/i)?.[1] || 0);
  const bookTitle = libraryBook?.title || schema?.name || cleanText(title).replace(/\s+by\s+.+$/i, '').replace(/\s+—.*$/i, '');
  const author = libraryBook?.author || schema?.author?.name || cleanText(title).match(/\bby\s+(.+?)(?:\s+—|$)/i)?.[1] || '';
  const series = schema?.isPartOf?.name || '';
  const tropes = Array.isArray(libraryBook?.tropes)
    ? libraryBook.tropes
    : (Array.isArray(schema?.genre) ? schema.genre : []);
  const quotes = uniqueQuotes([
    ...extractRenderedBookQuotes(html),
    ...(await fetchLinkedBookQuotes(parsed.origin, libraryBook?.id || 0)),
  ]);
  const quote = quotes[0] || '';
  const linkedBoyfriend = await fetchBookBoyfriendProfile(parsed.origin, bookTitle, books);
  const boyfriendName =
    libraryBook?.boyfriend_name ||
    libraryBook?.boyfriend ||
    linkedBoyfriend?.profileName ||
    cleanText(firstMatch(html, /book boyfriend:[\s\S]*?<strong[^>]*>([\s\S]*?)<\/strong>/i)) ||
    '';
  const coverImage = cover
    ? {
        src: cover,
        proxySrc: proxiedImageUrl(cover),
        alt: `${bookTitle} book cover`,
        width: 0,
        height: 0,
        score: 30,
        bookTitle,
        author,
        spice: Number.isFinite(spice) ? Math.max(0, Math.min(5, spice)) : 0,
      }
    : images[0];

  return {
    bookTitle,
    author,
    series,
    tropes: tropes.filter(Boolean).slice(0, 4).map(normalizeTrope),
    quote,
    quotes,
    boyfriendName,
    boyfriendImage: linkedBoyfriend?.profileImage || null,
    spice: Number.isFinite(spice) ? Math.max(0, Math.min(5, spice)) : 0,
    coverImage,
  };
}

function extractBoyfriendProfile(html, parsed, title, description, images) {
  const hero = extractSectionBlock(html, 'bbb-fb-profile-hero');
  const heroImageBlock = extractClassBlock(hero, 'bbb-fb-profile-hero__image', 'div');
  const heroCopy = extractClassBlock(hero, 'bbb-fb-profile-hero__copy', 'div');
  const stats = extractStats(html);
  const profileImage = extractFirstImage(heroImageBlock, parsed.href);
  const profileName = cleanText(firstMatch(heroCopy, /<h1[^>]*>([\s\S]*?)<\/h1>/i)) || extractBoyfriendName(title);
  const profileLine =
    cleanText(extractClassBlock(heroCopy, 'bbb-fb-profile-hero__verdict', 'p')) ||
    boyfriendProfileLine(description);
  const shelf = cleanText(extractClassBlock(heroCopy, 'bbb-fb-kicker', 'p')) || 'fictional boyfriend';
  const bookLine = cleanText(extractClassBlock(heroCopy, 'bbb-fb-profile-hero__book', 'p'));
  const series = cleanText(extractClassBlock(heroCopy, 'bbb-fb-profile-hero__series', 'p'));
  const bookTitle = stats.book || extractBoyfriendBook(description);
  const selectedBook = images.find((image) => normalizeTitleKey(image.bookTitle || image.alt) === normalizeTitleKey(bookTitle)) || images[0];
  const spice = Number(String(stats['spice level'] || '').match(/🌶/gu)?.length || selectedBook?.spice || 0);

  return {
    profileName,
    profileLine,
    shelf,
    bookLine,
    bookTitle,
    series,
    trope: stats.trope || '',
    loveLanguage: stats['love language'] || '',
    wouldTextBack: stats['would he text back'] || '',
    spice: Number.isFinite(spice) ? Math.max(0, Math.min(5, spice)) : 0,
    profileImage: profileImage
      ? {
          src: profileImage,
          proxySrc: proxiedImageUrl(profileImage),
          alt: `${profileName} profile image`,
          width: 0,
          height: 0,
          score: 20,
        }
      : null,
    selectedBook,
  };
}

function normalizeImageKey(value = '') {
  try {
    const pathname = new URL(value, 'https://bybookishbabe.com').pathname;
    return pathname
      .split('/')
      .pop()
      .toLowerCase()
      .replace(/-\d+x\d+(?=\.[a-z]+$)/, '')
      .replace(/\.[a-z0-9]+$/, '')
      .replace(/[^a-z0-9]+/g, '');
  } catch {
    return String(value).toLowerCase().replace(/[^a-z0-9]+/g, '');
  }
}

function normalizeTitleKey(value = '') {
  return cleanText(value)
    .split(' by ')[0]
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '');
}

function extractMeta(html, property) {
  const re = new RegExp(`<meta[^>]+(?:property|name)=["']${property}["'][^>]+content=["']([^"']+)["'][^>]*>`, 'i');
  const alt = new RegExp(`<meta[^>]+content=["']([^"']+)["'][^>]+(?:property|name)=["']${property}["'][^>]*>`, 'i');
  return cleanText((html.match(re) || html.match(alt) || [])[1] || '');
}

function extractTitle(html) {
  return (
    extractMeta(html, 'og:title') ||
    cleanText((html.match(/<h1[^>]*>([\s\S]*?)<\/h1>/i) || [])[1] || '') ||
    cleanText((html.match(/<title[^>]*>([\s\S]*?)<\/title>/i) || [])[1] || '')
  ).replace(/\s+[-|]\s+By Bookish Babe$/i, '');
}

function scoreBookImage(src, alt) {
  const haystack = `${src} ${alt}`.toLowerCase();
  let score = 0;
  if (/book cover|cover/.test(haystack)) score += 8;
  if (/pinimg\.com/.test(haystack)) score += 5;
  if (/book|romance|hockey|kindle|review/.test(haystack)) score += 4;
  if (/bybookishbabe|custom-emojis|logo|icon|avatar|profile|footer|header|pinterest|facebook|instagram|svg/.test(haystack)) score -= 10;
  if (/\.(jpg|jpeg|png|webp)(\?|$)/.test(src.toLowerCase())) score += 2;
  if (/uploads|wp-content/.test(haystack)) score += 2;
  if (alt.length > 8) score += 1;
  return score;
}

function extractImages(html, pageUrl) {
  const images = [];
  const seen = new Set();
  const imgRe = /<img\b([^>]+)>/gi;
  let match;

  while ((match = imgRe.exec(html))) {
    const tag = match[1];
    const src =
      (tag.match(/\bsrc=["']([^"']+)["']/i) || [])[1] ||
      (tag.match(/\bdata-src=["']([^"']+)["']/i) || [])[1] ||
      (tag.match(/\bdata-lazy-src=["']([^"']+)["']/i) || [])[1] ||
      '';
    const srcset = (tag.match(/\bsrcset=["']([^"']+)["']/i) || [])[1] || '';
    const bestSrcset = srcset
      .split(',')
      .map((item) => item.trim().split(/\s+/)[0])
      .filter(Boolean)
      .pop();
    const absolute = fullSizeUploadUrl(absoluteUrl(src || bestSrcset || '', pageUrl));
    if (!absolute || seen.has(absolute)) continue;

    const alt = cleanText((tag.match(/\balt=["']([^"']*)["']/i) || [])[1] || '');
    const width = Number((tag.match(/\bwidth=["']?(\d+)/i) || [])[1] || 0);
    const height = Number((tag.match(/\bheight=["']?(\d+)/i) || [])[1] || 0);
    const score = scoreBookImage(absolute, alt) + (height > width ? 2 : 0);
    if (score < 6) continue;

    seen.add(absolute);
    images.push({
      src: absolute,
      proxySrc: proxiedImageUrl(absolute),
      alt,
      width,
      height,
      score,
    });
  }

  return images
    .sort((a, b) => b.score - a.score)
    .slice(0, 18);
}

async function fetchBookLibrary(origin) {
  try {
    const response = await fetch(new URL('/wp-json/bbb/v1/books', origin).href, {
      headers: {
        'user-agent': 'BBB Image Studio Local Prototype',
        accept: 'application/json',
      },
    });
    if (!response.ok) return [];
    const payload = await response.json();
    return Array.isArray(payload) ? payload : [];
  } catch {
    return [];
  }
}

function attachBookData(images, books) {
  const byCover = new Map();
  const byTitle = new Map();

  books.forEach((book) => {
    if (book?.cover_url) byCover.set(normalizeImageKey(book.cover_url), book);
    if (book?.title) byTitle.set(normalizeTitleKey(book.title), book);
  });

  return images.map((image) => {
    const coverMatch = byCover.get(normalizeImageKey(image.src));
    const titleMatch = byTitle.get(normalizeTitleKey(image.alt));
    const book = coverMatch || titleMatch;
    const spice = Number(book?.spice_level || 0);
    const tropeFallback = cleanText(image.alt).match(/\s+[–-]\s+(.+?)\s+book cover/i)?.[1] || '';

    return {
      ...image,
      bookTitle: book?.title || cleanText(image.alt).split(' – ')[0] || '',
      author: book?.author || '',
      slug: book?.slug || '',
      spice: Number.isFinite(spice) ? Math.max(0, Math.min(5, spice)) : 0,
      tropes: bookTropeData(book),
      label: bookLabel(book, tropeFallback || image.label || ''),
      seriesName: cleanText(book?.series_name || book?.series || ''),
    };
  });
}

async function scrapeBlog(url) {
  const parsed = new URL(url);
  if (!ALLOWED_HOSTS.has(parsed.hostname)) {
    throw new Error('For this local prototype, enter a bybookishbabe.com URL.');
  }

  const response = await fetch(parsed.href, {
    headers: {
      'user-agent': 'BBB Image Studio Local Prototype',
      accept: 'text/html,application/xhtml+xml',
    },
  });

  if (!response.ok) {
    throw new Error(`Could not fetch page: HTTP ${response.status}`);
  }

  const html = await response.text();
  const pageType = detectPageType(parsed, html);
  const title = extractTitle(html);
  const description = extractMeta(html, 'og:description');
  const featuredImage = extractMeta(html, 'og:image');
  const books = await fetchBookLibrary(parsed.origin);
  const images = attachBookData(extractImages(html, parsed.href), books);
  const boyfriendProfile = 'boyfriend' === pageType ? extractBoyfriendProfile(html, parsed, title, description, images) : null;
  const bookProfile = 'book' === pageType ? await extractBookProfile(html, parsed, title, description, books, images) : null;
  const reviewProfile = 'review' === pageType ? await extractReviewProfile(html, parsed, title, description, books, images) : null;
  const seriesProfile = 'series' === pageType ? await extractSeriesProfile(html, parsed, title) : null;
  const taxonomyProfile = 'trope' === pageType ? extractTaxonomyProfile(html, parsed, title, description, books, images) : null;

  if (
    featuredImage &&
    scoreBookImage(featuredImage, 'Featured image') >= 6 &&
    !images.some((image) => image.src === featuredImage)
  ) {
    const featured = attachBookData([{
      src: featuredImage,
      proxySrc: proxiedImageUrl(featuredImage),
      alt: 'Featured image',
      width: 0,
      height: 0,
      score: 4,
    }], books)[0];
    if ('boyfriend' === pageType) images.unshift(featured);
    else images.push(featured);
  }

  const profileName = boyfriendProfile?.profileName || ('boyfriend' === pageType ? extractBoyfriendName(title) : '');
  const profileBook = boyfriendProfile?.bookTitle || ('boyfriend' === pageType ? extractBoyfriendBook(description) : '');
  const boyfriendImages = boyfriendProfile?.profileImage
    ? [boyfriendProfile.profileImage, ...(boyfriendProfile.selectedBook ? [boyfriendProfile.selectedBook] : []), ...images.filter((image) => image.src !== boyfriendProfile.selectedBook?.src)]
    : images;
  const bookImages = bookProfile?.coverImage
    ? [bookProfile.coverImage, ...images.filter((image) => image.src !== bookProfile.coverImage?.src)]
    : images;
  const reviewImages = reviewProfile?.coverImage
    ? [reviewProfile.coverImage, ...images.filter((image) => image.src !== reviewProfile.coverImage?.src)]
    : images;
  const isSeriesReview = 'review' === pageType && (
    (reviewProfile?.seriesBooks || []).length >= 2 ||
    /\b(?:series|duet|trilogy|saga)\b/i.test(reviewProfile?.series || title)
  );
  const seriesImages = seriesProfile?.images?.length ? seriesProfile.images : images;
  const taxonomyImages = taxonomyProfile?.images?.length ? taxonomyProfile.images : images;

  return {
    url: parsed.href,
    title: seriesProfile?.seriesName || title,
    description: seriesProfile?.blurb || description,
    pageType,
    suggestedTemplate: 'boyfriend' === pageType ? 'boyfriend' : ('book' === pageType ? 'book' : ('series' === pageType ? 'seriesReadingOrder' : ('review' === pageType ? (isSeriesReview ? 'seriesReviewPin' : 'reviewPin') : ('trope' === pageType ? 'tropeMood' : 'library')))),
    profileName,
    profileLine: boyfriendProfile?.profileLine || ('boyfriend' === pageType ? boyfriendProfileLine(description) : ''),
    cta: 'boyfriend' === pageType ? 'would he ruin your standards?' : ('book' === pageType ? 'have you read it?' : ('series' === pageType ? 'read full breakdown' : ('review' === pageType ? 'would you read it?' : ('trope' === pageType ? 'which one are you reading first?' : '')))),
    profileBook,
    shelf: boyfriendProfile?.shelf || '',
    bookLine: boyfriendProfile?.bookLine || '',
    series: boyfriendProfile?.series || '',
    trope: boyfriendProfile?.trope || '',
    loveLanguage: boyfriendProfile?.loveLanguage || '',
    wouldTextBack: boyfriendProfile?.wouldTextBack || '',
    spice: boyfriendProfile?.spice || bookProfile?.spice || reviewProfile?.coverImage?.spice || seriesProfile?.seriesBooks?.[0]?.spice || 0,
    bookTitle: bookProfile?.bookTitle || reviewProfile?.bookTitle || '',
    author: bookProfile?.author || reviewProfile?.author || seriesProfile?.author || '',
    bookSeries: bookProfile?.series || reviewProfile?.series || seriesProfile?.seriesName || '',
    tropes: bookProfile?.tropes || reviewProfile?.tropes || [],
    quote: bookProfile?.quote || reviewProfile?.quote || '',
    quotes: bookProfile?.quotes || reviewProfile?.quotes || [],
    reviewLine: reviewProfile?.vibeLine || '',
    reviewStats: reviewProfile?.reviewStats || [],
    seriesBooks: seriesProfile?.seriesBooks || reviewProfile?.seriesBooks || [],
    boyfriendName: bookProfile?.boyfriendName || reviewProfile?.boyfriendName || seriesProfile?.boyfriendName || '',
    boyfriendImage: bookProfile?.boyfriendImage || reviewProfile?.boyfriendImage || null,
    termName: taxonomyProfile?.termName || '',
    termDescription: taxonomyProfile?.termDescription || '',
    images: 'boyfriend' === pageType ? boyfriendImages : ('book' === pageType ? bookImages : ('series' === pageType ? seriesImages : ('review' === pageType ? reviewImages : ('trope' === pageType ? taxonomyImages : images)))),
  };
}

async function proxyImage(res, imageUrl) {
  const parsed = new URL(imageUrl);
  if (!ALLOWED_HOSTS.has(parsed.hostname) && !/\.pinimg\.com$/i.test(parsed.hostname)) {
    res.writeHead(403);
    res.end('Image host is not allowed.');
    return;
  }

  const response = await fetch(parsed.href, {
    headers: { 'user-agent': 'BBB Image Studio Local Prototype' },
  });
  if (!response.ok) {
    res.writeHead(response.status);
    res.end(`Image fetch failed: HTTP ${response.status}`);
    return;
  }

  const contentType = response.headers.get('content-type') || 'application/octet-stream';
  const buffer = Buffer.from(await response.arrayBuffer());
  res.writeHead(200, {
    'content-type': contentType,
    'cache-control': 'public, max-age=3600',
    'access-control-allow-origin': '*',
  });
  res.end(buffer);
}

async function serveStatic(req, res, pathname) {
  const requested = pathname === '/' ? '/index.html' : pathname;
  const safePath = normalize(requested).replace(/^(\.\.[/\\])+/, '');
  const filePath = join(__dirname, safePath);
  if (!filePath.startsWith(__dirname)) {
    res.writeHead(403);
    res.end('Forbidden');
    return;
  }

  try {
    const body = await readFile(filePath);
    res.writeHead(200, {
      'content-type': MIME[extname(filePath)] || 'application/octet-stream',
      'cache-control': 'no-store',
    });
    res.end(body);
  } catch {
    res.writeHead(404);
    res.end('Not found');
  }
}

createServer(async (req, res) => {
  try {
    const requestUrl = new URL(req.url, `http://localhost:${PORT}`);

    if (requestUrl.pathname === '/api/scrape') {
      const pageUrl = requestUrl.searchParams.get('url') || '';
      if (!pageUrl) return sendJson(res, 400, { error: 'Missing URL.' });
      return sendJson(res, 200, await scrapeBlog(pageUrl));
    }

    if (requestUrl.pathname === '/api/image') {
      const imageUrl = requestUrl.searchParams.get('url') || '';
      if (!imageUrl) {
        res.writeHead(400);
        res.end('Missing URL.');
        return;
      }
      await proxyImage(res, imageUrl);
      return;
    }

    await serveStatic(req, res, requestUrl.pathname);
  } catch (error) {
    if (req.url?.startsWith('/api/')) {
      sendJson(res, 500, { error: error.message || 'Unexpected error.' });
      return;
    }
    res.writeHead(500);
    res.end(error.message || 'Unexpected error.');
  }
}).listen(PORT, () => {
  console.log(`BBB Blog Image Studio running at http://localhost:${PORT}`);
});
