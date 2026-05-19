<?php
/**
 * Article library strip shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_library_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID()), $atts, 'sss_library');
	$books = sss_article_all_visible_books();
	$count = count($books);
	if (0 === $count) {
		return '';
	}

	$offset = ((int) $atts['post_id']) % $count;
	$selected = array();
	for ($i = 0; $i < min(5, $count); $i++) {
		$selected[] = $books[($offset + $i) % $count];
	}
	$library_url = function_exists('bbb_resolve_page_url') ? bbb_resolve_page_url('library') : home_url('/pages/library/');

	ob_start();
	?>
<div class="sss-blog-library">
  <div class="sss-blog-library__header">
    <h3>peek inside the society library</h3>
    <a href="<?php echo esc_url($library_url); ?>" class="sss-blog-library__cta">take me to the library →</a>
  </div>
  <div class="sss-blog-library__row">
    <?php foreach ($selected as $book_post) : ?>
      <?php $book = sss_article_book_data($book_post->ID); ?>
      <div class="sss-blog-library__card" data-book-preview
        data-title="<?php echo esc_attr($book['title']); ?>" data-author="<?php echo esc_attr($book['author']); ?>" data-cover="<?php echo esc_attr($book['cover']); ?>"
        data-amazon="<?php echo esc_attr($book['amazon']); ?>" data-bookshop="<?php echo esc_attr($book['bookshop']); ?>"
        data-spice="<?php echo esc_attr((string) $book['spice']); ?>" data-tropes="<?php echo esc_attr(implode(', ', wp_list_pluck($book['tropes'], 'name'))); ?>">
        <?php if ($book['cover']) : ?>
        <img src="<?php echo esc_url($book['cover']); ?>" alt="<?php echo esc_attr($book['title']); ?>" loading="lazy">
        <?php endif; ?>
        <div class="sss-blog-library__title"><?php echo esc_html($book['title']); ?></div>
        <div class="sss-blog-library__author"><?php echo esc_html($book['author']); ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_library', 'sss_library_shortcode');
