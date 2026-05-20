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
	<section class="bbb-livequiz" id="fictional-boyfriend-quiz" data-reader-quiz data-quiz-type="boyfriend" data-sss-lib="public">
		<div class="bbb-livequiz__wrap">
			<nav class="bbb-livequiz__topbar" aria-label="quiz navigation">
				<a class="bbb-livequiz__back" href="<?php echo esc_url(home_url('/reader-quizes/')); ?>">back to all quizzes</a>
				<button class="bbb-livequiz__again" type="button" data-quiz-again>retake quiz</button>
			</nav>

			<header class="bbb-livequiz__hero">
				<p class="bbb-livequiz__kicker">reader quiz</p>
				<h1 class="bbb-livequiz__title">who is your fictional boyfriend?</h1>
				<p class="bbb-livequiz__sub">choose your chaos, your comfort, and the man currently ruining your standards. I will diagnose the type and hand you books that match.</p>
				<button class="bbb-livequiz__start" type="button" data-quiz-begin>meet him</button>
			</header>

			<div class="bbb-livequiz__track" data-quiz-track hidden>
				<section class="bbb-livequiz__slide is-active" data-quiz-slide>
					<p class="bbb-livequiz__count">question 01</p>
					<h2>what pulls you in first?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="golden:3,soft:1,sports:1">the sweet one who notices everything</button>
						<button type="button" data-quiz-answer data-score="gray:3,dark:2,protective:1">the dangerous one with a soft spot</button>
						<button type="button" data-quiz-answer data-score="rivals:3,tension:2,enemies:1">the one who argues like flirting is a contact sport</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 02</p>
					<h2>pick the romantic problem.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="sports:3,golden:2,hefalls:1">he falls first and makes it everyone's problem</button>
						<button type="button" data-quiz-answer data-score="dark:3,gray:2,protective:2">touch her and die energy, legally concerning</button>
						<button type="button" data-quiz-answer data-score="slow:3,damage:2,broody:1">the slow burn hurts on purpose</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 03</p>
					<h2>where are we meeting him?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="sports:3,golden:1">rink, court, field, team bus</button>
						<button type="button" data-quiz-answer data-score="dark:3,gray:2">a place with secrets and bad decisions</button>
						<button type="button" data-quiz-answer data-score="fantasy:3,protective:1">somewhere cursed, winged, or slightly immortal</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 04</p>
					<h2>what is his fatal flaw?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="golden:2,hefalls:2,soft:1">he cares too loudly</button>
						<button type="button" data-quiz-answer data-score="gray:3,dark:2">he thinks obsession counts as a plan</button>
						<button type="button" data-quiz-answer data-score="broody:3,slow:2,damage:1">he would rather suffer than explain himself</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 05</p>
					<h2>what ending do you want tonight?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="golden:2,soft:2">soft landing, hard swoon</button>
						<button type="button" data-quiz-answer data-score="dark:2,gray:2,spicy:1">unhinged devotion and spice</button>
						<button type="button" data-quiz-answer data-score="rivals:2,tension:2,slow:1">banter, ache, payoff</button>
					</div>
				</section>
			</div>

			<section class="bbb-livequiz__result" data-quiz-result hidden></section>
			<script type="application/json" data-quiz-books><?php echo wp_json_encode($books, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
		</div>
	</section>
</main>

<?php
get_footer();
