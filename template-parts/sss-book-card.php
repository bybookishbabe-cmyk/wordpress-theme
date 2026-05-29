<?php
/**
 * Reusable Society Library book card for series pages.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$book = $args['book'] ?? null;

if (!function_exists('bbb_series_bool')) {
	function bbb_series_bool($value): bool {
		if (is_bool($value)) {
			return $value;
		}

		if (is_numeric($value)) {
			return 1 === (int) $value;
		}

		return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on'), true);
	}
}

if (!function_exists('bbb_series_field')) {
	function bbb_series_field(int $post_id, string $key, $default = '') {
		$candidates = array($key, 'sss_' . $key);
		$sss_map    = array(
			'amazon_link'              => 'sss_amazon',
			'bookshop_link'            => 'sss_bookshop',
			'cover'                    => 'sss_cover_url',
			'darkness_level'           => 'sss_darkness',
			'emotional_damage_score'   => 'sss_damage',
			'mini_note'                => 'sss_mini',
			'newsletter_url'           => 'sss_newsletter',
			'on_kindle_unlimited'      => 'sss_ku',
			'read_as_standalone'       => 'sss_standalone',
			'reread_badge'             => 'sss_reread',
			'spice_level'              => 'sss_spice',
			'tension_score'            => 'sss_tension',
			'why_i_loved_it'           => 'sss_why',
		);
		$bbb_map    = array(
			'amazon_link'              => '_bbb_amazon_url',
			'author'                   => '_bbb_author',
			'bookshop_link'            => '_bbb_bookshop_url',
			'boyfriend_name'           => '_bbb_boyfriend_name',
			'boyfriend_type'           => '_bbb_boyfriend_type',
			'cover'                    => '_bbb_cover_url',
			'darkness_level'           => '_bbb_darkness',
			'emotional_damage_score'   => '_bbb_damage',
			'mini_note'                => '_bbb_mini_note',
			'newsletter_url'           => '_bbb_newsletter_url',
			'on_kindle_unlimited'      => '_bbb_ku',
			'read_as_standalone'       => '_bbb_standalone',
			'reread_badge'             => '_bbb_reread',
			'series_number'            => '_bbb_series_number',
			'spice_level'              => '_bbb_spice',
			'tension_score'            => '_bbb_tension',
			'why_i_loved_it'           => '_bbb_why',
			'yearning_level'           => '_bbb_yearning',
		);

		if (isset($sss_map[$key])) {
			$candidates[] = $sss_map[$key];
		}

		if (isset($bbb_map[$key])) {
			$candidates[] = $bbb_map[$key];
		}

		foreach (array_unique($candidates) as $candidate) {
			$value = get_post_meta($post_id, $candidate, true);
			if ('' !== $value && null !== $value) {
				return $value;
			}

			if (function_exists('get_field')) {
				$field = get_field($candidate, $post_id);
				if ('' !== $field && null !== $field && false !== $field) {
					return $field;
				}
			}
		}

		return $default;
	}
}

if (!function_exists('bbb_series_image_url')) {
	function bbb_series_image_url($image, int $post_id = 0): string {
		if (is_array($image)) {
			if (!empty($image['url'])) {
				return (string) $image['url'];
			}

			if (!empty($image['ID'])) {
				return (string) wp_get_attachment_image_url((int) $image['ID'], 'large');
			}
		}

		if (is_numeric($image)) {
			$url = wp_get_attachment_image_url((int) $image, 'large');
			if ($url) {
				return (string) $url;
			}
		}

		if (is_string($image) && '' !== trim($image) && !(function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url($image))) {
			return $image;
		}

		if (function_exists('bbb_get_book_cover_url') && $post_id > 0) {
			return bbb_get_book_cover_url($post_id);
		}

		$thumbnail = $post_id ? (string) (get_the_post_thumbnail_url($post_id, 'large') ?: '') : '';

		return function_exists('bbb_is_site_logo_url') && bbb_is_site_logo_url($thumbnail) ? '' : $thumbnail;
	}
}

if (!function_exists('bbb_series_url_value')) {
	function bbb_series_url_value($value): string {
		if (is_array($value)) {
			foreach (array('url', 'href', 'online_store_url') as $key) {
				if (!empty($value[$key])) {
					return (string) $value[$key];
				}
			}

			return '';
		}

		return is_string($value) || is_numeric($value) ? trim((string) $value) : '';
	}
}

if (!function_exists('bbb_series_book_terms')) {
	function bbb_series_book_terms(int $post_id, string $taxonomy): array {
		if (!taxonomy_exists($taxonomy)) {
			return array();
		}

		$terms = get_the_terms($post_id, $taxonomy);

		return ($terms && !is_wp_error($terms)) ? array_values($terms) : array();
	}
}

if (!function_exists('bbb_series_book_data')) {
	function bbb_series_book_data(WP_Post $book): array {
		$post_id      = $book->ID;
		$is_bbb       = 'bbb_book' === $book->post_type;
		$series_terms = bbb_series_book_terms($post_id, $is_bbb ? 'bbb_series' : 'sss_series');
		$series_term  = $series_terms[0] ?? null;
		$shelf_terms  = bbb_series_book_terms($post_id, $is_bbb ? 'bbb_shelf' : 'sss_shelf');
		$trope_terms  = bbb_series_book_terms($post_id, $is_bbb ? 'bbb_trope' : 'sss_trope');
		$tropes       = array();

		foreach ($trope_terms as $term) {
			$tropes[] = array(
				'name'   => $term->name,
				'emoji'  => function_exists('bbb_trope_emoji') ? bbb_trope_emoji(get_term_meta($term->term_id, $is_bbb ? 'trope_emoji' : 'emoji', true)) : (string) get_term_meta($term->term_id, $is_bbb ? 'trope_emoji' : 'emoji', true),
				'handle' => $term->slug,
			);
		}

		if (!$tropes) {
			$raw_tropes = bbb_series_field($post_id, 'tropes', array());
			if (is_array($raw_tropes)) {
				foreach ($raw_tropes as $trope) {
					if (!is_array($trope)) {
						continue;
					}

					$name = (string) ($trope['name'] ?? $trope['title'] ?? $trope['sss_trope_name'] ?? '');
					if ('' === trim($name)) {
						continue;
					}

					$tropes[] = array(
						'name'   => $name,
						'emoji'  => function_exists('bbb_trope_emoji') ? bbb_trope_emoji($trope['emoji'] ?? $trope['sss_trope_emoji'] ?? '') : (string) ($trope['emoji'] ?? $trope['sss_trope_emoji'] ?? '🖤'),
						'handle' => (string) ($trope['handle'] ?? $trope['slug'] ?? sanitize_title($name)),
					);
				}
			}
		}

		$series_handle = $series_term instanceof WP_Term ? $series_term->slug : (string) bbb_series_field($post_id, 'series_handle', '');
		if ('' === $series_handle && $is_bbb) {
			$series_handle = (string) get_post_meta($post_id, '_bbb_series_handle', true);
		}
		if ('' === $series_handle) {
			$series_handle = sanitize_title((string) bbb_series_field($post_id, 'series', ''));
		}

		$series_name = $series_term instanceof WP_Term ? $series_term->name : (string) bbb_series_field($post_id, 'series_name', '');
		if ('' === $series_name && '' !== $series_handle && taxonomy_exists('bbb_series')) {
			$series_lookup = get_term_by('slug', $series_handle, 'bbb_series');
			if ($series_lookup instanceof WP_Term) {
				$series_name = $series_lookup->name;
			}
		}
		$cover       = bbb_series_image_url(bbb_series_field($post_id, 'cover', ''), $post_id);

		return array(
			'handle'         => $book->post_name,
			'title'          => (string) bbb_series_field($post_id, 'title', get_the_title($post_id)),
			'author'         => (string) bbb_series_field($post_id, 'author', ''),
			'cover'          => $cover,
			'amazon'         => bbb_series_url_value(bbb_series_field($post_id, 'amazon_link', '')),
			'bookshop'       => bbb_series_url_value(bbb_series_field($post_id, 'bookshop_link', '')),
			'shelf'          => $shelf_terms ? $shelf_terms[0]->name : (string) bbb_series_field($post_id, 'shelf', ''),
			'is_private'     => $is_bbb && function_exists('bbb_is_book_private') ? bbb_is_book_private($post_id) : (function_exists('sss_book_is_private') ? sss_book_is_private($post_id) : bbb_series_bool(bbb_series_field($post_id, 'is_private', false))),
			'spice'          => (int) bbb_series_field($post_id, 'spice_level', 0),
			'tropes'         => $tropes,
			'why'            => (string) bbb_series_field($post_id, 'why_i_loved_it', ''),
			'newsletter'     => bbb_series_url_value(bbb_series_field($post_id, 'newsletter_url', '')),
			'mini'           => (string) bbb_series_field($post_id, 'mini_note', ''),
			'series_handle'  => $series_handle,
			'series_name'    => $series_name,
			'series_number'  => (string) bbb_series_field($post_id, 'series_number', ''),
			'tension'        => (string) bbb_series_field($post_id, 'tension_score', ''),
			'damage'         => (string) bbb_series_field($post_id, 'emotional_damage_score', ''),
			'yearning'       => (string) bbb_series_field($post_id, 'yearning_level', ''),
			'boyfriend'      => (string) bbb_series_field($post_id, 'boyfriend_type', ''),
			'boyfriend_name' => (string) bbb_series_field($post_id, 'boyfriend_name', ''),
			'reread'         => bbb_series_field($post_id, 'reread_badge', ''),
			'standalone'     => bbb_series_bool(bbb_series_field($post_id, 'read_as_standalone', false)),
			'ku'             => bbb_series_bool(bbb_series_field($post_id, 'on_kindle_unlimited', false)),
			'darkness'       => (string) bbb_series_field($post_id, 'darkness_level', ''),
		);
	}
}

if (!$book instanceof WP_Post) {
	return;
}

$data          = bbb_series_book_data($book);
$trope_names   = array_map(static fn(array $trope): string => $trope['name'], $data['tropes']);
$trope_display = array_map(
	static fn(array $trope): string => function_exists('bbb_trope_label') ? bbb_trope_label($trope['name'], $trope['emoji'] ?? '') : trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . $trope['name']),
	$data['tropes']
);
$trope_urls    = array_map(
	static function (array $trope): string {
		$handle = sanitize_title((string) ($trope['handle'] ?: $trope['name']));

		return '/' . ('-books' === substr($handle, -6) ? $handle : $handle . '-books');
	},
	$data['tropes']
);
?>
<button
	type="button"
	class="sss-lib__book"
	data-handle="<?php echo esc_attr($data['handle']); ?>"
	data-url="<?php echo esc_url($data['url'] ?? ('/books/' . rawurlencode((string) $data['handle']) . '/')); ?>"
	data-title="<?php echo esc_attr($data['title']); ?>"
	data-author="<?php echo esc_attr($data['author']); ?>"
	data-cover="<?php echo esc_attr($data['cover']); ?>"
	data-amazon="<?php echo esc_attr($data['amazon']); ?>"
	data-bookshop="<?php echo esc_attr($data['bookshop']); ?>"
	data-shelf="<?php echo esc_attr($data['shelf']); ?>"
	data-private-shelf="<?php echo esc_attr($data['is_private'] ? 'true' : 'false'); ?>"
	data-spice="<?php echo esc_attr((string) $data['spice']); ?>"
	data-tropes="<?php echo esc_attr(implode(', ', $trope_names)); ?>"
	data-tropes-display="<?php echo esc_attr(implode(', ', $trope_display)); ?>"
	data-trope-urls="<?php echo esc_attr(implode(', ', $trope_urls)); ?>"
	data-why="<?php echo esc_attr($data['why']); ?>"
	data-newsletter="<?php echo esc_attr($data['newsletter']); ?>"
	data-mini="<?php echo esc_attr($data['mini']); ?>"
	data-series="<?php echo esc_attr($data['series_handle']); ?>"
	data-series-name="<?php echo esc_attr($data['series_name']); ?>"
	data-series-number="<?php echo esc_attr($data['series_number']); ?>"
	data-tension="<?php echo esc_attr($data['tension']); ?>"
	data-damage="<?php echo esc_attr($data['damage']); ?>"
	data-yearning="<?php echo esc_attr($data['yearning']); ?>"
	data-boyfriend="<?php echo esc_attr($data['boyfriend']); ?>"
	data-boyfriend-name="<?php echo esc_attr($data['boyfriend_name']); ?>"
	data-reread="<?php echo esc_attr((string) $data['reread']); ?>"
	data-standalone="<?php echo esc_attr($data['standalone'] ? 'true' : 'false'); ?>"
	data-ku="<?php echo esc_attr($data['ku'] ? 'true' : 'false'); ?>"
	data-darkness="<?php echo esc_attr($data['darkness']); ?>"
>
	<div class="sss-lib__coverWrap">
		<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
			<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
			<span class="sss-lib__heartLabel" data-heart-label>save</span>
		</span>

		<?php if ('' !== $data['series_number']) : ?>
			<span class="sss-lib__seriesBadge" data-series-url="/series/?series=<?php echo esc_attr($data['series_handle']); ?>" aria-label="open series page for <?php echo esc_attr($data['series_name']); ?>">
				<?php echo esc_html($data['series_number']); ?>
			</span>
		<?php endif; ?>

		<?php if ($data['spice'] > 0) : ?>
			<div class="sss-lib__floatSpice"><?php echo esc_html(str_repeat('🌶', $data['spice'])); ?></div>
		<?php endif; ?>

		<?php if ('' !== $data['cover']) : ?>
			<img class="sss-lib__cover" src="<?php echo esc_url($data['cover']); ?>" alt="<?php echo esc_attr($data['title']); ?>" loading="lazy">
		<?php endif; ?>
	</div>

	<div class="sss-lib__under">
		<div class="sss-lib__name"><?php echo esc_html($data['title']); ?></div>
		<div class="sss-lib__author"><?php echo esc_html($data['author']); ?></div>
	</div>
</button>
