<?php
/**
 * Template Name: Romance Trope Quiz
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_reader_quiz_enqueue_assets();
$books = bbb_reader_quiz_books();

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-livequiz bbb-livequiz--trope bbb-livequiz--standard" id="romance-trope-quiz" data-reader-quiz data-quiz-standard data-quiz-type="trope" data-sss-lib="public">
		<div class="bbb-livequiz__wrap">
			<nav class="bbb-livequiz__topbar" aria-label="quiz navigation">
				<a class="bbb-livequiz__back" href="<?php echo esc_url(home_url('/reader-quizzes/')); ?>"><span aria-hidden="true">←</span> back to all quizzes</a>
				<button class="bbb-livequiz__again" type="button" data-quiz-again>retake quiz</button>
				<button class="bbb-livequiz__shareBtn bbb-livequiz__shareBtn--result" type="button" data-quiz-share data-quiz-top-share aria-label="share your result" title="share your result">
					<span class="bbb-livequiz__shareText">share your result</span>
					<span class="bbb-livequiz__shareIcon" aria-hidden="true">📱</span>
				</button>
			</nav>

			<header class="bbb-livequiz__hero">
				<p class="bbb-livequiz__kicker">reader quiz</p>
				<h1 class="bbb-livequiz__title">what romance trope are you?</h1>
				<p class="bbb-livequiz__sub">choose the tension, setup, and payoff. i will tell you which trope is running your life.</p>
				<button class="bbb-livequiz__start" type="button" data-quiz-begin>find my trope</button>
			</header>

			<div class="bbb-livequiz__track" data-quiz-track hidden>
				<div class="bbb-livequiz__progress" data-quiz-progress>
					<p data-quiz-progress-text>question 1 of 5</p>
					<span aria-hidden="true"><i data-quiz-progress-bar></i></span>
				</div>

				<section class="bbb-livequiz__slide is-active" data-quiz-slide>
					<p class="bbb-livequiz__count">question 01</p>
					<h2>pick your tension.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="enemies:1">⚔️ banter that bites</button>
						<button type="button" data-quiz-answer data-score="friends:1">🤍 best friend butterflies</button>
						<button type="button" data-quiz-answer data-score="proximity:1">🚪 stuck together</button>
						<button type="button" data-quiz-answer data-score="fake:1">💍 pretend dating</button>
						<button type="button" data-quiz-answer data-score="second:1">💔 unfinished history</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 02</p>
					<h2>pick the setup.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="enemies:1">⚔️ rival project</button>
						<button type="button" data-quiz-answer data-score="friends:1">🤍 late-night hangout</button>
						<button type="button" data-quiz-answer data-score="proximity:1">🚪 one bed</button>
						<button type="button" data-quiz-answer data-score="fake:1">💍 fake date</button>
						<button type="button" data-quiz-answer data-score="second:1">💔 ex walks in</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 03</p>
					<h2>pick the moment.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="enemies:1">⚔️ argument almost turns into a kiss</button>
						<button type="button" data-quiz-answer data-score="friends:1">🤍 a touch feels different</button>
						<button type="button" data-quiz-answer data-score="proximity:1">🚪 sharing a small space</button>
						<button type="button" data-quiz-answer data-score="fake:1">💍 fake kiss feels real</button>
						<button type="button" data-quiz-answer data-score="second:1">💔 old song, old feelings</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 04</p>
					<h2>pick the problem.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="enemies:1">⚔️ too much pride</button>
						<button type="button" data-quiz-answer data-score="friends:1">🤍 too much to lose</button>
						<button type="button" data-quiz-answer data-score="proximity:1">🚪 nowhere to hide</button>
						<button type="button" data-quiz-answer data-score="fake:1">💍 feelings break the rules</button>
						<button type="button" data-quiz-answer data-score="second:1">💔 the past is loud</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 05</p>
					<h2>pick the payoff.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="enemies:1">⚔️ rivalry turns devoted</button>
						<button type="button" data-quiz-answer data-score="friends:1">🤍 home becomes romance</button>
						<button type="button" data-quiz-answer data-score="proximity:1">🚪 forced becomes chosen</button>
						<button type="button" data-quiz-answer data-score="fake:1">💍 pretend becomes real</button>
						<button type="button" data-quiz-answer data-score="second:1">💔 love comes back better</button>
					</div>
				</section>
			</div>

			<section class="bbb-livequiz__result" data-quiz-result hidden></section>
			<script type="application/json" data-quiz-books><?php echo wp_json_encode($books, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
		</div>

	</section>
	<?php bbb_render_component('sss-library-modal'); ?>
</main>

<?php
get_footer();
