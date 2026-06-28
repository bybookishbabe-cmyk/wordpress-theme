<?php
/**
 * Single SSS series template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$series_handle = sanitize_title((string) get_post_field('post_name', get_queried_object_id()));
if ('' !== $series_handle) {
	set_query_var('bbb_series_handle', $series_handle);
}

require get_theme_file_path('page-series.php');
