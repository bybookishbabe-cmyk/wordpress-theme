<?php
/**
 * Production stability controls.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

/**
 * Disable background updates that can leave production stuck in maintenance mode.
 */
function bbb_disable_background_updates(): void {
	add_filter('automatic_updater_disabled', '__return_true');
	add_filter('auto_update_core', '__return_false');
	add_filter('auto_update_plugin', '__return_false');
	add_filter('auto_update_theme', '__return_false');
	add_filter('allow_dev_auto_core_updates', '__return_false');
	add_filter('allow_minor_auto_core_updates', '__return_false');
	add_filter('allow_major_auto_core_updates', '__return_false');
}
bbb_disable_background_updates();
