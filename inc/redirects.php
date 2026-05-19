<?php
/**
 * Page redirects from the Shopify conversion.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

add_action(
	'template_redirect',
	static function (): void {
		if (!is_page('reading-list')) {
			return;
		}

		$target = get_page_by_path('curated-romance-guides');
		wp_safe_redirect($target ? get_permalink($target) : home_url('/blogs/curated-romance-guides/'), 301);
		exit;
	}
);
