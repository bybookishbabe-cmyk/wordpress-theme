<?php
/**
 * Shopify-faithful global header.
 *
 * @package ByBookishBabeShopifyPort
 */

$cart_count = bbb_cart_count();
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
					href="<?php echo esc_url(is_user_logged_in() ? bbb_wc_account_url() : wp_login_url()); ?>"
					class="header__icon header__icon--account link focus-inset small-hide"
					rel="nofollow"
				>
					<account-icon>
						<?php if (is_user_logged_in() && bbb_user_has_avatar()) : ?>
							<?php echo get_avatar(get_current_user_id(), 44); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php else : ?>
							<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-account'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
					</account-icon>
					<span class="visually-hidden"><?php echo esc_html(is_user_logged_in() ? __('Account', 'bybookishbabe-shopify-port') : __('Log in', 'bybookishbabe-shopify-port')); ?></span>
				</a>

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

				<a href="<?php echo esc_url(bbb_wc_cart_url()); ?>" class="header__icon header__icon--cart link focus-inset" id="cart-icon-bubble">
					<?php if (bbb_cart_is_empty()) : ?>
						<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-cart-empty'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php else : ?>
						<span class="svg-wrapper"><?php echo bbb_get_inline_svg('icon-cart'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php endif; ?>
					<span class="visually-hidden"><?php esc_html_e('Cart', 'bybookishbabe-shopify-port'); ?></span>
					<?php if (!bbb_cart_is_empty()) : ?>
						<div class="cart-count-bubble">
							<?php if ($cart_count < 100) : ?>
								<span aria-hidden="true"><?php echo esc_html((string) $cart_count); ?></span>
							<?php endif; ?>
							<span class="visually-hidden"><?php echo esc_html(sprintf(_n('%d item in cart', '%d items in cart', $cart_count, 'bybookishbabe-shopify-port'), $cart_count)); ?></span>
						</div>
					<?php endif; ?>
				</a>
			</div>
		</header>
	</sticky-header>
</div>
