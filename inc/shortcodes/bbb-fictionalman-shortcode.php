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
	$needle_compact = preg_replace('/[^a-z0-9]+/', '', $needle) ?? $needle;
	foreach ($profiles as $profile) {
		if (!$profile instanceof WP_Post) {
			continue;
		}

		$title = function_exists('sss_article_match_text') ? sss_article_match_text(get_the_title($profile)) : strtolower(get_the_title($profile));
		$title_compact = preg_replace('/[^a-z0-9]+/', '', $title) ?? $title;
		$slug_compact = str_replace('-', '', $profile->post_name);
		if (
			$needle === $title ||
			$needle_compact === $title_compact ||
			$needle_compact === $slug_compact ||
			('' !== $needle_compact && (str_starts_with($title_compact, $needle_compact) || str_starts_with($slug_compact, $needle_compact)))
		) {
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

function bbb_fictionalman_swipe_names_from_content(string $content): array {
	$names = array();

	if (preg_match_all('/\[fictionalman:([^\]\r\n]+)\]/i', $content, $matches)) {
		foreach ($matches[1] as $match) {
			$names[] = trim(wp_strip_all_tags((string) $match), " \t\n\r\0\x0B\"'");
		}
	}

	if (preg_match_all('/\[bbb_fictionalman\s+name=(["\'])([^"\']+)\1[^\]]*\]/i', $content, $matches)) {
		foreach ($matches[2] as $match) {
			$names[] = trim(wp_strip_all_tags((string) $match), " \t\n\r\0\x0B\"'");
		}
	}

	if (!$names) {
		$names = preg_split('/\s*(?:,|\||;|\R)\s*/', trim(wp_strip_all_tags($content))) ?: array();
	}

	return array_values(array_filter(array_map('trim', $names)));
}

function bbb_fictionalman_swipe_shortcode($atts, ?string $content = null): string {
	$names = bbb_fictionalman_swipe_names_from_content((string) $content);
	if (!$names) {
		return '';
	}

	ob_start();
	?>
	<section class="bbb-fictionalman-swipe" aria-label="swipe through fictional men">
		<div class="bbb-fictionalman-swipe__rail" tabindex="0">
			<?php foreach ($names as $name) : ?>
				<div class="bbb-fictionalman-swipe__item">
					<?php echo bbb_fictionalman_shortcode(array('name' => $name)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode('bbb_fictionalman_swipe', 'bbb_fictionalman_swipe_shortcode');
add_shortcode('fictionalmanswipe', 'bbb_fictionalman_swipe_shortcode');
