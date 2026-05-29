<?php
/**
 * Current bookish product feature for the shop page.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$obsession_url = 'https://www.etsy.com/listing/4499453072/book-lover-embroidered-sweatshirt?ls=r&sr_prefetch=1&pf_from=shop_home&ref=items-pagination-1&pro=1&sts=1&content_source=d7fd78385fbd7dfb0122a279f919557a%253ALT54b642d50d24037f748dc159223e7f4693cefdf4&logging_key=d7fd78385fbd7dfb0122a279f919557a%3ALT54b642d50d24037f748dc159223e7f4693cefdf4';
$obsession_image = get_theme_file_uri('assets/images/book-lover-embroidered-sweatshirt.webp');
?>

<section class="bbb-shop-obsession" aria-label="current obsessed with">
	<div class="bbb-shop-obsession__inner">
		<a class="bbb-shop-obsession__media" href="<?php echo esc_url($obsession_url); ?>" target="_blank" rel="noopener nofollow">
			<img class="bbb-shop-obsession__image" src="<?php echo esc_url($obsession_image); ?>" alt="book lover embroidered sweatshirt" loading="lazy">
		</a>
		<div class="bbb-shop-obsession__copy">
			<p class="bbb-shop-obsession__kicker">currently obsessed with</p>
			<h2>the book lover embroidered sweatshirt</h2>
			<p>a cozy little etsy find for readers who want their whole personality stitched on a crewneck, respectfully.</p>
			<div class="bbb-shop-obsession__meta">
				<span>etsy find</span>
				<span>embroidered</span>
				<span>$35+</span>
			</div>
			<a class="bbb-shop-obsession__button" href="<?php echo esc_url($obsession_url); ?>" target="_blank" rel="noopener nofollow">shop the sweatshirt</a>
		</div>
	</div>
</section>
