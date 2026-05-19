<?php
/**
 * Shopify-compatible "books like x" directory page.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

wp_enqueue_style('bbb-books-like', get_theme_file_uri('assets/css/books-like.css'), array('bbb-sss-library'), wp_get_theme()->get('Version'));
get_header();

$groups = function_exists('bbb_books_like_grouped_guides') ? bbb_books_like_grouped_guides() : array();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-books-like-directory">
		<div class="bbb-books-like-directory__wrap">
			<header class="bbb-books-like-directory__hero">
				<p class="bbb-books-like-directory__kicker">reading guides</p>
				<h1 class="bbb-books-like-directory__title">books like x</h1>
				<p class="bbb-books-like-directory__subtext">finished something that wrecked you and have no idea what to read next? start here. every list has been read, curated, and personally recommended.</p>
			</header>

			<?php if ($groups) : ?>
				<div class="bbb-books-like-directory__groups">
					<?php foreach ($groups as $group) : ?>
						<section class="bbb-books-like-directory__group" aria-label="<?php echo esc_attr((string) $group['name']); ?>">
							<h2 class="bbb-books-like-directory__groupTitle"><?php echo esc_html((string) $group['name']); ?></h2>
							<div class="bbb-books-like-directory__grid">
								<?php foreach ($group['items'] as $item) :
									$post   = $item['post'];
									$source = $item['source'];
									$book   = $source instanceof WP_Post ? bbb_books_like_book_data($source->ID) : array();
									$cover  = (string) ($book['cover'] ?? get_the_post_thumbnail_url($post->ID, 'large'));
									$tropes = array_slice((array) ($book['tropes'] ?? array()), 0, 3);
									$subtitle = $tropes ? implode(' · ', wp_list_pluck($tropes, 'name')) : wp_strip_all_tags(get_the_excerpt($post));
								?>
									<a class="bbb-books-like-directory__card" href="<?php echo esc_url(get_permalink($post)); ?>">
										<div class="bbb-books-like-directory__cardMedia">
											<?php if ($cover) : ?>
												<img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(get_the_title($post)); ?>" loading="lazy">
											<?php endif; ?>
										</div>
										<div class="bbb-books-like-directory__cardCopy">
											<h3><?php echo esc_html(get_the_title($post)); ?></h3>
											<p><?php echo esc_html(wp_trim_words($subtitle, 18, '')); ?></p>
										</div>
										<span class="bbb-books-like-directory__arrow" aria-hidden="true">→</span>
									</a>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
				<p class="bbb-books-like-directory__note">each card links directly to its blog post · grouped by genre · author and main trope visible at a glance</p>
			<?php else : ?>
				<p class="bbb-books-like-directory__empty">waiting on books like guide posts</p>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
