<?php
/**
 * Weekly Obsession query helpers.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_normalize_newsletter_date(string $date): string {
	$date = substr(trim($date), 0, 10);
	if (preg_match('/^\d{8}$/', $date)) {
		$date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
	}

	return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
}

function sss_newsletter_date_timestamp(string $date): int {
	$date = sss_normalize_newsletter_date($date);
	if ('' === $date) {
		return 0;
	}

	$timestamp = strtotime($date);

	return false === $timestamp ? 0 : $timestamp;
}

function sss_get_issue_publish_date(WP_Post $issue): string {
	$raw = function_exists('get_field') ? (string) get_field('publish_date', $issue->ID) : '';
	if ('' === trim($raw)) {
		$raw = (string) get_post_meta($issue->ID, 'publish_date', true);
	}
	if ('' === trim($raw)) {
		$raw = (string) get_post_meta($issue->ID, '_issue_publish_date', true);
	}

	return sss_normalize_newsletter_date($raw);
}

function sss_get_book_newsletter_date(WP_Post $book): string {
	$raw = 'bbb_book' === $book->post_type
		? (string) get_post_meta($book->ID, '_bbb_newsletter_date', true)
		: (string) get_post_meta($book->ID, 'featured_in_newsletter_date', true);

	if ('' === trim($raw) && function_exists('get_field')) {
		$raw = (string) get_field('featured_in_newsletter_date', $book->ID);
	}

	return sss_normalize_newsletter_date($raw);
}

function sss_get_book_newsletter_url(WP_Post $book): string {
	$url = 'bbb_book' === $book->post_type
		? (string) get_post_meta($book->ID, '_bbb_newsletter_url', true)
		: (string) get_post_meta($book->ID, 'newsletter_url', true);

	if ('' === trim($url) && function_exists('get_field')) {
		$url = (string) get_field('newsletter_url', $book->ID);
	}

	return function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($url) : trim($url);
}

function sss_newsletter_title_from_url(string $url): string {
	$path = (string) parse_url($url, PHP_URL_PATH);
	if ('' === $path) {
		return '';
	}

	$slug = basename(untrailingslashit($path));
	$slug = preg_replace('/^p\//', '', $slug);
	$title = str_replace(array('-', '_'), ' ', sanitize_title($slug));

	return trim($title);
}

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
		$issue_ts = sss_newsletter_date_timestamp(sss_get_issue_publish_date($issue));
		if (0 === $issue_ts) {
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

function sss_get_current_obsession_context(): array {
	$current_issue = sss_get_current_newsletter_issue();
	$issue_book    = $current_issue instanceof WP_Post ? sss_get_obsession_book($current_issue) : null;
	$latest_book   = sss_get_latest_featured_book();

	$issue_date = $current_issue instanceof WP_Post ? sss_get_issue_publish_date($current_issue) : '';
	$issue_ts   = sss_newsletter_date_timestamp($issue_date);
	$book_date  = $latest_book instanceof WP_Post ? sss_get_book_newsletter_date($latest_book) : '';
	$book_ts    = sss_newsletter_date_timestamp($book_date);

	$use_book_fallback = $latest_book instanceof WP_Post && $book_ts > $issue_ts;
	$book              = $use_book_fallback ? $latest_book : $issue_book;

	if (!$book instanceof WP_Post && $latest_book instanceof WP_Post) {
		$book              = $latest_book;
		$use_book_fallback = true;
	}

	$date     = $use_book_fallback ? $book_date : $issue_date;
	$url      = '';
	$title    = '';
	$subtitle = '';

	if (!$use_book_fallback && $current_issue instanceof WP_Post) {
		$url = (string) get_post_meta($current_issue->ID, '_bbb_newsletter_url', true);
		$url = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($url) : trim($url);
		$title = (string) get_post_meta($current_issue->ID, '_issue_title_override', true);
		if ('' === trim($title)) {
			$title = get_the_title($current_issue);
		}
		$subtitle = (string) get_post_meta($current_issue->ID, '_issue_subtitle', true);
	}

	if ($book instanceof WP_Post) {
		$book_url = sss_get_book_newsletter_url($book);
		if ('' === $url) {
			$url = $book_url;
		}
		if ('' === trim($title)) {
			$title = '' !== $book_url ? sss_newsletter_title_from_url($book_url) : '';
		}
		if ('' === trim($title)) {
			$title = get_the_title($book);
		}
		if ('' === trim($subtitle)) {
			$subtitle = 'bbb_book' === $book->post_type
				? (string) get_post_meta($book->ID, '_bbb_mini_note', true)
				: (string) get_post_meta($book->ID, 'mini_note', true);
		}
	}

	return array(
		'issue'            => $use_book_fallback ? null : $current_issue,
		'book'             => $book,
		'date'             => $date,
		'timestamp'        => sss_newsletter_date_timestamp($date),
		'title'            => $title,
		'subtitle'         => $subtitle,
		'url'              => $url,
		'is_book_fallback' => $use_book_fallback,
	);
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
	$issue_url = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value($issue_url) : trim($issue_url);
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
