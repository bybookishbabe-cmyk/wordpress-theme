<?php
/**
 * Single digital product detail layout.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_single_product_image')) {
	function bbb_single_product_image(int $post_id): string {
		$image = get_the_post_thumbnail_url($post_id, 'large');
		if ($image) {
			return (string) $image;
		}

		$candidates = array((string) get_post_meta($post_id, '_bbb_source_image_url', true));
		$attachment_ids = get_post_meta($post_id, '_bbb_product_media_attachment_ids', true);
		if (is_array($attachment_ids)) {
			foreach ($attachment_ids as $attachment_id) {
				$attachment_image = wp_get_attachment_image_url((int) $attachment_id, 'large');
				if ($attachment_image) {
					$candidates[] = (string) $attachment_image;
				}
			}
		}

		$media_urls = get_post_meta($post_id, '_bbb_product_media_urls', true);
		if (is_string($media_urls) && '' !== trim($media_urls)) {
			$decoded = json_decode($media_urls, true);
			$media_urls = is_array($decoded) ? $decoded : preg_split('/[|,]/', $media_urls);
		}
		if (is_array($media_urls)) {
			foreach ($media_urls as $url) {
				$candidates[] = (string) $url;
			}
		}

		$handle = (string) get_post_meta($post_id, '_bbb_shopify_product_handle', true);
		if ('' === $handle) {
			$handle = (string) get_post_field('post_name', $post_id);
		}
		if ('' !== $handle && function_exists('bbb_society_product_importer_export_rows')) {
			foreach (bbb_society_product_importer_export_rows() as $product) {
				if (!is_array($product) || sanitize_title((string) ($product['handle'] ?? '')) !== sanitize_title($handle)) {
					continue;
				}

				$candidates[] = (string) ($product['image_url'] ?? '');
				$export_media = $product['media_urls'] ?? $product['mediaUrls'] ?? array();
				if (is_string($export_media) && '' !== trim($export_media)) {
					$decoded = json_decode($export_media, true);
					$export_media = is_array($decoded) ? $decoded : preg_split('/[|,]/', $export_media);
				}
				foreach ((array) $export_media as $url) {
					$candidates[] = (string) $url;
				}
				break;
			}
		}

		foreach ($candidates as $candidate) {
			$candidate = trim((string) $candidate);
			if ('' === $candidate) {
				continue;
			}

			if (function_exists('bbb_society_product_importer_media_url')) {
				$mapped = bbb_society_product_importer_media_url($candidate);
				if ('' !== $mapped) {
					$candidate = $mapped;
				}
			}

			if (str_starts_with($candidate, '/wp-content/')) {
				$candidate = home_url($candidate);
			}

			$candidate = esc_url_raw($candidate);
			if ('' !== $candidate) {
				return $candidate;
			}
		}

		return '';
	}
}

if (!function_exists('bbb_single_product_price')) {
	function bbb_single_product_price(int $post_id): string {
		if ('download' === get_post_type($post_id) && function_exists('edd_get_download_price')) {
			$price = edd_get_download_price($post_id);
			if (function_exists('edd_format_amount')) {
				$price = edd_format_amount($price);
			}
			if (function_exists('edd_currency_filter')) {
				$price = edd_currency_filter($price);
			}

			return wp_strip_all_tags((string) $price);
		}

		if (function_exists('wc_get_product')) {
			$product = wc_get_product($post_id);
			if ($product) {
				return wp_strip_all_tags((string) $product->get_price_html());
			}
		}

		$price = (string) get_post_meta($post_id, '_regular_price', true);
		return '' !== $price ? '$' . number_format((float) $price, 2) : '';
	}
}

if (!function_exists('bbb_single_product_compare_price')) {
	function bbb_single_product_compare_price(int $post_id): string {
		if (function_exists('bbb_vault_full_access_product_ids') && in_array($post_id, bbb_vault_full_access_product_ids(), true)) {
			return '$49';
		}

		return '';
	}
}

if (!function_exists('bbb_single_product_file_count')) {
	function bbb_single_product_file_count(int $post_id): int {
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

if (!function_exists('bbb_single_product_size_options')) {
	function bbb_single_product_size_options(int $post_id): array {
		$prices = get_post_meta($post_id, 'edd_variable_prices', true);
		if (!is_array($prices) || count($prices) < 2) {
			return array();
		}

		$options = array();
		foreach ($prices as $price_id => $price) {
			if (is_array($price)) {
				$options[(string) $price_id] = (string) ($price['name'] ?? 'size ' . $price_id);
			}
		}

		return $options;
	}
}

if (!function_exists('bbb_single_product_size_select')) {
	function bbb_single_product_size_select(int $download_id, array $args = array()): void {
		$size_options = bbb_single_product_size_options($download_id);
		if (count($size_options) < 2) {
			return;
		}

		$default_price_id = (string) get_post_meta($download_id, '_edd_default_price_id', true);
		if ('' === $default_price_id || !isset($size_options[$default_price_id])) {
			$default_price_id = (string) array_key_first($size_options);
		}

		$select_id = 'bbb-product-size-' . $download_id;
		?>
		<div class="bbb-shop-card__size">
			<label for="<?php echo esc_attr($select_id); ?>">size</label>
			<select id="<?php echo esc_attr($select_id); ?>" class="bbb-shop-card__sizeSelect" onchange="this.closest('form').querySelector('.edd_price_option_<?php echo esc_attr((string) $download_id); ?>[type=hidden]').value=this.value;">
				<?php foreach ($size_options as $price_id => $label) : ?>
					<option value="<?php echo esc_attr($price_id); ?>" <?php selected($price_id, $default_price_id); ?>>
						<?php echo esc_html($label); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<input type="hidden" name="edd_options[price_id][]" class="edd_price_option_<?php echo esc_attr((string) $download_id); ?>" value="<?php echo esc_attr($default_price_id); ?>">
		</div>
		<?php
	}
}

if (!function_exists('bbb_single_product_purchase_form')) {
	function bbb_single_product_purchase_form(int $post_id): void {
		if ('download' === get_post_type($post_id)) {
			$size_options = bbb_single_product_size_options($post_id);
			if (function_exists('edd_get_purchase_link')) {
				if ($size_options && function_exists('edd_purchase_variable_pricing')) {
					remove_action('edd_purchase_link_top', 'edd_purchase_variable_pricing', 10);
					add_action('edd_purchase_link_top', 'bbb_single_product_size_select', 10, 2);
				}

				echo edd_get_purchase_link(
					array(
						'download_id' => $post_id,
						'text'        => 'add to cart',
						'checkout'    => 'checkout',
						'price'       => false,
						'class'       => 'bbb-shop-card__button bbb-product__addToCart',
						'style'       => 'button',
					)
				);

				if ($size_options && function_exists('edd_purchase_variable_pricing')) {
					remove_action('edd_purchase_link_top', 'bbb_single_product_size_select', 10);
					add_action('edd_purchase_link_top', 'edd_purchase_variable_pricing', 10, 2);
				}
				?>
				<a class="bbb-shop-card__button bbb-product__keepShopping" href="<?php echo esc_url(home_url('/shop/')); ?>">keep shopping</a>
				<?php
				return;
			}
		}

		$checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
		?>
		<a class="bbb-shop-card__button" href="<?php echo esc_url($checkout_url); ?>">checkout</a>
		<?php
	}
}

if (!function_exists('bbb_single_product_july_2026_staged_handles')) {
	function bbb_single_product_july_2026_staged_handles(): array {
		return array(
			'midnight-drive-printable-kindle-insert',
			'midnight-makeout-printable-kindle-insert',
			'midnight-movie-printable-kindle-insert',
			'midnight-swim-printable-kindle-insert',
		);
	}
}

if (!function_exists('bbb_single_product_is_july_2026_staged')) {
	function bbb_single_product_is_july_2026_staged(int $post_id): bool {
		if (function_exists('bbb_staged_product_is_locked')) {
			return bbb_staged_product_is_locked($post_id);
		}

		$release_at = new DateTimeImmutable('2026-07-01 00:00:00', new DateTimeZone('America/Los_Angeles'));
		$now        = new DateTimeImmutable('now', new DateTimeZone('America/Los_Angeles'));

		return $now < $release_at
			&& in_array(sanitize_title((string) get_post_field('post_name', $post_id)), bbb_single_product_july_2026_staged_handles(), true);
	}
}

if (!function_exists('bbb_single_product_is_kindle_insert')) {
	function bbb_single_product_is_kindle_insert(int $post_id): bool {
		$term_names = '';
		if (taxonomy_exists('download_category')) {
			$terms = get_the_terms($post_id, 'download_category');
			$term_names = is_array($terms) ? implode(' ', wp_list_pluck($terms, 'name')) : '';
		}

		$haystack = strtolower(
			get_the_title($post_id) . ' ' .
			(string) get_post_meta($post_id, '_bbb_shopify_product_type', true) . ' ' .
			$term_names
		);

		return str_contains($haystack, 'kindle insert')
			&& !str_contains($haystack, 'vault')
			&& !str_contains($haystack, 'canva')
			&& !str_contains($haystack, 'template');
	}
}

if (!function_exists('bbb_single_product_is_canva_template')) {
	function bbb_single_product_is_canva_template(int $post_id): bool {
		$term_text = '';
		foreach (array('download_category', 'download_tag', 'product_cat', 'product_tag') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$terms = get_the_terms($post_id, $taxonomy);
			if (is_array($terms)) {
				$term_text .= ' ' . implode(' ', array_merge(wp_list_pluck($terms, 'name'), wp_list_pluck($terms, 'slug')));
			}
		}

		$haystack = strtolower(
			get_the_title($post_id) . ' ' .
			(string) get_post_field('post_name', $post_id) . ' ' .
			(string) get_post_meta($post_id, '_bbb_shopify_product_type', true) . ' ' .
			$term_text
		);

		return (str_contains($haystack, 'canva') || str_contains($haystack, 'template'))
			&& !str_contains($haystack, 'kindle insert');
	}
}

if (!function_exists('bbb_single_product_kind_label')) {
	function bbb_single_product_kind_label(int $post_id): string {
		if (bbb_single_product_is_canva_template($post_id)) {
			return 'canva template';
		}

		if (bbb_single_product_is_kindle_insert($post_id)) {
			return 'printable';
		}

		$kind = strtolower((string) get_post_meta($post_id, '_bbb_shopify_product_type', true));
		return '' !== $kind ? $kind : ('download' === get_post_type($post_id) ? 'digital download' : 'product');
	}
}

if (!function_exists('bbb_single_product_related_terms')) {
	function bbb_single_product_related_terms(int $post_id): array {
		$term_ids = array();
		foreach (array('download_category', 'download_tag', 'product_cat', 'product_tag') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$terms = get_the_terms($post_id, $taxonomy);
			if (!is_array($terms)) {
				continue;
			}

			foreach ($terms as $term) {
				if ($term instanceof WP_Term) {
					$term_ids[] = (int) $term->term_id;
				}
			}
		}

		return array_values(array_unique(array_filter($term_ids)));
	}
}

if (!function_exists('bbb_single_product_related_keywords')) {
	function bbb_single_product_related_keywords(int $post_id): array {
		$haystack = strtolower(get_the_title($post_id));
		foreach (array('download_category', 'download_tag', 'product_cat', 'product_tag') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$terms = get_the_terms($post_id, $taxonomy);
			if (is_array($terms)) {
				$haystack .= ' ' . strtolower(implode(' ', wp_list_pluck($terms, 'name')));
			}
		}

		$keywords = array(
			'aesthetic',
			'autumn',
			'black',
			'blue',
			'book',
			'bright',
			'brown',
			'cowboy',
			'cream',
			'dark',
			'delight',
			'dream',
			'fairy',
			'floral',
			'golden',
			'green',
			'haze',
			'letter',
			'marlboro',
			'midnight',
			'nude',
			'pink',
			'purple',
			'red',
			'romance',
			'rose',
			'smut',
			'smutty',
			'soft',
			'summer',
			'that girl',
			'vintage',
			'villain',
		);

		return array_values(
			array_filter(
				$keywords,
				static fn(string $keyword): bool => str_contains($haystack, $keyword)
			)
		);
	}
}

if (!function_exists('bbb_single_product_related_products')) {
	function bbb_single_product_related_products(int $post_id, int $limit = 3): array {
		if ('download' !== get_post_type($post_id)) {
			return array();
		}

		$term_ids  = bbb_single_product_related_terms($post_id);
		$keywords  = bbb_single_product_related_keywords($post_id);
		$is_template_product = bbb_single_product_is_canva_template($post_id);
		$tax_query = array();
		if ($is_template_product) {
			$tax_query[] = array(
				'taxonomy' => 'download_category',
				'field'    => 'slug',
				'terms'    => array('bookish-templates', 'canva-templates'),
				'operator' => 'IN',
			);
		} elseif ($term_ids) {
			$tax_query[] = array(
				'taxonomy' => 'download_category',
				'field'    => 'term_id',
				'terms'    => $term_ids,
				'operator' => 'IN',
			);
		}

		$query_args = array(
			'post_type'           => 'download',
			'post_status'         => 'publish',
			'posts_per_page'      => 48,
			'post__not_in'        => array($post_id),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ($tax_query) {
			$query_args['tax_query'] = $tax_query;
		}

		$candidates = get_posts($query_args);
		if (!$candidates && $term_ids) {
			unset($query_args['tax_query']);
			$candidates = get_posts($query_args);
		}

		$scored = array();
		foreach ($candidates as $candidate) {
			if (!$candidate instanceof WP_Post) {
				continue;
			}

			$candidate_id = (int) $candidate->ID;
			if ('yes' === get_post_meta($candidate_id, '_bbb_missing_download_url', true)) {
				continue;
			}

			if ($is_template_product && !bbb_single_product_is_canva_template($candidate_id)) {
				continue;
			}

			$candidate_terms    = bbb_single_product_related_terms($candidate_id);
			$candidate_keywords = bbb_single_product_related_keywords($candidate_id);
			$score              = count(array_intersect($term_ids, $candidate_terms)) * 10;
			$score             += count(array_intersect($keywords, $candidate_keywords)) * 7;
			$score             += bbb_single_product_is_kindle_insert($post_id) && bbb_single_product_is_kindle_insert($candidate_id) ? 12 : 0;
			$score             += $is_template_product && bbb_single_product_is_canva_template($candidate_id) ? 20 : 0;

			if ($score <= 0) {
				$score = 1;
			}

			$scored[] = array(
				'post'  => $candidate,
				'score' => $score,
			);
		}

		usort(
			$scored,
			static fn(array $a, array $b): int => ($b['score'] <=> $a['score']) ?: ((int) $b['post']->ID <=> (int) $a['post']->ID)
		);

		return array_map(
			static fn(array $item): WP_Post => $item['post'],
			array_slice($scored, 0, $limit)
		);
	}
}

if (!function_exists('bbb_single_product_filter_slug')) {
	function bbb_single_product_filter_slug(string $value): string {
		return sanitize_title(strtolower(trim($value)));
	}
}

if (!function_exists('bbb_single_product_color_groups')) {
	function bbb_single_product_color_groups(): array {
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

if (!function_exists('bbb_single_product_keyword_colors')) {
	function bbb_single_product_keyword_colors(string $haystack): array {
		$haystack = strtolower($haystack);
		$colors   = array();
		foreach (bbb_single_product_color_groups() as $color => $needles) {
			foreach ($needles as $needle) {
				if (preg_match('/(^|[^a-z0-9])' . preg_quote($needle, '/') . '([^a-z0-9]|$)/', $haystack)) {
					$colors[] = (string) $color;
					break;
				}
			}
		}

		return array_values(array_unique($colors));
	}
}

if (!function_exists('bbb_single_product_handle')) {
	function bbb_single_product_handle(int $post_id): string {
		$handle = (string) get_post_meta($post_id, '_bbb_shopify_product_handle', true);
		if ('' === trim($handle)) {
			$handle = (string) get_post_field('post_name', $post_id);
		}

		return sanitize_title($handle);
	}
}

if (!function_exists('bbb_single_product_visual_color_map')) {
	function bbb_single_product_visual_color_map(): array {
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
		$map  = is_array($data) ? $data : array();
		return $map;
	}
}

if (!function_exists('bbb_single_product_image_source')) {
	function bbb_single_product_image_source(string $image_url): string {
		$image_url = trim($image_url);
		if ('' === $image_url) {
			return '';
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

		return (str_starts_with($image_url, 'http://') || str_starts_with($image_url, 'https://')) ? $image_url : '';
	}
}

if (!function_exists('bbb_single_product_rgb_to_hsv')) {
	function bbb_single_product_rgb_to_hsv(int $red, int $green, int $blue): array {
		$r     = $red / 255;
		$g     = $green / 255;
		$b     = $blue / 255;
		$max   = max($r, $g, $b);
		$min   = min($r, $g, $b);
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

if (!function_exists('bbb_single_product_hue_bucket')) {
	function bbb_single_product_hue_bucket(float $hue): string {
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

		return 'pink';
	}
}

if (!function_exists('bbb_single_product_image_color_buckets')) {
	function bbb_single_product_image_color_buckets(string $image_source): array {
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
				list($hue, $saturation, $value) = bbb_single_product_rgb_to_hsv($red, $green, $blue);

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

				$bucket = bbb_single_product_hue_bucket($hue);
				$scores[$bucket] += 0.75 + $saturation + (1 - abs($value - 0.52));
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
			if ($bucket === $top_bucket || $score / $all_score >= 0.14 || $score / max($top_score, 1) >= 0.55) {
				$buckets[] = (string) $bucket;
			}
			if (count($buckets) >= 4) {
				break;
			}
		}

		return array_values(array_unique($buckets));
	}
}

if (!function_exists('bbb_single_product_can_compute_visual_matches')) {
	function bbb_single_product_can_compute_visual_matches(): bool {
		return (defined('WP_CLI') && WP_CLI)
			|| wp_doing_cron()
			|| (is_admin() && current_user_can('edit_posts'));
	}
}

if (!function_exists('bbb_single_product_color_tokens')) {
	function bbb_single_product_color_tokens(int $post_id, string $image_url): array {
		$handle = bbb_single_product_handle($post_id);
		if ('' !== $handle) {
			$map = bbb_single_product_visual_color_map();
			if (!empty($map[$handle]) && is_array($map[$handle])) {
				return array_values(array_filter(array_map('bbb_single_product_filter_slug', $map[$handle])));
			}
		}

		$cached = (string) get_post_meta($post_id, '_bbb_shop_visual_color_cache', true);
		if (str_contains($cached, '|')) {
			list(, $cached_colors) = explode('|', $cached, 2);
			if ('' !== trim($cached_colors)) {
				return array_values(array_filter(array_map('bbb_single_product_filter_slug', preg_split('/\s+/', $cached_colors) ?: array())));
			}
		}

		if (!bbb_single_product_can_compute_visual_matches()) {
			return bbb_single_product_keyword_colors(get_the_title($post_id));
		}

		$image_source = bbb_single_product_image_source($image_url);
		$colors       = '' !== $image_source ? bbb_single_product_image_color_buckets($image_source) : array();
		if (!$colors) {
			$colors = bbb_single_product_keyword_colors(get_the_title($post_id));
		}

		return array_values(array_filter(array_map('bbb_single_product_filter_slug', $colors)));
	}
}

if (!function_exists('bbb_single_product_book_data')) {
	function bbb_single_product_book_data(int $book_id): array {
		if (function_exists('bbb_books_like_book_data')) {
			return bbb_books_like_book_data($book_id);
		}

		return array(
			'id'              => $book_id,
			'title'           => function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($book_id)) : get_the_title($book_id),
			'author'          => function_exists('bbb_get_book_author') ? bbb_get_book_author($book_id) : '',
			'cover'           => function_exists('sss_get_book_cover_url') ? sss_get_book_cover_url($book_id) : (string) get_the_post_thumbnail_url($book_id, 'large'),
			'boyfriend'       => (string) get_post_meta($book_id, '_bbb_boyfriend_type', true),
			'boyfriend_name'  => (string) get_post_meta($book_id, '_bbb_boyfriend_name', true),
			'bookshop'        => (string) get_post_meta($book_id, '_bbb_bookshop_url', true),
			'amazon'          => (string) get_post_meta($book_id, '_bbb_amazon_url', true),
		);
	}
}

if (!function_exists('bbb_single_product_book_color_tokens')) {
	function bbb_single_product_book_color_tokens(int $book_id, string $cover_url): array {
		if ('' === trim($cover_url)) {
			return array();
		}

		$image_source   = bbb_single_product_image_source($cover_url);
		$source_version = '' !== $image_source && is_readable($image_source) ? (string) @filemtime($image_source) : md5($cover_url);
		$cache_key      = md5('book-cover-colors-v1|' . $cover_url . '|' . $source_version);
		$cached         = (string) get_post_meta($book_id, '_bbb_book_cover_color_cache', true);
		if (str_contains($cached, '|')) {
			list($cached_key, $cached_colors) = explode('|', $cached, 2);
			if ($cached_key === $cache_key && '' !== trim($cached_colors)) {
				return array_values(array_filter(array_map('bbb_single_product_filter_slug', preg_split('/\s+/', $cached_colors) ?: array())));
			}
		}

		if (!bbb_single_product_can_compute_visual_matches()) {
			return array();
		}

		$colors = '' !== $image_source ? bbb_single_product_image_color_buckets($image_source) : array();
		$colors = array_values(array_filter(array_map('bbb_single_product_filter_slug', $colors)));
		if ($colors) {
			update_post_meta($book_id, '_bbb_book_cover_color_cache', $cache_key . '|' . implode(' ', $colors));
		}

		return $colors;
	}
}

if (!function_exists('bbb_single_product_book_matches')) {
	function bbb_single_product_book_matches(int $post_id, string $image_url, int $limit = 4): array {
		$product_colors = bbb_single_product_color_tokens($post_id, $image_url);
		if (!$product_colors) {
			return array('colors' => array(), 'books' => array());
		}

		$match_colors = array_values(array_diff($product_colors, array('neutral', 'gray')));
		if (!$match_colors) {
			$match_colors = $product_colors;
		}

		$version   = function_exists('sss_library_cache_version') ? sss_library_cache_version() : (string) wp_get_theme()->get('Version');
		$cache_key = 'bbb_product_book_matches_' . $post_id . '_' . md5($version . '|' . $limit . '|' . implode(',', $match_colors));
		$cached    = get_transient($cache_key);
		if (is_array($cached)) {
			$books = array();
			foreach (array_slice($cached, 0, $limit) as $book_id) {
				$book = get_post((int) $book_id);
				if ($book instanceof WP_Post) {
					$data     = bbb_single_product_book_data((int) $book->ID);
					$books[] = array(
						'post'   => $book,
						'data'   => $data,
						'cover'  => (string) ($data['cover'] ?? ''),
						'colors' => bbb_single_product_book_color_tokens((int) $book->ID, (string) ($data['cover'] ?? '')),
					);
				}
			}

			return array('colors' => $match_colors, 'books' => $books);
		}

		if (!bbb_single_product_can_compute_visual_matches()) {
			return array('colors' => $match_colors, 'books' => array());
		}

		$book_posts = function_exists('sss_get_all_books') ? sss_get_all_books() : get_posts(
			array(
				'post_type'      => array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists')),
				'post_status'    => 'publish',
				'posts_per_page' => 160,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$scored = array();
		foreach (array_slice($book_posts, 0, 180) as $book) {
			if (!$book instanceof WP_Post) {
				continue;
			}

			$data      = bbb_single_product_book_data((int) $book->ID);
			$cover_url = (string) ($data['cover'] ?? '');
			if ('' === $cover_url) {
				continue;
			}

			$book_colors = bbb_single_product_book_color_tokens((int) $book->ID, $cover_url);
			$overlap     = array_values(array_intersect($match_colors, $book_colors));
			if (!$overlap) {
				continue;
			}

			$score = count($overlap) * 100;
			if (!empty($match_colors[0]) && in_array($match_colors[0], $book_colors, true)) {
				$score += 45;
			}
			if (!empty($book_colors[0]) && in_array($book_colors[0], $match_colors, true)) {
				$score += 25;
			}
			$scored[] = array(
				'post'   => $book,
				'data'   => $data,
				'cover'  => $cover_url,
				'colors' => $book_colors,
				'score'  => $score,
			);
		}

		usort(
			$scored,
			static fn(array $a, array $b): int => ($b['score'] <=> $a['score']) ?: ((int) $b['post']->ID <=> (int) $a['post']->ID)
		);

		$books = array_slice($scored, 0, $limit);
		set_transient($cache_key, wp_list_pluck(wp_list_pluck($books, 'post'), 'ID'), 6 * HOUR_IN_SECONDS);

		return array('colors' => $match_colors, 'books' => $books);
	}
}

$post_id       = get_the_ID();
$image_url     = bbb_single_product_image($post_id);
$price         = bbb_single_product_price($post_id);
$compare_price = bbb_single_product_compare_price($post_id);
$file_count    = bbb_single_product_file_count($post_id);
$is_full_vault_product = function_exists('bbb_vault_full_access_product_ids') && in_array($post_id, bbb_vault_full_access_product_ids(), true);
$missing_files = !$is_full_vault_product && 'yes' === get_post_meta($post_id, '_bbb_missing_download_url', true) && 0 === $file_count;
$is_july_staged = bbb_single_product_is_july_2026_staged($post_id);
$can_purchase  = ($is_full_vault_product || 0 < $file_count) && !$missing_files && !$is_july_staged;
$is_free       = function_exists('bbb_society_product_has_member_access') && bbb_society_product_has_member_access($post_id);
$kind          = bbb_single_product_kind_label($post_id);
$edit_url      = get_edit_post_link($post_id);
$is_kindle_insert = bbb_single_product_is_kindle_insert($post_id);
$is_canva_template = bbb_single_product_is_canva_template($post_id);
$related_title = $is_canva_template ? 'other canva templates' : 'more like this';
$related_products = bbb_single_product_related_products($post_id, 3);
$book_matches = $is_kindle_insert ? bbb_single_product_book_matches($post_id, $image_url, 3) : array('colors' => array(), 'books' => array());
?>

<main class="bbb-product" id="main">
	<section class="bbb-product__hero">
		<div class="bbb-product__media">
			<?php if ($image_url) : ?>
				<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
			<?php else : ?>
				<span><?php echo esc_html(substr(get_the_title(), 0, 1)); ?></span>
			<?php endif; ?>
		</div>

		<div class="bbb-product__summary">
			<p class="bbb-shop__kicker">digital shop</p>
			<?php if ($is_kindle_insert) : ?>
				<a class="bbb-shop-card__details bbb-product__hubLink" href="<?php echo esc_url(home_url('/kindle-inserts/')); ?>">browse all kindle inserts</a>
			<?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<div class="bbb-shop-card__badges">
				<span><?php echo esc_html($kind); ?></span>
				<?php if ($is_free) : ?>
					<span>member access</span>
				<?php endif; ?>
				<?php if ($missing_files) : ?>
					<span>needs file</span>
				<?php endif; ?>
			</div>
			<div class="bbb-product__meta">
				<strong>
					<?php if ('' !== $compare_price) : ?>
						<s><?php echo esc_html($compare_price); ?></s>
					<?php endif; ?>
					<?php echo esc_html($price); ?>
				</strong>
				<span><?php echo esc_html($is_full_vault_product ? 'on sale' : ($file_count ? $file_count . ' file' . (1 === $file_count ? '' : 's') : 'file pending')); ?></span>
			</div>
			<div class="bbb-product__actions">
				<?php if ($missing_files && current_user_can('edit_posts') && $edit_url) : ?>
					<a class="bbb-shop-card__button bbb-shop-card__button--ghost" href="<?php echo esc_url($edit_url); ?>">finish setup</a>
				<?php elseif ($can_purchase) : ?>
					<?php bbb_single_product_purchase_form($post_id); ?>
				<?php elseif ($is_july_staged) : ?>
					<span class="bbb-shop-card__button bbb-shop-card__button--disabled bbb-shop-card__button--locked" aria-disabled="true">coming july 1</span>
				<?php else : ?>
					<span class="bbb-shop-card__button bbb-shop-card__button--disabled" aria-disabled="true">coming soon</span>
				<?php endif; ?>
			</div>
			<?php if ($is_free) : ?>
				<p class="bbb-product__note">paid society members can access this through their membership.</p>
			<?php endif; ?>
		</div>
	</section>

	<?php if (trim(wp_strip_all_tags((string) get_the_content()))) : ?>
		<section class="bbb-product__details" aria-label="product details">
			<p class="bbb-shop__kicker">details</p>
			<div class="bbb-product__content">
				<?php
				$content = get_the_content(null, false, $post_id);
				if (function_exists('do_blocks')) {
					$content = do_blocks($content);
				}
				$content = do_shortcode(shortcode_unautop(wpautop($content)));
				echo wp_kses_post($content);
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ($related_products) : ?>
		<section class="bbb-product__related" aria-labelledby="bbb-product-related-title">
			<p class="bbb-shop__kicker">you may also like</p>
			<h2 id="bbb-product-related-title"><?php echo esc_html($related_title); ?></h2>
			<div class="bbb-product__relatedGrid">
				<?php foreach ($related_products as $related_product) : ?>
					<?php
					$related_id    = (int) $related_product->ID;
					$related_image = bbb_single_product_image($related_id);
					$related_price = bbb_single_product_price($related_id);
					$related_kind  = bbb_single_product_kind_label($related_id);
					?>
					<article class="bbb-product-related-card">
						<a class="bbb-product-related-card__media" href="<?php echo esc_url(get_permalink($related_id)); ?>">
							<?php if ($related_image) : ?>
								<img src="<?php echo esc_url($related_image); ?>" alt="<?php echo esc_attr(get_the_title($related_id)); ?>" loading="lazy">
							<?php else : ?>
								<span><?php echo esc_html(substr(get_the_title($related_id), 0, 1)); ?></span>
							<?php endif; ?>
						</a>
						<div class="bbb-product-related-card__body">
							<span><?php echo esc_html($related_kind); ?></span>
							<h3><a href="<?php echo esc_url(get_permalink($related_id)); ?>"><?php echo esc_html(get_the_title($related_id)); ?></a></h3>
							<p><?php echo esc_html($related_price); ?></p>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if (!empty($book_matches['books'])) : ?>
		<section class="bbb-product__bookMatches" aria-labelledby="bbb-product-book-matches-title">
			<div class="bbb-product__bookMatchesHeader">
				<div>
					<p class="bbb-shop__kicker">reads for you &amp; your new insert</p>
					<h2 id="bbb-product-book-matches-title">books to match the aesthetic</h2>
				</div>
				<?php if (!empty($book_matches['colors'])) : ?>
					<div class="bbb-product__colorChips" aria-label="matched colors">
						<?php foreach ($book_matches['colors'] as $match_color) : ?>
							<span><?php echo esc_html($match_color); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="bbb-product__bookGrid">
					<?php foreach ($book_matches['books'] as $book_item) : ?>
						<?php
						$book       = $book_item['post'] ?? null;
						$book_data  = is_array($book_item['data'] ?? null) ? $book_item['data'] : array();
						$book_cover = (string) ($book_item['cover'] ?? $book_data['cover'] ?? '');
						if (!$book instanceof WP_Post) {
							continue;
						}
						$book_author = trim((string) ($book_data['author'] ?? ''));
						$book_url    = get_permalink($book);
						?>
						<article class="bbb-product-book-card">
							<a class="bbb-product-book-card__cover" href="<?php echo esc_url($book_url); ?>">
								<?php if ($book_cover) : ?>
									<img src="<?php echo esc_url($book_cover); ?>" alt="<?php echo esc_attr(get_the_title($book)); ?>" loading="lazy">
								<?php else : ?>
									<span><?php echo esc_html(substr(get_the_title($book), 0, 1)); ?></span>
								<?php endif; ?>
							</a>
							<div class="bbb-product-book-card__body">
								<h3><a href="<?php echo esc_url($book_url); ?>"><?php echo esc_html(get_the_title($book)); ?></a></h3>
								<?php if ($book_author) : ?>
								<p><?php echo esc_html($book_author); ?></p>
							<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</main>
