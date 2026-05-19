<?php
declare(strict_types=1);

$books         = array_values(array_filter($args['books'] ?? array(), static fn($book): bool => $book instanceof WP_Post));
$starter_count = 0;
$shelf_counts  = array();

foreach ($books as $book) {
	$data = sss_book_data($book);
	if ($data['starter_pack']) {
		++$starter_count;
	}

	$shelf = strtolower(trim((string) $data['shelf']));
	if ($shelf && 'society classics' !== $shelf) {
		$shelf_counts[$shelf] = ($shelf_counts[$shelf] ?? 0) + 1;
	}
}

$has_mood_shelves = false;
foreach ($shelf_counts as $count) {
	if ($count > 0) {
		$has_mood_shelves = true;
		break;
	}
}
?>
<nav class="sss-lib__jumpNav">
	<div class="sss-lib__jumpTitle">choose where to begin</div>
	<div class="sss-lib__jumpLinks">
		<a href="#sssMyShelfSection">📚 your bookshelf</a>
		<a href="#society-classics">👑 classics</a>
		<a href="<?php echo esc_url(home_url('/series-reading-orders/')); ?>">🔗 series</a>
		<?php if ($starter_count > 0) : ?>
			<a href="#starter-pack">✨ start here</a>
		<?php endif; ?>
		<a href="#monthly">📅 books of the month</a>
		<?php if ($has_mood_shelves) : ?>
			<a href="#moods">🖤 trope shelves</a>
		<?php endif; ?>
		<a href="#archive">🗂️ full library</a>
	</div>
</nav>
