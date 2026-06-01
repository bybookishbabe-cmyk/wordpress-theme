<?php
/**
 * Single book page prototype.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_enqueue_css('bbb-book-breakdown-page', 'assets/css/book-breakdown-page.css', array('bbb-bookshelf-signup'));

get_header();

if (!have_posts()) {
	get_footer();
	return;
}

the_post();

$book_id = get_the_ID();

if (function_exists('sss_book_is_visible') && !sss_book_is_visible($book_id)) {
	global $wp_query;
	$wp_query->set_404();
	status_header(404);
	nocache_headers();
	get_template_part('404');
	get_footer();
	return;
}

$book      = get_post();
$data      = $book instanceof WP_Post && function_exists('sss_book_data') ? sss_book_data($book) : array();
$is_locked = function_exists('sss_book_is_private') && sss_book_is_private($book_id) && !(function_exists('bbb_reader_is_society') && bbb_reader_is_society());

foreach (array('cover', 'amazon', 'bookshop', 'newsletter') as $url_key) {
	if (isset($data[$url_key]) && function_exists('bbb_normalize_url_value')) {
		$data[$url_key] = bbb_normalize_url_value($data[$url_key]);
	}
}

$title         = (string) ($data['title'] ?? get_the_title());
$title         = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($title) : $title;
$author        = (string) ($data['author'] ?? '');
$author        = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name($author) : $author;
$series_name   = (string) ($data['series_name'] ?? '');
$series_name   = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($series_name) : $series_name;
$series_handle = (string) ($data['series_handle'] ?? '');
$series_number = (string) ($data['series_number'] ?? '');
$mini          = (string) ($data['mini'] ?? '');
$why           = (string) ($data['why'] ?? '');
$spice_count   = max(0, (int) ($data['spice'] ?? 0));
$ku            = !empty($data['ku']);
$reread        = !empty($data['reread']) && 'false' !== (string) $data['reread'];

$book_page_meta = static function (string $key) use ($book_id): string {
	$value = get_post_meta($book_id, $key, true);

	return is_scalar($value) ? trim((string) $value) : '';
};

$reader_notes = array(
	'verdict'          => $book_page_meta('_bbb_verdict'),
	'vibe_description' => $book_page_meta('_bbb_vibe_description'),
	'spice_words'      => $book_page_meta('_bbb_spice_words'),
	'read_this_if'     => $book_page_meta('_bbb_read_this_if'),
	'skip_this_if'     => $book_page_meta('_bbb_skip_this_if'),
	'content_warnings' => $book_page_meta('_bbb_content_warnings'),
	'standalone_hea'   => $book_page_meta('_bbb_standalone_hea'),
);
$reader_notes['vibe_description'] = function_exists('bbb_bookreview_clean_vibe_text')
	? bbb_bookreview_clean_vibe_text($reader_notes['vibe_description'])
	: trim(preg_replace('/\s*🌶.*$/u', '', $reader_notes['vibe_description']) ?? $reader_notes['vibe_description']);
$has_reader_notes = '' !== implode('', array_filter($reader_notes));

$rating_dots = static function ($value): string {
	$count = max(0, min(5, (int) $value));
	$html  = '';

	for ($i = 1; $i <= 5; $i++) {
		$html .= '<span class="sss-book-page__dot' . ($i <= $count ? ' is-filled' : '') . '"></span>';
	}

	$html .= '<span class="sss-book-page__score">' . esc_html((string) $count) . '/5</span>';

	return $html;
};

$tropes = array();
foreach ((array) ($data['tropes'] ?? array()) as $trope) {
	$name = trim((string) ($trope['name'] ?? ''));
	if ('' === $name) {
		continue;
	}

	$handle   = sanitize_title((string) ($trope['handle'] ?? $name));
	$tropes[] = array(
		'label' => function_exists('bbb_trope_label') ? bbb_trope_label($name, $trope['emoji'] ?? '') : trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . $name),
		'html'  => function_exists('bbb_trope_label_html') ? bbb_trope_label_html($name, $trope['emoji'] ?? '', $handle) : esc_html(trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . $name)),
		'name'  => $name,
		'url'   => function_exists('bbb_trope_page_url') ? bbb_trope_page_url($name, $handle) : home_url('/' . $handle . '-books/'),
	);
}

$related_books = array();
if (function_exists('bbb_books_like_recommendations')) {
	foreach (bbb_books_like_recommendations($book_id) as $related) {
		if (count($related_books) >= 3 || empty($related['id'])) {
			continue;
		}

		$related_post = get_post((int) $related['id']);
		if (!$related_post instanceof WP_Post) {
			continue;
		}

		$related_data    = function_exists('sss_book_data') ? sss_book_data($related_post) : array();
		$related_tropes  = (array) ($related_data['tropes'] ?? array());
		$first_trope     = $related_tropes[0] ?? array();
		$first_trope_name = (string) ($first_trope['name'] ?? '');
		$first_trope_html = '' !== $first_trope_name && function_exists('bbb_trope_label_html')
			? bbb_trope_label_html($first_trope_name, $first_trope['emoji'] ?? '', (string) ($first_trope['handle'] ?? $first_trope_name))
			: esc_html($first_trope_name);
		$related_cover    = (string) ($related_data['cover'] ?? '');
		if ('' !== $related_cover && function_exists('bbb_normalize_url_value')) {
			$related_cover = bbb_normalize_url_value($related_cover);
		}
		$related_books[] = array(
			'handle'  => (string) ($related_data['handle'] ?? $related_post->post_name),
			'title'   => (string) ($related_data['title'] ?? get_the_title($related_post)),
			'author'  => (string) ($related_data['author'] ?? ''),
			'cover'   => $related_cover,
			'amazon'  => (string) ($related_data['amazon'] ?? ''),
			'bookshop' => (string) ($related_data['bookshop'] ?? ''),
			'spice'   => (int) ($related_data['spice'] ?? 0),
			'shelf'   => (string) ($related_data['shelf'] ?? ''),
			'trope'   => $first_trope_name,
			'trope_html' => $first_trope_html,
			'tropes'  => implode(', ', array_filter(array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), $related_tropes))),
			'mini'    => (string) ($related_data['mini'] ?? ''),
			'ku'      => !empty($related_data['ku']),
			'url'     => get_permalink($related_post),
		);
	}
}

$book_quotes = array();
if (function_exists('bbb_quote_post_types') && function_exists('bbb_bookquote_quote_text') && function_exists('bbb_bookquote_quote_book_matches')) {
	$quote_types = bbb_quote_post_types();
	if ($quote_types) {
		$book_handle = sanitize_title((string) get_post_field('post_name', $book_id));
		$direct_quotes = get_posts(
			array(
				'post_type'      => $quote_types,
				'post_status'    => 'publish',
				'posts_per_page' => 6,
				'orderby'        => 'menu_order date',
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'OR',
					array('key' => '_quote_book_id', 'value' => (string) $book_id),
					array('key' => '_quote_library_book_id', 'value' => (string) $book_id),
					array('key' => 'book_id', 'value' => (string) $book_id),
					array('key' => 'library_book_id', 'value' => (string) $book_id),
					array('key' => '_quote_book_handle', 'value' => $book_handle),
					array('key' => 'book_handle', 'value' => $book_handle),
					array('key' => '_bbb_book_handle', 'value' => $book_handle),
				),
			)
		);

		foreach ($direct_quotes as $quote) {
			if ($quote instanceof WP_Post) {
				$book_quotes[$quote->ID] = $quote;
			}
		}

		if (count($book_quotes) < 6) {
			$maybe_quotes = get_posts(
				array(
					'post_type'      => $quote_types,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'menu_order date',
					'order'          => 'ASC',
				)
			);
			foreach ($maybe_quotes as $quote) {
				if (count($book_quotes) >= 6) {
					break;
				}
				if ($quote instanceof WP_Post && !isset($book_quotes[$quote->ID]) && bbb_bookquote_quote_book_matches($quote, $book)) {
					$book_quotes[$quote->ID] = $quote;
				}
			}
		}
	}
}
?>

<main class="sss-book-page">
	<div class="sss-book-page__inner">
		<nav class="sss-book-page__breadcrumb" aria-label="breadcrumb">
			<a href="<?php echo esc_url(home_url('/')); ?>">home</a>
			<span>›</span>
			<a href="<?php echo esc_url(home_url('/library/')); ?>">library</a>
			<?php if ($series_name && $series_handle) : ?>
				<span>›</span>
				<a style="text-transform:none !important;" href="<?php echo esc_url(home_url('/series/' . sanitize_title($series_handle) . '/')); ?>"><?php echo esc_html($series_name); ?> series</a>
			<?php endif; ?>
			<span>›</span>
			<span style="text-transform:none !important;"><?php echo esc_html($title); ?></span>
		</nav>

		<?php if ($is_locked) : ?>
			<section class="sss-book-page__locked">
				<p class="sss-book-page__eyebrow">private shelf</p>
				<h1 class="sss-book-page__title"><?php echo esc_html($title); ?></h1>
				<p>This book lives on the private shelf. Log in with Society access to see the full breakdown.</p>
				<a class="sss-book-page__login" href="<?php echo esc_url(home_url('/account/')); ?>">log in</a>
			</section>
		<?php else : ?>
			<article class="sss-book-page__content">
				<?php if ($series_name) : ?>
					<a class="sss-book-page__seriesTag" style="text-transform:none !important;" href="<?php echo esc_url(home_url('/series/' . sanitize_title($series_handle) . '/')); ?>">
						<?php echo esc_html($series_name); ?> series<?php echo $series_number ? ' · book ' . esc_html($series_number) : ''; ?>
					</a>
				<?php elseif (!empty($data['standalone'])) : ?>
					<p class="sss-book-page__bookNumber">standalone</p>
				<?php endif; ?>

				<div
					class="sss-book-page__titleRow sss-lib__book"
					data-handle="<?php echo esc_attr((string) ($data['handle'] ?? $book->post_name)); ?>"
					data-title="<?php echo esc_attr($title); ?>"
					data-author="<?php echo esc_attr($author); ?>"
					data-cover="<?php echo esc_url((string) ($data['cover'] ?? '')); ?>"
					data-amazon="<?php echo esc_url((string) ($data['amazon'] ?? '')); ?>"
					data-bookshop="<?php echo esc_url((string) ($data['bookshop'] ?? '')); ?>"
					data-spice="<?php echo esc_attr((string) $spice_count); ?>"
					data-shelf="<?php echo esc_attr((string) ($data['shelf'] ?? '')); ?>"
					data-tropes="<?php echo esc_attr(implode(', ', array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), (array) ($data['tropes'] ?? array())))); ?>"
					data-mini="<?php echo esc_attr($mini); ?>"
					data-series="<?php echo esc_attr($series_handle); ?>"
					data-series-name="<?php echo esc_attr($series_name); ?>"
					data-series-number="<?php echo esc_attr($series_number); ?>"
					data-ku="<?php echo $ku ? 'true' : 'false'; ?>"
				>
					<h1 class="sss-book-page__title" style="text-transform:none !important;"><?php echo esc_html($title); ?></h1>
					<span class="sss-lib__heart sss-book-page__addTbr" data-heart role="button" tabindex="0" aria-label="add to tbr">
						<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
						<span class="sss-lib__heartLabel" data-heart-label>add to tbr</span>
					</span>
				</div>
				<?php if ($author) : ?>
					<p class="sss-book-page__author">by <span style="text-transform:none !important;"><?php echo esc_html($author); ?></span></p>
				<?php endif; ?>

				<section class="sss-book-page__hero" aria-label="book overview">
					<div
						class="sss-book-page__coverWrap sss-lib__book"
						data-handle="<?php echo esc_attr((string) ($data['handle'] ?? $book->post_name)); ?>"
						data-title="<?php echo esc_attr($title); ?>"
						data-author="<?php echo esc_attr($author); ?>"
						data-cover="<?php echo esc_url((string) ($data['cover'] ?? '')); ?>"
						data-amazon="<?php echo esc_url((string) ($data['amazon'] ?? '')); ?>"
						data-bookshop="<?php echo esc_url((string) ($data['bookshop'] ?? '')); ?>"
						data-spice="<?php echo esc_attr((string) $spice_count); ?>"
						data-shelf="<?php echo esc_attr((string) ($data['shelf'] ?? '')); ?>"
						data-tropes="<?php echo esc_attr(implode(', ', array_map(static fn(array $trope): string => (string) ($trope['name'] ?? ''), (array) ($data['tropes'] ?? array())))); ?>"
						data-mini="<?php echo esc_attr($mini); ?>"
						data-series="<?php echo esc_attr($series_handle); ?>"
						data-series-name="<?php echo esc_attr($series_name); ?>"
						data-series-number="<?php echo esc_attr($series_number); ?>"
						data-ku="<?php echo $ku ? 'true' : 'false'; ?>"
					>
						<span class="sss-lib__heart" data-heart role="button" tabindex="0" aria-label="save to your bookshelf">
							<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
							<span class="sss-lib__heartLabel" data-heart-label>save</span>
						</span>
						<?php if ($series_number) : ?>
							<span class="sss-lib__seriesBadge" aria-label="book <?php echo esc_attr($series_number); ?>"><?php echo esc_html($series_number); ?></span>
						<?php endif; ?>
						<?php if ($spice_count > 0) : ?>
							<span class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $spice_count)); ?></span>
						<?php endif; ?>
						<?php if (!empty($data['cover'])) : ?>
							<img class="sss-book-page__cover" src="<?php echo esc_url((string) $data['cover']); ?>" alt="<?php echo esc_attr($title . ($author ? ' by ' . $author : '') . ' book cover'); ?>">
						<?php endif; ?>
					</div>

					<div class="sss-book-page__overview">
						<?php if ($mini) : ?>
							<p class="sss-book-page__blurb"><?php echo esc_html($mini); ?></p>
						<?php endif; ?>

						<div class="sss-book-page__metaGrid">
							<?php if ('' !== (string) ($data['tension'] ?? '')) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">🔥 tension</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($data['tension']); ?></div>
								</div>
							<?php endif; ?>
							<?php if ('' !== (string) ($data['damage'] ?? '')) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">💔 emotional damage</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($data['damage']); ?></div>
								</div>
							<?php endif; ?>
							<?php if ('' !== (string) ($data['darkness'] ?? '')) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">💀 darkness</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($data['darkness']); ?></div>
								</div>
							<?php endif; ?>
							<?php if ('' !== (string) ($data['yearning'] ?? '')) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">💕 yearning</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($data['yearning']); ?></div>
								</div>
							<?php endif; ?>
							<?php if ($spice_count > 0) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">🌶 spice</div>
									<div class="sss-book-page__rating"><?php echo $rating_dots($spice_count); ?></div>
								</div>
							<?php endif; ?>
							<?php if ($reread) : ?>
								<div class="sss-book-page__metaItem">
									<div class="sss-book-page__metaLabel">↻ reread</div>
									<div class="sss-book-page__metaValue">worthy</div>
								</div>
							<?php endif; ?>
						</div>

						<?php if (!empty($data['boyfriend_name']) || !empty($data['boyfriend'])) : ?>
							<p class="sss-book-page__boyfriend">🖤 book boyfriend: <strong><?php echo esc_html((string) ($data['boyfriend_name'] ?: $data['boyfriend'])); ?></strong></p>
						<?php endif; ?>
					</div>
				</section>

				<?php if ($tropes) : ?>
					<section class="sss-book-page__section" aria-label="tropes in this book">
						<p class="sss-book-page__sectionLabel">tropes in this book</p>
						<div class="sss-book-page__tropeTags">
							<?php foreach ($tropes as $trope) : ?>
								<a class="sss-book-page__tropeTag" href="<?php echo esc_url($trope['url']); ?>"><?php echo wp_kses_post($trope['html']); ?></a>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<div class="sss-book-page__ctaRow">
					<?php if (!empty($data['amazon'])) : ?>
						<?php if ($ku) : ?>
							<a class="sss-book-page__cta sss-book-page__cta--ku" href="<?php echo esc_url((string) $data['amazon']); ?>" target="_blank" rel="noopener">read free on kindle unlimited</a>
						<?php endif; ?>
						<a class="sss-book-page__cta sss-book-page__cta--amazon" href="<?php echo esc_url((string) $data['amazon']); ?>" target="_blank" rel="noopener">
							buy on amazon<?php if ($ku) : ?> <span>· own it forever</span><?php endif; ?>
						</a>
					<?php endif; ?>
					<?php if (!empty($data['bookshop'])) : ?>
						<a class="sss-book-page__cta sss-book-page__cta--bookshop" href="<?php echo esc_url((string) $data['bookshop']); ?>" target="_blank" rel="noopener">prefer indie? bookshop.org →</a>
					<?php endif; ?>
				</div>

				<?php if ($has_reader_notes) : ?>
					<section class="sss-book-page__readerGuide" aria-label="reader guide for <?php echo esc_attr($title); ?>">
						<?php if ('' !== $reader_notes['verdict']) : ?>
							<section class="sss-book-page__guideBlock sss-book-page__guideBlock--feature">
								<p class="sss-book-page__guideLabel">verdict</p>
								<div class="sss-book-page__guideText"><?php echo wp_kses_post(wpautop($reader_notes['verdict'])); ?></div>
							</section>
						<?php endif; ?>

						<?php if ('' !== $reader_notes['vibe_description']) : ?>
							<section class="sss-book-page__guideBlock">
								<p class="sss-book-page__guideLabel">vibe</p>
								<div class="sss-book-page__guideText"><?php echo wp_kses_post(wpautop($reader_notes['vibe_description'])); ?></div>
							</section>
						<?php endif; ?>

						<?php if ('' !== $reader_notes['spice_words']) : ?>
							<section class="sss-book-page__guideBlock">
								<p class="sss-book-page__guideLabel">spice</p>
								<div class="sss-book-page__guideText"><?php echo wp_kses_post(wpautop($reader_notes['spice_words'])); ?></div>
							</section>
						<?php endif; ?>

						<?php if ('' !== $reader_notes['read_this_if'] || '' !== $reader_notes['skip_this_if']) : ?>
							<section class="sss-book-page__guideBlock">
								<p class="sss-book-page__guideLabel">reader fit</p>
								<div class="sss-book-page__fitGrid">
									<?php if ('' !== $reader_notes['read_this_if']) : ?>
										<div class="sss-book-page__fitCard sss-book-page__fitCard--read">
											<strong>read this if</strong>
											<p><?php echo esc_html($reader_notes['read_this_if']); ?></p>
										</div>
									<?php endif; ?>
									<?php if ('' !== $reader_notes['skip_this_if']) : ?>
										<div class="sss-book-page__fitCard sss-book-page__fitCard--skip">
											<strong>skip this if</strong>
											<p><?php echo esc_html($reader_notes['skip_this_if']); ?></p>
										</div>
									<?php endif; ?>
								</div>
							</section>
						<?php endif; ?>

						<?php if ('' !== $reader_notes['content_warnings']) : ?>
							<section class="sss-book-page__guideBlock sss-book-page__guideBlock--warning">
								<p class="sss-book-page__guideLabel">content warnings</p>
								<div class="sss-book-page__guideText"><?php echo wp_kses_post(wpautop($reader_notes['content_warnings'])); ?></div>
							</section>
						<?php endif; ?>

						<?php if ('' !== $reader_notes['standalone_hea']) : ?>
							<section class="sss-book-page__guideBlock sss-book-page__guideBlock--status">
								<p class="sss-book-page__guideLabel">standalone + hea</p>
								<div class="sss-book-page__guideText"><?php echo wp_kses_post(wpautop($reader_notes['standalone_hea'])); ?></div>
							</section>
						<?php endif; ?>
					</section>
				<?php endif; ?>

				<?php if ($why) : ?>
					<div class="sss-book-page__quote"><?php echo wp_kses_post($why); ?></div>
				<?php endif; ?>

				<?php if ($book_quotes) : ?>
					<section class="sss-book-page__quotes" aria-label="quotes from <?php echo esc_attr($title); ?>">
						<div class="sss-book-page__quoteList">
							<?php foreach ($book_quotes as $quote) : ?>
								<?php $quote_text = bbb_bookquote_quote_text($quote); ?>
								<?php if ('' === $quote_text) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<blockquote class="sss-book-page__bookQuote">
									<a class="sss-book-page__quoteWallLink" href="<?php echo esc_url(home_url('/sss-quote-wall/')); ?>">‹ all quotes</a>
									<p>&ldquo;<?php echo esc_html($quote_text); ?>&rdquo;</p>
									<button class="sss-book-page__quoteCopy" type="button" data-bbb-copy-quote="<?php echo esc_attr($quote_text); ?>" aria-label="copy quote">copy</button>
								</blockquote>
							<?php endforeach; ?>
						</div>
					</section>
					<script>
						document.addEventListener('click', function(event) {
							var button = event.target.closest('[data-bbb-copy-quote]');
							if (!button || !navigator.clipboard) {
								return;
							}
							navigator.clipboard.writeText(button.getAttribute('data-bbb-copy-quote') || '').then(function() {
								button.textContent = 'copied';
								window.setTimeout(function() {
									button.textContent = 'copy';
								}, 1400);
							});
						});
					</script>
				<?php endif; ?>

				<?php if ($related_books) : ?>
					<section class="sss-book-page__related" aria-label="related books">
						<p class="sss-book-page__relatedLabel">if you liked <?php echo esc_html($title); ?>, try these →</p>
						<div class="sss-book-page__relatedGrid">
							<?php foreach ($related_books as $related_book) : ?>
								<article
									class="sss-book-page__relatedCard sss-lib__book"
									data-handle="<?php echo esc_attr($related_book['handle']); ?>"
									data-title="<?php echo esc_attr($related_book['title']); ?>"
									data-author="<?php echo esc_attr($related_book['author']); ?>"
									data-cover="<?php echo esc_url($related_book['cover']); ?>"
									data-amazon="<?php echo esc_url($related_book['amazon']); ?>"
									data-bookshop="<?php echo esc_url($related_book['bookshop']); ?>"
									data-spice="<?php echo esc_attr((string) $related_book['spice']); ?>"
									data-shelf="<?php echo esc_attr($related_book['shelf']); ?>"
									data-tropes="<?php echo esc_attr($related_book['tropes']); ?>"
									data-mini="<?php echo esc_attr($related_book['mini']); ?>"
									data-ku="<?php echo $related_book['ku'] ? 'true' : 'false'; ?>"
								>
									<?php if (!empty($related_book['cover'])) : ?>
										<span class="sss-book-page__relatedCoverWrap">
											<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
												<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
												<span class="sss-lib__heartLabel" data-heart-label>save</span>
											</span>
											<?php if ($related_book['spice'] > 0) : ?>
												<span class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $related_book['spice'])); ?></span>
											<?php endif; ?>
											<a class="sss-book-page__relatedCoverLink" href="<?php echo esc_url($related_book['url']); ?>" data-book-page-link aria-label="<?php echo esc_attr('open ' . $related_book['title']); ?>">
												<img class="sss-book-page__relatedCover" src="<?php echo esc_url($related_book['cover']); ?>" alt="<?php echo esc_attr($related_book['title'] . ($related_book['author'] ? ' by ' . $related_book['author'] : '') . ' book cover'); ?>" loading="lazy">
											</a>
										</span>
									<?php endif; ?>
									<a class="sss-book-page__relatedTitle" href="<?php echo esc_url($related_book['url']); ?>" data-book-page-link><?php echo esc_html($related_book['title']); ?></a>
									<?php if ($related_book['author']) : ?>
										<span class="sss-book-page__relatedAuthor"><?php echo esc_html($related_book['author']); ?></span>
									<?php endif; ?>
									<?php if ($related_book['trope']) : ?>
										<span class="sss-book-page__relatedTrope"><?php echo wp_kses_post($related_book['trope_html']); ?></span>
									<?php endif; ?>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<section class="sss-book-page__seoLinks" aria-label="explore more like this">
					<p class="sss-book-page__sectionLabel">explore more like this</p>
					<div class="sss-book-page__seoGrid">
						<?php foreach (array_slice($tropes, 0, 4) as $trope) : ?>
							<a class="sss-book-page__seoLink" href="<?php echo esc_url($trope['url']); ?>">→ <?php echo wp_kses_post($trope['html']); ?> books</a>
						<?php endforeach; ?>
						<?php if ($author) : ?>
							<a class="sss-book-page__seoLink" href="<?php echo esc_url(home_url('/?s=' . rawurlencode($author))); ?>">→ all <?php echo esc_html($author); ?> books</a>
						<?php endif; ?>
						<a class="sss-book-page__seoLink" href="<?php echo esc_url(home_url('/books-like-' . sanitize_title($title) . '/')); ?>">→ books like <?php echo esc_html($title); ?></a>
						<?php if ($series_name && $series_handle) : ?>
							<a class="sss-book-page__seoLink" href="<?php echo esc_url(home_url('/series/' . sanitize_title($series_handle) . '/')); ?>">→ <?php echo esc_html(strtolower($series_name)); ?> reading order</a>
						<?php endif; ?>
					</div>
				</section>
			</article>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();
