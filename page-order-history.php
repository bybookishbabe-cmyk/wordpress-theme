<?php
/**
 * Order history page template.
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
			<p class="bbb-checkout__kicker">your downloads</p>
			<h1><?php echo esc_html(strtolower(get_the_title() ?: 'order history')); ?></h1>
			<p>find past orders and return to your download links when you need them.</p>
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
				<p class="bbb-receipt__fallback">if your orders are not showing, log into the account/email you used at checkout.</p>
				<a class="bbb-receipt__action" href="<?php echo esc_url(home_url('/account/')); ?>">open account</a>
			</div>
		</div>
	</section>
</div>

<?php
get_footer();
