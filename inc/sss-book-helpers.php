<?php
/**
 * Helpers for the public SSS library page.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_meta(int $post_id, string $key, $default = '') {
	$value = get_post_meta($post_id, $key, true);

	if ('' !== $value && null !== $value) {
		return $value;
	}

	if (function_exists('get_field')) {
		$field = get_field($key, $post_id);
		if ('' !== $field && null !== $field && false !== $field) {
			return $field;
		}
	}

	return $default;
}

function sss_bool($value): bool {
	if (is_bool($value)) {
		return $value;
	}

	if (is_numeric($value)) {
		return 1 === (int) $value;
	}

	return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
}

function sss_book_is_visible(int $post_id): bool {
	if (apply_filters('bbb_show_all_imported_books', false, $post_id)) {
		return 'publish' === get_post_status($post_id);
	}

	if ('bbb_book' === get_post_type($post_id) && function_exists('bbb_is_book_visible')) {
		return bbb_is_book_visible($post_id);
	}

	return !sss_bool(sss_meta($post_id, 'sss_hide_from_library', false));
}

function sss_book_is_private(int $post_id): bool {
	if ('bbb_book' === get_post_type($post_id) && function_exists('bbb_is_book_private')) {
		return bbb_is_book_private($post_id);
	}

	$shelf = strtolower(trim((string) sss_meta($post_id, 'sss_shelf', '')));
	$flag  = sss_bool(sss_meta($post_id, 'sss_is_private', false));

	return $flag || 'private shelf' === $shelf;
}

function sss_book_is_top_shelf(int $post_id): bool {
	if ('bbb_book' === get_post_type($post_id)) {
		return sss_bool(get_post_meta($post_id, '_bbb_top_shelf', true));
	}

	return sss_bool(sss_meta($post_id, 'top_shelf', sss_meta($post_id, 'sss_top_shelf', false)));
}

function sss_book_is_starter_pack(int $post_id): bool {
	if ('bbb_book' === get_post_type($post_id)) {
		return sss_bool(get_post_meta($post_id, '_bbb_starter_pack', true));
	}

	return sss_bool(sss_meta($post_id, 'sss_starter_pack', sss_meta($post_id, 'starter_pack', false)));
}

function sss_get_all_books(): array {
	$post_types = array_values(
		array_filter(
			array('sss_book', 'bbb_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);

	if (!$post_types) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'      => $post_types,
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	return array_values(
		array_filter(
			$query->posts,
			static fn(WP_Post $post): bool => sss_book_is_visible($post->ID)
		)
	);
}

function sss_get_book_cover_url(int $post_id): string {
	if (function_exists('bbb_get_book_cover_url')) {
		return bbb_get_book_cover_url($post_id);
	}

	$cover = sss_meta($post_id, 'sss_cover_url', '');

	if (is_array($cover)) {
		if (!empty($cover['url'])) {
			return (string) $cover['url'];
		}

		if (!empty($cover['ID'])) {
			return (string) wp_get_attachment_image_url((int) $cover['ID'], 'large');
		}
	}

	if (is_numeric($cover)) {
		$url = wp_get_attachment_image_url((int) $cover, 'large');
		if ($url) {
			return (string) $url;
		}
	}

	if ($cover && !(function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url((string) $cover))) {
		return (string) $cover;
	}

	$thumbnail = (string) (get_the_post_thumbnail_url($post_id, 'large') ?: '');

	return function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url($thumbnail) ? '' : $thumbnail;
}

function sss_get_book_tropes(int $post_id): array {
	$tropes_raw = sss_meta($post_id, 'sss_tropes', array());
	$tropes     = array();

	if (is_array($tropes_raw)) {
		foreach ($tropes_raw as $trope) {
			if (!is_array($trope)) {
				continue;
			}

			$name = (string) ($trope['sss_trope_name'] ?? $trope['name'] ?? '');
			if ('' === trim($name)) {
				continue;
			}

			$tropes[] = array(
				'name'   => $name,
				'emoji'  => function_exists('bbb_trope_emoji') ? bbb_trope_emoji($trope['sss_trope_emoji'] ?? $trope['emoji'] ?? '') : (string) ($trope['sss_trope_emoji'] ?? $trope['emoji'] ?? '🖤'),
				'handle' => (string) ($trope['sss_trope_handle'] ?? $trope['handle'] ?? sanitize_title($name)),
			);
		}
	}

	if (!$tropes) {
		$terms = get_the_terms($post_id, 'sss_trope');
		if ($terms && !is_wp_error($terms)) {
			foreach ($terms as $term) {
				$tropes[] = array(
					'name'   => $term->name,
					'emoji'  => function_exists('bbb_trope_emoji') ? bbb_trope_emoji(get_term_meta($term->term_id, 'emoji', true)) : (string) get_term_meta($term->term_id, 'emoji', true),
					'handle' => $term->slug,
				);
			}
		}
	}

	return $tropes;
}

function sss_get_series_name(string $series_handle): string {
	if ('' === trim($series_handle) || !post_type_exists('sss_series')) {
		return '';
	}

	$series = get_page_by_path($series_handle, OBJECT, 'sss_series');

	return $series instanceof WP_Post ? get_the_title($series) : '';
}

function sss_book_data(WP_Post $post): array {
	if ('bbb_book' === $post->post_type) {
		$series_handle = (string) get_post_meta($post->ID, '_bbb_series_handle', true);
		$series_name   = '';
		if ($series_handle) {
			$series_term = get_term_by('slug', $series_handle, 'bbb_series');
			if ($series_term instanceof WP_Term) {
				$series_name = $series_term->name;
			}
		}

		$shelf_terms = get_the_terms($post->ID, 'bbb_shelf');
		$shelf       = ($shelf_terms && !is_wp_error($shelf_terms)) ? $shelf_terms[0]->name : '';
		if ('' === $shelf) {
			$shelf = (string) get_post_meta($post->ID, '_bbb_shelf_name', true);
		}
		$trope_terms = get_the_terms($post->ID, 'bbb_trope');
		$tropes      = array();

		if ($trope_terms && !is_wp_error($trope_terms)) {
			foreach ($trope_terms as $term) {
				$tropes[] = array(
					'name'   => $term->name,
					'emoji'  => function_exists('bbb_trope_emoji') ? bbb_trope_emoji(get_term_meta($term->term_id, 'trope_emoji', true)) : (string) get_term_meta($term->term_id, 'trope_emoji', true),
					'handle' => $term->slug,
				);
			}
		}

		$ku_raw = get_post_meta($post->ID, '_bbb_ku', true);

		return array(
			'handle'         => $post->post_name,
			'url'            => get_permalink($post) ?: home_url('/books/' . $post->post_name . '/'),
			'title'          => $post->post_title,
			'author'         => (string) get_post_meta($post->ID, '_bbb_author', true),
			'cover'          => function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($post->ID) : (string) get_post_meta($post->ID, '_bbb_cover_url', true),
			'amazon'         => (string) get_post_meta($post->ID, '_bbb_amazon_url', true),
			'bookshop'       => (string) get_post_meta($post->ID, '_bbb_bookshop_url', true),
			'shelf'          => $shelf,
			'spice'          => (int) get_post_meta($post->ID, '_bbb_spice', true),
			'darkness'       => get_post_meta($post->ID, '_bbb_darkness', true),
			'tropes'         => $tropes,
			'why'            => (string) get_post_meta($post->ID, '_bbb_why', true),
			'mini'           => (string) get_post_meta($post->ID, '_bbb_mini_note', true),
			'newsletter'     => (string) get_post_meta($post->ID, '_bbb_newsletter_url', true),
			'series_handle'  => $series_handle,
			'series_name'    => $series_name,
			'series_number'  => get_post_meta($post->ID, '_bbb_series_number', true),
			'standalone'     => '1' === get_post_meta($post->ID, '_bbb_standalone', true),
			'tension'        => get_post_meta($post->ID, '_bbb_tension', true),
			'damage'         => get_post_meta($post->ID, '_bbb_damage', true),
			'yearning'       => (string) get_post_meta($post->ID, '_bbb_yearning', true),
			'boyfriend'      => (string) get_post_meta($post->ID, '_bbb_boyfriend_type', true),
			'boyfriend_name' => (string) get_post_meta($post->ID, '_bbb_boyfriend_name', true),
			'reread'         => (string) get_post_meta($post->ID, '_bbb_reread', true),
			'ku'             => '1' === $ku_raw,
			'starter_pack'   => sss_book_is_starter_pack($post->ID),
			'top_shelf'      => sss_book_is_top_shelf($post->ID),
			'is_private'     => sss_book_is_private($post->ID),
			'featured_month' => substr((string) get_post_meta($post->ID, '_bbb_newsletter_date', true), 0, 7),
		);
	}

	$series_handle = (string) sss_meta($post->ID, 'sss_series_handle', '');

	return array(
		'handle'         => $post->post_name,
		'url'            => get_permalink($post) ?: home_url('/books/' . $post->post_name . '/'),
		'title'          => $post->post_title,
		'author'         => (string) sss_meta($post->ID, 'sss_author', ''),
		'cover'          => sss_get_book_cover_url($post->ID),
		'amazon'         => (string) sss_meta($post->ID, 'sss_amazon', ''),
		'bookshop'       => (string) sss_meta($post->ID, 'sss_bookshop', ''),
		'shelf'          => (string) sss_meta($post->ID, 'sss_shelf', ''),
		'spice'          => (int) sss_meta($post->ID, 'sss_spice', 0),
		'darkness'       => sss_meta($post->ID, 'sss_darkness', ''),
		'tropes'         => sss_get_book_tropes($post->ID),
		'why'            => (string) sss_meta($post->ID, 'sss_why', ''),
		'mini'           => (string) sss_meta($post->ID, 'sss_mini', ''),
		'newsletter'     => (string) sss_meta($post->ID, 'sss_newsletter', ''),
		'series_handle'  => $series_handle,
		'series_name'    => sss_get_series_name($series_handle),
		'series_number'  => sss_meta($post->ID, 'sss_series_number', ''),
		'standalone'     => sss_bool(sss_meta($post->ID, 'sss_standalone', false)),
		'tension'        => sss_meta($post->ID, 'sss_tension', ''),
		'damage'         => sss_meta($post->ID, 'sss_damage', ''),
		'yearning'       => (string) sss_meta($post->ID, 'sss_yearning', ''),
		'boyfriend'      => (string) sss_meta($post->ID, 'sss_boyfriend_type', ''),
		'boyfriend_name' => (string) sss_meta($post->ID, 'sss_boyfriend_name', ''),
		'reread'         => sss_bool(sss_meta($post->ID, 'sss_reread', false)),
		'ku'             => sss_bool(sss_meta($post->ID, 'sss_ku', false)),
		'starter_pack'   => sss_book_is_starter_pack($post->ID),
		'top_shelf'      => sss_book_is_top_shelf($post->ID),
		'is_private'     => sss_book_is_private($post->ID),
		'featured_month' => (string) sss_meta($post->ID, 'sss_featured_month', ''),
	);
}
