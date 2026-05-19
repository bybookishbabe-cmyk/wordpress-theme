<?php
declare(strict_types=1);

$query = $args['query'] ?? new WP_Query(
	array(
		'post_type'      => array_values(
			array_filter(
				array('sss_book', 'bbb_book'),
				static fn(string $post_type): bool => post_type_exists($post_type)
			)
		) ?: 'sss_book',
		'posts_per_page' => 8,
		'meta_query'     => array(
			array(
				'relation' => 'OR',
				array(
					'key'     => 'top_shelf',
					'value'   => '1',
					'compare' => '=',
				),
				array(
					'key'     => 'top_shelf',
					'value'   => 'true',
					'compare' => '=',
				),
				array(
					'key'     => '_bbb_top_shelf',
					'value'   => '1',
					'compare' => '=',
				),
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
