<?php
declare(strict_types=1);

$query = $args['query'] ?? bbb_get_public_books_query();
?>
<section class="sss-lib__section" id="archive">
	<p class="sss-lib__kicker">full archive</p>
	<h2 class="sss-lib__sectionTitle">the full library</h2>
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
