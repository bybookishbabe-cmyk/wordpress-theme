<?php
/**
 * Society-only directory for generated if-you-liked page-template guides.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$books_like_css_path = get_theme_file_path('assets/css/books-like.css');
wp_enqueue_style('bbb-books-like', get_theme_file_uri('assets/css/books-like.css'), array('bbb-sss-library'), file_exists($books_like_css_path) ? (string) filemtime($books_like_css_path) : wp_get_theme()->get('Version'));
get_header();

if (!function_exists('bbb_reader_is_society') || !bbb_reader_is_society()) {
	if (function_exists('bbb_society_render_locked_preview_page')) {
		bbb_society_render_locked_preview_page(
			array(
				'access'      => 'paid',
				'kicker'      => 'paid society preview',
				'title'       => 'if you liked',
				'intro'       => 'peek at the exclusive recommendation directory before deciding whether to unlock the full society shelf.',
				'panel_title' => 'upgrade to open every guide',
				'panel_copy'  => 'paid society members get generated if-you-liked guides, trope-led pairings, and deeper recommendation paths.',
				'items'       => array(
					'preview the kind of book-pairing guides inside the directory',
					'open the full guide grid once your paid membership is active',
					'jump from a book you loved into related trope and mood picks',
				),
			)
		);
	}
	get_footer();
	return;
}

$groups = function_exists('bbb_books_like_grouped_guides') ? bbb_books_like_grouped_guides() : array();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-books-like-directory">
		<div class="bbb-books-like-directory__wrap">
			<header class="bbb-books-like-directory__hero">
				<p class="bbb-books-like-directory__kicker">society exclusives</p>
				<h1 class="bbb-books-like-directory__title">if you liked</h1>
				<p class="bbb-books-like-directory__subtext">generated recommendation pages for jumping from one book you loved into seven more with the same mood, trope, or damage.</p>
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
									$url = (string) ($item['url'] ?? home_url('/if-you-liked-pages/' . trim((string) $post->post_name, '/') . '/'));
									$title = get_the_title($post) ?: (string) $post->post_title;
								?>
									<a class="bbb-books-like-directory__card" href="<?php echo esc_url($url); ?>">
										<div class="bbb-books-like-directory__cardMedia">
											<?php if ($cover) : ?>
												<img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
											<?php endif; ?>
										</div>
										<div class="bbb-books-like-directory__cardCopy">
											<h3><?php echo esc_html($title); ?></h3>
											<p><?php echo esc_html(wp_trim_words($subtitle, 18, '')); ?></p>
										</div>
										<span class="bbb-books-like-directory__arrow" aria-hidden="true">→</span>
									</a>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="bbb-books-like-directory__empty">waiting on books-like template pages</p>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
