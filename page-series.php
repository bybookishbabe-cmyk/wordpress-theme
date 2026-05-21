<?php
/**
 * Template Name: Book Series
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

require_once get_theme_file_path('template-parts/sss-book-card.php');

if (!function_exists('bbb_series_visible_books')) {
	function bbb_series_visible_books(): array {
		$books = get_posts(
			array(
				'post_type'      => 'sss_book',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		return array_values(
			array_filter(
				$books,
				static fn(WP_Post $book): bool => function_exists('sss_book_is_visible') ? sss_book_is_visible($book->ID) : true
			)
		);
	}
}

if (!function_exists('bbb_series_terms')) {
	function bbb_series_terms(): array {
		if (taxonomy_exists('sss_series')) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'sss_series',
					'hide_empty' => false,
				)
			);

			return ($terms && !is_wp_error($terms)) ? array_values($terms) : array();
		}

		if (!post_type_exists('sss_series')) {
			return array();
		}

		return get_posts(
			array(
				'post_type'      => 'sss_series',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
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

if (!function_exists('bbb_series_term_tropes')) {
	function bbb_series_term_tropes($series): array {
		$raw    = bbb_series_entity_meta($series, 'series_tropes', array());
		$tropes = array();

		if (!is_array($raw)) {
			$raw = '' !== (string) $raw ? explode(',', (string) $raw) : array();
		}

		foreach ($raw as $item) {
			if ($item instanceof WP_Term) {
				$tropes[] = $item->name;
			} elseif (is_numeric($item)) {
				$term = get_term((int) $item, 'sss_trope');
				if ($term instanceof WP_Term) {
					$tropes[] = $term->name;
				}
			} elseif (is_array($item)) {
				$name = (string) ($item['name'] ?? $item['title'] ?? $item['sss_trope_name'] ?? '');
				if ('' !== trim($name)) {
					$tropes[] = $name;
				}
			} elseif ('' !== trim((string) $item)) {
				$tropes[] = trim((string) $item);
			}
		}

		return array_values(array_unique($tropes));
	}
}

if (!function_exists('bbb_series_book_matches')) {
	function bbb_series_book_matches(WP_Post $book, $series): bool {
		$slug = bbb_series_entity_slug($series);
		$name = bbb_series_entity_title($series);

		if ($series instanceof WP_Term && has_term($series->term_id, 'sss_series', $book)) {
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

$series_list = bbb_series_terms();
$books       = bbb_series_visible_books();
$archive     = array();

foreach ($series_list as $series) {
	$lead_book = bbb_series_lead_book($series, $books);
	if (!$lead_book) {
		continue;
	}

	$lead_data = bbb_series_book_data($lead_book);
	$shelf     = '' !== $lead_data['shelf'] ? $lead_data['shelf'] : 'series';

	$archive[$shelf][] = array(
		'series' => $series,
		'book'   => $lead_book,
		'data'   => $lead_data,
	);
}

get_header();
?>

<section class="series-container">
	<section class="series-archive" data-series-archive>
		<div class="series-header series-header--archive">
			<div class="series-subtitle">the series index</div>
			<h1>every series worth keeping together</h1>
			<div class="series-archive-intro">browse the full series archive by shelf, then open the individual series file when you’re ready to binge.</div>
		</div>

		<?php foreach ($archive as $shelf_name => $cards) : ?>
			<section class="series-archive-shelfGroup">
				<div class="series-archive-shelfKicker">shelf</div>
				<h2 class="series-archive-shelfTitle"><?php echo esc_html($shelf_name); ?></h2>
				<div class="series-archive-grid">
					<?php foreach ($cards as $card) : ?>
						<?php
						$series = $card['series'];
						$data   = $card['data'];
						$slug   = bbb_series_entity_slug($series);
						$title  = bbb_series_entity_title($series);
						$tropes = array_slice($data['tropes'] ? array_column($data['tropes'], 'name') : bbb_series_term_tropes($series), 0, 3);
						?>
						<a class="series-archive-card" href="/series/?series=<?php echo esc_attr($slug); ?>" data-series-card="<?php echo esc_attr($slug); ?>">
							<div class="series-archive-coverWrap">
								<?php if ($data['spice'] > 0) : ?>
									<div class="series-archive-spice"><?php echo esc_html(str_repeat('🌶', $data['spice'])); ?></div>
								<?php endif; ?>
								<?php if ('' !== $data['cover']) : ?>
									<img class="series-archive-cover" src="<?php echo esc_url($data['cover']); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
								<?php endif; ?>
							</div>
							<div class="series-archive-meta">
								<div class="series-archive-title"><?php echo esc_html($title); ?></div>
								<?php if ($tropes) : ?>
									<div class="series-archive-tropes">
										<?php foreach ($tropes as $trope) : ?>
											<span><?php echo esc_html($trope); ?></span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
								<div class="series-archive-cta">open series file →</div>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>
	</section>

	<div class="series-shelf-banner" id="seriesShelfBanner" hidden>
		<span>you’ve saved books to your shelf ♥</span>
		<a href="<?php echo esc_url(home_url('/library/?shelf=open')); ?>">view your list</a>
	</div>

	<?php foreach ($series_list as $series) : ?>
		<?php
		$series_slug          = bbb_series_entity_slug($series);
		$series_title         = bbb_series_entity_title($series);
		$series_display_title = false === stripos($series_title, 'series') ? $series_title . ' series' : $series_title;
		$series_tropes        = bbb_series_term_tropes($series);
		$series_books         = bbb_series_books_for_series($series, $books);
		?>
		<section class="series-page" data-series="<?php echo esc_attr($series_slug); ?>" style="display:none;">
			<div class="series-header">
				<h1><?php echo esc_html($series_display_title); ?></h1>
				<div class="series-reading-note" data-series-reading></div>
				<div class="series-subtitle">the society-approved books in this series</div>
				<?php if ($series_tropes) : ?>
					<div class="series-tropes"><?php echo esc_html(implode(', ', $series_tropes)); ?></div>
				<?php endif; ?>
				<a href="/series-reading-orders/" class="series-back" data-series-back data-default-href="/series-reading-orders/" data-default-label="← back to series reading orders">← back to series reading orders</a>
			</div>

			<div class="series-grid">
				<?php foreach ($series_books as $book) : ?>
					<?php get_template_part('template-parts/sss-book-card', null, array('book' => $book)); ?>
				<?php endforeach; ?>
			</div>

			<div class="related-series">
				<h2>more series to binge</h2>
				<div class="related-series-grid">
					<?php
					$related_count = 0;
					foreach ($series_list as $other_series) :
						if ($related_count >= 5 || bbb_series_entity_slug($other_series) === $series_slug) {
							continue;
						}

						$related_book = bbb_series_lead_book($other_series, $books, true);
						if (!$related_book) {
							continue;
						}

						$related_data = bbb_series_book_data($related_book);
						$related_slug = bbb_series_entity_slug($other_series);
						$related_count++;
						?>
						<a class="related-series-card" href="/series/?series=<?php echo esc_attr($related_slug); ?>">
							<div class="related-series-coverWrap">
								<?php if ('' !== $related_data['cover']) : ?>
									<img class="related-series-cover" src="<?php echo esc_url($related_data['cover']); ?>" alt="<?php echo esc_attr($related_data['title']); ?>">
								<?php endif; ?>
							</div>
							<div class="related-series-meta">
								<div class="related-series-title"><?php echo esc_html(bbb_series_entity_title($other_series)); ?></div>
								<?php if ($related_data['tropes']) : ?>
									<div class="related-series-trope"><?php echo esc_html(implode(', ', array_column($related_data['tropes'], 'name'))); ?></div>
								<?php endif; ?>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endforeach; ?>
</section>

<?php
get_template_part('template-parts/sss/library-modal');
get_footer();
