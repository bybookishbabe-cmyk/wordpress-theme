<?php
/**
 * Data helpers for reader quizzes.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_reader_quiz_books(): array {
	if (!function_exists('bbb_books_like_all_visible_books') || !function_exists('bbb_books_like_book_data')) {
		return array();
	}

	$books = array();
	foreach (bbb_books_like_all_visible_books() as $book_post) {
		if (!$book_post instanceof WP_Post) {
			continue;
		}
		if (function_exists('bbb_book_is_private') && bbb_book_is_private($book_post->ID)) {
			continue;
		}

		$data          = bbb_books_like_book_data((int) $book_post->ID);
		$series_number = (int) ($data['series_number'] ?? 0);
		if ($series_number > 1 && empty($data['standalone'])) {
			continue;
		}

		$tropes = array();
		foreach ((array) ($data['tropes'] ?? array()) as $trope) {
			$name = (string) ($trope['name'] ?? '');
			if ($name !== '') {
				$tropes[] = $name;
			}
		}

		$books[] = array(
			'handle'        => (string) ($data['handle'] ?? ''),
			'title'         => (string) ($data['title'] ?? ''),
			'author'        => (string) ($data['author'] ?? ''),
			'cover'         => (string) ($data['cover'] ?? ''),
			'amazon'        => (string) ($data['amazon'] ?? ''),
			'bookshop'      => (string) ($data['bookshop'] ?? ''),
			'newsletter'    => (string) ($data['newsletter'] ?? ''),
			'why'           => wp_strip_all_tags((string) ($data['why'] ?? '')),
			'mini'          => wp_strip_all_tags((string) ($data['mini'] ?? '')),
			'shelf'         => (string) ($data['shelf']['name'] ?? ''),
			'shelfSlug'     => (string) ($data['shelf']['slug'] ?? ''),
			'tropes'        => $tropes,
			'spice'         => (int) ($data['spice'] ?? 0),
			'darkness'      => (int) ($data['darkness'] ?? 0),
			'tension'       => (int) ($data['tension'] ?? 0),
			'damage'        => (int) ($data['damage'] ?? 0),
			'yearning'      => (string) ($data['yearning'] ?? ''),
			'boyfriend'     => (string) ($data['boyfriend'] ?? ''),
			'boyfriendName' => (string) ($data['boyfriend_name'] ?? ''),
			'reread'        => (string) ($data['reread'] ?? ''),
			'ku'            => !empty($data['ku']) ? 'true' : 'false',
			'series'        => (string) ($data['series_handle'] ?? ''),
			'seriesName'    => (string) ($data['series_name'] ?? ''),
			'seriesNumber'  => (string) ($data['series_number'] ?? ''),
			'standalone'    => !empty($data['standalone']) ? 'true' : 'false',
			'darknessRaw'   => (string) ($data['darkness'] ?? ''),
			'url'           => home_url('/library/?book=' . rawurlencode((string) ($data['handle'] ?? ''))),
		);
	}

	return $books;
}

function bbb_reader_quiz_enqueue_assets(): void {
	wp_enqueue_style('bbb-reader-quizzes', get_theme_file_uri('assets/css/reader-quizzes.css'), array('bbb-sss-library'), wp_get_theme()->get('Version'));
	wp_enqueue_script('bbb-reader-quiz', get_theme_file_uri('assets/js/reader-quiz.js'), array('bbb-sss-library'), wp_get_theme()->get('Version'), true);
}

