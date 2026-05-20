import { mkdir, readFile, stat, writeFile } from 'node:fs/promises';
import { basename, extname, resolve } from 'node:path';

const root = process.cwd();
const productsDir = resolve(root, 'migration', 'exports', 'products');
const localUploadDir = process.env.WP_PRODUCT_MEDIA_UPLOAD_DIR
  || '/Users/autumnmarie/Local Sites/bybookishbabe/app/public/wp-content/uploads/edd/shopify-product-media';
const publicBaseUrl = (process.env.WP_PRODUCT_MEDIA_BASE_URL || '/wp-content/uploads/edd/shopify-product-media').replace(/\/$/, '');

await mkdir(localUploadDir, { recursive: true });

const digitalProducts = await readJson(resolve(productsDir, 'digital-products.json'));
const fullExport = await readJson(resolve(productsDir, 'shopify-products-full.json'));
const fullProducts = Array.isArray(fullExport.products) ? fullExport.products : [];
const fullByHandle = new Map(fullProducts.map((product) => [product.handle, product]));
const manifest = [];
const localized = [];
const urlMap = new Map();

for (const product of digitalProducts) {
  const fullProduct = fullByHandle.get(product.handle) || {};
  const imageUrls = collectProductImageUrls(product, fullProduct);
  const localizedUrls = [];

  for (let index = 0; index < imageUrls.length; index += 1) {
    const sourceUrl = imageUrls[index];
    if (!sourceUrl || !sourceUrl.includes('cdn.shopify.com')) {
      localizedUrls.push(sourceUrl);
      continue;
    }

    const filename = safeImageFilename(sourceUrl, product.handle, index);
    const targetPath = resolve(localUploadDir, filename);
    const targetUrl = `${publicBaseUrl}/${encodeURIComponent(filename)}`;

    await downloadIfNeeded(sourceUrl, targetPath);

    localizedUrls.push(targetUrl);
    urlMap.set(sourceUrl, targetUrl);
    manifest.push({
      product_handle: product.handle,
      product_title: product.title,
      filename,
      source_url: sourceUrl,
      local_path: targetPath,
      wordpress_url: targetUrl,
    });
  }

  const primaryImage = localizedUrls[0] || product.image_url || '';
  localized.push({
    ...product,
    image_url: primaryImage,
    media_urls: JSON.stringify(localizedUrls.filter(Boolean)),
  });
}

const freeForMembers = localized.filter((product) => product.society_free);

await writeJson(resolve(productsDir, 'digital-products.json'), localized);
await writeFile(resolve(productsDir, 'digital-products.csv'), toCsv(localized));
await writeJson(resolve(productsDir, 'society-products.json'), localized);
await writeFile(resolve(productsDir, 'society-products.csv'), toCsv(localized));
await writeJson(resolve(productsDir, 'society-products-free-for-members.json'), freeForMembers);
await writeFile(resolve(productsDir, 'society-products-free-for-members.csv'), toCsv(freeForMembers));
await writeJson(resolve(productsDir, 'localized-product-media-manifest.json'), manifest);
await writeJson(resolve(productsDir, 'localized-product-media-url-map.json'), Object.fromEntries(urlMap));

console.log(`Downloaded/localized ${manifest.length} product media files into ${localUploadDir}`);
console.log(`Rewrote product image URLs to ${publicBaseUrl}`);
console.log(`${localized.filter((product) => String(product.image_url || '').includes('cdn.shopify.com')).length} products still reference Shopify images.`);

function collectProductImageUrls(product, fullProduct) {
  const urls = [];
  addUrl(urls, product.image_url);
  addUrl(urls, fullProduct.featuredImage?.url);

  for (const node of fullProduct.media?.nodes || []) {
    addUrl(urls, node?.image?.url);
  }

  return urls;
}

async function downloadIfNeeded(url, targetPath) {
  try {
    const existing = await stat(targetPath);
    if (existing.size > 0) return;
  } catch {
    // File does not exist yet.
  }

  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`Download failed ${response.status}: ${url}`);
  }

  const bytes = Buffer.from(await response.arrayBuffer());
  await writeFile(targetPath, bytes);
}

function addUrl(urls, value) {
  const url = String(value || '').trim();
  if (url && !urls.includes(url)) {
    urls.push(url);
  }
}

function safeImageFilename(url, handle, index) {
  const parsed = new URL(url);
  const raw = decodeURIComponent(parsed.pathname.split('/').pop() || `${handle}-${index + 1}.png`);
  const extension = extname(raw) || '.png';
  const cleanBase = basename(raw, extension)
    .replace(/[^A-Za-z0-9._-]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
  const prefix = String(handle || 'product').replace(/[^A-Za-z0-9._-]+/g, '-').replace(/-+/g, '-');
  return `${prefix}-${index + 1}-${cleanBase || 'image'}${extension.toLowerCase()}`;
}

async function readJson(path) {
  return JSON.parse(await readFile(path, 'utf8'));
}

async function writeJson(path, value) {
  await writeFile(path, `${JSON.stringify(value, null, 2)}\n`);
}

function toCsv(rows) {
  const headers = [
    'handle',
    'title',
    'price',
    'download_url',
    'download_files',
    'image_url',
    'media_urls',
    'society_free',
    'status',
    'id',
    'product_type',
    'categories',
    'tags',
    'vendor',
    'shopify_url',
    'source_status',
    'source_variant_id',
    'source_variant_title',
    'download_missing',
  ];

  return `${headers.join(',')}\n${rows.map((row) => headers.map((header) => csvCell(row[header])).join(',')).join('\n')}\n`;
}

function csvCell(value) {
  const raw = value === true ? 'yes' : value === false ? 'no' : String(value ?? '');
  return `"${raw.replace(/"/g, '""')}"`;
}
