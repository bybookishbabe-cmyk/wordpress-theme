<?php
/**
 * Swipeable mini book-card shortcode for article previews.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_book_swipe_assets(): void {
	if (function_exists('bbb_enqueue_css')) {
		bbb_enqueue_css('bbb-book-swipe', 'assets/css/bbb-book-swipe.css', array('blog-system'));
		return;
	}

	wp_enqueue_style('bbb-book-swipe', get_theme_file_uri('assets/css/bbb-book-swipe.css'), array(), wp_get_theme()->get('Version'));
}

function bbb_book_swipe_parse_books(string $raw_books): array {
	$books = array();
	foreach (preg_split('/\s*(?:,|\||;|\R)\s*/', trim($raw_books)) ?: array() as $raw_book) {
		$raw_book = trim(wp_strip_all_tags($raw_book), " \t\n\r\0\x0B\"'");
		if ('' === $raw_book) {
			continue;
		}

		if (function_exists('sss_article_book_from_name')) {
			$book = sss_article_book_from_name($raw_book);
			if ($book instanceof WP_Post) {
				$books[$book->ID] = $book;
			}
		}
	}

	return array_values($books);
}

function bbb_book_swipe_books_from_content(string $content): array {
	$book_names = array();

	if (preg_match_all('/\[(?:bookcard|book):([^\]\r\n]+)\]/i', $content, $matches)) {
		foreach ($matches[1] as $match) {
			$book_names[] = trim(wp_strip_all_tags((string) $match), " \t\n\r\0\x0B\"'");
		}
	}

	if (!$book_names) {
		$book_names = preg_split('/\s*(?:,|\||;|\R)\s*/', trim(wp_strip_all_tags($content))) ?: array();
	}

	return bbb_book_swipe_parse_books(implode('|', array_filter($book_names)));
}

function bbb_book_swipe_first_trope(array $book): array {
	$tropes = (array) ($book['tropes'] ?? array());
	if (!$tropes) {
		return array();
	}

	$trope = $tropes[0];
	return is_array($trope) ? $trope : array();
}

function bbb_book_swipe_trope_url(array $trope): string {
	$name = (string) ($trope['name'] ?? '');
	$slug = (string) ($trope['slug'] ?? $trope['handle'] ?? sanitize_title($name));
	if ('' === $name && '' === $slug) {
		return '';
	}

	if (function_exists('bbb_trope_page_url')) {
		return bbb_trope_page_url($name, $slug);
	}

	return home_url('/' . sanitize_title($slug ?: $name) . '-books/');
}

function bbb_book_swipe_render_card(WP_Post $post): string {
	if (!function_exists('sss_article_book_data')) {
		return '';
	}

	$book       = sss_article_book_data((int) $post->ID);
	$title      = (string) ($book['title'] ?? get_the_title($post));
	$author     = (string) ($book['author'] ?? '');
	$url        = (string) ($book['url'] ?? get_permalink($post));
	$cover      = (string) ($book['cover'] ?? '');
	$spice      = max(0, min(5, (int) ($book['spice'] ?? 0)));
	$shelf      = (string) ($book['shelf']['name'] ?? '');
	$trope      = bbb_book_swipe_first_trope($book);
	$trope_url  = bbb_book_swipe_trope_url($trope);
	$trope_name = (string) ($trope['name'] ?? '');
	$trope_emoji = (string) ($trope['emoji'] ?? '');
	$trope_slug = (string) ($trope['slug'] ?? $trope['handle'] ?? '');
	$data_attrs = function_exists('sss_article_data_attrs') ? sss_article_data_attrs($book) : '';
	$cover_alt  = function_exists('bbb_book_cover_alt') ? bbb_book_cover_alt($title, $author, $shelf) : $title . ' book cover';

	ob_start();
	?>
	<article class="bbb-book-swipe__card" data-book-preview <?php echo $data_attrs; ?>>
		<a class="bbb-book-swipe__coverLink" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr('view ' . $title); ?>">
			<?php if ('' !== $cover) : ?>
				<img class="bbb-book-swipe__cover" src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($cover_alt); ?>" loading="lazy">
			<?php else : ?>
				<span class="bbb-book-swipe__cover bbb-book-swipe__cover--empty" aria-hidden="true"></span>
			<?php endif; ?>
			<?php if ($spice > 0) : ?>
				<span class="bbb-book-swipe__spice" aria-label="<?php echo esc_attr((string) $spice . ' out of 5 spice'); ?>"><?php echo esc_html(str_repeat('🌶', $spice)); ?></span>
			<?php endif; ?>
		</a>
		<div class="bbb-book-swipe__body">
			<a class="bbb-book-swipe__title" href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a>
			<?php if ('' !== $author) : ?>
				<div class="bbb-book-swipe__author"><?php echo esc_html($author); ?></div>
			<?php endif; ?>
			<div class="bbb-book-swipe__meta">
				<?php if ('' !== $trope_name) : ?>
					<a class="bbb-book-swipe__pill" href="<?php echo esc_url($trope_url); ?>">
						<?php echo function_exists('bbb_trope_label_html') ? wp_kses_post(bbb_trope_label_html($trope_name, $trope_emoji, $trope_slug)) : esc_html(trim(($trope_emoji ?: '♡') . ' ' . $trope_name)); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}

function bbb_book_swipe_shortcode($atts, ?string $content = null): string {
	$atts = shortcode_atts(
		array(
			'books' => '',
			'title' => '',
		),
		(array) $atts,
		'bbb_book_swipe'
	);

	$raw_books = trim((string) $atts['books']);
	$books     = '' !== $raw_books ? bbb_book_swipe_parse_books($raw_books) : array();
	if (!$books && null !== $content) {
		$books = bbb_book_swipe_books_from_content((string) $content);
	}

	if (!$books) {
		return '';
	}

	bbb_book_swipe_assets();

	$heading = trim(wp_strip_all_tags((string) $atts['title']));
	$uid     = 'bbb-book-swipe-' . wp_generate_password(8, false, false);

	ob_start();
	?>
	<section class="bbb-book-swipe" aria-labelledby="<?php echo esc_attr($uid); ?>">
		<?php if ('' !== $heading) : ?>
			<div class="bbb-book-swipe__head">
				<h3 class="bbb-book-swipe__heading" id="<?php echo esc_attr($uid); ?>"><?php echo esc_html($heading); ?></h3>
			</div>
		<?php else : ?>
			<h3 class="screen-reader-text" id="<?php echo esc_attr($uid); ?>">book previews</h3>
		<?php endif; ?>
		<div class="bbb-book-swipe__rail" tabindex="0" aria-label="swipe through book previews">
			<?php foreach ($books as $book) : ?>
				<?php echo bbb_book_swipe_render_card($book); ?>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

add_shortcode('bbb_book_swipe', 'bbb_book_swipe_shortcode');
add_shortcode('bookswipe', 'bbb_book_swipe_shortcode');
