import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = process.cwd();
const envPath = resolve(root, '.env');
const exportsDir = resolve(root, 'migration', 'exports');
const definitionsPath = resolve(exportsDir, 'metaobject-definitions.json');
const definitionsOnly = process.argv.includes('--definitions-only');

await loadEnv(envPath);

const shopDomain = requireEnv('SHOPIFY_SHOP_DOMAIN').replace(/^https?:\/\//, '').replace(/\/$/, '');
const token = requireEnv('SHOPIFY_ADMIN_TOKEN');
const apiVersion = process.env.SHOPIFY_API_VERSION || '2026-01';
const endpoint = `https://${shopDomain}/admin/api/${apiVersion}/graphql.json`;

await mkdir(exportsDir, { recursive: true });

const definitions = await fetchMetaobjectDefinitions();
await writeJson(definitionsPath, definitions);

console.log(`Exported ${definitions.length} metaobject definitions to ${relative(definitionsPath)}`);

if (!definitionsOnly) {
  const metaobjectsDir = resolve(exportsDir, 'metaobjects');
  await mkdir(metaobjectsDir, { recursive: true });

  for (const definition of definitions) {
    const entries = await fetchMetaobjectsByType(definition.type);
    const outputPath = resolve(metaobjectsDir, `${safeFileName(definition.type)}.json`);
    await writeJson(outputPath, {
      type: definition.type,
      name: definition.name,
      count: entries.length,
      entries,
    });
    console.log(`Exported ${entries.length} ${definition.type} entries to ${relative(outputPath)}`);
  }
}

async function fetchMetaobjectDefinitions() {
  const query = `#graphql
    query MetaobjectDefinitions($cursor: String) {
      metaobjectDefinitions(first: 250, after: $cursor) {
        pageInfo {
          hasNextPage
          endCursor
        }
        nodes {
          id
          name
          type
          description
          displayNameKey
          metaobjectsCount
          fieldDefinitions {
            key
            name
            description
            required
            type {
              name
              category
            }
            validations {
              name
              type
              value
            }
          }
        }
      }
    }
  `;

  const definitions = [];
  let cursor = null;

  do {
    const data = await shopifyGraphql(query, { cursor });
    const connection = data.metaobjectDefinitions;
    definitions.push(...connection.nodes);
    cursor = connection.pageInfo.hasNextPage ? connection.pageInfo.endCursor : null;
  } while (cursor);

  return definitions;
}

async function fetchMetaobjectsByType(type) {
  const query = `#graphql
    query Metaobjects($type: String!, $cursor: String) {
      metaobjects(first: 250, type: $type, after: $cursor, sortKey: "updated_at") {
        pageInfo {
          hasNextPage
          endCursor
        }
        nodes {
          id
          handle
          type
          displayName
          updatedAt
          fields {
            key
            type
            value
            jsonValue
            reference {
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
              ... on GenericFile {
                id
                url
              }
              ... on Metaobject {
                id
                handle
                type
                displayName
              }
              ... on Product {
                id
                handle
                title
              }
              ... on Page {
                id
                handle
                title
              }
              ... on Article {
                id
                handle
                title
              }
            }
            references(first: 100) {
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
                ... on GenericFile {
                  id
                  url
                }
                ... on Metaobject {
                  id
                  handle
                  type
                  displayName
                }
                ... on Product {
                  id
                  handle
                  title
                }
                ... on Page {
                  id
                  handle
                  title
                }
                ... on Article {
                  id
                  handle
                  title
                }
              }
            }
          }
        }
      }
    }
  `;

  const entries = [];
  let cursor = null;

  do {
    const data = await shopifyGraphql(query, { type, cursor });
    const connection = data.metaobjects;
    entries.push(...connection.nodes);
    cursor = connection.pageInfo.hasNextPage ? connection.pageInfo.endCursor : null;
  } while (cursor);

  return entries;
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

function safeFileName(value) {
  return value.replace(/[^a-z0-9_.-]+/gi, '_');
}

function relative(path) {
  return path.replace(`${root}/`, '');
}

async function writeJson(path, data) {
  await writeFile(path, `${JSON.stringify(data, null, 2)}\n`);
}
