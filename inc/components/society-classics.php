<?php
declare(strict_types=1);

$query = $args['query'] ?? new WP_Query(
	array(
		'post_type'      => 'sss_book',
		'posts_per_page' => 8,
		'tax_query'      => array(
			array(
				'taxonomy' => 'sss_shelf',
				'field'    => 'slug',
				'terms'    => 'society-classics',
			),
		),
	)
);
?>
<section class="sss-lib__section" id="society-classics">
	<p class="sss-lib__kicker">society classics</p>
	<h2 class="sss-lib__sectionTitle">the society's classics shelf</h2>
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
