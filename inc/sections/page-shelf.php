<?php
declare(strict_types=1);

$term = bbb_get_field('shelf_term', get_the_ID());
if (is_array($term) && isset($term['term_id'])) {
	$term = get_term((int) $term['term_id'], 'sss_shelf');
}
if (!$term instanceof WP_Term) {
	$slug = sanitize_title((string) bbb_get_field('shelf_slug', get_the_ID(), get_post_field('post_name', get_the_ID())));
	$term = get_term_by('slug', $slug, 'sss_shelf');
}
?>
<section class="sss-lib sss-lib--shelf" data-sss-lib="public">
	<div class="sss-lib__wrap">
		<header class="sss-tropeTop">
			<div class="sss-tropeTop__left">
				<p class="sss-lib__kicker"><?php echo esc_html($term instanceof WP_Term ? $term->name : get_the_title()); ?></p>
				<h1 class="sss-lib__title"><?php echo esc_html($term instanceof WP_Term ? $term->name : get_the_title()); ?></h1>
				<?php if ($term instanceof WP_Term && $term->description) : ?>
					<p class="sss-lib__sub"><?php echo esc_html($term->description); ?></p>
				<?php endif; ?>
			</div>
		</header>
		<div class="sss-lib__grid sss-lib__grid--browsePage" id="sssShelfGrid">
			<?php
			if ($term instanceof WP_Term) {
				$query = bbb_get_public_books_query(
					array(
						'tax_query' => array(
							array(
								'taxonomy' => 'sss_shelf',
								'field'    => 'term_id',
								'terms'    => $term->term_id,
							),
						),
					)
				);
				while ($query->have_posts()) {
					$query->the_post();
					bbb_render_component('sss-book-card', array('book' => get_post()));
				}
				wp_reset_postdata();
			}
			?>
		</div>
	</div>
	<div id="sssSaveToast" class="sss-lib__saveToast" hidden>saved to your shelf</div>
	<div id="sssFloatingShare"></div>
	<?php bbb_render_component('library-modal'); ?>
</section>
