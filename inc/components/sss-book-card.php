<?php
/**
 * Society library book card.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$book = $args['book'] ?? null;
if ($book instanceof WP_Post) {
	$book_id = $book->ID;
} else {
	$book_id = (int) $book;
}

if (!$book_id) {
	return;
}

$mini = !empty($args['mini']);

if ('bbb_book' === get_post_type($book_id) && function_exists('bbb_render_library_book_card')) {
	echo bbb_render_library_book_card($book_id, $mini);
	return;
}

if (!function_exists('bbb_sss_card_field')) {
	function bbb_sss_card_field(string $key, int $post_id, $default = '') {
		return function_exists('bbb_get_field') ? bbb_get_field($key, $post_id, $default) : get_post_meta($post_id, $key, true);
	}
}

if (!function_exists('bbb_sss_card_bool')) {
	function bbb_sss_card_bool($value): bool {
		return function_exists('bbb_truthy') ? bbb_truthy($value) : in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
	}
}

if (!function_exists('bbb_sss_card_cover_url')) {
	function bbb_sss_card_cover_url(int $post_id): string {
		if (function_exists('bbb_get_book_cover_url')) {
			return bbb_get_book_cover_url($post_id);
		}

		$cover = bbb_sss_card_field('cover', $post_id, '');

		if (is_array($cover)) {
			if (!empty($cover['url'])) {
				return (string) $cover['url'];
			}

			if (!empty($cover['ID'])) {
				return (string) wp_get_attachment_image_url((int) $cover['ID'], 'large');
			}
		}

		if (is_numeric($cover)) {
			$url = wp_get_attachment_image_url((int) $cover, 'large');
			if ($url) {
				return (string) $url;
			}
		}

		if ($cover && !(function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url((string) $cover))) {
			return (string) $cover;
		}

		$thumbnail = (string) (get_the_post_thumbnail_url($post_id, 'large') ?: '');

		return function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url($thumbnail) ? '' : $thumbnail;
	}
}

if (!function_exists('bbb_sss_card_tropes')) {
	function bbb_sss_card_tropes(int $post_id): array {
		$tropes = array();
		$terms  = get_the_terms($post_id, 'sss_trope');

		if ($terms && !is_wp_error($terms)) {
			foreach ($terms as $term) {
				$emoji    = function_exists('bbb_trope_emoji') ? bbb_trope_emoji(get_term_meta($term->term_id, 'emoji', true)) : (string) get_term_meta($term->term_id, 'emoji', true);
				$tropes[] = array(
					'name'  => $term->name,
					'emoji' => $emoji,
					'slug'  => $term->slug,
				);
			}
		}

		$meta_tropes = bbb_sss_card_field('tropes', $post_id, array());
		if (!$tropes && is_array($meta_tropes)) {
			foreach ($meta_tropes as $trope) {
				if (is_array($trope)) {
					$name = (string) ($trope['name'] ?? $trope['title'] ?? '');
					if (!$name) {
						continue;
					}

					$tropes[] = array(
						'name'  => $name,
						'emoji' => function_exists('bbb_trope_emoji') ? bbb_trope_emoji($trope['emoji'] ?? '') : (string) ($trope['emoji'] ?? '🖤'),
						'slug'  => sanitize_title((string) ($trope['slug'] ?? $name)),
					);
				} elseif (is_string($trope) && '' !== trim($trope)) {
					$tropes[] = array(
						'name'  => trim($trope),
						'emoji' => function_exists('bbb_trope_emoji') ? bbb_trope_emoji() : '🖤',
						'slug'  => sanitize_title($trope),
					);
				}
			}
		}

		return $tropes;
	}
}

if (!function_exists('bbb_sss_card_series_post')) {
	function bbb_sss_card_series_post($series_value): ?WP_Post {
		if ($series_value instanceof WP_Post) {
			return $series_value;
		}

		if (is_array($series_value) && isset($series_value[0])) {
			return bbb_sss_card_series_post($series_value[0]);
		}

		if (is_numeric($series_value)) {
			$series = get_post((int) $series_value);
			return $series instanceof WP_Post ? $series : null;
		}

		return null;
	}
}

$title         = (string) bbb_sss_card_field('title', $book_id, get_the_title($book_id));
$title         = '' !== $title ? $title : get_the_title($book_id);
$title         = function_exists('bbb_bookish_book_title') ? bbb_bookish_book_title($title) : $title;
$author        = function_exists('bbb_get_book_author') ? bbb_get_book_author($book_id) : (string) bbb_sss_card_field('author', $book_id, '');
$author        = function_exists('bbb_bookish_proper_name') ? bbb_bookish_proper_name((string) $author) : $author;
$cover_url     = bbb_sss_card_cover_url($book_id);
$amazon_link   = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(bbb_sss_card_field('amazon_link', $book_id, '')) : (string) bbb_sss_card_field('amazon_link', $book_id, '');
$bookshop_link = function_exists('bbb_normalize_url_value') ? bbb_normalize_url_value(bbb_sss_card_field('bookshop_link', $book_id, '')) : (string) bbb_sss_card_field('bookshop_link', $book_id, '');
$shelf         = (string) bbb_sss_card_field('shelf', $book_id, '');
$shelf_terms   = get_the_terms($book_id, 'sss_shelf');

if ('' === $shelf && $shelf_terms && !is_wp_error($shelf_terms)) {
	$shelf = $shelf_terms[0]->name;
}

$series_value  = bbb_sss_card_field('series', $book_id, '');
$series_post   = bbb_sss_card_series_post($series_value);
$series_handle = $series_post ? $series_post->post_name : sanitize_title((string) $series_value);
$series_name   = (string) bbb_sss_card_field('series_name', $book_id, '');

if ('' === $series_name && $series_post) {
	$series_name = get_the_title($series_post);
}

$series_number = (string) bbb_sss_card_field('series_number', $book_id, '');
$spice_level   = (int) bbb_sss_card_field('spice_level', $book_id, bbb_sss_card_field('book_spice_level', $book_id, 0));
$tropes        = bbb_sss_card_tropes($book_id);

$trope_names   = array_map(static fn(array $trope): string => $trope['name'], $tropes);
$trope_display = array_map(
	static function (array $trope): string {
		return function_exists('bbb_trope_label') ? bbb_trope_label($trope['name'], $trope['emoji'] ?? '') : trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . $trope['name']);
	},
	$tropes
);
$trope_urls    = array_map(
	static function (array $trope): string {
		$slug = sanitize_title($trope['slug'] ?: $trope['name']);

		return home_url('/' . ('-books' === substr($slug, -6) ? $slug : $slug . '-books') . '/');
	},
	$tropes
);

$is_private = bbb_sss_card_bool(bbb_sss_card_field('is_private', $book_id, false));
$standalone = bbb_sss_card_bool(bbb_sss_card_field('read_as_standalone', $book_id, bbb_sss_card_field('standalone', $book_id, false)));
?>
<button
	type="button"
	class="sss-lib__book<?php echo $mini ? ' sss-lib__book--mini' : ''; ?>"
	data-handle="<?php echo esc_attr(get_post_field('post_name', $book_id)); ?>"
	data-url="<?php echo esc_url(get_permalink($book_id)); ?>"
	data-title="<?php echo esc_attr($title); ?>"
	data-author="<?php echo esc_attr($author); ?>"
	data-cover="<?php echo esc_attr($cover_url); ?>"
	data-amazon="<?php echo esc_attr($amazon_link); ?>"
	data-bookshop="<?php echo esc_attr($bookshop_link); ?>"
	data-shelf="<?php echo esc_attr($shelf); ?>"
	data-private-shelf="<?php echo esc_attr($is_private ? 'true' : 'false'); ?>"
	data-spice="<?php echo esc_attr((string) $spice_level); ?>"
	data-tropes="<?php echo esc_attr(implode(', ', $trope_names)); ?>"
	data-tropes-display="<?php echo esc_attr(implode(', ', $trope_display)); ?>"
	data-trope-urls="<?php echo esc_attr(implode(', ', $trope_urls)); ?>"
	data-why="<?php echo esc_attr((string) bbb_sss_card_field('why_i_loved_it', $book_id, '')); ?>"
	data-newsletter="<?php echo esc_attr((string) bbb_sss_card_field('newsletter_url', $book_id, '')); ?>"
	data-mini="<?php echo esc_attr((string) bbb_sss_card_field('mini_note', $book_id, '')); ?>"
	data-series="<?php echo esc_attr($series_handle); ?>"
	data-series-name="<?php echo esc_attr($series_name); ?>"
	data-series-number="<?php echo esc_attr($series_number); ?>"
	data-tension="<?php echo esc_attr((string) bbb_sss_card_field('tension_score', $book_id, '')); ?>"
	data-damage="<?php echo esc_attr((string) bbb_sss_card_field('emotional_damage_score', $book_id, '')); ?>"
	data-yearning="<?php echo esc_attr((string) bbb_sss_card_field('yearning_level', $book_id, '')); ?>"
	data-boyfriend="<?php echo esc_attr((string) bbb_sss_card_field('boyfriend_type', $book_id, '')); ?>"
	data-boyfriend-name="<?php echo esc_attr((string) bbb_sss_card_field('boyfriend_name', $book_id, '')); ?>"
	data-reread="<?php echo esc_attr((string) bbb_sss_card_field('reread_badge', $book_id, '')); ?>"
	data-standalone="<?php echo esc_attr($standalone ? 'true' : 'false'); ?>"
	data-ku="<?php echo esc_attr(bbb_sss_card_bool(bbb_sss_card_field('on_kindle_unlimited', $book_id, false)) ? 'true' : 'false'); ?>"
	data-darkness="<?php echo esc_attr((string) bbb_sss_card_field('darkness_level', $book_id, '')); ?>"
>
	<div class="sss-lib__coverWrap">
		<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
			<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
			<span class="sss-lib__heartLabel" data-heart-label>save</span>
		</span>

		<?php if ('' !== $series_number) : ?>
			<span
				class="sss-lib__seriesBadge<?php echo $standalone ? ' sss-lib__seriesBadge--standalone' : ''; ?>"
				data-series-url="/series/<?php echo esc_attr($series_handle); ?>/"
				aria-label="open series page for <?php echo esc_attr($series_name); ?>"
			>
				<?php echo esc_html($series_number); ?>
			</span>
		<?php endif; ?>

		<?php if ($spice_level > 0) : ?>
			<div class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $spice_level)); ?></div>
		<?php endif; ?>

		<?php if ('' !== $cover_url) : ?>
			<img class="sss-lib__cover" src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
		<?php endif; ?>
	</div>

	<div class="sss-lib__under">
		<div class="sss-lib__name" style="text-transform:none !important;"><?php echo esc_html($title); ?></div>
		<div class="sss-lib__author" style="text-transform:none !important;"><?php echo esc_html($author); ?></div>
	</div>
</button>
