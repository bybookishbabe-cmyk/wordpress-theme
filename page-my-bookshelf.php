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

if (!function_exists('bbb_my_bookshelf_quote_text')) {
	function bbb_my_bookshelf_quote_text(WP_Post $quote): string {
		$text = trim((string) get_post_meta($quote->ID, '_quote_text', true));
		$text = '' !== $text ? $text : trim((string) get_post_meta($quote->ID, 'quote_text', true));
		$text = '' !== $text ? $text : trim((string) get_post_meta($quote->ID, 'quote', true));
		$text = '' !== $text ? $text : trim((string) get_post_meta($quote->ID, '_bbb_quote', true));
		$text = '' !== $text ? $text : trim(wp_strip_all_tags($quote->post_content));

		return wp_strip_all_tags($text);
	}
}

if (!function_exists('bbb_my_bookshelf_quote_entries')) {
	function bbb_my_bookshelf_quote_entries(int $limit = 16): array {
		$entries          = array();
		$quote_post_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array();
		$quotes           = $quote_post_types
			? get_posts(
				array(
					'post_type'      => $quote_post_types,
					'post_status'    => 'publish',
					'posts_per_page' => $limit,
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			)
			: array();

		foreach ($quotes as $quote) {
			if (!$quote instanceof WP_Post) {
				continue;
			}

			$text = bbb_my_bookshelf_quote_text($quote);
			if ('' === $text) {
				continue;
			}

			$book_title  = (string) get_post_meta($quote->ID, '_quote_book_title', true);
			$book_title  = '' !== $book_title ? $book_title : (string) get_post_meta($quote->ID, 'book_title', true);
			$book_handle = (string) get_post_meta($quote->ID, '_quote_book_handle', true);
			$book_handle = '' !== $book_handle ? $book_handle : (string) get_post_meta($quote->ID, 'book_handle', true);
			$book_handle = '' !== $book_handle ? $book_handle : (string) get_post_meta($quote->ID, '_bbb_book_handle', true);

			$entries[] = array(
				'text'        => $text,
				'book_title'  => $book_title,
				'book_handle' => $book_handle,
			);
		}

		if (!$entries && function_exists('bbb_quote_export_entries')) {
			$entries = bbb_quote_export_entries($limit);
		}

		return array_slice($entries, 0, $limit);
	}
}

$quotes = bbb_my_bookshelf_quote_entries();

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
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(function_exists('bbb_wc_account_url') ? bbb_wc_account_url() : home_url('/account/')); ?>">account</a>
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

			<div class="bbb-account-shelf__feature" data-account-read-feature hidden>
				<div class="bbb-account-shelf__readShelf">
					<div class="bbb-account-shelf__featureHead">
						<p class="bbb-account-shelf__featureKicker">marked as read</p>
						<h2>your finished shelf</h2>
						<p data-account-read-copy>covers you have marked as read will stack here, face-out like a private trophy shelf.</p>
					</div>
					<div class="bbb-account-shelf__rail" aria-hidden="true"></div>
					<div class="bbb-account-shelf__coverStage" data-account-read-covers></div>
				</div>
				<a class="bbb-account-shelf__quoteCard" href="<?php echo esc_url(home_url('/sss-quote-wall/')); ?>" data-account-quote-card>
					<p class="bbb-account-shelf__featureKicker">pulled from the quote wall</p>
					<blockquote data-account-quote-text>mark a few books as read and a related quote can find you here.</blockquote>
					<span data-account-quote-source>visit the quote wall →</span>
				</a>
			</div>

			<div class="bbb-account-shelf__grid" data-account-shelf-grid></div>

			<div class="bbb-account-shelf__empty" data-account-shelf-empty hidden>
				<div class="bbb-account-shelf__emptyIcon" aria-hidden="true">📖</div>
				<h2>your shelf is waiting.</h2>
				<p>save a few books from the library and they’ll collect here.</p>
				<a href="<?php echo esc_url(home_url('/library/')); ?>">find your first save →</a>
			</div>

			<script type="application/json" data-account-library-books><?php echo wp_json_encode($books, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
			<script type="application/json" data-account-library-quotes><?php echo wp_json_encode($quotes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
		</div>
	</section>
</main>

<?php
get_footer();
