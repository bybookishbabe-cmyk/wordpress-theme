<?php
/**
 * Template Name: Reader Quizzes
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_reader_quiz_cover_urls')) {
	function bbb_reader_quiz_cover_urls(int $offset = 0, int $limit = 3): array {
		$post_types = array_values(
			array_filter(
				array('bbb_book', 'sss_book'),
				static fn(string $post_type): bool => post_type_exists($post_type)
			)
		);

		if (!$post_types) {
			return array();
		}

		$books = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => 24,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$covers = array();
		foreach ($books as $book) {
			if (function_exists('bbb_book_is_hidden') && bbb_book_is_hidden($book->ID)) {
				continue;
			}
			if (function_exists('bbb_book_is_private') && bbb_book_is_private($book->ID)) {
				continue;
			}
			if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($book->ID)) {
				continue;
			}

			$cover = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID) : (get_the_post_thumbnail_url($book->ID, 'medium') ?: '');
			if (!$cover) {
				continue;
			}

			$covers[] = array(
				'url'   => $cover,
				'title' => get_the_title($book),
			);
		}

		return array_slice($covers, max(0, $offset), $limit);
	}
}

$quiz_css_path = get_theme_file_path('assets/css/reader-quizzes.css');
wp_enqueue_style('bbb-reader-quizzes', get_theme_file_uri('assets/css/reader-quizzes.css'), array('bbb-sss-library'), file_exists($quiz_css_path) ? (string) filemtime($quiz_css_path) : wp_get_theme()->get('Version'));

$feature_covers = bbb_reader_quiz_cover_urls(0, 3);
$mood_covers    = bbb_reader_quiz_cover_urls(3, 3);
$trope_covers   = bbb_reader_quiz_cover_urls(6, 3);

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-quizdash" id="reader-quizzes">
		<div class="bbb-quizdash__wrap">
			<header class="bbb-quizdash__hero">
				<p class="bbb-quizdash__kicker">reader quizzes</p>
				<h1 class="bbb-quizdash__title">choose your next little diagnosis</h1>
				<p class="bbb-quizdash__sub">romance quizzes for your mood, your type, and the fictional problem currently ruining your standards.</p>
			</header>

			<div class="bbb-quizdash__grid" aria-label="reader quiz selection">
				<a class="bbb-quizdash__card bbb-quizdash__card--feature" href="<?php echo esc_url(home_url('/fictional-boyfriend-quiz/')); ?>">
					<span class="bbb-quizdash__emojiRain" aria-hidden="true">
						<span>🖤</span><span>🖤</span><span>🖤</span><span>🖤</span><span>🖤</span>
					</span>
					<span class="bbb-quizdash__badge">most popular</span>
					<span class="bbb-quizdash__covers" aria-hidden="true">
						<?php foreach ($feature_covers as $cover) : ?>
							<img src="<?php echo esc_url($cover['url']); ?>" alt="" loading="lazy">
						<?php endforeach; ?>
					</span>
					<span class="bbb-quizdash__issue">quiz 01</span>
					<h2>who is your fictional boyfriend?</h2>
					<p>for the reader who has a type and deserves to be lovingly exposed by it.</p>
					<span class="bbb-quizdash__cta">meet him →</span>
				</a>

				<a class="bbb-quizdash__card bbb-quizdash__card--pink" href="<?php echo esc_url(home_url('/reader-mood-quiz/')); ?>">
					<span class="bbb-quizdash__emojiRain" aria-hidden="true">
						<span>💌</span><span>🌶</span><span>🖤</span><span>✨</span><span>🔥</span>
					</span>
					<span class="bbb-quizdash__badge">new</span>
					<span class="bbb-quizdash__covers bbb-quizdash__covers--right" aria-hidden="true">
						<?php foreach ($mood_covers as $cover) : ?>
							<img src="<?php echo esc_url($cover['url']); ?>" alt="" loading="lazy">
						<?php endforeach; ?>
					</span>
					<span class="bbb-quizdash__issue">quiz 02</span>
					<h2>what should you read based on your mood?</h2>
					<p>tell me what emotional state dragged you in here and i’ll hand you a romance era.</p>
					<span class="bbb-quizdash__cta">take the mood quiz →</span>
				</a>

				<a class="bbb-quizdash__card" href="<?php echo esc_url(home_url('/romance-trope-quiz/')); ?>">
					<span class="bbb-quizdash__emojiRain" aria-hidden="true">
						<span>💘</span><span>📖</span><span>🎭</span><span>💌</span><span>✨</span>
					</span>
					<span class="bbb-quizdash__badge">new</span>
					<span class="bbb-quizdash__covers" aria-hidden="true">
						<?php foreach ($trope_covers as $cover) : ?>
							<img src="<?php echo esc_url($cover['url']); ?>" alt="" loading="lazy">
						<?php endforeach; ?>
					</span>
					<span class="bbb-quizdash__issue">quiz 03</span>
					<h2>what romance trope are you?</h2>
					<p>seven questions, five romantic problems, and one trope currently telling on you.</p>
					<span class="bbb-quizdash__cta">find your trope →</span>
				</a>

				<article class="bbb-quizdash__card bbb-quizdash__card--locked">
					<span class="bbb-quizdash__emojiRain" aria-hidden="true">
						<span>🌶</span><span>🔥</span><span>💋</span><span>🌶</span><span>🔥</span>
					</span>
					<span class="bbb-quizdash__issue">coming next</span>
					<h2>what spice level is your current era?</h2>
					<p>a deeply unserious diagnosis for the state of your kindle, your standards, and your sleep schedule.</p>
					<span class="bbb-quizdash__cta">coming soon</span>
				</article>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
