<?php
/**
 * Template Name: Romance Reading Bingo
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$bingo_css_path = get_theme_file_path('assets/css/romance-reading-bingo.css');
$bingo_js_path  = get_theme_file_path('assets/js/romance-reading-bingo.js');

wp_enqueue_style('bbb-romance-reading-bingo', get_theme_file_uri('assets/css/romance-reading-bingo.css'), array('bbb-base'), file_exists($bingo_css_path) ? (string) filemtime($bingo_css_path) : wp_get_theme()->get('Version'));
wp_enqueue_script('bbb-romance-reading-bingo', get_theme_file_uri('assets/js/romance-reading-bingo.js'), array(), file_exists($bingo_js_path) ? (string) filemtime($bingo_js_path) : wp_get_theme()->get('Version'), true);

if (!function_exists('bbb_bingo_square')) {
	function bbb_bingo_square(string $type, string $text, string $emoji, string $url = '', string $image = '', string $emoji_html = ''): array {
		return array(
			'type'       => $type,
			'text'       => $text,
			'emoji'      => $emoji,
			'emoji_html' => $emoji_html,
			'url'        => $url,
			'image'      => $image,
		);
	}
}

if (!function_exists('bbb_bingo_recent_book_squares')) {
	function bbb_bingo_recent_book_squares(int $limit): array {
		$post_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
		if (!$post_types) {
			return array();
		}

		$query_args = array(
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'date_query'             => array(
				array(
					'after'     => gmdate('Y-m-d', strtotime('-3 months', current_time('timestamp'))),
					'inclusive' => true,
				),
			),
		);

		$books = get_posts($query_args);
		if (count($books) < $limit) {
			unset($query_args['date_query']);
			$query_args['posts_per_page'] = $limit;
			$books = get_posts($query_args);
		}

		$squares = array();
		foreach ($books as $book) {
			if (!$book instanceof WP_Post) {
				continue;
			}
			if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($book->ID)) {
				continue;
			}
			if (function_exists('sss_book_is_private') && sss_book_is_private($book->ID)) {
				continue;
			}

			$title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($book)) : get_the_title($book);
			$cover = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID, 'medium') : (string) get_the_post_thumbnail_url($book->ID, 'medium');
			$squares[] = bbb_bingo_square('new release', $title, '📚', (string) get_permalink($book), (string) $cover);
		}

		return array_slice($squares, 0, $limit);
	}
}

if (!function_exists('bbb_bingo_fictional_man_squares')) {
	function bbb_bingo_fictional_man_squares(int $limit): array {
		if (!post_type_exists('bbb_boyfriend')) {
			return array();
		}

		$profiles = get_posts(
			array(
				'post_type'              => 'bbb_boyfriend',
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		$squares = array();
		foreach ($profiles as $profile) {
			if (!$profile instanceof WP_Post) {
				continue;
			}
			if (function_exists('bbb_fictional_boyfriend_profile_ready') && !bbb_fictional_boyfriend_profile_ready($profile->ID)) {
				continue;
			}

			$image = (string) get_the_post_thumbnail_url($profile->ID, 'medium');
			$squares[] = bbb_bingo_square('fictional man', 'fell for ' . get_the_title($profile), '💘', (string) get_permalink($profile), $image);
		}

		return array_slice($squares, 0, $limit);
	}
}

if (!function_exists('bbb_bingo_trope_squares')) {
	function bbb_bingo_trope_squares(int $limit): array {
		$taxonomies = array_values(array_filter(array('bbb_trope', 'sss_trope'), 'taxonomy_exists'));
		if (!$taxonomies) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomies,
				'hide_empty' => true,
				'number'     => $limit * 2,
				'orderby'    => 'count',
				'order'      => 'DESC',
			)
		);
		if (is_wp_error($terms)) {
			return array();
		}

		$squares = array();
		$seen    = array();
		foreach ($terms as $term) {
			if (!$term instanceof WP_Term || isset($seen[strtolower($term->name)])) {
				continue;
			}
			$seen[strtolower($term->name)] = true;
			$emoji = (string) get_term_meta($term->term_id, 'trope_emoji', true);
			$url   = function_exists('bbb_book_taxonomy_term_url') ? bbb_book_taxonomy_term_url($term) : get_term_link($term);
			$emoji_html = function_exists('bbb_custom_emoji_html')
				? bbb_custom_emoji_html($term->name, $term->slug, 'bbb-bingo__customEmojiImg')
				: '';
			if ('' === $emoji_html) {
				continue;
			}
			$squares[] = bbb_bingo_square('trope', strtolower($term->name), '' !== $emoji ? $emoji : '🖤', is_wp_error($url) ? '' : (string) $url, '', $emoji_html);
		}

		return array_slice($squares, 0, $limit);
	}
}

if (!function_exists('bbb_bingo_custom_trope_fallback_squares')) {
	function bbb_bingo_custom_trope_fallback_squares(): array {
		$fallbacks = array(
			array('enemies to lovers', 'enemies-to-lovers'),
			array('forced proximity', 'forced-proximity'),
			array('dark romance', 'dark-romance'),
			array('slow burn', 'slow-burn'),
			array('hockey romance', 'hockey-romance'),
			array('romantasy', 'romantasy'),
			array('touch her and die', 'touch-her-and-die'),
			array('fake dating', 'fake-dating'),
			array('fated mates', 'fated-mates'),
			array('mafia romance', 'mafia-romance'),
			array('one bed', 'one-bed'),
		);

		$squares = array();
		foreach ($fallbacks as $fallback) {
			$emoji_html = function_exists('bbb_custom_emoji_html')
				? bbb_custom_emoji_html($fallback[0], $fallback[1], 'bbb-bingo__customEmojiImg')
				: '';
			if ('' === $emoji_html) {
				continue;
			}
			$squares[] = bbb_bingo_square('trope', $fallback[0], '', function_exists('bbb_trope_page_url') ? bbb_trope_page_url($fallback[0], $fallback[1]) : '', '', $emoji_html);
		}

		return $squares;
	}
}

if (!function_exists('bbb_bingo_unique_squares')) {
	function bbb_bingo_unique_squares(array $squares): array {
		$unique = array();
		$seen   = array();

		foreach ($squares as $square) {
			if (!is_array($square)) {
				continue;
			}
			$key_parts = array(
				strtolower((string) ($square['type'] ?? '')),
				sanitize_title((string) ($square['url'] ?? '')),
				sanitize_title((string) ($square['text'] ?? '')),
			);
			$key = implode('|', array_filter($key_parts));
			if ('' === $key || isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$unique[] = $square;
		}

		return $unique;
	}
}

$book_squares      = bbb_bingo_recent_book_squares(7);
$boyfriend_squares = bbb_bingo_fictional_man_squares(6);
$trope_squares     = array_slice(bbb_bingo_unique_squares(array_merge(bbb_bingo_trope_squares(11), bbb_bingo_custom_trope_fallback_squares())), 0, 11);
$fallback_squares  = bbb_bingo_unique_squares(array_merge(
	bbb_bingo_custom_trope_fallback_squares(),
	array(
		bbb_bingo_square('fictional man', 'fell for a morally gray man', '💘'),
		bbb_bingo_square('new release', 'recent bybookishbabe library pick', '📚'),
	)
));

$mixed_squares = bbb_bingo_unique_squares(array_merge($book_squares, $trope_squares, $boyfriend_squares, $fallback_squares));
$bingo_squares = array();
for ($index = 0; $index < 25; $index++) {
	if (12 === $index) {
		$bingo_squares[] = bbb_bingo_square('free', 'FREE square', '✨');
		continue;
	}
	$bingo_squares[] = $mixed_squares[$index] ?? $fallback_squares[$index % count($fallback_squares)];
}

if (!function_exists('bbb_bingo_term_keywords')) {
	function bbb_bingo_term_keywords(int $post_id, array $taxonomies): array {
		$keywords = array();
		foreach (array_values(array_filter($taxonomies, 'taxonomy_exists')) as $taxonomy) {
			$terms = get_the_terms($post_id, $taxonomy);
			if (!$terms || is_wp_error($terms)) {
				continue;
			}
			foreach ($terms as $term) {
				if ($term instanceof WP_Term) {
					$keywords[] = strtolower($term->name);
					$keywords[] = strtolower($term->slug);
				}
			}
		}

		return array_values(array_unique(array_filter($keywords)));
	}
}

if (!function_exists('bbb_bingo_book_rec_pool')) {
	function bbb_bingo_book_series_keywords(int $post_id): array {
		$series = bbb_bingo_term_keywords($post_id, array('bbb_series', 'sss_series'));
		$series_handle = trim((string) get_post_meta($post_id, '_bbb_series_handle', true));
		if ('' !== $series_handle) {
			$series[] = strtolower($series_handle);
			$series[] = strtolower(str_replace('-', ' ', $series_handle));
		}

		return array_values(array_unique(array_filter($series)));
	}

	function bbb_bingo_book_rec_from_post(WP_Post $book): array {
		$title = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($book)) : get_the_title($book);
		$cover = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID, 'medium') : (string) get_the_post_thumbnail_url($book->ID, 'medium');

		return array(
			'title'    => $title,
			'url'      => (string) get_permalink($book),
			'image'    => (string) $cover,
			'source'   => strtolower($title),
			'series'   => bbb_bingo_book_series_keywords($book->ID),
			'keywords' => bbb_bingo_term_keywords($book->ID, array('bbb_trope', 'sss_trope', 'bbb_shelf', 'sss_shelf')),
		);
	}

	function bbb_bingo_book_rec_for_title(string $title): array {
		$title = trim($title);
		$post_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
		if ('' === $title || !$post_types) {
			return array();
		}

		$book = get_page_by_path(sanitize_title($title), OBJECT, $post_types);
		if (!$book instanceof WP_Post) {
			$matches = get_posts(
				array(
					'post_type'      => $post_types,
					'post_status'    => 'publish',
					'title'          => $title,
					'posts_per_page' => 1,
					'no_found_rows'  => true,
				)
			);
			$book = $matches[0] ?? null;
		}
		if (!$book instanceof WP_Post) {
			return array();
		}
		if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($book->ID)) {
			return array();
		}
		if (function_exists('sss_book_is_private') && sss_book_is_private($book->ID)) {
			return array();
		}

		return bbb_bingo_book_rec_from_post($book);
	}

	function bbb_bingo_book_rec_pool(int $limit): array {
		$post_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
		if (!$post_types) {
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

		$pool = array();
		foreach ($books as $book) {
			if (!$book instanceof WP_Post) {
				continue;
			}
			if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($book->ID)) {
				continue;
			}
			if (function_exists('sss_book_is_private') && sss_book_is_private($book->ID)) {
				continue;
			}

			$pool[] = bbb_bingo_book_rec_from_post($book);
		}

		return $pool;
	}
}

if (!function_exists('bbb_bingo_man_rec_pool')) {
	function bbb_bingo_man_rec_pool(int $limit): array {
		if (!post_type_exists('bbb_boyfriend')) {
			return array();
		}

		$profiles = get_posts(
			array(
				'post_type'              => 'bbb_boyfriend',
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);

		$pool = array();
		foreach ($profiles as $profile) {
			if (!$profile instanceof WP_Post) {
				continue;
			}
			if (function_exists('bbb_fictional_boyfriend_profile_ready') && !bbb_fictional_boyfriend_profile_ready($profile->ID)) {
				continue;
			}

			$source = (string) get_post_meta($profile->ID, '_bbb_fb_source', true);
			$source_books = array_values(array_filter(array_map('trim', explode(',', $source))));
			$source_book = $source_books ? bbb_bingo_book_rec_for_title($source_books[0]) : array();
			$keywords = array_filter(array_map('strtolower', array_merge(
				function_exists('bbb_fictional_boyfriend_tropes') ? bbb_fictional_boyfriend_tropes($profile->ID) : array(),
				array(
					$source,
					(string) get_post_meta($profile->ID, '_bbb_fb_series', true),
				)
			)));
			$pool[] = array(
				'title'    => get_the_title($profile),
				'url'      => (string) get_permalink($profile),
				'image'    => (string) get_the_post_thumbnail_url($profile->ID, 'medium'),
				'source'   => strtolower($source),
				'sourceBook' => $source_book,
				'series'   => array_values(array_unique(array_filter(array_map('strtolower', explode(',', (string) get_post_meta($profile->ID, '_bbb_fb_series', true)))))),
				'keywords' => array_values(array_unique($keywords)),
			);
		}

		return $pool;
	}
}

$bingo_result_recs = array(
	'fallback' => array(
		'book' => array('title' => 'browse the library', 'url' => home_url('/library/'), 'image' => '', 'keywords' => array()),
		'man'  => array('title' => 'take the fictional boyfriend quiz', 'url' => home_url('/fictional-boyfriend-quiz/'), 'image' => '', 'keywords' => array()),
	),
	'pools'    => array(
		'books' => bbb_bingo_book_rec_pool(120),
		'men'   => bbb_bingo_man_rec_pool(24),
	),
);

$reader_types = array(
	array(
		'title' => 'the trope loyalist',
		'copy'  => 'you know your favorite romance trope bingo squares on sight. enemies, fake dating, forced proximity: you are here for the pattern and the payoff.',
	),
	array(
		'title' => 'the book boyfriend archivist',
		'copy'  => 'you came for the plot and left with a ranked list of fictional men. this is your unofficial book boyfriend quiz result.',
	),
	array(
		'title' => 'the new-release hunter',
		'copy'  => 'your TBR updates monthly, your preorders have opinions, and the summer romance reading challenge 2026 was built for your calendar.',
	),
	array(
		'title' => 'the shadow-season reader',
		'copy'  => 'you like the page a little sharper. dark romance reading challenge, romantasy bingo, morally gray men: yes, yes, and unfortunately yes.',
	),
);

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-bingo" data-bbb-bingo>
		<div class="bbb-bingo__hero">
			<div class="bbb-bingo__wrap bbb-bingo__heroGrid">
				<div class="bbb-bingo__heroCopy">
					<p class="bbb-bingo__eyebrow">romance reading bingo 2026 · summer edition</p>
					<h1>romance reading bingo</h1>
					<p>mark the tropes you have read, the book boyfriends you have fallen for, and the new releases you devoured. get a bingo and find out exactly what kind of romance reader you are.</p>
					<div class="bbb-bingo__heroActions">
						<a class="bbb-bingo__btn bbb-bingo__btn--primary" href="#bingo-board">let's get playing</a>
					</div>
				</div>
			</div>
		</div>

		<section class="bbb-bingo__how" aria-labelledby="bingo-how-title">
			<div class="bbb-bingo__wrap bbb-bingo__twoCol">
				<div>
					<p class="bbb-bingo__kicker">how it works</p>
					<h2 id="bingo-how-title">how to play the bybookishbabe bingo</h2>
				</div>
				<div class="bbb-bingo__copy">
					<p>Each board mixes three things readers already love to check off: romance trope bingo squares, fictional men, and books that came out in the past quarter. Five across, down, or diagonal gets you a bingo.</p>
					<p>When you complete a row, the page reveals your reader type with confetti and a shareable card. The card is built to screenshot, copy, and pin, because the real question is still: what kind of romance reader am i?</p>
				</div>
			</div>
		</section>

		<section class="bbb-bingo__boardSection" id="bingo-board" aria-labelledby="bingo-board-title">
			<div class="bbb-bingo__wrap">
				<header class="bbb-bingo__boardHead">
					<div>
						<p class="bbb-bingo__kicker">summer board</p>
						<h2 id="bingo-board-title">the 2026 romance reader bingo board</h2>
					</div>
					<div class="bbb-bingo__boardTools">
						<button class="bbb-bingo__reset" type="button" data-bbb-bingo-reset>reset board</button>
						<div class="bbb-bingo__score" aria-live="polite">
							<strong data-bbb-bingo-count>0</strong>
							<span>squares marked</span>
						</div>
					</div>
				</header>

				<div class="bbb-bingo__grid" role="group" aria-label="romance reading bingo board">
					<?php foreach ($bingo_squares as $index => $square) : ?>
						<button class="bbb-bingo__square <?php echo 12 === $index ? 'is-free' : ''; ?>" type="button" data-bbb-bingo-square="<?php echo esc_attr((string) $index); ?>" data-bbb-bingo-type="<?php echo esc_attr((string) $square['type']); ?>"<?php echo !empty($square['url']) ? ' data-bbb-bingo-url="' . esc_url((string) $square['url']) . '"' : ''; ?> aria-pressed="<?php echo 12 === $index ? 'true' : 'false'; ?>">
							<?php if (!empty($square['image'])) : ?>
								<span class="bbb-bingo__thumb" aria-hidden="true"><img src="<?php echo esc_url((string) $square['image']); ?>" alt="" loading="lazy"></span>
							<?php elseif (!empty($square['emoji_html'])) : ?>
								<b class="bbb-bingo__customEmoji" aria-hidden="true"><?php echo wp_kses_post((string) $square['emoji_html']); ?></b>
							<?php else : ?>
								<b aria-hidden="true"><?php echo esc_html((string) $square['emoji']); ?></b>
							<?php endif; ?>
							<span><?php echo esc_html((string) $square['text']); ?></span>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="bbb-bingo__result" data-bbb-bingo-result>
					<p class="bbb-bingo__kicker">current result</p>
					<h3>mark a few squares to reveal your reader type.</h3>
					<p>Your board saves in this browser while you test the local version. Complete a row to unlock the pretty card.</p>
				</div>
			</div>
		</section>

		<section class="bbb-bingo__types" id="reader-type" aria-labelledby="bingo-type-title">
			<div class="bbb-bingo__wrap">
				<div class="bbb-bingo__sectionHead">
					<p class="bbb-bingo__kicker">reader diagnosis</p>
					<h2 id="bingo-type-title">what your bingo says about your reader type</h2>
				</div>
				<div class="bbb-bingo__typeGrid">
					<?php foreach ($reader_types as $type) : ?>
						<article>
							<h3><?php echo esc_html($type['title']); ?></h3>
							<p><?php echo esc_html($type['copy']); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="bbb-bingo__share" aria-labelledby="bingo-share-title">
			<div class="bbb-bingo__wrap bbb-bingo__shareInner">
				<div>
					<p class="bbb-bingo__kicker">share it</p>
					<h2 id="bingo-share-title">share the board with your book bestie</h2>
					<p>Keep each other updated as you mark your squares and compare what kind of romance reader you are.</p>
				</div>
				<div class="bbb-bingo__shareAction">
					<button class="bbb-bingo__phoneShare" type="button" data-bbb-bingo-share aria-label="share the romance reading bingo board">
						<span aria-hidden="true">📲</span>
					</button>
					<span class="bbb-bingo__shareToast" data-bbb-bingo-share-toast role="status" aria-live="polite">link copied</span>
				</div>
			</div>
		</section>

		<div class="bbb-bingo__modal" data-bbb-bingo-modal hidden>
			<button class="bbb-bingo__modalScrim" type="button" data-bbb-bingo-close aria-label="close bingo result"></button>
			<div class="bbb-bingo__modalPanel" role="dialog" aria-modal="true" aria-labelledby="bbb-bingo-modal-title">
				<button class="bbb-bingo__modalClose" type="button" data-bbb-bingo-close aria-label="close">×</button>
				<div class="bbb-bingo__confetti" aria-hidden="true">
					<i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
				</div>
				<div class="bbb-bingo__shareCard" data-bbb-bingo-card>
					<p class="bbb-bingo__kicker">#bookishbabebingo</p>
					<h2 id="bbb-bingo-modal-title" data-bbb-bingo-modal-type>the trope loyalist</h2>
					<p data-bbb-bingo-modal-copy>you know the pattern, you respect the payoff, and you still want the book to ruin you in a fresh way.</p>
					<div class="bbb-bingo__matches" aria-label="personalized result matches">
						<a class="bbb-bingo__match" href="<?php echo esc_url(home_url('/library/')); ?>" data-bbb-bingo-book-link>
							<span class="bbb-bingo__matchMedia" data-bbb-bingo-book-media></span>
							<span>
								<small>read next</small>
								<strong data-bbb-bingo-book-title>browse the library</strong>
							</span>
						</a>
						<a class="bbb-bingo__match" href="<?php echo esc_url(home_url('/fictional-boyfriend-quiz/')); ?>" data-bbb-bingo-man-link>
							<span class="bbb-bingo__matchMedia" data-bbb-bingo-man-media></span>
							<span>
								<small>would ruin you</small>
								<strong data-bbb-bingo-man-title>take the fictional boyfriend quiz</strong>
							</span>
						</a>
					</div>
				</div>
				<div class="bbb-bingo__modalActions">
					<button class="bbb-bingo__btn bbb-bingo__btn--primary" type="button" data-bbb-bingo-copy-card>copy result</button>
					<a class="bbb-bingo__btn bbb-bingo__btn--secondary" href="https://www.pinterest.com/pin/create/button/" target="_blank" rel="noopener" data-bbb-bingo-pin>pin it</a>
					<button class="bbb-bingo__btn bbb-bingo__btn--secondary" type="button" data-bbb-bingo-close>keep playing</button>
				</div>
				<p class="bbb-bingo__modalHint">Screenshot the card for Instagram Stories or pin the board prompt to save it for later.</p>
			</div>
		</div>
		<script type="application/json" data-bbb-bingo-recs><?php echo wp_json_encode($bingo_result_recs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
	</section>
</main>

<?php
get_footer();
