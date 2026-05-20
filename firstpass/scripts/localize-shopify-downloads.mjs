import { mkdir, readFile, stat, writeFile } from 'node:fs/promises';
import { basename, resolve } from 'node:path';

const root = process.cwd();
const productsDir = resolve(root, 'migration', 'exports', 'products');
const localUploadDir = process.env.WP_DOWNLOAD_UPLOAD_DIR
  || '/Users/autumnmarie/Local Sites/bybookishbabe/app/public/wp-content/uploads/edd/shopify-digital-products';
const publicBaseUrl = (process.env.WP_DOWNLOAD_BASE_URL || 'https://bybookishbabe.com/wp-content/uploads/edd/shopify-digital-products').replace(/\/$/, '');

await mkdir(localUploadDir, { recursive: true });

const digitalProducts = await readJson(resolve(productsDir, 'digital-products.json'));
const localized = [];
const manifest = [];

for (const product of digitalProducts) {
  const originalFiles = parseDownloadFiles(product.download_files);
  const nextFiles = [];

  for (const file of originalFiles) {
    if (!file.url || !file.url.includes('cdn.shopify.com')) {
      nextFiles.push(file);
      continue;
    }

    const filename = safeFilename(file.name || file.url);
    const targetPath = resolve(localUploadDir, filename);
    const targetUrl = `${publicBaseUrl}/${encodeURIComponent(filename)}`;

    await downloadIfNeeded(file.url, targetPath);

    nextFiles.push({
      name: filename,
      url: targetUrl,
    });
    manifest.push({
      product_handle: product.handle,
      product_title: product.title,
      filename,
      source_url: file.url,
      local_path: targetPath,
      wordpress_url: targetUrl,
    });
  }

  localized.push({
    ...product,
    download_url: nextFiles[0]?.url || product.download_url,
    download_files: JSON.stringify(nextFiles),
    download_missing: product.is_digital && !nextFiles.length && !product.download_url,
  });
}

const freeForMembers = localized.filter((product) => product.society_free);

await writeJson(resolve(productsDir, 'digital-products.json'), localized);
await writeFile(resolve(productsDir, 'digital-products.csv'), toCsv(localized));
await writeJson(resolve(productsDir, 'society-products.json'), localized);
await writeFile(resolve(productsDir, 'society-products.csv'), toCsv(localized));
await writeJson(resolve(productsDir, 'society-products-free-for-members.json'), freeForMembers);
await writeFile(resolve(productsDir, 'society-products-free-for-members.csv'), toCsv(freeForMembers));
await writeJson(resolve(productsDir, 'localized-download-manifest.json'), manifest);

console.log(`Downloaded/localized ${manifest.length} files into ${localUploadDir}`);
console.log(`Rewrote downloads to ${publicBaseUrl}`);
console.log(`${localized.filter((product) => product.download_missing).length} products still need download files.`);

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

function parseDownloadFiles(value) {
  if (Array.isArray(value)) return value;
  if ('string' !== typeof value || '' === value.trim()) return [];

  try {
    const parsed = JSON.parse(value);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

function safeFilename(value) {
  const raw = decodeURIComponent(String(value).split('/').pop()?.split('?')[0] || 'download.pdf');
  const clean = basename(raw).replace(/[^A-Za-z0-9._-]+/g, '-').replace(/-+/g, '-');
  return clean || 'download.pdf';
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
