<?php
/**
 * Template Name: Reading Challenge
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$challenge_css_path = get_theme_file_path('assets/css/reading-challenge.css');
$challenge_js_path  = get_theme_file_path('assets/js/reading-challenge.js');

wp_enqueue_style('bbb-reading-challenge', get_theme_file_uri('assets/css/reading-challenge.css'), array('bbb-base'), file_exists($challenge_css_path) ? (string) filemtime($challenge_css_path) : wp_get_theme()->get('Version'));
wp_enqueue_script('bbb-reading-challenge', get_theme_file_uri('assets/js/reading-challenge.js'), array(), file_exists($challenge_js_path) ? (string) filemtime($challenge_js_path) : wp_get_theme()->get('Version'), true);

if (!function_exists('bbb_reading_challenge_cover_urls')) {
	function bbb_reading_challenge_cover_urls(int $limit = 5): array {
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

		return array_slice($covers, 0, $limit);
	}
}

$prompts = array(
	array(
		'n'     => 1,
		'cat'   => 'library',
		'emoji' => '📚',
		'text'  => 'choose your book from the library. the one you have been circling. commit to it.',
		'detail'=> 'start in the bybookishbabe library and pick the book you keep almost choosing. this week is the commitment card: title, trope, spice guess, and the reason you know it is going to be a problem.',
		'url'   => home_url('/library/'),
		'label' => 'browse the library',
	),
	array(
		'n'     => 2,
		'cat'   => 'bookshelf',
		'emoji' => '✍️',
		'text'  => 'rate + review it in your own words. your take, your spice rating, saved to your bookshelf.',
		'detail'=> 'leave receipts. write the review you would text your best friend: spice, damage, favorite trope, and the exact moment you knew you were in trouble.',
		'url'   => home_url('/my-bookshelf/'),
		'label' => 'open your bookshelf',
	),
	array(
		'n'     => 3,
		'cat'   => 'download',
		'emoji' => '💌',
		'text'  => 'make the book card. print it, give it to your bestie. your rec, your handwriting, their problem now.',
		'detail'=> 'this week unlocks a printable “you need to read this” card. add your rec, the trope warning, and one dramatic sentence. hand it over like evidence.',
		'url'   => home_url('/shop/'),
		'label' => 'browse reader downloads',
	),
	array(
		'n'     => 4,
		'cat'   => 'quiz',
		'emoji' => '🎭',
		'text'  => 'take the quiz. find your book boyfriend. share your result.',
		'detail'=> 'week four is your official diagnosis. take the fictional boyfriend quiz, accept whatever it reveals, and post the result if you are brave enough.',
		'url'   => home_url('/reader-quizzes/'),
		'label' => 'take the quiz',
	),
	array(
		'n'     => 5,
		'cat'   => 'series',
		'emoji' => '🗂️',
		'text'  => 'build your perfect series order. pick a series from the reading orders, then make it yours.',
		'detail'=> 'choose a series and map your route: main books, bonus books, novellas, chaos order if necessary. this is planning disguised as a spiral.',
		'url'   => home_url('/series-reading-orders/'),
		'label' => 'choose a series',
	),
	array(
		'n'     => 6,
		'cat'   => 'template',
		'emoji' => '📱',
		'text'  => 'use the currently reading story card. show exactly what you are reading, spice level and all.',
		'detail'=> 'unlock a shareable story template for the book currently causing problems. title, mood, spice level, and whether you are emotionally stable. be honest.',
		'url'   => home_url('/shop/'),
		'label' => 'open the templates',
	),
	array(
		'n'     => 7,
		'cat'   => 'society',
		'emoji' => '🔥',
		'text'  => 'write your most unhinged book rec. one sentence, no context, maximum chaos energy.',
		'detail'=> 'submit the sentence that would make someone immediately ask “wait, what book is this?” the best ones can be featured in the society or on the site.',
		'url'   => home_url('/society-submissions/'),
		'label' => 'submit your chaos rec',
	),
	array(
		'n'     => 8,
		'cat'   => 'weekly obsession',
		'emoji' => '⭐',
		'text'  => 'nominate a book for the weekly obsession. tell the society why it deserves the spotlight.',
		'detail'=> 'this is your campaign speech. nominate the book, defend the obsession, and tell me why it should be considered for a weekly obsession feature.',
		'url'   => home_url('/weekly-obsession/'),
		'label' => 'visit weekly obsession',
	),
	array(
		'n'     => 9,
		'cat'   => 'wrap card',
		'emoji' => '🧾',
		'text'  => 'make the wrap card. a mid-challenge recap of everything you have read so far.',
		'detail'=> 'build your damage report: books read, top trope, highest spice, favorite man you probably should not defend, and the book still living in your head.',
		'url'   => home_url('/my-bookshelf/'),
		'label' => 'check your bookshelf',
	),
	array(
		'n'     => 10,
		'cat'   => 'reward',
		'emoji' => '🎁',
		'text'  => 'unlock the coupon. you earned it: shop reward, free download, or society upgrade offer.',
		'detail'=> 'the finale is the reward week. finish the challenge and unlock the thank-you: a coupon, a free download, or a society upgrade offer.',
		'url'   => home_url('/shop/'),
		'label' => 'claim the reward',
	),
);

$cover_images = bbb_reading_challenge_cover_urls();
$mockup_url   = get_theme_file_uri('assets/freebies/may-2026-bookend-8x10-art-print-mockup.jpg');

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-rc" id="reading-challenge" data-bbb-reading-challenge>
		<div class="bbb-rc__hero">
			<div class="bbb-rc__wrap bbb-rc__heroGrid">
				<div class="bbb-rc__heroCopy">
					<p class="bbb-rc__eyebrow">smut &amp; sentiment society · summer 2026</p>
					<h1 class="bbb-rc__title">we are entering a ruin me summer</h1>
					<p class="bbb-rc__sub">10 weeks of book recs, downloads, sunday prompts, and tiny reading assignments designed to make your kindle look deeply suspicious.</p>
					<div class="bbb-rc__heroActions">
					<a class="bbb-rc__btn bbb-rc__btn--light" href="#challenge-tracker">start the challenge</a>
						<button class="bbb-rc__btn bbb-rc__btn--ghost" type="button" data-bbb-rc-open-tracker>get the tracker</button>
					</div>
				</div>
				<div class="bbb-rc__stage" aria-label="reading challenge preview">
					<div class="bbb-rc__stageGlow" aria-hidden="true"></div>
					<div class="bbb-rc__coverStack" aria-hidden="true">
						<?php foreach (array_slice($cover_images, 0, 3) as $index => $cover) : ?>
							<img class="bbb-rc__cover bbb-rc__cover--<?php echo esc_attr((string) ($index + 1)); ?>" src="<?php echo esc_url($cover['url']); ?>" alt="">
						<?php endforeach; ?>
					</div>
					<div class="bbb-rc__trackerPreview">
						<p>ruin me tracker</p>
						<strong>10 prompts</strong>
						<span>dark romance · spice · slow burn · reader choice</span>
						<div>
							<i></i><i></i><i></i><i></i><i></i>
						</div>
					</div>
					<div class="bbb-rc__photoCard">
						<img src="<?php echo esc_url($mockup_url); ?>" alt="staged printable tracker preview" loading="lazy">
						<span>printable tracker preview</span>
					</div>
				</div>
			</div>
		</div>

		<div class="bbb-rc__stats" aria-label="challenge details">
			<div><strong>10</strong><span>weeks</span></div>
			<div><strong>book recs</strong><span>every prompt</span></div>
			<div><strong>downloads</strong><span>tracker + extras</span></div>
			<div><strong>sunday</strong><span>weekly fun</span></div>
		</div>

		<div class="bbb-rc__wrap bbb-rc__intro">
			<div class="bbb-rc__introMain">
				<p class="bbb-rc__kicker">what this actually is</p>
				<h2>not a reading sprint. a curated spiral.</h2>
				<ul class="bbb-rc__whoList" aria-label="who the reading challenge is for">
					<li>for the reader who says “i’m fine” and then starts a 600-page dark romance at midnight</li>
					<li>for the kindle unlimited girlies who need structure, not restraint</li>
					<li>for anyone who loves a little weekly assignment with a lot of emotional consequences</li>
					<li>for the soft hearts with suspiciously specific fictional-men standards</li>
				</ul>
			</div>
			<aside class="bbb-rc__calendar" aria-label="june challenge preview">
				<div class="bbb-rc__calendarTop">
					<p>june preview</p>
					<strong>the weekly fun</strong>
				</div>
				<div class="bbb-rc__calendarGrid" aria-hidden="true">
					<span>sun</span><span>mon</span><span>tue</span><span>wed</span><span>thu</span><span>fri</span><span>sat</span>
					<b></b><i class="is-week is-week-1">1</i><i>2</i><i>3</i><i>4</i><i>5</i><i>6</i>
					<i>7</i><i class="is-week is-week-2">8</i><i>9</i><i>10</i><i>11</i><i>12</i><i>13</i>
					<i>14</i><i class="is-week is-week-3">15</i><i>16</i><i>17</i><i>18</i><i>19</i><i>20</i>
					<i>21</i><i class="is-week is-week-4">22</i><i>23</i><i>24</i><i>25</i><i>26</i><i>27</i>
					<i>28</i><i class="is-week is-week-5">29</i><i>30</i>
				</div>
				<div class="bbb-rc__calendarNotes">
					<p><span>june 1</span> choose your library read</p>
					<p><span>june 8</span> rate it + save the review</p>
					<p><span>june 15</span> unlock the book card download</p>
					<p><span>june 22</span> take the boyfriend quiz</p>
				</div>
			</aside>
		</div>

		<section class="bbb-rc__how" aria-labelledby="reading-challenge-how-title">
			<div class="bbb-rc__wrap bbb-rc__howGrid">
				<div>
					<p class="bbb-rc__kicker">how this works.</p>
					<h2 id="reading-challenge-how-title">every sunday, the next little assignment drops.</h2>
				</div>
				<div class="bbb-rc__howCard">
					<p>the weekly note goes out inside the smut &amp; sentiment society with this week’s prompt, book recs, and the tiny nudge you probably needed.</p>
					<p>then the matching week unlocks here on the website, so you can track it, grab the downloads, and keep your summer spiral beautifully organized.</p>
					<a class="bbb-rc__btn bbb-rc__btn--light" href="<?php echo esc_url(home_url('/smut-sentiment-society/')); ?>">join the society</a>
				</div>
			</div>
		</section>

		<section class="bbb-rc__tracker" id="challenge-tracker" aria-labelledby="challenge-tracker-title">
			<div class="bbb-rc__wrap">
				<header class="bbb-rc__trackerHead">
					<div>
						<p class="bbb-rc__kicker">the tracker</p>
						<h2 id="challenge-tracker-title">the fun starts june 01</h2>
					</div>
					<div class="bbb-rc__progress" aria-live="polite">
						<span data-bbb-rc-count>0 of 10</span>
						<div class="bbb-rc__progressTrack"><span data-bbb-rc-fill></span></div>
					</div>
				</header>

				<div class="bbb-rc__promptList">
					<?php foreach ($prompts as $prompt) : ?>
						<article class="bbb-rc__prompt" data-bbb-rc-prompt data-bbb-rc-number="<?php echo esc_attr((string) $prompt['n']); ?>" data-bbb-rc-category="<?php echo esc_attr((string) $prompt['cat']); ?>" data-bbb-rc-detail="<?php echo esc_attr((string) $prompt['detail']); ?>" data-bbb-rc-link="<?php echo esc_url((string) $prompt['url']); ?>" data-bbb-rc-link-label="<?php echo esc_attr((string) $prompt['label']); ?>">
							<button class="bbb-rc__check" type="button" data-bbb-rc-toggle aria-label="<?php echo esc_attr('mark prompt ' . (string) $prompt['n'] . ' complete'); ?>">
								<span></span>
							</button>
							<div class="bbb-rc__num"><?php echo esc_html(str_pad((string) $prompt['n'], 2, '0', STR_PAD_LEFT)); ?></div>
							<div class="bbb-rc__promptBody">
								<p class="bbb-rc__tag">
									<strong><?php echo esc_html('week ' . str_pad((string) $prompt['n'], 2, '0', STR_PAD_LEFT)); ?></strong>
									<span><?php echo esc_html((string) $prompt['emoji']); ?></span><?php echo esc_html((string) $prompt['cat']); ?>
								</p>
								<h3><?php echo esc_html((string) $prompt['text']); ?></h3>
								<div class="bbb-rc__promptFoot">
									<a href="<?php echo esc_url((string) $prompt['url']); ?>"><?php echo esc_html((string) $prompt['label']); ?> →</a>
									<button type="button" data-bbb-rc-open-prompt>peek inside</button>
									<label>
										<span>what did you read?</span>
										<input type="text" data-bbb-rc-note placeholder="title / author">
									</label>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<div class="bbb-rc__complete" data-bbb-rc-complete hidden>
					<p class="bbb-rc__kicker">completed</p>
					<h2>you survived. barely.</h2>
					<p>ten books, ten prompts, and a reading history that probably needs its own private folder. download the badge when it is ready, then send yourself somewhere even more specific.</p>
					<button class="bbb-rc__btn bbb-rc__btn--ghost" type="button" data-bbb-rc-open-tracker>claim the badge</button>
				</div>
			</div>
		</section>

		<section class="bbb-rc__capture" aria-labelledby="challenge-tracker-capture">
			<div class="bbb-rc__wrap bbb-rc__captureGrid">
				<div>
					<p class="bbb-rc__kicker">printable version</p>
					<h2 id="challenge-tracker-capture">want the tracker in your inbox?</h2>
					<p>i will send the printable tracker, the full prompt list, and the sunday nudges when the challenge is ready to run live.</p>
				</div>
				<form class="bbb-rc__form" action="<?php echo esc_url(home_url('/contact/')); ?>" method="get">
					<label for="bbb-rc-email">email</label>
					<input id="bbb-rc-email" type="email" name="email" placeholder="your@email.com" required>
					<input type="hidden" name="source" value="reading-challenge">
					<button class="bbb-rc__btn bbb-rc__btn--light" type="submit">send me the tracker</button>
					<p>no spam. just the kind of sunday note that knows exactly why your kindle is at two percent.</p>
				</form>
			</div>
		</section>

		<div class="bbb-rc__modal" data-bbb-rc-prompt-modal hidden>
			<button class="bbb-rc__modalScrim" type="button" data-bbb-rc-close aria-label="close prompt preview"></button>
			<div class="bbb-rc__modalPanel" role="dialog" aria-modal="true" aria-labelledby="bbb-rc-modal-title">
				<button class="bbb-rc__modalClose" type="button" data-bbb-rc-close aria-label="close">×</button>
				<p class="bbb-rc__kicker" data-bbb-rc-modal-cat>prompt</p>
				<h2 id="bbb-rc-modal-title" data-bbb-rc-modal-title></h2>
				<p data-bbb-rc-modal-detail></p>
				<div class="bbb-rc__modalActions">
					<a class="bbb-rc__btn bbb-rc__btn--light" href="#" data-bbb-rc-modal-link>open the shelf</a>
					<button class="bbb-rc__btn bbb-rc__btn--ghost" type="button" data-bbb-rc-close>keep browsing</button>
				</div>
			</div>
		</div>

		<div class="bbb-rc__modal" data-bbb-rc-tracker-modal hidden>
			<button class="bbb-rc__modalScrim" type="button" data-bbb-rc-close aria-label="close tracker signup"></button>
			<div class="bbb-rc__modalPanel bbb-rc__modalPanel--tracker" role="dialog" aria-modal="true" aria-labelledby="bbb-rc-tracker-title">
				<button class="bbb-rc__modalClose" type="button" data-bbb-rc-close aria-label="close">×</button>
				<p class="bbb-rc__kicker">tracker drop</p>
				<h2 id="bbb-rc-tracker-title">send me the pretty version.</h2>
				<p>the printable tracker, prompt list, and badge flow will live here once we wire the automation. for now, this is the pop-up experience and visual system.</p>
				<form class="bbb-rc__form" action="<?php echo esc_url(home_url('/contact/')); ?>" method="get">
					<label for="bbb-rc-modal-email">email</label>
					<input id="bbb-rc-modal-email" type="email" name="email" placeholder="your@email.com" required>
					<input type="hidden" name="source" value="reading-challenge-modal">
					<button class="bbb-rc__btn bbb-rc__btn--light" type="submit">send the tracker</button>
				</form>
			</div>
		</div>

		<section class="bbb-rc__after" aria-label="where to go next">
			<div class="bbb-rc__wrap">
				<p class="bbb-rc__kicker">after the last page</p>
				<div class="bbb-rc__afterGrid">
					<a href="<?php echo esc_url(home_url('/shop/')); ?>">
						<span>track it beautifully</span>
						<em>kindle inserts, templates, and reader tools for the damage archive.</em>
					</a>
					<a href="<?php echo esc_url(home_url('/smut-sentiment-society/')); ?>">
						<span>join the society</span>
						<em>weekly obsessions, deeper picks, and a more organized version of the spiral.</em>
					</a>
					<a href="<?php echo esc_url(home_url('/library/')); ?>">
						<span>browse the library</span>
						<em>when the prompt is simply: choose the one you cannot stop thinking about.</em>
					</a>
				</div>
			</div>
		</section>

		<script type="application/json" data-bbb-rc-prompts><?php echo wp_json_encode($prompts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
	</section>
</main>

<?php
get_footer();
