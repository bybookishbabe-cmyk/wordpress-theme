<?php
/**
 * WordPress bootstrap for the Shopify-faithful port.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

require_once get_theme_file_path('inc/linking.php');
require_once get_theme_file_path('inc/main-menu.php');
require_once get_theme_file_path('inc/page-router.php');
require_once get_theme_file_path('inc/header-functions.php');
require_once get_theme_file_path('inc/bbb-helpers.php');
require_once get_theme_file_path('inc/footer.php');
require_once get_theme_file_path('inc/customizer/hero-smut-sentiment.php');
require_once get_theme_file_path('inc/customizer/society-landing.php');
require_once get_theme_file_path('inc/weekly-obsession-query.php');
require_once get_theme_file_path('inc/newsletter-issue-cpt.php');
require_once get_theme_file_path('inc/acf-society-hero-options.php');
require_once get_theme_file_path('inc/blog-society-recommendations.php');
require_once get_theme_file_path('inc/society-pages.php');
require_once get_theme_file_path('inc/books/book-cpt.php');
require_once get_theme_file_path('inc/books/book-visibility.php');
require_once get_theme_file_path('inc/books/trope-colors.php');
require_once get_theme_file_path('inc/books/book-renderers.php');
require_once get_theme_file_path('inc/books/book-taxonomy-pages.php');
require_once get_theme_file_path('inc/books/books-like-helpers.php');
require_once get_theme_file_path('inc/books/article-book-connections.php');
require_once get_theme_file_path('inc/books/book-import.php');
require_once get_theme_file_path('inc/books/book-admin-importer.php');
require_once get_theme_file_path('inc/reader-quiz-helpers.php');
require_once get_theme_file_path('inc/cpt-sss-book.php');
require_once get_theme_file_path('inc/sss-book-helpers.php');
require_once get_theme_file_path('inc/cpt/sss-series.php');
require_once get_theme_file_path('inc/cpt/sss-quote.php');
require_once get_theme_file_path('inc/taxonomy-sss-trope.php');
require_once get_theme_file_path('inc/taxonomy-sss-shelf.php');
require_once get_theme_file_path('inc/taxonomies-extra.php');
require_once get_theme_file_path('inc/redirects.php');
require_once get_theme_file_path('inc/reader-account.php');
require_once get_theme_file_path('inc/api/books-endpoint.php');
require_once get_theme_file_path('inc/api/shelf-endpoint.php');
require_once get_theme_file_path('inc/enqueue-weekly-obsession.php');
require_once get_theme_file_path('inc/token-engine.php');
require_once get_theme_file_path('inc/shortcodes/sss-book-shortcode.php');
require_once get_theme_file_path('inc/shortcodes/sss-quickstats-shortcode.php');
require_once get_theme_file_path('inc/shortcodes/sss-library-shortcode.php');
require_once get_theme_file_path('inc/shortcodes/sss-signoff-shortcode.php');
require_once get_theme_file_path('inc/shortcodes/sss-readnext-shortcode.php');
require_once get_theme_file_path('inc/shortcodes/sss-series-shortcode.php');
require_once get_theme_file_path('inc/shortcodes/sss-pillar-shortcode.php');
require_once get_theme_file_path('inc/shortcodes/sss-newsletter-shortcode.php');
require_once get_theme_file_path('inc/admin/society-members.php');
require_once get_theme_file_path('inc/admin/society-drop-importer.php');
require_once get_theme_file_path('inc/admin/society-product-importer.php');

function bbb_reader_is_society(): bool {
	if (is_user_logged_in() && current_user_can('manage_options')) {
		return true;
	}

	return bbb_user_is_society(get_current_user_id()) || bbb_is_sss_member();
}

add_filter(
	'acf/settings/load_json',
	static function (array $paths): array {
		$paths[] = get_theme_file_path('acf-groups');

		return $paths;
	}
);

add_filter(
	'acf/settings/save_json',
	static fn(): string => get_theme_file_path('acf-groups')
);

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support('title-tag');
		add_theme_support('post-thumbnails');
		add_theme_support('custom-logo');
		add_theme_support('site-icon');

		add_image_size('obsession-360', 360, 0, false);
		add_image_size('obsession-540', 540, 0, false);
		add_image_size('obsession-720', 720, 0, false);
		add_image_size('obsession-900', 900, 0, false);
		add_image_size('obsession-1200', 1200, 0, false);

		register_nav_menus(
			array(
				'main-menu'       => __('Main Navigation', 'bybookishbabe-shopify-port'),
				'footer-policies' => __('Footer Policies', 'bybookishbabe-shopify-port'),
			)
		);
	}
);

function bbb_asset_exists(string $relative_path): bool {
	return file_exists(get_theme_file_path($relative_path));
}

function bbb_enqueue_css(string $handle, string $relative_path, array $deps = array(), ?string $media = null): void {
	if (!bbb_asset_exists($relative_path)) {
		return;
	}

	$asset_path = get_theme_file_path($relative_path);
	$version    = file_exists($asset_path) ? (string) filemtime($asset_path) : wp_get_theme()->get('Version');

	wp_enqueue_style($handle, get_theme_file_uri($relative_path), $deps, $version, $media ?: 'all');
}

function bbb_enqueue_js(string $handle, string $relative_path, array $deps = array(), bool $in_footer = true): void {
	if (!bbb_asset_exists($relative_path)) {
		return;
	}

	$asset_path = get_theme_file_path($relative_path);
	$version    = file_exists($asset_path) ? (string) filemtime($asset_path) : wp_get_theme()->get('Version');

	wp_enqueue_script($handle, get_theme_file_uri($relative_path), $deps, $version, $in_footer);
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style('bbb-font-kaushan', 'https://fonts.googleapis.com/css2?family=Kaushan+Script&display=swap', array(), null);
		wp_enqueue_style('bbb-fonts', 'https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300;0,400;0,600;0,700;1,400;1,600&family=Kaushan+Script&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap', array(), null);
		wp_enqueue_style('bbb-font-cormorant-allura', 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Allura&display=swap', array(), null);
		wp_enqueue_style('bbb-font-dancing', 'https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;600&display=swap', array(), null);
		if (is_front_page()) {
			wp_enqueue_style('bbb-font-connect-cards', 'https://fonts.googleapis.com/css2?family=Great+Vibes&family=Cormorant:wght@500;600&display=swap', array(), null);
		}

		bbb_enqueue_css('bbb-base', 'assets/base.css', array('bbb-font-kaushan', 'bbb-font-cormorant-allura', 'bbb-font-dancing'));
		if (bbb_asset_exists('assets/bbb-design-tokens.css')) {
			wp_add_inline_style('bbb-base', (string) file_get_contents(get_theme_file_path('assets/bbb-design-tokens.css')));
		}

		bbb_enqueue_css('bbb-component-list-menu', 'assets/css/component-list-menu.css', array('bbb-base'));
		bbb_enqueue_css('bbb-component-search', 'assets/component-search.css', array('bbb-component-list-menu'));
		bbb_enqueue_css('bbb-component-menu-drawer', 'assets/component-menu-drawer.css', array('bbb-component-search'));
		bbb_enqueue_css('bbb-component-cart-notification', 'assets/component-cart-notification.css', array('bbb-component-menu-drawer'));
		bbb_enqueue_css('bbb-component-price-header', 'assets/component-price.css', array('bbb-component-cart-notification'));
		bbb_enqueue_css('bbb-component-predictive-search', 'assets/component-predictive-search.css', array('bbb-component-price-header'));
		bbb_enqueue_css('bbb-component-list-social', 'assets/css/component-list-social.css', array('bbb-component-predictive-search'));
		bbb_enqueue_css('bbb-component-newsletter', 'assets/css/component-newsletter.css', array('bbb-component-list-social'));
		bbb_enqueue_css('bbb-component-list-payment', 'assets/css/component-list-payment.css', array('bbb-component-newsletter'));
		bbb_enqueue_css('bbb-section-footer', 'assets/css/section-footer.css', array('bbb-component-list-payment'));
		bbb_enqueue_css('bbb-contact-footer', 'assets/css/contact-footer.css', array('bbb-section-footer'));
		bbb_enqueue_css('bbb-header-inline', 'assets/header-inline.css', array('bbb-contact-footer'));
		bbb_enqueue_css('bbb-custom-overrides', 'assets/site-custom-overrides.css', array('bbb-header-inline'));
		bbb_enqueue_css('bbb-bookshelf-signup', 'assets/bookshelf-signup.css', array('bbb-custom-overrides'));
		if (is_singular('post')) {
			bbb_enqueue_css('section-blog-post', 'assets/css/section-blog-post.css', array('bbb-bookshelf-signup'));
			bbb_enqueue_css('sss-library', 'assets/css/sss-library.css', array('section-blog-post'));
			bbb_enqueue_css('blog-system', 'assets/css/blog-system.css', array('sss-library'));
			bbb_enqueue_css('blog-signoff', 'assets/css/blog-signoff.css', array('blog-system'));
			bbb_enqueue_css('sss-you-might-like', 'assets/css/sss-you-might-like.css', array('blog-signoff'));
		}
		if (is_home() || is_archive()) {
			bbb_enqueue_css('component-card', 'assets/css/component-card.css', array('bbb-bookshelf-signup'));
			bbb_enqueue_css('component-article-card', 'assets/css/component-article-card.css', array('component-card'));
			bbb_enqueue_css('section-main-blog', 'assets/css/section-main-blog.css', array('component-article-card'));
			bbb_enqueue_js('blog-trope-rotator', 'assets/js/blog-trope-rotator.js', array(), true);
		}
		bbb_enqueue_css('bbb-sss-library', 'assets/css/sss-library.css', array('bbb-bookshelf-signup'));
		bbb_enqueue_css('bbb-sss-folder-tabs', 'assets/css/sss-folder-tabs.css', array('bbb-sss-library'));
		bbb_enqueue_css('bbb-sss-memberdash', 'assets/css/sss-memberdash.css', array('bbb-sss-folder-tabs'));
		bbb_enqueue_css('bbb-page-spice', 'assets/css/page-spice.css', array('bbb-sss-library'));
		$bbb_request_path    = isset($_SERVER['REQUEST_URI']) ? trailingslashit((string) parse_url((string) wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH)) : '';
		$bbb_is_series_route = is_page(array('series', 'series-reading-orders')) || in_array($bbb_request_path, array('/series/', '/series-reading-orders/'), true);
		if ($bbb_is_series_route) {
			bbb_enqueue_css('bbb-series-library', 'assets/sss-library.css', array('bbb-page-spice'));
			bbb_enqueue_css('bbb-sss-series', 'assets/sss-series.css', array('bbb-series-library'));
		}
		bbb_enqueue_css('bbb-blog-system', 'assets/blog-system.css', array('bbb-sss-library'));
		bbb_enqueue_css('bbb-blog-signoff', 'assets/blog-signoff.css', array('bbb-blog-system'));
		bbb_enqueue_css('bbb-component-cart-items', 'assets/component-cart-items.css', array('bbb-blog-signoff'), 'print');
		bbb_enqueue_css('bbb-favorite-card-atc', 'assets/bbb-favorite-card-atc.css', array('bbb-component-cart-items'));
		bbb_enqueue_css('bbb-holiday-overlay', 'assets/bbb-holiday-overlay.css', array('bbb-favorite-card-atc'));
		bbb_enqueue_css('bbb-society-gate', 'assets/bbb-society-gate.css', array('bbb-holiday-overlay'));
		$bbb_society_page_routes = array(
			'about-the-society',
			'smut-sentiment-society',
			'society-newsletter-recent',
			'society-newsletter-archive',
		);
		if (function_exists('bbb_current_route_slug') && in_array(bbb_current_route_slug(), $bbb_society_page_routes, true)) {
			bbb_enqueue_css('bbb-society-landing', 'assets/css/society-landing.css', array('bbb-society-gate'));
		}
		if (is_front_page()) {
			bbb_enqueue_css('bbb-hero', 'assets/css/hero-smut-sentiment.css', array('bbb-society-gate'));
			bbb_enqueue_css('bbb-browse-by-trope', 'assets/css/sections/browse-by-trope.css', array('bbb-hero'));
			bbb_enqueue_css('bbb-home-static', 'assets/home-static.css', array('bbb-browse-by-trope'));
			bbb_enqueue_css('bbb-section-society-hero', 'assets/css/section-society-hero.css', array('bbb-home-static'));
		}

		if ('drawer' === get_option('bbb_cart_type', 'notification')) {
			bbb_enqueue_css('bbb-component-cart-drawer', 'assets/component-cart-drawer.css', array('bbb-holiday-overlay'));
			bbb_enqueue_css('bbb-component-cart', 'assets/component-cart.css', array('bbb-component-cart-drawer'));
			bbb_enqueue_css('bbb-component-totals', 'assets/component-totals.css', array('bbb-component-cart'));
			bbb_enqueue_css('bbb-component-price', 'assets/component-price.css', array('bbb-component-totals'));
			bbb_enqueue_css('bbb-component-discounts', 'assets/component-discounts.css', array('bbb-component-price'));
		}

		wp_enqueue_script('bbb-supabase', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2', array(), null, false);
		bbb_enqueue_js('bbb-globals', 'assets/bbb-globals.js', array(), false);
		bbb_enqueue_js('bbb-post-login-redirect', 'assets/bbb-post-login-redirect.js', array('bbb-globals'), false);
		bbb_enqueue_js('bbb-constants', 'assets/constants.js', array('bbb-post-login-redirect'));
		bbb_enqueue_js('bbb-pubsub', 'assets/pubsub.js', array('bbb-constants'));
		bbb_enqueue_js('bbb-global', 'assets/global.js', array('bbb-pubsub'));
		bbb_enqueue_js('bbb-details-disclosure', 'assets/details-disclosure.js', array('bbb-global'));
		bbb_enqueue_js('bbb-details-modal', 'assets/details-modal.js', array('bbb-global'));
		bbb_enqueue_js('bbb-search-form', 'assets/search-form.js', array('bbb-global'));

		if ((bool) get_option('bbb_predictive_search_enabled', true)) {
			bbb_enqueue_js('bbb-predictive-search', 'assets/predictive-search.js', array('bbb-global'));
		}

		bbb_enqueue_js('bbb-animations', 'assets/animations.js', array('bbb-global'));
		bbb_enqueue_js('bbb-sticky-header', 'assets/sticky-header.js', array('bbb-global'));
		bbb_enqueue_js('bbb-cart-notification', 'assets/cart-notification.js', array('bbb-pubsub'));
		bbb_enqueue_js('bbb-bookshelf-signup', 'assets/bookshelf-signup.js', array('bbb-supabase'));
		bbb_enqueue_js('bbb-sss-library', 'assets/js/sss-library.js', array('bbb-supabase'), false);
		if ($bbb_is_series_route) {
			bbb_enqueue_js('bbb-series-library', 'assets/sss-library.js', array('bbb-supabase'), false);
			bbb_enqueue_js('bbb-sss-series', 'assets/sss-series.js', array('bbb-series-library'));
		}
		bbb_enqueue_js('bbb-sss-memberdash', 'assets/js/sss-memberdash.js', array('bbb-global'));
		bbb_enqueue_js('bbb-sss-library-member', 'assets/js/sss-library-member.js', array('bbb-sss-library'));
		bbb_enqueue_js('bbb-page-spice', 'assets/js/page-spice.js', array('bbb-sss-library'));
		if (is_singular('post')) {
			bbb_enqueue_js('blog-system', 'assets/js/blog-system.js', array('bbb-sss-library'));
		}
		bbb_enqueue_js('bbb-blog-system', 'assets/blog-system.js', array('bbb-sss-library'));
		bbb_enqueue_js('bbb-favorite-card-atc', 'assets/bbb-favorite-card-atc.js', array('bbb-globals'));
		bbb_enqueue_js('bbb-thread-carousel', 'assets/bbb-thread-carousel.js', array('bbb-global'));
		bbb_enqueue_js('bbb-rose-petals', 'assets/bbb-rose-petals.js', array('bbb-global'));
		bbb_enqueue_js('bbb-holiday-overlay', 'assets/bbb-holiday-overlay.js', array('bbb-rose-petals'));
		if (is_front_page()) {
			bbb_enqueue_js('bbb-browse-by-trope', 'assets/js/browse-by-trope.js', array('bbb-global'));
			bbb_enqueue_js('bbb-homepage-library-preview', 'assets/js/homepage-library-preview.js', array('bbb-supabase', 'bbb-sss-library'));
			bbb_enqueue_js('bbb-home-static', 'assets/home-static.js', array('bbb-global'));
			bbb_enqueue_js('bbb-section-society-hero', 'assets/js/section-society-hero.js', array('bbb-global'));
		}

		if ((bool) get_option('bbb_localization_enabled', false)) {
			bbb_enqueue_js('bbb-localization-form', 'assets/localization-form.js', array('bbb-global'));
		}

		$user         = wp_get_current_user();
		$is_logged_in = is_user_logged_in();
		$bookshelf   = home_url('/my-bookshelf/');
		$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
		$cart_url    = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');

		wp_localize_script(
			'bbb-globals',
			'BBBSiteData',
			array(
				'shopUrl'                   => home_url(),
				'BBBReaderAccount'          => array(
					'loggedIn'     => $is_logged_in,
					'customerId'   => $is_logged_in ? (int) $user->ID : null,
					'email'        => $is_logged_in ? (string) $user->user_email : '',
					'firstName'    => $is_logged_in ? (string) $user->first_name : '',
					'isSociety'    => bbb_reader_is_society(),
					'bookshelfUrl' => $bookshelf,
					'accountUrl'   => $account_url,
					'loginUrl'     => wp_login_url($bookshelf),
				),
				'routes'                    => array(
					'cart_add_url'          => admin_url('admin-ajax.php'),
					'cart_change_url'       => admin_url('admin-ajax.php?action=bbb_cart_change'),
					'cart_update_url'       => admin_url('admin-ajax.php?action=bbb_cart_update'),
					'cart_url'              => $cart_url,
					'predictive_search_url' => home_url('/wp-json/bbb/v1/search'),
				),
				'cartStrings'               => array(
					'error'         => __('An error occurred while updating your cart.', 'bybookishbabe-shopify-port'),
					'quantityError' => __('You can only add [quantity] of this item to your cart.', 'bybookishbabe-shopify-port'),
				),
				'variantStrings'            => array(
					'addToCart'               => __('Add to cart', 'bybookishbabe-shopify-port'),
					'soldOut'                 => __('Sold out', 'bybookishbabe-shopify-port'),
					'unavailable'             => __('Unavailable', 'bybookishbabe-shopify-port'),
					'unavailable_with_option' => __('[value] is unavailable', 'bybookishbabe-shopify-port'),
				),
				'quickOrderListStrings'     => array(
					'itemsAdded'   => __('[quantity] items were added to your cart.', 'bybookishbabe-shopify-port'),
					'itemAdded'    => __('[quantity] item was added to your cart.', 'bybookishbabe-shopify-port'),
					'itemsRemoved' => __('[quantity] items were removed from your cart.', 'bybookishbabe-shopify-port'),
					'itemRemoved'  => __('[quantity] item was removed from your cart.', 'bybookishbabe-shopify-port'),
					'viewCart'     => __('View cart', 'bybookishbabe-shopify-port'),
					'each'         => __('[money] each', 'bybookishbabe-shopify-port'),
					'min_error'    => __('Minimum quantity is [min].', 'bybookishbabe-shopify-port'),
					'max_error'    => __('Maximum quantity is [max].', 'bybookishbabe-shopify-port'),
					'step_error'   => __('Quantity must be a multiple of [step].', 'bybookishbabe-shopify-port'),
				),
				'accessibilityStrings'      => array(
					'imageAvailable'             => __('Image [index] is now displayed.', 'bybookishbabe-shopify-port'),
					'shareSuccess'               => __('Link copied to clipboard.', 'bybookishbabe-shopify-port'),
					'pauseSlideshow'             => __('Pause slideshow.', 'bybookishbabe-shopify-port'),
					'playSlideshow'              => __('Play slideshow.', 'bybookishbabe-shopify-port'),
					'recipientFormExpanded'      => __('Gift card form expanded.', 'bybookishbabe-shopify-port'),
					'recipientFormCollapsed'     => __('Gift card form collapsed.', 'bybookishbabe-shopify-port'),
					'countrySelectorSearchCount' => __('[count] countries found.', 'bybookishbabe-shopify-port'),
				),
				'bbbData'                   => array(
					'nonce' => wp_create_nonce('bbb_ajax'),
				),
				'readerAccount'             => array(
					'endpoint'      => rest_url('bbb/v1/reader-account'),
					'shelfEndpoint' => rest_url('bbb/v1/reader-account/shelf'),
					'nonce'         => wp_create_nonce('wp_rest'),
				),
			)
		);

		if (wp_script_is('bbb-sss-library', 'enqueued')) {
			wp_localize_script(
				'bbb-sss-library',
				'BBBLibraryData',
				array(
					'books'       => bbb_get_all_books_json(),
					'supabaseUrl' => defined('SUPABASE_URL') ? SUPABASE_URL : 'https://efmrfxsmgbeikfgtrxjv.supabase.co',
					'supabaseKey' => defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : 'sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk',
					'currentUser' => is_user_logged_in() ? wp_get_current_user()->user_email : null,
					'isMember'    => bbb_is_sss_member(),
					'ajaxUrl'     => admin_url('admin-ajax.php'),
					'nonce'       => wp_create_nonce('bbb_shelf'),
					'homeUrl'     => home_url('/'),
				)
			);
		}
	}
);

function bbb_society_gate_check(): void {
	$gated_slugs = array(
		'society-library',
		'sss-library-page',
		'sss-private-shelf',
		'sss-made-for-you',
		'sss-printable-kindle-inserts',
		'sss-canva-templates',
		'sss-freebies',
	);

	if (!is_page($gated_slugs) || bbb_reader_is_society()) {
		return;
	}

	add_action('wp_footer', 'bbb_render_society_gate');
	add_action(
		'wp_body_open',
		static function (): void {
			echo '<script>document.body.classList.add("sss-member-gated");</script>';
		}
	);
}
add_action('template_redirect', 'bbb_society_gate_check');

function bbb_render_society_gate(): void {
	get_template_part('template-parts/society-gate');
}

add_action(
	'wp_footer',
	static function (): void {
		echo '<div id="bbb-holiday-overlay" aria-hidden="true"></div>';
	}
);
