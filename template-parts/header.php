<?php
/**
 * Shopify-faithful global header.
 *
 * @package ByBookishBabeShopifyPort
 */

$logo       = sprintf(
	'<a href="%1$s" class="header__heading-link link link--text focus-inset"><div class="header__heading-logo-wrapper"><img src="%2$s" class="header__heading-logo motion-reduce" width="200" height="%3$d" alt="%4$s" sizes="(max-width: 400px) 50vw, 200px" loading="eager"></div></a>',
	esc_url(home_url('/')),
	esc_url(bbb_logo_url()),
	(int) bbb_logo_height(),
	esc_attr(get_bloginfo('name'))
);

$account_status      = 'visitor';
$account_status_text = __('visitor account', 'bybookishbabe-shopify-port');
$account_url         = wp_login_url();

if (is_user_logged_in()) {
	$account_url = function_exists('bbb_wc_account_url') ? bbb_wc_account_url() : home_url('/account/');
	if (function_exists('bbb_reader_is_society') && bbb_reader_is_society()) {
		$account_status      = 'paid';
		$account_status_text = __('paid society member', 'bybookishbabe-shopify-port');
	} else {
		$account_status      = 'free';
		$account_status_text = __('free reader account', 'bybookishbabe-shopify-port');
	}
}
?>
<div class="shopify-section section-header" data-section="header">
	<sticky-header data-sticky-type="on-scroll-up" class="header-wrapper color-scheme-1 gradient header-wrapper--border-bottom">
		<header class="header header--top-center header--mobile-center page-width header--has-menu header--has-account">
			<?php get_template_part('template-parts/header/header-drawer'); ?>
			<?php get_template_part('template-parts/header/header-search', null, array('input_id' => 'Search-In-Modal-1')); ?>

			<?php if (is_front_page()) : ?>
				<h1 class="header__heading"><?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			<?php else : ?>
				<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php get_template_part('template-parts/header/header-dropdown-menu'); ?>

			<div class="header__icons">
				<div class="desktop-localization-wrapper"></div>
				<?php get_template_part('template-parts/header/header-search', null, array('input_id' => 'Search-In-Modal')); ?>

				<a
					href="<?php echo esc_url($account_url); ?>"
					class="header__account-indicator header__account-indicator--<?php echo esc_attr($account_status); ?> link focus-inset"
					aria-label="<?php echo esc_attr($account_status_text); ?>"
					title="<?php echo esc_attr($account_status_text); ?>"
				>
					<span class="header__account-dot" aria-hidden="true">A</span>
				</a>

				<?php get_template_part('template-parts/header/reader-bookshelf-access'); ?>

				<a
					href="https://thesmutandsentimentsociety.substack.com/subscribe"
					class="header__sss-link link focus-inset"
					target="_blank"
					rel="noopener"
					aria-label="<?php esc_attr_e('Visit The Smut and Sentiment Society on Substack', 'bybookishbabe-shopify-port'); ?>"
				>
					<img
						src="<?php echo esc_url(get_theme_file_uri('assets/SSS_Logo.png')); ?>"
						alt="<?php esc_attr_e('The Smut and Sentiment Society', 'bybookishbabe-shopify-port'); ?>"
						class="header__sss-image"
						loading="lazy"
						width="104"
						height="104"
					>
				</a>
			</div>
		</header>
	</sticky-header>
</div>
