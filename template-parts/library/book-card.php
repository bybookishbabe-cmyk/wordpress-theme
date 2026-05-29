<?php
/**
 * Public library book card.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$post = $args['post'] ?? null;
$mini = !empty($args['mini']);

if (!$post instanceof WP_Post) {
	return;
}

if ('bbb_book' === $post->post_type && function_exists('bbb_render_library_book_card')) {
	echo bbb_render_library_book_card($post->ID, $mini);
	return;
}

$data          = sss_book_data($post);
$trope_names   = array_column($data['tropes'], 'name');
$trope_display = array_map(
	static fn(array $trope): string => function_exists('bbb_trope_label') ? bbb_trope_label($trope['name'], $trope['emoji'] ?? '') : trim(((string) ($trope['emoji'] ?? '') ?: '🖤') . ' ' . $trope['name']),
	$data['tropes']
);
$trope_urls    = array_map(
	static function (array $trope): string {
		$handle = sanitize_title((string) ($trope['handle'] ?: $trope['name']));

		return home_url('/' . $handle . '-books/');
	},
	$data['tropes']
);
?>
<button
	type="button"
	class="sss-lib__book<?php echo $mini ? ' sss-lib__book--mini' : ''; ?>"
	data-handle="<?php echo esc_attr($data['handle']); ?>"
	data-url="<?php echo esc_url($data['url'] ?? get_permalink($post)); ?>"
	data-title="<?php echo esc_attr($data['title']); ?>"
	data-author="<?php echo esc_attr($data['author']); ?>"
	data-cover="<?php echo esc_url($data['cover']); ?>"
	data-amazon="<?php echo esc_url($data['amazon']); ?>"
	data-bookshop="<?php echo esc_url($data['bookshop']); ?>"
	data-shelf="<?php echo esc_attr($data['shelf']); ?>"
	data-private-shelf="<?php echo $data['is_private'] ? 'true' : 'false'; ?>"
	data-spice="<?php echo esc_attr((string) $data['spice']); ?>"
	data-tropes="<?php echo esc_attr(implode(', ', $trope_names)); ?>"
	data-tropes-display="<?php echo esc_attr(implode(', ', $trope_display)); ?>"
	data-trope-urls="<?php echo esc_attr(implode(', ', $trope_urls)); ?>"
	data-why="<?php echo esc_attr($data['why']); ?>"
	data-newsletter="<?php echo esc_url($data['newsletter']); ?>"
	data-mini="<?php echo esc_attr($data['mini']); ?>"
	data-series="<?php echo esc_attr($data['series_handle']); ?>"
	data-series-name="<?php echo esc_attr($data['series_name']); ?>"
	data-series-number="<?php echo esc_attr((string) $data['series_number']); ?>"
	data-tension="<?php echo esc_attr((string) $data['tension']); ?>"
	data-damage="<?php echo esc_attr((string) $data['damage']); ?>"
	data-yearning="<?php echo esc_attr($data['yearning']); ?>"
	data-boyfriend="<?php echo esc_attr($data['boyfriend']); ?>"
	data-boyfriend-name="<?php echo esc_attr($data['boyfriend_name']); ?>"
	data-reread="<?php echo $data['reread'] ? 'true' : 'false'; ?>"
	data-standalone="<?php echo $data['standalone'] ? 'true' : 'false'; ?>"
	data-ku="<?php echo $data['ku'] ? 'true' : 'false'; ?>"
	data-darkness="<?php echo esc_attr((string) $data['darkness']); ?>"
>
	<div class="sss-lib__coverWrap">
		<span class="sss-lib__heart" data-heart role="button" aria-label="save to your bookshelf">
			<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
			<span class="sss-lib__heartLabel" data-heart-label>save</span>
		</span>

		<?php if ($data['series_number']) : ?>
			<span
				class="sss-lib__seriesBadge<?php echo $data['standalone'] ? ' sss-lib__seriesBadge--standalone' : ''; ?>"
				data-series-url="/series/?series=<?php echo esc_attr($data['series_handle']); ?>"
				aria-label="open series page"
			>
				<?php echo esc_html((string) (int) $data['series_number']); ?>
			</span>
		<?php endif; ?>

		<?php if ($data['spice']) : ?>
			<div class="sss-lib__floatSpice">
				<?php echo esc_html(str_repeat('🌶', (int) $data['spice'])); ?>
			</div>
		<?php endif; ?>

		<?php if ($data['cover']) : ?>
			<img class="sss-lib__cover" src="<?php echo esc_url($data['cover']); ?>" alt="<?php echo esc_attr($data['title']); ?>" loading="lazy">
		<?php endif; ?>
	</div>

	<div class="sss-lib__under">
		<div class="sss-lib__name"><?php echo esc_html($data['title']); ?></div>
		<div class="sss-lib__author"><?php echo esc_html($data['author']); ?></div>
	</div>
</button>
