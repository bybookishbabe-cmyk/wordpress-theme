<?php
declare(strict_types=1);

$terms = get_terms(array('taxonomy' => 'sss_shelf', 'hide_empty' => true, 'number' => 8));
?>
<section class="sss-lib__section" id="moods">
	<p class="sss-lib__kicker">trope shelves</p>
	<h2 class="sss-lib__sectionTitle">browse by mood</h2>
	<div class="sss-lib__jumpLinks">
		<?php if ($terms && !is_wp_error($terms)) : ?>
			<?php foreach ($terms as $term) : ?>
				<a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</section>
