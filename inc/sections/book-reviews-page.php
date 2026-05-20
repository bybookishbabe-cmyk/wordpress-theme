<?php
/**
 * Book reviews index section.
 *
 * Mirrors sections/book-reviews-page.liquid.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_review_index_bool')) {
	function bbb_review_index_bool($value): bool {
		return function_exists('bbb_truthy')
			? bbb_truthy($value)
			: in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
	}
}

if (!function_exists('bbb_review_index_field')) {
	function bbb_review_index_field(int $post_id, array $keys, $default = '') {
		foreach ($keys as $key) {
			$value = function_exists('get_field') ? get_field($key, $post_id) : null;
			if (null !== $value && '' !== $value && false !== $value) {
				return $value;
			}

			$value = get_post_meta($post_id, $key, true);
			if ('' !== $value && null !== $value && false !== $value) {
				return $value;
			}

			$value = get_post_meta($post_id, '_' . $key, true);
			if ('' !== $value && null !== $value && false !== $value) {
				return $value;
			}
		}

		return $default;
	}
}

if (!function_exists('bbb_review_index_posts')) {
	function bbb_review_index_posts(string $blog_handle): array {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 250,
				'category_name'  => $blog_handle,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		if (!$posts) {
			$posts = get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => 250,
					'meta_key'       => '_shopify_blog_handle',
					'meta_value'     => $blog_handle,
					'orderby'        => 'date',
					'order'          => 'DESC',
				)
			);
		}

		return $posts;
	}
}

if (!function_exists('bbb_review_index_article_books')) {
	function bbb_review_index_article_books(int $post_id): array {
		if (function_exists('sss_article_post_books')) {
			$books = sss_article_post_books($post_id);
			if ($books) {
				return $books;
			}
		}

		$book_ids = get_post_meta($post_id, '_bbb_article_books', true);
		if (is_array($book_ids)) {
			return array_values(array_filter(array_map('get_post', array_map('absint', $book_ids))));
		}

		$books = array();
		for ($index = 1; $index <= 24; $index++) {
			$book_id = (int) get_post_meta($post_id, '_bbb_article_book_' . $index, true);
			if ($book_id > 0) {
				$book = get_post($book_id);
				if ($book instanceof WP_Post) {
					$books[] = $book;
				}
			}
		}

		if ($books) {
			return $books;
		}

		$haystack = strtolower(wp_strip_all_tags(get_the_title($post_id) . ' ' . get_post_field('post_content', $post_id)));
		if (!$haystack) {
			return array();
		}

		$all_books = get_posts(
			array(
				'post_type'      => array('bbb_book', 'sss_book'),
				'post_status'    => 'publish',
				'posts_per_page' => 250,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ($all_books as $book) {
			$title = strtolower(get_the_title($book));
			if ($title && str_contains($haystack, $title)) {
				return array($book);
			}
		}

		return array();
	}
}

if (!function_exists('bbb_review_index_has_review_flag')) {
	function bbb_review_index_has_review_flag(int $post_id): bool {
		$value = bbb_review_index_field(
			$post_id,
			array('book_review', '_book_review', '_bbb_book_review', 'review', '_review', '_bbb_review'),
			null
		);

		if (null !== $value && '' !== $value && bbb_review_index_bool($value)) {
			return true;
		}

		$review_terms = get_the_terms($post_id, 'book_review_category');
		if ($review_terms && !is_wp_error($review_terms)) {
			return true;
		}

		$meta = get_post_meta($post_id);
		foreach ($meta as $key => $values) {
			$normalized_key = strtolower(trim((string) $key, '_'));
			if (!in_array($normalized_key, array('book_review', 'custom_book_review', 'custom.book_review'), true)) {
				continue;
			}

			foreach ((array) $values as $meta_value) {
				if (bbb_review_index_bool(maybe_unserialize($meta_value))) {
					return true;
				}
			}
		}

		$slug_and_title = strtolower((string) get_post_field('post_name', $post_id) . ' ' . get_the_title($post_id));
		return str_contains($slug_and_title, 'review');
	}
}

if (!function_exists('bbb_review_index_card_image')) {
	function bbb_review_index_card_image(WP_Post $post, ?WP_Post $book): array {
		$url = get_the_post_thumbnail_url($post, 'large') ?: '';
		$alt = get_the_title($post);

		if (!$url) {
			$url = (string) get_post_meta($post->ID, '_thumbnail_external_url', true);
			$alt = (string) get_post_meta($post->ID, '_thumbnail_external_alt', true) ?: $alt;
		}

		if (!$url && $book instanceof WP_Post) {
			$url = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($book->ID) : '';
			$alt = get_the_title($book);
		}

		return array($url, $alt);
	}
}

if (!function_exists('bbb_review_index_review_meta')) {
	function bbb_review_index_review_meta(int $post_id, ?WP_Post $book): array {
		$rating = (string) bbb_review_index_field($post_id, array('review_rating', 'rating', '_bbb_review_rating'), '');
		$label  = (string) bbb_review_index_field($post_id, array('review_label', 'verdict', 'review_verdict', '_bbb_review_label'), '');

		if (!$rating && $book instanceof WP_Post) {
			$spice = 'bbb_book' === $book->post_type
				? (string) get_post_meta($book->ID, '_bbb_spice', true)
				: (string) bbb_review_index_field($book->ID, array('spice_level'), '');
			if ($spice !== '') {
				$rating = str_repeat('🌶', max(1, min(5, (int) $spice)));
			}
		}

		if (!$label && $book instanceof WP_Post) {
			$label = (string) bbb_review_index_field($book->ID, array('reread_badge', '_bbb_reread'), '');
		}

		return array($rating, $label);
	}
}

if (!function_exists('bbb_render_review_index_card')) {
	function bbb_render_review_index_card(WP_Post $post, bool $is_trending = false, string $trending_label = 'trending now'): string {
		$books  = bbb_review_index_article_books($post->ID);
		$book   = $books[0] ?? null;
		$author = $book instanceof WP_Post
			? (function_exists('bbb_get_book_author') ? bbb_get_book_author($book->ID) : (string) bbb_review_index_field($book->ID, array('author'), ''))
			: '';
		$image  = bbb_review_index_card_image($post, $book instanceof WP_Post ? $book : null);
		$meta   = bbb_review_index_review_meta($post->ID, $book instanceof WP_Post ? $book : null);
		$excerpt = (string) bbb_review_index_field($post->ID, array('review_excerpt', 'review_summary', 'excerpt', '_bbb_review_excerpt'), '');
		if (!$excerpt) {
			$excerpt = get_the_excerpt($post);
		}
		$classes = 'bbb-review-card' . ($is_trending ? ' bbb-review-card--trending' : '');
		$loading = $is_trending ? 'eager' : 'lazy';

		ob_start();
		?>
<a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url(get_permalink($post)); ?>">
  <div class="bbb-review-card__media">
    <?php if ($image[0]) : ?>
    <img class="bbb-review-card__blogImage" src="<?php echo esc_url($image[0]); ?>" alt="<?php echo esc_attr($image[1]); ?>" loading="<?php echo esc_attr($loading); ?>">
    <?php else : ?>
    <div class="bbb-review-card__placeholder" aria-hidden="true">
      <span><?php echo esc_html(substr(get_the_title($post), 0, 1)); ?></span>
    </div>
    <?php endif; ?>
  </div>

  <div class="bbb-review-card__body">
    <div class="bbb-review-card__meta">
      <span class="bbb-review-card__badge"><?php echo esc_html($is_trending ? $trending_label : 'reviewed'); ?></span>
      <time datetime="<?php echo esc_attr(get_the_date('Y-m-d', $post)); ?>"><?php echo esc_html(get_the_date('M j, Y', $post)); ?></time>
    </div>

    <h2 class="bbb-review-card__title"><?php echo esc_html(get_the_title($post)); ?></h2>

    <?php if ($book instanceof WP_Post || $author) : ?>
    <p class="bbb-review-card__book">
      <?php if ($book instanceof WP_Post) : ?><span><?php echo esc_html(get_the_title($book)); ?></span><?php endif; ?>
      <?php if ($author) : ?><span>by <?php echo esc_html($author); ?></span><?php endif; ?>
    </p>
    <?php endif; ?>

    <?php if ($meta[0] || $meta[1]) : ?>
    <p class="bbb-review-card__reviewMeta">
      <?php if ($meta[0]) : ?><span><?php echo esc_html($meta[0]); ?></span><?php endif; ?>
      <?php if ($meta[1]) : ?><span><?php echo esc_html($meta[1]); ?></span><?php endif; ?>
    </p>
    <?php endif; ?>

    <?php if ($excerpt) : ?>
    <p class="bbb-review-card__excerpt"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($excerpt), 24)); ?></p>
    <?php endif; ?>
  </div>
</a>
		<?php
		return ob_get_clean();
	}
}

$page_id           = get_the_ID();
$blog_handle       = sanitize_title((string) bbb_get_field('blog', $page_id, 'curated-romance-guides'));
$page_size         = max(1, (int) bbb_get_field('articles_per_page', $page_id, 20));
$posts             = bbb_review_index_posts($blog_handle ?: 'curated-romance-guides');
$review_posts      = array_values(array_filter($posts, static fn(WP_Post $post): bool => bbb_review_index_has_review_flag($post->ID)));
$trending_post     = $review_posts[0] ?? null;
$archive_posts     = $trending_post ? array_values(array_filter($review_posts, static fn(WP_Post $post): bool => (int) $post->ID !== (int) $trending_post->ID)) : array();
$review_section_id = 'BookReviews-' . (int) $page_id;
?>
<section class="bbb-review-index" id="<?php echo esc_attr($review_section_id); ?>" data-review-index data-review-page-size="<?php echo esc_attr((string) $page_size); ?>">
  <div class="bbb-review-index__wrap page-width">
    <header class="bbb-review-index__hero">
      <p class="bbb-review-index__kicker"><?php echo esc_html((string) bbb_get_field('kicker', $page_id, 'bybookishbabe book reviews')); ?></p>
      <h1 class="bbb-review-index__title"><?php echo esc_html((string) bbb_get_field('heading', $page_id, 'book reviews')); ?></h1>
      <?php $subtext = (string) bbb_get_field('subtext', $page_id, "every book on this page has been read, rated, and actually recommended by bybookishbabe.\n\nspice levels, darkness ratings, tropes, and the honest take - no filler, no algorithm, no books i haven't personally finished.\n\nif it's here, it earned its place."); ?>
      <?php if ($subtext) : ?>
      <p class="bbb-review-index__sub"><?php echo nl2br(esc_html($subtext)); ?></p>
      <?php endif; ?>
    </header>

    <?php if ($trending_post instanceof WP_Post) : ?>
    <section class="bbb-review-index__trending" aria-labelledby="BookReviewsTrending-<?php echo esc_attr((string) $page_id); ?>" data-review-trending>
      <div class="bbb-review-index__sectionHead">
        <p class="bbb-review-index__sectionKicker"><?php echo esc_html((string) bbb_get_field('trending_kicker', $page_id, 'latest book review')); ?></p>
        <h2 class="bbb-review-index__sectionTitle" id="BookReviewsTrending-<?php echo esc_attr((string) $page_id); ?>"><?php echo esc_html((string) bbb_get_field('trending_heading', $page_id, 'trending now')); ?></h2>
      </div>
      <?php echo bbb_render_review_index_card($trending_post, true, (string) bbb_get_field('trending_label', $page_id, 'trending now')); ?>
    </section>

    <section class="bbb-review-index__archive" aria-labelledby="BookReviewsArchive-<?php echo esc_attr((string) $page_id); ?>">
      <div class="bbb-review-index__sectionHead bbb-review-index__sectionHead--archive">
        <p class="bbb-review-index__sectionKicker"><?php echo esc_html((string) bbb_get_field('archive_kicker', $page_id, 'read, rated, recommended')); ?></p>
        <h2 class="bbb-review-index__sectionTitle" id="BookReviewsArchive-<?php echo esc_attr((string) $page_id); ?>"><?php echo esc_html((string) bbb_get_field('archive_heading', $page_id, 'all book reviews')); ?></h2>
      </div>

      <div class="bbb-review-index__grid" data-review-grid>
        <?php foreach ($archive_posts as $index => $post) : ?>
        <div class="bbb-review-index__item" data-review-card <?php echo ($index + 1) > $page_size ? 'hidden' : ''; ?>>
          <?php echo bbb_render_review_index_card($post); ?>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if (count($archive_posts) > $page_size) : ?>
      <nav class="bbb-review-index__pager" aria-label="book review pages" data-review-pager>
        <button type="button" class="bbb-review-index__pagerButton" data-review-prev disabled>previous</button>
        <span class="bbb-review-index__pagerStatus" data-review-status>page 1</span>
        <button type="button" class="bbb-review-index__pagerButton" data-review-next>next</button>
      </nav>
      <?php elseif (count($archive_posts) === 0) : ?>
      <div class="bbb-review-index__empty"><?php echo esc_html((string) bbb_get_field('single_empty_text', $page_id, 'Only one reviewed guide is live right now. Set book_review to true on another blog post and it will appear here.')); ?></div>
      <?php endif; ?>
    </section>
    <?php else : ?>
    <div class="bbb-review-index__empty"><?php echo esc_html((string) bbb_get_field('empty_text', $page_id, 'No reviewed guides are showing yet. Set book_review to true on the blog posts you want here.')); ?></div>
    <?php endif; ?>
  </div>
</section>

<script>
  (function(){
    var root = document.getElementById(<?php echo wp_json_encode($review_section_id); ?>);
    if (!root) return;

    var cards = Array.prototype.slice.call(root.querySelectorAll('[data-review-card]'));
    var pager = root.querySelector('[data-review-pager]');
    var prev = root.querySelector('[data-review-prev]');
    var next = root.querySelector('[data-review-next]');
    var status = root.querySelector('[data-review-status]');
    var trending = root.querySelector('[data-review-trending]');
    var pageSize = parseInt(root.getAttribute('data-review-page-size'), 10) || 20;
    var currentPage = 1;
    var totalPages = Math.max(1, Math.ceil(cards.length / pageSize));

    function renderPage(page){
      currentPage = Math.min(Math.max(page, 1), totalPages);
      var start = (currentPage - 1) * pageSize;
      var end = start + pageSize;

      cards.forEach(function(card, index){
        card.hidden = index < start || index >= end;
      });

      if (trending) {
        trending.hidden = currentPage !== 1;
      }

      if (prev) prev.disabled = currentPage === 1;
      if (next) next.disabled = currentPage === totalPages;
      if (status) status.textContent = 'page ' + currentPage + ' of ' + totalPages;

      if (page !== 1) {
        root.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    if (!pager || totalPages <= 1) return;

    prev.addEventListener('click', function(){
      renderPage(currentPage - 1);
    });

    next.addEventListener('click', function(){
      renderPage(currentPage + 1);
    });

    renderPage(1);
  })();
</script>
