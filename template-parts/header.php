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

				<a href="<?php echo esc_url(home_url('/my-vault/')); ?>" class="header__vault-link link focus-inset" aria-label="<?php esc_attr_e('open my vault', 'bybookishbabe-shopify-port'); ?>">
					<span class="header__vault-badge" aria-hidden="true">V</span>
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
