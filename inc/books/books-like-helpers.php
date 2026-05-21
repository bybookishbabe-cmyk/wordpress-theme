<?php
/**
 * Helpers for the Shopify "books like x" pages.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_books_like_post_types(): array {
	return array_values(
		array_filter(
			array('sss_book', 'bbb_book'),
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);
}

function bbb_books_like_book_data(int $book_id): array {
	if (function_exists('sss_article_book_data')) {
		$data = sss_article_book_data($book_id);
	} else {
		$data = array(
			'id'            => $book_id,
			'handle'        => get_post_field('post_name', $book_id),
			'title'         => get_the_title($book_id),
			'author'        => function_exists('bbb_get_book_author') ? bbb_get_book_author($book_id) : '',
			'cover'         => function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book_id) : '',
			'amazon'        => function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(get_post_meta($book_id, '_bbb_amazon_url', true)) : (string) get_post_meta($book_id, '_bbb_amazon_url', true),
			'bookshop'      => function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(get_post_meta($book_id, '_bbb_bookshop_url', true)) : (string) get_post_meta($book_id, '_bbb_bookshop_url', true),
			'newsletter'    => function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(get_post_meta($book_id, '_bbb_newsletter_url', true)) : (string) get_post_meta($book_id, '_bbb_newsletter_url', true),
			'spice'         => (int) get_post_meta($book_id, '_bbb_spice', true),
			'darkness'      => (int) get_post_meta($book_id, '_bbb_darkness', true),
			'ku'            => '1' === get_post_meta($book_id, '_bbb_ku', true),
			'standalone'    => '1' === get_post_meta($book_id, '_bbb_standalone', true),
			'mini'          => (string) get_post_meta($book_id, '_bbb_mini_note', true),
			'why'           => (string) get_post_meta($book_id, '_bbb_why', true),
			'tropes'        => array(),
			'shelf'         => array('name' => '', 'slug' => ''),
			'series_handle' => (string) get_post_meta($book_id, '_bbb_series_handle', true),
			'series_name'   => '',
			'series_number' => (string) get_post_meta($book_id, '_bbb_series_number', true),
		);
	}

	$data['tension'] = (int) (function_exists('sss_article_field') ? sss_article_field('tension_score', $book_id, get_post_meta($book_id, '_bbb_tension', true)) : get_post_meta($book_id, '_bbb_tension', true));
	$data['damage'] = (int) (function_exists('sss_article_field') ? sss_article_field('emotional_damage_score', $book_id, get_post_meta($book_id, '_bbb_damage', true)) : get_post_meta($book_id, '_bbb_damage', true));
	$data['yearning'] = (string) (function_exists('sss_article_field') ? sss_article_field('yearning_level', $book_id, get_post_meta($book_id, '_bbb_yearning', true)) : get_post_meta($book_id, '_bbb_yearning', true));
	$data['boyfriend'] = (string) (function_exists('sss_article_field') ? sss_article_field('boyfriend_type', $book_id, get_post_meta($book_id, '_bbb_boyfriend_type', true)) : get_post_meta($book_id, '_bbb_boyfriend_type', true));
	$data['boyfriend_name'] = (string) (function_exists('sss_article_field') ? sss_article_field('boyfriend_name', $book_id, get_post_meta($book_id, '_bbb_boyfriend_name', true)) : get_post_meta($book_id, '_bbb_boyfriend_name', true));
	$data['reread'] = (string) (function_exists('sss_article_field') ? sss_article_field('reread_badge', $book_id, get_post_meta($book_id, '_bbb_reread', true)) : get_post_meta($book_id, '_bbb_reread', true));
	$data['private'] = function_exists('bbb_book_is_private') ? bbb_book_is_private($book_id) : false;

	if (!isset($data['shelf']) || !is_array($data['shelf'])) {
		$data['shelf'] = array('name' => '', 'slug' => '');
	}
	if (!isset($data['tropes']) || !is_array($data['tropes'])) {
		$data['tropes'] = array();
	}

	return $data;
}

function bbb_books_like_data_attrs(array $book): string {
	$trope_names   = array();
	$trope_display = array();
	$trope_urls    = array();

	foreach ($book['tropes'] as $trope) {
		$name = (string) ($trope['name'] ?? '');
		if ($name === '') {
			continue;
		}
		$slug            = (string) ($trope['slug'] ?? sanitize_title($name));
		$emoji           = (string) ($trope['emoji'] ?? '');
		$trope_names[]   = $name;
		$trope_display[] = trim(($emoji ? $emoji . ' ' : '') . $name);
		$trope_urls[]    = home_url('/' . trim($slug, '/') . '-books/');
	}

	$ku = '';
	if (array_key_exists('ku', $book)) {
		$ku = $book['ku'] ? 'true' : 'false';
	}

	$attrs = array(
		'data-handle'         => $book['handle'] ?? '',
		'data-title'          => $book['title'] ?? '',
		'data-author'         => $book['author'] ?? '',
		'data-cover'          => $book['cover'] ?? '',
		'data-amazon'         => $book['amazon'] ?? '',
		'data-bookshop'       => $book['bookshop'] ?? '',
		'data-shelf'          => $book['shelf']['name'] ?? '',
		'data-private-shelf'  => !empty($book['private']) ? 'true' : 'false',
		'data-spice'          => (string) ($book['spice'] ?? ''),
		'data-tropes'         => implode(', ', $trope_names),
		'data-tropes-display' => implode(', ', $trope_display),
		'data-trope-urls'     => implode(', ', $trope_urls),
		'data-why'            => $book['why'] ?? '',
		'data-newsletter'     => $book['newsletter'] ?? '',
		'data-mini'           => $book['mini'] ?? '',
		'data-series'         => $book['series_handle'] ?? '',
		'data-series-name'    => $book['series_name'] ?? '',
		'data-series-number'  => $book['series_number'] ?? '',
		'data-tension'        => (string) ($book['tension'] ?? ''),
		'data-damage'         => (string) ($book['damage'] ?? ''),
		'data-yearning'       => $book['yearning'] ?? '',
		'data-boyfriend'      => $book['boyfriend'] ?? '',
		'data-boyfriend-name' => $book['boyfriend_name'] ?? '',
		'data-reread'         => $book['reread'] ?? '',
		'data-standalone'     => !empty($book['standalone']) ? 'true' : 'false',
		'data-ku'             => $ku,
		'data-darkness'       => (string) ($book['darkness'] ?? ''),
	);

	$out = array();
	foreach ($attrs as $key => $value) {
		$out[] = $key . '="' . esc_attr((string) $value) . '"';
	}

	return implode(' ', $out);
}

function bbb_books_like_find_book($needle): ?WP_Post {
	if ($needle instanceof WP_Post && in_array($needle->post_type, bbb_books_like_post_types(), true)) {
		return $needle;
	}
	if (is_array($needle)) {
		if (isset($needle['ID'])) {
			return bbb_books_like_find_book((int) $needle['ID']);
		}
		if (isset($needle[0])) {
			return bbb_books_like_find_book($needle[0]);
		}
	}
	if (is_numeric($needle)) {
		$post = get_post((int) $needle);
		return $post instanceof WP_Post && in_array($post->post_type, bbb_books_like_post_types(), true) ? $post : null;
	}

	$value = trim((string) $needle);
	if ($value === '') {
		return null;
	}

	$slug = sanitize_title($value);
	foreach (bbb_books_like_post_types() as $post_type) {
		$post = get_page_by_path($slug, OBJECT, $post_type);
		if ($post instanceof WP_Post) {
			return $post;
		}
	}

	$matches = get_posts(
		array(
			'post_type'      => bbb_books_like_post_types(),
			'post_status'    => 'publish',
			's'              => $value,
			'posts_per_page' => 10,
		)
	);
	foreach ($matches as $match) {
		if (strtolower($match->post_title) === strtolower($value)) {
			return $match;
		}
	}

	return $matches[0] ?? null;
}

function bbb_books_like_infer_source_title(string $title): string {
	$lower = strtolower($title);
	$pos   = strpos($lower, 'books like ');
	if (false === $pos) {
		return '';
	}

	$name = substr($title, $pos + strlen('books like '));
	$name = preg_replace('/\s+(?:and|&)\s+what\s+to\s+read\s+next.*$/i', '', $name) ?: $name;
	$name = preg_replace('/\s*[\(\[\|:–—-].*$/u', '', $name) ?: $name;

	return trim($name);
}

function bbb_books_like_source_title_candidates(string $title): array {
	$inferred = bbb_books_like_infer_source_title($title);
	if ('' === $inferred) {
		return array();
	}

	$candidates = array($inferred);
	foreach (array(' but ', ' for ', ' if ', ' when ', ' with ') as $marker) {
		$position = stripos($inferred, $marker);
		if (false !== $position) {
			$candidates[] = trim(substr($inferred, 0, $position));
		}
	}

	return array_values(array_unique(array_filter($candidates)));
}

function bbb_books_like_source_for_guide(WP_Post $post): ?WP_Post {
	if (function_exists('sss_article_post')) {
		foreach (array('source_book', 'book', 'books') as $key) {
			$field = function_exists('sss_article_field') ? sss_article_field($key, $post->ID, null) : null;
			$book  = sss_article_post($field);
			if ($book instanceof WP_Post) {
				return $book;
			}
		}
	}

	foreach (array('_bbb_source_book', 'source_book', '_source_book', 'custom.source_book', 'custom_source_book', '_custom_source_book', '_shopify_metafield_custom_source_book', 'source_book_handle', '_source_book_handle', 'book', '_book', 'books', '_books') as $meta_key) {
		$book = bbb_books_like_find_book(get_post_meta($post->ID, $meta_key, true));
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	foreach (bbb_books_like_source_title_candidates($post->post_title) as $candidate) {
		$book = bbb_books_like_find_book($candidate);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	return null;
}

function bbb_books_like_guide_posts(): array {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_wp_page_template',
					'value'   => array('page-books-like.php', 'templates/page.books-like.json', 'page.books-like'),
					'compare' => 'IN',
				),
				array(
					'key'     => '_shopify_template_suffix',
					'value'   => array('books-like', 'page.books-like'),
					'compare' => 'IN',
				),
			),
		)
	);

	$guides = array();
	foreach ($pages as $page) {
		if (in_array($page->post_name, array('books-like', 'books-like-directory'), true)) {
			continue;
		}

		$source = bbb_books_like_source_for_guide($page);
		$guides[] = array(
			'post'   => $page,
			'source' => $source,
		);
	}

	return $guides;
}

function bbb_books_like_blog_guide_posts(): array {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$guides = array();
	foreach ($posts as $post) {
		$title = strtolower((string) $post->post_title);
		$slug  = strtolower((string) $post->post_name);
		if (!str_starts_with($title, 'books like ') && !str_starts_with($slug, 'books-like-')) {
			continue;
		}

		$guides[] = array(
			'post'   => $post,
			'source' => bbb_books_like_source_for_guide($post),
		);
	}

	return $guides;
}

function bbb_books_like_grouped_guides(): array {
	$groups = array();
	foreach (bbb_books_like_guide_posts() as $guide) {
		$source = $guide['source'];
		$key    = 'more-books-like';
		$name   = 'more books like this';
		if ($source instanceof WP_Post) {
			$data = bbb_books_like_book_data($source->ID);
			$key  = (string) ($data['shelf']['slug'] ?? '');
			$name = (string) ($data['shelf']['name'] ?? '');
		}
		if ($key === '') {
			$key = 'more-books-like';
		}
		if ($name === '') {
			$name = 'more books like this';
		}
		if (!isset($groups[$key])) {
			$groups[$key] = array(
				'name'  => $name,
				'items' => array(),
			);
		}
		$groups[$key]['items'][] = $guide;
	}

	uasort(
		$groups,
		static fn(array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name'])
	);

	return $groups;
}

function bbb_books_like_grouped_blog_guides(): array {
	$groups = array();
	foreach (bbb_books_like_blog_guide_posts() as $guide) {
		$source = $guide['source'];
		$key    = 'more-books-like';
		$name   = 'more books like';
		if ($source instanceof WP_Post) {
			$data = bbb_books_like_book_data($source->ID);
			$key  = (string) ($data['shelf']['slug'] ?? '');
			$name = (string) ($data['shelf']['name'] ?? '');
		}
		if ('' === $key) {
			$key = 'more-books-like';
		}
		if ('' === $name) {
			$name = 'more books like';
		}
		if (!isset($groups[$key])) {
			$groups[$key] = array(
				'name'  => $name,
				'items' => array(),
			);
		}
		$groups[$key]['items'][] = $guide;
	}

	uasort(
		$groups,
		static fn(array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name'])
	);

	return $groups;
}

function bbb_books_like_all_visible_books(): array {
	$books = get_posts(
		array(
			'post_type'      => bbb_books_like_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	return array_values(
		array_filter(
			$books,
			static function (WP_Post $book): bool {
				if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($book->ID)) {
					return false;
				}
				if (function_exists('bbb_book_is_hidden') && bbb_book_is_hidden($book->ID)) {
					return false;
				}
				return true;
			}
		)
	);
}

function bbb_books_like_shared_tropes(array $source, array $candidate): array {
	$source_tropes = array();
	foreach ($source['tropes'] as $trope) {
		$source_tropes[sanitize_title((string) ($trope['slug'] ?? $trope['name'] ?? ''))] = (string) ($trope['name'] ?? '');
	}

	$shared = array();
	foreach ($candidate['tropes'] as $trope) {
		$slug = sanitize_title((string) ($trope['slug'] ?? $trope['name'] ?? ''));
		if ($slug && isset($source_tropes[$slug])) {
			$shared[] = (string) ($trope['name'] ?? $source_tropes[$slug]);
		}
	}

	return array_values(array_filter(array_unique($shared)));
}

function bbb_books_like_score(array $source, array $candidate): float {
	$score = 0.0;

	if (($source['shelf']['slug'] ?? '') && ($source['shelf']['slug'] ?? '') === ($candidate['shelf']['slug'] ?? '')) {
		$score += 36;
	}
	if (($source['boyfriend'] ?? '') && strtolower((string) $source['boyfriend']) === strtolower((string) ($candidate['boyfriend'] ?? ''))) {
		$score += 24;
	}

	$shared = bbb_books_like_shared_tropes($source, $candidate);
	$score += min(42, count($shared) * 14);

	foreach (array('spice' => 9, 'darkness' => 8, 'tension' => 7, 'damage' => 7) as $key => $weight) {
		$source_value = (int) ($source[$key] ?? 0);
		$candidate_value = (int) ($candidate[$key] ?? 0);
		if ($source_value > 0 && $candidate_value > 0) {
			$score += max(0, $weight - (abs($source_value - $candidate_value) * 3));
		}
	}

	if (($source['yearning'] ?? '') && strtolower((string) $source['yearning']) === strtolower((string) ($candidate['yearning'] ?? ''))) {
		$score += 8;
	}
	if (!empty($source['ku']) && !empty($candidate['ku'])) {
		$score += 3;
	}

	return $score;
}

function bbb_books_like_recommendations(int $source_id): array {
	$source = bbb_books_like_book_data($source_id);
	$items  = array();

	foreach (bbb_books_like_all_visible_books() as $book) {
		if ((int) $book->ID === (int) $source_id) {
			continue;
		}
		$data = bbb_books_like_book_data($book->ID);
		$series_number = (int) ($data['series_number'] ?? 0);
		if ($series_number > 1 && empty($data['standalone'])) {
			continue;
		}

		$data['score'] = bbb_books_like_score($source, $data);
		$data['shared_tropes'] = bbb_books_like_shared_tropes($source, $data);
		$items[] = $data;
	}

	usort(
		$items,
		static function (array $a, array $b): int {
			if ((float) $a['score'] === (float) $b['score']) {
				return strcasecmp((string) $a['title'], (string) $b['title']);
			}
			return (float) $a['score'] < (float) $b['score'] ? 1 : -1;
		}
	);

	return $items;
}

function bbb_books_like_current_source_book(): ?WP_Post {
	$query_keys = array('book', 'source', 'handle');
	foreach ($query_keys as $key) {
		$value = isset($_GET[$key]) ? sanitize_text_field((string) wp_unslash($_GET[$key])) : '';
		$book  = bbb_books_like_find_book($value);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	$page_id = get_queried_object_id();
	if ($page_id) {
		foreach (array('source_book', '_source_book', 'custom.source_book', 'custom_source_book', '_custom_source_book', '_shopify_metafield_custom_source_book', 'source_book_handle', '_source_book_handle', 'book', '_book') as $meta_key) {
			$book = bbb_books_like_find_book(get_post_meta($page_id, $meta_key, true));
			if ($book instanceof WP_Post) {
				return $book;
			}
		}

		$page = get_post($page_id);
		if ($page instanceof WP_Post) {
			$candidates = bbb_books_like_source_title_candidates($page->post_title);
			if (!$candidates && str_starts_with((string) $page->post_name, 'books-like-')) {
				$candidates[] = str_replace('-', ' ', substr((string) $page->post_name, strlen('books-like-')));
			}

			foreach ($candidates as $candidate) {
				$book = bbb_books_like_find_book($candidate);
				if ($book instanceof WP_Post) {
					return $book;
				}
			}
		}
	}

	return null;
}
