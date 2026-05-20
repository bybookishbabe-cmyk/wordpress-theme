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
	<section class="bbb-livequiz bbb-livequiz--mood" id="reader-mood-quiz" data-reader-quiz data-quiz-type="mood" data-sss-lib="public">
		<div class="bbb-livequiz__wrap">
			<nav class="bbb-livequiz__topbar" aria-label="quiz navigation">
				<a class="bbb-livequiz__back" href="<?php echo esc_url(home_url('/reader-quizes/')); ?>">back to all quizzes</a>
				<button class="bbb-livequiz__again" type="button" data-quiz-again>retake quiz</button>
			</nav>

			<header class="bbb-livequiz__hero">
				<p class="bbb-livequiz__kicker">reader quiz</p>
				<h1 class="bbb-livequiz__title">what should you read based on your mood?</h1>
				<p class="bbb-livequiz__sub">tell me the emotional weather, the spice tolerance, and the level of damage you can survive. I will build the stack.</p>
				<button class="bbb-livequiz__start" type="button" data-quiz-begin>find my read</button>
			</header>

			<div class="bbb-livequiz__track" data-quiz-track hidden>
				<section class="bbb-livequiz__slide is-active" data-quiz-slide>
					<p class="bbb-livequiz__count">question 01</p>
					<h2>what kind of night is this?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="comfort:3,soft:2,contemporary:1">i need comfort with a little ache</button>
						<button type="button" data-quiz-answer data-score="chaos:3,dark:2,spicy:1">i want chaos and consequences</button>
						<button type="button" data-quiz-answer data-score="escape:3,fantasy:2,paranormal:1">take me somewhere impossible</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 02</p>
					<h2>how much spice are we permitting?</h2>
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
						<button type="button" data-quiz-answer data-score="friends:2,comfort:2,soft:1">friends, found family, soft healing</button>
						<button type="button" data-quiz-answer data-score="enemies:2,tension:2,chaos:1">rivals, enemies, no one is calm</button>
						<button type="button" data-quiz-answer data-score="cry:3,damage:2,slow:1">i am willing to be emotionally rearranged</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 04</p>
					<h2>which shelf are you reaching toward?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="contemporary:3,comfort:1">contemporary romance</button>
						<button type="button" data-quiz-answer data-score="dark:3,chaos:1">dark romance</button>
						<button type="button" data-quiz-answer data-score="fantasy:3,escape:1">romantasy or paranormal</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 05</p>
					<h2>what should the book leave behind?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="comfort:2,soft:2">a little smile and a softer nervous system</button>
						<button type="button" data-quiz-answer data-score="chaos:2,spicy:2">a raised eyebrow and a locked door</button>
						<button type="button" data-quiz-answer data-score="cry:2,damage:2,escape:1">a thousand-yard stare, but romantically</button>
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
