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
						'class'       => 'bbb-shop-card__button',
						'style'       => 'button',
					)
				);

				if ($size_options && function_exists('edd_purchase_variable_pricing')) {
					remove_action('edd_purchase_link_top', 'bbb_single_product_size_select', 10);
					add_action('edd_purchase_link_top', 'edd_purchase_variable_pricing', 10, 2);
				}
				return;
			}
		}

		if ('product' === get_post_type($post_id) && function_exists('woocommerce_template_single_add_to_cart')) {
			if (function_exists('wc_get_product')) {
				$GLOBALS['product'] = wc_get_product($post_id);
			}
			woocommerce_template_single_add_to_cart();
			return;
		}
		?>
		<a class="bbb-shop-card__button" href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?>">open cart</a>
		<?php
	}
}

$post_id       = get_the_ID();
$image_url     = bbb_single_product_image($post_id);
$price         = bbb_single_product_price($post_id);
$file_count    = bbb_single_product_file_count($post_id);
$missing_files = 'yes' === get_post_meta($post_id, '_bbb_missing_download_url', true);
$is_free       = 'yes' === get_post_meta($post_id, '_bbb_society_free_download', true);
$kind          = strtolower((string) get_post_meta($post_id, '_bbb_shopify_product_type', true));
$kind          = '' !== $kind ? $kind : ('download' === get_post_type($post_id) ? 'digital download' : 'product');
$edit_url      = get_edit_post_link($post_id);
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
			<h1><?php the_title(); ?></h1>
			<div class="bbb-shop-card__badges">
				<span><?php echo esc_html($kind); ?></span>
				<?php if ($is_free) : ?>
					<span>member free</span>
				<?php endif; ?>
				<?php if ($missing_files) : ?>
					<span>needs file</span>
				<?php endif; ?>
			</div>
			<div class="bbb-product__meta">
				<strong><?php echo esc_html($price); ?></strong>
				<span><?php echo esc_html($file_count ? $file_count . ' file' . (1 === $file_count ? '' : 's') : 'file pending'); ?></span>
			</div>
			<div class="bbb-product__actions">
				<?php if ($missing_files && current_user_can('edit_posts') && $edit_url) : ?>
					<a class="bbb-shop-card__button bbb-shop-card__button--ghost" href="<?php echo esc_url($edit_url); ?>">finish setup</a>
				<?php else : ?>
					<?php bbb_single_product_purchase_form($post_id); ?>
				<?php endif; ?>
			</div>
			<?php if ($is_free) : ?>
				<p class="bbb-product__note">paid society members can download this for free.</p>
			<?php endif; ?>
		</div>
	</section>

	<?php if (trim(wp_strip_all_tags((string) get_the_content()))) : ?>
		<section class="bbb-product__details" aria-label="product details">
			<p class="bbb-shop__kicker">details</p>
			<div class="bbb-product__content">
				<?php the_content(); ?>
			</div>
		</section>
	<?php endif; ?>
</main>
