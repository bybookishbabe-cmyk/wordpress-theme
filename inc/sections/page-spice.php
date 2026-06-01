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
$spice_levels = array(
	1 => array(
		'peppers' => '🌶',
		'label'   => 'soft',
		'title'   => 'soft spice',
		'copy'    => 'low heat, soft tension, mostly fade to black',
	),
	2 => array(
		'peppers' => '🌶🌶',
		'label'   => 'some heat',
		'title'   => 'some heat',
		'copy'    => 'a little steam, a lot of yearning, still easy to breathe',
	),
	3 => array(
		'peppers' => '🌶🌶🌶',
		'label'   => 'balanced',
		'title'   => 'balanced spice',
		'copy'    => 'clear heat, emotional payoff, romance-forward pacing',
	),
	4 => array(
		'peppers' => '🌶🌶🌶🌶',
		'label'   => 'high',
		'title'   => 'high spice',
		'copy'    => 'explicit scenes, dominant energy, tension that pays off',
	),
	5 => array(
		'peppers' => '🌶🌶🌶🌶🌶',
		'label'   => 'wreck me',
		'title'   => 'wreck me spice',
		'copy'    => 'maximum heat, high intensity, no delicate little fade out',
	),
);
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
				<p class="sss-lib__kicker">the spice archive</p>
				<h1 class="sss-lib__title">pick your spice level 🌶</h1>
				<p class="sss-lib__spiceDesc">choose how much heat you want tonight and let the library narrow itself for you.</p>
			</div>
			<div class="sss-tropeTop__right">
				<div class="sss-lib__societyInviteCard">
					<div class="sss-lib__societyInviteKicker">the private layer</div>
					<div class="sss-lib__societyInviteTitle">join the society for the weekly recommendation</div>
					<a href="https://thesmutandsentimentsociety.substack.com/subscribe" class="sss-lib__societyInviteBtn">enter the society</a>
				</div>
			</div>
		</header>
		<div class="sss-spiceDial" data-spice-dial>
			<div class="sss-spiceDial__top">
				<p class="sss-spiceDial__selected">
					<span data-spice-peppers><?php echo esc_html($spice_levels[4]['peppers']); ?></span>
					<span data-spice-title><?php echo esc_html($spice_levels[4]['title']); ?></span>
				</p>
				<p class="sss-lib__spiceCount">showing <span id="sssSpiceCount">0</span> books</p>
			</div>
			<input class="sss-spiceDial__range" type="range" min="1" max="5" step="1" value="4" aria-label="choose spice level" data-spice-range>
			<nav class="sss-spiceDial__labels" aria-label="spice levels">
				<?php foreach ($spice_levels as $level => $meta) : ?>
					<button
						class="sss-spiceDial__label"
						type="button"
						data-spice-filter="<?php echo esc_attr((string) $level); ?>"
						aria-label="<?php echo esc_attr($meta['title'] . ', ' . $spice_counts[$level] . ' books'); ?>"
					>
						<?php echo esc_html($meta['label']); ?>
					</button>
				<?php endforeach; ?>
			</nav>
			<div class="sss-spiceDial__summary" aria-live="polite">
				<div class="sss-spiceDial__summaryMain">
					<span class="sss-spiceDial__summaryPeppers" data-spice-card-peppers><?php echo esc_html($spice_levels[4]['peppers']); ?></span>
					<div>
						<h2 data-spice-card-title><?php echo esc_html($spice_levels[4]['title']); ?></h2>
						<p data-spice-card-copy><?php echo esc_html($spice_levels[4]['copy']); ?></p>
					</div>
				</div>
				<span class="sss-spiceDial__badge"><span data-spice-card-count>0</span><span>books</span></span>
			</div>
		</div>
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
			<a class="sss-lib__spiceAction sss-lib__spiceAction--ghost" href="<?php echo esc_url(bbb_resolve_page_url('library')); ?>">← back to full library</a>
			<a class="sss-lib__spiceAction" href="https://thesmutandsentimentsociety.substack.com/subscribe">join the society →</a>
		</div>
	</div>
	<?php bbb_render_component('library-modal'); ?>
</section>
