<?php
declare(strict_types=1);

const BBB_HOMEPAGE_SEO_TITLE = 'romance book recommendations by trope & spice level | bybookishbabe';
const BBB_HOMEPAGE_SEO_DESCRIPTION = 'find your next romance read organized by trope, spice level, and mood. book reviews, curated guides, and weekly recs for soft hearts with sinful taste.';

function bbb_is_homepage_seo_context(): bool {
	return ! is_admin() && ( is_front_page() || is_home() );
}

function bbb_route_seo_slug(): string {
	$path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
	if (str_starts_with($path, 'pages/')) {
		$path = substr($path, strlen('pages/'));
	}

	return sanitize_title(trim($path, '/'));
}

function bbb_book_list_seo_slugs(): array {
	return array_values(array_unique(array_merge(array_keys(bbb_book_list_seo_route_data()), array('kindle-inserts'))));
}

function bbb_book_list_seo_route_data(): array {
	if (!function_exists('bbb_trope_page_seo_rows')) {
		return array();
	}

	$route_data = array();
	foreach (bbb_trope_page_seo_rows() as $row) {
		$slug        = sanitize_title(trim((string) ($row['page'] ?? ''), '/'));
		$title       = (string) ($row['seo_title'] ?? '');
		$description = (string) ($row['description'] ?? '');
		if ('' === $slug || '' === $title || '' === $description) {
			continue;
		}

		$route_data[$slug] = array(
			'title'       => $title,
			'description' => $description,
		);
	}

	return $route_data;
}

function bbb_route_seo_data(): array {
	$path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
	if (preg_match('#^series/([^/]+)/?$#', $path, $matches)) {
		$series_seo = get_option('bbb_series_seo_overrides', array());
		$series_slug = sanitize_title($matches[1]);
		if (is_array($series_seo) && isset($series_seo[$series_slug]) && is_array($series_seo[$series_slug])) {
			return $series_seo[$series_slug];
		}
	}

	$map = array(
		'library'                   => array(
			'title'       => 'romance book library — curated by trope & spice level',
			'description' => 'a hand-curated romance library with spice ratings, darkness scores, tension indexes, and trope filters. every book personally read and recommended — no filler.',
		),
		'book-reviews'              => array(
			'title'       => 'romance book reviews — tropes, spice & honest takes',
			'description' => 'honest romance book reviews with full trope breakdowns, spice ratings, and who should read it. no spoilers — just the details you actually need.',
		),
		'come-in'                   => array(
			'title'       => 'bybookishbabe — soft hearts, sinful taste | romance books, recs & the society',
			'description' => 'the home of the smut & sentiment society. dark romance recs, spice level browsing, the reader quiz, and a sunday letter that will ruin your reading life in the best way.',
		),
		'curated-romance-guides'   => array(
			'title'       => 'curated romance reading guides by trope & mood | bybookishbabe',
			'description' => "curated romance reading guides organized by trope, mood, and series. find exactly what to read next with bybookishbabe's handpicked lists.",
		),
		'series-reading-orders'     => array(
			'title'       => 'romance series reading orders — where to start every series | bybookishbabe',
			'description' => "find the correct reading order for your favorite romance series. bybookishbabe's complete guide to series order so you never read out of sequence.",
		),
		'romance-books-by-spice-level' => array(
			'title'       => 'romance books by spice level 🌶️ — from sweet to steamy | bybookishbabe',
			'description' => 'browse romance books organized by spice level. from sweet slow burns to five-chili steamy reads — find exactly the heat level you want.',
		),
		'what-to-read-next'         => array(
			'title'       => 'what romance book should i read next? | bybookishbabe',
			'description' => "not sure what to read next? bybookishbabe's romance recommendation engine matches your mood, tropes, and favorite reads to your next obsession.",
		),
		'books-like'                => array(
			'title'       => 'books like [title] — romance recommendations | bybookishbabe',
			'description' => 'looking for books like your favorites? find romance reads with the same energy, tropes, and emotional damage as the books you already love.',
		),
		'smut-sentiment-society'    => array(
			'title'       => 'the smut & sentiment society — weekly romance book club | bybookishbabe',
			'description' => 'join the smut & sentiment society for weekly romance recs, curated reading lists, and private book notes delivered every sunday.',
		),
		'reader-quizzes'            => array(
			'title'       => 'romance reader quizzes — find your next book & fictional boyfriend | bybookishbabe',
			'description' => "take bybookishbabe's romance reader quizzes. find your fictional boyfriend, your next read, and which trope matches your personality.",
		),
		'romance-trope-quiz'        => array(
			'title'       => 'what romance trope are you? quiz | bybookishbabe',
			'description' => 'take the romance trope quiz to find out if you are enemies to lovers, friends to lovers, forced proximity, fake dating, or second chance romance.',
		),
		'reading-challenge'         => array(
			'title'       => 'dark romance reading challenge 2026 — ruin me | bybookishbabe',
			'description' => 'a dark romance reading challenge with 10 curated prompts, real site picks, and a free tracker for readers who want their next ten books chosen with intent.',
		),
		'romance-reading-bingo'     => array(
			'title'       => 'romance reading bingo 2026 — find your reader type | bybookishbabe',
			'description' => "mark the tropes you've read, the book boyfriends you've fallen for, and the new releases you've devoured. get a bingo and find out exactly what kind of romance reader you are.",
		),
		'dark-romance-books'        => array(
			'title'       => 'dark romance books — curated library by trope & spice',
			'description' => 'browse a curated dark romance book library, sorted by trope, spice level, and series. every book is hand-picked and rated.',
		),
		'sports-romance-books'      => array(
			'title'       => 'the best sports romance books: ultimate guide (2026)',
			'description' => 'the best sports romance books ranked by spice — hockey romance, football romance, booktok favorites and underrated reads you need on your list.',
		),
		'enemies-to-lovers-books'   => array(
			'title'       => 'enemies to lovers books — curated library by spice',
			'description' => "a curated enemies-to-lovers book library sorted by spice level and trope. every book that made us fall for someone we shouldn't.",
		),
		'romantasy-books'           => array(
			'title'       => 'romantasy books — curated library by trope & spice',
			'description' => 'a hand-curated romantasy book library sorted by trope, spice level, and series. find your next fantasy romance obsession.',
		),
		'contemporary-romance-books' => array(
			'title'       => 'best contemporary romance books — modern love stories | bybookishbabe',
			'description' => 'the best contemporary romance books — modern love stories with emotional depth, relatable characters, and the perfect amount of chaos.',
		),
		'historical-romance-books' => array(
			'title'       => 'best historical romance books — reputations ruined by love | bybookishbabe',
			'description' => 'the best historical romance books featuring scandal, forbidden love, and men in waistcoats who will ruin your standards.',
		),
		'paranormal-romance-books' => array(
			'title'       => 'best paranormal romance books — vampires fae & supernatural love | bybookishbabe',
			'description' => 'the best paranormal romance books featuring vampires, fae, shifters, and supernatural love stories that go way beyond human.',
		),
		'dystopian-romance-books'   => array(
			'title'       => 'best dystopian romance books — love at the end of the world | bybookishbabe',
			'description' => 'the best dystopian romance books — love stories set in broken worlds with tension, survival, and men who would destroy systems for her.',
		),
		'slow-burn-books'           => array(
			'title'       => 'slow burn romance books — curated library by spice',
			'description' => 'browse a curated slow burn romance library. every book sorted by spice level and trope — all the tension, all worth the wait.',
		),
		'mafia-romance-books'       => array(
			'title'       => 'mafia romance books — curated library by trope & spice',
			'description' => 'browse a curated mafia romance book library, filtered by spice level, trope, and series order. no filler — only the best.',
		),
		'morally-gray-men-romance-books' => array(
			'title'       => 'best morally gray men romance books — dark romance that will ruin you | bybookishbabe',
			'description' => 'the best romance books featuring morally gray men — anti-heroes, obsessive love interests, and dark romance that will permanently raise your standards.',
		),
		'shop'                      => array(
			'title'       => 'romance book digital downloads & guides | bybookishbabe shop',
			'description' => "shop bybookishbabe's digital romance reading guides, book lists, and downloads. everything a romance reader needs in one place.",
		),
		'kindle-inserts'            => array(
			'title'       => 'printable kindle inserts for romance books | bybookishbabe',
			'description' => 'browse printable kindle inserts for romance readers. find aesthetic Kindle case inserts, open each design page, and download the size that fits your Kindle.',
		),
	);

	foreach (bbb_book_list_seo_route_data() as $slug => $seo) {
		$map[$slug] = $seo;
	}

	return $map[bbb_route_seo_slug()] ?? array();
}

function bbb_homepage_seo_title(string $title = ''): string {
	if (bbb_is_homepage_seo_context()) {
		return BBB_HOMEPAGE_SEO_TITLE;
	}

	$route_seo = bbb_route_seo_data();
	return $route_seo['title'] ?? $title;
}

function bbb_homepage_seo_description(string $description = ''): string {
	if (bbb_is_homepage_seo_context()) {
		return BBB_HOMEPAGE_SEO_DESCRIPTION;
	}

	$route_seo = bbb_route_seo_data();
	return $route_seo['description'] ?? $description;
}

add_filter('pre_get_document_title', 'bbb_homepage_seo_title', 99);
add_filter('rank_math/frontend/title', 'bbb_homepage_seo_title', 99);
add_filter('rank_math/frontend/description', 'bbb_homepage_seo_description', 99);
add_filter('rank_math/opengraph/facebook/title', 'bbb_homepage_seo_title', 99);
add_filter('rank_math/opengraph/facebook/description', 'bbb_homepage_seo_description', 99);
add_filter('rank_math/opengraph/twitter/title', 'bbb_homepage_seo_title', 99);
add_filter('rank_math/opengraph/twitter/description', 'bbb_homepage_seo_description', 99);

add_filter(
	'rank_math/frontend/canonical',
	static function (string $canonical): string {
		$slug = bbb_route_seo_slug();
		if (!in_array($slug, bbb_book_list_seo_slugs(), true)) {
			return $canonical;
		}

		return home_url('/' . $slug . '/');
	},
	99
);

add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		$slug = bbb_route_seo_slug();
		if ('come-in' !== $slug && !in_array($slug, bbb_book_list_seo_slugs(), true)) {
			return $robots;
		}

		unset($robots['noindex'], $robots['nofollow']);
		$robots['index'] = 'index';
		$robots['follow'] = 'follow';
		return $robots;
	},
	99
);

add_filter(
	'wp_robots',
	static function (array $robots): array {
		$slug = bbb_route_seo_slug();
		if ('come-in' !== $slug && !in_array($slug, bbb_book_list_seo_slugs(), true)) {
			return $robots;
		}

		unset($robots['noindex'], $robots['nofollow']);
		$robots['index'] = true;
		$robots['follow'] = true;
		return $robots;
	},
	99
);

add_filter(
	'robots_txt',
	static function (string $output): string {
		$lines = preg_split('/\r\n|\r|\n/', trim($output));
		$lines = is_array($lines) ? array_filter(array_map('trim', $lines), static fn(string $line): bool => '' !== $line) : array();

		$required = array(
			'User-agent: *',
			'Disallow: /wp-admin/',
			'Allow: /wp-admin/admin-ajax.php',
			'Allow: /wp-content/uploads/',
			'Sitemap: ' . home_url('/sitemap_index.xml'),
		);

		foreach ($required as $line) {
			if (!in_array($line, $lines, true)) {
				$lines[] = $line;
			}
		}

		return implode("\n", $lines) . "\n";
	},
	99
);

add_filter(
	'rank_math/json_ld',
	static function (array $data): array {
		if (! bbb_is_homepage_seo_context() && ! bbb_route_seo_data()) {
			return $data;
		}

		$title       = bbb_is_homepage_seo_context() ? BBB_HOMEPAGE_SEO_TITLE : (string) bbb_route_seo_data()['title'];
		$description = bbb_is_homepage_seo_context() ? BBB_HOMEPAGE_SEO_DESCRIPTION : (string) bbb_route_seo_data()['description'];

		foreach ($data as &$entity) {
			if (! is_array($entity) || empty($entity['@type'])) {
				continue;
			}

			$types = (array) $entity['@type'];
			if (array_intersect($types, array('WebSite', 'WebPage', 'CollectionPage'))) {
				$entity['name']        = $title;
				$entity['description'] = $description;
			}
		}
		unset($entity);

		return $data;
	},
	99
);

function bbb_schema_first_text_meta(int $post_id, array $keys): string {
	foreach ($keys as $key) {
		$value = function_exists('get_field') ? get_field($key, $post_id) : null;
		if (null === $value || '' === $value || false === $value) {
			$value = get_post_meta($post_id, $key, true);
		}
		if (null === $value || '' === $value || false === $value) {
			$value = get_post_meta($post_id, '_' . $key, true);
		}
		if (is_array($value)) {
			$value = $value['name'] ?? $value['title'] ?? $value['label'] ?? '';
		}
		if (is_scalar($value) && '' !== trim((string) $value)) {
			return trim(wp_strip_all_tags((string) $value));
		}
	}

	return '';
}

function bbb_schema_review_rating(int $post_id): ?float {
	$raw = bbb_schema_first_text_meta(
		$post_id,
		array('review_rating', 'rating', 'star_rating', 'book_rating', 'bbb_review_rating', 'bbb_star_rating')
	);

	if ('' === $raw || !preg_match('/([0-5](?:\.\d+)?)/', $raw, $matches)) {
		return null;
	}

	$rating = (float) $matches[1];
	if ($rating <= 0 || $rating > 5) {
		return null;
	}

	return $rating;
}

function bbb_schema_article_books(int $post_id): array {
	if (function_exists('bbb_review_index_article_books')) {
		return bbb_review_index_article_books($post_id);
	}

	if (function_exists('sss_article_post_books')) {
		return sss_article_post_books($post_id);
	}

	$ids = get_post_meta($post_id, '_bbb_article_books', true);
	if (!is_array($ids)) {
		$ids = array();
	}

	for ($index = 1; $index <= 24; $index++) {
		$id = (int) get_post_meta($post_id, '_bbb_article_book_' . $index, true);
		if ($id > 0) {
			$ids[] = $id;
		}
	}

	return array_values(array_filter(array_map('get_post', array_unique(array_map('absint', $ids)))));
}

function bbb_schema_explicit_article_books(int $post_id): array {
	if (function_exists('sss_article_post_books')) {
		return sss_article_post_books($post_id, false);
	}

	return bbb_schema_article_books($post_id);
}

function bbb_schema_current_post_id(): int {
	$post_id = get_queried_object_id();
	if ($post_id > 0) {
		return $post_id;
	}

	$slug = function_exists('bbb_route_seo_slug') ? bbb_route_seo_slug() : '';
	if ('' === $slug) {
		return 0;
	}

	$post = get_page_by_path($slug, OBJECT, array('post', 'page'));
	return $post instanceof WP_Post ? (int) $post->ID : 0;
}

function bbb_schema_clean_text(string $text, int $word_limit = 0): string {
	$text = wp_strip_all_tags(strip_shortcodes($text), true);
	$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
	$text = trim((string) preg_replace('/\s+/', ' ', $text));

	return $word_limit > 0 ? wp_trim_words($text, $word_limit, '') : $text;
}

function bbb_schema_bool($value): bool {
	if (is_bool($value)) {
		return $value;
	}

	if (is_numeric($value)) {
		return (int) $value > 0;
	}
	if (is_array($value)) {
		foreach ($value as $item) {
			if (bbb_schema_bool($item)) {
				return true;
			}
		}

		return false;
	}

	$value = strtolower(trim((string) $value));
	return in_array($value, array('1', 'true', 'yes', 'y', 'on', 'review', 'book_review'), true);
}

function bbb_schema_review_body_text(string $text): string {
	$text = bbb_schema_clean_text($text);
	if ('' === $text) {
		return '';
	}

	$sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: array($text);
	$summary   = trim(implode(' ', array_slice(array_filter(array_map('trim', $sentences)), 0, 2)));

	return wp_trim_words('' !== $summary ? $summary : $text, 45, '');
}

function bbb_schema_org_entity(): array {
	$logo = '';
	$custom_logo_id = (int) get_theme_mod('custom_logo');
	if ($custom_logo_id > 0) {
		$logo = (string) wp_get_attachment_image_url($custom_logo_id, 'full');
	}

	$entity = array(
		'@type' => 'Organization',
		'@id'   => home_url('/#organization'),
		'name'  => 'ByBookishBabe',
		'url'   => home_url('/'),
	);

	if ('' !== $logo) {
		$entity['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => $logo,
		);
	}

	return $entity;
}

function bbb_schema_website_entity(): array {
	return array(
		'@type'           => 'WebSite',
		'@id'             => home_url('/#website'),
		'url'             => home_url('/'),
		'name'            => 'ByBookishBabe',
		'alternateName'   => 'bybookishbabe',
		'description'     => BBB_HOMEPAGE_SEO_DESCRIPTION,
		'publisher'       => array('@id' => home_url('/#organization')),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => home_url('/?s={search_term_string}'),
			'query-input' => 'required name=search_term_string',
		),
	);
}

function bbb_schema_book_entity(WP_Post $book): array {
	$author = function_exists('bbb_get_book_author')
		? bbb_get_book_author($book->ID)
		: bbb_schema_first_text_meta($book->ID, array('author'));
	$cover = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID) : '';
	$tropes = array();
	$book_data = function_exists('sss_book_data') ? sss_book_data($book) : array();

	foreach (array('bbb_trope', 'sss_trope', 'bbb_shelf', 'sss_shelf') as $taxonomy) {
		if (!taxonomy_exists($taxonomy)) {
			continue;
		}
		$terms = get_the_terms($book->ID, $taxonomy);
		if ($terms && !is_wp_error($terms)) {
			$tropes = array_merge($tropes, wp_list_pluck($terms, 'name'));
		}
	}

	$entity = array(
		'@type' => 'Book',
		'@id'   => get_permalink($book) . '#book',
		'url'   => get_permalink($book),
		'name'  => function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($book)) : get_the_title($book),
		'inLanguage' => 'en',
	);

	if ($author) {
		$entity['author'] = array(
			'@type' => 'Person',
			'name'  => $author,
		);
	}
	if ($cover) {
		$entity['image'] = $cover;
	}
	if ($tropes) {
		$entity['genre'] = array_values(array_unique(array_filter(array_map('strval', $tropes))));
	}

	$series = bbb_schema_book_series_entity($book, $book_data);
	if ($series) {
		$entity['isPartOf'] = $series;
	}

	if ('bbb_book' === $book->post_type && function_exists('bbb_book_seo_description')) {
		$description = bbb_book_seo_description($book->ID);
	} else {
		$description = bbb_schema_first_text_meta($book->ID, array('description', 'mini', 'why', 'sss_mini', 'sss_why'));
	}
	if ('' !== $description) {
		$entity['description'] = bbb_schema_clean_text($description, 45);
	}

	$publisher = bbb_schema_org_entity();
	$entity['publisher'] = array('@id' => $publisher['@id']);

	$properties = bbb_schema_book_additional_properties($book, $book_data);
	if ($properties) {
		$entity['additionalProperty'] = $properties;
	}

	$review = bbb_schema_single_book_review_entity($book);
	if ($review) {
		$entity['review'] = $review;
	}

	return $entity;
}

function bbb_schema_book_series_entity(WP_Post $book, array $book_data = array()): array {
	$series_name = trim((string) ($book_data['series_name'] ?? ''));
	$series_handle = sanitize_title((string) ($book_data['series_handle'] ?? get_post_meta($book->ID, '_bbb_series_handle', true)));

	if ('' === $series_name && '' !== $series_handle && taxonomy_exists('bbb_series')) {
		$series_term = get_term_by('slug', $series_handle, 'bbb_series');
		if ($series_term instanceof WP_Term) {
			$series_name = $series_term->name;
		}
	}

	if ('' === $series_name && '' !== $series_handle) {
		$series_name = ucwords(str_replace('-', ' ', $series_handle));
	}
	if ('' === $series_name) {
		return array();
	}

	$series_url = '' !== $series_handle ? home_url('/series/' . $series_handle . '/') : '';
	$entity = array(
		'@type' => 'BookSeries',
		'name'  => function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($series_name) : $series_name,
	);

	if ('' !== $series_url) {
		$entity['url'] = $series_url;
		$entity['@id'] = $series_url . '#series';
	}

	return $entity;
}

function bbb_schema_book_additional_properties(WP_Post $book, array $book_data = array()): array {
	$metrics = array(
		'Spice level'            => $book_data['spice'] ?? get_post_meta($book->ID, '_bbb_spice', true),
		'Darkness level'         => $book_data['darkness'] ?? get_post_meta($book->ID, '_bbb_darkness', true),
		'Tension score'          => $book_data['tension'] ?? get_post_meta($book->ID, '_bbb_tension', true),
		'Emotional damage score' => $book_data['damage'] ?? get_post_meta($book->ID, '_bbb_damage', true),
		'Yearning level'         => $book_data['yearning'] ?? get_post_meta($book->ID, '_bbb_yearning', true),
	);

	$properties = array();
	foreach ($metrics as $name => $raw_value) {
		$value = is_scalar($raw_value) ? trim((string) $raw_value) : '';
		if ('' === $value || '0' === $value) {
			continue;
		}

		if (is_numeric($value) && (float) $value > 0 && (float) $value <= 5) {
			$value = rtrim(rtrim((string) (float) $value, '0'), '.') . '/5';
		}

		$properties[] = array(
			'@type' => 'PropertyValue',
			'name'  => $name,
			'value' => $value,
		);
	}

	$spicy_chapters = function_exists('bbb_book_spicy_chapters') ? bbb_book_spicy_chapters($book->ID) : array();
	if ($spicy_chapters) {
		$properties[] = array(
			'@type' => 'PropertyValue',
			'name'  => 'Spicy chapters',
			'value' => implode(', ', $spicy_chapters),
		);
	}

	return $properties;
}

function bbb_schema_single_book_review_entity(WP_Post $book): array {
	if ('bbb_book' !== $book->post_type) {
		return array();
	}

	$review_body = bbb_schema_first_text_meta(
		$book->ID,
		array('_bbb_verdict', '_bbb_mini_note', '_bbb_why', '_bbb_vibe_description')
	);
	if ('' === $review_body) {
		return array();
	}

	$publisher = bbb_schema_org_entity();

	return array(
		'@type'        => 'Review',
		'@id'          => get_permalink($book) . '#review',
		'author'       => array(
			'@type' => 'Person',
			'name'  => 'bybookishbabe',
			'url'   => home_url('/'),
		),
		'publisher'    => array('@id' => $publisher['@id']),
		'itemReviewed' => array('@id' => get_permalink($book) . '#book'),
		'reviewBody'   => bbb_schema_review_body_text($review_body),
	);
}

function bbb_schema_single_book_breadcrumb_entity(int $post_id): array {
	if (!is_singular('bbb_book')) {
		return array();
	}

	$post = get_post($post_id);
	if (!$post instanceof WP_Post || 'bbb_book' !== $post->post_type) {
		return array();
	}

	$book_data = function_exists('sss_book_data') ? sss_book_data($post) : array();
	$series = bbb_schema_book_series_entity($post, $book_data);
	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'Home',
			'item'     => home_url('/'),
		),
		array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => 'Library',
			'item'     => home_url('/library/'),
		),
	);

	if ($series && !empty($series['url'])) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => count($items) + 1,
			'name'     => (string) $series['name'],
			'item'     => (string) $series['url'],
		);
	}

	$items[] = array(
		'@type'    => 'ListItem',
		'position' => count($items) + 1,
		'name'     => bbb_schema_book_title($post),
	);

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => get_permalink($post) . '#breadcrumb',
		'itemListElement' => $items,
	);
}

function bbb_schema_book_title(WP_Post $book): string {
	return function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($book)) : get_the_title($book);
}

function bbb_schema_book_author_name(WP_Post $book): string {
	if (function_exists('bbb_get_book_author')) {
		return trim((string) bbb_get_book_author($book->ID));
	}

	if ('bbb_book' === $book->post_type) {
		return bbb_schema_first_text_meta($book->ID, array('_bbb_author', 'author'));
	}

	return bbb_schema_first_text_meta($book->ID, array('sss_author', '_sss_author', 'author'));
}

function bbb_schema_single_book_entity(): array {
	if (!is_singular('bbb_book')) {
		return array();
	}

	$post = get_post((int) get_queried_object_id());
	if (!$post instanceof WP_Post || 'publish' !== get_post_status($post)) {
		return array();
	}

	if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($post->ID)) {
		return array();
	}

	return bbb_schema_book_entity($post);
}

function bbb_review_seo_cover_url(int $post_id): string {
	if (!is_singular('post')) {
		return '';
	}

	if (!function_exists('bbb_get_book_cover_url')) {
		return '';
	}

	if (function_exists('bbb_schema_is_review_post') && !bbb_schema_is_review_post($post_id)) {
		return '';
	}

	$books = function_exists('bbb_schema_article_books') ? bbb_schema_article_books($post_id) : array();
	$book  = $books[0] ?? null;

	return $book instanceof WP_Post ? bbb_get_book_cover_url($book->ID, 'full') : '';
}

function bbb_review_seo_post_image_url(int $post_id): string {
	if (!is_singular('post')) {
		return '';
	}

	$candidates = array(
		get_the_post_thumbnail_url($post_id, 'full') ?: '',
		(string) get_post_meta($post_id, 'rank_math_facebook_image', true),
		(string) get_post_meta($post_id, 'rank_math_twitter_image', true),
		(string) get_post_meta($post_id, '_thumbnail_external_url', true),
	);

	foreach ($candidates as $candidate) {
		$url = trim((string) $candidate);
		if ('' !== $url && !(function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url($url))) {
			return esc_url_raw($url);
		}
	}

	return '';
}

function bbb_review_seo_filter_image(string $image): string {
	$post_id = (int) get_queried_object_id();
	$image   = trim($image);
	if ('' !== $image && !(function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url($image))) {
		return $image;
	}

	$post_image = $post_id > 0 ? bbb_review_seo_post_image_url($post_id) : '';
	if ('' !== $post_image) {
		return $post_image;
	}

	$cover = $post_id > 0 ? bbb_review_seo_cover_url($post_id) : '';
	return '' !== $cover ? $cover : $image;
}
add_filter('rank_math/opengraph/facebook/image', 'bbb_review_seo_filter_image', 120);
add_filter('rank_math/opengraph/twitter/image', 'bbb_review_seo_filter_image', 120);

function bbb_review_seo_add_rank_math_image($opengraph_image): void {
	$post_id = (int) get_queried_object_id();
	$cover   = $post_id > 0 && '' === bbb_review_seo_post_image_url($post_id) ? bbb_review_seo_cover_url($post_id) : '';

	if ('' !== $cover && is_object($opengraph_image) && method_exists($opengraph_image, 'add_image')) {
		$opengraph_image->add_image($cover);
	}
}
add_action('rank_math/opengraph/facebook/add_additional_images', 'bbb_review_seo_add_rank_math_image', 5);
add_action('rank_math/opengraph/twitter/add_additional_images', 'bbb_review_seo_add_rank_math_image', 5);

function bbb_schema_is_review_post(int $post_id): bool {
	if (function_exists('bbb_review_index_has_review_flag')) {
		return bbb_review_index_has_review_flag($post_id);
	}

	foreach (array('book_review', '_book_review', '_bbb_book_review', 'review', '_review', '_bbb_review') as $key) {
		$value = get_post_meta($post_id, $key, true);
		if (null !== $value && '' !== $value && bbb_schema_bool(maybe_unserialize($value))) {
			return true;
		}
	}

	if (taxonomy_exists('book_review_category')) {
		$review_terms = get_the_terms($post_id, 'book_review_category');
		if ($review_terms && !is_wp_error($review_terms)) {
			return true;
		}
	}

	$meta = get_post_meta($post_id);
	foreach ($meta as $key => $values) {
		$normalized_key = strtolower(trim((string) $key, '_'));
		if (!in_array($normalized_key, array('book_review', 'custom_book_review', 'custom.book_review'), true)) {
			continue;
		}

		foreach ((array) $values as $meta_value) {
			if (bbb_schema_bool(maybe_unserialize($meta_value))) {
				return true;
			}
		}
	}

	$title_and_slug = strtolower((string) get_the_title($post_id) . ' ' . get_post_field('post_name', $post_id));
	return str_contains($title_and_slug, 'review');
}

function bbb_schema_book_review_entity(int $post_id): array {
	if (!is_singular('post') || !bbb_schema_is_review_post($post_id)) {
		return array();
	}

	$books = bbb_schema_article_books($post_id);
	$book  = $books[0] ?? null;
	if (!$book instanceof WP_Post) {
		return array();
	}

	$book_title  = bbb_schema_book_title($book);
	$book_author = bbb_schema_book_author_name($book);
	$description = bbb_schema_first_text_meta(
		$post_id,
		array('review_summary', 'review_excerpt', 'rank_math_description', 'description', 'excerpt')
	);
	if ('' === $description) {
		$description = get_the_excerpt($post_id);
	}
	if ('' === $description) {
		$description = bbb_schema_first_text_meta($book->ID, array('_bbb_verdict', '_bbb_mini_note', '_bbb_why', 'sss_mini', 'sss_why'));
	}

	$review = array(
		'@type'         => 'Review',
		'@id'           => get_permalink($post_id) . '#book-review',
		'url'           => get_permalink($post_id),
		'name'          => trim($book_title . ' review' . ('' !== $book_author ? ' — ' . $book_author : '')),
		'datePublished' => get_the_date(DATE_W3C, $post_id),
		'dateModified'  => get_the_modified_date(DATE_W3C, $post_id),
		'author'        => array(
			'@type' => 'Person',
			'name'  => 'bybookishbabe',
			'url'   => home_url('/'),
		),
		'publisher'     => array(
			'@type' => 'Organization',
			'name'  => 'ByBookishBabe',
			'url'   => home_url('/'),
		),
		'itemReviewed'  => bbb_schema_book_entity($book),
	);

	if ($description) {
		$review['reviewBody']  = bbb_schema_review_body_text((string) $description);
		$review['description'] = $review['reviewBody'];
	}

	$rating = bbb_schema_review_rating($post_id);
	if (null !== $rating) {
		$review['reviewRating'] = array(
			'@type'       => 'Rating',
			'ratingValue' => $rating,
			'bestRating'  => 5,
			'worstRating' => 1,
		);
	}

	return $review;
}

function bbb_schema_book_list_entity(): array {
	$post_id = bbb_schema_current_post_id();
	$post    = $post_id > 0 ? get_post($post_id) : null;
	if (!$post instanceof WP_Post || !in_array($post->post_type, array('post', 'page'), true) || bbb_schema_is_review_post($post_id)) {
		return bbb_schema_route_book_list_entity();
	}

	$books = bbb_schema_explicit_article_books($post_id);
	if (!$books) {
		$books = function_exists('sss_article_books_for_post') ? sss_article_books_for_post($post_id) : bbb_schema_article_books($post_id);
	}
	$books = array_values(array_filter($books, static fn($book): bool => $book instanceof WP_Post));
	if (count($books) < 2) {
		return bbb_schema_route_book_list_entity();
	}

	$items = array();
	foreach (array_slice($books, 0, 24) as $index => $book) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'item'     => bbb_schema_book_entity($book),
		);
	}

	return array(
		'@type'           => 'ItemList',
		'@id'             => get_permalink($post_id) . '#book-list',
		'name'            => get_the_title($post_id),
		'url'             => get_permalink($post_id),
		'numberOfItems'   => count($items),
		'itemListElement' => $items,
	);
}

function bbb_schema_public_book_posts(array $posts): array {
	return array_values(
		array_filter(
			$posts,
			static function ($book): bool {
				if (!$book instanceof WP_Post || 'publish' !== get_post_status($book)) {
					return false;
				}
				if (function_exists('sss_book_is_private') && sss_book_is_private($book->ID)) {
					return false;
				}
				if (function_exists('bbb_book_is_publicly_visible')) {
					return bbb_book_is_publicly_visible($book->ID);
				}
				return function_exists('sss_book_is_visible') ? sss_book_is_visible($book->ID) : true;
			}
		)
	);
}

function bbb_schema_books_for_current_route(): array {
	$slug = function_exists('bbb_route_seo_slug') ? bbb_route_seo_slug() : '';
	if ('' === $slug) {
		return array();
	}

	if ('library' === $slug && function_exists('sss_get_all_books')) {
		return bbb_schema_public_book_posts(sss_get_all_books());
	}

	$term = null;
	if (function_exists('bbb_get_page_taxonomy_term')) {
		$term = bbb_get_page_taxonomy_term('trope') ?: bbb_get_page_taxonomy_term('shelf');
	}
	if (!$term instanceof WP_Term && function_exists('bbb_find_book_taxonomy_term')) {
		$term = bbb_find_book_taxonomy_term($slug);
	}
	if (!$term instanceof WP_Term || !function_exists('bbb_get_book_ids_for_taxonomy_term')) {
		return array();
	}

	$books = array_filter(array_map('get_post', bbb_get_book_ids_for_taxonomy_term($term)));

	return bbb_schema_public_book_posts($books);
}

function bbb_schema_route_book_list_entity(): array {
	$route_seo = function_exists('bbb_route_seo_data') ? bbb_route_seo_data() : array();
	$slug      = function_exists('bbb_route_seo_slug') ? bbb_route_seo_slug() : '';
	if ('' === $slug || !$route_seo) {
		return array();
	}

	$books = bbb_schema_books_for_current_route();
	if (count($books) < 2) {
		return array();
	}

	$items = array();
	foreach (array_slice($books, 0, 24) as $index => $book) {
		if (!$book instanceof WP_Post) {
			continue;
		}
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'url'      => get_permalink($book),
			'item'     => bbb_schema_book_entity($book),
		);
	}

	if (!$items) {
		return array();
	}

	return array(
		'@type'           => 'ItemList',
		'@id'             => home_url('/' . $slug . '/#book-list'),
		'name'            => (string) ($route_seo['title'] ?? get_the_title(bbb_schema_current_post_id())),
		'description'     => (string) ($route_seo['description'] ?? ''),
		'url'             => home_url('/' . $slug . '/'),
		'numberOfItems'   => count($items),
		'itemListElement' => $items,
	);
}

function bbb_schema_faq_text(string $value): string {
	$text = wp_strip_all_tags(do_shortcode($value));
	$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
	$text = (string) preg_replace('/\s+/', ' ', $text);

	return trim($text);
}

function bbb_schema_faq_pairs_from_content(string $content): array {
	$content = (string) preg_replace('/<p\b[^>]*>\s*(\[\/?(?:faq|q|a)\])\s*<\/p>/i', '$1', $content);
	$content = str_ireplace(array('[FAQ]', '[/FAQ]', '[Q]', '[/Q]', '[A]', '[/A]'), array('[faq]', '[/faq]', '[q]', '[/q]', '[a]', '[/a]'), $content);

	$pairs = array();
	if (preg_match_all('/\[q\](.*?)\[\/q\]\s*\[a\](.*?)(?:\[\/a\]|\/a\])/is', $content, $blocks, PREG_SET_ORDER)) {
		foreach ($blocks as $block) {
			$question = bbb_schema_faq_text($block[1]);
			$answer   = bbb_schema_faq_text($block[2]);

			if ('' !== $question && '' !== $answer) {
				$pairs[] = array(
					'question' => $question,
					'answer'   => $answer,
				);
			}
		}
	}

	if ($pairs) {
		return $pairs;
	}

	if (preg_match_all('/\[(q|a)\](.*?)\[\/\1\]/is', $content, $tokens, PREG_SET_ORDER)) {
		$question = '';
		foreach ($tokens as $token) {
			$type = strtolower($token[1]);
			$body = trim($token[2]);

			if ('q' === $type) {
				$question = bbb_schema_faq_text($body);
				continue;
			}

			if ('a' !== $type || '' === $question || '' === $body) {
				continue;
			}

			$answer = bbb_schema_faq_text($body);
			if ('' !== $answer) {
				$pairs[] = array(
					'question' => $question,
					'answer'   => $answer,
				);
			}
			$question = '';
		}
	}

	return $pairs;
}

function bbb_schema_faq_pairs_from_rendered(string $content): array {
	$rendered = do_shortcode($content);
	$pairs    = array();

	if (!str_contains($rendered, 'blog-faq')) {
		return $pairs;
	}

	if (preg_match_all('/<details\b[^>]*class="[^"]*\bblog-faq__item\b[^"]*"[^>]*>.*?<summary\b[^>]*class="[^"]*\bblog-faq__question\b[^"]*"[^>]*>\s*<span>(.*?)<\/span>.*?<div\b[^>]*class="[^"]*\bblog-faq__answer\b[^"]*"[^>]*>(.*?)<\/div>/is', $rendered, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$question = bbb_schema_faq_text($match[1]);
			$answer   = bbb_schema_faq_text($match[2]);

			if ('' !== $question && '' !== $answer) {
				$pairs[] = array(
					'question' => $question,
					'answer'   => $answer,
				);
			}
		}
	}

	return $pairs;
}

function bbb_schema_faq_entity(int $post_id): array {
	if (!is_singular(array('post', 'bbb_boyfriend'))) {
		return array();
	}

	$post = get_post($post_id);
	if (!$post instanceof WP_Post || !in_array($post->post_type, array('post', 'bbb_boyfriend'), true)) {
		return array();
	}

	$pairs = bbb_schema_faq_pairs_from_rendered((string) $post->post_content);
	if (!$pairs) {
		$pairs = bbb_schema_faq_pairs_from_content((string) $post->post_content);
	}
	if (!$pairs && 'bbb_boyfriend' === $post->post_type && function_exists('bbb_schema_fictional_boyfriend_faq_pairs')) {
		$pairs = bbb_schema_fictional_boyfriend_faq_pairs($post_id);
	}

	$seen = array();
	$main = array();
	foreach ($pairs as $pair) {
		$question = trim((string) ($pair['question'] ?? ''));
		$answer   = trim((string) ($pair['answer'] ?? ''));
		$key      = strtolower($question);

		if ('' === $question || '' === $answer || isset($seen[$key])) {
			continue;
		}

		$seen[$key] = true;
		$main[]     = array(
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	if (!$main) {
		return array();
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink($post_id) . '#faq',
		'url'        => get_permalink($post_id),
		'name'       => get_the_title($post_id) . ' FAQ',
		'mainEntity' => $main,
	);
}

function bbb_schema_without_entity_id(array $data, string $id): array {
	foreach ($data as $key => $entity) {
		if (is_array($entity) && isset($entity['@id']) && (string) $entity['@id'] === $id) {
			unset($data[$key]);
		}
	}

	return $data;
}

function bbb_schema_fictional_boyfriend_book_entity(int $post_id): array {
	if (!function_exists('bbb_fictional_boyfriend_primary_book_id')) {
		return array();
	}

	$book_id = bbb_fictional_boyfriend_primary_book_id($post_id);
	$book    = $book_id ? get_post($book_id) : null;
	if ($book instanceof WP_Post && in_array($book->post_type, array('bbb_book', 'sss_book'), true)) {
		$entity = bbb_schema_book_entity($book);
		if ($entity) {
			$entity['character'] = array('@id' => get_permalink($post_id) . '#person');
		}

		return $entity;
	}

	$source = trim((string) get_post_meta($post_id, '_bbb_fb_source', true));
	$author = trim((string) get_post_meta($post_id, '_bbb_fb_author', true));
	if ('' === $source) {
		return array();
	}

	$entity = array(
		'@type'     => 'Book',
		'@id'       => get_permalink($post_id) . '#source-book',
		'name'      => function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($source) : $source,
		'character' => array('@id' => get_permalink($post_id) . '#person'),
	);

	if ('' !== $author) {
		$entity['author'] = array(
			'@type' => 'Person',
			'name'  => $author,
		);
	}

	return $entity;
}

function bbb_schema_fictional_boyfriend_series_entity(int $post_id): array {
	$series_name = trim((string) get_post_meta($post_id, '_bbb_fb_series', true));
	$series_url  = '';
	if (function_exists('bbb_fictional_boyfriend_series_post')) {
		$series_post = bbb_fictional_boyfriend_series_post($post_id);
		if ($series_post instanceof WP_Post) {
			$series_name = get_the_title($series_post);
			$series_url  = get_permalink($series_post);
		}
	}

	if ('' === $series_name) {
		return array();
	}

	$entity = array(
		'@type'     => 'CreativeWorkSeries',
		'@id'       => ('' !== $series_url ? $series_url : get_permalink($post_id)) . '#series',
		'name'      => $series_name,
		'character' => array('@id' => get_permalink($post_id) . '#person'),
	);

	if ('' !== $series_url) {
		$entity['url'] = $series_url;
	}

	$author = trim((string) get_post_meta($post_id, '_bbb_fb_author', true));
	if ('' !== $author) {
		$entity['author'] = array(
			'@type' => 'Person',
			'name'  => $author,
		);
	}

	return $entity;
}

function bbb_schema_fictional_boyfriend_person_entity(int $post_id): array {
	if (!is_singular('bbb_boyfriend')) {
		return array();
	}

	$post = get_post($post_id);
	if (!$post instanceof WP_Post || 'bbb_boyfriend' !== $post->post_type) {
		return array();
	}

	$description = function_exists('bbb_fictional_boyfriend_seo_description')
		? bbb_fictional_boyfriend_seo_description($post_id)
		: bbb_schema_clean_text((string) get_the_excerpt($post_id), 35);
	$book_entity = bbb_schema_fictional_boyfriend_book_entity($post_id);
	$series_entity = bbb_schema_fictional_boyfriend_series_entity($post_id);
	$tropes = function_exists('bbb_fictional_boyfriend_tropes') ? bbb_fictional_boyfriend_tropes($post_id) : array();
	$traits = function_exists('bbb_fictional_boyfriend_traits') ? array_map('bbb_fictional_boyfriend_trait_label', bbb_fictional_boyfriend_traits($post_id)) : array();

	$entity = array(
		'@type'                    => 'Person',
		'@id'                      => get_permalink($post_id) . '#person',
		'url'                      => get_permalink($post_id),
		'name'                     => get_the_title($post_id),
		'description'              => bbb_schema_clean_text($description, 40),
		'disambiguatingDescription' => 'Fictional character profile from ByBookishBabe.',
		'mainEntityOfPage'         => array('@id' => get_permalink($post_id) . '#webpage'),
	);

	$image = get_the_post_thumbnail_url($post_id, 'full');
	if ($image) {
		$entity['image'] = $image;
	}
	if ($tropes) {
		$entity['knowsAbout'] = array_values(array_unique(array_filter(array_map('strval', $tropes))));
	}
	if ($traits) {
		$entity['additionalProperty'] = array(
			array(
				'@type' => 'PropertyValue',
				'name'  => 'fictional boyfriend traits',
				'value' => implode(', ', array_values(array_unique(array_filter($traits)))),
			),
		);
	}

	$subject_of = array();
	if ($book_entity) {
		$subject_of[] = array('@id' => $book_entity['@id']);
	}
	if ($series_entity) {
		$subject_of[] = array('@id' => $series_entity['@id']);
	}
	if ($subject_of) {
		$entity['subjectOf'] = $subject_of;
	}

	return $entity;
}

function bbb_schema_fictional_boyfriend_breadcrumb_entity(int $post_id): array {
	if (!is_singular('bbb_boyfriend')) {
		return array();
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => get_permalink($post_id) . '#breadcrumb',
		'itemListElement' => array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => 'home',
				'item'     => home_url('/'),
			),
			array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => 'fictional boyfriends',
				'item'     => home_url('/fictional-boyfriends/'),
			),
			array(
				'@type'    => 'ListItem',
				'position' => 3,
				'name'     => get_the_title($post_id),
				'item'     => get_permalink($post_id),
			),
		),
	);
}

function bbb_schema_fictional_boyfriend_faq_pairs(int $post_id): array {
	$source = trim((string) get_post_meta($post_id, '_bbb_fb_source', true));
	$author = trim((string) get_post_meta($post_id, '_bbb_fb_author', true));
	$tropes = function_exists('bbb_fictional_boyfriend_tropes') ? bbb_fictional_boyfriend_tropes($post_id) : array();
	$spice = function_exists('bbb_fictional_boyfriend_spice') ? bbb_fictional_boyfriend_spice($post_id) : 0;

	return array(
		array(
			'question' => 'what book is ' . get_the_title($post_id) . ' from?',
			'answer'   => get_the_title($post_id) . ' is from ' . ('' !== $source ? $source : 'a linked romance book') . ('' !== $author ? ' by ' . $author : '') . '.',
		),
		array(
			'question' => 'what are ' . get_the_title($post_id) . '\'s tropes?',
			'answer'   => $tropes ? implode(', ', $tropes) . '.' : 'His tropes are listed in the fictional boyfriend profile.',
		),
		array(
			'question' => 'how spicy is ' . get_the_title($post_id) . '\'s book?',
			'answer'   => ('' !== $source ? $source : 'The linked book') . ' is rated ' . (function_exists('bbb_fictional_boyfriend_peppers') ? bbb_fictional_boyfriend_peppers($spice) : (string) $spice) . ' in the library.',
		),
		array(
			'question' => 'is ' . get_the_title($post_id) . ' in the fictional boyfriend quiz?',
			'answer'   => get_the_title($post_id) . ' belongs in the fictional boyfriend universe on ByBookishBabe.',
		),
	);
}

add_filter(
	'rank_math/json_ld',
	static function (array $data): array {
		if (bbb_is_homepage_seo_context()) {
			$organization = bbb_schema_org_entity();
			$website      = bbb_schema_website_entity();
			$has_org      = false;
			$has_website  = false;

			foreach ($data as &$entity) {
				if (!is_array($entity) || empty($entity['@type'])) {
					continue;
				}

				$types = (array) $entity['@type'];
				if (in_array('Organization', $types, true) && !$has_org) {
					$entity  = array_replace($organization, $entity);
					$has_org = true;
				}
				if (in_array('WebSite', $types, true) && !$has_website) {
					$entity      = array_replace($website, $entity);
					$has_website = true;
				}
			}
			unset($entity);

			if (!$has_org) {
				$data['bbb-organization'] = $organization;
			}
			if (!$has_website) {
				$data['bbb-website'] = $website;
			}

			$GLOBALS['bbb_organization_schema_added'] = true;
			$GLOBALS['bbb_website_schema_added']      = true;
		}

		$post_id = bbb_schema_current_post_id();
		if ($post_id <= 0 && !is_singular('bbb_book')) {
			return $data;
		}

		$cover = $post_id > 0 && '' === bbb_review_seo_post_image_url($post_id) ? bbb_review_seo_cover_url($post_id) : '';
		if ('' !== $cover) {
			$image_id = $cover;
			$data['bbb-review-cover-image'] = array(
				'@type'      => 'ImageObject',
				'@id'        => $image_id,
				'url'        => $cover,
				'inLanguage' => get_bloginfo('language'),
			);

			foreach ($data as &$entity) {
				if (!is_array($entity) || empty($entity['@type'])) {
					continue;
				}

				$types = (array) $entity['@type'];
				if (array_intersect($types, array('WebPage', 'Article', 'BlogPosting'))) {
					$entity['image'] = array('@id' => $image_id);
					if (in_array('WebPage', $types, true)) {
						$entity['primaryImageOfPage'] = array('@id' => $image_id);
					}
				}
			}
			unset($entity);
		}

		$review = bbb_schema_book_review_entity($post_id);
		if ($review) {
			$data['bbb-book-review'] = $review;
			$GLOBALS['bbb_book_review_schema_added'] = true;
		} else {
				$list = bbb_schema_book_list_entity();
				if ($list) {
					if (!empty($list['@id'])) {
						$data = bbb_schema_without_entity_id($data, (string) $list['@id']);
					}
					$data['bbb-book-list'] = $list;
					$GLOBALS['bbb_book_list_schema_added'] = true;
				}
		}

		$book = bbb_schema_single_book_entity();
		if ($book) {
			$organization = bbb_schema_org_entity();
			$breadcrumb = bbb_schema_single_book_breadcrumb_entity($post_id);
			$data['bbb-organization'] = $organization;
			$data['bbb-book']         = $book;
			if ($breadcrumb) {
				$data['bbb-book-breadcrumb'] = $breadcrumb;
				$GLOBALS['bbb_book_breadcrumb_schema_added'] = true;
			}

			foreach ($data as &$entity) {
				if (!is_array($entity) || empty($entity['@type'])) {
					continue;
				}

				$types = (array) $entity['@type'];
				if (in_array('WebPage', $types, true)) {
					$entity['mainEntity'] = array('@id' => $book['@id']);
					$entity['about']      = array('@id' => $book['@id']);
					if ($breadcrumb) {
						$entity['breadcrumb'] = array('@id' => $breadcrumb['@id']);
					}
				}
			}
			unset($entity);

			$GLOBALS['bbb_organization_schema_added'] = true;
			$GLOBALS['bbb_book_schema_added']         = true;
		}

		if (is_singular('bbb_boyfriend')) {
			$person = bbb_schema_fictional_boyfriend_person_entity($post_id);
			$source_book = bbb_schema_fictional_boyfriend_book_entity($post_id);
			$series = bbb_schema_fictional_boyfriend_series_entity($post_id);
			$breadcrumb = bbb_schema_fictional_boyfriend_breadcrumb_entity($post_id);

			if ($person) {
				$data['bbb-fictional-boyfriend-person'] = $person;
				$GLOBALS['bbb_fictional_boyfriend_person_schema_added'] = true;
			}
			if ($source_book) {
				$data['bbb-fictional-boyfriend-source-book'] = $source_book;
				$GLOBALS['bbb_fictional_boyfriend_book_schema_added'] = true;
			}
			if ($series) {
				$data['bbb-fictional-boyfriend-series'] = $series;
				$GLOBALS['bbb_fictional_boyfriend_series_schema_added'] = true;
			}
			if ($breadcrumb) {
				$data['bbb-fictional-boyfriend-breadcrumb'] = $breadcrumb;
				$GLOBALS['bbb_fictional_boyfriend_breadcrumb_schema_added'] = true;
			}

			foreach ($data as &$entity) {
				if (!is_array($entity) || empty($entity['@type'])) {
					continue;
				}

				$types = (array) $entity['@type'];
				if (in_array('WebPage', $types, true)) {
					$entity['@id'] = get_permalink($post_id) . '#webpage';
					if ($person) {
						$entity['mainEntity'] = array('@id' => $person['@id']);
						$entity['about']      = array('@id' => $person['@id']);
					}
					if ($breadcrumb) {
						$entity['breadcrumb'] = array('@id' => $breadcrumb['@id']);
					}
				}
			}
			unset($entity);
		}

			$faq = bbb_schema_faq_entity($post_id);
			if ($faq) {
				if (!empty($faq['@id'])) {
					$data = bbb_schema_without_entity_id($data, (string) $faq['@id']);
				}
				$data['bbb-faq'] = $faq;
				if (is_singular('bbb_boyfriend')) {
					$GLOBALS['bbb_fictional_boyfriend_faq_schema_added'] = true;
			}
		}

		return $data;
	},
	110
);

add_filter(
	'rank_math/json_ld',
	static function (array $data): array {
		$seen = array();

		foreach ($data as $key => $entity) {
			if (!is_array($entity) || empty($entity['@id'])) {
				continue;
			}

			$id = (string) $entity['@id'];
			if (isset($seen[$id])) {
				unset($data[$key]);
				continue;
			}

			$seen[$id] = true;
		}

		return $data;
	},
	PHP_INT_MAX
);

function bbb_schema_output_json_ld(): void {
	$graph = array();

	if (bbb_is_homepage_seo_context()) {
		if (empty($GLOBALS['bbb_organization_schema_added'])) {
			$graph[] = bbb_schema_org_entity();
		}
		if (empty($GLOBALS['bbb_website_schema_added'])) {
			$graph[] = bbb_schema_website_entity();
		}
	}

	if (is_singular('bbb_book') && empty($GLOBALS['bbb_book_schema_added'])) {
		if (empty($GLOBALS['bbb_organization_schema_added'])) {
			$graph[] = bbb_schema_org_entity();
		}
		$book = bbb_schema_single_book_entity();
		if ($book) {
			$graph[] = $book;
		}
		if (empty($GLOBALS['bbb_book_breadcrumb_schema_added'])) {
			$breadcrumb = bbb_schema_single_book_breadcrumb_entity((int) get_queried_object_id());
			if ($breadcrumb) {
				$graph[] = $breadcrumb;
			}
		}
	}

	$post_id = bbb_schema_current_post_id();
	if (is_singular('bbb_boyfriend') && $post_id > 0) {
		$person = empty($GLOBALS['bbb_fictional_boyfriend_person_schema_added']) ? bbb_schema_fictional_boyfriend_person_entity($post_id) : array();
		$source_book = empty($GLOBALS['bbb_fictional_boyfriend_book_schema_added']) ? bbb_schema_fictional_boyfriend_book_entity($post_id) : array();
		$series = empty($GLOBALS['bbb_fictional_boyfriend_series_schema_added']) ? bbb_schema_fictional_boyfriend_series_entity($post_id) : array();
		$breadcrumb = empty($GLOBALS['bbb_fictional_boyfriend_breadcrumb_schema_added']) ? bbb_schema_fictional_boyfriend_breadcrumb_entity($post_id) : array();
		$faq = empty($GLOBALS['bbb_fictional_boyfriend_faq_schema_added']) ? bbb_schema_faq_entity($post_id) : array();

		foreach (array($person, $source_book, $series, $breadcrumb, $faq) as $entity) {
			if ($entity) {
				$graph[] = $entity;
			}
		}
	}

	$post_id = bbb_schema_current_post_id();
	if ($post_id > 0 && empty($GLOBALS['bbb_book_review_schema_added'])) {
		$review = bbb_schema_book_review_entity($post_id);
		if ($review) {
			$graph[] = $review;
		}
	}

	if (empty($GLOBALS['bbb_book_list_schema_added'])) {
		$list = bbb_schema_book_list_entity();
		if ($list) {
			$graph[] = $list;
		}
	}

	if (!$graph) {
		return;
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo "\n" . '<script type="application/ld+json" class="bbb-schema">' . wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}
add_action('wp_head', 'bbb_schema_output_json_ld', 99);
