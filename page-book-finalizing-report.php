<?php
/**
 * Temporary local diagnostics for unfinished book records.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$bbb_report_host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$bbb_report_is_local = str_contains($bbb_report_host, 'localhost')
	|| str_contains($bbb_report_host, '127.0.0.1')
	|| str_contains($bbb_report_host, '.local')
	|| str_contains($bbb_report_host, '.test')
	|| str_contains($bbb_report_host, 'bybookishbabe.local');

if (!$bbb_report_is_local && !current_user_can('manage_options')) {
	status_header(404);
	nocache_headers();
	include get_query_template('404');
	return;
}

nocache_headers();

function bbb_book_finalizing_report_clean_text(string $value): string {
	$value = wp_strip_all_tags($value);
	$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$value = preg_replace('/\s+/', ' ', $value) ?? $value;
	return trim($value);
}

function bbb_book_finalizing_report_word_count(string $value): int {
	$value = bbb_book_finalizing_report_clean_text($value);
	if ('' === $value) {
		return 0;
	}

	return str_word_count($value);
}

function bbb_book_finalizing_report_truthy($value): bool {
	if (function_exists('bbb_truthy')) {
		return bbb_truthy($value);
	}

	return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
}

function bbb_book_finalizing_report_meta(int $post_id, array $keys): string {
	foreach ($keys as $key) {
		$value = get_post_meta($post_id, $key, true);
		if (is_array($value)) {
			$value = implode(', ', array_filter(array_map('strval', $value)));
		}

		$value = trim((string) $value);
		if ('' !== $value) {
			return $value;
		}
	}

	return '';
}

function bbb_book_finalizing_report_book_data(WP_Post $book): array {
	$post_id = (int) $book->ID;
	$data = function_exists('sss_book_data') ? sss_book_data($book) : array();
	$data = is_array($data) ? $data : array();

	$series_handle = trim((string) ($data['series_handle'] ?? ''));
	if ('' === $series_handle) {
		$series_handle = bbb_book_finalizing_report_meta($post_id, array('_bbb_series_handle', 'sss_series_handle'));
	}

	$series_name = trim((string) ($data['series_name'] ?? ''));
	if ('' === $series_name && '' !== $series_handle && function_exists('sss_get_series_name')) {
		$series_name = (string) sss_get_series_name($series_handle);
	}
	if ('' === $series_name && taxonomy_exists('bbb_series')) {
		$terms = wp_get_post_terms($post_id, 'bbb_series', array('fields' => 'names'));
		if (!is_wp_error($terms) && $terms) {
			$series_name = (string) $terms[0];
		}
	}

	$series_number = trim((string) ($data['series_number'] ?? ''));
	if ('' === $series_number) {
		$series_number = bbb_book_finalizing_report_meta($post_id, array('_bbb_series_number', 'series_number', 'sss_series_number'));
	}

	$why = (string) ($data['why'] ?? '');
	if ('' === trim($why)) {
		$why = bbb_book_finalizing_report_meta($post_id, array('_bbb_why', 'why_i_loved_it', 'sss_why'));
	}

	$mini = (string) ($data['mini'] ?? '');
	if ('' === trim($mini)) {
		$mini = bbb_book_finalizing_report_meta($post_id, array('_bbb_mini_note', 'mini_note', 'sss_mini'));
	}

	$boyfriend_name = trim((string) ($data['boyfriend_name'] ?? ''));
	if ('' === $boyfriend_name) {
		$boyfriend_name = bbb_book_finalizing_report_meta($post_id, array('_bbb_boyfriend_name', 'boyfriend_name', 'sss_boyfriend_name'));
	}

	$boyfriend_type = trim((string) ($data['boyfriend'] ?? ''));
	if ('' === $boyfriend_type) {
		$boyfriend_type = bbb_book_finalizing_report_meta($post_id, array('_bbb_boyfriend_type', 'boyfriend_type', 'sss_boyfriend_type'));
	}

	$standalone = !empty($data['standalone'])
		|| bbb_book_finalizing_report_truthy(bbb_book_finalizing_report_meta($post_id, array('_bbb_standalone', 'standalone', 'read_as_standalone', 'sss_standalone')));

	return array(
		'id'              => $post_id,
		'post_type'       => (string) $book->post_type,
		'status'          => (string) $book->post_status,
		'title'           => get_the_title($book),
		'author'          => trim((string) ($data['author'] ?? bbb_book_finalizing_report_meta($post_id, array('_bbb_author', 'author', 'sss_author')))),
		'edit_url'        => (string) get_edit_post_link($post_id, ''),
		'view_url'        => (string) get_permalink($book),
		'why'             => $why,
		'why_words'       => bbb_book_finalizing_report_word_count($why),
		'mini'            => $mini,
		'mini_words'      => bbb_book_finalizing_report_word_count($mini),
		'series_handle'   => $series_handle,
		'series_name'     => $series_name,
		'series_number'   => $series_number,
		'standalone'      => $standalone,
		'boyfriend_name'  => $boyfriend_name,
		'boyfriend_type'  => $boyfriend_type,
	);
}

function bbb_book_finalizing_report_is_placeholder_voice(string $value): bool {
	$clean = strtolower(bbb_book_finalizing_report_clean_text($value));
	if ('' === $clean) {
		return true;
	}

	foreach (array('todo', 'tbd', 'coming soon', 'needs review', 'needs copy', 'placeholder', 'lorem ipsum', 'add why', 'fill this in') as $needle) {
		if (str_contains($clean, $needle)) {
			return true;
		}
	}

	return false;
}

function bbb_book_finalizing_report_series_sort(array $a, array $b): int {
	$series = strcasecmp((string) ($a['series_name'] ?: $a['series_handle']), (string) ($b['series_name'] ?: $b['series_handle']));
	if (0 !== $series) {
		return $series;
	}

	return (float) ($a['series_number'] ?: 9999) <=> (float) ($b['series_number'] ?: 9999);
}

function bbb_book_finalizing_report_quote_count(int $book_id): int {
	$quote_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array('sss_quote', 'bbb_quote', 'book_quote');
	$quote_types = array_values(array_filter(array_unique($quote_types), 'post_type_exists'));
	if (!$quote_types) {
		return 0;
	}

	$quote_ids = get_posts(
		array(
			'post_type'      => $quote_types,
			'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'OR',
				array('key' => '_quote_book_id', 'value' => $book_id),
				array('key' => '_quote_library_book_id', 'value' => $book_id),
				array('key' => 'book_id', 'value' => $book_id),
				array('key' => 'library_book_id', 'value' => $book_id),
			),
		)
	);

	return count($quote_ids);
}

$bbb_report_book_types = array_values(array_filter(array('bbb_book', 'sss_book'), 'post_type_exists'));
$bbb_report_books = $bbb_report_book_types
	? get_posts(
		array(
			'post_type'              => $bbb_report_book_types,
			'post_status'            => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	)
	: array();

$bbb_report_rows = array_values(
	array_map(
		static fn(WP_Post $book): array => bbb_book_finalizing_report_book_data($book),
		array_filter($bbb_report_books, static fn($book): bool => $book instanceof WP_Post)
	)
);

$bbb_report_voice_rows = array_values(
	array_filter(
		$bbb_report_rows,
		static fn(array $book): bool => (int) $book['why_words'] < 18 || bbb_book_finalizing_report_is_placeholder_voice((string) $book['why'])
	)
);

$bbb_report_series_rows = array_values(
	array_filter(
		$bbb_report_rows,
		static fn(array $book): bool => !$book['standalone'] && ('' !== $book['series_handle'] || '' !== $book['series_name'] || '' !== $book['series_number'])
	)
);
usort($bbb_report_series_rows, 'bbb_book_finalizing_report_series_sort');

foreach ($bbb_report_rows as $index => $book) {
	$book_id = (int) $book['id'];
	$label = trim((string) ($book['boyfriend_name'] ?: $book['boyfriend_type']));
	$quote_count = bbb_book_finalizing_report_quote_count($book_id);
	$bbb_report_rows[$index]['quote_count'] = $quote_count;
	$bbb_report_rows[$index]['voice_needs_work'] = (int) $book['why_words'] < 18 || bbb_book_finalizing_report_is_placeholder_voice((string) $book['why']);
	$bbb_report_rows[$index]['boyfriend_status'] = '' === $label ? 'needs boyfriend label' : 'ready to set up';
	$bbb_report_rows[$index]['book_page_status'] = $bbb_report_rows[$index]['voice_needs_work'] ? 'needs book page copy' : 'book page copy present';
	$bbb_report_rows[$index]['boyfriend_done'] = false;
	$bbb_report_rows[$index]['quote_done'] = false;
	$bbb_report_rows[$index]['book_page_done'] = false;
}

$bbb_report_profile_rows = array();
if (post_type_exists('bbb_boyfriend')) {
	$bbb_report_profiles = get_posts(
		array(
			'post_type'              => 'bbb_boyfriend',
			'post_status'            => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	foreach ($bbb_report_profiles as $profile) {
		if (!$profile instanceof WP_Post) {
			continue;
		}

		$linked_ids = function_exists('bbb_fictional_boyfriend_book_ids') ? bbb_fictional_boyfriend_book_ids((int) $profile->ID) : array();
		$ready = function_exists('bbb_fictional_boyfriend_profile_ready') ? bbb_fictional_boyfriend_profile_ready((int) $profile->ID) : (bool) $linked_ids;
		if ($ready && $linked_ids) {
			continue;
		}

		$bbb_report_profile_rows[] = array(
			'id'         => (int) $profile->ID,
			'title'      => get_the_title($profile),
			'status'     => (string) $profile->post_status,
			'edit_url'   => (string) get_edit_post_link((int) $profile->ID, ''),
			'book_ids'   => implode(', ', $linked_ids),
			'has_image'  => has_post_thumbnail((int) $profile->ID),
			'issue'      => !$linked_ids ? 'no linked book' : 'missing featured image',
		);
	}
}

$bbb_report_generated = current_time('M j, Y g:i A');

get_header();
?>
<main class="bbb-final-report">
	<style>
		body.page-id-0:has(.bbb-final-report),
		body.page-id-0:has(.bbb-final-report) #MainContent,
		body.page-id-0:has(.bbb-final-report) .content-for-layout { background: #070707 !important; }
		.bbb-final-report { --fb-bg: #0b0b0b; --fb-panel: rgba(255,255,255,.045); --fb-panel-strong: rgba(255,255,255,.075); --fb-line: rgba(255,255,255,.12); --fb-line-strong: rgba(255,138,199,.36); --fb-ink: #f7f3ee; --fb-muted: rgba(247,243,238,.68); --fb-soft: rgba(247,243,238,.48); --fb-pink: #ff8ac7; --fb-pink-soft: #f3bfd5; --fb-plum: #4b112d; background: linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.028) 1px, transparent 1px), #070707; background-size: 42px 42px; color: var(--fb-ink); font-family: var(--font-body-family, Assistant, sans-serif); min-height: calc(100vh - 120px); padding: 34px min(5vw, 56px) 76px; }
		.bbb-final-report a { color: var(--fb-pink); font-weight: 700; text-decoration-thickness: 1px; text-underline-offset: 3px; }
		.bbb-final-report__top { display: grid; gap: 16px; margin: 0 auto 28px; max-width: 1180px; }
		.bbb-final-report__kicker { color: var(--fb-pink-soft); font-size: 11px; font-weight: 700; letter-spacing: .16em; margin: 0; text-transform: lowercase; }
		.bbb-final-report h1 { color: #fff; font-family: var(--font-heading-family, "Cormorant Garamond", Cormorant, Georgia, serif); font-size: clamp(46px, 8vw, 82px); font-weight: 500; line-height: 1.02; margin: 0; max-width: 920px; text-transform: lowercase; }
		.bbb-final-report__hint { color: var(--fb-muted); font-size: 15px; line-height: 1.7; margin: 0; max-width: 820px; }
		.bbb-final-report__summary { display: grid; gap: 10px; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); max-width: 1040px; }
		.bbb-final-report__stat { background: var(--fb-panel); border: 1px solid var(--fb-line); border-radius: 8px; padding: 14px; }
		.bbb-final-report__stat strong { color: #fff; display: block; font-family: var(--font-heading-family, "Cormorant Garamond", Cormorant, Georgia, serif); font-size: 40px; font-weight: 500; line-height: 1; }
		.bbb-final-report__stat span { color: var(--fb-muted); display: block; font-size: 13px; margin-top: 6px; text-transform: lowercase; }
		.bbb-final-report__toolbar { align-items: center; background: rgba(11,11,11,.94); border: 1px solid var(--fb-line); border-radius: 8px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: space-between; margin: 0 auto 14px; max-width: 1180px; padding: 12px; position: sticky; top: 0; z-index: 2; backdrop-filter: blur(18px); }
		.bbb-final-report__progress { color: #fff; font-size: 14px; font-weight: 700; text-transform: lowercase; }
		.bbb-final-report__button { background: rgba(255,138,199,.1); border: 1px solid rgba(255,138,199,.42); border-radius: 999px; color: var(--fb-pink); cursor: pointer; font-size: 11px; font-weight: 700; letter-spacing: .08em; padding: 10px 14px; text-transform: lowercase; }
		.bbb-final-report__list { display: grid; gap: 12px; margin: 0 auto; max-width: 1180px; }
		.bbb-final-report__book { background: linear-gradient(145deg, rgba(255,255,255,.055), rgba(255,255,255,.028)); border: 1px solid var(--fb-line); border-radius: 8px; box-shadow: 0 18px 46px rgba(0,0,0,.28); display: grid; gap: 16px; grid-template-columns: 42px minmax(0, 1fr); padding: 16px; transition: opacity .2s ease, transform .2s ease, border-color .2s ease; }
		.bbb-final-report__book.is-complete { border-color: rgba(255,138,199,.42); opacity: .58; }
		.bbb-final-report__bookCheck { align-items: center; background: rgba(255,138,199,.08); border: 1px solid rgba(255,138,199,.28); border-radius: 8px; display: flex; height: 42px; justify-content: center; width: 42px; }
		.bbb-final-report input[type="checkbox"] { accent-color: var(--fb-pink); height: 19px; width: 19px; }
		.bbb-final-report__bookCheck input { pointer-events: none; }
		.bbb-final-report__bookMain { display: grid; gap: 12px; min-width: 0; }
		.bbb-final-report__bookHead { align-items: start; display: grid; gap: 10px; grid-template-columns: minmax(0, 1fr) auto; }
		.bbb-final-report__title { color: #fff; font-family: var(--font-heading-family, "Cormorant Garamond", Cormorant, Georgia, serif); font-size: clamp(28px, 4vw, 42px); font-weight: 500; line-height: 1.03; margin: 0; text-transform: lowercase; }
		.bbb-final-report__meta { color: var(--fb-muted); font-size: 13px; line-height: 1.45; margin: 6px 0 0; text-transform: lowercase; }
		.bbb-final-report__actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
		.bbb-final-report__actions a { border: 1px solid rgba(255,138,199,.36); border-radius: 999px; padding: 7px 10px; text-decoration: none; text-transform: lowercase; }
		.bbb-final-report__badges { display: flex; flex-wrap: wrap; gap: 6px; }
		.bbb-final-report__pill { background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.08); border-radius: 999px; color: var(--fb-muted); display: inline-flex; font-size: 11px; font-weight: 700; padding: 5px 8px; text-transform: lowercase; }
		.bbb-final-report__bad { background: rgba(255,138,199,.1); border-color: rgba(255,138,199,.28); color: var(--fb-pink); }
		.bbb-final-report__warn { background: rgba(255,255,255,.07); border-color: rgba(255,255,255,.16); color: var(--fb-pink-soft); }
		.bbb-final-report__good { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.18); color: #fff; }
		.bbb-final-report__tasks { display: grid; gap: 10px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
		.bbb-final-report__task { align-items: start; background: rgba(255,255,255,.045); border: 1px solid var(--fb-line); border-radius: 8px; cursor: pointer; display: grid; gap: 10px; grid-template-columns: 24px minmax(0, 1fr); padding: 12px; }
		.bbb-final-report__task strong { color: #fff; display: block; font-size: 14px; line-height: 1.2; text-transform: lowercase; }
		.bbb-final-report__task small { color: var(--fb-muted); display: block; font-size: 12px; line-height: 1.4; margin-top: 3px; }
		.bbb-final-report__snippet { background: rgba(255,255,255,.035); border-left: 2px solid #d95d96; color: var(--fb-muted); font-size: 13px; line-height: 1.55; margin: 0; padding: 10px 12px; }
		.bbb-final-report__empty { color: var(--fb-muted); padding: 18px; }
		@media (max-width: 760px) {
			.bbb-final-report { padding: 24px 14px 48px; }
			.bbb-final-report__book { grid-template-columns: 1fr; }
			.bbb-final-report__bookHead { grid-template-columns: 1fr; }
			.bbb-final-report__actions { justify-content: flex-start; }
			.bbb-final-report__tasks { grid-template-columns: 1fr; }
		}
	</style>

	<header class="bbb-final-report__top">
		<p class="bbb-final-report__kicker">temporary local diagnostics | generated <?php echo esc_html($bbb_report_generated); ?></p>
		<h1>book setup checklist</h1>
		<p class="bbb-final-report__hint">Every book is listed once. Check off boyfriend setup, quote setup, and book page as you finalize each one; when all three are checked, the book marks itself complete and drops to the bottom.</p>
		<div class="bbb-final-report__summary" aria-label="report summary">
			<div class="bbb-final-report__stat"><strong><?php echo esc_html((string) count($bbb_report_rows)); ?></strong><span>books scanned</span></div>
			<div class="bbb-final-report__stat"><strong><?php echo esc_html((string) count($bbb_report_voice_rows)); ?></strong><span>book pages needing copy</span></div>
			<div class="bbb-final-report__stat"><strong><?php echo esc_html((string) count($bbb_report_series_rows)); ?></strong><span>books in series</span></div>
			<div class="bbb-final-report__stat"><strong><?php echo esc_html((string) count($bbb_report_rows)); ?></strong><span>boyfriend setups to finalize</span></div>
			<div class="bbb-final-report__stat"><strong><?php echo esc_html((string) count($bbb_report_rows)); ?></strong><span>quote setups to finalize</span></div>
		</div>
	</header>

	<div class="bbb-final-report__toolbar" data-bbb-report-toolbar>
		<div class="bbb-final-report__progress" data-bbb-report-progress>Loading checklist...</div>
		<button type="button" class="bbb-final-report__button" data-bbb-report-reset>Reset manual checks</button>
	</div>

	<section class="bbb-final-report__list" aria-label="book checklist" data-bbb-report-list>
		<?php foreach ($bbb_report_rows as $book) : ?>
			<?php
			$book_id = (int) $book['id'];
			$boyfriend_label = trim((string) ($book['boyfriend_name'] ?: $book['boyfriend_type']));
			$series_label = trim((string) ($book['series_name'] ?: $book['series_handle']));
			$snippet = bbb_book_finalizing_report_clean_text((string) $book['why']);
			?>
			<article
				class="bbb-final-report__book"
				data-book-card
				data-book-id="<?php echo esc_attr((string) $book_id); ?>"
				data-default-boyfriend="<?php echo esc_attr(!empty($book['boyfriend_done']) ? '1' : '0'); ?>"
				data-default-quote="<?php echo esc_attr(!empty($book['quote_done']) ? '1' : '0'); ?>"
				data-default-bookpage="<?php echo esc_attr(!empty($book['book_page_done']) ? '1' : '0'); ?>"
			>
				<div class="bbb-final-report__bookCheck" aria-label="book complete">
					<input type="checkbox" data-book-complete disabled>
				</div>
				<div class="bbb-final-report__bookMain">
					<div class="bbb-final-report__bookHead">
						<div>
							<h2 class="bbb-final-report__title"><?php echo esc_html($book['title']); ?></h2>
							<p class="bbb-final-report__meta">
								<?php echo esc_html(trim((string) $book['author'] . ' | #' . $book_id, ' |')); ?>
								<?php if ('' !== $series_label || '' !== $book['series_number']) : ?>
									<br><?php echo esc_html(trim('Series: ' . ($series_label ?: 'missing name') . ('' !== $book['series_number'] ? ' #' . $book['series_number'] : ''), ' ')); ?>
								<?php endif; ?>
							</p>
						</div>
						<div class="bbb-final-report__actions">
							<?php if ($book['edit_url']) : ?><a href="<?php echo esc_url((string) $book['edit_url']); ?>">edit</a><?php endif; ?>
							<?php if ($book['view_url']) : ?><a href="<?php echo esc_url((string) $book['view_url']); ?>">view</a><?php endif; ?>
						</div>
					</div>

					<div class="bbb-final-report__badges" aria-label="current site data">
						<span class="bbb-final-report__pill"><?php echo esc_html($book['post_type']); ?></span>
						<span class="bbb-final-report__pill"><?php echo esc_html($book['status']); ?></span>
						<span class="bbb-final-report__pill <?php echo !empty($book['voice_needs_work']) ? 'bbb-final-report__bad' : 'bbb-final-report__good'; ?>"><?php echo esc_html((string) $book['why_words']); ?> book page words</span>
						<?php if ('' !== $series_label || '' !== $book['series_number']) : ?>
							<span class="bbb-final-report__pill bbb-final-report__warn">series</span>
						<?php endif; ?>
						<span class="bbb-final-report__pill bbb-final-report__warn"><?php echo esc_html((string) $book['boyfriend_status']); ?></span>
						<span class="bbb-final-report__pill <?php echo (int) $book['quote_count'] > 0 ? 'bbb-final-report__good' : 'bbb-final-report__warn'; ?>"><?php echo esc_html((string) $book['quote_count']); ?> linked quotes found</span>
					</div>

					<div class="bbb-final-report__tasks">
						<label class="bbb-final-report__task">
							<input type="checkbox" data-task="boyfriend">
							<span>
								<strong>Boyfriend setup</strong>
								<small><?php echo esc_html('' !== $boyfriend_label ? $boyfriend_label . ' - finalize profile/linking' : 'add or confirm the boyfriend label'); ?></small>
							</span>
						</label>
						<label class="bbb-final-report__task">
							<input type="checkbox" data-task="quote">
							<span>
								<strong>Quote setup</strong>
								<small><?php echo (int) $book['quote_count'] > 0 ? esc_html((string) $book['quote_count'] . ' linked quote(s) found - choose/finalize') : 'add or link the quote'; ?></small>
							</span>
						</label>
						<label class="bbb-final-report__task">
							<input type="checkbox" data-task="bookpage">
							<span>
								<strong>Book page</strong>
								<small><?php echo esc_html((string) $book['book_page_status']); ?></small>
							</span>
						</label>
					</div>

					<p class="bbb-final-report__snippet"><?php echo esc_html('' !== $snippet ? wp_html_excerpt($snippet, 260, '...') : 'Custom voice is blank.'); ?></p>
				</div>
			</article>
		<?php endforeach; ?>
	</section>

	<script>
		(function() {
			var storageKey = 'bbbBookFinalizingChecklistV1';
			var list = document.querySelector('[data-bbb-report-list]');
			if (!list) {
				return;
			}

			var cards = Array.prototype.slice.call(list.querySelectorAll('[data-book-card]'));
			var progress = document.querySelector('[data-bbb-report-progress]');
			var reset = document.querySelector('[data-bbb-report-reset]');

			function loadState() {
				try {
					return JSON.parse(window.localStorage.getItem(storageKey) || '{}') || {};
				} catch (error) {
					return {};
				}
			}

			function saveState(state) {
				window.localStorage.setItem(storageKey, JSON.stringify(state));
			}

			function taskValue(card, task, state) {
				var id = card.getAttribute('data-book-id');
				if (state[id] && typeof state[id][task] === 'boolean') {
					return state[id][task];
				}

				return card.getAttribute('data-default-' + task) === '1';
			}

			function updateCard(card, state) {
				var boyfriend = card.querySelector('[data-task="boyfriend"]');
				var quote = card.querySelector('[data-task="quote"]');
				var bookpage = card.querySelector('[data-task="bookpage"]');
				var complete = card.querySelector('[data-book-complete]');
				boyfriend.checked = taskValue(card, 'boyfriend', state);
				quote.checked = taskValue(card, 'quote', state);
				bookpage.checked = taskValue(card, 'bookpage', state);
				complete.checked = boyfriend.checked && quote.checked && bookpage.checked;
				card.classList.toggle('is-complete', complete.checked);
				card.dataset.complete = complete.checked ? '1' : '0';
			}

			function sortCards() {
				cards.sort(function(a, b) {
					var done = Number(a.dataset.complete || '0') - Number(b.dataset.complete || '0');
					if (done !== 0) {
						return done;
					}

					return String(a.querySelector('.bbb-final-report__title').textContent).localeCompare(String(b.querySelector('.bbb-final-report__title').textContent));
				});
				cards.forEach(function(card) {
					list.appendChild(card);
				});
			}

			function updateProgress() {
				var done = cards.filter(function(card) {
					return card.dataset.complete === '1';
				}).length;
				if (progress) {
					progress.textContent = done + ' of ' + cards.length + ' books complete';
				}
			}

			function render() {
				var state = loadState();
				cards.forEach(function(card) {
					updateCard(card, state);
				});
				sortCards();
				updateProgress();
			}

			cards.forEach(function(card) {
				Array.prototype.slice.call(card.querySelectorAll('[data-task]')).forEach(function(input) {
					input.addEventListener('change', function() {
						var state = loadState();
						var id = card.getAttribute('data-book-id');
						state[id] = state[id] || {};
						state[id][input.getAttribute('data-task')] = input.checked;
						saveState(state);
						updateCard(card, state);
						sortCards();
						updateProgress();
					});
				});
			});

			if (reset) {
				reset.addEventListener('click', function() {
					window.localStorage.removeItem(storageKey);
					render();
				});
			}

			render();
		})();
	</script>
</main>
<?php
get_footer();
