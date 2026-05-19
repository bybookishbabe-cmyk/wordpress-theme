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
		$path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

		if ($path === 'cart' && function_exists('wc_get_cart_url')) {
			wp_safe_redirect(wc_get_cart_url(), 302);
			exit;
		}

		if ($path === 'account/login') {
			wp_safe_redirect(wp_login_url(), 302);
			exit;
		}

		if (($path === 'account' || str_starts_with($path, 'account/')) && function_exists('wc_get_account_endpoint_url')) {
			wp_safe_redirect(wc_get_account_endpoint_url('dashboard'), 302);
			exit;
		}

		if (!is_page('reading-list')) {
			return;
		}

		$target = get_page_by_path('curated-romance-guides');
		wp_safe_redirect($target ? get_permalink($target) : home_url('/blogs/curated-romance-guides/'), 301);
		exit;
	}
);
