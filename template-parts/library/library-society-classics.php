<?php
declare(strict_types=1);

$books = array_filter(
	$args['books'] ?? array(),
	static function ($book): bool {
		if (!$book instanceof WP_Post) {
			return false;
		}

		$data = sss_book_data($book);

		return sss_book_is_visible($book->ID)
			&& (
				(function_exists('sss_book_is_top_shelf') && sss_book_is_top_shelf($book->ID))
				|| 'society classics' === strtolower(trim((string) $data['shelf']))
			);
	}
);

if (!$books) {
	return;
}
?>
<div id="society-classics" class="sss-lib__shelf sss-lib__shelf--classics">
	<div class="sss-lib__shelfHead">
		<div class="sss-lib__shelfTitle">💌 society classics</div>
		<div class="sss-lib__shelfDesc">(the permanent canon of the smut &amp; sentiment society.)</div>
	</div>
	<div class="sss-lib__shelfRow">
		<?php foreach ($books as $book) : ?>
			<?php get_template_part('template-parts/library/book-card', null, array('post' => $book, 'mini' => true)); ?>
		<?php endforeach; ?>
	</div>
</div>
