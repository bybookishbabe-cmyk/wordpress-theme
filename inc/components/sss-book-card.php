<?php
/**
 * SSS book card component.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$book = $args['book'] ?? null;
if ($book instanceof WP_Post) {
	$book_id = $book->ID;
} else {
	$book_id = (int) $book;
}

if (!$book_id) {
	return;
}

$title  = get_the_title($book_id);
$author = bbb_get_book_author($book_id);
$cover  = bbb_get_book_cover_url($book_id);
$spice  = (int) bbb_get_field('spice_level', $book_id, bbb_get_field('book_spice_level', $book_id, 0));
$terms  = get_the_terms($book_id, 'sss_trope');
?>
<article class="sss-lib__card" data-book-id="<?php echo esc_attr((string) $book_id); ?>" data-book-handle="<?php echo esc_attr(get_post_field('post_name', $book_id)); ?>" data-spice="<?php echo esc_attr((string) $spice); ?>">
	<button class="sss-lib__cardSurface" type="button" data-sss-book-open data-book-title="<?php echo esc_attr($title); ?>" data-book-author="<?php echo esc_attr($author); ?>" data-book-cover="<?php echo esc_url($cover); ?>">
		<?php if ($cover) : ?>
			<img class="sss-lib__cover" src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title); ?>">
		<?php endif; ?>
		<div class="sss-lib__cardBody">
			<h3 class="sss-lib__bookTitle"><?php echo esc_html($title); ?></h3>
			<?php if ($author) : ?>
				<p class="sss-lib__author"><?php echo esc_html($author); ?></p>
			<?php endif; ?>
			<?php if ($spice > 0) : ?>
				<p class="sss-lib__spice" aria-label="<?php echo esc_attr(sprintf('%d spice level', $spice)); ?>"><?php echo esc_html(str_repeat('🌶', $spice)); ?></p>
			<?php endif; ?>
			<?php if ($terms && !is_wp_error($terms)) : ?>
				<div class="sss-lib__tropeRow">
					<?php foreach (array_slice($terms, 0, 3) as $term) : ?>
						<span class="sss-lib__trope"><?php echo esc_html($term->name); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</button>
</article>
