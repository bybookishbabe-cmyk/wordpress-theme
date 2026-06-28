<?php
/**
 * Shared policy-page metadata.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_policy_website_label(): string {
	return 'bybookishbabe.com';
}

function bbb_policy_current_post_id(): int {
	$post_id = get_the_ID();

	return $post_id ? (int) $post_id : 0;
}

function bbb_policy_last_updated_label(?int $post_id = null): string {
	$post_id = null === $post_id ? bbb_policy_current_post_id() : $post_id;

	if ($post_id > 0) {
		$modified = get_the_modified_date('F j, Y', $post_id);
		if (is_string($modified) && '' !== trim($modified)) {
			return $modified;
		}
	}

	return date_i18n('F j, Y');
}

function bbb_policy_last_updated_iso(?int $post_id = null): string {
	$post_id = null === $post_id ? bbb_policy_current_post_id() : $post_id;

	if ($post_id > 0) {
		$modified = get_post_modified_time('c', false, $post_id);
		if (is_string($modified) && '' !== trim($modified)) {
			return $modified;
		}
	}

	return current_time('c');
}

function bbb_render_policy_meta(string $class_name = 'bbb-policy-meta'): void {
	$post_id = bbb_policy_current_post_id();
	?>
	<div class="<?php echo esc_attr($class_name); ?>" aria-label="policy details">
		<p><span>Website:</span> <?php echo esc_html(bbb_policy_website_label()); ?></p>
		<p><span>Last updated:</span> <time datetime="<?php echo esc_attr(bbb_policy_last_updated_iso($post_id)); ?>"><?php echo esc_html(bbb_policy_last_updated_label($post_id)); ?></time></p>
	</div>
	<?php
}
