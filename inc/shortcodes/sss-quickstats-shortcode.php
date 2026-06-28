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
	$post_id = (int) $atts['post_id'];
	$books = sss_article_post_books((int) $atts['post_id']);
	if (!$books) {
		$books = sss_article_books_for_post((int) $atts['post_id']);
	}
	$book = $books[max(0, (int) $atts['index'] - 1)] ?? null;
	if (!$book instanceof WP_Post) {
		return '';
	}

	$data = sss_article_book_data($book->ID);
	$post_title = get_the_title($post_id);
	$pin_image = get_the_post_thumbnail_url($post_id, 'large');
	if (!$pin_image && function_exists('sss_article_cover_url')) {
		$pin_image = sss_article_cover_url($book->ID);
	}
	if (!$pin_image && !empty($data['cover_url'])) {
		$pin_image = (string) $data['cover_url'];
	}
	$review_pin_enabled = '1' === (string) get_post_meta($post_id, '_bbb_review_pin_enabled', true);
	$review_pin_image = trim((string) get_post_meta($post_id, '_bbb_review_pin_media_url', true));
	$show_review_pin = $review_pin_enabled && '' !== $review_pin_image;
	if ($show_review_pin) {
		$pin_image = $review_pin_image;
	}
	$pin_target_url = function_exists('bbb_review_pin_link_url') ? bbb_review_pin_link_url($post_id) : get_permalink($post_id);
	$pin_title = function_exists('bbb_review_pin_title') ? bbb_review_pin_title($post_id) : $post_title;
	$pin_description = function_exists('bbb_review_pin_description') ? bbb_review_pin_description($post_id) : trim($post_title . ' - ' . (string) $data['title']);
	$pinterest_save_url = 'https://www.pinterest.com/pin/create/button/?' . http_build_query(
		array_filter(
			array(
				'url'         => $pin_target_url,
				'media'       => $pin_image ?: '',
				'description' => $pin_description,
			)
		),
		'',
		'&',
		PHP_QUERY_RFC3986
	);
	$has_series = $data['series'] instanceof WP_Post || '' !== trim((string) ($data['series_handle'] ?? '')) || '' !== trim((string) ($data['series_name'] ?? ''));
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
<div class="blog-review-pin-feature" data-review-pin-feature>
  <?php if ($show_review_pin) : ?>
  <div class="blog-review-pin-feature__action">
    <span class="blog-review-pin-feature__label">pin the review</span>
    <a
      class="blog-review-pin-feature__link"
      href="<?php echo esc_url($pinterest_save_url); ?>"
      data-pin-do="buttonPin"
      data-pin-custom="true"
      data-pin-url="<?php echo esc_url($pin_target_url); ?>"
      <?php if ($pin_image) : ?>
      data-pin-media="<?php echo esc_url($pin_image); ?>"
      <?php endif; ?>
      data-pin-title="<?php echo esc_attr($pin_title); ?>"
      data-pin-description="<?php echo esc_attr($pin_description); ?>"
      target="_blank"
      rel="noopener noreferrer"
      aria-label="<?php echo esc_attr('save ' . $pin_title . ' to Pinterest'); ?>"
    >
      <svg class="blog-review-pin-feature__icon" aria-hidden="true" viewBox="0 0 20 20" focusable="false">
        <path fill="currentColor" d="M10 2.01a8.1 8.1 0 0 1 5.666 2.353 8.09 8.09 0 0 1 1.277 9.68A7.95 7.95 0 0 1 10 18.04a8.2 8.2 0 0 1-2.276-.307c.403-.653.672-1.24.816-1.729l.567-2.2c.134.27.393.5.768.702.384.192.768.297 1.19.297q1.254 0 2.248-.72a4.7 4.7 0 0 0 1.537-1.969c.37-.89.554-1.848.537-2.813 0-1.249-.48-2.315-1.43-3.227a5.06 5.06 0 0 0-3.65-1.374c-.893 0-1.729.154-2.478.461a5.02 5.02 0 0 0-3.236 4.552c0 .72.134 1.355.413 1.902.269.538.672.922 1.22 1.152.096.039.182.039.25 0 .066-.028.114-.096.143-.192l.173-.653c.048-.144.02-.288-.105-.432a2.26 2.26 0 0 1-.548-1.565 3.803 3.803 0 0 1 3.976-3.861c1.047 0 1.863.288 2.44.855.585.576.883 1.315.883 2.228a6.8 6.8 0 0 1-.317 2.122 3.8 3.8 0 0 1-.893 1.556c-.384.384-.836.576-1.345.576-.413 0-.749-.144-1.018-.451-.259-.307-.345-.672-.25-1.085q.22-.77.452-1.537l.173-.701c.057-.25.086-.451.086-.624 0-.346-.096-.634-.269-.855-.192-.22-.451-.336-.797-.336-.432 0-.797.192-1.085.595-.288.394-.442.893-.442 1.499.005.374.063.746.173 1.104l.058.144c-.576 2.478-.913 3.938-1.037 4.36-.116.528-.154 1.153-.125 1.863A8.07 8.07 0 0 1 2 10.03c0-2.208.778-4.11 2.343-5.666A7.72 7.72 0 0 1 10 2.001z" />
      </svg>
    </a>
  </div>
  <?php endif; ?>
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

    <div class="blog-quickstats__row">
      <dt>standalone or series</dt>
      <dd><?php echo esc_html($series_label); ?></dd>
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
</div>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_quickstats', 'sss_quickstats_shortcode');
add_shortcode('quickstats', 'sss_quickstats_shortcode');
