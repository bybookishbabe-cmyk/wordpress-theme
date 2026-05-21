<?php
declare(strict_types=1);

if (!function_exists('bbb_sss_drop_field_map')) {
	function bbb_sss_drop_field_map(array $entry): array {
		$fields = array();
		foreach ((array) ($entry['fields'] ?? array()) as $field) {
			if (!is_array($field) || empty($field['key'])) {
				continue;
			}

			$fields[(string) $field['key']] = $field;
		}

		return $fields;
	}
}

if (!function_exists('bbb_sss_drop_value')) {
	function bbb_sss_drop_value(array $fields, string $key, string $default = ''): string {
		if (empty($fields[$key]) || !is_array($fields[$key])) {
			return $default;
		}

		$field = $fields[$key];
		$value = $field['jsonValue'] ?? $field['value'] ?? $default;
		if (is_array($value)) {
			$value = $field['value'] ?? $default;
		}

		return trim((string) $value);
	}
}

if (!function_exists('bbb_sss_drop_file_url')) {
	function bbb_sss_drop_file_url(array $fields, string $key): string {
		if (empty($fields[$key]) || !is_array($fields[$key])) {
			return '';
		}

		$field = $fields[$key];
		$url   = $field['reference']['image']['url'] ?? $field['reference']['url'] ?? '';

		return is_string($url) ? $url : '';
	}
}

if (!function_exists('bbb_sss_drop_file_urls')) {
	function bbb_sss_drop_file_urls(array $fields, string $key): array {
		if (empty($fields[$key]['references']['nodes']) || !is_array($fields[$key]['references']['nodes'])) {
			return array();
		}

		$urls = array();
		foreach ($fields[$key]['references']['nodes'] as $node) {
			$url = $node['image']['url'] ?? $node['url'] ?? '';
			if (is_string($url) && '' !== $url) {
				$urls[] = $url;
			}
		}

		return $urls;
	}
}

if (!function_exists('bbb_sss_drop_file_items')) {
	function bbb_sss_drop_file_items(array $fields, string $key): array {
		if (empty($fields[$key]['references']['nodes']) || !is_array($fields[$key]['references']['nodes'])) {
			return array();
		}

		$items = array();
		foreach ($fields[$key]['references']['nodes'] as $node) {
			$url = $node['image']['url'] ?? $node['url'] ?? '';
			if (!is_string($url) || '' === $url) {
				continue;
			}

			$items[] = array(
				'url' => $url,
				'alt' => (string) ($node['image']['altText'] ?? ''),
			);
		}

		return $items;
	}
}

if (!function_exists('bbb_sss_drop_reference_items')) {
	function bbb_sss_drop_reference_items(array $fields, string $key): array {
		if (empty($fields[$key]['references']['nodes']) || !is_array($fields[$key]['references']['nodes'])) {
			return array();
		}

		return $fields[$key]['references']['nodes'];
	}
}

if (!function_exists('bbb_sss_drop_reference_item')) {
	function bbb_sss_drop_reference_item(array $fields, string $key): array {
		return !empty($fields[$key]['reference']) && is_array($fields[$key]['reference']) ? $fields[$key]['reference'] : array();
	}
}

if (!function_exists('bbb_sss_drop_link_url')) {
	function bbb_sss_drop_link_url(array $fields, string $key): string {
		if (empty($fields[$key]) || !is_array($fields[$key])) {
			return '';
		}

		$field = $fields[$key];
		$value = $field['jsonValue'] ?? $field['value'] ?? '';
		if (is_array($value)) {
			return trim((string) ($value['url'] ?? ''));
		}

		$value = trim((string) $value);
		if (str_starts_with($value, '{')) {
			$decoded = json_decode($value, true);
			return is_array($decoded) ? trim((string) ($decoded['url'] ?? '')) : '';
		}

		return $value;
	}
}

if (!function_exists('bbb_sss_drop_product_url')) {
	function bbb_sss_drop_product_url(array $product): string {
		$handle = sanitize_title((string) ($product['handle'] ?? ''));
		if ('' === $handle) {
			return bbb_page_url('shop');
		}

		if (post_type_exists('product')) {
			$wp_product = get_page_by_path($handle, OBJECT, 'product');
			if ($wp_product instanceof WP_Post) {
				return get_permalink($wp_product);
			}
		}

		return home_url('/product/' . $handle . '/');
	}
}

if (!function_exists('bbb_sss_drop_normalize_asset_url')) {
	function bbb_sss_drop_normalize_asset_url(string $url): string {
		$url = trim($url);
		if ('' === $url) {
			return '';
		}

		if (str_starts_with($url, '//')) {
			$url = 'https:' . $url;
		}

		if (str_contains($url, 'dropbox.com')) {
			$url = str_replace('www.dropbox.com', 'dl.dropboxusercontent.com', $url);
			$url = remove_query_arg(array('dl', 'raw'), $url);
		}

		if (str_starts_with($url, '/wp-content/')) {
			return bbb_sss_drop_local_upload_url($url);
		}

		$path = (string) wp_parse_url($url, PHP_URL_PATH);
		if (str_starts_with($path, '/wp-content/')) {
			$query = (string) wp_parse_url($url, PHP_URL_QUERY);
			return bbb_sss_drop_local_upload_url($path . ('' !== $query ? '?' . $query : ''));
		}

		return esc_url_raw($url);
	}
}

if (!function_exists('bbb_sss_drop_local_upload_url')) {
	function bbb_sss_drop_local_upload_url(string $path): string {
		$url = home_url($path);
		$host = (string) wp_parse_url($url, PHP_URL_HOST);
		if (preg_match('/(^localhost$|^127\.|\.local$)/', $host)) {
			$url = set_url_scheme($url, 'http');
		}

		return esc_url_raw($url);
	}
}

if (!function_exists('bbb_sss_drop_size_label')) {
	function bbb_sss_drop_size_label(array $file): string {
		if (function_exists('bbb_society_product_importer_size_label')) {
			$label = bbb_society_product_importer_size_label($file);
			if ('' !== $label) {
				return $label;
			}
		}

		$haystack = strtolower(rawurldecode((string) ($file['name'] ?? '') . ' ' . (string) ($file['url'] ?? '') . ' ' . (string) ($file['file'] ?? '')));
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

		$name = trim((string) ($file['name'] ?? ''));
		return '' !== $name ? strtolower((string) preg_replace('/\.[^.]+$/', '', $name)) : 'download';
	}
}

if (!function_exists('bbb_sss_drop_product_export_index')) {
	function bbb_sss_drop_product_export_index(): array {
		static $index = null;
		if (null !== $index) {
			return $index;
		}

		$index = array();
		$files = array(
			'/firstpass/migration/exports/products/society-products-free-for-members.json',
			'/firstpass/migration/exports/products/society-products.json',
			'/firstpass/migration/exports/products/digital-products.json',
		);

		foreach ($files as $relative_path) {
			$path = get_template_directory() . $relative_path;
			if (!is_readable($path)) {
				continue;
			}

			$rows = json_decode((string) file_get_contents($path), true);
			if (!is_array($rows)) {
				continue;
			}

			foreach ($rows as $row) {
				if (!is_array($row)) {
					continue;
				}

				$handle = sanitize_title((string) ($row['handle'] ?? ''));
				if ('' !== $handle && empty($index[$handle])) {
					$index[$handle] = $row;
				}
			}
		}

		return $index;
	}
}

if (!function_exists('bbb_sss_drop_product_export')) {
	function bbb_sss_drop_product_export(string $handle): array {
		$index = bbb_sss_drop_product_export_index();
		return $index[$handle] ?? array();
	}
}

if (!function_exists('bbb_sss_drop_edd_product_post')) {
	function bbb_sss_drop_edd_product_post(string $handle): ?WP_Post {
		if ('' === $handle || !post_type_exists('download')) {
			return null;
		}

		$post = get_page_by_path($handle, OBJECT, 'download');
		return $post instanceof WP_Post ? $post : null;
	}
}

if (!function_exists('bbb_sss_drop_product_image_url')) {
	function bbb_sss_drop_product_image_url(array $product, string $handle): string {
		$post = bbb_sss_drop_edd_product_post($handle);
		if ($post instanceof WP_Post) {
			$thumbnail = get_the_post_thumbnail_url($post, 'medium_large');
			if (is_string($thumbnail) && '' !== $thumbnail) {
				return $thumbnail;
			}
		}

		$export = bbb_sss_drop_product_export($handle);
		$image = trim((string) ($export['image_url'] ?? $product['image_url'] ?? $product['featuredImage']['url'] ?? ''));
		return bbb_sss_drop_normalize_asset_url($image);
	}
}

if (!function_exists('bbb_sss_drop_unique_urls')) {
	function bbb_sss_drop_unique_urls(array $urls): array {
		$seen = array();
		$unique = array();

		foreach ($urls as $url) {
			$url = trim((string) $url);
			if ('' === $url || isset($seen[$url])) {
				continue;
			}

			$seen[$url] = true;
			$unique[] = $url;
		}

		return $unique;
	}
}

if (!function_exists('bbb_sss_drop_hex_to_rgb')) {
	function bbb_sss_drop_hex_to_rgb(string $hex): array {
		$hex = ltrim(trim($hex), '#');
		if (3 === strlen($hex)) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if (6 !== strlen($hex) || !ctype_xdigit($hex)) {
			return array(255, 138, 199);
		}

		return array(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
	}
}

if (!function_exists('bbb_sss_drop_export_download_files')) {
	function bbb_sss_drop_export_download_files(array $export): array {
		$raw = $export['download_files'] ?? $export['downloadFiles'] ?? array();
		if (is_string($raw) && '' !== trim($raw)) {
			$decoded = json_decode($raw, true);
			$raw = is_array($decoded) ? $decoded : array();
		}

		$files = array();
		foreach ((array) $raw as $file) {
			if (!is_array($file)) {
				continue;
			}

			$url = bbb_sss_drop_normalize_asset_url((string) ($file['url'] ?? $file['file'] ?? ''));
			if ('' === $url) {
				continue;
			}

			$files[] = array(
				'label' => bbb_sss_drop_size_label($file),
				'name'  => (string) ($file['name'] ?? ''),
				'url'   => $url,
			);
		}

		return $files;
	}
}

if (!function_exists('bbb_sss_drop_product_download_files')) {
	function bbb_sss_drop_product_download_files(string $handle): array {
		$files = array();
		$post = bbb_sss_drop_edd_product_post($handle);

		if ($post instanceof WP_Post) {
			$price_labels = array();
			$prices = get_post_meta($post->ID, 'edd_variable_prices', true);
			if (is_array($prices)) {
				foreach ($prices as $price_id => $price) {
					if (is_array($price) && !empty($price['name'])) {
						$price_labels[(string) $price_id] = strtolower((string) $price['name']);
					}
				}
			}

			$edd_files = get_post_meta($post->ID, 'edd_download_files', true);
			if (is_array($edd_files)) {
				foreach ($edd_files as $file) {
					if (!is_array($file)) {
						continue;
					}

					$url = bbb_sss_drop_normalize_asset_url((string) ($file['file'] ?? $file['url'] ?? ''));
					if ('' === $url) {
						continue;
					}

					$condition = (string) ($file['condition'] ?? '');
					$files[] = array(
						'label' => $price_labels[$condition] ?? bbb_sss_drop_size_label($file),
						'name'  => (string) ($file['name'] ?? ''),
						'url'   => $url,
					);
				}
			}
		}

		if (!$files) {
			$files = bbb_sss_drop_export_download_files(bbb_sss_drop_product_export($handle));
		}

		$seen = array();
		$files = array_values(
			array_filter(
				$files,
				static function (array $file) use (&$seen): bool {
					$key = strtolower((string) ($file['label'] ?? '') . '|' . (string) ($file['url'] ?? ''));
					if ('' === trim((string) ($file['url'] ?? '')) || isset($seen[$key])) {
						return false;
					}

					$seen[$key] = true;
					return true;
				}
			)
		);

		$order = array('6 inch' => 1, '10th gen' => 2, '11th gen' => 3, '12th gen' => 4);
		usort(
			$files,
			static fn(array $a, array $b): int => ($order[strtolower((string) ($a['label'] ?? ''))] ?? 99) <=> ($order[strtolower((string) ($b['label'] ?? ''))] ?? 99)
		);

		return $files;
	}
}

if (!function_exists('bbb_sss_render_drop_products')) {
	function bbb_sss_render_drop_products(array $products, string $heading, string $kicker): void {
		if (!$products) {
			return;
		}
		?>
		<section class="sss-drop-theme__products">
			<div class="sss-drop-theme__sectionHead">
				<p><?php echo esc_html($kicker); ?></p>
				<h2><?php echo esc_html($heading); ?></h2>
			</div>
			<div class="sss-drop-theme__productGrid">
				<?php foreach ($products as $product) : ?>
					<?php
					$title  = strtolower((string) ($product['title'] ?? 'printable'));
					$handle = sanitize_title((string) ($product['handle'] ?? ''));
					$url    = bbb_sss_drop_product_url($product);
					$image  = bbb_sss_drop_product_image_url($product, $handle);
					$files  = bbb_sss_drop_product_download_files($handle);
					?>
					<article class="sss-drop-theme__productCard" data-shopify-product-handle="<?php echo esc_attr($handle); ?>">
						<?php if ('' !== $image) : ?>
							<a class="sss-drop-theme__productMedia" href="<?php echo esc_url($url); ?>">
								<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="eager">
							</a>
						<?php endif; ?>
						<div class="sss-drop-theme__productBody">
							<a class="sss-drop-theme__productTitle" href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
							<?php if ($files) : ?>
								<div class="sss-drop-theme__downloadGrid" aria-label="<?php echo esc_attr($title . ' downloads'); ?>">
									<?php foreach ($files as $file) : ?>
										<a href="<?php echo esc_url((string) $file['url']); ?>" target="_blank" rel="noopener" download>
											<span><?php echo esc_html((string) ($file['label'] ?? 'download')); ?></span>
											<small>download</small>
										</a>
									<?php endforeach; ?>
								</div>
							<?php else : ?>
								<a class="sss-drop-theme__productFallback" href="<?php echo esc_url($url); ?>">open product</a>
								<small class="sss-drop-theme__downloadMissing">download files need attaching</small>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}

if (!function_exists('bbb_sss_active_drop')) {
	function bbb_sss_active_drop(): array {
		if (function_exists('bbb_sss_drop_importer_active_entry')) {
			$imported = bbb_sss_drop_importer_active_entry();
			if ($imported) {
				return $imported;
			}
		}

		$current_handle = '';
		if (function_exists('bbb_sss_drop_importer_current_handle')) {
			$current_handle = bbb_sss_drop_importer_current_handle();
		} elseif (function_exists('get_field')) {
			$field_handle = get_field('current_drop_handle', 'option');
			$current_handle = is_string($field_handle) ? sanitize_title($field_handle) : '';
		}

		$imported_posts = get_posts(
			array(
				'post_type'      => 'sss_drop',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => '_bbb_sss_drop_release_date',
				'orderby'        => 'meta_value',
				'order'          => 'DESC',
			)
		);

		if ($imported_posts) {
			$today = (int) current_time('timestamp');
			$best = null;
			$best_time = 0;
			$fallback = null;
			$fallback_time = 0;
			$newest = null;
			$newest_time = 0;

			foreach ($imported_posts as $post) {
				if (!$post instanceof WP_Post) {
					continue;
				}

				$post_handle = sanitize_title((string) get_post_meta($post->ID, '_bbb_sss_drop_handle', true));
				if ('' !== $current_handle && ($post->post_name === $current_handle || $post_handle === $current_handle)) {
					$entry = json_decode((string) get_post_meta($post->ID, '_bbb_sss_drop_entry', true), true);
					return is_array($entry) ? $entry : array();
				}

				$release = (string) get_post_meta($post->ID, '_bbb_sss_drop_release_date', true);
				$time = '' !== $release ? strtotime($release . ' 00:00:00') : false;
				if (!$time) {
					continue;
				}

				$end = (string) get_post_meta($post->ID, '_bbb_sss_drop_end_date', true);
				$end_time = '' !== $end ? strtotime($end . ' 23:59:59') : false;
				if ($time <= $today && (!$end_time || $end_time >= $today) && $time >= $best_time) {
					$best = $post;
					$best_time = $time;
				}

				if ($time <= $today && $time >= $fallback_time) {
					$fallback = $post;
					$fallback_time = $time;
				}

				if ($time >= $newest_time) {
					$newest = $post;
					$newest_time = $time;
				}
			}

			$active_post = $best ?: $fallback ?: $newest ?: $imported_posts[0];
			if ($active_post instanceof WP_Post) {
				$entry = json_decode((string) get_post_meta($active_post->ID, '_bbb_sss_drop_entry', true), true);
				if (is_array($entry)) {
					return $entry;
				}
			}
		}

		global $wpdb;
		if (is_object($wpdb) && isset($wpdb->posts, $wpdb->postmeta)) {
			$rows = $wpdb->get_results(
				"SELECT p.ID, p.post_name, entry_meta.meta_value AS entry_json, handle_meta.meta_value AS handle, release_meta.meta_value AS release_date, end_meta.meta_value AS end_date
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} entry_meta ON entry_meta.post_id = p.ID AND entry_meta.meta_key = '_bbb_sss_drop_entry'
				LEFT JOIN {$wpdb->postmeta} handle_meta ON handle_meta.post_id = p.ID AND handle_meta.meta_key = '_bbb_sss_drop_handle'
				LEFT JOIN {$wpdb->postmeta} release_meta ON release_meta.post_id = p.ID AND release_meta.meta_key = '_bbb_sss_drop_release_date'
				LEFT JOIN {$wpdb->postmeta} end_meta ON end_meta.post_id = p.ID AND end_meta.meta_key = '_bbb_sss_drop_end_date'
				WHERE p.post_type = 'sss_drop' AND p.post_status = 'publish'
				ORDER BY release_meta.meta_value DESC, p.ID DESC"
			);

			if (is_array($rows) && $rows) {
				$today = (int) current_time('timestamp');
				$best = null;
				$best_time = 0;
				$fallback = null;
				$fallback_time = 0;
				$newest = null;
				$newest_time = 0;

				foreach ($rows as $row) {
					$row_handle = sanitize_title((string) ($row->handle ?? ''));
					$post_name = sanitize_title((string) ($row->post_name ?? ''));
					if ('' !== $current_handle && ($row_handle === $current_handle || $post_name === $current_handle)) {
						$entry = json_decode((string) ($row->entry_json ?? ''), true);
						return is_array($entry) ? $entry : array();
					}

					$release = (string) ($row->release_date ?? '');
					$time = '' !== $release ? strtotime($release . ' 00:00:00') : false;
					if (!$time) {
						continue;
					}

					$end = (string) ($row->end_date ?? '');
					$end_time = '' !== $end ? strtotime($end . ' 23:59:59') : false;
					if ($time <= $today && (!$end_time || $end_time >= $today) && $time >= $best_time) {
						$best = $row;
						$best_time = $time;
					}

					if ($time <= $today && $time >= $fallback_time) {
						$fallback = $row;
						$fallback_time = $time;
					}

					if ($time >= $newest_time) {
						$newest = $row;
						$newest_time = $time;
					}
				}

				$active_row = $best ?: $fallback ?: $newest ?: $rows[0];
				if ($active_row) {
					$entry = json_decode((string) ($active_row->entry_json ?? ''), true);
					if (is_array($entry)) {
						return $entry;
					}
				}
			}
		}

		$path = get_theme_file_path('firstpass/migration/exports/metaobjects/sss_drop.json');
		if (!file_exists($path)) {
			return array();
		}

		$raw = file_get_contents($path);
		if (!is_string($raw) || '' === trim($raw)) {
			return array();
		}

		$data = json_decode($raw, true);
		if (!is_array($data) || empty($data['entries']) || !is_array($data['entries'])) {
			return array();
		}

		$today    = (int) current_time('timestamp');
		$best     = array();
		$best_time = 0;
		$fallback = array();
		$fallback_time = 0;
		$newest = array();
		$newest_time = 0;
		foreach ($data['entries'] as $entry) {
			if (!is_array($entry)) {
				continue;
			}

			if ('' !== $current_handle && sanitize_title((string) ($entry['handle'] ?? '')) === $current_handle) {
				return $entry;
			}

			$fields = bbb_sss_drop_field_map($entry);
			$date   = bbb_sss_drop_value($fields, 'release_date');
			if ('' === $date) {
				continue;
			}

			$time = strtotime($date . ' 00:00:00');
			if (!$time) {
				continue;
			}

			$end_date = bbb_sss_drop_value($fields, 'end_date');
			$end_time = '' !== $end_date ? strtotime($end_date . ' 23:59:59') : false;
			if ($time <= $today && (!$end_time || $end_time >= $today) && $time >= $best_time) {
				$best      = $entry;
				$best_time = $time;
			}

			if ($time <= $today && $time >= $fallback_time) {
				$fallback      = $entry;
				$fallback_time = $time;
			}

			if ($time >= $newest_time) {
				$newest      = $entry;
				$newest_time = $time;
			}
		}

		return $best ?: $fallback ?: $newest;
	}
}

$drop = bbb_sss_active_drop();
$fields = $drop ? bbb_sss_drop_field_map($drop) : array();

if (!$drop) {
	?>
	<section class="sss-drop-theme sss-drop-theme--empty">
		<div class="sss-drop-theme__wrap">
			<header class="sss-drop-theme__hero">
				<p class="sss-drop-theme__kicker">monthly theme</p>
				<h1>drop data missing</h1>
				<p class="sss-drop-theme__mood">import the Shopify <code>sss_drop.json</code> in WordPress under <code>Users > Society Drops</code>, or keep the export file at <code>firstpass/migration/exports/metaobjects/sss_drop.json</code>.</p>
			</header>
		</div>
	</section>
	<style>
	.sss-drop-theme{background:#050505;color:#f6f6f6;padding:clamp(34px,6vw,76px) 18px;text-transform:lowercase}
	.sss-drop-theme__wrap{width:min(1180px,100%);margin:0 auto}
	.sss-drop-theme__hero{text-align:center;padding:10px 0 30px}
	.sss-drop-theme__kicker{margin:0 0 8px;color:#ff8ac7;font-size:11px;letter-spacing:.16em;text-transform:lowercase}
	.sss-drop-theme h1{margin:0;color:#fff;font-family:Cormorant,"Cormorant Garamond",Georgia,serif;font-size:clamp(48px,8vw,104px);font-weight:400;line-height:.9;letter-spacing:0;text-transform:lowercase}
	.sss-drop-theme__mood{max-width:760px;margin:18px auto 0;color:rgba(246,246,246,.72);font-size:15px;line-height:1.6}
	.sss-drop-theme code{color:#fff}
	</style>
	<?php
	return;
}

$name       = bbb_sss_drop_value($fields, 'name', 'monthly theme');
$mood_title = bbb_sss_drop_value($fields, 'moodboard_title', $name);
$quote      = bbb_sss_drop_value($fields, 'quote_text', 'the atmosphere, the notes, and the songs are all gathered here.');
$accent     = bbb_sss_drop_value($fields, 'mood_accent', '#ff8ac7');
$pill_bg    = bbb_sss_drop_value($fields, 'mood_pill_bg', '#151515');
$pill_ink   = bbb_sss_drop_value($fields, 'mood_pill_ink', '#f6f6f6');
$emoji_list = bbb_sss_drop_value($fields, 'emoji_list', '🕯️, 🖤, ✦');
$gram_image = bbb_sss_drop_file_url($fields, 'gram_image');
$gram_kicker = bbb_sss_drop_value($fields, 'gram_kicker', 'from the gram');
$gram_title  = bbb_sss_drop_value($fields, 'gram_title', 'this belongs on your kindle');
$gram_sub    = bbb_sss_drop_value($fields, 'gram_sub', '');
$gram_caption = bbb_sss_drop_value($fields, 'gram_caption', '');
$spotify_url = bbb_sss_drop_value($fields, 'spotify_url');
$spotify_id  = '';
if ('' !== $spotify_url) {
	$spotify_path = (string) wp_parse_url($spotify_url, PHP_URL_PATH);
	$spotify_bits = array_values(array_filter(explode('/', $spotify_path)));
	$spotify_id = (string) end($spotify_bits);
}
$calendar_image = bbb_sss_drop_file_url($fields, 'calendar_image');
$calendar_pdf   = bbb_sss_drop_file_url($fields, 'calendar_pdf');
$pdf_link       = bbb_sss_drop_link_url($fields, 'pdf_link');
$canva_link     = bbb_sss_drop_link_url($fields, 'canva_link');
$wallpapers = bbb_sss_drop_file_items($fields, 'wallpaper_images');
if (!$wallpapers) {
	$wallpapers = bbb_sss_drop_file_items($fields, 'mood_images');
}
$mood_images = bbb_sss_drop_file_items($fields, 'mood_images');
$mood_stickers = bbb_sss_drop_file_items($fields, 'mood_stickers');
$era_images = bbb_sss_drop_file_items($fields, 'era_images');
$mood_quotes_raw = $fields['mood_quotes']['jsonValue'] ?? array();
$mood_quotes = is_array($mood_quotes_raw) ? array_values(array_filter(array_map('strval', $mood_quotes_raw))) : array();
$prompts_raw = bbb_sss_drop_value($fields, 'prompts');
$prompts = array_values(
	array_filter(
		array_map('trim', preg_split('/\s*\|\|\s*/', $prompts_raw) ?: array())
	)
);
$journal_start = bbb_sss_drop_value($fields, 'journal_start_date');
$daily_prompt = array(
	'text'  => '',
	'day'   => 0,
	'total' => count($prompts),
);

if ($prompts) {
	$start = strtotime((string) $journal_start . ' 00:00:00');
	$today = (int) current_time('timestamp');
	$day   = 1;

	if (false !== $start) {
		$day = (int) floor(($today - $start) / (60 * 60 * 24)) + 1;
	}

	if ($day < 1) {
		$day = 1;
	} elseif ($day > $daily_prompt['total']) {
		$day = $daily_prompt['total'];
	}

	$daily_prompt['day'] = $day;
	$daily_prompt['text'] = (string) ($prompts[$day - 1] ?? '');

	if ('' === $daily_prompt['text']) {
		$daily_prompt = array(
			'text'  => '',
			'day'   => 0,
			'total' => $daily_prompt['total'],
		);
	}
}
$mood_pills = bbb_sss_drop_reference_items($fields, 'trial');
$printable_products = bbb_sss_drop_reference_items($fields, 'monthly_collection_printable_products');
$physical_products = bbb_sss_drop_reference_items($fields, 'monthly_collection_physical_products');
$bonus_printable = bbb_sss_drop_reference_item($fields, 'bonus_printable_product');
$bonus_physical = bbb_sss_drop_reference_item($fields, 'bonus_physical_product');
$bonus_products = array_values(array_filter(array($bonus_printable, $bonus_physical)));
$drop_handle = (string) ($drop['handle'] ?? '');
$product_visuals = array();
foreach (array_merge($printable_products, $physical_products, $bonus_products) as $visual_product) {
	if (!is_array($visual_product)) {
		continue;
	}

	$visual_handle = sanitize_title((string) ($visual_product['handle'] ?? ''));
	$visual_url = bbb_sss_drop_product_image_url($visual_product, $visual_handle);
	if ('' !== $visual_url) {
		$product_visuals[] = $visual_url;
	}
}

$mood_visuals = array_values(
	array_filter(
		array_map(
			static fn(array $item): string => (string) ($item['url'] ?? ''),
			array_merge($mood_images, $mood_stickers, $era_images, $wallpapers)
		)
	)
);
$drop_visuals = bbb_sss_drop_unique_urls(array_merge(array($calendar_image, $gram_image), $product_visuals, $mood_visuals));
$hero_visual = (string) ($drop_visuals[0] ?? '');
$hero_support_visuals = array_slice($drop_visuals, 1, 5);
list($accent_r, $accent_g, $accent_b) = bbb_sss_drop_hex_to_rgb($accent);
$accent_rgb = $accent_r . ', ' . $accent_g . ', ' . $accent_b;
$drop_nav = array_filter(
	array(
		array('href' => '#drop-atmosphere', 'label' => 'atmosphere', 'show' => '' !== $gram_image || '' !== $spotify_id),
		array('href' => '#drop-moodboard', 'label' => 'moodboard', 'show' => (bool) ($mood_images || $mood_stickers || $era_images || $mood_quotes)),
		array('href' => '#drop-wallpapers', 'label' => 'wallpapers', 'show' => (bool) $wallpapers),
		array('href' => '#drop-calendar', 'label' => 'calendar', 'show' => '' !== $calendar_image || (bool) $prompts),
		array('href' => '#drop-products', 'label' => 'shop the drop', 'show' => (bool) ($printable_products || $physical_products || $bonus_products)),
	),
	static fn(array $item): bool => (bool) $item['show']
);
?>
<section class="sss-drop-theme" style="--drop-accent: <?php echo esc_attr($accent); ?>; --drop-accent-rgb: <?php echo esc_attr($accent_rgb); ?>; --drop-pill-bg: <?php echo esc_attr($pill_bg); ?>; --drop-pill-ink: <?php echo esc_attr($pill_ink); ?>;">
	<div class="sss-drop-theme__wrap">
		<header class="sss-drop-theme__hero">
			<div class="sss-drop-theme__heroCopy">
				<p class="sss-drop-theme__kicker">monthly theme</p>
				<h1><?php echo esc_html(strtolower($name)); ?></h1>
				<p class="sss-drop-theme__mood"><?php echo esc_html(strtolower($mood_title)); ?></p>
				<?php if ('' !== $quote) : ?>
					<blockquote><?php echo nl2br(esc_html(strtolower($quote))); ?></blockquote>
				<?php endif; ?>
			</div>
			<?php if ('' !== $hero_visual) : ?>
				<div class="sss-drop-theme__heroArt" aria-label="monthly theme visual preview">
					<div class="sss-drop-theme__heroFrame">
						<img src="<?php echo esc_url($hero_visual); ?>" alt="<?php echo esc_attr($name . ' theme preview'); ?>" loading="eager">
					</div>
					<?php if ($hero_support_visuals) : ?>
						<div class="sss-drop-theme__miniRail" aria-hidden="true">
							<?php foreach ($hero_support_visuals as $support_visual) : ?>
								<img src="<?php echo esc_url($support_visual); ?>" alt="" loading="eager">
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</header>

		<?php if ($drop_nav) : ?>
			<nav class="sss-drop-theme__nav" aria-label="monthly theme sections">
				<?php foreach ($drop_nav as $item) : ?>
					<a href="<?php echo esc_url($item['href']); ?>"><?php echo esc_html($item['label']); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<div class="sss-drop-theme__grid" id="drop-atmosphere">
			<?php if ('' !== $gram_image) : ?>
				<article class="sss-drop-theme__panel sss-drop-theme__panel--gram">
					<div>
						<p><?php echo esc_html(strtolower($gram_kicker)); ?></p>
						<h2><?php echo esc_html(strtolower($gram_title)); ?></h2>
						<?php if ('' !== $gram_sub) : ?>
							<span><?php echo esc_html(strtolower($gram_sub)); ?></span>
						<?php endif; ?>
					</div>
					<img src="<?php echo esc_url($gram_image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
					<?php if ('' !== $gram_caption) : ?>
						<small><?php echo esc_html(strtolower($gram_caption)); ?></small>
					<?php endif; ?>
				</article>
			<?php endif; ?>

			<?php if ('' !== $spotify_id) : ?>
				<article class="sss-drop-theme__panel">
					<p>playlist</p>
					<h2><?php echo esc_html(strtolower($name)); ?></h2>
					<iframe src="https://open.spotify.com/embed/playlist/<?php echo esc_attr($spotify_id); ?>?theme=0" width="100%" height="380" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"></iframe>
				</article>
			<?php endif; ?>
		</div>

		<?php if ($mood_images || $mood_stickers || $era_images || $mood_quotes) : ?>
			<section class="sss-drop-theme__moodboard" id="drop-moodboard">
				<div class="sss-drop-theme__sectionHead">
					<p>moodboard</p>
					<h2>the vibe file</h2>
				</div>
				<?php if ($mood_quotes) : ?>
					<div class="sss-drop-theme__quoteRow">
						<?php foreach ($mood_quotes as $mood_quote) : ?>
							<span><?php echo esc_html(strtolower($mood_quote)); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php foreach (array('mood images' => $mood_images, 'stickers' => $mood_stickers, 'era images' => $era_images) as $label => $assets) : ?>
					<?php if ($assets) : ?>
						<p class="sss-drop-theme__assetLabel"><?php echo esc_html($label); ?></p>
						<div class="sss-drop-theme__assetGrid">
							<?php foreach ($assets as $asset) : ?>
								<a href="<?php echo esc_url($asset['url']); ?>" target="_blank" rel="noopener">
									<img src="<?php echo esc_url($asset['url']); ?>" alt="<?php echo esc_attr($asset['alt'] ?: $name . ' ' . $label); ?>" loading="lazy">
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php if ($wallpapers) : ?>
			<section class="sss-drop-theme__wallpapers" id="drop-wallpapers">
				<div class="sss-drop-theme__sectionHead">
					<p>wallpapers</p>
					<h2>the visual file</h2>
					<?php if ('' !== bbb_sss_drop_value($fields, 'wallpaper_canva_url')) : ?>
						<a href="<?php echo esc_url(bbb_sss_drop_value($fields, 'wallpaper_canva_url')); ?>" target="_blank" rel="noopener">edit in canva</a>
					<?php endif; ?>
				</div>
				<div class="sss-drop-theme__wallpaperGrid">
					<?php foreach ($wallpapers as $index => $wallpaper) : ?>
						<a href="<?php echo esc_url($wallpaper['url']); ?>" target="_blank" rel="noopener">
							<img src="<?php echo esc_url($wallpaper['url']); ?>" alt="<?php echo esc_attr($wallpaper['alt'] ?: $name . ' wallpaper ' . ((int) $index + 1)); ?>" loading="lazy">
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ('' !== $calendar_image || $prompts) : ?>
			<section class="sss-drop-theme__calendar" id="drop-calendar">
				<div class="sss-drop-theme__sectionHead">
					<p>journal + calendar</p>
					<h2>daily prompts</h2>
					<div class="sss-drop-theme__actions">
						<?php if ('' !== $calendar_pdf) : ?>
							<a href="<?php echo esc_url($calendar_pdf); ?>" target="_blank" rel="noopener">download pdf</a>
						<?php endif; ?>
						<?php if ('' !== $pdf_link) : ?>
							<a href="<?php echo esc_url($pdf_link); ?>" target="_blank" rel="noopener">open pdf link</a>
						<?php endif; ?>
						<?php if ('' !== $canva_link) : ?>
							<a href="<?php echo esc_url($canva_link); ?>" target="_blank" rel="noopener">edit in canva</a>
						<?php endif; ?>
					</div>
				</div>
					<?php if ('' !== $calendar_image) : ?>
						<img class="sss-drop-theme__calendarImage" src="<?php echo esc_url($calendar_image); ?>" alt="<?php echo esc_attr($name . ' calendar'); ?>" loading="lazy">
					<?php endif; ?>
					<?php if ('' !== $daily_prompt['text']) : ?>
						<article class="sss-drop-theme__dailyPrompt" aria-label="daily journal prompt">
							<div class="sss-drop-theme__promptTop">
								<p class="sss-drop-theme__promptLabel">today's prompt</p>
								<span class="sss-drop-theme__promptDay"><?php echo esc_html('day ' . (string) $daily_prompt['day'] . ' of ' . (string) $daily_prompt['total']); ?></span>
							</div>
							<p class="sss-drop-theme__promptBody"><?php echo esc_html($daily_prompt['text']); ?></p>
						</article>
					<?php endif; ?>
				</section>
			<?php endif; ?>

		<div id="drop-products">
		<?php bbb_sss_render_drop_products($printable_products, 'printable kindle inserts', 'current drop'); ?>
		<?php bbb_sss_render_drop_products($physical_products, 'physical kindle inserts', 'current drop'); ?>
		<?php bbb_sss_render_drop_products($bonus_products, 'bonus products', 'member bonus'); ?>
		</div>
	</div>
</section>

<style>
.sss-drop-theme{position:relative;overflow:hidden;background:
	radial-gradient(circle at 16% 2%, rgba(var(--drop-accent-rgb),.32), transparent 34rem),
	radial-gradient(circle at 90% 14%, rgba(255,255,255,.13), transparent 28rem),
	linear-gradient(145deg,#050505 0%,#111 48%,#050505 100%);
	color:#f6f6f6;padding:clamp(28px,5vw,64px) 18px;text-transform:lowercase;isolation:isolate}
.sss-drop-theme:before{content:"";position:absolute;inset:0;z-index:-1;pointer-events:none;background:
	linear-gradient(rgba(255,255,255,.024) 1px, transparent 1px),
	linear-gradient(90deg, rgba(255,255,255,.018) 1px, transparent 1px);
	background-size:42px 42px;mask-image:linear-gradient(to bottom,rgba(0,0,0,.82),transparent 78%)}
.sss-drop-theme__wrap{width:min(1180px,100%);margin:0 auto}
.sss-drop-theme__hero{position:relative;display:grid;grid-template-columns:minmax(0,1.02fr) minmax(280px,.72fr);gap:clamp(20px,4vw,46px);align-items:center;min-height:clamp(540px,72vh,760px);padding:clamp(26px,5vw,58px);margin-bottom:18px;border:1px solid rgba(255,255,255,.13);border-radius:28px;background:
	linear-gradient(135deg,rgba(255,255,255,.105),rgba(255,255,255,.035) 46%,rgba(var(--drop-accent-rgb),.12)),
	rgba(9,9,9,.72);
	box-shadow:0 28px 90px rgba(0,0,0,.46),inset 0 1px 0 rgba(255,255,255,.1);overflow:hidden}
.sss-drop-theme__hero:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 70% 20%,rgba(var(--drop-accent-rgb),.28),transparent 34%),linear-gradient(115deg,rgba(0,0,0,.18),rgba(0,0,0,.72));pointer-events:none}
.sss-drop-theme__heroCopy,.sss-drop-theme__heroArt{position:relative;z-index:2}
.sss-drop-theme__heroCopy{max-width:710px}
.sss-drop-theme__kicker,.sss-drop-theme__sectionHead p,.sss-drop-theme__panel p{margin:0 0 8px;color:var(--drop-accent);font-size:11px;letter-spacing:.16em;text-transform:lowercase}
.sss-drop-theme h1,.sss-drop-theme h2{margin:0;color:#fff;font-family:Cormorant,"Cormorant Garamond",Georgia,serif;font-weight:400;letter-spacing:0;text-transform:lowercase}
.sss-drop-theme h1{font-size:clamp(58px,9vw,132px);line-height:.82;max-width:10ch;text-wrap:balance;text-shadow:0 20px 60px rgba(0,0,0,.5)}
.sss-drop-theme h2{font-size:clamp(25px,4vw,44px);line-height:1}
.sss-drop-theme__mood{margin:16px 0 0;max-width:620px;color:rgba(246,246,246,.78);font-size:16px;line-height:1.65}
.sss-drop-theme blockquote{max-width:680px;margin:26px 0 0;color:rgba(246,246,246,.92);font-family:Cormorant,"Cormorant Garamond",Georgia,serif;font-size:clamp(27px,4.2vw,48px);font-style:italic;line-height:1.08}
.sss-drop-theme__heroArt{display:grid;gap:14px;align-self:stretch;align-content:center}
.sss-drop-theme__heroFrame{position:relative;overflow:hidden;border:1px solid rgba(255,255,255,.18);border-radius:18px;background:rgba(0,0,0,.32);box-shadow:0 24px 64px rgba(0,0,0,.5);transform:rotate(1.2deg)}
.sss-drop-theme__heroFrame:before{content:"";position:absolute;inset:10px;border:1px solid rgba(255,255,255,.18);border-radius:12px;pointer-events:none;z-index:1}
.sss-drop-theme__heroFrame img{display:block;width:100%;height:min(56vh,540px);object-fit:cover}
.sss-drop-theme__miniRail{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px;align-items:end}
.sss-drop-theme__miniRail img{width:100%;aspect-ratio:1;object-fit:cover;border:1px solid rgba(255,255,255,.15);border-radius:10px;background:#111;box-shadow:0 14px 34px rgba(0,0,0,.36)}
.sss-drop-theme__miniRail img:nth-child(even){transform:translateY(12px) rotate(2deg)}
.sss-drop-theme__miniRail img:nth-child(odd){transform:rotate(-1.5deg)}
.sss-drop-theme__nav{position:sticky;top:0;z-index:5;display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin:0 0 24px;padding:10px;border:1px solid rgba(255,255,255,.14);border-radius:18px;background:rgba(5,5,5,.82);backdrop-filter:blur(16px);box-shadow:0 16px 40px rgba(0,0,0,.28)}
.sss-drop-theme__nav a{display:inline-flex;align-items:center;min-height:38px;padding:0 14px;border:1px solid rgba(255,255,255,.13);border-radius:999px;background:linear-gradient(135deg,rgba(var(--drop-accent-rgb),.14),rgba(255,255,255,.045));color:rgba(246,246,246,.86);font-size:12px;letter-spacing:.08em;text-decoration:none}
.sss-drop-theme__nav a:hover,.sss-drop-theme__nav a:focus{border-color:var(--drop-accent);color:#fff;outline:none}
.sss-drop-theme__grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;margin-top:24px}
.sss-drop-theme__panel,.sss-drop-theme__wallpapers,.sss-drop-theme__calendar,.sss-drop-theme__products,.sss-drop-theme__moodboard{border:1px solid rgba(255,255,255,.13);border-radius:18px;background:linear-gradient(145deg,rgba(255,255,255,.06),rgba(255,255,255,.025));box-shadow:0 20px 60px rgba(0,0,0,.34),inset 0 1px 0 rgba(255,255,255,.08)}
.sss-drop-theme__panel{padding:16px}
.sss-drop-theme__panel--gram{display:grid;gap:14px}
.sss-drop-theme__panel img{display:block;width:100%;border-radius:8px}
.sss-drop-theme__panel span,.sss-drop-theme__panel small{display:block;margin-top:8px;color:rgba(246,246,246,.68);font-size:13px;line-height:1.5}
.sss-drop-theme iframe{display:block;margin-top:14px;border:0;border-radius:8px;background:#111}
.sss-drop-theme__wallpapers,.sss-drop-theme__calendar,.sss-drop-theme__products,.sss-drop-theme__moodboard{margin-top:16px;padding:16px}
.sss-drop-theme__sectionHead{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:14px}
.sss-drop-theme__sectionHead a,.sss-drop-theme__actions a{color:var(--drop-accent);text-decoration:none}
.sss-drop-theme__quoteRow{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.sss-drop-theme__quoteRow span{display:inline-flex;padding:8px 10px;border:1px solid rgba(255,255,255,.1);border-radius:999px;background:rgba(0,0,0,.2);color:rgba(246,246,246,.72);font-size:12px}
.sss-drop-theme__assetLabel{margin:16px 0 8px;color:var(--drop-accent);font-size:11px;letter-spacing:.14em;text-transform:lowercase}
.sss-drop-theme__assetGrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.sss-drop-theme__assetGrid img{display:block;width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px;background:#111}
.sss-drop-theme__wallpaperGrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.sss-drop-theme__wallpaperGrid img{display:block;width:100%;aspect-ratio:9/16;object-fit:cover;border-radius:8px}
.sss-drop-theme__calendarImage{display:block;width:100%;border-radius:8px}
.sss-drop-theme__actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end}
.sss-drop-theme__dailyPrompt{
	border:1px solid rgba(255,255,255,.16);
	border-radius:12px;
	padding:16px;
	margin-top:14px;
	background:radial-gradient(circle at 82% 18%, rgba(var(--drop-accent-rgb),.18), rgba(var(--drop-accent-rgb),0) 44%), rgba(255,255,255,.025);
	box-shadow:0 18px 42px rgba(0,0,0,.34);
}
.sss-drop-theme__promptTop{
	display:flex;
	flex-wrap:wrap;
	justify-content:space-between;
	align-items:baseline;
	gap:10px;
	margin-bottom:10px;
}
.sss-drop-theme__promptLabel{
	margin:0;
	color:var(--drop-accent);
	font-size:11px;
	letter-spacing:.12em;
	text-transform:lowercase;
}
.sss-drop-theme__promptDay{
	color:rgba(246,246,246,.72);
	font-size:11px;
	letter-spacing:.08em;
}
.sss-drop-theme__promptBody{
	margin:0;
	font-family:"Cormorant Garamond", Georgia, serif;
	font-size:clamp(26px,3.5vw,40px);
	line-height:1.15;
	color:#fff;
	font-style:italic;
}
.sss-drop-theme__productGrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));align-items:start;gap:0;padding:14px 12px 30px}
.sss-drop-theme__productCard{position:relative;overflow:visible;border:1px solid rgba(255,255,255,.12);border-radius:8px;background:rgba(0,0,0,.22);box-shadow:0 22px 54px rgba(0,0,0,.34)}
.sss-drop-theme__productCard:nth-child(1){z-index:4;transform:rotate(-1.4deg)}
.sss-drop-theme__productCard:nth-child(2){z-index:3;margin-top:36px;margin-left:-22px;transform:rotate(1.2deg)}
.sss-drop-theme__productCard:nth-child(3){z-index:2;margin-top:6px;margin-left:-28px;transform:rotate(-.7deg)}
.sss-drop-theme__productCard:nth-child(4){z-index:1;margin-top:46px;margin-left:-24px;transform:rotate(1deg)}
.sss-drop-theme__productMedia{display:block;padding:10px;background:radial-gradient(circle at 50% 18%,rgba(255,255,255,.08),rgba(255,255,255,.02) 44%,rgba(0,0,0,.2) 100%)}
.sss-drop-theme__productMedia img{display:block;width:100%;height:auto;border-radius:6px}
.sss-drop-theme__productBody{position:relative;z-index:2;display:grid;gap:11px;margin:-12px 10px 10px;padding:12px;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:rgba(5,5,5,.86);backdrop-filter:blur(12px)}
.sss-drop-theme__productTitle{min-height:45px;color:#fff;font-family:Cormorant,"Cormorant Garamond",Georgia,serif;font-size:21px;line-height:1.05;text-decoration:none}
.sss-drop-theme__productTitle:hover,.sss-drop-theme__productTitle:focus{color:var(--drop-accent);outline:none}
.sss-drop-theme__downloadGrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
.sss-drop-theme__downloadGrid a,.sss-drop-theme__productFallback{display:flex;min-height:46px;align-items:center;justify-content:center;padding:8px;border:1px solid rgba(var(--drop-accent-rgb),.4);border-radius:7px;background:rgba(var(--drop-accent-rgb),.1);color:#fff;text-align:center;text-decoration:none}
.sss-drop-theme__downloadGrid a{flex-direction:column}
.sss-drop-theme__downloadGrid a:hover,.sss-drop-theme__downloadGrid a:focus,.sss-drop-theme__productFallback:hover,.sss-drop-theme__productFallback:focus{border-color:var(--drop-accent);background:rgba(var(--drop-accent-rgb),.18);color:#fff;outline:none}
.sss-drop-theme__downloadGrid span{display:block;font-size:12px;font-weight:700;letter-spacing:.04em}
.sss-drop-theme__downloadGrid small,.sss-drop-theme__downloadMissing{display:block;color:rgba(246,246,246,.5);font-size:10px;letter-spacing:.1em;line-height:1.25;text-transform:lowercase}
.sss-drop-theme__downloadGrid small{margin-top:3px}
.sss-drop-theme__productFallback{font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:lowercase}
.sss-drop-theme__downloadMissing{margin-top:-4px}
@media (max-width:900px){.sss-drop-theme__hero{grid-template-columns:1fr;min-height:0;padding:26px 18px}.sss-drop-theme__heroArt{max-width:520px;width:100%;margin:0 auto}.sss-drop-theme__heroFrame img{height:auto;aspect-ratio:4/5}.sss-drop-theme h1{max-width:9ch}.sss-drop-theme__nav{position:relative;justify-content:flex-start;overflow-x:auto;flex-wrap:nowrap}.sss-drop-theme__nav a{white-space:nowrap}}
@media (max-width:800px){.sss-drop-theme__grid,.sss-drop-theme__productGrid,.sss-drop-theme__assetGrid{grid-template-columns:1fr}.sss-drop-theme__productGrid{gap:12px;padding:0}.sss-drop-theme__productCard:nth-child(n){margin:0;transform:none}.sss-drop-theme__productBody{margin:-10px 10px 10px}.sss-drop-theme__wallpaperGrid{display:flex;overflow-x:auto;padding-bottom:4px}.sss-drop-theme__wallpaperGrid a{min-width:46%}.sss-drop-theme__sectionHead{display:block}.sss-drop-theme__actions{justify-content:flex-start;margin-top:10px}.sss-drop-theme__promptTop{align-items:flex-start}}
</style>
