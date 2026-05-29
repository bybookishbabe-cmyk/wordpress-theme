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
			'title'       => 'romance book library — browse by trope & spice level | bybookishbabe',
			'description' => 'browse the full bybookishbabe romance library organized by trope, spice level, and mood. from dark romance to romantasy — find your next obsession.',
		),
		'book-reviews'              => array(
			'title'       => 'romance book reviews with tropes & spice ratings | bybookishbabe',
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
		'reader-quizes'             => array(
			'title'       => 'romance reader quizzes — find your next book & fictional boyfriend | bybookishbabe',
			'description' => "take bybookishbabe's romance reader quizzes. find your fictional boyfriend, your next read, and which trope matches your personality.",
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
		'dark-romance-books'        => array(
			'title'       => 'best dark romance books with morally gray men | bybookishbabe',
			'description' => 'the best dark romance books featuring morally gray men, obsessive love interests, and stories that will ruin you in the best way.',
		),
		'sports-romance-books'      => array(
			'title'       => 'the best sports romance books: ultimate guide (2026)',
			'description' => 'the best sports romance books ranked by spice — hockey romance, football romance, booktok favorites and underrated reads you need on your list.',
		),
		'enemies-to-lovers'        => array(
			'title'       => 'best enemies to lovers romance books | bybookishbabe',
			'description' => 'the best enemies to lovers romance books — slow burn tension, hate-to-love chemistry, and the trope that never gets old.',
		),
		'romantasy-books'           => array(
			'title'       => 'best romantasy books — dragons magic & romance | bybookishbabe',
			'description' => 'the best romantasy books featuring magic, dragons, fated mates, and men who would burn kingdoms for her. curated by bybookishbabe.',
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
			'title'       => 'best slow burn romance books — worth every agonizing page | bybookishbabe',
			'description' => 'the best slow burn romance books where the tension builds for hundreds of pages and the payoff is absolutely worth it.',
		),
		'morally-gray-men-romance-books' => array(
			'title'       => 'best morally gray men romance books — dark romance that will ruin you | bybookishbabe',
			'description' => 'the best romance books featuring morally gray men — anti-heroes, obsessive love interests, and dark romance that will permanently raise your standards.',
		),
		'shop'                      => array(
			'title'       => 'romance book digital downloads & guides | bybookishbabe shop',
			'description' => "shop bybookishbabe's digital romance reading guides, book lists, and downloads. everything a romance reader needs in one place.",
		),
	);

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
	'rank_math/frontend/robots',
	static function (array $robots): array {
		if ('come-in' !== bbb_route_seo_slug()) {
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
		if ('come-in' !== bbb_route_seo_slug()) {
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

function bbb_schema_book_entity(WP_Post $book): array {
	$author = function_exists('bbb_get_book_author')
		? bbb_get_book_author($book->ID)
		: bbb_schema_first_text_meta($book->ID, array('author'));
	$cover = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID) : '';
	$tropes = array();

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
		'name'  => get_the_title($book),
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

	return $entity;
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

function bbb_review_seo_filter_image(string $image): string {
	$post_id = (int) get_queried_object_id();
	$cover   = $post_id > 0 ? bbb_review_seo_cover_url($post_id) : '';

	return '' !== $cover ? $cover : $image;
}
add_filter('rank_math/opengraph/facebook/image', 'bbb_review_seo_filter_image', 120);
add_filter('rank_math/opengraph/twitter/image', 'bbb_review_seo_filter_image', 120);

function bbb_review_seo_add_rank_math_image($opengraph_image): void {
	$post_id = (int) get_queried_object_id();
	$cover   = $post_id > 0 ? bbb_review_seo_cover_url($post_id) : '';

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

	$description = bbb_schema_first_text_meta(
		$post_id,
		array('review_summary', 'review_excerpt', 'rank_math_description', 'description', 'excerpt')
	);
	if ('' === $description) {
		$description = get_the_excerpt($post_id);
	}

	$review = array(
		'@type'         => 'Review',
		'@id'           => get_permalink($post_id) . '#book-review',
		'url'           => get_permalink($post_id),
		'name'          => get_the_title($post_id),
		'datePublished' => get_the_date(DATE_W3C, $post_id),
		'dateModified'  => get_the_modified_date(DATE_W3C, $post_id),
		'author'        => array(
			'@type' => 'Organization',
			'name'  => 'ByBookishBabe',
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
		$review['reviewBody']  = wp_trim_words(wp_strip_all_tags((string) $description), 45, '');
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
		return array();
	}

	$books = function_exists('sss_article_books_for_post') ? sss_article_books_for_post($post_id) : bbb_schema_article_books($post_id);
	$books = array_values(array_filter($books, static fn($book): bool => $book instanceof WP_Post));
	if (count($books) < 2) {
		return array();
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
	if (!is_singular('post')) {
		return array();
	}

	$post = get_post($post_id);
	if (!$post instanceof WP_Post || 'post' !== $post->post_type) {
		return array();
	}

	$pairs = bbb_schema_faq_pairs_from_content((string) $post->post_content);
	if (!$pairs) {
		$pairs = bbb_schema_faq_pairs_from_rendered((string) $post->post_content);
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

add_filter(
	'rank_math/json_ld',
	static function (array $data): array {
		$post_id = bbb_schema_current_post_id();
		if ($post_id <= 0) {
			return $data;
		}

		$cover = bbb_review_seo_cover_url($post_id);
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
		} else {
			$list = bbb_schema_book_list_entity();
			if ($list) {
				$data['bbb-book-list'] = $list;
			}
		}

		$faq = bbb_schema_faq_entity($post_id);
		if ($faq) {
			$data['bbb-faq'] = $faq;
		}

		return $data;
	},
	110
);
