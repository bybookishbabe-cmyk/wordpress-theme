<?php
/**
 * Template Name: Shop
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$shop_css_path = get_theme_file_path('assets/css/shop-page.css');
wp_enqueue_style('bbb-shop-page', get_template_directory_uri() . '/assets/css/shop-page.css', array('bbb-base'), file_exists($shop_css_path) ? (string) filemtime($shop_css_path) : wp_get_theme()->get('Version'));

get_header();

$is_admin_preview = current_user_can('edit_posts');
$post_status      = array('publish');
$downloads_query  = new WP_Query(
	array(
		'post_type'      => post_type_exists('download') ? 'download' : 'product',
		'post_status'    => $post_status,
		'posts_per_page' => 96,
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
		return function_exists('bbb_society_product_importer_media_url') ? bbb_society_product_importer_media_url($image_url) : esc_url_raw($image_url);
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

if (!function_exists('bbb_shop_download_file_count')) {
	function bbb_shop_download_file_count(int $post_id): int {
		$edd_files = get_post_meta($post_id, 'edd_download_files', true);
		if (is_array($edd_files)) {
			return count(array_filter($edd_files));
		}

		$woo_files = get_post_meta($post_id, '_downloadable_files', true);
		return is_array($woo_files) ? count(array_filter($woo_files)) : 0;
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

if (!function_exists('bbb_shop_download_kind')) {
	function bbb_shop_download_kind(WP_Post $download): string {
		$title = strtolower(get_the_title($download));
		$type  = strtolower((string) get_post_meta($download->ID, '_bbb_shopify_product_type', true));
		$terms = get_the_terms($download, 'download_category');
		$term_names = is_array($terms) ? strtolower(implode(' ', wp_list_pluck($terms, 'name'))) : '';
		$haystack = $title . ' ' . $type . ' ' . $term_names;

		if (str_contains($haystack, 'canva') || str_contains($haystack, 'template')) {
			return 'templates';
		}

		if (str_contains($haystack, 'tracker') || str_contains($haystack, 'vault')) {
			return 'tools';
		}

		return 'inserts';
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
		$haystack = strtolower(
			(string) ($product['title'] ?? '') . ' ' .
			(string) ($product['product_type'] ?? '') . ' ' .
			(string) ($product['categories'] ?? '') . ' ' .
			(string) ($product['tags'] ?? '')
		);

		if (str_contains($haystack, 'canva') || str_contains($haystack, 'template')) {
			return 'templates';
		}

		if (str_contains($haystack, 'tracker') || str_contains($haystack, 'vault')) {
			return 'tools';
		}

		return 'inserts';
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

$downloads = $downloads_query->posts;
$seed_products = $downloads ? array() : bbb_shop_seed_products();
$counts    = array(
	'all'       => count($downloads) + count($seed_products),
	'inserts'   => 0,
	'templates' => 0,
	'tools'     => 0,
);
$groups = array(
	'inserts'   => array(),
	'templates' => array(),
	'tools'     => array(),
);

foreach ($downloads as $download) {
	if ($download instanceof WP_Post) {
		$kind = bbb_shop_download_kind($download);
		$counts[$kind]++;
		$groups[$kind][] = $download;
	}
}

foreach ($seed_products as $product) {
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
	'tools'     => array(
		'id'     => 'reader-tools',
		'kicker' => 'tools',
		'title'  => 'reader tools',
	),
);

if (function_exists('edd_purchase_variable_pricing')) {
	remove_action('edd_purchase_link_top', 'edd_purchase_variable_pricing', 10);
	add_action('edd_purchase_link_top', 'bbb_shop_purchase_size_select', 10, 2);
}
?>

<main class="bbb-shop" id="main">
	<section class="bbb-shop__hero">
		<div class="bbb-shop__heroInner">
			<p class="bbb-shop__kicker">digital shop</p>
			<h1>printables, templates, and reader tools</h1>
			<p class="bbb-shop__intro">a cleaner home for the downloads: kindle inserts, bookish canva templates, and the little tools that make a reading life feel prettier and easier to keep.</p>
			<nav class="bbb-shop__filters" aria-label="Shop sections">
				<a href="#shop-all">all <span><?php echo esc_html((string) $counts['all']); ?></span></a>
				<a href="#kindle-inserts">kindle inserts <span><?php echo esc_html((string) $counts['inserts']); ?></span></a>
				<a href="#templates">templates <span><?php echo esc_html((string) $counts['templates']); ?></span></a>
				<a href="#reader-tools">reader tools <span><?php echo esc_html((string) $counts['tools']); ?></span></a>
			</nav>
		</div>
	</section>

	<?php if (!$downloads && !$seed_products) : ?>
		<section class="bbb-shop__empty">
			<h2>shop downloads are almost ready.</h2>
			<p>publish the downloads you want shown here.</p>
		</section>
	<?php else : ?>
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
						if ($is_seed_product) {
							$post_id       = 0;
							$title         = strtolower((string) ($download['title'] ?? 'download'));
							$permalink     = (string) ($download['fallback_url'] ?? '#');
							$image_url     = (string) ($download['fallback_image'] ?? '');
							$price_value   = trim((string) ($download['price'] ?? ''));
							$price         = '' !== $price_value ? '$' . number_format((float) $price_value, 2) : '';
							$file_count    = bbb_shop_seed_file_count($download);
							$missing_files = (bool) ($download['download_missing'] ?? false);
							$is_free       = bbb_society_product_importer_truthy($download['society_free'] ?? false);
							$excerpt       = bbb_shop_seed_excerpt($download);
						} else {
							$post_id       = (int) $download->ID;
							$title         = get_the_title($download);
							$permalink     = get_permalink($download);
							$image_url     = bbb_shop_download_image($post_id);
							$price         = bbb_shop_download_price($post_id);
							$file_count    = bbb_shop_download_file_count($post_id);
							$missing_files = 'yes' === get_post_meta($post_id, '_bbb_missing_download_url', true);
							$is_free       = 'yes' === get_post_meta($post_id, '_bbb_society_free_download', true);
							$excerpt       = bbb_shop_download_excerpt($download);
						}
						?>
						<article class="bbb-shop-card bbb-shop-card--<?php echo esc_attr($kind); ?>">
							<a class="bbb-shop-card__media" href="<?php echo esc_url($permalink); ?>">
								<?php if ($image_url) : ?>
									<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
								<?php else : ?>
									<span><?php echo esc_html(substr($title, 0, 1)); ?></span>
								<?php endif; ?>
							</a>
							<div class="bbb-shop-card__body">
								<div class="bbb-shop-card__badges">
									<span><?php echo esc_html($kind === 'templates' ? 'template' : ($kind === 'tools' ? 'reader tool' : 'printable')); ?></span>
									<?php if ($is_free) : ?>
										<span>member free</span>
									<?php endif; ?>
									<?php if (!$is_seed_product && 'publish' !== get_post_status($download)) : ?>
										<span><?php echo esc_html(get_post_status($download)); ?></span>
									<?php endif; ?>
									<?php if ($missing_files) : ?>
										<span>needs file</span>
									<?php endif; ?>
								</div>
								<h3><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>
								<p><?php echo esc_html($excerpt); ?></p>
								<div class="bbb-shop-card__meta">
									<strong><?php echo esc_html($price); ?></strong>
									<span><?php echo esc_html($file_count ? $file_count . ' file' . (1 === $file_count ? '' : 's') : 'file pending'); ?></span>
								</div>
								<div class="bbb-shop-card__actions">
									<?php if ($is_seed_product) : ?>
										<?php bbb_shop_seed_size_select($download); ?>
										<a class="bbb-shop-card__button" href="<?php echo esc_url($permalink); ?>">view details</a>
									<?php elseif ($missing_files && $is_admin_preview) : ?>
										<a class="bbb-shop-card__button bbb-shop-card__button--ghost" href="<?php echo esc_url(get_edit_post_link($post_id)); ?>">finish setup</a>
									<?php elseif (function_exists('edd_get_purchase_link')) : ?>
										<?php
										echo edd_get_purchase_link(
											array(
												'download_id' => $post_id,
												'text'        => 'add to cart',
												'price'       => false,
												'class'       => 'bbb-shop-card__button',
												'style'       => 'button',
											)
										);
										?>
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

<?php
if (function_exists('edd_purchase_variable_pricing')) {
	remove_action('edd_purchase_link_top', 'bbb_shop_purchase_size_select', 10);
	add_action('edd_purchase_link_top', 'edd_purchase_variable_pricing', 10, 2);
}
wp_reset_postdata();
get_footer();
