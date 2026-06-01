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
	$published_iso  = get_post_time('c', false, $post_id);
	$published_text = get_the_date('F j, Y', $post_id);
	$modified_iso   = get_post_modified_time('c', false, $post_id);
	$modified_text  = get_the_modified_date('F j, Y', $post_id);
	$show_modified  = $published_text !== $modified_text;
	if (!$show_hero && function_exists('get_field')) {
		$show_hero = sss_article_bool(get_field('_sss_show_hero', $post_id));
	}
	$GLOBALS['sss_article_has_pillar'] = $has_pillar;
	?>

<article class="article-template<?php echo $show_hero ? ' has-hero' : ''; ?>" itemscope itemtype="https://schema.org/Article">
  <meta itemprop="mainEntityOfPage" content="<?php echo esc_url(get_permalink($post_id)); ?>">
  <meta itemprop="author" content="<?php echo esc_attr(get_the_author_meta('display_name', (int) get_post_field('post_author', $post_id))); ?>">
  <?php if (!$show_modified) : ?>
    <meta itemprop="dateModified" content="<?php echo esc_attr($modified_iso); ?>">
  <?php endif; ?>

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
    <h1 class="article-template__title" itemprop="headline"><?php the_title(); ?></h1>
    <div class="article-template__meta" aria-label="article dates">
      <span class="article-template__meta-label">Published</span>
      <time class="entry-date published" itemprop="datePublished" datetime="<?php echo esc_attr($published_iso); ?>"><?php echo esc_html($published_text); ?></time>
      <?php if ($show_modified) : ?>
        <span class="article-template__meta-separator" aria-hidden="true">/</span>
        <span class="article-template__meta-label">Updated</span>
        <time class="updated" itemprop="dateModified" datetime="<?php echo esc_attr($modified_iso); ?>"><?php echo esc_html($modified_text); ?></time>
      <?php endif; ?>
    </div>
  </header>

  <div class="article-template__content page-width page-width--narrow rte">
    <?php
	$raw_content       = get_the_content(null, false, $post_id);
	$processed_content = sss_token_engine($raw_content, $post_id);
	echo apply_filters('the_content', $processed_content); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
  </div>

  <?php
	if (function_exists('bbb_render_society_recommendations')) {
		echo bbb_render_society_recommendations($post_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>

</article>

	<?php
endwhile;

get_footer();
