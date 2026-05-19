<?php
/**
 * Blog article card used by book review indexes.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);
?>
<article class="article-trope-book-card bbb-article-card">
	<a href="<?php the_permalink(); ?>">
		<?php if (has_post_thumbnail()) : ?>
			<?php the_post_thumbnail('medium_large'); ?>
		<?php endif; ?>
		<h3><?php the_title(); ?></h3>
		<p><?php echo esc_html(get_the_excerpt()); ?></p>
	</a>
</article>
