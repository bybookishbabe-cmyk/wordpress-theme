<?php
/**
 * Template Name: Reader Mood Quiz
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_reader_quiz_enqueue_assets();
$books = bbb_reader_quiz_books();

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-livequiz bbb-livequiz--mood bbb-livequiz--standard" id="reader-mood-quiz" data-reader-quiz data-quiz-standard data-quiz-type="mood" data-sss-lib="public">
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
				<h1 class="bbb-livequiz__title">what should you read based on your mood?</h1>
				<p class="bbb-livequiz__sub">tell me the mood, heat, and damage level. i will build the stack.</p>
				<button class="bbb-livequiz__start" type="button" data-quiz-begin>find my read</button>
			</header>

			<div class="bbb-livequiz__track" data-quiz-track hidden>
				<div class="bbb-livequiz__progress" data-quiz-progress>
					<p data-quiz-progress-text>question 1 of 5</p>
					<span aria-hidden="true"><i data-quiz-progress-bar></i></span>
				</div>

				<section class="bbb-livequiz__slide is-active" data-quiz-slide>
					<p class="bbb-livequiz__count">question 01</p>
					<h2>what kind of night?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="comfort:3,soft:2,contemporary:1">comfort with ache</button>
						<button type="button" data-quiz-answer data-score="chaos:3,dark:2,spicy:1">chaos and consequences</button>
						<button type="button" data-quiz-answer data-score="escape:3,fantasy:2,paranormal:1">somewhere impossible</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 02</p>
					<h2>spice level?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="sweet:3,comfort:1">low heat, high feelings</button>
						<button type="button" data-quiz-answer data-score="medium:3,tension:1">give me slow tension first</button>
						<button type="button" data-quiz-answer data-score="spicy:3,chaos:1">make it a problem</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 03</p>
					<h2>pick the ache.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="friends:2,comfort:2,soft:1">soft healing</button>
						<button type="button" data-quiz-answer data-score="enemies:2,tension:2,chaos:1">rival tension</button>
						<button type="button" data-quiz-answer data-score="cry:3,damage:2,slow:1">emotional damage</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 04</p>
					<h2>which shelf?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="contemporary:3,comfort:1">contemporary romance</button>
						<button type="button" data-quiz-answer data-score="dark:3,chaos:1">dark romance</button>
						<button type="button" data-quiz-answer data-score="fantasy:3,escape:1">romantasy or paranormal</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 05</p>
					<h2>leave me with?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="comfort:2,soft:2">soft smile</button>
						<button type="button" data-quiz-answer data-score="chaos:2,spicy:2">locked-door chaos</button>
						<button type="button" data-quiz-answer data-score="cry:2,damage:2,escape:1">romantic damage</button>
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
