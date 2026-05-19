<?php
/**
 * Temporary header bridge.
 *
 * The next conversion slice ports sections/header.liquid exactly.
 *
 * @package ByBookishBabeShopifyPort
 */

?>
<header class="shopify-section section-header" data-section="header">
	<div class="header-wrapper color-scheme-1 gradient header-wrapper--border-bottom">
		<div class="header page-width header--top-center header--mobile-center">
			<a class="header__heading-link link link--text focus-inset" href="<?php echo esc_url(home_url('/')); ?>">
				<span class="h2"><?php bloginfo('name'); ?></span>
			</a>
		</div>
	</div>
</header>
