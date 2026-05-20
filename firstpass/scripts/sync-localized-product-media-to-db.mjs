import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { spawn } from 'node:child_process';

const root = process.cwd();
const productsPath = resolve(root, 'migration', 'exports', 'products', 'digital-products.json');
const mysqlBin = process.env.MYSQL_BIN
  || '/Users/autumnmarie/Library/Application Support/Local/lightning-services/mysql-8.4.0/bin/darwin-arm64/bin/mysql';
const mysqlSocket = process.env.MYSQL_SOCKET
  || '/Users/autumnmarie/Library/Application Support/Local/run/1wlaP1REx/mysql/mysqld.sock';
const mysqlDatabase = process.env.MYSQL_DATABASE || 'local';
const mysqlUser = process.env.MYSQL_USER || 'root';
const mysqlPassword = process.env.MYSQL_PASSWORD || 'root';

const products = JSON.parse(await readFile(productsPath, 'utf8'));
const rows = products
  .filter((product) => product.handle && product.image_url)
  .map((product) => ({
    handle: String(product.handle),
    imageUrl: String(product.image_url),
    mediaUrls: parseMediaUrls(product.media_urls, product.image_url),
  }));

if (!rows.length) {
  console.log('No localized product image rows found.');
  process.exit(0);
}

const sql = [
  'START TRANSACTION;',
  ...rows.flatMap((row) => [
    `DELETE pm FROM wp_postmeta pm INNER JOIN wp_postmeta handle_meta ON handle_meta.post_id = pm.post_id AND handle_meta.meta_key = '_bbb_shopify_product_handle' AND handle_meta.meta_value = ${sqlString(row.handle)} WHERE pm.meta_key IN ('_bbb_source_image_url', '_bbb_product_media_urls');`,
    `INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT post_id, '_bbb_source_image_url', ${sqlString(row.imageUrl)} FROM wp_postmeta WHERE meta_key = '_bbb_shopify_product_handle' AND meta_value = ${sqlString(row.handle)};`,
    `INSERT INTO wp_postmeta (post_id, meta_key, meta_value) SELECT post_id, '_bbb_product_media_urls', ${sqlString(phpSerializeArray(row.mediaUrls))} FROM wp_postmeta WHERE meta_key = '_bbb_shopify_product_handle' AND meta_value = ${sqlString(row.handle)};`,
  ]),
  'COMMIT;',
  '',
].join('\n');

await runMysql(sql);
console.log(`Synced localized media URLs onto ${rows.length} imported products.`);

function parseMediaUrls(value, fallback) {
  if (Array.isArray(value)) return value.filter(Boolean).map(String);
  if ('string' === typeof value && value.trim()) {
    try {
      const parsed = JSON.parse(value);
      if (Array.isArray(parsed)) return parsed.filter(Boolean).map(String);
    } catch {
      return value.split(/[|,]/).map((item) => item.trim()).filter(Boolean);
    }
  }

  return fallback ? [String(fallback)] : [];
}

function phpSerializeArray(values) {
  return `a:${values.length}:{${values.map((value, index) => `i:${index};s:${Buffer.byteLength(value)}:"${value}";`).join('')}}`;
}

function sqlString(value) {
  return `'${String(value).replace(/\\/g, '\\\\').replace(/'/g, "''")}'`;
}

function runMysql(sqlInput) {
  return new Promise((resolvePromise, rejectPromise) => {
    const child = spawn(mysqlBin, [
      `--socket=${mysqlSocket}`,
      `-u${mysqlUser}`,
      `-p${mysqlPassword}`,
      mysqlDatabase,
    ], {
      stdio: ['pipe', 'pipe', 'pipe'],
    });

    let stdout = '';
    let stderr = '';
    child.stdout.on('data', (chunk) => {
      stdout += chunk.toString();
    });
    child.stderr.on('data', (chunk) => {
      stderr += chunk.toString();
    });
    child.on('error', rejectPromise);
    child.stdin.on('error', () => {
      // mysql can close stdin early when connection arguments are wrong; stderr below has the useful detail.
    });
    child.on('close', (code) => {
      if (code === 0) {
        if (stdout.trim()) console.log(stdout.trim());
        if (stderr.trim()) console.error(stderr.trim());
        resolvePromise();
      } else {
        rejectPromise(new Error(`mysql exited ${code}\n${stderr}`));
      }
    });

    child.stdin.end(sqlInput);
  });
}
