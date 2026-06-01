<?php
/**
 * SEO defaults for series reading order pages.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_series_seo_clean_text(string $text): string {
	$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
	$text = wp_strip_all_tags(strip_shortcodes($text), true);
	$text = preg_replace('/\s+/', ' ', $text);

	return trim((string) $text);
}

function bbb_series_seo_trim(string $text, int $limit = 155): string {
	$text = bbb_series_seo_clean_text($text);
	if (strlen($text) <= $limit) {
		return $text;
	}

	$trimmed = substr($text, 0, $limit - 1);
	$trimmed = preg_replace('/\s+\S*$/', '', $trimmed);

	return rtrim((string) $trimmed, " \t\n\r\0\x0B,.") . '.';
}

function bbb_series_seo_current_id(): int {
	if (!is_singular('sss_series')) {
		return 0;
	}

	return (int) get_queried_object_id();
}

function bbb_series_seo_books(int $post_id): array {
	$series = get_post($post_id);
	if ($series instanceof WP_Post && function_exists('sss_series_books')) {
		return array_values(array_filter(sss_series_books($series), static fn($book): bool => $book instanceof WP_Post));
	}

	$ids   = preg_split('/[\s,]+/', (string) get_post_meta($post_id, '_bbb_series_book_ids', true)) ?: array();
	$books = array_values(
		array_filter(
			array_map(
				static function (string $id): ?WP_Post {
					$post = get_post(absint($id));

					return $post instanceof WP_Post ? $post : null;
				},
				$ids
			)
		)
	);

	return $books;
}

function bbb_series_seo_book_data(WP_Post $book): array {
	if (function_exists('bbb_books_like_book_data')) {
		return bbb_books_like_book_data($book->ID);
	}

	return array(
		'title'  => get_the_title($book),
		'author' => function_exists('bbb_get_book_author') ? bbb_get_book_author($book->ID) : get_post_meta($book->ID, '_bbb_author', true),
		'ku'     => '1' === (string) get_post_meta($book->ID, '_bbb_ku', true),
		'shelf'  => '',
		'tropes' => array(),
	);
}

function bbb_series_seo_title_value(int $post_id): string {
	return bbb_series_seo_clean_text(get_the_title($post_id));
}

function bbb_series_seo_reading_order_phrase(string $series_title): string {
	$series_title = bbb_series_seo_clean_text($series_title);
	if ('' === $series_title) {
		return '';
	}

	if (preg_match('/\b(series|duet|trilogy)\s*$/i', $series_title)) {
		return $series_title . ' reading order';
	}

	return $series_title . ' series reading order';
}

function bbb_series_seo_prefixed_name(string $series_title): string {
	$series_title = bbb_series_seo_clean_text($series_title);
	if (preg_match('/^the\b/i', $series_title)) {
		return $series_title;
	}

	return 'the ' . $series_title;
}

function bbb_series_seo_subject_name(string $series_title): string {
	$name = bbb_series_seo_prefixed_name($series_title);
	if (preg_match('/\bseries\s*$/i', $name)) {
		return $name;
	}

	return $name . ' series';
}

function bbb_series_seo_author(int $post_id, array $books = array()): string {
	$author = bbb_series_seo_clean_text((string) get_post_meta($post_id, '_bbb_series_author', true));
	if ('' !== $author) {
		return $author;
	}

	$first_book = $books[0] ?? null;
	if ($first_book instanceof WP_Post) {
		$data = bbb_series_seo_book_data($first_book);

		return bbb_series_seo_clean_text((string) ($data['author'] ?? ''));
	}

	return '';
}

function bbb_series_seo_shelf_name(array $data): string {
	$shelf = $data['shelf'] ?? '';
	if (is_array($shelf)) {
		$shelf = $shelf['name'] ?? '';
	}

	return strtolower(bbb_series_seo_clean_text((string) $shelf));
}

function bbb_series_seo_genre_phrase(string $shelf_name): string {
	$shelf_name = strtolower(trim($shelf_name));
	if ('' === $shelf_name || 'series' === $shelf_name) {
		return 'romance';
	}
	if (str_ends_with($shelf_name, 'romance')) {
		return $shelf_name;
	}

	return trim($shelf_name . ' romance');
}

function bbb_series_seo_article(string $phrase): string {
	$phrase = strtolower(trim($phrase));

	return '' !== $phrase && preg_match('/^[aeiou]/', $phrase) ? 'an' : 'a';
}

function bbb_series_seo_trope_names(array $books): array {
	$names = array();
	foreach ($books as $book) {
		if (!$book instanceof WP_Post) {
			continue;
		}

		$data = bbb_series_seo_book_data($book);
		foreach ((array) ($data['tropes'] ?? array()) as $trope) {
			$name = strtolower(bbb_series_seo_clean_text((string) ($trope['name'] ?? '')));
			if ('' !== $name) {
				$names[] = $name;
			}
		}
	}

	return array_slice(array_values(array_unique($names)), 0, 2);
}

function bbb_series_seo_trope_phrase(array $trope_names): string {
	if (count($trope_names) >= 2) {
		return $trope_names[0] . ' and ' . $trope_names[1] . ' tropes';
	}
	if (1 === count($trope_names)) {
		return $trope_names[0] . ' tropes';
	}

	return 'romance tropes';
}

function bbb_series_seo_kindle_text(array $books): string {
	if (!$books) {
		return '';
	}

	foreach ($books as $book) {
		if (!$book instanceof WP_Post) {
			continue;
		}

		$data = bbb_series_seo_book_data($book);
		if (empty($data['ku'])) {
			return 'not on kindle unlimited';
		}
	}

	return 'on kindle unlimited';
}

function bbb_series_seo_focus_keyword(int $post_id): string {
	$title = bbb_series_seo_title_value($post_id);

	return strtolower(bbb_series_seo_reading_order_phrase($title));
}

function bbb_series_seo_title(int $post_id): string {
	$title  = bbb_series_seo_title_value($post_id);
	$books  = bbb_series_seo_books($post_id);
	$author = bbb_series_seo_author($post_id, $books);

	return strtolower(trim(bbb_series_seo_reading_order_phrase($title) . ('' !== $author ? ' — ' . $author : '')));
}

function bbb_series_seo_description(int $post_id): string {
	$title      = bbb_series_seo_title_value($post_id);
	$books      = bbb_series_seo_books($post_id);
	$first_book = $books[0] ?? null;
	$first_data = $first_book instanceof WP_Post ? bbb_series_seo_book_data($first_book) : array();
	$author     = bbb_series_seo_author($post_id, $books);
	$genre      = bbb_series_seo_genre_phrase(bbb_series_seo_shelf_name($first_data));
	$tropes     = bbb_series_seo_trope_phrase(bbb_series_seo_trope_names($books));
	$ku_text    = bbb_series_seo_kindle_text($books);
	$start      = bbb_series_seo_clean_text((string) ($first_data['title'] ?? ''));
	$name       = bbb_series_seo_prefixed_name($title);
	$trope_sets = array(
		$tropes,
		bbb_series_seo_trope_phrase(array_slice(bbb_series_seo_trope_names($books), 0, 1)),
		'romance tropes',
	);
	$genre_sets = array_values(array_unique(array($genre, 'romance')));
	$ku_sets    = array_values(array_unique(array_filter(array($ku_text, 'not on kindle unlimited' === $ku_text ? 'not on ku' : $ku_text, ''))));

	foreach ($genre_sets as $genre_candidate) {
		foreach ($trope_sets as $trope_candidate) {
			foreach ($ku_sets as $ku_candidate) {
				$text = sprintf(
					'%s by %s is %s %s with %s.%s%s',
					bbb_series_seo_subject_name($title),
					$author,
					bbb_series_seo_article($genre_candidate),
					$genre_candidate,
					$trope_candidate,
					'' !== $ku_candidate ? ' ' . $ku_candidate . '.' : '',
					'' !== $start ? ' start with ' . $start . '.' : ''
				);

				if (strlen($text) <= 155) {
					return strtolower($text);
				}
			}
		}
	}

	return strtolower(
		bbb_series_seo_trim(
			sprintf(
				'%s by %s is a romance reading order.%s',
				bbb_series_seo_subject_name($title),
				$author,
				'' !== $start ? ' start with ' . $start . '.' : ''
			),
			155
		)
	);
}

function bbb_series_seo_sync_route_overrides(int $post_id): void {
	$slug = sanitize_title((string) get_post_field('post_name', $post_id));
	if ('' === $slug) {
		return;
	}

	$overrides = get_option('bbb_series_seo_overrides', array());
	if (!is_array($overrides)) {
		$overrides = array();
	}

	$overrides[$slug] = array(
		'title'       => bbb_series_seo_title($post_id),
		'description' => bbb_series_seo_description($post_id),
	);

	update_option('bbb_series_seo_overrides', $overrides, false);
}

function bbb_series_seo_sync_meta(int $post_id): void {
	if ('sss_series' !== get_post_type($post_id) || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
		return;
	}

	$title       = bbb_series_seo_title($post_id);
	$description = bbb_series_seo_description($post_id);
	$keyword     = bbb_series_seo_focus_keyword($post_id);

	$meta = array(
		'rank_math_title'                => $title,
		'rank_math_description'          => $description,
		'rank_math_focus_keyword'        => $keyword,
		'rank_math_facebook_title'       => $title,
		'rank_math_facebook_description' => $description,
		'rank_math_twitter_title'        => $title,
		'rank_math_twitter_description'  => $description,
		'_yoast_wpseo_title'             => $title,
		'_yoast_wpseo_metadesc'          => $description,
		'_yoast_wpseo_focuskw'           => $keyword,
		'_yoast_wpseo_opengraph-title'   => $title,
		'_yoast_wpseo_opengraph-description' => $description,
		'_yoast_wpseo_twitter-title'     => $title,
		'_yoast_wpseo_twitter-description' => $description,
	);

	foreach ($meta as $key => $value) {
		if ('' === $value) {
			delete_post_meta($post_id, $key);
			continue;
		}

		update_post_meta($post_id, $key, $value);
	}

	bbb_series_seo_sync_route_overrides($post_id);
}
add_action('save_post_sss_series', 'bbb_series_seo_sync_meta', 30);

function bbb_series_seo_filter_title(string $title): string {
	$post_id = bbb_series_seo_current_id();

	return $post_id ? bbb_series_seo_title($post_id) : $title;
}
add_filter('rank_math/frontend/title', 'bbb_series_seo_filter_title', 99);
add_filter('rank_math/opengraph/facebook/title', 'bbb_series_seo_filter_title', 99);
add_filter('rank_math/opengraph/twitter/title', 'bbb_series_seo_filter_title', 99);
add_filter('wpseo_title', 'bbb_series_seo_filter_title', 99);
add_filter('wpseo_opengraph_title', 'bbb_series_seo_filter_title', 99);
add_filter('wpseo_twitter_title', 'bbb_series_seo_filter_title', 99);

function bbb_series_seo_filter_description(string $description): string {
	$post_id = bbb_series_seo_current_id();

	return $post_id ? bbb_series_seo_description($post_id) : $description;
}
add_filter('rank_math/frontend/description', 'bbb_series_seo_filter_description', 99);
add_filter('rank_math/opengraph/facebook/description', 'bbb_series_seo_filter_description', 99);
add_filter('rank_math/opengraph/twitter/description', 'bbb_series_seo_filter_description', 99);
add_filter('wpseo_metadesc', 'bbb_series_seo_filter_description', 99);
add_filter('wpseo_opengraph_desc', 'bbb_series_seo_filter_description', 99);
add_filter('wpseo_twitter_description', 'bbb_series_seo_filter_description', 99);
