<?php
/**
 * Template Name: Fictional Boyfriend Quiz
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_reader_quiz_enqueue_assets();
$books = bbb_reader_quiz_books();
$boyfriend_profiles = function_exists('bbb_reader_quiz_boyfriend_profiles') ? bbb_reader_quiz_boyfriend_profiles() : array();
$quiz_title = 'Fictional Boyfriend Quiz: Which Book Boyfriend Is Yours? (2026)';
$quiz_description = 'Take the fictional boyfriend quiz to find your book boyfriend type, then browse morally gray protectors, golden retrievers, rivals, broody softies, and romantasy matches.';
$quiz_url = home_url('/fictional-boyfriend-quiz/');
$quiz_image = get_theme_file_uri('assets/seo/share-cards/fictional-boyfriend-quiz.png');

add_filter('pre_get_document_title', static fn(string $title): string => $quiz_title, 100);
add_filter('rank_math/frontend/title', static fn(string $title): string => $quiz_title, 100);
add_filter('rank_math/frontend/description', static fn(string $description): string => $quiz_description, 100);
add_filter('rank_math/opengraph/facebook/title', static fn(string $title): string => $quiz_title, 100);
add_filter('rank_math/opengraph/facebook/description', static fn(string $description): string => $quiz_description, 100);
add_filter('rank_math/opengraph/twitter/title', static fn(string $title): string => $quiz_title, 100);
add_filter('rank_math/opengraph/twitter/description', static fn(string $description): string => $quiz_description, 100);
add_filter('rank_math/frontend/canonical', static fn(string $canonical): string => $quiz_url, 100);
add_filter('rank_math/opengraph/facebook/url', static fn(string $url): string => $quiz_url, 100);
add_filter('rank_math/opengraph/twitter/url', static fn(string $url): string => $quiz_url, 100);
add_filter('rank_math/opengraph/type', static fn(string $type): string => 'website', 100);
add_filter('rank_math/opengraph/facebook/image', static fn(string $image): string => $quiz_image, 100);
add_filter('rank_math/opengraph/twitter/image', static fn(string $image): string => $quiz_image, 100);
add_action(
	'wp_head',
	static function () use ($quiz_title, $quiz_description, $quiz_url): void {
		$schema = array(
			'@context'   => 'https://schema.org',
			'@graph'     => array(
				array(
					'@type'       => 'WebPage',
					'name'        => $quiz_title,
					'url'         => $quiz_url,
					'description' => $quiz_description,
					'isPartOf'    => array(
						'@type' => 'WebSite',
						'name'  => 'bybookishbabe',
						'url'   => home_url('/'),
					),
				),
			),
		);

		echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
	},
	30
);

$archetypes = array(
	array(
		'title' => 'the morally gray protector',
		'copy'  => 'dangerous to everyone else. devoted to one person. start here for dark romance, mafia, and touch-her-and-die energy.',
		'links' => array(
			array('dark romance books', home_url('/dark-romance-books/')),
			array('mafia romance books', home_url('/mafia-romance-books/')),
			array('fictional boyfriend profiles', home_url('/fictional-boyfriends/')),
		),
	),
	array(
		'title' => 'the golden retriever menace',
		'copy'  => 'falls first, texts back, remembers the tiny thing. your route for sports romance, friends to lovers, and soft devotion.',
		'links' => array(
			array('sports romance books', home_url('/sports-romance-books/')),
			array('friends to lovers books', home_url('/friends-to-lovers-books/')),
			array('he falls first romance books', home_url('/he-falls-first-romance-books/')),
		),
	),
	array(
		'title' => 'the rival with banter privileges',
		'copy'  => 'chemistry with friction first. go here for enemies to lovers, fake dating, forced proximity, and verbal sparring as foreplay.',
		'links' => array(
			array('enemies to lovers books', home_url('/enemies-to-lovers-books/')),
			array('fake dating romance books', home_url('/fake-dating-romance-books/')),
			array('forced proximity romance books', home_url('/forced-proximity-books/')),
		),
	),
	array(
		'title' => 'the broody wounded softie',
		'copy'  => 'quiet damage, huge payoff. best for slow burn, second chance, emotional wreckage, and men who need one thousand pages to say one feeling.',
		'links' => array(
			array('slow burn romance books', home_url('/slow-burn-romance-books/')),
			array('second chance romance books', home_url('/second-chance-romance-books/')),
			array('browse by spice level', home_url('/romance-books-by-spice-level/')),
		),
	),
	array(
		'title' => 'the cursed romantasy lead',
		'copy'  => 'curses, courts, bonds, monsters, wings, or throne problems. your portal to romantasy, paranormal, and fated chaos.',
		'links' => array(
			array('romantasy books', home_url('/romantasy-books/')),
			array('paranormal romance books', home_url('/paranormal-romance-books/')),
			array('romance trope dictionary', home_url('/romance-trope-dictionary/')),
		),
	),
);

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-livequiz bbb-livequiz--standard" id="fictional-boyfriend-quiz" data-reader-quiz data-quiz-standard data-quiz-type="boyfriend" data-sss-lib="public">
		<div class="bbb-livequiz__wrap">
			<nav class="bbb-livequiz__topbar" aria-label="quiz navigation">
				<a class="bbb-livequiz__back" href="<?php echo esc_url(home_url('/reader-quizzes/')); ?>"><span aria-hidden="true">←</span> back to all quizzes</a>
				<button class="bbb-livequiz__again" type="button" data-quiz-again>retake quiz</button>
				<button class="bbb-livequiz__shareBtn bbb-livequiz__shareBtn--result" type="button" data-quiz-share data-quiz-top-share aria-label="send to book bestie" title="send to book bestie">
					<span class="bbb-livequiz__shareText">send to book bestie</span>
					<span class="bbb-livequiz__shareIcon" aria-hidden="true">📲</span>
				</button>
			</nav>

			<header class="bbb-livequiz__hero">
				<p class="bbb-livequiz__kicker">reader quiz</p>
				<h1 class="bbb-livequiz__title">who is your fictional boyfriend?</h1>
				<p class="bbb-livequiz__sub">choose your chaos, your comfort, and the man currently ruining your standards. i will diagnose the type and hand you books that match.</p>
				<button class="bbb-livequiz__start" type="button" data-quiz-begin>meet him</button>
			</header>

			<div class="bbb-livequiz__track" data-quiz-track hidden>
				<div class="bbb-livequiz__progress" data-quiz-progress>
					<p data-quiz-progress-text>question 1 of 5</p>
					<span aria-hidden="true"><i data-quiz-progress-bar></i></span>
				</div>

				<section class="bbb-livequiz__slide is-active" data-quiz-slide>
					<p class="bbb-livequiz__count">question 01</p>
					<h2>what gets you first?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="golden:3,soft:2,hefalls:1">sweet and emotionally useful</button>
						<button type="button" data-quiz-answer data-score="gray:3,dark:2,protective:1">dangerous but devoted</button>
						<button type="button" data-quiz-answer data-score="rivals:3,tension:2,enemies:1">banter with bite</button>
						<button type="button" data-quiz-answer data-score="broody:3,slow:2,damage:1">quiet damage</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 02</p>
					<h2>pick the trope.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="fakeDating:3,forcedProx:2,rivals:1">fake dating that gets real</button>
						<button type="button" data-quiz-answer data-score="enemies:3,rivals:2,tension:2">enemies with eye contact</button>
						<button type="button" data-quiz-answer data-score="marriage:3,mafia:2,dark:1">marriage of convenience</button>
						<button type="button" data-quiz-answer data-score="singleDad:3,soft:2,golden:1">single dad devotion</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 03</p>
					<h2>where is he?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="sports:3,golden:1">on the team</button>
						<button type="button" data-quiz-answer data-score="mafia:3,dark:2,gray:1">in a dangerous family</button>
						<button type="button" data-quiz-answer data-score="fantasy:3,paranormal:2,protective:1">somewhere cursed</button>
						<button type="button" data-quiz-answer data-score="smallTown:3,soft:1,golden:1">somewhere small-town soft</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 04</p>
					<h2>his flaw?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="golden:2,hefalls:2,soft:1">too earnest</button>
						<button type="button" data-quiz-answer data-score="stalker:3,possessive:3,dark:2">too obsessive</button>
						<button type="button" data-quiz-answer data-score="broody:3,slow:2,damage:2">too guarded</button>
						<button type="button" data-quiz-answer data-score="billionaire:2,workplace:2,gray:1">too in control</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 05</p>
					<h2>how intense are we getting?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="soft:3,golden:2">soft swoon</button>
						<button type="button" data-quiz-answer data-score="spicy:3,tension:2,gray:1">high heat</button>
						<button type="button" data-quiz-answer data-score="dark:3,stalker:2,possessive:2">unhinged devotion</button>
						<button type="button" data-quiz-answer data-score="slow:3,rivals:2,tension:1">make me wait for it</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 06</p>
					<h2>what is the green flag?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="soft:3,golden:2,hefalls:1">he talks about feelings</button>
						<button type="button" data-quiz-answer data-score="protective:3,gray:1">he handles the threat</button>
						<button type="button" data-quiz-answer data-score="devoted:3,foundFamily:2,soft:1">he shows up every time</button>
						<button type="button" data-quiz-answer data-score="rivals:2,tension:2,fakeDating:1">he keeps up with you</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 07</p>
					<h2>tonight's ending?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="hefalls:3,golden:2,soft:1">he says it first</button>
						<button type="button" data-quiz-answer data-score="dark:2,protective:2,possessive:1">he burns it down for her</button>
						<button type="button" data-quiz-answer data-score="rivals:2,enemies:2,tension:2">the argument becomes a kiss</button>
						<button type="button" data-quiz-answer data-score="broody:2,damage:2,slow:2">the wall finally cracks</button>
					</div>
				</section>
			</div>

			<section class="bbb-livequiz__result" data-quiz-result hidden></section>
			<script type="application/json" data-quiz-books><?php echo wp_json_encode($books, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
			<script type="application/json" data-quiz-boyfriends><?php echo wp_json_encode($boyfriend_profiles, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
			</div>
		</section>
		<section class="bbb-quiz-seo" aria-labelledby="book-boyfriend-types">
			<div class="bbb-quiz-seo__wrap">
				<details class="bbb-quiz-seo__drawer">
					<summary>
						<span class="bbb-quiz-seo__kicker">updated june 2026</span>
						<span id="book-boyfriend-types">book boyfriend type dictionary</span>
					</summary>
					<div class="bbb-quiz-seo__drawerBody">
						<header class="bbb-quiz-seo__intro">
							<p>use this mini map as your next stop after the quiz: pick the type that feels dangerous, comforting, or personally inconvenient, then follow the links into books, tropes, and profiles.</p>
						</header>

						<div class="bbb-quiz-seo__grid" aria-label="book boyfriend archetypes">
							<?php foreach ($archetypes as $archetype) : ?>
								<article class="bbb-quiz-seo__type">
									<h3><?php echo esc_html($archetype['title']); ?></h3>
									<p><?php echo esc_html($archetype['copy']); ?></p>
									<div class="bbb-quiz-seo__links" aria-label="<?php echo esc_attr($archetype['title']); ?> links">
										<?php foreach ($archetype['links'] as $link) : ?>
											<a href="<?php echo esc_url($link[1]); ?>"><?php echo esc_html($link[0]); ?></a>
										<?php endforeach; ?>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</div>
				</details>
			</div>
		</section>
		<?php bbb_render_component('sss-library-modal'); ?>
	</main>

<?php
get_footer();
