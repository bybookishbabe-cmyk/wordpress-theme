<?php
/**
 * Header helpers for the Shopify-faithful shell.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_get_inline_svg(string $name): string {
	$name = sanitize_file_name($name);
	if ('.svg' !== substr($name, -4)) {
		$name .= '.svg';
	}

	$path = get_theme_file_path('assets/' . $name);
	if (!file_exists($path)) {
		return '';
	}

	return (string) file_get_contents($path);
}

function bbb_logo_url(): string {
	$custom_logo_id = (int) get_theme_mod('custom_logo');
	if ($custom_logo_id) {
		$url = wp_get_attachment_image_url($custom_logo_id, 'full');
		if ($url) {
			return $url;
		}
	}

	return get_theme_file_uri('assets/bybookishbabe_4c4e36b4-20d6-4135-8721-c1d4c22273fb.png');
}

function bbb_logo_height(): int {
	$custom_logo_id = (int) get_theme_mod('custom_logo');
	if ($custom_logo_id) {
		$meta = wp_get_attachment_metadata($custom_logo_id);
		if (!empty($meta['width']) && !empty($meta['height'])) {
			return max(1, (int) round(200 * ((float) $meta['height'] / (float) $meta['width'])));
		}
	}

	return 200;
}

function bbb_user_is_society(int $user_id = 0): bool {
	$user_id = $user_id ?: get_current_user_id();
	if (!$user_id) {
		return false;
	}

	$user = get_user_by('id', $user_id);
	if (!$user instanceof WP_User) {
		return false;
	}

	return in_array('society', (array) $user->roles, true)
		|| in_array('paid', (array) $user->roles, true)
		|| '1' === get_user_meta($user_id, 'bbb_society_member', true);
}

function bbb_user_has_avatar(int $user_id = 0): bool {
	$user_id = $user_id ?: get_current_user_id();
	if (!$user_id) {
		return false;
	}

	$avatar = get_avatar_data($user_id, array('size' => 44));
	return !empty($avatar['found_avatar']);
}

function bbb_wc_account_url(): string {
	return function_exists('wc_get_account_endpoint_url')
		? wc_get_account_endpoint_url('dashboard')
		: home_url('/my-account/');
}

function bbb_wc_cart_url(): string {
	return function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
}

function bbb_cart_count(): int {
	if (function_exists('WC') && WC() && WC()->cart) {
		return (int) WC()->cart->get_cart_contents_count();
	}

	return 0;
}

function bbb_cart_is_empty(): bool {
	return 0 === bbb_cart_count();
}

function bbb_get_header_menu_items(): array {
	$locations = get_nav_menu_locations();
	$menu_id   = $locations['main-menu'] ?? 0;
	$items     = $menu_id ? wp_get_nav_menu_items($menu_id) : array();

	if (!$items) {
		return array();
	}

	$lookup = array();
	$tree   = array();

	foreach ($items as $item) {
		$item->children = array();
		$lookup[$item->ID] = $item;
	}

	foreach ($items as $item) {
		$parent_id = (int) $item->menu_item_parent;
		if ($parent_id && isset($lookup[$parent_id])) {
			$lookup[$parent_id]->children[] = $item;
			continue;
		}
		$tree[] = $item;
	}

	return $tree;
}

function bbb_menu_item_handle(WP_Post $item): string {
	return sanitize_title($item->post_name ?: $item->title);
}

function bbb_menu_item_is_current(WP_Post $item): bool {
	$classes = (array) $item->classes;
	return in_array('current-menu-item', $classes, true);
}

function bbb_menu_item_child_active(WP_Post $item): bool {
	$classes = (array) $item->classes;
	return in_array('current-menu-ancestor', $classes, true)
		|| in_array('current-menu-parent', $classes, true);
}

function bbb_predictive_search_markup(string $query): string {
	$posts = get_posts(
		array(
			's'              => $query,
			'post_type'      => array('post', 'page', 'product', 'bbb_book'),
			'post_status'    => 'publish',
			'posts_per_page' => 6,
		)
	);

	$count = count($posts);
	$html  = '<div id="shopify-section-predictive-search">';
	$html .= '<div id="predictive-search-results" role="listbox">';
	$html .= '<div id="predictive-search-results-groups-wrapper" class="predictive-search__results-groups-wrapper">';
	$html .= '<div class="predictive-search__result-group">';
	$html .= '<h2 id="predictive-search-pages" class="predictive-search__heading caption-with-letter-spacing">Results</h2>';
	$html .= '<ul id="predictive-search-results-list" class="predictive-search__results-list list-unstyled" role="group" aria-labelledby="predictive-search-pages">';

	foreach ($posts as $index => $result) {
		$html .= sprintf(
			'<li id="predictive-search-option-%1$d" class="predictive-search__list-item" role="option" aria-selected="false"><a href="%2$s" class="predictive-search__item predictive-search__item--link link link--text" tabindex="-1"><div class="predictive-search__item-content predictive-search__item-content--centered"><p class="predictive-search__item-heading h5">%3$s</p></div></a></li>',
			$index + 1,
			esc_url(get_permalink($result)),
			esc_html(get_the_title($result))
		);
	}

	$search_url = add_query_arg('s', rawurlencode($query), home_url('/'));
	$html .= sprintf(
		'<li id="predictive-search-option-search-keywords" class="predictive-search__list-item" role="option"><button class="predictive-search__item predictive-search__item--term link link--text h5 animate-arrow" tabindex="-1"><span data-predictive-search-search-for-text>Search for “%1$s”</span></button></li>',
		esc_html($query)
	);
	$html .= '</ul></div></div>';
	$html .= sprintf(
		'<div class="predictive-search__loading-state" aria-hidden="true">%s</div>',
		bbb_get_inline_svg('loading-spinner')
	);
	$html .= sprintf(
		'<span class="predictive-search-status visually-hidden" data-predictive-search-live-region-count-value>%d result%s</span>',
		$count,
		1 === $count ? '' : 's'
	);
	$html .= sprintf(
		'<a class="predictive-search__search-for-button button button--primary" href="%s">Search</a>',
		esc_url($search_url)
	);
	$html .= '</div></div>';

	return $html;
}

add_action(
	'parse_request',
	static function (): void {
		$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
		if (!is_string($path) || !preg_match('#/wp-json/bbb/v1/search/?$#', $path)) {
			return;
		}

		$query = sanitize_text_field((string) ($_GET['q'] ?? ''));
		header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
		echo bbb_predictive_search_markup($query);
		exit;
	},
	1
);
