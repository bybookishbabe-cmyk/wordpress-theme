<?php
/**
 * Template Name: romance book moodboards
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$moodboards_library_css_path = get_theme_file_path('assets/css/sss-library.css');
wp_enqueue_style(
	'bbb-sss-library',
	get_theme_file_uri('assets/css/sss-library.css'),
	array('bbb-bookshelf-signup'),
	file_exists($moodboards_library_css_path) ? (string) filemtime($moodboards_library_css_path) : wp_get_theme()->get('Version')
);

$moodboards_css_path = get_theme_file_path('assets/css/romance-book-moodboards.css');
wp_enqueue_style(
	'bbb-romance-book-moodboards',
	get_theme_file_uri('assets/css/romance-book-moodboards.css'),
	array('bbb-sss-library'),
	file_exists($moodboards_css_path) ? (string) filemtime($moodboards_css_path) : wp_get_theme()->get('Version')
);

$moodboards_library_js_path = get_theme_file_path('assets/js/sss-library.js');
wp_enqueue_script(
	'bbb-sss-library',
	get_theme_file_uri('assets/js/sss-library.js'),
	array('bbb-supabase'),
	file_exists($moodboards_library_js_path) ? (string) filemtime($moodboards_library_js_path) : wp_get_theme()->get('Version'),
	false
);

$moodboards_title              = 'romance book moodboards — browse by trope & aesthetic';
$moodboards_description        = 'browse romance book moodboards by trope — dark romance, romantasy, sports romance and more. pin the aesthetic, save the book, find your next read.';
$moodboards_social_title       = 'romance book moodboards — browse by trope & aesthetic';
$moodboards_social_description = 'pin the aesthetic, save the book. browse dark romance, romantasy, sports romance and more — moodboards curated by bybookishbabe.';
$moodboards_image              = 'https://i.pinimg.com/originals/ed/db/b9/eddbb9f3fc84c9bca64ec7997c04e0d1.jpg';
$moodboards_canonical          = 'https://bybookishbabe.com/romance-book-moodboards/';

add_filter('pre_get_document_title', static fn(string $title): string => $moodboards_title, 100);
add_filter('rank_math/frontend/title', static fn(string $title): string => $moodboards_title, 100);
add_filter('rank_math/frontend/description', static fn(string $description): string => $moodboards_description, 100);
add_filter('rank_math/opengraph/facebook/title', static fn(string $title): string => $moodboards_social_title, 100);
add_filter('rank_math/opengraph/facebook/description', static fn(string $description): string => $moodboards_social_description, 100);
add_filter('rank_math/opengraph/twitter/title', static fn(string $title): string => $moodboards_social_title, 100);
add_filter('rank_math/opengraph/twitter/description', static fn(string $description): string => $moodboards_social_description, 100);
add_filter('rank_math/frontend/canonical', static fn(string $canonical): string => $moodboards_canonical, 100);
add_filter('rank_math/opengraph/facebook/url', static fn(string $url): string => $moodboards_canonical, 100);
add_filter('rank_math/opengraph/twitter/url', static fn(string $url): string => $moodboards_canonical, 100);
add_filter('rank_math/opengraph/type', static fn(string $type): string => 'website', 100);
add_filter('rank_math/opengraph/facebook/image', static fn(string $image): string => $moodboards_image, 100);
add_filter('rank_math/opengraph/twitter/image', static fn(string $image): string => $moodboards_image, 100);
add_filter(
	'rank_math/frontend/robots',
	static function (array $robots): array {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = 'index';
		$robots['follow'] = 'follow';

		return $robots;
	},
	100
);
add_filter(
	'wp_robots',
	static function (array $robots): array {
		unset($robots['noindex'], $robots['nofollow']);
		$robots['index']  = true;
		$robots['follow'] = true;

		return $robots;
	},
	100
);
add_action(
	'rank_math/opengraph/facebook',
	static function () use ($moodboards_social_title, $moodboards_social_description, $moodboards_image, $moodboards_canonical): void {
		remove_all_actions('rank_math/opengraph/facebook', 5);
		remove_all_actions('rank_math/opengraph/facebook', 10);
		remove_all_actions('rank_math/opengraph/facebook', 11);
		remove_all_actions('rank_math/opengraph/facebook', 12);
		remove_all_actions('rank_math/opengraph/facebook', 30);

		printf('<meta property="og:type" content="website">%s', "\n");
		printf('<meta property="og:title" content="%s">%s', esc_attr($moodboards_social_title), "\n");
		printf('<meta property="og:description" content="%s">%s', esc_attr($moodboards_social_description), "\n");
		printf('<meta property="og:image" content="%s">%s', esc_url($moodboards_image), "\n");
		printf('<meta property="og:url" content="%s">%s', esc_url($moodboards_canonical), "\n");
	},
	4
);
add_action(
	'wp_head',
	static function () use ($moodboards_canonical): void {
		printf('<link rel="canonical" href="%s">%s', esc_url($moodboards_canonical), "\n");

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'WebPage',
			'name'        => 'Romance Book Moodboards — Browse by Trope & Aesthetic',
			'url'         => $moodboards_canonical,
			'description' => 'browse romance book moodboards by trope, pin the aesthetic, save the book, and find your next read.',
			'keywords'    => array(
				'romance book moodboards',
				'dark romance',
				'romantasy',
				'paranormal romance',
				'sports romance',
				'book aesthetics',
				'book moodboard',
				'Pinterest books',
				'find your next read',
				'romance by trope',
			),
			'publisher'   => array(
				'@type' => 'Person',
				'name'  => 'ByBookishBabe',
				'url'   => 'https://bybookishbabe.com',
			),
		);

		echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
	},
	30
);

function bbb_moodboards_find_book(string $title): ?WP_Post {
	$title = trim($title);
	if ('' === $title) {
		return null;
	}

	$post_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
	foreach ($post_types as $post_type) {
		$slug_match = get_page_by_path(sanitize_title($title), OBJECT, $post_type);
		if ($slug_match instanceof WP_Post) {
			return $slug_match;
		}
	}

	$query = new WP_Query(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			's'                      => $title,
			'posts_per_page'         => 12,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	foreach ($query->posts as $post) {
		if ($post instanceof WP_Post && 0 === strcasecmp($title, get_the_title($post))) {
			return $post;
		}
	}

	return !empty($query->posts) && $query->posts[0] instanceof WP_Post ? $query->posts[0] : null;
}

function bbb_moodboards_fallback_books(int $limit = 6): array {
	$post_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
	if (empty($post_types)) {
		return array();
	}

	$books = get_posts(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	return array_values(array_filter($books, static fn($book): bool => $book instanceof WP_Post));
}

function bbb_moodboards_book_terms(WP_Post $book, array $extra_terms = array()): string {
	$terms = array_map('sanitize_title', $extra_terms);
	if (function_exists('sss_book_data')) {
		$data = sss_book_data($book);
		foreach ((array) ($data['tropes'] ?? array()) as $trope) {
			$terms[] = sanitize_title((string) ($trope['handle'] ?? $trope['name'] ?? ''));
		}
		$terms[] = sanitize_title((string) ($data['shelf'] ?? ''));
	}

	return implode(' ', array_values(array_filter(array_unique($terms))));
}

function bbb_moodboards_book_trope_labels(WP_Post $book): array {
	if (!function_exists('sss_book_data')) {
		return array();
	}

	$data   = sss_book_data($book);
	$labels = array();
	foreach ((array) ($data['tropes'] ?? array()) as $trope) {
		$name  = trim((string) ($trope['name'] ?? ''));
		$emoji = trim((string) ($trope['emoji'] ?? ''));
		if ('' === $name) {
			continue;
		}

		$slug     = trim((string) ($trope['slug'] ?? $trope['handle'] ?? ''));
		$labels[] = function_exists('bbb_trope_label_html') ? bbb_trope_label_html($name, $emoji, $slug) : esc_html(trim(($emoji ?: '') . ' ' . $name));
	}

	return array_slice(array_values(array_filter(array_unique($labels))), 0, 3);
}

function bbb_moodboards_trope_label_html(string $trope): string {
	$trope = trim(wp_strip_all_tags($trope));
	if ('' === $trope) {
		return '';
	}

	return function_exists('bbb_trope_label_html') ? bbb_trope_label_html($trope) : esc_html($trope);
}

function bbb_moodboards_post_is_review(int $post_id, WP_Post $book): bool {
	if (function_exists('bbb_review_index_has_review_flag') && bbb_review_index_has_review_flag($post_id)) {
		return true;
	}

	$review_terms = taxonomy_exists('book_review_category') ? get_the_terms($post_id, 'book_review_category') : false;
	if ($review_terms && !is_wp_error($review_terms)) {
		return true;
	}

	$connected_ids = function_exists('bbb_article_book_connection_selected_ids') ? bbb_article_book_connection_selected_ids($post_id) : array();
	if (in_array((int) $book->ID, $connected_ids, true)) {
		$title_and_slug = strtolower((string) get_the_title($post_id) . ' ' . get_post_field('post_name', $post_id));
		return str_contains($title_and_slug, 'review');
	}

	return false;
}

function bbb_moodboards_review_url(WP_Post $book): string {
	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 250,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	foreach ($posts as $post) {
		if (!$post instanceof WP_Post) {
			continue;
		}

		$connected_ids = function_exists('bbb_article_book_connection_selected_ids') ? bbb_article_book_connection_selected_ids($post->ID) : array();
		if (in_array((int) $book->ID, $connected_ids, true) && bbb_moodboards_post_is_review($post->ID, $book)) {
			return get_permalink($post);
		}
	}

	$book_title = strtolower((string) get_the_title($book));
	foreach ($posts as $post) {
		if (!$post instanceof WP_Post) {
			continue;
		}

		$haystack = strtolower((string) get_the_title($post) . ' ' . get_post_field('post_name', $post->ID));
		if (str_contains($haystack, $book_title) && str_contains($haystack, 'review')) {
			return get_permalink($post);
		}
	}

	return '';
}

function bbb_moodboards_pin_url_from_book(?WP_Post $book): string {
	if (!$book instanceof WP_Post) {
		return '';
	}

	$pin_url = trim((string) get_post_meta($book->ID, '_bbb_moodboard_pin_url', true));
	if ('' === $pin_url) {
		return '';
	}

	return function_exists('bbb_normalize_moodboard_pin_url') ? bbb_normalize_moodboard_pin_url($pin_url) : esc_url_raw($pin_url);
}

function bbb_moodboards_group_pin_url(array $group, array $books): string {
	foreach ($books as $book) {
		$pin_url = bbb_moodboards_pin_url_from_book($book instanceof WP_Post ? $book : null);
		if ('' !== $pin_url) {
			return $pin_url;
		}
	}

	$fallback_pin = (string) ($group['pin'] ?? '');
	return function_exists('bbb_normalize_moodboard_pin_url') ? bbb_normalize_moodboard_pin_url($fallback_pin) : esc_url_raw($fallback_pin);
}

function bbb_moodboards_embed_title(array $group): string {
	$label = trim((string) ($group['label'] ?? 'romance moodboard'));
	if ('' === $label) {
		return 'romance book moodboard';
	}

	return str_contains($label, 'moodboard')
		? str_replace('moodboard', 'book moodboard', $label)
		: $label . ' book moodboard';
}

function bbb_moodboards_render_book_meta(array $tropes, string $review_url = ''): void {
	if (empty($tropes) && '' === $review_url) {
		return;
	}
	?>
	<div class="bbb-moodboards__bookMeta">
		<?php if (!empty($tropes)) : ?>
			<div class="bbb-moodboards__bookTropes">
				<?php foreach ($tropes as $trope) : ?>
					<span><?php echo wp_kses_post(str_contains((string) $trope, '<') ? (string) $trope : bbb_moodboards_trope_label_html((string) $trope)); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ('' !== $review_url) : ?>
			<a class="bbb-moodboards__reviewLink" href="<?php echo esc_url($review_url); ?>">see review</a>
		<?php endif; ?>
	</div>
	<?php
}

function bbb_moodboards_render_book(WP_Post $book, array $terms = array()): void {
	$term_value = bbb_moodboards_book_terms($book, $terms);
	?>
	<div class="bbb-moodboards__bookShell" data-mood-book data-mood-terms="<?php echo esc_attr($term_value); ?>">
		<?php get_template_part('template-parts/library/book-card', null, array('post' => $book)); ?>
		<?php bbb_moodboards_render_book_meta(bbb_moodboards_book_trope_labels($book), bbb_moodboards_review_url($book)); ?>
	</div>
	<?php
}

function bbb_moodboards_render_manual_book(array $book, array $terms = array()): void {
	$title   = trim((string) ($book['title'] ?? ''));
	$author  = trim((string) ($book['author'] ?? ''));
	$cover   = trim((string) ($book['cover'] ?? ''));
	$spice   = max(0, min(5, (int) ($book['spice'] ?? 0)));
	$tropes  = array_values(array_filter(array_map('trim', (array) ($book['tropes'] ?? array()))));
	$review_url = trim((string) ($book['review_url'] ?? ''));
	$handle  = sanitize_title((string) ($book['handle'] ?? $title));
	$why     = trim((string) ($book['why'] ?? ''));
	$terms[] = sanitize_title(implode(' ', $tropes));
	$term_value = implode(' ', array_values(array_filter(array_unique(array_map('sanitize_title', $terms)))));
	?>
	<div class="bbb-moodboards__bookShell" data-mood-book data-mood-terms="<?php echo esc_attr($term_value); ?>">
		<button
			type="button"
			class="sss-lib__book bbb-moodboards__manualBook"
			data-handle="<?php echo esc_attr($handle); ?>"
			data-title="<?php echo esc_attr($title); ?>"
			data-author="<?php echo esc_attr($author); ?>"
			data-cover="<?php echo esc_url($cover); ?>"
			data-amazon=""
			data-bookshop=""
			data-shelf="dark romance"
			data-private-shelf="false"
			data-spice="<?php echo esc_attr((string) $spice); ?>"
			data-tropes="<?php echo esc_attr(implode(', ', $tropes)); ?>"
			data-tropes-display="<?php echo esc_attr(implode(', ', $tropes)); ?>"
			data-trope-urls=""
			data-why="<?php echo esc_attr($why); ?>"
			data-newsletter=""
			data-mini="<?php echo esc_attr('pin-backed moodboard placeholder until the full library card is added.'); ?>"
			data-series=""
			data-series-name=""
			data-series-number=""
			data-tension=""
			data-damage=""
			data-yearning=""
			data-boyfriend=""
			data-boyfriend-name=""
			data-reread="false"
			data-standalone="false"
			data-ku="false"
			data-darkness=""
		>
			<div class="sss-lib__coverWrap">
				<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
					<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
					<span class="sss-lib__heartLabel" data-heart-label>save</span>
				</span>
				<?php if ($spice > 0) : ?>
					<div class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $spice)); ?></div>
				<?php endif; ?>
				<?php if ('' !== $cover) : ?>
					<img class="sss-lib__cover" src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
				<?php else : ?>
					<div class="bbb-moodboards__manualCover">
						<span>pin-backed pick</span>
						<strong><?php echo esc_html($title); ?></strong>
					</div>
				<?php endif; ?>
			</div>
			<div class="sss-lib__under">
				<div class="sss-lib__name"><?php echo esc_html($title); ?></div>
				<div class="sss-lib__author"><?php echo esc_html($author); ?></div>
			</div>
		</button>
		<?php bbb_moodboards_render_book_meta($tropes, $review_url); ?>
	</div>
	<?php
}

function bbb_moodboards_book_shelf_slug(WP_Post $book): string {
	$shelf = '';
	if (function_exists('sss_book_data')) {
		$data  = sss_book_data($book);
		$shelf = trim((string) ($data['shelf'] ?? ''));
	}

	if ('' === $shelf) {
		$shelf = trim((string) get_post_meta($book->ID, '_bbb_shelf_name', true));
	}

	return sanitize_title('' !== $shelf ? $shelf : 'romance');
}

function bbb_moodboards_group_url_for_term(string $term): string {
	$urls = array(
		'dark-romance'       => '/dark-romance-books/',
		'paranormal-romance' => '/paranormal-romance-books/',
		'romantasy'          => '/romantasy-books/',
		'sports-romance'     => '/sports-romance-books/',
	);

	return $urls[$term] ?? '';
}

function bbb_moodboards_apply_auto_books(array $groups): array {
	$post_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
	if (empty($post_types)) {
		return $groups;
	}

	$existing_titles = array();
	foreach ($groups as $group) {
		foreach ((array) ($group['books'] ?? array()) as $title) {
			$existing_titles[] = sanitize_title((string) $title);
		}
	}
	$existing_titles = array_unique(array_filter($existing_titles));

	$books = get_posts(
		array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'meta_key'               => '_bbb_moodboard_pin_url',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);

	foreach ($books as $book) {
		if (!$book instanceof WP_Post || '' === bbb_moodboards_pin_url_from_book($book)) {
			continue;
		}

		$title = get_the_title($book);
		if (in_array(sanitize_title($title), $existing_titles, true)) {
			continue;
		}

		$term       = bbb_moodboards_book_shelf_slug($book);
		$shelf_name = ucwords(str_replace('-', ' ', $term));
		$merged     = false;

		foreach ($groups as $index => $group) {
			if (!empty($group['preview_only']) || !empty($group['pin']) || !in_array($term, (array) ($group['terms'] ?? array()), true)) {
				continue;
			}

			$groups[$index]['books'][] = $title;
			$merged                    = true;
			break;
		}

		if (!$merged) {
			$groups[] = array(
				'label' => strtolower($shelf_name) . ' moodboard',
				'title' => strtolower($title) . ' moodboard',
				'copy'  => 'a reader-saved board pulled straight from the book record, ready to pin, save, and open.',
				'terms' => array($term),
				'books' => array($title),
				'url'   => bbb_moodboards_group_url_for_term($term),
			);
		}
	}

	return $groups;
}

function bbb_moodboards_render_group_inner(array $group): void {
	$books = array_values(
		array_filter(
			array_map('bbb_moodboards_find_book', (array) ($group['books'] ?? array())),
			static fn($book): bool => $book instanceof WP_Post
		)
	);
	if (empty($group['pin'])) {
		$books = array_values(
			array_filter(
				$books,
				static fn(WP_Post $book): bool => '' !== bbb_moodboards_pin_url_from_book($book)
			)
		);
	}
	$manual_book = array();
	if (empty($books) && !empty($group['fallback_book']) && is_array($group['fallback_book'])) {
		$manual_book = $group['fallback_book'];
	} elseif (empty($books)) {
		$books = bbb_moodboards_fallback_books(3);
	}
	$book_count = count($books) + (!empty($manual_book) ? 1 : 0);
	$pin_url    = bbb_moodboards_group_pin_url($group, $books);
	?>
	<div class="bbb-moodboards__copy">
		<p class="bbb-moodboards__eyebrow"><?php echo esc_html((string) ($group['label'] ?? 'moodboard')); ?></p>
		<h2>
			<?php if (!empty($group['url'])) : ?>
				<a href="<?php echo esc_url(home_url((string) $group['url'])); ?>"><?php echo esc_html((string) ($group['title'] ?? 'moodboard')); ?></a>
			<?php else : ?>
				<?php echo esc_html((string) ($group['title'] ?? 'moodboard')); ?>
			<?php endif; ?>
		</h2>
		<p><?php echo esc_html((string) ($group['copy'] ?? '')); ?></p>
	</div>
	<div class="bbb-moodboards__pinPanel" aria-label="pinterest moodboard">
		<?php if ('' !== $pin_url) : ?>
			<iframe
				class="bbb-moodboards__pinEmbed"
				src="<?php echo esc_url($pin_url); ?>"
				height="714"
				width="345"
				frameborder="0"
				scrolling="no"
				loading="lazy"
				title="<?php echo esc_attr(bbb_moodboards_embed_title($group)); ?>"
			></iframe>
		<?php else : ?>
			<div class="bbb-moodboards__pinFrame">
				<span>pin embed goes here</span>
				<strong>save the aesthetic</strong>
				<small>drop the pinterest pin url into this board when it is ready.</small>
			</div>
		<?php endif; ?>
	</div>
	<div class="bbb-moodboards__books">
		<?php if ($book_count > 1) : ?>
			<p class="bbb-moodboards__swipeCue">swipe me</p>
		<?php endif; ?>
		<?php foreach ($books as $book_post) : ?>
			<?php bbb_moodboards_render_book($book_post, (array) ($group['terms'] ?? array())); ?>
		<?php endforeach; ?>
		<?php if (!empty($manual_book)) : ?>
			<?php bbb_moodboards_render_manual_book($manual_book, (array) ($group['terms'] ?? array())); ?>
		<?php endif; ?>
	</div>
	<?php
}

$featured_groups = array(
	array(
		'label' => 'weekly obsession',
		'title' => 'one board for the book everyone is spiraling over',
		'copy'  => 'save the visual, then click the book to open the breakdown.',
		'terms' => array('dark-romance'),
		'books' => array('Eternal'),
		'pin'   => 'https://assets.pinterest.com/ext/embed.html?id=999728817300895309',
		'preview_only' => true,
		'fallback_book' => array(
			'title'  => 'eternal',
			'author' => 'Eva Simmons',
			'cover'  => 'https://s3.amazonaws.com/romance.io/books/large/6728c3c4550993069e34bb92.jpg',
			'spice'  => 4,
			'tropes' => array('dark romance', 'morally gray'),
			'why'    => 'for the readers saving the moodboard first and waiting for the full library card next.',
		),
	),
	array(
		'label' => 'dark romance moodboard',
		'title' => 'dangerous energy, soft landing optional',
		'copy'  => 'the darker pins, the messier men, the books you read with one eye narrowed.',
		'terms' => array('dark-romance'),
		'books' => array('My Dreadful Darling'),
		'pin'   => 'https://assets.pinterest.com/ext/embed.html?id=999728817300188239',
	),
	array(
		'label' => 'dark romance moodboard',
		'title' => 'power plays, pretty threats, and one wrong move',
		'copy'  => 'a darker shelf board for the books that feel like silk, smoke, and someone dangerous deciding you are theirs.',
		'terms' => array('dark-romance'),
		'books' => array('Twisted Pawn'),
		'pin'   => 'https://assets.pinterest.com/ext/embed.html?id=999728817299979993',
	),
	array(
		'label' => 'dark romance moodboard',
		'title' => 'old halls, sharp edges, and no easy way out',
		'copy'  => 'a darker shelf board for the books that feel like a locked door, a dare, and a warning you ignore anyway.',
		'terms' => array('dark-romance'),
		'books' => array('Inescapable Darkness'),
		'pin'   => 'https://assets.pinterest.com/ext/embed.html?id=999728817299742459',
	),
	array(
		'label' => 'paranormal romance moodboard',
		'title' => 'fated shadows, wicked pull, and no human rules',
		'copy'  => 'for the books with supernatural heat, enemies who feel inevitable, and darkness that bites back.',
		'terms' => array('paranormal-romance'),
		'books' => array('Satanic Shadows'),
		'pin'   => 'https://assets.pinterest.com/ext/embed.html?id=999728817299245280',
		'url'   => '/paranormal-romance-books/',
	),
	array(
		'label' => 'romantasy moodboard',
		'title' => 'ruined magic, captive hearts, and fate with teeth',
		'copy'  => 'for fantasy romance that feels ancient, obsessive, and just dangerous enough to keep reading.',
		'terms' => array('romantasy'),
		'books' => array('Runebreaker'),
		'pin'   => 'https://assets.pinterest.com/ext/embed.html?id=999728817298993144',
		'url'   => '/romantasy-books/',
	),
	array(
		'label' => 'sports romance moodboard',
		'title' => 'game day tension, locker room looks, and one more play',
		'copy'  => 'for the shelf with competitive boys, soft landings, and the kind of tension that keeps score.',
		'terms' => array('sports-romance'),
		'books' => array('The Right Move', 'Rewind It Back'),
		'url'   => '/sports-romance-books/',
	),
);

$featured_groups = bbb_moodboards_apply_auto_books($featured_groups);

$dark_romance_groups = array_values(
	array_filter(
		$featured_groups,
		static fn(array $group): bool => empty($group['preview_only']) && in_array('dark-romance', (array) ($group['terms'] ?? array()), true)
	)
);
$non_dark_romance_groups = array_values(
	array_filter(
		$featured_groups,
		static fn(array $group): bool => empty($group['preview_only']) && !in_array('dark-romance', (array) ($group['terms'] ?? array()), true)
	)
);

$filters = array(
	'all'                => 'all',
	'dark-romance'       => '🖤 dark romance',
	'paranormal-romance' => '🌙 paranormal romance',
	'romantasy'          => '🐉 romantasy',
	'sports-romance'     => '🏒 sports romance',
);

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-moodboards" data-moodboards data-sss-lib="public">
		<header class="bbb-moodboards__hero">
			<p class="bbb-moodboards__kicker">romance book moodboards</p>
			<h1>romance book moodboards — pin the feeling, save the book</h1>
			<p class="bbb-moodboards__intro">browse romance book moodboards by trope, save the aesthetic, and find your next read.</p>
			<div class="bbb-moodboards__heroActions" aria-label="moodboard actions">
				<a class="bbb-moodboards__button bbb-moodboards__button--pink" href="#moodboard-grid">browse boards</a>
				<a class="bbb-moodboards__button" href="<?php echo esc_url(home_url('/library/')); ?>">open library</a>
				<a class="bbb-moodboards__button" href="<?php echo esc_url(home_url('/what-to-read-next/')); ?>">find your read</a>
			</div>
		</header>

		<?php if (!empty($featured_groups[0])) : ?>
			<section class="bbb-moodboards__preview" id="moodboard-preview" aria-label="romance moodboard preview">
				<div class="bbb-moodboards__previewIntro">
					<p class="bbb-moodboards__eyebrow">preview</p>
					<h2>start with the visual.</h2>
					<p>each moodboard gives you the aesthetic first, then the book, tropes, save button, and review link when one exists.</p>
				</div>
				<article class="bbb-moodboards__previewCard">
					<?php bbb_moodboards_render_group_inner($featured_groups[0]); ?>
				</article>
			</section>
		<?php endif; ?>

		<section class="bbb-moodboards__how">
			<h2>how it works</h2>
			<div class="bbb-moodboards__steps">
				<div><span>01</span><strong>pick a shelf</strong><p>use the aesthetic menu to jump into the kind of romance you want.</p></div>
				<div><span>02</span><strong>save the visual</strong><p>pin the moodboard or tap the heart to keep the book on your shelf.</p></div>
				<div><span>03</span><strong>open the book</strong><p>click the cover for spice, tropes, ratings, and read links.</p></div>
			</div>
		</section>

		<section class="bbb-moodboards__aesthetic" aria-label="the aesthetic menu">
			<div class="bbb-moodboards__aestheticHead">
				<p class="bbb-moodboards__eyebrow">the aesthetic</p>
				<h2>jump to your shelf.</h2>
			</div>
			<nav class="bbb-moodboards__filters" aria-label="filter romance moodboards by shelf">
				<?php foreach ($filters as $filter => $label) : ?>
					<button class="bbb-moodboards__filter<?php echo 'all' === $filter ? ' is-active' : ''; ?>" type="button" data-mood-filter="<?php echo esc_attr($filter); ?>" aria-pressed="<?php echo 'all' === $filter ? 'true' : 'false'; ?>">
						<?php echo esc_html($label); ?>
					</button>
				<?php endforeach; ?>
			</nav>
		</section>

		<div class="bbb-moodboards__wrap" id="moodboard-grid">
			<?php if (!empty($dark_romance_groups)) : ?>
				<section class="bbb-moodboards__shelfGroup" data-mood-group data-mood-terms="dark-romance">
					<div class="bbb-moodboards__shelfHeader">
						<p class="bbb-moodboards__eyebrow">dark romance shelf</p>
						<h2><a href="<?php echo esc_url(home_url('/dark-romance-books/')); ?>">one shelf. multiple spirals.</a></h2>
						<p>swipe through the darker boards, then save the book that fits your current obsession.</p>
						<p class="bbb-moodboards__swipeCue bbb-moodboards__swipeCue--rail">swipe me</p>
					</div>
					<div class="bbb-moodboards__rail" aria-label="dark romance shelf moodboards">
						<?php foreach ($dark_romance_groups as $group) : ?>
							<article class="bbb-moodboards__slide">
								<?php bbb_moodboards_render_group_inner($group); ?>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php foreach ($non_dark_romance_groups as $group) : ?>
				<section class="bbb-moodboards__group" data-mood-group data-mood-terms="<?php echo esc_attr(implode(' ', $group['terms'])); ?>">
					<?php bbb_moodboards_render_group_inner($group); ?>
				</section>
			<?php endforeach; ?>
		</div>

		<p class="bbb-moodboards__empty" data-mood-empty hidden>no boards on that shelf yet. try all.</p>
	</section>

	<?php get_template_part('template-parts/library/library-modal'); ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
	const root = document.querySelector('[data-moodboards]');
	if (!root) return;

	const filters = Array.from(root.querySelectorAll('[data-mood-filter]'));
	const groups = Array.from(root.querySelectorAll('[data-mood-group]'));
	const empty = root.querySelector('[data-mood-empty]');
	const grid = root.querySelector('#moodboard-grid');

	filters.forEach(function (button) {
		button.addEventListener('click', function () {
			const filter = button.getAttribute('data-mood-filter') || 'all';
			let shown = 0;

			filters.forEach(function (item) {
				const active = item === button;
				item.classList.toggle('is-active', active);
				item.setAttribute('aria-pressed', active ? 'true' : 'false');
			});

			groups.forEach(function (group) {
				const terms = (group.getAttribute('data-mood-terms') || '').split(/\s+/);
				const show = filter === 'all' || terms.indexOf(filter) !== -1;

				group.querySelectorAll('[data-mood-book]').forEach(function (book) {
					book.hidden = !show;
				});

				group.hidden = !show;
				if (show) shown++;
			});

			if (empty) empty.hidden = shown > 0;
			if (grid) {
				window.setTimeout(function () {
					grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}, 80);
			}
		});
	});
});
</script>

<?php get_footer(); ?>
