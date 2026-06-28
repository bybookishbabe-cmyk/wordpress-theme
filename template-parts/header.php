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
$account_url         = home_url('/account/');
$checkout_url        = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
$sss_url             = function_exists('bbb_substack_subscribe_url') ? bbb_substack_subscribe_url() : 'https://thesmutandsentimentsociety.substack.com/subscribe';
$reader_identity     = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$reader_user_id      = is_array($reader_identity) ? (int) ($reader_identity['userId'] ?? 0) : 0;
$reader_email        = is_array($reader_identity) ? (string) ($reader_identity['email'] ?? '') : '';

if (is_array($reader_identity) && '' !== trim($reader_email)) {
	$account_url = home_url('/account/');
	$reader_tier = function_exists('bbb_reader_access_tier_for_email')
		? bbb_reader_access_tier_for_email($reader_email, $reader_user_id)
		: (function_exists('bbb_reader_access_tier') ? bbb_reader_access_tier($reader_user_id) : 'free');
	if ('society' === $reader_tier || (function_exists('bbb_user_is_society') && $reader_user_id && bbb_user_is_society($reader_user_id))) {
		$account_status      = 'paid';
		$account_status_text = __('paid society member', 'bybookishbabe-shopify-port');
	} else {
		$account_status      = 'free';
		$account_status_text = __('free reader account', 'bybookishbabe-shopify-port');
	}
}
$trending_now = function_exists('bbb_trending_now_banner') ? bbb_trending_now_banner() : array();
?>
<div class="shopify-section section-header" data-section="header">
	<sticky-header data-sticky-type="on-scroll-up" class="header-wrapper color-scheme-1 gradient header-wrapper--border-bottom">
		<header class="header header--top-center header--mobile-center page-width header--has-menu header--has-account">
			<?php get_template_part('template-parts/header/header-drawer'); ?>
			<?php get_template_part('template-parts/header/header-search', null, array('input_id' => 'Search-In-Modal-1')); ?>

			<?php if (is_front_page()) : ?>
				<h1 class="visually-hidden">Romance Book Recommendations by Trope &amp; Spice Level</h1>
			<?php endif; ?>
			<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

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
					href="<?php echo esc_url($sss_url); ?>"
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
				<a
					href="<?php echo esc_url($checkout_url); ?>"
					class="header__checkout-link link focus-inset"
					aria-label="<?php esc_attr_e('go to checkout', 'bybookishbabe-shopify-port'); ?>"
				>
					<span class="svg-wrapper" aria-hidden="true"><?php echo bbb_get_inline_svg('icon-cart.svg'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</a>
			</div>
		</header>
	</sticky-header>
	<?php if (is_front_page() && !empty($trending_now['url']) && !empty($trending_now['title'])) : ?>
		<a class="bbb-trending-now-bar" href="<?php echo esc_url((string) $trending_now['url']); ?>">
			<span class="bbb-trending-now-bar__track" aria-hidden="true">
				<?php for ($i = 0; $i < 4; $i++) : ?>
					<span class="bbb-trending-now-bar__item<?php echo 0 === $i % 2 ? ' is-bright' : ' is-faded'; ?>">
						<span>☀️</span>
						<span class="bbb-trending-now-bar__title"><?php echo esc_html((string) $trending_now['title']); ?></span>
						<?php if (!empty($trending_now['meta'])) : ?>
							<span class="bbb-trending-now-bar__meta"><?php echo esc_html((string) $trending_now['meta']); ?></span>
						<?php endif; ?>
					</span>
				<?php endfor; ?>
			</span>
			<span class="visually-hidden">
				<?php
				echo esc_html(
					trim(
						(string) $trending_now['title'] . (!empty($trending_now['meta']) ? ' ' . (string) $trending_now['meta'] : '')
					)
				);
				?>
			</span>
		</a>
	<?php endif; ?>
</div>
