<?php
declare(strict_types=1);

$books = array_values(array_filter($args['books'] ?? array(), static fn($book): bool => $book instanceof WP_Post));
?>
<div id="archive" class="sss-lib__archive" data-archive-section>
	<div class="sss-lib__archiveHead">
		<div class="sss-lib__archiveKicker">full library</div>
		<h2 class="sss-lib__archiveTitle">every book in the collection</h2>
	</div>
	<div class="sss-lib__grid" id="sssArchiveGrid">
		<?php foreach ($books as $book) : ?>
			<?php get_template_part('template-parts/library/book-card', null, array('post' => $book)); ?>
		<?php endforeach; ?>
	</div>
</div>
