<?php
/**
 * Series reading order shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_series_books(WP_Post $series): array {
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
		$books = array_values(
			array_filter(
				sss_article_all_visible_books(),
				static function (WP_Post $book) use ($series, $series_handle): bool {
					$book_series = sss_article_post(sss_article_field('series', $book->ID, null));
					if ($book_series && (int) $book_series->ID === (int) $series->ID) {
						return true;
					}

					return '' !== $series_handle && (string) get_post_meta($book->ID, '_bbb_series_handle', true) === $series_handle;
				}
			)
		);
	}

	usort(
		$books,
		static function (WP_Post $a, WP_Post $b): int {
			$a_num = sss_article_field('series_number', $a->ID, '');
			$b_num = sss_article_field('series_number', $b->ID, '');
			$a_sort = '' === (string) $a_num ? 999 : (int) $a_num;
			$b_sort = '' === (string) $b_num ? 999 : (int) $b_num;
			return $a_sort <=> $b_sort;
		}
	);

	return $books;
}

function sss_series_from_blog_post(int $post_id): ?WP_Post {
	$post = get_post($post_id);
	if (!$post instanceof WP_Post) {
		return null;
	}

	$query = get_posts(
		array(
			'post_type'      => 'sss_series',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_bbb_series_linked_blog_post_id',
			'meta_value'     => (string) $post_id,
		)
	);
	if (!empty($query[0]) && $query[0] instanceof WP_Post) {
		return $query[0];
	}

	$handle = sanitize_title($post->post_name);
	if ('' === $handle) {
		return null;
	}

	$query = get_posts(
		array(
			'post_type'      => 'sss_series',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_bbb_series_linked_blog_post_handle',
			'meta_value'     => $handle,
		)
	);

	return (!empty($query[0]) && $query[0] instanceof WP_Post) ? $query[0] : null;
}

function sss_series_url(WP_Post $series): string {
	$linked_post = sss_article_post(sss_article_field('linked_blog_post', $series->ID, null));
	if ($linked_post) {
		return get_permalink($linked_post);
	}

	$handle = sanitize_title((string) sss_article_field('linked_blog_handle', $series->ID, ''));
	if ('' !== $handle) {
		$post = get_page_by_path($handle, OBJECT, 'post');
		if ($post instanceof WP_Post) {
			$permalink = get_permalink($post);
			if ($permalink) {
				return $permalink;
			}
		}
	}

	$url = (string) sss_article_field('linked_blog_url', $series->ID, '');
	return $url ?: home_url('/series/' . $series->post_name . '/');
}

function sss_series_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID()), $atts, 'sss_series');
	$post_id = (int) $atts['post_id'];
	$series = sss_article_post(sss_article_field('sss_series', $post_id, null));
	if (!$series) {
		$series = sss_article_post(sss_article_field('series', $post_id, null));
	}
	if (!$series) {
		$series = sss_series_from_blog_post($post_id);
	}
	if (!$series) {
		return '';
	}

	$books = sss_series_books($series);
	if (!$books) {
		return '';
	}
	$author = (string) sss_article_field('author', $series->ID, '');

	ob_start();
	?>
<div class="guide-bookcard guide-bookcard--series" data-guide-bookcard data-guide-title="<?php echo esc_attr(get_the_title($series)); ?>">
  <div class="guide-bookcard__head guide-bookcard__head--series">
    <div class="guide-bookcard__header">
      <span class="guide-bookcard__eyebrow">series reading order</span>
      <h3 class="guide-bookcard__seriesTitle"><?php echo esc_html(get_the_title($series)); ?></h3>
      <p class="guide-bookcard__seriesMeta"><?php echo esc_html(trim($author . ' · ' . count($books) . ' books', ' ·')); ?></p>
    </div>
    <a class="guide-bookcard__seriesLink" href="<?php echo esc_url(sss_series_url($series)); ?>">open reading guide →</a>
  </div>
  <div class="guide-bookcard__list guide-bookcard__list--series">
    <?php foreach ($books as $book) : ?>
    <div class="guide-bookcard__item guide-bookcard__item--series">
      <?php echo sss_render_article_book_card($book->ID); ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_series', 'sss_series_shortcode');
