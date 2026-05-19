<?php
declare(strict_types=1);

$query = $args['query'] ?? bbb_get_public_books_query(array('posts_per_page' => 8, 'meta_key' => 'month_featured', 'orderby' => 'meta_value', 'order' => 'DESC'));
?>
<section class="sss-lib__section" id="monthly">
	<p class="sss-lib__kicker">books of the month</p>
	<h2 class="sss-lib__sectionTitle">monthly obsessions</h2>
	<div class="sss-lib__grid">
		<?php
		while ($query->have_posts()) {
			$query->the_post();
			bbb_render_component('sss-book-card', array('book' => get_post()));
		}
		wp_reset_postdata();
		?>
	</div>
</section>
