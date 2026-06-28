<?php
/**
 * Fictional man blog callout shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_fictionalman_shortcode_profile_from_name(string $name): ?WP_Post {
	$name = trim(wp_strip_all_tags($name));
	if ('' === $name || !post_type_exists('bbb_boyfriend')) {
		return null;
	}

	$slug = sanitize_title($name);
	if ('' !== $slug) {
		$profile = get_page_by_path($slug, OBJECT, 'bbb_boyfriend');
		if ($profile instanceof WP_Post && 'publish' === get_post_status($profile)) {
			return $profile;
		}
	}

	$profiles = get_posts(
		array(
			'post_type'              => 'bbb_boyfriend',
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$needle = function_exists('sss_article_match_text') ? sss_article_match_text($name) : strtolower($name);
	foreach ($profiles as $profile) {
		if (!$profile instanceof WP_Post) {
			continue;
		}

		$title = function_exists('sss_article_match_text') ? sss_article_match_text(get_the_title($profile)) : strtolower(get_the_title($profile));
		if ($needle === $title) {
			return $profile;
		}
	}

	return null;
}

function bbb_fictionalman_shortcode($atts): string {
	$atts = shortcode_atts(
		array(
			'name' => '',
		),
		(array) $atts,
		'bbb_fictionalman'
	);

	$profile = bbb_fictionalman_shortcode_profile_from_name((string) $atts['name']);
	if (!$profile instanceof WP_Post) {
		return '';
	}

	$name = get_the_title($profile);
	$url  = get_permalink($profile);
	if (!$url) {
		return '';
	}
	$image = get_the_post_thumbnail_url($profile, 'medium_large');

	ob_start();
	?>
	<a class="bbb-fictionalman-callout" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr('view the full fictional man profile for ' . $name); ?>">
		<div class="bbb-fictionalman-callout__portrait">
			<?php if ($image) : ?>
				<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
			<?php else : ?>
				<span aria-hidden="true">&hearts;</span>
			<?php endif; ?>
		</div>
		<div class="bbb-fictionalman-callout__body">
			<p class="bbb-fictionalman-callout__kicker">meet the fictional man</p>
			<div class="bbb-fictionalman-callout__name"><?php echo esc_html($name); ?></div>
			<span class="bbb-fictionalman-callout__link">view full profile</span>
		</div>
	</a>
	<?php
	return (string) ob_get_clean();
}
add_shortcode('bbb_fictionalman', 'bbb_fictionalman_shortcode');
