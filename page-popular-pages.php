<?php
/**
 * Popular reader pages.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_popular_pages_excerpt(?WP_Post $post, string $fallback): string {
	if (!$post instanceof WP_Post) {
		return $fallback;
	}

	$excerpt = trim(wp_strip_all_tags((string) get_the_excerpt($post)));
	if ('' === $excerpt) {
		$excerpt = trim(wp_strip_all_tags((string) $post->post_content));
	}

	return $excerpt ? wp_trim_words($excerpt, 22, '') : $fallback;
}

function bbb_popular_pages_image(?WP_Post $post): string {
	if (!$post instanceof WP_Post) {
		return '';
	}

	$image = (string) get_the_post_thumbnail_url($post->ID, 'large');
	if ('' === $image) {
		$image = (string) get_post_meta($post->ID, '_thumbnail_external_url', true);
	}

	return $image;
}

function bbb_popular_pages_path(string $url): string {
	$path = (string) parse_url($url, PHP_URL_PATH);
	$path = '/' . trim($path, '/') . '/';

	return '//' === $path ? '/' : $path;
}

function bbb_popular_pages_make_item(string $title, string $url, string $type, string $description, ?WP_Post $post = null, string $image = ''): array {
	return array(
		'title'       => $title,
		'url'         => $url,
		'path'        => bbb_popular_pages_path($url),
		'type'        => $type,
		'description' => bbb_popular_pages_excerpt($post, $description),
		'image'       => $image ?: bbb_popular_pages_image($post),
	);
}

function bbb_popular_pages_candidates(): array {
	$route_items = array(
		array('Reader Quizzes', 'reader-quizes', 'quiz hub', 'personality quizzes, trope matches, and reader chaos in one place.'),
		array('Reader Mood Quiz', 'reader-mood-quiz', 'quiz', 'pick the mood and let the site find the reading lane.'),
		array('Fictional Boyfriend Quiz', 'fictional-boyfriend-quiz', 'quiz', 'for finding exactly which fictional man is your current problem.'),
		array('Find Your Next Read', 'what-to-read-next', 'recommendation tool', 'a fast route into a book match by shelf, trope, and mood.'),
		array('Romance Books by Spice Level', 'romance-books-by-spice-level', 'spice guide', 'browse by heat level without guessing.'),
		array('Enemies to Lovers', 'enemies-to-lovers', 'trope page', 'tension, resentment, and payoff that actually earns it.'),
		array('Slow Burn Books', 'slow-burn-books', 'trope page', 'for readers who want the almost before the finally.'),
		array('Sports Romance Books', 'sports-romance-books', 'shelf page', 'hockey, athletes, competition, and locker-room-level tension.'),
		array('Book Reviews', 'book-reviews', 'review index', 'the review shelf for deciding what deserves your weekend.'),
		array('Books Like X', 'books-like', 'reading guide', 'next-read guides based on books you already loved.'),
		array('Series Reading Orders', 'series-reading-orders', 'reading order', 'start the series in the right place and avoid the chaos.'),
		array('Weekly Obsession', 'weekly-obsession', 'weekly pick', 'the current book taking up too much space in the group chat.'),
		array('Quote Wall', 'sss-quote-wall', 'quote archive', 'reader-favorite lines, beautifully collected.'),
		array('Library', 'library', 'book library', 'the full recommendation library by shelf, trope, and mood.'),
	);

	$items = array();
	foreach ($route_items as $item) {
		[$title, $slug, $type, $description] = $item;
		$post = get_page_by_path($slug);
		$items[] = bbb_popular_pages_make_item(
			$post instanceof WP_Post ? get_the_title($post) : $title,
			bbb_page_url($slug),
			$type,
			$description,
			$post instanceof WP_Post ? $post : null
		);
	}

	if (function_exists('bbb_books_like_guide_posts')) {
		foreach (array_slice(bbb_books_like_guide_posts(), 0, 8) as $guide) {
			$post = $guide['post'] ?? null;
			if ($post instanceof WP_Post) {
				$items[] = bbb_popular_pages_make_item(
					get_the_title($post),
					get_permalink($post),
					'if you liked guide',
					'a next-read list built from the books-like template.',
					$post
				);
			}
		}
	}

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	foreach ($posts as $post) {
		$items[] = bbb_popular_pages_make_item(
			get_the_title($post),
			get_permalink($post),
			'blog guide',
			'a reader-favorite dispatch from the guide archive.',
			$post
		);
	}

	$seen = array();
	$unique = array();
	foreach ($items as $item) {
		$key = (string) ($item['path'] ?? '');
		if (isset($seen[$key])) {
			continue;
		}
		$seen[$key] = true;
		$unique[] = $item;
	}

	return $unique;
}

$popular_candidates = bbb_popular_pages_candidates();
$fallback_items     = array_slice($popular_candidates, 0, 10);
$popular_css_path   = get_theme_file_path('assets/css/popular-pages.css');
$popular_js_path    = get_theme_file_path('assets/js/popular-pages.js');

wp_enqueue_style('bbb-popular-pages', get_theme_file_uri('assets/css/popular-pages.css'), array(), file_exists($popular_css_path) ? (string) filemtime($popular_css_path) : wp_get_theme()->get('Version'));
wp_enqueue_script('bbb-popular-pages', get_theme_file_uri('assets/js/popular-pages.js'), array('bbb-supabase'), file_exists($popular_js_path) ? (string) filemtime($popular_js_path) : wp_get_theme()->get('Version'), true);
wp_localize_script(
	'bbb-popular-pages',
	'BBBPopularPages',
	array(
		'candidates'  => $popular_candidates,
		'supabaseUrl' => defined('SUPABASE_URL') ? SUPABASE_URL : 'https://efmrfxsmgbeikfgtrxjv.supabase.co',
		'supabaseKey' => defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : 'sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk',
	)
);

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-popular" data-popular-pages>
		<div class="bbb-popular__wrap">
			<header class="bbb-popular__hero">
				<p class="bbb-popular__kicker">society pulse</p>
				<h1 class="bbb-popular__title">the 10 pages readers keep opening</h1>
				<p class="bbb-popular__sub">quizzes, trope pages, blog guides, reading-order rabbit holes, and the useful corners getting the most visits right now.</p>
				<div class="bbb-popular__meta">
					<span data-popular-window>live reader activity</span>
					<span data-popular-status>checking the shelves...</span>
				</div>
			</header>

			<div class="bbb-popular__feature" data-popular-feature>
				<?php $feature = $fallback_items[0] ?? null; ?>
				<?php if ($feature) : ?>
					<a class="bbb-popular__featureLink" href="<?php echo esc_url((string) $feature['url']); ?>">
						<span class="bbb-popular__rank">01</span>
						<span class="bbb-popular__featureCopy">
							<span class="bbb-popular__type"><?php echo esc_html((string) $feature['type']); ?></span>
							<strong><?php echo esc_html((string) $feature['title']); ?></strong>
							<span><?php echo esc_html((string) $feature['description']); ?></span>
						</span>
					</a>
				<?php endif; ?>
			</div>

			<div class="bbb-popular__grid" data-popular-list>
				<?php foreach ($fallback_items as $index => $item) : ?>
					<a class="bbb-popular__card" href="<?php echo esc_url((string) $item['url']); ?>">
						<span class="bbb-popular__cardRank"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
						<span class="bbb-popular__cardBody">
							<span class="bbb-popular__type"><?php echo esc_html((string) $item['type']); ?></span>
							<strong><?php echo esc_html((string) $item['title']); ?></strong>
							<span><?php echo esc_html((string) $item['description']); ?></span>
						</span>
						<span class="bbb-popular__open">open</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
