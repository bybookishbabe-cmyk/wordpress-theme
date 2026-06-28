<?php
/**
 * Single book page prototype.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_enqueue_css('bbb-sss-library', 'assets/css/sss-library.css', array('bbb-bookshelf-signup'));
bbb_enqueue_css('bbb-book-breakdown-page', 'assets/css/book-breakdown-page.css', array('bbb-sss-library'));
wp_add_inline_style('bbb-book-breakdown-page', 'body.single-bbb_book,body.single-bbb_book.gradient,body.single-bbb_book #MainContent,body.single-bbb_book .content-for-layout,body.single-bbb_book .sss-book-page{background:#070707!important;background-image:none!important;background-size:auto!important;}body.single-bbb_book::before,body.single-bbb_book::after,body.single-bbb_book #MainContent::before,body.single-bbb_book #MainContent::after,body.single-bbb_book .content-for-layout::before,body.single-bbb_book .content-for-layout::after,body.single-bbb_book .sss-book-page::before,body.single-bbb_book .sss-book-page::after{background-image:none!important;}');
bbb_enqueue_css('bbb-society-content-cta', 'assets/css/society-content-cta.css', array('bbb-book-breakdown-page'));
bbb_enqueue_css('bbb-fictional-boyfriends', 'assets/css/fictional-boyfriends.css', array('bbb-book-breakdown-page'));
bbb_enqueue_js('bbb-fictional-boyfriends', 'assets/js/fictional-boyfriends.js', array(), true);
bbb_enqueue_js('bbb-book-page-rating', 'assets/js/book-page-rating.js', array('bbb-sss-library'), true);
wp_enqueue_script('bbb-pinterest-pinit', 'https://assets.pinterest.com/js/pinit.js', array(), null, true);

get_header();

if (!have_posts()) {
	get_footer();
	return;
}

the_post();

$book_id = get_the_ID();

$book      = get_post();
$data      = $book instanceof WP_Post && function_exists('sss_book_data') ? sss_book_data($book) : array();
$is_locked = function_exists('sss_book_is_private') && sss_book_is_private($book_id) && !(function_exists('bbb_reader_is_society') && bbb_reader_is_society());

foreach (array('cover', 'amazon', 'bookshop', 'newsletter') as $url_key) {
	if (isset($data[$url_key]) && function_exists('bbb_normalize_url_value')) {
		$data[$url_key] = bbb_normalize_url_value($data[$url_key]);
	}
}

$title         = (string) ($data['title'] ?? get_the_title());
$title         = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($title) : $title;
$author        = (string) ($data['author'] ?? '');
$author        = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($author) : $author;
$series_name   = (string) ($data['series_name'] ?? '');
$series_name   = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($series_name) : $series_name;
$series_handle = (string) ($data['series_handle'] ?? '');
$series_number = (string) ($data['series_number'] ?? '');
$mini          = (string) ($data['mini'] ?? '');
$why           = (string) ($data['why'] ?? '');
$spice_count   = max(0, (int) ($data['spice'] ?? 0));
$ku            = !empty($data['ku']);
$reread        = !empty($data['reread']) && 'false' !== (string) $data['reread'];
$boyfriend_label = trim((string) (!empty($data['boyfriend_name']) ? $data['boyfriend_name'] : ($data['boyfriend'] ?? '')));
$boyfriend_profile = function_exists('bbb_fictional_boyfriend_for_book') && '' !== $boyfriend_label
	? bbb_fictional_boyfriend_for_book($book_id, $boyfriend_label)
	: null;
$boyfriend_profile_url = $boyfriend_profile instanceof WP_Post ? get_permalink($boyfriend_profile) : '';
$boyfriend_profile_image = $boyfriend_profile instanceof WP_Post ? (string) get_the_post_thumbnail_url($boyfriend_profile, 'thumbnail') : '';
$book_permalink = (string) get_permalink($book_id);
$book_cover_url = (string) ($data['cover'] ?? '');
$notes_url      = function_exists('bbb_page_url') ? bbb_page_url('my-notes') : home_url('/my-notes/');
$book_pin_title = trim(sprintf('%1$s%2$s', $title, $author ? ' by ' . $author : ''));
$book_pin_description = trim(
	sprintf(
		'Book details for %1$s%2$s: tropes, spice level, reader notes, quotes, and where to read.%3$s',
		$title,
		$author ? ' by ' . $author : '',
		$spice_count > 0 ? ' Spice level: ' . $spice_count . '/5.' : ''
	)
);
$book_pin_save_url = $book_cover_url
	? add_query_arg(
		array(
			'url'         => $book_permalink,
			'media'       => $book_cover_url,
			'title'       => $book_pin_title,
			'description' => $book_pin_description,
		),
		'https://www.pinterest.com/pin/create/button/'
	)
	: '';
$book_review_post = null;
if (function_exists('bbb_article_book_connections_posts_for_book')) {
	$review_candidates = bbb_article_book_connections_posts_for_book($book_id);

	foreach ($review_candidates as $review_candidate) {
		if (!$review_candidate instanceof WP_Post || 'publish' !== $review_candidate->post_status) {
			continue;
		}

		$review_handle = strtolower(trim((string) get_post_meta($review_candidate->ID, '_shopify_blog_handle', true)));
		$review_terms = taxonomy_exists('book_review_category') ? get_the_terms($review_candidate, 'book_review_category') : false;
		$review_title_slug = strtolower((string) $review_candidate->post_name . ' ' . get_the_title($review_candidate));
		$is_review = has_category('book-reviews', $review_candidate)
			|| 'book-reviews' === $review_handle
			|| ($review_terms && !is_wp_error($review_terms))
			|| str_contains($review_title_slug, 'review');

		if ($is_review) {
			$book_review_post = $review_candidate;
			break;
		}
	}
}

$book_page_meta = static function (string $key) use ($book_id): string {
	$value = get_post_meta($book_id, $key, true);

	return is_scalar($value) ? trim((string) $value) : '';
};

$reader_notes = array(
	'verdict'          => $book_page_meta('_bbb_verdict'),
	'vibe_description' => $book_page_meta('_bbb_vibe_description'),
	'spice_words'      => $book_page_meta('_bbb_spice_words'),
	'read_this_if'     => $book_page_meta('_bbb_read_this_if'),
	'skip_this_if'     => $book_page_meta('_bbb_skip_this_if'),
	'content_warnings' => $book_page_meta('_bbb_content_warnings'),
	'standalone_hea'   => $book_page_meta('_bbb_standalone_hea'),
);
	$reader_notes['vibe_description'] = function_exists('bbb_bookreview_clean_vibe_text')
		? bbb_bookreview_clean_vibe_text($reader_notes['vibe_description'])
		: trim(preg_replace('/\s*🌶.*$/u', '', $reader_notes['vibe_description']) ?? $reader_notes['vibe_description']);
	$has_reader_notes = '' !== implode('', array_filter($reader_notes)) || $spice_count > 0;
	$spicy_chapters = function_exists('bbb_book_spicy_chapters') ? bbb_book_spicy_chapters($book_id) : array_values(
		array_filter(
			array_map(
				static fn(string $chapter): string => trim(wp_strip_all_tags($chapter)),
				preg_split('/\r\n|\r|\n/', $book_page_meta('_bbb_spicy_chapters')) ?: array()
			),
			static fn(string $chapter): bool => '' !== $chapter
		)
	);
	$spicy_chapter_items = $spicy_chapters ?: array('coming soon...');

$rating_dots = static function ($value): string {
	$count = max(0, min(5, (int) $value));
	$html  = '';

	for ($i = 1; $i <= 5; $i++) {
		$html .= '<span class="sss-book-page__dot' . ($i <= $count ? ' is-filled' : '') . '"></span>';
	}

	$html .= '<span class="sss-book-page__score">' . esc_html((string) $count) . '/5</span>';

	return $html;
};

$tropes = array();
foreach ((array) ($data['tropes'] ?? array()) as $trope) {
	$name = trim((string) ($trope['name'] ?? ''));
	if ('' === $name) {
		continue;
	}

	$handle   = sanitize_title((string) ($trope['handle'] ?? $name));
	$tropes[] = array(
		'label' => function_exists('bbb_trope_label') ? bbb_trope_label($name, $trope['emoji'] ?? '') : trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . $name),
		'html'  => function_exists('bbb_trope_label_html') ? bbb_trope_label_html($name, $trope['emoji'] ?? '', $handle) : esc_html(trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . $name)),
		'name'  => $name,
		'url'   => function_exists('bbb_trope_page_url') ? bbb_trope_page_url($name, $handle) : home_url('/' . $handle . '-books/'),
	);
}

$related_books = array();
if (function_exists('bbb_books_like_recommendations')) {
	foreach (bbb_books_like_recommendations($book_id) as $related) {
		if (count($related_books) >= 3 || empty($related['id'])) {
			continue;
		}

		$related_post = get_post((int) $related['id']);
		if (!$related_post instanceof WP_Post) {
			continue;
		}

		$related_data    = function_exists('sss_book_data') ? sss_book_data($related_post) : array();
		$related_tropes  = (array) ($related_data['tropes'] ?? array());
		$first_trope     = $related_tropes[0] ?? array();
		$first_trope_name = (string) ($first_trope['name'] ?? '');
		$first_trope_html = '' !== $first_trope_name && function_exists('bbb_trope_label_html')
			? bbb_trope_label_html($first_trope_name, $first_trope['emoji'] ?? '', (string) ($first_trope['handle'] ?? $first_trope_name))
			: esc_html($first_trope_name);
		$related_cover    = (string) ($related_data['cover'] ?? '');
		if ('' !== $related_cover && function_exists('bbb_normalize_url_value')) {
			$related_cover = bbb_normalize_url_value($related_cover);
		}
		$related_shelf      = (string) ($related_data['shelf'] ?? '');
		$related_shelf_slug = sanitize_title($related_shelf);
		$related_shelf_emoji = '';
		$related_shelf_terms = get_the_terms($related_post->ID, 'bbb_shelf');
		if ($related_shelf_terms && !is_wp_error($related_shelf_terms)) {
			$related_shelf_term = $related_shelf_terms[0];
			if ($related_shelf_term instanceof WP_Term) {
				$related_shelf      = '' !== $related_shelf ? $related_shelf : $related_shelf_term->name;
				$related_shelf_slug = $related_shelf_term->slug;
				$related_shelf_emoji = function_exists('bbb_book_taxonomy_term_emoji')
					? bbb_book_taxonomy_term_emoji($related_shelf_term)
					: (string) get_term_meta($related_shelf_term->term_id, 'shelf_emoji', true);
			}
		}
		$related_shelf_html = '' !== trim($related_shelf) && function_exists('bbb_trope_label_html')
			? bbb_trope_label_html($related_shelf, $related_shelf_emoji, $related_shelf_slug)
			: esc_html($related_shelf);
		$related_books[] = array(
			'handle'  => (string) ($related_data['handle'] ?? $related_post->post_name),
			'title'   => (string) ($related_data['title'] ?? get_the_title($related_post)),
			'author'  => (string) ($related_data['author'] ?? ''),
			'cover'   => $related_cover,
			'amazon'  => (string) ($related_data['amazon'] ?? ''),
			'bookshop' => (string) ($related_data['bookshop'] ?? ''),
			'spice'   => (int) ($related_data['spice'] ?? 0),
			'shelf'   => $related_shelf,
			'shelf_html' => $related_shelf_html,
			'trope'   => $first_trope_name,
			'trope_html' => $first_trope_html,
			'tropes'  => implode(', ', array_filter(array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), $related_tropes))),
			'mini'    => (string) ($related_data['mini'] ?? ''),
			'ku'      => !empty($related_data['ku']),
			'url'     => get_permalink($related_post),
		);
	}
	}

	$series_books = array();
	$series_url   = '' !== trim($series_handle) ? home_url('/series/' . sanitize_title($series_handle) . '/') : '';
	if ('' !== trim($series_handle)) {
		$series_posts = get_posts(
			array(
				'post_type'      => 'bbb_book',
				'post_status'    => 'publish',
				'posts_per_page' => 4,
				'post__not_in'   => array($book_id),
				'meta_key'       => '_bbb_series_number',
				'orderby'        => 'meta_value_num title',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'   => '_bbb_series_handle',
						'value' => sanitize_title($series_handle),
					),
				),
			)
		);
		if (!$series_posts && taxonomy_exists('bbb_series')) {
			$series_terms = get_the_terms($book_id, 'bbb_series');
			if ($series_terms && !is_wp_error($series_terms)) {
				$series_term = reset($series_terms);
				if ($series_term instanceof WP_Term) {
					$series_posts = get_posts(
						array(
							'post_type'      => 'bbb_book',
							'post_status'    => 'publish',
							'posts_per_page' => 4,
							'post__not_in'   => array($book_id),
							'meta_key'       => '_bbb_series_number',
							'orderby'        => 'meta_value_num title',
							'order'          => 'ASC',
							'tax_query'      => array(
								array(
									'taxonomy' => 'bbb_series',
									'field'    => 'term_id',
									'terms'    => (int) $series_term->term_id,
								),
							),
						)
					);
				}
			}
		}

		foreach ($series_posts as $series_post) {
			if (!$series_post instanceof WP_Post || count($series_books) >= 3) {
				continue;
			}

			$series_data  = function_exists('sss_book_data') ? sss_book_data($series_post) : array();
			$series_cover = (string) ($series_data['cover'] ?? '');
			if ('' !== $series_cover && function_exists('bbb_normalize_url_value')) {
				$series_cover = bbb_normalize_url_value($series_cover);
			}

			$series_book_title = (string) ($series_data['title'] ?? get_the_title($series_post));
			$series_book_title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($series_book_title) : $series_book_title;
			$series_book_author = (string) ($series_data['author'] ?? '');
			$series_book_author = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($series_book_author) : $series_book_author;
			$series_book_number = trim((string) ($series_data['series_number'] ?? get_post_meta($series_post->ID, '_bbb_series_number', true)));
			$series_tropes      = (array) ($series_data['tropes'] ?? array());

			$series_books[] = array(
				'handle'   => (string) ($series_data['handle'] ?? $series_post->post_name),
				'title'    => $series_book_title,
				'author'   => $series_book_author,
				'cover'    => $series_cover,
				'amazon'   => (string) ($series_data['amazon'] ?? ''),
				'bookshop' => (string) ($series_data['bookshop'] ?? ''),
				'spice'    => (int) ($series_data['spice'] ?? 0),
				'shelf'    => (string) ($series_data['shelf'] ?? ''),
				'tropes'   => implode(', ', array_filter(array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), $series_tropes))),
				'mini'     => (string) ($series_data['mini'] ?? ''),
				'ku'       => !empty($series_data['ku']),
				'url'      => get_permalink($series_post),
				'position' => '' !== $series_book_number ? 'book ' . $series_book_number : 'same series',
			);
		}
	}

	$book_quotes          = $book instanceof WP_Post && function_exists('bbb_book_quote_posts') ? bbb_book_quote_posts($book, 6) : array();
$book_quote_teasers   = array_slice($book_quotes, 0, 3);
$book_quotes_page_url = function_exists('bbb_book_quotes_url') ? bbb_book_quotes_url($book_id) : home_url('/sss-quote-wall/');

$book_aesthetic_trope = !empty($tropes[0]['name']) ? (string) $tropes[0]['name'] : 'dark romance';
$book_aesthetic_alt = trim(
	sprintf(
		'%1$s dark romance moodboard - book aesthetic%2$s',
		$title,
		$author ? ' by ' . $author : ''
	)
);
$book_aesthetic_pin_description = trim(
	sprintf(
		'%1$s aesthetic | dark romance | romance book aesthetic | book moodboard%2$s%3$s',
		$title,
		$author ? ' | ' . $author : '',
		$book_aesthetic_trope ? ' | ' . $book_aesthetic_trope : ''
	)
);
$book_aesthetic_uploaded_tiles = array();
$book_aesthetic_external_tiles = array();
foreach (preg_split('/\r\n|\r|\n/', (string) get_post_meta($book_id, '_bbb_book_aesthetic_urls', true)) ?: array() as $book_aesthetic_line) {
	$book_aesthetic_line = trim((string) $book_aesthetic_line);
	if ('' === $book_aesthetic_line) {
		continue;
	}

	$book_aesthetic_parts = array_map('trim', explode('|', $book_aesthetic_line, 2));
	$book_aesthetic_media = (string) ($book_aesthetic_parts[0] ?? '');
	$book_aesthetic_source = (string) ($book_aesthetic_parts[1] ?? $book_aesthetic_media);
	$is_book_aesthetic_image = (bool) preg_match('/\.(?:avif|gif|jpe?g|png|webp)(?:\?.*)?$/i', $book_aesthetic_media);
	if (!$is_book_aesthetic_image) {
		continue;
	}

	$book_aesthetic_tile = array(
		'media'  => $book_aesthetic_media,
		'source' => $book_aesthetic_source,
	);
	if (0 === strpos($book_aesthetic_media, home_url('/'))) {
		$book_aesthetic_uploaded_tiles[] = $book_aesthetic_tile;
	} else {
		$book_aesthetic_external_tiles[] = $book_aesthetic_tile;
	}
}
$book_aesthetic_tiles = array_slice(array_merge($book_aesthetic_uploaded_tiles, $book_aesthetic_external_tiles), 0, 3);
$book_aesthetic_has_media = static function (array $tiles, string $media_url): bool {
	$media_url = trim($media_url);
	if ('' === $media_url) {
		return true;
	}

	foreach ($tiles as $tile) {
		if ($media_url === trim((string) ($tile['media'] ?? ''))) {
			return true;
		}
	}

	return false;
};
if (count($book_aesthetic_tiles) < 3 && '' !== $book_cover_url && !$book_aesthetic_has_media($book_aesthetic_tiles, $book_cover_url)) {
	$book_aesthetic_tiles[] = array(
		'media'           => $book_cover_url,
		'source'          => $book_permalink,
		'alt'             => trim($title . ($author ? ' by ' . $author : '') . ' book cover'),
		'pin_title'       => $book_pin_title ?: trim($title . ' book cover'),
		'pin_description' => trim(
			sprintf(
				'Save the %1$s book cover%2$s.%3$s',
				$title,
				$author ? ' by ' . $author : '',
				$book_aesthetic_trope ? ' | ' . $book_aesthetic_trope : ''
			)
		),
	);
}
$book_aesthetic_quote_tile = null;
if (function_exists('bbb_quote_pin_card_url') && $book_quotes) {
	$book_quote_rotations = array();
	foreach ($book_quotes as $quote) {
		if (!$quote instanceof WP_Post) {
			continue;
		}

		$quote_text = bbb_quote_pin_quote_text($quote);
		if ('' === $quote_text) {
			continue;
		}

		$book_quote_rotations[] = array(
			'media' => bbb_quote_pin_card_url($quote, array('context' => 'book', 'source_id' => $book_id)),
			'alt'   => bbb_quote_pin_title($quote, array('context' => 'book', 'source_id' => $book_id)),
			'title' => bbb_quote_pin_title($quote, array('context' => 'book', 'source_id' => $book_id)),
			'description' => bbb_quote_pin_description($quote, array('context' => 'book', 'source_id' => $book_id)),
		);
	}

	if ($book_quote_rotations) {
		$first_quote_tile = $book_quote_rotations[0];
		$book_aesthetic_quote_tile = array(
			'media'           => (string) $first_quote_tile['media'],
			'source'          => $book_permalink,
			'alt'             => (string) $first_quote_tile['alt'],
			'pin_title'       => (string) $first_quote_tile['title'],
			'pin_description' => (string) $first_quote_tile['description'],
			'rotations'       => $book_quote_rotations,
		);
	}
}
if ($book_aesthetic_quote_tile && count($book_aesthetic_tiles) < 3) {
	$book_aesthetic_side_tiles = array_slice($book_aesthetic_tiles, 0, 2);
	$book_aesthetic_tiles = array();
	if (!empty($book_aesthetic_side_tiles[0])) {
		$book_aesthetic_tiles[0] = $book_aesthetic_side_tiles[0];
	}
	$book_aesthetic_tiles[1] = $book_aesthetic_quote_tile;
	if (!empty($book_aesthetic_side_tiles[1])) {
		$book_aesthetic_tiles[2] = $book_aesthetic_side_tiles[1];
	}
}
?>

<main class="sss-book-page">
	<div class="sss-book-page__inner">
		<nav class="sss-book-page__breadcrumb" aria-label="breadcrumb">
			<a href="<?php echo esc_url(home_url('/')); ?>">home</a>
			<span>›</span>
			<a href="<?php echo esc_url(home_url('/library/')); ?>">library</a>
			<?php if ($series_name && $series_handle) : ?>
				<span>›</span>
				<a style="text-transform:none !important;" href="<?php echo esc_url(home_url('/series/' . sanitize_title($series_handle) . '/')); ?>"><?php echo esc_html($series_name); ?> series</a>
			<?php endif; ?>
			<span>›</span>
			<span style="text-transform:none !important;"><?php echo esc_html($title); ?></span>
		</nav>

		<?php if ($is_locked) : ?>
			<section class="sss-book-page__locked">
				<p class="sss-book-page__eyebrow">private shelf</p>
				<h1 class="sss-book-page__title"><?php echo esc_html($title); ?></h1>
				<p>This book lives on the private shelf. Log in with Society access to see the full breakdown.</p>
				<?php if ($book_review_post instanceof WP_Post) : ?>
					<a class="sss-book-page__reviewLink" href="<?php echo esc_url(get_permalink($book_review_post)); ?>">
						read the full review <span aria-hidden="true">→</span>
					</a>
				<?php endif; ?>
				<a class="sss-book-page__login" href="<?php echo esc_url(home_url('/account/')); ?>">log in</a>
			</section>
		<?php else : ?>
			<article class="sss-book-page__content">
				<?php if ($series_name) : ?>
					<a class="sss-book-page__seriesTag" style="text-transform:none !important;" href="<?php echo esc_url(home_url('/series/' . sanitize_title($series_handle) . '/')); ?>">
						<?php echo esc_html($series_name); ?> series<?php echo $series_number ? ' · book ' . esc_html($series_number) : ''; ?>
					</a>
				<?php elseif (!empty($data['standalone'])) : ?>
					<p class="sss-book-page__bookNumber">standalone</p>
				<?php endif; ?>

				<div
					class="sss-book-page__titleRow sss-lib__book"
					data-handle="<?php echo esc_attr((string) ($data['handle'] ?? $book->post_name)); ?>"
					data-title="<?php echo esc_attr($title); ?>"
					data-author="<?php echo esc_attr($author); ?>"
					data-cover="<?php echo esc_url((string) ($data['cover'] ?? '')); ?>"
					data-amazon="<?php echo esc_url((string) ($data['amazon'] ?? '')); ?>"
					data-bookshop="<?php echo esc_url((string) ($data['bookshop'] ?? '')); ?>"
					data-spice="<?php echo esc_attr((string) $spice_count); ?>"
					data-shelf="<?php echo esc_attr((string) ($data['shelf'] ?? '')); ?>"
					data-tropes="<?php echo esc_attr(implode(', ', array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), (array) ($data['tropes'] ?? array())))); ?>"
					data-mini="<?php echo esc_attr($mini); ?>"
					data-series="<?php echo esc_attr($series_handle); ?>"
					data-series-name="<?php echo esc_attr($series_name); ?>"
					data-series-number="<?php echo esc_attr($series_number); ?>"
					data-ku="<?php echo $ku ? 'true' : 'false'; ?>"
				>
					<h1 class="sss-book-page__title" style="text-transform:none !important;"><?php echo esc_html($title); ?></h1>
					<span class="sss-lib__heart sss-book-page__addTbr" data-heart role="button" tabindex="0" aria-label="add to tbr">
						<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
						<span class="sss-lib__heartLabel" data-heart-label>add to tbr</span>
					</span>
				</div>
				<?php if ($author) : ?>
					<p class="sss-book-page__author">
						<span class="sss-book-page__authorText">by <span style="text-transform:none !important;"><?php echo esc_html($author); ?></span></span>
						<?php if ($book_pin_save_url && $book_cover_url) : ?>
							<a
								class="sss-book-page__authorPin"
								href="<?php echo esc_url($book_pin_save_url); ?>"
								data-pin-do="buttonPin"
								data-pin-custom="true"
								data-pin-media="<?php echo esc_url($book_cover_url); ?>"
								data-pin-url="<?php echo esc_url($book_permalink); ?>"
								data-pin-title="<?php echo esc_attr($book_pin_title); ?>"
								data-pin-description="<?php echo esc_attr($book_pin_description); ?>"
								target="_blank"
								rel="noopener noreferrer"
								aria-label="<?php echo esc_attr('save ' . $title . ' to Pinterest'); ?>"
							>
								<svg aria-hidden="true" viewBox="0 0 20 20" focusable="false">
									<path d="M10 2.01a8.1 8.1 0 0 1 5.666 2.353 8.09 8.09 0 0 1 1.277 9.68A7.95 7.95 0 0 1 10 18.04a8.2 8.2 0 0 1-2.276-.307c.403-.653.672-1.24.816-1.729l.567-2.2c.134.27.393.5.768.702.384.192.768.297 1.19.297q1.254 0 2.248-.72a4.7 4.7 0 0 0 1.537-1.969c.37-.89.554-1.848.537-2.813 0-1.249-.48-2.315-1.43-3.227a5.06 5.06 0 0 0-3.65-1.374c-.893 0-1.729.154-2.478.461a5.02 5.02 0 0 0-3.236 4.552c0 .72.134 1.355.413 1.902.269.538.672.922 1.22 1.152.096.039.182.039.25 0 .066-.028.114-.096.143-.192l.173-.653c.048-.144.02-.288-.105-.432a2.26 2.26 0 0 1-.548-1.565 3.803 3.803 0 0 1 3.976-3.861c1.047 0 1.863.288 2.44.855.585.576.883 1.315.883 2.228a6.8 6.8 0 0 1-.317 2.122 3.8 3.8 0 0 1-.893 1.556c-.384.384-.836.576-1.345.576-.413 0-.749-.144-1.018-.451-.259-.307-.345-.672-.25-1.085q.22-.77.452-1.537l.173-.701c.057-.25.086-.451.086-.624 0-.346-.096-.634-.269-.855-.192-.22-.451-.336-.797-.336-.432 0-.797.192-1.085.595-.288.394-.442.893-.442 1.499.005.374.063.746.173 1.104l.058.144c-.576 2.478-.913 3.938-1.037 4.36-.116.528-.154 1.153-.125 1.863A8.07 8.07 0 0 1 2 10.03c0-2.208.778-4.11 2.343-5.666A7.72 7.72 0 0 1 10 2.001z" />
								</svg>
							</a>
						<?php endif; ?>
					</p>
				<?php endif; ?>

				<section class="sss-book-page__hero" aria-label="book overview">
					<div class="sss-book-page__coverColumn">
						<div
							class="sss-book-page__coverWrap sss-lib__book"
							data-handle="<?php echo esc_attr((string) ($data['handle'] ?? $book->post_name)); ?>"
							data-title="<?php echo esc_attr($title); ?>"
							data-author="<?php echo esc_attr($author); ?>"
							data-cover="<?php echo esc_url((string) ($data['cover'] ?? '')); ?>"
							data-amazon="<?php echo esc_url((string) ($data['amazon'] ?? '')); ?>"
							data-bookshop="<?php echo esc_url((string) ($data['bookshop'] ?? '')); ?>"
							data-spice="<?php echo esc_attr((string) $spice_count); ?>"
							data-shelf="<?php echo esc_attr((string) ($data['shelf'] ?? '')); ?>"
							data-tropes="<?php echo esc_attr(implode(', ', array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), (array) ($data['tropes'] ?? array())))); ?>"
							data-mini="<?php echo esc_attr($mini); ?>"
							data-series="<?php echo esc_attr($series_handle); ?>"
							data-series-name="<?php echo esc_attr($series_name); ?>"
							data-series-number="<?php echo esc_attr($series_number); ?>"
							data-ku="<?php echo $ku ? 'true' : 'false'; ?>"
						>
							<span class="sss-lib__heart" data-heart role="button" tabindex="0" aria-label="save to your bookshelf">
								<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
								<span class="sss-lib__heartLabel" data-heart-label>save</span>
							</span>
							<span class="sss-lib__noteToggle sss-book-page__noteToggle" data-reader-note-toggle role="button" tabindex="0" aria-label="add your private note">
								<span class="sss-lib__noteIcon" aria-hidden="true">✎</span>
							</span>
							<?php if ($series_number) : ?>
								<span class="sss-lib__seriesBadge" aria-label="book <?php echo esc_attr($series_number); ?>"><?php echo esc_html($series_number); ?></span>
							<?php endif; ?>
							<?php if ($spice_count > 0) : ?>
								<span class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $spice_count)); ?></span>
							<?php endif; ?>
							<?php if (!empty($data['cover'])) : ?>
								<img class="sss-book-page__cover" src="<?php echo esc_url((string) $data['cover']); ?>" alt="<?php echo esc_attr($title . ($author ? ' by ' . $author : '') . ' book cover'); ?>">
							<?php endif; ?>
						</div>
						<div class="sss-book-page__noteActions">
							<button
								type="button"
								class="sss-book-page__noteText sss-lib__book"
								data-reader-note-toggle
								data-handle="<?php echo esc_attr((string) ($data['handle'] ?? $book->post_name)); ?>"
								data-title="<?php echo esc_attr($title); ?>"
								data-author="<?php echo esc_attr($author); ?>"
								data-cover="<?php echo esc_url((string) ($data['cover'] ?? '')); ?>"
								data-amazon="<?php echo esc_url((string) ($data['amazon'] ?? '')); ?>"
								data-bookshop="<?php echo esc_url((string) ($data['bookshop'] ?? '')); ?>"
								data-spice="<?php echo esc_attr((string) $spice_count); ?>"
								data-shelf="<?php echo esc_attr((string) ($data['shelf'] ?? '')); ?>"
								data-tropes="<?php echo esc_attr(implode(', ', array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), (array) ($data['tropes'] ?? array())))); ?>"
								data-mini="<?php echo esc_attr($mini); ?>"
								data-series="<?php echo esc_attr($series_handle); ?>"
								data-series-name="<?php echo esc_attr($series_name); ?>"
								data-series-number="<?php echo esc_attr($series_number); ?>"
								data-ku="<?php echo $ku ? 'true' : 'false'; ?>"
								aria-label="add your private note for <?php echo esc_attr($title); ?>"
							>add note</button>
							<a class="sss-book-page__notesLink" href="<?php echo esc_url($notes_url); ?>">open my notes</a>
						</div>
					</div>

						<div class="sss-book-page__overview">
							<?php if ($mini || $book_review_post instanceof WP_Post) : ?>
								<div class="sss-book-page__blurbStack">
									<?php if ($mini) : ?>
										<p class="sss-book-page__blurb"><?php echo esc_html($mini); ?></p>
									<?php endif; ?>

									<?php if ($book_review_post instanceof WP_Post) : ?>
										<a class="sss-book-page__reviewLink" href="<?php echo esc_url(get_permalink($book_review_post)); ?>">
											read the full review <span aria-hidden="true">→</span>
										</a>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<section class="sss-lib__mreaderRating sss-book-page__readerRating" data-book-page-rating-controls aria-label="<?php echo esc_attr('rate ' . $title); ?>">
								<div class="sss-lib__mstatusLabel">your rating</div>
								<div class="sss-lib__mstarButtons" role="radiogroup" aria-label="rate this book">
									<?php for ($star = 1; $star <= 5; $star++) : ?>
										<button
											type="button"
											class="sss-lib__mstarBtn"
											data-book-page-rating-option="<?php echo esc_attr((string) $star); ?>"
											aria-label="<?php echo esc_attr(sprintf('rate %d %s', $star, 1 === $star ? 'star' : 'stars')); ?>"
											aria-checked="false"
											role="radio"
										>★</button>
									<?php endfor; ?>
								</div>
								<div class="sss-lib__mratingNote" data-book-page-rating-summary>rating marks it read and saves it to your bookshelf.</div>
							</section>

							<div class="sss-book-page__metaGrid">
								<?php if ('' !== (string) ($data['tension'] ?? '')) : ?>
									<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">🔥 tension</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($data['tension']); ?></div>
								</div>
							<?php endif; ?>
							<?php if ('' !== (string) ($data['damage'] ?? '')) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">💔 emotional damage</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($data['damage']); ?></div>
								</div>
							<?php endif; ?>
							<?php if ('' !== (string) ($data['darkness'] ?? '')) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">💀 darkness</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($data['darkness']); ?></div>
								</div>
							<?php endif; ?>
							<?php if ('' !== (string) ($data['yearning'] ?? '')) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">💕 yearning</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($data['yearning']); ?></div>
								</div>
							<?php endif; ?>
							<?php if ($spice_count > 0) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">🌶 spice</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($spice_count); ?></div>
								</div>
							<?php endif; ?>
							<?php if ($reread) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">↻ reread</div>
									<div class="sss-book-page__metaValue">worthy</div>
								</div>
							<?php endif; ?>
						</div>

						<?php if ('' !== $boyfriend_label) : ?>
							<p class="sss-book-page__boyfriend">
								<?php if ($boyfriend_profile_url) : ?>
									<a class="sss-book-page__boyfriendLink" href="<?php echo esc_url($boyfriend_profile_url); ?>" aria-label="<?php echo esc_attr('open ' . $boyfriend_label . ' fictional boyfriend profile'); ?>">
										<?php if ($boyfriend_profile_image) : ?>
											<img src="<?php echo esc_url($boyfriend_profile_image); ?>" alt="<?php echo esc_attr($boyfriend_label); ?>" loading="lazy">
										<?php else : ?>
											<span class="sss-book-page__boyfriendIcon" aria-hidden="true">🖤</span>
										<?php endif; ?>
										<span>book boyfriend: <strong><?php echo esc_html($boyfriend_label); ?></strong></span>
									</a>
								<?php else : ?>
									<span class="sss-book-page__boyfriendFallback">🖤 book boyfriend: <strong><?php echo esc_html($boyfriend_label); ?></strong></span>
								<?php endif; ?>
							</p>
						<?php endif; ?>
					</div>
				</section>

						<?php if ($tropes) : ?>
							<section class="sss-book-page__section" aria-label="tropes in this book">
								<p class="sss-book-page__sectionLabel">tropes in this book</p>
						<div class="sss-book-page__tropeTags">
							<?php foreach ($tropes as $trope) : ?>
								<a class="sss-book-page__tropeTag" href="<?php echo esc_url($trope['url']); ?>"><?php echo wp_kses_post($trope['html']); ?></a>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<div class="sss-book-page__ctaRow">
					<?php if (!empty($data['amazon'])) : ?>
						<?php if ($ku) : ?>
							<a class="sss-book-page__cta sss-book-page__cta--ku" href="<?php echo esc_url((string) $data['amazon']); ?>" target="_blank" rel="noopener">read free on kindle unlimited</a>
						<?php endif; ?>
						<a class="sss-book-page__cta sss-book-page__cta--amazon" href="<?php echo esc_url((string) $data['amazon']); ?>" target="_blank" rel="noopener">
							buy on amazon<?php if ($ku) : ?> <span>· own it forever</span><?php endif; ?>
						</a>
					<?php endif; ?>
					<?php if (!empty($data['bookshop'])) : ?>
						<a class="sss-book-page__cta sss-book-page__cta--bookshop" href="<?php echo esc_url((string) $data['bookshop']); ?>" target="_blank" rel="noopener">prefer indie? bookshop.org →</a>
							<?php endif; ?>
						</div>

						<?php if ($series_books) : ?>
							<section class="sss-book-page__related sss-book-page__seriesBooks" aria-label="other books in the series">
								<p class="sss-book-page__relatedLabel">
									<span>other books in the series</span>
									<?php if ('' !== $series_url) : ?>
										<a class="sss-book-page__seriesLink" href="<?php echo esc_url($series_url); ?>">see full series →</a>
									<?php endif; ?>
								</p>
								<div class="sss-book-page__relatedGrid">
									<?php foreach ($series_books as $series_book) : ?>
										<article
											class="sss-book-page__relatedCard sss-lib__book"
											data-handle="<?php echo esc_attr($series_book['handle']); ?>"
											data-title="<?php echo esc_attr($series_book['title']); ?>"
											data-author="<?php echo esc_attr($series_book['author']); ?>"
											data-cover="<?php echo esc_url($series_book['cover']); ?>"
											data-amazon="<?php echo esc_url($series_book['amazon']); ?>"
											data-bookshop="<?php echo esc_url($series_book['bookshop']); ?>"
											data-spice="<?php echo esc_attr((string) $series_book['spice']); ?>"
											data-shelf="<?php echo esc_attr($series_book['shelf']); ?>"
											data-tropes="<?php echo esc_attr($series_book['tropes']); ?>"
											data-mini="<?php echo esc_attr($series_book['mini']); ?>"
											data-ku="<?php echo $series_book['ku'] ? 'true' : 'false'; ?>"
										>
											<?php if (!empty($series_book['cover'])) : ?>
												<span class="sss-book-page__relatedCoverWrap">
													<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
														<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
														<span class="sss-lib__heartLabel" data-heart-label>save</span>
													</span>
													<?php if ($series_book['spice'] > 0) : ?>
														<span class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $series_book['spice'])); ?></span>
													<?php endif; ?>
													<a class="sss-book-page__relatedCoverLink" href="<?php echo esc_url($series_book['url']); ?>" data-book-page-link aria-label="<?php echo esc_attr('open ' . $series_book['title']); ?>">
														<img class="sss-book-page__relatedCover" src="<?php echo esc_url($series_book['cover']); ?>" alt="<?php echo esc_attr($series_book['title'] . ($series_book['author'] ? ' by ' . $series_book['author'] : '') . ' book cover'); ?>" loading="lazy">
													</a>
												</span>
											<?php endif; ?>
											<a class="sss-book-page__relatedTitle" href="<?php echo esc_url($series_book['url']); ?>" data-book-page-link><?php echo esc_html($series_book['title']); ?></a>
											<?php if ($series_book['author']) : ?>
												<span class="sss-book-page__relatedAuthor"><?php echo esc_html($series_book['author']); ?></span>
											<?php endif; ?>
											<span class="sss-book-page__relatedTrope"><?php echo esc_html($series_book['position']); ?></span>
										</article>
									<?php endforeach; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ($has_reader_notes) : ?>
							<section class="sss-book-page__readerGuide" aria-label="reader guide for <?php echo esc_attr($title); ?>">
						<?php if ('' !== $reader_notes['verdict']) : ?>
							<section class="sss-book-page__guideBlock sss-book-page__guideBlock--feature">
								<p class="sss-book-page__guideLabel">verdict</p>
								<div class="sss-book-page__guideText"><?php echo wp_kses_post(wpautop($reader_notes['verdict'])); ?></div>
							</section>
						<?php endif; ?>

						<?php if ('' !== $reader_notes['vibe_description']) : ?>
							<section class="sss-book-page__guideBlock">
								<p class="sss-book-page__guideLabel">vibe</p>
								<div class="sss-book-page__guideText"><?php echo wp_kses_post(wpautop($reader_notes['vibe_description'])); ?></div>
							</section>
						<?php endif; ?>

							<?php if ($spice_count > 0) : ?>
								<section class="sss-book-page__guideBlock">
									<p class="sss-book-page__guideLabel">spice</p>
									<div class="sss-book-page__guideText" aria-label="<?php echo esc_attr(sprintf('spice rating %d out of 5', $spice_count)); ?>">
										<?php echo esc_html(str_repeat('🌶', $spice_count)); ?>
									</div>
								</section>
							<?php endif; ?>

							<section class="sss-book-page__spicyChapters" aria-labelledby="bbb-spicy-chapters-heading">
								<p class="sss-book-page__guideLabel">chapter index</p>
								<h2 id="bbb-spicy-chapters-heading" class="sss-book-page__chapterHeading"><?php echo esc_html($title); ?> <span>spicy</span> chapters</h2>
								<ol class="sss-book-page__chapterList">
									<?php foreach ($spicy_chapter_items as $chapter) : ?>
										<li>
											<span class="sss-book-page__chapterNumber"><?php echo esc_html($chapter); ?></span>
										</li>
									<?php endforeach; ?>
								</ol>
							</section>

							<?php if ('' !== $reader_notes['read_this_if'] || '' !== $reader_notes['skip_this_if']) : ?>
							<section class="sss-book-page__guideBlock">
								<p class="sss-book-page__guideLabel">reader fit</p>
								<div class="sss-book-page__fitGrid">
									<?php if ('' !== $reader_notes['read_this_if']) : ?>
										<div class="sss-book-page__fitCard sss-book-page__fitCard--read">
											<strong>read this if</strong>
											<p><?php echo esc_html($reader_notes['read_this_if']); ?></p>
										</div>
									<?php endif; ?>
									<?php if ('' !== $reader_notes['skip_this_if']) : ?>
										<div class="sss-book-page__fitCard sss-book-page__fitCard--skip">
											<strong>skip this if</strong>
											<p><?php echo esc_html($reader_notes['skip_this_if']); ?></p>
										</div>
									<?php endif; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ('' !== $reader_notes['content_warnings']) : ?>
							<section class="sss-book-page__guideBlock sss-book-page__guideBlock--warning">
								<p class="sss-book-page__guideLabel">content warnings</p>
								<div class="sss-book-page__guideText"><?php echo wp_kses_post(wpautop($reader_notes['content_warnings'])); ?></div>
							</section>
						<?php endif; ?>

						<?php if ('' !== $reader_notes['standalone_hea']) : ?>
							<section class="sss-book-page__guideBlock sss-book-page__guideBlock--status">
								<p class="sss-book-page__guideLabel">standalone + hea</p>
								<div class="sss-book-page__guideText"><?php echo wp_kses_post(wpautop($reader_notes['standalone_hea'])); ?></div>
							</section>
						<?php endif; ?>
					</section>
				<?php endif; ?>

				<?php if ($why) : ?>
					<div class="sss-book-page__quote"><?php echo wp_kses_post($why); ?></div>
				<?php endif; ?>

				<?php
				if (function_exists('bbb_render_society_content_cta')) {
					bbb_render_society_content_cta(
						array(
							'variant'    => 'book',
							'title'      => 'the fun keeps going...',
							'copy'       => 'Join the Society, log into your account, and turn this page into your own reader hub: bookshelf saves, private notes, ratings, quote saves, and more bookish rabbit holes waiting for you.',
							'play_label' => 'open the bookshelf',
							'play_url'   => function_exists('bbb_page_url') ? bbb_page_url('my-bookshelf') : home_url('/my-bookshelf/'),
							'features'   => array(
								array('title' => 'join the society', 'text' => 'start here so your bookish saves have a home.'),
								array('title' => 'log into account', 'text' => 'come back to your shelf, ratings, notes, and quotes.'),
								array('title' => 'have fun', 'text' => 'save this book, save the quotes, and keep reading.', 'url' => function_exists('bbb_page_url') ? bbb_page_url('my-bookshelf') : home_url('/my-bookshelf/')),
							),
						)
					);
				}
				?>

				<section class="bbb-fb-aesthetic" data-fb-reveal="aesthetic" aria-label="<?php echo esc_attr($title . ' aesthetic moodboard'); ?>">
					<div class="bbb-fb-wrap">
						<p class="bbb-fb-kicker">pinterest moodboard</p>
						<h2><?php echo esc_html($title); ?> aesthetic</h2>
						<div class="bbb-fb-moodboard<?php echo $book_aesthetic_tiles ? '' : ' bbb-fb-moodboard--empty'; ?>" data-fb-carousel aria-label="<?php echo esc_attr($title . ' aesthetic images'); ?>">
							<?php for ($slot = 0; $slot < 3; $slot++) : ?>
								<?php $tile = $book_aesthetic_tiles[$slot] ?? null; ?>
								<?php if ($tile) : ?>
									<?php
									$tile_media = (string) ($tile['media'] ?? '');
									$tile_source = (string) ($tile['source'] ?? '');
									$tile_alt = (string) ($tile['alt'] ?? $book_aesthetic_alt);
									$tile_pin_title = (string) ($tile['pin_title'] ?? $title . ' aesthetic');
									$tile_pin_description = (string) ($tile['pin_description'] ?? $book_aesthetic_pin_description);
									$tile_rotations = !empty($tile['rotations']) && is_array($tile['rotations']) ? wp_json_encode($tile['rotations']) : '';
									$tile_is_site_media = 0 === strpos($tile_media, home_url('/'));
									$tile_click_url = $tile_is_site_media ? '' : ($tile_source ?: $tile_media);
									$tile_pin_url = add_query_arg(
										array(
											'url'         => $book_permalink,
											'media'       => $tile_media,
											'title'       => $tile_pin_title,
											'description' => $tile_pin_description,
										),
										'https://www.pinterest.com/pin/create/button/'
									);
									?>
									<figure class="bbb-fb-moodboard__tile"<?php echo $tile_rotations ? ' data-bbb-quote-rotator="' . esc_attr($tile_rotations) . '"' : ''; ?>>
										<?php if ($tile_is_site_media) : ?>
											<div class="bbb-fb-moodboard__imageLink">
												<img src="<?php echo esc_url($tile_media); ?>" alt="<?php echo esc_attr($tile_alt); ?>" loading="lazy" decoding="async">
											</div>
											<a class="bbb-fb-moodboard__pin" href="<?php echo esc_url($tile_pin_url); ?>" data-pin-do="buttonPin" data-pin-custom="true" data-pin-media="<?php echo esc_url($tile_media); ?>" data-pin-url="<?php echo esc_url($book_permalink); ?>" data-pin-title="<?php echo esc_attr($tile_pin_title); ?>" data-pin-description="<?php echo esc_attr($tile_pin_description); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr('save ' . $tile_pin_title . ' to Pinterest'); ?>">
												<svg aria-hidden="true" viewBox="0 0 20 20" focusable="false">
													<path d="M10 2.01a8.1 8.1 0 0 1 5.666 2.353 8.09 8.09 0 0 1 1.277 9.68A7.95 7.95 0 0 1 10 18.04a8.2 8.2 0 0 1-2.276-.307c.403-.653.672-1.24.816-1.729l.567-2.2c.134.27.393.5.768.702.384.192.768.297 1.19.297q1.254 0 2.248-.72a4.7 4.7 0 0 0 1.537-1.969c.37-.89.554-1.848.537-2.813 0-1.249-.48-2.315-1.43-3.227a5.06 5.06 0 0 0-3.65-1.374c-.893 0-1.729.154-2.478.461a5.02 5.02 0 0 0-3.236 4.552c0 .72.134 1.355.413 1.902.269.538.672.922 1.22 1.152.096.039.182.039.25 0 .066-.028.114-.096.143-.192l.173-.653c.048-.144.02-.288-.105-.432a2.26 2.26 0 0 1-.548-1.565 3.803 3.803 0 0 1 3.976-3.861c1.047 0 1.863.288 2.44.855.585.576.883 1.315.883 2.228a6.8 6.8 0 0 1-.317 2.122 3.8 3.8 0 0 1-.893 1.556c-.384.384-.836.576-1.345.576-.413 0-.749-.144-1.018-.451-.259-.307-.345-.672-.25-1.085q.22-.77.452-1.537l.173-.701c.057-.25.086-.451.086-.624 0-.346-.096-.634-.269-.855-.192-.22-.451-.336-.797-.336-.432 0-.797.192-1.085.595-.288.394-.442.893-.442 1.499.005.374.063.746.173 1.104l.058.144c-.576 2.478-.913 3.938-1.037 4.36-.116.528-.154 1.153-.125 1.863A8.07 8.07 0 0 1 2 10.03c0-2.208.778-4.11 2.343-5.666A7.72 7.72 0 0 1 10 2.001z" />
												</svg>
											</a>
										<?php else : ?>
											<a class="bbb-fb-moodboard__imageLink" href="<?php echo esc_url($tile_click_url); ?>" target="_blank" rel="noopener noreferrer nofollow" aria-label="<?php echo esc_attr('open ' . $title . ' aesthetic source on Pinterest'); ?>">
												<img src="<?php echo esc_url($tile_media); ?>" alt="<?php echo esc_attr($tile_alt); ?>" loading="lazy" decoding="async">
											</a>
										<?php endif; ?>
									</figure>
								<?php else : ?>
									<span class="bbb-fb-moodboard__blank"></span>
								<?php endif; ?>
							<?php endfor; ?>
						</div>
					</div>
				</section>

				<section class="bbb-fb-quotes" data-fb-reveal="quotes" aria-label="quotes from <?php echo esc_attr($title); ?>">
					<div class="bbb-fb-wrap">
						<h2><?php echo esc_html($title); ?> quotes</h2>
						<div class="sss-book-page__quoteList">
						<?php if ($book_quote_teasers) : ?>
							<?php foreach ($book_quote_teasers as $quote) : ?>
								<?php $quote_text = bbb_bookquote_quote_text($quote); ?>
								<?php if ('' === $quote_text) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<blockquote class="sss-book-page__bookQuote">
									<a class="sss-book-page__quoteWallLink" href="<?php echo esc_url($book_quotes_page_url); ?>">‹ all quotes</a>
									<p>&ldquo;<?php echo esc_html($quote_text); ?>&rdquo;</p>
									<cite class="bbb-fb-quote-source">
										— <?php echo esc_html($title . ('' !== $author ? ' by ' . $author : '')); ?>
									</cite>
									<button class="sss-book-page__quoteCopy" type="button" data-bbb-copy-quote="<?php echo esc_attr($quote_text); ?>" aria-label="copy quote">copy</button>
								</blockquote>
							<?php endforeach; ?>
						<?php endif; ?>
						</div>
						<?php if ($book_quote_teasers) : ?>
							<a class="sss-book-page__quoteMore" href="<?php echo esc_url($book_quotes_page_url); ?>">see all book quotes <span aria-hidden="true">→</span></a>
						<?php endif; ?>
					</div>
				</section>
				<script>
					document.addEventListener('click', function(event) {
						var button = event.target.closest('[data-bbb-copy-quote]');
						if (!button || !navigator.clipboard) {
							return;
						}
						navigator.clipboard.writeText(button.getAttribute('data-bbb-copy-quote') || '').then(function() {
							button.textContent = 'copied';
							window.setTimeout(function() {
								button.textContent = 'copy';
							}, 1400);
						});
					});
				</script>

				<?php if ($related_books) : ?>
					<section class="sss-book-page__related" aria-label="related books">
						<p class="sss-book-page__relatedLabel">if you liked <?php echo esc_html($title); ?>, try these →</p>
						<div class="sss-book-page__relatedGrid">
							<?php foreach ($related_books as $related_book) : ?>
								<article
									class="sss-book-page__relatedCard sss-lib__book"
									data-handle="<?php echo esc_attr($related_book['handle']); ?>"
									data-title="<?php echo esc_attr($related_book['title']); ?>"
									data-author="<?php echo esc_attr($related_book['author']); ?>"
									data-cover="<?php echo esc_url($related_book['cover']); ?>"
									data-amazon="<?php echo esc_url($related_book['amazon']); ?>"
									data-bookshop="<?php echo esc_url($related_book['bookshop']); ?>"
									data-spice="<?php echo esc_attr((string) $related_book['spice']); ?>"
									data-shelf="<?php echo esc_attr($related_book['shelf']); ?>"
									data-tropes="<?php echo esc_attr($related_book['tropes']); ?>"
									data-mini="<?php echo esc_attr($related_book['mini']); ?>"
									data-ku="<?php echo $related_book['ku'] ? 'true' : 'false'; ?>"
								>
									<?php if (!empty($related_book['cover'])) : ?>
										<span class="sss-book-page__relatedCoverWrap">
											<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
												<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
												<span class="sss-lib__heartLabel" data-heart-label>save</span>
											</span>
											<?php if ($related_book['spice'] > 0) : ?>
												<span class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $related_book['spice'])); ?></span>
											<?php endif; ?>
											<a class="sss-book-page__relatedCoverLink" href="<?php echo esc_url($related_book['url']); ?>" data-book-page-link aria-label="<?php echo esc_attr('open ' . $related_book['title']); ?>">
												<img class="sss-book-page__relatedCover" src="<?php echo esc_url($related_book['cover']); ?>" alt="<?php echo esc_attr($related_book['title'] . ($related_book['author'] ? ' by ' . $related_book['author'] : '') . ' book cover'); ?>" loading="lazy">
											</a>
										</span>
									<?php endif; ?>
									<a class="sss-book-page__relatedTitle" href="<?php echo esc_url($related_book['url']); ?>" data-book-page-link><?php echo esc_html($related_book['title']); ?></a>
									<?php if ($related_book['author']) : ?>
										<span class="sss-book-page__relatedAuthor"><?php echo esc_html($related_book['author']); ?></span>
									<?php endif; ?>
									<?php if ('' !== trim((string) $related_book['shelf'])) : ?>
										<span class="sss-book-page__relatedGenre">
											<span class="sss-book-page__relatedGenreLine" aria-hidden="true"></span>
											<span class="sss-book-page__relatedGenreText"><?php echo wp_kses_post($related_book['shelf_html']); ?></span>
										</span>
									<?php endif; ?>
									<?php if ($related_book['trope']) : ?>
										<span class="sss-book-page__relatedTrope"><?php echo wp_kses_post($related_book['trope_html']); ?></span>
									<?php endif; ?>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<section class="sss-book-page__seoLinks" aria-label="explore more like this">
					<p class="sss-book-page__sectionLabel">explore more like this</p>
					<div class="sss-book-page__seoGrid">
						<?php foreach (array_slice($tropes, 0, 4) as $trope) : ?>
							<a class="sss-book-page__seoLink" href="<?php echo esc_url($trope['url']); ?>">→ <?php echo wp_kses_post($trope['html']); ?> books</a>
						<?php endforeach; ?>
						<a class="sss-book-page__seoLink" href="<?php echo esc_url(home_url('/books-like-' . sanitize_title($title) . '/')); ?>">→ books like <?php echo esc_html($title); ?></a>
						<?php if ($series_name && $series_handle) : ?>
							<a class="sss-book-page__seoLink" href="<?php echo esc_url(home_url('/series/' . sanitize_title($series_handle) . '/')); ?>">→ <?php echo esc_html(strtolower($series_name)); ?> reading order</a>
						<?php endif; ?>
					</div>
				</section>
			</article>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
