<?php
/**
 * Per-book quote archive helpers.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_book_quotes_endpoint(): string {
	return 'quotes';
}

add_action(
	'init',
	static function (): void {
		add_rewrite_endpoint(bbb_book_quotes_endpoint(), EP_PERMALINK);
	}
);

add_filter(
	'query_vars',
	static function (array $vars): array {
		$vars[] = bbb_book_quotes_endpoint();

		return $vars;
	}
);

add_filter(
	'template_include',
	static function (string $template): string {
		if (!is_singular('bbb_book') || null === get_query_var(bbb_book_quotes_endpoint(), null)) {
			return $template;
		}

		$quote_template = get_theme_file_path('single-bbb_book-quotes.php');

		return file_exists($quote_template) ? $quote_template : $template;
	}
);

function bbb_book_quotes_url(int $book_id): string {
	$permalink = get_permalink($book_id);

	return $permalink ? trailingslashit($permalink) . bbb_book_quotes_endpoint() . '/' : home_url('/sss-quote-wall/');
}

function bbb_book_quotes_current_book_id(): int {
	if (!is_singular('bbb_book')) {
		return 0;
	}

	return null === get_query_var(bbb_book_quotes_endpoint(), null) ? 0 : (int) get_queried_object_id();
}

function bbb_book_quotes_is_context(): bool {
	return bbb_book_quotes_current_book_id() > 0;
}

function bbb_book_quotes_cache_version(): string {
	$version = get_option('bbb_book_quotes_cache_version', '');

	if (!is_string($version) || '' === $version) {
		$version = sprintf('%.6F', microtime(true));
		update_option('bbb_book_quotes_cache_version', $version, false);
	}

	return $version;
}

function bbb_book_quotes_flush_cache(): void {
	update_option('bbb_book_quotes_cache_version', sprintf('%.6F', microtime(true)), false);
}

function bbb_book_quote_series_handle(WP_Post $book): string {
	$handle = '';
	if (function_exists('sss_book_data')) {
		$data   = sss_book_data($book);
		$handle = (string) ($data['series_handle'] ?? '');
	}

	if ('' === trim($handle)) {
		$handle = (string) get_post_meta($book->ID, '_bbb_series_handle', true);
	}
	if ('' === trim($handle)) {
		$handle = (string) get_post_meta($book->ID, 'sss_series_handle', true);
	}

	return sanitize_title($handle);
}

function bbb_book_quote_series_name(WP_Post $book): string {
	$name = '';
	if (function_exists('sss_book_data')) {
		$data = sss_book_data($book);
		$name = trim((string) ($data['series_name'] ?? ''));
	}

	$handle = bbb_book_quote_series_handle($book);
	if ('' === $name && '' !== $handle && taxonomy_exists('bbb_series')) {
		$term = get_term_by('slug', $handle, 'bbb_series');
		if ($term instanceof WP_Term) {
			$name = $term->name;
		}
	}
	if ('' === $name && '' !== $handle && function_exists('sss_get_series_name')) {
		$name = sss_get_series_name($handle);
	}

	return trim(wp_strip_all_tags($name));
}

function bbb_book_quote_series_sort_value(WP_Post $book): int {
	if (function_exists('sss_article_field')) {
		$value = sss_article_field('series_number', $book->ID, '');
		if ('' !== (string) $value) {
			return (int) $value;
		}
	}

	foreach (array('_bbb_series_number', 'sss_series_number') as $key) {
		$value = get_post_meta($book->ID, $key, true);
		if ('' !== (string) $value) {
			return (int) $value;
		}
	}

	return 999;
}

function bbb_book_quote_scope_books(WP_Post $book): array {
	$handle = bbb_book_quote_series_handle($book);
	if ('' === $handle) {
		return array($book);
	}

	$books  = array();
	$series = post_type_exists('sss_series') ? get_page_by_path($handle, OBJECT, 'sss_series') : null;
	if (!$series instanceof WP_Post && post_type_exists('sss_series')) {
		$matches = get_posts(
			array(
				'post_type'      => 'sss_series',
				'post_status'    => array('publish', 'draft', 'pending', 'private'),
				'posts_per_page' => 1,
				'meta_key'       => '_bbb_series_handle',
				'meta_value'     => $handle,
				'no_found_rows'  => true,
			)
		);
		$series = $matches[0] ?? null;
	}

	if ($series instanceof WP_Post && function_exists('sss_series_books')) {
		$books = array_values(array_filter(sss_series_books($series), static fn($candidate): bool => $candidate instanceof WP_Post));
	}

	if (!$books) {
		$post_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
		if ($post_types) {
			$books = get_posts(
				array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'no_found_rows'  => true,
					'meta_query'     => array(
						'relation' => 'OR',
						array('key' => '_bbb_series_handle', 'value' => $handle),
						array('key' => 'sss_series_handle', 'value' => $handle),
					),
				)
			);
		}
	}

	$books[$book->ID] = $book;
	$books = array_values(
		array_filter(
			$books,
			static fn($candidate): bool => $candidate instanceof WP_Post && in_array(get_post_type($candidate), array('bbb_book', 'sss_book'), true)
		)
	);

	$unique = array();
	foreach ($books as $candidate) {
		$unique[(int) $candidate->ID] = $candidate;
	}
	$books = array_values($unique);

	usort(
		$books,
		static function (WP_Post $a, WP_Post $b): int {
			$number_compare = bbb_book_quote_series_sort_value($a) <=> bbb_book_quote_series_sort_value($b);
			return 0 !== $number_compare ? $number_compare : strcasecmp(get_the_title($a), get_the_title($b));
		}
	);

	return count($books) > 1 ? $books : array($book);
}

function bbb_book_quote_scope_label(WP_Post $book): string {
	$scope_books = bbb_book_quote_scope_books($book);
	if (count($scope_books) < 2) {
		return bbb_book_quotes_title((int) $book->ID);
	}

	$series_name = bbb_book_quote_series_name($book);
	if ('' !== $series_name) {
		return function_exists('bbb_book_series_label') ? bbb_book_series_label($series_name) : $series_name;
	}

	return bbb_book_quotes_title((int) $book->ID);
}

function bbb_book_quote_source_book(WP_Post $quote, WP_Post $context_book): WP_Post {
	foreach (bbb_book_quote_scope_books($context_book) as $candidate) {
		if ($candidate instanceof WP_Post && function_exists('bbb_bookquote_quote_book_matches') && bbb_bookquote_quote_book_matches($quote, $candidate)) {
			return $candidate;
		}
	}

	if (function_exists('bbb_quote_wall_book')) {
		$linked_book = bbb_quote_wall_book($quote);
		if ($linked_book instanceof WP_Post) {
			return $linked_book;
		}
	}

	return $context_book;
}

function bbb_book_quotes_title(int $book_id): string {
	$title = get_the_title($book_id);
	$title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title((string) $title) : (string) $title;

	return trim($title);
}

function bbb_book_quotes_author(int $book_id): string {
	$author = function_exists('bbb_get_book_author')
		? bbb_get_book_author($book_id)
		: get_post_meta($book_id, '_bbb_author', true);
	$author = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name((string) $author) : (string) $author;

	return trim(wp_strip_all_tags($author));
}

function bbb_book_quotes_hook(int $book_id): string {
	foreach (array('_bbb_vibe_description', '_bbb_verdict', '_bbb_read_this_if', '_bbb_why') as $meta_key) {
		$value = trim(wp_strip_all_tags((string) get_post_meta($book_id, $meta_key, true)));
		if ('' === $value) {
			continue;
		}

		if (str_contains(strtolower($value), 'mafia romance slump')) {
			return 'pulled me out of a mafia romance slump.';
		}

		if (function_exists('bbb_book_seo_lead_sentence')) {
			return bbb_book_seo_lead_sentence($value, 58);
		}

		return trim((string) preg_replace('/\s+/', ' ', $value));
	}

	return 'hit with obsessive, high-drama romance energy.';
}

function bbb_book_quotes_seo_title(int $book_id): string {
	$book   = get_post($book_id);
	$title  = $book instanceof WP_Post ? bbb_book_quote_scope_label($book) : bbb_book_quotes_title($book_id);
	$author = bbb_book_quotes_author($book_id);

	return '' !== $author
		? sprintf('%s Quotes — %s | bybookishbabe', $title, $author)
		: sprintf('%s Quotes | bybookishbabe', $title);
}

function bbb_book_quotes_seo_description(int $book_id): string {
	$book   = get_post($book_id);
	$quotes = $book instanceof WP_Post && function_exists('bbb_book_quote_posts') ? bbb_book_quote_posts($book) : array();
	$count  = count($quotes);
	$title  = $book instanceof WP_Post ? bbb_book_quote_scope_label($book) : bbb_book_quotes_title($book_id);
	$author = bbb_book_quotes_author($book_id);
	$hook   = bbb_book_quotes_hook($book_id);
	$number = 1 === $count ? '1 quote' : $count . ' quotes';

	$description = '' !== $author
		? sprintf('The best quotes from %s by %s. %s from the dark romance that %s Save your favorites.', $title, $author, $number, $hook)
		: sprintf('The best quotes from %s. %s from the dark romance that %s Save your favorites.', $title, $number, $hook);

	return function_exists('bbb_book_seo_trim') ? bbb_book_seo_trim($description, 155) : trim($description);
}

function bbb_book_quotes_filter_title(string $title): string {
	$book_id = bbb_book_quotes_current_book_id();

	return $book_id ? bbb_book_quotes_seo_title($book_id) : $title;
}
add_filter('pre_get_document_title', 'bbb_book_quotes_filter_title', 101);
add_filter('rank_math/frontend/title', 'bbb_book_quotes_filter_title', 101);
add_filter('rank_math/opengraph/facebook/title', 'bbb_book_quotes_filter_title', 101);
add_filter('rank_math/opengraph/twitter/title', 'bbb_book_quotes_filter_title', 101);
add_filter('wpseo_title', 'bbb_book_quotes_filter_title', 101);
add_filter('wpseo_opengraph_title', 'bbb_book_quotes_filter_title', 101);
add_filter('wpseo_twitter_title', 'bbb_book_quotes_filter_title', 101);

function bbb_book_quotes_filter_description(string $description): string {
	$book_id = bbb_book_quotes_current_book_id();

	return $book_id ? bbb_book_quotes_seo_description($book_id) : $description;
}
add_filter('rank_math/frontend/description', 'bbb_book_quotes_filter_description', 101);
add_filter('rank_math/opengraph/facebook/description', 'bbb_book_quotes_filter_description', 101);
add_filter('rank_math/opengraph/twitter/description', 'bbb_book_quotes_filter_description', 101);
add_filter('wpseo_metadesc', 'bbb_book_quotes_filter_description', 101);
add_filter('wpseo_opengraph_desc', 'bbb_book_quotes_filter_description', 101);
add_filter('wpseo_twitter_description', 'bbb_book_quotes_filter_description', 101);

function bbb_book_quotes_filter_canonical(string $canonical): string {
	$book_id = bbb_book_quotes_current_book_id();

	return $book_id ? bbb_book_quotes_url($book_id) : $canonical;
}
add_filter('rank_math/frontend/canonical', 'bbb_book_quotes_filter_canonical', 101);
add_filter('wpseo_canonical', 'bbb_book_quotes_filter_canonical', 101);
add_filter('get_canonical_url', 'bbb_book_quotes_filter_canonical', 101);

add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		if (!bbb_book_quotes_is_context()) {
			return $robots;
		}

		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = 'index';
		$robots['follow'] = 'follow';

		return $robots;
	},
	101
);

add_filter(
	'wp_robots',
	static function (array $robots): array {
		if (!bbb_book_quotes_is_context()) {
			return $robots;
		}

		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = true;
		$robots['follow'] = true;

		return $robots;
	},
	101
);

add_filter(
	'redirect_canonical',
	static function ($redirect_url, string $requested_url) {
		if (!is_singular('bbb_book')) {
			return $redirect_url;
		}

		$book_id = (int) get_queried_object_id();
		if ($book_id <= 0) {
			return $redirect_url;
		}

		if (null !== get_query_var(bbb_book_quotes_endpoint(), null)) {
			return bbb_book_quotes_url($book_id);
		}

		$has_query_quotes = isset($_GET[bbb_book_quotes_endpoint()]);
		if ($has_query_quotes) {
			return bbb_book_quotes_url($book_id);
		}

		return $redirect_url;
	},
	101,
	2
);

add_action(
	'template_redirect',
	static function (): void {
		if (!is_singular('bbb_book') || !isset($_GET[bbb_book_quotes_endpoint()])) {
			return;
		}

		$book_id = (int) get_queried_object_id();
		if ($book_id <= 0) {
			return;
		}

		wp_safe_redirect(bbb_book_quotes_url($book_id), 301);
		exit;
	},
	1
);

function bbb_book_quotes_schema(): void {
	$book_id = bbb_book_quotes_current_book_id();
	$book    = $book_id ? get_post($book_id) : null;
	if (!$book instanceof WP_Post || !function_exists('bbb_book_quote_posts')) {
		return;
	}

	$title     = bbb_book_quotes_title($book_id);
	$author    = bbb_book_quotes_author($book_id);
	$canonical = bbb_book_quotes_url($book_id);
	$quotes    = bbb_book_quote_posts($book);
	$title     = bbb_book_quote_scope_label($book);
	$items     = array();

	foreach ($quotes as $quote) {
		if (!$quote instanceof WP_Post) {
			continue;
		}

		$text = function_exists('bbb_bookquote_quote_text') ? bbb_bookquote_quote_text($quote) : trim(wp_strip_all_tags((string) $quote->post_content));
		if ('' === $text) {
			continue;
		}

		$source_book   = bbb_book_quote_source_book($quote, $book);
		$source_id     = (int) $source_book->ID;
		$source_title  = bbb_book_quotes_title($source_id);
		$source_author = bbb_book_quotes_author($source_id);
		$source_url    = get_permalink($source_book) ?: $canonical;

		$items[] = array(
			'@type'        => 'Quotation',
			'text'         => $text,
			'isPartOf'     => array(
				'@type'  => 'Book',
				'name'   => $source_title,
				'author' => '' !== $source_author ? array('@type' => 'Person', 'name' => $source_author) : null,
				'url'    => $source_url,
			),
			'url'          => $canonical . '#quote-' . (string) $quote->ID,
			'datePublished' => get_the_date(DATE_W3C, $quote),
		);
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'CollectionPage',
		'name'        => sprintf('%s Quotes', $title),
		'description' => bbb_book_quotes_seo_description($book_id),
		'url'         => $canonical,
		'isPartOf'    => array('@type' => 'WebSite', 'name' => 'bybookishbabe', 'url' => home_url('/')),
		'mainEntity'  => $items,
	);

	echo '<script type="application/ld+json" class="bbb-book-quotes-schema">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('wp_head', 'bbb_book_quotes_schema', 30);

function bbb_book_quotes_sitemap_url(): string {
	return home_url('/book-quote-sitemap.xml');
}

function bbb_book_quotes_sitemap_entries(): array {
	$entries = array();
	$books   = get_posts(
		array(
			'post_type'      => 'bbb_book',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ($books as $book) {
		if (!$book instanceof WP_Post) {
			continue;
		}
		if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible((int) $book->ID)) {
			continue;
		}

		$quotes = bbb_book_quote_posts($book, 1);
		if (!$quotes) {
			continue;
		}

		$entries[] = array(
			'loc'     => bbb_book_quotes_url((int) $book->ID),
			'lastmod' => get_post_modified_time(DATE_W3C, true, $book),
		);
	}

	return $entries;
}

add_action(
	'template_redirect',
	static function (): void {
		$path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
		if ('book-quote-sitemap.xml' !== $path) {
			return;
		}

		$entries = bbb_book_quotes_sitemap_entries();

		status_header(200);
		header('Content-Type: application/xml; charset=' . get_bloginfo('charset'), true);
		echo '<?xml version="1.0" encoding="' . esc_attr(get_bloginfo('charset') ?: 'UTF-8') . "\"?>\n";
		echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
		foreach ($entries as $entry) {
			echo "\t<url>\n";
			echo "\t\t<loc>" . esc_xml((string) $entry['loc']) . "</loc>\n";
			if (!empty($entry['lastmod'])) {
				echo "\t\t<lastmod>" . esc_xml((string) $entry['lastmod']) . "</lastmod>\n";
			}
			echo "\t</url>\n";
		}
		echo "</urlset>\n";
		exit;
	},
	0
);

add_filter(
	'robots_txt',
	static function (string $output): string {
		$line = 'Sitemap: ' . bbb_book_quotes_sitemap_url();
		if (str_contains($output, $line)) {
			return $output;
		}

		return rtrim($output) . "\n" . $line . "\n";
	},
	100
);

function bbb_book_quote_posts(WP_Post $book, int $limit = 0): array {
	if (!function_exists('bbb_quote_post_types') || !function_exists('bbb_bookquote_quote_book_matches')) {
		return array();
	}

	$quote_types = bbb_quote_post_types();
	if (!$quote_types) {
		return array();
	}

	$scope_books      = array($book);
	$scope_book_ids   = array_values(array_unique(array_map(static fn(WP_Post $scope_book): int => (int) $scope_book->ID, $scope_books)));
	$scope_handles    = array_values(
		array_unique(
			array_filter(
				array_map(
					static fn(WP_Post $scope_book): string => sanitize_title((string) get_post_field('post_name', $scope_book->ID)),
					$scope_books
				)
			)
		)
	);
	$posts_per_page   = $limit > 0 ? $limit : -1;
	$quote_cache_seed = (function_exists('sss_library_cache_version') ? sss_library_cache_version() : wp_get_theme()->get('Version')) . '|' . bbb_book_quotes_cache_version() . '|book-scope-v1|' . implode(',', $quote_types) . '|' . implode(',', $scope_book_ids) . '|' . $limit;
	$quote_cache_key  = 'bbb_book_quotes_' . (int) $book->ID . '_' . md5($quote_cache_seed);
	$quote_ids        = get_transient($quote_cache_key);

	if (!is_array($quote_ids)) {
		$quote_ids     = array();
		$meta_query    = array('relation' => 'OR');
		foreach ($scope_book_ids as $book_id) {
			$meta_query[] = array('key' => '_quote_book_id', 'value' => (string) $book_id);
			$meta_query[] = array('key' => '_quote_library_book_id', 'value' => (string) $book_id);
			$meta_query[] = array('key' => 'book_id', 'value' => (string) $book_id);
			$meta_query[] = array('key' => 'library_book_id', 'value' => (string) $book_id);
		}
		foreach ($scope_handles as $book_handle) {
			$meta_query[] = array('key' => '_quote_book_handle', 'value' => $book_handle);
			$meta_query[] = array('key' => 'book_handle', 'value' => $book_handle);
			$meta_query[] = array('key' => '_bbb_book_handle', 'value' => $book_handle);
		}

		$direct_quotes = get_posts(
			array(
				'post_type'      => $quote_types,
				'post_status'    => 'publish',
				'posts_per_page' => $posts_per_page,
				'orderby'        => 'menu_order date',
				'order'          => 'ASC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => $meta_query,
			)
		);

		$quote_ids = array_values(array_unique(array_filter(array_map('absint', $direct_quotes))));
		$quote_ids = array_values(
			array_filter(
				$quote_ids,
				static function (int $quote_id) use ($scope_books): bool {
					$quote = get_post($quote_id);
					if (!$quote instanceof WP_Post) {
						return false;
					}

					foreach ($scope_books as $scope_book) {
						if ($scope_book instanceof WP_Post && bbb_bookquote_quote_book_matches($quote, $scope_book)) {
							return true;
						}
					}

					return false;
				}
			)
		);

		if ($limit < 1 || count($quote_ids) < $limit) {
			$maybe_quote_ids = get_posts(
				array(
					'post_type'      => $quote_types,
					'post_status'    => 'publish',
					'posts_per_page' => 120,
					'orderby'        => 'menu_order date',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);

			_prime_post_caches($maybe_quote_ids, true, true);

			foreach ($maybe_quote_ids as $maybe_quote_id) {
				if ($limit > 0 && count($quote_ids) >= $limit) {
					break;
				}

				$quote = get_post((int) $maybe_quote_id);
				if (!$quote instanceof WP_Post || in_array($quote->ID, $quote_ids, true)) {
					continue;
				}

				foreach ($scope_books as $scope_book) {
					if ($scope_book instanceof WP_Post && bbb_bookquote_quote_book_matches($quote, $scope_book)) {
						$quote_ids[] = $quote->ID;
						break;
					}
				}
			}
		}

		if ($limit > 0) {
			$quote_ids = array_slice($quote_ids, 0, $limit);
		}

		set_transient($quote_cache_key, $quote_ids, 12 * HOUR_IN_SECONDS);
	}

	$quote_ids = array_values(array_filter(array_map('absint', $quote_ids)));
	if (!$quote_ids) {
		return array();
	}

	_prime_post_caches($quote_ids, true, true);

	return array_values(
		array_filter(
			array_map('get_post', $quote_ids),
			static fn($quote): bool => $quote instanceof WP_Post
		)
	);
}
