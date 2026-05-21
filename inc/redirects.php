<?php
/**
 * Page redirects from the Shopify conversion.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_redirect_with_query_string(string $target, int $status = 301): void {
	$query = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
	if ('' !== $query) {
		$target .= str_contains($target, '?') ? '&' . $query : '?' . $query;
	}

	wp_safe_redirect($target, $status);
	exit;
}

function bbb_shopify_post_permalink(string $slug): string {
	$post = get_posts(
		array(
			'name'           => sanitize_title($slug),
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
		)
	);

	if ($post) {
		$permalink = get_permalink($post[0]);
		if ($permalink) {
			return $permalink;
		}
	}

	return home_url('/' . sanitize_title($slug) . '/');
}

function bbb_shopify_product_permalink(string $handle): string {
	$handle     = sanitize_title($handle);
	$post_types = array_values(array_filter(array('download', 'product'), 'post_type_exists'));
	if ($post_types) {
		$product = get_posts(
			array(
				'name'           => $handle,
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
			)
		);
		if ($product) {
			$permalink = get_permalink($product[0]);
			if ($permalink) {
				return $permalink;
			}
		}
	}

	return home_url('/product/' . $handle . '/');
}

function bbb_shopify_collection_permalink(string $handle): string {
	$handle = sanitize_title($handle);
	if ('all' === $handle) {
		return home_url('/shop/');
	}

	foreach (array('product_cat', 'download_category') as $taxonomy) {
		if (!taxonomy_exists($taxonomy)) {
			continue;
		}

		$term = get_term_by('slug', $handle, $taxonomy);
		if ($term instanceof WP_Term) {
			$permalink = get_term_link($term);
			if (!is_wp_error($permalink)) {
				return $permalink;
			}
		}
	}

	return home_url('/product-category/' . $handle . '/');
}

function bbb_shopify_legacy_redirect_target(string $path): string {
	$path = trim($path, '/');

	$clean_pages = array(
		'sss-library'                  => 'member-library',
		'sss-library-page'             => 'member-library',
		'sss-made-for-you'             => 'made-for-you',
		'sss-printable-kindle'         => 'kindle-inserts',
		'sss-printable-kindle-inserts' => 'kindle-inserts',
		'sss-canva-templates'          => 'canva-templates',
		'sss-freebies'                 => 'freebies',
		'sss-private-shelf'            => 'private-shelf',
		'sss-quote-wall'               => 'quote-wall',
		'sss-series'                   => 'series',
		'sss-series-page'              => 'series',
	);
	if (isset($clean_pages[$path])) {
		return bbb_page_url($clean_pages[$path]);
	}

	if (str_starts_with($path, 'pages/')) {
		return bbb_page_url(substr($path, strlen('pages/')));
	}

	if (preg_match('#^blogs/([^/]+)/page/([0-9]+)/?$#', $path, $matches)) {
		return home_url('/' . sanitize_title($matches[1]) . '/page/' . max(1, (int) $matches[2]) . '/');
	}

	if (preg_match('#^blogs/([^/]+)/tagged/([^/]+)/?$#', $path, $matches)) {
		$term = get_term_by('slug', sanitize_title($matches[2]), 'post_tag');
		if ($term instanceof WP_Term) {
			$permalink = get_tag_link($term);
			if (!is_wp_error($permalink)) {
				return $permalink;
			}
		}

		return home_url('/' . sanitize_title($matches[1]) . '/');
	}

	if (preg_match('#^blogs/([^/]+)/([^/]+)/?$#', $path, $matches)) {
		return bbb_shopify_post_permalink($matches[2]);
	}

	if (preg_match('#^blogs/([^/]+)/?$#', $path, $matches)) {
		return bbb_page_url(sanitize_title($matches[1]));
	}

	if (str_starts_with($path, 'collections/')) {
		return bbb_shopify_collection_permalink(substr($path, strlen('collections/')));
	}

	if (str_starts_with($path, 'products/')) {
		return bbb_shopify_product_permalink(substr($path, strlen('products/')));
	}

	return '';
}

add_action(
	'template_redirect',
	static function (): void {
		$path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');

		$spice_level = isset($_GET['spice']) ? absint(wp_unslash($_GET['spice'])) : 0;
		if ('romance-books-by-spice-level' === $path && $spice_level >= 1 && $spice_level <= 5) {
			wp_safe_redirect(home_url('/romance-books-by-spice-level/spice-' . $spice_level . '/'), 301);
			exit;
		}

		$series_handle = isset($_GET['series']) ? sanitize_title(wp_unslash($_GET['series'])) : '';
		if ('series' === $path && '' !== $series_handle) {
			wp_safe_redirect(home_url('/series/' . $series_handle . '/'), 301);
			exit;
		}

		if ($path === 'cart' && function_exists('wc_get_cart_url')) {
			bbb_redirect_with_query_string(wc_get_cart_url(), 302);
		}

		if ($path === 'account/login') {
			bbb_redirect_with_query_string(wp_login_url(), 302);
		}

		if (($path === 'account' || str_starts_with($path, 'account/')) && function_exists('wc_get_account_endpoint_url')) {
			bbb_redirect_with_query_string(wc_get_account_endpoint_url('dashboard'), 302);
		}

		$legacy_target = bbb_shopify_legacy_redirect_target($path);
		if ('' !== $legacy_target) {
			bbb_redirect_with_query_string($legacy_target, 301);
		}

		if (!is_page('reading-list')) {
			return;
		}

		$target = get_page_by_path('curated-romance-guides');
		bbb_redirect_with_query_string($target ? get_permalink($target) : home_url('/curated-romance-guides/'), 301);
	},
	-20
);
