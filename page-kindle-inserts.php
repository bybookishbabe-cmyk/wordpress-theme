<?php
/**
 * Template Name: Kindle Inserts Hub
 *
 * Public SEO hub for printable Kindle insert downloads.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$shop_css_path = get_theme_file_path('assets/css/shop-page.css');
wp_enqueue_style('bbb-shop-page', get_template_directory_uri() . '/assets/css/shop-page.css', array('bbb-base'), file_exists($shop_css_path) ? (string) filemtime($shop_css_path) : wp_get_theme()->get('Version'));
$shop_cart_js_path = get_theme_file_path('assets/js/shop-edd-cart.js');
wp_enqueue_script('bbb-shop-edd-cart', get_template_directory_uri() . '/assets/js/shop-edd-cart.js', array(), file_exists($shop_cart_js_path) ? (string) filemtime($shop_cart_js_path) : wp_get_theme()->get('Version'), true);

if (!function_exists('bbb_kindle_hub_seed_url')) {
	function bbb_kindle_hub_seed_url(string $url): string {
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

if (!function_exists('bbb_kindle_hub_product_export')) {
	function bbb_kindle_hub_product_export(string $handle): array {
		$handle = sanitize_title($handle);
		if ('' === $handle || !function_exists('bbb_society_product_importer_export_rows')) {
			return array();
		}

		foreach (bbb_society_product_importer_export_rows() as $product) {
			if (is_array($product) && sanitize_title((string) ($product['handle'] ?? '')) === $handle) {
				return $product;
			}
		}

		return array();
	}
}

if (!function_exists('bbb_kindle_hub_download_image')) {
	function bbb_kindle_hub_download_image(int $post_id): string {
		$image = get_the_post_thumbnail_url($post_id, 'large');
		if ($image) {
			return (string) $image;
		}

		$image = (string) get_post_meta($post_id, '_bbb_source_image_url', true);
		if (function_exists('bbb_society_product_importer_media_url')) {
			$image = bbb_society_product_importer_media_url($image);
		}

		if ('' === $image || str_starts_with($image, '/wp-content/')) {
			$export = bbb_kindle_hub_product_export((string) get_post_field('post_name', $post_id));
			$export_image_url = bbb_kindle_hub_seed_url((string) ($export['image_url'] ?? ''));
			if ('' !== $export_image_url) {
				$image = $export_image_url;
			}
		}

		return esc_url_raw($image);
	}
}

if (!function_exists('bbb_kindle_hub_file_count')) {
	function bbb_kindle_hub_file_count(int $post_id): int {
		if (function_exists('bbb_society_product_file_count')) {
			return bbb_society_product_file_count($post_id);
		}

		$files = get_post_meta($post_id, 'edd_download_files', true);
		if (!is_array($files)) {
			return 0;
		}

		$files = array_filter(
			$files,
			static fn($file): bool => is_array($file)
				? '' !== trim((string) ($file['file'] ?? $file['url'] ?? ''))
				: '' !== trim((string) $file)
		);

		return count($files);
	}
}

if (!function_exists('bbb_kindle_hub_download_price')) {
	function bbb_kindle_hub_download_price(int $post_id): string {
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

if (!function_exists('bbb_kindle_hub_size_options')) {
	function bbb_kindle_hub_size_options(int $post_id): array {
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

if (!function_exists('bbb_kindle_hub_purchase_size_select')) {
	function bbb_kindle_hub_purchase_size_select(int $download_id, array $args = array()): void {
		$prices = bbb_kindle_hub_size_options($download_id);
		if (count($prices) < 2) {
			return;
		}

		$default_price_id = function_exists('edd_get_default_variable_price') ? (string) edd_get_default_variable_price($download_id) : (string) array_key_first($prices);
		$select_id        = 'bbb-kindle-hub-size-' . $download_id;
		if (!empty($args['form_id'])) {
			$select_id .= '-' . sanitize_html_class((string) $args['form_id']);
		}
		?>
		<div class="bbb-shop-card__size">
			<label for="<?php echo esc_attr($select_id); ?>">size</label>
			<select id="<?php echo esc_attr($select_id); ?>" class="bbb-shop-card__sizeSelect" onchange="this.closest('form').querySelector('.edd_price_option_<?php echo esc_attr((string) $download_id); ?>[type=hidden]').value=this.value;">
				<?php foreach ($prices as $price_id => $label) : ?>
					<option value="<?php echo esc_attr($price_id); ?>" <?php selected((string) $price_id, $default_price_id); ?>>
						<?php echo esc_html($label); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<input type="hidden" name="edd_options[price_id][]" class="edd_price_option_<?php echo esc_attr((string) $download_id); ?>" value="<?php echo esc_attr($default_price_id); ?>">
		</div>
		<?php
	}
}

if (!function_exists('bbb_kindle_hub_purchase_form')) {
	function bbb_kindle_hub_purchase_form(int $post_id): void {
		$size_options = bbb_kindle_hub_size_options($post_id);
		if (!function_exists('edd_get_purchase_link')) {
			?>
			<a class="bbb-shop-card__button" href="<?php echo esc_url(get_permalink($post_id)); ?>">view details</a>
			<?php
			return;
		}

		if ($size_options && function_exists('edd_purchase_variable_pricing')) {
			remove_action('edd_purchase_link_top', 'edd_purchase_variable_pricing', 10);
			add_action('edd_purchase_link_top', 'bbb_kindle_hub_purchase_size_select', 10, 2);
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
			remove_action('edd_purchase_link_top', 'bbb_kindle_hub_purchase_size_select', 10);
			add_action('edd_purchase_link_top', 'edd_purchase_variable_pricing', 10, 2);
		}
	}
}

if (!function_exists('bbb_kindle_hub_is_insert')) {
	function bbb_kindle_hub_is_insert(WP_Post $post): bool {
		$term_names = '';
		if (taxonomy_exists('download_category')) {
			$terms = get_the_terms($post, 'download_category');
			$term_names = is_array($terms) ? implode(' ', wp_list_pluck($terms, 'name')) : '';
		}

		$haystack = strtolower(
			get_the_title($post) . ' ' .
			(string) get_post_meta($post->ID, '_bbb_shopify_product_type', true) . ' ' .
			$term_names
		);

		return str_contains($haystack, 'kindle insert')
			&& !str_contains($haystack, 'vault')
			&& !str_contains($haystack, 'canva')
			&& !str_contains($haystack, 'template');
	}
}

$post_type = post_type_exists('download') ? 'download' : 'product';
$query     = new WP_Query(
	array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => 200,
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

$inserts = array_values(
	array_filter(
		$query->posts,
		static fn($download): bool => $download instanceof WP_Post
			&& bbb_kindle_hub_is_insert($download)
			&& (current_user_can('edit_posts') || bbb_kindle_hub_file_count((int) $download->ID) > 0)
	)
);

get_header();
?>

<main class="bbb-shop bbb-kindle-hub" id="main">
	<section class="bbb-shop__hero">
		<div class="bbb-shop__heroInner">
			<div class="bbb-kindle-hub__heroTitle">
				<p class="bbb-shop__kicker">printable kindle inserts</p>
				<h1>printable kindle inserts for romance books</h1>
				<p class="bbb-shop__intro">Romance readers use them like tiny moodboards: one for a dark romance binge, one for a soft reread, one for the bookish era they are currently making everyone hear about. Download the file, print it at full size, trim along the edges, and place it under your case.</p>
			</div>
			<div class="bbb-kindle-hub__heroCopy">
				<p class="bbb-shop__kicker">how it works</p>
				<h2>what are kindle inserts?</h2>
				<div class="bbb-kindle-hub__copy">
					<p>A Kindle insert is a printable design that sits underneath a clear Kindle case. It lets you decorate your e-reader without stickers, residue, or committing to one look forever.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-shop__section" id="all-kindle-inserts" aria-labelledby="all-kindle-inserts-title">
		<div class="bbb-shop__sectionHead">
			<div>
				<p class="bbb-shop__kicker">download library</p>
				<h2 id="all-kindle-inserts-title">browse printable kindle inserts</h2>
			</div>
		</div>

		<?php if (!$inserts) : ?>
			<div class="bbb-shop__empty">
				<h2>kindle inserts are almost ready.</h2>
				<p>publish the insert downloads you want shown here.</p>
			</div>
		<?php else : ?>
			<div class="bbb-shop__grid">
				<?php foreach ($inserts as $insert) : ?>
					<?php
					$insert_id  = (int) $insert->ID;
					$title      = get_the_title($insert);
					$permalink  = get_permalink($insert);
					$image_url  = bbb_kindle_hub_download_image($insert_id);
					$file_count = bbb_kindle_hub_file_count($insert_id);
					$price      = bbb_kindle_hub_download_price($insert_id);
					$is_free    = 'yes' === get_post_meta($insert_id, '_bbb_society_free_download', true);
					$missing_files = 'yes' === get_post_meta($insert_id, '_bbb_missing_download_url', true) && 0 === $file_count;
					$can_purchase  = 0 < $file_count && !$missing_files;
					?>
					<article class="bbb-shop-card bbb-shop-card--inserts">
						<a class="bbb-shop-card__media" href="<?php echo esc_url($permalink); ?>">
							<?php if ($image_url) : ?>
								<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
							<?php else : ?>
								<span><?php echo esc_html(substr($title, 0, 1)); ?></span>
							<?php endif; ?>
						</a>
						<div class="bbb-shop-card__body">
							<div class="bbb-shop-card__badges">
								<span>printable</span>
								<?php if ($is_free) : ?>
									<span>member access</span>
								<?php endif; ?>
							</div>
							<h3><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>
							<div class="bbb-shop-card__meta">
								<strong><?php echo esc_html($price); ?></strong>
								<span><?php echo esc_html($file_count ? $file_count . ' file' . (1 === $file_count ? '' : 's') : 'file pending'); ?></span>
							</div>
							<div class="bbb-shop-card__actions">
								<?php if ($missing_files && current_user_can('edit_posts')) : ?>
									<a class="bbb-shop-card__button bbb-shop-card__button--ghost" href="<?php echo esc_url(get_edit_post_link($insert_id)); ?>">finish setup</a>
								<?php elseif ($can_purchase) : ?>
									<?php bbb_kindle_hub_purchase_form($insert_id); ?>
									<a class="bbb-shop-card__details" href="<?php echo esc_url($permalink); ?>">view details</a>
								<?php else : ?>
									<span class="bbb-shop-card__button bbb-shop-card__button--disabled" aria-disabled="true">coming soon</span>
									<a class="bbb-shop-card__details" href="<?php echo esc_url($permalink); ?>">view details</a>
								<?php endif; ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>
</main>

<style>
	@media (min-width: 990px) {
		.bbb-kindle-hub .bbb-shop__heroInner {
			grid-template-columns: minmax(0, 0.98fr) minmax(360px, 0.78fr);
			align-items: center;
			gap: clamp(42px, 6vw, 96px);
		}

		.bbb-kindle-hub .bbb-shop__heroInner h1 {
			max-width: 720px;
		}

		.bbb-kindle-hub__heroCopy {
			display: grid;
			gap: 16px;
			max-width: 560px;
			padding: 28px 0 0;
		}

		.bbb-kindle-hub__heroCopy h2 {
			max-width: 520px;
		}
	}

	.bbb-kindle-hub__heroTitle {
		display: grid;
		gap: 18px;
	}

	.bbb-kindle-hub__heroTitle .bbb-shop__intro {
		max-width: 650px;
	}

	.bbb-kindle-hub__heroCopy {
		display: grid;
		gap: 14px;
		align-content: center;
		border-top: 1px solid var(--shop-line);
		padding-top: 22px;
	}

	.bbb-kindle-hub__heroCopy h2 {
		margin: 0;
		color: var(--shop-ink);
		font-family: "Cormorant Garamond", Georgia, serif;
		font-size: clamp(34px, 4vw, 52px);
		font-weight: 500;
		letter-spacing: 0;
		line-height: 0.98;
		text-transform: lowercase;
	}

	.bbb-kindle-hub__copy {
		display: grid;
		gap: 12px;
		max-width: 560px;
		color: var(--shop-muted);
		font: 500 17px/1.7 Assistant, system-ui, sans-serif;
		text-transform: lowercase;
	}

	.bbb-kindle-hub__copy p {
		margin: 0;
	}

</style>

<?php
wp_reset_postdata();
get_footer();
