<?php
/**
 * Virtual/template-routed Shopify page.
 *
 * @package WordPressTheme
 */

declare(strict_types=1);

$route_slug = (string) get_query_var('bbb_shopify_page');
if (!$route_slug && is_page()) {
	$route_slug = (string) get_post_field('post_name', get_queried_object_id());
}

echo do_blocks('<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /-->');
echo bbb_render_shopify_page_template($route_slug); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo do_blocks('<!-- wp:template-part {"slug":"footer","tagName":"footer","className":"site-footer"} /-->');
