<?php
declare(strict_types=1);

$query = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => (int) bbb_get_field('articles_per_page', get_the_ID(), 20),
		'category_name'  => 'curated-romance-guides',
	)
);
?>
<section class="bbb-book-reviews page-width">
	<header class="bbb-book-reviews__head">
		<p class="bbb-page-kicker"><?php echo esc_html((string) bbb_get_field('kicker', get_the_ID(), 'bybookishbabe book reviews')); ?></p>
		<h1><?php echo esc_html((string) bbb_get_field('heading', get_the_ID(), 'book reviews')); ?></h1>
	</header>
	<div class="bbb-book-reviews__grid">
		<?php if ($query->have_posts()) : ?>
			<?php while ($query->have_posts()) : $query->the_post(); ?>
				<article class="bbb-article-card">
					<a href="<?php the_permalink(); ?>">
						<?php if (has_post_thumbnail()) : ?>
							<?php the_post_thumbnail('medium_large'); ?>
						<?php endif; ?>
						<h2><?php the_title(); ?></h2>
						<p><?php echo esc_html(get_the_excerpt()); ?></p>
					</a>
				</article>
			<?php endwhile; wp_reset_postdata(); ?>
		<?php else : ?>
			<p><?php esc_html_e('No book reviews are published yet.', 'bybookishbabe-shopify-port'); ?></p>
		<?php endif; ?>
	</div>
</section>
