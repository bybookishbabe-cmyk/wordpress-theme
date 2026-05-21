<?php
/**
 * Convert imported multi-size EDD files into single-choice size options.
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

$rows = bbb_size_options_rows(
	$mysqli,
	"SELECT p.ID, p.post_title, price.meta_value AS price, files.meta_value AS files
	FROM wp_posts p
	INNER JOIN wp_postmeta source ON source.post_id = p.ID AND source.meta_key = '_bbb_import_source' AND source.meta_value = 'society_product_importer'
	INNER JOIN wp_postmeta files ON files.post_id = p.ID AND files.meta_key = 'edd_download_files'
	LEFT JOIN wp_postmeta price ON price.post_id = p.ID AND price.meta_key = 'edd_price'
	WHERE p.post_type = 'download'"
);

$converted = 0;
$file_options = 0;
$skipped = array();

foreach ($rows as $row) {
	$post_id = (int) $row['ID'];
	$title   = (string) $row['post_title'];
	$price   = '' !== (string) ($row['price'] ?? '') ? (string) $row['price'] : '0.00';
	$files   = @unserialize((string) $row['files']);

	if (!is_array($files)) {
		$skipped[] = array('id' => $post_id, 'reason' => 'files did not unserialize');
		continue;
	}

	$options = bbb_size_options_build($files, $price, $title);
	if (!$options) {
		$skipped[] = array('id' => $post_id, 'reason' => 'no multi-size file set');
		continue;
	}

	bbb_size_options_set_meta($mysqli, $post_id, '_variable_pricing', '1');
	bbb_size_options_set_meta($mysqli, $post_id, 'edd_variable_prices', serialize($options['prices']));
	bbb_size_options_set_meta($mysqli, $post_id, '_edd_default_price_id', '1');
	bbb_size_options_delete_meta($mysqli, $post_id, '_edd_price_options_mode');
	bbb_size_options_set_meta($mysqli, $post_id, 'edd_download_files', serialize($options['files']));

	$converted++;
	$file_options += count($options['files']);
}

echo json_encode(
	array(
		'productsScanned'   => count($rows),
		'productsConverted' => $converted,
		'fileOptions'       => $file_options,
		'skipped'           => $skipped,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . PHP_EOL;

function bbb_size_options_label(array $file): string {
	$haystack = strtolower(rawurldecode((string) ($file['name'] ?? '') . ' ' . (string) ($file['file'] ?? '') . ' ' . (string) ($file['url'] ?? '')));
	$haystack = str_replace(array('_', '-', '%20'), ' ', $haystack);

	$sizes = array(
		'6 inch'   => array('6inch', '6 inch', '6-inch'),
		'10th gen' => array('10thgen', '10th gen', '10th-gen', '10th generation'),
		'11th gen' => array('11thgen', '11th gen', '11th-gen', '11th generation'),
		'12th gen' => array('12thgen', '12th gen', '12th-gen', '12th generation'),
	);

	foreach ($sizes as $label => $needles) {
		foreach ($needles as $needle) {
			if (str_contains($haystack, $needle)) {
				return $label;
			}
		}
	}

	return '';
}

function bbb_size_options_build(array $files, string $price, string $title): array {
	$prices = array();
	$converted_files = array();
	$seen = array();
	$index = 1;

	foreach ($files as $file) {
		if (!is_array($file)) {
			continue;
		}

		$url = (string) ($file['file'] ?? $file['url'] ?? '');
		if ('' === trim($url)) {
			continue;
		}

		$label = bbb_size_options_label($file);
		if ('' === $label || isset($seen[$label])) {
			continue;
		}

		$seen[$label] = true;
		$prices[(string) $index] = array(
			'name'   => $label,
			'amount' => $price,
			'index'  => $index,
		);
		$converted_files[] = array(
			'name'      => '' !== trim((string) ($file['name'] ?? '')) ? (string) $file['name'] : $title . ' - ' . $label,
			'file'      => $url,
			'condition' => (string) $index,
		);
		$index++;
	}

	if (count($prices) < 2) {
		return array();
	}

	return array(
		'prices' => $prices,
		'files'  => $converted_files,
	);
}

function bbb_size_options_rows(mysqli $mysqli, string $sql): array {
	$result = mysqli_query($mysqli, $sql);
	if (!$result) {
		fwrite(STDERR, mysqli_error($mysqli) . PHP_EOL);
		exit(1);
	}

	return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function bbb_size_options_set_meta(mysqli $mysqli, int $post_id, string $key, string $value): void {
	bbb_size_options_delete_meta($mysqli, $post_id, $key);

	$key_sql = mysqli_real_escape_string($mysqli, $key);
	$value_sql = mysqli_real_escape_string($mysqli, $value);
	if (!mysqli_query($mysqli, "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ({$post_id}, '{$key_sql}', '{$value_sql}')")) {
		fwrite(STDERR, mysqli_error($mysqli) . PHP_EOL);
		exit(1);
	}
}

function bbb_size_options_delete_meta(mysqli $mysqli, int $post_id, string $key): void {
	$key_sql = mysqli_real_escape_string($mysqli, $key);
	if (!mysqli_query($mysqli, "DELETE FROM wp_postmeta WHERE post_id = {$post_id} AND meta_key = '{$key_sql}'")) {
		fwrite(STDERR, mysqli_error($mysqli) . PHP_EOL);
		exit(1);
	}
}
