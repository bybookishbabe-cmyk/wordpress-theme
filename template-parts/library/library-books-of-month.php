<?php
declare(strict_types=1);

$current_month = date('Y-m');
$books         = array_values(
	array_filter(
		$args['books'] ?? array(),
		static fn($book): bool => $book instanceof WP_Post && $current_month === (string) sss_meta($book->ID, 'sss_featured_month', '')
	)
);

if (!$books) {
	return;
}
?>
<div id="monthly" class="sss-lib__topshelf">
	<div class="sss-lib__topshelfHead">
		<div class="sss-lib__topshelfKicker">books of the month</div>
	</div>
	<div class="sss-lib__topshelfRow">
		<?php foreach ($books as $book) : ?>
			<?php get_template_part('template-parts/library/book-card', null, array('post' => $book, 'mini' => true)); ?>
		<?php endforeach; ?>
	</div>
</div>
