<?php
/**
 * CLI importer for Society product exports.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$wp_load = dirname(__DIR__, 5) . '/wp-load.php';
if (!file_exists($wp_load)) {
	fwrite(STDERR, "Could not find wp-load.php from this script location.\n");
	exit(1);
}

$db_socket = getenv('BBB_WP_DB_SOCKET') ?: '/Users/autumnmarie/Library/Application Support/Local/run/1wlaP1REx/mysql/mysqld.sock';
if (file_exists($db_socket)) {
	ini_set('mysqli.default_socket', $db_socket);
	ini_set('pdo_mysql.default_socket', $db_socket);
}

require_once $wp_load;

if (!function_exists('bbb_society_product_importer_import')) {
	fwrite(STDERR, "Society product importer is not loaded.\n");
	exit(1);
}

$export_path = $argv[1] ?? get_theme_file_path('firstpass/migration/exports/products/society-products.json');
if (!is_readable($export_path)) {
	fwrite(STDERR, "Could not read product export: {$export_path}\n");
	exit(1);
}

$payload = file_get_contents($export_path);
if (!is_string($payload) || '' === trim($payload)) {
	fwrite(STDERR, "Product export is empty: {$export_path}\n");
	exit(1);
}

$result = bbb_society_product_importer_import($payload, 'json', false);
if (is_wp_error($result)) {
	fwrite(STDERR, $result->get_error_message() . PHP_EOL);
	exit(1);
}

$imported = is_array($result) ? $result : array();

echo wp_json_encode(
	array(
		'export'            => $export_path,
		'imported'          => count($imported),
		'downloadable'      => count(array_filter($imported, static fn($item): bool => !empty($item['downloadable']))),
		'missingDownloads'  => count(array_filter($imported, static fn($item): bool => !empty($item['missing_download']))),
		'freeForMembers'    => count(array_filter($imported, static fn($item): bool => !empty($item['society_free']))),
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . PHP_EOL;
