<?php
declare(strict_types=1);

$books = function_exists('bbb_books_like_all_visible_books')
	? bbb_books_like_all_visible_books()
	: get_posts(
		array(
			'post_type'      => array('sss_book', 'bbb_book'),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

$spice_books  = array();
$spice_counts = array_fill(1, 5, 0);
foreach ($books as $book) {
	if (!$book instanceof WP_Post) {
		continue;
	}
	if (function_exists('bbb_book_is_private') && bbb_book_is_private($book->ID)) {
		continue;
	}
	if (function_exists('bbb_book_is_hidden') && bbb_book_is_hidden($book->ID)) {
		continue;
	}

	$data  = function_exists('sss_article_book_data') ? sss_article_book_data($book->ID) : array('spice' => (int) get_post_meta($book->ID, '_bbb_spice', true));
	$spice = max(0, min(5, (int) ($data['spice'] ?? 0)));
	if ($spice < 1) {
		continue;
	}

	$spice_books[] = $book;
	$spice_counts[$spice]++;
}
?>
<section class="sss-lib sss-lib--spicePage" data-sss-lib="public">
	<div class="sss-lib__wrap">
		<header class="sss-tropeTop">
			<div class="sss-tropeTop__left">
				<p class="sss-lib__kicker">browse by spice</p>
				<h1 class="sss-lib__title">romance books by spice level</h1>
			</div>
			<div class="sss-tropeTop__right">
				<div class="sss-lib__societyInviteCard">
					<div class="sss-lib__societyInviteKicker">the private layer</div>
					<div class="sss-lib__societyInviteTitle">join the society for the weekly recommendation</div>
					<a href="https://thesmutandsentimentsociety.substack.com/subscribe" class="sss-lib__societyInviteBtn">enter the society</a>
				</div>
			</div>
		</header>
		<nav class="sss-spiceNav">
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="1">🌶 barely there <span><?php echo esc_html((string) $spice_counts[1]); ?></span></button>
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="2">🌶🌶 warming up <span><?php echo esc_html((string) $spice_counts[2]); ?></span></button>
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="3">🌶🌶🌶 medium heat <span><?php echo esc_html((string) $spice_counts[3]); ?></span></button>
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="4">🌶🌶🌶🌶 getting hot <span><?php echo esc_html((string) $spice_counts[4]); ?></span></button>
			<button class="sss-spiceNav__pill" type="button" data-spice-filter="5">🌶🌶🌶🌶🌶 five chili nights <span><?php echo esc_html((string) $spice_counts[5]); ?></span></button>
		</nav>
		<p class="sss-lib__spiceCount">showing <span id="sssSpiceCount">0</span> books</p>
		<div class="sss-lib__grid sss-lib__grid--spicePage" id="sssSpiceGrid">
			<?php
			foreach ($spice_books as $book) {
				if ('bbb_book' === $book->post_type && function_exists('bbb_render_library_book_card')) {
					echo bbb_render_library_book_card($book->ID); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					bbb_render_component('sss-book-card', array('book' => $book));
				}
			}
			?>
		</div>
		<div class="sss-lib__spiceActions">
			<a href="<?php echo esc_url(bbb_resolve_page_url('library')); ?>">← back to full library</a>
			<a href="https://thesmutandsentimentsociety.substack.com/subscribe">join the society →</a>
		</div>
	</div>
	<?php bbb_render_component('library-modal'); ?>
</section>
