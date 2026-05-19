<?php
/**
 * Library modal bridge.
 *
 * @package ByBookishBabeShopifyPort
 */

$source = get_theme_file_path('snippets/sss-library-modal.liquid');
if (current_user_can('manage_options') && file_exists($source)) {
	echo "\n<!-- sss-library-modal.liquid is staged for direct PHP conversion. -->\n";
}
