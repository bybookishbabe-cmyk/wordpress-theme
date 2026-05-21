<?php
/**
 * Checkout page template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_enqueue_css('bbb-edd-checkout', 'assets/css/edd-checkout.css', array('bbb-base'));

get_header();
?>

<main class="bbb-checkout" id="main">
	<section class="bbb-checkout__hero">
		<div class="bbb-checkout__inner">
			<p class="bbb-checkout__kicker">checkout</p>
			<h1><?php echo esc_html(strtolower(get_the_title() ?: 'checkout')); ?></h1>
			<p>secure your downloads, then your files will be waiting in your receipt and account area.</p>
		</div>
	</section>

	<section class="bbb-checkout__body">
		<div class="bbb-checkout__panel">
			<?php
			while (have_posts()) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
	</section>
</main>

<?php
get_footer();
