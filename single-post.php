<?php
/**
 * Article page template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();

while (have_posts()) :
	the_post();

	$post_id        = get_the_ID();
	$books          = function_exists('get_field') ? get_field('book', $post_id) : array();
	$article_trope  = function_exists('get_field') ? get_field('trope', $post_id) : null;
	$article_trope  = sss_article_post($article_trope);
	$article_series = function_exists('get_field') ? (get_field('sss_series', $post_id) ?: get_field('series', $post_id)) : null;
	$article_series = sss_article_post($article_series);
	$guide_cat      = function_exists('get_field') ? get_field('guide_category', $post_id) : null;
	$guide_cat      = sss_article_post($guide_cat);
	$has_pillar     = sss_content_has_pillar((string) get_the_content(null, false, $post_id));
	$show_hero      = sss_article_bool(get_post_meta($post_id, '_sss_show_hero', true));
	if (!$show_hero && function_exists('get_field')) {
		$show_hero = sss_article_bool(get_field('_sss_show_hero', $post_id));
	}
	$GLOBALS['sss_article_has_pillar'] = $has_pillar;
	?>

<article class="article-template<?php echo $show_hero ? ' has-hero' : ''; ?>">

  <?php if ($show_hero) : ?>
  <div class="article-template__hero-container">
    <div class="sss-article-hero">
      <div class="sss-article-hero__collage">
        <?php
		$hero_books = array();
		foreach (sss_article_all_visible_books() as $book) {
			$shelf = sss_article_shelf($book->ID);
			if ('morally-gray-lovers' === ($shelf['slug'] ?? '')) {
				$hero_books[] = $book;
			}
			if (3 === count($hero_books)) {
				break;
			}
		}
		foreach ($hero_books as $i => $book) :
			$cover = sss_article_cover_url($book->ID);
			if (!$cover) {
				continue;
			}
			?>
        <div class="sss-article-hero__book book-<?php echo esc_attr((string) ($i + 1)); ?>"><img src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr(get_the_title($book)); ?>"></div>
        <?php endforeach; ?>
      </div>
      <div class="sss-article-hero__title">
        <h1><?php the_title(); ?></h1>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <header class="page-width page-width--narrow scroll-trigger animate--fade-in">
    <h1 class="article-template__title"><?php the_title(); ?></h1>
    <span class="circle-divider caption-with-letter-spacing">
      <?php echo esc_html(get_the_date()); ?>
    </span>
  </header>

  <div class="article-template__content page-width page-width--narrow rte">
    <?php
	$raw_content       = get_the_content(null, false, $post_id);
	$processed_content = sss_token_engine($raw_content, $post_id);
	echo apply_filters('the_content', $processed_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
  </div>

</article>

	<?php
endwhile;

get_footer();
