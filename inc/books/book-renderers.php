<?php
/**
 * Book card renderers for the Shopify-faithful DOM contract.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_get_book_data_attrs(int $post_id): string {
	$slug       = get_post_field('post_name', $post_id);
	$title      = get_the_title($post_id);
	$author     = get_post_meta($post_id, '_bbb_author', true);
	$cover      = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($post_id) : get_post_meta($post_id, '_bbb_cover_url', true);
	$amazon     = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(get_post_meta($post_id, '_bbb_amazon_url', true)) : get_post_meta($post_id, '_bbb_amazon_url', true);
	$bookshop   = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(get_post_meta($post_id, '_bbb_bookshop_url', true)) : get_post_meta($post_id, '_bbb_bookshop_url', true);
	$mini       = get_post_meta($post_id, '_bbb_mini_note', true);
	$why        = get_post_meta($post_id, '_bbb_why', true);
	$newsletter = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(get_post_meta($post_id, '_bbb_newsletter_url', true)) : get_post_meta($post_id, '_bbb_newsletter_url', true);
	$spice      = get_post_meta($post_id, '_bbb_spice', true);
	$tension    = get_post_meta($post_id, '_bbb_tension', true);
	$damage     = get_post_meta($post_id, '_bbb_damage', true);
	$yearning   = get_post_meta($post_id, '_bbb_yearning', true);
	$bftype     = get_post_meta($post_id, '_bbb_boyfriend_type', true);
	$bfname     = get_post_meta($post_id, '_bbb_boyfriend_name', true);
	$reread     = get_post_meta($post_id, '_bbb_reread', true);
	$darkness   = get_post_meta($post_id, '_bbb_darkness', true);
	$ku_raw     = get_post_meta($post_id, '_bbb_ku', true);
	$ku         = ($ku_raw === '1') ? 'true' : (($ku_raw === '0') ? 'false' : '');

	$standalone_raw = get_post_meta($post_id, '_bbb_standalone', true);
	$standalone     = ($standalone_raw === '1') ? 'true' : 'false';
	$private        = bbb_is_book_private($post_id) ? 'true' : 'false';

	$series_handle = get_post_meta($post_id, '_bbb_series_handle', true);
	$series_number = get_post_meta($post_id, '_bbb_series_number', true);
	$series_name   = '';
	if ($series_handle) {
		$series_term = get_term_by('slug', $series_handle, 'bbb_series');
		if ($series_term) {
			$series_name = $series_term->name;
		}
	}

	$shelf_terms = get_the_terms($post_id, 'bbb_shelf');
	$shelf_name  = ($shelf_terms && !is_wp_error($shelf_terms)) ? $shelf_terms[0]->name : '';

	$trope_terms   = get_the_terms($post_id, 'bbb_trope');
	$trope_names   = array();
	$trope_display = array();
	$trope_urls    = array();
	if ($trope_terms && !is_wp_error($trope_terms)) {
		foreach ($trope_terms as $trope) {
			$emoji           = get_term_meta($trope->term_id, 'trope_emoji', true);
			$trope_link      = function_exists('bbb_book_taxonomy_term_url') ? bbb_book_taxonomy_term_url($trope) : get_term_link($trope);
			$trope_names[]   = $trope->name;
			$trope_display[] = function_exists('bbb_trope_label') ? bbb_trope_label($trope->name, $emoji) : trim(($emoji ? $emoji : '🖤') . ' ' . $trope->name);
			$trope_urls[]    = is_wp_error($trope_link) ? '' : $trope_link;
		}
	}

	$attrs = array(
		'data-handle'         => $slug,
		'data-url'            => get_permalink($post_id) ?: home_url('/books/' . $slug . '/'),
		'data-title'          => $title,
		'data-author'         => $author,
		'data-cover'          => $cover,
		'data-amazon'         => $amazon,
		'data-bookshop'       => $bookshop,
		'data-shelf'          => $shelf_name,
		'data-private-shelf'  => $private,
		'data-spice'          => $spice,
		'data-tropes'         => implode(', ', $trope_names),
		'data-tropes-display' => implode(', ', $trope_display),
		'data-trope-urls'     => implode(', ', $trope_urls),
		'data-why'            => $why,
		'data-newsletter'     => $newsletter,
		'data-mini'           => $mini,
		'data-series'         => $series_handle,
		'data-series-name'    => $series_name,
		'data-series-number'  => $series_number,
		'data-tension'        => $tension,
		'data-damage'         => $damage,
		'data-yearning'       => $yearning,
		'data-boyfriend'      => $bftype,
		'data-boyfriend-name' => $bfname,
		'data-reread'         => $reread,
		'data-standalone'     => $standalone,
		'data-ku'             => $ku,
		'data-darkness'       => $darkness,
	);

	$parts = array();
	foreach ($attrs as $key => $val) {
		$parts[] = $key . '="' . esc_attr((string) $val) . '"';
	}

	return implode("\n  ", $parts);
}

function bbb_render_library_book_card(int $post_id, bool $mini = false): string {
	if (!bbb_is_book_visible($post_id)) {
		return '';
	}

	$cover           = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($post_id) : get_post_meta($post_id, '_bbb_cover_url', true);
	$title           = get_the_title($post_id);
	$author          = get_post_meta($post_id, '_bbb_author', true);
	$spice           = (int) get_post_meta($post_id, '_bbb_spice', true);
	$series_handle   = get_post_meta($post_id, '_bbb_series_handle', true);
	$series_number   = get_post_meta($post_id, '_bbb_series_number', true);
	$standalone_raw  = get_post_meta($post_id, '_bbb_standalone', true);
	$is_standalone   = $standalone_raw === '1';
	$series_name     = '';
	$mini_class      = $mini ? ' sss-lib__book--mini' : '';
	$data_attrs      = bbb_get_book_data_attrs($post_id);
	$series_badge    = '';

	if ($series_handle) {
		$series_term = get_term_by('slug', $series_handle, 'bbb_series');
		if ($series_term) {
			$series_name = $series_term->name;
		}
	}

	if ($series_number !== '' && $series_number !== null) {
		$badge_class  = 'sss-lib__seriesBadge' . ($is_standalone ? ' sss-lib__seriesBadge--standalone' : '');
		$series_badge = sprintf(
			'<span class="%s" data-series-url="/series/%s/" aria-label="open series page for %s">%s</span>',
			esc_attr($badge_class),
			esc_attr((string) $series_handle),
			esc_attr($series_name),
			esc_html((string) $series_number)
		);
	}

	ob_start();
	?>
<button
  type="button"
  class="sss-lib__book<?php echo esc_attr($mini_class); ?>"
  <?php echo $data_attrs; ?>
>
  <div class="sss-lib__coverWrap">
    <span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
      <span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
      <span class="sss-lib__heartLabel" data-heart-label>save</span>
    </span>
    <?php echo $series_badge; ?>
    <?php if ($spice > 0) : ?>
    <div class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $spice)); ?></div>
    <?php endif; ?>
    <?php if ($cover) : ?>
    <img class="sss-lib__cover" src="<?php echo esc_url((string) $cover); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
    <?php endif; ?>
  </div>
  <div class="sss-lib__under">
    <div class="sss-lib__name"><?php echo esc_html($title); ?></div>
    <div class="sss-lib__author"><?php echo esc_html((string) $author); ?></div>
  </div>
</button>
	<?php
	return ob_get_clean();
}

function bbb_render_article_book_card(int $post_id, bool $show_why = false): string {
	$cover         = function_exists('bbb_get_book_cover_url') ? bbb_get_book_cover_url($post_id) : get_post_meta($post_id, '_bbb_cover_url', true);
	$title         = get_the_title($post_id);
	$author        = get_post_meta($post_id, '_bbb_author', true);
	$spice         = (int) get_post_meta($post_id, '_bbb_spice', true);
	$mini          = get_post_meta($post_id, '_bbb_mini_note', true);
	$why           = get_post_meta($post_id, '_bbb_why', true);
	$ku_raw        = get_post_meta($post_id, '_bbb_ku', true);
	$amazon        = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(get_post_meta($post_id, '_bbb_amazon_url', true)) : get_post_meta($post_id, '_bbb_amazon_url', true);
	$bookshop      = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(get_post_meta($post_id, '_bbb_bookshop_url', true)) : get_post_meta($post_id, '_bbb_bookshop_url', true);
	$series_handle = get_post_meta($post_id, '_bbb_series_handle', true);
	$series_number = get_post_meta($post_id, '_bbb_series_number', true);
	$series_name   = '';
	if ($series_handle) {
		$term = get_term_by('slug', $series_handle, 'bbb_series');
		if ($term) {
			$series_name = $term->name;
		}
	}

	$shelf_terms = get_the_terms($post_id, 'bbb_shelf');
	$shelf_name  = ($shelf_terms && !is_wp_error($shelf_terms)) ? $shelf_terms[0]->name : '';
	$trope_terms = get_the_terms($post_id, 'bbb_trope');
	$data_attrs  = bbb_get_book_data_attrs($post_id);

	ob_start();
	?>
<div class="article-book-card" data-book-preview
  <?php echo $data_attrs; ?>>

  <div class="article-book-card__header">
    <?php if ($shelf_name) : ?>
    <div class="article-book-card__genreRow">
      <span class="article-book-card__genreLine" aria-hidden="true"></span>
      <span class="article-book-card__genre"><?php echo esc_html($shelf_name); ?></span>
    </div>
    <?php endif; ?>
    <h3><?php echo esc_html($title); ?></h3>
    <?php if ($author) : ?>
    <div class="article-book-card__author"><?php echo esc_html((string) $author); ?></div>
    <?php endif; ?>
    <?php if ($series_name || $series_number) : ?>
    <div class="article-book-card__series">
      <?php if ($series_number) echo '#' . esc_html((string) $series_number) . ' • '; ?>
      <?php echo esc_html($series_name); ?><?php if ($series_name) echo ' series'; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="article-book-card__image">
    <button type="button" class="article-book-card__heart" data-blog-heart aria-label="save to your bookshelf">
      <span class="article-book-card__heartIcon" aria-hidden="true">♡</span>
      <span class="article-book-card__heartLabel">save</span>
    </button>
    <?php if ($spice > 0) : ?>
    <div class="article-book-card__spice"><?php echo esc_html(str_repeat('🌶', $spice)); ?></div>
    <?php endif; ?>
    <?php if ($cover) : ?>
    <img src="<?php echo esc_url((string) $cover); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
    <?php endif; ?>
  </div>

  <div class="article-book-card__content">
    <?php if ($mini) : ?>
    <p class="book-pitch"><?php echo esc_html((string) $mini); ?></p>
    <?php endif; ?>
    <?php if ($show_why && $why) : ?>
    <p class="book-pitch book-pitch--why">
      <span class="book-pitch__label">why i loved it</span>
      <?php echo esc_html((string) $why); ?>
    </p>
    <?php endif; ?>
    <?php if ($trope_terms && !is_wp_error($trope_terms)) : ?>
    <div class="article-book-card__tropes">
      <?php foreach ($trope_terms as $trope) : ?>
        <?php
        $trope_emoji = (string) get_term_meta($trope->term_id, 'trope_emoji', true);
        $trope_url   = function_exists('bbb_book_taxonomy_term_url') ? bbb_book_taxonomy_term_url($trope) : get_term_link($trope);
        ?>
      <a class="article-book-card__trope" href="<?php echo esc_url(is_wp_error($trope_url) ? '#' : $trope_url); ?>"><?php echo function_exists('bbb_trope_label_html') ? bbb_trope_label_html($trope->name, $trope_emoji, $trope->slug) : esc_html(trim(($trope_emoji ? $trope_emoji : '🖤') . ' ' . $trope->name)); ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="article-book-card__ratings">
      <?php if ($ku_raw === '1') : ?>
      <span class="article-book-card__ku article-book-card__ku--yes">✓ on kindle unlimited</span>
      <?php elseif ($ku_raw === '0') : ?>
      <span class="article-book-card__ku article-book-card__ku--no">✕ not on kindle unlimited</span>
      <?php endif; ?>
    </div>
    <div class="article-book-card__buttons">
      <?php if ($amazon && $ku_raw === '1') : ?>
      <a class="article-book-card__button article-book-card__button--ku" href="<?php echo esc_url((string) $amazon); ?>" target="_blank" rel="noopener">read free on kindle unlimited</a>
      <?php endif; ?>
      <?php if ($amazon) : ?>
      <a class="article-book-card__button article-book-card__button--amazon" href="<?php echo esc_url((string) $amazon); ?>" target="_blank" rel="noopener">buy on amazon <span>· own it forever</span></a>
      <?php endif; ?>
      <?php if ($bookshop) : ?>
      <a class="article-book-card__button article-book-card__button--bookshop" href="<?php echo esc_url((string) $bookshop); ?>" target="_blank" rel="noopener">prefer indie? bookshop.org →</a>
      <?php endif; ?>
    </div>
  </div>

</div>
	<?php
	return ob_get_clean();
}

function bbb_render_book_card(int $post_id, array $args = array()): string {
	$context  = $args['context'] ?? 'library';
	$mini     = (bool) ($args['mini'] ?? false);
	$show_why = (bool) ($args['show_why'] ?? false);

	if ($context === 'article') {
		return bbb_render_article_book_card($post_id, $show_why);
	}

	return bbb_render_library_book_card($post_id, $mini);
}

function bbb_render_article_trope_book_card(string $trope_slug, int $index = 1, bool $show_why = false): string {
	$query = new WP_Query(
		array(
			'post_type'      => 'bbb_book',
			'post_status'    => 'publish',
			'posts_per_page' => 250,
			'tax_query'      => array(
				array(
					'taxonomy' => 'bbb_trope',
					'field'    => 'slug',
					'terms'    => $trope_slug,
				),
			),
			'fields'         => 'ids',
		)
	);

	$match_count = 0;
	foreach ($query->posts as $pid) {
		if (!bbb_is_book_visible((int) $pid)) {
			continue;
		}

		$match_count++;
		if ($match_count === $index) {
			return bbb_render_article_book_card((int) $pid, $show_why);
		}
	}

	return '';
}
