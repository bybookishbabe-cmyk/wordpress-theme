<?php
/**
 * Temporary launch locks for staged digital products.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_staged_product_release_at')) {
	function bbb_staged_product_release_at(): DateTimeImmutable {
		return new DateTimeImmutable('2026-07-01 00:00:00', new DateTimeZone('America/Los_Angeles'));
	}
}

if (!function_exists('bbb_staged_product_handles')) {
	function bbb_staged_product_handles(): array {
		return array(
			'midnight-summer-book-review-editable-canva-template',
			'midnight-drive-printable-kindle-insert',
			'midnight-makeout-printable-kindle-insert',
			'midnight-movie-printable-kindle-insert',
			'midnight-swim-printable-kindle-insert',
		);
	}
}

if (!function_exists('bbb_staged_product_is_locked')) {
	function bbb_staged_product_is_locked(int $post_id): bool {
		if ('download' !== get_post_type($post_id)) {
			return false;
		}

		$now = new DateTimeImmutable('now', new DateTimeZone('America/Los_Angeles'));
		if ($now >= bbb_staged_product_release_at()) {
			return false;
		}

		return in_array(sanitize_title((string) get_post_field('post_name', $post_id)), bbb_staged_product_handles(), true);
	}
}

if (!function_exists('bbb_staged_product_block_cart_item')) {
	function bbb_staged_product_block_cart_item($item) {
		$download_id = is_array($item) ? (int) ($item['id'] ?? 0) : 0;
		if ($download_id > 0 && bbb_staged_product_is_locked($download_id)) {
			if (function_exists('edd_set_error')) {
				edd_set_error('bbb_staged_product_locked', 'This download unlocks July 1.');
			}

			return false;
		}

		return $item;
	}
}
add_filter('edd_add_to_cart_item', 'bbb_staged_product_block_cart_item', 20);

if (!function_exists('bbb_staged_product_remove_locked_cart_items')) {
	function bbb_staged_product_remove_locked_cart_items(): void {
		if (!function_exists('edd_get_cart_contents') || !function_exists('edd_remove_from_cart')) {
			return;
		}

		$removed = false;
		foreach ((array) edd_get_cart_contents() as $key => $item) {
			$download_id = is_array($item) ? (int) ($item['id'] ?? 0) : 0;
			if ($download_id > 0 && bbb_staged_product_is_locked($download_id)) {
				edd_remove_from_cart($key);
				$removed = true;
			}
		}

		if ($removed && function_exists('edd_set_error')) {
			edd_set_error('bbb_staged_product_locked', 'This download unlocks July 1.');
		}
	}
}
add_action('edd_pre_process_purchase', 'bbb_staged_product_remove_locked_cart_items', 1);
