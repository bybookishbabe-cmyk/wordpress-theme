<?php
/**
 * Template Name: Shop
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_shop_has_private_cache_context')) {
	function bbb_shop_has_private_cache_context(): bool {
		if (is_user_logged_in()) {
			return true;
		}

		foreach (array_keys($_COOKIE) as $cookie_name) {
			$cookie_name = strtolower((string) $cookie_name);
			if (
				str_starts_with($cookie_name, 'edd_') ||
				str_contains($cookie_name, 'edd') ||
				str_contains($cookie_name, 'cart') ||
				str_contains($cookie_name, 'checkout') ||
				str_contains($cookie_name, 'wordpress_logged_in')
			) {
				return true;
			}
		}

		return false;
	}
}

if (!bbb_shop_has_private_cache_context()) {
	$bbb_shop_public_cache = 'public, max-age=300, s-maxage=900, stale-while-revalidate=86400';
	add_filter(
		'nocache_headers',
		static function (array $headers) use ($bbb_shop_public_cache): array {
			return array(
				'Cache-Control' => $bbb_shop_public_cache,
				'Expires'       => gmdate('D, d M Y H:i:s', time() + 300) . ' GMT',
			);
		},
		100
	);

	if (!headers_sent()) {
		header_remove('Pragma');
		header_remove('Expires');
		header('Cache-Control: ' . $bbb_shop_public_cache, true);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT', true);
	}
}

$shop_css_path = get_theme_file_path('assets/css/shop-page.css');
wp_enqueue_style('bbb-shop-page', get_template_directory_uri() . '/assets/css/shop-page.css', array('bbb-base'), file_exists($shop_css_path) ? (string) filemtime($shop_css_path) : wp_get_theme()->get('Version'));
$shop_filters_js_path = get_theme_file_path('assets/js/shop-filters.js');
wp_enqueue_script('bbb-shop-filters', get_template_directory_uri() . '/assets/js/shop-filters.js', array(), file_exists($shop_filters_js_path) ? (string) filemtime($shop_filters_js_path) : wp_get_theme()->get('Version'), true);
$shop_popup_css_path = get_theme_file_path('assets/css/shop-drop-popup.css');
wp_enqueue_style('bbb-shop-drop-popup', get_template_directory_uri() . '/assets/css/shop-drop-popup.css', array('bbb-shop-page'), file_exists($shop_popup_css_path) ? (string) filemtime($shop_popup_css_path) : wp_get_theme()->get('Version'));
$shop_popup_js_path = get_theme_file_path('assets/js/shop-drop-popup.js');
wp_enqueue_script('bbb-shop-drop-popup', get_template_directory_uri() . '/assets/js/shop-drop-popup.js', array(), file_exists($shop_popup_js_path) ? (string) filemtime($shop_popup_js_path) : wp_get_theme()->get('Version'), true);

get_header();

$is_admin_preview = current_user_can('edit_posts');
$post_status      = array('publish');
$removed_product_handles = function_exists('bbb_society_product_importer_removed_handles') ? bbb_society_product_importer_removed_handles() : array();
$downloads_query  = new WP_Query(
	array(
		'post_type'      => post_type_exists('download') ? 'download' : 'product',
		'post_status'    => $post_status,
		'posts_per_page' => 96,
		'no_found_rows'  => true,
		'update_post_term_cache' => true,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => '_bbb_import_source',
				'value'   => 'society_product_importer',
				'compare' => '=',
			),
		),
	)
);

if (!function_exists('bbb_shop_download_image')) {
	function bbb_shop_download_image(int $post_id): string {
		$thumbnail = get_the_post_thumbnail_url($post_id, 'large');
		if ($thumbnail) {
			return (string) $thumbnail;
		}

		$image_url = (string) get_post_meta($post_id, '_bbb_source_image_url', true);
		if (function_exists('bbb_society_product_importer_media_url')) {
			$image_url = bbb_society_product_importer_media_url($image_url);
		}

		if ('' === $image_url || str_starts_with($image_url, '/wp-content/')) {
			$export = bbb_shop_product_export((string) get_post_field('post_name', $post_id));
			$export_image_url = bbb_shop_seed_url((string) ($export['image_url'] ?? ''));
			if ('' !== $export_image_url) {
				$image_url = $export_image_url;
			}
		}

		return esc_url_raw($image_url);
	}
}

if (!function_exists('bbb_shop_download_price')) {
	function bbb_shop_download_price(int $post_id): string {
		if (function_exists('edd_get_download_price')) {
			$price = edd_get_download_price($post_id);
			if (function_exists('edd_format_amount')) {
				$price = edd_format_amount($price);
			}
			if (function_exists('edd_currency_filter')) {
				$price = edd_currency_filter($price);
			}

			return wp_strip_all_tags((string) $price);
		}

		$price = (string) get_post_meta($post_id, '_regular_price', true);
		return '' !== $price ? '$' . number_format((float) $price, 2) : '';
	}
}

if (!function_exists('bbb_shop_download_compare_price')) {
	function bbb_shop_download_compare_price(int $post_id): string {
		if (function_exists('bbb_vault_full_access_product_ids') && in_array($post_id, bbb_vault_full_access_product_ids(), true)) {
			return '$49';
		}

		return '';
	}
}

if (!function_exists('bbb_shop_download_file_count')) {
	function bbb_shop_download_file_count(int $post_id): int {
		if (function_exists('bbb_society_product_file_count')) {
			return bbb_society_product_file_count($post_id);
		}

		$edd_files = get_post_meta($post_id, 'edd_download_files', true);
		if (is_array($edd_files)) {
			$edd_files = array_filter(
				$edd_files,
				static fn($file): bool => is_array($file)
					? '' !== trim((string) ($file['file'] ?? $file['url'] ?? ''))
					: '' !== trim((string) $file)
			);
			return count($edd_files);
		}

		$woo_files = get_post_meta($post_id, '_downloadable_files', true);
		if (!is_array($woo_files)) {
			return 0;
		}

		$woo_files = array_filter(
			$woo_files,
			static fn($file): bool => is_array($file)
				? '' !== trim((string) ($file['file'] ?? $file['url'] ?? ''))
				: '' !== trim((string) $file)
		);

		return count($woo_files);
	}
}

if (!function_exists('bbb_shop_purchase_size_select')) {
	function bbb_shop_purchase_size_select(int $download_id, array $args = array()): void {
		if (!function_exists('edd_has_variable_prices') || !edd_has_variable_prices($download_id) || !function_exists('edd_get_variable_prices')) {
			return;
		}

		$prices = edd_get_variable_prices($download_id);
		if (!is_array($prices) || count($prices) < 2) {
			return;
		}

		$default_price_id = function_exists('edd_get_default_variable_price') ? (string) edd_get_default_variable_price($download_id) : (string) array_key_first($prices);
		$select_id        = 'bbb-shop-size-' . $download_id;
		if (!empty($args['form_id'])) {
			$select_id .= '-' . sanitize_html_class((string) $args['form_id']);
		}
		?>
		<div class="bbb-shop-card__size">
			<label for="<?php echo esc_attr($select_id); ?>">size</label>
			<select id="<?php echo esc_attr($select_id); ?>" class="bbb-shop-card__sizeSelect" onchange="this.closest('form').querySelector('.edd_price_option_<?php echo esc_attr((string) $download_id); ?>[type=hidden]').value=this.value;">
				<?php foreach ($prices as $price_id => $price) : ?>
					<option value="<?php echo esc_attr((string) $price_id); ?>" <?php selected((string) $price_id, $default_price_id); ?>>
						<?php echo esc_html((string) ($price['name'] ?? 'size ' . $price_id)); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<input type="hidden" name="edd_options[price_id][]" class="edd_price_option_<?php echo esc_attr((string) $download_id); ?>" value="<?php echo esc_attr($default_price_id); ?>">
		</div>
		<?php
	}
}

if (!function_exists('bbb_shop_product_export')) {
	function bbb_shop_product_export(string $handle): array {
		static $products_by_handle = null;

		$handle = sanitize_title($handle);
		if ('' === $handle || !function_exists('bbb_society_product_importer_export_rows')) {
			return array();
		}

		if (null === $products_by_handle) {
			$products_by_handle = array();
			foreach (bbb_society_product_importer_export_rows() as $product) {
				if (!is_array($product)) {
					continue;
				}

				$product_handle = sanitize_title((string) ($product['handle'] ?? ''));
				if ('' !== $product_handle) {
					$products_by_handle[$product_handle] = $product;
				}
			}
		}

		return $products_by_handle[$handle] ?? array();
	}
}

if (!function_exists('bbb_shop_is_full_vault_handle')) {
	function bbb_shop_is_full_vault_handle(string $handle): bool {
		$handle = sanitize_title($handle);
		if ('' === $handle) {
			return false;
		}

		$vault_slugs = function_exists('bbb_vault_full_access_product_slugs') ? bbb_vault_full_access_product_slugs() : array('bybookishbabe-vault');
		return in_array($handle, array_map('sanitize_title', $vault_slugs), true);
	}
}

if (!function_exists('bbb_shop_is_full_vault_download')) {
	function bbb_shop_is_full_vault_download(WP_Post $download): bool {
		$post_id = (int) $download->ID;
		if (function_exists('bbb_vault_full_access_product_ids') && in_array($post_id, bbb_vault_full_access_product_ids(), true)) {
			return true;
		}

		return bbb_shop_is_full_vault_handle((string) $download->post_name);
	}
}

if (!function_exists('bbb_shop_download_kind')) {
	function bbb_shop_download_kind(WP_Post $download): string {
		if (bbb_shop_is_full_vault_download($download)) {
			return 'vault';
		}

		$title = strtolower(get_the_title($download));
		$type  = strtolower((string) get_post_meta($download->ID, '_bbb_shopify_product_type', true));
		$terms = get_the_terms($download, 'download_category');
		$term_names = is_array($terms) ? strtolower(implode(' ', wp_list_pluck($terms, 'name'))) : '';
		$haystack = $title . ' ' . $type . ' ' . $term_names;

		if (str_contains($haystack, 'canva') || str_contains($haystack, 'template')) {
			return 'templates';
		}

		return 'inserts';
	}
}

if (!function_exists('bbb_shop_download_size_options')) {
	function bbb_shop_download_size_options(int $post_id): array {
		$prices = get_post_meta($post_id, 'edd_variable_prices', true);
		if (!is_array($prices) || count($prices) < 2) {
			return array();
		}

		$options = array();
		foreach ($prices as $price_id => $price) {
			if (!is_array($price)) {
				continue;
			}

			$options[(string) $price_id] = (string) ($price['name'] ?? 'size ' . $price_id);
		}

		return $options;
	}
}

if (!function_exists('bbb_shop_download_purchase_form')) {
	function bbb_shop_download_purchase_form(int $post_id): void {
		$size_options = bbb_shop_download_size_options($post_id);
		if (function_exists('edd_get_purchase_link')) {
			if ($size_options && function_exists('edd_purchase_variable_pricing')) {
				remove_action('edd_purchase_link_top', 'edd_purchase_variable_pricing', 10);
				add_action('edd_purchase_link_top', 'bbb_shop_purchase_size_select', 10, 2);
			}

			echo edd_get_purchase_link(
				array(
					'download_id' => $post_id,
					'text'        => 'add to cart',
					'checkout'    => 'checkout',
					'price'       => false,
					'class'       => 'bbb-shop-card__button',
					'style'       => 'button',
				)
			);

			if ($size_options && function_exists('edd_purchase_variable_pricing')) {
				remove_action('edd_purchase_link_top', 'bbb_shop_purchase_size_select', 10);
				add_action('edd_purchase_link_top', 'edd_purchase_variable_pricing', 10, 2);
			}
			return;
		}
		?>
		<a class="bbb-shop-card__button" href="<?php echo esc_url(get_permalink($post_id)); ?>">view details</a>
		<?php
	}
}

if (!function_exists('bbb_shop_seed_url')) {
	function bbb_shop_seed_url(string $url): string {
		$url = trim($url);
		if ('' === $url) {
			return '';
		}

		if (function_exists('bbb_society_product_importer_media_url')) {
			$media_url = bbb_society_product_importer_media_url($url);
			if ('' !== $media_url) {
				return $media_url;
			}
		}

		if (str_starts_with($url, '/wp-content/')) {
			return esc_url_raw(home_url($url));
		}

		return esc_url_raw($url);
	}
}

if (!function_exists('bbb_shop_seed_download_files')) {
	function bbb_shop_seed_download_files(array $product): array {
		$raw = $product['download_files'] ?? $product['downloadFiles'] ?? array();
		if (is_string($raw) && '' !== trim($raw)) {
			$decoded = json_decode($raw, true);
			$raw = is_array($decoded) ? $decoded : array();
		}

		return array_values(array_filter((array) $raw, static fn($file): bool => is_array($file) && !empty($file['url'])));
	}
}

if (!function_exists('bbb_shop_seed_file_count')) {
	function bbb_shop_seed_file_count(array $product): int {
		return count(bbb_shop_seed_download_files($product));
	}
}

if (!function_exists('bbb_shop_seed_size_label')) {
	function bbb_shop_seed_size_label(array $file): string {
		if (function_exists('bbb_society_product_importer_size_label')) {
			$label = bbb_society_product_importer_size_label($file);
			if ('' !== $label) {
				return $label;
			}
		}

		$name = trim((string) ($file['name'] ?? ''));
		return '' !== $name ? strtolower((string) preg_replace('/\.[^.]+$/', '', $name)) : 'download';
	}
}

if (!function_exists('bbb_shop_seed_size_select')) {
	function bbb_shop_seed_size_select(array $product): void {
		$files = bbb_shop_seed_download_files($product);
		if (count($files) < 2) {
			return;
		}

		$select_id = 'bbb-shop-size-' . sanitize_html_class((string) ($product['handle'] ?? uniqid('seed-', false)));
		?>
		<div class="bbb-shop-card__size">
			<label for="<?php echo esc_attr($select_id); ?>">size</label>
			<select id="<?php echo esc_attr($select_id); ?>" class="bbb-shop-card__sizeSelect">
				<?php foreach ($files as $file) : ?>
					<option value="<?php echo esc_attr((string) ($file['url'] ?? '')); ?>">
						<?php echo esc_html(bbb_shop_seed_size_label($file)); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}
}

if (!function_exists('bbb_shop_seed_excerpt')) {
	function bbb_shop_seed_excerpt(array $product): string {
		$text = wp_strip_all_tags((string) ($product['description'] ?? ''));
		$text = preg_replace('/\s+/', ' ', $text) ?: '';

		return wp_trim_words($text, 22, '...');
	}
}

if (!function_exists('bbb_shop_seed_kind')) {
	function bbb_shop_seed_kind(array $product): string {
		if (bbb_shop_is_full_vault_handle((string) ($product['handle'] ?? ''))) {
			return 'vault';
		}

		$haystack = strtolower(
			(string) ($product['title'] ?? '') . ' ' .
			(string) ($product['product_type'] ?? '') . ' ' .
			(string) ($product['categories'] ?? '') . ' ' .
			(string) ($product['tags'] ?? '')
		);

		if (str_contains($haystack, 'canva') || str_contains($haystack, 'template')) {
			return 'templates';
		}

		return 'inserts';
	}
}

if (!function_exists('bbb_shop_filter_slug')) {
	function bbb_shop_filter_slug(string $value): string {
		return sanitize_title($value);
	}
}

if (!function_exists('bbb_shop_first_matching_filter')) {
	function bbb_shop_first_matching_filter(string $haystack, array $groups, string $fallback = 'other'): string {
		foreach ($groups as $slug => $needles) {
			foreach ($needles as $needle) {
				$needle = trim((string) $needle);
				if ('' === $needle) {
					continue;
				}

				if (preg_match('/(^|[^a-z0-9])' . preg_quote($needle, '/') . '([^a-z0-9]|$)/', $haystack)) {
					return (string) $slug;
				}
			}
		}

		return $fallback;
	}
}

if (!function_exists('bbb_shop_color_groups')) {
	function bbb_shop_color_groups(): array {
		return array(
			'pink'    => array('pink', 'blush', 'rose', 'rosy', 'mauve', 'berry', 'fuchsia', 'magenta'),
			'red'     => array('red', 'crimson', 'scarlet', 'burgundy', 'wine', 'cherry'),
			'orange'  => array('orange', 'peach', 'coral', 'apricot', 'terracotta', 'autumn', 'fall'),
			'yellow'  => array('yellow', 'gold', 'golden', 'honey', 'butter'),
			'green'   => array('green', 'sage', 'olive', 'emerald', 'mint', 'forest'),
			'blue'    => array('blue', 'navy', 'sky', 'teal', 'turquoise', 'aqua'),
			'purple'  => array('purple', 'lavender', 'lilac', 'violet', 'plum'),
			'black'   => array('black', 'noir', 'gothic', 'charcoal'),
			'neutral' => array('white', 'cream', 'ivory', 'nude', 'beige', 'tan', 'neutral', 'brown', 'espresso', 'chocolate'),
			'gray'    => array('gray', 'grey', 'silver', 'fog'),
		);
	}
}

if (!function_exists('bbb_shop_image_file_path')) {
	function bbb_shop_image_file_path(int $post_id, string $image_url): string {
		$attachment_ids = array();
		$thumbnail_id   = $post_id ? (int) get_post_thumbnail_id($post_id) : 0;
		if ($thumbnail_id) {
			$attachment_ids[] = $thumbnail_id;
		}

		if ($post_id) {
			$media_attachment_ids = get_post_meta($post_id, '_bbb_product_media_attachment_ids', true);
			if (is_array($media_attachment_ids)) {
				foreach ($media_attachment_ids as $attachment_id) {
					$attachment_ids[] = (int) $attachment_id;
				}
			}
		}

		foreach (array_unique(array_filter($attachment_ids)) as $attachment_id) {
			$file = get_attached_file((int) $attachment_id);
			if (is_string($file) && is_readable($file)) {
				return $file;
			}
		}

		$attachment_id = function_exists('attachment_url_to_postid') ? (int) attachment_url_to_postid($image_url) : 0;
		if ($attachment_id) {
			$file = get_attached_file($attachment_id);
			if (is_string($file) && is_readable($file)) {
				return $file;
			}
		}

		$uploads = wp_get_upload_dir();
		$path    = (string) wp_parse_url($image_url, PHP_URL_PATH);
		if ('' !== $path && !empty($uploads['baseurl']) && !empty($uploads['basedir'])) {
			$base_path = (string) wp_parse_url((string) $uploads['baseurl'], PHP_URL_PATH);
			if ('' !== $base_path && str_starts_with($path, $base_path)) {
				$file = rtrim((string) $uploads['basedir'], '/') . substr($path, strlen($base_path));
				if (is_readable($file)) {
					return $file;
				}
			}
		}

		if (defined('ABSPATH') && str_starts_with($path, '/wp-content/')) {
			$file = rtrim((string) ABSPATH, '/') . $path;
			if (is_readable($file)) {
				return $file;
			}
		}

		return '';
	}
}

if (!function_exists('bbb_shop_rgb_to_hsv')) {
	function bbb_shop_rgb_to_hsv(int $red, int $green, int $blue): array {
		$r = $red / 255;
		$g = $green / 255;
		$b = $blue / 255;
		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$delta = $max - $min;

		if ($delta <= 0.000001) {
			$hue = 0.0;
		} elseif ($max === $r) {
			$hue = 60 * fmod((($g - $b) / $delta), 6);
		} elseif ($max === $g) {
			$hue = 60 * ((($b - $r) / $delta) + 2);
		} else {
			$hue = 60 * ((($r - $g) / $delta) + 4);
		}

		if ($hue < 0) {
			$hue += 360;
		}

		return array($hue, $max <= 0.000001 ? 0.0 : $delta / $max, $max);
	}
}

if (!function_exists('bbb_shop_hue_color_bucket')) {
	function bbb_shop_hue_color_bucket(float $hue): string {
		if ($hue < 16 || $hue >= 346) {
			return 'red';
		}
		if ($hue < 40) {
			return 'orange';
		}
		if ($hue < 68) {
			return 'yellow';
		}
		if ($hue < 166) {
			return 'green';
		}
		if ($hue < 250) {
			return 'blue';
		}
		if ($hue < 292) {
			return 'purple';
		}
		if ($hue < 346) {
			return 'pink';
		}

		return 'mixed';
	}
}

if (!function_exists('bbb_shop_image_color_buckets')) {
	function bbb_shop_image_color_buckets(string $image_source): array {
		$is_remote = str_starts_with($image_source, 'http://') || str_starts_with($image_source, 'https://');
		if ('' === $image_source || (!$is_remote && !is_readable($image_source)) || !function_exists('imagecreatetruecolor')) {
			return array();
		}

		$info = @getimagesize($image_source);
		if (!is_array($info) || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
			return array();
		}

		$loader = array(
			'image/jpeg' => 'imagecreatefromjpeg',
			'image/png'  => 'imagecreatefrompng',
			'image/webp' => 'imagecreatefromwebp',
			'image/gif'  => 'imagecreatefromgif',
		)[$info['mime']] ?? '';

		if ('' === $loader || !function_exists($loader)) {
			return array();
		}

		$source = @$loader($image_source);
		if (!$source) {
			return array();
		}

		$size   = 64;
		$sample = imagecreatetruecolor($size, $size);
		imagealphablending($sample, false);
		imagesavealpha($sample, true);
		imagecopyresampled($sample, $source, 0, 0, 0, 0, $size, $size, (int) $info[0], (int) $info[1]);
		imagedestroy($source);

		$scores = array_fill_keys(array('pink', 'red', 'orange', 'yellow', 'green', 'blue', 'purple', 'black', 'neutral', 'gray'), 0.0);
		for ($y = 0; $y < $size; $y++) {
			for ($x = 0; $x < $size; $x++) {
				$pixel = imagecolorat($sample, $x, $y);
				$alpha = ($pixel & 0x7F000000) >> 24;
				if ($alpha > 80) {
					continue;
				}

				$red   = ($pixel >> 16) & 0xFF;
				$green = ($pixel >> 8) & 0xFF;
				$blue  = $pixel & 0xFF;
				list($hue, $saturation, $value) = bbb_shop_rgb_to_hsv($red, $green, $blue);

				if ($value > 0.88 && $saturation < 0.22) {
					continue;
				}

				if ($value < 0.24 && $saturation < 0.42) {
					$scores['black'] += 1.7;
					continue;
				}

				if ($saturation < 0.18) {
					if ($value < 0.58) {
						$scores['gray'] += 0.9;
					} elseif ($value < 0.86) {
						$scores['neutral'] += 0.5;
					}
					continue;
				}

				if ($hue >= 16 && $hue < 40 && $saturation < 0.42 && $value > 0.38) {
					$scores['neutral'] += 0.55 + (1 - abs($value - 0.62));
					continue;
				}

				$bucket = bbb_shop_hue_color_bucket($hue);
				$weight = 0.75 + $saturation + (1 - abs($value - 0.52));
				$scores[$bucket] += $weight;
			}
		}
		imagedestroy($sample);

		arsort($scores);
		$top_bucket = (string) array_key_first($scores);
		$top_score  = (float) current($scores);
		$all_score  = (float) array_sum($scores);

		if ($top_score < 18 || $all_score <= 0) {
			return array();
		}

		$buckets = array();
		foreach ($scores as $bucket => $score) {
			$score = (float) $score;
			if ($score < 14) {
				continue;
			}

			$share = $score / $all_score;
			if ($bucket === $top_bucket || $share >= 0.14 || $score / max($top_score, 1) >= 0.55) {
				$buckets[] = (string) $bucket;
			}

			if (count($buckets) >= 4) {
				break;
			}
		}

		return array_values(array_unique($buckets));
	}
}

if (!function_exists('bbb_shop_visual_color_filters')) {
	function bbb_shop_visual_color_map(): array {
		static $map = null;
		if (is_array($map)) {
			return $map;
		}

		$path = get_theme_file_path('data/shop-visual-colors.json');
		if (!is_readable($path)) {
			$map = array();
			return $map;
		}

		$data = json_decode((string) file_get_contents($path), true);
		$map = is_array($data) ? $data : array();
		return $map;
	}
}

if (!function_exists('bbb_shop_visual_color_filters')) {
	function bbb_shop_visual_color_filters(int $post_id, string $image_url, array $fallback, string $handle = ''): array {
		$handle = sanitize_title($handle);
		if ('' !== $handle) {
			$map = bbb_shop_visual_color_map();
			if (!empty($map[$handle]) && is_array($map[$handle])) {
				return array_values(array_filter(array_map('bbb_shop_filter_slug', $map[$handle])));
			}
		}

		if ($post_id < 1 || '' === $image_url) {
			return $fallback;
		}

		$image_source = bbb_shop_image_file_path($post_id, $image_url);
		if ('' === $image_source) {
			return $fallback;
		}

		$source_version = is_readable($image_source) ? (string) @filemtime($image_source) : md5($image_source);
		$cache_key = md5('visual-color-v4|' . $image_source . '|' . $source_version);
		$cached    = (string) get_post_meta($post_id, '_bbb_shop_visual_color_cache', true);
		if (str_contains($cached, '|')) {
			list($cached_key, $cached_color) = explode('|', $cached, 2);
			if ($cached_key === $cache_key && '' !== $cached_color) {
				return array_values(array_filter(array_map('bbb_shop_filter_slug', preg_split('/\s+/', $cached_color) ?: array())));
			}
		}

		$colors = bbb_shop_image_color_buckets($image_source);
		if (!$colors) {
			$colors = $fallback;
		}

		$colors = array_values(array_filter(array_map('bbb_shop_filter_slug', $colors)));
		update_post_meta($post_id, '_bbb_shop_visual_color_cache', $cache_key . '|' . implode(' ', $colors));
		return $colors;
	}
}

if (!function_exists('bbb_shop_theme_groups')) {
	function bbb_shop_theme_groups(): array {
		return array(
			'dark-romance' => array('dark romance', 'dark', 'gothic', 'villain', 'obsession', 'ruin', 'fourth wing'),
			'soft-romance' => array('soft romance', 'soft', 'romance', 'romantic', 'blush', 'happily ever after'),
			'fantasy'      => array('fantasy', 'fairy', 'fae', 'dragon', 'wing', 'fairytale', 'once upon'),
			'floral'       => array('floral', 'flower', 'rose', 'garden'),
			'seasonal'     => array('seasonal', 'spring', 'summer', 'autumn', 'fall', 'winter', 'holiday', 'christmas'),
			'western'      => array('western', 'cowboy'),
			'minimal'      => array('minimal', 'neutral', 'nude', 'subtle', 'classic'),
			'smutty'       => array('smutty', 'smut', 'spicy'),
			'aesthetic'    => array('aesthetic', 'bookish', 'reading', 'pages'),
		);
	}
}

if (!function_exists('bbb_shop_filter_haystack')) {
	function bbb_shop_filter_haystack($download, bool $is_seed_product): string {
		if ($is_seed_product && is_array($download)) {
			return strtolower(
				(string) ($download['title'] ?? '') . ' ' .
				(string) ($download['product_type'] ?? '') . ' ' .
				(string) ($download['categories'] ?? '') . ' ' .
				(string) ($download['tags'] ?? '')
			);
		}

		if (!$download instanceof WP_Post) {
			return '';
		}

		$post_id = (int) $download->ID;
		$terms = array();
		foreach (array('download_category', 'download_tag', 'product_cat', 'product_tag') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$post_terms = get_the_terms($post_id, $taxonomy);
			if (is_array($post_terms)) {
				$terms = array_merge($terms, wp_list_pluck($post_terms, 'name'));
			}
		}

		$export = bbb_shop_product_export((string) get_post_field('post_name', $post_id));

		return strtolower(
			get_the_title($download) . ' ' .
			(string) get_post_meta($post_id, '_bbb_shopify_product_type', true) . ' ' .
			implode(' ', $terms) . ' ' .
			(string) ($export['categories'] ?? '') . ' ' .
			(string) ($export['tags'] ?? '')
		);
	}
}

if (!function_exists('bbb_shop_filter_values')) {
	function bbb_shop_filter_values($download, bool $is_seed_product, string $kind, string $image_url = ''): array {
		$haystack = bbb_shop_filter_haystack($download, $is_seed_product);
		$post_id  = (!$is_seed_product && $download instanceof WP_Post) ? (int) $download->ID : 0;
		$handle   = $is_seed_product && is_array($download)
			? (string) ($download['handle'] ?? '')
			: (string) get_post_field('post_name', $post_id);
		$color    = bbb_shop_first_matching_filter($haystack, bbb_shop_color_groups(), 'mixed');
		$colors   = bbb_shop_visual_color_filters($post_id, $image_url, array($color), $handle);
		$theme    = bbb_shop_first_matching_filter($haystack, bbb_shop_theme_groups(), 'aesthetic');

		return array(
			'kind'   => bbb_shop_filter_slug($kind),
			'color'  => implode(' ', array_values(array_unique($colors))),
			'theme'  => bbb_shop_filter_slug($theme),
			'search' => trim((string) preg_replace('/\s+/', ' ', $haystack)),
		);
	}
}

if (!function_exists('bbb_shop_seed_products')) {
	function bbb_shop_seed_products(): array {
		if (!function_exists('bbb_society_product_importer_export_rows')) {
			return array();
		}

		$products = array();
		foreach (bbb_society_product_importer_export_rows() as $product) {
			if (!is_array($product)) {
				continue;
			}

			$title = trim((string) ($product['title'] ?? ''));
			$url = bbb_shop_seed_url((string) ($product['shopify_url'] ?? ''));
			if ('' === $title || '' === $url) {
				continue;
			}

			$source_status = strtolower((string) ($product['source_status'] ?? 'active'));
			if ('' !== $source_status && 'active' !== $source_status) {
				continue;
			}

			if (!current_user_can('edit_posts') && 0 === bbb_shop_seed_file_count($product)) {
				continue;
			}

			$product['fallback_url'] = $url;
			$product['fallback_image'] = bbb_shop_seed_url((string) ($product['image_url'] ?? ''));
			$product['fallback_kind'] = bbb_shop_seed_kind($product);
			$products[] = $product;
		}

		return $products;
	}
}

if (!function_exists('bbb_shop_download_excerpt')) {
	function bbb_shop_download_excerpt(WP_Post $download): string {
		$text = wp_strip_all_tags((string) $download->post_content);
		$text = preg_replace('/\s+/', ' ', $text) ?: '';

		return wp_trim_words($text, 22, '...');
	}
}

if (!function_exists('bbb_shop_new_drop_product')) {
	function bbb_shop_new_drop_product(): ?array {
		$post_type = post_type_exists('download') ? 'download' : 'product';
		$query     = new WP_Query(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array(
					array(
						'key'     => '_bbb_import_source',
						'value'   => 'society_product_importer',
						'compare' => '=',
					),
				),
			)
		);

		foreach ($query->posts as $product) {
			if (!$product instanceof WP_Post) {
				continue;
			}

			$product_id = (int) $product->ID;
			if (function_exists('bbb_society_product_is_publicly_sellable') && !bbb_society_product_is_publicly_sellable($product_id)) {
				continue;
			}

			if (bbb_shop_download_file_count($product_id) < 1) {
				continue;
			}

			$added_at = get_post_time('U', true, $product);
			if (!$added_at || $added_at < current_time('timestamp', true) - WEEK_IN_SECONDS) {
				continue;
			}

			return array(
				'id'          => $product_id,
				'title'       => strtolower(get_the_title($product)),
				'url'         => get_permalink($product),
				'image'       => bbb_shop_download_image($product_id),
				'price'       => bbb_shop_download_price($product_id),
				'member_free' => 'yes' === get_post_meta($product_id, '_bbb_society_free_download', true),
				'added_at'    => $added_at,
			);
		}

		return null;
	}
}

$downloads = array_values(
	array_filter(
		$downloads_query->posts,
		static function ($download) use ($is_admin_preview, $removed_product_handles): bool {
			if (!$download instanceof WP_Post) {
				return false;
			}

			if (in_array(sanitize_title((string) $download->post_name), $removed_product_handles, true)) {
				return false;
			}

			if ($is_admin_preview) {
				return true;
			}

			if (function_exists('bbb_society_product_is_publicly_sellable')) {
				return bbb_society_product_is_publicly_sellable((int) $download->ID);
			}

			return bbb_shop_download_file_count((int) $download->ID) > 0;
		}
	)
);
$seed_products = $downloads ? array() : bbb_shop_seed_products();
$counts    = array(
	'all'       => count($downloads) + count($seed_products),
	'inserts'   => 0,
	'templates' => 0,
	'vault'     => 0,
);
$groups = array(
	'inserts'   => array(),
	'templates' => array(),
	'vault'     => array(),
);

foreach ($downloads as $download) {
	if ($download instanceof WP_Post) {
		$kind = bbb_shop_download_kind($download);
		$counts[$kind]++;
		$groups[$kind][] = $download;
	}
}

foreach ($seed_products as $product) {
	if (is_array($product) && in_array(sanitize_title((string) ($product['handle'] ?? '')), $removed_product_handles, true)) {
		continue;
	}

	$kind = (string) ($product['fallback_kind'] ?? 'inserts');
	if (!isset($groups[$kind])) {
		$kind = 'inserts';
	}

	$counts[$kind]++;
	$groups[$kind][] = $product;
}

$sections = array(
	'inserts'   => array(
		'id'     => 'kindle-inserts',
		'kicker' => 'printables',
		'title'  => 'kindle inserts',
	),
	'templates' => array(
		'id'     => 'templates',
		'kicker' => 'editable',
		'title'  => 'canva templates',
	),
	'vault'     => array(
		'id'     => 'bybookishbabe-vault',
		'kicker' => 'the bybookishbabe vault',
		'title'  => 'buy once, have forever',
	),
);

$shop_drop_product = bbb_shop_new_drop_product();
$vault_buy_url = function_exists('bbb_vault_buy_url') ? bbb_vault_buy_url() : home_url('/shop/');
$all_vault_assets = function_exists('bbb_vault_assets') ? bbb_vault_assets() : array();
$vault_assets = array_slice($all_vault_assets, 0, 4);
$vault_asset_count = count($all_vault_assets);
$vault_file_count = array_reduce(
	$all_vault_assets,
	static fn(int $carry, array $asset): int => $carry + (int) ($asset['fileCount'] ?? 0),
	0
);

?>

<main class="bbb-shop" id="main">
	<section class="bbb-shop__hero">
		<div class="bbb-shop__heroInner">
			<p class="bbb-shop__kicker">digital shop</p>
			<h1>printables, templates, and the bybookishbabe vault</h1>
			<p class="bbb-shop__intro">a cleaner home for the downloads: kindle inserts, bookish canva templates, and the bybookishbabe vault for everything in one place, current and future.</p>
			<nav class="bbb-shop__filters" aria-label="Shop sections">
				<a href="#shop-all">all <span><?php echo esc_html((string) $counts['all']); ?></span></a>
				<a href="#kindle-inserts">kindle inserts <span><?php echo esc_html((string) $counts['inserts']); ?></span></a>
				<a href="#templates">templates <span><?php echo esc_html((string) $counts['templates']); ?></span></a>
				<a href="#bybookishbabe-vault">bybookishbabe vault <span><?php echo esc_html((string) $counts['vault']); ?></span></a>
			</nav>
		</div>
	</section>

	<?php get_template_part('template-parts/shop/current-obsession'); ?>

	<section class="bbb-shop-vaultPreview" aria-labelledby="bbb-shop-vault-preview-title">
		<div class="bbb-shop-vaultPreview__inner">
			<div class="bbb-shop-vaultPreview__copy">
				<p class="bbb-shop__kicker">bybookishbabe vault preview</p>
				<h2 id="bbb-shop-vault-preview-title">get lifetime access to the bybookishbabe vault</h2>
				<p>one price. every download. forever.</p>
				<ul>
					<li>every kindle insert, Canva template, reading journal, and tracker we have made</li>
					<li>everything we make next is already yours</li>
					<li>buy once and open it all from your account</li>
					<li>get in now and your price is locked forever</li>
				</ul>
				<div class="bbb-shop-vaultPreview__actions">
					<a class="bbb-shop-vaultPreview__button" href="<?php echo esc_url($vault_buy_url); ?>">get lifetime access <s>$49</s> <strong>$34</strong></a>
					<span><?php echo esc_html((string) $vault_asset_count); ?> products / <?php echo esc_html((string) $vault_file_count); ?> files inside</span>
				</div>
			</div>
			<div class="bbb-shop-vaultPreview__teasers" aria-label="a taste of products inside the vault">
				<?php foreach ($vault_assets as $asset) : ?>
					<?php if (!is_array($asset)) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<a class="bbb-shop-vaultPreview__teaser" href="<?php echo esc_url($vault_buy_url); ?>">
						<?php if (!empty($asset['image'])) : ?>
							<img src="<?php echo esc_url((string) $asset['image']); ?>" alt="<?php echo esc_attr((string) $asset['title']); ?>" loading="lazy">
						<?php else : ?>
							<span aria-hidden="true"><?php echo esc_html(substr((string) ($asset['title'] ?? 'v'), 0, 1)); ?></span>
						<?php endif; ?>
						<small><?php echo esc_html((string) ($asset['group'] ?? 'vault')); ?></small>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php if (!$downloads && !$seed_products) : ?>
		<section class="bbb-shop__empty">
			<h2>shop downloads are almost ready.</h2>
			<p>publish the downloads you want shown here.</p>
		</section>
	<?php else : ?>
		<section class="bbb-shop-filterPanel" aria-label="filter shop designs" data-bbb-shop-filters>
			<div class="bbb-shop-filterPanel__inner">
				<div class="bbb-shop-filterPanel__head">
					<p class="bbb-shop__kicker">browse by vibe</p>
					<p data-bbb-shop-filter-count><?php echo esc_html((string) $counts['all']); ?> designs</p>
				</div>
				<div class="bbb-shop-filterPanel__controls">
					<label>
						<span>color</span>
						<select data-bbb-shop-filter="color">
							<option value="">all colors</option>
							<option value="pink">pink</option>
							<option value="red">red</option>
							<option value="orange">orange</option>
							<option value="yellow">yellow / gold</option>
							<option value="green">green</option>
							<option value="blue">blue</option>
							<option value="purple">purple</option>
							<option value="black">black</option>
							<option value="neutral">neutral</option>
							<option value="gray">gray</option>
							<option value="mixed">mixed</option>
						</select>
					</label>
					<label>
						<span>theme</span>
						<select data-bbb-shop-filter="theme">
							<option value="">all themes</option>
							<option value="dark-romance">dark romance</option>
							<option value="soft-romance">soft romance</option>
							<option value="fantasy">fantasy</option>
							<option value="floral">floral</option>
							<option value="seasonal">seasonal</option>
							<option value="western">western</option>
							<option value="minimal">minimal</option>
							<option value="smutty">smutty</option>
							<option value="aesthetic">aesthetic</option>
						</select>
					</label>
					<label>
						<span>search</span>
						<input type="search" data-bbb-shop-search placeholder="title, mood, collection">
					</label>
					<button type="button" data-bbb-shop-filter-reset>reset</button>
				</div>
			</div>
		</section>
		<p class="bbb-shop__noResults" data-bbb-shop-no-results hidden>no designs match those filters yet.</p>
		<div id="shop-all"></div>
		<?php foreach ($sections as $kind => $section) : ?>
			<?php if (empty($groups[$kind])) : ?>
				<?php continue; ?>
			<?php endif; ?>
			<section class="bbb-shop__section" id="<?php echo esc_attr($section['id']); ?>">
				<div class="bbb-shop__sectionHead">
					<p class="bbb-shop__kicker"><?php echo esc_html($section['kicker']); ?></p>
					<h2><?php echo esc_html($section['title']); ?></h2>
				</div>
				<div class="bbb-shop__grid">
					<?php foreach ($groups[$kind] as $download) : ?>
						<?php
						$is_seed_product = is_array($download);
						$compare_price = '';
						$is_full_vault_product = false;
						if ($is_seed_product) {
							$post_id       = 0;
							$title         = strtolower((string) ($download['title'] ?? 'download'));
							$permalink     = (string) ($download['fallback_url'] ?? '#');
							$image_url     = (string) ($download['fallback_image'] ?? '');
							$price_value   = trim((string) ($download['price'] ?? ''));
							$price         = '' !== $price_value ? '$' . number_format((float) $price_value, 2) : '';
							$file_count    = bbb_shop_seed_file_count($download);
							$missing_files = (bool) ($download['download_missing'] ?? false) && 0 === $file_count;
							$can_purchase  = false;
							$is_free       = bbb_society_product_importer_truthy($download['society_free'] ?? false);
						} else {
							$post_id       = (int) $download->ID;
							$title         = get_the_title($download);
							$permalink     = get_permalink($download);
							$image_url     = bbb_shop_download_image($post_id);
							$price         = bbb_shop_download_price($post_id);
							$compare_price = bbb_shop_download_compare_price($post_id);
							$file_count    = bbb_shop_download_file_count($post_id);
							$is_full_vault_product = function_exists('bbb_vault_full_access_product_ids') && in_array($post_id, bbb_vault_full_access_product_ids(), true);
							$missing_files = !$is_full_vault_product && 'yes' === get_post_meta($post_id, '_bbb_missing_download_url', true) && 0 === $file_count;
							$can_purchase  = ($is_full_vault_product || 0 < $file_count) && !$missing_files;
							$is_free       = 'yes' === get_post_meta($post_id, '_bbb_society_free_download', true);
						}
						$filter_values = bbb_shop_filter_values($download, $is_seed_product, $kind, $image_url);
						?>
						<article
							class="bbb-shop-card bbb-shop-card--<?php echo esc_attr($kind); ?>"
							data-bbb-shop-card
							data-filter-kind="<?php echo esc_attr($filter_values['kind']); ?>"
							data-filter-color="<?php echo esc_attr($filter_values['color']); ?>"
							data-filter-theme="<?php echo esc_attr($filter_values['theme']); ?>"
							data-filter-search="<?php echo esc_attr($filter_values['search']); ?>"
						>
							<a class="bbb-shop-card__media" href="<?php echo esc_url($permalink); ?>">
								<?php if ($image_url) : ?>
									<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
								<?php else : ?>
									<span><?php echo esc_html(substr($title, 0, 1)); ?></span>
								<?php endif; ?>
							</a>
							<div class="bbb-shop-card__body">
								<div class="bbb-shop-card__badges">
									<span><?php echo esc_html($kind === 'templates' ? 'template' : ($kind === 'vault' ? 'vault access' : 'printable')); ?></span>
									<?php if ($is_free) : ?>
										<span>member access</span>
									<?php endif; ?>
									<?php if (!$is_seed_product && 'publish' !== get_post_status($download)) : ?>
										<span><?php echo esc_html(get_post_status($download)); ?></span>
									<?php endif; ?>
									<?php if ($missing_files) : ?>
										<span>needs file</span>
									<?php endif; ?>
								</div>
								<h3><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>
								<div class="bbb-shop-card__meta">
									<strong>
										<?php if ('' !== $compare_price) : ?>
											<s><?php echo esc_html($compare_price); ?></s>
										<?php endif; ?>
										<?php echo esc_html($price); ?>
									</strong>
									<span><?php echo esc_html($is_full_vault_product ? 'vault access' : ($file_count ? $file_count . ' file' . (1 === $file_count ? '' : 's') : 'file pending')); ?></span>
								</div>
								<div class="bbb-shop-card__actions">
									<?php if ($is_seed_product) : ?>
										<?php bbb_shop_seed_size_select($download); ?>
										<a class="bbb-shop-card__button" href="<?php echo esc_url($permalink); ?>">view details</a>
									<?php elseif ($missing_files && $is_admin_preview) : ?>
										<a class="bbb-shop-card__button bbb-shop-card__button--ghost" href="<?php echo esc_url(get_edit_post_link($post_id)); ?>">finish setup</a>
									<?php elseif ($can_purchase) : ?>
										<a class="bbb-shop-card__button" href="<?php echo esc_url($permalink); ?>">view details</a>
									<?php elseif (!$can_purchase) : ?>
										<span class="bbb-shop-card__button bbb-shop-card__button--disabled" aria-disabled="true">coming soon</span>
										<a class="bbb-shop-card__details" href="<?php echo esc_url($permalink); ?>">view details</a>
									<?php else : ?>
										<a class="bbb-shop-card__button" href="<?php echo esc_url(get_permalink($download)); ?>">view details</a>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>
</main>

<?php if (is_array($shop_drop_product)) : ?>
	<div
		class="bbb-shop-drop"
		data-bbb-shop-drop
		data-drop-id="<?php echo esc_attr('shop-drop-' . (string) $shop_drop_product['id'] . '-' . (string) $shop_drop_product['added_at']); ?>"
		hidden
	>
		<div class="bbb-shop-drop__backdrop" data-bbb-shop-drop-close></div>
		<section class="bbb-shop-drop__dialog" role="dialog" aria-modal="true" aria-labelledby="bbb-shop-drop-title" data-bbb-shop-drop-link="<?php echo esc_url((string) $shop_drop_product['url']); ?>" tabindex="0">
			<button class="bbb-shop-drop__close" type="button" data-bbb-shop-drop-close aria-label="close shop drop popup">
				<span aria-hidden="true">&times;</span>
			</button>
			<a class="bbb-shop-drop__media" href="<?php echo esc_url((string) $shop_drop_product['url']); ?>">
				<?php if (!empty($shop_drop_product['image'])) : ?>
					<img src="<?php echo esc_url((string) $shop_drop_product['image']); ?>" alt="<?php echo esc_attr((string) $shop_drop_product['title']); ?>">
				<?php else : ?>
					<span><?php echo esc_html(substr((string) $shop_drop_product['title'], 0, 1)); ?></span>
				<?php endif; ?>
			</a>
			<div class="bbb-shop-drop__copy">
				<p class="bbb-shop-drop__kicker">🌶️ new insert drop</p>
				<h2 id="bbb-shop-drop-title">just dropped: <?php echo esc_html((string) $shop_drop_product['title']); ?></h2>
				<p><?php echo esc_html((string) $shop_drop_product['price']); ?></p>
				<a class="bbb-shop-drop__button" href="<?php echo esc_url((string) $shop_drop_product['url']); ?>">shop the drop</a>
			</div>
		</section>
	</div>
<?php endif; ?>

<?php
wp_reset_postdata();
get_footer();
