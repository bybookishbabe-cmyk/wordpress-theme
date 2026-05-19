<?php
declare(strict_types=1);

$books       = array_values(array_filter($args['books'] ?? array(), static fn($book): bool => $book instanceof WP_Post));
$shelf_names = array();

foreach ($books as $post) {
	$data  = sss_book_data($post);
	$shelf = strtolower(trim((string) $data['shelf']));
	if ($shelf && 'society classics' !== $shelf && !in_array($shelf, $shelf_names, true)) {
		$shelf_names[] = $shelf;
	}
}

$shelves = array();
$sub_map = array(
	'romantasy'            => 'dragons, magic, and men who would burn kingdoms for her',
	'sports romance'       => 'locker rooms, tension, and the man who has abs',
	'dark romance'         => 'morally questionable men doing unforgivable things for obsession',
	'mafia romance'        => 'crime, arranged marriages, and dangerous devotion',
	'dystopian romance'    => 'the world is ending but somehow the yearning survives',
	'historical romance'   => 'reputations ruined by love',
	'paranormal romance'   => 'immortals, fated mates, and supernatural obsession',
	'contemporary romance' => 'modern love stories that still ruin your emotional stability',
);

foreach ($shelf_names as $shelf) {
	$shelf_books = array_values(
		array_filter(
			$books,
			static function (WP_Post $post) use ($shelf): bool {
				$data = sss_book_data($post);
				return strtolower(trim((string) $data['shelf'])) === $shelf;
			}
		)
	);

	if (count($shelf_books) <= 5) {
		continue;
	}

	$preview = array();
	foreach ($shelf_books as $post) {
		$data       = sss_book_data($post);
		$series_num = $data['series_number'];
		$standalone = (bool) $data['standalone'];
		$has_series = !empty($data['series_handle']);

		if ($has_series && !$standalone && $series_num && '1' !== (string) $series_num) {
			continue;
		}

		$preview[] = $post;
		if (count($preview) >= 8) {
			break;
		}
	}

	$shelves[] = array(
		'name'    => $shelf,
		'sub'     => $sub_map[$shelf] ?? 'romance the society cannot stop thinking about',
		'preview' => $preview,
	);
}

if (!$shelves) {
	return;
}
?>
<div id="moods" class="sss-lib__moods">
	<div class="sss-lib__moodHead">
		<div class="sss-lib__moodKicker">the society shelves</div>
		<h2 class="sss-lib__moodTitle">what are you in the mood for today?</h2>
		<div class="sss-lib__moodSub">pick your poison.</div>
		<div class="sss-lib__seriesDisclaimer">
			<span class="sss-lib__seriesBadge sss-lib__seriesBadge--demo">1</span>
			<span class="sss-lib__seriesText">the pink # badge means the book is part of a connected series.</span>
			<span class="sss-lib__seriesBadge sss-lib__seriesBadge--demo sss-lib__seriesBadge--standalone">1</span>
			<span class="sss-lib__seriesText">the white # badge means the book is part of a series but can be read as a standalone.</span>
		</div>
	</div>

	<?php foreach ($shelves as $shelf) : ?>
		<?php
		$shelf_handle = sanitize_title($shelf['name']);
		$page         = get_page_by_path($shelf_handle . '-books') ?: get_page_by_path($shelf_handle);
		$shelf_url    = $page instanceof WP_Post ? get_permalink($page) : home_url('/' . $shelf_handle . '-books/');
		?>
		<div class="sss-lib__shelf">
			<div class="sss-lib__shelfBreak">
				<a class="sss-lib__shelfBreakLink" href="<?php echo esc_url($shelf_url); ?>">
					<div class="sss-lib__shelfBreakTitle"><?php echo esc_html($shelf['name']); ?></div>
					<div class="sss-lib__shelfBreakSub"><?php echo esc_html($shelf['sub']); ?></div>
					<div class="sss-lib__shelfBreakCta">see full shelf →</div>
				</a>
			</div>
			<div class="sss-lib__shelfRow">
				<?php foreach ($shelf['preview'] as $book) : ?>
					<?php get_template_part('template-parts/library/book-card', null, array('post' => $book, 'mini' => true)); ?>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
