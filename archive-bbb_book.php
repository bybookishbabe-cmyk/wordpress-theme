<?php
/**
 * Book archive template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_book_archive_search_text')) {
	function bbb_book_archive_search_text(string $value): string {
		$value = html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$value = strtolower($value);
		$value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

		return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
	}
}

if (!function_exists('bbb_book_archive_book_matches_search')) {
	function bbb_book_archive_book_matches_search(WP_Post $book, string $search): bool {
		$needle = bbb_book_archive_search_text($search);
		if ('' === $needle) {
			return true;
		}

		$book_id = (int) $book->ID;
		$terms = array();
		foreach (array('bbb_trope', 'bbb_shelf', 'bbb_series') as $taxonomy) {
			$book_terms = get_the_terms($book_id, $taxonomy);
			if ($book_terms && !is_wp_error($book_terms)) {
				$terms = array_merge($terms, wp_list_pluck($book_terms, 'name'));
			}
		}

		$haystack = implode(
			' ',
			array_filter(
				array_merge(
					array(
						get_the_title($book_id),
						(string) get_post_field('post_name', $book_id),
						function_exists('bbb_get_book_author') ? bbb_get_book_author($book_id) : (string) get_post_meta($book_id, '_bbb_author', true),
						function_exists('bbb_get_book_shelf_name') ? bbb_get_book_shelf_name($book_id) : '',
						(string) get_post_meta($book_id, '_bbb_mini_note', true),
						(string) get_post_meta($book_id, '_bbb_why', true),
						(string) get_post_meta($book_id, '_bbb_series_handle', true),
						(string) get_post_meta($book_id, '_bbb_boyfriend_name', true),
						(string) get_post_meta($book_id, '_bbb_boyfriend_type', true),
					),
					$terms
				)
			)
		);

		return str_contains(bbb_book_archive_search_text($haystack), $needle);
	}
}

$book_search = isset($_GET['book_search']) ? sanitize_text_field((string) wp_unslash($_GET['book_search'])) : '';
$books = new WP_Query(
	array(
		'post_type'      => 'bbb_book',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	)
);

$visible_books = array_values(
	array_filter(
		$books->posts,
		static function (WP_Post $book): bool {
			return function_exists('bbb_book_is_publicly_visible')
				? bbb_book_is_publicly_visible($book->ID)
				: true;
		}
	)
);

if ('' !== trim($book_search)) {
	$visible_books = array_values(
		array_filter(
			$visible_books,
			static function (WP_Post $book) use ($book_search): bool {
				return bbb_book_archive_book_matches_search($book, $book_search);
			}
		)
	);
}

get_header();
?>

<main class="bbb-review-index bbb-book-archive">
	<section class="page-width">
		<header class="bbb-review-index__hero bbb-book-archive__hero">
			<p class="bbb-review-index__kicker">book pages</p>
			<h1 class="bbb-review-index__title">book pages</h1>
			<p class="bbb-review-index__sub">the full book hub: covers, tropes, spice, series details, and every little page built for choosing your next obsession.</p>
		</header>

		<form class="bbb-book-archive-search" role="search" method="get" action="<?php echo esc_url(get_post_type_archive_link('bbb_book') ?: home_url('/books/')); ?>" data-book-archive-search>
			<label class="bbb-book-archive-search__label" for="bbb-book-archive-search-input">search book pages</label>
			<div class="bbb-book-archive-search__bar">
				<input
					id="bbb-book-archive-search-input"
					name="book_search"
					type="search"
					value="<?php echo esc_attr($book_search); ?>"
					placeholder="search by title, author, trope, series, or boyfriend"
					autocomplete="off"
					data-book-archive-search-input
				>
				<button class="bbb-book-archive-search__submit" type="submit">search</button>
				<button class="bbb-book-archive-search__clear" type="button" data-book-archive-search-clear hidden>clear</button>
			</div>
			<p class="bbb-book-archive-search__status" data-book-archive-search-status aria-live="polite"></p>
		</form>

		<?php if ($visible_books) : ?>
			<div class="bbb-book-archive__grid">
				<?php foreach ($visible_books as $book) : ?>
					<?php
					$book_id    = $book->ID;
					$title      = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title(get_the_title($book_id)) : get_the_title($book_id);
					$author     = function_exists('bbb_get_book_author') ? bbb_get_book_author($book_id) : (string) get_post_meta($book_id, '_bbb_author', true);
					$cover      = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book_id) : (string) get_the_post_thumbnail_url($book_id, 'large');
					$shelf      = function_exists('bbb_get_book_shelf_name') ? bbb_get_book_shelf_name($book_id) : '';
					$cover_alt  = function_exists('bbb_book_cover_alt') ? bbb_book_cover_alt($title, $author, $shelf) : $title . ' book cover';
					$spice      = max(0, (int) get_post_meta($book_id, '_bbb_spice', true));
					$mini       = (string) get_post_meta($book_id, '_bbb_mini_note', true);
					$book_url   = get_permalink($book_id) ?: home_url('/books/' . $book->post_name . '/');
					$trope_terms = get_the_terms($book_id, 'bbb_trope');
					$series_terms = get_the_terms($book_id, 'bbb_series');
					$search_terms = array();
					if ($trope_terms && !is_wp_error($trope_terms)) {
						$search_terms = array_merge($search_terms, wp_list_pluck($trope_terms, 'name'));
					}
					if ($series_terms && !is_wp_error($series_terms)) {
						$search_terms = array_merge($search_terms, wp_list_pluck($series_terms, 'name'));
					}
					$search_text = bbb_book_archive_search_text(
						implode(
							' ',
							array_filter(
								array_merge(
									array(
										$title,
										$book->post_name,
										$author,
										$shelf,
										$mini,
										(string) get_post_meta($book_id, '_bbb_why', true),
										(string) get_post_meta($book_id, '_bbb_series_handle', true),
										(string) get_post_meta($book_id, '_bbb_boyfriend_name', true),
										(string) get_post_meta($book_id, '_bbb_boyfriend_type', true),
									),
									$search_terms
								)
							)
						)
					);
					?>
					<a class="bbb-book-archive-card" href="<?php echo esc_url($book_url); ?>" data-book-archive-card data-search-text="<?php echo esc_attr($search_text); ?>">
						<div class="bbb-book-archive-card__cover">
							<?php if ($cover) : ?>
								<img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($cover_alt); ?>" loading="lazy" onerror="this.hidden=true;">
							<?php else : ?>
								<span><?php echo esc_html(mb_substr($title, 0, 1)); ?></span>
							<?php endif; ?>
							<?php if ($spice > 0) : ?>
								<span class="bbb-book-archive-card__spice"><?php echo esc_html(str_repeat('🌶', min(5, $spice))); ?></span>
							<?php endif; ?>
						</div>
						<div class="bbb-book-archive-card__body">
							<?php if ($shelf) : ?>
								<p class="bbb-book-archive-card__kicker"><?php echo esc_html($shelf); ?></p>
							<?php endif; ?>
							<h2><?php echo esc_html($title); ?></h2>
							<?php if ($author) : ?>
								<p class="bbb-book-archive-card__author">by <?php echo esc_html($author); ?></p>
							<?php endif; ?>
							<?php if ($mini) : ?>
								<p class="bbb-book-archive-card__mini"><?php echo esc_html($mini); ?></p>
							<?php endif; ?>
							<?php if ($trope_terms && !is_wp_error($trope_terms)) : ?>
								<div class="bbb-book-archive-card__tropes">
									<?php foreach (array_slice($trope_terms, 0, 3) as $trope) : ?>
										<span><?php echo esc_html($trope->name); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="bbb-review-index__empty" data-book-archive-empty hidden>no book pages matched that search.</div>
		<?php else : ?>
			<div class="bbb-review-index__empty">
				<?php echo esc_html('' !== trim($book_search) ? 'no book pages matched that search.' : 'no book pages are published yet.'); ?>
			</div>
		<?php endif; ?>
	</section>
</main>

<script>
(() => {
	const root = document.querySelector('[data-book-archive-search]');
	const input = root ? root.querySelector('[data-book-archive-search-input]') : null;
	const clear = root ? root.querySelector('[data-book-archive-search-clear]') : null;
	const status = root ? root.querySelector('[data-book-archive-search-status]') : null;
	const empty = document.querySelector('[data-book-archive-empty]');
	const cards = Array.from(document.querySelectorAll('[data-book-archive-card]'));
	if (!root || !input || !cards.length) return;

	const normalize = (value) => String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim().replace(/\s+/g, ' ');
	const update = () => {
		const query = normalize(input.value);
		let visible = 0;
		cards.forEach((card) => {
			const match = !query || (card.dataset.searchText || '').includes(query);
			card.hidden = !match;
			if (match) visible += 1;
		});
		if (clear) clear.hidden = !query;
		if (empty) empty.hidden = !query || visible > 0;
		if (status) {
			status.textContent = query
				? `${visible} result${visible === 1 ? '' : 's'} for "${input.value.trim()}"`
				: '';
		}
	};

	root.addEventListener('submit', (event) => {
		event.preventDefault();
		update();

		const query = normalize(input.value);
		if (!query) return;

		const matches = cards.filter((card) => !card.hidden);
		if (matches.length === 1 && matches[0].href) {
			window.location.href = matches[0].href;
		}
	});
	input.addEventListener('input', update);
	if (clear) {
		clear.addEventListener('click', () => {
			input.value = '';
			input.focus();
			update();
		});
	}
	update();
})();
</script>

<?php
wp_reset_postdata();
get_footer();
