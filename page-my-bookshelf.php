<?php
/**
 * Template Name: My Bookshelf
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$my_bookshelf_css_path = get_theme_file_path('assets/css/my-bookshelf.css');
$my_bookshelf_js_path  = get_theme_file_path('assets/js/my-bookshelf.js');
wp_enqueue_style('bbb-my-bookshelf', get_theme_file_uri('assets/css/my-bookshelf.css'), array('bbb-sss-library'), file_exists($my_bookshelf_css_path) ? (string) filemtime($my_bookshelf_css_path) : wp_get_theme()->get('Version'));
wp_enqueue_script('bbb-my-bookshelf', get_theme_file_uri('assets/js/my-bookshelf.js'), array('bbb-sss-library', 'bbb-supabase'), file_exists($my_bookshelf_js_path) ? (string) filemtime($my_bookshelf_js_path) : wp_get_theme()->get('Version'), true);

$is_society = function_exists('bbb_reader_is_society') ? bbb_reader_is_society() : false;
$account    = wp_get_current_user();
$books      = function_exists('bbb_reader_quiz_books') ? bbb_reader_quiz_books() : array();

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section
		class="bbb-account-shelf"
		data-account-shelf
		data-sss-lib="<?php echo esc_attr($is_society ? 'society' : 'public'); ?>"
		data-logged-in="<?php echo esc_attr(is_user_logged_in() ? 'true' : 'false'); ?>"
		data-customer-id="<?php echo esc_attr(is_user_logged_in() ? (string) get_current_user_id() : ''); ?>"
		data-customer-email="<?php echo esc_attr(is_user_logged_in() ? (string) $account->user_email : ''); ?>"
		data-is-society="<?php echo esc_attr($is_society ? 'true' : 'false'); ?>"
	>
		<div class="bbb-account-shelf__wrap">
			<div class="bbb-account-shelf__hero">
				<p class="bbb-account-shelf__kicker">your reader dashboard</p>
				<div class="bbb-account-shelf__memberBadge<?php echo $is_society ? ' bbb-account-shelf__memberBadge--secret' : ''; ?>" data-account-shelf-badge>
					<span aria-hidden="true"><?php echo esc_html($is_society ? '♥' : '📚'); ?></span>
					<span data-account-shelf-badge-label><?php echo esc_html($is_society ? 'secret society member' : 'guest reader'); ?></span>
				</div>
				<h1 class="bbb-account-shelf__title">my bookshelf</h1>
				<p class="bbb-account-shelf__sub">saved books, current obsessions, and the beginning of your personal romance archive.</p>

				<div class="bbb-account-shelf__actions">
					<a class="bbb-account-shelf__button" href="<?php echo esc_url(home_url($is_society ? '/sss-library-page/' : '/library/')); ?>">browse the library</a>
					<?php if (is_user_logged_in()) : ?>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : admin_url('profile.php')); ?>">account</a>
					<?php else : ?>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(wp_login_url(home_url('/my-bookshelf/'))); ?>">log in to sync</a>
					<?php endif; ?>
				</div>
			</div>

			<div class="bbb-account-shelf__status<?php echo is_user_logged_in() ? '' : ' bbb-account-shelf__status--login'; ?>" data-account-shelf-status>
				<div class="bbb-account-shelf__statusMain">
					<span class="bbb-account-shelf__statusIcon" aria-hidden="true">📚</span>
					<div>
						<strong><?php echo esc_html(is_user_logged_in() ? 'syncing your shelf...' : 'log in to keep your shelf across devices.'); ?></strong>
						<span data-account-shelf-status-copy><?php echo esc_html(is_user_logged_in() ? 'local saves show first, account saves follow when they load.' : 'you can still save books on this device, but an account makes the shelf yours everywhere.'); ?></span>
					</div>
				</div>
				<div class="bbb-account-shelf__tools" data-account-shelf-tools hidden>
					<button type="button" data-account-copy>copy list</button>
					<button type="button" data-account-email>email list</button>
				</div>
			</div>

			<div class="bbb-account-shelf__grid" data-account-shelf-grid></div>

			<div class="bbb-account-shelf__empty" data-account-shelf-empty hidden>
				<div class="bbb-account-shelf__emptyIcon" aria-hidden="true">📖</div>
				<h2>your shelf is waiting.</h2>
				<p>save a few books from the library and they’ll collect here.</p>
				<a href="<?php echo esc_url(home_url('/library/')); ?>">find your first save →</a>
			</div>

			<div class="bbb-account-shelf__perk">
				<p class="bbb-account-shelf__perkKicker" data-account-shelf-tier><?php echo esc_html($is_society ? 'society shelf' : 'free shelf'); ?></p>
				<h2 data-account-shelf-perk-title><?php echo esc_html($is_society ? 'your private reader layer is ready.' : 'save books now. unlock smarter recs later.'); ?></h2>
				<p data-account-shelf-perk-copy>
					<?php echo esc_html($is_society ? 'Society features can build from here: private notes, richer unlocks, mood shelves, and custom recommendations based on what you save.' : 'Free readers can keep a saved bookshelf. Society readers can become the private layer: richer notes, extra shelves, and future custom recommendation unlocks.'); ?>
				</p>
				<a href="<?php echo esc_url(home_url($is_society ? '/sss-library-page/' : '/smut-sentiment-society/')); ?>" data-account-shelf-perk-link><?php echo esc_html($is_society ? 'open the society library →' : 'enter the society →'); ?></a>
			</div>

			<script type="application/json" data-account-library-books><?php echo wp_json_encode($books, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
		</div>
	</section>
</main>

<?php
get_footer();
