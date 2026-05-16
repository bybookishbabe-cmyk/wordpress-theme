import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = process.cwd();
const metaobjectsDir = resolve(root, 'migration', 'exports', 'metaobjects');
const outputPath = resolve(root, 'migration', 'exports', 'wordpress-library-import-drafts.xml');

const library = await readMetaobjectFile('sss_library.json');
const quotes = await readMetaobjectFile('sss_quote.json');

await mkdir(resolve(root, 'migration', 'exports'), { recursive: true });

const bookItems = library.entries.map(bookToItem);
const quoteItems = quotes.entries.map(quoteToItem);
const items = [...bookItems, ...quoteItems];

await writeFile(outputPath, buildWxr(items));

console.log(`Created ${relative(outputPath)}`);
console.log(`Included ${bookItems.length} books and ${quoteItems.length} quotes as drafts`);

async function readMetaobjectFile(name) {
  return JSON.parse(await readFile(resolve(metaobjectsDir, name), 'utf8'));
}

function bookToItem(entry) {
  const fields = fieldMap(entry);
  const title = textField(fields.title) || entry.displayName || entry.handle;
  const author = textField(fields.author);
  const cover = linkUrl(fields.cover);
  const note = textField(fields.mini_note) || textField(fields.why_i_loved_it);

  return {
    title,
    slug: entry.handle,
    type: 'bbb_book',
    status: 'draft',
    date: entry.updatedAt,
    modified: entry.updatedAt,
    content: renderBookContent(fields, note),
    excerpt: note || '',
    terms: [
      ...taxonomyTerms('bbb_genre', [fields.shelf?.reference?.displayName]),
      ...taxonomyTerms('bbb_series', [fields.series?.reference?.displayName]),
      ...taxonomyTerms('bbb_trope', fields.tropes?.references?.nodes?.map((node) => node.displayName) || []),
    ],
    meta: {
      _shopify_id: entry.id,
      _shopify_handle: entry.handle,
      _bbb_access_level: booleanField(fields.private_shelf) === '1' ? 'society' : 'public',
      _bbb_author: author,
      _bbb_cover_url: cover,
      _bbb_genre_handle: fields.shelf?.reference?.handle,
      _bbb_series_handle: fields.series?.reference?.handle,
      _bbb_series_number: numberField(fields.series_number),
      _bbb_spice_level: numberField(fields.spice_level),
      _bbb_darkness_level: numberField(fields.darkness_level),
      _bbb_tension_score: numberField(fields.tension_score),
      _bbb_emotional_damage_score: numberField(fields.emotional_damage_score),
      _bbb_yearning_level: numberField(fields.yearning_level),
      _bbb_amazon_url: linkUrl(fields.amazon_link),
      _bbb_bookshop_url: linkUrl(fields.bookshop_link),
      _bbb_newsletter_url: textField(fields.newsletter_url),
      _bbb_on_kindle_unlimited: booleanField(fields.on_kindle_unlimited),
      _bbb_hide_from_library: booleanField(fields.hide_from_library),
      _bbb_private_shelf: booleanField(fields.private_shelf),
      _bbb_top_shelf: booleanField(fields.top_shelf),
      _bbb_starter_pack: booleanField(fields.starter_pack),
      _bbb_raw_fields: JSON.stringify(entry.fields),
    },
  };
}

function quoteToItem(entry) {
  const fields = fieldMap(entry);
  const quote = textField(fields.quote) || entry.displayName || entry.handle;
  const book = fields.library_book?.reference;

  return {
    title: quote.slice(0, 110),
    slug: entry.handle,
    type: 'bbb_quote',
    status: 'draft',
    date: entry.updatedAt,
    modified: entry.updatedAt,
    content: `<blockquote>${escapeHtml(quote)}</blockquote>`,
    excerpt: quote,
    terms: [],
    meta: {
      _shopify_id: entry.id,
      _shopify_handle: entry.handle,
      _bbb_quote: quote,
      _bbb_book_shopify_id: book?.id,
      _bbb_book_handle: book?.handle,
      _bbb_book_title: book?.displayName,
      _bbb_raw_fields: JSON.stringify(entry.fields),
    },
  };
}

function renderBookContent(fields, note) {
  const bits = [];
  if (note) bits.push(`<p>${escapeHtml(note)}</p>`);

  const facts = [
    ['Author', textField(fields.author)],
    ['Spice level', numberField(fields.spice_level)],
    ['Darkness level', numberField(fields.darkness_level)],
    ['Tension score', numberField(fields.tension_score)],
    ['Emotional damage score', numberField(fields.emotional_damage_score)],
    ['Yearning level', numberField(fields.yearning_level)],
    ['Kindle Unlimited', booleanField(fields.on_kindle_unlimited) === '1' ? 'Yes' : ''],
  ].filter(([, value]) => value !== undefined && value !== null && value !== '');

  if (facts.length) {
    bits.push(`<ul>${facts.map(([label, value]) => `<li><strong>${escapeHtml(label)}:</strong> ${escapeHtml(String(value))}</li>`).join('')}</ul>`);
  }

  return bits.join('\n');
}

function fieldMap(entry) {
  return Object.fromEntries(entry.fields.map((field) => [field.key, field]));
}

function textField(field) {
  if (!field) return '';
  if (field.jsonValue !== undefined && field.jsonValue !== null && typeof field.jsonValue !== 'object') {
    return String(field.jsonValue);
  }
  return field.value ? String(field.value) : '';
}

function numberField(field) {
  if (!field) return '';
  if (typeof field.jsonValue === 'number') return field.jsonValue;
  return field.value || '';
}

function booleanField(field) {
  if (!field) return '';
  const value = field.jsonValue ?? field.value;
  if (value === true || value === 'true') return '1';
  if (value === false || value === 'false') return '0';
  return '';
}

function linkUrl(field) {
  if (!field) return '';
  if (field.jsonValue?.url) return field.jsonValue.url;
  try {
    return JSON.parse(field.value || '{}').url || '';
  } catch {
    return '';
  }
}

function taxonomyTerms(taxonomy, values) {
  return values
    .filter(Boolean)
    .map((name) => ({
      taxonomy,
      name,
      slug: slugify(name),
    }));
}

function buildWxr(items) {
  return `<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
  xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:wp="http://wordpress.org/export/1.2/">
  <channel>
    <title>By Bookish Babe Shopify Library</title>
    <link>https://bybookishbabe.com</link>
    <description>Shopify library metaobjects prepared for WordPress import.</description>
    <pubDate>${new Date().toUTCString()}</pubDate>
    <language>en-US</language>
    <wp:wxr_version>1.2</wp:wxr_version>
${items.map(renderItem).join('\n')}
  </channel>
</rss>
`;
}

function renderItem(item) {
  return `    <item>
      <title>${escapeXml(item.title)}</title>
      <link>${escapeXml(`https://bybookishbabe.com/${item.slug}`)}</link>
      <pubDate>${toRfc822(item.date)}</pubDate>
      <content:encoded>${cdata(item.content)}</content:encoded>
      <excerpt:encoded>${cdata(item.excerpt)}</excerpt:encoded>
      <wp:post_date>${escapeXml(toWpDate(item.date))}</wp:post_date>
      <wp:post_date_gmt>${escapeXml(toWpDate(item.date, true))}</wp:post_date_gmt>
      <wp:post_modified>${escapeXml(toWpDate(item.modified || item.date))}</wp:post_modified>
      <wp:post_modified_gmt>${escapeXml(toWpDate(item.modified || item.date, true))}</wp:post_modified_gmt>
      <wp:comment_status>closed</wp:comment_status>
      <wp:ping_status>closed</wp:ping_status>
      <wp:post_name>${escapeXml(item.slug)}</wp:post_name>
      <wp:status>${escapeXml(item.status)}</wp:status>
      <wp:post_parent>0</wp:post_parent>
      <wp:menu_order>0</wp:menu_order>
      <wp:post_type>${escapeXml(item.type)}</wp:post_type>
      <wp:post_password></wp:post_password>
      <wp:is_sticky>0</wp:is_sticky>
${item.terms.map(renderTerm).join('\n')}
${Object.entries(item.meta).map(([key, value]) => renderMeta(key, value)).join('\n')}
    </item>`;
}

function renderTerm(term) {
  return `      <category domain="${escapeXml(term.taxonomy)}" nicename="${escapeXml(term.slug)}">${cdata(term.name)}</category>`;
}

function renderMeta(key, value) {
  if (value === undefined || value === null || value === '') return '';
  return `      <wp:postmeta>
        <wp:meta_key>${escapeXml(key)}</wp:meta_key>
        <wp:meta_value>${cdata(String(value))}</wp:meta_value>
      </wp:postmeta>`;
}

function toRfc822(value) {
  const date = value ? new Date(value) : new Date();
  return Number.isNaN(date.getTime()) ? new Date().toUTCString() : date.toUTCString();
}

function toWpDate(value) {
  const date = value ? new Date(value) : new Date();
  const safeDate = Number.isNaN(date.getTime()) ? new Date() : date;
  return safeDate.toISOString().slice(0, 19).replace('T', ' ');
}

function cdata(value) {
  return `<![CDATA[${String(value || '').replaceAll(']]>', ']]]]><![CDATA[>')}]]>`;
}

function escapeXml(value) {
  return String(value || '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&apos;');
}

function escapeHtml(value) {
  return String(value || '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function slugify(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/&/g, ' and ')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function relative(path) {
  return path.replace(`${root}/`, '');
}
