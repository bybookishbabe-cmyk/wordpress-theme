<?php
/**
 * Template Name: Fictional Boyfriend Quiz
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_reader_quiz_enqueue_assets();
$books = bbb_reader_quiz_books();

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-livequiz bbb-livequiz--standard" id="fictional-boyfriend-quiz" data-reader-quiz data-quiz-standard data-quiz-type="boyfriend" data-sss-lib="public">
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
						<button type="button" data-quiz-answer data-score="golden:3,soft:1,sports:1">sweet and observant</button>
						<button type="button" data-quiz-answer data-score="gray:3,dark:2,protective:1">dangerous but soft for her</button>
						<button type="button" data-quiz-answer data-score="rivals:3,tension:2,enemies:1">banter that bites</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 02</p>
					<h2>pick the problem.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="sports:3,golden:2,hefalls:1">he falls first</button>
						<button type="button" data-quiz-answer data-score="dark:3,gray:2,protective:2">touch her and die</button>
						<button type="button" data-quiz-answer data-score="slow:3,damage:2,broody:1">slow burn pain</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 03</p>
					<h2>where is he?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="sports:3,golden:1">on the team</button>
						<button type="button" data-quiz-answer data-score="dark:3,gray:2">somewhere secret</button>
						<button type="button" data-quiz-answer data-score="fantasy:3,protective:1">somewhere cursed</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 04</p>
					<h2>his flaw?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="golden:2,hefalls:2,soft:1">too earnest</button>
						<button type="button" data-quiz-answer data-score="gray:3,dark:2">too obsessive</button>
						<button type="button" data-quiz-answer data-score="broody:3,slow:2,damage:1">too guarded</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 05</p>
					<h2>tonight's ending?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="golden:2,soft:2">soft swoon</button>
						<button type="button" data-quiz-answer data-score="dark:2,gray:2,spicy:1">unhinged devotion</button>
						<button type="button" data-quiz-answer data-score="rivals:2,tension:2,slow:1">banter payoff</button>
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
