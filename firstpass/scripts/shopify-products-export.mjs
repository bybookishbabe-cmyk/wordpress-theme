import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = process.cwd();
const envPath = resolve(root, '.env');
const exportsDir = resolve(root, 'migration', 'exports');
const productsDir = resolve(exportsDir, 'products');
const dropsPath = resolve(exportsDir, 'metaobjects', 'sss_drop.json');

await loadEnv(envPath);

const shopDomain = requireEnv('SHOPIFY_SHOP_DOMAIN').replace(/^https?:\/\//, '').replace(/\/$/, '');
const token = requireEnv('SHOPIFY_ADMIN_TOKEN');
const apiVersion = process.env.SHOPIFY_API_VERSION || '2026-01';
const endpoint = `https://${shopDomain}/admin/api/${apiVersion}/graphql.json`;

await mkdir(productsDir, { recursive: true });

const dropProductHandles = await loadDropProductHandles();
const products = await fetchProducts();
const normalized = products.map((product) => normalizeProduct(product, dropProductHandles));
const digitalProducts = normalized.filter((product) => product.is_digital);
const freeForMembers = digitalProducts.filter((product) => product.society_free);

const jsonPath = resolve(productsDir, 'society-products.json');
const csvPath = resolve(productsDir, 'society-products.csv');
const digitalJsonPath = resolve(productsDir, 'digital-products.json');
const digitalCsvPath = resolve(productsDir, 'digital-products.csv');
const freeJsonPath = resolve(productsDir, 'society-products-free-for-members.json');
const freeCsvPath = resolve(productsDir, 'society-products-free-for-members.csv');
const fullJsonPath = resolve(productsDir, 'shopify-products-full.json');

await writeJson(fullJsonPath, { count: products.length, products });
await writeJson(jsonPath, digitalProducts);
await writeFile(csvPath, toCsv(digitalProducts));
await writeJson(digitalJsonPath, digitalProducts);
await writeFile(digitalCsvPath, toCsv(digitalProducts));
await writeJson(freeJsonPath, freeForMembers);
await writeFile(freeCsvPath, toCsv(freeForMembers));

console.log(`Exported ${products.length} Shopify products to ${relative(fullJsonPath)}`);
console.log(`Wrote ${digitalProducts.length} WordPress-ready digital products to ${relative(digitalJsonPath)} and ${relative(digitalCsvPath)}`);
console.log(`Updated legacy import aliases at ${relative(jsonPath)} and ${relative(csvPath)}`);
console.log(`${freeForMembers.length} digital products matched the sss_drop product references.`);
console.log(`${digitalProducts.filter((product) => product.download_missing).length} digital products still need download_url filled before publishing.`);

async function fetchProducts() {
  const query = `#graphql
    query Products($cursor: String) {
      products(first: 50, after: $cursor, sortKey: UPDATED_AT, reverse: true) {
        pageInfo {
          hasNextPage
          endCursor
        }
        nodes {
          id
          handle
          title
          status
          vendor
          productType
          tags
          descriptionHtml
          onlineStoreUrl
          collections(first: 10) {
            nodes {
              handle
              title
            }
          }
          featuredImage {
            id
            url
            altText
            width
            height
          }
          variants(first: 5) {
            nodes {
              id
              title
              sku
              price
              compareAtPrice
              inventoryItem {
                requiresShipping
              }
            }
          }
          media(first: 5) {
            nodes {
              __typename
              ... on MediaImage {
                id
                image {
                  url
                  altText
                  width
                  height
                }
              }
            }
          }
          metafields(first: 30) {
            nodes {
              namespace
              key
              type
              value
              reference {
                __typename
                ... on GenericFile {
                  id
                  url
                }
                ... on MediaImage {
                  id
                  image {
                    url
                    altText
                    width
                    height
                  }
                }
              }
              references(first: 5) {
                nodes {
                  __typename
                  ... on GenericFile {
                    id
                    url
                  }
                  ... on MediaImage {
                    id
                    image {
                      url
                      altText
                      width
                      height
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  `;

  const products = [];
  let cursor = null;

  do {
    const data = await shopifyGraphql(query, { cursor });
    const connection = data.products;
    products.push(...connection.nodes);
    cursor = connection.pageInfo.hasNextPage ? connection.pageInfo.endCursor : null;
  } while (cursor);

  return products;
}

function normalizeProduct(product, dropProductHandles) {
  const variant = product.variants?.nodes?.[0] || {};
  const imageUrl = product.featuredImage?.url || firstMediaImageUrl(product.media?.nodes || '');
  const downloadUrl = firstDownloadUrl(product.metafields?.nodes || []);
  const productType = product.productType || '';
  const tags = product.tags || [];
  const collections = product.collections?.nodes || [];
  const isDigital = isDigitalProduct(product, variant, downloadUrl);

  return {
    handle: product.handle || '',
    title: product.title || product.handle || '',
    price: variant.price || '',
    download_url: downloadUrl,
    image_url: imageUrl || '',
    society_free: isDigital && dropProductHandles.has(product.handle || ''),
    status: 'draft',
    id: product.id || '',
    description: product.descriptionHtml || '',
    product_type: productType,
    vendor: product.vendor || '',
    shopify_url: product.onlineStoreUrl || '',
    tags: tags.join('|'),
    categories: productCategories(productType, collections).join('|'),
    source_status: product.status || '',
    source_variant_id: variant.id || '',
    source_variant_title: variant.title || '',
    is_digital: isDigital,
    download_missing: isDigital && !downloadUrl,
  };
}

function isDigitalProduct(product, variant, downloadUrl) {
  if (downloadUrl) return true;

  const productType = String(product.productType || '').toLowerCase();
  const handle = String(product.handle || '').toLowerCase();
  const title = String(product.title || '').toLowerCase();
  const description = stripHtml(product.descriptionHtml || '').toLowerCase();
  const tags = (product.tags || []).map((tag) => String(tag).toLowerCase());
  const haystack = [productType, handle, title, ...tags].join(' ');

  if (productType.includes('physical') || productType.includes('bookmark')) {
    return false;
  }

  if (description.includes('physical item') || description.includes('not a digital download')) {
    return false;
  }

  if (variant?.inventoryItem?.requiresShipping === true && !/printable|digital|template|vault|tracker|download/.test(haystack)) {
    return false;
  }

  return /printable|digital|template|vault|tracker|download|canva/.test(haystack);
}

function productCategories(productType, collections) {
  const categories = new Set(['Digital Products']);
  const type = String(productType || '').trim();

  if (type) categories.add(type);

  for (const collection of collections) {
    const title = String(collection?.title || '').trim();
    if (title) categories.add(title);
  }

  return [...categories];
}

function stripHtml(value) {
  return String(value).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

function firstMediaImageUrl(nodes) {
  for (const node of nodes) {
    const url = node?.image?.url;
    if (url) return url;
  }

  return '';
}

function firstDownloadUrl(metafields) {
  for (const field of metafields) {
    const haystack = `${field.namespace || ''} ${field.key || ''} ${field.type || ''}`.toLowerCase();
    const candidates = [
      field.reference?.url,
      ...(field.references?.nodes || []).map((node) => node?.url),
    ].filter(Boolean);

    if ((haystack.includes('download') || haystack.includes('file')) && candidates.length) {
      return candidates[0];
    }

    if ((haystack.includes('download') || haystack.includes('file')) && /^https?:\/\//.test(field.value || '')) {
      return field.value;
    }
  }

  return '';
}

async function loadDropProductHandles() {
  let raw;
  try {
    raw = await readFile(dropsPath, 'utf8');
  } catch {
    return new Set();
  }

  const data = JSON.parse(raw);
  const handles = new Set();
  const keys = [
    'bonus_printable_product',
    'bonus_physical_product',
    'monthly_collection_printable_products',
    'monthly_collection_physical_products',
  ];

  for (const entry of data.entries || []) {
    for (const field of entry.fields || []) {
      if (!keys.includes(field.key)) continue;

      if (field.reference?.handle) handles.add(field.reference.handle);
      for (const node of field.references?.nodes || []) {
        if (node?.handle) handles.add(node.handle);
      }
    }
  }

  return handles;
}

async function shopifyGraphql(query, variables = {}) {
  const response = await fetch(endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Shopify-Access-Token': token,
    },
    body: JSON.stringify({ query, variables }),
  });

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throw new Error(`Shopify API ${response.status}: ${JSON.stringify(payload)}`);
  }

  if (payload?.errors?.length) {
    throw new Error(`Shopify GraphQL errors: ${JSON.stringify(payload.errors, null, 2)}`);
  }

  return payload.data;
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

function toCsv(rows) {
  const headers = [
    'handle',
    'title',
    'price',
    'download_url',
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

function relative(path) {
  return path.replace(`${root}/`, '');
}
