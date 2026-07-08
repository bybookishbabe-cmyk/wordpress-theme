<?php
/**
 * Template Name: Reader Type Quiz
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_reader_quiz_enqueue_assets();

$books            = bbb_reader_quiz_books();
$account_url      = function_exists('bbb_page_url') ? bbb_page_url('account') : home_url('/account/');
$made_for_you_url = function_exists('bbb_page_url') ? bbb_page_url('made-for-you') : home_url('/made-for-you/');
$society_join_url = function_exists('bbb_substack_subscribe_url') ? bbb_substack_subscribe_url() : get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$society_join_url = '' !== trim((string) $society_join_url) ? (string) $society_join_url : 'https://thesmutandsentimentsociety.substack.com/subscribe';

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section
		class="bbb-livequiz bbb-livequiz--reader-type bbb-livequiz--standard"
		id="reader-type-quiz"
		data-reader-quiz
		data-quiz-standard
		data-quiz-type="reader-type"
		data-quiz-result-cta="reader-type"
		data-quiz-account-url="<?php echo esc_url($account_url); ?>"
		data-quiz-subscribe-url="<?php echo esc_url($society_join_url); ?>"
		data-quiz-recs-url="<?php echo esc_url($made_for_you_url); ?>"
		data-sss-lib="public"
	>
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
				<h1 class="bbb-livequiz__title">find your romance reader type</h1>
				<p class="bbb-livequiz__sub">answer a few trope, heat, and fictional-man questions. the result saves as a reader signal and turns into smarter recs when you open an account.</p>
				<button class="bbb-livequiz__start" type="button" data-quiz-begin>diagnosis me</button>
			</header>

			<div class="bbb-livequiz__track" data-quiz-track hidden>
				<div class="bbb-livequiz__progress" data-quiz-progress>
					<p data-quiz-progress-text>question 1 of 6</p>
					<span aria-hidden="true"><i data-quiz-progress-bar></i></span>
				</div>

				<section class="bbb-livequiz__slide is-active" data-quiz-slide>
					<p class="bbb-livequiz__count">question 01</p>
					<h2>what pulls you in first?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="tension:3,enemies:2,slow:1">banter sharp enough to leave marks</button>
						<button type="button" data-quiz-answer data-score="comfort:3,soft:2,friends:1">softness, safety, and a little ache</button>
						<button type="button" data-quiz-answer data-score="chaos:3,dark:2,spicy:1">dangerous devotion and bad decisions</button>
						<button type="button" data-quiz-answer data-score="escape:3,fantasy:2,paranormal:1">magic, monsters, curses, or wings</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 02</p>
					<h2>pick the man problem.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="gray:3,dark:2,protective:1">morally gray protector</button>
						<button type="button" data-quiz-answer data-score="golden:3,comfort:2,soft:1">golden retriever menace</button>
						<button type="button" data-quiz-answer data-score="rivals:3,tension:2,enemies:1">rival with banter privileges</button>
						<button type="button" data-quiz-answer data-score="sports:3,golden:1,hefalls:1">athlete with feelings he cannot hide</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 03</p>
					<h2>how messy can it get?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="sweet:3,comfort:2">low panic, high feelings</button>
						<button type="button" data-quiz-answer data-score="slow:3,tension:2">slow-burn restraint first</button>
						<button type="button" data-quiz-answer data-score="spicy:3,chaos:2,dark:1">make it hot and inconvenient</button>
						<button type="button" data-quiz-answer data-score="damage:3,cry:2,second:1">emotional damage with payoff</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 04</p>
					<h2>choose the shelf you keep circling.</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="contemporary:3,comfort:1,sweet:1">contemporary comfort</button>
						<button type="button" data-quiz-answer data-score="dark:3,chaos:2,gray:1">dark romance</button>
						<button type="button" data-quiz-answer data-score="fantasy:3,escape:2,paranormal:1">romantasy or paranormal</button>
						<button type="button" data-quiz-answer data-score="sports:3,golden:1,tension:1">sports romance</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 05</p>
					<h2>which trope owns you?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="fake:3,comfort:1,tension:1">fake dating with real feelings</button>
						<button type="button" data-quiz-answer data-score="proximity:3,tension:2,slow:1">forced proximity, obviously</button>
						<button type="button" data-quiz-answer data-score="second:3,damage:2,cry:1">second chance with receipts</button>
						<button type="button" data-quiz-answer data-score="friends:3,soft:2,sweet:1">friends to lovers softness</button>
					</div>
				</section>

				<section class="bbb-livequiz__slide" data-quiz-slide>
					<p class="bbb-livequiz__count">question 06</p>
					<h2>what should the algorithm remember?</h2>
					<div class="bbb-livequiz__answers">
						<button type="button" data-quiz-answer data-score="chaos:2,spicy:2,dark:1">i want the unhinged stack</button>
						<button type="button" data-quiz-answer data-score="comfort:2,sweet:2,soft:1">i want the soft landing</button>
						<button type="button" data-quiz-answer data-score="tension:2,slow:2,enemies:1">i want the slow-burn payoff</button>
						<button type="button" data-quiz-answer data-score="escape:2,fantasy:2,gray:1">i want full escape</button>
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
