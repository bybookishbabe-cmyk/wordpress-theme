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
		$books = array_filter(
			sss_article_all_visible_books(),
			static fn(WP_Post $book): bool => ($s = sss_article_post(sss_article_field('series', $book->ID, null))) && (int) $s->ID === (int) $series->ID
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
	$book = $books[max(0, (int) $atts['index'] - 1)] ?? null;
	if (!$book instanceof WP_Post) {
		return '';
	}

	$data = sss_article_book_data($book->ID);
	$series_books = $data['series'] instanceof WP_Post ? sss_quickstats_series_books($data['series']) : array();
	$first_book = $series_books[0] ?? null;
	$trope_names = wp_list_pluck($data['tropes'], 'name');
	$is_standalone = !$data['series'] || $data['standalone'];

	ob_start();
	?>
<aside class="blog-quickstats" aria-label="quick stats for <?php echo esc_attr($data['title']); ?>">
  <div class="blog-quickstats__head">
    <h3 class="blog-quickstats__kicker"><?php echo esc_html(strtolower($data['title'])); ?> book stats</h3>
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
      <dd><?php echo esc_html($is_standalone ? 'standalone' : $data['series_name'] . ' series'); ?></dd>
    </div>

    <?php if ($series_books) : ?>
    <div class="blog-quickstats__row">
      <dt>books in series</dt>
      <dd><?php echo esc_html(count($series_books) . ' books'); ?></dd>
    </div>
    <?php endif; ?>

    <?php if (!$is_standalone && $first_book instanceof WP_Post) : ?>
    <div class="blog-quickstats__row">
      <dt>start with</dt>
      <dd><?php echo esc_html(get_the_title($first_book)); ?></dd>
    </div>
    <?php endif; ?>

    <div class="blog-quickstats__row">
      <dt>read in order?</dt>
      <dd><?php echo esc_html($is_standalone ? 'no, can be read as a standalone' : ($first_book ? 'yes, start with ' . get_the_title($first_book) : 'yes, read in series order')); ?></dd>
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
