<?php
/**
 * URL resolution helpers: Shopify → WordPress.
 *
 * Converts every Shopify URL pattern found in the bybookishbabe-theme into
 * its WordPress equivalent. Safe to call on already-WP or external URLs —
 * unknown/external strings are returned verbatim.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

/**
 * Resolve a Shopify URL (any format) to its WordPress equivalent.
 *
 * Handles:
 *   shopify://pages/{slug}                   → /{slug}/
 *   shopify://blogs/{blog}/{article}          → /{blog}/{article}/
 *   shopify://blogs/{blog}                    → /{blog}/
 *   shopify://collections/all                 → /shop/
 *   shopify://collections/{handle}            → /product-category/{handle}/
 *   shopify://products/{handle}               → /product/{handle}/
 *   /pages/{slug}                             → /{slug}/
 *   /blogs/{blog}/{article}                   → /{blog}/{article}/
 *   /blogs/{blog}                             → /{blog}/
 *   /collections/all                          → /shop/
 *   /collections/{handle}                     → /product-category/{handle}/
 *   /products/{handle}                        → /product/{handle}/
 *   /cart                                     → WooCommerce cart URL
 *   /account*                                 → WooCommerce account URL or /account/
 *   https://bybookishbabe.com/pages/{slug}    → /{slug}/
 *   https://bybookishbabe.com/blogs/…         → same rules as /blogs/…
 *   External or already-WP URLs               → returned verbatim
 *
 * @param string $url Raw URL from a Shopify link field or navigation item.
 * @return string     Resolved WordPress-relative or absolute URL.
 */
function bbb_resolve_shopify_url( string $url ): string {
	if ( '' === trim( $url ) ) {
		return home_url( '/' );
	}

	// ── shopify:// protocol ──────────────────────────────────────────────────
	if ( str_starts_with( $url, 'shopify://' ) ) {
		$path = substr( $url, strlen( 'shopify://' ) );
		return bbb_resolve_shopify_path( $path );
	}

	// ── Absolute URLs from the Shopify storefront domain ────────────────────
	$shopify_origin = 'https://bybookishbabe.com';
	if ( str_starts_with( $url, $shopify_origin . '/' ) ) {
		$path = substr( $url, strlen( $shopify_origin ) );
		return bbb_resolve_shopify_path( ltrim( $path, '/' ) );
	}

	// ── Relative Shopify paths (/pages/…, /blogs/…, /collections/…, etc.) ──
	if ( str_starts_with( $url, '/' ) ) {
		$path     = (string) parse_url( $url, PHP_URL_PATH );
		$stripped = ltrim( $path, '/' );

		if ( 'cart' === trim( $stripped, '/' ) ) {
			return function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
		}

		if ( 'account/login' === trim( $stripped, '/' ) ) {
			return home_url( '/account/' );
		}

		if ( 'account' === trim( $stripped, '/' ) || str_starts_with( $stripped, 'account/' ) ) {
			return home_url( '/account/' );
		}

		if ( bbb_shopify_path_has_rewrite_rule( $stripped ) ) {
			return bbb_resolve_shopify_path( $stripped );
		}

		// Unknown relative path — return as-is (already a WP URL).
		return $url;
	}

	// ── External URLs — return verbatim ─────────────────────────────────────
	return $url;
}

/**
 * Determine whether a path should be rewritten as a Shopify-origin path.
 *
 * @param string $path Path without a leading slash.
 * @return bool
 */
function bbb_shopify_path_has_rewrite_rule( string $path ): bool {
	return str_starts_with( $path, 'pages/' )
		|| str_starts_with( $path, 'blogs/' )
		|| str_starts_with( $path, 'collections/' )
		|| str_starts_with( $path, 'products/' );
}

/**
 * Resolve a Shopify path segment (without leading slash or shopify://) to a
 * WordPress URL. Used internally by bbb_resolve_shopify_url().
 *
 * @param string $path e.g. "pages/library", "collections/all", "blogs/curated-romance-guides/article-slug"
 * @return string      Absolute WordPress URL.
 */
function bbb_resolve_shopify_path( string $path ): string {
	// pages/{slug} → /{slug}/
	if ( str_starts_with( $path, 'pages/' ) ) {
		$slug = substr( $path, strlen( 'pages/' ) );
		return bbb_page_url( $slug );
	}

	// blogs/{blog}/{article} → /{blog}/{article}/
	// blogs/{blog}           → /{blog}/
	if ( str_starts_with( $path, 'blogs/' ) ) {
		$rest   = substr( $path, strlen( 'blogs/' ) );
		$parts  = explode( '/', $rest, 2 );
		$blog   = $parts[0] ?? '';
		$article = $parts[1] ?? '';

		if ( '' !== $article ) {
			return home_url( '/' . $blog . '/' . $article . '/' );
		}
		return home_url( '/' . $blog . '/' );
	}

	// collections/all      → /shop/
	// collections/{handle} → /product-category/{handle}/
	if ( str_starts_with( $path, 'collections/' ) ) {
		$handle = substr( $path, strlen( 'collections/' ) );
		if ( 'all' === trim( $handle, '/' ) ) {
			return home_url( '/shop/' );
		}
		return home_url( '/product-category/' . trim( $handle, '/' ) . '/' );
	}

	// products/{handle} → /product/{handle}/
	if ( str_starts_with( $path, 'products/' ) ) {
		$handle = substr( $path, strlen( 'products/' ) );
		return home_url( '/product/' . trim( $handle, '/' ) . '/' );
	}

	// Unrecognised — best-effort passthrough.
	return home_url( '/' . trim( $path, '/' ) . '/' );
}

/**
 * Return the WordPress permalink for a page by its slug.
 *
 * Tries get_page_by_path() first (live DB lookup) so that any WordPress page
 * that exists returns its canonical permalink. Falls back to a constructed
 * home_url() path for pages not yet created.
 *
 * Alias of the existing bbb_resolve_page_url() in bbb-helpers.php but named
 * bbb_page_url() for conciseness inside menu/link code.
 *
 * @param string $slug Shopify page handle / WordPress page slug.
 * @return string      Absolute WordPress URL.
 */
function bbb_page_url( string $slug ): string {
	$slug = trim( $slug, '/' );
	if ( '' === $slug ) {
		return home_url( '/' );
	}

	$clean_slugs = array(
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
	$slug = $clean_slugs[ $slug ] ?? $slug;

	$page = get_page_by_path( $slug );
	if ( $page instanceof WP_Post ) {
		$permalink = get_permalink( $page );
		if ( $permalink ) {
			return $permalink;
		}
	}

	return home_url( '/' . $slug . '/' );
}

/**
 * Normalize old internal links to clean WordPress URLs before output.
 *
 * This complements the Redirection plugin import: redirects protect old
 * inbound links, while this keeps links generated by WordPress clean.
 *
 * @param string $url URL to normalize.
 * @return string
 */
function bbb_normalize_internal_url( string $url ): string {
	$trimmed = trim( $url );
	if ( '' === $trimmed || str_starts_with( $trimmed, '#' ) || preg_match( '#^(?:mailto|tel|sms):#i', $trimmed ) ) {
		return $url;
	}

	$parts = wp_parse_url( $trimmed );
	if ( ! is_array( $parts ) ) {
		return $url;
	}

	$host = isset( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';
	if ( '' !== $host ) {
		$home_host      = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$internal_hosts = array_filter(
			array_unique(
				array(
					$home_host,
					'bybookishbabe.com',
					'www.bybookishbabe.com',
					'bybookishbabe.wpenginepowered.com',
					'bybookishbabe.local',
				)
			)
		);

		if ( ! in_array( $host, $internal_hosts, true ) ) {
			return $url;
		}
	}

	$path  = isset( $parts['path'] ) ? '/' . ltrim( (string) $parts['path'], '/' ) : '/';
	$query = array();
	if ( isset( $parts['query'] ) ) {
		wp_parse_str( (string) $parts['query'], $query );
	}

	$path_only = trim( $path, '/' );

	if ( 'romance-books-by-spice-level' === $path_only && isset( $query['spice'] ) ) {
		$spice = absint( $query['spice'] );
		if ( $spice >= 1 && $spice <= 5 ) {
			return home_url( '/romance-books-by-spice-level/spice-' . $spice . '/' );
		}
	}

	if ( ( 'series' === $path_only || 'pages/series' === $path_only ) && isset( $query['series'] ) ) {
		$series = sanitize_title( (string) $query['series'] );
		if ( '' !== $series ) {
			return home_url( '/series/' . $series . '/' );
		}
	}

	if ( bbb_shopify_path_has_rewrite_rule( $path_only ) ) {
		return bbb_resolve_shopify_path( $path_only );
	}

	$legacy_target = function_exists( 'bbb_shopify_legacy_redirect_target' ) ? bbb_shopify_legacy_redirect_target( $path_only ) : '';
	if ( '' !== $legacy_target ) {
		return $legacy_target;
	}

	return $url;
}

function bbb_normalize_links_in_markup( string $markup ): string {
	return preg_replace_callback(
		'#\bhref=(["\'])(.*?)\1#i',
		static function ( array $matches ): string {
			$quote      = $matches[1];
			$normalized = bbb_normalize_internal_url( html_entity_decode( $matches[2], ENT_QUOTES ) );

			return 'href=' . $quote . esc_url( $normalized ) . $quote;
		},
		$markup
	) ?? $markup;
}

add_filter(
	'the_content',
	static fn( string $content ): string => is_admin() ? $content : bbb_normalize_links_in_markup( $content ),
	20
);

add_filter(
	'nav_menu_link_attributes',
	static function ( array $atts ): array {
		if ( ! is_admin() && isset( $atts['href'] ) ) {
			$atts['href'] = bbb_normalize_internal_url( (string) $atts['href'] );
		}

		return $atts;
	}
);

add_filter(
	'clean_url',
	static function ( string $good_protocol_url, string $original_url, string $_context ): string {
		if ( is_admin() ) {
			return $good_protocol_url;
		}

		$normalized = bbb_normalize_internal_url( $original_url );
		if ( $normalized === $original_url ) {
			return $good_protocol_url;
		}

		return $normalized;
	},
	10,
	3
);
