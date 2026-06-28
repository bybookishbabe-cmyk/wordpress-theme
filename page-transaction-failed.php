<?php
/**
 * Transaction failed page template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_enqueue_css('bbb-edd-checkout', 'assets/css/edd-checkout.css', array('bbb-base'));

get_header();
?>

<div class="bbb-checkout bbb-receipt bbb-receipt--utility">
	<section class="bbb-checkout__hero bbb-receipt__hero">
		<div class="bbb-checkout__inner">
			<p class="bbb-checkout__kicker">payment needs another try</p>
			<h1><?php echo esc_html(strtolower(get_the_title() ?: 'transaction failed')); ?></h1>
			<p>nothing was completed. you can return to checkout and try again with the same cart.</p>
		</div>
	</section>

	<section class="bbb-checkout__body bbb-receipt__body">
		<div class="bbb-receipt__panel">
			<div class="bbb-receipt__content">
				<?php
				while (have_posts()) :
					the_post();
					the_content();
				endwhile;
				?>
				<a class="bbb-receipt__action" href="<?php echo esc_url(home_url('/checkout/')); ?>">return to checkout</a>
				<a class="bbb-receipt__secondary" href="<?php echo esc_url(home_url('/shop/')); ?>">keep shopping</a>
			</div>
		</div>
	</section>
</div>

<?php
get_footer();
