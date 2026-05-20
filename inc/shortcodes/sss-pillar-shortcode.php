<?php
/**
 * Pillar article shortcodes.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_spice_shortcode($atts): string {
	$atts = shortcode_atts(array('level' => 1), $atts, 'sss_spice');
	$level = max(1, min(5, (int) $atts['level']));
	$map = array(
		1 => array('soft open door', 'for tension, tenderness, and the kind of romance that lets the feelings do most of the damage.', '🌶'),
		2 => array('some heat', 'a little more touch, a little more trouble, and enough heat to make the chemistry impossible to ignore.', '🌶 🌶'),
		3 => array('balanced', 'the sweet spot: plot, obsession, feelings, and heat all pulling their weight.', '🌶 🌶 🌶'),
		4 => array('high spice', 'do not read in public unless you enjoy pretending your screen brightness is not the problem.', '🌶 🌶 🌶 🌶'),
		5 => array('wreck me', 'the cancel-your-plans shelf. high heat, high chaos, and absolutely no emotional safety equipment.', '🌶 🌶 🌶 🌶 🌶'),
	);
	$data = $map[$level];

	return sprintf(
		'<section class="blog-pillar-spice" id="spice-%1$d"><div class="blog-pillar-spice__line" aria-hidden="true"></div><div class="blog-pillar-spice__head"><div><p class="blog-pillar-spice__label">spice %1$d</p><h2 class="blog-pillar-spice__title">%2$s</h2></div><div class="blog-pillar-spice__heat" aria-label="%4$s spice">%4$s</div></div><p class="blog-pillar-spice__copy">%3$s</p></section>',
		$level,
		esc_html($data[0]),
		esc_html($data[1]),
		esc_html($data[2])
	);
}
add_shortcode('sss_spice', 'sss_spice_shortcode');

function sss_pillar_nav_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID()), $atts, 'sss_pillar_nav');
	$post_id = (int) $atts['post_id'];
	$books = sss_article_books_for_post($post_id);
	if (!$books) {
		return '';
	}

	$counts = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0);
	$labels = array(
		1 => array('🌶 1', 'soft open door'),
		2 => array('🌶🌶 2', 'some heat'),
		3 => array('🌶🌶🌶 3', 'balanced'),
		4 => array('🌶🌶🌶🌶 4', 'high spice'),
		5 => array('🌶🌶🌶🌶🌶 5', 'wreck me'),
	);
	foreach ($books as $book) {
		$level = max(1, min(5, (int) sss_article_field('spice_level', $book->ID, 0)));
		$counts[$level]++;
	}

	$label = 'romance';
	if (function_exists('get_field')) {
		$guide_cat = sss_article_post(get_field('guide_category', $post_id));
		$trope     = sss_article_post(get_field('trope', $post_id));
		if ($guide_cat instanceof WP_Post) {
			$label = get_the_title($guide_cat);
		} elseif ($trope instanceof WP_Post) {
			$label = get_the_title($trope);
		}
	}
	if ('romance' === $label && function_exists('sss_article_match_text')) {
		$context = sss_article_match_text((string) get_the_title($post_id) . ' ' . (string) get_post_field('post_name', $post_id));
		foreach (array('dark romance', 'sports romance', 'mafia romance', 'hockey romance', 'romantasy', 'paranormal romance', 'contemporary romance', 'historical romance', 'dystopian romance') as $candidate) {
			if (str_contains(' ' . $context . ' ', ' ' . $candidate . ' ')) {
				$label = $candidate;
				break;
			}
		}
	}
	$label = trim(strtolower(wp_strip_all_tags((string) $label))) ?: 'romance';
	$total = array_sum($counts);

	ob_start();
	?>
<nav class="blog-pillar-nav" aria-label="pillar guide spice navigation">
  <div class="blog-pillar-nav__eyebrow"><?php echo esc_html($label); ?> pillar guide</div>
  <div class="blog-pillar-nav__header">
    <h2 class="blog-pillar-nav__title">choose your <?php echo esc_html($label); ?> spice level</h2>
    <?php if ($total > 0) : ?>
      <p class="blog-pillar-nav__count"><?php echo esc_html((string) $total); ?> books, sorted by heat instead of chaos.</p>
    <?php endif; ?>
  </div>
  <div class="blog-pillar-nav__links">
  <?php foreach ($counts as $level => $count) : ?>
    <a class="blog-pillar-nav__link<?php echo $count > 0 ? '' : ' is-disabled'; ?>"<?php echo $count > 0 ? ' href="#spice-' . esc_attr((string) $level) . '"' : ' aria-disabled="true" tabindex="-1"'; ?>>
      <span><?php echo esc_html($labels[$level][0]); ?></span>
      <?php echo esc_html($labels[$level][1]); ?>
    </a>
  <?php endforeach; ?>
  </div>
</nav>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_pillar_nav', 'sss_pillar_nav_shortcode');
add_shortcode('pillar', 'sss_pillar_nav_shortcode');
add_shortcode('pillarnav', 'sss_pillar_nav_shortcode');
add_shortcode('pillar_nav', 'sss_pillar_nav_shortcode');
