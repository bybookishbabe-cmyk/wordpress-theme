<?php
declare(strict_types=1);

$books = array_values(array_filter($args['books'] ?? array(), static fn($book): bool => $book instanceof WP_Post));
?>
<div id="sssTrendingShelf" class="sss-lib__shelf">
	<div class="sss-lib__shelfHead">
		<div class="sss-lib__shelfTitle">🔥 trending in the society</div>
		<div class="sss-lib__shelfDesc">the books the society is obsessing over right now</div>
		<p class="sss-lib__shelfMeta">weekly saves first; all-time fills in while the week warms up</p>
	</div>
	<div class="sss-lib__shelfRow" id="sssTrendingRow">
		<?php foreach (array_slice($books, 0, 5) as $book) : ?>
			<?php get_template_part('template-parts/library/book-card', null, array('post' => $book, 'mini' => true)); ?>
		<?php endforeach; ?>
	</div>
	<div id="sssTrendingSourcePool" hidden>
		<?php foreach ($books as $book) : ?>
			<?php get_template_part('template-parts/library/book-card', null, array('post' => $book, 'mini' => true)); ?>
		<?php endforeach; ?>
	</div>
</div>
