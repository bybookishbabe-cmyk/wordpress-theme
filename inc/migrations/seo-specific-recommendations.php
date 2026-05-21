<?php
/**
 * One-time admin migration for Rank Math SEO and society recommendations.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

const BBB_SEO_SPECIFIC_MIGRATION_VERSION = '2026-05-21-seo-specific-v1';

function bbb_seo_specific_migration_payload_path(): string {
	return get_theme_file_path('inc/migrations/data/bookishbabe-seo-specific-map.json');
}

function bbb_seo_specific_migration_norm_title(string $value): string {
	$value = strtolower($value);
	$value = (string) preg_replace('/[^a-z0-9]+/', ' ', $value);

	return trim((string) preg_replace('/\s+/', ' ', $value));
}

function bbb_seo_specific_migration_find_source(array $row): ?WP_Post {
	$title = (string) ($row['title'] ?? '');
	$slug  = (string) ($row['slug'] ?? '');

	$candidates = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page' => 10,
			's'              => $title,
		)
	);

	foreach ($candidates as $candidate) {
		if (bbb_seo_specific_migration_norm_title((string) $candidate->post_title) === bbb_seo_specific_migration_norm_title($title)) {
			return $candidate;
		}
	}

	$by_slug = $slug ? get_page_by_path($slug, OBJECT, 'post') : null;

	return $by_slug instanceof WP_Post ? $by_slug : null;
}

function bbb_seo_specific_migration_find_seo_items(array $row): array {
	$title = (string) ($row['title'] ?? '');
	$slug  = (string) ($row['slug'] ?? '');
	$items = array();

	foreach (array('post', 'page') as $post_type) {
		$by_slug = $slug ? get_page_by_path($slug, OBJECT, $post_type) : null;
		if ($by_slug instanceof WP_Post) {
			$items[(int) $by_slug->ID] = $by_slug;
		}
	}

	$candidates = get_posts(
		array(
			'post_type'      => array('post', 'page'),
			'post_status'    => array('publish', 'draft', 'pending', 'private', 'future'),
			'posts_per_page' => 20,
			's'              => $title,
		)
	);

	foreach ($candidates as $candidate) {
		if (bbb_seo_specific_migration_norm_title((string) $candidate->post_title) === bbb_seo_specific_migration_norm_title($title)) {
			$items[(int) $candidate->ID] = $candidate;
		}
	}

	return array_values($items);
}

function bbb_seo_specific_migration_find_target(array $row): ?WP_Post {
	$slug = (string) ($row['slug'] ?? '');

	if ('PILLAR' === ($row['type'] ?? '') && $slug) {
		$page = get_page_by_path($slug, OBJECT, 'page');
		if ($page instanceof WP_Post && 'publish' === get_post_status($page->ID)) {
			return $page;
		}
	}

	$post = bbb_seo_specific_migration_find_source($row);
	if ($post instanceof WP_Post && 'publish' === get_post_status($post->ID)) {
		return $post;
	}

	$page = $slug ? get_page_by_path($slug, OBJECT, 'page') : null;
	if ($page instanceof WP_Post && 'publish' === get_post_status($page->ID)) {
		return $page;
	}

	return null;
}

function bbb_seo_specific_migration_run(): array {
	$path = bbb_seo_specific_migration_payload_path();
	if (!is_readable($path)) {
		return array(
			'updated'         => 0,
			'missing_sources' => array('payload file missing'),
			'missing_targets' => array(),
		);
	}

	$rows = json_decode((string) file_get_contents($path), true);
	if (!is_array($rows)) {
		return array(
			'updated'         => 0,
			'missing_sources' => array('payload json invalid'),
			'missing_targets' => array(),
		);
	}

	$by_row = array();
	foreach ($rows as $row) {
		if (is_array($row) && isset($row['row'])) {
			$by_row[(int) $row['row']] = $row;
		}
	}

	$updated         = 0;
	$seo_updated     = 0;
	$missing_sources = array();
	$missing_targets = array();
	$missing_seo     = array();

	foreach ($rows as $row) {
		if (!is_array($row)) {
			continue;
		}

		$seo_items = bbb_seo_specific_migration_find_seo_items($row);
		if (!$seo_items) {
			$missing_seo[] = sprintf('%s:%s', (string) ($row['row'] ?? '?'), (string) ($row['title'] ?? ''));
		}

		foreach ($seo_items as $seo_item) {
			update_post_meta($seo_item->ID, 'rank_math_title', (string) ($row['seo_title'] ?? ''));
			update_post_meta($seo_item->ID, 'rank_math_description', (string) ($row['description'] ?? ''));
			update_post_meta($seo_item->ID, 'rank_math_focus_keyword', (string) ($row['focus_keyword'] ?? ''));
			$seo_updated++;
		}

		$source = bbb_seo_specific_migration_find_source($row);
		if (!$source instanceof WP_Post) {
			$missing_sources[] = sprintf('%s:%s', (string) ($row['row'] ?? '?'), (string) ($row['title'] ?? ''));
			continue;
		}

		$values = array();
		foreach ((array) ($row['recommendation_target_rows'] ?? array()) as $target_row) {
			$target_row = (int) $target_row;
			if (!isset($by_row[$target_row])) {
				continue;
			}

			$target = bbb_seo_specific_migration_find_target($by_row[$target_row]);
			if (!$target instanceof WP_Post) {
				$missing_targets[] = sprintf('%s->%s', (string) ($row['row'] ?? '?'), (string) $target_row);
				continue;
			}

			if ((int) $target->ID === (int) $source->ID) {
				continue;
			}

			$values[] = get_post_type($target->ID) . ':' . $target->ID;
		}

		$values = array_slice(array_values(array_unique($values)), 0, 3);
		if ($values) {
			update_post_meta($source->ID, '_bbb_society_recommendations', $values);
		} else {
			delete_post_meta($source->ID, '_bbb_society_recommendations');
		}

		for ($index = 1; $index <= 3; $index++) {
			if (isset($values[$index - 1])) {
				update_post_meta($source->ID, '_bbb_society_recommendation_' . $index, $values[$index - 1]);
			} else {
				delete_post_meta($source->ID, '_bbb_society_recommendation_' . $index);
			}
		}

		$updated++;
	}

	$result = array(
		'updated'         => $updated,
		'seo_updated'     => $seo_updated,
		'missing_sources' => $missing_sources,
		'missing_targets' => $missing_targets,
		'missing_seo'     => $missing_seo,
		'ran_at'          => current_time('mysql'),
	);

	update_option('bbb_seo_specific_migration_result', $result, false);

	return $result;
}

function bbb_seo_specific_migration_maybe_run(): void {
	if (!is_admin() || !current_user_can('manage_options')) {
		return;
	}

	if (BBB_SEO_SPECIFIC_MIGRATION_VERSION === get_option('bbb_seo_specific_migration_version')) {
		return;
	}

	$result = bbb_seo_specific_migration_run();
	if (!empty($result['updated']) || !empty($result['seo_updated'])) {
		update_option('bbb_seo_specific_migration_version', BBB_SEO_SPECIFIC_MIGRATION_VERSION, false);
	}
}
add_action('admin_init', 'bbb_seo_specific_migration_maybe_run', 20);

function bbb_seo_specific_migration_admin_notice(): void {
	if (!is_admin() || !current_user_can('manage_options')) {
		return;
	}

	$result = get_option('bbb_seo_specific_migration_result');
	if (!is_array($result) || empty($result['updated'])) {
		return;
	}

	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html(
			sprintf(
				'ByBookishBabe SEO/recommendation migration ran: %d recommendation posts updated, %d SEO records updated.',
				(int) $result['updated'],
				(int) ($result['seo_updated'] ?? 0)
			)
		)
	);
}
add_action('admin_notices', 'bbb_seo_specific_migration_admin_notice');
