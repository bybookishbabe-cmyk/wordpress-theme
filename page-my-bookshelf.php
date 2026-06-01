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

$reader_identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$reader_email    = $reader_identity ? (string) ($reader_identity['email'] ?? '') : '';
$reader_user_id  = $reader_identity ? (int) ($reader_identity['userId'] ?? 0) : 0;
$has_reader_access = '' !== $reader_email;
$is_society = function_exists('bbb_reader_is_society') ? bbb_reader_is_society() : false;
$books      = function_exists('bbb_reader_quiz_books') ? bbb_reader_quiz_books() : array();
$account_data = array();

if ($has_reader_access && function_exists('bbb_reader_account_response_for_identity')) {
	try {
		$account_data = bbb_reader_account_response_for_identity((array) $reader_identity);
	} catch (Throwable $error) {
		error_log('BBB bookshelf page failed softly: ' . $error->getMessage());
		$account_data = array(
			'accessTier' => 'free',
			'books'      => array(),
			'readerType' => array(
				'title'     => 'fresh shelf romantic',
				'summary'   => 'your bookshelf opened, but the account sync needs a retry.',
				'topTropes' => array(),
				'counts'    => array('saved' => 0, 'read' => 0, 'reading' => 0, 'tbr' => 0),
			),
			'nextRead'   => null,
		);
	}
}
$reader_type = isset($account_data['readerType']) && is_array($account_data['readerType']) ? $account_data['readerType'] : array(
	'title'     => 'fresh shelf romantic',
	'summary'   => 'save or tag a few books and this will start calling your pattern.',
	'topTropes' => array(),
	'counts'    => array('saved' => 0, 'read' => 0, 'reading' => 0, 'tbr' => 0),
);
$reader_type_title = trim((string) ($reader_type['title'] ?? 'fresh shelf romantic'));
$reader_type_summary = trim((string) ($reader_type['summary'] ?? 'save or tag a few books and this will start calling your pattern.'));
$reader_type_counts = is_array($reader_type['counts'] ?? null) ? $reader_type['counts'] : array();
$reader_type_tropes = is_array($reader_type['topTropes'] ?? null) ? array_values(array_filter($reader_type['topTropes'])) : array();

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
			$book_title  = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($book_title) : $book_title;
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
		data-logged-in="<?php echo esc_attr($has_reader_access ? 'true' : 'false'); ?>"
		data-customer-id="<?php echo esc_attr($reader_user_id ? (string) $reader_user_id : ''); ?>"
		data-customer-email="<?php echo esc_attr($reader_email); ?>"
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
					<a class="bbb-account-shelf__button" href="<?php echo esc_url(bbb_page_url('library')); ?>">browse the library</a>
					<?php if ($has_reader_access) : ?>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(home_url('/account/')); ?>">account</a>
					<?php else : ?>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(home_url('/account/')); ?>">enter email to sync</a>
					<?php endif; ?>
				</div>
			</div>

			<div class="bbb-account-shelf__status<?php echo $has_reader_access ? '' : ' bbb-account-shelf__status--login'; ?>" data-account-shelf-status>
				<div class="bbb-account-shelf__statusMain">
					<span class="bbb-account-shelf__statusIcon" aria-hidden="true">📚</span>
					<div>
						<strong><?php echo esc_html($has_reader_access ? 'syncing your shelf...' : 'enter your email to keep your shelf across devices.'); ?></strong>
						<span data-account-shelf-status-copy><?php echo esc_html($has_reader_access ? 'local saves show first, email saves follow when they load.' : 'you can still save books on this device, but email access makes the shelf yours everywhere.'); ?></span>
					</div>
				</div>
				<div class="bbb-account-shelf__tools" data-account-shelf-tools hidden>
					<button type="button" data-account-copy>copy list</button>
					<button type="button" data-account-email>email list</button>
				</div>
			</div>

			<?php if ($has_reader_access) : ?>
				<section class="bbb-account-shelf__readerProfile bbb-account-shelf__readerProfile--bookshelf" aria-label="reader type">
					<div class="bbb-account-shelf__readerType">
						<p class="bbb-account-shelf__perkKicker">reader type</p>
						<h2><?php echo esc_html($reader_type_title); ?></h2>
						<p><?php echo esc_html($reader_type_summary); ?></p>
					</div>
					<div class="bbb-account-shelf__readerTypeSide">
						<div class="bbb-account-shelf__readerStats" aria-label="bookshelf stats">
							<span><?php echo esc_html((string) ($reader_type_counts['saved'] ?? 0)); ?> saved</span>
							<span><?php echo esc_html((string) ($reader_type_counts['read'] ?? 0)); ?> read</span>
							<span><?php echo esc_html((string) ($reader_type_counts['reading'] ?? 0)); ?> reading</span>
							<span><?php echo esc_html((string) ($reader_type_counts['tbr'] ?? 0)); ?> tbr</span>
						</div>
						<?php if ($reader_type_tropes) : ?>
							<div class="bbb-account-shelf__readerSignals" aria-label="top reader tropes">
								<?php foreach (array_slice($reader_type_tropes, 0, 3) as $trope) : ?>
									<span><?php echo esc_html((string) $trope); ?></span>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p class="bbb-account-shelf__readerHint">tag a few books and your top tropes will show here.</p>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>

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
				<a class="bbb-account-shelf__quoteCard" href="<?php echo esc_url(bbb_page_url('quote-wall')); ?>" data-account-quote-card>
					<p class="bbb-account-shelf__featureKicker">pulled from the quote wall</p>
					<blockquote data-account-quote-text>mark a few books as read and a related quote can find you here.</blockquote>
					<span data-account-quote-source>visit the quote wall →</span>
				</a>
			</div>

			<div class="bbb-account-shelf__lanes" data-account-shelf-lanes>
				<article class="bbb-account-shelf__lane bbb-account-shelf__lane--read" data-account-status-lane="read">
					<div class="bbb-account-shelf__laneHead">
						<p>finished</p>
						<span data-account-status-count="read">0 books</span>
					</div>
					<div class="bbb-account-shelf__laneBooks" data-account-status-books="read"></div>
				</article>
				<article class="bbb-account-shelf__lane bbb-account-shelf__lane--reading" data-account-status-lane="reading">
					<div class="bbb-account-shelf__laneHead">
						<p>reading now</p>
						<span data-account-status-count="reading">0 books</span>
					</div>
					<div class="bbb-account-shelf__laneBooks" data-account-status-books="reading"></div>
				</article>
				<article class="bbb-account-shelf__lane bbb-account-shelf__lane--tbr" data-account-status-lane="tbr">
					<div class="bbb-account-shelf__laneHead">
						<p>on the tbr</p>
						<span data-account-status-count="tbr">0 books</span>
					</div>
					<div class="bbb-account-shelf__laneBooks" data-account-status-books="tbr"></div>
				</article>
			</div>

			<div class="bbb-account-shelf__sectionHead">
				<p class="bbb-account-shelf__toolbarKicker">saved shelf</p>
				<h2>all saved books</h2>
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
	<?php bbb_render_component('library-modal'); ?>
</main>

<?php
get_footer();
