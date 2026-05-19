<?php
declare(strict_types=1);

$books = array_filter(
	$args['books'] ?? array(),
	static function ($book): bool {
		if (!$book instanceof WP_Post) {
			return false;
		}

		$data = sss_book_data($book);

		return $book instanceof WP_Post
			&& sss_book_is_visible($book->ID)
			&& 'society classics' === strtolower(trim((string) $data['shelf']));
	}
);

if (!$books) {
	return;
}
?>
<div id="society-classics" class="sss-lib__classics">
	<div class="sss-lib__classicsHead">
		<div class="sss-lib__archiveKicker">society classics</div>
		<h2 class="sss-lib__archiveTitle">the society classics shelf</h2>
		<div class="sss-lib__archiveSub">the books that keep coming up because they earned their place.</div>
	</div>
	<div class="sss-lib__grid">
		<?php foreach ($books as $book) : ?>
			<?php get_template_part('template-parts/library/book-card', null, array('post' => $book)); ?>
		<?php endforeach; ?>
	</div>
</div>
