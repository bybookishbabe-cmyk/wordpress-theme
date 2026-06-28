<?php
/**
 * Search results template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$search_query = get_search_query();

get_header();
?>

<div class="main-blog main-blog--search page-width">
	<div class="main-blog__hero">
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
			<?php esc_html_e('romance guides, reading lists, book breakdowns, and reader tools from the site.', 'bybookishbabe-shopify-port'); ?>
		</p>
	</div>

	<section class="main-blog__posts" aria-labelledby="search-results-title">
		<div class="blog-discovery__header blog-discovery__header--posts">
			<p class="blog-discovery__kicker">matched pages</p>
			<h2 class="blog-discovery__title" id="search-results-title">
				<?php esc_html_e('open a result', 'bybookishbabe-shopify-port'); ?>
			</h2>
		</div>

		<?php if (have_posts()) : ?>
			<div class="blog-articles">
				<?php while (have_posts()) : ?>
					<?php the_post(); ?>
					<div class="blog-articles__article">
						<?php get_template_part('template-parts/article-card'); ?>
					</div>
				<?php endwhile; ?>
			</div>
		<?php else : ?>
			<div class="rte">
				<p><?php esc_html_e('No results found. Try a book title, trope, or genre.', 'bybookishbabe-shopify-port'); ?></p>
			</div>
		<?php endif; ?>
	</section>

	<?php if ($wp_query->max_num_pages > 1) : ?>
		<nav class="pagination" aria-label="<?php esc_attr_e('Search results navigation', 'bybookishbabe-shopify-port'); ?>">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'current'   => max(1, (int) get_query_var('paged')),
						'total'     => (int) $wp_query->max_num_pages,
						'prev_text' => '&larr; previous',
						'next_text' => 'next &rarr;',
					)
				)
			);
			?>
		</nav>
	<?php endif; ?>
</div>

<?php
get_footer();
