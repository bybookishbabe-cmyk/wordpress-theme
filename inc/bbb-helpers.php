<?php
/**
 * Shared helpers for the Shopify to WordPress conversion.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_get_field(string $key, $post_id = null, $default = null) {
	$post_id = null === $post_id ? get_the_ID() : (int) $post_id;
	$is_url_field = str_contains($key, 'url') || str_contains($key, 'link');

	if ('bbb_book' === get_post_type($post_id)) {
		$bbb_map = array(
			'author'                      => '_bbb_author',
			'cover'                       => '_bbb_cover_url',
			'amazon_link'                 => '_bbb_amazon_url',
			'bookshop_link'               => '_bbb_bookshop_url',
			'spice_level'                 => '_bbb_spice',
			'book_spice_level'            => '_bbb_spice',
			'starter_pack'                => '_bbb_starter_pack',
			'top_shelf'                   => '_bbb_top_shelf',
			'hide_from_library'           => '_bbb_hide_from_library',
			'is_private'                  => '_bbb_private_shelf',
			'read_as_standalone'          => '_bbb_standalone',
			'standalone'                  => '_bbb_standalone',
			'on_kindle_unlimited'         => '_bbb_ku',
			'why_i_loved_it'              => '_bbb_why',
			'mini_note'                   => '_bbb_mini_note',
			'featured_in_newsletter_date' => '_bbb_newsletter_date',
		);

		if (isset($bbb_map[$key])) {
			$value = get_post_meta($post_id, $bbb_map[$key], true);
			if ('' !== $value && null !== $value) {
				return $is_url_field && function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($value) : $value;
			}
		}
	}

	if (function_exists('get_field')) {
		$value = get_field($key, $post_id);
		if (null !== $value && '' !== $value && false !== $value) {
			return $is_url_field && function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($value) : $value;
		}
	}

	$raw = get_post_meta($post_id, $key, true);
	if ('' !== $raw) {
		return $is_url_field && function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($raw) : $raw;
	}

	$legacy = get_post_meta($post_id, '_' . $key, true);
	if ('' !== $legacy) {
		return $is_url_field && function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($legacy) : $legacy;
	}

	return $default;
}

function bbb_normalize_url_value($value): string {
	if (is_array($value)) {
		foreach (array('url', 'href', 'link') as $key) {
			if (!empty($value[$key]) && is_scalar($value[$key])) {
				return trim((string) $value[$key]);
			}
		}

		return '';
	}

	if (!is_scalar($value)) {
		return '';
	}

	$url = trim((string) $value);
	if ('' === $url) {
		return '';
	}

	$decoded = json_decode($url, true);
	if (is_array($decoded)) {
		return bbb_normalize_url_value($decoded);
	}

	if (
		(strlen($url) >= 2)
		&& (('"' === $url[0] && '"' === substr($url, -1)) || ("'" === $url[0] && "'" === substr($url, -1)))
	) {
		$url = substr($url, 1, -1);
	}

	return trim($url);
}

function bbb_is_sss_member(): bool {
	if (!is_user_logged_in()) {
		return false;
	}

	$user = wp_get_current_user();

	return in_array('sss_member', (array) $user->roles, true)
		|| in_array('society', (array) $user->roles, true)
		|| in_array('paid', (array) $user->roles, true)
		|| in_array('society_member', (array) $user->roles, true)
		|| (function_exists('bbb_user_is_society') && bbb_user_is_society(get_current_user_id()))
		|| '1' === get_user_meta(get_current_user_id(), 'bbb_society_member', true)
		|| '1' === get_user_meta(get_current_user_id(), '_bbb_society_member_active', true)
		|| (function_exists('wc_memberships_is_user_active_member')
			&& wc_memberships_is_user_active_member(get_current_user_id(), 'smut-sentiment-society'));
}

function bbb_resolve_page_url(string $slug): string {
	$page = get_page_by_path($slug);

	return $page ? get_permalink($page) : home_url('/' . trim($slug, '/') . '/');
}

function bbb_require_sss_member(): void {
	if (bbb_is_sss_member()) {
		return;
	}

	wp_safe_redirect(bbb_resolve_page_url('join'));
	exit;
}

function bbb_get_book_cover_url(int $post_id): string {
	if ('bbb_book' === get_post_type($post_id)) {
		$cover = (string) get_post_meta($post_id, '_bbb_cover_url', true);
		if ('' !== $cover) {
			return $cover;
		}
	}

	$field = bbb_get_field('cover', $post_id, '');
	if (is_array($field)) {
		return (string) ($field['url'] ?? '');
	}

	if ($field) {
		return (string) $field;
	}

	return get_the_post_thumbnail_url($post_id, 'large') ?: '';
}

function bbb_get_book_author(int $post_id): string {
	if ('bbb_book' === get_post_type($post_id)) {
		return (string) get_post_meta($post_id, '_bbb_author', true);
	}

	return (string) bbb_get_field('author', $post_id, '');
}

function bbb_book_is_hidden(int $post_id): bool {
	if ('bbb_book' === get_post_type($post_id)) {
		return bbb_truthy(get_post_meta($post_id, '_bbb_hide_from_library', true));
	}

	return (bool) bbb_get_field('hide_from_library', $post_id, false);
}

function bbb_book_is_private(int $post_id): bool {
	if ('bbb_book' === get_post_type($post_id)) {
		return function_exists('bbb_is_book_private') ? bbb_is_book_private($post_id) : bbb_truthy(get_post_meta($post_id, '_bbb_private_shelf', true));
	}

	return (bool) bbb_get_field('is_private', $post_id, false);
}

function bbb_truthy($value): bool {
	if (is_bool($value)) {
		return $value;
	}

	if (is_numeric($value)) {
		return 1 === (int) $value;
	}

	$normalized = strtolower(trim((string) $value));

	return in_array($normalized, array('1', 'true', 'yes', 'on'), true);
}

function bbb_book_newsletter_is_unlocked(int $post_id): bool {
	$featured_date = 'bbb_book' === get_post_type($post_id)
		? (string) get_post_meta($post_id, '_bbb_newsletter_date', true)
		: (string) bbb_get_field('featured_in_newsletter_date', $post_id, '');
	if ('' === trim($featured_date)) {
		return true;
	}

	$date = substr(trim($featured_date), 0, 10);
	if (preg_match('/^\d{8}$/', $date)) {
		$date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
	}

	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
		return true;
	}

	try {
		$timezone = new DateTimeZone('America/Los_Angeles');
		$unlock   = new DateTimeImmutable($date . ' 10:00:00', $timezone);
		$now      = new DateTimeImmutable('now', $timezone);

		return $unlock <= $now;
	} catch (Exception $exception) {
		return true;
	}
}

function bbb_book_is_publicly_visible(int $post_id): bool {
	if (apply_filters('bbb_show_all_imported_books', true, $post_id)) {
		return 'publish' === get_post_status($post_id);
	}

	return !bbb_truthy(bbb_get_field('hide_from_library', $post_id, false))
		&& !bbb_truthy(bbb_get_field('is_private', $post_id, false))
		&& bbb_book_newsletter_is_unlocked($post_id);
}

function bbb_get_all_books_json(bool $include_private = true): array {
	$post_types = array_values(
		array_filter(
			array('sss_book', 'bbb_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);

	$books = get_posts(
		array(
			'post_type'        => $post_types ?: 'sss_book',
			'numberposts'      => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'suppress_filters' => false,
		)
	);

	$out = array();
	foreach ($books as $book) {
		if (bbb_book_is_hidden($book->ID)) {
			continue;
		}

		$is_private = bbb_book_is_private($book->ID);
		if (!$include_private && $is_private) {
			continue;
		}

		$is_bbb      = 'bbb_book' === $book->post_type;
		$shelf_terms = get_the_terms($book->ID, $is_bbb ? 'bbb_shelf' : 'sss_shelf');
		$trope_terms = get_the_terms($book->ID, $is_bbb ? 'bbb_trope' : 'sss_trope');

		$out[] = array(
			'id'           => $book->ID,
			'title'        => $book->post_title,
			'slug'         => $book->post_name,
			'author'       => bbb_get_book_author($book->ID),
			'cover_url'    => bbb_get_book_cover_url($book->ID),
			'shelf'        => $shelf_terms && !is_wp_error($shelf_terms) ? $shelf_terms[0]->slug : '',
			'shelf_name'   => $shelf_terms && !is_wp_error($shelf_terms) ? $shelf_terms[0]->name : '',
			'tropes'       => $trope_terms && !is_wp_error($trope_terms) ? wp_list_pluck($trope_terms, 'name') : array(),
			'spice_level'  => (int) bbb_get_field('spice_level', $book->ID, bbb_get_field('book_spice_level', $book->ID, 0)),
			'is_private'   => $is_private,
			'starter_pack' => function_exists('sss_book_is_starter_pack') ? sss_book_is_starter_pack($book->ID) : (bool) bbb_get_field('starter_pack', $book->ID, false),
			'top_shelf'    => function_exists('sss_book_is_top_shelf') ? sss_book_is_top_shelf($book->ID) : (bool) bbb_get_field('top_shelf', $book->ID, false),
			'on_ku'        => bbb_truthy(bbb_get_field('on_kindle_unlimited', $book->ID, false)),
			'why'          => (string) bbb_get_field('why_i_loved_it', $book->ID, ''),
			'mini'         => (string) bbb_get_field('mini_note', $book->ID, ''),
		);
	}

	return $out;
}

function bbb_get_public_books_query(array $args = array()): WP_Query {
	$post_types = array_values(
		array_filter(
			array('sss_book', 'bbb_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);

	$defaults = array(
		'post_type'      => $post_types ?: 'sss_book',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	return new WP_Query(array_replace_recursive($defaults, $args));
}

function bbb_render_section(string $name, array $args = array()): void {
	$file = get_theme_file_path('inc/sections/' . $name . '.php');
	if (file_exists($file)) {
		require $file;
	}
}

function bbb_render_component(string $name, array $args = array()): void {
	$file = get_theme_file_path('inc/components/' . $name . '.php');
	if (file_exists($file)) {
		require $file;
	}
}
