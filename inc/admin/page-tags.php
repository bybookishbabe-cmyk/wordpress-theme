<?php
/**
 * Page tagging helpers for admin organization.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_enable_tags_for_pages(): void {
	register_taxonomy_for_object_type('post_tag', 'page');
}
add_action('init', 'bbb_enable_tags_for_pages');

function bbb_page_tag_filter_dropdown(string $post_type): void {
	if ('page' !== $post_type || !taxonomy_exists('post_tag')) {
		return;
	}

	$selected = isset($_GET['bbb_page_tag']) ? sanitize_title((string) wp_unslash($_GET['bbb_page_tag'])) : '';

	wp_dropdown_categories(
		array(
			'taxonomy'          => 'post_tag',
			'name'              => 'bbb_page_tag',
			'id'                => 'bbb-page-tag-filter',
			'show_option_all'   => __('all page tags', 'bybookishbabe-shopify-port'),
			'hide_empty'        => false,
			'hierarchical'      => false,
			'orderby'           => 'name',
			'selected'          => $selected,
			'value_field'       => 'slug',
			'show_count'        => true,
			'class'             => 'postform',
		)
	);
}
add_action('restrict_manage_posts', 'bbb_page_tag_filter_dropdown');

function bbb_filter_pages_by_tag(WP_Query $query): void {
	if (!is_admin() || !$query->is_main_query()) {
		return;
	}

	global $pagenow;
	if ('edit.php' !== $pagenow || 'page' !== (string) $query->get('post_type')) {
		return;
	}

	$tag = isset($_GET['bbb_page_tag']) ? sanitize_title((string) wp_unslash($_GET['bbb_page_tag'])) : '';
	if ('' === $tag) {
		return;
	}

	$query->set(
		'tax_query',
		array(
			array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => $tag,
			),
		)
	);
}
add_action('pre_get_posts', 'bbb_filter_pages_by_tag');

function bbb_page_tags_admin_column(array $columns): array {
	$updated = array();

	foreach ($columns as $key => $label) {
		$updated[$key] = $label;
		if ('title' === $key) {
			$updated['bbb_page_tags'] = __('tags', 'bybookishbabe-shopify-port');
		}
	}

	return $updated;
}
add_filter('manage_pages_columns', 'bbb_page_tags_admin_column');

function bbb_page_tags_admin_column_content(string $column_name, int $post_id): void {
	if ('bbb_page_tags' !== $column_name) {
		return;
	}

	$terms = get_the_terms($post_id, 'post_tag');
	if (empty($terms) || is_wp_error($terms)) {
		echo '<span aria-hidden="true">-</span>';
		return;
	}

	$links = array();
	foreach ($terms as $term) {
		if (!$term instanceof WP_Term) {
			continue;
		}

		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url(add_query_arg(array('post_type' => 'page', 'bbb_page_tag' => $term->slug), admin_url('edit.php'))),
			esc_html($term->name)
		);
	}

	echo wp_kses_post(implode(', ', $links));
}
add_action('manage_pages_custom_column', 'bbb_page_tags_admin_column_content', 10, 2);
