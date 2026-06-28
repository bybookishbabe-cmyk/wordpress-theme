<?php
/**
 * Per-book quote wall.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('bbb-sss-library', 'assets/css/sss-library.css');
	bbb_enqueue_css('bbb-quote-wall', 'assets/css/sss-quote-wall.css', array('bbb-sss-library'));
} else {
	wp_enqueue_style('bbb-sss-library', get_template_directory_uri() . '/assets/css/sss-library.css', array(), wp_get_theme()->get('Version'));
	wp_enqueue_style('bbb-quote-wall', get_template_directory_uri() . '/assets/css/sss-quote-wall.css', array('bbb-sss-library'), wp_get_theme()->get('Version'));
}

if (function_exists('bbb_enqueue_js')) {
	bbb_enqueue_js('bbb-sss-library', 'assets/js/sss-library.js', array(), false);
} else {
	wp_enqueue_script('bbb-sss-library', get_template_directory_uri() . '/assets/js/sss-library.js', array(), wp_get_theme()->get('Version'), false);
}

if (!function_exists('bbb_quote_wall_highlighted_text')) {
	function bbb_quote_wall_highlighted_text(string $text, string $theme): string {
		$lines = preg_split('/\R/u', $text);
		if (!is_array($lines) || !$lines) {
			$lines = array($text);
		}

		$output     = array();
		$last_index = count($lines) - 1;
		foreach ($lines as $index => $line) {
			if ($index > 0) {
				$output[] = '<br>';
			}

			if ('' === trim((string) $line)) {
				continue;
			}

			$line_text = (string) $line;
			if (0 === $index) {
				$line_text = '"' . $line_text;
			}
			if ($last_index === $index) {
				$line_text .= '"';
			}

			$output[] = sprintf(
				'<span class="hl hl--%1$s">%2$s</span>',
				esc_attr($theme),
				esc_html($line_text)
			);
		}

		return implode('', $output);
	}
}

get_header();

if (!have_posts()) {
	get_footer();
	return;
}

the_post();

$book_id = get_the_ID();
$book    = get_post($book_id);
if (!$book instanceof WP_Post) {
	get_footer();
	return;
}

$data   = function_exists('sss_book_data') ? sss_book_data($book) : array();
$title  = (string) ($data['title'] ?? get_the_title($book));
$author = (string) ($data['author'] ?? '');
$shelf  = (string) ($data['shelf'] ?? '');

if (function_exists('bbb_bookish_book_title')) {
	$title = bbb_bookish_book_title($title);
}
if (function_exists('bbb_bookish_proper_name')) {
	$author = bbb_bookish_proper_name($author);
}

$quote_scope_books = function_exists('bbb_book_quote_scope_books') ? bbb_book_quote_scope_books($book) : array($book);
$is_series_quotes  = count($quote_scope_books) > 1;
$quote_scope_label = $is_series_quotes && function_exists('bbb_book_quote_scope_label') ? bbb_book_quote_scope_label($book) : $title;
$quotes            = function_exists('bbb_book_quote_posts') ? bbb_book_quote_posts($book) : array();
$book_url         = get_permalink($book);
$all_quotes_url   = home_url('/sss-quote-wall/');
$books_like_url   = home_url('/books-like-' . sanitize_title($title) . '/');
$cover_url        = function_exists('bbb_get_book_cover_url') ? (string) bbb_get_book_cover_url($book_id, 'large') : '';
$cover_alt        = function_exists('bbb_book_cover_alt') ? bbb_book_cover_alt($title, $author, $shelf) : trim($title . ($author ? ' by ' . $author : '') . ' book cover');
$quote_count_text = 1 === count($quotes) ? '1 quote' : count($quotes) . ' quotes';
$quote_h2_text    = $is_series_quotes ? ucfirst($quote_count_text) . ' from ' . $quote_scope_label : ($author ? ucfirst($quote_count_text) . ' from ' . $title . ' by ' . $author : ucfirst($quote_count_text) . ' from ' . $title);
$explore_links    = array();

foreach (array_slice((array) ($data['tropes'] ?? array()), 0, 4) as $trope) {
	$name = trim((string) ($trope['name'] ?? ''));
	if ('' === $name) {
		continue;
	}

	$handle = sanitize_title((string) ($trope['handle'] ?? $name));
	$explore_links[] = array(
		'label' => (function_exists('bbb_trope_label_html') ? bbb_trope_label_html($name, $trope['emoji'] ?? '', $handle) : esc_html(trim(((string) ($trope['emoji'] ?? '') ?: '') . ' ' . $name))) . ' books',
		'url'   => function_exists('bbb_trope_page_url') ? bbb_trope_page_url($name, $handle) : home_url('/' . $handle . '-books/'),
	);
}

$explore_links[] = array(
	'label' => 'books like ' . esc_html($title),
	'url'   => $books_like_url,
);

$series_name   = (string) ($data['series_name'] ?? '');
$series_handle = sanitize_title((string) ($data['series_handle'] ?? ''));
if ('' !== $series_name && '' !== $series_handle) {
	$explore_links[] = array(
		'label' => esc_html(strtolower(function_exists('bbb_book_series_label') ? bbb_book_series_label($series_name) : $series_name)) . ' reading order',
		'url'   => home_url('/series/' . $series_handle . '/'),
	);
}

$book_quote_theme = static function (int $index) use ($shelf): string {
	$shelf_value = strtolower($shelf);
	if (str_contains($shelf_value, 'dark') || str_contains($shelf_value, 'private')) {
		return 'red';
	}
	if (str_contains($shelf_value, 'fantasy') || str_contains($shelf_value, 'romantasy')) {
		return 'blue';
	}
	if (str_contains($shelf_value, 'soft') || str_contains($shelf_value, 'sentimental')) {
		return 'yellow';
	}

	return array('default', 'red', 'gray', 'yellow')[$index % 4];
};
?>

<section class="sss-qw bbb-book-quotes is-unlocked" data-sss-quote-wall>
	<div class="sss-qw__wrap">
		<p class="sss-kicker">book quote library</p>
		<h1 class="sss-title"><?php echo esc_html($quote_scope_label); ?> Quotes</h1>
		<h2 class="bbb-book-quotes__subtitle"><?php echo esc_html($quote_h2_text); ?></h2>
		<p class="sss-sub"><?php echo esc_html($is_series_quotes ? 'save the lines you want to keep; each quote shows the book it came from.' : 'save the lines you want to keep, then step back into the full book page when you are ready.'); ?></p>

		<div class="sss-qw__tools">
			<label class="screen-reader-text" for="bbbBookQuoteSearch">search this book's quotes</label>
			<input
				type="search"
				id="bbbBookQuoteSearch"
				class="sss-qw__search"
				placeholder="search <?php echo esc_attr(strtolower($quote_scope_label)); ?> quotes"
				autocomplete="off"
				data-qw-search
			>
			<div class="sss-qw__metaRow">
				<div class="sss-qw__count" data-qw-count><?php echo esc_html($quote_count_text); ?></div>
				<div class="sss-qw__hint">
					<a href="<?php echo esc_url($book_url); ?>">back to book</a>
					<span class="sss-qw__hintDivider" aria-hidden="true"></span>
					<a href="<?php echo esc_url($all_quotes_url); ?>">all quotes</a>
				</div>
			</div>
		</div>

		<div class="qw-list" aria-label="<?php echo esc_attr($title . ' quotes'); ?>" data-qw-list>
		<?php if (!$quotes) : ?>
			<div class="sss-qw__empty is-visible">
				<?php echo esc_html($is_series_quotes ? 'no quotes are linked to this series yet.' : 'no quotes are linked to this book yet.'); ?>
			</div>
		<?php endif; ?>

		<?php foreach ($quotes as $index => $quote) : ?>
			<?php
			$quote_text = function_exists('bbb_bookquote_quote_text') ? bbb_bookquote_quote_text($quote) : trim(wp_strip_all_tags((string) $quote->post_content));
			if ('' === $quote_text) {
				continue;
			}

			$theme       = $book_quote_theme((int) $index);
			$align       = 0 === ((int) $index % 2) ? 'is-left' : 'is-right';
			$quote_anchor = 'quote-' . (string) $quote->ID;
			$source_book  = function_exists('bbb_book_quote_source_book') ? bbb_book_quote_source_book($quote, $book) : $book;
			$source_data  = function_exists('sss_book_data') ? sss_book_data($source_book) : array();
			$source_title = (string) ($source_data['title'] ?? get_the_title($source_book));
			$source_author = (string) ($source_data['author'] ?? '');
			$source_shelf = (string) ($source_data['shelf'] ?? $shelf);
			$source_url   = get_permalink($source_book) ?: $book_url;
			if (function_exists('bbb_bookish_book_title')) {
				$source_title = bbb_bookish_book_title($source_title);
			}
			if (function_exists('bbb_bookish_proper_name')) {
				$source_author = bbb_bookish_proper_name($source_author);
			}
			?>
			<div
				id="<?php echo esc_attr($quote_anchor); ?>"
				class="qw-item <?php echo esc_attr($align); ?>"
				data-qw-item
				data-qw-quote="<?php echo esc_attr($quote_text); ?>"
				data-qw-title="<?php echo esc_attr($source_title); ?>"
				data-qw-author="<?php echo esc_attr($source_author); ?>"
				data-qw-shelf="<?php echo esc_attr($source_shelf); ?>"
				style="--d: <?php echo esc_attr((string) (((int) $index % 8) * 45)); ?>ms;"
			>
				<div class="qw-card">
					<a class="qw-cardSurface" href="<?php echo esc_url($source_url); ?>">
							<div class="qw-paper">
								<p class="qw-quote">
									<?php echo bbb_quote_wall_highlighted_text($quote_text, $theme); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</p>
							<div class="qw-meta">
								<div class="qw-book">
									<?php echo esc_html($source_title . ($source_author ? ' - ' . $source_author : '')); ?>
								</div>
							</div>
						</div>
					</a>
				</div>
			</div>
		<?php endforeach; ?>
		</div>

		<div class="sss-qw__empty" data-qw-empty>no matches yet. try another word from the quote.</div>

			<section class="bbb-book-quotes__next" aria-label="more ways to keep reading">
				<div class="bbb-book-quotes__nextGrid">
					<a class="bbb-book-quotes__nextCard bbb-book-quotes__nextCard--book" href="<?php echo esc_url($book_url); ?>">
						<span class="bbb-book-quotes__nextText">
							<span class="bbb-book-quotes__nextKicker">main book page</span>
							<strong>back to <?php echo esc_html($title); ?></strong>
							<span><?php echo esc_html($author ? $title . ' by ' . $author : $title); ?></span>
						</span>
						<?php if ($cover_url) : ?>
							<span class="bbb-book-quotes__nextCover">
								<img src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr($cover_alt); ?>" loading="lazy" decoding="async">
							</span>
						<?php endif; ?>
					</a>
					<a class="bbb-book-quotes__nextCard" href="<?php echo esc_url($books_like_url); ?>">
						<span class="bbb-book-quotes__nextText">
							<span class="bbb-book-quotes__nextKicker">same damage</span>
							<strong>books like <?php echo esc_html($title); ?></strong>
							<span>more reads with the same mood, tropes, and emotional wreckage.</span>
						</span>
					</a>
				</div>
			</section>

			<?php if ($explore_links) : ?>
				<section class="bbb-book-quotes__explore" aria-label="explore more like this">
					<p class="bbb-book-quotes__sectionLabel">explore more like this</p>
					<div class="bbb-book-quotes__exploreGrid">
						<?php foreach ($explore_links as $link) : ?>
							<a class="bbb-book-quotes__exploreLink" href="<?php echo esc_url((string) $link['url']); ?>">→ <?php echo wp_kses_post((string) $link['label']); ?></a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
	</div>
</section>

<script>
(function(){
	function initBookQuoteWall(){
		var roots = document.querySelectorAll('[data-sss-quote-wall]');
		if (!roots.length) return;

		roots.forEach(function(root){
			if (root.__qwBound) return;
			root.__qwBound = true;

			var items = Array.prototype.slice.call(root.querySelectorAll('[data-qw-item]'));
			var searchInput = root.querySelector('[data-qw-search]');
			var countLabel = root.querySelector('[data-qw-count]');
			var emptyState = root.querySelector('[data-qw-empty]');
			if (!items.length) return;

			function updateCount(visibleCount, query){
				if (!countLabel) return;
				if (!query){
					countLabel.textContent = visibleCount === 1 ? '1 quote' : visibleCount + ' quotes';
					return;
				}
				countLabel.textContent = visibleCount === 1
					? '1 match for "' + query + '"'
					: visibleCount + ' matches for "' + query + '"';
			}

			function applyFilter(){
				var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
				var terms = query.replace(/[^\w\s]+/g, ' ').trim().split(/\s+/).filter(Boolean);
				var visibleCount = 0;

				items.forEach(function(item){
					var haystack = [
						item.getAttribute('data-qw-quote') || '',
						item.getAttribute('data-qw-title') || '',
						item.getAttribute('data-qw-author') || '',
						item.getAttribute('data-qw-shelf') || ''
					].join(' ').toLowerCase().replace(/[^\w\s]+/g, ' ');
					var isMatch = !terms.length || terms.every(function(term){ return haystack.indexOf(term) !== -1; });
					item.hidden = !isMatch;
					item.style.display = isMatch ? '' : 'none';
					if (isMatch) visibleCount += 1;
				});

				if (emptyState) emptyState.classList.toggle('is-visible', visibleCount === 0);
				updateCount(visibleCount, query);
			}

			if (searchInput){
				searchInput.addEventListener('input', applyFilter);
				searchInput.addEventListener('search', applyFilter);
				searchInput.addEventListener('change', applyFilter);
			}

			applyFilter();

			if (!('IntersectionObserver' in window)) {
				items.forEach(function(item){ item.classList.add('is-in'); });
				return;
			}

			var observer = new IntersectionObserver(function(entries){
				entries.forEach(function(entry){
					if (!entry.isIntersecting) return;
					entry.target.classList.add('is-in');
					observer.unobserve(entry.target);
				});
			}, { threshold: 0.12, rootMargin: '0px 0px -10% 0px' });

			items.forEach(function(item){ observer.observe(item); });
		});
	}

	initBookQuoteWall();
})();
</script>

<?php get_footer(); ?>
