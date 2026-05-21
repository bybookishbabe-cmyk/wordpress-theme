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

		$image = (string) get_post_meta($post_id, '_bbb_source_image_url', true);
		if (function_exists('bbb_society_product_importer_media_url')) {
			$localized = bbb_society_product_importer_media_url($image);
			if ('' !== $localized) {
				$image = $localized;
			}
		}

		return esc_url_raw($image);
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
		$edd_files = get_post_meta($post_id, 'edd_download_files', true);
		if (is_array($edd_files)) {
			return count(array_filter($edd_files));
		}

		$woo_files = get_post_meta($post_id, '_downloadable_files', true);
		return is_array($woo_files) ? count(array_filter($woo_files)) : 0;
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

if (!function_exists('bbb_single_product_purchase_form')) {
	function bbb_single_product_purchase_form(int $post_id): void {
		if ('download' === get_post_type($post_id)) {
			$size_options = bbb_single_product_size_options($post_id);
			if ($size_options) {
				$default_price_id = (string) get_post_meta($post_id, '_edd_default_price_id', true);
				if ('' === $default_price_id || !isset($size_options[$default_price_id])) {
					$default_price_id = (string) array_key_first($size_options);
				}
				$select_id = 'bbb-product-size-' . $post_id;
				?>
				<form class="edd_download_purchase_form bbb-shop-card__purchaseForm bbb-product__purchaseForm" method="post">
					<div class="bbb-shop-card__size">
						<label for="<?php echo esc_attr($select_id); ?>">size</label>
						<select id="<?php echo esc_attr($select_id); ?>" class="bbb-shop-card__sizeSelect" name="edd_options[price_id][]">
							<?php foreach ($size_options as $price_id => $label) : ?>
								<option value="<?php echo esc_attr($price_id); ?>" <?php selected($price_id, $default_price_id); ?>>
									<?php echo esc_html($label); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<input type="hidden" name="download_id" value="<?php echo esc_attr((string) $post_id); ?>">
					<input type="hidden" name="edd_action" value="add_to_cart">
					<input type="hidden" name="edd_redirect_to_checkout" value="">
					<button type="submit" class="edd-submit bbb-shop-card__button">add to cart</button>
				</form>
				<?php
				return;
			}

			if (function_exists('edd_get_purchase_link')) {
				echo edd_get_purchase_link(
					array(
						'download_id' => $post_id,
						'text'        => 'add to cart',
						'price'       => false,
						'class'       => 'bbb-shop-card__button',
						'style'       => 'button',
					)
				);
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
