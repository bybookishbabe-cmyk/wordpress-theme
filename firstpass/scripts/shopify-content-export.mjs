import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = process.cwd();
const envPath = resolve(root, '.env');
const contentDir = resolve(root, 'migration', 'exports', 'content');

await loadEnv(envPath);

const shopDomain = requireEnv('SHOPIFY_SHOP_DOMAIN').replace(/^https?:\/\//, '').replace(/\/$/, '');
const token = requireEnv('SHOPIFY_ADMIN_TOKEN');
const apiVersion = process.env.SHOPIFY_API_VERSION || '2026-01';
const restBase = `https://${shopDomain}/admin/api/${apiVersion}`;

await mkdir(contentDir, { recursive: true });

const pages = await fetchAll(`${restBase}/pages.json?limit=250`, 'pages');
await writeJson(resolve(contentDir, 'pages.json'), pages);
console.log(`Exported ${pages.length} pages to ${relative(resolve(contentDir, 'pages.json'))}`);

const blogs = await fetchAll(`${restBase}/blogs.json?limit=250`, 'blogs');
await writeJson(resolve(contentDir, 'blogs.json'), blogs);
console.log(`Exported ${blogs.length} blogs to ${relative(resolve(contentDir, 'blogs.json'))}`);

const blogArticles = [];
for (const blog of blogs) {
  const articles = await fetchAll(`${restBase}/blogs/${blog.id}/articles.json?limit=250`, 'articles');
  blogArticles.push({
    blog: pickBlogFields(blog),
    count: articles.length,
    articles,
  });
  console.log(`Exported ${articles.length} articles from ${blog.handle || blog.title}`);
}

await writeJson(resolve(contentDir, 'blog-articles.json'), blogArticles);
console.log(`Exported blog article groups to ${relative(resolve(contentDir, 'blog-articles.json'))}`);

async function fetchAll(initialUrl, key) {
  const items = [];
  let url = initialUrl;

  while (url) {
    const response = await fetch(url, {
      headers: {
        Accept: 'application/json',
        'X-Shopify-Access-Token': token,
      },
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      throw new Error(`Shopify REST ${response.status}: ${JSON.stringify(payload)}`);
    }

    items.push(...(payload?.[key] || []));
    url = nextPageUrl(response.headers.get('link'));
  }

  return items;
}

function nextPageUrl(linkHeader) {
  if (!linkHeader) return null;

  const nextLink = linkHeader
    .split(',')
    .map((part) => part.trim())
    .find((part) => part.includes('rel="next"'));

  const match = nextLink?.match(/<([^>]+)>/);
  return match?.[1] || null;
}

function pickBlogFields(blog) {
  return {
    id: blog.id,
    handle: blog.handle,
    title: blog.title,
    commentable: blog.commentable,
    feedburner: blog.feedburner,
    feedburner_location: blog.feedburner_location,
    created_at: blog.created_at,
    updated_at: blog.updated_at,
  };
}

async function loadEnv(path) {
  let raw;
  try {
    raw = await readFile(path, 'utf8');
  } catch {
    return;
  }

  for (const line of raw.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) continue;
    const separator = trimmed.indexOf('=');
    if (separator === -1) continue;
    const key = trimmed.slice(0, separator).trim();
    const value = trimmed.slice(separator + 1).trim().replace(/^['"]|['"]$/g, '');
    if (!process.env[key]) process.env[key] = value;
  }
}

function requireEnv(name) {
  const value = process.env[name];
  if (!value) {
    throw new Error(`Missing ${name}. Copy .env.example to .env and fill it in.`);
  }
  return value;
}

async function writeJson(path, value) {
  await writeFile(path, `${JSON.stringify(value, null, 2)}\n`);
}

function relative(path) {
  return path.replace(`${root}/`, '');
}
