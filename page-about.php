<?php
/**
 * Template Name: about
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$about_css_path = get_theme_file_path('assets/css/about.css');
wp_enqueue_style(
	'bbb-about',
	get_theme_file_uri('assets/css/about.css'),
	array('bbb-bookshelf-signup'),
	file_exists($about_css_path) ? (string) filemtime($about_css_path) : wp_get_theme()->get('Version')
);

$about_title = 'about | bybookishbabe — real romance reviews, ratings & the smut & sentiment society';
$about_description = 'real romance book reviews and ratings from a reader who actually read them. no paid promos. plus: the smut & sentiment society — your sunday book obsession.';
$about_social_title = 'about bybookishbabe — real romance reviews & the smut & sentiment society';
$about_social_description = "built by a romance reader, for romance readers. honest ratings, real recs, zero paid promotions. plus a sunday book obsession you don't want to miss.";
$about_image = 'https://bybookishbabe.com/wp-content/uploads/2026/05/bybookishbabe.png';
$about_canonical = 'https://bybookishbabe.com/about/';

add_filter(
	'pre_get_document_title',
	static fn(string $title): string => $about_title,
	100
);
add_filter(
	'rank_math/frontend/title',
	static fn(string $title): string => $about_title,
	100
);
add_filter(
	'rank_math/frontend/description',
	static fn(string $description): string => $about_description,
	100
);
add_filter(
	'rank_math/opengraph/facebook/title',
	static fn(string $title): string => $about_social_title,
	100
);
add_filter(
	'rank_math/opengraph/facebook/description',
	static fn(string $description): string => $about_social_description,
	100
);
add_filter(
	'rank_math/opengraph/twitter/title',
	static fn(string $title): string => $about_social_title,
	100
);
add_filter(
	'rank_math/opengraph/twitter/description',
	static fn(string $description): string => $about_social_description,
	100
);
add_filter(
	'rank_math/frontend/canonical',
	static fn(string $canonical): string => $about_canonical,
	100
);
add_filter(
	'rank_math/opengraph/facebook/url',
	static fn(string $url): string => $about_canonical,
	100
);
add_filter(
	'rank_math/opengraph/twitter/url',
	static fn(string $url): string => $about_canonical,
	100
);
add_filter(
	'rank_math/opengraph/type',
	static fn(string $type): string => 'website',
	100
);
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
add_filter(
	'rank_math/opengraph/facebook/image',
	static fn(string $image): string => $about_image,
	100
);
add_filter(
	'rank_math/opengraph/twitter/image',
	static fn(string $image): string => $about_image,
	100
);
add_action(
	'rank_math/opengraph/facebook',
	static function () use ($about_social_title, $about_social_description, $about_image, $about_canonical): void {
		remove_all_actions('rank_math/opengraph/facebook', 5);
		remove_all_actions('rank_math/opengraph/facebook', 10);
		remove_all_actions('rank_math/opengraph/facebook', 11);
		remove_all_actions('rank_math/opengraph/facebook', 12);
		remove_all_actions('rank_math/opengraph/facebook', 30);

		printf('<meta property="og:type" content="website">%s', "\n");
		printf('<meta property="og:title" content="%s">%s', esc_attr($about_social_title), "\n");
		printf('<meta property="og:description" content="%s">%s', esc_attr($about_social_description), "\n");
		printf('<meta property="og:image" content="%s">%s', esc_url($about_image), "\n");
		printf('<meta property="og:url" content="%s">%s', esc_url($about_canonical), "\n");
	},
	4
);
add_action(
	'rank_math/opengraph/twitter',
	static function () use ($about_social_title, $about_social_description, $about_image): void {
		remove_all_actions('rank_math/opengraph/twitter', 5);
		remove_all_actions('rank_math/opengraph/twitter', 10);
		remove_all_actions('rank_math/opengraph/twitter', 11);
		remove_all_actions('rank_math/opengraph/twitter', 30);

		printf('<meta name="twitter:title" content="%s">%s', esc_attr($about_social_title), "\n");
		printf('<meta name="twitter:description" content="%s">%s', esc_attr($about_social_description), "\n");
		printf('<meta name="twitter:image" content="%s">%s', esc_url($about_image), "\n");
	},
	4
);
add_action(
	'wp_head',
	static function () use ($about_canonical): void {
		printf('<link rel="canonical" href="%s">%s', esc_url($about_canonical), "\n");

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'AboutPage',
			'name'        => 'About ByBookishBabe',
			'url'         => $about_canonical,
			'description' => 'real romance book reviews, ratings, and weekly recommendations from a reader who actually read the books. no paid promotions. home of the smut & sentiment society.',
			'publisher'   => array(
				'@type'  => 'Person',
				'name'   => 'ByBookishBabe',
				'url'    => 'https://bybookishbabe.com',
				'sameAs' => array(
					'https://www.instagram.com/bybookishbabe',
					'https://www.tiktok.com/@bybookishbabe',
					'https://thesmutandsentimentsociety.substack.com',
				),
			),
		);

		echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
	},
	30
);

$society_join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$society_join_url = '' !== trim((string) $society_join_url) ? (string) $society_join_url : 'https://thesmutandsentimentsociety.substack.com/subscribe';

$about_pillars = array(
	array(
		'title' => 'honest reviews & ratings',
		'copy'  => 'spice levels, tropes, and real takes from someone who actually read the book.',
		'url'   => bbb_page_url('book-reviews'),
		'mark'  => '01',
		'emoji' => '⭐',
	),
	array(
		'title' => 'the full library',
		'copy'  => 'every book i have covered, searchable and organized so you can find your next read fast.',
		'url'   => bbb_page_url('library'),
		'mark'  => '02',
		'emoji' => '📚',
	),
	array(
		'title' => 'curated guides',
		'copy'  => 'trope deep-dives, reading orders, and books-like lists for when you know exactly what mood you want.',
		'url'   => bbb_page_url('curated-romance-guides'),
		'mark'  => '03',
		'emoji' => '✨',
	),
	array(
		'title' => 'reader quizzes',
		'copy'  => 'fictional boyfriends, next-read nudges, and quick reader logic with a little drama built in.',
		'url'   => bbb_page_url('reader-quizzes'),
		'mark'  => '04',
		'emoji' => '❓',
	),
	array(
		'title' => 'browse by spice',
		'copy'  => 'pick your heat before you commit, from soft slow burns to very specific bad decisions.',
		'url'   => bbb_page_url('romance-books-by-spice-level'),
		'mark'  => '05',
		'emoji' => '🌶️',
	),
	array(
		'title' => 'sunday letters',
		'copy'  => 'one book i am genuinely obsessed with lands in your inbox every sunday.',
		'url'   => bbb_page_url('smut-sentiment-society'),
		'mark'  => '06',
		'emoji' => '💌',
	),
);

$explore_links = array(
	array('label' => 'browse', 'title' => 'the full library', 'meta' => 'every book covered', 'url' => bbb_page_url('library')),
	array('label' => 'reviews', 'title' => 'book reviews', 'meta' => 'honest takes', 'url' => bbb_page_url('book-reviews')),
	array('label' => 'spice', 'title' => 'by spice level', 'meta' => 'know what you want', 'url' => bbb_page_url('romance-books-by-spice-level')),
	array('label' => 'discover', 'title' => 'what to read next', 'meta' => 'rec engine', 'url' => bbb_page_url('what-to-read-next')),
	array('label' => 'fun', 'title' => 'reader quizzes', 'meta' => 'find your book boyfriend', 'url' => bbb_page_url('reader-quizzes')),
	array('label' => 'guides', 'title' => 'romance guides', 'meta' => 'curated by trope', 'url' => bbb_page_url('curated-romance-guides')),
	array('label' => 'order', 'title' => 'series reading orders', 'meta' => 'start right', 'url' => bbb_page_url('series-reading-orders')),
	array('label' => 'sundays', 'title' => 'join the society', 'meta' => 'do not miss a sunday', 'url' => $society_join_url, 'external' => true),
);

$real_books = get_posts(
	array(
		'post_type'      => array('bbb_book', 'sss_book'),
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
$real_books = array_values(
	array_slice(
		array_filter(
			$real_books,
			static function (WP_Post $book): bool {
				if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($book->ID)) {
					return false;
				}

				if (function_exists('sss_book_is_visible') && !sss_book_is_visible($book->ID)) {
					return false;
				}

				$cover = 'bbb_book' === $book->post_type
					? (function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID) : (string) get_post_meta($book->ID, '_bbb_cover_url', true))
					: (function_exists('sss_get_book_cover_url') ? sss_get_book_cover_url($book->ID) : '');

				if ('' === trim($cover)) {
					return false;
				}

				$is_bbb_book   = 'bbb_book' === $book->post_type;
				$is_standalone = $is_bbb_book
					? '1' === (string) get_post_meta($book->ID, '_bbb_standalone', true)
					: in_array((string) get_post_meta($book->ID, 'sss_standalone', true), array('1', 'true', 'yes'), true);
				$series_number = trim(
					(string) (
						$is_bbb_book
							? get_post_meta($book->ID, '_bbb_series_number', true)
							: get_post_meta($book->ID, 'sss_series_number', true)
					)
				);
				$series_handle = trim(
					(string) (
						$is_bbb_book
							? get_post_meta($book->ID, '_bbb_series_handle', true)
							: get_post_meta($book->ID, 'sss_series_handle', true)
					)
				);
				$is_first_in_series = in_array($series_number, array('1', '1.0'), true);
				$has_series         = '' !== $series_number || '' !== $series_handle;

				return $is_standalone || $is_first_in_series || ! $has_series;
			}
		),
		0,
		3
	)
);

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none bbb-about-page" role="main" tabindex="-1">
	<section class="bbb-about-page__hero" aria-labelledby="bbb-about-title">
		<div class="bbb-about-page__wrap bbb-about-page__heroGrid">
			<div class="bbb-about-page__heroCopy">
				<p class="bbb-about-page__eyebrow">welcome to the corner i built for us</p>
				<h1 class="bbb-about-page__title" id="bbb-about-title">soft hearts.<br>
				<em>sinful taste.</em></h1>
				<p class="bbb-about-page__lede">romance book reviews and recommendations, real ratings, and a sunday book newsletter, made by a reader, for readers.</p>
				<div class="bbb-about-page__actions">
					<a class="bbb-about-page__button" href="<?php echo esc_url(bbb_page_url('library')); ?>">explore the library</a>
					<a class="bbb-about-page__button bbb-about-page__button--ghost" href="#about-society">about the society</a>
				</div>
			</div>
		</div>
	</section>

	<section class="bbb-about-page__section bbb-about-page__section--story" aria-labelledby="bbb-about-story-title">
		<div class="bbb-about-page__wrap bbb-about-page__split">
			<div class="bbb-about-page__quotePanel bbb-about-page__reveal">
				<p class="bbb-about-page__quote">
					<span>real books.</span>
					<span>real ratings.</span>
					<span>real obsession.</span>
				</p>
				<?php if ($real_books) : ?>
					<div class="bbb-about-page__realBooks" aria-label="real books featured by bybookishbabe">
						<?php foreach ($real_books as $index => $book) : ?>
							<?php
							$cover_url = 'bbb_book' === $book->post_type
								? (function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID) : (string) get_post_meta($book->ID, '_bbb_cover_url', true))
								: (function_exists('sss_get_book_cover_url') ? sss_get_book_cover_url($book->ID) : '');
							$spice = 'bbb_book' === $book->post_type
								? (int) get_post_meta($book->ID, '_bbb_spice', true)
								: (int) get_post_meta($book->ID, 'sss_spice', true);
							?>
							<a class="bbb-about-page__realBook" style="--book-index: <?php echo esc_attr((string) $index); ?>" href="<?php echo esc_url(get_permalink($book)); ?>">
								<span class="bbb-about-page__bookSave" aria-hidden="true">♡</span>
								<?php if ($spice > 0) : ?>
									<span class="bbb-about-page__bookSpice" aria-label="<?php echo esc_attr((string) $spice . ' spice'); ?>"><?php echo esc_html(str_repeat('🌶', min($spice, 5))); ?></span>
								<?php endif; ?>
								<img src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr(get_the_title($book)); ?>" loading="lazy">
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="bbb-about-page__copy bbb-about-page__reveal">
				<p class="bbb-about-page__kicker">the story</p>
				<h2 id="bbb-about-story-title">I built the romance corner I always <em>wished existed.</em></h2>
				<p>i am a romance reader, through and through. for a long time i wanted somewhere i could find honest romance book ratings, actually see the books, learn where to grab them, and know the person recommending them had actually read them.</p>
				<p>not an algorithm. not paid promos. just a reader who genuinely loves the books they are talking about, with spice level book reviews and obsessive notes on the good stuff.</p>
				<p>i could not find it. so i built it. welcome to <strong>bybookishbabe</strong>.</p>
			</div>
		</div>
	</section>

	<div class="bbb-about-page__pinkBreak" aria-hidden="true"></div>

	<section class="bbb-about-page__section bbb-about-page__section--rose bbb-about-page__section--pillars" aria-labelledby="bbb-about-pillars-title">
		<div class="bbb-about-page__wrap">
			<div class="bbb-about-page__sectionHead bbb-about-page__reveal">
				<p class="bbb-about-page__kicker">what you will find here</p>
				<h2 id="bbb-about-pillars-title">What you'll <em>find here</em></h2>
				<p>browse romance trope recommendations, books for romance readers, and the reader tools that make finding your next read easier.</p>
			</div>
			<div class="bbb-about-page__pillars">
				<?php foreach ($about_pillars as $pillar) : ?>
					<a class="bbb-about-page__pillar bbb-about-page__reveal" style="--pillar-index: <?php echo esc_attr((string) ((int) $pillar['mark'] - 1)); ?>;" href="<?php echo esc_url($pillar['url']); ?>">
						<span><?php echo esc_html($pillar['mark']); ?></span>
						<i aria-hidden="true"><?php echo esc_html($pillar['emoji']); ?></i>
						<strong><?php echo esc_html($pillar['title']); ?></strong>
						<em><?php echo esc_html($pillar['copy']); ?></em>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="bbb-about-page__section bbb-about-page__section--dark" id="about-society" aria-labelledby="bbb-about-society-title">
		<div class="bbb-about-page__wrap bbb-about-page__societyGrid">
			<div class="bbb-about-page__copy bbb-about-page__reveal">
				<p class="bbb-about-page__kicker">the smut & sentiment society</p>
				<h2 id="bbb-about-society-title">The Smut &amp; Sentiment Society — the thing you don't want to <em>miss out on.</em></h2>
				<p>every sunday, something i am genuinely obsessed with lands in your inbox. this sunday book newsletter is a book of the week because i cannot stop thinking about it and need you to read it immediately.</p>
				<p>the smut and sentiment society is the archive, the inside access, and the romance reader community i cannot fit anywhere else.</p>
				<ul class="bbb-about-page__checks">
					<li>book of the week every sunday</li>
					<li>the archive of past obsessions</li>
					<li>exclusive reading lists</li>
					<li>reader polls and society-only extras</li>
					<li>private notes from a fellow romance obsessive</li>
				</ul>
				<a class="bbb-about-page__button bbb-about-page__button--gold" href="<?php echo esc_url($society_join_url); ?>" target="_blank" rel="noopener">join the society</a>
			</div>
			<aside class="bbb-about-page__societyCard bbb-about-page__reveal">
				<p class="bbb-about-page__kicker">straight from the society</p>
				<blockquote>"sundays are for letting fiction ruin me a little and pretending it is self care."</blockquote>
				<hr>
				<p>one curated romance rec from someone who read it twice and would read it again. where to get it. why it broke me. why it will break you too.</p>
				<a href="<?php echo esc_url(bbb_page_url('smut-sentiment-society')); ?>">learn more about the society</a>
			</aside>
		</div>
	</section>

	<section class="bbb-about-page__promise" aria-labelledby="bbb-about-promise-title">
		<div class="bbb-about-page__wrap bbb-about-page__reveal">
			<p class="bbb-about-page__badge">the bookish babe promise</p>
			<h2 id="bbb-about-promise-title">Real ratings.<br>
			Real reads.<br>
			Zero paid promotions.</h2>
			<p>every book here i have read. every rating i mean. every recommendation comes from the books i cannot stop thinking about.</p>
			<div class="bbb-about-page__ticker" aria-hidden="true">
				<div>
					<span>actually read</span>
					<span>honest spice ratings</span>
					<span>updated every sunday</span>
					<span>soft hearts, sinful taste</span>
					<span>actually read</span>
					<span>honest spice ratings</span>
					<span>updated every sunday</span>
					<span>soft hearts, sinful taste</span>
				</div>
			</div>
			<div class="bbb-about-page__actions bbb-about-page__actions--center">
				<a class="bbb-about-page__button" href="<?php echo esc_url(bbb_page_url('library')); ?>">find your next read</a>
				<a class="bbb-about-page__button bbb-about-page__button--ghost" href="<?php echo esc_url($society_join_url); ?>" target="_blank" rel="noopener">join the society</a>
			</div>
		</div>
	</section>

	<section class="bbb-about-page__section bbb-about-page__section--rose" aria-labelledby="bbb-about-explore-title">
		<div class="bbb-about-page__wrap">
			<div class="bbb-about-page__sectionHead bbb-about-page__reveal">
				<p class="bbb-about-page__kicker">keep exploring</p>
				<h2 id="bbb-about-explore-title">Find your corner <em>of the library.</em></h2>
			</div>
			<nav class="bbb-about-page__explore bbb-about-page__reveal" aria-label="Site sections">
				<?php foreach ($explore_links as $link) : ?>
					<a href="<?php echo esc_url($link['url']); ?>" <?php echo !empty($link['external']) ? 'target="_blank" rel="noopener"' : ''; ?>>
						<span><?php echo esc_html($link['label']); ?></span>
						<strong><?php echo esc_html($link['title']); ?></strong>
						<em><?php echo esc_html($link['meta']); ?></em>
					</a>
				<?php endforeach; ?>
			</nav>
		</div>
	</section>
</main>

<script>
(() => {
	const items = document.querySelectorAll('.bbb-about-page__reveal');
	if (!items.length || !('IntersectionObserver' in window)) {
		items.forEach((item) => item.classList.add('is-visible'));
		return;
	}
	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) {
				return;
			}
			entry.target.classList.add('is-visible');
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.14, rootMargin: '0px 0px -40px 0px' });
	items.forEach((item) => observer.observe(item));
})();
</script>

<?php
get_footer();
