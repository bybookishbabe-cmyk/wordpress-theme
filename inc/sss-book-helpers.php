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
	return !sss_bool(sss_meta($post_id, 'sss_hide_from_library', false));
}

function sss_book_is_private(int $post_id): bool {
	$shelf = strtolower(trim((string) sss_meta($post_id, 'sss_shelf', '')));
	$flag  = sss_bool(sss_meta($post_id, 'sss_is_private', false));

	return $flag || 'private shelf' === $shelf;
}

function sss_get_all_books(): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'sss_book',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'relation' => 'OR',
					array(
						'key'     => 'sss_hide_from_library',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'sss_hide_from_library',
						'value'   => '1',
						'compare' => '!=',
					),
				),
			),
		)
	);

	return $query->posts;
}

function sss_get_book_cover_url(int $post_id): string {
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

	if ($cover) {
		return (string) $cover;
	}

	return get_the_post_thumbnail_url($post_id, 'large') ?: '';
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
				'emoji'  => (string) ($trope['sss_trope_emoji'] ?? $trope['emoji'] ?? ''),
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
					'emoji'  => (string) get_term_meta($term->term_id, 'emoji', true),
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
	$series_handle = (string) sss_meta($post->ID, 'sss_series_handle', '');

	return array(
		'handle'         => $post->post_name,
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
		'starter_pack'   => sss_bool(sss_meta($post->ID, 'sss_starter_pack', false)),
		'is_private'     => sss_book_is_private($post->ID),
		'featured_month' => (string) sss_meta($post->ID, 'sss_featured_month', ''),
	);
}
