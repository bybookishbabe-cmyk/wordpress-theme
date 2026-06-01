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
		'products'                     => 'shop',
		'sss-library'                  => 'member-library',
		'sss-library-page'             => 'library',
		'sss-made-for-you'             => 'made-for-you',
		'sss-printable-kindle'         => 'kindle-inserts',
		'sss-printable-kindle-inserts' => 'monthly-theme',
		'sss-canva-templates'          => 'smut-sentiment-society',
		'sss-freebies'                 => 'smut-sentiment-society',
		'sss-private-shelf'            => 'library',
		'sss-quote-wall'               => 'quote-library',
		'quote-wall'                   => 'quote-library',
		'sss-series'                   => 'series',
		'sss-series-page'              => 'series',
		'society-library'              => 'smut-sentiment-society',
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

		$direct_page_redirects = array(
			'canva-templates'              => '/smut-sentiment-society/',
			'freebies'                     => '/smut-sentiment-society/',
			'member-library'               => '/library/',
			'private-shelf'                => '/library/',
			'products'                     => '/shop/',
			'series'                       => '/series-reading-orders/',
			'society-library'              => '/smut-sentiment-society/',
			'sss-library-page'             => '/library/',
			'sss-printable-kindle'         => '/monthly-theme/',
			'sss-printable-kindle-inserts' => '/monthly-theme/',
			'sss-canva-templates'          => '/smut-sentiment-society/',
			'sss-freebies'                 => '/smut-sentiment-society/',
			'sss-private-shelf'            => '/library/',
		);
		if (isset($direct_page_redirects[$path])) {
			bbb_redirect_with_query_string(home_url($direct_page_redirects[$path]), 301);
		}

		if (preg_match('#^if-you-liked-pages/(books-like-[^/]+)/?$#', $path, $matches)) {
			$if_you_liked_slug = preg_replace('#^books-like-#', 'if-you-liked-', sanitize_title($matches[1]));
			if (is_string($if_you_liked_slug) && '' !== $if_you_liked_slug) {
				bbb_redirect_with_query_string(home_url('/if-you-liked-pages/' . $if_you_liked_slug . '/'), 301);
			}
		}

		if (preg_match('#^if-you-liked-pages/(if-you-liked-[^/]+)/?$#', $path, $matches) && function_exists('bbb_books_like_source_for_slug')) {
			$source   = bbb_books_like_source_for_slug(sanitize_title($matches[1]));
			$template = get_theme_file_path('page-books-like.php');
			if ($source instanceof WP_Post && file_exists($template)) {
				bbb_mark_virtual_route_found();
				require $template;
				exit;
			}
		}

		$spice_level = isset($_GET['spice']) ? absint(wp_unslash($_GET['spice'])) : 0;
		if (('' === $path || 'romance-books-by-spice-level' === $path) && $spice_level >= 1 && $spice_level <= 5) {
			wp_safe_redirect(home_url('/romance-books-by-spice-level/spice-' . $spice_level . '/'), 301);
			exit;
		}

		if ('reader-quizes' === $path || 'pages/reader-quizes' === $path) {
			wp_safe_redirect(bbb_page_url('reader-quizzes'), 301);
			exit;
		}

		if (preg_match('#^if-you-liked-(.+)-read-these-next/?$#', $path, $matches)) {
			bbb_redirect_with_query_string(home_url('/books-like-' . sanitize_title($matches[1]) . '/'), 301);
		}

		$protected_guide_slugs = array(
			'the-ultimate-dark-romance-reading-guide',
			'the-ultimate-romantasy-reading-guide',
			'the-ultimate-sports-romance-reading-guide',
		);
		if (preg_match('#^curated-romance-guides/([^/]+)/?$#', $path, $matches)) {
			$legacy_guide_slug = sanitize_title($matches[1]);
			if (in_array($legacy_guide_slug, $protected_guide_slugs, true)) {
				$guide_post = get_page_by_path($legacy_guide_slug, OBJECT, 'post');
				if ($guide_post instanceof WP_Post && 'publish' === $guide_post->post_status) {
					bbb_redirect_with_query_string((string) get_permalink($guide_post), 301);
				}
			}
		}
		if (in_array($path, $protected_guide_slugs, true)) {
			$guide_post = get_page_by_path($path, OBJECT, 'post');
			if ($guide_post instanceof WP_Post && 'publish' === $guide_post->post_status) {
				return;
			}
		}

		$seo_slug_redirects = get_option('bbb_seo_slug_redirects', array());
		if (is_array($seo_slug_redirects) && preg_match('#^curated-romance-guides/([^/]+)/?$#', $path, $matches)) {
			$legacy_slug = sanitize_title($matches[1]);
			if (isset($seo_slug_redirects[$legacy_slug])) {
				$target_slug = sanitize_title((string) $seo_slug_redirects[$legacy_slug]);
				if ('' !== $target_slug && $target_slug !== $legacy_slug) {
					bbb_redirect_with_query_string(home_url('/' . $target_slug . '/'), 301);
				}
			}
		}

		if (is_array($seo_slug_redirects) && isset($seo_slug_redirects[$path])) {
			$target_slug = sanitize_title((string) $seo_slug_redirects[$path]);
			if ('' !== $target_slug && $target_slug !== $path) {
				bbb_redirect_with_query_string(home_url('/' . $target_slug . '/'), 301);
			}
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
			bbb_redirect_with_query_string(home_url('/account/'), 302);
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
	-999
);
