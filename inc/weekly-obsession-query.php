<?php
/**
 * Weekly Obsession query helpers.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

/**
 * Returns the WP_Post for the current newsletter issue, or null.
 * Mirrors the Liquid: live_ts = publish_date + 36000 (10 hrs), latest wins.
 */
function sss_get_current_newsletter_issue(): ?WP_Post {
	$now = time();
	$post_types = array_values(
		array_filter(
			array('newsletter_issue', 'bbb_newsletter_issue'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);

	if (!$post_types) {
		return null;
	}

	$issues = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'publish_date',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_issue_publish_date',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	$current    = null;
	$current_ts = 0;

	foreach ($issues as $issue) {
		$raw = function_exists('get_field') ? get_field('publish_date', $issue->ID) : get_post_meta($issue->ID, 'publish_date', true);
		if (empty($raw)) {
			$raw = get_post_meta($issue->ID, '_issue_publish_date', true);
		}
		if (empty($raw)) {
			continue;
		}

		$date = trim((string) $raw);
		if (preg_match('/^\d{8}$/', $date)) {
			$date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
		}

		$issue_ts = strtotime($date);
		if (false === $issue_ts) {
			continue;
		}

		$live_ts = $issue_ts + 36000; // +10 hours, exact match to Liquid.

		if ($live_ts <= $now && $issue_ts >= $current_ts) {
			$current    = $issue;
			$current_ts = $issue_ts;
		}
	}

	return $current;
}

/**
 * Returns the WP_Post for the featured book linked to an issue.
 * Tries _issue_book_id first, falls back to _issue_library_book_id.
 */
function sss_get_obsession_book(WP_Post $issue): ?WP_Post {
	foreach (array('_issue_book_id', '_issue_library_book_id') as $key) {
		$book_id = (int) get_post_meta($issue->ID, $key, true);
		if ($book_id > 0) {
			$book = get_post($book_id);
			if ($book && in_array($book->post_type, array('sss_book', 'bbb_book'), true)) {
				return $book;
			}
		}
	}

	$book_handle = (string) get_post_meta($issue->ID, '_issue_book_handle', true);
	if ('' !== $book_handle) {
		$book = get_page_by_path($book_handle, OBJECT, array('sss_book', 'bbb_book'));
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	$issue_url = (string) get_post_meta($issue->ID, '_bbb_newsletter_url', true);
	if ('' !== $issue_url) {
		$books = get_posts(
			array(
				'post_type'      => array_values(
					array_filter(
						array('bbb_book', 'sss_book'),
						static fn(string $post_type): bool => post_type_exists($post_type)
					)
				) ?: 'bbb_book',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'   => '_bbb_newsletter_url',
						'value' => $issue_url,
					),
					array(
						'key'   => 'newsletter_url',
						'value' => $issue_url,
					),
				),
			)
		);

		if (!empty($books[0]) && $books[0] instanceof WP_Post) {
			return $books[0];
		}
	}

	$publish_date = (string) get_post_meta($issue->ID, '_issue_publish_date', true);
	if ('' === $publish_date) {
		$publish_date = (string) get_post_meta($issue->ID, 'publish_date', true);
	}

	$publish_date = substr(trim($publish_date), 0, 10);
	if (preg_match('/^\d{8}$/', $publish_date)) {
		$publish_date = substr($publish_date, 0, 4) . '-' . substr($publish_date, 4, 2) . '-' . substr($publish_date, 6, 2);
	}

	if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $publish_date)) {
		$books = get_posts(
			array(
				'post_type'      => array_values(
					array_filter(
						array('bbb_book', 'sss_book'),
						static fn(string $post_type): bool => post_type_exists($post_type)
					)
				) ?: 'bbb_book',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'   => '_bbb_newsletter_date',
						'value' => $publish_date,
					),
					array(
						'key'   => 'featured_in_newsletter_date',
						'value' => $publish_date,
					),
				),
			)
		);

		if (!empty($books[0]) && $books[0] instanceof WP_Post) {
			return $books[0];
		}
	}

	return null;
}

function sss_get_latest_featured_book(): ?WP_Post {
	$post_types = array_values(
		array_filter(
			array('sss_book', 'bbb_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);

	if (!$post_types) {
		return null;
	}

	$today = wp_date('Y-m-d');
	$query = new WP_Query(
		array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'featured_in_newsletter_date',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_bbb_newsletter_date',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	$latest    = null;
	$latest_ts = 0;

	foreach ($query->posts as $book) {
		if (function_exists('sss_book_is_visible') && !sss_book_is_visible($book->ID)) {
			continue;
		}

		$date = 'bbb_book' === $book->post_type
			? (string) get_post_meta($book->ID, '_bbb_newsletter_date', true)
			: (string) get_post_meta($book->ID, 'featured_in_newsletter_date', true);

		if ('' === $date && function_exists('get_field')) {
			$date = (string) get_field('featured_in_newsletter_date', $book->ID);
		}

		$date = substr(trim($date), 0, 10);
		if (preg_match('/^\d{8}$/', $date)) {
			$date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
		}

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > $today) {
			continue;
		}

		$ts = strtotime($date);
		if (false !== $ts && $ts >= $latest_ts) {
			$latest    = $book;
			$latest_ts = $ts;
		}
	}

	wp_reset_postdata();

	if ($latest instanceof WP_Post) {
		return $latest;
	}

	$fallback = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	return $fallback[0] ?? null;
}

function sss_get_trope_colors(string $slug): array {
	$map = array(
		'enemies-to-lovers'        => array('#f2a7ad', '#6e1422'),
		'friends-to-lovers'        => array('#bfe3cb', '#144a31'),
		'slow-burn'                => array('#f2c179', '#6a3700'),
		'billionaire-romance'      => array('#bfdca0', '#365316'),
		'billionaire'              => array('#bfdca0', '#365316'),
		'second-chance'            => array('#cfbef5', '#4b2280'),
		'forced-proximity'         => array('#a9cdf6', '#163f72'),
		'grumpy-sunshine'          => array('#f2d35f', '#5f4700'),
		'workplace-romance'        => array('#bfd0ef', '#274469'),
		'fake-dating'              => array('#efb6d3', '#6e2147'),
		'marriage-of-convenience'  => array('#dbc2a7', '#6c4221'),
		'sports-romance'           => array('#9fd8e5', '#0f5064'),
		'small-town'               => array('#c7d89b', '#405719'),
		'brothers-best-friend'     => array('#ebb99c', '#71351a'),
		'dark-romance'             => array('#b8a0d8', '#2f1646'),
		'stalker-romance'          => array('#b8a0d8', '#2f1646'),
		'stalker'                  => array('#b8a0d8', '#2f1646'),
		'morally-gray-hero'        => array('#b9c1cb', '#26303b'),
		'morally-gray-men'         => array('#b9c1cb', '#26303b'),
		'morally-gray'             => array('#b9c1cb', '#26303b'),
		'touch-her-and-die'        => array('#e596a8', '#641223'),
		'one-bed'                  => array('#d8b9ea', '#55276f'),
		'fated-mates'              => array('#e7acd1', '#74204f'),
		'age-gap'                  => array('#c4d4ec', '#31486e'),
		'single-dad'               => array('#b7dbc9', '#1f543b'),
		'reverse-harem'            => array('#d7a8d7', '#651c58'),
	);

	return $map[$slug] ?? array('#f3bfd5', '#4b112d');
}

function sss_get_homepage_field(string $field_name, string $default): string {
	$value = function_exists('get_field') ? get_field($field_name) : '';
	$value = is_string($value) ? trim($value) : '';

	return '' !== $value ? $value : $default;
}
