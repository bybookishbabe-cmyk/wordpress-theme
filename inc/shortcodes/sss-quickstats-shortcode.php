<?php
/**
 * Quick stats shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_quickstats_series_books(WP_Post $series): array {
	$books = sss_article_posts(sss_article_field('sss_books', $series->ID, array()));
	if (!$books) {
		$books = sss_article_posts(sss_article_field('books_in_series', $series->ID, array()));
	}
	if (!$books) {
		$book_ids = preg_split('/[\s,]+/', (string) get_post_meta($series->ID, '_bbb_series_book_ids', true)) ?: array();
		$books    = array_values(
			array_filter(
				array_map(
					static function (string $book_id): ?WP_Post {
						$post = get_post(absint($book_id));
						return $post instanceof WP_Post ? $post : null;
					},
					$book_ids
				)
			)
		);
	}
	if (!$books) {
		$handles = preg_split('/[\s,]+/', (string) get_post_meta($series->ID, '_bbb_series_book_handles', true)) ?: array();
		foreach ($handles as $handle) {
			$book = sss_article_book_from_slug($handle);
			if ($book instanceof WP_Post) {
				$books[$book->ID] = $book;
			}
		}
		$books = array_values($books);
	}
	if (!$books) {
		$series_handle = (string) get_post_meta($series->ID, '_bbb_series_handle', true);
		if ('' === $series_handle) {
			$series_handle = $series->post_name;
		}
		$books = array_filter(
			sss_article_all_visible_books(),
			static function (WP_Post $book) use ($series, $series_handle): bool {
				$linked_series = sss_article_post(sss_article_field('series', $book->ID, null));
				if ($linked_series instanceof WP_Post && (int) $linked_series->ID === (int) $series->ID) {
					return true;
				}

				return 'bbb_book' === $book->post_type && '' !== $series_handle && (string) get_post_meta($book->ID, '_bbb_series_handle', true) === $series_handle;
			}
		);
	}

	usort(
		$books,
		static fn(WP_Post $a, WP_Post $b): int => ((int) sss_article_field('series_number', $a->ID, 999)) <=> ((int) sss_article_field('series_number', $b->ID, 999))
	);

	return array_values($books);
}

function sss_quickstats_shortcode($atts): string {
	$atts = shortcode_atts(array('index' => 1, 'post_id' => get_the_ID()), $atts, 'sss_quickstats');
	$books = sss_article_post_books((int) $atts['post_id']);
	if (!$books) {
		$books = sss_article_books_for_post((int) $atts['post_id']);
	}
	$book = $books[max(0, (int) $atts['index'] - 1)] ?? null;
	if (!$book instanceof WP_Post) {
		return '';
	}

	$data = sss_article_book_data($book->ID);
	$series_books = $data['series'] instanceof WP_Post ? sss_quickstats_series_books($data['series']) : array();
	$first_book = $series_books[0] ?? null;
	$trope_names = wp_list_pluck($data['tropes'], 'name');
	$has_series = $data['series'] instanceof WP_Post || '' !== trim((string) ($data['series_handle'] ?? '')) || '' !== trim((string) ($data['series_name'] ?? ''));
	$can_read_standalone = (bool) $data['standalone'];
	$series_label = 'standalone';
	if ($has_series) {
		$series_label = trim((string) ($data['series_name'] ?? ''));
		if ('' === $series_label) {
			$series_label = trim((string) ($data['series_handle'] ?? ''));
			if ('' !== $series_label) {
				$series_label = ucwords(str_replace(array('-', '_'), ' ', $series_label));
			}
		}
		if ('' === $series_label) {
			$series_label = 'series';
		} elseif (!preg_match('/\b(?:series|duet|trilogy|saga)\b$/i', $series_label)) {
			$series_label .= ' series';
		}
	}

	ob_start();
	?>
<aside class="blog-quickstats" aria-label="quick stats for <?php echo esc_attr($data['title']); ?>">
  <div class="blog-quickstats__head">
    <h3 class="blog-quickstats__kicker"><?php echo esc_html(function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title((string) $data['title']) : (string) $data['title']); ?> book stats</h3>
  </div>
  <dl class="blog-quickstats__list">

    <?php if ($data['spice'] > 0) : ?>
    <div class="blog-quickstats__row blog-quickstats__row--mobile-wide">
      <dt>spice</dt>
      <dd><span aria-label="<?php echo esc_attr((string) $data['spice']); ?> out of 5 spice"><?php echo esc_html(str_repeat('🌶', $data['spice'])); ?></span><span class="blog-quickstats__scale">/ 5</span></dd>
    </div>
    <?php endif; ?>

    <?php if ($data['darkness'] > 0) : ?>
    <div class="blog-quickstats__row blog-quickstats__row--mobile-wide">
      <dt>darkness</dt>
      <dd><span aria-label="<?php echo esc_attr((string) $data['darkness']); ?> out of 5 darkness"><?php echo esc_html(str_repeat('💀', $data['darkness'])); ?></span><span class="blog-quickstats__scale">/ 5</span></dd>
    </div>
    <?php endif; ?>

    <?php if ($trope_names) : ?>
    <div class="blog-quickstats__row blog-quickstats__row--wide">
      <dt>tropes</dt>
      <dd><?php echo wp_kses_post(implode('<span class="blog-quickstats__dot">·</span>', array_map('esc_html', $trope_names))); ?></dd>
    </div>
    <?php endif; ?>

    <div class="blog-quickstats__row">
      <dt>standalone or series</dt>
      <dd><?php echo esc_html($series_label); ?></dd>
    </div>

    <?php if ($series_books) : ?>
    <div class="blog-quickstats__row">
      <dt>books in series</dt>
      <dd><?php echo esc_html(count($series_books) . ' ' . (1 === count($series_books) ? 'book' : 'books')); ?></dd>
    </div>
    <?php endif; ?>

    <?php if ($has_series && !$can_read_standalone && $first_book instanceof WP_Post) : ?>
    <div class="blog-quickstats__row">
      <dt>start with</dt>
      <dd><?php echo esc_html(get_the_title($first_book)); ?></dd>
    </div>
    <?php endif; ?>

    <div class="blog-quickstats__row">
      <dt>read in order?</dt>
      <dd><?php echo esc_html($can_read_standalone || !$has_series ? 'no, can be read as a standalone' : ($first_book ? 'yes, start with ' . get_the_title($first_book) : 'yes, read in series order')); ?></dd>
    </div>

    <div class="blog-quickstats__row">
      <dt>on kindle unlimited</dt>
      <dd>
        <?php if ($data['ku']) : ?>
        <span class="blog-quickstats__availability blog-quickstats__availability--yes" aria-label="yes">✓ yes</span>
        <?php else : ?>
        <span class="blog-quickstats__availability blog-quickstats__availability--no" aria-label="no">× no</span>
        <?php endif; ?>
      </dd>
    </div>

  </dl>
</aside>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_quickstats', 'sss_quickstats_shortcode');
add_shortcode('quickstats', 'sss_quickstats_shortcode');
