<?php
/**
 * Bookshelf signup modal bridge.
 *
 * @package ByBookishBabeShopifyPort
 */

$source = get_theme_file_path('snippets/bookshelf-signup-modal.liquid');
if (current_user_can('manage_options') && file_exists($source)) {
	echo "\n<!-- bookshelf-signup-modal.liquid is staged for direct PHP conversion. -->\n";
}
