<?php
/**
 * Receipt page template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_enqueue_css('bbb-edd-checkout', 'assets/css/edd-checkout.css', array('bbb-base'));

get_header();
?>

<div class="bbb-checkout bbb-receipt">
	<section class="bbb-checkout__hero bbb-receipt__hero">
		<div class="bbb-checkout__inner">
			<p class="bbb-checkout__kicker">order complete</p>
			<h1><?php echo esc_html(strtolower(get_the_title() ?: 'receipt')); ?></h1>
			<p>your download links and order details are below. save this page or come back from your account any time.</p>
		</div>
	</section>

	<section class="bbb-checkout__body bbb-receipt__body">
		<div class="bbb-receipt__panel">
			<?php
			while (have_posts()) :
				the_post();
				the_content();
			endwhile;
			?>
		</div>
	</section>
</div>

<?php
get_footer();
