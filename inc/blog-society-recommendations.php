<?php
/**
 * Manual "the society recommends" links for blog posts.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_society_recommendation_meta_key(): string {
	return '_bbb_society_recommendations';
}

function bbb_society_recommendation_options(string $post_type): array {
	return get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => array('publish', 'draft', 'pending', 'private'),
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
}

function bbb_society_recommendation_shortcut_options(): array {
	$shortcuts = array(
		array(
			'label' => __('sports romance', 'bybookishbabe-shopify-port'),
			'slug'  => 'sports-romance-books',
		),
		array(
			'label' => __('hockey romance', 'bybookishbabe-shopify-port'),
			'slug'  => 'the-best-hockey-romance-books',
		),
	);
	$options   = array();

	foreach ($shortcuts as $shortcut) {
		$item = get_page_by_path((string) $shortcut['slug'], OBJECT, array('post', 'page'));
		if (!$item instanceof WP_Post || !in_array(get_post_status($item), array('publish', 'draft', 'pending', 'private'), true)) {
			continue;
		}

		$options[] = array(
			'label' => (string) $shortcut['label'],
			'post'  => $item,
		);
	}

	return $options;
}

function bbb_society_recommendation_selected(int $post_id): array {
	$stored = get_post_meta($post_id, bbb_society_recommendation_meta_key(), true);

	if (!is_array($stored)) {
		$stored = array();
	}

	return array_slice(
		array_values(
			array_filter(
				array_map(
					static function ($value): string {
						if (!is_string($value)) {
							return '';
						}

						return preg_match('/^(post|page):\d+$/', $value) ? $value : '';
					},
					$stored
				)
			)
		),
		0,
		3
	);
}

function bbb_society_recommendations_add_meta_box(): void {
	add_meta_box(
		'bbb_society_recommendations',
		__('The society recommends', 'bybookishbabe-shopify-port'),
		'bbb_society_recommendations_render_meta_box',
		'post',
		'side',
		'default'
	);
}
add_action('add_meta_boxes_post', 'bbb_society_recommendations_add_meta_box');

function bbb_society_recommendations_render_meta_box(WP_Post $post): void {
	$selected        = bbb_society_recommendation_selected((int) $post->ID);
	$shortcut_items  = bbb_society_recommendation_shortcut_options();
	$shortcut_values = array();
	$posts           = bbb_society_recommendation_options('post');
	$pages           = bbb_society_recommendation_options('page');

	wp_nonce_field('bbb_society_recommendations_save', 'bbb_society_recommendations_nonce');
	?>
	<p style="margin-top:0;">
		<?php esc_html_e('Pick up to three posts or pages to link at the bottom of this blog post.', 'bybookishbabe-shopify-port'); ?>
	</p>
	<?php if ($shortcut_items) : ?>
		<p style="margin:8px 0 12px;">
			<span style="display:block;font-weight:600;margin-bottom:6px;"><?php esc_html_e('Quick picks', 'bybookishbabe-shopify-port'); ?></span>
			<?php foreach ($shortcut_items as $shortcut_item) : ?>
				<?php
				$linked_item = $shortcut_item['post'];
				if ((int) $linked_item->ID === (int) $post->ID) {
					continue;
				}
				$value = get_post_type($linked_item) . ':' . $linked_item->ID;
				?>
				<button type="button" class="button button-small bbb-society-recommendation-quick-pick" data-bbb-recommendation-value="<?php echo esc_attr($value); ?>" style="margin:0 4px 4px 0;">
					<?php echo esc_html((string) $shortcut_item['label']); ?>
				</button>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>
	<?php for ($index = 0; $index < 3; $index++) : ?>
		<p>
			<label for="bbb-society-recommendation-<?php echo esc_attr((string) $index); ?>" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php echo esc_html(sprintf(__('Recommendation %d', 'bybookishbabe-shopify-port'), $index + 1)); ?>
			</label>
			<select id="bbb-society-recommendation-<?php echo esc_attr((string) $index); ?>" name="bbb_society_recommendations[]" style="width:100%;">
				<option value=""><?php esc_html_e('None selected', 'bybookishbabe-shopify-port'); ?></option>
				<?php if ($shortcut_items) : ?>
					<optgroup label="<?php esc_attr_e('Popular guide shortcuts', 'bybookishbabe-shopify-port'); ?>">
						<?php foreach ($shortcut_items as $shortcut_item) : ?>
							<?php
							$linked_item = $shortcut_item['post'];
							if ((int) $linked_item->ID === (int) $post->ID) {
								continue;
							}
							$value = get_post_type($linked_item) . ':' . $linked_item->ID;
							$shortcut_values[$value] = true;
							?>
							<option value="<?php echo esc_attr($value); ?>" <?php selected(($selected[$index] ?? '') === $value); ?>>
								<?php echo esc_html(sprintf('%s - %s', (string) $shortcut_item['label'], get_the_title($linked_item))); ?>
							</option>
						<?php endforeach; ?>
					</optgroup>
				<?php endif; ?>
				<optgroup label="<?php esc_attr_e('Posts and guides', 'bybookishbabe-shopify-port'); ?>">
					<?php foreach ($posts as $linked_post) : ?>
						<?php
						if ((int) $linked_post->ID === (int) $post->ID) {
							continue;
						}
						$value = 'post:' . $linked_post->ID;
						if (isset($shortcut_values[$value])) {
							continue;
						}
						?>
						<option value="<?php echo esc_attr($value); ?>" <?php selected(($selected[$index] ?? '') === $value); ?>>
							<?php echo esc_html(get_the_title($linked_post)); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
				<optgroup label="<?php esc_attr_e('Pages', 'bybookishbabe-shopify-port'); ?>">
					<?php foreach ($pages as $linked_page) : ?>
						<?php $value = 'page:' . $linked_page->ID; ?>
						<option value="<?php echo esc_attr($value); ?>" <?php selected(($selected[$index] ?? '') === $value); ?>>
							<?php echo esc_html(get_the_title($linked_page)); ?>
						</option>
					<?php endforeach; ?>
				</optgroup>
			</select>
		</p>
	<?php endfor; ?>
	<p class="description">
		<?php esc_html_e('These render in this order as "the society recommends".', 'bybookishbabe-shopify-port'); ?>
	</p>
	<script>
		(function () {
			var box = document.getElementById('bbb_society_recommendations');
			if (!box) {
				return;
			}

			box.addEventListener('click', function (event) {
				var button = event.target.closest('.bbb-society-recommendation-quick-pick');
				if (!button) {
					return;
				}

				var value = button.getAttribute('data-bbb-recommendation-value');
				var selects = Array.prototype.slice.call(box.querySelectorAll('select[name="bbb_society_recommendations[]"]'));
				var selected = selects.some(function (select) {
					return select.value === value;
				});

				if (selected) {
					return;
				}

				var target = selects.find(function (select) {
					return !select.value;
				}) || selects[0];
				if (target) {
					target.value = value;
					target.dispatchEvent(new Event('change', {bubbles: true}));
				}
			});
		}());
	</script>
	<?php
}

function bbb_society_recommendations_save_post(int $post_id): void {
	if (!isset($_POST['bbb_society_recommendations_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['bbb_society_recommendations_nonce']), 'bbb_society_recommendations_save')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$raw_values = isset($_POST['bbb_society_recommendations']) && is_array($_POST['bbb_society_recommendations'])
		? wp_unslash($_POST['bbb_society_recommendations'])
		: array();

	$values = array();
	foreach ($raw_values as $raw_value) {
		if (!is_string($raw_value) || !preg_match('/^(post|page):(\d+)$/', $raw_value, $matches)) {
			continue;
		}

		$linked_id   = absint($matches[2]);
		$linked_type = $matches[1];
		if (!$linked_id || !get_post_status($linked_id)) {
			continue;
		}

		if ($linked_type !== get_post_type($linked_id) || (int) $linked_id === $post_id) {
			continue;
		}

		$values[] = $linked_type . ':' . $linked_id;
	}

	$values = array_slice(array_values(array_unique($values)), 0, 3);
	if ($values) {
		update_post_meta($post_id, bbb_society_recommendation_meta_key(), $values);
	} else {
		delete_post_meta($post_id, bbb_society_recommendation_meta_key());
	}

	for ($index = 1; $index <= 3; $index++) {
		if (isset($values[$index - 1])) {
			update_post_meta($post_id, '_bbb_society_recommendation_' . $index, $values[$index - 1]);
		} else {
			delete_post_meta($post_id, '_bbb_society_recommendation_' . $index);
		}
	}
}
add_action('save_post_post', 'bbb_society_recommendations_save_post');

function bbb_society_recommendations_items(int $post_id): array {
	$items = array();

	foreach (bbb_society_recommendation_selected($post_id) as $value) {
		if (!preg_match('/^(post|page):(\d+)$/', $value, $matches)) {
			continue;
		}

		$linked_id = absint($matches[2]);
		$item      = $linked_id ? get_post($linked_id) : null;
		if (!$item || 'publish' !== get_post_status($item) || !in_array($item->post_type, array('post', 'page'), true)) {
			continue;
		}

		if ((int) $item->ID === $post_id) {
			continue;
		}

		$items[] = $item;
	}

	return array_slice($items, 0, 3);
}

function bbb_society_recommendation_icon(WP_Post $item): string {
	$slug  = sanitize_title((string) $item->post_name);
	$title = sanitize_title(get_the_title($item));

	if (str_contains($slug, 'what-to-read-next') || str_contains($title, 'what-to-read-next')) {
		return '📖';
	}

	if ('library' === $slug || str_contains($slug, 'library') || 'library' === $title || str_contains($title, 'library')) {
		return '📚';
	}

	if (str_contains($slug, 'spice') || str_contains($title, 'spice')) {
		return '🌶';
	}

	return 'post' === $item->post_type ? '📖' : '↗';
}

function bbb_render_society_recommendations(int $post_id): string {
	$items = bbb_society_recommendations_items($post_id);
	if (!$items) {
		return '';
	}

	ob_start();
	?>
	<section class="sss-you-might-like page-width page-width--narrow js-you-might-like" aria-labelledby="bbb-society-recommendations-title">
		<p class="sss-you-might-like__kicker"><?php esc_html_e('continue exploring', 'bybookishbabe-shopify-port'); ?></p>
		<h2 id="bbb-society-recommendations-title" class="sss-you-might-like__title">
			<?php esc_html_e('the society recommends', 'bybookishbabe-shopify-port'); ?>
		</h2>
			<div class="sss-you-might-like__grid">
				<?php foreach ($items as $item) : ?>
					<?php
					$item_title        = get_the_title($item);
					$custom_emoji_html = function_exists('bbb_custom_emoji_html') ? bbb_custom_emoji_html($item_title, $item->post_name, 'sss-you-might-like__customEmoji') : '';
					$fallback_emoji    = bbb_society_recommendation_icon($item);
					?>
					<a href="<?php echo esc_url(get_permalink($item)); ?>" class="sss-you-might-like__item<?php echo 'post' === $item->post_type ? ' sss-you-might-like__guide' : ''; ?>">
						<span class="sss-you-might-like__emoji" aria-hidden="true"><?php echo '' !== $custom_emoji_html ? $custom_emoji_html : esc_html($fallback_emoji); ?></span>
						<span class="sss-you-might-like__name"><?php echo esc_html($item_title); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
	</section>
	<?php

	return (string) ob_get_clean();
}
