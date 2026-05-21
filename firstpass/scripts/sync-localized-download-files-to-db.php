<?php
/**
 * Rewrite existing EDD/Woo download-file metadata from Shopify CDN to WP uploads.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$socket = getenv('BBB_WP_DB_SOCKET') ?: '/Users/autumnmarie/Library/Application Support/Local/run/1wlaP1REx/mysql/mysqld.sock';
$public_base = rtrim(getenv('BBB_WP_DOWNLOAD_BASE_URL') ?: 'https://bybookishbabe.com/wp-content/uploads/edd/shopify-digital-products', '/');
$local_dir = getenv('BBB_WP_DOWNLOAD_UPLOAD_DIR') ?: '/Users/autumnmarie/Local Sites/bybookishbabe/app/public/wp-content/uploads/edd/shopify-digital-products';

$mysqli = mysqli_init();
if (!$mysqli || !mysqli_real_connect($mysqli, 'localhost', 'root', 'root', 'local', null, $socket)) {
	fwrite(STDERR, "Could not connect to local WordPress database.\n");
	exit(1);
}

$files = array();
foreach (glob(rtrim($local_dir, '/') . '/*') ?: array() as $path) {
	if (is_file($path)) {
		$files[basename($path)] = $public_base . '/' . rawurlencode(basename($path));
	}
}

$rows = bbb_download_sync_rows(
	$mysqli,
	"SELECT post_id, meta_key, meta_value
	FROM wp_postmeta
	WHERE meta_key IN ('edd_download_files', '_downloadable_files')
	AND meta_value LIKE '%cdn.shopify.com%'"
);

$updated_rows = 0;
$rewritten_files = 0;
$missing_files = array();

foreach ($rows as $row) {
	$post_id = (int) $row['post_id'];
	$meta_key = (string) $row['meta_key'];
	$original_value = (string) $row['meta_value'];
	$value = @unserialize($original_value);

	if (!is_array($value)) {
		continue;
	}

	$changed = false;
	array_walk_recursive(
		$value,
		function (&$leaf) use ($files, &$changed, &$rewritten_files, &$missing_files): void {
			if (!is_string($leaf) || !str_contains($leaf, 'cdn.shopify.com')) {
				return;
			}

			$filename = bbb_download_sync_filename($leaf);
			if (isset($files[$filename])) {
				$leaf = $files[$filename];
				$changed = true;
				$rewritten_files++;
				return;
			}

			$missing_files[$filename] = true;
		}
	);

	if (!$changed) {
		continue;
	}

	bbb_download_sync_update_meta($mysqli, $post_id, $meta_key, serialize($value));
	$updated_rows++;
}

echo json_encode(
	array(
		'updatedRows'    => $updated_rows,
		'rewrittenFiles' => $rewritten_files,
		'missingFiles'   => array_keys($missing_files),
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . PHP_EOL;

function bbb_download_sync_filename(string $url): string {
	$path = parse_url($url, PHP_URL_PATH);
	$filename = $path ? basename((string) $path) : basename(strtok($url, '?') ?: $url);

	return preg_replace('/[^A-Za-z0-9._-]+/', '-', rawurldecode($filename)) ?: 'download.pdf';
}

function bbb_download_sync_rows(mysqli $mysqli, string $sql): array {
	$result = mysqli_query($mysqli, $sql);
	if (!$result) {
		fwrite(STDERR, mysqli_error($mysqli) . PHP_EOL);
		exit(1);
	}

	return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function bbb_download_sync_update_meta(mysqli $mysqli, int $post_id, string $key, string $value): void {
	$key_sql = mysqli_real_escape_string($mysqli, $key);
	$value_sql = mysqli_real_escape_string($mysqli, $value);

	if (!mysqli_query($mysqli, "UPDATE wp_postmeta SET meta_value = '{$value_sql}' WHERE post_id = {$post_id} AND meta_key = '{$key_sql}'")) {
		fwrite(STDERR, mysqli_error($mysqli) . PHP_EOL);
		exit(1);
	}
}
