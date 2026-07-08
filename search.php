<?php
/**
 * Search results template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$search_query = get_search_query();
$search_term  = trim($search_query);

$query_results = static function (array $args) use ($search_term): WP_Query {
	$defaults = array(
		's'                      => $search_term,
		'post_status'            => 'publish',
		'posts_per_page'         => 6,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	);

	return new WP_Query(array_merge($defaults, $args));
};

$existing_types = static function (array $post_types): array {
	return array_values(
		array_filter(
			$post_types,
			static fn(string $post_type): bool => post_type_exists($post_type)
		)
	);
};

$book_post_types = $existing_types(array('bbb_book', 'sss_book'));
$book_query      = $book_post_types && '' !== $search_term
	? $query_results(array('post_type' => $book_post_types, 'posts_per_page' => 4))
	: null;

$boyfriend_query = post_type_exists('bbb_boyfriend') && '' !== $search_term
	? $query_results(array('post_type' => 'bbb_boyfriend', 'posts_per_page' => 1))
	: null;

$boyfriend_book = null;
if ($boyfriend_query instanceof WP_Query && !empty($boyfriend_query->posts)) {
	$boyfriend_post = $boyfriend_query->posts[0] ?? null;
	if ($boyfriend_post instanceof WP_Post && function_exists('bbb_fictional_boyfriend_primary_book_id')) {
		$boyfriend_book_id = bbb_fictional_boyfriend_primary_book_id((int) $boyfriend_post->ID);
		$maybe_book        = $boyfriend_book_id > 0 ? get_post($boyfriend_book_id) : null;
		if ($maybe_book instanceof WP_Post && 'publish' === $maybe_book->post_status) {
			$boyfriend_book = $maybe_book;
		}
	}
}

$review_category = get_category_by_slug('book-reviews');
$review_query    = '' !== $search_term
	? $query_results(
		array_filter(
				array(
					'post_type'         => 'post',
					'posts_per_page'    => 1,
					'category__in'      => $review_category instanceof WP_Term ? array((int) $review_category->term_id) : null,
					'search_columns'    => array('post_title', 'post_excerpt', 'post_content'),
				)
		)
	)
	: null;

$blog_query = '' !== $search_term
	? $query_results(
		array_filter(
			array(
				'post_type'         => 'post',
				'posts_per_page'    => 5,
				'category__not_in'  => $review_category instanceof WP_Term ? array((int) $review_category->term_id) : null,
				'search_columns'    => array('post_title', 'post_excerpt', 'post_content'),
			)
		)
	)
	: null;

$related_types  = $existing_types(array('page', 'sss_series'));
$related_query  = $related_types && '' !== $search_term
	? $query_results(array('post_type' => $related_types, 'posts_per_page' => 6))
	: null;
$book_posts     = $book_query instanceof WP_Query ? $book_query->posts : array();
if ($boyfriend_book instanceof WP_Post) {
	$book_post_ids = array_map(static fn(WP_Post $post): int => (int) $post->ID, $book_posts);
	if (!in_array((int) $boyfriend_book->ID, $book_post_ids, true)) {
		array_unshift($book_posts, $boyfriend_book);
	}
}

$normalized_search_term = strtolower(remove_accents($search_term));
$is_generic_search      = '' !== $normalized_search_term
	&& (bool) preg_match('/\b(books?|romance|trope|tropes|genre|genres|lists?|guides?|recommendations?|recs|spice|dark|sports|paranormal|romantasy|mafia|why choose|reverse harem|enemies|slow burn)\b/', $normalized_search_term);

$total_sections = array_filter(array($book_query, $boyfriend_query, $review_query, $blog_query, $related_query), static function ($query): bool {
	return $query instanceof WP_Query && $query->have_posts();
});
if (!$is_generic_search && $book_posts) {
	$total_sections[] = $book_posts;
}

$book_data = static function (int $post_id): array {
	if (function_exists('sss_article_book_data')) {
		$data = sss_article_book_data($post_id);
		if (is_array($data)) {
			return $data;
		}
	}

	return array();
};

$book_cover = static function (int $post_id) use ($book_data): string {
	$data  = $book_data($post_id);
	$cover = trim((string) ($data['cover'] ?? ''));

	if ('' === $cover) {
		$cover = (string) get_the_post_thumbnail_url($post_id, 'medium_large');
	}

	return $cover;
};

$linked_review_book = static function (int $post_id): ?WP_Post {
	$book_id = (int) get_post_meta($post_id, '_bbb_article_book_1', true);

	if ($book_id > 0) {
		$book = get_post($book_id);
		if ($book instanceof WP_Post) {
			return $book;
		}
	}

	if (function_exists('bbb_blog_post_seo_book_for_review')) {
		$book = bbb_blog_post_seo_book_for_review($post_id, get_the_title($post_id));
		return $book instanceof WP_Post ? $book : null;
	}

	return null;
};

$excerpt_for = static function (WP_Post $post, string $fallback = ''): string {
	$excerpt = trim((string) get_the_excerpt($post));

	if ('' === $excerpt) {
		$excerpt = $fallback;
	}

	return wp_trim_words(wp_strip_all_tags($excerpt), 24);
};

$render_section_header = static function (string $label, string $title): void {
	?>
	<div class="bbb-search-results__sectionHead">
		<p class="bbb-search-results__kicker"><?php echo esc_html($label); ?></p>
		<h2 class="bbb-search-results__sectionTitle"><?php echo esc_html($title); ?></h2>
	</div>
	<?php
};

$render_media = static function (string $image, string $alt, string $kind): void {
	?>
	<div class="bbb-search-card__media bbb-search-card__media--<?php echo esc_attr($kind); ?>">
		<?php if ('' !== $image) : ?>
			<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
		<?php else : ?>
			<span aria-hidden="true"><?php echo esc_html(substr($alt, 0, 1)); ?></span>
		<?php endif; ?>
	</div>
	<?php
};

$render_card = static function (WP_Post $post, array $args = array()) use ($render_media): void {
	$defaults = array(
		'type'        => 'related',
		'label'       => 'related',
		'image'       => '',
		'image_alt'   => get_the_title($post),
		'title'       => get_the_title($post),
		'title_html'  => '',
		'meta'        => '',
		'description' => '',
		'url'         => get_permalink($post),
		'compact'     => false,
	);
	$card = array_merge($defaults, $args);
	?>
	<article class="bbb-search-card bbb-search-card--<?php echo esc_attr((string) $card['type']); ?><?php echo $card['compact'] ? ' bbb-search-card--compact' : ''; ?>">
		<a class="bbb-search-card__link" href="<?php echo esc_url((string) $card['url']); ?>">
			<?php if (!$card['compact']) : ?>
				<?php $render_media((string) $card['image'], (string) $card['image_alt'], (string) $card['type']); ?>
			<?php endif; ?>
			<div class="bbb-search-card__body">
				<div class="bbb-search-card__topline">
					<span class="bbb-search-card__type"><?php echo esc_html((string) $card['label']); ?></span>
					<?php if ('' !== (string) $card['meta']) : ?>
						<span class="bbb-search-card__meta"><?php echo esc_html((string) $card['meta']); ?></span>
					<?php endif; ?>
				</div>
				<h3 class="bbb-search-card__title">
					<?php
					if ('' !== (string) $card['title_html']) {
						echo wp_kses_post((string) $card['title_html']);
					} else {
						echo esc_html((string) $card['title']);
					}
					?>
				</h3>
				<?php if ('' !== (string) $card['description']) : ?>
					<p class="bbb-search-card__desc"><?php echo esc_html((string) $card['description']); ?></p>
				<?php endif; ?>
			</div>
		</a>
	</article>
	<?php
};

$taxonomy_title_html = static function (WP_Post $post): string {
	if (!function_exists('bbb_find_book_taxonomy_term')) {
		return esc_html(get_the_title($post));
	}

	$term = bbb_find_book_taxonomy_term((string) $post->post_name);
	if (!$term instanceof WP_Term) {
		return esc_html(get_the_title($post));
	}

	$emoji_html = '';
	$emoji      = function_exists('bbb_book_taxonomy_term_emoji') ? bbb_book_taxonomy_term_emoji($term) : '';

	if (function_exists('bbb_trope_emoji_html')) {
		$emoji_html = bbb_trope_emoji_html($term->name, $emoji, $term->slug);
	} elseif (function_exists('bbb_custom_emoji_html')) {
		$emoji_html = bbb_custom_emoji_html($term->name, $term->slug, 'bbb-search-card__emoji');
	}

	if ('' === $emoji_html && '' !== $emoji) {
		$emoji_html = '<span class="bbb-custom-emoji bbb-custom-emoji--text" aria-hidden="true">' . esc_html($emoji) . '</span>';
	}

	return trim($emoji_html . ' <span class="bbb-search-card__titleText">' . esc_html(get_the_title($post)) . '</span>');
};

if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('bbb-search-results', 'assets/css/search-results.css', array('section-main-blog'));
}

get_header();
?>

<div class="main-blog main-blog--search bbb-search-results page-width">
	<div class="main-blog__hero bbb-search-results__hero">
		<p class="main-blog__eyebrow">search</p>
		<h1 class="title--primary">
			<?php
			if ('' !== $search_query) {
				printf(
					/* translators: %s: search query. */
					esc_html__('results for "%s"', 'bybookishbabe-shopify-port'),
					esc_html($search_query)
				);
			} else {
				esc_html_e('search bybookishbabe', 'bybookishbabe-shopify-port');
			}
			?>
		</h1>
		<p class="main-blog__intro">
			<?php
			if ($is_generic_search) {
				esc_html_e('guides, reading lists, trope pages, and genre shelves grouped for browsing.', 'bybookishbabe-shopify-port');
			} else {
				esc_html_e('book pages, fictional men, reviews, blog guides, and related site pages grouped by what they are.', 'bybookishbabe-shopify-port');
			}
			?>
		</p>
	</div>

	<?php if ('' === $search_term) : ?>
		<div class="bbb-search-results__empty rte">
			<p><?php esc_html_e('Try a book title, trope, character, or author.', 'bybookishbabe-shopify-port'); ?></p>
		</div>
	<?php elseif (!$total_sections) : ?>
		<div class="bbb-search-results__empty rte">
			<p><?php esc_html_e('No results found. Try a book title, trope, character, or author.', 'bybookishbabe-shopify-port'); ?></p>
		</div>
	<?php else : ?>
		<div class="bbb-search-results__stack">
			<?php if ($is_generic_search && $blog_query instanceof WP_Query && $blog_query->have_posts()) : ?>
				<section class="bbb-search-results__section" aria-labelledby="bbb-search-blogs">
					<?php $render_section_header('blogs', 'guides and reading lists'); ?>
					<div class="bbb-search-results__list" id="bbb-search-blogs">
						<?php while ($blog_query->have_posts()) : ?>
							<?php $blog_query->the_post(); ?>
							<?php
							$render_card(
								get_post(),
								array(
									'type'        => 'blog',
									'label'       => 'blog',
									'compact'     => true,
									'meta'        => get_the_date('F j, Y'),
									'description' => $excerpt_for(get_post(), 'Open the guide.'),
								)
							);
							?>
						<?php endwhile; ?>
					</div>
				</section>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>

			<?php if ($is_generic_search && $related_query instanceof WP_Query && $related_query->have_posts()) : ?>
				<section class="bbb-search-results__section" aria-labelledby="bbb-search-related">
					<?php $render_section_header('trope & genre pages', 'browse the shelf'); ?>
					<div class="bbb-search-results__related" id="bbb-search-related">
						<?php while ($related_query->have_posts()) : ?>
							<?php $related_query->the_post(); ?>
							<?php
							$type_label = 'sss_series' === get_post_type() ? 'series page' : 'browse page';
							$render_card(
								get_post(),
								array(
									'type'        => 'related',
									'label'       => $type_label,
									'title_html'  => $taxonomy_title_html(get_post()),
									'meta'        => get_post_type_object(get_post_type())->labels->singular_name ?? '',
									'description' => $excerpt_for(get_post(), 'Open this browse page.'),
									'compact'     => true,
								)
							);
							?>
						<?php endwhile; ?>
					</div>
				</section>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>

			<?php if (!$is_generic_search && $book_posts) : ?>
				<section class="bbb-search-results__section" aria-labelledby="bbb-search-books">
					<?php $render_section_header('book pages', 'start with the book'); ?>
					<div class="bbb-search-results__grid bbb-search-results__grid--books" id="bbb-search-books">
						<?php foreach ($book_posts as $book_post) : ?>
							<?php
							if (!$book_post instanceof WP_Post) {
								continue;
							}
							$post_id = (int) $book_post->ID;
							$data    = $book_data($post_id);
							$author  = trim((string) ($data['author'] ?? ''));
							$spice   = max(0, min(5, (int) ($data['spice'] ?? 0)));
							$meta    = trim($author . ($spice > 0 ? ' - ' . $spice . '/5 spice' : ''));
							?>
							<?php
							$render_card(
								$book_post,
								array(
									'type'        => 'book',
									'label'       => 'book page',
									'image'       => $book_cover($post_id),
									'title'       => (string) ($data['title'] ?? get_the_title($book_post)),
									'meta'        => $meta,
									'description' => $excerpt_for($book_post, (string) ($data['mini'] ?? $data['why'] ?? 'Open the full book page.')),
								)
							);
							?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if (!$is_generic_search && $boyfriend_query instanceof WP_Query && $boyfriend_query->have_posts()) : ?>
				<section class="bbb-search-results__section" aria-labelledby="bbb-search-boyfriends">
					<?php $render_section_header('book boyfriends', 'open the character profile'); ?>
					<div class="bbb-search-results__grid bbb-search-results__grid--boyfriends" id="bbb-search-boyfriends">
						<?php while ($boyfriend_query->have_posts()) : ?>
							<?php
							$boyfriend_query->the_post();
							$post_id = get_the_ID();
							$book_id = function_exists('bbb_fictional_boyfriend_primary_book_id') ? bbb_fictional_boyfriend_primary_book_id($post_id) : 0;
							$book    = $book_id > 0 ? get_post($book_id) : null;
							$source  = $book instanceof WP_Post ? get_the_title($book) : '';
							$meta    = function_exists('bbb_fictional_boyfriend_descriptor') ? bbb_fictional_boyfriend_descriptor($post_id) : '';
							?>
							<?php
							$render_card(
								get_post(),
								array(
									'type'        => 'boyfriend',
									'label'       => 'book boyfriend',
									'image'       => (string) get_the_post_thumbnail_url($post_id, 'medium_large'),
									'title'       => get_the_title(),
									'meta'        => $source,
									'description' => $excerpt_for(get_post(), $meta),
								)
							);
							?>
						<?php endwhile; ?>
					</div>
				</section>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>

			<?php if (!$is_generic_search && $review_query instanceof WP_Query && $review_query->have_posts()) : ?>
				<section class="bbb-search-results__section" aria-labelledby="bbb-search-reviews">
					<?php $render_section_header('reviews on books', 'read the review'); ?>
					<div class="bbb-search-results__list" id="bbb-search-reviews">
						<?php while ($review_query->have_posts()) : ?>
							<?php
							$review_query->the_post();
							$post_id = get_the_ID();
							$book    = $linked_review_book($post_id);
							$cover   = $book instanceof WP_Post ? $book_cover((int) $book->ID) : (string) get_the_post_thumbnail_url($post_id, 'medium_large');
							$meta    = $book instanceof WP_Post ? 'review on ' . get_the_title($book) : get_the_date('F j, Y');
							?>
							<?php
							$render_card(
								get_post(),
								array(
									'type'        => 'review',
									'label'       => 'review',
									'image'       => $cover,
									'image_alt'   => $book instanceof WP_Post ? get_the_title($book) . ' cover' : get_the_title(),
									'meta'        => $meta,
									'description' => $excerpt_for(get_post(), 'Open the review breakdown.'),
								)
							);
							?>
						<?php endwhile; ?>
					</div>
				</section>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>

			<?php if (!$is_generic_search && $blog_query instanceof WP_Query && $blog_query->have_posts()) : ?>
				<section class="bbb-search-results__section" aria-labelledby="bbb-search-blogs">
					<?php $render_section_header('blogs', 'guides and reading lists'); ?>
					<div class="bbb-search-results__list" id="bbb-search-blogs">
						<?php while ($blog_query->have_posts()) : ?>
							<?php $blog_query->the_post(); ?>
							<?php
							$render_card(
								get_post(),
								array(
									'type'        => 'blog',
									'label'       => 'blog',
									'compact'     => true,
									'meta'        => get_the_date('F j, Y'),
									'description' => $excerpt_for(get_post(), 'Open the guide.'),
								)
							);
							?>
						<?php endwhile; ?>
					</div>
				</section>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>

			<?php if (!$is_generic_search && $related_query instanceof WP_Query && $related_query->have_posts()) : ?>
				<section class="bbb-search-results__section" aria-labelledby="bbb-search-related">
					<?php $render_section_header('related', 'site pages and series'); ?>
					<div class="bbb-search-results__related" id="bbb-search-related">
						<?php while ($related_query->have_posts()) : ?>
							<?php $related_query->the_post(); ?>
							<?php
							$type_label = 'sss_series' === get_post_type() ? 'series page' : 'site page';
							$render_card(
								get_post(),
								array(
									'type'        => 'related',
									'label'       => $type_label,
									'title_html'  => $taxonomy_title_html(get_post()),
									'meta'        => get_post_type_object(get_post_type())->labels->singular_name ?? '',
									'description' => $excerpt_for(get_post(), 'Open this related page.'),
									'compact'     => true,
								)
							);
							?>
						<?php endwhile; ?>
					</div>
				</section>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
