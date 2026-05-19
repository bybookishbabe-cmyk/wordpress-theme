<?php
/**
 * Article book card wrapper.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$post_id  = (int) ($args['post_id'] ?? 0);
$show_why = (bool) ($args['show_why'] ?? false);

if (!$post_id) {
	return;
}

echo bbb_render_article_book_card($post_id, $show_why);
