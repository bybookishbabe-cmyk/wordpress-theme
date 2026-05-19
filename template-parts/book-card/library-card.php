<?php
/**
 * Library book card wrapper.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$post_id = (int) ($args['post_id'] ?? 0);
$mini    = (bool) ($args['mini'] ?? false);

if (!$post_id) {
	return;
}

echo bbb_render_library_book_card($post_id, $mini);
