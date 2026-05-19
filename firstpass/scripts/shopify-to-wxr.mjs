import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = process.cwd();
const contentDir = resolve(root, 'migration', 'exports', 'content');
const forceDrafts = process.argv.includes('--drafts');
const outputFileName = forceDrafts ? 'wordpress-content-import-drafts.xml' : 'wordpress-content-import.xml';
const outputPath = resolve(root, 'migration', 'exports', outputFileName);

const pages = await readJson(resolve(contentDir, 'pages.json'));
const blogGroups = await readJson(resolve(contentDir, 'blog-articles.json'));

await mkdir(resolve(root, 'migration', 'exports'), { recursive: true });

const items = [
  ...pages.map(pageToWxrItem),
  ...blogGroups.flatMap((group) => group.articles.map((article) => articleToWxrItem(article, group.blog))),
].map((item) => forceDrafts ? { ...item, status: 'draft' } : item);

await writeFile(outputPath, buildWxr(items));

console.log(`Created ${relative(outputPath)}`);
console.log(`Included ${pages.length} pages and ${items.length - pages.length} posts`);

async function readJson(path) {
  return JSON.parse(await readFile(path, 'utf8'));
}

function pageToWxrItem(page) {
  return {
    title: page.title,
    slug: page.handle,
    type: 'page',
    status: page.published_at ? 'publish' : 'draft',
    date: page.published_at || page.created_at,
    modified: page.updated_at,
    content: page.body_html || '',
    excerpt: '',
    categories: [],
    tags: [],
    meta: {
      _shopify_id: page.id,
      _shopify_handle: page.handle,
      _shopify_admin_graphql_api_id: page.admin_graphql_api_id,
      _shopify_template_suffix: page.template_suffix,
    },
  };
}

function articleToWxrItem(article, blog) {
  return {
    title: article.title,
    slug: article.handle,
    type: 'post',
    status: article.published_at ? 'publish' : 'draft',
    date: article.published_at || article.created_at,
    modified: article.updated_at,
    content: article.body_html || '',
    excerpt: article.summary_html || '',
    categories: [blog.title || blog.handle].filter(Boolean),
    tags: splitTags(article.tags),
    meta: {
      _shopify_id: article.id,
      _shopify_handle: article.handle,
      _shopify_blog_id: blog.id,
      _shopify_blog_handle: blog.handle,
      _shopify_admin_graphql_api_id: article.admin_graphql_api_id,
      _thumbnail_external_url: article.image?.src,
      _thumbnail_external_alt: article.image?.alt,
    },
  };
}

function buildWxr(items) {
  return `<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
  xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/"
  xmlns:wp="http://wordpress.org/export/1.2/">
  <channel>
    <title>By Bookish Babe Shopify Content</title>
    <link>https://bybookishbabe.com</link>
    <description>Shopify pages and blog posts prepared for WordPress import.</description>
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
${item.categories.map((category) => renderCategory(category)).join('\n')}
${item.tags.map((tag) => renderTag(tag)).join('\n')}
${Object.entries(item.meta).map(([key, value]) => renderMeta(key, value)).join('\n')}
    </item>`;
}

function renderCategory(category) {
  return `      <category domain="category" nicename="${escapeXml(slugify(category))}">${cdata(category)}</category>`;
}

function renderTag(tag) {
  return `      <category domain="post_tag" nicename="${escapeXml(slugify(tag))}">${cdata(tag)}</category>`;
}

function renderMeta(key, value) {
  if (value === undefined || value === null || value === '') return '';
  return `      <wp:postmeta>
        <wp:meta_key>${escapeXml(key)}</wp:meta_key>
        <wp:meta_value>${cdata(String(value))}</wp:meta_value>
      </wp:postmeta>`;
}

function splitTags(value) {
  if (!value) return [];
  if (Array.isArray(value)) return value.map(String).filter(Boolean);
  return String(value).split(',').map((tag) => tag.trim()).filter(Boolean);
}

function toRfc822(value) {
  const date = value ? new Date(value) : new Date();
  return Number.isNaN(date.getTime()) ? new Date().toUTCString() : date.toUTCString();
}

function toWpDate(value, gmt = false) {
  const date = value ? new Date(value) : new Date();
  const safeDate = Number.isNaN(date.getTime()) ? new Date() : date;
  const formatted = gmt
    ? safeDate.toISOString().slice(0, 19)
    : safeDate.toISOString().slice(0, 19);
  return formatted.replace('T', ' ');
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
