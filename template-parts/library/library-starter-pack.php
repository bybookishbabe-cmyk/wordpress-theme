<?php
declare(strict_types=1);

$starter_books = array_values(
	array_filter(
		$args['books'] ?? array(),
		static fn($book): bool => $book instanceof WP_Post
			&& function_exists('sss_book_is_starter_pack')
			&& sss_book_is_starter_pack($book->ID)
	)
);

if (!$starter_books) {
	return;
}
?>
<div id="starter-pack" class="sss-lib__starter">
	<div class="sss-lib__starterHead">
		<div class="sss-lib__starterKicker">starter pack</div>
		<h2 class="sss-lib__starterTitle">new to the society? start here.</h2>
		<div class="sss-lib__starterSub">
			these are the books i recommend starting with if you're new to romance…
		</div>
	</div>
	<div class="sss-lib__grid">
		<?php foreach (array_slice($starter_books, 0, 12) as $book) : ?>
			<?php get_template_part('template-parts/library/book-card', null, array('post' => $book)); ?>
		<?php endforeach; ?>
	</div>
</div>
