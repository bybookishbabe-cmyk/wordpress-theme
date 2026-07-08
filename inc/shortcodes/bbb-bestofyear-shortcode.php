<?php
/**
 * Best of year numbered book slots shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_bestofyear_shortcode_enqueue_assets(): void {
	if (function_exists('bbb_enqueue_css')) {
		bbb_enqueue_css('section-blog-post', 'assets/css/section-blog-post.css', array('bbb-bookshelf-signup'));
		bbb_enqueue_css('blog-system', 'assets/css/blog-system.css', array('section-blog-post'));
		bbb_enqueue_css('bbb-best-romance-books', 'assets/css/best-romance-books.css', array('blog-system', 'bbb-cta-design-system'));
	}
}

function bbb_bestofyear_year(int $post_id, array $atts): int {
	$year = absint($atts['year'] ?? 0);
	if ($year >= 2000 && $year <= 2100) {
		return $year;
	}

	$meta_year = absint(get_post_meta($post_id, '_bbb_best_books_year', true));
	if ($meta_year >= 2000 && $meta_year <= 2100) {
		return $meta_year;
	}

	if (preg_match('/(20\d{2})/', (string) get_post_field('post_name', $post_id), $matches)) {
		return (int) $matches[1];
	}

	if (preg_match('/(20\d{2})/', (string) get_the_title($post_id), $matches)) {
		return (int) $matches[1];
	}

	return (int) wp_date('Y');
}

function bbb_bestofyear_tokens(string $raw): array {
	return array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $raw) ?: array())));
}

function bbb_bestofyear_book_from_token(string $token): ?WP_Post {
	$token = trim($token);
	if ('' === $token) {
		return null;
	}

	if (is_numeric($token)) {
		$post = get_post((int) $token);
		if ($post instanceof WP_Post && in_array($post->post_type, array('bbb_book', 'sss_book'), true)) {
			return $post;
		}
	}

	if (function_exists('sss_article_book_from_name')) {
		$book = sss_article_book_from_name($token);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	foreach (array('bbb_book', 'sss_book') as $post_type) {
		if (!post_type_exists($post_type)) {
			continue;
		}

		$post = get_page_by_path(sanitize_title($token), OBJECT, $post_type);
		if ($post instanceof WP_Post) {
			return $post;
		}
	}

	return null;
}

function bbb_bestofyear_books(int $post_id, int $year, array $atts): array {
	$raw = trim((string) ($atts['books'] ?? ''));
	if ('' === $raw) {
		$raw = (string) get_post_meta($post_id, '_bbb_best_books_ids', true);
	}

	if ('' === trim($raw) && 2026 === $year) {
		$raw = 'my dreadful darling';
	}

	$books = array();
	foreach (bbb_bestofyear_tokens($raw) as $token) {
		$book = bbb_bestofyear_book_from_token($token);
		if ($book instanceof WP_Post) {
			$books[$book->ID] = $book;
		}
	}

	return array_values($books);
}

function bbb_bestofyear_fictionalman_map(int $post_id, array $atts): array {
	$raw = trim((string) ($atts['fictionalmen'] ?? $atts['fictionalman'] ?? ''));
	if ('' === $raw) {
		$raw = (string) get_post_meta($post_id, '_bbb_best_books_fictionalman_names', true);
	}
	if ('' === trim($raw)) {
		$raw = "my dreadful darling: Nathan White\nMy Dreadful Darling: Nathan White";
	}

	$map = array();
	foreach (preg_split('/[\r\n,]+/', $raw) ?: array() as $line) {
		$parts = array_map('trim', explode(':', (string) $line, 2));
		if (2 !== count($parts) || '' === $parts[0] || '' === $parts[1]) {
			continue;
		}

		$map[sanitize_title($parts[0])] = $parts[1];
	}

	return $map;
}

function bbb_bestofyear_fictionalman_for_book(WP_Post $book, array $map): string {
	$keys = array_filter(
		array(
			sanitize_title((string) get_post_field('post_name', $book)),
			sanitize_title((string) get_the_title($book)),
		)
	);

	foreach ($keys as $key) {
		if (isset($map[$key])) {
			return $map[$key];
		}
	}

	if (function_exists('bbb_fictional_boyfriend_for_book')) {
		$boyfriend_names = function_exists('bbb_fictional_boyfriend_book_boyfriend_names')
			? bbb_fictional_boyfriend_book_boyfriend_names($book)
			: array((string) get_post_meta($book->ID, '_bbb_boyfriend_name', true));

		foreach (array_merge($boyfriend_names, array('')) as $boyfriend_name) {
			$profile = bbb_fictional_boyfriend_for_book($book->ID, (string) $boyfriend_name);
			if ($profile instanceof WP_Post) {
				return get_the_title($profile);
			}
		}
	}

	$fallback_name = trim((string) get_post_meta($book->ID, '_bbb_boyfriend_name', true));
	if ('' !== $fallback_name) {
		return $fallback_name;
	}

	return '';
}

function bbb_bestofyear_render_book_card(WP_Post $book): string {
	if (function_exists('sss_render_article_book_card')) {
		return sss_render_article_book_card($book->ID, true);
	}

	if (function_exists('bbb_render_article_book_card')) {
		return bbb_render_article_book_card($book->ID, true);
	}

	return '';
}

function bbb_bestofyear_shortcode($atts): string {
	$atts = shortcode_atts(
		array(
			'year'          => '',
			'total'         => '10',
			'title'         => '',
			'intro'         => '',
			'show_header'   => 'true',
			'books'         => '',
			'fictionalmen'  => '',
			'fictionalman'  => '',
			'show_empty'    => 'true',
		),
		(array) $atts,
		'bestofyear'
	);

	$post_id = get_the_ID() ?: 0;
	$year    = bbb_bestofyear_year($post_id, $atts);
	$total   = max(1, absint($atts['total']) ?: 10);
	$books   = bbb_bestofyear_books($post_id, $year, $atts);
	$filled  = count($books);
	$title   = trim((string) $atts['title']);
	if ('' === $title) {
		$title = "bybookishbabe's best romance books of " . $year;
	}
	$intro = trim((string) $atts['intro']);
	if ('' === $intro) {
		$intro = 'not a ranked list. not a roundup of everything hyped. just the books that actually got me this year, filled in as i read them.';
	}
	$show_header = !in_array(strtolower((string) $atts['show_header']), array('0', 'false', 'no'), true);
	$show_empty = !in_array(strtolower((string) $atts['show_empty']), array('0', 'false', 'no'), true);
	$fictionalman_map = bbb_bestofyear_fictionalman_map($post_id, $atts);

	bbb_bestofyear_shortcode_enqueue_assets();

	ob_start();
	?>
	<section class="bbb-bestOfYear" aria-label="<?php echo esc_attr('best romance books of ' . $year); ?>">
		<?php if ($show_header) : ?>
			<header class="bbb-bestOfYear__header">
				<h1><?php echo esc_html($title); ?></h1>
				<p><?php echo esc_html($intro); ?></p>
			</header>
		<?php endif; ?>
		<div class="bbb-bestOfYear__status">
			<span><?php echo esc_html((string) $filled); ?> / <?php echo esc_html((string) $total); ?> spots filled</span>
		</div>

		<div class="bbb-bestOfYear__list">
			<?php foreach ($books as $index => $book) : ?>
				<?php $fictionalman = bbb_bestofyear_fictionalman_for_book($book, $fictionalman_map); ?>
				<article class="bbb-bestOfYear__slot bbb-bestOfYear__slot--filled">
					<div class="bbb-bestOfYear__number"><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></div>
					<div class="bbb-bestOfYear__body">
						<?php echo bbb_bestofyear_render_book_card($book); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ('' !== $fictionalman && function_exists('bbb_fictionalman_shortcode')) : ?>
							<?php echo bbb_fictionalman_shortcode(array('name' => $fictionalman)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>

			<?php if ($show_empty && $filled < $total) : ?>
				<?php for ($spot = $filled + 1; $spot <= $total; $spot++) : ?>
					<div class="bbb-bestOfYear__slot bbb-bestOfYear__slot--empty">
						<div class="bbb-bestOfYear__number"><?php echo esc_html(str_pad((string) $spot, 2, '0', STR_PAD_LEFT)); ?></div>
						<p>spot <?php echo esc_html((string) $spot); ?> — coming soon</p>
					</div>
				<?php endfor; ?>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode('bestofyear', 'bbb_bestofyear_shortcode');
