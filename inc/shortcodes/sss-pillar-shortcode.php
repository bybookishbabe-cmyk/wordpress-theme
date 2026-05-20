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
		'<div class="pillar-bookcard__spiceSection" id="spice-%1$d"><div class="pillar-bookcard__spiceLabel">spice %1$d</div><h3 class="pillar-bookcard__spiceTitle">%2$s</h3><p class="pillar-bookcard__spiceCopy">%3$s</p><div class="pillar-bookcard__heat" aria-hidden="true">%4$s</div></div>',
		$level,
		esc_html($data[0]),
		esc_html($data[1]),
		esc_html($data[2])
	);
}
add_shortcode('sss_spice', 'sss_spice_shortcode');

function sss_pillar_nav_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID()), $atts, 'sss_pillar_nav');
	$books = sss_article_books_for_post((int) $atts['post_id']);
	if (!$books) {
		return '';
	}

	$levels = array();
	foreach ($books as $book) {
		$level = max(1, min(5, (int) sss_article_field('spice_level', $book->ID, 0)));
		$levels[$level] = true;
	}
	ksort($levels);

	ob_start();
	?>
<nav class="pillar-bookcard__nav" aria-label="spice level navigation">
  <?php foreach (array_keys($levels) as $level) : ?>
  <a href="#spice-<?php echo esc_attr((string) $level); ?>">spice <?php echo esc_html((string) $level); ?></a>
  <?php endforeach; ?>
</nav>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_pillar_nav', 'sss_pillar_nav_shortcode');
add_shortcode('pillar', 'sss_pillar_nav_shortcode');
add_shortcode('pillarnav', 'sss_pillar_nav_shortcode');
add_shortcode('pillar_nav', 'sss_pillar_nav_shortcode');
