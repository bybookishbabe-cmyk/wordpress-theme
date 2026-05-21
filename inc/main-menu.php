<?php
/**
 * Hardcoded fallback main menu for the Shopify → WordPress port.
 *
 * WHY THIS EXISTS
 * ───────────────
 * Shopify stores navigation link lists in the admin database, not inside theme
 * files. The bybookishbabe-theme only references the menu by handle
 * ("main-menu"). The actual item list was NOT exported with the theme and
 * therefore cannot be reconstructed from source files alone.
 *
 * Until the WordPress admin "Main Navigation" menu is populated, this file
 * provides a hardcoded fallback so the header/drawer never render empty.
 *
 * HOW TO USE
 * ──────────
 * 1. Import bbb_get_fallback_main_menu() into functions.php (done below).
 * 2. To activate WP-managed menus: go to Appearance → Menus in WP admin,
 *    assign a menu to the "Main Navigation" location. Once assigned,
 *    bbb_get_header_menu_items() in inc/header-functions.php automatically
 *    uses the WP menu instead of this fallback.
 *
 * ⚠️  INCOMPLETE SKELETON — REQUIRES SHOPIFY EXPORT
 * ──────────────────────────────────────────────────
 * The items below are inferred from:
 *   - Homepage section links in templates/index.json
 *   - Page template filenames (templates/page.*.json)
 *   - Hardcoded links in sections/header.liquid
 *   - Society-gated page slugs in functions.php
 *
 * The exact menu titles, order, and child items MUST be verified against
 * Shopify Admin → Online Store → Navigation → "Main menu".
 * Export steps: Shopify Admin → Online Store → Navigation → Main menu → copy
 * items manually or export via Shopify CLI: `shopify theme pull`.
 *
 * Items marked [INFERRED] are best-guess reconstructions.
 * Items marked [CONFIRMED] appear explicitly in theme source files.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

/**
 * Return the hardcoded fallback main menu as a plain array.
 *
 * Structure:
 *   array(
 *     array(
 *       'title'    => string,    // Menu item label.
 *       'url'      => string,    // Absolute WordPress URL.
 *       'children' => array(     // Child items (same structure, no grandchildren shown here).
 *         array( 'title' => ..., 'url' => ..., 'children' => array() ),
 *       ),
 *     ),
 *   )
 *
 * Replace the contents with the exact Shopify admin export once available.
 *
 * @return array<int, array{title: string, url: string, children: array}>
 */
function bbb_get_fallback_main_menu(): array {
	return array(
		array(
			'title'    => 'home',
			'url'      => home_url( '/' ),
			'children' => array(),
		),

		// ── Library [CONFIRMED — primary hero CTA: /library/] ─────
		array(
			'title'    => 'library',
			'url'      => bbb_page_url( 'library' ),
			'children' => array(),
		),

		array(
			'title'    => 'find your read',
			'url'      => bbb_page_url( 'what-to-read-next' ),
			'children' => array(
				array(
					'title'    => 'what should i read next',
					'url'      => bbb_page_url( 'what-to-read-next' ),
					'children' => array(),
				),
				array(
					'title'    => 'books by spice level',
					'url'      => bbb_page_url( 'romance-books-by-spice-level' ),
					'children' => array(),
				),
				array(
					'title'    => 'reader quizzes',
					'url'      => bbb_page_url( 'reader-quizes' ),
					'children' => array(),
				),
			),
		),

		array(
			'title'    => 'reviews & guides',
			'url'      => home_url( '/curated-romance-guides/' ),
			'children' => array(
				array(
					'title'    => 'book reviews',
					'url'      => bbb_page_url( 'book-reviews' ),
					'children' => array(),
				),
				array(
					'title'    => 'romance guides',
					'url'      => home_url( '/curated-romance-guides/' ),
					'children' => array(),
				),
				array(
					'title'    => 'series reading orders',
					'url'      => bbb_page_url( 'series-reading-orders' ),
					'children' => array(),
				),
				array(
					'title'    => 'books like x',
					'url'      => bbb_page_url( 'books-like' ),
					'children' => array(),
				),
			),
		),

		array(
			'title'    => 'the society',
			'url'      => bbb_page_url( 'smut-sentiment-society' ),
			'children' => array(
				array(
					'title'    => 'about the society',
					'url'      => bbb_page_url( 'smut-sentiment-society' ),
					'children' => array(),
				),
				array(
					'title'    => 'society\'s library',
					'url'      => bbb_page_url( 'library' ),
					'children' => array(),
				),
				array(
					'title'    => 'society submissions',
					'url'      => bbb_page_url( 'society-submissions' ),
					'children' => array(),
				),
			),
		),

		array(
			'title'    => 'shop',
			'url'      => bbb_page_url( 'shop' ),
			'children' => array(),
		),

	);
}

/**
 * Convert one entry from bbb_get_fallback_main_menu() into a WP_Post-shaped
 * object that is compatible with the existing bbb_render_header_dropdown_item()
 * and bbb_render_drawer_item() functions in the header template-parts.
 *
 * WordPress nav menu items are WP_Post objects with extra properties appended
 * dynamically by the menu walker (url, title, classes, children, etc.).
 * WP_Post supports dynamic properties via #[AllowDynamicProperties] (WP ≥ 6.2)
 * and __set() magic (WP < 6.2), so this is safe across all supported versions.
 *
 * @param array{title: string, url: string, children: array} $item         Raw fallback item.
 * @param WP_Post[]                                           $child_posts  Already-converted child objects.
 * @return WP_Post
 */
function bbb_fallback_item_to_post( array $item, array $child_posts = array() ): WP_Post {
	$title = (string) ( $item['title'] ?? '' );
	$url   = (string) ( $item['url']   ?? home_url( '/' ) );

	// WP_Post constructor accepts an object; only known scalar properties are
	// set here. Dynamic properties (url, title, classes, children) are assigned
	// below — WP_Post allows them.
	$post = new WP_Post(
		(object) array(
			'ID'          => 0,
			'post_title'  => $title,
			'post_name'   => sanitize_title( $title ),
			'post_status' => 'publish',
			'post_type'   => 'nav_menu_item',
		)
	);

	// Nav-menu-item dynamic properties consumed by the header template-parts.
	$post->title           = $title;   // bbb_menu_item_handle() uses $item->post_name ?: $item->title
	$post->url             = $url;
	$post->classes         = array();
	$post->menu_item_parent = 0;
	$post->children        = $child_posts;

	// Mark as current-menu-item if URL matches the request.
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$current_url = untrailingslashit( home_url( strtok( $request_uri, '?' ) ) );
	$item_url    = untrailingslashit( $url );

	if ( '' !== $item_url && $current_url === $item_url ) {
		$post->classes[] = 'current-menu-item';
	}

	// Mark as current-menu-parent if any direct child is current.
	foreach ( $child_posts as $child ) {
		if ( in_array( 'current-menu-item', (array) $child->classes, true ) ) {
			$post->classes[] = 'current-menu-parent';
			$post->classes[] = 'current-menu-ancestor';
			break;
		}
		// Also propagate ancestor flag from deeper levels.
		if ( in_array( 'current-menu-ancestor', (array) $child->classes, true ) ) {
			$post->classes[] = 'current-menu-ancestor';
			break;
		}
	}

	return $post;
}

/**
 * Convert the full bbb_get_fallback_main_menu() array into a tree of WP_Post
 * objects identical in shape to what bbb_get_header_menu_items() returns when
 * a live WP nav menu is present.
 *
 * @return WP_Post[]
 */
function bbb_get_fallback_menu_tree(): array {
	$raw  = bbb_get_fallback_main_menu();
	$tree = array();

	foreach ( $raw as $item ) {
		$child_posts = array();

		foreach ( (array) ( $item['children'] ?? array() ) as $child ) {
			$grand_posts = array();

			foreach ( (array) ( $child['children'] ?? array() ) as $grand ) {
				$grand_posts[] = bbb_fallback_item_to_post( $grand );
			}

			$child_posts[] = bbb_fallback_item_to_post( $child, $grand_posts );
		}

		$tree[] = bbb_fallback_item_to_post( $item, $child_posts );
	}

	return $tree;
}
