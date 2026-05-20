<?php
/**
 * Template Name: Series Reading Orders
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

require_once get_theme_file_path('template-parts/sss-book-card.php');

if (!function_exists('bbb_series_visible_books')) {
	function bbb_series_visible_books(): array {
		$post_types = array_values(
			array_filter(
				array('sss_book', 'bbb_book'),
				static fn(string $post_type): bool => post_type_exists($post_type)
			)
		);

		$books = get_posts(
			array(
				'post_type'      => $post_types ?: 'sss_book',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		return array_values(
			array_filter(
				$books,
				static function (WP_Post $book): bool {
					if ('bbb_book' === $book->post_type && function_exists('bbb_book_is_publicly_visible')) {
						return bbb_book_is_publicly_visible($book->ID);
					}

					return function_exists('sss_book_is_visible') ? sss_book_is_visible($book->ID) : true;
				}
			)
		);
	}
}

if (!function_exists('bbb_series_terms')) {
	function bbb_series_terms(): array {
		$series = array();

		foreach (array('bbb_series', 'sss_series') as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			if ($terms && !is_wp_error($terms)) {
				$series = array_merge($series, array_values($terms));
			}
		}

		if (post_type_exists('sss_series')) {
			$series = array_merge(
				$series,
				get_posts(
					array(
						'post_type'      => 'sss_series',
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				)
			);
		}

		return $series;
	}
}

if (!function_exists('bbb_series_entity_slug')) {
	function bbb_series_entity_slug($series): string {
		if ($series instanceof WP_Term) {
			return $series->slug;
		}

		return $series instanceof WP_Post ? $series->post_name : '';
	}
}

if (!function_exists('bbb_series_entity_title')) {
	function bbb_series_entity_title($series): string {
		if ($series instanceof WP_Term) {
			return $series->name;
		}

		return $series instanceof WP_Post ? get_the_title($series) : '';
	}
}

if (!function_exists('bbb_series_entity_meta')) {
	function bbb_series_entity_meta($series, string $key, $default = '') {
		if ($series instanceof WP_Term) {
			$value = get_term_meta($series->term_id, $key, true);
			if ('' !== $value && null !== $value) {
				return $value;
			}

			if (function_exists('get_field')) {
				$field = get_field($key, 'term_' . $series->term_id);
				if ('' !== $field && null !== $field && false !== $field) {
					return $field;
				}
			}

			return $default;
		}

		return $series instanceof WP_Post ? bbb_series_field($series->ID, $key, $default) : $default;
	}
}

if (!function_exists('bbb_series_book_matches')) {
	function bbb_series_book_matches(WP_Post $book, $series): bool {
		$slug = bbb_series_entity_slug($series);
		$name = bbb_series_entity_title($series);

		if ($series instanceof WP_Term && has_term($series->term_id, $series->taxonomy, $book)) {
			return true;
		}

		$data = bbb_series_book_data($book);

		return $data['series_handle'] === $slug || strtolower($data['series_name']) === strtolower($name);
	}
}

if (!function_exists('bbb_series_books_for_series')) {
	function bbb_series_books_for_series($series, array $books): array {
		$matches = array_values(
			array_filter(
				$books,
				static fn(WP_Post $book): bool => bbb_series_book_matches($book, $series)
			)
		);

		usort(
			$matches,
			static function (WP_Post $a, WP_Post $b): int {
				$a_number = (int) bbb_series_book_data($a)['series_number'];
				$b_number = (int) bbb_series_book_data($b)['series_number'];

				return $a_number <=> $b_number;
			}
		);

		return $matches;
	}
}

if (!function_exists('bbb_series_lead_book')) {
	function bbb_series_lead_book($series, array $books, bool $require_one = false): ?WP_Post {
		$series_books = bbb_series_books_for_series($series, $books);
		$fallback     = $series_books[0] ?? null;

		foreach ($series_books as $book) {
			if ('1' === trim((string) bbb_series_book_data($book)['series_number'])) {
				return $book;
			}
		}

		return $require_one ? null : $fallback;
	}
}

if (!function_exists('bbb_series_guide_posts')) {
	function bbb_series_guide_posts(): array {
		$queries = array(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 250,
				'category_name'  => 'curated-romance-guides',
				'orderby'        => 'date',
				'order'          => 'DESC',
			),
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 250,
				'meta_key'       => '_shopify_blog_handle',
				'meta_value'     => 'curated-romance-guides',
				'orderby'        => 'date',
				'order'          => 'DESC',
			),
		);
		$posts   = array();

		foreach ($queries as $query_args) {
			foreach (get_posts($query_args) as $post) {
				if ($post instanceof WP_Post) {
					$posts[$post->ID] = $post;
				}
			}
		}

		return array_values($posts);
	}
}

if (!function_exists('bbb_series_matches_guide_value')) {
	function bbb_series_matches_guide_value($value, $series): bool {
		$series_slug  = sanitize_title(bbb_series_entity_slug($series));
		$series_title = strtolower(trim(bbb_series_entity_title($series)));

		if ($value instanceof WP_Post) {
			return ($series instanceof WP_Post && (int) $value->ID === (int) $series->ID)
				|| sanitize_title($value->post_name) === $series_slug
				|| strtolower(trim(get_the_title($value))) === $series_title;
		}

		if ($value instanceof WP_Term) {
			return ($series instanceof WP_Term && (int) $value->term_id === (int) $series->term_id)
				|| sanitize_title($value->slug) === $series_slug
				|| strtolower(trim($value->name)) === $series_title;
		}

		if (is_array($value)) {
			foreach (array('ID', 'id') as $id_key) {
				if (isset($value[$id_key])) {
					$post = get_post((int) $value[$id_key]);
					if ($post instanceof WP_Post && bbb_series_matches_guide_value($post, $series)) {
						return true;
					}
				}
			}

			foreach (array('slug', 'post_name', 'handle') as $slug_key) {
				if (!empty($value[$slug_key]) && sanitize_title((string) $value[$slug_key]) === $series_slug) {
					return true;
				}
			}

			foreach (array('name', 'title', 'post_title') as $title_key) {
				if (!empty($value[$title_key]) && strtolower(trim((string) $value[$title_key])) === $series_title) {
					return true;
				}
			}

			foreach ($value as $item) {
				if (bbb_series_matches_guide_value($item, $series)) {
					return true;
				}
			}

			return false;
		}

		$raw = trim((string) $value);
		if ('' === $raw) {
			return false;
		}

		if (is_numeric($raw)) {
			$post = get_post((int) $raw);
			if ($post instanceof WP_Post && bbb_series_matches_guide_value($post, $series)) {
				return true;
			}
		}

		return sanitize_title($raw) === $series_slug || strtolower($raw) === $series_title;
	}
}

if (!function_exists('bbb_series_guide_post_for_series')) {
	function bbb_series_guide_post_for_series($series, array $guide_posts): ?WP_Post {
		$field_keys = array('sss_series', '_sss_series', 'series', '_series');

		foreach ($guide_posts as $post) {
			foreach ($field_keys as $field_key) {
				$value = function_exists('sss_article_field') ? sss_article_field($field_key, $post->ID, null) : null;
				if (bbb_series_matches_guide_value($value, $series)) {
					return $post;
				}

				$raw = get_post_meta($post->ID, $field_key, true);
				if (bbb_series_matches_guide_value($raw, $series)) {
					return $post;
				}
			}

			if (bbb_series_title_matches_guide_post($post, $series)) {
				return $post;
			}
		}

		return null;
	}
}

if (!function_exists('bbb_series_title_matches_guide_post')) {
	function bbb_series_title_matches_guide_post(WP_Post $post, $series): bool {
		$series_slug = sanitize_title(bbb_series_entity_slug($series));
		$title_slug  = sanitize_title(bbb_series_entity_title($series));
		$post_slug   = sanitize_title($post->post_name);
		$post_title  = sanitize_title(get_the_title($post));
		$haystack    = $post_slug . ' ' . $post_title;

		if ('' === $series_slug && '' === $title_slug) {
			return false;
		}

		$looks_like_reading_order = str_contains($haystack, 'series-reading-order') || str_contains($haystack, 'reading-order');
		if (!$looks_like_reading_order) {
			return false;
		}

		return ('' !== $series_slug && str_contains($haystack, $series_slug))
			|| ('' !== $title_slug && str_contains($haystack, $title_slug));
	}
}

$series_list  = bbb_series_terms();
$books        = bbb_series_visible_books();
$guide_posts  = bbb_series_guide_posts();
$series_cards = array();
$shelf_groups = array();

foreach ($series_list as $series) {
	$series_slug  = bbb_series_entity_slug($series);
	$series_title = bbb_series_entity_title($series);

	if ('' === $series_slug || '' === $series_title) {
		continue;
	}

	$series_books = bbb_series_books_for_series($series, $books);
	$first_book   = bbb_series_lead_book($series, $books);

	if (!$first_book || !$series_books) {
		continue;
	}

	$first_data       = bbb_series_book_data($first_book);
	$book_count       = count($series_books);
	$preview_books    = array_slice($series_books, 0, 3);
	$remaining_count  = max(0, $book_count - count($preview_books));
	$standalone_title = '';
	$max_spice        = 0;
	$shelf_name       = '' !== $first_data['shelf'] ? $first_data['shelf'] : 'series';
	$shelf_slug       = sanitize_title($shelf_name);

	foreach ($series_books as $book) {
		$book_data = bbb_series_book_data($book);
		$max_spice = max($max_spice, (int) $book_data['spice']);

		if ('' === $standalone_title && $book_data['standalone']) {
			$standalone_title = $book_data['title'];
		}
	}

	$shelf_groups[$shelf_slug] = $shelf_name;

	$guide_post   = bbb_series_guide_post_for_series($series, $guide_posts);
	$destination  = $guide_post instanceof WP_Post ? get_permalink($guide_post) : '';

	$series_cards[] = array(
		'series'           => $series,
		'title'            => $series_title,
		'destination'      => $destination,
		'has_guide'        => '' !== $destination,
		'first_data'       => $first_data,
		'book_count'       => $book_count,
		'preview_books'    => $preview_books,
		'remaining_count'  => $remaining_count,
		'standalone_title' => $standalone_title,
		'shelf_name'       => $shelf_name,
		'shelf_slug'       => $shelf_slug,
		'max_spice'        => $max_spice,
	);
}

get_header();
?>

<section class="bbb-seriesOrders" id="SeriesReadingOrders">
	<div class="bbb-seriesOrders__wrap page-width">
		<header class="bbb-seriesOrders__hero">
			<p class="bbb-seriesOrders__kicker">reading guides</p>
			<h1 class="bbb-seriesOrders__title">series reading orders</h1>
			<p class="bbb-seriesOrders__sub">every series i've read, in order — with spice levels and where to start if you don't want to commit to book one.</p>
		</header>

		<?php if ($series_cards) : ?>
			<section class="bbb-seriesOrders__archive">
				<div class="bbb-seriesOrders__filters" data-series-filters>
					<button class="bbb-seriesOrders__filter is-active" type="button" data-filter="all">all series</button>
					<?php foreach ($shelf_groups as $shelf_slug => $shelf_name) : ?>
						<button class="bbb-seriesOrders__filter" type="button" data-filter="<?php echo esc_attr($shelf_slug); ?>"><?php echo esc_html($shelf_name); ?></button>
					<?php endforeach; ?>
				</div>

				<div class="bbb-seriesOrders__grid" data-series-grid>
					<?php foreach ($series_cards as $card) : ?>
						<?php
						$has_guide = !empty($card['has_guide']);
						$link_tag  = $has_guide ? 'a' : 'div';
						?>
						<article class="bbb-seriesOrders__card<?php echo $has_guide ? '' : ' bbb-seriesOrders__card--locked'; ?>" data-series-card data-series-genre="<?php echo esc_attr($card['shelf_slug']); ?>" data-series-title="<?php echo esc_attr($card['title']); ?>">
							<<?php echo esc_html($link_tag); ?> class="bbb-seriesOrders__cardLink<?php echo $has_guide ? '' : ' bbb-seriesOrders__cardLink--locked'; ?>"<?php echo $has_guide ? ' href="' . esc_url($card['destination']) . '"' : ' aria-disabled="true"'; ?>>
								<div class="bbb-seriesOrders__cardTop">
									<div class="bbb-seriesOrders__coverWrap">
										<?php if ('' !== $card['first_data']['cover']) : ?>
											<img class="bbb-seriesOrders__cover" src="<?php echo esc_url($card['first_data']['cover']); ?>" alt="<?php echo esc_attr($card['first_data']['title']); ?>" loading="lazy">
										<?php endif; ?>
									</div>
									<div class="bbb-seriesOrders__meta">
										<div>
											<h3 class="bbb-seriesOrders__seriesName"><?php echo esc_html($card['title']); ?></h3>
											<p class="bbb-seriesOrders__seriesSub">
												<?php echo esc_html($card['first_data']['author']); ?><?php echo $card['book_count'] > 0 ? esc_html(' · ' . $card['book_count'] . ' ' . (1 === $card['book_count'] ? 'book' : 'books')) : ''; ?>
											</p>
										</div>
										<span class="bbb-seriesOrders__genrePill"><?php echo esc_html($card['shelf_name']); ?></span>
									</div>
								</div>

								<div class="bbb-seriesOrders__cardBody">
									<?php foreach ($card['preview_books'] as $index => $book) : ?>
										<?php $book_data = bbb_series_book_data($book); ?>
										<div class="bbb-seriesOrders__bookRow">
											<span class="bbb-seriesOrders__bookNumber"><?php echo esc_html((string) ($index + 1)); ?>.</span>
											<span class="bbb-seriesOrders__bookTitle"><?php echo esc_html($book_data['title']); ?></span>
											<?php if ((int) $book_data['spice'] > 0) : ?>
												<span class="bbb-seriesOrders__bookSpice" aria-label="<?php echo esc_attr((string) $book_data['spice']); ?> spice"><?php echo esc_html(str_repeat('🌶', (int) $book_data['spice'])); ?></span>
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
									<?php if ($card['remaining_count'] > 0) : ?>
										<div class="bbb-seriesOrders__more">+ <?php echo esc_html((string) $card['remaining_count']); ?> more</div>
									<?php endif; ?>
								</div>

								<div class="bbb-seriesOrders__footer">
									<span>
										<?php
										echo esc_html(
											$has_guide
												? ('' !== $card['standalone_title'] ? 'start with: ' . $card['standalone_title'] . ' →' : 'must read in order →')
												: 'waiting on series guide'
										);
										?>
									</span>
								</div>
							</<?php echo esc_html($link_tag); ?>>
						</article>
					<?php endforeach; ?>
				</div>
			</section>

			<p class="bbb-seriesOrders__note">cards open only when a matching guide post has SSS Series filled in · filtered by vibe · spice levels visible at a glance</p>
		<?php else : ?>
			<div class="bbb-seriesOrders__empty">No series are ready to show here yet.</div>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
