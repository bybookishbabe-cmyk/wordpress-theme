<?php
/**
 * Admin list table improvements for posts.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

/**
 * Default the Posts admin screen to the most recently edited posts.
 */
function bbb_admin_posts_order_by_modified(WP_Query $query): void {
	if (!is_admin() || !$query->is_main_query()) {
		return;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || 'edit-post' !== $screen->id) {
		return;
	}

	if ('' !== (string) $query->get('orderby')) {
		return;
	}

	$query->set('orderby', 'modified');
	$query->set('order', 'DESC');
}
add_action('pre_get_posts', 'bbb_admin_posts_order_by_modified');

/**
 * Keep the Posts admin search focused on titles instead of broad content matches.
 */
function bbb_admin_posts_search_titles_only(string $search, WP_Query $query): string {
	if (!is_admin() || !$query->is_main_query()) {
		return $search;
	}

	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if (!$screen || 'edit-post' !== $screen->id) {
		return $search;
	}

	$raw_search = trim((string) $query->get('s'));
	if ('' === $raw_search) {
		return $search;
	}

	$terms = bbb_admin_posts_title_search_terms($raw_search);
	if (empty($terms)) {
		return $search;
	}

	global $wpdb;

	$title_clauses = array();
	foreach ($terms as $term) {
		$title_clauses[] = $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like($term) . '%');
	}

	return ' AND (' . implode(' AND ', $title_clauses) . ')';
}
add_filter('posts_search', 'bbb_admin_posts_search_titles_only', 20, 2);

/**
 * Split admin search text into words, while keeping quoted phrases together.
 *
 * @return string[]
 */
function bbb_admin_posts_title_search_terms(string $search): array {
	$terms = array();

	if (preg_match_all('/"([^"]+)"|(\S+)/', $search, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$term = isset($match[1]) && '' !== $match[1] ? $match[1] : $match[2];
			$term = trim($term);

			if ('' !== $term) {
				$terms[] = $term;
			}
		}
	}

	return array_values(array_unique($terms));
}

/**
 * Add a visible last-edited column beside the normal publish date.
 */
function bbb_admin_posts_last_edited_column(array $columns): array {
	$updated = array();

	foreach ($columns as $key => $label) {
		if ('date' === $key) {
			$updated['bbb_last_edited'] = __('Last edited', 'bybookishbabe-shopify-port');
		}

		$updated[$key] = $label;
	}

	return $updated;
}
add_filter('manage_post_posts_columns', 'bbb_admin_posts_last_edited_column');

/**
 * Render the last-edited timestamp for each post row.
 */
function bbb_admin_posts_last_edited_column_content(string $column, int $post_id): void {
	if ('bbb_last_edited' !== $column) {
		return;
	}

	printf(
		'<span class="bbb-admin-last-edited">%s</span><br><span class="bbb-admin-last-edited__time">%s</span>',
		esc_html__('Edited', 'bybookishbabe-shopify-port'),
		esc_html(get_post_modified_time('Y/m/d \a\t g:i a', false, $post_id))
	);
}
add_action('manage_post_posts_custom_column', 'bbb_admin_posts_last_edited_column_content', 10, 2);

/**
 * Let the Last edited column stay clickable/sortable.
 */
function bbb_admin_posts_last_edited_sortable(array $columns): array {
	$columns['bbb_last_edited'] = 'modified';

	return $columns;
}
add_filter('manage_edit-post_sortable_columns', 'bbb_admin_posts_last_edited_sortable');
