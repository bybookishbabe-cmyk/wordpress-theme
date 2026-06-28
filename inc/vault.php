<?php
/**
 * Full vault access and asset helpers.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_vault_full_access_product_slugs(): array {
	return (array) apply_filters(
		'bbb_vault_full_access_product_slugs',
		array(
			'bybookishbabe-vault',
			'full-vault',
			'the-full-vault',
			'bybookishbabe-full-vault',
			'printable-kindle-insert-vault',
		)
	);
}

function bbb_vault_product_id_by_slug(string $slug): int {
	$slug = sanitize_title($slug);
	if ('' === $slug) {
		return 0;
	}

	foreach (array('download', 'product') as $post_type) {
		if (!post_type_exists($post_type)) {
			continue;
		}

		$post = get_page_by_path($slug, OBJECT, $post_type);
		if ($post instanceof WP_Post) {
			return (int) $post->ID;
		}
	}

	return 0;
}

function bbb_vault_full_access_product_ids(): array {
	$ids = array_map('absint', (array) get_option('bbb_vault_full_access_product_ids', array()));

	foreach (bbb_vault_full_access_product_slugs() as $slug) {
		$id = bbb_vault_product_id_by_slug((string) $slug);
		if ($id > 0) {
			$ids[] = $id;
		}
	}

	return array_values(array_unique(array_filter($ids)));
}

function bbb_vault_manual_access_emails(): array {
	$stored = get_option('bbb_vault_full_access_emails', array());
	if (is_string($stored)) {
		$stored = preg_split('/[\s,]+/', $stored);
	}

	$emails = array();
	foreach ((array) $stored as $email) {
		$email = function_exists('bbb_reader_normalize_email') ? bbb_reader_normalize_email((string) $email) : strtolower(trim((string) $email));
		if ('' !== $email && is_email($email)) {
			$emails[] = $email;
		}
	}

	return array_values(array_unique($emails));
}

function bbb_vault_identity_has_manual_access(string $email, int $user_id): bool {
	$email = function_exists('bbb_reader_normalize_email') ? bbb_reader_normalize_email($email) : strtolower(trim($email));
	if ('' !== $email && in_array($email, bbb_vault_manual_access_emails(), true)) {
		return true;
	}

	return $user_id > 0 && '1' === get_user_meta($user_id, 'bbb_full_vault_access', true);
}

function bbb_vault_edd_customer_ids_for_identity(string $email, int $user_id = 0): array {
	if (!function_exists('edd_get_customer_by')) {
		return array();
	}

	$customer_ids = array();
	if ($user_id > 0) {
		$customer = edd_get_customer_by('user_id', $user_id);
		if (is_object($customer) && !empty($customer->id)) {
			$customer_ids[] = (int) $customer->id;
		}
	}

	$email = function_exists('bbb_reader_normalize_email') ? bbb_reader_normalize_email($email) : strtolower(trim($email));
	if ('' !== $email && is_email($email)) {
		$customer = edd_get_customer_by('email', $email);
		if (is_object($customer) && !empty($customer->id)) {
			$customer_ids[] = (int) $customer->id;
		}
	}

	return array_values(array_unique(array_filter($customer_ids)));
}

function bbb_vault_identity_has_edd_product(string $email, int $user_id, array $product_ids): bool {
	if (!function_exists('edd_get_orders') || !function_exists('edd_get_order_items')) {
		return false;
	}

	$product_ids = array_values(array_unique(array_filter(array_map('absint', $product_ids))));
	if (!$product_ids) {
		return false;
	}

	foreach (bbb_vault_edd_customer_ids_for_identity($email, $user_id) as $customer_id) {
		$orders = edd_get_orders(
			array(
				'customer_id' => $customer_id,
				'type'        => 'sale',
				'status__in'  => array('complete', 'publish', 'processing'),
				'number'      => 100,
			)
		);

		foreach ((array) $orders as $order) {
			$order_id = isset($order->id) ? (int) $order->id : (isset($order->ID) ? (int) $order->ID : 0);
			if ($order_id <= 0) {
				continue;
			}

			$items = edd_get_order_items(
				array(
					'order_id' => $order_id,
					'number'   => 100,
				)
			);

			foreach ((array) $items as $item) {
				$item_product_id = isset($item->product_id) ? (int) $item->product_id : (isset($item->download_id) ? (int) $item->download_id : 0);
				if ($item_product_id > 0 && in_array($item_product_id, $product_ids, true)) {
					return true;
				}
			}
		}
	}

	return false;
}

function bbb_vault_identity_has_woo_product(string $email, int $user_id, array $product_ids): bool {
	if (!function_exists('wc_get_orders')) {
		return false;
	}

	$product_ids = array_values(array_unique(array_filter(array_map('absint', $product_ids))));
	if (!$product_ids) {
		return false;
	}

	$order_args = array(
		'limit'   => 100,
		'status'  => array('wc-completed', 'wc-processing'),
		'orderby' => 'date',
		'order'   => 'DESC',
	);
	$orders = array();
	if ($user_id > 0) {
		$orders = array_merge($orders, (array) wc_get_orders(array('customer_id' => $user_id) + $order_args));
	}
	if ('' !== $email && is_email($email)) {
		$orders = array_merge($orders, (array) wc_get_orders(array('billing_email' => $email) + $order_args));
	}

	foreach ($orders as $order) {
		if (!$order instanceof WC_Order) {
			continue;
		}
		foreach ($order->get_items() as $item) {
			$product_id = (int) $item->get_product_id();
			$variation_id = (int) $item->get_variation_id();
			if (in_array($product_id, $product_ids, true) || ($variation_id > 0 && in_array($variation_id, $product_ids, true))) {
				return true;
			}
		}
	}

	return false;
}

function bbb_vault_current_identity(): ?array {
	$identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
	if ($identity || !function_exists('bbb_reader_is_local_request') || !bbb_reader_is_local_request()) {
		return $identity;
	}

	return array(
		'email'       => 'autumn@example.com',
		'displayName' => 'autumn',
		'userId'      => 0,
		'user'        => null,
	);
}

function bbb_vault_user_has_full_access(?array $identity = null): bool {
	$identity = $identity ?: bbb_vault_current_identity();
	if (!$identity) {
		return false;
	}

	$user_id = (int) ($identity['userId'] ?? 0);
	if ($user_id > 0 && current_user_can('manage_options')) {
		return true;
	}

	$email = function_exists('bbb_reader_normalize_email') ? bbb_reader_normalize_email((string) ($identity['email'] ?? '')) : strtolower(trim((string) ($identity['email'] ?? '')));
	$product_ids = bbb_vault_full_access_product_ids();

	return bbb_vault_identity_has_manual_access($email, $user_id)
		|| bbb_vault_identity_has_edd_product($email, $user_id, $product_ids)
		|| bbb_vault_identity_has_woo_product($email, $user_id, $product_ids)
		|| '1' === get_user_meta($user_id, 'bbb_full_vault_access', true);
}

function bbb_vault_download_files_for_post(int $post_id): array {
	$files = array();
	foreach (array('edd_download_files', '_downloadable_files') as $meta_key) {
		$stored = get_post_meta($post_id, $meta_key, true);
		if (!is_array($stored)) {
			continue;
		}

		foreach ($stored as $file) {
			if (!is_array($file)) {
				continue;
			}

			$url = trim((string) ($file['file'] ?? $file['url'] ?? ''));
			if ('' === $url) {
				continue;
			}

			$files[] = array(
				'label' => sanitize_text_field((string) ($file['name'] ?? basename((string) wp_parse_url($url, PHP_URL_PATH)) ?: 'download')),
				'url'   => esc_url_raw($url),
			);
		}
	}

	return array_values(array_unique($files, SORT_REGULAR));
}

function bbb_vault_asset_group_for_post(WP_Post $post): string {
	$taxonomies = array_values(array_filter(array('download_category', 'product_cat', 'download_tag', 'product_tag'), 'taxonomy_exists'));
	$terms = $taxonomies ? wp_get_object_terms((int) $post->ID, $taxonomies, array('fields' => 'names')) : array();
	$text = strtolower($post->post_title . ' ' . implode(' ', is_wp_error($terms) ? array() : (array) $terms));

	if (str_contains($text, 'kindle insert')) {
		return 'kindle inserts';
	}
	if (str_contains($text, 'template') || str_contains($text, 'canva')) {
		return 'templates';
	}
	if (str_contains($text, 'tracker') || str_contains($text, 'planner') || str_contains($text, 'journal')) {
		return 'trackers';
	}

	return 'extras';
}

function bbb_vault_asset_posts(): array {
	$post_types = array_values(array_filter(array('download', 'product'), 'post_type_exists'));
	if (!$post_types) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => array('publish', 'private'),
			'posts_per_page' => 300,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'edd_download_files',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_downloadable_files',
					'compare' => 'EXISTS',
				),
				array(
					'key'   => '_bbb_is_digital_product',
					'value' => 'yes',
				),
			),
		)
	);
}

function bbb_vault_assets(): array {
	$access_product_ids = bbb_vault_full_access_product_ids();
	$assets = array();

	foreach (bbb_vault_asset_posts() as $post) {
		if (!$post instanceof WP_Post || in_array((int) $post->ID, $access_product_ids, true)) {
			continue;
		}

		$files = bbb_vault_download_files_for_post((int) $post->ID);
		if (!$files) {
			continue;
		}

		$assets[] = array(
			'id'        => (int) $post->ID,
			'title'     => get_the_title($post),
			'url'       => get_permalink($post) ?: '',
			'image'     => function_exists('bbb_society_product_image_url') ? bbb_society_product_image_url((int) $post->ID) : (get_the_post_thumbnail_url($post, 'medium_large') ?: ''),
			'group'     => bbb_vault_asset_group_for_post($post),
			'fileCount' => count($files),
			'files'     => $files,
		);
	}

	usort(
		$assets,
		static function (array $a, array $b): int {
			$a_has_image = '' !== trim((string) ($a['image'] ?? '')) ? 1 : 0;
			$b_has_image = '' !== trim((string) ($b['image'] ?? '')) ? 1 : 0;
			return ($b_has_image <=> $a_has_image) ?: strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
		}
	);

	return $assets;
}

function bbb_vault_buy_url(): string {
	foreach (bbb_vault_full_access_product_slugs() as $slug) {
		$id = bbb_vault_product_id_by_slug((string) $slug);
		if ($id > 0) {
			$url = get_permalink($id);
			if ($url) {
				return (string) $url;
			}
		}
	}

	return function_exists('bbb_page_url') ? bbb_page_url('shop') : home_url('/shop/');
}

function bbb_vault_primary_product_id(): int {
	$ids = bbb_vault_full_access_product_ids();
	return $ids ? (int) $ids[0] : 0;
}

function bbb_vault_price_label(): string {
	$product_id = bbb_vault_primary_product_id();
	if ($product_id <= 0 || !function_exists('edd_get_download_price')) {
		return '';
	}

	$price = edd_get_download_price($product_id);
	if ('' === (string) $price) {
		return '';
	}

	if (function_exists('edd_format_amount')) {
		$price = edd_format_amount($price);
	}

	return function_exists('edd_currency_filter') ? (string) edd_currency_filter($price) : '$' . (string) $price;
}

function bbb_vault_upgrade_checkout_url(): string {
	$product_id = bbb_vault_primary_product_id();
	if ($product_id <= 0) {
		return bbb_vault_buy_url();
	}

	$url = add_query_arg(
		array(
			'edd_action' => 'bbb_upgrade_to_vault',
			'download_id' => $product_id,
		),
		function_exists('edd_get_checkout_uri') ? edd_get_checkout_uri() : home_url('/checkout/')
	);

	return wp_nonce_url($url, 'bbb-upgrade-to-vault-' . $product_id, 'bbb_vault_nonce');
}

function bbb_vault_process_upgrade_to_vault(array $data): void {
	$product_id = isset($data['download_id']) ? absint($data['download_id']) : bbb_vault_primary_product_id();
	$nonce = isset($data['bbb_vault_nonce']) ? sanitize_text_field((string) $data['bbb_vault_nonce']) : '';

	if ($product_id <= 0 || !wp_verify_nonce($nonce, 'bbb-upgrade-to-vault-' . $product_id)) {
		wp_safe_redirect(function_exists('edd_get_checkout_uri') ? edd_get_checkout_uri() : home_url('/checkout/'));
		exit;
	}

	$vault_ids = bbb_vault_full_access_product_ids();
	if (!$vault_ids || !in_array($product_id, $vault_ids, true)) {
		wp_safe_redirect(bbb_vault_buy_url());
		exit;
	}

	if (function_exists('edd_empty_cart')) {
		edd_empty_cart();
	}

	if (function_exists('edd_add_to_cart')) {
		edd_add_to_cart($product_id, array());
	}

	wp_safe_redirect(function_exists('edd_get_checkout_uri') ? edd_get_checkout_uri() : home_url('/checkout/'));
	exit;
}

add_action('edd_bbb_upgrade_to_vault', 'bbb_vault_process_upgrade_to_vault');
