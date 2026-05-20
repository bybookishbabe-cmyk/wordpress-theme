<?php
/**
 * Convert localized product media files into WordPress attachments.
 *
 * Run from the Local WordPress install:
 * php firstpass/scripts/sync-product-media-attachments.php
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
require_once ABSPATH . 'wp-admin/includes/image.php';

$products = get_posts(
	array(
		'post_type'      => 'download',
		'post_status'    => array('publish', 'draft', 'private', 'trash'),
		'posts_per_page' => -1,
		'meta_key'       => '_bbb_import_source',
		'meta_value'     => 'society_product_importer',
	)
);

$created = 0;
$reused  = 0;
$thumbs  = 0;
$galleries = 0;

foreach ($products as $product) {
	$post_id = (int) $product->ID;
	$image_url = (string) get_post_meta($post_id, '_bbb_source_image_url', true);
	$media_urls = get_post_meta($post_id, '_bbb_product_media_urls', true);
	$media_urls = is_array($media_urls) ? $media_urls : array();

	if ('' !== $image_url && !in_array($image_url, $media_urls, true)) {
		array_unshift($media_urls, $image_url);
	}

	$attachment_ids = array();
	foreach (array_values(array_unique(array_filter($media_urls))) as $index => $media_url) {
		$result = bbb_sync_product_media_attachment((string) $media_url, $post_id);
		if (!$result['id']) {
			continue;
		}

		$attachment_ids[] = $result['id'];
		if ($result['created']) {
			$created++;
		} else {
			$reused++;
		}

		if (0 === $index) {
			set_post_thumbnail($post_id, $result['id']);
			$thumbs++;
		}
	}

	if ($attachment_ids) {
		update_post_meta($post_id, '_bbb_product_media_attachment_ids', array_values(array_unique($attachment_ids)));
		$galleries++;
	}
}

echo wp_json_encode(
	array(
		'products'       => count($products),
		'created'        => $created,
		'reused'         => $reused,
		'featuredImages' => $thumbs,
		'galleries'      => $galleries,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . PHP_EOL;

function bbb_sync_product_media_attachment(string $url, int $post_id): array {
	$path = bbb_sync_product_media_path($url);
	if ('' === $path || !file_exists($path)) {
		return array('id' => 0, 'created' => false);
	}

	$uploads = wp_upload_dir();
	$relative_upload = ltrim(str_replace(wp_normalize_path(trailingslashit((string) $uploads['basedir'])), '', wp_normalize_path($path)), '/');
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_attached_file',
			'meta_value'     => $relative_upload,
		)
	);

	if (!empty($existing[0])) {
		return array('id' => (int) $existing[0], 'created' => false);
	}

	$filetype = wp_check_filetype(basename($path));
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'] ?: 'image/png',
			'post_title'     => sanitize_text_field(pathinfo($path, PATHINFO_FILENAME)),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$path,
		$post_id,
		true
	);

	if (is_wp_error($attachment_id) || !$attachment_id) {
		return array('id' => 0, 'created' => false);
	}

	$metadata = wp_generate_attachment_metadata((int) $attachment_id, $path);
	if (!is_wp_error($metadata) && !empty($metadata)) {
		wp_update_attachment_metadata((int) $attachment_id, $metadata);
	}

	return array('id' => (int) $attachment_id, 'created' => true);
}

function bbb_sync_product_media_path(string $url): string {
	$url = esc_url_raw($url);
	if ('' === $url) {
		return '';
	}

	$uploads = wp_upload_dir();
	$baseurl = (string) ($uploads['baseurl'] ?? '');
	$basedir = (string) ($uploads['basedir'] ?? '');

	if (str_starts_with($url, '/wp-content/uploads/') && defined('ABSPATH')) {
		return wp_normalize_path(ABSPATH . ltrim($url, '/'));
	}

	if ('' !== $baseurl && str_starts_with($url, $baseurl)) {
		$relative = ltrim(substr($url, strlen($baseurl)), '/');
		return wp_normalize_path(trailingslashit($basedir) . $relative);
	}

	return '';
}
