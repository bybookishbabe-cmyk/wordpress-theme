<?php
/**
 * Template Name: SSS Quote Wall
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('bbb-quote-wall', 'assets/css/sss-quote-wall.css');
} else {
	wp_enqueue_style('bbb-quote-wall', get_template_directory_uri() . '/assets/css/sss-quote-wall.css', array(), wp_get_theme()->get('Version'));
}

if (!function_exists('bbb_quote_wall_text')) {
	function bbb_quote_wall_text(WP_Post $quote): string {
		$text = trim((string) get_post_meta($quote->ID, '_quote_text', true));
		if ('' === $text) {
			$text = trim((string) get_post_meta($quote->ID, 'quote_text', true));
		}
		if ('' === $text) {
			$text = trim((string) get_post_meta($quote->ID, 'quote', true));
		}
		if ('' === $text) {
			$text = trim((string) get_post_meta($quote->ID, '_bbb_quote', true));
		}
		if ('' === $text) {
			$text = trim(wp_strip_all_tags($quote->post_content));
		}

		return wp_strip_all_tags($text);
	}
}

if (!function_exists('bbb_quote_wall_book')) {
	function bbb_quote_wall_book(WP_Post $quote): ?WP_Post {
		$book = function_exists('get_field') ? get_field('book', $quote->ID) : null;
		if ($book instanceof WP_Post) {
			return $book;
		}
		if (is_array($book) && isset($book['ID']) && is_numeric($book['ID'])) {
			$book_post = get_post((int) $book['ID']);
			if ($book_post instanceof WP_Post) {
				return $book_post;
			}
		}
		if (is_numeric($book)) {
			$book_post = get_post((int) $book);
			if ($book_post instanceof WP_Post) {
				return $book_post;
			}
		}

		$book_id = max(
			(int) get_post_meta($quote->ID, '_quote_book_id', true),
			(int) get_post_meta($quote->ID, '_quote_library_book_id', true),
			(int) get_post_meta($quote->ID, 'book_id', true),
			(int) get_post_meta($quote->ID, 'library_book_id', true)
		);
		if ($book_id > 0) {
			$book_post = get_post($book_id);
			if ($book_post instanceof WP_Post) {
				return $book_post;
			}
		}

		$handle = (string) get_post_meta($quote->ID, '_quote_book_handle', true);
		$handle = '' !== $handle ? $handle : (string) get_post_meta($quote->ID, 'book_handle', true);
		$handle = '' !== $handle ? $handle : (string) get_post_meta($quote->ID, '_bbb_book_handle', true);
		if ('' !== $handle) {
			foreach (array('bbb_book', 'sss_book') as $post_type) {
				$book_post = get_page_by_path($handle, OBJECT, $post_type);
				if ($book_post instanceof WP_Post) {
					return $book_post;
				}
			}
		}

		return null;
	}
}

if (!function_exists('bbb_quote_wall_book_meta')) {
	function bbb_quote_wall_book_meta(?WP_Post $book): array {
		if (!$book instanceof WP_Post) {
			return array('title' => '', 'author' => '', 'handle' => '', 'shelf' => '', 'url' => home_url('/library/'));
		}

		if (function_exists('sss_book_data')) {
			$data = sss_book_data($book);
			return array(
				'title'  => (string) ($data['title'] ?? get_the_title($book)),
				'author' => (string) ($data['author'] ?? ''),
				'handle' => (string) ($data['handle'] ?? $book->post_name),
				'shelf'  => (string) ($data['shelf'] ?? ''),
				'url'    => home_url('/library/?book=' . rawurlencode((string) ($data['handle'] ?? $book->post_name))),
			);
		}

		return array(
			'title'  => get_the_title($book),
			'author' => '',
			'handle' => $book->post_name,
			'shelf'  => '',
			'url'    => home_url('/library/?book=' . rawurlencode($book->post_name)),
		);
	}
}

if (!function_exists('bbb_quote_wall_theme')) {
	function bbb_quote_wall_theme(string $shelf, int $index): string {
		$shelf = strtolower($shelf);
		if (str_contains($shelf, 'dark') || str_contains($shelf, 'private')) {
			return 'rose';
		}
		if (str_contains($shelf, 'fantasy') || str_contains($shelf, 'romantasy')) {
			return 'paper';
		}
		if (str_contains($shelf, 'soft') || str_contains($shelf, 'sentimental')) {
			return 'gold';
		}

		return array('paper', 'rose', 'gray')[$index % 3];
	}
}

$quote_post_types = function_exists('bbb_quote_post_types') ? bbb_quote_post_types() : array();
$is_society = function_exists('bbb_reader_is_society') ? bbb_reader_is_society() : false;
$quote_limit = $is_society ? -1 : 6;
$quotes = $quote_post_types
	? get_posts(
		array(
			'post_type'      => $quote_post_types,
			'post_status'    => array('publish', 'draft'),
			'posts_per_page' => $quote_limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	)
	: array();
if (!$quotes && function_exists('bbb_quote_export_entries')) {
	$quotes = bbb_quote_export_entries((int) $quote_limit);
}

$join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');

get_header();
?>

<main class="bbb-quote-wall<?php echo $is_society ? ' is-unlocked' : ' is-preview'; ?>">
	<section class="bbb-quote-wall__hero">
		<div class="bbb-quote-wall__heroInner">
			<p class="bbb-quote-wall__kicker">quote library</p>
			<h1>lines that ruined me in a good way.</h1>
			<p>Soft damage, sharp longing, and the book lines worth pinning somewhere dramatic.</p>
			<div class="bbb-quote-wall__actions">
				<a href="<?php echo esc_url(home_url('/library/')); ?>">back to the library</a>
				<?php if (!$is_society) : ?>
					<a href="<?php echo esc_url($join_url); ?>">unlock the full wall</a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section class="bbb-quote-wall__board" aria-label="<?php esc_attr_e('Quote wall', 'bybookishbabe-shopify-port'); ?>" data-qw-list>
		<?php if (!$quotes) : ?>
			<div class="bbb-quote-wall__empty">
				<h2>No quotes yet.</h2>
				<p>Add quotes under the Quotes admin area and they will land here.</p>
			</div>
		<?php endif; ?>

		<?php foreach ($quotes as $index => $quote) : ?>
			<?php
			$book_meta = array('title' => '', 'author' => '', 'handle' => '', 'shelf' => '', 'url' => home_url('/library/'));
			if ($quote instanceof WP_Post) {
				$text = bbb_quote_wall_text($quote);
				$book_meta = bbb_quote_wall_book_meta(bbb_quote_wall_book($quote));
				$fallback_title = get_the_title($quote);
			} elseif (is_array($quote)) {
				$text = trim((string) ($quote['text'] ?? ''));
				$book_handle = (string) ($quote['book_handle'] ?? '');
				$book_meta = array(
					'title'  => (string) ($quote['book_title'] ?? ''),
					'author' => '',
					'handle' => $book_handle,
					'shelf'  => '',
					'url'    => '' !== $book_handle ? home_url('/library/?book=' . rawurlencode($book_handle)) : home_url('/library/'),
				);
				$fallback_title = '';
			} else {
				continue;
			}
			if ('' === $text) {
				continue;
			}
			$theme = bbb_quote_wall_theme((string) $book_meta['shelf'], (int) $index);
			?>
			<article class="bbb-quote-card bbb-quote-card--<?php echo esc_attr($theme); ?> <?php echo 0 === ((int) $index % 2) ? 'is-left' : 'is-right'; ?>" data-qw-item style="--d: <?php echo esc_attr((string) (((int) $index % 8) * 45)); ?>ms;">
				<div class="bbb-quote-card__pin" aria-hidden="true"></div>
				<blockquote><?php echo esc_html($text); ?></blockquote>
				<footer>
					<?php if ('' !== $book_meta['title']) : ?>
						<a href="<?php echo esc_url((string) $book_meta['url']); ?>"><?php echo esc_html((string) $book_meta['title']); ?></a>
						<?php if ('' !== $book_meta['author']) : ?>
							<span>by <?php echo esc_html((string) $book_meta['author']); ?></span>
						<?php endif; ?>
					<?php else : ?>
						<span><?php echo esc_html($fallback_title); ?></span>
					<?php endif; ?>
				</footer>
			</article>
		<?php endforeach; ?>
	</section>

	<?php if (!$is_society) : ?>
		<section class="bbb-quote-wall__gate">
			<p>preview mode</p>
			<h2>paid members get the full quote wall.</h2>
			<a href="<?php echo esc_url($join_url); ?>">join the society</a>
		</section>
	<?php endif; ?>
</main>

<script>
(function(){
	var items = document.querySelectorAll('[data-qw-item]');
	if (!items.length) return;

	if (!('IntersectionObserver' in window)) {
		items.forEach(function(item){ item.classList.add('is-in'); });
		return;
	}

	var observer = new IntersectionObserver(function(entries){
		entries.forEach(function(entry){
			if (!entry.isIntersecting) return;
			entry.target.classList.add('is-in');
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.12, rootMargin: '0px 0px -10% 0px' });

	items.forEach(function(item){ observer.observe(item); });
})();
</script>

<?php get_footer(); ?>
