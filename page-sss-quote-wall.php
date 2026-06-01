<?php
/**
 * Template Name: SSS Quote Wall
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('bbb-sss-library', 'assets/css/sss-library.css');
	bbb_enqueue_css('bbb-quote-wall', 'assets/css/sss-quote-wall.css');
} else {
	wp_enqueue_style('bbb-sss-library', get_template_directory_uri() . '/assets/css/sss-library.css', array(), wp_get_theme()->get('Version'));
	wp_enqueue_style('bbb-quote-wall', get_template_directory_uri() . '/assets/css/sss-quote-wall.css', array(), wp_get_theme()->get('Version'));
}
wp_enqueue_script('bbb-supabase', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2', array(), null, false);
if (function_exists('bbb_enqueue_js')) {
	bbb_enqueue_js('bbb-sss-library', 'assets/js/sss-library.js', array('bbb-supabase'), false);
} else {
	wp_enqueue_script('bbb-sss-library', get_template_directory_uri() . '/assets/js/sss-library.js', array('bbb-supabase'), wp_get_theme()->get('Version'), false);
}

if (!function_exists('bbb_quote_wall_text')) {
	function bbb_quote_wall_text(WP_Post $quote): string {
		$text = trim((string) get_post_meta($quote->ID, '_quote_text', true));
		if ('' === $text) {
			$text = trim((string) get_post_meta($quote->ID, 'quote_text', true));
		}
		if ('' === $text) {
			$text = trim((string) get_post_meta($quote->ID, 'quote', true));
		}
		if ('' === $text) {
			$text = trim((string) get_post_meta($quote->ID, '_bbb_quote', true));
		}
		if ('' === $text) {
			$text = trim(wp_strip_all_tags($quote->post_content));
		}

		return wp_strip_all_tags($text);
	}
}

if (!function_exists('bbb_quote_wall_book')) {
	function bbb_quote_wall_book(WP_Post $quote): ?WP_Post {
		$book = function_exists('get_field') ? get_field('book', $quote->ID) : null;
		if ($book instanceof WP_Post) {
			return $book;
		}
		if (is_array($book) && isset($book['ID']) && is_numeric($book['ID'])) {
			$book_post = get_post((int) $book['ID']);
			if ($book_post instanceof WP_Post) {
				return $book_post;
			}
		}
		if (is_numeric($book)) {
			$book_post = get_post((int) $book);
			if ($book_post instanceof WP_Post) {
				return $book_post;
			}
		}

		$book_id = max(
			(int) get_post_meta($quote->ID, '_quote_book_id', true),
			(int) get_post_meta($quote->ID, '_quote_library_book_id', true),
			(int) get_post_meta($quote->ID, 'book_id', true),
			(int) get_post_meta($quote->ID, 'library_book_id', true)
		);
		if ($book_id > 0) {
			$book_post = get_post($book_id);
			if ($book_post instanceof WP_Post) {
				return $book_post;
			}
		}

		$handle = (string) get_post_meta($quote->ID, '_quote_book_handle', true);
		$handle = '' !== $handle ? $handle : (string) get_post_meta($quote->ID, 'book_handle', true);
		$handle = '' !== $handle ? $handle : (string) get_post_meta($quote->ID, '_bbb_book_handle', true);
		if ('' !== $handle) {
			foreach (array('bbb_book', 'sss_book') as $post_type) {
				$book_post = get_page_by_path($handle, OBJECT, $post_type);
				if ($book_post instanceof WP_Post) {
					return $book_post;
				}
			}
		}

		return null;
	}
}

if (!function_exists('bbb_quote_wall_book_meta')) {
	function bbb_quote_wall_url_value($value): string {
		if (is_array($value)) {
			return (string) ($value['url'] ?? $value['href'] ?? '');
		}

		$value = trim((string) $value);
		if (str_starts_with($value, '{')) {
			$decoded = json_decode($value, true);
			if (is_array($decoded)) {
				return (string) ($decoded['url'] ?? $decoded['href'] ?? '');
			}
		}

		return $value;
	}

	function bbb_quote_wall_book_meta(?WP_Post $book): array {
		if (!$book instanceof WP_Post) {
			return array('title' => '', 'author' => '', 'handle' => '', 'shelf' => '', 'url' => home_url('/library/'), 'modal' => array());
		}

		if (function_exists('sss_book_data')) {
			$data          = sss_book_data($book);
			$trope_names   = array_column((array) ($data['tropes'] ?? array()), 'name');
			$trope_display = array_map(
				static fn(array $trope): string => trim(((string) ($trope['emoji'] ?? '') ? (string) $trope['emoji'] . ' ' : '') . (string) ($trope['name'] ?? '')),
				(array) ($data['tropes'] ?? array())
			);
			$trope_urls    = array_map(
				static function (array $trope): string {
					$handle = sanitize_title((string) (($trope['handle'] ?? '') ?: ($trope['name'] ?? '')));

					return home_url('/' . $handle . '-books/');
				},
				(array) ($data['tropes'] ?? array())
			);

			return array(
				'title'  => (string) ($data['title'] ?? get_the_title($book)),
				'author' => (string) ($data['author'] ?? ''),
				'handle' => (string) ($data['handle'] ?? $book->post_name),
				'shelf'  => (string) ($data['shelf'] ?? ''),
				'url'    => home_url('/library/?book=' . rawurlencode((string) ($data['handle'] ?? $book->post_name))),
				'modal'  => array(
					'handle'         => (string) ($data['handle'] ?? $book->post_name),
					'title'          => (string) ($data['title'] ?? get_the_title($book)),
					'author'         => (string) ($data['author'] ?? ''),
					'cover'          => bbb_quote_wall_url_value($data['cover'] ?? ''),
					'amazon'         => bbb_quote_wall_url_value($data['amazon'] ?? ''),
					'bookshop'       => bbb_quote_wall_url_value($data['bookshop'] ?? ''),
					'shelf'          => (string) ($data['shelf'] ?? ''),
					'private-shelf'  => !empty($data['is_private']) ? 'true' : 'false',
					'spice'          => (string) ($data['spice'] ?? ''),
					'tropes'         => implode(', ', $trope_names),
					'tropes-display' => implode(', ', $trope_display),
					'trope-urls'     => implode(', ', $trope_urls),
					'why'            => (string) ($data['why'] ?? ''),
					'newsletter'     => bbb_quote_wall_url_value($data['newsletter'] ?? ''),
					'mini'           => (string) ($data['mini'] ?? ''),
					'series'         => (string) ($data['series_handle'] ?? ''),
					'series-name'    => (string) ($data['series_name'] ?? ''),
					'series-number'  => (string) ($data['series_number'] ?? ''),
					'tension'        => (string) ($data['tension'] ?? ''),
					'damage'         => (string) ($data['damage'] ?? ''),
					'yearning'       => (string) ($data['yearning'] ?? ''),
					'boyfriend'      => (string) ($data['boyfriend'] ?? ''),
					'boyfriend-name' => (string) ($data['boyfriend_name'] ?? ''),
					'reread'         => !empty($data['reread']) ? 'true' : 'false',
					'standalone'     => !empty($data['standalone']) ? 'true' : 'false',
					'ku'             => !empty($data['ku']) ? 'true' : 'false',
					'darkness'       => (string) ($data['darkness'] ?? ''),
				),
			);
		}

		return array(
			'title'  => get_the_title($book),
			'author' => '',
			'handle' => $book->post_name,
			'shelf'  => '',
			'url'    => home_url('/library/?book=' . rawurlencode($book->post_name)),
			'modal'  => array(),
		);
	}
}

if (!function_exists('bbb_quote_wall_theme')) {
	function bbb_quote_wall_theme(string $shelf, int $index): string {
		$shelf = strtolower($shelf);
		if (str_contains($shelf, 'dark') || str_contains($shelf, 'private')) {
			return 'red';
		}
		if (str_contains($shelf, 'fantasy') || str_contains($shelf, 'romantasy')) {
			return 'blue';
		}
		if (str_contains($shelf, 'soft') || str_contains($shelf, 'sentimental')) {
			return 'yellow';
		}

		return array('default', 'red', 'gray', 'yellow')[$index % 4];
	}
}

$quote_post_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array();
$is_society = function_exists('bbb_reader_is_society') ? bbb_reader_is_society() : false;
$quote_limit = $is_society ? -1 : 6;
$quotes = $quote_post_types
	? get_posts(
		array(
			'post_type'      => $quote_post_types,
			'post_status'    => array('publish', 'draft'),
			'posts_per_page' => $quote_limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	)
	: array();
if (!$quotes && function_exists('bbb_quote_export_entries')) {
	$quotes = bbb_quote_export_entries((int) $quote_limit);
}

$join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');

get_header();
?>

<section class="sss-qw<?php echo $is_society ? ' is-unlocked' : ' is-preview'; ?>" data-sss-quote-wall data-sss-lib="<?php echo esc_attr($is_society ? 'society' : 'public'); ?>">
	<div class="sss-qw__wrap">
		<p class="sss-kicker">quote library</p>
		<h1 class="sss-title">faded pages & fatal lines.</h1>
		<p class="sss-sub">tap a line to open the book. search by quote, book, author, trope, or shelf.</p>

		<div class="sss-qw__tools">
			<label class="screen-reader-text" for="bbbQuoteWallSearch">search quote wall</label>
			<input
				type="search"
				id="bbbQuoteWallSearch"
				class="sss-qw__search"
				placeholder="search by quote, book, author, trope, or shelf"
				autocomplete="off"
				data-qw-search
			>
			<div class="sss-qw__metaRow">
				<div class="sss-qw__count" data-qw-count>all quotes</div>
				<div class="sss-qw__hint">paid members get the full wall.</div>
			</div>
		</div>

		<div class="qw-list" aria-label="<?php esc_attr_e('Quote wall', 'bybookishbabe-shopify-port'); ?>" data-qw-list>
		<?php if (!$quotes) : ?>
			<div class="sss-qw__empty is-visible">
				no quotes yet. add quotes under the Quotes admin area and they will land here.
			</div>
		<?php endif; ?>

		<?php foreach ($quotes as $index => $quote) : ?>
			<?php
			$book_meta = array('title' => '', 'author' => '', 'handle' => '', 'shelf' => '', 'url' => home_url('/library/'));
			if ($quote instanceof WP_Post) {
				$text = bbb_quote_wall_text($quote);
				$book_meta = bbb_quote_wall_book_meta(bbb_quote_wall_book($quote));
				$fallback_title = get_the_title($quote);
			} elseif (is_array($quote)) {
				$text = trim((string) ($quote['text'] ?? ''));
				$book_handle = (string) ($quote['book_handle'] ?? '');
				$book_meta = array(
					'title'  => (string) ($quote['book_title'] ?? ''),
					'author' => '',
					'handle' => $book_handle,
					'shelf'  => '',
					'url'    => '' !== $book_handle ? home_url('/library/?book=' . rawurlencode($book_handle)) : home_url('/library/'),
				);
				$fallback_title = '';
			} else {
				continue;
			}
			if ('' === $text) {
				continue;
			}
			if (function_exists('bbb_bookish_book_title')) {
				$book_meta['title'] = bbb_bookish_book_title((string) $book_meta['title']);
			}
			if (function_exists('bbb_bookish_proper_name')) {
				$book_meta['author'] = bbb_bookish_proper_name((string) $book_meta['author']);
			}
			$theme = bbb_quote_wall_theme((string) $book_meta['shelf'], (int) $index);
			$align = 0 === ((int) $index % 2) ? 'is-left' : 'is-right';
			$modal = (array) ($book_meta['modal'] ?? array());
			?>
			<div
				class="qw-item <?php echo esc_attr($align); ?>"
				data-qw-item
				data-qw-quote="<?php echo esc_attr($text); ?>"
				data-qw-title="<?php echo esc_attr((string) $book_meta['title']); ?>"
				data-qw-author="<?php echo esc_attr((string) $book_meta['author']); ?>"
				data-qw-shelf="<?php echo esc_attr((string) $book_meta['shelf']); ?>"
				data-qw-tropes="<?php echo esc_attr((string) ($modal['tropes'] ?? '')); ?>"
				data-qw-boyfriend="<?php echo esc_attr((string) ($modal['boyfriend'] ?? '')); ?>"
				style="--d: <?php echo esc_attr((string) (((int) $index % 8) * 45)); ?>ms;"
			>
				<div class="qw-card">
					<?php if ($modal) : ?>
						<button
							type="button"
							class="qw-cardSurface sss-lib__book"
							data-qw-open
							<?php foreach ($modal as $attr => $value) : ?>
								data-<?php echo esc_attr((string) $attr); ?>="<?php echo esc_attr((string) $value); ?>"
							<?php endforeach; ?>
						>
					<?php else : ?>
						<a class="qw-cardSurface" href="<?php echo esc_url((string) $book_meta['url']); ?>">
					<?php endif; ?>
							<div class="qw-paper">
								<p class="qw-quote">
									<span class="hl hl--<?php echo esc_attr($theme); ?>">"<?php echo esc_html($text); ?>"</span>
								</p>
								<div class="qw-meta">
									<div class="qw-book">
										<?php if ('' !== $book_meta['title']) : ?>
											<?php echo esc_html((string) $book_meta['title']); ?>
											<?php if ('' !== $book_meta['author']) : ?>
												<?php echo esc_html(' — ' . (string) $book_meta['author']); ?>
											<?php endif; ?>
										<?php else : ?>
											<?php echo esc_html($fallback_title); ?>
										<?php endif; ?>
									</div>
								</div>
							</div>
					<?php echo $modal ? '</button>' : '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		<?php endforeach; ?>
		</div>
		<div class="sss-qw__empty" data-qw-empty>no matches yet. try a book title, author, shelf, or trope instead.</div>

	<?php if (!$is_society) : ?>
		<section class="sss-qw__gate">
			<p>preview mode</p>
			<h2>paid members get the full quote wall.</h2>
			<a href="<?php echo esc_url($join_url); ?>">join the society</a>
		</section>
	<?php endif; ?>
	</div>

	<?php get_template_part('template-parts/library/library-modal'); ?>
</section>

<script>
(function(){
	function initQuoteWall(){
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
						item.getAttribute('data-qw-shelf') || '',
						item.getAttribute('data-qw-tropes') || '',
						item.getAttribute('data-qw-boyfriend') || ''
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

	initQuoteWall();
})();
</script>

<?php get_footer(); ?>
