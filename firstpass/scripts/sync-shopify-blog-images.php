<?php
/**
 * Import Shopify blog post images into the WordPress media library.
 *
 * Run from the Local WordPress theme directory:
 * php firstpass/scripts/sync-shopify-blog-images.php
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
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$posts = get_posts(
	array(
		'post_type'      => 'post',
		'post_status'    => array('publish', 'draft', 'private', 'trash'),
		'posts_per_page' => -1,
		'meta_key'       => '_shopify_id',
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);

$created        = 0;
$reused         = 0;
$featured       = 0;
$content_updates = 0;
$missing        = array();
$errors         = array();

foreach ($posts as $post) {
	$post_id = (int) $post->ID;
	$featured_url = (string) get_post_meta($post_id, '_thumbnail_external_url', true);

	if ('' !== $featured_url && bbb_is_shopify_image_url($featured_url)) {
		$result = bbb_import_shopify_blog_image($featured_url, $post_id, (string) get_post_meta($post_id, '_thumbnail_external_alt', true));

		if (!empty($result['id'])) {
			set_post_thumbnail($post_id, (int) $result['id']);
			update_post_meta($post_id, '_bbb_imported_shopify_thumbnail_url', bbb_normalize_shopify_image_url($featured_url));
			$featured++;

			if (!empty($result['created'])) {
				$created++;
			} else {
				$reused++;
			}
		} else {
			$missing[] = array(
				'post_id' => $post_id,
				'title'   => get_the_title($post_id),
				'url'     => $featured_url,
			);
		}
	}

	$updated_content = bbb_replace_inline_shopify_images((string) $post->post_content, $post_id, $created, $reused, $errors);
	if ($updated_content !== $post->post_content) {
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $updated_content,
			)
		);
		$content_updates++;
	}
}

echo wp_json_encode(
	array(
		'postsScanned'         => count($posts),
		'attachmentsCreated'   => $created,
		'attachmentsReused'    => $reused,
		'featuredImagesSet'    => $featured,
		'contentPostsUpdated'  => $content_updates,
		'featuredImageMisses'  => $missing,
		'inlineImportErrors'   => $errors,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . PHP_EOL;

function bbb_replace_inline_shopify_images(string $content, int $post_id, int &$created, int &$reused, array &$errors): string {
	if ('' === $content || !str_contains($content, 'cdn.shopify.com')) {
		return $content;
	}

	return (string) preg_replace_callback(
		'~(<img\b[^>]*\bsrc=["\'])(https?://[^"\']*cdn\.shopify\.com/[^"\']+)(["\'][^>]*>)~i',
		function (array $matches) use ($post_id, &$created, &$reused, &$errors): string {
			$url = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5);
			$alt = '';

			if (preg_match('~\balt=["\']([^"\']*)["\']~i', $matches[0], $alt_match)) {
				$alt = html_entity_decode($alt_match[1], ENT_QUOTES | ENT_HTML5);
			}

			$result = bbb_import_shopify_blog_image($url, $post_id, $alt);
			if (empty($result['id']) || empty($result['url'])) {
				$errors[] = array(
					'post_id' => $post_id,
					'url'     => $url,
				);
				return $matches[0];
			}

			if (!empty($result['created'])) {
				$created++;
			} else {
				$reused++;
			}

			return $matches[1] . esc_url($result['url']) . $matches[3];
		},
		$content
	);
}

function bbb_import_shopify_blog_image(string $url, int $post_id, string $alt = ''): array {
	$url = bbb_normalize_shopify_image_url($url);
	if ('' === $url) {
		return array('id' => 0, 'url' => '', 'created' => false);
	}

	$existing_id = bbb_find_existing_shopify_blog_attachment($url);
	if ($existing_id) {
		return array(
			'id'      => $existing_id,
			'url'     => wp_get_attachment_url($existing_id),
			'created' => false,
		);
	}

	$tmp = download_url($url, 60);
	if (is_wp_error($tmp)) {
		return array('id' => 0, 'url' => '', 'created' => false);
	}

	$filename = bbb_shopify_image_filename($url, $post_id);
	$file = array(
		'name'     => $filename,
		'type'     => wp_check_filetype($filename)['type'] ?: 'image/jpeg',
		'tmp_name' => $tmp,
		'error'    => 0,
		'size'     => filesize($tmp),
	);

	$attachment_id = media_handle_sideload($file, $post_id);
	if (is_wp_error($attachment_id)) {
		@unlink($tmp);
		return array('id' => 0, 'url' => '', 'created' => false);
	}

	update_post_meta((int) $attachment_id, '_bbb_shopify_source_image_url', $url);
	if ('' !== $alt) {
		update_post_meta((int) $attachment_id, '_wp_attachment_image_alt', sanitize_text_field($alt));
	}

	return array(
		'id'      => (int) $attachment_id,
		'url'     => wp_get_attachment_url((int) $attachment_id),
		'created' => true,
	);
}

function bbb_find_existing_shopify_blog_attachment(string $url): int {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_key'       => '_bbb_shopify_source_image_url',
			'meta_value'     => $url,
		)
	);

	return !empty($existing[0]) ? (int) $existing[0] : 0;
}

function bbb_is_shopify_image_url(string $url): bool {
	return str_contains($url, 'cdn.shopify.com');
}

function bbb_normalize_shopify_image_url(string $url): string {
	$url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
	if ('' === $url || !bbb_is_shopify_image_url($url)) {
		return '';
	}

	return esc_url_raw($url);
}

function bbb_shopify_image_filename(string $url, int $post_id): string {
	$path = (string) wp_parse_url($url, PHP_URL_PATH);
	$raw = basename($path) ?: 'shopify-blog-image.jpg';
	$extension = pathinfo($raw, PATHINFO_EXTENSION) ?: 'jpg';
	$name = pathinfo($raw, PATHINFO_FILENAME) ?: 'shopify-blog-image';
	$name = sanitize_file_name($name);

	return sanitize_file_name(sprintf('shopify-blog-%d-%s.%s', $post_id, $name, strtolower($extension)));
}
