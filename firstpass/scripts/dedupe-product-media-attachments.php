<?php
/**
 * Keep one attachment record per localized product media file.
 *
 * This intentionally uses direct database access so it can run even when the
 * Local PHP CLI cannot bootstrap WordPress through its default socket.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$socket = getenv('BBB_WP_DB_SOCKET') ?: '/Users/autumnmarie/Library/Application Support/Local/run/1wlaP1REx/mysql/mysqld.sock';
$mysqli = mysqli_init();
if (!$mysqli || !mysqli_real_connect($mysqli, 'localhost', 'root', 'root', 'local', null, $socket)) {
	fwrite(STDERR, "Could not connect to local WordPress database.\n");
	exit(1);
}

$attachment_rows = bbb_dedupe_rows(
	$mysqli,
	"SELECT post_id, meta_value
	FROM wp_postmeta
	WHERE meta_key = '_wp_attached_file'
	AND meta_value LIKE '%shopify-product-media%'
	ORDER BY meta_value ASC, post_id ASC"
);

$by_file = array();
foreach ($attachment_rows as $row) {
	$by_file[(string) $row['meta_value']][] = (int) $row['post_id'];
}

$duplicate_to_canonical = array();
foreach ($by_file as $ids) {
	$canonical = (int) $ids[0];
	foreach (array_slice($ids, 1) as $duplicate_id) {
		$duplicate_to_canonical[(int) $duplicate_id] = $canonical;
	}
}

$products = bbb_dedupe_rows(
	$mysqli,
	"SELECT post_id
	FROM wp_postmeta
	WHERE meta_key = '_bbb_import_source'
	AND meta_value = 'society_product_importer'"
);

$rewired_products = 0;
foreach ($products as $product) {
	$post_id = (int) $product['post_id'];
	$ids_value = bbb_dedupe_meta($mysqli, $post_id, '_bbb_product_media_attachment_ids');
	$ids = bbb_dedupe_decode_ids($ids_value);
	$mapped_ids = bbb_dedupe_map_ids($ids, $duplicate_to_canonical);

	if ($mapped_ids !== $ids) {
		bbb_dedupe_update_meta($mysqli, $post_id, '_bbb_product_media_attachment_ids', serialize($mapped_ids));
		$rewired_products++;
	}

	$thumbnail_id = (int) bbb_dedupe_meta($mysqli, $post_id, '_thumbnail_id');
	if (isset($duplicate_to_canonical[$thumbnail_id])) {
		bbb_dedupe_update_meta($mysqli, $post_id, '_thumbnail_id', (string) $duplicate_to_canonical[$thumbnail_id]);
		$rewired_products++;
	} elseif (!$thumbnail_id && !empty($mapped_ids[0])) {
		bbb_dedupe_update_meta($mysqli, $post_id, '_thumbnail_id', (string) $mapped_ids[0]);
		$rewired_products++;
	}
}

$deleted_duplicates = 0;
foreach (array_keys($duplicate_to_canonical) as $duplicate_id) {
	bbb_dedupe_exec($mysqli, 'DELETE FROM wp_postmeta WHERE post_id = ' . (int) $duplicate_id);
	bbb_dedupe_exec($mysqli, 'DELETE FROM wp_posts WHERE ID = ' . (int) $duplicate_id . " AND post_type = 'attachment'");
	bbb_dedupe_exec($mysqli, 'DELETE FROM wp_term_relationships WHERE object_id = ' . (int) $duplicate_id);
	bbb_dedupe_exec($mysqli, 'DELETE FROM wp_comments WHERE comment_post_ID = ' . (int) $duplicate_id);
	$deleted_duplicates++;
}

echo json_encode(
	array(
		'uniqueFiles'        => count($by_file),
		'duplicatesRemoved' => $deleted_duplicates,
		'productsRewired'   => $rewired_products,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . PHP_EOL;

function bbb_dedupe_rows(mysqli $mysqli, string $sql): array {
	$result = mysqli_query($mysqli, $sql);
	if (!$result) {
		fwrite(STDERR, mysqli_error($mysqli) . PHP_EOL);
		exit(1);
	}

	return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function bbb_dedupe_exec(mysqli $mysqli, string $sql): void {
	if (!mysqli_query($mysqli, $sql)) {
		fwrite(STDERR, mysqli_error($mysqli) . PHP_EOL);
		exit(1);
	}
}

function bbb_dedupe_meta(mysqli $mysqli, int $post_id, string $key): string {
	$key = mysqli_real_escape_string($mysqli, $key);
	$result = bbb_dedupe_rows(
		$mysqli,
		"SELECT meta_value FROM wp_postmeta WHERE post_id = {$post_id} AND meta_key = '{$key}' ORDER BY meta_id DESC LIMIT 1"
	);

	return isset($result[0]['meta_value']) ? (string) $result[0]['meta_value'] : '';
}

function bbb_dedupe_update_meta(mysqli $mysqli, int $post_id, string $key, string $value): void {
	$key_sql = mysqli_real_escape_string($mysqli, $key);
	$value_sql = mysqli_real_escape_string($mysqli, $value);
	$existing = bbb_dedupe_rows(
		$mysqli,
		"SELECT meta_id FROM wp_postmeta WHERE post_id = {$post_id} AND meta_key = '{$key_sql}' ORDER BY meta_id DESC LIMIT 1"
	);

	if (!empty($existing[0]['meta_id'])) {
		bbb_dedupe_exec($mysqli, "UPDATE wp_postmeta SET meta_value = '{$value_sql}' WHERE meta_id = " . (int) $existing[0]['meta_id']);
		return;
	}

	bbb_dedupe_exec($mysqli, "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ({$post_id}, '{$key_sql}', '{$value_sql}')");
}

function bbb_dedupe_decode_ids(string $value): array {
	if ('' === $value) {
		return array();
	}

	$decoded = @unserialize($value);
	if (!is_array($decoded)) {
		return array_values(array_filter(array_map('intval', explode(',', $value))));
	}

	return array_values(array_filter(array_map('intval', $decoded)));
}

function bbb_dedupe_map_ids(array $ids, array $duplicate_to_canonical): array {
	$mapped = array();
	foreach ($ids as $id) {
		$id = (int) $id;
		$mapped[] = isset($duplicate_to_canonical[$id]) ? (int) $duplicate_to_canonical[$id] : $id;
	}

	return array_values(array_unique(array_filter($mapped)));
}
