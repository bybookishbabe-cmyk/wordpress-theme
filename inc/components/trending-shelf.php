<?php
declare(strict_types=1);

$query = $args['query'] ?? bbb_get_public_books_query(array('posts_per_page' => 6, 'orderby' => 'date', 'order' => 'DESC'));
?>
<section class="sss-lib__section sss-lib__trending">
	<p class="sss-lib__kicker">trending shelf</p>
	<h2 class="sss-lib__sectionTitle">trending in the society</h2>
	<div class="sss-lib__grid">
		<?php
		while ($query->have_posts()) {
			$query->the_post();
			bbb_render_component('sss-book-card', array('book' => get_post()));
		}
		wp_reset_postdata();
		?>
	</div>
	<?php get_template_part('template-parts/sponsorship/trending-strip', null, array('variant' => 'kindle-unlimited')); ?>
</section>
